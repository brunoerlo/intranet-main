<div class="container-fluid mt-4">
    <div class="card card-body" style="max-width: 850px;">
        <h2>Importar Orçamento</h2>
        <form id="importForm" class="mt-4" enctype="multipart/form-data">
            <div class="mb-3">
                <label for="pdfFile" class="form-label">Escolha o arquivo PDF:</label>
                <input class="form-control" type="file" name="pdfFile" id="pdfFile" accept=".pdf" style="width: 800px;" required>
            </div>
            <button type="submit" class="btn btn-primary" id="btnSubmit">Processar</button>
        </form>
        <div id="result" class="mt-4"></div>
    </div>

    <!-- ============ MODAL ============ -->
    <div class="modal fade" id="modalProdutosOrcamento" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header align-items-center">
                    <img src="logo-black.png" class="print-logo" style="display: none; width: 150px; margin-right: 20px;" alt="Logo" />
                    <h5 class="modal-title">Saldos do dia: <span id="modalNomeOrcamento"></span></h5>

                    <div class="d-flex align-items-center gap-2 ms-auto">
                        <button class="btn btn-success" id="btnExportarCsv" title="Exportar CSV">
                            <i class="fa-solid fa-file-csv"></i> Exportar
                        </button>
                        <button class="btn btn-outline-secondary" id="btn-print" title="Imprimir">
                            🖨️ Imprimir
                        </button>
                        <button type="button" class="btn-close m-0" data-bs-dismiss="modal" aria-label="Fechar"></button>
                    </div>
                </div>
                <div class="modal-body">
                    <table class="table table-hover table-bordered align-middle mb-0" id="show-print">
                        <thead class="table-dark text-center text-nowrap">
                            <tr>
                                <th style="width: 95px;" class="sortable col-codigo">Código</th>
                                <th class="sortable col-descricao text-start">Descrição</th>
                                <th style="width: 45px;" class="col-un">UN</th>
                                <th style="width: 55px;" class="col-qtd">QTD</th>
                                <th style="width: 105px;" class="col-unitario text-end">Preço Unit.</th>
                                <th style="width: 110px;" class="col-total text-end">Preço Total</th>
                            </tr>
                        </thead>
                        <tbody id="modalProdutosBody">
                            <!-- preenchido via JS -->
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <th colspan="5" class="text-end fw-normal text-muted">Preço Total:</th>
                                <th class="text-end fw-normal col-total" id="modalTotalOrcamento">R$ 0,00</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    #show-print {
        font-size: 0.875rem;
    }

    #show-print th,
    #show-print td {
        padding: 0.45rem 0.65rem;
        vertical-align: middle;
    }

    #show-print thead th {
        text-align: center;
        white-space: nowrap;
    }

    #show-print .col-codigo,
    #show-print .col-un,
    #show-print .col-qtd,
    #show-print .col-unitario,
    #show-print .col-total {
        white-space: nowrap;
    }

    th.sortable {
        cursor: pointer;
        user-select: none;
    }

    @page {
        size: auto;
        margin: 0; /* Remove cabeçalho e rodapé do navegador (data, título da página, URL, paginação) */
    }

    @media print {

        /* Esconde o sidebar, navbar, botões e elementos de fundo */
        #sidebar,
        nav.navbar,
        #loading,
        .card,
        #importForm,
        #result,
        .modal-backdrop,
        .modal-header .btn,
        .modal-header .btn-close {
            display: none !important;
        }

        /* Reseta html e body para evitar página fantasma e espaçar o conteúdo do papel */
        html,
        body {
            height: auto !important;
            min-height: 0 !important;
            max-height: none !important;
            margin: 0 !important;
            padding: 8mm 5mm !important;
            overflow: visible !important;
            background: #ffffff !important;
        }

        /* Ancestrais sem margens e paddings residuais */
        .container-fluid,
        .content,
        #modulo-content {
            display: block !important;
            visibility: visible !important;
            position: static !important;
            overflow: visible !important;
            height: auto !important;
            min-height: 0 !important;
            width: 100% !important;
            margin: 0 !important;
            padding: 0 !important;
        }

        /* Modal no fluxo normal */
        #modalProdutosOrcamento {
            display: block !important;
            position: static !important;
            overflow: visible !important;
            opacity: 1 !important;
            height: auto !important;
            min-height: 0 !important;
            margin: 0 !important;
            padding: 0 !important;
        }

        .modal-dialog,
        .modal-content {
            max-width: 100% !important;
            width: 100% !important;
            margin: 0 !important;
            padding: 0 !important;
            border: none !important;
            box-shadow: none !important;
            height: auto !important;
            min-height: 0 !important;
        }

        .modal-header {
            display: flex !important;
            align-items: center !important;
            border-bottom: 1px solid #dee2e6 !important;
            padding: 0 0 8px 0 !important;
            margin-bottom: 12px !important;
        }

        .modal-body {
            padding: 0 !important;
            margin: 0 !important;
            overflow: visible !important;
        }

        .print-logo {
            display: block !important;
        }

        /* Tabela exatamente nos mesmos moldes do modal, sem quebras de linha */
        #show-print {
            overflow: visible !important;
            width: 100% !important;
            font-size: 0.8125rem !important;
            border-collapse: collapse !important;
            border: 1px solid #dee2e6 !important;
            margin-bottom: 0 !important;
            page-break-after: avoid !important;
            break-after: avoid !important;
        }

        #show-print th,
        #show-print td {
            padding: 0.35rem 0.5rem !important;
            vertical-align: middle !important;
            border: 1px solid #dee2e6 !important;
            white-space: nowrap !important;
        }

        #show-print thead {
            display: table-header-group !important;
        }

        #show-print thead th {
            background-color: #212529 !important;
            color: #ffffff !important;
            text-align: center !important;
            white-space: nowrap !important;
            border: 1px solid #373b3e !important;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        #show-print tfoot th {
            background-color: #f8f9fa !important;
            white-space: nowrap !important;
            border: 1px solid #dee2e6 !important;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        #show-print tr {
            page-break-inside: avoid !important;
            break-inside: avoid !important;
        }
    }
</style>

<script src="js/faturas/orcamento.js"></script>