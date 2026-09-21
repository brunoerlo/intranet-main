<?php
$logDir = __DIR__ . '/action/log.json';
$logs = [];

if (file_exists($logDir)) {
    $content = file_get_contents($logDir);
    $logs = json_decode($content);
}

?>
<div class="container-fluid mt-4">
    <h2><i class="fa-solid fa-list-check"></i> Histórico de Atividades (Logs)</h2>
    <hr>
    <!-- Filtro
    <div class="card mt-4">
        <div>

        </div>
    </div>
    -->
    
</div>