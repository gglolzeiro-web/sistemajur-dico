<?php
require_once __DIR__ . '/../../includes/auth.php';
$usuario = exigirLogin();

$pdo = getConexao();
$id = isset($_GET['id']) ? (int) $_GET['id'] : null;
$cliente = ['nome' => '', 'cpf_cnpj' => '', 'email' => '', 'telefone' => '', 'endereco' => '', 'observacoes' => ''];
$erro = null;

if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM clientes WHERE id = ?');
    $stmt->execute([$id]);
    $encontrado = $stmt->fetch();
    if (!$encontrado) {
        http_response_code(404);
        die('Cliente não encontrado.');
    }
    $cliente = $encontrado;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfVerificar();
    $cliente = array_merge($cliente, [
        'nome' => trim($_POST['nome'] ?? ''),
        'cpf_cnpj' => trim($_POST['cpf_cnpj'] ?? ''),
        'email' => trim($_POST['email'] ?? ''),
        'telefone' => trim($_POST['telefone'] ?? ''),
        'endereco' => trim($_POST['endereco'] ?? ''),
        'observacoes' => trim($_POST['observacoes'] ?? ''),
    ]);

    if ($cliente['nome'] === '') {
        $erro = 'O nome é obrigatório.';
    } else {
        if ($id) {
            $stmt = $pdo->prepare(
                'UPDATE clientes SET nome=?, cpf_cnpj=?, email=?, telefone=?, endereco=?, observacoes=? WHERE id=?'
            );
            $stmt->execute([$cliente['nome'], $cliente['cpf_cnpj'], $cliente['email'], $cliente['telefone'], $cliente['endereco'], $cliente['observacoes'], $id]);
        } else {
            $stmt = $pdo->prepare(
                'INSERT INTO clientes (nome, cpf_cnpj, email, telefone, endereco, observacoes, criado_por) VALUES (?,?,?,?,?,?,?)'
            );
            $stmt->execute([$cliente['nome'], $cliente['cpf_cnpj'], $cliente['email'], $cliente['telefone'], $cliente['endereco'], $cliente['observacoes'], $usuario['id']]);
        }
        header('Location: index.php');
        exit;
    }
}

$tituloPagina = $id ? 'Editar cliente' : 'Novo cliente';
$paginaAtiva = 'clientes';
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
            <label>Nome completo / Razão social</label>
            <input type="text" name="nome" value="<?= htmlspecialchars($cliente['nome']) ?>" required>
        </div>
        <div class="campo">
            <label>CPF/CNPJ</label>
            <input type="text" name="cpf_cnpj" value="<?= htmlspecialchars($cliente['cpf_cnpj'] ?? '') ?>">
        </div>
        <div class="campo">
            <label>Telefone</label>
            <input type="text" name="telefone" value="<?= htmlspecialchars($cliente['telefone'] ?? '') ?>">
        </div>
        <div class="campo campo-largo">
            <label>E-mail</label>
            <input type="email" name="email" value="<?= htmlspecialchars($cliente['email'] ?? '') ?>">
        </div>
        <div class="campo campo-largo">
            <label>Endereço</label>
            <input type="text" name="endereco" value="<?= htmlspecialchars($cliente['endereco'] ?? '') ?>">
        </div>
        <div class="campo campo-largo">
            <label>Observações</label>
            <textarea name="observacoes"><?= htmlspecialchars($cliente['observacoes'] ?? '') ?></textarea>
        </div>
    </div>
    <button type="submit" class="botao botao-primario" style="margin-top: 16px;">Salvar</button>
</form>

<?php require __DIR__ . '/../../includes/rodape.php'; ?>
