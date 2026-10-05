<?php

header('Content-Type: application/json');

$codigo = $_GET['codigo'] ?? '';

$produdosJson = file_get_contents(__DIR__ . '/../../cadastrar/action/produtos/produtos.json');
$produdos = json_decode($produdosJson, true);

foreach ($produdos as $produto) {
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