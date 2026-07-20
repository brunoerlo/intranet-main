<?php

header('Content-Type: application/json');

$codigo = $_GET['codigo'] ?? '';

$faturasJson = file_get_contents(__DIR__ . '/../../produtos/action/produtos.json');
$faturas = json_decode($faturasJson, true);

foreach ($faturas as $produto) {
    if ($produto['Item'] == $codigo) {
        echo json_encode([
            'encontrado' => true,
            'descricao' => $produto['Descricao']
        ]);
        exit;
    }
}

echo json_encode([
    'encontrado' => false
]);