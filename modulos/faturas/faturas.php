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
            <tr>
                <th class="sortable">Fatura</th>
                <th class="menor">Nota Fiscal</th>
                <th class="menor">Taxa Dólar</th>
                <th class="menor">Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($noRepeat as $nome):
                $index = array_search($nome, array_column($faturas, 'nomeFatura'));
                $nf        = $faturas[$index]["notaFiscal"] ?? '';
                $taxaDolar = $faturas[$index]["txDolar"]    ?? '';
            ?>
                <tr class="table-warning linha-fatura-unica">
                    <td class="clicavel" style="cursor:pointer;"><?= htmlspecialchars($nome) ?></td>
                    <td><?= htmlspecialchars($nf) ?></td>
                    <td><?= htmlspecialchars($taxaDolar) ?></td>
                    <td class="action-buttons text-center">
                        <button class="btn btn-danger btn-sm delete-btn" data-id="<?= htmlspecialchars($nome) ?>">
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
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <table class="table table-bordered table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>Código</th>
                            <th>Descrição</th>
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
</style>

<script>
    // Todos os itens de faturas já vêm do PHP, prontos pra filtrar no JS
    const todosOsItensFaturas = <?= json_encode($faturas) ?>;

    document.querySelectorAll('.linha-fatura-unica').forEach(function(linhaFatura) {
        linhaFatura.addEventListener('click', function() {
            const nomeClicado = this.querySelector('.clicavel').textContent.trim();

            // Filtra só os itens que pertencem a essa fatura
            const itensDaFatura = todosOsItensFaturas.filter(function(item) {
                return item.nomeFatura === nomeClicado;
            });

            // Monta as linhas do modal
            const tbody = document.getElementById('modalProdutosBody');
            tbody.innerHTML = '';

            if (itensDaFatura.length === 0) {
                tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted">Nenhum produto encontrado.</td></tr>';
            } else {
                itensDaFatura.forEach(function(item) {
                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td>${item.codigo ?? ''}</td>
                        <td>${item.descricao ?? ''}</td>
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

    // Ordenação por nome de fatura
    if (typeof ordemCrescente === 'undefined') {
        var ordemCrescente = true;
    }

    document.querySelector('.sortable').addEventListener('click', () => {
        const tbody = document.querySelector('#tabela-faturas-unicas tbody');
        const rows = Array.from(tbody.querySelectorAll('tr'));

        rows.sort((a, b) => {
            const nomeA = a.children[0].textContent.trim().toLowerCase();
            const nomeB = b.children[0].textContent.trim().toLowerCase();
            return ordemCrescente ? nomeA.localeCompare(nomeB) : nomeB.localeCompare(nomeA);
        });

        tbody.innerHTML = '';
        rows.forEach(row => tbody.appendChild(row));

        ordemCrescente = !ordemCrescente;
    });

    //Deletar
    document.querySelectorAll('.delete-btn').forEach(button => {
        button.addEventListener('click', function() {
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
                            location.reload();
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>