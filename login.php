<?php session_start(); ?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        body, html {
            height: 100%;
            margin: 0;
        }
        .wrapper {
            display: flex;
            height: 100vh;
        }
        .sidebar {
            width: 0%;
            background: #fff;
        }
        .login-container {
            width: 100%;
            background: #000;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-form {
            background: #222;
            padding: 30px;
            border-radius: 10px;
            color: #fff;
            width: 100%;
            max-width: 400px;
        }
        .login-form input {
            margin-bottom: 15px;
        }
        .text-link {
            color: #0d6efd;
            cursor: pointer;
            display: block;
            text-align: center;
            margin-top: 10px;
        }
        .text-link:hover {
            text-decoration: underline;
        }
    </style>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <meta name="robots" content="noindex, nofollow">

</head>
<body>
    <div class="wrapper">
        <div class="sidebar"></div>
        <div class="login-container">

            <!-- Formulário de Login -->
            <form class="login-form" id="login-form" method="POST" action="./modulos/configuracao/action/login.php">
                <h2 class="text-center mb-4"><img src="logo.png"/></h2>
                
                <?php if (isset($_SESSION['login_erro'])): ?>
                    <div class="alert alert-danger"><?= $_SESSION['login_erro'] ?></div>
                    <?php unset($_SESSION['login_erro']); ?>
                <?php endif; ?>

                <div class="mb-3">
                    <label for="email" class="form-label">E-mail</label>
                    <input type="email" class="form-control" id="email" name="email" required>
                </div>
                <div class="mb-3">
                    <label for="senha" class="form-label">Senha</label>
                    <input type="password" class="form-control" id="senha" name="senha" required>
                </div>
                <!-- Adicione o reCAPTCHA antes do botão de envio -->
                <div class="mb-3 text-center">
                    <div class="g-recaptcha" data-sitekey="6LcLb_kqAAAAAPsuu6eoRzv-wkm0JG-6kwwXACwy"></div>
                </div>
                <button type="submit" class="btn btn-primary w-100">Entrar</button>
                <span class="text-link" onclick="mostrarRecuperacao()">Esqueci minha senha</span>
            </form>

            <!-- Formulário de Recuperação de Senha -->
            <form class="login-form" id="recuperacao-form" style="display: none;">
                <h2 class="text-center mb-4">Recuperar Senha</h2>

                <div class="mb-3">
                    <label for="recuperacao-email" class="form-label">E-mail</label>
                    <input type="email" class="form-control" id="recuperacao-email" name="email" placeholder="Digite seu e-mail" required>
                </div>
                <button type="submit" class="btn btn-warning w-100" id="recuperacao-btn">Enviar redefinição</button>
                <span class="text-link" onclick="mostrarLogin()">Voltar ao login</span>

                <div id="recuperacao-mensagem" class="alert mt-3" style="display: none;"></div>
            </form>

        </div>
    </div>

    <script>
        function mostrarRecuperacao() {
            document.getElementById('login-form').style.display = 'none';
            document.getElementById('recuperacao-form').style.display = 'block';
        }

        function mostrarLogin() {
            document.getElementById('recuperacao-form').style.display = 'none';
            document.getElementById('login-form').style.display = 'block';
        }

        $(document).ready(function () {
            $("#recuperacao-form").submit(function (e) {
                e.preventDefault(); // Impede o envio tradicional do formulário

                let email = $("#recuperacao-email").val();
                let btn = $("#recuperacao-btn");
                let msgBox = $("#recuperacao-mensagem");

                btn.prop("disabled", true).text("Enviando...");

                $.ajax({
                    url: "./modulos/configuracao/action/recuperar_senha.php",
                    type: "POST",
                    data: { email: email },
                    dataType: "json",
                    success: function (response) {
                        if (response.status === "success") {
                            msgBox.removeClass("alert-danger").addClass("alert-success")
                                .text("Se o e-mail estiver cadastrado, um link de recuperação foi enviado. Verifique sua caixa de entrada.")
                                .fadeIn();
                        } else {
                            msgBox.removeClass("alert-success").addClass("alert-danger")
                                .text("Erro ao enviar o e-mail. Tente novamente.")
                                .fadeIn();
                        }
                    },
                    error: function () {
                        msgBox.removeClass("alert-success").addClass("alert-danger")
                            .text("Erro ao conectar ao servidor. Tente novamente mais tarde.")
                            .fadeIn();
                    },
                    complete: function () {
                        btn.prop("disabled", false).text("Enviar redefinição");
                    }
                });
            });
        });
    </script>
<!-- Inclua o script do reCAPTCHA -->
<script src="https://www.google.com/recaptcha/api.js" async defer></script>
</body>
</html>
