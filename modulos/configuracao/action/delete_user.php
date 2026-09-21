<?php
$arquivo = __DIR__ . "/users.json";

require_once dirname(__DIR__, 3) . '/logger.php';   

session_start();
$usuario = $_SESSION["usuario"]["nome"];

// Verifica se o ID foi enviado
if (!isset($_POST['id'])) {
    echo json_encode(["status" => "error", "message" => "ID não enviado"]);
    exit();
}

// Lê o arquivo JSON
$usuarios = file_exists($arquivo) ? json_decode(file_get_contents($arquivo), true) : [];

if (!$usuarios) {
    echo json_encode(["status" => "error", "message" => "Erro ao carregar usuários"]);
    exit();
}

// Captura o usuário que será deletado (antes de filtrar)
$idParaExcluir = $_POST['id'];
$itemDeletado = null;
foreach ($usuarios as $user) {
    if ($user['id'] === $idParaExcluir) {
        $itemDeletado = $user;
        break;
    }
}

// Filtra os usuários para remover o que tem o ID fornecido
$usuariosFiltrados = array_filter($usuarios, function ($user) use ($idParaExcluir) {
    return $user['id'] !== $idParaExcluir;
});

// Salva novamente o JSON atualizado
if (file_put_contents($arquivo, json_encode(array_values($usuariosFiltrados), JSON_PRETTY_PRINT))) {
    registrarLog($usuario, 'configuracao', 'usuario', 'excluir', "Excluiu o usuario {$itemDeletado['nome']}");
    echo json_encode(["status" => "success", "message" => "Usuário excluído com sucesso"]);
} else {
    echo json_encode(["status" => "error", "message" => "Erro ao salvar usuários"]);
}
?>
