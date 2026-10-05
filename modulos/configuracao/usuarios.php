<div class="container mt-4">
    <h2 class="mb-4">Cadastro de Usuário</h2>
    <form id="cadastroUsuario">
        <div class="row">
            <!-- Coluna 1 -->
            <div class="col-md-6">
                <div class="mb-3">
                    <label for="nome" class="form-label">Nome Completo</label>
                    <input type="text" class="form-control" id="nome" name="nome" required>
                </div>
                <div class="mb-3">
                    <label for="email" class="form-label">E-mail</label>
                    <input type="email" class="form-control" id="email" name="email" required>
                </div>
                <div class="mb-3">
                    <label for="senha" class="form-label">Senha</label>
                    <input type="password" class="form-control" id="senha" name="senha" required>
                    <div class="invalid-feedback">A senha deve ter no mínimo 8 caracteres, incluindo um número e um caractere especial.</div>
                </div>
                <div class="mb-3">
                    <label for="confirmarSenha" class="form-label">Confirmar Senha</label>
                    <input type="password" class="form-control" id="confirmarSenha" name="confirmarSenha" required>
                    <div class="invalid-feedback">As senhas não coincidem.</div>
                    </div>
                </div>
            <!-- Coluna 2 -->
            <div class="col-md-6">
                <div class="mb-3">
                    <label for="role" class="form-label">Tipo de Usuário</label>
                    <select class="form-select" id="role" name="role" required>
                        <option value="" disabled selected>Selecione um tipo de usuário</option>
                        <option value="admin">Admin</option>
                        <option value="user">User</option>
                    </select>
                </div>
                <div id="modulosContainer" class="mb-3 d-none">
                    <label class="form-label">Permissões de Módulos</label>
                    <div id="listaModulos"></div>
                </div>
            </div>
        </div>
        <button type="submit" class="btn btn-primary">Cadastrar</button>
        <div id="mensagem" class="mt-3"></div>
    </form>
</div>

<!-- LISTAGEM -->

<?php
$arquivo = __DIR__ . "/action/users.json";
$usuarios = [];

if (file_exists($arquivo)) {
    $json = file_get_contents($arquivo);
    $usuarios = json_decode($json, true) ?? [];

$teveExclusao = false;

$usuarios = array_filter($usuarios, function ($user) use (&$teveExclusao) {
    if(isset($user['status']) && $user['status'] === 'quarentena') {
        if (time() > $user['dataExclusao']) {
            $teveExclusao = true;
        }
        return false;
    }
    return true;
});

    if ($teveExclusao) {
        $usuarios = array_values($usuarios); // Reorganiza os índices
        file_put_contents($arquivo, json_encode($usuarios, JSON_PRETTY_PRINT));
    }
}
?>

<div class="container mt-4">
    <h2 class="mb-4">Lista de Usuários</h2>
    <table class="table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Status</th>
                <th>Nome</th>
                <th>E-mail</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($usuarios)): ?>
                <?php foreach ($usuarios as $user): ?>
                    <tr data-id="<?= htmlspecialchars($user['id']) ?>">
                        <td><?= htmlspecialchars($user['id']) ?></td>
                        <td>
                            <?php if ($user['status'] === 'quarentena'): ?>
                                <span class="badge bg-warning">Em Quarentena</span>
                            <?php else: ?>
                                <span class="badge bg-success">Ativo</span>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($user['nome']) ?></td>
                        <td><?= htmlspecialchars($user['email']) ?></td>
                        <td>
                            <button class="btn btn-warning btn-sm edit-btn" 
                                    data-user='<?= json_encode($user, JSON_HEX_APOS) ?>'>
                                <i class="fa-solid fa-pencil"></i></button>
                            </button>
                            <button class="btn btn-danger btn-sm delete-btn"><i class="fa-solid fa-trash"></i></button></button>
                        </td>
                    </tr>
                    <tr class="collapse-row" id="collapse-<?= htmlspecialchars($user['id']) ?>" style="display: none;">
                        <td colspan="4">
                        <form class="edit-form">
                            <input type="hidden" name="id" value="<?= htmlspecialchars($user['id']) ?>">
                            <div class="mb-3">
                                <label>Nome</label>
                                <input type="text" name="nome" class="form-control edit-name" required>
                            </div>
                            <div class="mb-3">
                                <label>E-mail</label>
                                <input type="email" name="email" class="form-control edit-email" required>
                            </div>
                            <div class="mb-3">
                                <label for="senha" class="form-label">Senha</label>
                                <input type="password" class="form-control" id="senhaEditar" name="senha">
                                <div class="invalid-feedback">A senha deve ter no mínimo 8 caracteres, incluindo um número e um caractere especial.</div>
                            </div>
                            <div class="mb-3">
                                <label for="confirmarSenha" class="form-label">Confirmar Senha</label>
                                <input type="password" class="form-control" id="confirmarSenhaEditar" name="confirmarSenha">
                                <div class="invalid-feedback">As senhas não coincidem.</div>
                            </div>

                            <div class="mb-3">
                                <label>Role</label>
                                <select name="role" class="form-control edit-role" required>
                                    <option value="" disabled>Selecione um papel</option>
                                    <option value="admin">Admin</option>
                                    <option value="user">User</option>
                                </select>
                            </div>
                            <div class="mb-3 modules-container">
                                <label>Módulos</label>
                                <div class="edit-modules"></div>
                            </div>
                            <button type="submit" class="btn btn-primary">Salvar Alterações</button>
                        </form>

                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="4" class="text-center">Nenhum usuário cadastrado.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<script src="js/configuracao/usuarios.js"></script>
