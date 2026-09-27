<?php
require_once __DIR__ . '/../../includes/auth.php';
exigirLogin();

$pdo = getConexao();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfVerificar();
    $acao = $_POST['acao'] ?? '';
    $id = isset($_POST['id']) ? (int) $_POST['id'] : null;

    if ($acao === 'salvar') {
        $nome = trim($_POST['nome'] ?? '');
        $dias = $_POST['dias_padrao'] !== '' ? (int) $_POST['dias_padrao'] : null;
        $diasUteis = isset($_POST['conta_em_dias_uteis']) ? 1 : 0;
        $cor = trim($_POST['cor'] ?? '#274B6D');

        if ($nome !== '') {
            if ($id) {
                $stmt = $pdo->prepare('UPDATE tipos_prazo SET nome=?, dias_padrao=?, conta_em_dias_uteis=?, cor=? WHERE id=?');
                $stmt->execute([$nome, $dias, $diasUteis, $cor, $id]);
            } else {
                $stmt = $pdo->prepare('INSERT INTO tipos_prazo (nome, dias_padrao, conta_em_dias_uteis, cor) VALUES (?,?,?,?)');
                $stmt->execute([$nome, $dias, $diasUteis, $cor]);
            }
        }
    } elseif ($acao === 'alternar_ativo' && $id) {
        $pdo->prepare('UPDATE tipos_prazo SET ativo = 1 - ativo WHERE id = ?')->execute([$id]);
    }

    header('Location: index.php');
    exit;
}

$tipos = $pdo->query('SELECT * FROM tipos_prazo ORDER BY nome')->fetchAll();

$tituloPagina = 'Tipos de prazo';
$paginaAtiva = 'tipos_prazo';
require __DIR__ . '/../../includes/cabecalho.php';
?>

<div class="pagina-cabecalho">
    <h1>Tipos de prazo</h1>
    <p>Catálogo usado para classificar e sugerir a duração dos prazos.</p>
</div>

<div class="card" style="margin-bottom: 24px;">
    <h3>Novo tipo</h3>
    <form method="post" class="form-grade" style="margin-top: 12px;">
        <?= csrfCampo() ?>
        <input type="hidden" name="acao" value="salvar">
        <div class="campo">
            <label>Nome</label>
            <input type="text" name="nome" required>
        </div>
        <div class="campo">
            <label>Dias padrão</label>
            <input type="number" name="dias_padrao" min="0">
        </div>
        <div class="campo">
            <label>Cor</label>
            <input type="color" name="cor" value="#274B6D">
        </div>
        <div class="campo" style="justify-content: center; flex-direction: row; align-items: center; gap: 8px;">
            <input type="checkbox" name="conta_em_dias_uteis" id="dias_uteis" checked style="width:auto;">
            <label for="dias_uteis" style="margin:0;">Contar em dias úteis</label>
        </div>
        <div class="campo campo-largo">
            <button type="submit" class="botao botao-primario"><?= icone('plus') ?> Adicionar</button>
        </div>
    </form>
</div>

<div class="card">
    <table>
        <thead>
            <tr><th>Nome</th><th>Dias padrão</th><th>Contagem</th><th>Cor</th><th>Status</th><th></th></tr>
        </thead>
        <tbody>
            <?php foreach ($tipos as $t): ?>
                <tr>
                    <td><?= htmlspecialchars($t['nome']) ?></td>
                    <td><?= $t['dias_padrao'] !== null ? $t['dias_padrao'] . ' dias' : '—' ?></td>
                    <td><?= $t['conta_em_dias_uteis'] ? 'Dias úteis' : 'Dias corridos' ?></td>
                    <td><span style="display:inline-block; width:16px; height:16px; border-radius:4px; background:<?= htmlspecialchars($t['cor']) ?>;"></span></td>
                    <td><span class="badge <?= $t['ativo'] ? 'badge-ativo' : 'badge-recusado' ?>"><?= $t['ativo'] ? 'Ativo' : 'Inativo' ?></span></td>
                    <td>
                        <form method="post">
                            <?= csrfCampo() ?>
                            <input type="hidden" name="acao" value="alternar_ativo">
                            <input type="hidden" name="id" value="<?= $t['id'] ?>">
                            <button type="submit" class="botao botao-secundario"><?= $t['ativo'] ? 'Desativar' : 'Ativar' ?></button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require __DIR__ . '/../../includes/rodape.php'; ?>
