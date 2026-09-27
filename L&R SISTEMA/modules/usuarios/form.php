<?php
require_once __DIR__ . '/../../includes/auth.php';
exigirCargo(['admin']);

$pdo = getConexao();
$id = isset($_GET['id']) ? (int) $_GET['id'] : null;
$usuarioForm = ['nome' => '', 'email' => '', 'cargo' => 'atendente', 'oab' => '', 'ativo' => 1];
$erro = null;

if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM usuarios WHERE id = ?');
    $stmt->execute([$id]);
    $encontrado = $stmt->fetch();
    if (!$encontrado) { http_response_code(404); die('Usuário não encontrado.'); }
    $usuarioForm = $encontrado;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfVerificar();
    $usuarioForm = array_merge($usuarioForm, [
        'nome' => trim($_POST['nome'] ?? ''),
        'email' => trim($_POST['email'] ?? ''),
        'cargo' => $_POST['cargo'] ?? 'atendente',
        'oab' => trim($_POST['oab'] ?? ''),
        'ativo' => isset($_POST['ativo']) ? 1 : 0,
    ]);
    $senha = $_POST['senha'] ?? '';

    if ($usuarioForm['nome'] === '' || $usuarioForm['email'] === '') {
        $erro = 'Nome e e-mail são obrigatórios.';
    } elseif (!$id && $senha === '') {
        $erro = 'Defina uma senha para o novo usuário.';
    } else {
        if ($id) {
            if ($senha !== '') {
                $stmt = $pdo->prepare('UPDATE usuarios SET nome=?, email=?, cargo=?, oab=?, ativo=?, senha_hash=? WHERE id=?');
                $stmt->execute([$usuarioForm['nome'], $usuarioForm['email'], $usuarioForm['cargo'], $usuarioForm['oab'], $usuarioForm['ativo'], password_hash($senha, PASSWORD_BCRYPT), $id]);
            } else {
                $stmt = $pdo->prepare('UPDATE usuarios SET nome=?, email=?, cargo=?, oab=?, ativo=? WHERE id=?');
                $stmt->execute([$usuarioForm['nome'], $usuarioForm['email'], $usuarioForm['cargo'], $usuarioForm['oab'], $usuarioForm['ativo'], $id]);
            }
        } else {
            $stmt = $pdo->prepare('INSERT INTO usuarios (nome, email, senha_hash, cargo, oab, ativo) VALUES (?,?,?,?,?,?)');
            $stmt->execute([$usuarioForm['nome'], $usuarioForm['email'], password_hash($senha, PASSWORD_BCRYPT), $usuarioForm['cargo'], $usuarioForm['oab'], $usuarioForm['ativo']]);
        }
        header('Location: index.php');
        exit;
    }
}

$tituloPagina = $id ? 'Editar usuário' : 'Novo usuário';
$paginaAtiva = 'usuarios';
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
        <div class="campo">
            <label>Nome</label>
            <input type="text" name="nome" value="<?= htmlspecialchars($usuarioForm['nome']) ?>" required>
        </div>
        <div class="campo">
            <label>E-mail</label>
            <input type="email" name="email" value="<?= htmlspecialchars($usuarioForm['email']) ?>" required>
        </div>
        <div class="campo">
            <label>Cargo</label>
            <select name="cargo">
                <?php foreach (['admin' => 'Administrador', 'advogado' => 'Advogado', 'atendente' => 'Atendente'] as $valor => $rotulo): ?>
                    <option value="<?= $valor ?>" <?= $usuarioForm['cargo'] === $valor ? 'selected' : '' ?>><?= $rotulo ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="campo">
            <label>OAB (se advogado)</label>
            <input type="text" name="oab" value="<?= htmlspecialchars($usuarioForm['oab'] ?? '') ?>">
        </div>
        <div class="campo">
            <label><?= $id ? 'Nova senha (deixe em branco para manter)' : 'Senha' ?></label>
            <input type="password" name="senha" <?= $id ? '' : 'required' ?>>
        </div>
        <div class="campo" style="justify-content: center; flex-direction: row; align-items: center; gap: 8px;">
            <input type="checkbox" name="ativo" id="ativo" <?= $usuarioForm['ativo'] ? 'checked' : '' ?> style="width:auto;">
            <label for="ativo" style="margin:0;">Usuário ativo</label>
        </div>
    </div>
    <button type="submit" class="botao botao-primario" style="margin-top: 16px;">Salvar</button>
</form>

<?php require __DIR__ . '/../../includes/rodape.php'; ?>
