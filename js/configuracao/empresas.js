function carregarEmpresas() {
    $.getJSON('./modulos/configuracao/action/read.php', function (dados) {
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
                                <button class="btn btn-sm btn-warning btn-editar"><i class="fa-solid fa-pencil"></i></button>
                                <button class="btn btn-sm btn-danger btn-excluir"><i class="fa-solid fa-trash"></i></button>
                            </td>
                        </tr>
                    `);
        });
    });
}

$('#form-empresa').on('submit', function (e) {
    e.preventDefault();
    const formData = $(this).serialize();
    const url = $('#empresa-id').val() ? './modulos/configuracao/action/update.php' : './modulos/configuracao/action/create.php';

    $.post(url, formData, function () {
        carregarEmpresas();
        $('#form-empresa')[0].reset();
        $('#empresa-id').val('');
        $('#cancelar-edicao').hide();
    });
});

$('#tabela-empresas').on('click', '.btn-editar', function () {
    const linha = $(this).closest('tr');
    $('#empresa-id').val(linha.data('id'));
    $('#form-empresa input[name="razaoSocial"]').val(linha.children().eq(0).text());
    $('#form-empresa input[name="cnpj"]').val(linha.children().eq(1).text());
    $('#form-empresa input[name="endereco"]').val(linha.children().eq(2).text());
    $('#form-empresa input[name="telefone"]').val(linha.children().eq(3).text());
    $('#form-empresa input[name="email"]').val(linha.children().eq(4).text());
    $('#cancelar-edicao').show();
});

$('#tabela-empresas').on('click', '.btn-excluir', function () {
    const id = $(this).closest('tr').data('id');
    if (confirm('Deseja excluir esta empresa?')) {
        $.post('./modulos/configuracao/action/delete.php', { id }, function () {
            carregarEmpresas();
        });
    }
});

$('#cancelar-edicao').on('click', function () {
    $('#form-empresa')[0].reset();
    $('#empresa-id').val('');
    $(this).hide();
});

// Carrega empresas ao abrir a página
carregarEmpresas();