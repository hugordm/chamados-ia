-- Usuários de teste. Senha de ambos: "senha123" (troque antes de qualquer uso real).
-- Hashes gerados com password_hash($senha, PASSWORD_BCRYPT).
INSERT INTO usuarios (nome, email, senha_hash, papel, setor) VALUES
('Marina Alves Ferreira', 'marina@empresa.com', '$2y$10$whKodYzFnWyEiikZkFxy1OocGreHNWmptRTNZCCfzogbje5Lakfhi', 'cliente', 'Financeiro'),
('Hugo Melo', 'hugo@empresa.com', '$2y$10$3stet6wfd/m9scXKq4mSz.gH4ln0jRR6vorCGRuIE9xyMfJZUj0ui', 'agente', 'TI')
ON CONFLICT (email) DO NOTHING;
