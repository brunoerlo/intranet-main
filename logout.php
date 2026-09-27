<?php
require_once __DIR__ . '/logger.php';  
session_start();
registrarLog($_SESSION["usuario"]["nome"], 'configuracao', 'logout', 'sair', "Fez Logout");
header("Location: /login.php");
session_destroy();
exit();
?>