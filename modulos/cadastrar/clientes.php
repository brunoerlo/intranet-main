<!-- CADASTRAR -->

<div class="container-fluid mt-4">
    <div class="d-flex gap-2 mb-3">
        <button class="btn btn-success mb-3" type="button" data-bs-toggle="collapse" data-bs-target="#collapseCadastroCliente" aria-expanded="false" aria-controls="collapseEstoque">
            <i class="fa-solid fa-plus"></i> Novo Cliente
        </button>
        <button class="btn btn-success mb-3" type="button" data-bs-toggle="collapse" data-bs-target="#collapseImportaCliente" aria-expanded="false" aria-controls="collapseEstoque">
            <i class="fa-solid fa-plus"></i> Importar Cliente
        </button>
    </div>

    <div class="collapse" id="collapseCadastroCliente">
        <div class="card card-body">
            <h4>NOVO CLIENTE</h4>

            <form id="formCadastroCliente">
                <div class="mb-3">
                    <label for="tipo" class="form-label">Tipo</label>
                    <select class="form-select" name="tipo" id="tipo" required>
                        <option value="" disabled selected>Selecione o tipo</option>
                        <option value="cpf">Pessoa Física (CPF)</option>
                        <option value="cnpj">Pessoa Jurídica (CNPJ)</option>
                        <option value="exterior">Cliente do Exterior</option>
                    </select>
                </div>

                <!-- Campos comuns ou específicos por tipo -->

                <!-- Pessoa Física -->
                <div id="grupo-cpf" class="tipo-grupo d-none">
                    <div class="mb-3">
                        <label for="cpf" class="form-label">CPF</label>
                        <input type="text" class="form-control" id="cpf" name="cpf" required>
                    </div>
                    <div class="mb-3">
                        <label for="nomeCompleto" class="form-label">Nome Completo</label>
                        <input type="text" class="form-control" id="nomeCompleto" name="nomeCompleto" required>
                    </div>
                </div>

                <!-- Pessoa Jurídica -->
                <div id="grupo-cnpj" class="tipo-grupo d-none">
                    <div class="mb-3">
                        <label for="cnpj" class="form-label">CNPJ</label>
                        <input type="text" class="form-control" id="cnpj" name="cnpj" required>
                    </div>
                    <div class="mb-3">
                        <label for="razaoSocial" class="form-label">Nome ou Razão Social</label>
                        <input type="text" class="form-control" id="razaoSocial" name="razaoSocial" required>
                    </div>
                    <div class="mb-3">
                        <label for="inscricaoEstadual" class="form-label">Inscrição Estadual</label>
                        <input type="text" class="form-control" id="inscricaoEstadual" name="inscricaoEstadual" placeholder="Se não possuir, informe ISENTO" required>
                    </div>
                    <div class="mb-3">
                        <label for="suframa" class="form-label">Nº Suframa (opcional)</label>
                        <input type="text" class="form-control" id="suframa" name="suframa">
                    </div>
                    <div class="mb-3">
                        <label for="pessoaContato" class="form-label">Pessoa de Contato</label>
                        <input type="text" class="form-control" id="pessoaContato" name="pessoaContato" required>
                    </div>
                </div>

                <!-- Cliente do Exterior -->
                <div id="grupo-exterior" class="tipo-grupo d-none">
                    <div class="mb-3">
                        <label for="nomeImportador" class="form-label">Nome do Importador</label>
                        <input type="text" class="form-control" id="nomeImportador" name="nomeImportador" required>
                    </div>
                    <div class="mb-3">
                        <label for="docImportador" class="form-label">Documento de Identificação</label>
                        <input type="text" class="form-control" id="docImportador" name="docImportador" required>
                    </div>
                    <div class="mb-3">
                        <label for="enderecoExterior" class="form-label">Endereço Completo</label>
                        <textarea class="form-control" id="enderecoExterior" name="enderecoExterior" rows="2" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="pais" class="form-label">País</label>
                        <input type="text" class="form-control" id="pais" name="pais" required>
                    </div>
                    <div class="mb-3">
                        <label for="portoOuAeroporto" class="form-label">Porto ou Aeroporto</label>
                        <input type="text" class="form-control" id="portoOuAeroporto" name="portoOuAeroporto" required>
                    </div>
                    <div class="mb-3">
                        <label for="contatoAduaneiro" class="form-label">Contato Aduaneiro</label>
                        <input type="text" class="form-control" id="contatoAduaneiro" name="contatoAduaneiro" required>
                    </div>

                </div>

                <!-- Campos comuns (exceto exterior que tem endereço diferente) -->
                <div id="enderecoCampos" class="d-none">
                    <div class="mb-3">
                        <label for="rua" class="form-label">Rua</label>
                        <input type="text" class="form-control" id="rua" name="rua" required>
                    </div>
                    <div class="mb-3">
                        <label for="bairro" class="form-label">Bairro</label>
                        <input type="text" class="form-control" id="bairro" name="bairro" required>
                    </div>
                    <div class="mb-3">
                        <label for="cidade" class="form-label">Cidade</label>
                        <input type="text" class="form-control" id="cidade" name="cidade" required>
                    </div>
                    <div class="mb-3">
                        <label for="estado" class="form-label">Estado</label>
                        <input type="text" class="form-control" id="estado" name="estado" required>
                    </div>
                    <div class="mb-3">
                        <label for="cep" class="form-label">CEP</label>
                        <input type="text" class="form-control" id="cep" name="cep" required>
                    </div>
                </div>

                <!-- Campos finais (comuns a todos) -->
                <div id="camposContato" class="d-none">
                    <div class="mb-3">
                        <label for="telefone" class="form-label">Telefone</label>
                        <input type="text" class="form-control" id="telefone" name="telefone" required>
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label">E-mail</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                </div>
                <div>
                    <button type="submit" class="btn btn-primary">Cadastrar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- IMPORTAR -->

