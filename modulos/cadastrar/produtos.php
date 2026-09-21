<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: text/html; charset=utf-8');

$empresasPath = __DIR__ . '/../configuracao/action/empresas.json';
$empresasMoeda = [];
$empresasRazao = [];

if (file_exists($empresasPath)) {
    $empresas = json_decode(file_get_contents($empresasPath), true);
    foreach ($empresas as $empresa) {
        $empresasRazao[$empresa['id']] = $empresa['razaoSocial'];
        $empresasMoeda[$empresa['id']] = $empresa['moeda'] ?? 'R$'; // padrão R$ se não tiver definido
    }
}

?>

<div class="container mt-4">
    <div class="d-flex gap-2 mb-3">
        <button class="btn btn-success mb-3" type="button" data-bs-toggle="collapse" data-bs-target="#collapseCadastroProduto" aria-expanded="false" aria-controls="collapseCadastroProduto">
            <i class="fa-solid fa-plus"></i> Novo Produto
        </button>
        <button class="btn btn-success mb-3" type="button" data-bs-toggle="collapse" data-bs-target="#collapseImportarProduto" aria-expanded="false" aria-controls="collapseImportarProduto">
            <i class="fa-solid fa-plus"></i> Importar Produto
        </button>
    </div>

    <div class="collapse" id="collapseCadastroProduto">
        <div class="card card-body">
            <h2 class="mb-4">Cadastro de Novo Produto</h2>
            <form id="formProduto" action="modulos/cadastrar/action/produtos/cadastrar_produto.php" method="POST" enctype="multipart/form-data">
                <!-- todo conteúdo do seu formulário aqui, intacto -->
                <!-- exemplo: -->
                <div class="mb-3">
                    <label for="empresa_id" class="form-label">Selecione a Empresa:</label>
                    <select class="form-select" name="empresa_id" id="empresa_id" required>
                        <option value="">-- Selecione --</option>
                        <?php
                        if (!empty($empresas)) {
                            foreach ($empresas as $empresa) {
                                echo "<option value=\"{$empresa['id']}\">{$empresa['razaoSocial']}</option>";
                            }
                        }
                        ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="Item" class="form-label">Código (BM xxx.xxx)</label>
                    <input type="text" class="form-control" id="Item" name="Item" pattern="BM\s\d{3}\.\d{3}" placeholder="BM 123.456" required>
                </div>

                <div class="mb-3">
                    <label for="Descricao" class="form-label">Descrição (Português)</label>
                    <input type="text" class="form-control" id="descricao_pt" name="descricao_pt" required>
                </div>

                <div class="mb-3">
                    <label for="Descricao Complementar" class="form-label">Descrição Complementar</label>
                    <input type="text" class="form-control" id="Descricao Complementar" name="Descricao Complementar" required>
                </div>

                <div class="mb-3">
                    <label for="UM" class="form-label">Unidade de Comercialização</label>
                    <select class="form-select" id="UM" name="UM" required>
                        <option value="">Selecione</option>
                        <option value="UN">UN</option>
                        <option value="KG">KG</option>
                        <option value="MT">MT</option>
                        <option value="JG">JG</option>
                        <option value="GL">GL</option>
                        <option value="TO">TO</option>
                        <option value="RL">RL</option>
                        <option value="M2">M2</option>
                        <option value="PR">PR</option>
                        <option value="PC">PC</option>
                        <option value="FD">FD</option>
                        <option value="CJ">CJ</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="Preco Venda" class="form-label">Preço de Venda (R$ ou US$)</label>
                    <input type="text" class="form-control" id="Preco Venda" name="Preco Venda">
                </div>

                <div class="mb-3">
                    <label for="NCM" class="form-label">NCM</label>
                    <input type="text" class="form-control" id="NCM" name="NCM" required>
                </div>

                <div class="mb-3">
                    <label for="Peso Liquido" class="form-label">Peso Líquido (kg)</label>
                    <input type="number" class="form-control" step="0.01" id="Peso Liquido" name="Peso Liquido">
                </div>

                <div class="mb-3">
                    <label for="Vlr Total" class="form-label">Valor Total (R$ ou US$)</label>
                    <input type="text" class="form-control" id="Vlr Total" name="Vlr Total">
                </div>
                <!--
                <div class="mb-3">
                    <label for="imagem" class="form-label">Imagem do Produto</label>
                    <input class="form-control" type="file" id="imagem" name="imagem" accept="image/*">
                </div>
                -->
                <button type="submit" class="btn btn-primary">Cadastrar Produto</button>
            </form>
        </div>
    </div>
