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
$service = new ChamadoService(
    new ChamadoRepository($pdo),
    new AnthropicClient(getenv('ANTHROPIC_API_KEY'), getenv('ANTHROPIC_MODEL') ?: 'claude-sonnet-4-6'),
    new FirecrawlClient(getenv('FIRECRAWL_API_KEY')),
    new UsuarioRepository($pdo),
    new EmailClient(getenv('RESEND_API_KEY'), getenv('RESEND_FROM_EMAIL'))
);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int) ($_POST['id'] ?? 0);
    if ($id > 0) {
        $service->desarquivar($id);
    }
    header('Location: /agente/arquivados.php');
    exit;
}

$chamados = $service->listarArquivados();

$tituloPagina = 'Arquivados — Central de Chamados de TI';
require __DIR__ . '/../includes/header.php';
?>

<h1 class="font-semibold uppercase tracking-wide text-xl mb-6">Chamados Arquivados</h1>

<div class="space-y-3">
    <?php if ($chamados === []): ?>
        <p class="text-slate-500">Nenhum chamado arquivado.</p>
    <?php endif; ?>

    <?php foreach ($chamados as $chamado): ?>
        <div class="bg-white border border-slate-200 border-l-2 border-dashed border-l-slate-400 rounded p-4">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <div class="font-mono text-xs uppercase tracking-wide text-slate-500">
                        OS #<?= str_pad((string) $chamado['id'], 5, '0', STR_PAD_LEFT) ?>
                        · <?= htmlspecialchars($chamado['setor']) ?>
                        · <?= htmlspecialchars($chamado['solicitante']) ?>
                        · Arquivado em <?= htmlspecialchars(formatar_data_hora($chamado['arquivado_em'])) ?>
                    </div>
                    <div class="text-lg font-medium mt-1"><?= htmlspecialchars($chamado['titulo']) ?></div>
                    <div class="font-mono text-xs uppercase tracking-wide mt-2 <?= classe_status($chamado['status']) ?>">
                        <?= htmlspecialchars($chamado['status']) ?>
                    </div>
                </div>
                <form method="POST">
                    <input type="hidden" name="id" value="<?= (int) $chamado['id'] ?>">
                    <button type="submit"
                            class="font-mono text-xs uppercase tracking-wide border border-slate-300 text-slate-700 px-4 py-2 rounded hover:border-slate-400">
                        Desarquivar
                    </button>
                </form>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
