<?php
require_once __DIR__ . '/includes/auth.php';

if (usuarioLogado()) {
    header('Location: ' . BASE_URL . '/modules/painel/index.php');
    exit;
}

$erro = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';

    if ($email === '' || $senha === '') {
        $erro = 'Informe e-mail e senha.';
    } elseif (tentarLogin($email, $senha)) {
        header('Location: ' . BASE_URL . '/modules/painel/index.php');
        exit;
    } else {
        $erro = 'E-mail ou senha inválidos.';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Entrar · <?= APP_NOME ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body class="pagina-login">
    <div class="login-card">
        <div class="login-marca">
            <span class="login-marca-texto">L&amp;R</span>
        </div>
        <p class="login-subtitulo">gestão de prazos e documentos</p>

        <?php if ($erro): ?>
            <div class="alerta alerta-erro"><?= htmlspecialchars($erro) ?></div>
        <?php endif; ?>

        <form method="post" class="form-login">
            <label for="email">E-mail</label>
            <input type="email" id="email" name="email" required autofocus>

            <label for="senha">Senha</label>
            <input type="password" id="senha" name="senha" required>

            <button type="submit" class="botao botao-primario">Entrar</button>
        </form>
    </div>
</body>
</html>
