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