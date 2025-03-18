<?php
// Inclusion du framework Dolibarr
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/security.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

// Chargement des traductions
$langs->loadLangs(array("mail@mail"));

$action = GETPOST('action', 'aZ09');

// Initialisation du gestionnaire de hooks
$hookmanager->initHooks(array('mailindex'));

// Vérification si le formulaire a été soumis
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['send'])) {
    $to = GETPOST('to', 'alpha');
    $subject = GETPOST('subject', 'alpha');
    $content = GETPOST('content', 'restricthtml');
    $form_link = GETPOST('form_link', 'alpha');

    // Vérification du jeton de sécurité Dolibarr
    if (!isset($_POST['token']) || $_POST['token'] !== $_SESSION['token']) {
        setEventMessage('Jeton de sécurité invalide.', 'errors');
        header("Location: ".$_SERVER['PHP_SELF']);
        exit();
    }

    // Envoi à tous les utilisateurs si "@everyone" est utilisé
    if ($to == "@everyone") {
        $sql = "SELECT email FROM llx_e_mail WHERE email != ''";
        $resql = $db->query($sql);
    
        if (!$resql) {
            setEventMessage('Erreur SQL : ' . $db->lasterror(), 'errors');
            header("Location: ".$_SERVER['PHP_SELF']);
            exit();
        }
    
        $to = "";
        while ($obj = $db->fetch_object($resql)) {
            $to .= $obj->email . ",";
        }
        $to = rtrim($to, ",");
    
        if (empty($to)) {
            setEventMessage('Aucune adresse e-mail trouvée pour "@everyone".', 'errors');
            header("Location: ".$_SERVER['PHP_SELF']);
            exit();
        }
    }
    
    $mail = new PHPMailer(true);
    
    try {
        $mail->SMTPDebug = 0;
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = '2it.pinf@gmail.com';
        $mail->Password   = 'jzfk cohr afdb okbl'; // Remplacez par un App Password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
    
        $mail->setFrom('2it.pinf@gmail.com', 'Harmonie');
        foreach (explode(',', $to) as $email) {
            $email = trim($email);
            if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $mail->addAddress($email);
            } else {
                setEventMessage('Adresse e-mail invalide : ' . $email, 'errors');
            }
        }
    
        // Gestion des pièces jointes
        if (!empty($_FILES['attachments']['name'][0])) {
            for ($i = 0; $i < count($_FILES['attachments']['name']); $i++) {
                if ($_FILES['attachments']['error'][$i] == 0) {
                    $mail->addAttachment($_FILES['attachments']['tmp_name'][$i], $_FILES['attachments']['name'][$i]);
                }
            }
        }
    
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = nl2br($content) . "<br><br>Formulaire: " . $form_link;
        
        $mail->Body .= "<br><br>--<br><strong>Association Lyre & Harmonie de Lumbres</strong><br>";
        $mail->Body .= "Adresse: ... <br>Téléphone: ... <br>Email: ... <br>Site web: ... <br><br>";
        $mail->Body .= "<a href='http://localhost/dolibarr-develop/dolibarr-develop/htdocs/custom/mail/mailindex.php' style='background-color:rgb(73, 91, 179); border: none; color: white; padding: 10px 20px; text-align: center; text-decoration: none; display: inline-block; font-size: 16px; margin: 4px 2px; cursor: pointer; width: 90%'>Visiter le site</a>";
    
        $mail->send();
        setEventMessage('Email envoyé avec succès!', 'mesgs');
    } catch (Exception $e) {
        setEventMessage('Erreur lors de l\'envoi: ' . $mail->ErrorInfo, 'errors');
    }
    
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Affichage de l'interface utilisateur Dolibarr
llxHeader("", $langs->trans("MailArea"));
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        ::placeholder {
            color: black;
            text-align: center;
        }
        
        .container {
            display: flex;
            width: 100%;
			max-width: 2000px;
            height: auto;
            margin: 5px;
            padding: 10px;
            background-color: rgb(200, 200, 200);
            border-radius: 12px;
        }

        .form-fields {
            flex: 1;
            margin-right: 30px;
            padding: 10px;
        }

        .form-group {
            margin-bottom: 25px;
            width: 100%;
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 16px;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            border-color: #007bff;
            box-shadow: 0 0 8px rgba(0, 123, 255, 0.2);
            outline: none;
        }

        .form-group textarea {
            resize: none;
            height: 120px;
        }

        .form-group.attachments input[type="file"] {
            padding: 8px;
            background-color: rgb(255, 255, 255);
            border-radius: 6px;
            width: 101%;
        }

        .button-container {
            display: flex;
            align-items: center;
            width: 25%;
        }

        .button-container button {
            background-color: rgb(217, 217, 217);
            color: black;
            padding: 12px 12px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
            height: 100%;
            width: 100%;
        }

        .button-container button:hover {
            background-color: rgb(128, 128, 128);
            transform: scale(1.01);
			color : white;
        }

        .button-container button:active {
            background-color: rgb(42, 42, 42);
            transform: scale(0.95);
        }
    </style>
</head>
<body>
<div class="container">
    <div class="form-fields">
        <form id="emailForm" method="POST" action="<?php echo $_SERVER['PHP_SELF']; ?>" enctype="multipart/form-data">
            <input type="hidden" name="token" value="<?php echo newToken(); ?>">
            
            <div class="form-group">
                <input type="text" name="to" id="to" placeholder="Adresse mail" required>
            </div>
            
            <div class="form-group">
                <input type="text" name="subject" id="subject" placeholder="Objet" required>
            </div>
            
            <div class="form-group">
                <textarea name="content" id="bodyemail " placeholder="Contenu" required></textarea>
            </div>
            
            <div class="form-group">
                <input type="text" name="form_link" id="form_link" placeholder="Lien vers le formulaire">
            </div>
            
            <div class="form-group attachments">
                <input type="file" name="attachments[]" id="attachments" multiple>
            </div>

            
        
    </div>
	<div class="button-container">
        <button type="submit" name="send">Envoyer</button>
    </div>
	</form>
</div>
</body>
</html>
<?php
llxFooter();
$db->close();
?>
