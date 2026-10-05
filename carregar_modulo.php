<?php
session_start();

if (!isset($_SESSION["usuario"])) {
    echo "<script>window.location.href='login.php?msg=sessao_expirada';</script>";
    exit();
}

$timeout_duration = 1800; // 30 minutos
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > $timeout_duration)) {
    session_unset();
    session_destroy();
    echo "<script>window.location.href='login.php?msg=sessao_expirada';</script>";
    exit();
}
$_SESSION['LAST_ACTIVITY'] = time();

if (isset($_GET['modulo']) && isset($_GET['submodulo'])) {
    $modulo = preg_replace('/[^a-zA-Z0-9_-]/', '', $_GET['modulo']);
    $submodulo = preg_replace('/[^a-zA-Z0-9_-]/', '', $_GET['submodulo']);
    $arquivo = __DIR__ . "/modulos/$modulo/$submodulo.php";

    if (file_exists($arquivo)) {
        include($arquivo);
    } else {
        echo "<h2 class='text-danger'>Erro: Arquivo não encontrado.</h2>";
    }
} else {
    echo "<h2 class='text-warning'>Nenhum módulo selecionado.</h2>";
}
?>
