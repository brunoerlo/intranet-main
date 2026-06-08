<?php
$data = $_POST;
$arquivo = __DIR__ . '/empresas.json';
$empresas = file_exists($arquivo) ? json_decode(file_get_contents($arquivo), true) : [];

foreach ($empresas as &$empresa) {
    if ($empresa['id'] == $data['id']) {
        $empresa = array_merge($empresa, $data);
        break;
    }
}

file_put_contents($arquivo, json_encode($empresas, JSON_PRETTY_PRINT));
echo json_encode(['status' => 'sucesso', 'mensagem' => 'Empresa atualizada com sucesso']);
