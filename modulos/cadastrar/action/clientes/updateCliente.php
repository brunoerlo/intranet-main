<?php

$clientesImportadosPath = __DIR__ . '/clientes.json';
$clientesCadastradosPath = __DIR__ . '/clientes_cadastrados.json';

require_once dirname(__DIR__, 4) . '/logger.php';   

session_start();
$usuario = $_SESSION["usuario"]["nome"];

// Verifica se os arquivos existem
if (!file_exists($clientesImportadosPath) || !file_exists($clientesCadastradosPath)) {
    echo json_encode(['success' => false, 'message' => 'Arquivo(s) de clientes não encontrado(s).']);
    exit;
}

// Carrega os arquivos JSON
$clientesImportados = json_decode(file_get_contents($clientesImportadosPath), true);
$clientesCadastrados = json_decode(file_get_contents($clientesCadastradosPath), true);

// Verifica se os dados foram enviados corretamente
if (!isset($_POST['Codigo_Cliente'])) {
    echo json_encode(['success' => false, 'message' => 'Código do cliente não especificado.']);
    exit;
}

$codigoCliente = $_POST['Codigo_Cliente'];

$clienteImportadoEncontrado = false;

// === FLUXO 1: Atualiza cliente importado ===
if ($codigoCliente) {
    $itemEditado = null;
    foreach ($clientesImportados as &$cliente) {
        if (($cliente['Codigo Cliente'] ?? '') === $codigoCliente) {
            // Atualiza todos os campos recebidos no POST que existirem no cliente
            foreach ($_POST as $campo => $valor) {
                if (array_key_exists($campo, $cliente)) {
                    $cliente[$campo] = $valor;
                }
            }

            $itemEditado = $cliente;
            $clienteImportadoEncontrado = true;
            break;
        }
    }
    unset($cliente);

    if ($clienteImportadoEncontrado) {
        if (file_put_contents($clientesImportadosPath, json_encode($clientesImportados, JSON_PRETTY_PRINT))) {
            $nomeLog = $itemEditado['tipo'] === 'cpf' ? $itemEditado['nomeCompleto'] : ($itemEditado['tipo'] === 'cnpj' ? $itemEditado['razaoSocial'] : $itemEditado['nomeImportador']);
            registrarLog($usuario, 'cadastrar', 'clientes', 'editar', "Editou o cliente {$nomeLog}");
            echo json_encode(['success' => true, 'message' => 'Cliente importado atualizado.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Falha ao salvar o arquivo de clientes importados.']);
        }
        exit;
    }
}

// === FLUXO 2: Atualiza cliente cadastrado ===
if ($codigoCliente) {
    $clienteCadastradoEncontrado = false;
    $itemEditado = null;

    foreach ($clientesCadastrados as &$cliente) {
        if ($cliente['Codigo Cliente'] === $codigoCliente) {
            // Atualiza todos os campos recebidos no POST que existirem no cliente
            foreach ($_POST as $campo => $valor) {
                if (array_key_exists($campo, $cliente)) {
                    $cliente[$campo] = $valor;
                }
            }

            $itemEditado = $cliente;
            $clienteCadastradoEncontrado = true;
            break;
        }
    }
    unset($cliente);

    if ($clienteCadastradoEncontrado) {
        if (file_put_contents($clientesCadastradosPath, json_encode($clientesCadastrados, JSON_PRETTY_PRINT))) {
            $nomeLog = $itemEditado['tipo'] === 'cpf' ? $itemEditado['nomeCompleto'] : ($itemEditado['tipo'] === 'cnpj' ? $itemEditado['razaoSocial'] : $itemEditado['nomeImportador']);
            registrarLog($usuario, 'cadastrar', 'clientes', 'editar', "Editou o cliente {$nomeLog}");
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
