<?php
require_once __DIR__ . '/../../includes/auth.php';
$usuario = exigirLogin();

$pdo = getConexao();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfVerificar();
    $acao = $_POST['acao'] ?? '';
    $id = isset($_POST['id']) ? (int) $_POST['id'] : null;

    if ($acao === 'salvar') {
        $titulo = trim($_POST['titulo'] ?? '');
        $conteudo = trim($_POST['conteudo'] ?? '');
        if ($titulo !== '' && $conteudo !== '') {
            if ($id) {
                $pdo->prepare('UPDATE contatos_roteiros SET titulo=?, conteudo=? WHERE id=?')->execute([$titulo, $conteudo, $id]);
            } else {
                $pdo->prepare('INSERT INTO contatos_roteiros (titulo, conteudo, criado_por) VALUES (?,?,?)')->execute([$titulo, $conteudo, $usuario['id']]);
            }
        }
    } elseif ($acao === 'alternar_ativo' && $id) {
        $pdo->prepare('UPDATE contatos_roteiros SET ativo = 1 - ativo WHERE id = ?')->execute([$id]);
    }

    header('Location: roteiros.php');
    exit;
}

$roteiros = $pdo->query('SELECT * FROM contatos_roteiros ORDER BY ativo DESC, titulo')->fetchAll();

$tituloPagina = 'Roteiros de conversa';
$paginaAtiva = 'contatos';
require __DIR__ . '/../../includes/cabecalho.php';
?>

<div class="pagina-cabecalho">
    <a href="index.php" class="botao botao-secundario" style="margin-bottom: 14px;"><?= icone('arrow-left') ?> Voltar para Contatos</a>
    <h1>Roteiros de conversa</h1>
    <p>Scripts que os atendentes seguem ao tentar captar um cliente.</p>
</div>

<div class="card" style="margin-bottom: 24px;">
    <h3>Novo roteiro</h3>
    <form method="post" style="margin-top: 12px; display:flex; flex-direction:column; gap:12px;">
        <?= csrfCampo() ?>
        <input type="hidden" name="acao" value="salvar">
        <div class="campo">
            <label>Título</label>
            <input type="text" name="titulo" required>
        </div>
        <div class="campo">
            <label>Conteúdo</label>
            <textarea name="conteudo" required style="min-height:140px;"></textarea>
        </div>
        <div>
            <button type="submit" class="botao botao-primario"><?= icone('plus') ?> Adicionar roteiro</button>
        </div>
    </form>
</div>

<?php foreach ($roteiros as $r): ?>
    <div class="card">
        <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:12px;">
            <div>
                <h3 style="margin-bottom:4px;"><?= htmlspecialchars($r['titulo']) ?></h3>
                <span class="badge <?= $r['ativo'] ? 'badge-ativo' : 'badge-recusado' ?>"><?= $r['ativo'] ? 'Ativo' : 'Inativo' ?></span>
            </div>
            <form method="post">
                <?= csrfCampo() ?>
                <input type="hidden" name="acao" value="alternar_ativo">
                <input type="hidden" name="id" value="<?= $r['id'] ?>">
                <button type="submit" class="botao botao-secundario"><?= $r['ativo'] ? 'Desativar' : 'Ativar' ?></button>
            </form>
        </div>
        <p style="white-space: pre-line; color: var(--texto-suave); margin-top: 10px;"><?= htmlspecialchars($r['conteudo']) ?></p>
    </div>
<?php endforeach; ?>

<?php require __DIR__ . '/../../includes/rodape.php'; ?>
