<?php
$id = $_POST['id'];
$arquivo = __DIR__ . '/empresas.json';
$empresas = file_exists($arquivo) ? json_decode(file_get_contents($arquivo), true) : [];

$empresas = array_filter($empresas, function ($empresa) use ($id) {
    return $empresa['id'] != $id;
});

file_put_contents($arquivo, json_encode(array_values($empresas), JSON_PRETTY_PRINT));
echo json_encode(['status' => 'sucesso', 'mensagem' => 'Empresa excluída com sucesso']);