</div>

<script>
    document.getElementById('formProduto').addEventListener('submit', function(e) {
        e.preventDefault(); // Evita o envio padrão

        const form = e.target;
        const formData = new FormData(form);

        fetch(form.action, {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(result => {
                alert(result); // Você pode substituir por um modal ou mensagem no DOM
                form.reset(); // Limpa o formulário
            })
            .catch(error => {
                console.error('Erro:', error);
                alert('Ocorreu um erro ao cadastrar o produto.');
            });
    });
</script>

<!-- Importar -->

<div class="container mt-5">

    <div class="collapse" id="collapseImportarProduto">
        <div class="card card-body">
            <h2>Importar Produtos</h2>
            <form id="importForm" class="mt-4" enctype="multipart/form-data">
                <div class="mb-3">
                    <label for="empresa_id" class="form-label">Selecione a Empresa:</label>
                    <select class="form-select" name="empresa_id" id="empresa_id" required>
                        <option value="">-- Selecione --</option>
                        <?php
                        $empresasPath = __DIR__ . '/../configuracao/action/empresas.json';
                        if (file_exists($empresasPath)) {
                            $empresas = json_decode(file_get_contents($empresasPath), true);
                            foreach ($empresas as $empresa) {
                                echo "<option value=\"{$empresa['id']}\">{$empresa['razaoSocial']}</option>";
                            }
                        }
                        ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="csvFile" class="form-label">Escolha o arquivo CSV:</label>
                    <input class="form-control" type="file" name="csvFile" id="csvFile" accept=".csv" required>
                </div>

                <button type="submit" class="btn btn-primary">Importar</button>
            </form>
            <div id="result" class="mt-3"></div>
        </div>

        <script>
            document.getElementById('importForm').addEventListener('submit', function(event) {
                event.preventDefault();

                const formData = new FormData(this);

                fetch('./modulos/cadastrar/action/produtos/importar_produtos.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        const resultDiv = document.getElementById('result');
                        if (data.status === 'success') {
                            resultDiv.innerHTML = '<div class="alert alert-success">Produtos importados com sucesso!</div>';
                        } else if (data.error) {
                            resultDiv.innerHTML = `<div class="alert alert-danger">Erro ao importar Produtos: ${data.error}</div>`;
                        }
                    })
                    .catch(error => {
                        document.getElementById('result').innerHTML = `<div class="alert alert-danger">Erro ao importar Produtos: ${error.message}</div>`;
                    });
            });
        </script>
    </div>
</div>

<?php

// Caminhos atualizados
$produtosFile1 = __DIR__ . '/action/produtos/produto_cadastrado.json';
$produtosFile2 = __DIR__ . '/action/produtos/produtos.json';

$produtosCadastrados = [];
$produtosImportados = [];

// Lê os produtos de produto_cadastrado.json
if (file_exists($produtosFile1)) {
    $jsonContent1 = file_get_contents($produtosFile1);
    $produtosCadastrados = json_decode($jsonContent1, true);
}

// Lê os produtos de produtos.json
if (file_exists($produtosFile2)) {
    $jsonContent2 = file_get_contents($produtosFile2);
    $produtosImportados = json_decode($jsonContent2, true);
}

$todosProdutos = [];

if (!empty($produtosCadastrados)) {
    foreach ($produtosCadastrados as $p) {
        $todosProdutos[] = [
            'tipo'       => 'cadastrado',
            'codigo'     => $p['codigo']        ?? '-',
            'descricao'  => $p['descricao_pt']  ?? '-',
            'descComp'   => '',
            'unidade'    => $p['unidade']       ?? '-',
            'preco'      => $p['preco']         ?? '-',
            'ncm'        => $p['ncm']           ?? '-',
            'peso'       => $p['peso']          ?? '-',
            'empresa_id' => $p['empresa_id']    ?? '-',
            'imagem'     => $p['imagem']        ?? '',
        ];
    }
}

if (!empty($produtosImportados)) {
    foreach ($produtosImportados as $p) {
        $todosProdutos[] = [
            'tipo'       => 'importado',
            'codigo'     => $p['Item']        ?? '-',
            'descricao'  => $p['Descricao']   ?? '-',
            'descComp'   => $p['Descricao Complementar'] ?? '-',
            'unidade'    => $p['UM']          ?? '-',
            'preco'      => $p['Preco Venda'] ?? '-',
            'ncm'        => $p['NCM']         ?? '-',
            'peso'       => $p['peso']        ?? '-',
            'empresa_id' => $p['empresa_id']  ?? '-',
            'imagem'     => $p['imagem']      ?? '',
        ];
    }
}

$limite = isset($_GET['limite']) ? intval($_GET['limite']) : 30; // 30 por padrão
$paginaAtual = isset($_GET['p']) ? intval($_GET['p']) : 1;

$totalRegistros = count($todosProdutos);
$totalPaginas = ceil($totalRegistros / $limite);

$offset = ($paginaAtual - 1) * $limite; // primeiro elemento
$produtosPaginados = array_slice($todosProdutos, $offset, $limite);
?>

<div class="container py-5">

    <h1 class="mb-4 text-center">Lista de Produtos</h1>
    <!-- Formulário para selecionar tipo de produto e empresa lado a lado -->
    <div class="row mb-4">
        <div class="col-md-4">
            <select id="tipoProduto" class="form-select">
                <option value="todos">Todos os Produtos</option>
                <option value="cadastrados">Produtos Cadastrados</option>
                <option value="importados">Produtos Importados</option>
            </select>
        </div>
        <div class="col-md-4">
            <select class="form-select" name="filtro_empresa_id" id="filtro_empresa_id">
                <option value="">-- Selecione a Empresa --</option>
                <?php
                $empresasPath = __DIR__ . '/../configuracao/action/empresas.json';
                if (file_exists($empresasPath)) {
                    $empresas = json_decode(file_get_contents($empresasPath), true);
                    foreach ($empresas as $empresa) {
                        echo "<option value=\"{$empresa['id']}\">{$empresa['razaoSocial']}</option>";
                    }
                }
                ?>
            </select>
        </div>
        <div class="col-md-4">
            <select name="seletorLimite" id="seletorLimite" class="form-select form-select-sm" style="width: 100px">
                <option value="15" <?= $limite == 15 ? 'selected' : '' ?>>15</option>
                <option value="30" <?= $limite == 30 ? 'selected' : '' ?>>30</option>
                <option value="50" <?= $limite == 50 ? 'selected' : '' ?>>50</option>
                <option value="75" <?= $limite == 75 ? 'selected' : '' ?>>75</option>
                <option value="100" <?= $limite == 100 ? 'selected' : '' ?>>100</option>
            </select>
        </div>
    </div>

    <?php if (empty($produtosCadastrados) && empty($produtosImportados)): ?>
        <div class="alert alert-warning text-center">Nenhum produto encontrado.</div>
    <?php else: ?>
        <div class="table-responsive shadow-sm rounded mb-3">
            <table class="table table-striped table-hover table-bordered align-middle mb-0 text-center">
                <thead class="table-dark">
                    <tr>
                        <th>Item</th>
                        <th>Descrição</th>
                        <th>UM</th>
                        <th>Preço Venda</th>
                        <th>NCM</th>
                        <th>Peso Líquido</th>
                        <th>Valor Total</th>
                        <th>Empresa</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($produtosPaginados as $produto): ?>
                        <tr class="<?= $produto['tipo'] === 'cadastrado' ? 'produto-cadastrado' : 'produto-importado' ?>"
                            data-empresa="<?= htmlspecialchars($produto['empresa_id']) ?>">

                            <td><?= htmlspecialchars($produto['codigo']) ?></td>

                            <td>
                                <!-- Mostra imagem só se for cadastrado e existir -->
                                <?php if (!empty($produto['imagem'])): ?>
                                    <img src="modulos/cadastrar/action/produtos/<?= htmlspecialchars($produto['imagem']) ?>" style="width:50px; margin-right:30px" onerror="this.style.display='none'">
                                <?php endif; ?>
                                <?= htmlspecialchars($produto['descricao']) ?>
                            </td>

                            <td><?= htmlspecialchars($produto['unidade']) ?></td>
                            <td><?= htmlspecialchars(($empresasMoeda[$produto['empresa_id']] ?? '') . ' ' . $produto['preco']) ?></td>
                            <td><?= htmlspecialchars($produto['ncm']) ?></td>
                            <td><?= htmlspecialchars($produto['peso']) ?></td>

                            <!-- Coluna de Valor Total e Empresa que você já tinha -->
                            <td><?= htmlspecialchars(($empresasMoeda[$produto['empresa_id']] ?? '') . ' ' . $produto['preco']) ?></td>
                            <td><?= htmlspecialchars($empresasRazao[$produto['empresa_id']] ?? '-') ?></td>

                            <td class="action-buttons text-center">
                                <div class="d-inline-flex gap-1">
                                    <button class="btn btn-warning btn-sm edit-cad-btn">
                                        <i class="fa-solid fa-pen"></i>
                                    </button>
                                    <button class="btn btn-danger btn-sm delete-btn" data-id="<?= htmlspecialchars($produto['codigo']) ?>">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <nav>
                <ul class="pagination justify-content-center mt-4">
                    <!-- Botão Anterior -->
                    <li class="page-item <?= ($paginaAtual <= 1) ? 'disabled' : '' ?>">
                        <a class="page-link link-paginacao" href="?p=<?= max(1, $paginaAtual - 1) ?>&limite=<?= $limite ?>">Anterior</a>
                    </li>

                    <?php
                    $adjacents = 2; // Define a "janela" de botões (ex: 2 pra esquerda, 2 pra direita)
                    $inicioLoop = max(1, $paginaAtual - $adjacents);
                    $fimLoop = min($totalPaginas, $paginaAtual + $adjacents);
                    
                    // Ajuste fino para sempre mostrar o mesmo tamanho de bloco se estiver no comecinho ou finalzinho
                    if ($paginaAtual <= $adjacents) {
                        $fimLoop = min($totalPaginas, 1 + ($adjacents * 2));
                    }
                    if ($paginaAtual > $totalPaginas - $adjacents) {
                        $inicioLoop = max(1, $totalPaginas - ($adjacents * 2));
                    }

                    // Se a janela não começar no 1, mostra o 1 e os 3 pontinhos...
                    if ($inicioLoop > 1) {
                        echo '<li class="page-item"><a class="page-link link-paginacao" href="?p=1&limite=' . $limite . '">1</a></li>';
                        if ($inicioLoop > 2) {
                            echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                        }
                    }

                    // Laço de repetição só para a "janela" visível
                    for ($i = $inicioLoop; $i <= $fimLoop; $i++):
                    ?>
                        <li class="page-item <?= $i == $paginaAtual ? 'active' : '' ?>">
                            <a class="page-link link-paginacao" href="?p=<?= $i ?>&limite=<?= $limite ?>">
                                <?= $i ?>
                            </a>
                        </li>
                    <?php endfor; ?>

                    <?php
                    // Se a janela não terminar na última página, mostra os 3 pontinhos... e a última página
                    if ($fimLoop < $totalPaginas) {
                        if ($fimLoop < $totalPaginas - 1) {
                            echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                        }
                        echo '<li class="page-item"><a class="page-link link-paginacao" href="?p=' . $totalPaginas . '&limite=' . $limite . '">' . $totalPaginas . '</a></li>';
                    }
                    ?>

                    <!-- Botão Próximo -->
                    <li class="page-item <?= ($paginaAtual >= $totalPaginas) ? 'disabled' : '' ?>">
                        <a class="page-link link-paginacao" href="?p=<?= min($totalPaginas, $paginaAtual + 1) ?>&limite=<?= $limite ?>">Próxima</a>
                    </li>
                </ul>
                
                <div class="text-center text-muted small mb-4">
                    Exibindo página <?= $paginaAtual ?> de <?= $totalPaginas ?> (Total: <?= $totalRegistros ?> produtos)
                </div>
            </nav>
        </div>
    <?php endif; ?>
</div>

<!-- JavaScript para filtragem sem recarregar a página -->
<script>
    function recarregarTabela(queryString) {
        const contentDiv = document.getElementById("modulo-content");
        const url = `carregar_modulo.php?modulo=cadastrar&submodulo=produtos&${queryString}`;

        fetch(url)
            .then(response => response.text())
            .then(html => {
                const tempDiv = document.createElement("div");
                tempDiv.innerHTML = html;
                
                const scripts = tempDiv.querySelectorAll("script");
                scripts.forEach(s => s.remove());
                
                contentDiv.innerHTML = tempDiv.innerHTML;
                
                scripts.forEach(oldScript => {
                    const newScript = document.createElement("script");
                    Array.from(oldScript.attributes).forEach(attr => newScript.setAttribute(attr.name, attr.value));
                    newScript.textContent = oldScript.textContent;
                    document.body.appendChild(newScript);
                });
            })
            .catch(error => console.error("Erro ao carregar paginação:", error));
    }
    // Escuta a mudança na caixinha de limites (15, 30, 50)
    document.getElementById('seletorLimite').addEventListener('change', function() {
        const novoLimite = this.value;
        // Quando muda o limite, voltamos para a página 1 por garantia
        recarregarTabela(`p=1&limite=${novoLimite}`);
    });

    // Escuta os cliques nos números da página
    document.querySelectorAll('.link-paginacao').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const parametros = this.getAttribute('href').split('?')[1];
            recarregarTabela(parametros);
        });
    });

    function aplicarFiltrosProdutos() {
        const tipoProdutoSelect = document.getElementById('tipoProduto');
        const empresaSelect = document.getElementById('filtro_empresa_id');

        const tipoSelecionado = tipoProdutoSelect.value;
        const empresaSelecionada = empresaSelect.value;

        const linhas = document.querySelectorAll('tr[data-empresa]');

        linhas.forEach(function(linha) {
            const classe = linha.classList.contains('produto-cadastrado') ? 'cadastrados' :
                linha.classList.contains('produto-importado') ? 'importados' :
                '';
            const empresaId = linha.getAttribute('data-empresa');

            const exibirTipo = (tipoSelecionado === 'todos') || (classe === tipoSelecionado);
            const exibirEmpresa = (empresaSelecionada === '') || (empresaId === empresaSelecionada);

            linha.style.display = (exibirTipo && exibirEmpresa) ? '' : 'none';
        });
    }

    // Associa os eventos
    document.addEventListener('change', function(e) {
        if (e.target && (e.target.id === 'tipoProduto' || e.target.id === 'filtro_empresa_id')) {
            aplicarFiltrosProdutos();
        }
    });

    // ⚠️ Chame `aplicarFiltrosProdutos()` manualmente após carregar dinamicamente os elementos,
    // por exemplo, após um fetch/ajax ou append via JS.

    // EDITAR
    document.querySelectorAll('.edit-cad-btn').forEach(button => {
        button.addEventListener('click', function() {
            const row = this.closest('tr');
            const editRow = row.nextElementSibling;

            if (editRow && editRow.classList.contains('edit-row')) {
                const form = editRow.querySelector('.edit-form-cad');
                if (!form) return;

                // Alterna a exibição da linha de edição
                if (editRow.style.display === 'none' || editRow.style.display === '') {
                    // Pega os dados do atributo data-json da linha
                    const jsonData = JSON.parse(row.getAttribute('data-json'));

                    // Preenche os campos do formulário
                    if (form.elements['id']) form.elements['id'].value = jsonData['id'] || '';
                    if (form.elements['nomeFatura']) form.elements['nomeFatura'].value = jsonData['nomeFatura'] || '';
                    if (form.elements['cliente']) form.elements['cliente'].value = jsonData['cliente'] || '';
                    if (form.elements['nomeConsolidado']) form.elements['nomeConsolidado'].value = jsonData['nomeConsolidado'] || '';
                    if (form.elements['data']) form.elements['data'].value = jsonData['data'] || '';
                    if (form.elements['ctr']) form.elements['ctr'].value = jsonData['ctr'] || '';
                    if (form.elements['porto']) form.elements['porto'].value = jsonData['porto'] || '';
                    if (form.elements['booking']) form.elements['booking'].value = jsonData['booking'] || '';

                    editRow.style.display = 'table-row';
                } else {
                    editRow.style.display = 'none';
                }
            }
        });
    });

    // CANCELAR EDIÇÃO
    document.querySelectorAll('.cancel-btn').forEach(button => {
        button.addEventListener('click', function() {
            const editRow = this.closest('.edit-row');
            if (editRow) {
                editRow.style.display = 'none';
            }
        });
    });

    // SALVAR EDIÇÃO
    document.querySelectorAll('.edit-form-cad').forEach(form => {
        form.addEventListener('submit', function(event) {
            event.preventDefault();
            const formData = new FormData(this);
            fetch('./modulos/cadastrar/action/produtos/updateProduto.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Atualizado com sucesso!');
                        location.reload();
                    } else {
                        alert('Erro ao atualizar produto: ' + (data.message || data.error || 'Erro desconhecido'));
                    }
                })
                .catch(error => {
                    console.error('Erro:', error);
                    alert('Ocorreu um erro ao tentar atualizar o produto.');
                });
        });
    });

    document.querySelectorAll('.delete-btn').forEach(button => {
        button.addEventListener('click', function() {
            const faturaId = this.getAttribute('data-id');
            if (confirm("Tem certeza que deseja excluir este produto?")) {
                fetch('./modulos/cadastrar/action/produtos/deleteProduto.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            id: faturaId
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert('Produto excluído com sucesso!');
                            location.reload();
                        } else {
                            alert('Erro ao excluir produto: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Erro:', error);
                        alert('Ocorreu um erro ao tentar excluir o produto.');
                    });
            }
        });
    })
</script>