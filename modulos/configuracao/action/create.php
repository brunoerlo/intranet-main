<?php
header('Content-Type: application/json');

require_once dirname(__DIR__, 3) . '/logger.php';   

session_start();
$usuario = $_SESSION["usuario"]["nome"];

$data = $_POST;

$arquivo = __DIR__ . '/empresas.json';
$empresas = file_exists($arquivo) ? json_decode(file_get_contents($arquivo), true) : [];

if (!is_array($empresas)) {
    $empresas = [];
}

$novoId = count($empresas) > 0 ? max(array_column($empresas, 'id')) + 1 : 1;

$data['id'] = $novoId;
$empresas[] = $data;

$resultado = file_put_contents($arquivo, json_encode($empresas, JSON_PRETTY_PRINT));

if ($resultado === false) {
    http_response_code(500);
    echo json_encode(['status' => 'erro', 'mensagem' => 'Erro ao salvar a empresa']);
    exit;
}

registrarLog($usuario, 'configuracao', 'empresa', 'criar', "Criou a empresa {$data['razaoSocial']}");
echo json_encode(['status' => 'sucesso', 'mensagem' => 'Empresa criada com sucesso']);
