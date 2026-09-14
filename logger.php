<?php

function registrarLog(string $usuario, string $modulo, string $acao, string $detalhes): bool {
    $logDir = __DIR__ . '/logs';
    $logFile = $logDir . '/atividades.csv';

    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }

    $isNewFile = !file_exists($logFile);

    $fp = fopen($logFile, 'a');
    if ($fp === false) {
        error_log("Falha ao abrir arquivo de log: $logFile");
        return false;
    }

    // trava o arquivo pra evitar corrupção se dois usuários gravarem ao mesmo tempo
    if (flock($fp, LOCK_EX)) {
        if ($isNewFile) {
            fputcsv($fp, ['timestamp', 'usuario', 'modulo', 'acao', 'detalhes']);
        }

        fputcsv($fp, [
            date('Y-m-d H:i:s'),
            $usuario,
            $modulo,
            $acao,
            $detalhes
        ]);

        flock($fp, LOCK_UN);
    }

    fclose($fp);
    return true;
}