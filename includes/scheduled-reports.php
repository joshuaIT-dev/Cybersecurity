<?php
function report_catalog($db){
 $s=$db->query('SELECT * FROM report_schedule WHERE id=1')->fetch(PDO::FETCH_ASSOC);
 return [['Security event log','On demand','Up to the latest 10,000 collected security events','CSV / DOCX','Available'],['Scheduled email report',ucfirst($s['frequency']).' / '.substr($s['send_time'],0,5).' PHT','Event totals, failed logins and incident counts','Email summary',$s['enabled']?'Enabled':'Disabled']];
}
function report_next_run($frequency,$time,$after=null){
 $now=new DateTimeImmutable($after??'now',new DateTimeZone('Asia/Manila'));
 $next=$now->setTime((int)substr($time,0,2),(int)substr($time,3,2));
 if($frequency==='weekly'){$next=$next->modify('monday this week');if($next<=$now)$next=$next->modify('+1 week');}
 elseif($next<=$now)$next=$next->modify('+1 day');
 return $next->format('Y-m-d H:i:s');
}
function queue_security_report($db,$recipient,$due,$frequency,$test=false){
 $days=$frequency==='weekly'?7:1;
 $q=$db->prepare('SELECT COUNT(*) total,COALESCE(SUM(event_type="login_failed"),0) failures FROM security_events WHERE occurred_at>=DATE_SUB(?,INTERVAL ? DAY) AND occurred_at<?');$q->execute([$due,$days,$due]);$events=$q->fetch(PDO::FETCH_ASSOC);
 $q=$db->prepare('SELECT COUNT(*) FROM incidents WHERE created_at>=DATE_SUB(?,INTERVAL ? DAY) AND created_at<?');$q->execute([$due,$days,$due]);$incidents=$q->fetchColumn();
 $open=$db->query("SELECT COUNT(*) FROM incidents WHERE status IN ('open','investigating')")->fetchColumn();
 $subject=($test?'[VERIFICATION] ':'').'Cyber Watch / '.ucfirst($frequency).' security summary';
 $body="PLMUN CYBER WATCH\nSecurity summary\n\nPeriod: preceding $days day(s), ending $due PHT\nCollected events: {$events['total']}\nFailed student logins: {$events['failures']}\nNew incidents: $incidents\nCurrently open / investigating: $open\n\nReview the dashboard for evidence and incident actions.\n";
 $q=$db->prepare('INSERT IGNORE INTO report_runs(due_at,recipient,subject,body,next_attempt) VALUES(?,?,?,?,NOW())');$q->execute([$due,$recipient,$subject,$body]);
}
function process_scheduled_reports($db,$config){
 $counts=['sent'=>0,'failed'=>0];
 if(empty($config['enabled'])||empty($config['admin']))return $counts;
 $db->beginTransaction();
 try{
  $schedule=$db->query('SELECT * FROM report_schedule WHERE id=1 FOR UPDATE')->fetch(PDO::FETCH_ASSOC);
  if($schedule&&$schedule['enabled']&&$schedule['next_run']&&strtotime($schedule['next_run'])<=time()){
   queue_security_report($db,$config['admin'],$schedule['next_run'],$schedule['frequency']);
   $q=$db->prepare('UPDATE report_schedule SET next_run=? WHERE id=1');$q->execute([report_next_run($schedule['frequency'],$schedule['send_time'])]);
  }
  $db->commit();
 }catch(Throwable $e){$db->rollBack();throw $e;}
 foreach($db->query("SELECT * FROM report_runs WHERE status IN ('pending','retry') AND attempts<3 AND next_attempt<=NOW() ORDER BY id LIMIT 5")->fetchAll(PDO::FETCH_ASSOC) as $run){
  try{gmail_send($config,$run['recipient'],$run['subject'],$run['body']);$q=$db->prepare("UPDATE report_runs SET status='sent',attempts=attempts+1,sent_at=NOW(),last_error=NULL WHERE id=?");$q->execute([$run['id']]);$counts['sent']++;}
  catch(Throwable $e){$q=$db->prepare("UPDATE report_runs SET status=IF(attempts>=2,'failed','retry'),attempts=attempts+1,next_attempt=NOW()+INTERVAL 5 MINUTE,last_error='SMTP delivery failed; review Gmail configuration.' WHERE id=?");$q->execute([$run['id']]);$counts['failed']++;}
 }
 return $counts;
}
