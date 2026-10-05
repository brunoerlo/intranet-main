<?php
$faturasJson = file_get_contents(__DIR__ . '/action/faturas.json');
$faturas = json_decode($faturasJson, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    echo 'Erro ao decodificar faturas.json: ' . json_last_error_msg();
    $faturas = [];
}

?>
<div class="container-fluid mt-4">
    <button class="btn btn-success mb-3" id="btn-nova-importacao" type="button" data-bs-toggle="collapse" data-bs-target="#collapseImportar" aria-expanded="false" aria-controls="collapseImportar">
        <i class="fa-solid fa-plus"></i> Nova Importação
    </button>
    <div class="collapse" id="collapseImportar">
        <div class="card card-body" style="max-width: 850px;">
            <h2>Importar Faturas</h2>
            <form id="importForm" class="mt-4" enctype="multipart/form-data">
                <div class="mb-3">
                    <label for="csvFile" class="form-label">Escolha o arquivo CSV:</label>
                    <input class="form-control" type="file" name="csvFile" id="csvFile" accept=".csv" style="width: 800px;">
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-auto">
                        <label for="taxaDolar" class="form-label">Taxa do Dólar</label>
                        <input type="text" class="form-control" style="width: 150px;" name="taxaDolar" id="taxaDolar" placeholder="Ex: 5.8700" required>
                    </div>
                    <div class="col-auto">
                        <label for="notaFiscal" class="form-label">Nº da Nota Fiscal</label>
                        <input type="text" class="form-control" style="width: 150px;" name="notaFiscal" id="notaFiscal" placeholder="Ex: 12345" required>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">Importar</button>
            </form>
            <div id="result" class="mt-3"></div>
        </div>
    </div>
</div>

    <div class="table-responsive shadow-sm rounded mb-4" id="main-table-container">
        <table class="table table-hover table-bordered align-middle mb-0" id="tabela-faturas-unicas" style="max-width: 700px; text-align: center;">
            <thead class="table-dark">
                <tr id="th-thead-faturas">
                    <th class="sortable" style="width: 120px;">Fatura</th>
                    <th class="menor sortable">Nota Fiscal</th>
                    <th class="menor">Taxa Dólar</th>
                    <th class="menor">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $faturasUnicas = [];

                foreach ($faturas as $fatura) {
                    $nome = $fatura['nomeFatura'];

                    if (!isset($faturasUnicas[$nome])) {
                        $faturasUnicas[$nome] = $fatura;
                    }
                }

                sort($faturasUnicas);

                foreach (array_reverse($faturasUnicas) as $fatura): ?>
                    <tr class=" linha-fatura-unica">
                        <td class="clicavel"><?= htmlspecialchars($fatura['nomeFatura']) ?></td>
                        <td><?= htmlspecialchars($fatura['notaFiscal'] ?? '') ?></td>
                        <td><?= htmlspecialchars($fatura['txDolar'] ?? '') ?></td>
                        <td class="action-buttons text-center">
                            <button class="btn btn-danger btn-sm delete-btn"
                                data-id="<?= htmlspecialchars($fatura['nomeFatura']) ?>">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- ============ MODAL ============ -->
    <div class="modal fade" id="modalProdutosFatura" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header align-items-center">
                    <img src="logo-black.png" class="print-logo" style="display: none; width: 150px; margin-right: 20px;" alt="Logo" />
                    <h5 class="modal-title">Produtos da fatura: <span id="modalNomeFatura"></span></h5>
                    <button class="btn btn-outline-secondary" id="btn-print" title="Imprimir" style="margin-left: auto;">
                        🖨️ Imprimir
                    </button>

                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <table class="table table-hover table-bordered align-middle text-center mb-0" id="show-print">
                        <thead class="table-dark">
                            <tr>
                                <th style="width: 120px;" class="sortable">Código</th>
                                <th class="sortable">Descrição</th>
                                <th>UN</th>
                                <th>QTD</th>
                                <th>Preço</th>
                            </tr>
                        </thead>
                        <tbody id="modalProdutosBody">
                            <!-- preenchido via JS -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    th.menor {
        width: 120px;
    }

    #tabela-faturas-unicas {
        width: 600px;
    }

    .table th {
        padding: 0.3rem;
        vertical-align: middle;
        text-align: center;
    }

    th.sortable,
    th.clicavel {
        cursor: pointer;
        user-select: none;
    }

    @media print {

        /* Esconde o sidebar, navbar e os elementos da página de fundo */
        #sidebar,
        nav.navbar,
        #loading,
        #main-table-container,
        #btn-nova-importacao,
        #collapseImportar {
            display: none !important;
        }

        /* Cadeia de ancestrais do modal: tudo precisa ser visível e sem overflow */
        body,
        .content,
        #modulo-content {
            display: block !important;
            visibility: visible !important;
            position: static !important;
            overflow: visible !important;
            height: auto !important;
            width: 100% !important;
            margin: 0 !important;
            padding: 0 !important;
        }

        /* Modal visível e no fluxo normal */
        #modalProdutosFatura {
            display: block !important;
            position: static !important;
            overflow: visible !important;
            opacity: 1 !important;
        }

        .modal-backdrop {
            display: none !important;
        }

        .modal-dialog {
            max-width: 100% !important;
            margin: 0 !important;
            padding: 0 !important;
        }

        .modal-content {
            border: none !important;
            box-shadow: none !important;
        }

        /* Esconde apenas os botões do modal na impressão */
        .modal-header .btn {
            display: none !important;
        }

        .modal-header {
            border-bottom: none !important;
            padding-bottom: 0 !important;
        }

        .print-logo {
            display: block !important;
        }

        /* Tabela flui naturalmente entre páginas */
        #show-print {
            overflow: visible !important;
        }

        #show-print table {
            width: 100% !important;
            page-break-inside: auto !important;
        }

        #show-print tr {
            page-break-inside: avoid !important;
            page-break-after: auto !important;
        }

        #show-print thead {
            display: table-header-group !important;
        }
    }
</style>

<script>
    window.listagemFaturas = <?= json_encode($faturas) ?>;
</script>

<script src="js/faturas/faturas.js"></script>