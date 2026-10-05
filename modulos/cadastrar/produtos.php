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

<div class="container-fluid mt-4">
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
            <h2 class="mb-3">Cadastro de Novo Produto</h2>
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
                    <label for="codigo" class="form-label">Código (BM xxx.xxx)</label>
                    <input type="text" class="form-control" id="codigo" name="codigo" pattern="BM\s\d{3}\.\d{3}" placeholder="BM 123.456" required>
                </div>

                <div class="mb-3">
                    <label for="descricao" class="form-label">Descrição (Português)</label>
                    <input type="text" class="form-control" id="descricao" name="descricao" required>
                </div>

                <div class="mb-3">
                    <label for="descComp" class="form-label">Descrição Complementar</label>
                    <input type="text" class="form-control" id="descComp" name="descComp">
                </div>

                <div class="mb-3">
                    <label for="unidade" class="form-label">Unidade de Comercialização</label>
                    <select class="form-select" id="unidade" name="unidade" required>
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
                    <label for="preco" class="form-label">Preço de Venda (R$ ou US$)</label>
                    <input type="text" class="form-control" id="preco" name="preco">
                </div>

                <div class="mb-3">
                    <label for="ncm" class="form-label">NCM</label>
                    <input type="text" class="form-control" id="ncm" name="ncm" required>
                </div>

                <div class="mb-3">
                    <label for="peso" class="form-label">Peso Líquido (kg)</label>
                    <input type="number" class="form-control" step="0.01" id="peso" name="peso">
                </div>
                <!--
                <div class="mb-3">
                    <label for="Vlr Total" class="form-label">Valor Total (R$ ou US$)</label>
                    <input type="text" class="form-control" id="Vlr Total" name="Vlr Total">
                </div>
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

<!-- Importar -->

<div class="collapse" id="collapseImportarProduto">
    <div class="card card-body">
        <h2>Importar Produtos</h2>
        <form id="importFormProdutos" class="mt-4" enctype="multipart/form-data">
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
            'descricao'  => $p['descricao']  ?? '-',
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
            'ncm'        => $p['N.C.M.']         ?? '-',
            'peso'       => $p['peso']        ?? '-',
            'empresa_id' => $p['empresa_id']  ?? '-',
            'imagem'     => $p['imagem']      ?? '',
        ];
    }
}

$filtroTipo = $_GET['tipoProduto'] ?? 'todos';
$filtroEmpresaId = $_GET['filtro_empresa_id'] ?? '';
$termoBusca = $_GET['termo_busca'] ?? '';

$produtosFiltrados = array_filter($todosProdutos, function ($produto) use ($filtroTipo, $filtroEmpresaId, $termoBusca) {
    if (
        $filtroTipo !== 'todos' && $filtroTipo !== '' &&
        strtolower($produto['tipo'] ?? '') !== strtolower($filtroTipo)
    ) return false;

    if ($termoBusca !== '') {
        $codigo = $produto['codigo'] ?? '';
        $descricao = $produto['descricao'] ?? '';
        if (stripos($codigo, $termoBusca) === false && stripos($descricao, $termoBusca) === false)
            return false;
    }

    if ($filtroEmpresaId !== '' && strtolower($produto['empresa_id'] ?? '') !== strtolower($filtroEmpresaId)) return false;

    return true;
});

// Usa 'p' em vez de 'page' para não conflitar com rotas principais do sistema
$p = max(1, intval($_GET['p'] ?? 1));

// Captura o limite da URL, ou usa 30 como padrão
$limitePorPagina = isset($_GET['limite']) ? max(1, intval($_GET['limite'])) : 30;

$totalProdutos = count($produtosFiltrados);
$totalPaginas = ceil($totalProdutos / $limitePorPagina);
if ($p > $totalPaginas && $totalPaginas > 0) $p = $totalPaginas;

$offset = ($p - 1) * $limitePorPagina;
$produtosPaginados = array_slice($produtosFiltrados, $offset, $limitePorPagina);

// Helper para gerar URL da paginação sem perder os filtros e rotas atuais
function gerarUrlPaginacao($novaPagina)
{
    $params = $_GET;
    $params['p'] = $novaPagina;
    unset($params['modulo']);
    unset($params['submodulo']);
    return '?' . http_build_query($params);
}
?>

