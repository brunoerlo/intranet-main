<?php
require_once dirname(__DIR__, 3) . '/logger.php';   

session_start();
$usuario = $_SESSION["usuario"]["nome"];

$arquivo = __DIR__ . "/users.json";
$usuarios = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input || !isset($input['id'], $input['nome'], $input['email'], $input['role'])) {
        http_response_code(400);
        echo json_encode(["erro" => "Dados inválidos."]);
        exit;
    }

    if (file_exists($arquivo)) {
        $usuarios = json_decode(file_get_contents($arquivo), true) ?? [];
    }

    $itemEditado = null;
    foreach ($usuarios as &$user) {
        if ($user['id'] === $input['id']) {
            $user['nome'] = $input['nome'];
            $user['email'] = $input['email'];
            $user['role'] = $input['role'];
            
            if ($user['role'] === 'user' && isset($input['modulos'])) {
                $user['modulos'] = array_values(array_unique($input['modulos']));
            } else {
                unset($user['modulos']);
            }

            $itemEditado = $user;
            break;
        }
    }
    unset($user);

    file_put_contents($arquivo, json_encode($usuarios, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    registrarLog($usuario, 'configuracao', 'usuario', 'editar', "Editou o usuario {$itemEditado['nome']}");
    echo json_encode(["sucesso" => "Usuário atualizado com sucesso."]);
} else {
    http_response_code(405);
    echo json_encode(["erro" => "Método não permitido."]);
}
