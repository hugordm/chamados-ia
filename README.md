# Central de Chamados de TI com IA

Sistema de abertura e acompanhamento de chamados de suporte técnico interno, com IA analisando cada chamado desde a abertura.

## 🔗 Acesse online

- **Aplicação**: [chamados-ia.onrender.com](https://chamados-ia.onrender.com)
- **Documentação da API (Swagger)**: [chamados-ia.onrender.com/docs/](https://chamados-ia.onrender.com/docs/)
- **Repositório**: [github.com/hugordm/chamados-ia](https://github.com/hugordm/chamados-ia)

## Contexto

É um projeto de portfólio/estudo que simula um service desk de TI real. Funcionários (papel `cliente`) abrem chamados descrevendo um problema; o time de TI (papel `agente`) acompanha, prioriza e resolve. A IA entra logo na abertura, sugerindo categoria, prioridade e uma primeira solução, e busca artigos relacionados na web para agilizar o atendimento.

## Funcionalidades

- Abertura de chamado com análise automática via IA (Anthropic/Claude): categoria, prioridade e sugestão inicial
- Busca de artigos relacionados na web (Firecrawl) anexados ao chamado
- Dois portais separados por papel (`cliente` / `agente`), cada um com seu próprio controle de acesso
- Cadastro de novos funcionários (cliente ou agente) feito pelo próprio time de TI
- Recuperação de senha por e-mail, com token de uso único e expiração
- Notificação por e-mail a cada mudança de status do chamado (aberto → em andamento → resolvido)
- Documentação de API via Swagger/OpenAPI
- Testes automatizados (PHPUnit) e pipeline de CI

## Destaques técnicos

- **Camadas separadas** (Controller → Service → Repository) para a regra de negócio de chamados, sem framework — o fluxo de dados fica explícito, sem mágica escondida.
- **PDO com prepared statements**, sem ORM — escolha deliberada para manter o SQL visível e sob controle, não por limitação da stack.
- **Prevenção de IDOR**: um cliente não consegue abrir o chamado de outro usuário pela URL — a checagem de propriedade retorna 404, sem revelar que o chamado existe.
- **Senhas com hash bcrypt**; tokens de redefinição de senha também são armazenados só como hash (SHA-256) — o token em texto puro existe apenas no link enviado por e-mail, nunca no banco.
- **Mensagens de erro genéricas** em login e recuperação de senha ("e-mail ou senha incorretos", "se esse e-mail estiver cadastrado...") — evita que alguém descubra por tentativa e erro quais e-mails estão cadastrados.
- **Fallback seguro nas integrações externas**: se a Anthropic, o Firecrawl ou o Resend falharem, o chamado ainda é criado e o fluxo do usuário não trava — o erro só é registrado em log.

## Stack

| Camada | Tecnologia |
|---|---|
| Linguagem | PHP 8.2 |
| Banco de dados | PostgreSQL 16 |
| Frontend | PHP server-side + Tailwind CSS (via CDN) + JS vanilla |
| IA — análise de chamado | Anthropic API (Claude) |
| IA — busca de artigos | Firecrawl |
| E-mail transacional | Resend |
| Containerização | Docker + Docker Compose |
| Documentação de API | Swagger / OpenAPI 3.0 |
| Testes | PHPUnit |
| CI/CD | GitHub Actions |
| Banco de dados (produção) | Neon (PostgreSQL gerenciado, serverless) |
| Hospedagem (produção) | Render (deploy via Docker, a partir do `Dockerfile` do próprio repositório) |

Desenvolvimento local usa o PostgreSQL do Docker Compose; produção usa o Neon — são dois bancos diferentes, não a mesma instância.

## Como rodar localmente

```bash
git clone <url-do-repositorio>
cd chamados-ia
cp .env.example .env
# preencha ANTHROPIC_API_KEY, FIRECRAWL_API_KEY, RESEND_API_KEY e RESEND_FROM_EMAIL no .env
docker compose up -d   # ou "docker-compose up -d" em versões mais antigas do Docker Compose
```

- Aplicação: [http://localhost:8080](http://localhost:8080)
- Swagger UI: [http://localhost:8081](http://localhost:8081) — localmente roda como container separado (`swagger-ui`, só no `docker-compose.yml`); em produção (onde não existe esse segundo serviço) a mesma documentação é servida como página estática em `/docs/`, direto pela aplicação.
- PostgreSQL fica exposto em `localhost:5434` (mapeado nessa porta no `docker-compose.yml` para não conflitar com um Postgres local já rodando na 5432 — ajuste se quiser outra).

Sem preencher as chaves de API o sistema continua funcionando normalmente (graças ao fallback das integrações), só que sem a análise real da IA, sem busca de artigos e sem envio de e-mails.

## Usuários de demonstração

O `sql/seed.sql` cria dois usuários de teste, um de cada papel:

- `marina@empresa.com` — papel `cliente`
- `hugo@empresa.com` — papel `agente`

A senha de ambos está comentada no próprio `sql/seed.sql` (não é repetida aqui por ser um repositório público) — troque-a antes de qualquer uso além de teste local.

## Rodando os testes

```bash
docker compose exec app vendor/bin/phpunit
```

## Estrutura de pastas

```
chamados-ia/
├── .env.example                              # modelo de variáveis de ambiente, sem segredos
├── .gitignore                                # ignora .env, vendor/ e cache de testes
├── composer.json                             # dependências PHP e autoload PSR-4
├── composer.lock                             # versões travadas das dependências
├── docker-compose.yml                        # orquestra os containers app, db e swagger-ui
├── Dockerfile                                # imagem PHP 8.2 + Apache da aplicação
├── phpunit.xml                                # configuração da suíte de testes
├── .github/
│   └── workflows/
│       └── ci.yml                            # roda os testes e builda a imagem a cada push/PR
├── docs/
│   └── openapi.yaml                          # especificação da API consumida pelo Swagger UI
├── sql/
│   ├── schema.sql                            # cria todas as tabelas, índices e chaves estrangeiras
│   └── seed.sql                              # usuários de teste (um cliente, um agente)
├── config/
│   ├── auth.php                              # sessão, exigir_login(), exigir_papel()
│   ├── database.php                          # conexão PDO com o PostgreSQL
│   └── env.php                               # lê o .env e registra as variáveis de ambiente
├── src/
│   ├── Integrations/
│   │   ├── AnthropicClient.php               # chama a API do Claude, com fallback em caso de erro
│   │   ├── EmailClient.php                   # envia e-mails transacionais via Resend
│   │   └── FirecrawlClient.php               # busca artigos relacionados ao problema na web
│   ├── Repositories/
│   │   ├── ChamadoRepository.php             # SQL de chamados e artigos sugeridos
│   │   ├── TokenRedefinicaoSenhaRepository.php  # SQL dos tokens de redefinição de senha
│   │   └── UsuarioRepository.php             # SQL de usuários, autenticação e hash de senha
│   ├── Services/
│   │   └── ChamadoService.php                # orquestra validação, IA, e-mail e persistência
│   └── Validation/
│       ├── ChamadoValidator.php              # valida os dados do formulário de chamado
│       └── ValidacaoException.php            # exceção carregando a lista de erros de validação
├── tests/
│   ├── AuthTest.php                          # testa a comparação de papel usada em exigir_papel()
│   ├── ChamadoServiceTest.php                # testa abertura de chamado e mudança de status
│   └── UsuarioRepositoryTest.php             # testa autenticação com PDO mockado
└── public/
    ├── index.php                             # redireciona pro login ou pro portal do papel logado
    ├── login.php                             # tela de login
    ├── logout.php                            # encerra a sessão
    ├── esqueci_senha.php                     # solicita o link de redefinição de senha
    ├── redefinir_senha.php                   # define a nova senha a partir do token recebido
    ├── openapi.yaml                          # symlink pra docs/openapi.yaml (serve em produção)
    ├── docs/
    │   └── index.html                        # Swagger UI estático, via CDN, lendo /openapi.yaml
    ├── includes/
    │   ├── header.php                        # topo comum, navegação por papel, helpers de estilo
    │   └── footer.php                        # rodapé comum, inclui os scripts JS da página
    ├── js/
    │   ├── app.js                            # interações da página: modal, IA, olho da senha
    │   └── modal.js                          # controlador genérico de modal acessível (focus trap)
    ├── api/
    │   ├── .htaccess                         # reescreve /api/chamados/{id} pra chamados_id.php
    │   ├── chamados.php                      # endpoint GET/POST /api/chamados
    │   └── chamados_id.php                   # endpoint GET/PATCH /api/chamados/{id}
    ├── agente/
    │   ├── index.php                         # dashboard de chamados, com filtro por status
    │   ├── chamado.php                       # detalhe do chamado, permite mudar o status
    │   └── usuarios.php                      # cadastro e listagem de funcionários
    └── cliente/
        ├── index.php                         # lista só os chamados do usuário logado
        ├── novo_chamado.php                  # formulário de abertura de chamado
        └── chamado.php                       # detalhe do chamado, somente leitura
```
