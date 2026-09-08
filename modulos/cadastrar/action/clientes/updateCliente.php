<?php

$clientesImportadosPath = __DIR__ . '/clientes.json';
$clientesCadastradosPath = __DIR__ . '/clientes_cadastrados.json';

// Verifica se os arquivos existem
if (!file_exists($clientesImportadosPath) || !file_exists($clientesCadastradosPath)) {
    echo json_encode(['success' => false, 'message' => 'Arquivo(s) de clientes não encontrado(s).']);
    exit;
}

// Carrega os arquivos JSON
$clientesImportados = json_decode(file_get_contents($clientesImportadosPath), true);
$clientesCadastrados = json_decode(file_get_contents($clientesCadastradosPath), true);

// Verifica se os dados foram enviados corretamente
if (!isset($_POST['Cliente']) && !isset($_POST['Codigo Cliente'])) {
    echo json_encode(['success' => false, 'message' => 'Código do cliente não especificado.']);
    exit;
}

$codigoClienteImportado = $_POST['Cliente'] ?? null;
$codigoClienteCadastrado = $_POST['Codigo Cliente'] ?? null;

$clienteImportadoEncontrado = false;

// === FLUXO 1: Atualiza cliente importado ===
if ($codigoClienteImportado) {
    foreach ($clientesImportados as &$cliente) {
        if ($cliente['Cliente'] === $codigoClienteImportado) {
            $cliente['Nome'] = $_POST['Nome'] ?? $cliente['Nome'];
            $cliente['CNPJ/CPF'] = $_POST['CNPJ/CPF'] ?? $cliente['CNPJ/CPF'];
            $cliente['Endereco'] = $_POST['Endereco'] ?? $cliente['Endereco'];
            $cliente['Cidade'] = $_POST['Cidade'] ?? $cliente['Cidade'];
            $cliente['Estado'] = $_POST['Estado'] ?? $cliente['Estado'];
            $cliente['Fone1'] = $_POST['Fone1'] ?? $cliente['Fone1'];
            $cliente['Email'] = $_POST['Email'] ?? $cliente['Email'];

            $clienteImportadoEncontrado = true;
            break;
        }
    }

    if ($clienteImportadoEncontrado) {
        if (file_put_contents($clientesImportadosPath, json_encode($clientesImportados, JSON_PRETTY_PRINT))) {
            echo json_encode(['success' => true, 'message' => 'Cliente importado atualizado.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Falha ao salvar o arquivo de clientes importados.']);
        }
        exit;
    }
}

// === FLUXO 2: Atualiza cliente cadastrado ===
if ($codigoClienteCadastrado) {
    $clienteCadastradoEncontrado = false;

    foreach ($clientesCadastrados as &$cliente) {
        if ($cliente['Codigo Cliente'] === $codigoClienteCadastrado) {
            // Atualiza todos os campos recebidos no POST que existirem no cliente
            foreach ($_POST as $campo => $valor) {
                if (array_key_exists($campo, $cliente)) {
                    $cliente[$campo] = $valor;
                }
            }

            $clienteCadastradoEncontrado = true;
            break;
        }
    }

    if ($clienteCadastradoEncontrado) {
        if (file_put_contents($clientesCadastradosPath, json_encode($clientesCadastrados, JSON_PRETTY_PRINT))) {
            echo json_encode(['success' => true, 'message' => 'Cliente cadastrado atualizado.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Falha ao salvar o arquivo de clientes cadastrados.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Cliente não encontrado em nenhum dos fluxos.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Código do cliente cadastrado não informado.']);
}
