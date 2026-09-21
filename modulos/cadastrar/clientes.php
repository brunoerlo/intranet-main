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


        <script>
            document.getElementById('tipo').addEventListener('change', function() {
                const tipo = this.value;

                // Oculta todos os grupos inicialmente
                document.querySelectorAll('.tipo-grupo').forEach(div => div.classList.add('d-none'));
                document.getElementById('enderecoCampos').classList.add('d-none');
                document.getElementById('camposContato').classList.add('d-none');

                // Desativa todos os campos
                document.querySelectorAll('#formCadastroCliente input, #formCadastroCliente textarea, #formCadastroCliente select').forEach(el => {
                    if (el.id !== 'tipo') el.required = false;
                });

                if (tipo === 'cpf') {
                    document.getElementById('grupo-cpf').classList.remove('d-none');
                    document.getElementById('enderecoCampos').classList.remove('d-none');
                    document.getElementById('camposContato').classList.remove('d-none');
                    document.getElementById('cpf').required = true;
                    document.getElementById('nomeCompleto').required = true;
                } else if (tipo === 'cnpj') {
                    document.getElementById('grupo-cnpj').classList.remove('d-none');
                    document.getElementById('enderecoCampos').classList.remove('d-none');
                    document.getElementById('camposContato').classList.remove('d-none');
                    ['cnpj', 'razaoSocial', 'inscricaoEstadual', 'pessoaContato'].forEach(id => {
                        document.getElementById(id).required = true;
                    });
                } else if (tipo === 'exterior') {
                    document.getElementById('grupo-exterior').classList.remove('d-none');
                    document.getElementById('camposContato').classList.remove('d-none');
                    ['nomeImportador', 'docImportador', 'enderecoExterior', 'pais'].forEach(id => {
                        document.getElementById(id).required = true;
                    });
                }

                // Campos comuns
                if (tipo) {
                    document.getElementById('telefone').required = true;
                    document.getElementById('email').required = true;
                }
            });

            document.getElementById('formCadastroCliente').addEventListener('submit', function(event) {
                event.preventDefault(); // Evita o envio tradicional do formulário

                // Coleta os dados do formulário
                const formData = new FormData(this);

                // Converte os dados do FormData para um objeto simples
                const data = {};
                formData.forEach((value, key) => {
                    data[key] = value;
                });

                // Envia os dados via AJAX
                fetch('./modulos/cadastrar/action/clientes/cadastrarCliente.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify(data),
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert('Cliente cadastrado com sucesso!');
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
    </div>
</div>

<?php
// Carrega os dados do JSON principal
$clientesJson = file_get_contents(__DIR__ . '/action/clientes/clientes.json');
$clientes = json_decode($clientesJson, true);

// Carrega os dados importados de outro JSON
$clientesImportadosJson = file_get_contents(__DIR__ . '/action/clientes/clientes_cadastrados.json');
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
function mostrar($tiposPermitidos, $tipoAtual)
{
    return in_array($tipoAtual, $tiposPermitidos) ? '' : 'style="display: none;"';
}

$todosClientes = [];

if (!empty($clientes) && !empty($clientesImportados)) $todosClientes = array_merge($clientes, $clientesImportados);
elseif (empty($clientes)) $todosClientes = $clientesImportados;
elseif (empty($clientesImportados)) $todosClientes = $clientes;

if (!empty($todosClientes)) {
    foreach ($todosClientes as $c) {
        $todosClientes = [
            "tipo"              => $c['tipo']               ?? '-',
            "cpf"               => $c['cpf']                ?? '-',
            "nomeCompleto"      => $c['nomeCompleto']       ?? '-',
            "cnpj"              => $c['cnpj']               ?? '-',
            "razaoSocial"       => $c['razaoSocial']        ?? '-',
            "inscricaoEstadual" => $c['inscricaoEstadual']  ?? '-',
            "suframa"           => $c['suframa']            ?? '-',
            "pessoaContao"      => $c['pessoaContato']      ?? '-',
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
            "email"             => $c['email']              ?? '-'
        ];
    }
}

$limite = isset($_GET['limite']) ? intval($_GET['limite']) : 30; // 30 por padrão
$paginaAtual = isset($_GET['p']) ? intval($_GET['p']) : 1;

$totalRegistros = count($todosClientes);
$totalPaginas = ceil($totalRegistros / $limite);

$offset = ($paginaAtual - 1) * $limite; // primeiro elemento
$produtosPaginados = array_slice($todosClientes, $offset, $limite);

?>

<!-- IMPORTAR -->

<div class="container-fluid mt-5">
    <div class="collapse" id="collapseImportaCliente">
        <div class="card card-box">
            <h2>Importar Clientes</h2>
            <form id="importForm" class="mt-4" enctype="multipart/form-data">
                <div class="mb-3">
                    <label for="csvFile" class="form-label">Escolha o arquivo CSV:</label>
                    <input class="form-control" type="file" name="csvFile" id="csvFile" accept=".csv" required>
                </div>
                <button type="submit" class="btn btn-primary">Importar</button>
            </form>
            <div id="result" class="mt-3"></div>
        </div>
    </div>
</div>

<script>
    document.getElementById('importForm').addEventListener('submit', function(event) {
        event.preventDefault();

        const formData = new FormData(this);

        fetch('./modulos/cadastrar/action/clientes/importar_clientes.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                const resultDiv = document.getElementById('result');
                if (data.status === 'success') {
                    resultDiv.innerHTML = '<div class="alert alert-success">Clientes importados com sucesso!</div>';
                } else if (data.error) {
                    resultDiv.innerHTML = `<div class="alert alert-danger">Erro ao importar clientes: ${data.error}</div>`;
                }
            })
            .catch(error => {
                document.getElementById('result').innerHTML = `<div class="alert alert-danger">Erro ao importar clientes: ${error.message}</div>`;
            });
    });
</script>

<div class="container mt-4" id="listagem-clientes">
    <h2>Listagem de Clientes</h2>
    <div class="row mb-4">
        <div class="col-md-4">
            <label for="filtro-clientes" class="form-label">Mostrar:</label>
            <select class="form-select w-auto d-inline-block" id="filtro-clientes">
                <option value="todos">Todos os clientes</option>
                <option value="importado">Cadastrados</option>
                <option value="cadastrado" selected>Importados</option>
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
        <div class="table-responsive shadow-sm rounded mb-4">
            <table class="table table-striped table-hover table-bordered align-middle mb-0" id="tabela-clientes">
                <thead class="table-dark">
                    <tr>
                        <th class="sortable" style="text-align: center;">Nome ou Razão Social</th>
                        <th style="width: 150px !important; text-align: center;">CPF ou CNPJ</th>
                        <th style="text-align: center;">Endereço</th>
                        <th style="text-align: center;">Telefone</th>
                        <th style="text-align: center;">E-mail</th>
                        <th style="width: 50px; text-align: center;">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Primeiramente, renderiza os clientes importados -->
                    <?php foreach ($clientesImportados as $clienteImportado):
                        $nome = $clienteImportado["tipo"] === "cpf" ? $clienteImportado["nomeCompleto"] : $clienteImportado["razaoSocial"];
                        $cpfCnpj = $clienteImportado["tipo"] === "cpf" ? $clienteImportado["cpf"] : $clienteImportado["cnpj"];
                        $endereco = $clienteImportado["rua"] . ' - ' . $clienteImportado["bairro"] . ' - ' . $clienteImportado["cidade"] . '/' . $clienteImportado["estado"] . ' - ' . $clienteImportado["cep"];
                        $telefone = $clienteImportado["telefone"];
                        $email = $clienteImportado["email"];
                        $clienteJsonImportado = htmlspecialchars(json_encode($clienteImportado), ENT_QUOTES, 'UTF-8');
                    ?>
                        <tr data-json="<?= $clienteJsonImportado ?>" class="table-warning cliente-importado">
                            <td class="small-text copy-container">
                                <?= trim($nome) ?>
                                <span class="badge bg-secondary ms-1">Cadastrado</span>
                            </td>
                            <td class="small-text copy-container">
                                <?= formatarCpfCnpj($cpfCnpj) ?>
                            </td>
                            <td class="small-text copy-container">
                                <?= $endereco ?>
                            </td>
                            <td class="small-text copy-container">
                                <?= $telefone ?>
                            </td>
                            <td class="small-text copy-container">
                                <?= $email ?>
                            </td>
                            <td class="action-buttons text-center">
                                <div class="d-inline-flex gap-1">
                                    <button class="btn btn-warning btn-sm edit-cad-btn">
                                        <i class="fa-solid fa-pen"></i>
                                    </button>
                                    <button class="btn btn-danger btn-sm delete-btn" data-id="<?= htmlspecialchars($clienteImportado['Codigo Cliente'] ?? '') ?>">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php
                        $tipo = $clienteImportado["tipo"];

                        ?>

                        <tr class="edit-row" style="display: none;">
                            <td colspan="6">
                                <form class="edit-form">
                                    <input type="hidden" name="Codigo Cliente" value="<?= htmlspecialchars($clienteImportado['Codigo Cliente'] ?? '') ?>">
                                    <input type="hidden" name="tipo" value="<?= $tipo ?>">

                                    <div class="mb-2" <?= mostrar(['cpf'], $tipo) ?>><label>CPF:</label><input type="text" name="cpf" class="form-control"></div>
                                    <div class="mb-2" <?= mostrar(['cnpj'], $tipo) ?>><label>CNPJ:</label><input type="text" name="cnpj" class="form-control"></div>
                                    <div class="mb-2" <?= mostrar(['cpf'], $tipo) ?>><label>Nome Completo:</label><input type="text" name="nomeCompleto" class="form-control"></div>
                                    <div class="mb-2" <?= mostrar(['cnpj'], $tipo) ?>><label>Razão Social:</label><input type="text" name="razaoSocial" class="form-control"></div>
                                    <div class="mb-2" <?= mostrar(['cnpj'], $tipo) ?>><label>Inscrição Estadual:</label><input type="text" name="inscricaoEstadual" class="form-control"></div>
                                    <div class="mb-2" <?= mostrar(['cnpj'], $tipo) ?>><label>Suframa:</label><input type="text" name="suframa" class="form-control"></div>
                                    <div class="mb-2" <?= mostrar(['cnpj'], $tipo) ?>><label>Pessoa de Contato:</label><input type="text" name="pessoaContato" class="form-control"></div>
                                    <div class="mb-2" <?= mostrar(['exterior'], $tipo) ?>><label>Nome do Importador:</label><input type="text" name="nomeImportador" class="form-control"></div>
                                    <div class="mb-2" <?= mostrar(['exterior'], $tipo) ?>><label>Documento do Importador:</label><input type="text" name="docImportador" class="form-control"></div>
                                    <div class="mb-2" <?= mostrar(['exterior'], $tipo) ?>><label>Endereço Exterior:</label><input type="text" name="enderecoExterior" class="form-control"></div>
                                    <div class="mb-2" <?= mostrar(['exterior'], $tipo) ?>><label>País:</label><input type="text" name="pais" class="form-control"></div>
                                    <div class="mb-2" <?= mostrar(['exterior'], $tipo) ?>><label>Porto ou Aeroporto:</label><input type="text" name="portoOuAeroporto" class="form-control"></div>
                                    <div class="mb-2" <?= mostrar(['exterior'], $tipo) ?>><label>Contato Aduaneiro:</label><input type="text" name="contatoAduaneiro" class="form-control"></div>

                                    <!-- Campos comuns para todos os tipos -->
                                    <div class="mb-2"><label>Rua:</label><input type="text" name="rua" class="form-control"></div>
                                    <div class="mb-2"><label>Bairro:</label><input type="text" name="bairro" class="form-control"></div>
                                    <div class="mb-2"><label>Cidade:</label><input type="text" name="cidade" class="form-control"></div>
                                    <div class="mb-2"><label>Estado:</label><input type="text" name="estado" class="form-control"></div>
                                    <div class="mb-2"><label>CEP:</label><input type="text" name="cep" class="form-control"></div>
                                    <div class="mb-2"><label>Telefone:</label><input type="text" name="telefone" class="form-control"></div>
                                    <div class="mb-2"><label>E-mail:</label><input type="email" name="email" class="form-control"></div>

                                    <div class="mb-2">
                                        <button type="submit" class="btn btn-primary">Salvar</button>
                                        <button type="button" class="btn btn-secondary cancel-btn">Cancelar</button>
                                    </div>
                                </form>
                            </td>
                        </tr>

                    <?php endforeach; ?>
                    <!-- Depois, renderiza os clientes normais -->
                    <?php foreach ($clientes as $cliente):
                        $nome = $cliente["tipo"] === "cpf" ? $cliente["nomeCompleto"] : ($cliente["tipo"] === "cnpj" ? $cliente["razaoSocial"] : $cliente["nomeImportador"]);
                        $cpfCnpj = $cliente["tipo"] === "cpf" ? $cliente["cpf"] : ($cliente["tipo"] === "cnpj" ? $cliente["cnpj"] : $cliente["docImportador"]);
                        $endereco = $cliente["tipo"] === "exterior" ? $cliente["enderecoExterior"] . ' - ' . $cliente["pais"] : $cliente["rua"] . ' - ' . $cliente["bairro"] . ' - ' . $cliente["cidade"] . '/' . $cliente["estado"] . ' - ' . $cliente["cep"];
                        $telefone = $cliente["telefone"];
                        $email = $cliente["email"];
                        $clienteJson = htmlspecialchars(json_encode($cliente), ENT_QUOTES, 'UTF-8');
                    ?>
                        <tr data-json="<?= $clienteJson ?>" class="cliente-cadastrado">
                            <td class="small-text copy-container">
                                <?= trim($nome ?? '') ?>
                                <span class="badge bg-primary ms-1">Importado</span>
                            </td>
                            <td class="small-text copy-container">
                                <?= formatarCpfCnpj(trim($cpfCnpj ?? '')) ?>
                            </td>
                            <td class="small-text copy-container">
                                <?= trim($endereco ?? '') ?>
                            </td>
                            <td class="small-text copy-container">
                                <?= trim($telefone ?? '') ?>
                            </td>
                            <td class="small-text copy-container">
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
                        $tipo = $cliente["tipo"];
                        ?>

                        <tr class="edit-row" style="display: none;">
                            <td colspan="6">
                                <form class="edit-form">
                                    <input type="hidden" name="Codigo Cliente" value="<?= htmlspecialchars($cliente['Codigo Cliente'] ?? '') ?>">
                                    <input type="hidden" name="tipo" value="<?= $tipo ?>">

                                    <div class="mb-2" <?= mostrar(['cpf'], $tipo) ?>><label>CPF:</label><input type="text" name="cpf" class="form-control"></div>
                                    <div class="mb-2" <?= mostrar(['cnpj'], $tipo) ?>><label>CNPJ:</label><input type="text" name="cnpj" class="form-control"></div>
                                    <div class="mb-2" <?= mostrar(['cpf'], $tipo) ?>><label>Nome Completo:</label><input type="text" name="nomeCompleto" class="form-control"></div>
                                    <div class="mb-2" <?= mostrar(['cnpj'], $tipo) ?>><label>Razão Social:</label><input type="text" name="razaoSocial" class="form-control"></div>
                                    <div class="mb-2" <?= mostrar(['cnpj'], $tipo) ?>><label>Inscrição Estadual:</label><input type="text" name="inscricaoEstadual" class="form-control"></div>
                                    <div class="mb-2" <?= mostrar(['cnpj'], $tipo) ?>><label>Suframa:</label><input type="text" name="suframa" class="form-control"></div>
                                    <div class="mb-2" <?= mostrar(['cnpj'], $tipo) ?>><label>Pessoa de Contato:</label><input type="text" name="pessoaContato" class="form-control"></div>
                                    <div class="mb-2" <?= mostrar(['exterior'], $tipo) ?>><label>Nome do Importador:</label><input type="text" name="nomeImportador" class="form-control"></div>
                                    <div class="mb-2" <?= mostrar(['exterior'], $tipo) ?>><label>Documento do Importador:</label><input type="text" name="docImportador" class="form-control"></div>
                                    <div class="mb-2" <?= mostrar(['exterior'], $tipo) ?>><label>Endereço Exterior:</label><input type="text" name="enderecoExterior" class="form-control"></div>
                                    <div class="mb-2" <?= mostrar(['exterior'], $tipo) ?>><label>País:</label><input type="text" name="pais" class="form-control"></div>
                                    <div class="mb-2" <?= mostrar(['exterior'], $tipo) ?>><label>Porto ou Aeroporto:</label><input type="text" name="portoOuAeroporto" class="form-control"></div>
                                    <div class="mb-2" <?= mostrar(['exterior'], $tipo) ?>><label>Contato Aduaneiro:</label><input type="text" name="contatoAduaneiro" class="form-control"></div>

                                    <!-- Campos comuns para todos os tipos -->
                                    <div class="mb-2"><label>Rua:</label><input type="text" name="rua" class="form-control"></div>
                                    <div class="mb-2"><label>Bairro:</label><input type="text" name="bairro" class="form-control"></div>
                                    <div class="mb-2"><label>Cidade:</label><input type="text" name="cidade" class="form-control"></div>
                                    <div class="mb-2"><label>Estado:</label><input type="text" name="estado" class="form-control"></div>
                                    <div class="mb-2"><label>CEP:</label><input type="text" name="cep" class="form-control"></div>
                                    <div class="mb-2"><label>Telefone:</label><input type="text" name="telefone" class="form-control"></div>
                                    <div class="mb-2"><label>E-mail:</label><input type="email" name="email" class="form-control"></div>

                                    <div class="mb-2">
                                        <button type="submit" class="btn btn-primary">Salvar</button>
                                        <button type="button" class="btn btn-secondary cancel-btn">Cancelar</button>
                                    </div>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

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

        function aplicarFiltroClientes(valorSelecionado) {
            const linhasImportadas = document.querySelectorAll('.cliente-importado');
            const linhasCadastradas = document.querySelectorAll('.cliente-cadastrado');

            if (valorSelecionado === 'todos') {
                linhasImportadas.forEach(l => l.style.display = '');
                linhasCadastradas.forEach(l => l.style.display = '');
            } else if (valorSelecionado === 'importado') {
                linhasImportadas.forEach(l => l.style.display = '');
                linhasCadastradas.forEach(l => l.style.display = 'none');
            } else if (valorSelecionado === 'cadastrado') {
                linhasImportadas.forEach(l => l.style.display = 'none');
                linhasCadastradas.forEach(l => l.style.display = '');
            }
        }

        // Espera o select existir e aplica o filtro
        function inicializarFiltroClientes() {
            const filtro = document.getElementById('filtro-clientes');
            if (filtro) {
                filtro.addEventListener('change', function() {
                    aplicarFiltroClientes(this.value);
                });

                // Aplica filtro inicial (cadastrados por padrão)
                aplicarFiltroClientes(filtro.value);
            } else {
                // Tenta de novo se o elemento ainda não estiver no DOM
                setTimeout(inicializarFiltroClientes, 100); // tenta a cada 100ms
            }
        }

        // Inicia o filtro manualmente
        inicializarFiltroClientes();

        if (typeof ordemCrescente === 'undefined') {
            var ordemCrescente = true;
        }
        document.querySelector('th.sortable').addEventListener('click', () => {
            const tbody = document.querySelector('#tabela-clientes tbody');
            const allRows = Array.from(tbody.querySelectorAll('tr'));

            const rowPairs = [];
            for (let i = 0; i < allRows.length; i += 2) {
                rowPairs.push([allRows[i], allRows[i + 1]]);
            }

            rowPairs.sort((a, b) => {
                const nomeA = a[0].children[0].textContent.trim().toLowerCase();
                const nomeB = b[0].children[0].textContent.trim().toLowerCase();
                return ordemCrescente ? nomeA.localeCompare(nomeB) : nomeB.localeCompare(nomeA);
            });

            tbody.innerHTML = '';
            rowPairs.forEach(pair => {
                tbody.appendChild(pair[0]);
                tbody.appendChild(pair[1]);
            });

            ordemCrescente = !ordemCrescente;
        });

        // DELETE
        document.querySelectorAll('.delete-btn').forEach(button => {
            button.addEventListener('click', function() {
                const clienteId = this.getAttribute('data-id');
                if (confirm("Tem certeza que deseja excluir este cliente?")) {
                    fetch('./modulos/cadastrar/action/clientes/deleteCliente.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({
                                id: clienteId
                            })
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                alert('Cliente excluído com sucesso!');
                                location.reload();
                            } else {
                                alert('Erro ao excluir o cliente: ' + data.message);
                            }
                        })
                        .catch(error => {
                            console.error('Erro:', error);
                            alert('Ocorreu um erro ao tentar excluir o cliente.');
                        });
                }
            });
        });


        // EDITAR CLIENTE CADASTRADO
        document.querySelectorAll('.edit-cad-btn').forEach(button => {
            button.addEventListener('click', function() {
                const row = this.closest('tr'); // linha com os dados
                const editRow = row.nextElementSibling; // linha com o formulário de edição

                if (editRow && editRow.classList.contains('edit-row')) {
                    const form = editRow.querySelector('.edit-form');
                    if (!form) return;

                    // Pega os dados do atributo data-json da linha
                    const jsonData = JSON.parse(row.getAttribute('data-json'));

                    // Preenche dinamicamente todos os campos do formulário com os dados do JSON
                    Object.keys(jsonData).forEach(key => {
                        const input = form.elements[key];
                        if (input) {
                            input.value = jsonData[key] || '';
                        }
                    });

                    // Exibe a linha do formulário
                    editRow.style.display = 'table-row';
                }
            });
        });

        // CANCELAR EDIÇÃO
        document.querySelectorAll('.cancel-btn').forEach(button => {
            button.addEventListener('click', function() {
                const editRow = this.closest('.edit-row');
                editRow.style.display = 'none';
            });
        });

        // SALVAR EDIÇÃO
        document.querySelectorAll('.edit-form').forEach(form => {
            form.addEventListener('submit', function(event) {
                event.preventDefault();
                const formData = new FormData(this);
                fetch('./modulos/cadastrar/action/clientes/updateCliente.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert('Cliente atualizado com sucesso!');
                            location.reload();
                        } else {
                            alert('Erro ao atualizar o cliente: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Erro:', error);
                        alert('Ocorreu um erro ao tentar atualizar o cliente.');
                    });
            });
        });
        document.addEventListener("click", function(e) {
            const td = e.target.closest("td.copy-container");
            if (td && td.closest("#tabela-clientes")) {
                const text = td.innerText.trim();

                // Copia para a área de transferência
                const tempInput = document.createElement("input");
                tempInput.value = text;
                document.body.appendChild(tempInput);
                tempInput.select();
                document.execCommand("copy");
                document.body.removeChild(tempInput);

                // Cria o tooltip
                const tooltip = document.createElement("div");
                tooltip.innerText = "Copiado!";
                tooltip.style.position = "absolute";
                tooltip.style.background = "#000";
                tooltip.style.color = "#fff";
                tooltip.style.padding = "4px 8px";
                tooltip.style.borderRadius = "4px";
                tooltip.style.fontSize = "12px";
                tooltip.style.zIndex = "9999";
                tooltip.style.pointerEvents = "none";
                tooltip.style.opacity = "0";
                tooltip.style.transition = "opacity 0.3s ease";

                document.body.appendChild(tooltip);

                // Posição do tooltip (acima do td clicado)
                const rect = td.getBoundingClientRect();
                tooltip.style.left = `${rect.left + window.scrollX + rect.width / 2 - tooltip.offsetWidth / 2}px`;
                tooltip.style.top = `${rect.top + window.scrollY - 30}px`;

                // Mostra e depois remove
                requestAnimationFrame(() => {
                    tooltip.style.opacity = "1";
                });

                setTimeout(() => {
                    tooltip.style.opacity = "0";
                    setTimeout(() => {
                        tooltip.remove();
                    }, 300);
                }, 1200);
            }
        });
    </script>

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