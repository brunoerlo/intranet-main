<?php

header('Content-Type: application/json');

$file_path = __DIR__ . '/estoque.json';

// Recebe os dados enviados via POST
$data = json_decode(file_get_contents('php://input'), true);

// Valida os dados recebidos
if (!isset($data['nome']) || !isset($data['descricao']) || !isset($data['codigo']) || !isset($data['quantidade'])) {
    echo json_encode(['success' => false, 'error' => 'Dados inválidos.']);
    exit;
}

// Normaliza o código
$codigo = trim($data['codigo']);
if (!str_starts_with($codigo, 'BM ')) {
    $codigo = 'BM ' . $codigo;
}
$data['codigo'] = $codigo;

// Carrega o estoque existente
if (file_exists($file_path)) {
    $estoque = json_decode(file_get_contents($file_path), true) ?? [];
} else {
    $estoque = [];
}

$jaExiste = false;

// Procura um item com mesmo código E mesma fatura (nome)
foreach ($estoque as &$item) {
    if ($item['codigo'] === $data['codigo'] && $item['nome'] === $data['nome']) {
        $item['quantidade'] = (int)$item['quantidade'] + (int)$data['quantidade'];
        $jaExiste = true;
        break;
    }
}
unset($item); // Boa prática após foreach com referência

// Se não existir, cria um novo registro
if (!$jaExiste) {
    $data['Codigo estoque'] = uniqid('cli_', true);
    $estoque[] = $data;
}

// Salva de volta no arquivo
if (file_put_contents($file_path, json_encode($estoque, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
    echo json_encode([
        'success' => true,
        'message' => $jaExiste ? 'Quantidade somada ao item existente.' : 'Novo item cadastrado.'
    ]);
} else {
    echo json_encode(['success' => false, 'error' => 'Não foi possível salvar os dados.']);
}

?>
