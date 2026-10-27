<?php
header('Content-Type: application/json');

require_once dirname(__DIR__, 4) . '/logger.php';

session_start();
$usuario = $_SESSION["usuario"]["nome"];

function normalizacaoClientes(array $row)
{
    $codigo = '';
    foreach ($row as $cod => $c) {
        $codLimpo = trim(preg_replace('/^\xEF\xBB\xBF/', '', $cod));
        if (strcasecmp($codLimpo, 'Codigo') === 0) {
            $codigo = trim($c);
            break;
        }
    }

    if ($codigo === '') {
        $codigo = uniqid('cli_', true);
    }

    $natureza = strtolower(trim($row['Natureza'] ?? ''));
    if ($natureza === 'fisica')       $tipo = 'cpf';
    elseif ($natureza === 'juridica') $tipo = 'cnpj';
    else                              $tipo = 'exterior';

    $nome = trim($row['Nome'] ?? '');
    $docRaw = trim($row['CNPJ_CPF'] ?? '');
    $docNumeros = preg_replace('/\D/', '', $docRaw);

    $ieRaw = trim($row['Inscr_Estadual'] ?? '');
    if ($ieRaw === '' || $ieRaw === '-' || preg_match('/(Endereco|TDS Software|\-{3,})/i', $ieRaw)) {
        $inscricaoEstadual = ($tipo === 'cnpj') ? 'ISENTO' : '';
    } else $inscricaoEstadual =  strtoupper($ieRaw) === 'ISENTO' ? 'ISENTO' : $ieRaw;

    $endereco = trim($row['Endereco'] ?? '');
    $complemento = trim($row['Complemento'] ?? '');
    $bairro = trim($row['Bairro'] ?? '');
    if ($complemento !== '' && strpos($complemento, 'Bairro') !== 0 && strcasecmp($complemento, $bairro) !== 0) {
        $rua = $endereco !== '' ? "{$endereco} - {$complemento}" : $complemento;
    } else $rua = $endereco;

    $cep = preg_replace('/\D/', '', $row['CEP'] ?? '');

    $contatoRaw = trim($row['Contato'] ?? '');
    if ($contatoRaw === '' || preg_match('/^(Caixa Postal|[\'\-\\\]+$)/i', $contatoRaw)) {
        $pessoaContato = '';
    } else $pessoaContato = $contatoRaw;

    $cidade = trim($row['Cidade'] ?? '');
    $ufRaw = trim($row['UF'] ?? '');
    $estado = '';
    if (preg_match('/^([A-Z]{2})\b/i', $ufRaw, $m)) {
        $estado = strtoupper($m[1]);
    }
    $paisRaw = trim($row['Pais'] ?? '-');
    if (strpos($paisRaw, '-') !== false) {
        $partesPais = explode('-', $paisRaw, 2);
        $pais = trim($partesPais[1]);
    } else {
        $pais = $paisRaw;
    }

    $fone1 = trim($row['Fone_1'] ?? '');
    $fone2 = trim($row['Fone_2'] ?? '');
    $limparFone = function (string $f): string {
        if ($f === '' || preg_match('/(Fax|Fone Modem|Ramal|:)/i', $f)) {
            return '';
        }
        return $f;
    };
    $telefone = $limparFone($fone1);
    if ($telefone === '') $telefone = $limparFone($fone2);

    $emailRaw = trim($row['Email'] ?? '');
    $emailRaw = trim(preg_replace('/^Site\s*\.*:\s*/i', '', $emailRaw), " \t\n\r\0\x0B\"'");
    $email = '';
    if (strpos($emailRaw, '@') !== false && strpos($emailRaw, 'não tem') === false) {
        $candidatos = preg_split('/[;\/]+/', $emailRaw);
        foreach ($candidatos as $cand) {
            // Remove observações entre parênteses como "(contato via Whats)"
            $cand = trim(preg_replace('/\(.*?\)/', '', $cand));
            if (strpos($cand, '@') !== false) {
                $email = $cand;
                break;
            }
        }
    }

    return [
        "origem"            => 'importado',
        "tipo"              => $tipo,
        "cpf"               => $tipo === 'cpf' ? $docNumeros : '',
        "nomeCompleto"      => $tipo === 'cpf' ? $nome : '',
        "cnpj"              => $tipo === 'cnpj' ? $docNumeros : '',
        "razaoSocial"       => $tipo === 'cnpj' ? $nome : '',
        "inscricaoEstadual" => $inscricaoEstadual,
        "suframa"           => '',
        "pessoaContato"     => $pessoaContato,
        "nomeImportador"    => $tipo === 'exterior' ? $nome : '',
        "docImportador"     => $tipo === 'exterior' ? $docRaw : '',
        "enderecoExterior"  => $tipo === 'exterior' ? $rua : '',
        "pais"              => $pais,
        "portoOuAeroporto"  => '',
        "contatoAduaneiro"  => $tipo === 'exterior' ? $pessoaContato : '',
        "rua"               => $tipo !== 'exterior' ? $rua : '',
        "bairro"            => $tipo !== 'exterior' ? $bairro : '',
        "cidade"            => $tipo !== 'exterior' ? $cidade : '',
        "estado"            => $tipo !== 'exterior' ? $estado : '',
        "cep"               => $tipo !== 'exterior' ? $cep : '',
        "telefone"          => $telefone,
        "email"             => $email,
        "Codigo Cliente"    => $codigo
    ];
}

if (isset($_FILES['csvFile']) && $_FILES['csvFile']['error'] === UPLOAD_ERR_OK) {
    $csvFile = $_FILES['csvFile']['tmp_name'];
    $handle = fopen($csvFile, 'r');

    if ($handle === false) {
        echo json_encode(['status' => 'error', 'error' => 'Não foi possível abrir o arquivo CSV.']);
        exit();
    }

    $headers = fgetcsv($handle, 0, ';'); // Separador de campos é o ponto e vírgula
    if ($headers === false) {
        echo json_encode(['status' => 'error', 'error' => 'Erro ao ler os cabeçalhos do arquivo CSV.']);
        exit();
    }

    $headers[0] = preg_replace('/^\xEF\xBB\xBF/', '', $headers[0]);
    $headers = array_map('trim', $headers);

    $clientes = [];
    
    while (($data = fgetcsv($handle, 0, ';')) !== false) {
        if (count($data) === count($headers)) {
            $cliente = array_combine($headers, $data);
            if (isset($cliente['Natureza']) || isset($cliente['CNPJ_CPF'])) {
                $clientes[] = normalizacaoClientes($cliente);
            } else {
                $clientes[] = $cliente;
            }
        }
    }

    fclose($handle);

    if (empty($clientes)) {
        echo json_encode(['status' => 'error', 'error' => 'Nenhum cliente foi encontrado no arquivo CSV.']);
        exit();
    }


    // Converte o array de clientes para JSON
    $json = json_encode($clientes, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

    // Salva o JSON em um arquivo
    $jsonFilePath = __DIR__ . '/clientes.json';
    if (file_put_contents($jsonFilePath, $json) === false) {
        echo json_encode(['status' => 'error', 'error' => 'Falha ao salvar o arquivo JSON.']);
        exit();
    }
    registrarLog($usuario, 'cadastrar', 'clientes', 'importar', "Importou cliente(s)");
    echo json_encode(['status' => 'success', 'message' => 'Clientes importados com sucesso.']);
} else {
    echo json_encode(['status' => 'error', 'error' => 'Nenhum arquivo CSV foi enviado ou ocorreu um erro no upload.']);
}
