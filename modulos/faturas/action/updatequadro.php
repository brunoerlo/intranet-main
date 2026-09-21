<?php
header('Content-Type: application/json');

require_once dirname(__DIR__, 3) . '/logger.php';   

session_start();
$usuario = $_SESSION["usuario"]["nome"] ?? "Desconhecido";

$quadroPath = __DIR__ . '/quadro.json';

// Verifica se os arquivos existem
if (!file_exists($quadroPath)) {
    echo json_encode(['success' => false, 'message' => 'Arquivo de quadro não encontrado.']);
    exit;
}

// Carrega os arquivos JSON
$quadro = json_decode(file_get_contents($quadroPath), true);

// Recebe os dados do corpo da requisição (suporta FormData/POST e JSON raw)
$inputRaw = json_decode(file_get_contents("php://input"), true);
$data = !empty($_POST) ? $_POST : ($inputRaw ?? []);

// Verifica se os dados foram enviados corretamente
if (!isset($data['id'])) {
    echo json_encode(['success' => false, 'message' => 'ID do quadro não especificado.']);
    exit;
}

$id = $data['id'];
$encontrado = false;
$itemEditado = null;
$detalhesMudanca = "";

//Atualiza fatura do estoque selecionado ===

foreach ($quadro as &$q) {
    if (($q['id'] ?? '') == $id) {
        $mudancas = [];
        
        $campos = [
            'nomeFatura' => 'Fatura',
            'cliente' => 'Cliente',
            'nomeConsolidado' => 'Consolidado',
            'data' => 'Estufagem',
            'ctr' => 'CTR',
            'porto' => 'Porto',
            'booking' => 'Booking'
        ];
        
        foreach ($campos as $key => $label) {
            if (isset($data[$key])) {
                $valorAntigo = $q[$key] ?? '';
                $valorNovo = $data[$key];
                if ($valorAntigo !== $valorNovo) {
                    $mudancas[] = "$label: de '$valorAntigo' para '$valorNovo'";
                    $q[$key] = $valorNovo;
                }
            }
        }
        
        $itemEditado = $q;
        $detalhesMudanca = !empty($mudancas) ? implode(' | ', $mudancas) : "Nenhuma alteração de valor";
        $encontrado = true;
        break;
    }
}
unset($q);

if (!$encontrado) {
    echo json_encode(['success' => false, 'message' => 'Registro não encontrado.']);
    exit;
}

if (file_put_contents($quadroPath, json_encode($quadro, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
    registrarLog($usuario, 'faturas', 'quadro', 'editar', "Editou a fatura {$itemEditado['nomeFatura']} - {$itemEditado['cliente']}. Alterações: $detalhesMudanca");
    echo json_encode(['success' => true, 'message' => 'Quadro atualizado com sucesso.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Falha ao salvar o arquivo.']);
}
