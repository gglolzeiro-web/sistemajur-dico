<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/ia.php';
$usuario = exigirLogin();

$pdo = getConexao();
$id = (int) ($_GET['id'] ?? 0);

$stmt = $pdo->prepare(
    "SELECT c.*, r.titulo AS roteiro_titulo, r.conteudo AS roteiro_conteudo, u.nome AS atendente_nome
     FROM contatos c
     LEFT JOIN contatos_roteiros r ON r.id = c.roteiro_id
     LEFT JOIN usuarios u ON u.id = c.atendente_atual_id
     WHERE c.id = ?"
);
$stmt->execute([$id]);
$contato = $stmt->fetch();

if (!$contato) {
    http_response_code(404);
    die('Contato não encontrado.');
}

$erro = null;
$sucesso = null;
$sugestaoGerada = null;
$mensagemDigitada = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfVerificar();
    $acao = $_POST['acao'] ?? '';

    if ($acao === 'gerar_sugestao') {
        $mensagemDigitada = trim($_POST['mensagem_cliente'] ?? '');
        if ($mensagemDigitada === '') {
            $erro = 'Cole a mensagem do cliente antes de gerar a sugestão.';
        } else {
            $resultadoIa = gerarSugestaoResposta($mensagemDigitada, $contato['roteiro_conteudo'] ?? null);
            $sugestaoGerada = $resultadoIa['texto'];
            if (!$resultadoIa['sucesso']) {
                $erro = 'Não foi possível gerar com IA — segue um modelo para você editar.';
            }
        }
    } elseif ($acao === 'salvar_mensagem') {
        $mensagemDigitada = trim($_POST['mensagem_cliente'] ?? '');
        $respostaFinal = trim($_POST['resposta_utilizada'] ?? '');
        $respostaSugerida = trim($_POST['resposta_sugerida_ia'] ?? '') ?: null;

        if ($mensagemDigitada === '' || $respostaFinal === '') {
            $erro = 'Preencha a mensagem do cliente e a resposta antes de registrar.';
        } else {
            $stmt = $pdo->prepare(
                'INSERT INTO contatos_mensagens (contato_id, mensagem_cliente, resposta_sugerida_ia, resposta_utilizada, usuario_id) VALUES (?,?,?,?,?)'
            );
            $stmt->execute([$id, $mensagemDigitada, $respostaSugerida, $respostaFinal, $usuario['id']]);

            if ($contato['status'] === 'novo') {
                $pdo->prepare("UPDATE contatos SET status = 'enviado', atendente_atual_id = ? WHERE id = ?")->execute([$usuario['id'], $id]);
                registrarAuditoriaContato($id, 'enviou_primeira_mensagem');
                registrarAuditoriaContato($id, 'mudou_status', 'novo → enviado');
                $contato['status'] = 'enviado';
            } else {
                registrarAuditoriaContato($id, 'respondeu_cliente');
            }
            $sucesso = 'Resposta registrada na pasta.';
        }
    } elseif ($acao === 'mudar_status') {
        $novoStatus = $_POST['novo_status'] ?? '';
        $transicoesValidas = ['enviado' => ['convertido', 'recusado'], 'novo' => ['enviado']];
        if (in_array($novoStatus, $transicoesValidas[$contato['status']] ?? [], true)) {
            $pdo->prepare('UPDATE contatos SET status = ? WHERE id = ?')->execute([$novoStatus, $id]);
            registrarAuditoriaContato($id, 'mudou_status', $contato['status'] . ' → ' . $novoStatus);
            $contato['status'] = $novoStatus;
            $sucesso = 'Status atualizado para ' . ucfirst($novoStatus) . '.';
        } else {
            $erro = 'Transição de status inválida.';
        }
    } elseif ($acao === 'converter_cliente' && $contato['status'] === 'convertido' && !$contato['cliente_id']) {
        $stmt = $pdo->prepare(
            'INSERT INTO clientes (nome, cpf_cnpj, email, telefone, contato_origem_id, criado_por) VALUES (?,?,?,?,?,?)'
        );
        $stmt->execute([$contato['nome'], $contato['cpf_cnpj'], $contato['email'], $contato['telefone'], $id, $usuario['id']]);
        $clienteId = (int) $pdo->lastInsertId();
        $pdo->prepare('UPDATE contatos SET cliente_id = ? WHERE id = ?')->execute([$clienteId, $id]);
        $contato['cliente_id'] = $clienteId;
        registrarAuditoriaContato($id, 'convertido_em_cliente', 'Cliente #' . $clienteId . ' criado');
        $sucesso = 'Cliente criado a partir desta pasta.';
    }
}

