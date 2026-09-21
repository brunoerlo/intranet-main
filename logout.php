<?php
session_start();
$usuario = isset($_SESSION["usuario"]["nome"]);
require_once __DIR__ . '/logger.php';  
registrarLog($usuario, 'configuracao', 'logout', 'sair', "Fez Logout");
session_destroy();
header("Location: /login.php");
exit();
?>