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
            $.getJSON('./modulos/configuracao/action/read.php', function(dados) {
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
            const url = $('#empresa-id').val() ? './modulos/configuracao/action/update.php' : './modulos/configuracao/action/create.php';

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
                $.post('./modulos/configuracao/action/delete.php', { id }, function() {
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
