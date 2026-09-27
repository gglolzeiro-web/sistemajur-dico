<?php
/**
 * Leitor mínimo de planilhas .xlsx, sem dependências externas (usa apenas
 * as extensões Zip e SimpleXML, nativas do PHP e habilitadas por padrão
 * em qualquer hospedagem cPanel/HostGator). Não usa Composer nem
 * bibliotecas de terceiros — só a primeira aba da planilha é lida.
 */

class LeitorXlsxException extends RuntimeException {}

/**
 * Lê a primeira planilha de um arquivo .xlsx e devolve suas linhas como
 * um array de arrays de strings (célula vazia = ''), na mesma ordem de
 * colunas do arquivo original — compatível com o formato que fgetcsv()
 * devolveria.
 */
function lerXlsxComoLinhas(string $caminhoArquivo): array
{
    if (!class_exists('ZipArchive')) {
        throw new LeitorXlsxException('A extensão PHP "zip" não está habilitada neste servidor. Ative-a no cPanel (Select PHP Version > Extensões) ou importe a planilha como .csv.');
    }

    $zip = new ZipArchive();
    if ($zip->open($caminhoArquivo) !== true) {
        throw new LeitorXlsxException('Não foi possível abrir o arquivo .xlsx enviado.');
    }

    $sharedStrings = lerSharedStrings($zip);
    $caminhoAba = resolverPrimeiraAba($zip);

    $xmlAba = $zip->getFromName($caminhoAba);
    if ($xmlAba === false) {
        $zip->close();
        throw new LeitorXlsxException('Não foi possível localizar a primeira aba dentro da planilha.');
    }

    $linhas = [];
    $sxAba = @simplexml_load_string($xmlAba);
    $zip->close();

    if ($sxAba === false) {
        throw new LeitorXlsxException('O conteúdo da planilha está corrompido ou em um formato inesperado.');
    }

    foreach ($sxAba->sheetData->row as $linhaXml) {
        $linha = [];
        $maiorIndice = -1;

        foreach ($linhaXml->c as $celula) {
            $referencia = (string) $celula['r'];
            $indiceColuna = referenciaParaIndiceColuna($referencia);
            $tipo = (string) $celula['t'];

            if ($tipo === 's') {
                $indiceShared = (int) $celula->v;
                $valor = $sharedStrings[$indiceShared] ?? '';
            } elseif ($tipo === 'inlineStr') {
                $valor = (string) ($celula->is->t ?? '');
            } elseif ($tipo === 'str' || $tipo === 'b' || $tipo === '') {
                $valor = (string) $celula->v;
            } else {
                $valor = (string) $celula->v;
            }

            $linha[$indiceColuna] = trim($valor);
            $maiorIndice = max($maiorIndice, $indiceColuna);
        }

        // Preenche eventuais colunas vazias no meio da linha.
        $linhaCompleta = [];
        for ($i = 0; $i <= $maiorIndice; $i++) {
            $linhaCompleta[$i] = $linha[$i] ?? '';
        }

        $linhas[] = $linhaCompleta;
    }

    return $linhas;
}

function lerSharedStrings(ZipArchive $zip): array
{
    $xml = $zip->getFromName('xl/sharedStrings.xml');
    if ($xml === false) {
        return [];
    }

    $sx = @simplexml_load_string($xml);
    if ($sx === false) {
        return [];
    }

    $lista = [];
    foreach ($sx->si as $si) {
        if (isset($si->t)) {
            $lista[] = (string) $si->t;
        } else {
            // Texto com formatação em runs (<r><t>...</t></r>).
            $texto = '';
            foreach ($si->r as $run) {
                $texto .= (string) $run->t;
            }
            $lista[] = $texto;
        }
    }

    return $lista;
}

function resolverPrimeiraAba(ZipArchive $zip): string
{
    $workbookXml = $zip->getFromName('xl/workbook.xml');
    $relsXml = $zip->getFromName('xl/_rels/workbook.xml.rels');

    if ($workbookXml !== false && $relsXml !== false) {
        $sxWorkbook = @simplexml_load_string($workbookXml);
        $sxRels = @simplexml_load_string($relsXml);

        if ($sxWorkbook !== false && $sxRels !== false && isset($sxWorkbook->sheets->sheet[0])) {
            $primeiraAba = $sxWorkbook->sheets->sheet[0];
            $rId = (string) $primeiraAba->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships')['id'];

            foreach ($sxRels->Relationship as $relacao) {
                if ((string) $relacao['Id'] === $rId) {
                    $alvo = ltrim((string) $relacao['Target'], '/');
                    return str_starts_with($alvo, 'worksheets') ? 'xl/' . $alvo : $alvo;
                }
            }
        }
    }

    // Fallback razoável quando a resolução via workbook falha.
    return 'xl/worksheets/sheet1.xml';
}

function referenciaParaIndiceColuna(string $referenciaCelula): int
{
    preg_match('/^([A-Z]+)/', $referenciaCelula, $m);
    $letras = $m[1] ?? 'A';
    $indice = 0;
    foreach (str_split($letras) as $letra) {
        $indice = $indice * 26 + (ord($letra) - ord('A') + 1);
    }
    return $indice - 1;
}
