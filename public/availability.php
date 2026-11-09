<?php
require_once __DIR__ . '/../vendor/autoload.php';

session_start();

use App\Core\Auth;
use App\Core\Database;

$auth = Auth::getInstance();
$auth->requireRole('teacher');

$db = Database::getInstance();
$user = $auth->getUser();
$error = '';
$success = '';

// Récupérer les disponibilités existantes
$availabilities = $db->fetchAll(
    "SELECT * FROM teacher_availabilities WHERE teacher_id = ? ORDER BY day_of_week, start_time",
    [$user['id']]
);

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $day = filter_input(INPUT_POST, 'day', FILTER_SANITIZE_STRING);
    $startTime = filter_input(INPUT_POST, 'start_time', FILTER_SANITIZE_STRING);
    $endTime = filter_input(INPUT_POST, 'end_time', FILTER_SANITIZE_STRING);
    $status = filter_input(INPUT_POST, 'status', FILTER_SANITIZE_STRING);

    if ($day && $startTime && $endTime && $status) {
        // Vérifier si une disponibilité existe déjà pour ce créneau
        $existing = $db->fetch(
            "SELECT * FROM teacher_availabilities 
             WHERE teacher_id = ? AND day_of_week = ? 
             AND ((start_time <= ? AND end_time > ?) OR (start_time < ? AND end_time >= ?))",
            [$user['id'], $day, $startTime, $startTime, $endTime, $endTime]
        );

        if ($existing) {
            $error = 'Un créneau existe déjà pour cette période';
        } else {
            $data = [
                'teacher_id' => $user['id'],
                'day_of_week' => $day,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'status' => $status
            ];

            if ($db->insert('teacher_availabilities', $data)) {
                $success = 'Disponibilité ajoutée avec succès';
                header('Location: ' . $_SERVER['PHP_SELF']);
                exit;
            } else {
                $error = 'Erreur lors de l\'ajout de la disponibilité';
            }
        }
    } else {
        $error = 'Veuillez remplir tous les champs';
    }
}

// Suppression d'une disponibilité
if (isset($_GET['delete'])) {
    $id = filter_input(INPUT_GET, 'delete', FILTER_VALIDATE_INT);
    if ($id) {
        if ($db->delete('teacher_availabilities', 'id = ? AND teacher_id = ?', [$id, $user['id']])) {
            $success = 'Disponibilité supprimée avec succès';
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        } else {
            $error = 'Erreur lors de la suppression';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des disponibilités - Gestion Emploi du Temps</title>
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
                        <a class="nav-link" href="schedule.php">Mon planning</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="availability.php">Disponibilités</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="assignments.php">Gérer les devoirs</a>
                    </li>
                </ul>
                <div class="navbar-nav">
                    <div class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                            <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>
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
        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Mes disponibilités</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                        <?php endif; ?>

                        <?php if ($success): ?>
                            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                        <?php endif; ?>

                        <?php if (empty($availabilities)): ?>
                            <p class="text-muted">Aucune disponibilité définie</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Jour</th>
                                            <th>Horaire</th>
                                            <th>Statut</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($availabilities as $availability): ?>
                                            <tr>
                                                <td><?php echo ucfirst($availability['day_of_week']); ?></td>
                                                <td>
                                                    <?php 
                                                    echo date('H:i', strtotime($availability['start_time'])) . ' - ' . 
                                                         date('H:i', strtotime($availability['end_time'])); 
                                                    ?>
                                                </td>
                                                <td>
                                                    <span class="badge bg-<?php echo $availability['status'] === 'available' ? 'success' : 'danger'; ?>">
                                                        <?php echo $availability['status'] === 'available' ? 'Disponible' : 'Indisponible'; ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <a href="?delete=<?php echo $availability['id']; ?>" 
                                                       class="btn btn-sm btn-danger"
                                                       onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette disponibilité ?')">
                                                        <i class="bi bi-trash"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Ajouter une disponibilité</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="day" class="form-label">Jour</label>
                                <select class="form-select" id="day" name="day" required>
                                    <option value="">Sélectionnez un jour</option>
                                    <option value="monday">Lundi</option>
                                    <option value="tuesday">Mardi</option>
                                    <option value="wednesday">Mercredi</option>
                                    <option value="thursday">Jeudi</option>
                                    <option value="friday">Vendredi</option>
                                    <option value="saturday">Samedi</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="start_time" class="form-label">Heure de début</label>
                                <input type="time" class="form-control" id="start_time" name="start_time" required min="07:00" max="18:00">
                            </div>

                            <div class="mb-3">
                                <label for="end_time" class="form-label">Heure de fin</label>
                                <input type="time" class="form-control" id="end_time" name="end_time" required min="07:00" max="18:00">
                            </div>

                            <div class="mb-3">
                                <label for="status" class="form-label">Statut</label>
                                <select class="form-select" id="status" name="status" required>
                                    <option value="available">Disponible</option>
                                    <option value="unavailable">Indisponible</option>
                                </select>
                            </div>

                            <button type="submit" class="btn btn-primary w-100">Ajouter</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 