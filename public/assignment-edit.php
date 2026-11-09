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

// Récupérer les données pour les listes déroulantes
$classes = $db->fetchAll("SELECT * FROM classes ORDER BY name");
$subjects = $db->fetchAll("SELECT * FROM subjects ORDER BY name");

// Récupérer l'ID du devoir à modifier
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$assignment = null;

if ($id) {
    $assignment = $db->fetch(
        "SELECT * FROM assignments WHERE id = ? AND teacher_id = ?",
        [$id, $user['id']]
    );
    if (!$assignment) {
        header('Location: /assignments.php');
        exit;
    }
}

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $classId = filter_input(INPUT_POST, 'class_id', FILTER_VALIDATE_INT);
    $subjectId = filter_input(INPUT_POST, 'subject_id', FILTER_VALIDATE_INT);
    $title = filter_input(INPUT_POST, 'title', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $type = filter_input(INPUT_POST, 'type', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $dueDate = filter_input(INPUT_POST, 'due_date', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $dueTime = filter_input(INPUT_POST, 'due_time', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

    if ($classId && $subjectId && $title && $description && $type && $dueDate && $dueTime) {
        $data = [
            'class_id' => $classId,
            'subject_id' => $subjectId,
            'title' => $title,
            'description' => $description,
            'type' => $type,
            'due_date' => $dueDate . ' ' . $dueTime,
            'teacher_id' => $user['id']
        ];

        // Gestion des pièces jointes
        $attachments = [];
        if (!empty($_FILES['attachments']['name'][0])) {
            $uploadDir = __DIR__ . '/../uploads/assignments/';
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            foreach ($_FILES['attachments']['tmp_name'] as $key => $tmp_name) {
                $fileName = $_FILES['attachments']['name'][$key];
                $fileType = $_FILES['attachments']['type'][$key];
                $fileSize = $_FILES['attachments']['size'][$key];
                $fileError = $_FILES['attachments']['error'][$key];

                if ($fileError === UPLOAD_ERR_OK) {
                    $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                    $newFileName = uniqid() . '.' . $fileExt;
                    $filePath = $uploadDir . $newFileName;

                    if (move_uploaded_file($tmp_name, $filePath)) {
                        $attachments[] = [
                            'name' => $fileName,
                            'path' => 'uploads/assignments/' . $newFileName,
                            'type' => $fileType,
                            'size' => $fileSize
                        ];
                    }
                }
            }
        }

        if ($id) {
            if ($db->update('assignments', $data, 'id = ?', [$id])) {
                // Supprimer les anciennes pièces jointes
                $db->delete('assignment_attachments', 'assignment_id = ?', [$id]);

                // Ajouter les nouvelles pièces jointes
                foreach ($attachments as $attachment) {
                    $db->insert('assignment_attachments', [
                        'assignment_id' => $id,
                        'file_name' => $attachment['name'],
                        'file_path' => $attachment['path'],
                        'file_type' => $attachment['type'],
                        'file_size' => $attachment['size']
                    ]);
                }

                $success = 'Devoir modifié avec succès';
                header('Location: /assignments.php');
                exit;
            } else {
                $error = 'Erreur lors de la modification du devoir';
            }
        } else {
            if ($db->insert('assignments', $data)) {
                $assignmentId = $db->lastInsertId();

                // Ajouter les pièces jointes
                foreach ($attachments as $attachment) {
                    $db->insert('assignment_attachments', [
                        'assignment_id' => $assignmentId,
                        'file_name' => $attachment['name'],
                        'file_path' => $attachment['path'],
                        'file_type' => $attachment['type'],
                        'file_size' => $attachment['size']
                    ]);
                }

                $success = 'Devoir ajouté avec succès';
                header('Location: /assignments.php');
                exit;
            } else {
                $error = 'Erreur lors de l\'ajout du devoir';
            }
        }
    } else {
        $error = 'Veuillez remplir tous les champs';
    }
}

// Récupérer les pièces jointes existantes
$attachments = [];
if ($id) {
    $attachments = $db->fetchAll(
        "SELECT * FROM assignment_attachments WHERE assignment_id = ?",
        [$id]
    );
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $id ? 'Modifier' : 'Ajouter'; ?> un devoir - Gestion Emploi du Temps</title>
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
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0"><?php echo $id ? 'Modifier' : 'Ajouter'; ?> un devoir</h5>
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>

                <form method="POST" action="" enctype="multipart/form-data">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="class_id" class="form-label">Classe</label>
                            <select class="form-select" id="class_id" name="class_id" required>
                                <option value="">Sélectionnez une classe</option>
                                <?php foreach ($classes as $class): ?>
                                    <option value="<?php echo $class['id']; ?>" <?php echo $assignment && $assignment['class_id'] == $class['id'] ? 'selected' : ''; ?>>
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
                                    <option value="<?php echo $subject['id']; ?>" <?php echo $assignment && $assignment['subject_id'] == $subject['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($subject['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-12">
                            <label for="title" class="form-label">Titre</label>
                            <input type="text" class="form-control" id="title" name="title" 
                                   value="<?php echo $assignment ? htmlspecialchars($assignment['title']) : ''; ?>" required>
                        </div>

                        <div class="col-md-12">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="5" required><?php 
                                echo $assignment ? htmlspecialchars($assignment['description']) : ''; 
                            ?></textarea>
                        </div>

                        <div class="col-md-4">
                            <label for="type" class="form-label">Type</label>
                            <select class="form-select" id="type" name="type" required>
                                <option value="">Sélectionnez un type</option>
                                <option value="homework" <?php echo $assignment && $assignment['type'] === 'homework' ? 'selected' : ''; ?>>Devoir</option>
                                <option value="exam" <?php echo $assignment && $assignment['type'] === 'exam' ? 'selected' : ''; ?>>Examen</option>
                                <option value="project" <?php echo $assignment && $assignment['type'] === 'project' ? 'selected' : ''; ?>>Projet</option>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label for="due_date" class="form-label">Date limite</label>
                            <input type="date" class="form-control" id="due_date" name="due_date" 
                                   value="<?php echo $assignment ? date('Y-m-d', strtotime($assignment['due_date'])) : ''; ?>" required>
                        </div>

                        <div class="col-md-4">
                            <label for="due_time" class="form-label">Heure limite</label>
                            <input type="time" class="form-control" id="due_time" name="due_time" 
                                   value="<?php echo $assignment ? date('H:i', strtotime($assignment['due_date'])) : ''; ?>" required>
                        </div>

                        <div class="col-md-12">
                            <label for="attachments" class="form-label">Pièces jointes</label>
                            <input type="file" class="form-control" id="attachments" name="attachments[]" multiple>
                            <small class="text-muted">Vous pouvez sélectionner plusieurs fichiers</small>

                            <?php if (!empty($attachments)): ?>
                                <div class="mt-3">
                                    <h6>Pièces jointes actuelles :</h6>
                                    <ul class="list-group">
                                        <?php foreach ($attachments as $attachment): ?>
                                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                                <div>
                                                    <i class="bi bi-file-earmark"></i>
                                                    <?php echo htmlspecialchars($attachment['file_name']); ?>
                                                    <small class="text-muted">
                                                        (<?php echo number_format($attachment['file_size'] / 1024, 2); ?> KB)
                                                    </small>
                                                </div>
                                                <a href="assignment-delete-attachment.php?id=<?php echo $attachment['id']; ?>" 
                                                   class="btn btn-sm btn-danger"
                                                   onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette pièce jointe ?')">
                                                    <i class="bi bi-trash"></i>
                                                </a>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="col-12">
                            <button type="submit" class="btn btn-primary"><?php echo $id ? 'Modifier' : 'Ajouter'; ?></button>
                            <a href="assignments.php" class="btn btn-secondary">Annuler</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 