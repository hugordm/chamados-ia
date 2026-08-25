<?php

require_once __DIR__ . '/../../config/auth.php';

$usuarioNav = usuario_logado();

function classe_prioridade(string $prioridade): string
{
    return match ($prioridade) {
        'Urgente', 'Alta' => 'text-orange-600 border-orange-600',
        default => 'text-slate-500 border-slate-400',
    };
}

function classe_status(string $status): string
{
    return match ($status) {
        'Resolvido' => 'text-emerald-600',
        'Em Andamento' => 'text-amber-500',
        default => 'text-slate-500',
    };
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($tituloPagina ?? 'Central de Chamados de TI') ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 text-slate-900 min-h-screen">
    <header class="bg-white border-b border-slate-200">
        <div class="max-w-5xl mx-auto px-4 py-4 flex items-center justify-between">
            <a href="<?= $usuarioNav ? '/' . $usuarioNav['papel'] . '/index.php' : '/login.php' ?>"
               class="font-semibold uppercase tracking-wide text-lg">
                Central de Chamados <span class="text-sky-700">// TI</span>
            </a>
            <div class="flex items-center gap-4">
                <?php if ($usuarioNav && $usuarioNav['papel'] === 'cliente'): ?>
                    <a href="/cliente/novo_chamado.php"
                       class="font-mono text-xs uppercase tracking-wide bg-sky-700 text-white px-4 py-2 rounded hover:bg-sky-800">
                        + Novo Chamado
                    </a>
                <?php endif; ?>
                <?php if ($usuarioNav): ?>
                    <span class="font-mono text-xs uppercase tracking-wide text-slate-500">
                        <?= htmlspecialchars($usuarioNav['nome']) ?> · <?= htmlspecialchars($usuarioNav['papel']) ?>
                    </span>
                    <?php if ($usuarioNav['papel'] === 'agente'): ?>
                        <a href="/agente/usuarios.php" class="font-mono text-xs uppercase tracking-wide text-slate-500 hover:text-sky-700">
                            Funcionários
                        </a>
                    <?php endif; ?>
                    <a href="/logout.php" class="font-mono text-xs uppercase tracking-wide text-slate-500 hover:text-sky-700">
                        Sair
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </header>
    <main class="max-w-5xl mx-auto px-4 py-8">
