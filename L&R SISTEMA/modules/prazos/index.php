<?php
require_once __DIR__ . '/../../includes/auth.php';
exigirLogin();

$pdo = getConexao();
$filtroStatus = $_GET['status'] ?? '';

$sql = "SELECT p.*, tp.nome AS tipo_nome, tp.cor AS tipo_cor, pr.numero_cnj, c.nome AS cliente_nome, u.nome AS responsavel_nome
        FROM prazos p
        LEFT JOIN tipos_prazo tp ON tp.id = p.tipo_prazo_id
        LEFT JOIN processos pr ON pr.id = p.processo_id
        LEFT JOIN clientes c ON c.id = pr.cliente_id
        LEFT JOIN usuarios u ON u.id = p.responsavel_id";
$parametros = [];
if ($filtroStatus !== '') {
    $sql .= ' WHERE p.status = ?';
    $parametros[] = $filtroStatus;
}
$sql .= ' ORDER BY p.data_limite ASC';

$stmt = $pdo->prepare($sql);
$stmt->execute($parametros);
$prazos = $stmt->fetchAll();

$tituloPagina = 'Prazos';
$paginaAtiva = 'prazos';
require __DIR__ . '/../../includes/cabecalho.php';
?>

<div class="pagina-cabecalho">
    <h1>Prazos</h1>
    <p>Agenda de prazos processuais e compromissos.</p>
</div>

<div class="barra-acoes">
    <form method="get" style="display:flex; gap:8px;">
        <select name="status" onchange="this.form.submit()">
            <option value="">Todos os status</option>
            <?php foreach (['pendente' => 'Pendente', 'confirmado' => 'Confirmado', 'cumprido' => 'Cumprido', 'perdido' => 'Perdido', 'cancelado' => 'Cancelado'] as $valor => $rotulo): ?>
                <option value="<?= $valor ?>" <?= $filtroStatus === $valor ? 'selected' : '' ?>><?= $rotulo ?></option>
            <?php endforeach; ?>
        </select>
    </form>
    <a href="form.php" class="botao botao-primario"><?= icone('plus') ?> Novo prazo</a>
</div>

<div class="card">
    <table>
        <thead>
            <tr><th>Prazo</th><th>Tipo</th><th>Processo</th><th>Data limite</th><th>Responsável</th><th>Status</th><th></th></tr>
        </thead>
        <tbody>
            <?php if (!$prazos): ?>
                <tr><td colspan="7" style="color: var(--texto-suave);">Nenhum prazo encontrado.</td></tr>
            <?php endif; ?>
            <?php foreach ($prazos as $p): ?>
                <tr>
                    <td><?= htmlspecialchars($p['titulo']) ?></td>
                    <td><?= htmlspecialchars($p['tipo_nome'] ?? '—') ?></td>
                    <td><?= htmlspecialchars($p['numero_cnj'] ?? ($p['cliente_nome'] ?? '—')) ?></td>
                    <td><?= date('d/m/Y', strtotime($p['data_limite'])) ?></td>
                    <td><?= htmlspecialchars($p['responsavel_nome'] ?? '—') ?></td>
                    <td><span class="badge badge-<?= htmlspecialchars($p['status']) ?>"><?= htmlspecialchars(ucfirst($p['status'])) ?></span></td>
                    <td><a href="form.php?id=<?= $p['id'] ?>" class="botao botao-secundario"><?= icone('edit') ?> Editar</a></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require __DIR__ . '/../../includes/rodape.php'; ?>
