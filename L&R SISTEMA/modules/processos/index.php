<?php
require_once __DIR__ . '/../../includes/auth.php';
exigirLogin();

$pdo = getConexao();
$busca = trim($_GET['busca'] ?? '');

$sql = "SELECT p.*, c.nome AS cliente_nome, u.nome AS advogado_nome
        FROM processos p
        JOIN clientes c ON c.id = p.cliente_id
        LEFT JOIN usuarios u ON u.id = p.advogado_responsavel_id";
$parametros = [];

if ($busca !== '') {
    $sql .= " WHERE p.numero_cnj LIKE ? OR c.nome LIKE ?";
    $termo = '%' . $busca . '%';
    $parametros = [$termo, $termo];
}
$sql .= ' ORDER BY p.criado_em DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute($parametros);
$processos = $stmt->fetchAll();

$tituloPagina = 'Processos';
$paginaAtiva = 'processos';
require __DIR__ . '/../../includes/cabecalho.php';
?>

<div class="pagina-cabecalho">
    <h1>Processos</h1>
    <p>Processos ativos e arquivados do escritório.</p>
</div>

<div class="barra-acoes">
    <form method="get" style="display:flex; gap:8px;">
        <input type="text" name="busca" placeholder="Buscar por número CNJ ou cliente" value="<?= htmlspecialchars($busca) ?>">
        <button type="submit" class="botao botao-secundario">Buscar</button>
    </form>
    <a href="form.php" class="botao botao-primario"><?= icone('plus') ?> Novo processo</a>
</div>

<div class="card">
    <table>
        <thead>
            <tr>
                <th>Número CNJ</th>
                <th>Cliente</th>
                <th>Tipo de ação</th>
                <th>Advogado</th>
                <th>Situação</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php if (!$processos): ?>
                <tr><td colspan="6" style="color: var(--texto-suave);">Nenhum processo cadastrado.</td></tr>
            <?php endif; ?>
            <?php foreach ($processos as $p): ?>
                <tr>
                    <td><?= htmlspecialchars($p['numero_cnj'] ?? '—') ?></td>
                    <td><?= htmlspecialchars($p['cliente_nome']) ?></td>
                    <td><?= htmlspecialchars($p['tipo_acao'] ?? '—') ?></td>
                    <td><?= htmlspecialchars($p['advogado_nome'] ?? '—') ?></td>
                    <td><span class="badge badge-<?= htmlspecialchars($p['situacao']) ?>"><?= htmlspecialchars(ucfirst($p['situacao'])) ?></span></td>
                    <td><a href="form.php?id=<?= $p['id'] ?>" class="botao botao-secundario"><?= icone('edit') ?> Editar</a></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require __DIR__ . '/../../includes/rodape.php'; ?>
