<?php

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/auth.php';

use App\Integrations\AnthropicClient;
use App\Integrations\EmailClient;
use App\Integrations\FirecrawlClient;
use App\Repositories\ChamadoRepository;
use App\Repositories\UsuarioRepository;
use App\Services\ChamadoService;
use App\Validation\ValidacaoException;

iniciar_sessao();
exigir_papel('cliente');

$usuario = usuario_logado();

$pdo = conectar_banco();
$service = new ChamadoService(
    new ChamadoRepository($pdo),
    new AnthropicClient(getenv('ANTHROPIC_API_KEY'), getenv('ANTHROPIC_MODEL') ?: 'claude-sonnet-4-6'),
    new FirecrawlClient(getenv('FIRECRAWL_API_KEY')),
    new UsuarioRepository($pdo),
    new EmailClient(getenv('RESEND_API_KEY'), getenv('RESEND_FROM_EMAIL'))
);

$erros = [];
$dados = ['titulo' => '', 'descricao' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dados = [
        'titulo' => trim($_POST['titulo'] ?? ''),
        'descricao' => trim($_POST['descricao'] ?? ''),
    ];

    try {
        $chamado = $service->abrirChamado([
            'solicitante' => $usuario['nome'],
            'setor' => $usuario['setor'],
            'titulo' => $dados['titulo'],
            'descricao' => $dados['descricao'],
            'usuario_id' => $usuario['id'],
        ]);
        header('Location: /cliente/chamado.php?id=' . $chamado['id']);
        exit;
    } catch (ValidacaoException $e) {
        $erros = $e->getErros();
    }
}

$tituloPagina = 'Abrir Chamado — Central de Chamados de TI';
require __DIR__ . '/../includes/header.php';
?>

<h1 class="font-semibold uppercase tracking-wide text-xl mb-6">Abrir Novo Chamado</h1>

<?php if ($erros !== []): ?>
    <div class="bg-white border border-orange-600 text-orange-600 rounded p-4 mb-6">
        <ul class="list-disc list-inside font-mono text-xs uppercase tracking-wide space-y-1">
            <?php foreach ($erros as $erro): ?>
                <li><?= htmlspecialchars($erro) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<form method="POST" id="form-novo-chamado" class="bg-white border border-slate-200 rounded p-6 space-y-4">
    <div class="font-mono text-xs uppercase tracking-wide text-slate-500">
        Solicitante: <?= htmlspecialchars($usuario['nome']) ?> · <?= htmlspecialchars($usuario['setor'] ?? '—') ?>
    </div>

    <div>
        <label class="font-mono text-xs uppercase tracking-wide text-slate-500" for="titulo">Título</label>
        <input type="text" name="titulo" id="titulo" maxlength="150" value="<?= htmlspecialchars($dados['titulo']) ?>"
               class="mt-1 w-full border border-slate-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-sky-700">
    </div>

    <div>
        <label class="font-mono text-xs uppercase tracking-wide text-slate-500" for="descricao">Descrição</label>
        <textarea name="descricao" id="descricao" rows="5"
                  class="mt-1 w-full border border-slate-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-sky-700"><?= htmlspecialchars($dados['descricao']) ?></textarea>
    </div>

    <button type="submit" id="btn-enviar"
            class="font-mono text-xs uppercase tracking-wide bg-sky-700 text-white px-4 py-2 rounded hover:bg-sky-800">
        Abrir Chamado
    </button>
</form>

<?php require __DIR__ . '/../includes/footer.php'; ?>
