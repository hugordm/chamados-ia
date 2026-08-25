CREATE TABLE IF NOT EXISTS chamados (
    id              SERIAL PRIMARY KEY,
    solicitante     VARCHAR(120) NOT NULL,
    setor           VARCHAR(80)  NOT NULL,
    titulo          VARCHAR(150) NOT NULL,
    descricao       TEXT NOT NULL,
    categoria       VARCHAR(50),
    prioridade      VARCHAR(20) DEFAULT 'Media'
                    CHECK (prioridade IN ('Baixa','Media','Alta','Urgente')),
    sugestao_ia     TEXT,
    status          VARCHAR(20) NOT NULL DEFAULT 'Aberto'
                    CHECK (status IN ('Aberto','Em Andamento','Resolvido')),
    criado_em       TIMESTAMP NOT NULL DEFAULT NOW(),
    atualizado_em   TIMESTAMP NOT NULL DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS artigos_sugeridos (
    id          SERIAL PRIMARY KEY,
    chamado_id  INTEGER NOT NULL REFERENCES chamados(id) ON DELETE CASCADE,
    titulo      VARCHAR(255) NOT NULL,
    url         VARCHAR(500) NOT NULL,
    resumo      TEXT
);

CREATE INDEX IF NOT EXISTS idx_chamados_status ON chamados(status);
CREATE INDEX IF NOT EXISTS idx_artigos_chamado ON artigos_sugeridos(chamado_id);

CREATE TABLE IF NOT EXISTS usuarios (
    id          SERIAL PRIMARY KEY,
    nome        VARCHAR(120) NOT NULL,
    email       VARCHAR(150) UNIQUE NOT NULL,
    senha_hash  VARCHAR(255) NOT NULL,
    papel       VARCHAR(20) NOT NULL CHECK (papel IN ('cliente','agente')),
    setor       VARCHAR(80),
    criado_em   TIMESTAMP NOT NULL DEFAULT NOW()
);

-- Liga cada chamado a quem abriu. Mantemos as colunas solicitante/setor
-- como estavam (um "retrato" do nome/setor no momento da abertura), mas
-- agora também guardamos o vínculo com o usuário de verdade.
ALTER TABLE chamados ADD COLUMN IF NOT EXISTS usuario_id INTEGER REFERENCES usuarios(id);

CREATE INDEX IF NOT EXISTS idx_chamados_usuario ON chamados(usuario_id);

CREATE TABLE IF NOT EXISTS tokens_redefinicao_senha (
    id          SERIAL PRIMARY KEY,
    usuario_id  INTEGER NOT NULL REFERENCES usuarios(id) ON DELETE CASCADE,
    token_hash  VARCHAR(255) NOT NULL,
    expira_em   TIMESTAMP NOT NULL,
    usado_em    TIMESTAMP,
    criado_em   TIMESTAMP NOT NULL DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_tokens_usuario ON tokens_redefinicao_senha(usuario_id);

-- Um chamado arquivado é aquele com arquivado_em preenchido — nada é
-- apagado, só deixa de aparecer nas listagens normais.
ALTER TABLE chamados ADD COLUMN IF NOT EXISTS arquivado_em TIMESTAMP;

CREATE INDEX IF NOT EXISTS idx_chamados_arquivado ON chamados(arquivado_em);
