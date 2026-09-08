<div class="d-flex flex-column gap-2">
    <!-- Cadastro de Empresa -->
    <div>
        <button class="btn btn-success mb-3" type="button" data-bs-toggle="collapse" data-bs-target="#collapseCadastroEmpresa" aria-expanded="false" aria-controls="collapseEstoque">
            <i class="fa-solid fa-plus"></i> Cadastro de Empresa
        </button>
        <div class="collapse" id="collapseCadastroEmpresa">
            <div class="card card-body">
                <h2>Cadastro de Empresas</h2>

                <!-- Formulário de cadastro -->
                <form id="form-empresa" class="mb-4" method="post" action="#">
                    <input type="hidden" name="id" id="empresa-id">

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Razão Social</label>
                                <input type="text" name="razaoSocial" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">CNPJ</label>
                                <input type="text" name="cnpj" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Telefone</label>
                                <input type="text" name="telefone" class="form-control">
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Endereço</label>
                                <input type="text" name="endereco" class="form-control">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">E-mail</label>
                                <input type="email" name="email" class="form-control">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Moeda</label>
                                <select name="moeda" class="form-select" required>
                                    <option value="R$">R$ (Real)</option>
                                    <option value="US$">US$ (Dólar)</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary">Salvar</button>
                    <button type="button" class="btn btn-secondary" id="cancelar-edicao" style="display: none;">Cancelar</button>
                </form>


                <!-- Tabela de listagem -->
                <table class="table table-bordered" id="tabela-empresas">
                    <thead class="table-dark">
                        <tr>
                            <th>Razão Social</th>
                            <th>CNPJ</th>
                            <th>Endereço</th>
                            <th>Telefone</th>
                            <th>E-mail</th>
                            <th>Moeda</th>
                            <th style="width: 100px">Ações</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>

                <script>
                    function carregarEmpresas() {
                        $.getJSON('./modulos/cadastrar/action/configuracao/read.php', function(dados) {
                            const tbody = $('#tabela-empresas tbody');
                            tbody.empty();

                            dados.forEach(empresa => {
                                tbody.append(`
                        <tr data-id="${empresa.id}">
                            <td>${empresa.razaoSocial}</td>
                            <td>${empresa.cnpj}</td>
                            <td>${empresa.endereco}</td>
                            <td>${empresa.telefone}</td>
                            <td>${empresa.email}</td>
                            <td>${empresa.moeda}</td>
                            <td>
                                <button class="btn btn-sm btn-warning btn-editar">✏️</button>
                                <button class="btn btn-sm btn-danger btn-excluir">🗑️</button>
                            </td>
                        </tr>
                    `);
                            });
                        });
                    }

                    $('#form-empresa').on('submit', function(e) {
                        e.preventDefault();
                        const formData = $(this).serialize();
                        const url = $('#empresa-id').val() ? './modulos/cadastrar/action/configuracao/update.php' : './modulos/cadastrar/action/configuracao/create.php';

                        $.post(url, formData, function() {
                            carregarEmpresas();
                            $('#form-empresa')[0].reset();
                            $('#empresa-id').val('');
                            $('#cancelar-edicao').hide();
                        });
                    });

                    $('#tabela-empresas').on('click', '.btn-editar', function() {
                        const linha = $(this).closest('tr');
                        $('#empresa-id').val(linha.data('id'));
                        $('#form-empresa input[name="razaoSocial"]').val(linha.children().eq(0).text());
                        $('#form-empresa input[name="cnpj"]').val(linha.children().eq(1).text());
                        $('#form-empresa input[name="endereco"]').val(linha.children().eq(2).text());
                        $('#form-empresa input[name="telefone"]').val(linha.children().eq(3).text());
                        $('#form-empresa input[name="email"]').val(linha.children().eq(4).text());
                        $('#cancelar-edicao').show();
                    });

                    $('#tabela-empresas').on('click', '.btn-excluir', function() {
                        const id = $(this).closest('tr').data('id');
                        if (confirm('Deseja excluir esta empresa?')) {
                            $.post('./modulos/cadastrar/action/configuracao/delete.php', {
                                id
                            }, function() {
                                carregarEmpresas();
                            });
                        }
                    });

                    $('#cancelar-edicao').on('click', function() {
                        $('#form-empresa')[0].reset();
                        $('#empresa-id').val('');
                        $(this).hide();
                    });

                    // Carrega empresas ao abrir a página
                    carregarEmpresas();
                </script>
            </div>
        </div>
    </div>
    <!-- Edição Configurações SMTP -->
    <div>
        <?php

        // Caminho para o arquivo de configuração
        $configFile = $configFile = __DIR__ . '/../action/configuracao/SMTP_config.json';

        // Verificar se o arquivo existe
        if (file_exists($configFile)) {
            // Carregar as configurações do arquivo JSON
            $config = json_decode(file_get_contents($configFile), true);

            // Verificar se houve erro na decodificação JSON
            if (json_last_error() !== JSON_ERROR_NONE) {
                echo 'Erro na leitura do arquivo JSON: ' . json_last_error_msg();
                // Definir valores padrão caso haja erro
                $config = [
                    'host' => '',
                    'username' => '',
                    'password' => '',
                    'port' => 465, // Valor padrão
                    'encryption' => 'ssl' // Valor padrão
                ];
            }
        } else {
            // Arquivo JSON não encontrado, definir valores padrão
            $config = [
                'host' => '',
                'username' => '',
                'password' => '',
                'port' => 465,
                'encryption' => 'ssl'
            ];
        }

        // Verificar se o formulário foi enviado para salvar as configurações
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Coletar os dados enviados pelo formulário
            $newConfig = [
                'host' => $_POST['host'],
                'username' => $_POST['username'],
                'password' => $_POST['password'],
                'port' => (int) $_POST['port'], // Garantir que a porta seja um número
                'encryption' => $_POST['encryption']
            ];

            // Salvar no arquivo JSON
            file_put_contents($configFile, json_encode($newConfig, JSON_PRETTY_PRINT));

            // Atualizar o array $config para refletir as alterações
            $config = $newConfig;
        }
        ?>

        <button class="btn btn-success mb-3" type="button" data-bs-toggle="collapse" data-bs-target="#collapseEdicaoSmtp" aria-expanded="false" aria-controls="collapseEstoque">
            <i class="fa-solid fa-plus"></i> Edição do SMTP
        </button>
        <div class="collapse" id="collapseEdicaoSmtp">
            <div class="card card-box">
                <div class="container mt-5">
                    <h2>Editar Configurações SMTP</h2>
                    <form method="POST" action="">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="host" class="form-label">Host</label>
                                    <input type="text" class="form-control" id="host" name="host" value="<?php echo htmlspecialchars($config['host']); ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="username" class="form-label">Username</label>
                                    <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($config['username']); ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="password" class="form-label">Password</label>
                                    <input type="text" class="form-control" id="password" name="password" value="<?php echo htmlspecialchars($config['password']); ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="port" class="form-label">Port</label>
                                    <input type="number" class="form-control" id="port" name="port" value="<?php echo htmlspecialchars($config['port']); ?>" min="1" max="65535">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="encryption" class="form-label">Encryption</label>
                                    <select class="form-select" id="encryption" name="encryption">
                                        <option value="ssl" <?php echo ($config['encryption'] == 'ssl') ? 'selected' : ''; ?>>SSL</option>
                                        <option value="tls" <?php echo ($config['encryption'] == 'tls') ? 'selected' : ''; ?>>TLS</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <button type="submit" class="btn btn-primary">Salvar Configurações</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <!-- Usuários -->
        <div>
            <button class="btn btn-success mb-3" type="button" data-bs-toggle="collapse" data-bs-target="#collapseUsuario" aria-expanded="false" aria-controls="collapseEstoque">
                <i class="fa-solid fa-plus"></i> Cadastro de Usuários
            </button>
            <div class="collapse" id="collapseUsuario">
                <div class="card card-box">
                    <div class="container mt-4">
                        <h2 class="mb-4">Cadastro de Usuário</h2>
                        <form id="cadastroUsuario">
                            <div class="row">
                                <!-- Coluna 1 -->
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="nome" class="form-label">Nome Completo</label>
                                        <input type="text" class="form-control" id="nome" name="nome" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="email" class="form-label">E-mail</label>
                                        <input type="email" class="form-control" id="email" name="email" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="senha" class="form-label">Senha</label>
                                        <input type="password" class="form-control" id="senha" name="senha" required>
                                        <div class="invalid-feedback">A senha deve ter no mínimo 8 caracteres, incluindo um número e um caractere especial.</div>
                                    </div>
                                    <div class="mb-3">
                                        <label for="confirmarSenha" class="form-label">Confirmar Senha</label>
                                        <input type="password" class="form-control" id="confirmarSenha" name="confirmarSenha" required>
                                        <div class="invalid-feedback">As senhas não coincidem.</div>
                                    </div>
                                </div>
                                <!-- Coluna 2 -->
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="role" class="form-label">Tipo de Usuário</label>
                                        <select class="form-select" id="role" name="role" required>
                                            <option value="" disabled selected>Selecione um tipo de usuário</option>
                                            <option value="admin">Admin</option>
                                            <option value="user">User</option>
                                        </select>
                                    </div>
                                    <div id="modulosContainer" class="mb-3 d-none">
                                        <label class="form-label">Permissões de Módulos</label>
                                        <div id="listaModulos"></div>
                                    </div>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary">Cadastrar</button>
                            <div id="mensagem" class="mt-3"></div>
                        </form>
                    </div>


                    <script>
                        function inicializarCadastroUsuario() {
                            const roleSelect = document.getElementById("role");
                            const modulosContainer = document.getElementById("modulosContainer");
                            const listaModulos = document.getElementById("listaModulos");
                            const mensagem = document.getElementById("mensagem");

                            function carregarModulos() {
                                const xhr = new XMLHttpRequest();
                                xhr.open("GET", "./modulos/cadastrar/action/configuracao/listar_modulos.php", true);
                                xhr.onreadystatechange = function() {
                                    if (xhr.readyState === 4 && xhr.status === 200) {
                                        const modulos = JSON.parse(xhr.responseText);
                                        listaModulos.innerHTML = "";

                                        // Iterando sobre as chaves do objeto
                                        Object.keys(modulos).forEach(modulo => {
                                            const div = document.createElement("div");
                                            div.classList.add("form-check");
                                            div.innerHTML = `<input class="form-check-input" type="checkbox" name="modulos[]" value="${modulo}"> 
                                 <label class="form-check-label">${modulo}</label>`;
                                            listaModulos.appendChild(div);
                                        });
                                    }
                                };
                                xhr.send();
                            }

                            roleSelect.addEventListener("change", function() {
                                if (this.value === "user") {
                                    modulosContainer.classList.remove("d-none");
                                    carregarModulos();
                                } else {
                                    modulosContainer.classList.add("d-none");
                                }
                            });

                            document.getElementById("cadastroUsuario").addEventListener("submit", function(event) {
                                event.preventDefault();
                                const nome = document.getElementById("nome").value.trim();
                                const email = document.getElementById("email").value.trim();
                                const senha = document.getElementById("senha").value;
                                const confirmarSenha = document.getElementById("confirmarSenha").value;
                                const role = roleSelect.value;
                                const modulos = Array.from(document.querySelectorAll('input[name="modulos[]"]:checked')).map(el => el.value);

                                const senhaValida = /^(?=.*\d)(?=.*[\W_]).{8,}$/;
                                let valido = true;

                                if (!senhaValida.test(senha)) {
                                    document.getElementById("senha").classList.add("is-invalid");
                                    valido = false;
                                } else {
                                    document.getElementById("senha").classList.remove("is-invalid");
                                }

                                if (senha !== confirmarSenha) {
                                    document.getElementById("confirmarSenha").classList.add("is-invalid");
                                    valido = false;
                                } else {
                                    document.getElementById("confirmarSenha").classList.remove("is-invalid");
                                }

                                if (!valido) {
                                    mensagem.innerHTML = '<div class="alert alert-danger">Corrija os erros antes de enviar.</div>';
                                    return;
                                }

                                const formData = new FormData();
                                formData.append("nome", nome);
                                formData.append("email", email);
                                formData.append("senha", senha);
                                formData.append("role", role);
                                modulos.forEach(modulo => formData.append("modulos[]", modulo));

                                const xhr = new XMLHttpRequest();
                                xhr.open("POST", "./modulos/cadastrar/action/configuracao/create_user.php", true);
                                xhr.onreadystatechange = function() {
                                    if (xhr.readyState === 4) {
                                        const result = JSON.parse(xhr.responseText);
                                        if (xhr.status === 200 && result.success) {
                                            mensagem.innerHTML = '<div class="alert alert-success">Usuário cadastrado com sucesso!</div>';
                                            document.getElementById("cadastroUsuario").reset();
                                            modulosContainer.classList.add("d-none");
                                        } else {
                                            mensagem.innerHTML = `<div class="alert alert-danger">${result.message || "Erro ao cadastrar."}</div>`;
                                        }
                                    }
                                };
                                xhr.send(formData);
                            });
                        }

                        // Certifique-se de chamar essa função depois de inserir o HTML dinamicamente:
                        inicializarCadastroUsuario();
                    </script>


                    <!-- LISTAGEM -->

                    <?php
                    $arquivo = __DIR__ . "/action/users.json";
                    $usuarios = [];

                    if (file_exists($arquivo)) {
                        $json = file_get_contents($arquivo);
                        $usuarios = json_decode($json, true) ?? [];
                    }
                    ?>
                    <div class="container mt-4">
                        <h2 class="mb-4">Lista de Usuários</h2>
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nome</th>
                                    <th>E-mail</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($usuarios)): ?>
                                    <?php foreach ($usuarios as $user): ?>
                                        <tr data-id="<?= htmlspecialchars($user['id']) ?>">
                                            <td><?= htmlspecialchars($user['id']) ?></td>
                                            <td><?= htmlspecialchars($user['nome']) ?></td>
                                            <td><?= htmlspecialchars($user['email']) ?></td>
                                            <td>
                                                <button class="btn btn-warning btn-sm edit-btn"
                                                    data-user='<?= json_encode($user, JSON_HEX_APOS) ?>'>
                                                    Editar
                                                </button>
                                                <button class="btn btn-danger btn-sm delete-btn">Excluir</button>
                                            </td>
                                        </tr>
                                        <tr class="collapse-row" id="collapse-<?= htmlspecialchars($user['id']) ?>" style="display: none;">
                                            <td colspan="4">
                                                <form class="edit-form">
                                                    <input type="hidden" name="id" value="<?= htmlspecialchars($user['id']) ?>">
                                                    <div class="mb-3">
                                                        <label>Nome</label>
                                                        <input type="text" name="nome" class="form-control edit-name" required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label>E-mail</label>
                                                        <input type="email" name="email" class="form-control edit-email" required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label>Role</label>
                                                        <select name="role" class="form-control edit-role" required>
                                                            <option value="" disabled>Selecione um papel</option>
                                                            <option value="admin">Admin</option>
                                                            <option value="user">User</option>
                                                        </select>
                                                    </div>
                                                    <div class="mb-3 modules-container">
                                                        <label>Módulos</label>
                                                        <div class="edit-modules"></div>
                                                    </div>
                                                    <button type="submit" class="btn btn-primary">Salvar Alterações</button>
                                                </form>

                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="text-center">Nenhum usuário cadastrado.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <script>
                    document.body.addEventListener("submit", function(event) {
                        if (event.target.classList.contains("edit-form")) {
                            event.preventDefault();

                            const form = event.target;
                            const formData = new FormData(form);
                            const data = {};

                            formData.forEach((value, key) => {
                                if (key === "modulos[]") {
                                    if (!Array.isArray(data.modulos)) {
                                        data.modulos = [];
                                    }
                                    data.modulos.push(value);
                                } else {
                                    data[key] = value;
                                }
                            });

                            console.log("Dados a serem enviados:", JSON.stringify(data));

                            fetch('./modulos/cadastrar/action/configuracao/update_user.php', {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                    },
                                    body: JSON.stringify(data),
                                })
                                .then(response => response.json())
                                .then(data => {
                                    if (data.sucesso) {
                                        location.reload();
                                        const collapseRow = form.closest('.collapse-row');
                                        collapseRow.style.display = "none";
                                    } else {
                                        alert("Erro ao atualizar usuário: " + (data.erro || 'Erro desconhecido'));
                                    }
                                })
                                .catch(error => {
                                    console.error("Erro na requisição AJAX:", error);
                                    alert("Ocorreu um erro ao tentar salvar as alterações.");
                                });
                        }
                    });





                    function carregarModulosExistentes(callback) {
                        fetch('./modulos/cadastrar/action/configuracao/listar_modulos.php')
                            .then(response => response.json())
                            .then(data => callback(Object.keys(data)))
                            .catch(error => {
                                console.error("Erro ao carregar módulos existentes:", error);
                                callback([]);
                            });
                    }


                    document.body.addEventListener("click", function(event) {
                        if (event.target.classList.contains("edit-btn")) {
                            const btn = event.target;
                            const user = JSON.parse(btn.getAttribute("data-user"));
                            const collapseRow = document.getElementById(`collapse-${user.id}`);
                            const editForm = collapseRow.querySelector(".edit-form");

                            collapseRow.style.display = collapseRow.style.display === "none" ? "" : "none";

                            editForm.querySelector(".edit-name").value = user.nome;
                            editForm.querySelector(".edit-email").value = user.email;
                            editForm.querySelector(".edit-role").value = user.role || "";

                            carregarModulosExistentes(modulosDisponiveis => {
                                const modulesContainer = editForm.querySelector(".edit-modules");
                                modulesContainer.innerHTML = "";

                                modulosDisponiveis.forEach(module => {
                                    const isChecked = user.modulos && user.modulos.includes(module) ? "checked" : "";
                                    modulesContainer.innerHTML += `
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="modulos[]" value="${module}" id="mod_${module}" ${isChecked}>
                        <label class="form-check-label" for="mod_${module}">${module}</label>
                    </div>
                `;
                                });

                                const roleSelect = editForm.querySelector(".edit-role");
                                const modulesDiv = editForm.querySelector(".modules-container");

                                modulesDiv.style.display = roleSelect.value === "user" ? "block" : "none";

                                roleSelect.addEventListener("change", () => {
                                    modulesDiv.style.display = roleSelect.value === "user" ? "block" : "none";
                                });
                            });
                        }
                    });

                    //Deletar
                    document.querySelectorAll('.delete-btn').forEach(button => {
                        button.addEventListener('click', function() {
                            const usuarioId = this.closest('tr').getAttribute('data-id');
                            if (confirm("Tem certeza que deseja excluir este Usuario?")) {
                                const formData = new FormData();
                                formData.append('id', usuarioId);

                                fetch('./modulos/cadastrar/action/configuracao/delete_user.php', {
                                        method: 'POST',
                                        body: formData
                                    })
                                    .then(response => response.json())
                                    .then(data => {
                                        if (data.success) {
                                            alert('Usuario excluído com sucesso!');
                                            location.reload();
                                        } else {
                                            alert('Erro ao excluir o Usuario: ' + data.message);
                                        }
                                    })
                                    .catch(error => {
                                        console.error('Erro:', error);
                                        alert('Ocorreu um erro ao tentar excluir o Usuario.');
                                    });
                            }
                        });
                    });
                </script>
            </div>
        </div>
        </table>