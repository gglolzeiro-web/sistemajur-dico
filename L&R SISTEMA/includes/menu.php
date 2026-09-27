<?php
/**
 * Itens do menu lateral. 'chave' é usada para destacar o item ativo
 * (definida em $paginaAtiva antes de incluir includes/cabecalho.php).
 */

return [
    [
        'chave' => 'painel',
        'rotulo' => 'Painel',
        'url' => BASE_URL . '/modules/painel/index.php',
        'icone' => 'grid',
    ],
    [
        'chave' => 'prazos',
        'rotulo' => 'Prazos',
        'url' => BASE_URL . '/modules/prazos/index.php',
        'icone' => 'clock',
    ],
    [
        'chave' => 'processos',
        'rotulo' => 'Processos',
        'url' => BASE_URL . '/modules/processos/index.php',
        'icone' => 'briefcase',
    ],
    [
        'chave' => 'clientes',
        'rotulo' => 'Clientes',
        'url' => BASE_URL . '/modules/clientes/index.php',
        'icone' => 'list',
    ],
    [
        'chave' => 'contatos',
        'rotulo' => 'Contatos',
        'url' => BASE_URL . '/modules/contatos/index.php',
        'icone' => 'user-plus',
    ],
    [
        'chave' => 'publicacoes',
        'rotulo' => 'Publicações',
        'url' => BASE_URL . '/modules/publicacoes/index.php',
        'icone' => 'mail',
    ],
    [
        'chave' => 'meus_dados',
        'rotulo' => 'Meus Dados',
        'url' => BASE_URL . '/modules/meus_dados/index.php',
        'icone' => 'settings',
    ],
    [
        'chave' => 'tipos_prazo',
        'rotulo' => 'Tipos de prazo',
        'url' => BASE_URL . '/modules/tipos_prazo/index.php',
        'icone' => 'shuffle',
    ],
    [
        'chave' => 'usuarios',
        'rotulo' => 'Usuários',
        'url' => BASE_URL . '/modules/usuarios/index.php',
        'icone' => 'user-circle',
    ],
];
