<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/cvpwfl/auth/session.php';
start_secure_session();
session_destroy();
header('Location: /cvpwfl/index.php');
exit;
?>