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
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label for="cliente" class="form-label">Cliente</label>
                            <div class="form-check">
                                <label for="consolidado" class="form-check-label">Consolidado?</label>
                                <input type="checkbox" class="form-check-input" name="consolidado" id="consolidado">
                            </div>
                        </div>
                        <input type="text" class="form-control" name="cliente" id="cliente" required>
                        <div id="divNomeConsolidado" class="mb-3 d-none">
                            <label for="nomeConsolidado" class="form-label">Nome Consolidado</label>
                            <input type="text" class="form-control" name="nomeConsolidado" id="nomeConsolidado">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="data" class="form-label">Estufagem</label>
                        <input type="date" class="form-control" name="data" id="data" required>
                    </div>
                    <div class="mb-3">
                        <label for="ctr" class="form-label">CTR</label>
                        <select class="form-select" name="ctr" id="ctr" required>
                            <option value="" disabled selected>Escolha forma de envio</option>
                            <option value="'20">'20</option>
                            <option value="'40">'40</option>
                            <option value="Rodoviário">Rodoviário</option>
                            <option value="Aéreo">Aéreo</option>
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
    const checkConsolidado = document.getElementById('consolidado');
    const divNomeConsolidado = document.getElementById('divNomeConsolidado');
    const inputNomeConsolidado = document.getElementById('nomeConsolidado');

    checkConsolidado.addEventListener('change', function() {
        if(this.checked) {
            divNomeConsolidado.classList.remove('d-none');
            inputNomeConsolidado.required = true;
            inputNomeConsolidado.focus(); // Para ir direto para essa linha preencher os dados
        } else {
            divNomeConsolidado.classList.add('d-none');
            inputNomeConsolidado.required = false;
            inputNomeConsolidado.value = '';
        }   
    });


    document.getElementById('formularioQuadro').addEventListener('submit', function(event) {
        event.preventDefault();
        const formData = new FormData(this);
        const data = {};
        formData.forEach((value, key) => {
            data[key] = value;
        });

        fetch('./modulos/quadro/action/cadastrar.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data),
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    alert('Cadastro efetuado com sucesso!');
                    document.getElementById('formularioQuadro').reset();
                    location.reload();
                } else {    
                    alert('Erro ao efetuar cadastro: ' + data.error);
                }
            })
            .catch(() => alert('Ocorreu um erro ao cadastrar.'));
    })
</script>

<div>
    <table class="table table-bordered table-hover mt-3">
        <thead class="table-dark">
            <tr>
                <th class="sortable">Fatura</th>
                <th>Cliente</th>
                <th>Consolidado</th>
                <th>Estufagem</th>
                <th>CTR</th>
                <th>Porto</th>
                <th>Booking</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($quadro as $q): 
                $jsonAttr = json_encode([
                    'nome'        => $q["nomeFatura"]      ?? '',
                    'cliente'     => $q["cliente"]         ?? '',
                    'consolidado' => $q["nomeConsolidado"] ?? '',
                    'data'        => $q["data"]            ?? '',
                    'ctr'         => $q["ctr"]             ?? '',
                    'porto'       => $q["porto"]           ?? '',
                    'booking'     => $q["booking"]         ?? '',
                ]);
                ?>
            <tr>
                <td><?= htmlspecialchars($q['nomeFatura'] ?? '') ?></td>
                <td><?= htmlspecialchars($q['cliente'] ?? '') ?></td>
                <td><?= htmlspecialchars($q['nomeConsolidado'] ?? '') ?></td>
                <td><?= htmlspecialchars($q['data'] ?? '') ?></td>
                <td><?= htmlspecialchars($q['ctr'] ?? '') ?></td>
                <td><?= htmlspecialchars($q['porto'] ?? '') ?></td>
                <td><?= htmlspecialchars($q['booking'] ?? '') ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>  
