<?php
$data = $_POST;

require_once dirname(__DIR__, 3) . '/logger.php';   

session_start();
$usuario = $_SESSION["usuario"]["nome"];
$arquivo = __DIR__ . '/empresas.json';
$empresas = file_exists($arquivo) ? json_decode(file_get_contents($arquivo), true) : [];

foreach ($empresas as &$empresa) {
    if ($empresa['id'] == $data['id']) {
        $empresa = array_merge($empresa, $data);
        break;
    }
}

file_put_contents($arquivo, json_encode($empresas, JSON_PRETTY_PRINT));
registrarLog($usuario, 'configuracao', 'empresa', 'editar', "Editou a empresa {$empresa['razaoSocial']}");
echo json_encode(['status' => 'sucesso', 'mensagem' => 'Empresa atualizada com sucesso']);
