<?php $schedule=$db->query('SELECT * FROM report_schedule WHERE id=1')->fetch(PDO::FETCH_ASSOC); ?>
<article class="panel module-panel" id="report-schedule">
 <div class="panel-heading"><div><h2>Scheduled email reports</h2><p>Daily summaries cover the preceding 24 hours. Weekly summaries run on Monday and cover seven days. Reports go to the administrator configured in Gmail Alerts.</p></div><span class="state-tag"><?=$schedule['enabled']?'Enabled':'Disabled'?></span></div>
 <?php if(isset($_GET['schedule_saved'])): ?><div class="notice" role="status">Report schedule saved.</div><?php endif; ?>
 <form method="post" class="module-toolbar"><input type="hidden" name="csrf" value="<?=h($_SESSION['csrf'])?>"><input type="hidden" name="save_report_schedule" value="1">
 <label>Frequency<select name="frequency"><option value="daily" <?=$schedule['frequency']==='daily'?'selected':''?>>Daily</option><option value="weekly" <?=$schedule['frequency']==='weekly'?'selected':''?>>Weekly · Monday</option></select></label>
 <label>Send time (Philippines)<input type="time" name="send_time" value="<?=h(substr($schedule['send_time'],0,5))?>" required></label>
 <label><span>Automatic reports</span><span><input type="checkbox" name="enabled" value="1" <?=$schedule['enabled']?'checked':''?> style="width:auto;min-width:0;height:auto"> Enabled</span></label><button class="button primary">Save schedule</button></form>
 <p class="muted">Next run: <?=h($schedule['next_run']?:'No scheduled run')?><?= $schedule['next_run']?' PHT':'' ?>. Requires Gmail notifications to be enabled and the delivery worker running.</p>
 <?php live_table(['RUN (PHT)','STATUS','ATTEMPTS','SENT (PHT)','DETAILS'],$db->query('SELECT due_at,status,attempts,sent_at,last_error FROM report_runs ORDER BY id DESC LIMIT 10')->fetchAll(PDO::FETCH_NUM)); ?>
</article>