<div class="collapse" id="collapseImportaCliente">
    <div class="card card-body">
        <h2>Importar Clientes</h2>
        <form id="importFormClientes" class="mt-4" enctype="multipart/form-data">
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
// Carrega os dados do JSON principal
$clientesJson = file_get_contents(__DIR__ . '/action/clientes/clientes_cadastrados.json');
$clientes = json_decode($clientesJson, true);

// Carrega os dados importados de outro JSON
$clientesImportadosJson = file_get_contents(__DIR__ . '/action/clientes/clientes.json');
$clientesImportados = json_decode($clientesImportadosJson, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    echo 'Erro ao decodificar clientes.json: ' . json_last_error_msg();
}

function formatarCpfCnpj($valor)
{
    $valor = preg_replace('/\D/', '', $valor);

    if (strlen($valor) === 11) {
        return preg_replace("/(\d{3})(\d{3})(\d{3})(\d{2})/", "$1.$2.$3-$4", $valor);
    } elseif (strlen($valor) === 14) {
        return preg_replace("/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/", "$1.$2.$3/$4-$5", $valor);
    } else {
        return $valor;
    }
}

$todosClientes = [];

if (!empty($clientesImportados)) {
    foreach ($clientesImportados as $c) {
        $todosClientes[] = [
            "tipo"              => $c['tipo']               ?? '-',
            "origem"            => 'importado',
            "cpf"               => $c['cpf']                ?? '-',
            "nomeCompleto"      => $c['nomeCompleto']       ?? '-',
            "cnpj"              => $c['cnpj']               ?? '-',
            "razaoSocial"       => $c['razaoSocial']        ?? '-',
            "inscricaoEstadual" => $c['inscricaoEstadual']  ?? '-',
            "suframa"           => $c['suframa']            ?? '-',
            "pessoaContato"     => $c['pessoaContato']      ?? '-',
            "nomeImportador"    => $c['nomeImportador']     ?? '-',
            "docImportador"     => $c['docImportador']      ?? '-',
            "enderecoExterior"  => $c['enderecoExterior']   ?? '-',
            "pais"              => $c['pais']               ?? '-',
            "portoAeroporto"    => $c['portoOuAeroporto']   ?? '-',
            "contatoAduaneiro"  => $c['contatoAduaneiro']   ?? '-',
            "rua"               => $c['rua']                ?? '-',
            "bairro"            => $c['bairro']             ?? '-',
            "cidade"            => $c['cidade']             ?? '-',
            "estado"            => $c['estado']             ?? '-',
            "cep"               => $c['cep']                ?? '-',
            "telefone"          => $c['telefone']           ?? '-',
            "email"             => $c['email']              ?? '-',
            "Codigo Cliente"    => $c['Codigo Cliente']
        ];
    }
}

