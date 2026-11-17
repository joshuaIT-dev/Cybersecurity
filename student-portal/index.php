<?php
require __DIR__.'/auth.php';
if (empty($_SESSION['student'])) { security_event('login_required', '', ['resource'=>'student_portal']); header('Location: login.php'); exit; }

$accountQuery=security_db()->prepare('SELECT student_number,first_name,last_name,notification_email FROM student_accounts WHERE student_number=?');$accountQuery->execute([$_SESSION['student']]);$studentAccount=$accountQuery->fetch(PDO::FETCH_ASSOC);$studentAccount['full_name']=$studentAccount['first_name'].' '.$studentAccount['last_name'];$studentAccount['initials']=mb_substr($studentAccount['first_name'],0,1).mb_substr($studentAccount['last_name'],0,1);
$pages = ['home','activity','profile','courses','announcements','grades','consultation','consultation-records','enrollment','fees','subjects','open-courses','cross-enroll','requests','forms','email','evaluation','support','security','qr','photo','curriculum','other-services','service-request'];
$page = $_GET['page'] ?? 'home';
if (!is_string($page) || !in_array($page, $pages, true)) $page = 'home';
security_event('page_view', $_SESSION['student'], ['page'=>$page]);
?>
<!doctype html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>PLMUN Student Portal</title><link rel="stylesheet" href="portal.css"><link rel="stylesheet" href="layout.css"><link rel="stylesheet" href="nav.css"><link rel="stylesheet" href="polish.css"><link rel="stylesheet" href="semester.css"><link rel="stylesheet" href="consultation.css"><link rel="stylesheet" href="forms.css"><link rel="stylesheet" href="refinements.css?v=fixed-email1"></head><body>
<div id="viewport"><div id="canvas"><?php $view = file_get_contents(__DIR__.'/views/'.(in_array($page, ['other-services','forms','service-request'], true) ? 'home' : $page).'.html'); $view=strtr($view,['Jordan Navarro'=>htmlspecialchars($studentAccount['full_name'],ENT_QUOTES,'UTF-8'),'20261108'=>htmlspecialchars($studentAccount['student_number'],ENT_QUOTES,'UTF-8')]); echo preg_replace('/<div([^>]*?)\s*\/>/s', '<div$1></div>', $view); ?></div></div>
<?php
$groups = [
  'eCOG' => ['Electronic Certificate of Grades'=>'grades','Curriculum Checklist'=>'curriculum'],
  'e-Consultation' => ['Consult My Prof'=>'consultation','My Consultations'=>'consultation-records'],
  'Downloadable Forms' => ['Application for Graduation'=>'forms'],
  'Enrollment Services' => ['Online Enrollment'=>'enrollment','Tuition Fees'=>'fees','My Current Enrolled Course'=>'subjects','Online Cross Enroll Request'=>['Send a Cross Enroll Request'=>'cross-enroll','My Requests'=>'requests'],'View All Colleges Schedule'=>'open-courses'],
  'Support' => ['Request for Support'=>'support','Request a Service'=>'service-request'],
];
?>
<nav id="portal-nav" aria-label="Student portal navigation">
  <a class="nav-home" href="?page=home" aria-label="Home" title="Home" <?php if ($page === 'home') echo 'aria-current="page"'; ?>><svg aria-hidden="true"
  xmlns="http://www.w3.org/2000/svg"
  width="24"
  height="24"
  viewBox="0 0 24 24"
  fill="none"
  stroke="currentColor"
  stroke-width="2"
  stroke-linecap="round"
  stroke-linejoin="round"
>
  <path d="M15 21v-8a1 1 0 0 0-1-1h-4a1 1 0 0 0-1 1v8" />
  <path d="M3 10a2 2 0 0 1 .709-1.528l7-6a2 2 0 0 1 2.582 0l7 6A2 2 0 0 1 21 10v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" />
</svg>
</a>
  <div class="nav-links">
  <?php foreach ($groups as $group=>$items): ?><details class="nav-dropdown"><summary><?= htmlspecialchars($group, ENT_QUOTES, 'UTF-8') ?></summary><div class="nav-menu"><?php foreach ($items as $label=>$route): ?><?php if (is_array($route)): ?><details class="nav-nested"><summary><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></summary><div class="nav-submenu"><?php foreach ($route as $subLabel=>$subRoute): ?><a href="?page=<?= rawurlencode($subRoute) ?>" <?= $page === $subRoute ? 'aria-current="page"' : '' ?>><?= htmlspecialchars($subLabel, ENT_QUOTES, 'UTF-8') ?></a><?php endforeach; ?></div></details><?php else: ?><a href="?page=<?= rawurlencode($route) ?>" <?= $page === $route ? 'aria-current="page"' : '' ?>><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></a><?php endif; ?><?php endforeach; ?></div></details><?php endforeach; ?>
  <a href="?page=email" <?= $page === 'email' ? 'aria-current="page"' : '' ?>>Institutional Email</a>
  <a href="?page=evaluation" <?= $page === 'evaluation' ? 'aria-current="page"' : '' ?>>Faculty Evaluation</a>
  <a href="?page=other-services" <?= $page === 'other-services' ? 'aria-current="page"' : '' ?>>Other Services</a>
  </div>
  <form action="logout.php" method="post" class="logout-form"><input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf'], ENT_QUOTES, 'UTF-8') ?>"><button id="portal-logout" type="submit"><svg aria-hidden="true"
  xmlns="http://www.w3.org/2000/svg"
  width="24"
  height="24"
  viewBox="0 0 24 24"
  fill="none"
  stroke="currentColor"
  stroke-width="2"
  stroke-linecap="round"
  stroke-linejoin="round"
>
  <path d="m16 17 5-5-5-5" />
  <path d="M21 12H9" />
  <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
</svg>
<span>Logout</span></button></form>
</nav>
<dialog id="notice"><p>Service preview only. This action requires the student portal backend and authorized school records.</p><form method="dialog"><button>Close</button></form></dialog><script>window.studentAccount=<?=json_encode($studentAccount,JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT)?>;</script><script src="portal.js?v=fixed-email1"></script><script src="connected.js?v=fixed-email1"></script><script src="account-security.js?v=2"></script></body></html>
