<?php
ob_start();
ini_set('display_errors', '0');
header('Content-Type: application/json; charset=utf-8');

// Garante que qualquer erro fatal retorne JSON (evita "Unexpected end of JSON input")
register_shutdown_function(function () {
    $err = error_get_last();
    if ($err && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        if (!headers_sent()) {
            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
        }
        error_log("transformarOrcamento: {$err['message']} em {$err['file']}:{$err['line']}");
        echo json_encode([
            'status' => 'error',
            'error'  => 'Erro interno: ' . $err['message'] . ' (linha ' . $err['line'] . ')',
        ], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    }
});

require_once dirname(__DIR__, 3) . '/logger.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$usuario = (string)($_SESSION["usuario"]["nome"] ?? 'desconhecido');

function funcaoDisponivel(string $fn): bool
{
    if (!function_exists($fn)) {
        return false;
    }
    $desativadas = array_map('trim', explode(',', (string)ini_get('disable_functions')));
    return !in_array($fn, $desativadas, true);
}

if (!isset($_FILES['pdfFile']) || $_FILES['pdfFile']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['status' => 'error', 'error' => 'Nenhum arquivo PDF foi enviado ou ocorreu um erro no envio.']);
    exit();
}

$tmpFile = $_FILES['pdfFile']['tmp_name'];

// 1. Extração do texto usando pdftotext (mantém layout) ou Smalot\PdfParser
$texto = '';
if (funcaoDisponivel('shell_exec') && (@is_executable('/usr/bin/pdftotext') || shell_exec('which pdftotext 2>/dev/null'))) {
    $saida = shell_exec('pdftotext -layout ' . escapeshellarg($tmpFile) . ' 2>/dev/null -');
    if (!empty(trim((string)$saida))) {
        $texto = (string)$saida;
    }
}

if (empty(trim($texto))) {
    $autoload = dirname(__DIR__, 3) . '/vendor/autoload.php';
    if (file_exists($autoload)) {
        require_once $autoload;
        if (class_exists('\\Smalot\\PdfParser\\Parser')) {
            try {
                $parser = new \Smalot\PdfParser\Parser();
                $pdf = $parser->parseFile($tmpFile);
                $texto = $pdf->getText();
            } catch (\Throwable $e) {
                error_log('transformarOrcamento PdfParser: ' . $e->getMessage());
            }
        }
    }
}

$texto = str_replace("\xc2\xa0", ' ', $texto);

if (empty(trim($texto))) {
    echo json_encode(['status' => 'error', 'error' => 'Não foi possível ler o texto do PDF informado.']);
    exit();
}

// 2. Extrai cabeçalho simples
$numeroOrcamento = '';
if (preg_match('/ORCAMENTO\s+DE\s+VENDA\s+N[º°\x{ba}\x{b0}]?\s*:\s*([0-9]+)/iu', $texto, $m)) {
    $numeroOrcamento = trim($m[1]);
    if (!empty($numeroOrcamento)) {
        $numeroOrcamento = '0' . (int)$numeroOrcamento;
    }
}

$cliente = '';
if (preg_match('/Cliente[\.\s]*:\s*([^\n\r]+)/iu', $texto, $m)) {
    $val = trim($m[1]);
    if (!empty($val) && stripos($val, 'CNPJ') === false) {
        if (preg_match('/^(\d+)(.*)$/', $val, $cMatch)) {
            $cliente = '0' . (int)$cMatch[1] . $cMatch[2];
        } else {
            $cliente = $val;
        }
    }
}

// 3. Extrai itens
$lines = explode("\n", $texto);
$itens = [];
$totalOrcamento = '0,00';
$inTable = false;

