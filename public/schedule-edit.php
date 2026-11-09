<?php
require_once __DIR__ . '/../vendor/autoload.php';

session_start();

use App\Core\Auth;
use App\Core\Database;

$auth = Auth::getInstance();
$auth->requireRole('admin');

$db = Database::getInstance();
$error = '';
$success = '';

// Récupérer les données pour les listes déroulantes
$classes = $db->fetchAll("SELECT * FROM classes ORDER BY name");
$subjects = $db->fetchAll("SELECT * FROM subjects ORDER BY name");
$rooms = $db->fetchAll("SELECT * FROM rooms ORDER BY name");
$teachers = $db->fetchAll("SELECT * FROM teachers ORDER BY name");

// Récupérer l'ID du cours à modifier
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$schedule = null;

if ($id) {
    $schedule = $db->fetch(
        "SELECT * FROM schedules WHERE id = ?",
        [$id]
    );
    if (!$schedule) {
        header('Location: /schedule-management.php');
        exit;
    }
}

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $classId = filter_input(INPUT_POST, 'class_id', FILTER_VALIDATE_INT);
    $subjectId = filter_input(INPUT_POST, 'subject_id', FILTER_VALIDATE_INT);
    $teacherId = filter_input(INPUT_POST, 'teacher_id', FILTER_VALIDATE_INT);
    $roomId = filter_input(INPUT_POST, 'room_id', FILTER_VALIDATE_INT);
    $day = filter_input(INPUT_POST, 'day', FILTER_SANITIZE_STRING);
    $startTime = filter_input(INPUT_POST, 'start_time', FILTER_SANITIZE_STRING);
    $endTime = filter_input(INPUT_POST, 'end_time', FILTER_SANITIZE_STRING);

    if ($classId && $subjectId && $teacherId && $roomId && $day && $startTime && $endTime) {
        // Vérifier les conflits d'horaires
        $conflicts = $db->fetchAll(
            "SELECT * FROM schedules 
             WHERE id != ? AND (
                (class_id = ? AND day_of_week = ? AND ((start_time <= ? AND end_time > ?) OR (start_time < ? AND end_time >= ?)))
                OR (teacher_id = ? AND day_of_week = ? AND ((start_time <= ? AND end_time > ?) OR (start_time < ? AND end_time >= ?)))
                OR (room_id = ? AND day_of_week = ? AND ((start_time <= ? AND end_time > ?) OR (start_time < ? AND end_time >= ?)))
             )",
            [
                $id ?: 0,
                $classId, $day, $startTime, $startTime, $endTime, $endTime,
                $teacherId, $day, $startTime, $startTime, $endTime, $endTime,
                $roomId, $day, $startTime, $startTime, $endTime, $endTime
            ]
        );

        if (!empty($conflicts)) {
            $error = 'Conflit d\'horaires détecté : ';
            foreach ($conflicts as $conflict) {
                $error .= sprintf(
                    'Le %s de %s à %s, ',
                    $conflict['day_of_week'],
                    date('H:i', strtotime($conflict['start_time'])),
                    date('H:i', strtotime($conflict['end_time']))
                );
            }
        } else {
            $data = [
                'class_id' => $classId,
                'subject_id' => $subjectId,
                'teacher_id' => $teacherId,
                'room_id' => $roomId,
                'day_of_week' => $day,
                'start_time' => $startTime,
                'end_time' => $endTime
            ];

            if ($id) {
                if ($db->update('schedules', $data, 'id = ?', [$id])) {
                    $success = 'Cours modifié avec succès';
                    header('Location: /schedule-management.php');
                    exit;
                } else {
                    $error = 'Erreur lors de la modification du cours';
                }
            } else {
                if ($db->insert('schedules', $data)) {
                    $success = 'Cours ajouté avec succès';
                    header('Location: /schedule-management.php');
                    exit;
                } else {
                    $error = 'Erreur lors de l\'ajout du cours';
                }
            }
        }
    } else {
        $error = 'Veuillez remplir tous les champs';
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $id ? 'Modifier' : 'Ajouter'; ?> un cours - Gestion Emploi du Temps</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="/php_calendrier/public/css/theme.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-custom">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">Gestion Emploi du Temps</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">Tableau de bord</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="users.php">Utilisateurs</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="classes.php">Classes</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="schedule-management.php">Emplois du temps</a>
                    </li>
                </ul>
                <div class="navbar-nav">
                    <div class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                            <?php echo htmlspecialchars($auth->getUser()['first_name'] . ' ' . $auth->getUser()['last_name']); ?>
                        </a>
                        <div class="dropdown-menu dropdown-menu-end">
                            <a class="dropdown-item" href="profile.php">Mon profil</a>
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item" href="logout.php">Déconnexion</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0"><?php echo $id ? 'Modifier' : 'Ajouter'; ?> un cours</h5>
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="class_id" class="form-label">Classe</label>
                            <select class="form-select" id="class_id" name="class_id" required>
                                <option value="">Sélectionnez une classe</option>
                                <?php foreach ($classes as $class): ?>
                                    <option value="<?php echo $class['id']; ?>" <?php echo $schedule && $schedule['class_id'] == $class['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($class['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label for="subject_id" class="form-label">Matière</label>
                            <select class="form-select" id="subject_id" name="subject_id" required>
                                <option value="">Sélectionnez une matière</option>
                                <?php foreach ($subjects as $subject): ?>
                                    <option value="<?php echo $subject['id']; ?>" <?php echo $schedule && $schedule['subject_id'] == $subject['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($subject['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label for="teacher_id" class="form-label">Enseignant</label>
                            <select class="form-select" id="teacher_id" name="teacher_id" required>
                                <option value="">Sélectionnez un enseignant</option>
                                <?php foreach ($teachers as $teacher): ?>
                                    <option value="<?php echo $teacher['id']; ?>" <?php echo $schedule && $schedule['teacher_id'] == $teacher['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($teacher['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label for="room_id" class="form-label">Salle</label>
                            <select class="form-select" id="room_id" name="room_id" required>
                                <option value="">Sélectionnez une salle</option>
                                <?php foreach ($rooms as $room): ?>
                                    <option value="<?php echo $room['id']; ?>" <?php echo $schedule && $schedule['room_id'] == $room['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($room['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label for="day" class="form-label">Jour</label>
                            <select class="form-select" id="day" name="day" required>
                                <option value="">Sélectionnez un jour</option>
                                <option value="monday" <?php echo $schedule && $schedule['day_of_week'] === 'monday' ? 'selected' : ''; ?>>Lundi</option>
                                <option value="tuesday" <?php echo $schedule && $schedule['day_of_week'] === 'tuesday' ? 'selected' : ''; ?>>Mardi</option>
                                <option value="wednesday" <?php echo $schedule && $schedule['day_of_week'] === 'wednesday' ? 'selected' : ''; ?>>Mercredi</option>
                                <option value="thursday" <?php echo $schedule && $schedule['day_of_week'] === 'thursday' ? 'selected' : ''; ?>>Jeudi</option>
                                <option value="friday" <?php echo $schedule && $schedule['day_of_week'] === 'friday' ? 'selected' : ''; ?>>Vendredi</option>
                                <option value="saturday" <?php echo $schedule && $schedule['day_of_week'] === 'saturday' ? 'selected' : ''; ?>>Samedi</option>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label for="start_time" class="form-label">Heure de début</label>
                            <input type="time" class="form-control" id="start_time" name="start_time" 
                                   value="<?php echo $schedule ? date('H:i', strtotime($schedule['start_time'])) : ''; ?>" required min="07:00" max="18:00">
                        </div>

                        <div class="col-md-4">
                            <label for="end_time" class="form-label">Heure de fin</label>
                            <input type="time" class="form-control" id="end_time" name="end_time" 
                                   value="<?php echo $schedule ? date('H:i', strtotime($schedule['end_time'])) : ''; ?>" required min="07:00" max="18:00">
                        </div>

                        <div class="col-12">
                            <button type="submit" class="btn btn-primary"><?php echo $id ? 'Modifier' : 'Ajouter'; ?></button>
                            <a href="schedule-management.php" class="btn btn-secondary">Annuler</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 