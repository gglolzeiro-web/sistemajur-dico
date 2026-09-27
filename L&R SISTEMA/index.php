<?php
require_once __DIR__ . '/includes/auth.php';

if (usuarioLogado()) {
    header('Location: ' . BASE_URL . '/modules/painel/index.php');
} else {
    header('Location: ' . BASE_URL . '/login.php');
}
exit;
