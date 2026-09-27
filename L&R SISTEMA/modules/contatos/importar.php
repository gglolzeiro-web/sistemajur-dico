<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/xlsx_leitor.php';
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
        $caminhoTemporario = $_FILES['planilha']['tmp_name'];
        $extensao = strtolower(pathinfo($nomeOriginal, PATHINFO_EXTENSION));

        try {
            $todasLinhas = match ($extensao) {
                'csv' => lerCsvComoLinhas($caminhoTemporario),
                'xlsx' => lerXlsxComoLinhas($caminhoTemporario),
                default => throw new RuntimeException('Formato não suportado. Envie um arquivo .xlsx ou .csv.'),
            };
        } catch (RuntimeException $e) {
            $erro = $e->getMessage();
            $todasLinhas = [];
        }

        if (!$erro) {
            if (!$todasLinhas) {
                $erro = 'A planilha está vazia.';
            } else {
                $cabecalho = array_map(fn ($c) => mb_strtolower(trim((string) $c)), array_shift($todasLinhas));
                $indiceNome = array_search('nome', $cabecalho, true);
                $indiceCpf = array_search('cpf_cnpj', $cabecalho, true);
                $indiceEmail = array_search('email', $cabecalho, true);
                $indiceTelefone = array_search('telefone', $cabecalho, true);

                if ($indiceNome === false) {
                    $erro = 'A planilha precisa ter uma coluna "nome". Colunas aceitas: nome, cpf_cnpj, email, telefone.';
                } else {
                    $importados = 0;
                    $ignorados = 0;

                    $pdo->beginTransaction();
                    $stmtInserir = $pdo->prepare(
                        'INSERT INTO contatos (nome, cpf_cnpj, email, telefone, origem, atendente_atual_id, criado_por) VALUES (?,?,?,?,\'planilha\',?,?)'
                    );

                    foreach ($todasLinhas as $linha) {
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
            }
        }
    }
}

/** Lê um .csv (; ou , como separador) e devolve linhas no mesmo formato do leitor de .xlsx. */
function lerCsvComoLinhas(string $caminhoArquivo): array
{
    $alca = fopen($caminhoArquivo, 'r');
    if ($alca === false) {
        throw new RuntimeException('Não foi possível ler o arquivo enviado.');
    }

    $primeiraLinha = fgetcsv($alca, 0, ';');
    $separador = ';';
    if ($primeiraLinha !== false && count($primeiraLinha) === 1) {
        rewind($alca);
        $primeiraLinha = fgetcsv($alca, 0, ',');
        $separador = ',';
    }

    $linhas = [];
    if ($primeiraLinha !== false) {
        $linhas[] = $primeiraLinha;
    }
    while (($linha = fgetcsv($alca, 0, $separador)) !== false) {
        $linhas[] = $linha;
    }
    fclose($alca);

    return $linhas;
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
        Formatos aceitos: <strong>.xlsx</strong> (Excel) ou <strong>.csv</strong>, com colunas
        <code>nome</code> (obrigatória), <code>cpf_cnpj</code>, <code>email</code>, <code>telefone</code>
        na primeira linha.
    </div>
    <form method="post" enctype="multipart/form-data" style="margin-top: 16px;">
        <?= csrfCampo() ?>
        <div class="campo">
            <label>Arquivo .xlsx ou .csv</label>
            <input type="file" name="planilha" accept=".xlsx,.csv" required>
        </div>
        <button type="submit" class="botao botao-primario" style="margin-top: 16px;"><?= icone('upload') ?> Importar</button>
    </form>
</div>

<?php require __DIR__ . '/../../includes/rodape.php'; ?>
