<?php
require_once __DIR__ . '/../../includes/auth.php';
$usuarioSessao = exigirLogin();

$pdo = getConexao();
$stmt = $pdo->prepare('SELECT * FROM usuarios WHERE id = ?');
$stmt->execute([$usuarioSessao['id']]);
$meuUsuario = $stmt->fetch();

$erro = null;
$sucesso = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfVerificar();
    $acao = $_POST['acao'] ?? '';

    if ($acao === 'dados') {
        $nome = trim($_POST['nome'] ?? '');
        $oab = trim($_POST['oab'] ?? '');
        if ($nome === '') {
            $erro = 'O nome não pode ficar em branco.';
        } else {
            $pdo->prepare('UPDATE usuarios SET nome=?, oab=? WHERE id=?')->execute([$nome, $oab, $meuUsuario['id']]);
            $_SESSION['usuario']['nome'] = $nome;
            $meuUsuario['nome'] = $nome;
            $meuUsuario['oab'] = $oab;
            $sucesso = 'Dados atualizados.';
        }
    } elseif ($acao === 'senha') {
        $atual = $_POST['senha_atual'] ?? '';
        $nova = $_POST['senha_nova'] ?? '';
        $confirmacao = $_POST['senha_confirmacao'] ?? '';

        if (!password_verify($atual, $meuUsuario['senha_hash'])) {
            $erro = 'Senha atual incorreta.';
        } elseif (strlen($nova) < 8) {
            $erro = 'A nova senha deve ter pelo menos 8 caracteres.';
        } elseif ($nova !== $confirmacao) {
            $erro = 'A confirmação não confere com a nova senha.';
        } else {
            $pdo->prepare('UPDATE usuarios SET senha_hash=? WHERE id=?')->execute([password_hash($nova, PASSWORD_BCRYPT), $meuUsuario['id']]);
            $sucesso = 'Senha alterada com sucesso.';
        }
    }
}

$tituloPagina = 'Meus Dados';
$paginaAtiva = 'meus_dados';
require __DIR__ . '/../../includes/cabecalho.php';
?>

<div class="pagina-cabecalho">
    <h1>Meus Dados</h1>
    <p>Edite suas informações e sua senha de acesso.</p>
</div>

<?php if ($erro): ?><div class="alerta alerta-erro"><?= htmlspecialchars($erro) ?></div><?php endif; ?>
<?php if ($sucesso): ?><div class="alerta alerta-sucesso"><?= htmlspecialchars($sucesso) ?></div><?php endif; ?>

<div class="card" style="margin-bottom: 20px;">
    <h3>Dados pessoais</h3>
    <form method="post" class="form-grade" style="margin-top: 12px;">
        <?= csrfCampo() ?>
        <input type="hidden" name="acao" value="dados">
        <div class="campo">
            <label>Nome</label>
            <input type="text" name="nome" value="<?= htmlspecialchars($meuUsuario['nome']) ?>" required>
        </div>
        <div class="campo">
            <label>E-mail</label>
            <input type="email" value="<?= htmlspecialchars($meuUsuario['email']) ?>" disabled>
        </div>
        <div class="campo">
            <label>OAB</label>
            <input type="text" name="oab" value="<?= htmlspecialchars($meuUsuario['oab'] ?? '') ?>">
        </div>
        <div class="campo campo-largo">
            <button type="submit" class="botao botao-primario">Salvar dados</button>
        </div>
    </form>
</div>

<div class="card">
    <h3>Alterar senha</h3>
    <form method="post" class="form-grade" style="margin-top: 12px;">
        <?= csrfCampo() ?>
        <input type="hidden" name="acao" value="senha">
        <div class="campo campo-largo">
            <label>Senha atual</label>
            <input type="password" name="senha_atual" required>
        </div>
        <div class="campo">
            <label>Nova senha</label>
            <input type="password" name="senha_nova" required minlength="8">
        </div>
        <div class="campo">
            <label>Confirmar nova senha</label>
            <input type="password" name="senha_confirmacao" required minlength="8">
        </div>
        <div class="campo campo-largo">
            <button type="submit" class="botao botao-primario">Alterar senha</button>
        </div>
    </form>
</div>

<?php require __DIR__ . '/../../includes/rodape.php'; ?>
