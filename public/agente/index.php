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
exigir_papel('agente');

$pdo = conectar_banco();
$repository = new ChamadoRepository($pdo);
$service = new ChamadoService(
    $repository,
    new AnthropicClient(getenv('ANTHROPIC_API_KEY'), getenv('ANTHROPIC_MODEL') ?: 'claude-sonnet-4-6'),
    new FirecrawlClient(getenv('FIRECRAWL_API_KEY')),
    new UsuarioRepository($pdo),
    new EmailClient(getenv('RESEND_API_KEY'), getenv('RESEND_FROM_EMAIL'))
);

$statusFiltro = $_GET['status'] ?? null;
$chamados = $service->listarChamados($statusFiltro ?: null);
$contagem = $service->contarPorStatus();

$tituloPagina = 'Dashboard — Central de Chamados de TI';
require __DIR__ . '/../includes/header.php';
?>

<div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8">
    <?php foreach (['Aberto', 'Em Andamento', 'Resolvido'] as $status): ?>
        <a href="?status=<?= urlencode($status) ?>"
           class="bg-white border border-slate-200 rounded p-4 <?= $statusFiltro === $status ? 'ring-2 ring-sky-700' : '' ?>">
            <div class="font-mono text-xs uppercase tracking-wide text-slate-500"><?= htmlspecialchars($status) ?></div>
            <div class="text-3xl font-semibold <?= classe_status($status) ?>"><?= $contagem[$status] ?? 0 ?></div>
        </a>
    <?php endforeach; ?>
</div>

<div class="flex items-center justify-between mb-4">
    <h1 class="font-semibold uppercase tracking-wide text-xl">Chamados</h1>
    <?php if ($statusFiltro): ?>
        <a href="/agente/index.php" class="font-mono text-xs uppercase tracking-wide text-sky-700 hover:underline">
            Limpar filtro
        </a>
    <?php endif; ?>
</div>

<div class="space-y-3">
    <?php if ($chamados === []): ?>
        <p class="text-slate-500">Nenhum chamado encontrado.</p>
    <?php endif; ?>

    <?php foreach ($chamados as $chamado): ?>
        <a href="/agente/chamado.php?id=<?= (int) $chamado['id'] ?>"
           class="block bg-white border border-slate-200 border-l-2 border-dashed border-l-slate-400 rounded p-4 hover:border-l-sky-700 transition">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <div class="font-mono text-xs uppercase tracking-wide text-slate-500">
                        OS #<?= str_pad((string) $chamado['id'], 5, '0', STR_PAD_LEFT) ?>
                        · <?= htmlspecialchars($chamado['setor']) ?>
                        · <?= htmlspecialchars($chamado['solicitante']) ?>
                        · <?= htmlspecialchars(formatar_data_hora($chamado['criado_em'])) ?>
                    </div>
                    <div class="text-lg font-medium mt-1"><?= htmlspecialchars($chamado['titulo']) ?></div>
                    <div class="font-mono text-xs uppercase tracking-wide mt-2 <?= classe_status($chamado['status']) ?>">
                        <?= htmlspecialchars($chamado['status']) ?>
                    </div>
                </div>
                <span class="font-mono text-xs uppercase tracking-wide border-2 rounded px-2 py-1 rotate-[-4deg] <?= classe_prioridade($chamado['prioridade']) ?>">
                    <?= htmlspecialchars($chamado['prioridade']) ?>
                </span>
            </div>
        </a>
    <?php endforeach; ?>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
