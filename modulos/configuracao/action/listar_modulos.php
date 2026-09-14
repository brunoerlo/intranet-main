<?php
header('Content-Type: application/json');

$baseDir = realpath(dirname(__DIR__, 2) . '/../modulos'); // Caminho correto para a pasta "modulos"

if (!$baseDir || !is_dir($baseDir)) {
    echo json_encode(["error" => "Diretório não encontrado: $baseDir"]);
    exit;
}

$modulosData = [];
foreach (scandir($baseDir) as $modulo) {
    if ($modulo !== '.' && $modulo !== '..' && is_dir("$baseDir/$modulo")) {
        $modulosData[$modulo] = [];

        foreach (scandir("$baseDir/$modulo") as $submodulo) {
            if ($submodulo !== '.' && $submodulo !== '..' && pathinfo($submodulo, PATHINFO_EXTENSION) === 'php') {
                $modulosData[$modulo][] = pathinfo($submodulo, PATHINFO_FILENAME);
            }
        }
    }
}

echo json_encode($modulosData, JSON_PRETTY_PRINT);
