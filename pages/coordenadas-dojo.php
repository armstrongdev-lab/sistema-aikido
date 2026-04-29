<?php
session_start();
require_once '../includes/db.php';  // deve definir $pdo como PDO

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['error' => 'Usuário não autenticado']);
    exit;
}

$usuario_id = $_SESSION['usuario_id'];

$sql = "SELECT d.dojoLatitude, d.dojoLongitude, d.distanciaPermitida
        FROM alunos a
        INNER JOIN dojos d ON a.dojo_id = d.id
        WHERE a.usuario_id = ?";

$stmt = $pdo->prepare($sql);
$stmt->execute([$usuario_id]);
$dojo = $stmt->fetch(PDO::FETCH_ASSOC);

if ($dojo) {
    echo json_encode($dojo);
} else {
    echo json_encode(['error' => 'Dados do dojo não encontrados']);
}
?>