if (!empty($clientes)) {
    foreach ($clientes as $c) {
        $todosClientes[] = [
            "tipo"              => $c['tipo']               ?? '-',
            "origem"            => 'cadastrado',
            "cpf"               => $c['cpf']                ?? '-',
            "nomeCompleto"      => $c['nomeCompleto']       ?? '-',
            "cnpj"              => $c['cnpj']               ?? '-',
            "razaoSocial"       => $c['razaoSocial']        ?? '-',
            "inscricaoEstadual" => $c['inscricaoEstadual']  ?? '-',
            "suframa"           => $c['suframa']            ?? '-',
            "pessoaContato"     => $c['pessoaContato']      ?? '-',
            "nomeImportador"    => $c['nomeImportador']     ?? '-',
            "docImportador"     => $c['docImportador']      ?? '-',
            "enderecoExterior"  => $c['enderecoExterior']   ?? '-',
            "pais"              => $c['pais']               ?? '-',
            "portoAeroporto"    => $c['portoOuAeroporto']   ?? '-',
            "contatoAduaneiro"  => $c['contatoAduaneiro']   ?? '-',
            "rua"               => $c['rua']                ?? '-',
            "bairro"            => $c['bairro']             ?? '-',
            "cidade"            => $c['cidade']             ?? '-',
            "estado"            => $c['estado']             ?? '-',
            "cep"               => $c['cep']                ?? '-',
            "telefone"          => $c['telefone']           ?? '-',
            "email"             => $c['email']              ?? '-',
            "Codigo Cliente"    => $c['Codigo Cliente']
        ];
    }
}

$filtroOrigem = $_GET['origem'] ?? 'todos';
$termoBusca = $_GET['termo_busca'] ?? '';

$clientesFiltrados = array_filter($todosClientes, function ($clientes) use ($filtroOrigem, $termoBusca) {
    if (
        $filtroOrigem !== '' && $filtroOrigem !== 'todos' &&
        strtolower($clientes['origem'] ?? '') !== strtolower($filtroOrigem)
    ) return false;

    $tipo = $clientes['tipo'];

    if ($termoBusca !== '') {
        if ($tipo === 'cpf') {$descricao = $clientes['nomeCompleto']; $codigo = $clientes['cpf']; }
        elseif ($tipo === 'cnpj') {$descricao = $clientes['razaoSocial']; $codigo = $clientes['cnpj']; }
        elseif ($tipo === 'exterior') {$descricao = $clientes['nomeImportador']; $codigo = $clientes['docImportador']; }
        
        if (stripos($codigo, $termoBusca) === false && stripos($descricao, $termoBusca) === false)
            return false;
    }

    return true;
});

$p = max(1, intval($_GET['p'] ?? 1));
$limitePorPagina = isset($_GET['limite']) ? max(1, intval($_GET['limite'])) : 30;

$totalClientes = count($clientesFiltrados);
$totalPaginas = ceil($totalClientes / $limitePorPagina);
if ($p > $totalPaginas && $totalPaginas > 0) $p = $totalPaginas;

$offset = ($p - 1) * $limitePorPagina; // primeiro elemento
$clientesPaginados = array_slice($clientesFiltrados, $offset, $limitePorPagina);

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

