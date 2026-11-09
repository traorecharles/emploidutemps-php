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

if ($id) {
    // Vérifier que la pièce jointe appartient à un devoir du professeur
    $attachment = $db->fetch(
        "SELECT aa.* FROM assignment_attachments aa
         JOIN assignments a ON a.id = aa.assignment_id
         WHERE aa.id = ? AND a.teacher_id = ?",
        [$id, $user['id']]
    );

    if ($attachment) {
        // Supprimer le fichier physique
        $filePath = __DIR__ . '/../' . $attachment['file_path'];
        if (file_exists($filePath)) {
            unlink($filePath);
        }

        // Supprimer l'enregistrement de la base de données
        if ($db->delete('assignment_attachments', 'id = ?', [$id])) {
            header('Location: /assignment-edit.php?id=' . $attachment['assignment_id'] . '&success=1');
            exit;
        }
    }
}

// En cas d'erreur ou si l'ID n'est pas valide
header('Location: /assignments.php');
exit; 