<?php
require_once __DIR__ . '/../../includes/auth.php';
exigirLogin();

$pdo = getConexao();
$id = isset($_GET['id']) ? (int) $_GET['id'] : null;
$processo = [
    'cliente_id' => '', 'numero_cnj' => '', 'tipo_acao' => '', 'vara' => '',
    'comarca' => '', 'tribunal' => '', 'advogado_responsavel_id' => '',
    'situacao' => 'ativo', 'observacoes' => '',
];
$erro = null;

if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM processos WHERE id = ?');
    $stmt->execute([$id]);
    $encontrado = $stmt->fetch();
    if (!$encontrado) { http_response_code(404); die('Processo não encontrado.'); }
    $processo = $encontrado;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfVerificar();
    $processo = array_merge($processo, [
        'cliente_id' => (int) ($_POST['cliente_id'] ?? 0),
        'numero_cnj' => trim($_POST['numero_cnj'] ?? ''),
        'tipo_acao' => trim($_POST['tipo_acao'] ?? ''),
        'vara' => trim($_POST['vara'] ?? ''),
        'comarca' => trim($_POST['comarca'] ?? ''),
        'tribunal' => trim($_POST['tribunal'] ?? ''),
        'advogado_responsavel_id' => $_POST['advogado_responsavel_id'] !== '' ? (int) $_POST['advogado_responsavel_id'] : null,
        'situacao' => $_POST['situacao'] ?? 'ativo',
        'observacoes' => trim($_POST['observacoes'] ?? ''),
    ]);

    if (!$processo['cliente_id']) {
        $erro = 'Selecione o cliente.';
    } else {
        if ($id) {
            $stmt = $pdo->prepare(
                'UPDATE processos SET cliente_id=?, numero_cnj=?, tipo_acao=?, vara=?, comarca=?, tribunal=?, advogado_responsavel_id=?, situacao=?, observacoes=? WHERE id=?'
            );
            $stmt->execute([$processo['cliente_id'], $processo['numero_cnj'], $processo['tipo_acao'], $processo['vara'], $processo['comarca'], $processo['tribunal'], $processo['advogado_responsavel_id'], $processo['situacao'], $processo['observacoes'], $id]);
        } else {
            $stmt = $pdo->prepare(
                'INSERT INTO processos (cliente_id, numero_cnj, tipo_acao, vara, comarca, tribunal, advogado_responsavel_id, situacao, observacoes) VALUES (?,?,?,?,?,?,?,?,?)'
            );
            $stmt->execute([$processo['cliente_id'], $processo['numero_cnj'], $processo['tipo_acao'], $processo['vara'], $processo['comarca'], $processo['tribunal'], $processo['advogado_responsavel_id'], $processo['situacao'], $processo['observacoes']]);
        }
        header('Location: index.php');
        exit;
    }
}

$clientes = $pdo->query('SELECT id, nome FROM clientes ORDER BY nome')->fetchAll();
$advogados = $pdo->query("SELECT id, nome FROM usuarios WHERE cargo IN ('advogado','admin') AND ativo = 1 ORDER BY nome")->fetchAll();

$tituloPagina = $id ? 'Editar processo' : 'Novo processo';
$paginaAtiva = 'processos';
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
            <label>Cliente</label>
            <select name="cliente_id" required>
                <option value="">Selecione...</option>
                <?php foreach ($clientes as $c): ?>
                    <option value="<?= $c['id'] ?>" <?= (int) $processo['cliente_id'] === (int) $c['id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['nome']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="campo">
            <label>Número CNJ</label>
            <input type="text" name="numero_cnj" placeholder="0000000-00.0000.0.00.0000" value="<?= htmlspecialchars($processo['numero_cnj'] ?? '') ?>">
        </div>
        <div class="campo">
            <label>Tipo de ação</label>
            <input type="text" name="tipo_acao" value="<?= htmlspecialchars($processo['tipo_acao'] ?? '') ?>">
        </div>
        <div class="campo">
            <label>Vara</label>
            <input type="text" name="vara" value="<?= htmlspecialchars($processo['vara'] ?? '') ?>">
        </div>
        <div class="campo">
            <label>Comarca</label>
            <input type="text" name="comarca" value="<?= htmlspecialchars($processo['comarca'] ?? '') ?>">
        </div>
        <div class="campo">
            <label>Tribunal</label>
            <input type="text" name="tribunal" value="<?= htmlspecialchars($processo['tribunal'] ?? '') ?>">
        </div>
        <div class="campo">
            <label>Advogado responsável</label>
            <select name="advogado_responsavel_id">
                <option value="">—</option>
                <?php foreach ($advogados as $a): ?>
                    <option value="<?= $a['id'] ?>" <?= (int) ($processo['advogado_responsavel_id'] ?? 0) === (int) $a['id'] ? 'selected' : '' ?>><?= htmlspecialchars($a['nome']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="campo">
            <label>Situação</label>
            <select name="situacao">
                <?php foreach (['ativo' => 'Ativo', 'suspenso' => 'Suspenso', 'arquivado' => 'Arquivado', 'encerrado' => 'Encerrado'] as $valor => $rotulo): ?>
                    <option value="<?= $valor ?>" <?= $processo['situacao'] === $valor ? 'selected' : '' ?>><?= $rotulo ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="campo campo-largo">
            <label>Observações</label>
            <textarea name="observacoes"><?= htmlspecialchars($processo['observacoes'] ?? '') ?></textarea>
        </div>
    </div>
    <button type="submit" class="botao botao-primario" style="margin-top: 16px;">Salvar</button>
</form>

<?php require __DIR__ . '/../../includes/rodape.php'; ?>
