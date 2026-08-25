<?php

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../config/database.php';

use App\Integrations\AnthropicClient;
use App\Integrations\EmailClient;
use App\Integrations\FirecrawlClient;
use App\Repositories\ChamadoRepository;
use App\Repositories\UsuarioRepository;
use App\Services\ChamadoService;
use App\Validation\ValidacaoException;

header('Content-Type: application/json');

$pdo = conectar_banco();
$service = new ChamadoService(
    new ChamadoRepository($pdo),
    new AnthropicClient(getenv('ANTHROPIC_API_KEY'), getenv('ANTHROPIC_MODEL') ?: 'claude-sonnet-4-6'),
    new FirecrawlClient(getenv('FIRECRAWL_API_KEY')),
    new UsuarioRepository($pdo),
    new EmailClient(getenv('RESEND_API_KEY'), getenv('RESEND_FROM_EMAIL'))
);

$metodo = $_SERVER['REQUEST_METHOD'];

if ($metodo === 'GET') {
    $status = $_GET['status'] ?? null;
    echo json_encode($service->listarChamados($status ?: null));
    exit;
}

if ($metodo === 'POST') {
    $dados = json_decode(file_get_contents('php://input'), true) ?? $_POST;

    try {
        $chamado = $service->abrirChamado($dados);
        http_response_code(201);
        echo json_encode($chamado);
    } catch (ValidacaoException $e) {
        http_response_code(422);
        echo json_encode(['erros' => $e->getErros()]);
    }
    exit;
}

http_response_code(405);
echo json_encode(['erro' => 'Método não permitido.']);
