<?php

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/auth.php';

use App\Repositories\UsuarioRepository;

iniciar_sessao();
exigir_papel('agente');

$pdo = conectar_banco();
$repository = new UsuarioRepository($pdo);

$papeisValidos = ['cliente', 'agente'];

$erros = [];
$sucesso = null;
$dados = ['nome' => '', 'email' => '', 'papel' => 'cliente', 'setor' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dados = [
        'nome' => trim($_POST['nome'] ?? ''),
        'email' => trim($_POST['email'] ?? ''),
        'papel' => $_POST['papel'] ?? '',
        'setor' => trim($_POST['setor'] ?? ''),
    ];
    $senha = (string) ($_POST['senha'] ?? '');

    if ($dados['nome'] === '') {
        $erros[] = 'O campo "nome" é obrigatório.';
    }

    if ($dados['email'] === '') {
        $erros[] = 'O campo "e-mail" é obrigatório.';
    } elseif (filter_var($dados['email'], FILTER_VALIDATE_EMAIL) === false) {
        $erros[] = 'Informe um e-mail em formato válido.';
    }

    if ($senha === '') {
        $erros[] = 'O campo "senha inicial" é obrigatório.';
    } elseif (strlen($senha) < 6) {
        $erros[] = 'A senha inicial deve ter no mínimo 6 caracteres.';
    }

    if (!in_array($dados['papel'], $papeisValidos, true)) {
        $erros[] = 'Papel inválido.';
    }

    if ($dados['setor'] === '') {
        $erros[] = 'O campo "setor" é obrigatório.';
    }

    if ($erros === []) {
        try {
            $repository->criar([
                'nome' => $dados['nome'],
                'email' => $dados['email'],
                'senha' => $senha,
                'papel' => $dados['papel'],
                'setor' => $dados['setor'],
            ]);
            $sucesso = 'Funcionário cadastrado com sucesso.';
            $dados = ['nome' => '', 'email' => '', 'papel' => 'cliente', 'setor' => ''];
        } catch (\PDOException $e) {
            if ($e->getCode() === '23505') {
                $erros[] = 'Já existe um funcionário cadastrado com esse e-mail.';
            } else {
                throw $e;
            }
        }
    }
}

$funcionarios = $repository->listar();

$tituloPagina = 'Funcionários — Central de Chamados de TI';
require __DIR__ . '/../includes/header.php';
?>

<h1 class="font-semibold uppercase tracking-wide text-xl mb-6">Funcionários</h1>

<?php if ($sucesso !== null): ?>
    <div class="bg-white border border-emerald-600 text-emerald-600 rounded p-4 mb-6 font-mono text-xs uppercase tracking-wide">
        <?= htmlspecialchars($sucesso) ?>
    </div>
<?php endif; ?>

<?php if ($erros !== []): ?>
    <div class="bg-white border border-orange-600 text-orange-600 rounded p-4 mb-6">
        <ul class="list-disc list-inside font-mono text-xs uppercase tracking-wide space-y-1">
            <?php foreach ($erros as $erro): ?>
                <li><?= htmlspecialchars($erro) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<form method="POST" class="bg-white border border-slate-200 rounded p-6 space-y-4 mb-10">
    <div>
        <label class="font-mono text-xs uppercase tracking-wide text-slate-500" for="nome">Nome</label>
        <input type="text" name="nome" id="nome" value="<?= htmlspecialchars($dados['nome']) ?>"
               class="mt-1 w-full border border-slate-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-sky-700">
    </div>

    <div>
        <label class="font-mono text-xs uppercase tracking-wide text-slate-500" for="email">E-mail</label>
        <input type="email" name="email" id="email" value="<?= htmlspecialchars($dados['email']) ?>"
               class="mt-1 w-full border border-slate-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-sky-700">
    </div>

    <div>
        <label class="font-mono text-xs uppercase tracking-wide text-slate-500" for="senha">Senha inicial</label>
        <div class="relative mt-1">
            <input type="password" name="senha" id="senha"
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

    <div>
        <label class="font-mono text-xs uppercase tracking-wide text-slate-500" for="papel">Papel</label>
        <select name="papel" id="papel" class="mt-1 w-full border border-slate-300 rounded px-3 py-2">
            <option value="cliente" <?= $dados['papel'] === 'cliente' ? 'selected' : '' ?>>Cliente</option>
            <option value="agente" <?= $dados['papel'] === 'agente' ? 'selected' : '' ?>>Agente</option>
        </select>
    </div>

    <div>
        <label class="font-mono text-xs uppercase tracking-wide text-slate-500" for="setor">Setor</label>
        <input type="text" name="setor" id="setor" value="<?= htmlspecialchars($dados['setor']) ?>"
               placeholder="Ex: Financeiro, RH, Comercial, TI"
               class="mt-1 w-full border border-slate-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-sky-700">
    </div>

    <button type="submit"
            class="font-mono text-xs uppercase tracking-wide bg-sky-700 text-white px-4 py-2 rounded hover:bg-sky-800">
        Cadastrar Funcionário
    </button>
</form>

<h2 class="font-semibold uppercase tracking-wide text-lg mb-4">Funcionários Cadastrados</h2>

<div class="bg-white border border-slate-200 rounded overflow-x-auto">
    <table class="w-full text-left">
        <thead>
            <tr class="border-b border-slate-200">
                <th class="font-mono text-xs uppercase tracking-wide text-slate-500 px-4 py-3">Nome</th>
                <th class="font-mono text-xs uppercase tracking-wide text-slate-500 px-4 py-3">E-mail</th>
                <th class="font-mono text-xs uppercase tracking-wide text-slate-500 px-4 py-3">Papel</th>
                <th class="font-mono text-xs uppercase tracking-wide text-slate-500 px-4 py-3">Setor</th>
                <th class="font-mono text-xs uppercase tracking-wide text-slate-500 px-4 py-3">Criado em</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($funcionarios as $funcionario): ?>
                <tr class="border-b border-slate-200 last:border-b-0">
                    <td class="px-4 py-3 text-slate-900"><?= htmlspecialchars($funcionario['nome']) ?></td>
                    <td class="px-4 py-3 text-slate-900"><?= htmlspecialchars($funcionario['email']) ?></td>
                    <td class="px-4 py-3 font-mono text-xs uppercase tracking-wide text-slate-500"><?= htmlspecialchars($funcionario['papel']) ?></td>
                    <td class="px-4 py-3 text-slate-900"><?= htmlspecialchars($funcionario['setor'] ?? '—') ?></td>
                    <td class="px-4 py-3 font-mono text-xs text-slate-500"><?= htmlspecialchars($funcionario['criado_em']) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
