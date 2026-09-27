<?php
require_once __DIR__ . '/../../includes/auth.php';
exigirLogin();

$pdo = getConexao();
$id = isset($_GET['id']) ? (int) $_GET['id'] : null;
$prazo = [
    'processo_id' => '', 'tipo_prazo_id' => '', 'titulo' => '', 'descricao' => '',
    'data_limite' => '', 'hora_limite' => '', 'responsavel_id' => '', 'status' => 'pendente',
];
$erro = null;

if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM prazos WHERE id = ?');
    $stmt->execute([$id]);
    $encontrado = $stmt->fetch();
    if (!$encontrado) { http_response_code(404); die('Prazo não encontrado.'); }
    $prazo = $encontrado;
} elseif (isset($_GET['processo_id'])) {
    $prazo['processo_id'] = (int) $_GET['processo_id'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfVerificar();
    $prazo = array_merge($prazo, [
        'processo_id' => $_POST['processo_id'] !== '' ? (int) $_POST['processo_id'] : null,
        'tipo_prazo_id' => $_POST['tipo_prazo_id'] !== '' ? (int) $_POST['tipo_prazo_id'] : null,
        'titulo' => trim($_POST['titulo'] ?? ''),
        'descricao' => trim($_POST['descricao'] ?? ''),
        'data_limite' => $_POST['data_limite'] ?? '',
        'hora_limite' => $_POST['hora_limite'] !== '' ? $_POST['hora_limite'] : null,
        'responsavel_id' => $_POST['responsavel_id'] !== '' ? (int) $_POST['responsavel_id'] : null,
        'status' => $_POST['status'] ?? 'pendente',
    ]);

    if ($prazo['titulo'] === '' || $prazo['data_limite'] === '') {
        $erro = 'Título e data limite são obrigatórios.';
    } else {
        if ($id) {
            $stmt = $pdo->prepare(
                'UPDATE prazos SET processo_id=?, tipo_prazo_id=?, titulo=?, descricao=?, data_limite=?, hora_limite=?, responsavel_id=?, status=? WHERE id=?'
            );
            $stmt->execute([$prazo['processo_id'], $prazo['tipo_prazo_id'], $prazo['titulo'], $prazo['descricao'], $prazo['data_limite'], $prazo['hora_limite'], $prazo['responsavel_id'], $prazo['status'], $id]);
        } else {
            $stmt = $pdo->prepare(
                'INSERT INTO prazos (processo_id, tipo_prazo_id, titulo, descricao, data_limite, hora_limite, responsavel_id, status) VALUES (?,?,?,?,?,?,?,?)'
            );
            $stmt->execute([$prazo['processo_id'], $prazo['tipo_prazo_id'], $prazo['titulo'], $prazo['descricao'], $prazo['data_limite'], $prazo['hora_limite'], $prazo['responsavel_id'], $prazo['status']]);
        }
        header('Location: index.php');
        exit;
    }
}

$processos = $pdo->query(
    "SELECT p.id, p.numero_cnj, c.nome AS cliente_nome FROM processos p JOIN clientes c ON c.id = p.cliente_id ORDER BY c.nome"
)->fetchAll();
$tiposPrazo = $pdo->query('SELECT id, nome FROM tipos_prazo WHERE ativo = 1 ORDER BY nome')->fetchAll();
$usuarios = $pdo->query('SELECT id, nome FROM usuarios WHERE ativo = 1 ORDER BY nome')->fetchAll();

$tituloPagina = $id ? 'Editar prazo' : 'Novo prazo';
$paginaAtiva = 'prazos';
require __DIR__ . '/../../includes/cabecalho.php';
?>

<div class="pagina-cabecalho">
    <a href="index.php" class="botao botao-secundario" style="margin-bottom: 14px;"><?= icone('arrow-left') ?> Voltar</a>
    <h1><?= $tituloPagina ?></h1>
</div>

<?php if ($erro): ?><div class="alerta alerta-erro"><?= htmlspecialchars($erro) ?></div><?php endif; ?>

<form method="post" class="card">
    <?= csrfCampo() ?>
    <div class="form-grade">
        <div class="campo campo-largo">
            <label>Título</label>
            <input type="text" name="titulo" value="<?= htmlspecialchars($prazo['titulo']) ?>" required>
        </div>
        <div class="campo">
            <label>Processo (opcional)</label>
            <select name="processo_id">
                <option value="">—</option>
                <?php foreach ($processos as $p): ?>
                    <option value="<?= $p['id'] ?>" <?= (int) ($prazo['processo_id'] ?? 0) === (int) $p['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars(($p['numero_cnj'] ?: 'sem número') . ' — ' . $p['cliente_nome']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="campo">
            <label>Tipo de prazo</label>
            <select name="tipo_prazo_id">
                <option value="">—</option>
                <?php foreach ($tiposPrazo as $t): ?>
                    <option value="<?= $t['id'] ?>" <?= (int) ($prazo['tipo_prazo_id'] ?? 0) === (int) $t['id'] ? 'selected' : '' ?>><?= htmlspecialchars($t['nome']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="campo">
            <label>Data limite</label>
            <input type="date" name="data_limite" value="<?= htmlspecialchars($prazo['data_limite']) ?>" required>
        </div>
        <div class="campo">
            <label>Hora (opcional)</label>
            <input type="time" name="hora_limite" value="<?= htmlspecialchars($prazo['hora_limite'] ?? '') ?>">
        </div>
        <div class="campo">
            <label>Responsável</label>
            <select name="responsavel_id">
                <option value="">—</option>
                <?php foreach ($usuarios as $u): ?>
                    <option value="<?= $u['id'] ?>" <?= (int) ($prazo['responsavel_id'] ?? 0) === (int) $u['id'] ? 'selected' : '' ?>><?= htmlspecialchars($u['nome']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="campo">
            <label>Status</label>
            <select name="status">
                <?php foreach (['pendente' => 'Pendente', 'confirmado' => 'Confirmado', 'cumprido' => 'Cumprido', 'perdido' => 'Perdido', 'cancelado' => 'Cancelado'] as $valor => $rotulo): ?>
                    <option value="<?= $valor ?>" <?= $prazo['status'] === $valor ? 'selected' : '' ?>><?= $rotulo ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="campo campo-largo">
            <label>Descrição</label>
            <textarea name="descricao"><?= htmlspecialchars($prazo['descricao'] ?? '') ?></textarea>
        </div>
    </div>
    <button type="submit" class="botao botao-primario" style="margin-top: 16px;">Salvar</button>
</form>

<?php require __DIR__ . '/../../includes/rodape.php'; ?>