$mensagens = $pdo->prepare('SELECT m.*, u.nome AS usuario_nome FROM contatos_mensagens m LEFT JOIN usuarios u ON u.id = m.usuario_id WHERE contato_id = ? ORDER BY criado_em DESC');
$mensagens->execute([$id]);
$mensagens = $mensagens->fetchAll();

$auditoria = $pdo->prepare('SELECT a.*, u.nome AS usuario_nome FROM contatos_auditoria a LEFT JOIN usuarios u ON u.id = a.usuario_id WHERE contato_id = ? ORDER BY criado_em DESC');
$auditoria->execute([$id]);
$auditoria = $auditoria->fetchAll();

$rotulosAcao = [
    'cadastrou' => 'Cadastrou a pasta',
    'editou_dados' => 'Editou os dados do contato',
    'enviou_primeira_mensagem' => 'Enviou a primeira mensagem',
    'respondeu_cliente' => 'Respondeu ao cliente',
    'mudou_status' => 'Mudou o status',
    'convertido_em_cliente' => 'Converteu em cliente',
];

$tituloPagina = $contato['nome'];
$paginaAtiva = 'contatos';
require __DIR__ . '/../../includes/cabecalho.php';
?>

<div class="pagina-cabecalho">
    <a href="index.php" class="botao botao-secundario" style="margin-bottom: 14px;"><?= icone('arrow-left') ?> Voltar</a>
    <div style="display:flex; align-items:center; gap:14px; flex-wrap: wrap;">
        <h1 style="margin:0;"><?= htmlspecialchars($contato['nome']) ?></h1>
        <span class="badge badge-<?= htmlspecialchars($contato['status']) ?>" style="font-size:13px;"><?= ucfirst($contato['status']) ?></span>
    </div>
    <p>
        <?= htmlspecialchars($contato['telefone'] ?? 'sem telefone') ?>
        &middot; <?= htmlspecialchars($contato['email'] ?? 'sem e-mail') ?>
        &middot; atendente atual: <?= htmlspecialchars($contato['atendente_nome'] ?? '—') ?>
    </p>
</div>

<?php if ($erro): ?><div class="alerta alerta-erro"><?= htmlspecialchars($erro) ?></div><?php endif; ?>
<?php if ($sucesso): ?><div class="alerta alerta-sucesso"><?= htmlspecialchars($sucesso) ?></div><?php endif; ?>

<div class="barra-acoes">
    <div style="display:flex; gap:8px; flex-wrap: wrap;">
        <?php if ($contato['status'] === 'novo'): ?>
            <span style="color: var(--texto-suave); font-size: 13.5px; align-self:center;">Envie a primeira mensagem abaixo para mover para "Enviado".</span>
        <?php elseif ($contato['status'] === 'enviado'): ?>
            <form method="post"><?= csrfCampo() ?><input type="hidden" name="acao" value="mudar_status"><input type="hidden" name="novo_status" value="convertido"><button class="botao botao-primario"><?= icone('check') ?> Cliente convertido</button></form>
            <form method="post"><?= csrfCampo() ?><input type="hidden" name="acao" value="mudar_status"><input type="hidden" name="novo_status" value="recusado"><button class="botao botao-perigo">Cliente recusou</button></form>
        <?php elseif ($contato['status'] === 'convertido' && !$contato['cliente_id']): ?>
            <form method="post"><?= csrfCampo() ?><input type="hidden" name="acao" value="converter_cliente"><button class="botao botao-dourado">Criar registro em Clientes</button></form>
        <?php elseif ($contato['cliente_id']): ?>
            <a href="<?= BASE_URL ?>/modules/clientes/form.php?id=<?= $contato['cliente_id'] ?>" class="botao botao-secundario">Ver cliente convertido</a>
        <?php endif; ?>
    </div>
    <a href="form.php?id=<?= $contato['id'] ?>" class="botao botao-secundario"><?= icone('edit') ?> Editar dados</a>
