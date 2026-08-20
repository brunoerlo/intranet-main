<?php
$quadroJson = file_get_contents('./modulos/faturas/action/estoque.json');
$quadro = json_decode($quadroJson, true) ?? [];

if (json_last_error() !== JSON_ERROR_NONE) {
    echo 'Erro ao decodificar estoque.json: ' . json_last_error_msg();
    $estoque = [];
}

$nomes = array_unique(array_column($quadro, 'nome'));
asort($nomes);
?>  
<!-- Formulario -->
<div class="container-fluid mt-4">
    <div class="card">
        <div class="card-body">
            <h4>NOVO CADASTRO</h4>
            <form id="formularioQuadro">
                <div class="tipo-grupo">
                    <div class="mb-3">
                        <label for="nomeFatura" class="form-label">Fatura</label>
                        <select class="form-select" name="nomeFatura" id="nomeFatura" required>
                            <option value="" disabled selected>Escolha a fatura</option>
                            <?php foreach ($nomes as $nome): ?>
                                <?php if ($nome !== 'sobra'): ?>
                                <option value="<?= htmlspecialchars($nome) ?>">
                                    <?= htmlspecialchars($nome) ?>
                                </option>
                                <?php endif; endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="cliente" class="form-label">Cliente</label>
                        <input type="text" class="form-control" name="cliente" id="cliente" required>
                    </div>
                    <div class="mb-3">
                        <label for="data" class="form-label">Estufagem</label>
                        <input type="date" class="form-control" name="data" id="data" required>
                    </div>
                    <div class="mb-3">
                        <label for="ctr" class="form-label">CTR</label>
                        <select class="form-select" name="ctr" id="ctr" required>
                            <option value="" disabled selected>Escolha forma de envio</option>
                            <option value="ctr20">'20</option>
                            <option value="ctr40">'40</option>
                            <option value="rod">Rodoviário</option>
                            <option value="aereo">Aéreo</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="porto" class="form-label">Porto</label>
                        <input type="text" class="form-control" name="porto" id="porto" required>
                    </div>
                    <div class="mb-3">
                        <label for="booking" class="form-label">Booking</label>
                        <input type="text" class="form-control" name="booking" id="booking" required>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">Cadastrar</button>
            </form>
        </div>
    </div>
</div>

<script>
    document.getElementById('formularioQuadro').addEventListener('submit', function(event) {
        // CADASTRAR.PHP
    })
</script>