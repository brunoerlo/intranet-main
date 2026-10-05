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