<div class="container py-3">
    <h1 class="mb-4 text-center">Lista de Produtos</h1>
    <!-- Formulário para selecionar tipo de produto e empresa lado a lado -->
    <form id="formFiltroProdutos" method="GET" action="" class="bg-white p-3 rounded shadow-sm mb-4 border">
        <div class="row g-3 align-items-center">
            <div class="col-md-3">
                <div class="form-floating">
                    <select id="tipoProduto" name="tipoProduto" class="form-select">
                        <option value="todos" <?= $filtroTipo === 'todos' ? 'selected' : '' ?>>Todos os Produtos</option>
                        <option value="cadastrado" <?= $filtroTipo === 'cadastrado' ? 'selected' : '' ?>>Cadastrados</option>
                        <option value="importado" <?= $filtroTipo === 'importado' ? 'selected' : '' ?>>Importados</option>
                    </select>
                    <label for="tipoProduto"><i class="fa-solid fa-filter text-primary"></i> Tipo</label>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-floating">
                    <select class="form-select" name="filtro_empresa_id" id="filtro_empresa_id">
                        <option value="">Todas as Empresas</option>
                        <?php
                        $empresasPath = __DIR__ . '/../configuracao/action/empresas.json';
                        if (file_exists($empresasPath)) {
                            $empresas = json_decode(file_get_contents($empresasPath), true);
                            foreach ($empresas as $empresa) {
                                $selected = ($filtroEmpresaId == $empresa['id']) ? 'selected' : '';
                                echo "<option value=\"{$empresa['id']}\" $selected>{$empresa['razaoSocial']}</option>";
                            }
                        }
                        ?>
                    </select>
                    <label for="filtro_empresa_id"><i class="fa-solid fa-building text-primary"></i> Empresa</label>
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-floating">
                    <select name="limite" id="seletorLimiteProdutos" class="form-select">
                        <option value="15" <?= $limitePorPagina == 15 ? 'selected' : '' ?>>15 itens</option>
                        <option value="30" <?= $limitePorPagina == 30 ? 'selected' : '' ?>>30 itens</option>
                        <option value="50" <?= $limitePorPagina == 50 ? 'selected' : '' ?>>50 itens</option>
                        <option value="75" <?= $limitePorPagina == 75 ? 'selected' : '' ?>>75 itens</option>
                        <option value="100" <?= $limitePorPagina == 100 ? 'selected' : '' ?>>100 itens</option>
                    </select>
                    <label for="seletorLimiteProdutos"><i class="fa-solid fa-list text-primary"></i> Exibição</label>
                </div>
            </div>
            <div class="col-md-4">
                <div class="d-flex gap-2 h-100">
                    <div class="form-floating flex-grow-1">
                        <input type="text" class="form-control" id="filtro-produtos" name="termo_busca" placeholder="Pesquisar..." style="height: 58px;">
                        <label for="filtro-produtos"><i class="fa-solid fa-magnifying-glass text-muted"></i> Buscar produto</label>
                    </div>
                    <button class="btn btn-success d-flex flex-column align-items-center justify-content-center px-3" id="btn-exportar-produtos" title="Exportar CSV Produtos" style="height: 58px;" type="button">
                        <i class="fa-solid fa-file-csv fs-5 mb-1"></i>
                        <span style="font-size: 0.75rem; font-weight: 600;">Exportar</span>
                    </button>
                </div>
            </div>
        </div>
    </form>

    <div class="table-responsive shadow-sm rounded mb-2">
        <table class="table table-hover table-bordered align-middle mb-0 text-center">
            <thead class="table-dark">
                <tr>
                    <th style="width: 110px;" class="sortable">Item</th>
                    <th class="sortable">Descrição</th>
                    <th>UM</th>
                    <th style="width: 115px;">Preço Venda</th>
                    <th>NCM</th>
                    <th style="width: 115px;">Peso Líquido</th>
                    <th>Empresa</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($produtosFiltrados)): ?>
                    <div class="alert alert-info text-mute text-center">Nenhum produto encontrado.</div>
                <?php else: ?>
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

                            <!-- Coluna de Valor Total e Empresa que você já tinha 
                            <td><?= htmlspecialchars(($empresasMoeda[$produto['empresa_id']] ?? '') . ' ' . $produto['preco']) ?></td>
                            -->
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
                        <tr class="edit-row" style="display: none;">
                            <td colspan="9">
                                <form class="edit-form-cad-produtos p-2">
                                    <input type="hidden" name="item" value="<?= htmlspecialchars($produto['codigo'] ?? '') ?>">

                                    <div class="row g-2 mb-2 text-start">
                                        <div class="col-md-4">
                                            <label class="form-label mb-0 small">Descrição</label>
                                            <input type="text" class="form-control form-control-sm" name="descricao" value="<?= htmlspecialchars($produto['descricao']) ?>" required>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label mb-0 small">Descrição Complementar</label>
                                            <input type="text" class="form-control form-control-sm" name="descComp" value="<?= htmlspecialchars($produto['descComp']) ?>">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label mb-0 small">Empresa</label>
                                            <select class="form-select form-select-sm" name="empresa_id" required>
                                                <option value="">-- Selecione --</option>
                                                <?php
                                                if (!empty($empresas)) {
                                                    foreach ($empresas as $empresa) {
                                                        $selected = ($produto['empresa_id'] == $empresa['id']) ? 'selected' : '';
                                                        echo "<option value=\"{$empresa['id']}\" $selected>{$empresa['razaoSocial']}</option>";
                                                    }
                                                }
                                                ?>
                                            </select>
                                        </div>
                                        <div class="col-md-1">
                                            <label class="form-label mb-0 small">UM</label>
                                            <select class="form-select form-select-sm" name="unidade" required>
                                                <?php $selected = $produto['unidade']; ?>
                                                <option value="<?= $selected ?>"><?= $selected ?></option>
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
                                    </div>

                                    <div class="row g-2 align-items-end text-start">
                                        <div class="col-md-3">
                                            <label class="form-label mb-0 small">Preço de Venda</label>
                                            <input type="text" class="form-control form-control-sm" name="preco" value="<?= htmlspecialchars($produto['preco']) ?>">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label mb-0 small">NCM</label>
                                            <input type="text" class="form-control form-control-sm" name="ncm" value="<?= htmlspecialchars($produto['ncm']) ?>" required>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label mb-0 small">Peso Líquido</label>
                                            <input type="text" class="form-control form-control-sm" name="peso" value="<?= htmlspecialchars($produto['peso']) ?>">
                                        </div>
                                        <div class="col-md-3 text-end pb-1">
                                            <button type="submit" class="btn btn-sm btn-success me-1" title="Salvar"><i class="fa-solid fa-check"></i> Salvar</button>
                                            <button type="button" class="btn btn-sm btn-secondary cancel-btn" title="Cancelar"><i class="fa-solid fa-xmark"></i> Cancelar</button>
                                        </div>
                                    </div>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
            </tbody>
        </table>
        <!-- Navegação de Paginação -->
        <nav>
            <ul class="pagination justify-content-center mt-4">
                <!-- Botão Anterior -->
                <li class="page-item <?= ($p <= 1) ? 'disabled' : '' ?>">
                    <a class="page-link link-paginacao" href="<?= gerarUrlPaginacao($p - 1) ?>">Anterior</a>
                </li>

                <?php
                    $adjacents = 2; // Define a "janela" de botões (ex: 2 pra esquerda, 2 pra direita)
                    $inicioLoop = max(1, $p - $adjacents);
                    $fimLoop = min($totalPaginas, $p + $adjacents);

                    // Ajuste fino para sempre mostrar o mesmo tamanho de bloco se estiver no comecinho ou finalzinho
                    if ($p <= $adjacents) {
                        $fimLoop = min($totalPaginas, 1 + ($adjacents * 2));
                    }
                    if ($p > $totalPaginas - $adjacents) {
                        $inicioLoop = max(1, $totalPaginas - ($adjacents * 2));
                    }

                    // Se a janela não começar no 1, mostra o 1 e os 3 pontinhos...
                    if ($inicioLoop > 1) {
                        echo '<li class="page-item"><a class="page-link link-paginacao" href="' . gerarUrlPaginacao(1) . '">' . 1 . '</a></li>';
                        if ($inicioLoop > 2) {
                            echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                        }
                    }

                    // Laço de repetição só para a "janela" visível
                    for ($i = $inicioLoop; $i <= $fimLoop; $i++):
                ?>
                    <li class="page-item <?= $i == $p ? 'active' : '' ?>">
                        <a class="page-link link-paginacao" href="<?= gerarUrlPaginacao($i) ?>">
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
                        echo '<li class="page-item"><a class="page-link link-paginacao" href="' . gerarUrlPaginacao($totalPaginas) . '">' . $totalPaginas . '</a></li>';
                    }
                ?>

                <!-- Botão Próximo -->
                <li class="page-item <?= ($p >= $totalPaginas) ? 'disabled' : '' ?>">
                    <a class="page-link link-paginacao" href="<?= gerarUrlPaginacao($p + 1) ?>">Próxima</a>
                </li>
            </ul>

            <div class="text-center text-muted small mb-4">
                Exibindo página <?= $p ?> de <?= $totalPaginas ?> (Total: <?= $totalProdutos ?> produtos)
            </div>
        </nav>
    </div>
<?php endif; ?>
</div>

<style>
    th.sortable {
        cursor: pointer;
        user-select: none;
    }
</style>

<script>
    window.planilhaProdutos = <?= json_encode(array_values($produtosFiltrados)) ?>
</script>

<script src="js/cadastrar/produtos.js"></script>