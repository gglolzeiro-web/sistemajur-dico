<?php
require_once __DIR__ . '/../../includes/auth.php';
exigirLogin();

$pdo = getConexao();
$filtroStatus = $_GET['status'] ?? '';
$busca = trim($_GET['busca'] ?? '');

$sql = "SELECT c.*, u.nome AS atendente_nome
        FROM contatos c
        LEFT JOIN usuarios u ON u.id = c.atendente_atual_id
        WHERE 1=1";
$parametros = [];

if ($filtroStatus !== '') {
    $sql .= ' AND c.status = ?';
    $parametros[] = $filtroStatus;
}
if ($busca !== '') {
    $sql .= ' AND (c.nome LIKE ? OR c.cpf_cnpj LIKE ? OR c.telefone LIKE ?)';
    $termo = '%' . $busca . '%';
    array_push($parametros, $termo, $termo, $termo);
}
$sql .= ' ORDER BY c.atualizado_em DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute($parametros);
$contatos = $stmt->fetchAll();

$contagens = $pdo->query(
    "SELECT status, COUNT(*) AS total FROM contatos GROUP BY status"
)->fetchAll(PDO::FETCH_KEY_PAIR);

$tituloPagina = 'Contatos';
$paginaAtiva = 'contatos';
require __DIR__ . '/../../includes/cabecalho.php';
?>

<div class="pagina-cabecalho">
    <h1>Contatos</h1>
    <p>Captação de clientes: da planilha ou cadastro manual até a conversão.</p>
</div>

<div class="grade-cards">
    <div class="card card-metrica"><div class="numero"><?= $contagens['novo'] ?? 0 ?></div><div class="rotulo">Novo</div></div>
    <div class="card card-metrica"><div class="numero"><?= $contagens['enviado'] ?? 0 ?></div><div class="rotulo">Enviado</div></div>
    <div class="card card-metrica"><div class="numero"><?= $contagens['recusado'] ?? 0 ?></div><div class="rotulo">Recusado</div></div>
    <div class="card card-metrica"><div class="numero"><?= $contagens['convertido'] ?? 0 ?></div><div class="rotulo">Convertido</div></div>
</div>

<div class="barra-acoes">
    <form method="get" style="display:flex; gap:8px; flex-wrap: wrap;">
        <input type="text" name="busca" placeholder="Buscar por nome, CPF/CNPJ ou telefone" value="<?= htmlspecialchars($busca) ?>">
        <select name="status" onchange="this.form.submit()">
            <option value="">Todos os status</option>
            <?php foreach (['novo' => 'Novo', 'enviado' => 'Enviado', 'recusado' => 'Recusado', 'convertido' => 'Convertido'] as $valor => $rotulo): ?>
                <option value="<?= $valor ?>" <?= $filtroStatus === $valor ? 'selected' : '' ?>><?= $rotulo ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="botao botao-secundario">Buscar</button>
    </form>
    <div style="display:flex; gap:8px;">
        <a href="roteiros.php" class="botao botao-secundario">Roteiros</a>
        <a href="importar.php" class="botao botao-secundario"><?= icone('upload') ?> Importar planilha</a>
        <a href="form.php" class="botao botao-primario"><?= icone('plus') ?> Novo contato</a>
    </div>
</div>

<div class="card">
    <table>
        <thead>
            <tr><th>Nome</th><th>Telefone</th><th>Origem</th><th>Atendente</th><th>Status</th><th>Atualizado</th><th></th></tr>
        </thead>
        <tbody>
            <?php if (!$contatos): ?>
                <tr><td colspan="7" style="color: var(--texto-suave);">Nenhum contato encontrado.</td></tr>
            <?php endif; ?>
            <?php foreach ($contatos as $c): ?>
                <tr>
                    <td><?= htmlspecialchars($c['nome']) ?></td>
                    <td><?= htmlspecialchars($c['telefone'] ?? '—') ?></td>
                    <td><?= $c['origem'] === 'planilha' ? 'Planilha' : 'Manual' ?></td>
                    <td><?= htmlspecialchars($c['atendente_nome'] ?? '—') ?></td>
                    <td><span class="badge badge-<?= htmlspecialchars($c['status']) ?>"><?= htmlspecialchars(ucfirst($c['status'])) ?></span></td>
                    <td><?= date('d/m/Y H:i', strtotime($c['atualizado_em'])) ?></td>
                    <td><a href="pasta.php?id=<?= $c['id'] ?>" class="botao botao-secundario">Abrir pasta</a></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require __DIR__ . '/../../includes/rodape.php'; ?>
