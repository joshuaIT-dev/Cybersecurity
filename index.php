<?php
require __DIR__.'/includes/guard.php';

$pages = ['overview' => 'Overview', 'events' => 'Event Log', 'auth' => 'Authentication & Roles', 'source' => 'Sources & Collection', 'rules' => 'Threat Detection', 'incidents' => 'Incidents', 'alerts' => 'Gmail Alerts', 'reports' => 'Reports & Analytics', 'health' => 'System Health'];
$page = $_GET['page'] ?? 'overview';
if (!is_string($page) || !isset($pages[$page])) $page = 'overview';
$title = $pages[$page];
?>
<!doctype html>
<html lang="en"><head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="theme-color" content="#0b1120">
  <title><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?> · PLMUN Cybersecurity</title><link rel="stylesheet" href="assets/style.css"><link rel="stylesheet" href="assets/overrides.css"><link rel="stylesheet" href="assets/modules.css">
<link rel="stylesheet" href="assets/live-ui.css"><link rel="stylesheet" href="assets/threat-ui.css"><link rel="stylesheet" href="assets/source-ui.css"><link rel="stylesheet" href="assets/reports-ui.css"><link rel="stylesheet" href="assets/health-ui.css"><link rel="stylesheet" href="assets/card-system.css?v=<?=filemtime(__DIR__.'/assets/card-system.css')?>"></head><body><div class="app-shell">
  <aside class="sidebar">
    <a class="brand" href="?page=overview" aria-label="PLMUN Cybersecurity home"><span class="brand-mark">P</span><span><b>PLMUN</b><small>CYBER WATCH</small></span></a>
    <div class="rail-label">MONITORING</div><nav aria-label="Main navigation">
      <?php foreach ($pages as $key => $label): ?><a class="nav-link <?= $page === $key ? 'active' : '' ?>" href="?page=<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?>" <?= $page === $key ? 'aria-current="page"' : '' ?>><span class="nav-icon" aria-hidden="true"><?= ['overview'=>'◫','events'=>'◷','auth'=>'♙','source'=>'▤','rules'=>'⌘','incidents'=>'⚑','alerts'=>'✉','reports'=>'▥','health'=>'◉'][$key] ?></span><span><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></span></a><?php endforeach; ?>
    </nav>
    <div class="rail-status"><span class="status-dot"></span><span>Portal source<br><b>Connected</b></span><span class="status-arrow">↗</span></div>
    <button class="logout help-trigger" id="help-open" type="button"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M4 4h7a3 3 0 0 1 3 3v14a3 3 0 0 0-3-3H4zM14 7a3 3 0 0 1 3-3h3v14h-3a3 3 0 0 0-3 3"/></svg><span>Help &amp; documentation</span></button>
  </aside>
  <main class="main-area">
    <?php include __DIR__.'/includes/topbar.php'; ?>
    <section class="workspace">
      <div class="page-heading"><div><div class="eyebrow">SECURITY OPERATIONS / <?= htmlspecialchars(strtoupper($title), ENT_QUOTES, 'UTF-8') ?></div><h1><?= htmlspecialchars($title === 'Overview' ? 'Cybersecurity Dashboard' : $title, ENT_QUOTES, 'UTF-8') ?></h1><p>Real-time visibility across the PLMUN student portal</p></div><div class="heading-actions"><span class="updated">Updated <b id="updated-at"><?= date('H:i:s') ?></b></span><button class="button" id="refresh" onclick="location.reload()">⟳ <span>Refresh</span></button></div></div>
      <?php include __DIR__.'/includes/live.php'; ?>
<footer class="footer"><span>PLMUN CYBER WATCH <i>·</i> Security monitoring workspace</span><span><span class="status-dot"></span> Live monitoring · Student portal events</span></footer>
    </section>
  </main>
</div><?php include __DIR__.'/includes/help.php'; ?></body></html>
