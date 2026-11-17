<?php
$selected = null;
$id = filter_input(INPUT_GET, 'incident', FILTER_VALIDATE_INT);
if ($id) {
    $q = $db->prepare('SELECT i.*,e.source_ip,e.user_identifier,e.event_type,e.event_details,e.occurred_at FROM incidents i LEFT JOIN security_events e ON e.id=i.event_id WHERE i.id=?');
    $q->execute([$id]);
    $selected = $q->fetch(PDO::FETCH_ASSOC);
}
if ($id && !$selected) echo '<div class="notice">Incident not found.</div>';
if ($selected):
    $q = $db->prepare("SELECT occurred_at,event_details FROM security_events WHERE event_type='incident_updated' AND JSON_UNQUOTE(JSON_EXTRACT(event_details,'$.incident'))=? ORDER BY id");
    $q->execute([(string)$id]);
    $timeline = [['occurred_at' => $selected['created_at'], 'status' => null]];
    foreach ($q->fetchAll(PDO::FETCH_ASSOC) as $update) {
        $details = json_decode($update['event_details'], true);
        $timeline[] = ['occurred_at' => $update['occurred_at'], 'status' => $details['status'] ?? 'unknown'];
    }
    $timeline = array_reverse($timeline);
    $timelinePages = max(1, (int)ceil(count($timeline) / 10));
    $timelinePage = min(max(1, (int)filter_input(INPUT_GET, 'timeline_page', FILTER_VALIDATE_INT)), $timelinePages);
    $timelineItems = array_slice($timeline, ($timelinePage - 1) * 10, 10);
?>
<article class="panel investigation-detail" id="investigation">
  <header class="investigation-head">
    <div><span class="eyebrow">INCIDENT / INC-<?=h($id)?></span><h2><?=h($selected['title'])?></h2><div class="incident-badges"><span class="severity <?=h($selected['priority'])?>"><?=h(ucfirst($selected['priority']))?> priority</span><span class="state-tag"><?=h(ucfirst($selected['status']))?></span><span>Opened <?=h($selected['created_at'])?> PHT</span></div></div>
    <a class="button" href="?page=incidents">Close details</a>
  </header>
  <?php if(isset($_GET['saved'])): ?><div class="notice" role="status">Incident status updated.</div><?php elseif(isset($_GET['unchanged'])): ?><div class="notice" role="status">No status change was needed.</div><?php endif; ?>
  <div class="investigation-body">
    <section>
      <h3>Activity timeline</h3><p class="muted">Recorded detection and administrator updates / 10 items per page</p>
      <ol class="timeline"><?php foreach($timelineItems as $index => $item): ?><li><time><?=h($item['occurred_at'])?> PHT</time><span><?php if($item['status']===null): ?>Detection created this incident<?php else: ?>Security administrator changed status to <b><?=h($item['status'])?></b><?php endif; ?></span></li><?php endforeach; ?></ol>
      <?php if($timelinePages>1): ?><nav class="record-pager timeline-pager" aria-label="Activity timeline pages"><span>Showing <?=($timelinePage-1)*10+1?>–<?=min($timelinePage*10,count($timeline))?> of <?=count($timeline)?> activities</span><div><?php if($timelinePage>1): ?><a class="button" href="?page=incidents&amp;incident=<?=$id?>&amp;timeline_page=<?=$timelinePage-1?>#investigation">Previous</a><?php else: ?><span class="pager-disabled">Previous</span><?php endif; ?><span>Page <?=$timelinePage?> of <?=$timelinePages?></span><?php if($timelinePage<$timelinePages): ?><a class="button" href="?page=incidents&amp;incident=<?=$id?>&amp;timeline_page=<?=$timelinePage+1?>#investigation">Next</a><?php else: ?><span class="pager-disabled">Next</span><?php endif; ?></div></nav><?php endif; ?>
    </section>
    <section class="evidence-card"><h3>Evidence summary</h3><dl class="evidence"><?php foreach(['Source event'=>'event_id','Source IP'=>'source_ip','Account'=>'user_identifier','Event type'=>'event_type','Event time'=>'occurred_at'] as $label=>$key): ?><dt><?=h($label)?></dt><dd><?=h($selected[$key]?:'Unauthenticated')?></dd><?php endforeach; ?></dl><details><summary>View event payload</summary><pre class="code-preview"><?=h(json_encode(json_decode($selected['event_details']??'{}'),JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES))?></pre></details></section>
  </div>
  <footer class="investigation-actions">
    <div><h3>Update investigation</h3><p class="muted">Status remains editable for open, investigating, resolved, and closed incidents.</p></div>
    <div class="incident-controls">
      <form method="post" class="incident-update"><input type="hidden" name="csrf" value="<?=h($_SESSION['csrf'])?>"><input type="hidden" name="incident" value="<?=h($id)?>"><label for="incident-status">Status<select id="incident-status" name="status"><?php foreach(['open','investigating','resolved','closed'] as $status): ?><option value="<?=$status?>" <?=$status===$selected['status']?'selected':''?>><?=ucfirst($status)?></option><?php endforeach; ?></select></label><button class="button primary" type="submit">Save status</button></form>
      <?php if(in_array($selected['status'],['resolved','closed'],true)): ?><form method="post" class="incident-reopen"><input type="hidden" name="csrf" value="<?=h($_SESSION['csrf'])?>"><input type="hidden" name="incident" value="<?=h($id)?>"><input type="hidden" name="status" value="open"><button class="button" type="submit">Reopen incident</button></form><?php endif; ?>
    </div>
  </footer>
</article>
<script>document.getElementById('investigation')?.scrollIntoView({block:'start'});</script>
<?php endif; ?>
