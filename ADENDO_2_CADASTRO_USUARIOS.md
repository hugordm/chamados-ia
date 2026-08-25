# Adendo 2 — Cadastro de funcionários (feito pelo agente)

Complementa o ADENDO_AUTENTICACAO.md. Hoje os usuários só existem via sql/seed.sql.
Este adendo dá ao papel `agente` uma tela pra cadastrar novos funcionários (cliente
ou agente), sem criar um terceiro papel `admin` — decisão consciente: numa empresa
pequena, quem já atende os chamados é quem também dá o acesso ao sistema.

## 1. Novo arquivo

```
public/agente/usuarios.php   (NOVO)
```

Protegido com `exigir_papel('agente')`, igual às outras páginas do portal do agente.

## 2. Conteúdo da página

Duas partes na mesma página:

**A) Formulário de cadastro** (topo da página)
Campos:
- Nome (obrigatório)
- E-mail (obrigatório, formato válido)
- Senha inicial (obrigatório, mínimo 6 caracteres)
- Papel: select com `Cliente` / `Agente`
- Setor (obrigatório — ex: Financeiro, RH, Comercial, TI)

**B) Lista dos funcionários já cadastrados** (abaixo do formulário)
Tabela ou lista simples: nome, e-mail, papel, setor, data de criação. Só leitura por
enquanto — sem editar nem excluir nesta etapa (fica como melhoria futura).

## 3. src/Repositories/UsuarioRepository.php — método novo

```php
listar(): array
// SELECT id, nome, email, papel, setor, criado_em FROM usuarios
// ORDER BY criado_em DESC
// NUNCA inclui a coluna senha_hash no SELECT.
```

(O método `criar()` já existe do adendo anterior — reaproveitar.)

## 4. Validação no submit do formulário

- Nome, e-mail, senha e setor não podem vir vazios;
- E-mail em formato válido (`filter_var($email, FILTER_VALIDATE_EMAIL)`);
- Senha com no mínimo 6 caracteres;
- Papel precisa ser exatamente `cliente` ou `agente` — nunca aceitar outro valor
  vindo do formulário (validar contra uma lista fixa no PHP, não confiar no que
  veio no POST);
- E-mail duplicado: a tabela `usuarios` já tem `UNIQUE` no e-mail — capturar a
  exceção do PDO nesse caso específico e mostrar uma mensagem amigável tipo
  "Já existe um funcionário cadastrado com esse e-mail", em vez de deixar o erro
  bruto do banco aparecer na tela.

## 5. Navegação

Adicionar um link "Funcionários" no menu do topo, ao lado de "Sair", visível só
quando `papel === 'agente'` (o cliente nunca deve ver esse link nem conseguir
acessar a URL diretamente — a proteção `exigir_papel('agente')` já cobre isso, mas
o link também não deve aparecer pra ele).

## 6. O que NÃO fazer nesta etapa

- Não implementar edição nem exclusão de usuário ainda;
- Não implementar troca de senha pelo próprio usuário ainda;
- Não enviar e-mail de boas-vindas nem nada por SMTP — o agente comunica a senha
  inicial ao funcionário por fora do sistema (é assim que a maioria das empresas
  pequenas faz mesmo).

## 7. Teste manual depois de implementado

1. Logado como agente (hugo@empresa.com), acessar `agente/usuarios.php`;
2. Cadastrar um funcionário novo como `cliente`, de outro setor (ex: RH);
3. Fazer logout, logar com o e-mail/senha desse funcionário novo, e confirmar que
   ele cai no portal do cliente, vendo só os próprios chamados (nenhum);
4. Tentar cadastrar de novo com o mesmo e-mail — deve mostrar o erro amigável, sem
   quebrar a página;
5. Logado como o cliente novo, tentar acessar `agente/usuarios.php` direto pela
   URL — deve dar 403.
