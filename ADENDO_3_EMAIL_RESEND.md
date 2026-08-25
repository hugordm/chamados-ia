# Adendo 3 — Recuperação de senha e notificações por e-mail (Resend)

Complementa ADENDO_AUTENTICACAO.md e ADENDO_2_CADASTRO_USUARIOS.md. Adiciona uma
integração de e-mail (Resend), usada em dois fluxos independentes: (A) recuperação
de senha e (B) notificação de chamado.

## 1. Nova integração — src/Integrations/EmailClient.php

Mesmo padrão do AnthropicClient/FirecrawlClient: isola o provedor externo, com
fallback seguro (se o envio falhar, o sistema continua funcionando — só loga o erro,
nunca quebra a ação principal do usuário).

```php
enviar(string $paraEmail, string $paraNome, string $assunto, string $corpoHtml): bool
// POST https://api.resend.com/emails
// Header: Authorization: Bearer {RESEND_API_KEY}
// Body: { "from": RESEND_FROM_EMAIL, "to": [paraEmail], "subject": assunto, "html": corpoHtml }
// Retorna true se o Resend respondeu 200, false em qualquer erro (sem lançar exceção
// — quem chama decide se quer registrar o erro em log, mas o fluxo principal nunca
// deve travar por causa de e-mail).
```

## 2. .env — novas variáveis

```
RESEND_API_KEY=re_xxxxxxxxxxxxxxxxxxxx
RESEND_FROM_EMAIL=chamados@seudominio.com
```

(No `.env.example`, deixar como placeholder — nunca comitar a chave real.)

## 3. Fluxo A — Recuperação de senha

### 3.1 Nova tabela

```sql
CREATE TABLE IF NOT EXISTS tokens_redefinicao_senha (
    id          SERIAL PRIMARY KEY,
    usuario_id  INTEGER NOT NULL REFERENCES usuarios(id) ON DELETE CASCADE,
    token_hash  VARCHAR(255) NOT NULL,
    expira_em   TIMESTAMP NOT NULL,
    usado_em    TIMESTAMP,
    criado_em   TIMESTAMP NOT NULL DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_tokens_usuario ON tokens_redefinicao_senha(usuario_id);
```

Importante: o token NUNCA é guardado em texto puro no banco — só o hash dele
(`hash('sha256', $tokenBruto)`), o mesmo princípio da senha. O token bruto só existe
no link que vai por e-mail.

### 3.2 Novos arquivos

```
public/esqueci_senha.php     (NOVO)
public/redefinir_senha.php   (NOVO)
```

### 3.3 public/esqueci_senha.php

Formulário com um campo: e-mail. No POST:
1. Busca o usuário pelo e-mail;
2. **Independente de ter encontrado ou não**, mostra a mesma mensagem de sucesso:
   "Se esse e-mail estiver cadastrado, você vai receber um link de redefinição."
   (Isso evita a mesma técnica de enumeração que já discutimos no login — não dá
   pra confirmar por aqui se um e-mail existe no sistema ou não.)
3. Se o usuário existir de fato: gera um token aleatório (`bin2hex(random_bytes(32))`),
   salva o **hash** dele em `tokens_redefinicao_senha` com `expira_em = NOW() + 1 hora`,
   e chama `EmailClient::enviar()` com um link tipo
   `https://seu-dominio/redefinir_senha.php?token={tokenBruto}`.

### 3.4 public/redefinir_senha.php

Lê `$_GET['token']`. No carregamento da página:
1. Calcula o hash do token recebido e busca na tabela;
2. Se não encontrar, se já estiver `usado_em` preenchido, ou se `expira_em` já
   passou: mostra mensagem de erro genérica ("Link inválido ou expirado, solicite
   um novo") e não mostra formulário nenhum;
3. Se válido: mostra formulário de nova senha (com o campo de "mostrar senha" que
   já implementamos). No POST, atualiza `senha_hash` do usuário, marca o token como
   usado (`usado_em = NOW()`), e redireciona pro login com mensagem de sucesso.

### 3.5 Link na tela de login

Adicionar "Esqueci minha senha" em `public/login.php`, apontando pra
`esqueci_senha.php`.

## 4. Fluxo B — Notificação de chamado por e-mail

Dois pontos de disparo, ambos dentro do `ChamadoService` (não no Controller — é
regra de negócio, não apresentação):

### 4.1 Ao abrir um chamado (`abrirChamado`)

Depois de criar o chamado com sucesso, enviar e-mail para o **solicitante**
(usando o e-mail do `usuarios`, via `usuario_id`) confirmando a abertura:
- Assunto: "Chamado #{id} aberto — {titulo}"
- Corpo: confirma o recebimento, mostra a categoria/prioridade sugeridas pela IA
  e a sugestão inicial, e informa que o time de TI vai acompanhar.

### 4.2 A cada mudança de status (`atualizarStatus`)

Enviar e-mail para o solicitante em toda transição de status, com assunto e corpo
adaptados ao novo status:

| Novo status | Assunto | Corpo |
|---|---|---|
| `Em Andamento` | "Chamado #{id} está em andamento" | Confirma que o time de TI já está atuando no problema, mostra o título do chamado. |
| `Resolvido` | "Chamado #{id} foi resolvido" | Confirma a resolução, mostra o título do chamado, e convida a abrir um novo chamado caso o problema volte. |

Importante: só dispara e-mail quando o status **realmente muda** (compare o status
anterior com o novo antes de enviar) — se o agente clicar em "Atualizar" sem trocar
o valor do select, ou se por algum motivo o mesmo status for salvo de novo, não
deve gerar e-mail duplicado.

O e-mail de "Chamado #{id} foi aberto" já está coberto no item 4.1
(`abrirChamado`), então o status inicial `Aberto` não precisa de lógica adicional
aqui — as três mensagens juntas (aberto → em andamento → resolvido) fecham o
acompanhamento completo do ciclo de vida do chamado.

## 5. O que NÃO fazer nesta etapa

- Não montar um sistema de templates de e-mail sofisticado — HTML simples e direto
  já resolve (`<h2>`, `<p>`, um link estilizado);
- Não implementar limite de tentativas para `esqueci_senha.php` ainda (alguém
  poderia forçar o envio de e-mails repetidos pro mesmo endereço) — anotar como
  melhoria futura, mesmo padrão do rate-limit de login já pendente;
- Não notificar o agente por e-mail quando um chamado novo é aberto nesta etapa —
  fica como extensão futura opcional, já que o dashboard já cumpre esse papel hoje.

## 6. Teste manual

1. Em `login.php`, clicar em "Esqueci minha senha", digitar o e-mail da Marina;
2. Confirmar que chegou um e-mail real (verificar a caixa de entrada real, já que
   agora tem uma chave de API de verdade);
3. Clicar no link do e-mail, definir uma senha nova, confirmar que consegue logar
   com a senha nova (e que a antiga não funciona mais);
4. Tentar reabrir o mesmo link do e-mail de novo — deve dizer "link inválido";
5. Testar um link com token inventado (não existente) — mesma mensagem genérica,
   sem detalhar o motivo;
6. Abrir um chamado novo pelo portal do cliente e confirmar que chegou o e-mail
   de confirmação de abertura;
7. Como agente, mudar o status desse chamado pra "Em Andamento" e confirmar que
   chegou o e-mail correspondente; em seguida, mudar pra "Resolvido" e confirmar
   o segundo e-mail. Depois, clicar em "Atualizar" de novo SEM trocar o status
   (mesmo valor "Resolvido") e confirmar que NÃO chega um terceiro e-mail
   duplicado.
