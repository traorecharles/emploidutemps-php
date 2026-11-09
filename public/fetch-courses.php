<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/config.php';
use App\Core\Auth;
$auth = Auth::getInstance();
$auth->requireLogin();
$user = $auth->getUser();
header('Content-Type: application/json');

$db = new PDO("mysql:host=localhost;dbname=school_schedule;charset=utf8", 'root', '');

if ($user['role'] === 'student') {
    $stmt = $db->prepare("SELECT c.*, cl.name as class_name, s.name as subject_name, CONCAT(u.first_name, ' ', u.last_name) as teacher_name, r.name as room_name FROM courses c JOIN classes cl ON c.class_id = cl.id JOIN subjects s ON c.subject_id = s.id JOIN users u ON c.teacher_id = u.id JOIN rooms r ON c.room_id = r.id WHERE c.class_id = ?");
    $stmt->execute([$user['class_id']]);
    $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    echo json_encode([]);
    exit;
}

$creneaux = [
    ['start' => '08:00:00', 'end' => '10:00:00'],
    ['start' => '10:00:00', 'end' => '12:00:00'],
    ['start' => '13:00:00', 'end' => '14:55:00'],
    ['start' => '15:00:00', 'end' => '17:05:00'],
];
$days = ['Lundi'=>1,'Mardi'=>2,'Mercredi'=>3,'Jeudi'=>4,'Vendredi'=>5,'Samedi'=>6];
$events = [];
foreach ($courses as $c) {
    $dayNum = $days[$c['day_of_week']] ?? 1;
    $startOfWeek = new DateTime();
    $startOfWeek->setISODate((int)date('o'), (int)date('W'), 1);
    $eventDate = clone $startOfWeek;
    $eventDate->modify('+' . ($dayNum-1) . ' days');
    $start = $eventDate->format('Y-m-d') . 'T' . $c['start_time'];
    $end = $eventDate->format('Y-m-d') . 'T' . $c['end_time'];
    $events[] = [
        'id' => $c['id'],
        'title' => $c['subject_name'] . ' (' . $c['room_name'] . ')\n' . $c['teacher_name'],
        'start' => $start,
        'end' => $end,
        'description' => $c['subject_name'] . ' avec ' . $c['teacher_name'] . ' en ' . $c['room_name'],
    ];
}
echo json_encode($events); 