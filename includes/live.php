<?php
function h($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
$counts=$db->query("SELECT COUNT(*) total, COALESCE(SUM(event_type='login_failed'),0) failed FROM security_events WHERE occurred_at>=CURDATE()")->fetch(PDO::FETCH_ASSOC);
$open=(int)$db->query("SELECT COUNT(*) FROM incidents WHERE status IN ('open','investigating')")->fetchColumn();
$threats=(int)$db->query('SELECT COUNT(*) FROM incidents WHERE created_at>=CURDATE()')->fetchColumn();
function live_table($headers,$rows){echo '<div class="table-scroll"><table><thead><tr>';foreach($headers as $header)echo '<th>'.h($header).'</th>';echo '</tr></thead><tbody>';if(!$rows)echo '<tr><td colspan="'.count($headers).'">No records yet.</td></tr>';foreach($rows as $row){echo '<tr>';foreach($row as $value)echo '<td>'.h($value).'</td>';echo '</tr>';}echo '</tbody></table></div>';}
?>
<?php if($page==='overview'){include __DIR__.'/overview.php';return;} ?>
<?php include __DIR__.'/modules-connected.php'; ?>
