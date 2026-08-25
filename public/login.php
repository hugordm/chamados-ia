<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';

use App\Repositories\UsuarioRepository;

iniciar_sessao();

$usuarioAtual = usuario_logado();
if ($usuarioAtual !== null) {
    header('Location: /' . $usuarioAtual['papel'] . '/index.php');
    exit;
}

$erro = null;
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $senha = (string) ($_POST['senha'] ?? '');

    $repository = new UsuarioRepository(conectar_banco());
    $usuario = $repository->autenticar($email, $senha);

    if ($usuario === null) {
        $erro = 'E-mail ou senha incorretos.';
    } else {
        $_SESSION['usuario'] = [
            'id' => $usuario['id'],
            'nome' => $usuario['nome'],
            'papel' => $usuario['papel'],
            'setor' => $usuario['setor'],
        ];
        header('Location: /' . $usuario['papel'] . '/index.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Entrar — Central de Chamados de TI</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 text-slate-900 min-h-screen flex items-center justify-center">
    <div class="w-full max-w-sm">
        <h1 class="font-semibold uppercase tracking-wide text-lg text-center mb-6">
            Central de Chamados <span class="text-sky-700">// TI</span>
        </h1>

        <form method="POST" class="bg-white border border-slate-200 rounded p-6 space-y-4">
            <?php if ($erro !== null): ?>
                <div class="bg-white border border-orange-600 text-orange-600 rounded p-3 font-mono text-xs uppercase tracking-wide">
                    <?= htmlspecialchars($erro) ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['senha_redefinida'])): ?>
                <div class="bg-white border border-emerald-600 text-emerald-600 rounded p-3 font-mono text-xs uppercase tracking-wide">
                    Senha redefinida com sucesso. Faça login com a nova senha.
                </div>
            <?php endif; ?>

            <div>
                <label class="font-mono text-xs uppercase tracking-wide text-slate-500" for="email">E-mail</label>
                <input type="email" name="email" id="email" required value="<?= htmlspecialchars($email) ?>"
                       class="mt-1 w-full border border-slate-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-sky-700">
            </div>

            <div>
                <label class="font-mono text-xs uppercase tracking-wide text-slate-500" for="senha">Senha</label>
                <div class="relative mt-1">
                    <input type="password" name="senha" id="senha" required
                           class="w-full border border-slate-300 rounded px-3 py-2 pr-10 focus:outline-none focus:ring-2 focus:ring-sky-700">
                    <button type="button" class="js-toggle-senha-olho absolute inset-y-0 right-0 flex items-center pr-3 text-slate-500 hover:text-slate-700"
                            data-alvo="#senha" aria-label="Mostrar senha">
                        <svg class="icone-olho-mostrar h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7Z"/>
                            <circle cx="12" cy="12" r="3"/>
                        </svg>
                        <svg class="icone-olho-ocultar hidden h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M17.94 17.94A10.94 10.94 0 0 1 12 19c-7 0-11-7-11-7a21.86 21.86 0 0 1 5.06-6.06M9.9 4.24A10.94 10.94 0 0 1 12 5c7 0 11 7 11 7a21.86 21.86 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/>
                            <line x1="1" y1="1" x2="23" y2="23"/>
                        </svg>
                    </button>
                </div>
            </div>

            <button type="submit"
                    class="w-full font-mono text-xs uppercase tracking-wide bg-sky-700 text-white px-4 py-2 rounded hover:bg-sky-800">
                Entrar
            </button>
        </form>

        <div class="text-center mt-4">
            <a href="/esqueci_senha.php" class="font-mono text-xs uppercase tracking-wide text-slate-500 hover:text-sky-700">
                Esqueci minha senha
            </a>
        </div>
    </div>
    <script src="/js/app.js"></script>
</body>
</html>
