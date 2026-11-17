<?php
session_set_cookie_params(['httponly'=>true,'secure'=>!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off','samesite'=>'Lax','path'=>'/']);
session_start();
require_once __DIR__.'/../includes/security.php';
header('Cache-Control: no-store');
if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
function valid_csrf(): bool { return isset($_POST['csrf']) && is_string($_POST['csrf']) && hash_equals($_SESSION['csrf'], $_POST['csrf']); }

if(!empty($_SESSION['student'])){
$q=security_db()->prepare('SELECT session_version FROM student_accounts WHERE student_number=?');$q->execute([$_SESSION['student']]);$version=$q->fetchColumn();
if(!$version||!isset($_SESSION['session_version'])||(int)$_SESSION['session_version']!==(int)$version||!student_session_valid(security_db(),$_SESSION['student'],$_SESSION['session_token']??'')){unset($_SESSION['student'],$_SESSION['session_version'],$_SESSION['session_token']);session_regenerate_id(true);}
}

inspect_route($_SESSION['student']??'');
