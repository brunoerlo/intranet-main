<?php

/**
 * Valida o número do CPF através do cálculo matemático dos dígitos verificadores.
 *
 * @param string|int $cpf
 * @return bool
 */
function validarCPF($cpf): bool
{
    // Remove qualquer caractere que não seja número
    $cpf = preg_replace('/\D/', '', (string)$cpf);

    // Deve conter exatamente 11 dígitos
    if (strlen($cpf) !== 11) {
        return false;
    }

    // Descarta sequências com todos os dígitos iguais (ex: 000.000.000-00, 111.111.111-11)
    if (count(array_unique(str_split($cpf))) === 1) {
        return false;
    }

    // Validação matemática do primeiro e segundo dígito verificador
    for ($t = 9; $t < 11; $t++) {
        $soma = 0;
        for ($c = 0; $c < $t; $c++) {
            $soma += (int)$cpf[$c] * (($t + 1) - $c);
        }
        $resto = $soma % 11;
        $digito = ($resto < 2) ? 0 : 11 - $resto;
        if ((int)$cpf[$t] !== $digito) {
            return false;
        }
    }

    return true;
}

/**
 * Valida o número do CNPJ através do cálculo matemático dos dígitos verificadores.
 *
 * @param string|int $cnpj
 * @return bool
 */
function validarCNPJ($cnpj): bool
{
    // Remove qualquer caractere que não seja número
    $cnpj = preg_replace('/\D/', '', (string)$cnpj);

    // Deve conter exatamente 14 dígitos
    if (strlen($cnpj) !== 14) {
        return false;
    }

    // Descarta sequências com todos os dígitos iguais (ex: 00.000.000/0000-00, 11.111.111/1111-11)
    if (count(array_unique(str_split($cnpj))) === 1) {
        return false;
    }

    // Validação matemática do primeiro dígito verificador
    $pesos1 = [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
    $soma1 = 0;
    for ($i = 0; $i < 12; $i++) {
        $soma1 += (int)$cnpj[$i] * $pesos1[$i];
    }
    $resto1 = $soma1 % 11;
    $digito1 = ($resto1 < 2) ? 0 : 11 - $resto1;
    if ((int)$cnpj[12] !== $digito1) {
        return false;
    }

    // Validação matemática do segundo dígito verificador
    $pesos2 = [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
    $soma2 = 0;
    for ($i = 0; $i < 13; $i++) {
        $soma2 += (int)$cnpj[$i] * $pesos2[$i];
    }
    $resto2 = $soma2 % 11;
    $digito2 = ($resto2 < 2) ? 0 : 11 - $resto2;
    if ((int)$cnpj[13] !== $digito2) {
        return false;
    }

    return true;
}

/**
 * Formata CPF ou CNPJ adicionando pontuações
 *
 * @param string|null $valor
 * @return string
 */
if (!function_exists('formatarCpfCnpj')) {
    function formatarCpfCnpj($valor): string
    {
        $valor = preg_replace('/\D/', '', (string)$valor);

        if (strlen($valor) === 11) {
            return preg_replace("/(\d{3})(\d{3})(\d{3})(\d{2})/", "$1.$2.$3-$4", $valor);
        } elseif (strlen($valor) === 14) {
            return preg_replace("/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/", "$1.$2.$3/$4-$5", $valor);
        } else {
            return $valor;
        }
    }
}

