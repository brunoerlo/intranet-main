<?php
session_start();

$arquivo = __DIR__ . '/users.json';

// Verifica se o reCAPTCHA foi preenchido
if (!isset($_POST['g-recaptcha-response'])) {
    $_SESSION['login_erro'] = "Por favor, confirme que você não é um robô.";
    header("Location: ../../../login.php");
    exit;
}

$recaptchaSecret = "6LcLb_kqAAAAAJ6IjCxw1I9KLUJ3EEq7LOzb25z5";
$recaptchaResponse = $_POST['g-recaptcha-response'];
$recaptchaVerify = file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret=$recaptchaSecret&response=$recaptchaResponse");
$recaptchaData = json_decode($recaptchaVerify);

// Verifica se o reCAPTCHA foi validado com sucesso
if (!$recaptchaData->success) {
    $_SESSION['login_erro'] = "Falha na verificação do reCAPTCHA. Tente novamente.";
    header("Location: ../../../login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST["email"]);
    $senha = $_POST["senha"];

    if (file_exists($arquivo)) {
        $usuarios = json_decode(file_get_contents($arquivo), true);
        
        foreach ($usuarios as $user) {
            if ($user["email"] === $email && password_verify($senha, $user["senha"])) {
                // Define os módulos de acesso com base no papel do usuário
                $modulos = ($user["role"] === "admin") ? ["todos"] : ($user["modulos"] ?? []);

                $_SESSION["usuario"] = [
                    "id" => $user["id"],
                    "nome" => $user["nome"],
                    "email" => $user["email"],
                    "role" => $user["role"],
                    "modulos" => $modulos
                ];
                
                header("Location: ../../../index.php");
                exit();
            }
        }
    }

    $_SESSION["login_erro"] = "E-mail ou senha inválidos.";
    header("Location: ../../../login.php");
    exit();
}
?>
