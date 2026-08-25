<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';

use App\Integrations\EmailClient;
use App\Repositories\TokenRedefinicaoSenhaRepository;
use App\Repositories\UsuarioRepository;

iniciar_sessao();

$usuarioAtual = usuario_logado();
if ($usuarioAtual !== null) {
    header('Location: /' . $usuarioAtual['papel'] . '/index.php');
    exit;
}

$mensagem = null;
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    $pdo = conectar_banco();
    $usuarioRepository = new UsuarioRepository($pdo);
    $tokenRepository = new TokenRedefinicaoSenhaRepository($pdo);

    $usuario = $email !== '' ? $usuarioRepository->buscarPorEmail($email) : null;

    if ($usuario !== null) {
        $tokenBruto = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $tokenBruto);
        $tokenRepository->criar((int) $usuario['id'], $tokenHash);

        $link = sprintf(
            '%s://%s/redefinir_senha.php?token=%s',
            (($_SERVER['HTTPS'] ?? '') === 'on') ? 'https' : 'http',
            $_SERVER['HTTP_HOST'],
            $tokenBruto
        );

        $corpo = '<h2>Redefinição de senha</h2>'
            . '<p>Recebemos uma solicitação para redefinir a senha da sua conta na Central de Chamados de TI.</p>'
            . '<p><a href="' . htmlspecialchars($link) . '">Clique aqui para definir uma nova senha</a></p>'
            . '<p>Esse link expira em 1 hora. Se você não solicitou essa redefinição, ignore este e-mail.</p>';

        $emailClient = new EmailClient(getenv('RESEND_API_KEY'), getenv('RESEND_FROM_EMAIL'));
        if (!$emailClient->enviar($usuario['email'], $usuario['nome'], 'Redefinição de senha — Central de Chamados de TI', $corpo)) {
            error_log("Falha ao enviar e-mail de redefinição de senha para o usuário #{$usuario['id']}");
        }
    }

    $mensagem = 'Se esse e-mail estiver cadastrado, você vai receber um link de redefinição.';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Esqueci Minha Senha — Central de Chamados de TI</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 text-slate-900 min-h-screen flex items-center justify-center">
    <div class="w-full max-w-sm">
        <h1 class="font-semibold uppercase tracking-wide text-lg text-center mb-6">
            Central de Chamados <span class="text-sky-700">// TI</span>
        </h1>

        <?php if ($mensagem !== null): ?>
            <div class="bg-white border border-slate-200 rounded p-6 space-y-4">
                <p class="text-slate-900"><?= htmlspecialchars($mensagem) ?></p>
                <a href="/login.php" class="font-mono text-xs uppercase tracking-wide text-sky-700 hover:underline">
                    ← Voltar para o login
                </a>
            </div>
        <?php else: ?>
            <form method="POST" class="bg-white border border-slate-200 rounded p-6 space-y-4">
                <p class="text-slate-500 text-sm">
                    Informe o e-mail cadastrado. Se ele existir no sistema, você vai receber um link para redefinir sua senha.
                </p>

                <div>
                    <label class="font-mono text-xs uppercase tracking-wide text-slate-500" for="email">E-mail</label>
                    <input type="email" name="email" id="email" required value="<?= htmlspecialchars($email) ?>"
                           class="mt-1 w-full border border-slate-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-sky-700">
                </div>

                <button type="submit"
                        class="w-full font-mono text-xs uppercase tracking-wide bg-sky-700 text-white px-4 py-2 rounded hover:bg-sky-800">
                    Enviar Link de Redefinição
                </button>

                <div class="text-center">
                    <a href="/login.php" class="font-mono text-xs uppercase tracking-wide text-slate-500 hover:text-sky-700">
                        ← Voltar para o login
                    </a>
                </div>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>
