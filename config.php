<?php   
require_once $_SERVER['DOCUMENT_ROOT'] . '/cvpwfl/phpmailer/src/Exception.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/cvpwfl/phpmailer/src/PHPMailer.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/cvpwfl/phpmailer/src/SMTP.php';
use PHPMailer\PHPMailer\PHPMailer;

// Database configuration

$db_host = 'localhost';
$db_name = 'cvpwfl';
$db_user = 'root';
$db_pass = '';
/*
$db_host = 'localhost';
$db_name = 'f923ec5_cvpwfl';  
$db_user = 'f923ec5_cvpwfl_admin'; 
$db_pass = 'BaxterHutch1212!'; 
*/
try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

function send_email($to, $subject, $body) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'mail.cvpwfl.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'no-reply@cvpwfl.com';
        $mail->Password = 'store10333!';
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;
        $mail->setFrom('no-reply@cvpwfl.com', "Pee Wee Football");
        $mail->addReplyTo('no-reply@cvpwfl.com', "Pee Wee Football");
        $mail->isHTML(true);
        $mail->addAddress($to);
        $mail->Subject = $subject;
        $mail->Body = $body;
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email sending failed: " . $e->getMessage());
        return false;
    }
}
?>