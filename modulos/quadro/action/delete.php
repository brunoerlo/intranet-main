<?php

header('Content-Type: application/json');

$quadroPath = __DIR__ . '/quadro.json';

// Verifica se os arquivos existem
if (!file_exists($quadroPath)) {
    echo json_encode(['success' => false, 'message' => 'Arquivo(s) de item(ns) do quadro não encontrado(s).']);
    exit;
}

// Carrega os dados dos dois arquivos JSON
$itensQuadro = json_decode(file_get_contents($quadroPath), true);

// Recebe os dados do corpo da requisição
$data = json_decode(file_get_contents("php://input"), true);

// Verifica se o ID foi enviado
if (!isset($data['id'])) {
    echo json_encode(['success' => false, 'message' => 'ID do quadro não fornecido.']);
    exit;
}

$faturaId = $data['id'];
$faturaDeletada = false;

$itensQuadroFiltrado = array_filter($itensQuadro, function ($quadro) use ($faturaId) {
    return (string)$quadro["id"] !== (string)$faturaId;
});

if (count($itensQuadroFiltrado) < count($itensQuadro)) {
    if (file_put_contents($quadroPath, json_encode(array_values($itensQuadroFiltrado), JSON_PRETTY_PRINT))) {
        echo json_encode(['success' => true, 'message' => 'Fatura deletada do quadro.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erro ao salvar.']);
    }
    exit;
}

// Nenhum cliente foi encontrado
echo json_encode(['success' => false, 'message' => 'Fatura não encontrada em nenhum arquivo.']);
