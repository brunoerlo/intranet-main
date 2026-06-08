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
  <button class="btn btn-success mb-3" type="button" data-bs-toggle="collapse" data-bs-target="#collapseCadastroProduto" aria-expanded="false" aria-controls="collapseCadastroProduto">
  <i class="fa-solid fa-plus"></i> Novo Produto
  </button>

  <div class="collapse" id="collapseCadastroProduto">
    <div class="card card-body">
      <h2 class="mb-4">Cadastro de Novo Produto</h2>
      <form id="formProduto" action="modulos/produtos/action/cadastrar_produto.php" method="POST" enctype="multipart/form-data">
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
      <label for="descricao_pt" class="form-label">Descrição (Português)</label>
      <input type="text" class="form-control" id="descricao_pt" name="descricao_pt" required>
    </div>

    <div class="mb-3">
      <label for="descricao_en" class="form-label">Descrição (Inglês)</label>
      <input type="text" class="form-control" id="descricao_en" name="descricao_en" required>
    </div>

    <div class="mb-3">
      <label for="descricao_es" class="form-label">Descrição (Espanhol)</label>
      <input type="text" class="form-control" id="descricao_es" name="descricao_es" required>
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
      <label for="ncm" class="form-label">NCM</label>
      <input type="text" class="form-control" id="ncm" name="ncm" required>
    </div>

    <div class="mb-3">
      <label for="peso" class="form-label">Peso Líquido (kg)</label>
      <input type="number" class="form-control" step="0.01" id="peso" name="peso" required>
    </div>

    <div class="mb-3">
      <label for="preco" class="form-label">Preço de Venda (R$ ou US$)</label>
      <input type="text" class="form-control" id="preco" name="preco" required>
    </div>

    <div class="mb-3">
      <label for="imagem" class="form-label">Imagem do Produto</label>
      <input class="form-control" type="file" id="imagem" name="imagem" accept="image/*" required>
    </div>

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
    form.reset();  // Limpa o formulário
  })
  .catch(error => {
    console.error('Erro:', error);
    alert('Ocorreu um erro ao cadastrar o produto.');
  });
});
</script>



<?php

// Caminhos atualizados
$produtosFile1 = __DIR__ . '/action/produto_cadastrado.json';
$produtosFile2 = __DIR__ . '/action/produtos.json';

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
?>

<!-- Bootstrap 5 CDN -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

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
            <select class="form-select" name="empresa_id" id="empresa_id" required>
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
    </div>

    <?php if (empty($produtosCadastrados) && empty($produtosImportados)): ?>
        <div class="alert alert-warning text-center">Nenhum produto encontrado.</div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-bordered table-hover table-striped align-middle text-center">
                <thead class="table-dark">
                    <tr>
                        <th>Item</th>
                        <th>Descrição</th>
                        <th>UM</th>
                        <th>Preço Venda</th>
                        <th>NCM</th>
                        <th>Peso Líquido</th>
                        <th>Valor Total</th>
                        <th>Empresa ID</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Produtos Cadastrados -->
                    <?php foreach ($produtosCadastrados as $produto): ?>
                        <tr class="produto-cadastrado" data-empresa="<?= htmlspecialchars($produto['empresa_id'] ?? '-') ?>">
                            <td><?= htmlspecialchars($produto['codigo'] ?? '-') ?></td>
                            <td><img src="modulos/produtos/action/<?= htmlspecialchars($produto['imagem'] ?? '-') ?>" style="width:50px; margin-right:30px"><?= htmlspecialchars($produto['descricao_pt'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($produto['unidade'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($empresasMoeda[$produto['empresa_id']] . ' ' . $produto['preco'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($produto['ncm'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($produto['peso'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($empresasMoeda[$produto['empresa_id']] . ' ' . $produto['preco'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($empresasRazao[$produto['empresa_id']] ?? '-') ?></td>
                        </tr>
                    <?php endforeach; ?>

                    <!-- Produtos Importados -->
                    <?php foreach ($produtosImportados as $produto): ?>
                        <tr class="produto-importado" data-empresa="<?= htmlspecialchars($produto['empresa_id'] ?? '-') ?>">
                            <td><?= htmlspecialchars($produto['Item'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($produto['Descricao'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($produto['UM'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($produto['Preco Venda'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($produto['NCM'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($produto['Peso Liquido'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($empresasMoeda[$produto['empresa_id']] . ' ' . $produto['Vlr Total'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($empresasRazao[$produto['empresa_id']] ?? '-') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- JavaScript para filtragem sem recarregar a página -->
<script>
    function aplicarFiltrosProdutos() {
        const tipoProdutoSelect = document.getElementById('tipoProduto');
        const empresaSelect = document.getElementById('empresa_id');

        const tipoSelecionado = tipoProdutoSelect.value;
        const empresaSelecionada = empresaSelect.value;

        const linhas = document.querySelectorAll('tr[data-empresa]');

        linhas.forEach(function (linha) {
            const classe = linha.classList.contains('produto-cadastrado') ? 'cadastrados'
                         : linha.classList.contains('produto-importado') ? 'importados'
                         : '';
            const empresaId = linha.getAttribute('data-empresa');

            const exibirTipo = (tipoSelecionado === 'todos') || (classe === tipoSelecionado);
            const exibirEmpresa = (empresaSelecionada === '') || (empresaId === empresaSelecionada);

            linha.style.display = (exibirTipo && exibirEmpresa) ? '' : 'none';
        });
    }

    // Associa os eventos
    document.addEventListener('change', function(e) {
        if (e.target && (e.target.id === 'tipoProduto' || e.target.id === 'empresa_id')) {
            aplicarFiltrosProdutos();
        }
    });

    // ⚠️ Chame `aplicarFiltrosProdutos()` manualmente após carregar dinamicamente os elementos,
    // por exemplo, após um fetch/ajax ou append via JS.
</script>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
