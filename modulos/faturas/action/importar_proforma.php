<?php
ob_start();
ini_set('display_errors', '0');
header('Content-Type: application/json; charset=utf-8');

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
        echo json_encode([
            'status' => 'error',
            'error'  => 'Erro interno: ' . $err['message'] . ' (linha ' . $err['line'] . ')',
        ], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    }
});

require_once dirname(__DIR__, 3) . '/logger.php';

if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}
$usuario = (string)($_SESSION["usuario"]["nome"] ?? 'desconhecido');

if (!isset($_FILES['pdfFile']) || $_FILES['pdfFile']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['status' => 'error', 'error' => 'Nenhum arquivo PDF foi enviado ou ocorreu um erro no envio.']);
    exit();
}

$tmpFile = $_FILES['pdfFile']['tmp_name'];
$nomeOriginal = $_FILES['pdfFile']['name'] ?? 'proforma.pdf';

function funcaoDisponivel(string $fn): bool {
    if (!function_exists($fn)) return false;
    $desativadas = array_map('trim', explode(',', (string)ini_get('disable_functions')));
    return !in_array($fn, $desativadas, true);
}

// 1. Extração de texto via pdftotext
$hasPdftotext = funcaoDisponivel('shell_exec') && (@is_executable('/usr/bin/pdftotext') || shell_exec('which pdftotext 2>/dev/null'));

$textoLayout = '';
$bboxXml = '';

if ($hasPdftotext) {
    $textoLayout = (string)shell_exec('pdftotext -layout ' . escapeshellarg($tmpFile) . ' 2>/dev/null -');
    $bboxXml = (string)shell_exec('pdftotext -bbox-layout ' . escapeshellarg($tmpFile) . ' 2>/dev/null -');
}

if (empty(trim($textoLayout))) {
    $autoload = dirname(__DIR__, 3) . '/vendor/autoload.php';
    if (file_exists($autoload)) {
        require_once $autoload;
        if (class_exists('\\Smalot\\PdfParser\\Parser')) {
            try {
                $parser = new \Smalot\PdfParser\Parser();
                $pdf = $parser->parseFile($tmpFile);
                $textoLayout = $pdf->getText();
            } catch (\Throwable $e) {
                error_log('importar_proforma PdfParser: ' . $e->getMessage());
            }
        }
    }
}

if (empty(trim($textoLayout))) {
    echo json_encode(['status' => 'error', 'error' => 'Não foi possível ler o conteúdo do PDF informado.']);
    exit();
}

// Inicializa estrutura padrão completa
$data = [
    "cabecalho" => [
        "empresa_endereco" => "BRAZMIX COMERCIAL EXPORTADORA DE BOMBAS PARA MINERAÇÃO LTDA RUA\nCAVALIERE AMBROGIO CIPOLLA, 562 - MARILAND, CEP 95057-000 - CAXIAS DO SUL - RS -\nBRASIL -PHONE +55 54 3229 9365 - www.brazmix.com",
        "titulo" => "FATURA PROFORMA",
        "numero" => "",
        "emissao" => "",
        "validade" => "",
    ],
    "cliente" => [
        "importador" => "",
        "importador_endereco" => "",
        "cosignatario" => "",
        "notificado" => "",
    ],
    "detalhes_adicionais" => [
        "pais_origem" => "",
        "equipamento" => "",
        "modalidade_frete" => "",
        "lugar_embarque" => "",
        "lugar_transbordo" => "",
        "lugar_descarga" => "",
        "tempo_transito" => "",
    ],
    "detalhes_envio" => [
        "embalagem" => "",
        "peso_liquido" => "",
        "peso_bruto" => "",
        "cubagem" => "",
        "volumes" => "",
        "condicoes_venda" => "",
        "pagamento" => "",
    ],
    "itens" => [],
    "totais" => [
        "peso_liquido_total" => "",
        "preco_total" => "",
    ],
    "custos_internos" => [
        "itens" => [],
        "total" => "",
        "observacao" => "SEM CARGAS CONSOLIDADAS"
    ],
    "resumo_financeiro" => [
        "custos_internos" => "",
        "frete" => "-",
        "seguro" => "-",
        "desconto" => "-",
        "total_geral" => "",
    ],
    "instrucoes_credito" => [
        "beneficiario" => "BRAZMIX COMERCIAL EXPORTADORA DE BOMBAS PARA MINERAÇÃO LTDA",
        "conta" => "45216-5",
        "iban" => "BR4700000000028710000452165C1",
        "banco" => "BANCO DO BRASIL S.A. (SAO PAULO - SP)",
        "swift" => "BRASBRRJSBO",
        "correspondente" => "BANCO DO BRASIL - NEW YORK - NY - USA",
        "correspondente_swift" => "BRASUS33",
        "fedwire" => "FW0260000",
    ],
    "rodape" => [
        "info_arquivo" => "Atualizado em " . date('d/m/Y H:i') . " - Arquivo: " . $nomeOriginal,
        "pagina" => "Página 1 de 1"
    ]
];

