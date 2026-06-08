<?php
header('Content-Type: application/json');
session_start();

$usersFile = './users.json';
$tokensFile = './tokens.json';

// Verifica se os parâmetros foram enviados
if (!isset($_POST['userId']) || !isset($_POST['senha'])) {
    echo json_encode(["status" => "error", "message" => "Dados incompletos."]);
    exit;
}

$userId = $_POST['userId'];
$novaSenha = $_POST['senha'];

// Verifica se o arquivo de usuários existe
if (!file_exists($usersFile)) {
    echo json_encode(["status" => "error", "message" => "Arquivo de usuários não encontrado."]);
    exit;
}

// Carrega os usuários
$users = json_decode(file_get_contents($usersFile), true);
if (!$users) {
    echo json_encode(["status" => "error", "message" => "Erro ao carregar os usuários."]);
    exit;
}

// Encontra o usuário pelo ID
$usuarioEncontrado = false;
foreach ($users as &$user) {
    if ($user['id'] === $userId) {
        // Atualiza a senha com hash seguro
        $user['senha'] = password_hash($novaSenha, PASSWORD_DEFAULT);
        $usuarioEncontrado = true;
        break;
    }
}

if (!$usuarioEncontrado) {
    echo json_encode(["status" => "error", "message" => "Usuário não encontrado."]);
    exit;
}

// Salva a nova lista de usuários
if (!file_put_contents($usersFile, json_encode($users, JSON_PRETTY_PRINT))) {
    echo json_encode(["status" => "error", "message" => "Erro ao atualizar a senha."]);
    exit;
}

// Remove o token usado do tokens.json
if (file_exists($tokensFile)) {
    $tokens = json_decode(file_get_contents($tokensFile), true);
    
    if (isset($tokens[$userId])) {
        // Remove apenas o token que foi utilizado
        array_shift($tokens[$userId]); // Remove o primeiro token da lista
        if (empty($tokens[$userId])) {
            unset($tokens[$userId]); // Remove a chave se não houver mais tokens
        }
        file_put_contents($tokensFile, json_encode($tokens, JSON_PRETTY_PRINT));
    }
}

// Responde ao AJAX com sucesso
echo json_encode(["status" => "success", "message" => "Senha redefinida com sucesso!"]);
exit;
?>
