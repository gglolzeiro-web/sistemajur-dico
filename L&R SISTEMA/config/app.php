<?php
/**
 * Configurações gerais da aplicação.
 */

define('APP_NOME', 'L&R');
define('APP_TIMEZONE', 'America/Sao_Paulo');

// Chave de API para geração de sugestão de resposta por IA (módulo Contatos).
// Deixe em branco para desativar a geração automática (o sistema mostra um
// modelo de resposta padrão nesse caso). Preencha com uma chave válida da
// Anthropic (https://console.anthropic.com) quando for ativar.
define('ANTHROPIC_API_KEY', '');

date_default_timezone_set(APP_TIMEZONE);

// Detecta a URL base automaticamente (funciona tanto na raiz do domínio
// quanto dentro de uma subpasta, comum em hospedagem compartilhada).
if (!defined('BASE_URL')) {
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
    $scriptDir = rtrim(preg_replace('#/modules/[^/]+$#', '', $scriptDir), '/');
    define('BASE_URL', $scriptDir);
}

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}
