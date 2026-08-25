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

$id = (int) ($_GET['id'] ?? 0);
$chamado = $id > 0 ? $service->buscarChamado($id) : null;

if ($chamado === null || (int) $chamado['usuario_id'] !== (int) $usuario['id']) {
    http_response_code(404);
    $tituloPagina = 'Chamado não encontrado';
    require __DIR__ . '/../includes/header.php';
    echo '<p class="text-slate-500">Chamado não encontrado.</p>';
    require __DIR__ . '/../includes/footer.php';
    exit;
}

$tituloPagina = 'OS #' . str_pad((string) $chamado['id'], 5, '0', STR_PAD_LEFT) . ' — Central de Chamados de TI';
require __DIR__ . '/../includes/header.php';
?>

<a href="/cliente/index.php" class="inline-block mb-4 font-mono text-xs uppercase tracking-wide text-slate-500 hover:text-sky-700">
    ← Voltar
</a>

<div class="bg-white border border-slate-200 border-l-2 border-dashed border-l-slate-400 rounded p-6">
    <div class="flex items-start justify-between gap-4">
        <div>
            <div class="font-mono text-xs uppercase tracking-wide text-slate-500">
                OS #<?= str_pad((string) $chamado['id'], 5, '0', STR_PAD_LEFT) ?>
                · <?= htmlspecialchars($chamado['setor']) ?>
                · <?= htmlspecialchars(formatar_data_hora($chamado['criado_em'])) ?>
            </div>
            <h1 class="text-2xl font-semibold mt-1"><?= htmlspecialchars($chamado['titulo']) ?></h1>
            <div class="font-mono text-xs uppercase tracking-wide text-slate-500 mt-1">
                Categoria: <?= htmlspecialchars($chamado['categoria'] ?? 'Outro') ?>
            </div>
        </div>
        <span class="font-mono text-xs uppercase tracking-wide border-2 rounded px-2 py-1 rotate-[-4deg] <?= classe_prioridade($chamado['prioridade']) ?>">
            <?= htmlspecialchars($chamado['prioridade']) ?>
        </span>
    </div>

    <p class="mt-4 text-slate-900 whitespace-pre-line"><?= htmlspecialchars($chamado['descricao']) ?></p>

    <div class="mt-6 font-mono text-xs uppercase tracking-wide <?= classe_status($chamado['status']) ?>">
        Status: <?= htmlspecialchars($chamado['status']) ?>
    </div>

    <?php if (!empty($chamado['sugestao_ia'])): ?>
        <div class="mt-6 bg-slate-100 border border-slate-200 rounded p-4">
            <div class="font-mono text-xs uppercase tracking-wide text-sky-700 mb-1">Sugestão da IA</div>
            <p class="text-slate-900"><?= htmlspecialchars($chamado['sugestao_ia']) ?></p>
        </div>
    <?php endif; ?>

    <?php if (!empty($chamado['artigos'])): ?>
        <div class="mt-6">
            <div class="font-mono text-xs uppercase tracking-wide text-slate-500 mb-2">Artigos relacionados</div>
            <ul class="space-y-2">
                <?php foreach ($chamado['artigos'] as $artigo): ?>
                    <li class="border border-slate-200 rounded p-3">
                        <a href="<?= htmlspecialchars($artigo['url']) ?>" target="_blank" rel="noopener"
                           class="text-sky-700 font-medium hover:underline">
                            <?= htmlspecialchars($artigo['titulo']) ?>
                        </a>
                        <?php if (!empty($artigo['resumo'])): ?>
                            <p class="text-slate-500 text-sm mt-1"><?= htmlspecialchars($artigo['resumo']) ?></p>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
