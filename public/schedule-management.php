<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/config.php';
use App\Core\Auth;
$auth = Auth::getInstance();
$auth->requireLogin();
$user = $auth->getUser();

// Connexion DB
$db = new PDO("mysql:host=localhost;dbname=school_schedule;charset=utf8", 'root', '');

// Ajout d'un événement (cours)
$error = '';
$success = '';
// D'abord la modification
if (($user['role'] === 'admin' || $user['role'] === 'teacher') && isset($_POST['edit_id'])) {
    $edit_id = (int)$_POST['edit_id'];
    $class_id = $_POST['class_id'] ?? '';
    $subject = $_POST['subject'] ?? '';
    $room = $_POST['room'] ?? '';
    $day = $_POST['day'] ?? '';
    $start = $_POST['start_time'] ?? '';
    $end = $_POST['end_time'] ?? '';
    if ($user['role'] === 'teacher') {
        $fullName = trim($user['first_name'] . (isset($user['last_name']) ? ' ' . $user['last_name'] : ''));
        $normalizedFullName = normalizeName($fullName);
        $teachersList = $db->query("SELECT id, name FROM teachers")->fetchAll(PDO::FETCH_ASSOC);
        $teacher_id = null;
        foreach ($teachersList as $teacher) {
            if (normalizeName($teacher['name']) === $normalizedFullName) {
                $teacher_id = $teacher['id'];
                break;
            }
        }
        if (!$teacher_id) {
            $error = "Impossible de trouver votre nom dans la liste des enseignants (vérifiez l'orthographe ou contactez l'administrateur).";
        }
    } else {
        $teacher_id = $_POST['teacher_id'] ?? null;
        if (!$teacher_id) {
            $error = "Aucun enseignant sélectionné.";
        }
    }
    if ($class_id && $subject && $room && $day && $start && $end) {
        $where = $user['role'] === 'teacher' ? 'id = ? AND teacher_id = ?' : 'id = ?';
        $params = $user['role'] === 'teacher' ? [$edit_id, $teacher_id] : [$edit_id];
        $sql = "UPDATE courses SET class_id=?, subject_id=?, teacher_id=?, room_id=?, day_of_week=?, start_time=?, end_time=? WHERE $where";
        $stmt = $db->prepare($sql);
        $stmt->execute([$class_id, $subject, $teacher_id, $room, $day, $start, $end, ...$params]);
        header('Location: schedule-management.php?success=2');
        exit;
    } else {
        $error = 'Veuillez remplir tous les champs.';
    }
// Ensuite l'ajout
} elseif (($user['role'] === 'admin' || $user['role'] === 'teacher') && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $class_id = $_POST['class_id'] ?? '';
    $subject = $_POST['subject'] ?? '';
    $room = $_POST['room'] ?? '';
    $day = $_POST['day'] ?? '';
    $start = $_POST['start_time'] ?? '';
    $end = $_POST['end_time'] ?? '';
    if ($user['role'] === 'teacher') {
        $fullName = trim($user['first_name'] . (isset($user['last_name']) ? ' ' . $user['last_name'] : ''));
        $normalizedFullName = normalizeName($fullName);
        $teachersList = $db->query("SELECT id, name FROM teachers")->fetchAll(PDO::FETCH_ASSOC);
        $teacher_id = null;
        foreach ($teachersList as $teacher) {
            if (normalizeName($teacher['name']) === $normalizedFullName) {
                $teacher_id = $teacher['id'];
                break;
            }
        }
        if (!$teacher_id) {
            $error = "Impossible de trouver votre nom dans la liste des enseignants (vérifiez l'orthographe ou contactez l'administrateur).";
        }
    } else {
        $teacher_id = $_POST['teacher_id'] ?? null;
        if (!$teacher_id) {
            $error = "Aucun enseignant sélectionné.";
        }
    }
    if (empty($error) && $class_id && $subject && $room && $day && $start && $end) {
        $stmt = $db->prepare("INSERT INTO courses (class_id, subject_id, teacher_id, room_id, day_of_week, start_time, end_time) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$class_id, $subject, $teacher_id, $room, $day, $start, $end]);
        header('Location: schedule-management.php?success=1');
        exit;
    } else if (empty($error)) {
        $error = 'Veuillez remplir tous les champs.';
    }
}

// Récupération des messages
if (isset($_GET['success'])) {
    if ($_GET['success'] == 1) {
        $success = 'Événement ajouté !';
    } elseif ($_GET['success'] == 2) {
        $success = 'Événement modifié !';
    }
}

// Suppression d'un événement (cours)
if (($user['role'] === 'admin' || $user['role'] === 'teacher') && isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $course_id = (int)$_GET['delete'];
    // Un enseignant ne peut supprimer que ses propres cours
    $where = $user['role'] === 'teacher' ? 'id = ? AND teacher_id = ?' : 'id = ?';
    $params = $user['role'] === 'teacher' ? [$course_id, $user['id']] : [$course_id];
    $stmt = $db->prepare("DELETE FROM courses WHERE $where");
    $stmt->execute($params);
    $success = 'Événement supprimé !';
}

// Récupération des cours
if ($user['role'] === 'student') {
    $courses = $db->prepare("SELECT c.*, cl.name as class_name, s.name as subject_name, CONCAT(u.first_name, ' ', u.last_name) as teacher_name, r.name as room_name FROM courses c JOIN classes cl ON c.class_id = cl.id JOIN subjects s ON c.subject_id = s.id JOIN users u ON c.teacher_id = u.id JOIN rooms r ON c.room_id = r.id WHERE c.class_id = ? ORDER BY c.day_of_week, c.start_time");
    $courses->execute([$user['class_id']]);
    $courses = $courses->fetchAll(PDO::FETCH_ASSOC);
} else {
    $courses = $db->query("SELECT c.*, cl.name as class_name, s.name as subject_name, CONCAT(u.first_name, ' ', u.last_name) as teacher_name, r.name as room_name FROM courses c JOIN classes cl ON c.class_id = cl.id JOIN subjects s ON c.subject_id = s.id JOIN users u ON c.teacher_id = u.id JOIN rooms r ON c.room_id = r.id ORDER BY c.day_of_week, c.start_time")->fetchAll(PDO::FETCH_ASSOC);
}

// Récupération des listes pour les formulaires
$classes = [
    ['id' => 1, 'name' => 'Prépa 1'],
    ['id' => 2, 'name' => 'Prépa 2'],
    ['id' => 3, 'name' => 'ING 1'],
    ['id' => 4, 'name' => 'ING 2'],
    ['id' => 5, 'name' => 'ING 3']
];
$subjects = $db->query("SELECT * FROM subjects ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
$rooms = [
    ['id' => 1, 'name' => 'Salle 1'],
    ['id' => 2, 'name' => 'Salle 2'],
    ['id' => 3, 'name' => 'Salle 3'],
    ['id' => 4, 'name' => 'Salle 4'],
    ['id' => 5, 'name' => 'Salle 5']
];
$teachers = $db->query("SELECT * FROM teachers ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
$days = ['Lundi','Mardi','Mercredi','Jeudi','Vendredi','Samedi'];

function normalizeName($str) {
    // Remplace les accents et met en minuscule
    $str = iconv('UTF-8', 'ASCII//TRANSLIT', $str);
    $str = strtolower($str);
    $str = preg_replace('/[^a-z0-9 ]/', '', $str);
    return trim($str);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion du calendrier</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="/php_calendrier/public/css/theme.css" rel="stylesheet">
    <!-- FullCalendar -->
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css" rel="stylesheet">
</head>
<body>
<?php require_once __DIR__ . '/../src/views/header.php'; ?>
<div class="container mt-4">
    <h2>Gestion du calendrier</h2>
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>

    <?php if (($user['role'] === 'admin' || $user['role'] === 'teacher') && !isset($_GET['edit'])): ?>
    <div class="card mb-4">
        <div class="card-header">Ajouter un événement</div>
        <div class="card-body">
            <form method="POST">
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label for="class_id" class="form-label">Classe</label>
                        <select name="class_id" id="class_id" class="form-select" required>
                            <option value="">Sélectionner</option>
                            <?php foreach ($classes as $cl): ?>
                                <option value="<?php echo $cl['id']; ?>"><?php echo htmlspecialchars($cl['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label for="subject" class="form-label">Matière</label>
                        <select name="subject" id="subject" class="form-select" required>
                            <option value="">Sélectionner</option>
                            <?php foreach ($subjects as $s): ?>
                                <option value="<?php echo $s['id']; ?>"><?php echo htmlspecialchars($s['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2 mb-3">
                        <label for="room" class="form-label">Salle</label>
                        <select name="room" id="room" class="form-select" required>
                            <option value="">Sélectionner</option>
                            <?php foreach ($rooms as $r): ?>
                                <option value="<?php echo $r['id']; ?>"><?php echo htmlspecialchars($r['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2 mb-3">
                        <label for="day" class="form-label">Jour</label>
                        <select name="day" id="day" class="form-select" required>
                            <option value="">Sélectionner</option>
                            <?php foreach ($days as $d): ?>
                                <option value="<?php echo $d; ?>"><?php echo $d; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-1 mb-3">
                        <label for="start_time" class="form-label">Début</label>
                        <input type="time" name="start_time" id="start_time" class="form-control" required min="07:00" max="18:00">
                    </div>
                    <div class="col-md-1 mb-3">
                        <label for="end_time" class="form-label">Fin</label>
                        <input type="time" name="end_time" id="end_time" class="form-control" required min="07:00" max="18:00">
                    </div>
                    <?php if ($user['role'] === 'admin'): ?>
                    <div class="col-md-3 mb-3">
                        <label for="teacher_id" class="form-label">Enseignant</label>
                        <select name="teacher_id" id="teacher_id" class="form-select" required>
                            <option value="">Sélectionner</option>
                            <?php foreach ($teachers as $t): ?>
                                <option value="<?php echo $t['id']; ?>" <?php if (isset($c) && $t['id'] == $c['teacher_id']) echo 'selected'; ?>><?php echo htmlspecialchars($t['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>
                </div>
                <button type="submit" class="btn btn-primary">Ajouter</button>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($user['role'] === 'student'): ?>
    <div class="card">
        <div class="card-header">Mon emploi du temps</div>
        <div class="card-body">
            <div id="calendar"></div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        var calendarEl = document.getElementById('calendar');
        var calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'timeGridWeek',
            locale: 'fr',
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,timeGridDay'
            },
            events: '/php_calendrier/public/fetch-courses.php',
            allDaySlot: false,
            slotMinTime: '08:00:00',
            slotMaxTime: '17:05:00',
            hiddenDays: [0],
            firstDay: 1,
            weekends: true,
            eventDisplay: 'block',
            eventTimeFormat: { hour: '2-digit', minute: '2-digit', hour12: false },
            height: 650
        });
        calendar.render();

        // Ajout d'une ligne de séparation à 12h00 et 13h00
        function addCustomSeparators() {
            // On cible les lignes horaires de 12h et 13h
            document.querySelectorAll('.fc-timegrid-slot').forEach(slot => {
                const label = slot.querySelector('.fc-timegrid-slot-label');
                if (label && (label.textContent.trim() === '12 h' || label.textContent.trim() === '13 h')) {
                    slot.style.borderTop = '3px solid #d9534f'; // rouge, épais
                } else {
                    slot.style.borderTop = '';
                }
            });
        }
        setTimeout(addCustomSeparators, 400);
        calendar.on('datesSet', addCustomSeparators);
    });
    </script>
    <?php else: ?>
    <div class="card">
        <div class="card-header">Emploi du temps</div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Classe</th>
                            <th>Matière</th>
                            <th>Enseignant</th>
                            <th>Salle</th>
                            <th>Jour</th>
                            <th>Début</th>
                            <th>Fin</th>
                            <?php if ($user['role'] === 'admin' || $user['role'] === 'teacher'): ?>
                            <th>Actions</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($courses as $c): ?>
                            <?php if (isset($_GET['edit']) && $_GET['edit'] == $c['id'] && ($user['role'] === 'admin' || ($user['role'] === 'teacher' && $c['teacher_id'] == $user['id']))): ?>
                            <form method="POST">
                                <tr>
                                    <input type="hidden" name="edit_id" value="<?php echo $c['id']; ?>">
                                    <td>
                                        <select name="class_id" class="form-select" required>
                                            <?php foreach ($classes as $cl): ?>
                                                <option value="<?php echo $cl['id']; ?>" <?php if ($cl['id'] == $c['class_id']) echo 'selected'; ?>><?php echo htmlspecialchars($cl['name']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                    <td>
                                        <select name="subject" class="form-select" required>
                                            <?php foreach ($subjects as $s): ?>
                                                <option value="<?php echo $s['id']; ?>" <?php if ($s['id'] == $c['subject_id']) echo 'selected'; ?>><?php echo htmlspecialchars($s['name']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                    <td>
                                        <?php if ($user['role'] === 'admin'): ?>
                                        <select name="teacher_id" class="form-select" required>
                                            <?php foreach ($teachers as $t): ?>
                                                <option value="<?php echo $t['id']; ?>" <?php if (isset($c) && $t['id'] == $c['teacher_id']) echo 'selected'; ?>><?php echo htmlspecialchars($t['name']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <?php else: ?>
                                        <?php echo htmlspecialchars($c['teacher_name']); ?>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <select name="room" class="form-select" required>
                                            <?php foreach ($rooms as $r): ?>
                                                <option value="<?php echo $r['id']; ?>" <?php if ($r['id'] == $c['room_id']) echo 'selected'; ?>><?php echo htmlspecialchars($r['name']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                    <td>
                                        <select name="day" class="form-select" required>
                                            <?php foreach ($days as $d): ?>
                                                <option value="<?php echo $d; ?>" <?php if ($d == $c['day_of_week']) echo 'selected'; ?>><?php echo $d; ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                    <td><input type="time" name="start_time" class="form-control" value="<?php echo htmlspecialchars(substr($c['start_time'],0,5)); ?>" required min="07:00" max="18:00"></td>
                                    <td><input type="time" name="end_time" class="form-control" value="<?php echo htmlspecialchars(substr($c['end_time'],0,5)); ?>" required min="07:00" max="18:00"></td>
                                    <td>
                                        <button type="submit" class="btn btn-success btn-sm">Enregistrer</button>
                                        <a href="schedule-management.php" class="btn btn-secondary btn-sm">Annuler</a>
                                    </td>
                                </tr>
                            </form>
                            <?php else: ?>
                            <tr>
                                <td><?php echo htmlspecialchars($c['class_name']); ?></td>
                                <td><?php echo htmlspecialchars($c['subject_name']); ?></td>
                                <td><?php echo htmlspecialchars($c['teacher_name']); ?></td>
                                <td><?php echo htmlspecialchars($c['room_name']); ?></td>
                                <td><?php echo htmlspecialchars($c['day_of_week']); ?></td>
                                <td><?php echo htmlspecialchars(substr($c['start_time'],0,5)); ?></td>
                                <td><?php echo htmlspecialchars(substr($c['end_time'],0,5)); ?></td>
                                <?php if ($user['role'] === 'admin' || ($user['role'] === 'teacher' && $c['teacher_id'] == $user['id'])): ?>
                                <td>
                                    <a href="?edit=<?php echo $c['id']; ?>" class="btn btn-warning btn-sm">Modifier</a>
                                    <a href="?delete=<?php echo $c['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Supprimer cet événement ?');">Supprimer</a>
                                </td>
                                <?php endif; ?>
                            </tr>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>
<?php require_once __DIR__ . '/../src/views/footer.php'; ?>
</body>
</html> 