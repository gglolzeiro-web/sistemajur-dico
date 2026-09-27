<?php
require_once __DIR__ . '/../../includes/auth.php';
exigirLogin();

$pdo = getConexao();

$totalPendentes = (int) $pdo->query(
    "SELECT COUNT(*) FROM prazos WHERE status = 'pendente'"
)->fetchColumn();

$totalContatosAbertos = (int) $pdo->query(
    "SELECT COUNT(*) FROM contatos WHERE status IN ('novo','enviado')"
)->fetchColumn();

$totalProcessosAtivos = (int) $pdo->query(
    "SELECT COUNT(*) FROM processos WHERE situacao = 'ativo'"
)->fetchColumn();

$proximosPrazos = $pdo->query(
    "SELECT p.id, p.titulo, p.data_limite, p.status, tp.nome AS tipo_nome, tp.cor AS tipo_cor,
            pr.numero_cnj, c.nome AS cliente_nome
     FROM prazos p
     LEFT JOIN tipos_prazo tp ON tp.id = p.tipo_prazo_id
     LEFT JOIN processos pr ON pr.id = p.processo_id
     LEFT JOIN clientes c ON c.id = pr.cliente_id
     WHERE p.status IN ('pendente','confirmado')
     ORDER BY p.data_limite ASC
     LIMIT 8"
)->fetchAll();

$tituloPagina = 'Painel';
$paginaAtiva = 'painel';
require __DIR__ . '/../../includes/cabecalho.php';
?>

<div class="pagina-cabecalho">
    <h1>Painel</h1>
    <p>Visão geral dos prazos e da carteira do escritório.</p>
</div>

<div class="grade-cards">
    <div class="card card-metrica">
        <div class="numero"><?= $totalPendentes ?></div>
        <div class="rotulo">Prazos pendentes</div>
    </div>
    <div class="card card-metrica">
        <div class="numero"><?= $totalContatosAbertos ?></div>
        <div class="rotulo">Contatos em captação</div>
    </div>
    <div class="card card-metrica">
        <div class="numero"><?= $totalProcessosAtivos ?></div>
        <div class="rotulo">Processos ativos</div>
    </div>
</div>

<div class="card">
    <h3>Próximos prazos</h3>
    <?php if (!$proximosPrazos): ?>
        <p style="color: var(--texto-suave); margin-top: 10px;">Nenhum prazo pendente no momento.</p>
    <?php else: ?>
        <div class="lista-itens" style="margin-top: 14px;">
            <?php foreach ($proximosPrazos as $prazo): ?>
                <div class="item-linha" style="border-left-color: <?= htmlspecialchars($prazo['tipo_cor'] ?? '#4C7A52') ?>;">
                    <div>
                        <div class="item-titulo"><?= htmlspecialchars($prazo['titulo']) ?></div>
                        <div class="item-sub">
                            <?= htmlspecialchars($prazo['tipo_nome'] ?? 'Sem tipo') ?>
                            <?php if ($prazo['cliente_nome']): ?>
                                &middot; <?= htmlspecialchars($prazo['cliente_nome']) ?>
                            <?php endif; ?>
                            <?php if ($prazo['numero_cnj']): ?>
                                &middot; <?= htmlspecialchars($prazo['numero_cnj']) ?>
                            <?php else: ?>
                                &middot; sem processo vinculado
                            <?php endif; ?>
                        </div>
                    </div>
                    <span class="badge badge-<?= htmlspecialchars($prazo['status']) ?>">
                        <?= date('d/m/Y', strtotime($prazo['data_limite'])) ?>
                    </span>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../../includes/rodape.php'; ?>
