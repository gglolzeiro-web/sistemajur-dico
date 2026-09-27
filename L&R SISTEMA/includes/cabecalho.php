<?php
/**
 * Cabeçalho/estrutura comum a todas as páginas internas.
 * Antes de incluir este arquivo, a página deve:
 *   - ter chamado exigirLogin() (ou exigirCargo())
 *   - definido $paginaAtiva (chave do menu.php) e $tituloPagina
 */

require_once __DIR__ . '/icones.php';
$usuario = usuarioLogado();
$itensMenu = require __DIR__ . '/menu.php';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($tituloPagina ?? 'Painel') ?> · <?= APP_NOME ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
<div class="app-shell">
    <aside class="sidebar" id="sidebar">
        <button type="button" class="sidebar-toggle" id="sidebarToggle" aria-label="Recolher menu">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
        </button>

        <div class="marca">
            <span class="marca-letra">L&amp;R</span>
            <span class="marca-subtitulo">gestão de prazos e documentos</span>
        </div>

        <nav class="menu">
            <?php foreach ($itensMenu as $item): ?>
                <a href="<?= $item['url'] ?>" class="menu-item <?= ($paginaAtiva ?? '') === $item['chave'] ? 'ativo' : '' ?>">
                    <?= icone($item['icone']) ?>
                    <span><?= htmlspecialchars($item['rotulo']) ?></span>
                </a>
            <?php endforeach; ?>
        </nav>

        <div class="sidebar-rodape">
            <p class="usuario-nome"><?= htmlspecialchars($usuario['nome'] ?? '') ?> (<?= htmlspecialchars($usuario['cargo'] ?? '') ?>)</p>
            <p class="usuario-links">
                <a href="<?= BASE_URL ?>/modules/meus_dados/index.php">Alterar senha</a>
                &middot;
                <a href="<?= BASE_URL ?>/logout.php">Sair</a>
            </p>
            <p class="sidebar-nota">Dados salvos no servidor, compartilhados entre os usuários do escritório.</p>
        </div>
    </aside>

    <main class="conteudo">
