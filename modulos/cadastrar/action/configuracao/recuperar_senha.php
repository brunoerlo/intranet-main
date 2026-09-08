<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');
require '../../../vendor/autoload.php'; // Carrega o PHPMailer

// Carrega as configurações do arquivo JSON
$config = json_decode(file_get_contents(__DIR__ . '/../../configuracao/action/SMTP_config.json'), true);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

define('USERS_FILE', 'users.json');
define('TOKENS_FILE', 'tokens.json');

function enviarEmail($destinatario, $assunto, $mensagem) {
    global $config;
    $mail = new PHPMailer(true);

    try {
        // Configuração do servidor SMTP
        $mail->isSMTP();
        $mail->Host = $config['host'];
        $mail->SMTPAuth = true;
        $mail->Username = $config['username'];
        $mail->Password = $config['password'];
        $mail->SMTPSecure = $config['encryption'];
        $mail->Port = $config['port'];

        // Configuração do e-mail
        $mail->CharSet = "UTF-8"; // ✅ Define a codificação para UTF-8
        $mail->Encoding = "base64"; // ✅ Garante que o conteúdo seja corretamente tratado

        $mail->setFrom($config['username'], 'Suporte');
        $mail->addAddress($destinatario);
        $mail->isHTML(true);
        $mail->Subject = "=?UTF-8?B?" . base64_encode($assunto) . "?="; // ✅ Codifica o assunto corretamente
        $mail->Body = $mensagem;

        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
}


// 1️⃣ Verifica se recebeu um e-mail via POST
if (!isset($_POST['email']) || empty($_POST['email'])) {
    echo json_encode(["status" => "error", "message" => "E-mail inválido."]);
    exit;
}

$email = trim($_POST['email']);

// 2️⃣ Carrega os usuários do JSON
if (!file_exists(USERS_FILE)) {
    echo json_encode(["status" => "error", "message" => "Banco de dados não encontrado."]);
    exit;
}

$users = json_decode(file_get_contents(USERS_FILE), true);
$userFound = null;

foreach ($users as $user) {
    if ($user['email'] === $email) {
        $userFound = $user;
        break;
    }
}

if (!$userFound) {
    echo json_encode(["status" => "success", "message" => "Se o e-mail estiver cadastrado, enviaremos um link de redefinição."]);
    exit;
}

// 3️⃣ Gera um token seguro e salva no JSON
$token = bin2hex(random_bytes(32));
$expira = date('Y-m-d H:i:s', strtotime('+1 hour'));

$tokens = file_exists(TOKENS_FILE) ? json_decode(file_get_contents(TOKENS_FILE), true) : [];
$tokens[$userFound['id']] = ["token" => $token, "expira" => $expira];

file_put_contents(TOKENS_FILE, json_encode($tokens, JSON_PRETTY_PRINT));

// 4️⃣ Envia o e-mail com o link de redefinição
$link = "https://intranet.brazmix.com/redefinir_senha.php?token=$token";
$mensagem = "<h3>Recuperação de Senha</h3>
<p>Olá, {$userFound['nome']},</p>
<p>Você solicitou a recuperação de senha. Clique no link abaixo para redefinir sua senha:</p>
<p><a href='$link'>$link</a></p>
<p>Se não foi você, ignore este e-mail.</p>";

if (enviarEmail($email, "Recuperação de Senha", $mensagem)) {
    echo json_encode(["status" => "success", "message" => "Se o e-mail estiver cadastrado, enviaremos um link de redefinição."]);
} else {
    echo json_encode(["status" => "error", "message" => "Erro ao enviar o e-mail."]);
}
?>
