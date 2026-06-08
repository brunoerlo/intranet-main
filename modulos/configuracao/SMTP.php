<?php

// Caminho para o arquivo de configuração
$configFile = $configFile = __DIR__ . '/action/SMTP_config.json';

// Verificar se o arquivo existe
if (file_exists($configFile)) {
    // Carregar as configurações do arquivo JSON
    $config = json_decode(file_get_contents($configFile), true);

    // Verificar se houve erro na decodificação JSON
    if (json_last_error() !== JSON_ERROR_NONE) {
        echo 'Erro na leitura do arquivo JSON: ' . json_last_error_msg();
        // Definir valores padrão caso haja erro
        $config = [
            'host' => '',
            'username' => '',
            'password' => '',
            'port' => 465, // Valor padrão
            'encryption' => 'ssl' // Valor padrão
        ];
    }
} else {
    // Arquivo JSON não encontrado, definir valores padrão
    $config = [
        'host' => '',
        'username' => '',
        'password' => '',
        'port' => 465,
        'encryption' => 'ssl'
    ];
}

// Verificar se o formulário foi enviado para salvar as configurações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Coletar os dados enviados pelo formulário
    $newConfig = [
        'host' => $_POST['host'],
        'username' => $_POST['username'],
        'password' => $_POST['password'],
        'port' => (int) $_POST['port'], // Garantir que a porta seja um número
        'encryption' => $_POST['encryption']
    ];

    // Salvar no arquivo JSON
    file_put_contents($configFile, json_encode($newConfig, JSON_PRETTY_PRINT));

    // Atualizar o array $config para refletir as alterações
    $config = $newConfig;
}
?>

<div class="container mt-5">
    <h2>Editar Configurações SMTP</h2>
    <form method="POST" action="">
        <div class="row">
            <div class="col-md-6">
                <div class="mb-3">
                    <label for="host" class="form-label">Host</label>
                    <input type="text" class="form-control" id="host" name="host" value="<?php echo htmlspecialchars($config['host']); ?>">
                </div>
            </div>
            <div class="col-md-6">
                <div class="mb-3">
                    <label for="username" class="form-label">Username</label>
                    <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($config['username']); ?>">
                </div>
            </div>
            <div class="col-md-6">
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input type="text" class="form-control" id="password" name="password" value="<?php echo htmlspecialchars($config['password']); ?>" >
                </div>
            </div>
            <div class="col-md-6">
                <div class="mb-3">
                    <label for="port" class="form-label">Port</label>
                    <input type="number" class="form-control" id="port" name="port" value="<?php echo htmlspecialchars($config['port']); ?>" min="1" max="65535">
                </div>
            </div>
            <div class="col-md-6">
                <div class="mb-3">
                    <label for="encryption" class="form-label">Encryption</label>
                    <select class="form-select" id="encryption" name="encryption">
                        <option value="ssl" <?php echo ($config['encryption'] == 'ssl') ? 'selected' : ''; ?>>SSL</option>
                        <option value="tls" <?php echo ($config['encryption'] == 'tls') ? 'selected' : ''; ?>>TLS</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <button type="submit" class="btn btn-primary">Salvar Configurações</button>
            </div>
        </div>
    </form>
</div>
