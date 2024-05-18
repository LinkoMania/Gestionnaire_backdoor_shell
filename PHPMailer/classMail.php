<?php
session_start();

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'src/Exception.php';
require 'src/PHPMailer.php';
require 'src/SMTP.php';

// Configuration pour l'envoi d'e-mails
$to_email = "linkocreation@gmail.com"; // Remplacez par votre adresse e-mail
$subject_prefix = "[Gestionnaire de fichiers]";

// Fonction pour envoyer un e-mail
function sendEmail($message) {
    global $to_email, $subject_prefix;
    $subject = $subject_prefix . " Action effectuée";

    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'linkocreation@gmail.com'; // Votre adresse e-mail Gmail
        $mail->Password = 'stkmffnekfvutwox'; // Votre mot de passe Gmail
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;

        $mail->setFrom('linkocreation@gmail.com', 'Gestionnaire de fichiers');
        $mail->addAddress($to_email);
        $mail->Subject = $subject;
        $mail->Body = $message;

        $mail->send();
    } catch (Exception $e) {
        echo "Erreur lors de l'envoi de l'e-mail : {$mail->ErrorInfo}";
    }
}

// Affichage de la page de connexion
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    if (isset($_POST['password'])) {
        $password = $_POST['password'];
        $correct_password = 'your_secure_password'; // Remplacez par votre mot de passe sécurisé

        if ($password === $correct_password) {
            $token = rand(100000, 999999); // Génère un code à 6 chiffres
            $_SESSION['token'] = $token;
            $_SESSION['token_expiry'] = time() + 300; // Le token expire dans 5 minutes

            $subject = "[Gestionnaire de fichiers] Votre code de vérification";
            $message = "Votre code de vérification est : $token";

            $mail = new PHPMailer(true);

            try {
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'linkocreation@gmail.com';
                $mail->Password = 'stkmffnekfvutwox';
                $mail->SMTPSecure = 'tls';
                $mail->Port = 587;

                $mail->setFrom('linkocreation@gmail.com', 'Gestionnaire de fichiers');
                $mail->addAddress($to_email);
                $mail->Subject = $subject;
                $mail->Body = $message;

                $mail->send();
                $_SESSION['step'] = 'verify';
            } catch (Exception $e) {
                echo "Erreur lors de l'envoi de l'e-mail : {$mail->ErrorInfo}";
            }
        } else {
            echo "Mot de passe incorrect";
        }
    } elseif (isset($_POST['token'])) {
        if ($_POST['token'] == $_SESSION['token'] && time() < $_SESSION['token_expiry']) {
            $_SESSION['logged_in'] = true;
            unset($_SESSION['token'], $_SESSION['token_expiry'], $_SESSION['step']);
        } else {
            echo "Code incorrect ou expiré";
        }
    }

    if (!isset($_SESSION['step'])) {
        $_SESSION['step'] = 'login';
    }

    if ($_SESSION['step'] === 'login') {
        echo '<h1>Connexion</h1>
            <form method="post">
                <input type="password" name="password" placeholder="Mot de passe" required>
                <input type="submit" value="Se connecter">
            </form>';
    } elseif ($_SESSION['step'] === 'verify') {
        echo '<h1>Vérification</h1>
            <form method="post">
                <input type="text" name="token" placeholder="Code de vérification" required>
                <input type="submit" value="Vérifier">
            </form>';
    }
    exit();
}

// Traitement des actions
$action = $_POST['action'] ?? '';
$cwd = $_SESSION['cwd'] ?? getcwd();

switch ($action) {
    case 'cd':
        $dir = realpath($cwd . DIRECTORY_SEPARATOR . $_POST['dir']);
        if ($dir && is_dir($dir)) {
            $_SESSION['cwd'] = $dir;
        }
        break;
    case 'back':
        $parent_dir = dirname($cwd);
        if (is_dir($parent_dir)) {
            $_SESSION['cwd'] = $parent_dir;
        }
        break;
    case 'create_file':
        $file = $cwd . DIRECTORY_SEPARATOR . $_POST['file_name'];
        if (!file_exists($file)) {
            file_put_contents($file, '');
            sendEmail("Un nouveau fichier a été créé : $file");
        }
        break;
    case 'create_dir':
        $dir = $cwd . DIRECTORY_SEPARATOR . $_POST['dir_name'];
        if (!file_exists($dir)) {
            mkdir($dir);
            sendEmail("Un nouveau dossier a été créé : $dir");
        }
        break;
    case 'delete':
        $target = $cwd . DIRECTORY_SEPARATOR . $_POST['target'];
        if (is_file($target)) {
            unlink($target);
            sendEmail("Un fichier a été supprimé : $target");
        } elseif (is_dir($target)) {
            rmdir($target);
            sendEmail("Un dossier a été supprimé : $target");
        }
        break;
    case 'view_file':
        $file = $cwd . DIRECTORY_SEPARATOR . $_POST['file_name'];
        if (file_exists($file)) {
            $file_content = file_get_contents($file);
        }
        break;
    case 'write_file':
        $file = $cwd . DIRECTORY_SEPARATOR . $_POST['file_name'];
        if (file_exists($file)) {
            file_put_contents($file, $_POST['content']);
            $file_content = file_get_contents($file); // Rafraîchir le contenu après écriture
            sendEmail("Le contenu du fichier $file a été modifié");
        }
        break;
}

