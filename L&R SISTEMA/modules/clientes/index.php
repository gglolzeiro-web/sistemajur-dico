<?php
require_once __DIR__ . '/../../includes/auth.php';
exigirLogin();

$pdo = getConexao();
$busca = trim($_GET['busca'] ?? '');

if ($busca !== '') {
    $stmt = $pdo->prepare(
        "SELECT * FROM clientes WHERE nome LIKE ? OR cpf_cnpj LIKE ? ORDER BY nome ASC"
    );
    $termo = '%' . $busca . '%';
    $stmt->execute([$termo, $termo]);
} else {
    $stmt = $pdo->query('SELECT * FROM clientes ORDER BY nome ASC');
}
$clientes = $stmt->fetchAll();

$tituloPagina = 'Clientes';
$paginaAtiva = 'clientes';
require __DIR__ . '/../../includes/cabecalho.php';
?>

<div class="pagina-cabecalho">
    <h1>Clientes</h1>
    <p>Carteira ativa do escritório.</p>
</div>

<div class="barra-acoes">
    <form method="get" style="display:flex; gap:8px;">
        <input type="text" name="busca" placeholder="Buscar por nome ou CPF/CNPJ" value="<?= htmlspecialchars($busca) ?>">
        <button type="submit" class="botao botao-secundario">Buscar</button>
    </form>
    <a href="form.php" class="botao botao-primario"><?= icone('plus') ?> Novo cliente</a>
</div>

<div class="card">
    <table>
        <thead>
            <tr>
                <th>Nome</th>
                <th>CPF/CNPJ</th>
                <th>Telefone</th>
                <th>E-mail</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php if (!$clientes): ?>
                <tr><td colspan="5" style="color: var(--texto-suave);">Nenhum cliente cadastrado.</td></tr>
            <?php endif; ?>
            <?php foreach ($clientes as $c): ?>
                <tr>
                    <td><?= htmlspecialchars($c['nome']) ?></td>
                    <td><?= htmlspecialchars($c['cpf_cnpj'] ?? '—') ?></td>
                    <td><?= htmlspecialchars($c['telefone'] ?? '—') ?></td>
                    <td><?= htmlspecialchars($c['email'] ?? '—') ?></td>
                    <td><a href="form.php?id=<?= $c['id'] ?>" class="botao botao-secundario"><?= icone('edit') ?> Editar</a></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require __DIR__ . '/../../includes/rodape.php'; ?>
