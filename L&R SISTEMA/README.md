# L&R Sistema

Sistema de gestão jurídica (prazos, processos, clientes e captação de contatos),
em PHP puro + MySQL, pronto para hospedagem compartilhada (HostGator/cPanel).

## Estrutura

```
L&R SISTEMA/
├── config/          # conexão com banco e configurações gerais (NÃO fica acessível pelo navegador)
├── includes/        # autenticação, layout (sidebar/cabeçalho), ícones, IA
├── database/         # schema.sql — script de criação do banco
├── modules/          # um diretório por aba do sistema
│   ├── painel/
│   ├── prazos/
│   ├── processos/
│   ├── clientes/
│   ├── contatos/      # captação de clientes (planilha/manual, pasta, status, IA)
│   ├── publicacoes/
│   ├── tipos_prazo/
│   ├── usuarios/
│   └── meus_dados/
├── assets/           # css/js
├── login.php / logout.php / index.php
└── .htaccess
```

## Passo a passo de publicação no HostGator

### 1. Criar o banco de dados
No cPanel: **Bancos de Dados MySQL**
1. Crie um banco (ex: `seuusuario_lr`).
2. Crie um usuário MySQL e uma senha forte.
3. Associe o usuário ao banco com **todos os privilégios**.

### 2. Importar o schema
No cPanel: **phpMyAdmin**
1. Selecione o banco criado.
2. Aba **Importar** → selecione o arquivo `database/schema.sql` deste projeto.
3. Execute. Isso cria todas as tabelas e um usuário admin inicial:
   - E-mail: `admin@lr.adv.br`
   - Senha: `trocaresta123` — **troque assim que entrar pela primeira vez**, em "Meus Dados".

### 3. Configurar a conexão
Edite `config/database.php` com os dados gerados no passo 1:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'seuusuario_lr');
define('DB_USER', 'seuusuario_lrapp');
define('DB_PASS', 'sua-senha-aqui');
```

### 4. Enviar os arquivos
Via FTP (FileZilla) ou o Gerenciador de Arquivos do cPanel, envie **todo o conteúdo** desta
pasta (`L&R SISTEMA/`) para dentro de `public_html/` (ou uma subpasta, se o sistema for
rodar em `seudominio.com/sistema`).

### 5. Testar
Acesse `https://seudominio.com/` (ou a subpasta escolhida) e faça login com o usuário admin.

## Sobre a geração de resposta por IA (aba Contatos)

O botão "Gerar sugestão com IA" chama a API da Anthropic. Sem uma chave configurada, ele
mostra um modelo de resposta padrão editável (não trava o uso). Para ativar a geração real:

1. Crie uma chave em https://console.anthropic.com
2. Edite `config/app.php` e preencha `ANTHROPIC_API_KEY`.

## Sobre publicações do Diário Oficial

O módulo Publicações já suporta cadastro (manual por enquanto) com vínculo automático ao
processo pelo número CNJ. A captura automática direto do Diário Oficial (DJEN/CNJ ou um
serviço terceirizado) ainda não está conectada — depende de liberar acesso de rede e
credenciais de API, que devem ser configuradas junto com você antes de ativar.

## Sobre a importação de planilha em Contatos

A importação aceita arquivos **.csv** (colunas: `nome`, `cpf_cnpj`, `email`, `telefone`).
Suporte a `.xlsx` nativo do Excel exigiria uma biblioteca adicional (ex: PhpSpreadsheet),
que não foi incluída para manter a hospedagem compartilhada simples (sem Composer/CLI).
No Excel: "Arquivo > Salvar como > CSV UTF-8".

## Segurança

- Sessões protegidas por `httponly` + `SameSite`.
- Senhas com hash bcrypt (`password_hash`).
- Formulários protegidos contra CSRF (token de sessão).
- `config/`, `includes/` e `database/` bloqueados para acesso direto via `.htaccess`.
- Consultas ao banco sempre via *prepared statements* (PDO).
