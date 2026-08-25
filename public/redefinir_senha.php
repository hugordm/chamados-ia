<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';

use App\Repositories\TokenRedefinicaoSenhaRepository;
use App\Repositories\UsuarioRepository;

iniciar_sessao();

$tokenBruto = $_GET['token'] ?? ($_POST['token'] ?? '');
$tokenHash = $tokenBruto !== '' ? hash('sha256', $tokenBruto) : '';

$pdo = conectar_banco();
$tokenRepository = new TokenRedefinicaoSenhaRepository($pdo);
$usuarioRepository = new UsuarioRepository($pdo);

$tokenValido = $tokenHash !== '' ? $tokenRepository->buscarValidoPorHash($tokenHash) : null;

$erro = null;

if ($tokenValido !== null && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $novaSenha = (string) ($_POST['senha'] ?? '');
    $confirmacao = (string) ($_POST['confirmacao'] ?? '');

    if (strlen($novaSenha) < 6) {
        $erro = 'A nova senha deve ter no mínimo 6 caracteres.';
    } elseif ($novaSenha !== $confirmacao) {
        $erro = 'As senhas não coincidem.';
    } else {
        $hashAtual = $usuarioRepository->buscarHashSenhaPorId((int) $tokenValido['usuario_id']);

        if ($hashAtual !== null && password_verify($novaSenha, $hashAtual)) {
            $erro = 'A nova senha precisa ser diferente da senha atual.';
        } else {
            $usuarioRepository->atualizarSenha((int) $tokenValido['usuario_id'], $novaSenha);
            $tokenRepository->marcarComoUsado((int) $tokenValido['id']);
            header('Location: /login.php?senha_redefinida=1');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Redefinir Senha — Central de Chamados de TI</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 text-slate-900 min-h-screen flex items-center justify-center">
    <div class="w-full max-w-sm">
        <h1 class="font-semibold uppercase tracking-wide text-lg text-center mb-6">
            Central de Chamados <span class="text-sky-700">// TI</span>
        </h1>

        <?php if ($tokenValido === null): ?>
            <div class="bg-white border border-orange-600 text-orange-600 rounded p-6 space-y-4">
                <p class="font-mono text-xs uppercase tracking-wide">Link inválido ou expirado, solicite um novo.</p>
                <a href="/esqueci_senha.php" class="font-mono text-xs uppercase tracking-wide text-sky-700 hover:underline">
                    Solicitar novo link
                </a>
            </div>
        <?php else: ?>
            <form method="POST" class="bg-white border border-slate-200 rounded p-6 space-y-4">
                <input type="hidden" name="token" value="<?= htmlspecialchars($tokenBruto) ?>">

                <?php if ($erro !== null): ?>
                    <div class="bg-white border border-orange-600 text-orange-600 rounded p-3 font-mono text-xs uppercase tracking-wide">
                        <?= htmlspecialchars($erro) ?>
                    </div>
                <?php endif; ?>

                <div>
                    <label class="font-mono text-xs uppercase tracking-wide text-slate-500" for="senha">Nova senha</label>
                    <input type="password" name="senha" id="senha" required minlength="6"
                           class="campo-senha-toggle mt-1 w-full border border-slate-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-sky-700">
                </div>

                <div>
                    <label class="font-mono text-xs uppercase tracking-wide text-slate-500" for="confirmacao">Confirmar nova senha</label>
                    <input type="password" name="confirmacao" id="confirmacao" required minlength="6"
                           class="campo-senha-toggle mt-1 w-full border border-slate-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-sky-700">
                </div>

                <label class="flex items-center gap-2 font-mono text-xs uppercase tracking-wide text-slate-500">
                    <input type="checkbox" data-toggle-senha=".campo-senha-toggle">
                    Mostrar senha
                </label>

                <button type="submit"
                        class="w-full font-mono text-xs uppercase tracking-wide bg-sky-700 text-white px-4 py-2 rounded hover:bg-sky-800">
                    Redefinir Senha
                </button>
            </form>
        <?php endif; ?>
    </div>
    <script src="/js/app.js"></script>
</body>
</html>
