<?php
require_once __DIR__ . '/../../includes/auth.php';
$usuario = exigirLogin();

$pdo = getConexao();
$id = isset($_GET['id']) ? (int) $_GET['id'] : null;
$contato = ['nome' => '', 'cpf_cnpj' => '', 'email' => '', 'telefone' => '', 'roteiro_id' => '', 'observacoes' => ''];
$erro = null;

if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM contatos WHERE id = ?');
    $stmt->execute([$id]);
    $encontrado = $stmt->fetch();
    if (!$encontrado) { http_response_code(404); die('Contato não encontrado.'); }
    $contato = $encontrado;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfVerificar();
    $contato = array_merge($contato, [
        'nome' => trim($_POST['nome'] ?? ''),
        'cpf_cnpj' => trim($_POST['cpf_cnpj'] ?? ''),
        'email' => trim($_POST['email'] ?? ''),
        'telefone' => trim($_POST['telefone'] ?? ''),
        'roteiro_id' => $_POST['roteiro_id'] !== '' ? (int) $_POST['roteiro_id'] : null,
        'observacoes' => trim($_POST['observacoes'] ?? ''),
    ]);

    if ($contato['nome'] === '') {
        $erro = 'O nome é obrigatório.';
    } else {
        if ($id) {
            $stmt = $pdo->prepare(
                'UPDATE contatos SET nome=?, cpf_cnpj=?, email=?, telefone=?, roteiro_id=?, observacoes=? WHERE id=?'
            );
            $stmt->execute([$contato['nome'], $contato['cpf_cnpj'], $contato['email'], $contato['telefone'], $contato['roteiro_id'], $contato['observacoes'], $id]);
            registrarAuditoriaContato($id, 'editou_dados');
            header('Location: pasta.php?id=' . $id);
        } else {
            $stmt = $pdo->prepare(
                'INSERT INTO contatos (nome, cpf_cnpj, email, telefone, origem, roteiro_id, observacoes, atendente_atual_id, criado_por) VALUES (?,?,?,?,\'manual\',?,?,?,?)'
            );
            $stmt->execute([$contato['nome'], $contato['cpf_cnpj'], $contato['email'], $contato['telefone'], $contato['roteiro_id'], $contato['observacoes'], $usuario['id'], $usuario['id']]);
            $novoId = (int) $pdo->lastInsertId();
            registrarAuditoriaContato($novoId, 'cadastrou', 'Cadastro manual');
            header('Location: pasta.php?id=' . $novoId);
        }
        exit;
    }
}

$roteiros = $pdo->query('SELECT id, titulo FROM contatos_roteiros WHERE ativo = 1 ORDER BY titulo')->fetchAll();

$tituloPagina = $id ? 'Editar contato' : 'Novo contato';
$paginaAtiva = 'contatos';
require __DIR__ . '/../../includes/cabecalho.php';
?>

<div class="pagina-cabecalho">
    <a href="<?= $id ? 'pasta.php?id=' . $id : 'index.php' ?>" class="botao botao-secundario" style="margin-bottom: 14px;"><?= icone('arrow-left') ?> Voltar</a>
    <h1><?= $tituloPagina ?></h1>
</div>

<?php if ($erro): ?><div class="alerta alerta-erro"><?= htmlspecialchars($erro) ?></div><?php endif; ?>

<form method="post" class="card">
    <?= csrfCampo() ?>
    <div class="form-grade">
        <div class="campo campo-largo">
            <label>Nome</label>
            <input type="text" name="nome" value="<?= htmlspecialchars($contato['nome']) ?>" required>
        </div>
        <div class="campo">
            <label>CPF/CNPJ</label>
            <input type="text" name="cpf_cnpj" value="<?= htmlspecialchars($contato['cpf_cnpj'] ?? '') ?>">
        </div>
        <div class="campo">
            <label>Telefone</label>
            <input type="text" name="telefone" value="<?= htmlspecialchars($contato['telefone'] ?? '') ?>">
        </div>
        <div class="campo campo-largo">
            <label>E-mail</label>
            <input type="email" name="email" value="<?= htmlspecialchars($contato['email'] ?? '') ?>">
        </div>
        <div class="campo campo-largo">
            <label>Roteiro de conversa</label>
            <select name="roteiro_id">
                <option value="">—</option>
                <?php foreach ($roteiros as $r): ?>
                    <option value="<?= $r['id'] ?>" <?= (int) ($contato['roteiro_id'] ?? 0) === (int) $r['id'] ? 'selected' : '' ?>><?= htmlspecialchars($r['titulo']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="campo campo-largo">
            <label>Observações</label>
            <textarea name="observacoes"><?= htmlspecialchars($contato['observacoes'] ?? '') ?></textarea>
        </div>
    </div>
    <button type="submit" class="botao botao-primario" style="margin-top: 16px;">Salvar</button>
</form>

<?php require __DIR__ . '/../../includes/rodape.php'; ?>
