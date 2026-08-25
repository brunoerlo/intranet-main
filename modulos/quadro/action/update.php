<?php
header('Content-Type: application/json');

$estoquePath = __DIR__ . '/estoque.json';

// Verifica se os arquivos existem
if (!file_exists($estoquePath)) {
    echo json_encode(['success' => false, 'message' => 'Arquivo de estoque não encontrado.']);
    exit;
}

// Carrega os arquivos JSON
$estoque = json_decode(file_get_contents($estoquePath), true);

// Recebe os dados do corpo da requisição
$data = json_decode(file_get_contents("php://input"), true);

// Verifica se os dados foram enviados corretamente
if (!isset($data['Codigo estoque'])) {
    echo json_encode(['success' => false, 'message' => 'Código do estoque não especificado.']);
    exit;
}

$codigoEstoque = $data['Codigo estoque'];
$encontrado = false;

//Atualiza fatura do estoque selecionado ===

foreach ($estoque as &$e) {
    if (($e['Codigo estoque'] ?? '') === $codigoEstoque) {
        if (isset($data['nome']))       $e['nome']       = $data['nome'];
        if (isset($data['codigo']))     $e['codigo']     = $data['codigo'];
        if (isset($data['descricao']))  $e['descricao']  = $data['descricao'];
        if (isset($data['quantidade'])) $e['quantidade'] = $data['quantidade'];

        $encontrado = true;
        break;
    }
}
unset($e);

if (!$encontrado) {
    echo json_encode(['success' => false, 'message' => 'Registro não encontrado.']);
    exit;
}

if (file_put_contents($estoquePath, json_encode($estoque, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
    echo json_encode(['success' => true, 'message' => 'Estoque atualizado com sucesso.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Falha ao salvar o arquivo.']);
}
