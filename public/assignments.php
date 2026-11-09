<?php
require_once __DIR__ . '/../vendor/autoload.php';

session_start();

use App\Core\Auth;
use App\Core\Database;

$auth = Auth::getInstance();
$auth->requireLogin();

$db = Database::getInstance();
$user = $auth->getUser();
$error = '';
$success = '';

// Récupérer les filtres
$classId = filter_input(INPUT_GET, 'class_id', FILTER_VALIDATE_INT);
$subjectId = filter_input(INPUT_GET, 'subject_id', FILTER_VALIDATE_INT);
$type = filter_input(INPUT_GET, 'type', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
$status = filter_input(INPUT_GET, 'status', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

// Récupérer les listes pour les filtres
$classes = $db->fetchAll("SELECT * FROM classes ORDER BY name");
$subjects = $db->fetchAll("SELECT * FROM subjects ORDER BY name");

// Construire la requête avec les filtres
$query = "SELECT a.*, c.name as class_name, sub.name as subject_name, 
          u.first_name, u.last_name
          FROM assignments a
          JOIN classes c ON a.class_id = c.id
          JOIN subjects sub ON a.subject_id = sub.id
          JOIN users u ON a.teacher_id = u.id
          WHERE 1=1";
$params = [];

if ($auth->hasRole('student')) {
    $query .= " AND a.class_id = ?";
    $params[] = $user['class_id'];
} elseif ($auth->hasRole('teacher')) {
    $query .= " AND a.teacher_id = ?";
    $params[] = $user['id'];
}

if ($classId) {
    $query .= " AND a.class_id = ?";
    $params[] = $classId;
}
if ($subjectId) {
    $query .= " AND a.subject_id = ?";
    $params[] = $subjectId;
}
if ($type) {
    $query .= " AND a.type = ?";
    $params[] = $type;
}
if ($status) {
    if ($status === 'upcoming') {
        $query .= " AND a.due_date >= CURDATE()";
    } elseif ($status === 'past') {
        $query .= " AND a.due_date < CURDATE()";
    }
}

$query .= " ORDER BY a.due_date DESC";

$assignments = $db->fetchAll($query, $params);

// Traitement de l'export
if (isset($_GET['export'])) {
    $format = $_GET['export'];
    if ($format === 'pdf' || $format === 'excel') {
        // TODO: Implémenter l'export PDF/Excel
        // Pour l'instant, on redirige vers une page d'erreur
        $error = "L'export n'est pas encore implémenté";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des devoirs et examens - Gestion Emploi du Temps</title>
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
                    <?php if ($auth->hasRole('student')): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="schedule.php">Mon emploi du temps</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="assignments.php">Devoirs & Examens</a>
                        </li>
                    <?php elseif ($auth->hasRole('teacher')): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="schedule.php">Mon planning</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="availability.php">Disponibilités</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="assignments.php">Gérer les devoirs</a>
                        </li>
                    <?php elseif ($auth->hasRole('admin')): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="users.php">Utilisateurs</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="classes.php">Classes</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="schedule-management.php">Emplois du temps</a>
                        </li>
                    <?php endif; ?>
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
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Gestion des devoirs et examens</h5>
                <?php if ($auth->hasRole('teacher')): ?>
                    <div class="btn-group">
                        <a href="assignment-edit.php" class="btn btn-primary btn-sm">
                            <i class="bi bi-plus"></i> Nouveau devoir
                        </a>
                        <a href="?export=pdf<?php echo $classId ? '&class_id=' . $classId : ''; ?>" class="btn btn-danger btn-sm">
                            <i class="bi bi-file-pdf"></i> Export PDF
                        </a>
                        <a href="?export=excel<?php echo $classId ? '&class_id=' . $classId : ''; ?>" class="btn btn-success btn-sm">
                            <i class="bi bi-file-excel"></i> Export Excel
                        </a>
                    </div>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>

                <!-- Filtres -->
                <form method="GET" class="row g-3 mb-4">
                    <div class="col-md-3">
                        <label for="class_id" class="form-label">Classe</label>
                        <select class="form-select" id="class_id" name="class_id">
                            <option value="">Toutes les classes</option>
                            <?php foreach ($classes as $class): ?>
                                <option value="<?php echo $class['id']; ?>" <?php echo $classId == $class['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($class['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="subject_id" class="form-label">Matière</label>
                        <select class="form-select" id="subject_id" name="subject_id">
                            <option value="">Toutes les matières</option>
                            <?php foreach ($subjects as $subject): ?>
                                <option value="<?php echo $subject['id']; ?>" <?php echo $subjectId == $subject['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($subject['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="type" class="form-label">Type</label>
                        <select class="form-select" id="type" name="type">
                            <option value="">Tous les types</option>
                            <option value="homework" <?php echo $type === 'homework' ? 'selected' : ''; ?>>Devoir</option>
                            <option value="exam" <?php echo $type === 'exam' ? 'selected' : ''; ?>>Examen</option>
                            <option value="project" <?php echo $type === 'project' ? 'selected' : ''; ?>>Projet</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="status" class="form-label">Statut</label>
                        <select class="form-select" id="status" name="status">
                            <option value="">Tous les statuts</option>
                            <option value="upcoming" <?php echo $status === 'upcoming' ? 'selected' : ''; ?>>À venir</option>
                            <option value="past" <?php echo $status === 'past' ? 'selected' : ''; ?>>Passés</option>
                        </select>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">Filtrer</button>
                    </div>
                </form>

                <!-- Liste des devoirs et examens -->
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Titre</th>
                                <th>Type</th>
                                <th>Classe</th>
                                <th>Matière</th>
                                <th>Date limite</th>
                                <?php if ($auth->hasRole('student')): ?>
                                    <th>Enseignant</th>
                                <?php endif; ?>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($assignments as $assignment): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($assignment['title']); ?></td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo $assignment['type'] === 'exam' ? 'danger' : 
                                                ($assignment['type'] === 'project' ? 'warning' : 'primary'); 
                                        ?>">
                                            <?php 
                                            echo $assignment['type'] === 'exam' ? 'Examen' : 
                                                ($assignment['type'] === 'project' ? 'Projet' : 'Devoir'); 
                                            ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($assignment['class_name']); ?></td>
                                    <td><?php echo htmlspecialchars($assignment['subject_name']); ?></td>
                                    <td>
                                        <?php 
                                        $dueDate = strtotime($assignment['due_date']);
                                        $now = time();
                                        $isPast = $dueDate < $now;
                                        ?>
                                        <span class="<?php echo $isPast ? 'text-danger' : ''; ?>">
                                            <?php echo date('d/m/Y H:i', $dueDate); ?>
                                        </span>
                                    </td>
                                    <?php if ($auth->hasRole('student')): ?>
                                        <td><?php echo htmlspecialchars($assignment['first_name'] . ' ' . $assignment['last_name']); ?></td>
                                    <?php endif; ?>
                                    <td>
                                        <div class="btn-group">
                                            <a href="assignment-view.php?id=<?php echo $assignment['id']; ?>" class="btn btn-sm btn-info">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <?php if ($auth->hasRole('teacher')): ?>
                                                <a href="assignment-edit.php?id=<?php echo $assignment['id']; ?>" class="btn btn-sm btn-primary">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <a href="assignment-delete.php?id=<?php echo $assignment['id']; ?>" 
                                                   class="btn btn-sm btn-danger"
                                                   onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce devoir ?')">
                                                    <i class="bi bi-trash"></i>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 