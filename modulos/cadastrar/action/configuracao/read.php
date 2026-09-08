<?php
$arquivo = __DIR__ . '/empresas.json';

if (!file_exists($arquivo)) {
    file_put_contents($arquivo, json_encode([]));
}

$empresas = json_decode(file_get_contents($arquivo), true);
header('Content-Type: application/json');
echo json_encode($empresas);