foreach ($lines as $line) {
    $trimmed = trim($line);
    if ($trimmed === '') continue;

    if (preg_match('/^Item\s+Qtde\s+UM\s+Descricao/i', $trimmed)) {
        $inTable = true;
        continue;
    }

    if (preg_match('/TOTAL\s+DO\s+ORCAMENTO[\.\s]*:\s*R?\\$\s*([\d\.,]+)/iu', $trimmed, $m)) {
        $totalOrcamento = trim($m[1]);
        $inTable = false;
        continue;
    }

    // Linha do item: Código | Quantidade | UM | Descrição | Unitário | Total | %IPI (opcional)
    if (preg_match('/^\s*([A-Za-z0-9\.\s\/-]+?)\s+(\d+(?:,\d{2})?)\s*([A-Z]{2,4})\s+(.+?)\s+([\d\.]+(?:,\d{2}))\s+([\d\.]+(?:,\d{2}))(?:\s+(\d+(?:,\d{2})?%))?\s*$/u', $line, $m)) {
        $itens[] = [
            'codigo'     => trim($m[1]),
            'quantidade' => trim($m[2]),
            'unidade'    => trim($m[3]),
            'descricao'  => trim($m[4]),
            'unitario'   => trim($m[5]),
            'total'      => trim($m[6]),
        ];
        continue;
    }

    // Linha de continuação de descrição
    if ($inTable && !empty($itens) && !preg_match('/\d+,\d{2}/', $trimmed) && !preg_match('/Total|Peso|OBSERV/i', $trimmed)) {
        $itens[count($itens) - 1]['descricao'] .= ' ' . $trimmed;
    }
}

if (empty($itens)) {
    echo json_encode(['status' => 'error', 'error' => 'Nenhum item encontrado no orçamento enviado.']);
    exit();
}

// 4. Se o total não veio no texto, calcula pela soma dos itens
if ($totalOrcamento === '0,00' || empty($totalOrcamento)) {
    $soma = 0.0;
    foreach ($itens as $it) {
        $val = (float)str_replace(['.', ','], ['', '.'], $it['total']);
        $soma += $val;
    }
    $totalOrcamento = number_format($soma, 2, ',', '.');
}

// Função de normalização para ordenação alfabética consistente em português
function normalizarTextoParaOrdenacao(string $str): string
{
    $str = function_exists('mb_strtolower') ? mb_strtolower(trim($str), 'UTF-8') : strtolower(trim($str));
    $mapa = [
        'á' => 'a',
        'à' => 'a',
        'ã' => 'a',
        'â' => 'a',
        'ä' => 'a',
        'é' => 'e',
        'è' => 'e',
        'ê' => 'e',
        'ë' => 'e',
        'í' => 'i',
        'ì' => 'i',
        'î' => 'i',
        'ï' => 'i',
        'ó' => 'o',
        'ò' => 'o',
        'õ' => 'o',
        'ô' => 'o',
        'ö' => 'o',
        'ú' => 'u',
        'ù' => 'u',
        'û' => 'u',
        'ü' => 'u',
        'ç' => 'c',
        'ñ' => 'n'
    ];
    return strtr($str, $mapa);
}

// 5. Ordena os itens em ordem alfabética real pela descrição
usort($itens, function ($a, $b) {
    $normA = normalizarTextoParaOrdenacao($a['descricao'] ?? '');
    $normB = normalizarTextoParaOrdenacao($b['descricao'] ?? '');
    $cmp = strcmp($normA, $normB);
    if ($cmp !== 0) {
        return $cmp;
    }
    return strcasecmp($a['descricao'] ?? '', $b['descricao'] ?? '');
});

// 6. Registra no log do sistema
$detalhesLog = "Importou o orçamento";
if (!empty($numeroOrcamento)) {
    $detalhesLog .= " Nº {$numeroOrcamento}";
}
if (!empty($cliente)) {
    $detalhesLog .= " - {$cliente}";
}
$detalhesLog .= " (" . count($itens) . " itens, Total R$ {$totalOrcamento})";

try {
    registrarLog($usuario, 'faturas', 'orcamento', 'importar', $detalhesLog);
} catch (\Throwable $e) {
    error_log('transformarOrcamento log: ' . $e->getMessage());
}

ob_end_clean();

// Retorna os dados limpos e diretos
echo json_encode([
    'status'          => 'success',
    'numeroOrcamento' => $numeroOrcamento,
    'cliente'         => $cliente,
    'totalGeral'      => $totalOrcamento,
    'itens'           => $itens,
], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
