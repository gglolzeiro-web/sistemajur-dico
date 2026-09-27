<?php
/**
 * Funções de autenticação e controle de sessão.
 * Toda página interna deve incluir este arquivo e chamar exigirLogin().
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';

function usuarioLogado(): ?array
{
    return $_SESSION['usuario'] ?? null;
}

function exigirLogin(): array
{
    $usuario = usuarioLogado();
    if (!$usuario) {
        header('Location: ' . BASE_URL . '/login.php');
        exit;
    }
    return $usuario;
}

function exigirCargo(array $cargosPermitidos): array
{
    $usuario = exigirLogin();
    if (!in_array($usuario['cargo'], $cargosPermitidos, true)) {
        http_response_code(403);
        die('Você não tem permissão para acessar esta página.');
    }
    return $usuario;
}

function tentarLogin(string $email, string $senha): bool
{
    $pdo = getConexao();
    $stmt = $pdo->prepare('SELECT id, nome, email, senha_hash, cargo, ativo FROM usuarios WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $usuario = $stmt->fetch();

    if (!$usuario || !$usuario['ativo'] || !password_verify($senha, $usuario['senha_hash'])) {
        return false;
    }

    session_regenerate_id(true);
    $_SESSION['usuario'] = [
        'id' => $usuario['id'],
        'nome' => $usuario['nome'],
        'email' => $usuario['email'],
        'cargo' => $usuario['cargo'],
    ];

    return true;
}

function fazerLogout(): void
{
    $_SESSION = [];
    session_destroy();
}

function csrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrfCampo(): string
{
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrfToken()) . '">';
}

function csrfVerificar(): void
{
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(419);
        die('Sessão expirada, recarregue a página e tente novamente.');
    }
}

/** Registra uma ação de auditoria na pasta de um contato. */
function registrarAuditoriaContato(int $contatoId, string $acao, ?string $detalhes = null): void
{
    $usuario = usuarioLogado();
    $pdo = getConexao();
    $stmt = $pdo->prepare(
        'INSERT INTO contatos_auditoria (contato_id, usuario_id, acao, detalhes) VALUES (?, ?, ?, ?)'
    );
    $stmt->execute([$contatoId, $usuario['id'] ?? null, $acao, $detalhes]);
}
