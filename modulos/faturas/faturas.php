<?php
$faturasJson = file_get_contents(__DIR__ . '/action/faturas.json');
$faturas = json_decode($faturasJson, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    echo 'Erro ao decodificar faturas.json: ' . json_last_error_msg();
    $faturas = [];
}

$nomes = array_column($faturas, 'nomeFatura');
$noRepeat = array_unique($nomes);
?>

<div class="container mt-4">
    <table class="table table-bordered table-hover mt-3" id="tabela-faturas-unicas">
        <thead class="table-dark">
            <tr id="th-thead">
                <th class="sortable">Fatura</th>
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

            foreach ($faturasUnicas as $fatura): ?>
                <tr class="table-warning linha-fatura-unica">
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
            <div class="modal-header">
                <h5 class="modal-title">Produtos da fatura: <span id="modalNomeFatura"></span></h5>
                <button class="btn btn-outline-secondary" id="btn-print" title="Imprimir" style="margin-left: auto;">
                    🖨️ Imprimir
                </button>

                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <table class="table table-bordered table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th style="width: 120px;" class="sortable">Código</th>
                            <th class="sortable">Descrição</th>
                            <th>UN</th>
                            <th>Quantidade</th>
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
        body * {
            visibility: hidden;
        }

        #image,
        #image * {
            visibility: visible;
        }

        .modal-body,
        .modal-body * {
            visibility: visible;
        }

        #image {
            position: absolute;
            left: 0;
            top: 0;
        }

        .modal-body {
            position: absolute;
            left: 0;
            top: 50px;
        }
    }
</style>

<script>
    // Todos os itens de faturas já vêm do PHP, prontos pra filtrar no JS
    const todosOsItensFaturas = <?= json_encode($faturas) ?>;
    
    document.querySelectorAll('.linha-fatura-unica').forEach(function(linhaFatura) {
        linhaFatura.addEventListener('click', function() {
            const nomeClicado = this.querySelector('.clicavel').textContent.trim();

            const faturasAgrupadas = {};

            todosOsItensFaturas.forEach(item => {
                if (!faturasAgrupadas[item.nomeFatura]) {
                    faturasAgrupadas[item.nomeFatura] = [];
                }

                faturasAgrupadas[item.nomeFatura].push(item);
            });

            const itensDaFatura = faturasAgrupadas[nomeClicado] || [];

            // Monta as linhas do modal
            const tbody = document.getElementById('modalProdutosBody');
            tbody.innerHTML = '';

            if (itensDaFatura.length === 0) {
                tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted">Nenhum produto encontrado.</td></tr>';
            } else {
                itensDaFatura.sort((a, b) => {
                    return a.descricao.localeCompare(b.descricao, 'pt-BR', {
                        sensitivity: 'base'
                    });
                })
                itensDaFatura.forEach(function(item) {
                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td class="sortable">${item.codigo ?? ''}</td>
                        <td class="sortable">${item.descricao ?? ''}</td>
                        <td>${item.unidadeMedida ?? ''}</td>
                        <td>${item.quantidade ?? ''}</td>
                        <td>${item.preco ?? ''}</td>
                    `;
                    tbody.appendChild(tr);
                });
            }

            document.getElementById('modalNomeFatura').textContent = nomeClicado;

            // Abre o modal (Bootstrap)
            const modal = new bootstrap.Modal(document.getElementById('modalProdutosFatura'));
            modal.show();
        });
    });

    //Deletar
    document.querySelectorAll('.delete-btn').forEach(button => {
        button.addEventListener('click', function(e){
            e.stopPropagation();
            const faturaId = this.getAttribute('data-id');

            if (confirm("Tem certeza que deseja excluir esta fatura?")) {
                fetch('./modulos/faturas/action/delete.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            id: faturaId
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert('Fatura excluída com sucesso!');
                            linha.remove();
                        } else {
                            alert('Erro ao excluir a fatura: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Erro:', error);
                        alert('Ocorreu um erro ao tentar excluir a fatura.');
                    });
            }
        });
    });

</script>

<script>
    const printBtn = document.getElementById('btn-print');

    //impressao
    printBtn.addEventListener('click', function() {
        print();
    })

</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
