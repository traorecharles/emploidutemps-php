<?php
require_once __DIR__ . '/../vendor/autoload.php';

session_start();

use App\Core\Auth;
use App\Core\Database;

$auth = Auth::getInstance();
$auth->requireRole('teacher');

$db = Database::getInstance();
$user = $auth->getUser();

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    header('Location: /assignments.php');
    exit;
}

// Récupérer le devoir et vérifier qu'il appartient au professeur
$assignment = $db->fetch(
    "SELECT a.*, s.name as subject_name, c.name as class_name
     FROM assignments a
     JOIN subjects s ON s.id = a.subject_id
     JOIN classes c ON c.id = a.class_id
     WHERE a.id = ? AND a.teacher_id = ?",
    [$id, $user['id']]
);

if (!$assignment) {
    header('Location: /assignments.php');
    exit;
}

// Traitement de la notation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submissionId = filter_input(INPUT_POST, 'submission_id', FILTER_VALIDATE_INT);
    $grade = filter_input(INPUT_POST, 'grade', FILTER_VALIDATE_FLOAT);
    $feedback = filter_input(INPUT_POST, 'feedback', FILTER_SANITIZE_STRING);

    if ($submissionId && $grade !== false) {
        $data = [
            'grade' => $grade,
            'feedback' => $feedback,
            'graded_at' => date('Y-m-d H:i:s')
        ];

        if ($db->update('assignment_submissions', $data, 'id = ?', [$submissionId])) {
            header('Location: /assignment-submissions.php?id=' . $id . '&success=1');
            exit;
        }
    }
}

// Récupérer les soumissions
$submissions = $db->fetchAll(
    "SELECT s.*, u.first_name, u.last_name
     FROM assignment_submissions s
     JOIN users u ON u.id = s.student_id
     WHERE s.assignment_id = ?
     ORDER BY s.submitted_at DESC",
    [$id]
);

// Récupérer les pièces jointes pour chaque soumission
foreach ($submissions as &$submission) {
    $submission['attachments'] = $db->fetchAll(
        "SELECT * FROM submission_attachments WHERE submission_id = ?",
        [$submission['id']]
    );
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Soumissions - <?php echo htmlspecialchars($assignment['title']); ?> - Gestion Emploi du Temps</title>
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
                        <a class="nav-link" href="availability.php">Disponibilités</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="assignments.php">Gérer les devoirs</a>
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
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">La note a été enregistrée avec succès.</div>
        <?php endif; ?>

        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">Soumissions - <?php echo htmlspecialchars($assignment['title']); ?></h5>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <p><strong>Matière :</strong> <?php echo htmlspecialchars($assignment['subject_name']); ?></p>
                        <p><strong>Classe :</strong> <?php echo htmlspecialchars($assignment['class_name']); ?></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Type :</strong> 
                            <?php
                            switch ($assignment['type']) {
                                case 'homework':
                                    echo 'Devoir';
                                    break;
                                case 'exam':
                                    echo 'Examen';
                                    break;
                                case 'project':
                                    echo 'Projet';
                                    break;
                            }
                            ?>
                        </p>
                        <p><strong>Date limite :</strong> <?php echo date('d/m/Y H:i', strtotime($assignment['due_date'])); ?></p>
                    </div>
                </div>

                <?php if (empty($submissions)): ?>
                    <div class="alert alert-info">Aucune soumission n'a encore été reçue.</div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Étudiant</th>
                                    <th>Date de soumission</th>
                                    <th>Statut</th>
                                    <th>Note</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($submissions as $submission): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($submission['first_name'] . ' ' . $submission['last_name']); ?></td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($submission['submitted_at'])); ?></td>
                                        <td>
                                            <?php
                                            $now = new DateTime();
                                            $dueDate = new DateTime($assignment['due_date']);
                                            $submittedDate = new DateTime($submission['submitted_at']);
                                            if ($submittedDate > $dueDate) {
                                                echo '<span class="text-danger">En retard</span>';
                                            } else {
                                                echo '<span class="text-success">À temps</span>';
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <?php if ($submission['grade'] !== null): ?>
                                                <?php echo number_format($submission['grade'], 2); ?>/20
                                            <?php else: ?>
                                                <span class="text-muted">Non noté</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#submissionModal<?php echo $submission['id']; ?>">
                                                Voir détails
                                            </button>
                                        </td>
                                    </tr>

                                    <!-- Modal pour les détails de la soumission -->
                                    <div class="modal fade" id="submissionModal<?php echo $submission['id']; ?>" tabindex="-1">
                                        <div class="modal-dialog modal-lg">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Soumission de <?php echo htmlspecialchars($submission['first_name'] . ' ' . $submission['last_name']); ?></h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <?php if ($submission['comment']): ?>
                                                        <div class="mb-3">
                                                            <h6>Commentaire de l'étudiant :</h6>
                                                            <p><?php echo nl2br(htmlspecialchars($submission['comment'])); ?></p>
                                                        </div>
                                                    <?php endif; ?>

                                                    <?php if (!empty($submission['attachments'])): ?>
                                                        <div class="mb-3">
                                                            <h6>Fichiers soumis :</h6>
                                                            <ul class="list-group">
                                                                <?php foreach ($submission['attachments'] as $attachment): ?>
                                                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                                                        <div>
                                                                            <i class="bi bi-file-earmark"></i>
                                                                            <a href="<?php echo $attachment['file_path']; ?>" target="_blank">
                                                                                <?php echo htmlspecialchars($attachment['file_name']); ?>
                                                                            </a>
                                                                            <small class="text-muted">
                                                                                (<?php echo number_format($attachment['file_size'] / 1024, 2); ?> KB)
                                                                            </small>
                                                                        </div>
                                                                    </li>
                                                                <?php endforeach; ?>
                                                            </ul>
                                                        </div>
                                                    <?php endif; ?>

                                                    <form method="POST" action="">
                                                        <input type="hidden" name="submission_id" value="<?php echo $submission['id']; ?>">
                                                        
                                                        <div class="mb-3">
                                                            <label for="grade<?php echo $submission['id']; ?>" class="form-label">Note (/20)</label>
                                                            <input type="number" class="form-control" id="grade<?php echo $submission['id']; ?>" 
                                                                   name="grade" min="0" max="20" step="0.5" 
                                                                   value="<?php echo $submission['grade']; ?>" required>
                                                        </div>

                                                        <div class="mb-3">
                                                            <label for="feedback<?php echo $submission['id']; ?>" class="form-label">Commentaire</label>
                                                            <textarea class="form-control" id="feedback<?php echo $submission['id']; ?>" 
                                                                      name="feedback" rows="3"><?php echo htmlspecialchars($submission['feedback']); ?></textarea>
                                                        </div>

                                                        <button type="submit" class="btn btn-primary">Enregistrer la note</button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 