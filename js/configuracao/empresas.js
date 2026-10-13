function carregarEmpresas() {
    $.getJSON('./modulos/configuracao/action/read.php', function (dados) {
        const tbody = $('#tabela-empresas tbody');
        tbody.empty();

        dados.forEach(empresa => {
            const cnpjFormatado = typeof formatarCpfCnpj === 'function' ? formatarCpfCnpj(empresa.cnpj) : empresa.cnpj;
            tbody.append(`
                        <tr data-id="${empresa.id}">
                            <td>${empresa.razaoSocial}</td>
                            <td>${cnpjFormatado}</td>
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

// Máscara e validação em tempo real no campo CNPJ
$('#form-empresa').on('input', 'input[name="cnpj"]', function () {
    if (typeof mascaraCNPJ === 'function') {
        this.value = mascaraCNPJ(this.value);
    }
    const clean = this.value.replace(/\D/g, '');
    if (clean.length === 14) {
        if (typeof validarCNPJ === 'function' && validarCNPJ(clean)) {
            $(this).removeClass('is-invalid').addClass('is-valid');
        } else {
            $(this).addClass('is-invalid').removeClass('is-valid');
        }
    } else {
        $(this).removeClass('is-valid is-invalid');
    }
});

$('#form-empresa').on('blur', 'input[name="cnpj"]', function () {
    const clean = this.value.replace(/\D/g, '');
    if (clean.length > 0) {
        if (typeof validarCNPJ === 'function' && validarCNPJ(clean)) {
            $(this).removeClass('is-invalid').addClass('is-valid');
        } else {
            $(this).addClass('is-invalid').removeClass('is-valid');
        }
    } else {
        $(this).removeClass('is-valid is-invalid');
    }
});

$('#form-empresa').on('submit', function (e) {
    e.preventDefault();

    const cnpjInput = $(this).find('input[name="cnpj"]');
    const cnpjVal = cnpjInput.val();
    if (typeof validarCNPJ === 'function' && !validarCNPJ(cnpjVal)) {
        cnpjInput.addClass('is-invalid').removeClass('is-valid');
        alert('CNPJ inválido. Por favor, digite um CNPJ válido existente.');
        cnpjInput.focus();
        return;
    }

    const formData = $(this).serialize();
    const url = $('#empresa-id').val() ? './modulos/configuracao/action/update.php' : './modulos/configuracao/action/create.php';

    $.post(url, formData)
        .done(function () {
            carregarEmpresas();
            $('#form-empresa')[0].reset();
            $('#form-empresa input[name="cnpj"]').removeClass('is-valid is-invalid');
            $('#empresa-id').val('');
            $('#cancelar-edicao').hide();
        })
        .fail(function (xhr) {
            let msg = 'Erro ao salvar empresa.';
            try {
                const res = JSON.parse(xhr.responseText);
                if (res.mensagem) msg = res.mensagem;
            } catch (err) {}
            alert(msg);
        });
});

$('#tabela-empresas').on('click', '.btn-editar', function () {
    const linha = $(this).closest('tr');
    $('#empresa-id').val(linha.data('id'));
    $('#form-empresa input[name="razaoSocial"]').val(linha.children().eq(0).text());
    const rawCnpj = linha.children().eq(1).text();
    $('#form-empresa input[name="cnpj"]')
        .val(typeof mascaraCNPJ === 'function' ? mascaraCNPJ(rawCnpj) : rawCnpj)
        .removeClass('is-invalid is-valid');
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
    $('#form-empresa input[name="cnpj"]').removeClass('is-valid is-invalid');
    $('#empresa-id').val('');
    $(this).hide();
});

// Carrega empresas ao abrir a página
carregarEmpresas();