<div class="container py-3" id="listagem-clientes">
    <h1 class="mb-4 text-center">Lista de Clientes</h1>
    <form id="formFiltroClientes" method="GET" action="" class="bg-white p-3 rounded shadow-sm mb-4 border">
        <div class="row g-3 align-items-center">
            <div class="col-md-4">
                <div class="form-floating">
                    <select name="origem" class="form-select" id="filtro-origem">
                        <option value="todos" <?= $filtroOrigem === 'todos' ? 'selected' : '' ?>>Todos os Clientes</option>
                        <option value="cadastrado" <?= $filtroOrigem === 'cadastrado' ? 'selected' : '' ?>>Cadastrados</option>
                        <option value="importado" <?= $filtroOrigem === 'importado' ? 'selected' : '' ?>>Importados</option>
                    </select>
                    <label for="filtro-origem"><i class="fa-solid fa-users text-primary"></i> Origem</label>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-floating">
                    <select name="limite" id="seletorLimiteClientes" class="form-select">
                        <option value="15" <?= $limitePorPagina == 15 ? 'selected' : '' ?>>15 itens</option>
                        <option value="30" <?= $limitePorPagina == 30 ? 'selected' : '' ?>>30 itens</option>
                        <option value="50" <?= $limitePorPagina == 50 ? 'selected' : '' ?>>50 itens</option>
                        <option value="75" <?= $limitePorPagina == 75 ? 'selected' : '' ?>>75 itens</option>
                        <option value="100" <?= $limitePorPagina == 100 ? 'selected' : '' ?>>100 itens</option>
                    </select>
                    <label for="seletorLimiteClientes"><i class="fa-solid fa-list text-primary"></i> Exibição</label>
                </div>
            </div>
            <div class="col-md-4">
                <div class="d-flex gap-2 h-100">
                    <div class="form-floating flex-grow-1">
                        <input type="text" class="form-control" id="filtro-clientes" name="termo_busca" placeholder="Pesquisar..." style="height: 58px;">
                        <label for="filtro-clientes"><i class="fa-solid fa-magnifying-glass text-muted"></i> Buscar cliente</label>
                    </div>
                    <button class="btn btn-success d-flex flex-column align-items-center justify-content-center px-3" id="btn-exportar-clientes" title="Exportar CSV Clientes" style="height: 58px;" type="button">
                        <i class="fa-solid fa-file-csv fs-5 mb-1"></i>
                        <span style="font-size: 0.75rem; font-weight: 600;">Exportar</span>
                    </button>
                </div>
            </div>
        </div>
    </form>
    <div class="table-responsive shadow-sm rounded mb-4">
        <table class="table table-hover table-bordered align-middle mb-0 text-center" id="tabela-clientes">
            <thead class="table-dark">
                <tr id="th-thead-clientes">
                    <th class="sortable">Nome ou Razão Social</th>
                    <th class="sortable" style="width: 150px !important;">CPF ou CNPJ</th>
                    <th>Endereço</th>
                    <th>Telefone</th>
                    <th>E-mail</th>
                    <th style="width: 50px">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($clientesPaginados as $cliente):
                    $nome = $cliente["tipo"] === "cpf" ? $cliente["nomeCompleto"] : ($cliente["tipo"] === "cnpj" ? $cliente["razaoSocial"] : $cliente["nomeImportador"]);
                    $cpfCnpj = $cliente["tipo"] === "cpf" ? $cliente["cpf"] : ($cliente["tipo"] === "cnpj" ? $cliente["cnpj"] : $cliente["docImportador"]);
                    $endereco = $cliente["tipo"] === "exterior" ? $cliente["enderecoExterior"] . ' - ' . $cliente["pais"] : $cliente["rua"] . ' - ' . $cliente["bairro"] . ' - ' . $cliente["cidade"] . '/' . $cliente["estado"] . ' - ' . $cliente["cep"];
                    $telefone = $cliente["telefone"];
                    $email = $cliente["email"];
                    $clienteJson = htmlspecialchars(json_encode($cliente), ENT_QUOTES, 'UTF-8');
                ?>
                    <tr data-json="<?= $clienteJson ?>" class="cliente-<?= $cliente['origem'] ?>">
                        <td class="copy-container">
                            <?= trim($nome ?? '') ?>
                        </td>
                        <td class="copy-container" style="width: 170px;">
                            <?= formatarCpfCnpj(trim($cpfCnpj ?? '')) ?>
                        </td>
                        <td class="copy-container">
                            <?= trim($endereco ?? '') ?>
                        </td>
                        <td class="copy-container">
                            <?= trim($telefone ?? '') ?>
                        </td>
                        <td class="copy-container">
                            <?= trim($email ?? '') ?>
                        </td>
                        <td class="action-buttons">
                            <div class="d-inline-flex gap-1">
                                <button class="btn btn-warning btn-sm edit-cad-btn">
                                    <i class="fa-solid fa-pen"></i>
                                </button>
                                <button class="btn btn-danger btn-sm delete-btn" data-id="<?= trim($cliente['Codigo Cliente'] ?? '') ?>">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php
                    $tipo = $cliente['tipo'];
                    ?>

                    <tr class="edit-row" style="display: none;">
                        <td colspan="6">
                            <form class="edit-form-cad-clientes p-2">
                                <input type="hidden" name="Codigo Cliente" value="<?= htmlspecialchars($cliente['Codigo Cliente'] ?? '') ?>">
                                <input type="hidden" name="tipo" value="<?= $tipo ?>">

                                <div class="row g-2 mb-2 text-start">
                                    <?php if ($tipo === 'cnpj'): ?>
                                        <div class="col-md-4">
                                            <label class="form-label mb-0 small">CNPJ</label>
                                            <input type="text" class="form-control form-control-sm" name="cnpj" value="<?= htmlspecialchars($cliente['cnpj']) ?>" required>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label mb-0 small">Razão Social</label>
                                            <input type="text" class="form-control form-control-sm" name="razaoSocial" value="<?= htmlspecialchars($cliente['razaoSocial']) ?>" required>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label mb-0 small">Inscrição Estadual</label>
                                            <input type="text" class="form-control form-control-sm" name="inscricaoEstadual" value="<?= htmlspecialchars($cliente['inscricaoEstadual']) ?>" required>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label mb-0 small">Suframa</label>
                                            <input type="text" class="form-control form-control-sm" name="suframa" value="<?= htmlspecialchars($cliente['suframa']) ?>">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label mb-0 small">Pessoa de Contato</label>
                                            <input type="text" class="form-control form-control-sm" name="pessoaContato" value="<?= htmlspecialchars($cliente['pessoaContato']) ?>" required>
                                        </div>
                                    <?php elseif ($tipo === 'cpf'): ?>
                                        <div class="col-md-4">
                                            <label class="form-label mb-0 small">CPF</label>
                                            <input type="text" class="form-control form-control-sm" name="cpf" value="<?= htmlspecialchars($cliente['cpf']) ?>" required>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label mb-0 small">Nome Completo</label>
                                            <input type="text" class="form-control form-control-sm" name="nomeCompleto" value="<?= htmlspecialchars($cliente['nomeCompleto']) ?>" required>
                                        </div>
                                    <?php elseif ($tipo === 'exterior'): ?>
                                        <div class="col-md-4">
                                            <label class="form-label mb-0 small">Nome do Importador</label>
                                            <input type="text" class="form-control form-control-sm" name="nomeImportador" value="<?= htmlspecialchars($cliente['nomeImportador']) ?>" required>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label mb-0 small">Documento do Importador</label>
                                            <input type="text" class="form-control form-control-sm" name="docImportador" value="<?= htmlspecialchars($cliente['docImportador']) ?>" required>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label mb-0 small">Endereço Exterior</label>
                                            <input type="text" class="form-control form-control-sm" name="enderecoExterior" value="<?= htmlspecialchars($cliente['enderecoExterior']) ?>" required>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label mb-0 small">País</label>
                                            <input type="text" class="form-control form-control-sm" name="pais" value="<?= htmlspecialchars($cliente['pais']) ?>" required>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label mb-0 small">Porto ou Aeroporto</label>
                                            <input type="text" class="form-control form-control-sm" name="portoOuAeroporto" value="<?= htmlspecialchars($cliente['portoOuAeroporto']) ?>" required>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label mb-0 small">Contato Aduaneiro</label>
                                            <input type="text" class="form-control form-control-sm" name="contatoAduaneiro" value="<?= htmlspecialchars($cliente['contatoAduaneiro']) ?>">
                                        </div>
                                    <?php endif;
                                    if ($tipo !== 'exterior'): ?>
                                        <div class="col-md-4">
                                            <label class="form-label mb-0 small">Rua</label>
                                            <input type="text" class="form-control form-control-sm" name="rua" value="<?= htmlspecialchars($cliente['rua']) ?>" required>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label mb-0 small">Bairro</label>
                                            <input type="text" class="form-control form-control-sm" name="bairro" value="<?= htmlspecialchars($cliente['bairro']) ?>">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label mb-0 small">Cidade</label>
                                            <input type="text" class="form-control form-control-sm" name="cidade" value="<?= htmlspecialchars($cliente['cidade']) ?>" required>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label mb-0 small">Estado</label>
                                            <input type="text" class="form-control form-control-sm" name="estado" value="<?= htmlspecialchars($cliente['estado']) ?>">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label mb-0 small">CEP</label>
                                            <input type="text" class="form-control form-control-sm" name="cep" value="<?= htmlspecialchars($cliente['cep']) ?>" required>
                                        </div>
                                    <?php endif; ?>
                                    <div class="col-md-4">
                                        <label class="form-label mb-0 small">Telefone</label>
                                        <input type="text" class="form-control form-control-sm" name="telefone" value="<?= htmlspecialchars($cliente['telefone']) ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label mb-0 small">Email</label>
                                        <input type="email" class="form-control form-control-sm" name="email" value="<?= htmlspecialchars($cliente['email']) ?>" required>
                                    </div>
                                    <div class="col-auto ms-auto align-self-end pb-1">
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
                Exibindo página <?= $p ?> de <?= $totalPaginas ?> (Total: <?= $totalClientes ?> clientes)
            </div>
        </nav>
    </div>
</div>

<style>
    .small-text {
        font-size: 10px;
    }

    .table th {
        padding: 0.3rem;
        vertical-align: middle;
        text-align: center;
    }

    .action-buttons .btn {
        padding: 0.2rem 0.4rem;
    }

    .d-inline-flex.gap-1>* {
        margin-right: 2px;
    }

    .copy-container {
        position: relative;
        cursor: pointer;
    }

    .copy-icon {
        margin-left: 5px;
        color: #666;
        font-size: 0.8rem;
        cursor: pointer;
    }

    .copy-icon:hover {
        color: #000;
    }

    th.sortable {
        cursor: pointer;
        user-select: none;
    }

    label {
        text-align: left
    }
</style>

<script>
    window.planilhaClientes = <?= json_encode(array_values($clientesFiltrados)) ?>
</script>

<script src="js/cadastrar/clientes.js"></script>