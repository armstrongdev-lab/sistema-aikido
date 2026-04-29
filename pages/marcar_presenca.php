<?php
session_start();
require_once '../includes/db.php';

// Verificar se está logado e é sensei
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo_usuario'] !== 'sensei') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Acesso negado.']);
    exit;
}

// Receber JSON do fetch
$data = json_decode(file_get_contents('php://input'), true);

if (!$data || empty($data['aluno_id']) || empty($data['data_presenca'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Dados inválidos.']);
    exit;
}

$aluno_id = (int)$data['aluno_id'];
$data_presenca = $data['data_presenca'];

// Validar formato da data (YYYY-MM-DD)
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data_presenca)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Formato de data inválido.']);
    exit;
}

try {
    // Evitar duplicidade: verificar se já existe essa presença
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM presencas WHERE aluno_id = ? AND data_presenca = ?");
    $stmt->execute([$aluno_id, $data_presenca]);
    if ($stmt->fetchColumn() > 0) {
        echo json_encode(['success' => false, 'error' => 'Presença já registrada para essa data.']);
        exit;
    }

    // Inserir a presença
    $stmt = $pdo->prepare("INSERT INTO presencas (aluno_id, data_presenca, status) VALUES (?, ?, 'presente')");
    $stmt->execute([$aluno_id, $data_presenca]);

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Erro no servidor: ' . $e->getMessage()]);
}
