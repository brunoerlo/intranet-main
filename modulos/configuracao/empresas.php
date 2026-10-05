<h2>Cadastro de Empresas</h2>

<!-- Formulário de cadastro -->
<form id="form-empresa" class="mb-4" method="post" action="#">
    <input type="hidden" name="id" id="empresa-id">

    <div class="row">
        <div class="col-md-6">
            <div class="mb-3">
                <label class="form-label">Razão Social</label>
                <input type="text" name="razaoSocial" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">CNPJ</label>
                <input type="text" name="cnpj" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Telefone</label>
                <input type="text" name="telefone" class="form-control">
            </div>
        </div>

        <div class="col-md-6">
            <div class="mb-3">
                <label class="form-label">Endereço</label>
                <input type="text" name="endereco" class="form-control">
            </div>
            <div class="mb-3">
                <label class="form-label">E-mail</label>
                <input type="email" name="email" class="form-control">
            </div>
            <div class="mb-3">
                <label class="form-label">Moeda</label>
                <select name="moeda" class="form-select" required>
                    <option value="R$">R$ (Real)</option>
                    <option value="US$">US$ (Dólar)</option>
                </select>
            </div>
        </div>
    </div>

    <button type="submit" class="btn btn-primary">Salvar</button>
    <button type="button" class="btn btn-secondary" id="cancelar-edicao" style="display: none;">Cancelar</button>
</form>


<!-- Tabela de listagem -->
<div class="table-responsive shadow-sm rounded mb-3">
        <table class="table table-hover table-bordered align-middle mb-0 text-center" id="tabela-empresas">
            <thead class="table-dark">
        <tr>
            <th>Razão Social</th>
            <th>CNPJ</th>
            <th>Endereço</th>
            <th>Telefone</th>
            <th>E-mail</th>
            <th>Moeda</th>
            <th style="width: 100px">Ações</th>
        </tr>
    </thead>
    <tbody></tbody>
</table>

<script src="js/configuracao/empresas.js"></script>