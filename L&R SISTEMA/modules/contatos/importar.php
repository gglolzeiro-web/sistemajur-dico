<?php
require_once __DIR__ . '/../../includes/auth.php';
$usuario = exigirLogin();

$pdo = getConexao();
$erro = null;
$resultado = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfVerificar();

    if (empty($_FILES['planilha']['tmp_name']) || $_FILES['planilha']['error'] !== UPLOAD_ERR_OK) {
        $erro = 'Selecione um arquivo válido.';
    } else {
        $nomeOriginal = $_FILES['planilha']['name'];
        $extensao = strtolower(pathinfo($nomeOriginal, PATHINFO_EXTENSION));

        if ($extensao !== 'csv') {
            $erro = 'Por enquanto, importe a planilha como .csv (no Excel: Arquivo > Salvar como > CSV UTF-8). '
                . 'Suporte a .xlsx direto exige uma biblioteca adicional — posso adicionar depois, se você quiser.';
        } else {
            $linhas = 0;
            $importados = 0;
            $ignorados = 0;

            if (($alca = fopen($_FILES['planilha']['tmp_name'], 'r')) !== false) {
                $cabecalho = fgetcsv($alca, 0, ';');
                if ($cabecalho !== false && count($cabecalho) === 1) {
                    // Arquivo separado por vírgula em vez de ponto-e-vírgula.
                    rewind($alca);
                    $cabecalho = fgetcsv($alca, 0, ',');
                    $separador = ',';
                } else {
                    $separador = ';';
                }
                $cabecalho = array_map(fn ($c) => mb_strtolower(trim($c)), $cabecalho ?: []);
                $indiceNome = array_search('nome', $cabecalho, true);
                $indiceCpf = array_search('cpf_cnpj', $cabecalho, true);
                $indiceEmail = array_search('email', $cabecalho, true);
                $indiceTelefone = array_search('telefone', $cabecalho, true);

                if ($indiceNome === false) {
                    $erro = 'A planilha precisa ter uma coluna "nome". Colunas aceitas: nome, cpf_cnpj, email, telefone.';
                } else {
                    $pdo->beginTransaction();
                    $stmtInserir = $pdo->prepare(
                        'INSERT INTO contatos (nome, cpf_cnpj, email, telefone, origem, atendente_atual_id, criado_por) VALUES (?,?,?,?,\'planilha\',?,?)'
                    );

                    while (($linha = fgetcsv($alca, 0, $separador)) !== false) {
                        $linhas++;
                        $nome = trim($linha[$indiceNome] ?? '');
                        if ($nome === '') {
                            $ignorados++;
                            continue;
                        }
                        $stmtInserir->execute([
                            $nome,
                            $indiceCpf !== false ? trim($linha[$indiceCpf] ?? '') : null,
                            $indiceEmail !== false ? trim($linha[$indiceEmail] ?? '') : null,
                            $indiceTelefone !== false ? trim($linha[$indiceTelefone] ?? '') : null,
                            $usuario['id'],
                            $usuario['id'],
                        ]);
                        registrarAuditoriaContato((int) $pdo->lastInsertId(), 'cadastrou', 'Importado via planilha');
                        $importados++;
                    }

                    $pdo->commit();
                    $resultado = "Importação concluída: {$importados} contato(s) criado(s), {$ignorados} linha(s) ignorada(s) por falta de nome.";
                }
                fclose($alca);
            } else {
                $erro = 'Não foi possível ler o arquivo enviado.';
            }
        }
    }
}

$tituloPagina = 'Importar contatos';
$paginaAtiva = 'contatos';
require __DIR__ . '/../../includes/cabecalho.php';
?>

<div class="pagina-cabecalho">
    <a href="index.php" class="botao botao-secundario" style="margin-bottom: 14px;"><?= icone('arrow-left') ?> Voltar</a>
    <h1>Importar contatos por planilha</h1>
    <p>Cada linha vira uma pasta de contato nova, com status "Novo".</p>
</div>

<?php if ($erro): ?><div class="alerta alerta-erro"><?= htmlspecialchars($erro) ?></div><?php endif; ?>
<?php if ($resultado): ?><div class="alerta alerta-sucesso"><?= htmlspecialchars($resultado) ?></div><?php endif; ?>

<div class="card">
    <div class="alerta alerta-info">
        Formato aceito no momento: <strong>.csv</strong> com colunas <code>nome</code> (obrigatória),
        <code>cpf_cnpj</code>, <code>email</code>, <code>telefone</code>. No Excel, use
        "Arquivo &gt; Salvar como &gt; CSV UTF-8 (separado por vírgulas)". Se preferir enviar o .xlsx original
        direto, dá para adicionar isso depois com uma biblioteca de planilha — me avise se quiser.
    </div>
    <form method="post" enctype="multipart/form-data" style="margin-top: 16px;">
        <?= csrfCampo() ?>
        <div class="campo">
            <label>Arquivo .csv</label>
            <input type="file" name="planilha" accept=".csv" required>
        </div>
        <button type="submit" class="botao botao-primario" style="margin-top: 16px;"><?= icone('upload') ?> Importar</button>
    </form>
</div>

<?php require __DIR__ . '/../../includes/rodape.php'; ?>
