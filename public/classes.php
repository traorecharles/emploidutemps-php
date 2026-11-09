<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/config.php';

use App\Core\Auth;
use App\Core\Database;

$auth = Auth::getInstance();
$auth->requireRole('admin');

$db = Database::getInstance();
$error = '';
$success = '';

// Traitement de l'ajout/modification d'une classe
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
    $description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING);

    if ($name) {
        if ($action === 'add') {
            if ($db->insert('classes', [
                'name' => $name,
                'description' => $description
            ])) {
                $success = 'Classe ajoutée avec succès';
            } else {
                $error = 'Erreur lors de l\'ajout de la classe';
            }
        } elseif ($action === 'edit') {
            $id = filter_input(INPUT_POST, 'class_id', FILTER_VALIDATE_INT);
            if ($id && $db->update('classes', [
                'name' => $name,
                'description' => $description
            ], 'id = ?', [$id])) {
                $success = 'Classe modifiée avec succès';
            } else {
                $error = 'Erreur lors de la modification de la classe';
            }
        }
    } else {
        $error = 'Veuillez remplir tous les champs obligatoires';
    }
}

// Traitement de la suppression
if (isset($_GET['delete'])) {
    $id = filter_input(INPUT_GET, 'delete', FILTER_VALIDATE_INT);
    if ($id) {
        // Vérifier si la classe a des étudiants
        $students = $db->fetch("SELECT COUNT(*) as count FROM users WHERE class_id = ?", [$id]);
        if ($students['count'] > 0) {
            $error = 'Impossible de supprimer cette classe car elle contient des étudiants';
        } else {
            if ($db->delete('classes', 'id = ?', [$id])) {
                $success = 'Classe supprimée avec succès';
            } else {
                $error = 'Erreur lors de la suppression de la classe';
            }
        }
    }
}

// Récupération des classes
$classes = $db->fetchAll("SELECT c.*, 
    (SELECT COUNT(*) FROM users WHERE class_id = c.id AND role = 'student') as student_count 
    FROM classes c ORDER BY name");

// Récupération d'une classe spécifique pour l'édition
$editClass = null;
if (isset($_GET['edit'])) {
    $id = filter_input(INPUT_GET, 'edit', FILTER_VALIDATE_INT);
    if ($id) {
        $editClass = $db->fetch("SELECT * FROM classes WHERE id = ?", [$id]);
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des classes - Administration</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="/php_calendrier/public/css/theme.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
    <?php require_once __DIR__ . '/../src/views/header.php'; ?>

    <div class="container mt-4">
        <div class="row">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0"><?php echo $editClass ? 'Modifier une classe' : 'Ajouter une classe'; ?></h5>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                        <?php endif; ?>

                        <?php if ($success): ?>
                            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                        <?php endif; ?>

                        <form method="POST" action="">
                            <input type="hidden" name="action" value="<?php echo $editClass ? 'edit' : 'add'; ?>">
                            <?php if ($editClass): ?>
                                <input type="hidden" name="class_id" value="<?php echo $editClass['id']; ?>">
                            <?php endif; ?>

                            <div class="mb-3">
                                <label for="name" class="form-label">Nom de la classe *</label>
                                <input type="text" class="form-control" id="name" name="name" 
                                       value="<?php echo $editClass ? htmlspecialchars($editClass['name']) : ''; ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control" id="description" name="description" rows="3"><?php 
                                    echo $editClass ? htmlspecialchars($editClass['description']) : ''; 
                                ?></textarea>
                            </div>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">
                                    <?php echo $editClass ? 'Modifier' : 'Ajouter'; ?>
                                </button>
                                <?php if ($editClass): ?>
                                    <a href="classes.php" class="btn btn-secondary mt-2">Annuler</a>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Liste des classes</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Nom</th>
                                        <th>Description</th>
                                        <th>Étudiants</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($classes as $class): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($class['name']); ?></td>
                                            <td><?php echo htmlspecialchars($class['description'] ?? ''); ?></td>
                                            <td><?php echo $class['student_count']; ?></td>
                                            <td>
                                                <div class="btn-group">
                                                    <a href="?edit=<?php echo $class['id']; ?>" class="btn btn-sm btn-warning">
                                                        <i class="bi bi-pencil"></i>
                                                    </a>
                                                    <?php if ($class['student_count'] == 0): ?>
                                                        <a href="?delete=<?php echo $class['id']; ?>" 
                                                           class="btn btn-sm btn-danger"
                                                           onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette classe ?')">
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
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 