// Parser via blocos de coordenadas se bboxXml disponível
$blocks = [];
if (!empty($bboxXml) && strpos($bboxXml, '<body>') !== false) {
    try {
        $start = strpos($bboxXml, '<body>');
        $end = strpos($bboxXml, '</body>') + 7;
        $body = substr($bboxXml, $start, $end - $start);
        $xml = new SimpleXMLElement($body);
        foreach ($xml->xpath('//block') as $b) {
            $lines = [];
            foreach ($b->line as $l) {
                $words = [];
                foreach ($l->word as $w) {
                    $words[] = (string)$w;
                }
                $lines[] = implode(' ', $words);
            }
            $blocks[] = [
                'xMin' => (float)$b['xMin'],
                'yMin' => (float)$b['yMin'],
                'xMax' => (float)$b['xMax'],
                'yMax' => (float)$b['yMax'],
                'text' => implode("\n", $lines),
            ];
        }
    } catch (\Throwable $e) {
        error_log('importar_proforma bboxXml parse: ' . $e->getMessage());
    }
}

// 1. Extração do Cabeçalho
if (preg_match('/DATA\s+DE\s+EMISS[AÃ]O:\s*([0-9\/]+)/iu', $textoLayout, $m)) {
    $data["cabecalho"]["emissao"] = trim($m[1]);
}
if (preg_match('/VALIDADE\s+DA\s+COTA[CÇ][AÃ]O:\s*([0-9\/]+)/iu', $textoLayout, $m)) {
    $data["cabecalho"]["validade"] = trim($m[1]);
}
if (preg_match('/(?:FATURA\s+PROFORMA[^\n]*\n(?:[^\n]*\n){0,3}\s*)(\b\d+-\d+\b)/u', $textoLayout, $m)) {
    $data["cabecalho"]["numero"] = trim($m[1]);
}

// 2. Extração de Itens da Tabela
$lines = explode("\n", $textoLayout);
foreach ($lines as $l) {
    if (preg_match('/^\s*(\d+)\s+(BM\s+[\d\.]+)\s+(\d{4}\.\d{2}\.\d{2})\s+(.+?)\s+(UN|MT|RL|JG|PR|M2|PC|PÇ)\s+(\d+)\s+([\d,\.]+)\s+USS\s+([\d,\.]+)\s+([\d,\.]+)\s+USS\s+([\d,\.]+)/u', $l, $m)) {
        $data["itens"][] = [
            "item" => (int)$m[1],
            "codigo" => trim($m[2]),
            "ncm" => trim($m[3]),
            "descricao" => trim($m[4]),
            "unidade" => trim($m[5]),
            "quantidade" => (int)$m[6],
            "peso_liq" => trim($m[7]),
            "preco_unitario" => trim($m[8]),
            "peso_liq_total" => trim($m[9]),
            "preco_total" => trim($m[10]),
        ];
    }
}

// 3. Totais da Tabela
if (preg_match('/TOTAIS\s+([\d\.,]+)\s+USS\s+([\d\.,]+)/iu', $textoLayout, $m)) {
    $data["totais"]["peso_liquido_total"] = trim($m[1]);
    $data["totais"]["preco_total"] = trim($m[2]);
}

