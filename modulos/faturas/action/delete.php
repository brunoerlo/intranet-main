<?php

header('Content-Type: application/json');

$faturasImportadasPath = __DIR__ . '/faturas.json';

// Verifica se os arquivos existem
if (!file_exists($faturasImportadasPath)) {
    echo json_encode(['success' => false, 'message' => 'Arquivo(s) de faturas não encontrado(s).']);
    exit;
}

// Carrega os dados dos dois arquivos JSON
$faturasImportadas = json_decode(file_get_contents($faturasImportadasPath), true);

// Recebe os dados do corpo da requisição
$data = json_decode(file_get_contents("php://input"), true);

// Verifica se o ID foi enviado
if (!isset($data['id'])) {
    echo json_encode(['success' => false, 'message' => 'ID da fatura não fornecida.']);
    exit;
}

$faturaId = $data['id'];
$faturaDeletado = false;

// === FLUXO 1: Deleta do faturas Importadas ===
$faturasFiltradasImportadas = array_filter($faturasImportadas, function ($fatura) use ($faturaId) {
    return $fatura["nomeFatura"] !== $faturaId;
});

if (count($faturasFiltradasImportadas) < count($faturasImportadas)) {
    if (file_put_contents($faturasImportadasPath, json_encode(array_values($faturasFiltradasImportadas), JSON_PRETTY_PRINT))) {
        echo json_encode(['success' => true, 'message' => 'Fatura deletada.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erro ao excluir faturas.']);
    }
    exit;
}

// Nenhum fatura foi encontrado
echo json_encode(['success' => false, 'message' => 'Fatura não encontrada.']);
