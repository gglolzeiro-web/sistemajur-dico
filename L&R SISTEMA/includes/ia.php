<?php
/**
 * Geração de rascunho de resposta ao cliente via IA (Anthropic Claude).
 * Se ANTHROPIC_API_KEY não estiver configurada, devolve um modelo padrão
 * editável em vez de falhar — o atendente sempre tem algo para trabalhar.
 */

function gerarSugestaoResposta(string $mensagemCliente, ?string $contextoRoteiro = null): array
{
    if (ANTHROPIC_API_KEY === '') {
        return [
            'sucesso' => false,
            'texto' => "Olá! Obrigado por sua mensagem. Recebemos o que você enviou e já vamos te dar um retorno "
                . "com mais detalhes sobre os próximos passos. Qualquer dúvida, estamos à disposição.\n\n"
                . '[IA não configurada — edite este modelo antes de enviar. Configure ANTHROPIC_API_KEY em config/app.php para gerar sugestões reais.]',
        ];
    }

    $prompt = "Você é um assistente de atendimento de um escritório de advocacia. "
        . "Com base na mensagem do cliente abaixo, escreva uma resposta breve, cordial e profissional em português do Brasil. "
        . "Não prometa resultado de processo nem dê aconselhamento jurídico específico — apenas conduza o atendimento.\n\n"
        . ($contextoRoteiro ? "Roteiro de referência do escritório:\n{$contextoRoteiro}\n\n" : '')
        . "Mensagem do cliente:\n{$mensagemCliente}";

    $payload = json_encode([
        'model' => 'claude-sonnet-5',
        'max_tokens' => 400,
        'messages' => [['role' => 'user', 'content' => $prompt]],
    ]);

    $ch = curl_init('https://api.anthropic.com/v1/messages');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'content-type: application/json',
            'x-api-key: ' . ANTHROPIC_API_KEY,
            'anthropic-version: 2023-06-01',
        ],
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_TIMEOUT => 20,
    ]);
    $resposta = curl_exec($ch);
    $erroCurl = curl_error($ch);
    curl_close($ch);

    if ($erroCurl || !$resposta) {
        error_log('Falha ao chamar IA: ' . $erroCurl);
        return ['sucesso' => false, 'texto' => 'Não foi possível gerar a sugestão agora. Tente novamente em instantes.'];
    }

    $dados = json_decode($resposta, true);
    $texto = $dados['content'][0]['text'] ?? null;

    if (!$texto) {
        return ['sucesso' => false, 'texto' => 'A IA não retornou uma sugestão válida. Tente novamente.'];
    }

    return ['sucesso' => true, 'texto' => trim($texto)];
}
