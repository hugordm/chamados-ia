<?php

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../config/database.php';

use App\Integrations\AnthropicClient;
use App\Integrations\EmailClient;
use App\Integrations\FirecrawlClient;
use App\Repositories\ChamadoRepository;
use App\Repositories\UsuarioRepository;
use App\Services\ChamadoService;

header('Content-Type: application/json');

$chave_recebida = $_SERVER['HTTP_X_API_KEY'] ?? '';
if (!hash_equals(getenv('API_KEY'), $chave_recebida)) {
    http_response_code(401);
    echo json_encode(['erro' => 'Chave de API inválida ou ausente']);
    exit;
}

$pdo = conectar_banco();
$service = new ChamadoService(
    new ChamadoRepository($pdo),
    new AnthropicClient(getenv('ANTHROPIC_API_KEY'), getenv('ANTHROPIC_MODEL') ?: 'claude-sonnet-4-6'),
    new FirecrawlClient(getenv('FIRECRAWL_API_KEY')),
    new UsuarioRepository($pdo),
    new EmailClient(getenv('RESEND_API_KEY'), getenv('RESEND_FROM_EMAIL'))
);

$id = (int) ($_GET['id'] ?? 0);
$metodo = $_SERVER['REQUEST_METHOD'];

if ($id <= 0) {
    http_response_code(400);
    echo json_encode(['erro' => 'ID inválido.']);
    exit;
}

if ($metodo === 'GET') {
    $chamado = $service->buscarChamado($id);

    if ($chamado === null) {
        http_response_code(404);
        echo json_encode(['erro' => 'Chamado não encontrado.']);
        exit;
    }

    echo json_encode($chamado);
    exit;
}

if ($metodo === 'PATCH') {
    $dados = json_decode(file_get_contents('php://input'), true) ?? [];

    try {
        $service->atualizarStatus($id, $dados['status'] ?? '');
        echo json_encode($service->buscarChamado($id));
    } catch (InvalidArgumentException $e) {
        http_response_code(422);
        echo json_encode(['erro' => $e->getMessage()]);
    }
    exit;
}

http_response_code(405);
echo json_encode(['erro' => 'Método não permitido.']);