</div>

<div class="card" style="margin-bottom: 20px;">
    <h3>Roteiro de conversa</h3>
    <?php if ($contato['roteiro_titulo']): ?>
        <p style="font-weight:600; margin-top:10px;"><?= htmlspecialchars($contato['roteiro_titulo']) ?></p>
        <p style="white-space: pre-line; color: var(--texto-suave);"><?= htmlspecialchars($contato['roteiro_conteudo']) ?></p>
    <?php else: ?>
        <p style="color: var(--texto-suave); margin-top:10px;">Nenhum roteiro vinculado. <a href="form.php?id=<?= $contato['id'] ?>" style="text-decoration:underline;">Escolher um roteiro</a>.</p>
    <?php endif; ?>
</div>

<div class="card" style="margin-bottom: 20px;">
    <h3>Resposta ao cliente</h3>
    <p style="color: var(--texto-suave); margin-top:6px; font-size:13.5px;">Cole a mensagem recebida do cliente, gere uma sugestão com IA, ajuste e registre.</p>
    <form method="post" style="margin-top: 14px; display:flex; flex-direction:column; gap:12px;">
        <?= csrfCampo() ?>
        <div class="campo">
            <label>Mensagem do cliente</label>
            <textarea name="mensagem_cliente"><?= htmlspecialchars($mensagemDigitada) ?></textarea>
        </div>
        <div style="display:flex; gap:8px;">
            <button type="submit" name="acao" value="gerar_sugestao" class="botao botao-secundario"><?= icone('sparkles') ?> Gerar sugestão com IA</button>
        </div>
        <div class="campo">
            <label>Resposta (edite antes de enviar ao cliente)</label>
            <textarea name="resposta_utilizada"><?= htmlspecialchars($sugestaoGerada ?? '') ?></textarea>
        </div>
        <input type="hidden" name="resposta_sugerida_ia" value="<?= htmlspecialchars($sugestaoGerada ?? '') ?>">
        <div>
            <button type="submit" name="acao" value="salvar_mensagem" class="botao botao-primario">Registrar resposta enviada</button>
        </div>
    </form>

    <?php if ($mensagens): ?>
        <h4 style="margin-top: 24px;">Histórico de mensagens</h4>
        <div class="lista-itens" style="margin-top: 10px;">
            <?php foreach ($mensagens as $m): ?>
                <div class="item-linha" style="border-left-color: var(--dourado); flex-direction: column; align-items: flex-start;">
                    <div class="item-sub"><?= date('d/m/Y H:i', strtotime($m['criado_em'])) ?> &middot; <?= htmlspecialchars($m['usuario_nome'] ?? '—') ?></div>
                    <div style="margin-top:6px;"><strong>Cliente:</strong> <?= nl2br(htmlspecialchars($m['mensagem_cliente'])) ?></div>
                    <div style="margin-top:6px;"><strong>Resposta:</strong> <?= nl2br(htmlspecialchars($m['resposta_utilizada'])) ?></div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<div class="card">
    <h3>Registro de auditoria</h3>
    <div class="timeline" style="margin-top: 14px;">
        <?php foreach ($auditoria as $a): ?>
            <div class="timeline-item">
                <div class="timeline-ponto"></div>
                <div class="timeline-texto">
                    <strong><?= htmlspecialchars($rotulosAcao[$a['acao']] ?? $a['acao']) ?></strong>
                    <span><?= htmlspecialchars($a['usuario_nome'] ?? 'Sistema') ?> &middot; <?= date('d/m/Y H:i', strtotime($a['criado_em'])) ?><?= $a['detalhes'] ? ' — ' . htmlspecialchars($a['detalhes']) : '' ?></span>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php require __DIR__ . '/../../includes/rodape.php'; ?>
