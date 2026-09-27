<?php
require_once __DIR__ . '/../../includes/auth.php';
$usuario = exigirLogin();

$pdo = getConexao();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfVerificar();
    $acao = $_POST['acao'] ?? '';

    if ($acao === 'cadastrar') {
        $numeroCnj = trim($_POST['numero_cnj'] ?? '');
        $fonte = trim($_POST['fonte'] ?? 'Manual');
        $dataPublicacao = $_POST['data_publicacao'] ?? date('Y-m-d');
        $conteudo = trim($_POST['conteudo'] ?? '');

        if ($conteudo !== '') {
            // Tenta casar automaticamente com um processo pelo número CNJ.
            $processoId = null;
            $status = 'novo';
            if ($numeroCnj !== '') {
                $stmt = $pdo->prepare('SELECT id FROM processos WHERE numero_cnj = ? LIMIT 1');
                $stmt->execute([$numeroCnj]);
                $processo = $stmt->fetch();
                if ($processo) {
                    $processoId = $processo['id'];
                    $status = 'vinculado';
                }
            }

            $stmt = $pdo->prepare(
                'INSERT INTO publicacoes (processo_id, numero_cnj, fonte, data_publicacao, conteudo, status) VALUES (?,?,?,?,?,?)'
            );
            $stmt->execute([$processoId, $numeroCnj ?: null, $fonte, $dataPublicacao, $conteudo, $status]);
        }
    } elseif ($acao === 'marcar_revisado') {
        $id = (int) ($_POST['id'] ?? 0);
        $stmt = $pdo->prepare("UPDATE publicacoes SET status = 'revisado', revisado_por = ?, revisado_em = NOW() WHERE id = ?");
        $stmt->execute([$usuario['id'], $id]);
    } elseif ($acao === 'ignorar') {
        $id = (int) ($_POST['id'] ?? 0);
        $pdo->prepare("UPDATE publicacoes SET status = 'ignorado' WHERE id = ?")->execute([$id]);
    }

    header('Location: index.php');
    exit;
}

$publicacoes = $pdo->query(
    "SELECT pub.*, pr.numero_cnj AS processo_cnj, c.nome AS cliente_nome
     FROM publicacoes pub
     LEFT JOIN processos pr ON pr.id = pub.processo_id
     LEFT JOIN clientes c ON c.id = pr.cliente_id
     ORDER BY pub.data_publicacao DESC, pub.id DESC"
)->fetchAll();

$tituloPagina = 'Publicações';
$paginaAtiva = 'publicacoes';
require __DIR__ . '/../../includes/cabecalho.php';
?>

<div class="pagina-cabecalho">
    <h1>Publicações</h1>
    <p>Publicações do Diário Oficial vinculadas aos processos.</p>
</div>

<div class="alerta alerta-info">
    A captura automática do Diário Oficial (via DJEN/CNJ ou serviço terceirizado) ainda não está conectada —
    isso exige liberar acesso de rede e credenciais de API, o que preciso configurar com você antes de ativar.
    Por enquanto, o cadastro abaixo é manual, mas o vínculo automático ao processo pelo número CNJ e o fluxo de
    revisão já funcionam normalmente.
</div>

<div class="card" style="margin-bottom: 24px;">
    <h3>Cadastrar publicação</h3>
    <form method="post" class="form-grade" style="margin-top: 12px;">
        <?= csrfCampo() ?>
        <input type="hidden" name="acao" value="cadastrar">
        <div class="campo">
            <label>Número CNJ do processo</label>
            <input type="text" name="numero_cnj" placeholder="0000000-00.0000.0.00.0000">
        </div>
        <div class="campo">
            <label>Fonte</label>
            <input type="text" name="fonte" placeholder="Ex: DJEN, TJSP" value="Manual">
        </div>
        <div class="campo">
            <label>Data da publicação</label>
            <input type="date" name="data_publicacao" value="<?= date('Y-m-d') ?>" required>
        </div>
        <div class="campo campo-largo">
            <label>Conteúdo da publicação</label>
            <textarea name="conteudo" required></textarea>
        </div>
        <div class="campo campo-largo">
            <button type="submit" class="botao botao-primario"><?= icone('plus') ?> Cadastrar</button>
        </div>
    </form>
</div>

<div class="card">
    <table>
        <thead>
            <tr><th>Data</th><th>Processo</th><th>Fonte</th><th>Conteúdo</th><th>Status</th><th></th></tr>
        </thead>
        <tbody>
            <?php if (!$publicacoes): ?>
                <tr><td colspan="6" style="color: var(--texto-suave);">Nenhuma publicação cadastrada.</td></tr>
            <?php endif; ?>
            <?php foreach ($publicacoes as $p): ?>
                <tr>
                    <td><?= date('d/m/Y', strtotime($p['data_publicacao'])) ?></td>
                    <td><?= htmlspecialchars($p['processo_cnj'] ?? $p['numero_cnj'] ?? '—') ?><?= $p['cliente_nome'] ? ' — ' . htmlspecialchars($p['cliente_nome']) : '' ?></td>
                    <td><?= htmlspecialchars($p['fonte'] ?? '—') ?></td>
                    <td style="max-width: 320px;"><?= htmlspecialchars(mb_strimwidth($p['conteudo'], 0, 140, '…')) ?></td>
                    <td><span class="badge badge-<?= $p['status'] === 'revisado' ? 'convertido' : ($p['status'] === 'ignorado' ? 'recusado' : 'novo') ?>"><?= htmlspecialchars(ucfirst($p['status'])) ?></span></td>
                    <td>
                        <?php if ($p['status'] !== 'revisado' && $p['status'] !== 'ignorado'): ?>
                            <div style="display:flex; gap:6px;">
                                <?php if ($p['processo_id']): ?>
                                <a href="<?= BASE_URL ?>/modules/prazos/form.php?processo_id=<?= $p['processo_id'] ?>" class="botao botao-secundario">Criar prazo</a>
                                <?php endif; ?>
                                <form method="post"><?= csrfCampo() ?><input type="hidden" name="acao" value="marcar_revisado"><input type="hidden" name="id" value="<?= $p['id'] ?>"><button class="botao botao-primario"><?= icone('check') ?></button></form>
                                <form method="post"><?= csrfCampo() ?><input type="hidden" name="acao" value="ignorar"><input type="hidden" name="id" value="<?= $p['id'] ?>"><button class="botao botao-secundario">Ignorar</button></form>
                            </div>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require __DIR__ . '/../../includes/rodape.php'; ?>
