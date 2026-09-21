<?php

date_default_timezone_set('America/Sao_Paulo');

function registrarLog(string $usuario, string $modulo, string $submodulo, string $acao, string $detalhes): bool {
    $logDir = __DIR__ . '/modulos/configuracao/action/';
    $logFile = $logDir . '/logs.json';

    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }

    $novoLog = [
        'timestamp' => date('d/m/Y H:i:s'),
        'usuario' => $usuario,
        'modulo' => $modulo,
        'submodulo' => $submodulo,
        'acao' => $acao,
        'detalhes' => $detalhes
    ];

    // Trava o arquivo para evitar corrupção de concorrência
    $fp = fopen($logFile, 'c+');
    if ($fp === false) {
        error_log("Falha ao abrir arquivo de log: $logFile");
        return false;
    }

    if (flock($fp, LOCK_EX)) {
        $filesize = filesize($logFile);
        $logs = [];
        if ($filesize > 0) {
            $conteudo = fread($fp, $filesize);
            $logs = json_decode($conteudo, true) ?? [];
        }

        $logs[] = $novoLog;

        // Limpa o arquivo e escreve o novo JSON
        ftruncate($fp, 0);
        rewind($fp);
        fwrite($fp, json_encode($logs, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        flock($fp, LOCK_UN);
    }

    fclose($fp);
    return true;
}