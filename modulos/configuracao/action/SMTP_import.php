<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../../../vendor/autoload.php'; // Carrega as dependências do Composer

// Carrega as configurações SMTP a partir de um arquivo JSON
$config = json_decode(file_get_contents('SMTP_config.json'), true);

function enviarEmail($destinatario, $assunto, $mensagem) {
    global $config;

    $mail = new PHPMailer(true);

    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // Certifique-se de que STARTTLS está ativado

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
        $mail->setFrom($config['username'], 'Dev');
        $mail->addAddress($destinatario);
        $mail->isHTML(true);
        $mail->Subject = $assunto;
        $mail->Body = $mensagem;

        $mail->send();
        return "E-mail enviado com sucesso!";
    } catch (Exception $e) {
        return "Erro ao enviar e-mail: {$mail->ErrorInfo}";
    }
}

// Exemplo de uso
echo enviarEmail('dev@brazmix.com', 'Teste do E-mail', '<h3>Olá, este é um teste!</h3>');
?>
