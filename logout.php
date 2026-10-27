<?php
require_once __DIR__ . '/logger.php';  
session_start();

$motivo = isset($_GET['motivo']) ? $_GET['motivo'] : 'sair';
if ($motivo === 'inatividade') {
    $msg_url = '?msg=sessao_expirada';
} elseif ($motivo === 'atualizacao') {
    $msg_url = '?msg=versao_atualizada';
} else {
    $msg_url = '';
}

if (isset($_SESSION["usuario"])) {
    if ($motivo === 'inatividade') {
        registrarLog('Sistema', 'configuracao', 'logout', 'sair', "Fez Logout do usuário {$_SESSION["usuario"]["nome"]}");
    } elseif ($motivo === 'atualizacao') {
        registrarLog('Sistema', 'configuracao', 'logout', 'sair', "Logout por atualização de versão do usuário {$_SESSION["usuario"]["nome"]}");
    } else {
        registrarLog($_SESSION["usuario"]["nome"], 'configuracao', 'logout', 'sair', "Fez Logout");
    }
}

session_unset();
session_destroy();
header("Location: /login.php" . $msg_url);
exit();
?>