$cwd = $_SESSION['cwd'] ?? getcwd();
$files = scandir($cwd);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestionnaire de fichiers</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f0f0f0;
            color: #333;
            margin: 0;
            padding: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        table, th, td {
            border: 1px solid #ccc;
        }
        th, td {
            padding: 10px;
            text-align: left;
        }
        th {
            background-color: #f9f9f9;
        }
        .button {
            display: inline-block;
            padding: 5px 10px;
            margin-right: 5px;
            color: #fff;
            background-color: #007bff;
            text-decoration: none;
            border-radius: 3px;
        }
        .button:hover {
            background-color: #0056b3;
        }
        .form-inline {
            display: flex;
            align-items: center;
        }
        .form-inline input[type="text"] {
            flex: 1;
            padding: 5px;
            margin-right: 5px;
        }
        .form-inline input[type="submit"] {
            padding: 5px 10px;
        }
        textarea {
            width: 100%;
            height: 200px;
            padding: 10px;
            font-family: monospace;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <h1>Gestionnaire de fichiers</h1>
    <p>Répertoire actuel : <?= htmlspecialchars($cwd) ?></p>
    <form method="post" class="form-inline">
        <input type="hidden" name="action" value="cd">
        <input type="text" name="dir" placeholder="Nom du dossier">
        <input type="submit" value="Aller dans">
    </form>
    <form method="post">
        <input type="hidden" name="action" value="back">
        <input type="submit" value="Revenir en arrière">
    </form>
    <form method="post" class="form-inline">
        <input type="hidden" name="action" value="create_file">
        <input type="text" name="file_name" placeholder="Nom du fichier">
        <input type="submit" value="Créer un fichier">
    </form>
    <form method="post" class="form-inline">
        <input type="hidden" name="action"
        value="create_dir">
        <input type="text" name="dir_name" placeholder="Nom du dossier">
        <input type="submit" value="Créer un dossier">
    </form>
    <table>
        <thead>
            <tr>
                <th>Nom</th>
                <th>Type</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($files as $file): ?>
                <?php if ($file == '.' || $file == '..') continue; ?>
                <tr>
                    <td><?= htmlspecialchars($file) ?></td>
                    <td><?= is_dir($cwd . DIRECTORY_SEPARATOR . $file) ? 'Dossier' : 'Fichier' ?></td>
                    <td>
                        <?php if (is_dir($cwd . DIRECTORY_SEPARATOR . $file)): ?>
                            <form method="post" style="display:inline;">
                                <input type="hidden" name="action" value="cd">
                                <input type="hidden" name="dir" value="<?= htmlspecialchars($file) ?>">
                                <input type="submit" value="Aller dans">
                            </form>
                        <?php else: ?>
                            <form method="post" style="display:inline;">
                                <input type="hidden" name="action" value="view_file">
                                <input type="hidden" name="file_name" value="<?= htmlspecialchars($file) ?>">
                                <input type="submit" value="Voir">
                            </form>
                            <form method="post" style="display:inline;">
                                <input type="hidden" name="action" value="write_file">
                                <input type="hidden" name="file_name" value="<?= htmlspecialchars($file) ?>">
                                <input type="text" name="content" placeholder="Nouveau contenu">
                                <input type="submit" value="Écrire">
                            </form>
                        <?php endif; ?>
                        <form method="post" style="display:inline;">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="target" value="<?= htmlspecialchars($file) ?>">
                            <input type="submit" value="Supprimer">
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <?php if (isset($file_content)): ?>
        <h2>Contenu de <?= htmlspecialchars($_POST['file_name']) ?></h2>
        <form method="post">
            <textarea name="content"><?= htmlspecialchars($file_content) ?></textarea>
            <input type="hidden" name="action" value="write_file">
            <input type="hidden" name="file_name" value="<?= htmlspecialchars($_POST['file_name']) ?>">
            <input type="submit" value="Enregistrer">
        </form>
    <?php endif; ?>
</body>
</html>
