<?php
$arquivo = __DIR__ . '/users.json';

// Verifica se o arquivo existe e lê os dados
if (file_exists($arquivo)) {
    echo file_get_contents($arquivo);
} else {
    echo json_encode([]);
}
?>
