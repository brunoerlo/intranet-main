<?php
header('Content-Type: application/json');

$arquivo = __DIR__ . '/users.json';

// Recebe os dados via POST
$nome = trim($_POST['nome'] ?? '');
$email = trim($_POST['email'] ?? '');
$senha = $_POST['senha'] ?? '';
$role = trim($_POST['role'] ?? '');  // Novo campo 'role'
$modulos = $_POST['modulos'] ?? [];  // Novo campo 'modulos'

// Validação básica
if (empty($nome) || empty($email) || empty($senha) || empty($role)) {
    echo json_encode(["success" => false, "message" => "Todos os campos são obrigatórios."]);
    exit;
}

// Criar um ID único para o usuário
$id = uniqid("user_");

// Hash da senha para segurança
$senhaHash = password_hash($senha, PASSWORD_DEFAULT);

// Estrutura do usuário com os novos campos
$novoUsuario = [
    "id" => $id,
    "nome" => $nome,
    "email" => $email,
    "senha" => $senhaHash,
    "role" => $role,  // Adicionando o campo 'role'
    "modulos" => $modulos,  // Adicionando o campo 'modulos'
];

// Verifica se o arquivo JSON já existe
if (file_exists($arquivo)) {
    $usuarios = json_decode(file_get_contents($arquivo), true);
} else {
    $usuarios = [];
}

// Adiciona o novo usuário à lista
$usuarios[] = $novoUsuario;

// Salva os dados no arquivo JSON
file_put_contents($arquivo, json_encode($usuarios, JSON_PRETTY_PRINT));

echo json_encode(["success" => true, "id" => $id]);
