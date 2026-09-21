<?php
$id = $_POST['id'];
$arquivo = __DIR__ . '/empresas.json';

require_once dirname(__DIR__, 3) . '/logger.php';   

session_start();
$usuario = $_SESSION["usuario"]["nome"];

$empresas = file_exists($arquivo) ? json_decode(file_get_contents($arquivo), true) : [];

// Captura a empresa que será deletada (antes de filtrar)
$itemDeletado = null;
foreach ($empresas as $empresa) {
    if ($empresa['id'] == $id) {
        $itemDeletado = $empresa;
        break;
    }
}

$empresas = array_filter($empresas, function ($empresa) use ($id) {
    return $empresa['id'] != $id;
});

file_put_contents($arquivo, json_encode(array_values($empresas), JSON_PRETTY_PRINT));
registrarLog($usuario, 'configuracao', 'empresa', 'excluir', "Excluiu a empresa {$itemDeletado['razaoSocial']}");
echo json_encode(['status' => 'sucesso', 'mensagem' => 'Empresa excluída com sucesso']);
