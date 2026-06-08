<?php session_start(); ?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Redefinir Senha</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        body, html { height: 100%; margin: 0; }
        .wrapper { display: flex; height: 100vh; }
        .sidebar { width: 0%; background: #fff; }
        .login-container { width: 100%; background: #000; display: flex; align-items: center; justify-content: center; }
        .login-form { background: #222; padding: 30px; border-radius: 10px; color: #fff; width: 100%; max-width: 400px; }
        .login-form input { margin-bottom: 15px; }
        .text-link { color: #0d6efd; cursor: pointer; display: block; text-align: center; margin-top: 10px; }
        .text-link:hover { text-decoration: underline; }
    </style>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>

<?php
session_start();

// Verifica se o token foi recebido via GET
if (!isset($_GET['token']) || empty($_GET['token'])) {
    die('<div class="text-center mt-5 text-danger">Token inválido ou ausente.</div>');
}

$token = $_GET['token'];
$tokensFile = './modulos/configuracao/action/tokens.json';

// Verifica se o arquivo de tokens existe
if (!file_exists($tokensFile)) {
    die('<div class="text-center mt-5 text-danger">Arquivo de tokens não encontrado.</div>');
}

// Carrega os tokens do arquivo JSON
$tokens = json_decode(file_get_contents($tokensFile), true);
if (!$tokens) {
    die('<div class="text-center mt-5 text-danger">Erro ao carregar os tokens.</div>');
}
// Procura pelo token dentro do JSON
$userId = null;

foreach ($tokens as $id => $tokenList) {
    foreach ($tokenList as $index => $data) {

        if ($data === $token) {
            $userId = $id;
            break 2; // Sai dos dois loops
        }
    }
}

// Se não encontrou um usuário com o token, retorna erro
if (!$userId) {
    die('<div class="text-center mt-5 text-danger">Token não encontrado.</div>');
}


// Exibe a página de redefinição de senha
?>


<div class="wrapper">
    <div class="sidebar"></div>
    <div class="login-container">
        <form class="login-form" id="redefinir-form">
            <h2 class="text-center mb-4">Redefinir Senha</h2>

            <input type="hidden" id="userId" value="<?= htmlspecialchars($userId) ?>">
            
            <div class="mb-3">
                <label for="senha" class="form-label">Nova Senha</label>
                <input type="password" class="form-control" id="senha" name="senha" required>
            </div>
            <div class="mb-3">
                <label for="confirmar-senha" class="form-label">Confirmar Senha</label>
                <input type="password" class="form-control" id="confirmar-senha" required>
            </div>
            
            <button type="submit" class="btn btn-success w-100">Alterar Senha</button>
            <div id="mensagem" class="alert mt-3" style="display: none;"></div>
        </form>
    </div>
</div>

<script>
    $(document).ready(function () {
        $("#redefinir-form").submit(function (e) {
            e.preventDefault();

            let senha = $("#senha").val();
            let confirmarSenha = $("#confirmar-senha").val();
            let userId = $("#userId").val();
            let mensagem = $("#mensagem");

            if (senha !== confirmarSenha) {
                mensagem.removeClass("alert-success").addClass("alert-danger")
                    .text("As senhas não coincidem.").fadeIn();
                return;
            }

            $.ajax({
                url: "./modulos/configuracao/action/alterar_senha.php",
                type: "POST",
                data: { userId: userId, senha: senha },
                dataType: "json",
                success: function (response) {
                    if (response.status === "success") {
                        mensagem.removeClass("alert-danger").addClass("alert-success")
                            .text("Senha redefinida com sucesso!").fadeIn();
                        setTimeout(() => window.location.href = "login.php", 2000);
                    } else {
                        mensagem.removeClass("alert-success").addClass("alert-danger")
                            .text("Erro ao redefinir a senha.").fadeIn();
                    }
                },
                error: function () {
                    mensagem.removeClass("alert-success").addClass("alert-danger")
                        .text("Erro ao conectar ao servidor.").fadeIn();
                }
            });
        });
    });
</script>

</body>
</html>
