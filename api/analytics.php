<?php
require_once '../config.php';

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$db = getDBConnection();

if ($method !== 'GET') {
    jsonResponse(['error' => 'Método no permitido'], 405);
}

$quizId = intval($_GET['quiz_id'] ?? 0);

if (!$quizId) {
    jsonResponse(['error' => 'quiz_id requerido'], 400);
}

// Estadísticas por pregunta
$stmt = $db->prepare("
    SELECT 
        q.id as question_id,
        q.text as question_text,
        q.question_order,
        COUNT(DISTINCT a.attempt_id) as total_attempts,
        SUM(CASE WHEN aa.is_correct = 1 THEN 1 ELSE 0 END) as correct_answers,
        SUM(CASE WHEN aa.is_correct = 0 THEN 1 ELSE 0 END) as incorrect_answers,
        ROUND(SUM(CASE WHEN aa.is_correct = 1 THEN 1 ELSE 0 END) * 100.0 / COUNT(DISTINCT a.attempt_id), 2) as success_rate
    FROM quiz_questions q
    LEFT JOIN quiz_attempts a ON q.quiz_id = a.quiz_id
    LEFT JOIN quiz_attempt_answers aa ON q.id = aa.question_id AND aa.attempt_id = a.id AND aa.is_selected = 1
    WHERE q.quiz_id = ?
    GROUP BY q.id, q.text, q.question_order
    ORDER BY q.question_order
");
$stmt->execute([$quizId]);
$questionStats = $stmt->fetchAll();

// Estadísticas generales
$stmt = $db->prepare("
    SELECT 
        COUNT(DISTINCT a.id) as total_attempts,
        COUNT(DISTINCT a.participant_name) as total_participants,
        AVG(a.percentage) as average_percentage,
        MAX(a.total_points) as max_points_achieved,
        MIN(a.total_points) as min_points_achieved
    FROM quiz_attempts a
    WHERE a.quiz_id = ?
");
$stmt->execute([$quizId]);
$generalStats = $stmt->fetch();

// Distribución de opciones por pregunta
$stmt = $db->prepare("
    SELECT 
        q.id as question_id,
        o.id as option_id,
        o.text as option_text,
        o.is_correct,
        COUNT(aa.id) as times_selected
    FROM quiz_questions q
    JOIN quiz_question_options o ON q.id = o.question_id
    LEFT JOIN quiz_attempt_answers aa ON o.id = aa.option_id AND aa.is_selected = 1
    LEFT JOIN quiz_attempts a ON aa.attempt_id = a.id AND a.quiz_id = ?
    WHERE q.quiz_id = ?
    GROUP BY q.id, o.id, o.text, o.is_correct
    ORDER BY q.question_order, o.option_order
");
$stmt->execute([$quizId, $quizId]);
$optionStats = $stmt->fetchAll();

// Organizar opciones por pregunta
$optionsByQuestion = [];
foreach ($optionStats as $opt) {
    $qId = $opt['question_id'];
    if (!isset($optionsByQuestion[$qId])) {
        $optionsByQuestion[$qId] = [];
    }
    $optionsByQuestion[$qId][] = $opt;
}

// Agregar opciones a las estadísticas de preguntas
foreach ($questionStats as &$stat) {
    $stat['options'] = $optionsByQuestion[$stat['question_id']] ?? [];
}

jsonResponse([
    'question_stats' => $questionStats,
    'general_stats' => $generalStats,
    'quiz_id' => $quizId
]);
?>


