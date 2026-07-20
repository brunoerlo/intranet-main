<?php
$faturasJson = file_get_contents(__DIR__ . '/action/faturas.json');
$faturas = json_decode($faturasJson, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    echo 'Erro ao decodificar faturas.json: ' . json_last_error_msg();
    $faturas = [];
}

$descricao = array_column($faturas, 'descricao');
$noRepeat = array_unique($descricao);
?>

<div class="container-fluid mt-4">
    <div class="card">
        <div class="card-body">
            <h4>NOVO ESTOQUE</h4>

            <form id="formCadastroEstoque">
                <div class="tipo-grupo">
                    <div class="mb-3">
                        <label for="numero" class="form-label">Número Fatura</label>
                        <input type="text" class="form-control" id="numero" name="numero">
                    </div>
                    <div class="mb-3">
                        <label for="codigo" class="form-label">Código</label>
                        <input type="text" class="form-control" id="codigo" name="codigo" required>
                    </div>
                    <div class="mb-3">
                        <label for="descricao" class="form-label">Descrição do Produto</label>
                        <input type="text" class="form-control" id="descricao" name="descricao" readonly>
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
    document.getElementById('formCadastroEstoque').addEventListener('submit', function(event) {
        event.preventDefault(); // Evita o envio tradicional do formulário

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
                    document.getElementById('formCadastroEstoque').reset();
                } else {
                    alert('Erro ao cadastrar estoque: ' + data.error);
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                alert('Ocorreu um erro ao cadastrar o cliente.');
            });
    });

    const campoCodigo = document.getElementById('codigo');
    const campoDescricao = document.getElementById('descricao');

    campoCodigo.addEventListener('blur', async function () {

        const codigo = this.value.trim();

        if (!codigo) {
            campoDescricao.value = '';
            return;
        }

        try {

            const response = await fetch(
                `./modulos/faturas/action/buscarProduto.php?codigo=${encodeURIComponent(codigo)}`
            );

            const produto = await response.json();

            if (produto.encontrado) {
                campoDescricao.value = produto.descricao;
            } else {
                campoDescricao.value = 'Produto não encontrado';
            }

        } catch (error) {
            console.error(error);
            campoDescricao.value = 'Erro ao buscar produto';
        }
    });
</script>

<?php
$estoqueJson = file_get_contents(__DIR__ . '/action/estoque.json');
$estoque = json_decode($estoqueJson, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    echo 'Erro ao decodificar estoque.json: ' . json_last_error_msg();
    $estoque = [];
}

$numero = array_column($estoque, 'numero');
$noRepeatNum = array_unique($numero);
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3" style="max-width: 700px;">
        <!-- Filtro -->
        <input type="text" class="form-control" id="filtro-estoque"
            style="width: 400px;" placeholder="Pesquisar por código ou descrição...">

        <!-- Botões -->
        <button class="btn btn-outline-secondary" id="btn-imprimir" title="Imprimir">
            🖨️ Imprimir
        </button>

        <!-- Exportar -->
        <button class="btn btn-outline-secondary" id="btn-exportar" title="Exportar CSV">
            Exportar CSV
        </button>

        <select class="btn btn-outline-secondary" id="btn-filtro">
            <option value="">Selecione a fatura</option>
            <?php foreach ($noRepeatNum as $nome): ?>
                <option value="<?= htmlspecialchars($nome) ?>">
                    <?= htmlspecialchars($nome) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <!-- Tabela Estoque -->
    <div id="mostrar-impressao">
        <table class="table table-bordered table-hover mt-3" id="tabela-estoque">
            <thead class="table-dark">
                <tr>
                    <th class="sortable filtravel">Descrição</th>
                    <th class="menor" id="mostrarEstoqueSelecionados">
                        <!-- alimentado via JS-->
                    </th>
                    <th class="menor">Estoque Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($estoque as $e):
                    $descricao = $e["descricao"] ?? '';
                    $numero    = $e["numero"]    ??  '';
                    $jsonAttr  = htmlspecialchars(json_encode($e), ENT_QUOTES, 'UTF-8');
                ?>
                    <tr data-json="<?= $jsonAttr ?>" class="table-warning linha-estoque" style="display: none;">
                        <td><?= htmlspecialchars($descricao) ?></td>
                        <td class="filtravel"><?= htmlspecialchars($numero) ?></td>
                    </tr>

                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <p id="sem-resultados" class="text-muted text-center" style="display: none;">
        Nenhum item encontrado para essa busca.
    </p>
</div>

<style>
    th.menor { width: 120px; }
    #tabela-estoque {width: 700px; }
    .table th { padding: 0.3rem; vertical-align: middle; text-align: center; }
    .action-buttons .btn { padding: 0.2rem 0.4rem; margin: right; width: 600px }
    .exportar { display: flex; margin-right: auto; }
    th.sortable { cursor: pointer; user-select: none; }
    
    @media print {
        body * { visibility: hidden; }
        #image, #image * { visibility: visible; }
        #mostrar-impressao, #mostrar-impressao * { visibility: visible; }
        #image { position: absolute; left: 0; top: 0; }
        #mostrar-impressao { position: absolute; left: 0; top: 50px; }
    }

</style>

<script>
    document.getElementById('btn-filtro').addEventListener('change', function () {
        const valorSelecionado = this.value; // pega o valor do select
        const linhas = document.querySelectorAll('.linha-estoque');
        let encontrou = false;

        if (valorSelecionado == '') {
            linhas.forEach(linha.linha.style.display = 'none');
            document.getElementById('sem-resultados').style.display = 'none';
            return;
        }

        linhas.forEach(function(linha) {
            const dados = JSON.parse(linha.getAttribute('data-json'));

            if (dados.numero === valorSelecionado) {
                linha.style.display = '';
                encontrou = true;
            } else {
                linha.style.display = 'none';
            }
        })

        document.getElementById('sem-resultado').style.display = encontrou ? 'none' : 'block';

    });
</script>