<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/config.php';

use App\Core\Auth;

$auth = Auth::getInstance();
$error = '';
$success = '';

// Récupération des classes pour le formulaire
$classes = [
    ['id' => 1, 'name' => 'Prépa 1'],
    ['id' => 2, 'name' => 'Prépa 2'],
    ['id' => 3, 'name' => 'ING 1'],
    ['id' => 4, 'name' => 'ING 2'],
    ['id' => 5, 'name' => 'ING 3'],
];

$class_id = isset($_POST['class_id']) ? $_POST['class_id'] : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $firstName = filter_input(INPUT_POST, 'first_name', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $lastName = filter_input(INPUT_POST, 'last_name', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $role = filter_input(INPUT_POST, 'role', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

    if ($email && $username && $password && $confirmPassword && $firstName && $lastName && $role && ($role !== 'student' || $class_id)) {
        if ($password === $confirmPassword) {
            if (strlen($password) >= 8) {
                $data = [
                    'email' => $email,
                    'username' => $username,
                    'password' => $password,
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'role' => $role
                ];
                if ($role === 'student') {
                    $data['class_id'] = $class_id;
                }

                if ($auth->register($data)) {
                    $success = 'Inscription réussie ! Veuillez attendre la validation de votre compte par un administrateur.';
                } else {
                    $error = "L'email ou le nom d'utilisateur est déjà pris.";
                }
            } else {
                $error = 'Le mot de passe doit contenir au moins 8 caractères';
            }
        } else {
            $error = 'Les mots de passe ne correspondent pas';
        }
    } else {
        $error = 'Veuillez remplir tous les champs correctement';
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription - Gestion Emploi du Temps</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="/php_calendrier/public/css/theme.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="auth-container">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-12">
                    <form method="POST" action="" class="auth-form">
                        <h2 class="text-center mb-4">Inscription</h2>
                        
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                        <?php endif; ?>

                        <?php if ($success): ?>
                            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                        <?php endif; ?>

                        <div class="row">
                            <div class="col-md-6 form-group">
                                <label for="last_name">Nom</label>
                                <input type="text" class="form-control" id="last_name" name="last_name" placeholder="Votre nom" required>
                            </div>

                            <div class="col-md-6 form-group">
                                <label for="first_name">Prénom</label>
                                <input type="text" class="form-control" id="first_name" name="first_name" placeholder="Votre prénom" required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 form-group">
                                <label for="email">Email</label>
                                <input type="email" class="form-control" id="email" name="email" placeholder="Votre email" required>
                            </div>

                            <div class="col-md-6 form-group">
                                <label for="username">Nom d'utilisateur</label>
                                <input type="text" class="form-control" id="username" name="username" placeholder="Votre nom d'utilisateur" required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 form-group">
                                <label for="role">Rôle</label>
                                <select class="form-control" id="role" name="role" required onchange="toggleClassField()">
                                    <option value="">Sélectionnez un rôle</option>
                                    <option value="student">Étudiant</option>
                                    <option value="teacher">Enseignant</option>
                                </select>
                            </div>

                            <div class="col-md-6 form-group" id="classField" style="display:none;">
                                <label for="class_id">Classe</label>
                                <select class="form-control" id="class_id" name="class_id">
                                    <option value="">Sélectionnez une classe</option>
                                    <?php foreach ($classes as $cl): ?>
                                        <option value="<?php echo $cl['id']; ?>"><?php echo htmlspecialchars($cl['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 form-group">
                                <label for="password">Mot de passe</label>
                                <input type="password" class="form-control" id="password" name="password" placeholder="Votre mot de passe" required>
                                <small class="form-text">Le mot de passe doit contenir au moins 8 caractères</small>
                            </div>

                            <div class="col-md-6 form-group">
                                <label for="confirm_password">Confirmer le mot de passe</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="Confirmez votre mot de passe" required>
                            </div>
                        </div>

                        <div class="text-center mt-4">
                            <button type="submit" class="btn btn-primary">S'inscrire</button>
                        </div>

                        <div class="text-center mt-3">
                            <a href="login.php" class="text-decoration-none">Déjà inscrit ? Se connecter</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Validation côté client
    document.querySelector('form').addEventListener('submit', function(e) {
        let valid = true;
        let errors = [];
        const username = document.getElementById('username').value.trim();
        const email = document.getElementById('email').value.trim();
        const password = document.getElementById('password').value;
        const confirmPassword = document.getElementById('confirm_password').value;
        const firstName = document.getElementById('first_name').value.trim();
        const lastName = document.getElementById('last_name').value.trim();
        const role = document.getElementById('role').value;

        // Vérification des champs obligatoires
        if (!firstName || !lastName || !username || !email || !password || !confirmPassword || !role) {
            errors.push('Veuillez remplir tous les champs.');
            valid = false;
        }
        // Vérification de l'email
        if (email && !/^\S+@\S+\.\S+$/.test(email)) {
            errors.push('Email invalide.');
            valid = false;
        }
        // Vérification du mot de passe
        if (password.length < 8) {
            errors.push('Le mot de passe doit contenir au moins 8 caractères.');
            valid = false;
        }
        if (!/[A-Z]/.test(password)) {
            errors.push('Le mot de passe doit contenir au moins une majuscule.');
            valid = false;
        }
        if (!/[0-9]/.test(password)) {
            errors.push('Le mot de passe doit contenir au moins un chiffre.');
            valid = false;
        }
        if (password !== confirmPassword) {
            errors.push('Les mots de passe ne correspondent pas.');
            valid = false;
        }
        // Affichage des erreurs
        if (!valid) {
            e.preventDefault();
            let errorDiv = document.querySelector('.alert-danger');
            if (!errorDiv) {
                errorDiv = document.createElement('div');
                errorDiv.className = 'alert alert-danger';
                document.querySelector('.card-body').insertBefore(errorDiv, document.querySelector('form'));
            }
            errorDiv.innerHTML = errors.join('<br>');
        }
    });

    // Indicateur de force du mot de passe
    const passwordInput = document.getElementById('password');
    const strengthBar = document.createElement('div');
    strengthBar.style.height = '5px';
    strengthBar.style.marginTop = '5px';
    passwordInput.parentNode.appendChild(strengthBar);
    passwordInput.addEventListener('input', function() {
        const val = passwordInput.value;
        let score = 0;
        if (val.length >= 8) score++;
        if (/[A-Z]/.test(val)) score++;
        if (/[0-9]/.test(val)) score++;
        if (/[^A-Za-z0-9]/.test(val)) score++;
        let color = ['#dc3545', '#fd7e14', '#ffc107', '#28a745'];
        strengthBar.style.width = ((score/4)*100) + '%';
        strengthBar.style.background = color[score-1] || '#dc3545';
    });

    function toggleClassField() {
        var role = document.getElementById('role').value;
        var classField = document.getElementById('classField');
        classField.style.display = (role === 'student') ? 'block' : 'none';
    }
    document.addEventListener('DOMContentLoaded', function() {
        toggleClassField();
    });
    </script>
</body>
</html> 