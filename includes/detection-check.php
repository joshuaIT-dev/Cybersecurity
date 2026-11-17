<?php
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require __DIR__.'/security.php';
$db=security_db();
// Temporary tables shadow live tables only on this connection; no test mail reaches the worker.
foreach(['security_events','incidents','security_alerts','detection_locks','mail_deliveries','student_accounts','student_sessions'] as $table){
 $sql=$db->query("SHOW CREATE TABLE $table")->fetch(PDO::FETCH_NUM)[1];
 $sql=str_replace('CREATE TABLE','CREATE TEMPORARY TABLE',$sql);
 $sql=preg_replace('/,?\n\s*CONSTRAINT[^\n]+/','',$sql);
 $sql=preg_replace('/,\s*\)/',"\n)",$sql);$db->exec($sql);
}
function check($condition,$message){if(!$condition)throw new RuntimeException($message);}
function emit_events($type,$count,$user,$ip){$_SERVER['REMOTE_ADDR']=$ip;$_SERVER['HTTP_USER_AGENT']='Test browser';for($i=0;$i<$count;$i++)security_event($type,$user,['verification'=>true]);}
function incident_count(){return (int)security_db()->query('SELECT COUNT(*) FROM incidents')->fetchColumn();}
$tests=[['login_failed',5,'student'],['admin_login_failed',5,'cyber'],['login_required',10,''],['csrf_failed',5,'student'],['page_view',30,'student']];
foreach($tests as $index=>[$event,$threshold,$user]){$before=incident_count();$ip='192.0.2.'.($index+1);emit_events($event,$threshold-1,$user,$ip);check(incident_count()===$before,'Alert below threshold: '.$event);emit_events($event,1,$user,$ip);check(incident_count()===$before+1,'Missing alert: '.$event);emit_events($event,1,$user,$ip);check(incident_count()===$before+1,'Duplicate alert: '.$event);}
foreach(['access_denied','suspicious_request'] as $index=>$event){$before=incident_count();emit_events($event,1,'','192.0.2.'.(20+$index));check(incident_count()===$before+1,'Missing immediate alert');}
$before=incident_count();emit_events('login_success',1,'new-student','192.0.2.30');emit_events('login_success',1,'new-student','192.0.2.30');check(incident_count()===$before,'First/known sign-in should not alert');emit_events('login_success',1,'new-student','192.0.2.31');check(incident_count()===$before+1,'New IP not detected');
check(suspicious_route('../config')&&suspicious_route('UNION SELECT password')&&suspicious_route('<script>')&&!suspicious_route('profile'),'Route signatures');
$c=['enabled'=>true,'admin'=>'admin@example.com'];check(gmail_recipients($c,'unknown','')===['admin@example.com'=>'admin'],'Anonymous isolation');check(count(gmail_recipients($c,'student','student@example.com'))===2,'Student routing');
echo "PASS thresholds, duplicate suppression, first/known/new sign-ins, route signatures and recipient isolation. No live rows or emails created.\n";
