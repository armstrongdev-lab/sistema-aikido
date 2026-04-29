<?php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['error' => 'Usuário não autenticado']);
    exit;
}

$usuario_id = $_SESSION['usuario_id'];

// Buscar dojo do aluno
$sqlDojo = "SELECT dojo_id FROM alunos WHERE usuario_id = ?";
$stmtDojo = $pdo->prepare($sqlDojo);
$stmtDojo->execute([$usuario_id]);
$rowDojo = $stmtDojo->fetch();

if (!$rowDojo) {
    echo json_encode(['error' => 'Aluno ou dojo não encontrado']);
    exit;
}

$dojo_id = $rowDojo['dojo_id'];

// Buscar dias permitidos + horários do dojo na tabela treinos_horarios
$sqlHorarios = "SELECT dia_semana, horario FROM treinos_horarios WHERE dojo_id = ?";
$stmtHorarios = $pdo->prepare($sqlHorarios);
$stmtHorarios->execute([$dojo_id]);
$horarios = $stmtHorarios->fetchAll();

// Normaliza os dias da semana para lowercase, sem acento
function normalizarDia($dia) {
    $map = [
        'segunda' => 'segunda',
        'terça' => 'terca',
        'terca' => 'terca',
        'quarta' => 'quarta',
        'quinta' => 'quinta',
        'sexta' => 'sexta',
        'sábado' => 'sabado',
        'sabado' => 'sabado',
        'domingo' => 'domingo',
    ];
    $dia = strtolower($dia);
    $dia = str_replace(['ç', 'á', 'ã', 'â', 'é', 'ê', 'í', 'ó', 'ô', 'ú'], 
                       ['c', 'a', 'a', 'a', 'e', 'e', 'i', 'o', 'o', 'u'], $dia);
    return $map[$dia] ?? $dia;
}

$horariosPermitidos = array_map(function($row) {
    return [
        'dia_semana' => normalizarDia($row['dia_semana']),
        'horario' => substr($row['horario'], 0, 5) // remove segundos para facilitar
    ];
}, $horarios);

// Dados do servidor para controle de horário e dia
date_default_timezone_set('America/Sao_Paulo');
$serverTime = date('H:i:s');
$serverDay = strtolower(strftime('%A')); // nome do dia em português
// normaliza o dia para manter padrão
function normalizarDiaServer($dia) {
    $map = [
        'monday' => 'segunda',
        'tuesday' => 'terca',
        'wednesday' => 'quarta',
        'thursday' => 'quinta',
        'friday' => 'sexta',
        'saturday' => 'sabado',
        'sunday' => 'domingo',
    ];
    return $map[$dia] ?? $dia;
}
$serverDayPt = normalizarDiaServer(strtolower(date('l')));

echo json_encode([
    'horariosPermitidos' => $horariosPermitidos,
    'serverTime' => $serverTime,
    'serverDay' => $serverDay,
    'serverDayPt' => $serverDayPt
]);
