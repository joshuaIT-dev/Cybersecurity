<?php
require __DIR__.'/auth.php';
if (!empty($_SESSION['student'])) { header('Location: index.php'); exit; }
function student_cooldown(){ $user=is_string($_POST['student_number']??null)?trim($_POST['student_number']):'';$q=security_db()->prepare("SELECT COUNT(*) FROM security_events WHERE event_type='login_failed' AND occurred_at>=NOW()-INTERVAL 1 MINUTE AND (user_identifier=? OR source_ip=?)");$q->execute([$user,$_SERVER['REMOTE_ADDR']??'']);return $q->fetchColumn()>=5;}
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!valid_csrf()) { security_event('csrf_failed',''); $error = 'Please refresh this page and try again.'; }
    elseif (student_cooldown()) { $error = 'Too many attempts. Please wait one minute.'; }
    else {
        $student = is_string($_POST['student_number']??null)?trim($_POST['student_number']):'';
        $password = $_POST['password'] ?? '';
        $q=security_db()->prepare('SELECT password_hash,session_version FROM student_accounts WHERE student_number=?');$q->execute([$student]);$account=$q->fetch(PDO::FETCH_ASSOC);
        if ($account && is_string($password) && password_verify($password,$account['password_hash'])) {
            $_SESSION['session_token']=student_session_start(security_db(),$student);
            security_event('login_success', $student);
            session_regenerate_id(true);
            $_SESSION['student'] = $student;
            $_SESSION['session_version']=(int)$account['session_version'];
            $_SESSION['csrf'] = bin2hex(random_bytes(32));
            unset($_SESSION['attempts'], $_SESSION['retry_after']);
            header('Location: index.php'); exit;
        }
        security_event('login_failed', is_string($student) ? $student : '');
        $_SESSION['attempts'] = ($_SESSION['attempts'] ?? 0) + 1;
        if ($_SESSION['attempts'] >= 5) { $_SESSION['retry_after'] = time()+60; $_SESSION['attempts'] = 0; }
        $error = 'Incorrect student number or password.';
    }
}
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Student Login | PLMUN</title><link rel="stylesheet" href="login.css"></head><body><main class="login-layout"><section class="intro"><div class="brand"><span>P</span>PLMUN</div><p class="eyebrow">STUDENT PORTAL</p><h1>Your campus.<br>Your connection.</h1><p>Access your courses, grades, enrollment, and student services in one place.</p><div class="school">Pamantasan ng Lungsod ng Muntinlupa</div></section><section class="login-card"><span class="eyebrow">WELCOME BACK</span><h2>Sign in to your portal</h2><p>Enter your student number and password to continue.</p><?php if ($error): ?><div class="error" role="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?><form method="post"><input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf'], ENT_QUOTES, 'UTF-8') ?>"><label for="student">Student number</label><input id="student" name="student_number" autocomplete="username" inputmode="numeric" placeholder="Enter your student number" required><label for="password">Password</label><input id="password" type="password" name="password" autocomplete="current-password" placeholder="Enter your password" required><button type="submit">Sign in</button></form><p class="help">For account assistance, contact the university ICT office.</p></section></main></body></html>
