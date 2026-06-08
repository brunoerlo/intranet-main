<?php
header('Content-Type: application/json');

$arquivo = __DIR__ . '/servicos.json';
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['id'])) {
    echo json_encode(['success' => false, 'message' => 'ID não fornecido.']);
    exit;
}

$id = $input['id'];

if (!file_exists($arquivo)) {
    echo json_encode(['success' => false, 'message' => 'Arquivo não encontrado.']);
    exit;
}

$servicos = json_decode(file_get_contents($arquivo), true);
$filtrados = array_filter($servicos, fn($s) => $s['id'] !== $id);

if (count($filtrados) === count($servicos)) {
    echo json_encode(['success' => false, 'message' => 'Serviço não encontrado.']);
    exit;
}

file_put_contents($arquivo, json_encode(array_values($filtrados), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo json_encode(['success' => true]);
