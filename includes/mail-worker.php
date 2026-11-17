<?php
if(PHP_SAPI!=='cli'){http_response_code(403);exit;}
require __DIR__.'/security.php';require_once __DIR__.'/gmail.php';
$lock=fopen('C:/xampp/cybersecurity-private/mail-worker.lock','c');if(!flock($lock,LOCK_EX|LOCK_NB))exit;
$workerState=['started_at'=>date('c'),'status'=>'running','sent'=>0,'failed'=>0];
register_shutdown_function(function()use(&$workerState){$error=error_get_last();if($error&&in_array($error['type'],[E_ERROR,E_PARSE,E_CORE_ERROR,E_COMPILE_ERROR],true)){$workerState['status']='error';$workerState['message']='Worker stopped unexpectedly. Review PHP logs.';}elseif($workerState['status']==='running')$workerState['status']='completed';$workerState['finished_at']=date('c');file_put_contents('C:/xampp/cybersecurity-private/mail-health.json',json_encode($workerState),LOCK_EX);});
$c=gmail_config();if(empty($c['enabled'])){$workerState['status']='disabled';exit;}
$db=security_db();
$accountCounts=process_account_mail($db,$c);$workerState['sent']+=$accountCounts['sent'];$workerState['failed']+=$accountCounts['failed'];
require_once __DIR__.'/scheduled-reports.php';
$reportCounts=process_scheduled_reports($db,$c);$workerState['sent']+=$reportCounts['sent'];$workerState['failed']+=$reportCounts['failed'];
foreach($db->query("SELECT d.*,i.title,e.source_ip,e.occurred_at,e.event_details FROM mail_deliveries d JOIN incidents i ON i.id=d.incident_id JOIN security_events e ON e.id=i.event_id WHERE d.status IN ('pending','retry') AND d.next_attempt<=NOW() AND d.attempts<3 ORDER BY d.id LIMIT 10")->fetchAll(PDO::FETCH_ASSOC) as $row){
try{$verification=!empty(json_decode($row['event_details'],true)['verification']);$body=($row['audience']==='student'?'Security activity was detected involving your student account.':'A security incident requires review.')."\n\nIncident #".$row['incident_id']."\n".$row['title']."\nTime: ".$row['occurred_at']." PHT\nIP: ".$row['source_ip']."\n\nIf this was not you, contact the university ICT administrator. An alert is a signal for review, not confirmation of an attack.";if($verification)$body='VERIFICATION TEST: This activity was deliberately generated to check the monitoring system.'."\n\n".$body;gmail_send($c,$row['recipient'],($verification?'[VERIFICATION] ':'').'Security alert / Incident #'.$row['incident_id'],$body);$q=$db->prepare("UPDATE mail_deliveries SET status='sent',attempts=attempts+1,sent_at=NOW(),last_error=NULL WHERE id=?");$q->execute([$row['id']]);$workerState['sent']++;}
catch(Throwable $e){$workerState['failed']++;$q=$db->prepare("UPDATE mail_deliveries SET status=IF(attempts>=2,'failed','retry'),attempts=attempts+1,last_error='SMTP delivery failed. Check sender credentials, TLS and network.',next_attempt=NOW()+INTERVAL 5 MINUTE WHERE id=?");$q->execute([$row['id']]);}
}
