<?php
require __DIR__.'/auth.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !valid_csrf()) { http_response_code(403); exit('Invalid sign-out request.'); }
$q=security_db()->prepare('UPDATE student_sessions SET revoked_at=NOW() WHERE student_number=? AND token_hash=?');$q->execute([$_SESSION['student']??'',hash('sha256',$_SESSION['session_token']??'')]);
security_event('logout', $_SESSION['student'] ?? '');
$_SESSION = [];
$params = session_get_cookie_params();
setcookie(session_name(), '', ['expires'=>time()-3600,'path'=>$params['path'],'secure'=>$params['secure'],'httponly'=>true,'samesite'=>'Lax']);
session_destroy();
header('Location: login.php'); exit;
