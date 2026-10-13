<?php
$data = $_POST;

require_once dirname(__DIR__, 3) . '/logger.php';   
require_once dirname(__DIR__, 3) . '/validador.php';   

session_start();
$usuario = $_SESSION["usuario"]["nome"];

if (isset($data['cnpj'])) {
    $cnpjLimpo = preg_replace('/\D/', '', $data['cnpj'] ?? '');
    if (!validarCNPJ($cnpjLimpo)) {
        http_response_code(400);
        echo json_encode(['status' => 'erro', 'mensagem' => 'CNPJ inválido. Por favor, digite um número existente.']);
        exit;
    }
    $data['cnpj'] = $cnpjLimpo;
}
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
