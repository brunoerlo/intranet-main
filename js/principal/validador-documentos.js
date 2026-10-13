/**
 * Validação e formatação de CPF e CNPJ
 */

function validarCPF(cpf) {
    if (!cpf) return false;
    cpf = String(cpf).replace(/\D/g, '');

    if (cpf.length !== 11) return false;

    // Elimina repetições como 000.000.000-00, 111.111.111-11, etc.
    if (/^(\d)\1{10}$/.test(cpf)) return false;

    let soma = 0;
    for (let i = 0; i < 9; i++) {
        soma += parseInt(cpf.charAt(i), 10) * (10 - i);
    }
    let resto = soma % 11;
    let digito1 = resto < 2 ? 0 : 11 - resto;
    if (digito1 !== parseInt(cpf.charAt(9), 10)) return false;

    soma = 0;
    for (let i = 0; i < 10; i++) {
        soma += parseInt(cpf.charAt(i), 10) * (11 - i);
    }
    resto = soma % 11;
    let digito2 = resto < 2 ? 0 : 11 - resto;
    if (digito2 !== parseInt(cpf.charAt(10), 10)) return false;

    return true;
}

function validarCNPJ(cnpj) {
    if (!cnpj) return false;
    cnpj = String(cnpj).replace(/\D/g, '');

    if (cnpj.length !== 14) return false;

    // Elimina repetições como 00.000.000/0000-00, 11.111.111/1111-11, etc.
    if (/^(\d)\1{13}$/.test(cnpj)) return false;

    let tamanho = 12;
    let numeros = cnpj.substring(0, tamanho);
    let soma = 0;
    let pos = tamanho - 7;
    for (let i = tamanho; i >= 1; i--) {
        soma += parseInt(numeros.charAt(tamanho - i), 10) * pos--;
        if (pos < 2) pos = 9;
    }
    let resultado = soma % 11 < 2 ? 0 : 11 - (soma % 11);
    if (resultado !== parseInt(cnpj.charAt(12), 10)) return false;

    tamanho = 13;
    numeros = cnpj.substring(0, tamanho);
    soma = 0;
    pos = tamanho - 7;
    for (let i = tamanho; i >= 1; i--) {
        soma += parseInt(numeros.charAt(tamanho - i), 10) * pos--;
        if (pos < 2) pos = 9;
    }
    resultado = soma % 11 < 2 ? 0 : 11 - (soma % 11);
    if (resultado !== parseInt(cnpj.charAt(13), 10)) return false;

    return true;
}

function mascaraCPF(valor) {
    if (!valor) return '';
    return String(valor)
        .replace(/\D/g, '')
        .slice(0, 11)
        .replace(/(\d{3})(\d)/, '$1.$2')
        .replace(/(\d{3})(\d)/, '$1.$2')
        .replace(/(\d{3})(\d{1,2})$/, '$1-$2');
}

function mascaraCNPJ(valor) {
    if (!valor) return '';
    return String(valor)
        .replace(/\D/g, '')
        .slice(0, 14)
        .replace(/(\d{2})(\d)/, '$1.$2')
        .replace(/(\d{3})(\d)/, '$1.$2')
        .replace(/(\d{3})(\d)/, '$1/$2')
        .replace(/(\d{4})(\d{1,2})$/, '$1-$2');
}

function formatarCpfCnpj(valor) {
    if (!valor) return '';
    const limpo = String(valor).replace(/\D/g, '');
    if (limpo.length === 11) {
        return mascaraCPF(limpo);
    } else if (limpo.length === 14) {
        return mascaraCNPJ(limpo);
    }
    return valor;
}

// Expõe globalmente
window.validarCPF = validarCPF;
window.validarCNPJ = validarCNPJ;
window.mascaraCPF = mascaraCPF;
window.mascaraCNPJ = mascaraCNPJ;
window.formatarCpfCnpj = formatarCpfCnpj;

