<?php
require_once __DIR__ . '/logger.php';  
session_start();

$motivo = isset($_GET['motivo']) ? $_GET['motivo'] : 'sair';
$msg_url = ($motivo === 'inatividade') ? '?msg=sessao_expirada' : '';

if (isset($_SESSION["usuario"])) {
    if ($motivo === 'inatividade') registrarLog('Sistema', 'configuracao', 'logout', 'sair', "Fez Logout do usuário {$_SESSION["usuario"]["nome"]}");
    else registrarLog($_SESSION["usuario"]["nome"], 'configuracao', 'logout', 'sair', "Fez Logout");
}

session_unset();
session_destroy();
header("Location: /login.php" . $msg_url);
exit();
?>