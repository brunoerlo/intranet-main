<div class="container-fluid mt-4">
    <div class="card">
        <div class="card-body">
            <h4>NOVO ESTOQUE</h4>

            <form id="formCadastroEstoque">
                <div class="tipo-grupo">
                    <div class="mb-3">
                        <label for="numero" class="form-label">Número Fatura</label>
                        <input type="text" class="form-control" id="numero" name="numero" required>
                    </div>
                    <div class="mb-3">
                        <label for="quantidade" class="form-label">Quantidade</label>
                        <input type="number" class="form-control" id="quantidade" name="quantidade" required>
                    </div>
                </div>

                <div>
                    <button type="submit" class="btn btn-primary">Cadastrar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('formCadastroEstoque').addEventListener('submit', function (event) {
    event.preventDefault();  // Evita o envio tradicional do formulário

    // Coleta os dados do formulário
    const formData = new FormData(this);

    // Converte os dados do FormData para um objeto simples
    const data = {};
    formData.forEach((value, key) => {
        data[key] = value;
    });

    // Envia os dados via AJAX
    fetch('./modulos/faturas/action/cadastrar.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(data),
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Estoque cadastrado com sucesso!');
            document.getElementById('formCadastroCliente').reset();
        } else {
            alert('Erro ao cadastrar cliente: ' + data.error);
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        alert('Ocorreu um erro ao cadastrar o cliente.');
    });
});

</script>

<?php
$faturasJson = file_get_contents(__DIR__ . '/action/faturas.json');
$faturas = json_decode($faturasJson, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    echo 'Erro ao decodificar faturas.json: ' . json_last_error_msg();
    $faturas = [];
}

$nomes = array_column($faturas, 'nomeFatura');
$noRepeat = array_unique($nomes);
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3" style="max-width: 700px;">
        <!-- Filtro -->
        <input type="text" class="form-control" id="filtro-faturas" 
            style="width: 400px;" placeholder="Pesquisar por código ou descrição...">

        <!-- Botões -->
        <button class="btn btn-outline-secondary" id="btn-imprimir" title="Imprimir">
            🖨️ Imprimir
        </button>

        <!-- Exportar -->
        <button class="btn btn-outline-secondary" id="btn-exportar" title="Exportar CSV">
            Exportar CSV
        </button>
    </div>
    <!-- Tabela Estoque -->
    <div id="mostrar-impressao">      
        <table class="table table-bordered table-hover mt-3" id="tabela-faturas">
            <thead class="table-dark">
                <tr>
                    <th class="sortable">Descrição</th>
                    <th class="menor">NF</th>
                    <th class="menor">Taxa Dólar</th>
                    <th class="menor">Quantidade</th>
                    <th class="menor">Preço</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($faturas as $f): 
                    $nome       = $f["nomeFatura"]    ?? '';
                    $nf         = $f["notaFiscal"]    ?? '';
                    $taxaDolar  = $f["txDolar"]       ?? '';
                    $quantidade = $f["quantidade"]    ?? '';
                    $preco      = $f["preco"]         ?? '';
                    $tipo       = $f["tipo"]          ?? '';
                    $codigo     = $f["codigo"]        ?? '';
                    $descricao  = $f["descricao"]     ?? '';
                    $jsonAttr   = htmlspecialchars(json_encode($f), ENT_QUOTES, 'UTF-8');
                    ?>
                <tr data-json="<?= $jsonAttr ?>" class="table-warning linha-fatura" style="display: none;">
                    <td><?= htmlspecialchars($nome) ?></td>
                    <td><?= htmlspecialchars($nf) ?></td>
                    <td><?= htmlspecialchars($taxaDolar) ?></td>
                    <td><?= htmlspecialchars($quantidade) ?></td>
                    <td class="filtravel" style="display: none;"><?= htmlspecialchars($codigo) ?></td>
                    <td class="filtravel" style="display: none;"><?= htmlspecialchars($descricao) ?></td>
                    <td><?= htmlspecialchars($preco) ?></td>
                </tr>
                
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <p id="sem-resultados" class="text-muted text-center" style="display: none;">
        Nenhum item encontrado para essa busca.
    </p>
</div>