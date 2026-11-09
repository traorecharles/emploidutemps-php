<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/config.php';
use App\Core\Auth;
$auth = Auth::getInstance();
$auth->requireLogin();
$user = $auth->getUser();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de bord</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="/php_calendrier/public/css/theme.css" rel="stylesheet">
</head>
<body>
<?php require_once __DIR__ . '/../src/views/header.php'; ?>
<div class="container mt-4">
    <div class="card">
        <div class="card-body">
            <h2 class="mb-4">Bienvenue, <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?> !</h2>
            <?php if ($user['role'] === 'admin'): ?>
                <p>Vous êtes administrateur. Vous pouvez gérer les utilisateurs, les classes et les emplois du temps.</p>
                <a href="users.php" class="btn btn-primary">Gérer les utilisateurs</a>
                <a href="classes.php" class="btn btn-secondary">Gérer les classes</a>
                <a href="schedule-management.php" class="btn btn-success">Gérer les emplois du temps</a>
            <?php elseif ($user['role'] === 'teacher'): ?>
                <p>Vous êtes enseignant. Vous pouvez consulter et gérer vos emplois du temps et devoirs.</p>
                <a href="schedule-management.php" class="btn btn-primary">Voir mon emploi du temps</a>
                <a href="assignments.php" class="btn btn-secondary">Gérer mes devoirs</a>
            <?php elseif ($user['role'] === 'student'): ?>
                <p>Vous êtes étudiant. Vous pouvez consulter votre emploi du temps et vos devoirs.</p>
                <a href="schedule-management.php" class="btn btn-primary">Voir mon emploi du temps</a>
                <a href="assignments.php" class="btn btn-secondary">Voir mes devoirs</a>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../src/views/footer.php'; ?>
</body>
</html> 