// 4. Detalhes Adicionais e Envio via regex direta
if (preg_match('/Pa[ií]s de Origem:\s*([^\n\r]+?)(?=\s{4,}Embalagem:|\n)/iu', $textoLayout, $m)) {
    $data["detalhes_adicionais"]["pais_origem"] = trim($m[1]);
}
if (preg_match('/Equipamento:\s*([^\n\r]+?)(?=\s{4,}Peso L[ií]quido:|\n)/iu', $textoLayout, $m)) {
    $data["detalhes_adicionais"]["equipamento"] = trim($m[1]);
}
if (preg_match('/Modalidade de Frete:\s*([^\n\r]+?)(?=\s{4,}Peso Bruto:|\n)/iu', $textoLayout, $m)) {
    $data["detalhes_adicionais"]["modalidade_frete"] = trim($m[1]);
}
if (preg_match('/Lugar de embarque:\s*([^\n\r]+?)(?=\s{4,}Cubagem:|\n)/iu', $textoLayout, $m)) {
    $data["detalhes_adicionais"]["lugar_embarque"] = trim($m[1]);
}
if (preg_match('/Lugar de transbordo:\s*([^\n\r]+?)(?=\s{4,}Volumes:|\n)/iu', $textoLayout, $m)) {
    $data["detalhes_adicionais"]["lugar_transbordo"] = trim($m[1]);
}
if (preg_match('/Lugar de descarga:\s*([^\n\r]+?)(?=\s{2,}(?:Condi|Venda)|\n)/iu', $textoLayout, $m)) {
    $data["detalhes_adicionais"]["lugar_descarga"] = trim($m[1]);
}
if (preg_match('/Tempo de Tr[aâ]nsito:\s*([^\n\r]+?)(?=\s{4,}Pagamento:|\n)/iu', $textoLayout, $m)) {
    $data["detalhes_adicionais"]["tempo_transito"] = trim($m[1]);
}

if (preg_match('/Embalagem:\s*([^\n\r]+)/iu', $textoLayout, $m)) {
    $data["detalhes_envio"]["embalagem"] = trim($m[1]);
}
if (preg_match('/Peso L[ií]quido:\s*([^\n\r]+)/iu', $textoLayout, $m)) {
    $data["detalhes_envio"]["peso_liquido"] = trim($m[1]);
}
if (preg_match('/Peso Bruto:\s*([^\n\r]+)/iu', $textoLayout, $m)) {
    $data["detalhes_envio"]["peso_bruto"] = trim($m[1]);
}
if (preg_match('/Cubagem:\s*([^\n\r]+)/iu', $textoLayout, $m)) {
    $data["detalhes_envio"]["cubagem"] = trim($m[1]);
}
if (preg_match('/Volumes:\s*([^\n\r]+)/iu', $textoLayout, $m)) {
    $data["detalhes_envio"]["volumes"] = trim($m[1]);
}
if (preg_match('/Condi[cç][oõ]es de\s*(?:Venda:)?\s*([^\n\r]+)/iu', $textoLayout, $m)) {
    $data["detalhes_envio"]["condicoes_venda"] = trim($m[1]);
}
if (preg_match('/Pagamento:\s*([^\n\r]+)/iu', $textoLayout, $m)) {
    $data["detalhes_envio"]["pagamento"] = trim($m[1]);
}

// Informações do cliente
if (preg_match('/Importador:\s*\n\s*([^\n\r]+)\s*\n\s*([^\n\r]+(?:\n[^\n\r]+)?)\s*Cosignat[aá]rio:/iu', $textoLayout, $m)) {
    $data["cliente"]["importador"] = trim($m[1]);
    $data["cliente"]["importador_endereco"] = trim(preg_replace('/\s+/', ' ', $m[2]));
}
if (preg_match('/Cosignat[aá]rio:\s*\n\s*([^\n\r]+)\s*Notificado:/iu', $textoLayout, $m)) {
    $data["cliente"]["cosignatario"] = trim($m[1]);
}
if (preg_match('/Notificado:\s*([^\n\r]+?)(?=\s{4,}Tempo de Tr[aâ]nsito|\s{4,}\d|\n)/iu', $textoLayout, $m)) {
    $data["cliente"]["notificado"] = trim($m[1]);
}

// 5. Custos Internos e Resumo Financeiro
$custosNomes = [
    "Taxas Bancárias e Diferença Cambial:",
    "Desembaraço Aduaneiro:",
    "Embalagens e Certificado de Origem:",
    "Custos de Armazenagem:",
    "Fretes Terrestres:",
    "Estufagem:",
    "Taxas Portuárias:",
    "Taxas Armador:",
    "Remessa de Documentos:",
    "Manuseio, Presença de Carga e Cia Aérea"
];

// Valores padrão se não extraídos
$custosItens = [];
foreach ($custosNomes as $cnome) {
    $custosItens[] = ["nome" => $cnome, "moeda" => "USS", "valor" => "-"];
}

