<?php
require_once __DIR__ . '/../../includes/auth.php';
exigirCargo(['admin']);

$pdo = getConexao();
$usuarios = $pdo->query('SELECT * FROM usuarios ORDER BY nome')->fetchAll();

$tituloPagina = 'Usuários';
$paginaAtiva = 'usuarios';
require __DIR__ . '/../../includes/cabecalho.php';
?>

<div class="pagina-cabecalho">
    <h1>Usuários</h1>
    <p>Equipe interna com acesso ao sistema.</p>
</div>

<div class="barra-acoes">
    <div></div>
    <a href="form.php" class="botao botao-primario"><?= icone('plus') ?> Novo usuário</a>
</div>

<div class="card">
    <table>
        <thead>
            <tr><th>Nome</th><th>E-mail</th><th>Cargo</th><th>OAB</th><th>Status</th><th></th></tr>
        </thead>
        <tbody>
            <?php foreach ($usuarios as $u): ?>
                <tr>
                    <td><?= htmlspecialchars($u['nome']) ?></td>
                    <td><?= htmlspecialchars($u['email']) ?></td>
                    <td><?= htmlspecialchars(ucfirst($u['cargo'])) ?></td>
                    <td><?= htmlspecialchars($u['oab'] ?? '—') ?></td>
                    <td><span class="badge <?= $u['ativo'] ? 'badge-ativo' : 'badge-recusado' ?>"><?= $u['ativo'] ? 'Ativo' : 'Inativo' ?></span></td>
                    <td><a href="form.php?id=<?= $u['id'] ?>" class="botao botao-secundario"><?= icone('edit') ?> Editar</a></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require __DIR__ . '/../../includes/rodape.php'; ?>
