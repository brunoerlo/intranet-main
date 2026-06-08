<?php

header('Content-Type: application/json');

$clientesImportadosPath = __DIR__ . '/clientes.json';
$clientesCadastradosPath = __DIR__ . '/clientes_cadastrados.json';

// Verifica se os arquivos existem
if (!file_exists($clientesImportadosPath) || !file_exists($clientesCadastradosPath)) {
    echo json_encode(['success' => false, 'message' => 'Arquivo(s) de clientes não encontrado(s).']);
    exit;
}

// Carrega os dados dos dois arquivos JSON
$clientesImportados = json_decode(file_get_contents($clientesImportadosPath), true);
$clientesCadastrados = json_decode(file_get_contents($clientesCadastradosPath), true);

// Recebe os dados do corpo da requisição
$data = json_decode(file_get_contents("php://input"), true);

// Verifica se o ID foi enviado
if (!isset($data['id'])) {
    echo json_encode(['success' => false, 'message' => 'ID do cliente não fornecido.']);
    exit;
}

$clienteId = $data['id'];
$clienteDeletado = false;

// === FLUXO 1: Deleta do clientes importados ===
$clientesFiltradosImportados = array_filter($clientesImportados, function ($cliente) use ($clienteId) {
    return $cliente["Cliente"] !== $clienteId;
});

if (count($clientesFiltradosImportados) < count($clientesImportados)) {
    if (file_put_contents($clientesImportadosPath, json_encode(array_values($clientesFiltradosImportados), JSON_PRETTY_PRINT))) {
        echo json_encode(['success' => true, 'message' => 'Cliente deletado dos importados.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erro ao salvar clientes importados.']);
    }
    exit;
}

// === FLUXO 2: Deleta do clientes cadastrados ===
$clientesFiltradosCadastrados = array_filter($clientesCadastrados, function ($cliente) use ($clienteId) {
    return $cliente["Codigo Cliente"] !== $clienteId;
});

if (count($clientesFiltradosCadastrados) < count($clientesCadastrados)) {
    if (file_put_contents($clientesCadastradosPath, json_encode(array_values($clientesFiltradosCadastrados), JSON_PRETTY_PRINT))) {
        echo json_encode(['success' => true, 'message' => 'Cliente deletado dos cadastrados.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erro ao salvar clientes cadastrados.']);
    }
    exit;
}

// Nenhum cliente foi encontrado
echo json_encode(['success' => false, 'message' => 'Cliente não encontrado em nenhum dos arquivos.']);