// Tenta extrair valores dos custos internos (não-guloso para pegar o primeiro USS da linha)
if (preg_match('/Taxas Banc[aá]rias[^\n\r]*?USS\s*([\d,\.\-]+)/iu', $textoLayout, $m)) $custosItens[0]["valor"] = trim($m[1]);
if (preg_match('/Desembara[cç]o Aduaneiro[^\n\r]*?USS\s*([\d,\.\-]+)/iu', $textoLayout, $m)) $custosItens[1]["valor"] = trim($m[1]);
if (preg_match('/Embalagens e Certificado[^\n\r]*?USS\s*([\d,\.\-]+)/iu', $textoLayout, $m)) $custosItens[2]["valor"] = trim($m[1]);
if (preg_match('/Custos de Armazenagem[^\n\r]*?USS\s*([\d,\.\-]+)/iu', $textoLayout, $m)) $custosItens[3]["valor"] = trim($m[1]);
if (preg_match('/Fretes Terrestres[^\n\r]*?USS\s*([\d,\.\-]+)/iu', $textoLayout, $m)) $custosItens[4]["valor"] = trim($m[1]);
if (preg_match('/Estufagem[^\n\r]*?USS\s*([\d,\.\-]+)/iu', $textoLayout, $m)) $custosItens[5]["valor"] = trim($m[1]);
if (preg_match('/Taxas Portu[aá]rias[^\n\r]*?USS\s*([\d,\.\-]+)/iu', $textoLayout, $m)) $custosItens[6]["valor"] = trim($m[1]);
if (preg_match('/Taxas Armador[^\n\r]*?USS\s*([\d,\.\-]+)/iu', $textoLayout, $m)) $custosItens[7]["valor"] = trim($m[1]);
if (preg_match('/Remessa de Documentos[^\n\r]*?USS\s*([\d,\.\-]+)/iu', $textoLayout, $m)) $custosItens[8]["valor"] = trim($m[1]);
if (preg_match('/Manuseio[^\n\r]*?USS\s*([\d,\.\-]+)/iu', $textoLayout, $m)) $custosItens[9]["valor"] = trim($m[1]);

$data["custos_internos"]["itens"] = $custosItens;

if (preg_match('/TOTAL DE CUSTOS INTERNOS\s*USS\s*([\d,\.]+)/iu', $textoLayout, $m)) {
    $data["custos_internos"]["total"] = trim($m[1]);
    $data["resumo_financeiro"]["custos_internos"] = trim($m[1]);
}

if (preg_match('/(?<!CUSTOS INTERNOS\s)\bTOTAL\s*(?:\.+|\s+)\s*USS\s*([\d,\.]+)/iu', $textoLayout, $m)) {
    $data["resumo_financeiro"]["total_geral"] = trim($m[1]);
}

if (preg_match('/(Atualizado em\s+[0-9\/]+\s+[0-9:]+\s*-\s*Arquivo:\s*[^\n\r]+)/iu', $textoLayout, $m)) {
    $data["rodape"]["info_arquivo"] = trim($m[1]);
}

// Se o JSON pré-existente tiver dados mais refinados para campos que não mudam, mescla
$jsonPath = __DIR__ . '/proforma.json';
if (file_exists($jsonPath)) {
    $atual = json_decode(file_get_contents($jsonPath), true);
    if (is_array($atual)) {
        // Preserva campos fixos se não detectados
        if (empty($data["cabecalho"]["numero"]) && !empty($atual["cabecalho"]["numero"])) {
            $data["cabecalho"]["numero"] = $atual["cabecalho"]["numero"];
        }
        if (empty($data["cliente"]["importador"]) && !empty($atual["cliente"]["importador"])) {
            $data["cliente"] = $atual["cliente"];
        }
        if (empty($data["detalhes_adicionais"]["pais_origem"]) && !empty($atual["detalhes_adicionais"]["pais_origem"])) {
            $data["detalhes_adicionais"] = $atual["detalhes_adicionais"];
        }
        if (empty($data["detalhes_envio"]["embalagem"]) && !empty($atual["detalhes_envio"]["embalagem"])) {
            $data["detalhes_envio"] = $atual["detalhes_envio"];
        }
    }
}

// Grava no arquivo proforma.json
$salvo = file_put_contents($jsonPath, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
if ($salvo === false) {
    echo json_encode(['status' => 'error', 'error' => 'Falha ao gravar arquivo proforma.json no servidor.']);
    exit();
}

try {
    registrarLog($usuario, 'faturas', 'proforma', 'importar', "Importou Fatura Proforma {$data['cabecalho']['numero']} (" . count($data['itens']) . " itens, Total USS {$data['resumo_financeiro']['total_geral']})");
} catch (\Throwable $e) {
    error_log('importar_proforma log: ' . $e->getMessage());
}

ob_end_clean();
echo json_encode([
    'status'  => 'success',
    'message' => 'Proforma ' . ($data['cabecalho']['numero'] ?? '') . ' importada com sucesso! (' . count($data['itens']) . ' itens)',
    'data'    => $data,
], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
