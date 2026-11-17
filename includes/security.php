<?php
date_default_timezone_set('Asia/Manila');
require_once __DIR__.'/gmail.php';
require_once __DIR__.'/detection-rules.php';
require_once __DIR__.'/account-security.php';
function security_db(): PDO {
 static $db;
 if (!$db) { $db = new PDO('mysql:host=127.0.0.1;dbname=plmun_cybersecurity;charset=utf8mb4', getenv('CYBER_DB_USER') ?: 'root', getenv('CYBER_DB_PASSWORD') ?: '', [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]); $db->exec("SET time_zone = '+08:00'"); }
 return $db;
}
function security_event(string $type, string $user='', array $details=[]): void {
 try {
 $user=trim($user);
 $db=security_db(); $ip=$_SERVER['REMOTE_ADDR'] ?? 'unknown';
 $db->beginTransaction();
 $lock=$db->prepare('INSERT IGNORE INTO detection_locks (source_ip) VALUES (?)');$lock->execute([$ip]);
 $lock=$db->prepare('SELECT source_ip FROM detection_locks WHERE source_ip=? FOR UPDATE');$lock->execute([$ip]);
 $severity=$type==='access_denied'?'high':($type==='login_failed'?'medium':'low');
 $stmt=$db->prepare('INSERT INTO security_events(event_type,severity,user_identifier,source_ip,user_agent,event_details,occurred_at) VALUES(?,?,?,?,?,?,NOW())');
 $stmt->execute([$type,$severity,substr($user,0,190),$ip,substr($_SERVER['HTTP_USER_AGENT']??'',0,500),json_encode($details)]);$id=$db->lastInsertId();
 $codes=detection_matches($db,$type,$user,$ip,substr($_SERVER['HTTP_USER_AGENT']??'',0,500),$id);
 foreach($codes as $code){$rule=null;$priority='high';unset($incident);
 foreach(detection_rules() as $definition)if($definition[0]===$code){$rule=$definition[1];$priority=strtolower($definition[3]);}
 if($rule) {
 $q=$db->prepare('SELECT i.id FROM incidents i JOIN security_events e ON i.event_id=e.id WHERE i.title=? AND e.source_ip=? AND COALESCE(e.user_identifier,\'\')=? AND i.created_at>=NOW()-INTERVAL 10 MINUTE');$q->execute([$rule,$ip,$user]);
 if(!$q->fetchColumn()) { $q=$db->prepare("INSERT INTO incidents(event_id,title,priority) VALUES(?,?,?)");$q->execute([$id,$rule,$priority]);$incident=$db->lastInsertId();$q=$db->prepare('INSERT INTO security_alerts(incident_id,message) VALUES(?,?)');$q->execute([$incident,$rule.' | IP '.$ip]); }
 }
 if(isset($incident))gmail_queue($db,$incident,$user);
 }
 $db->commit();
 } catch(Throwable $e) { if(isset($db)&&$db->inTransaction())$db->rollBack();error_log('Cybersecurity event collection failed: '.$e->getMessage());file_put_contents('C:/xampp/cybersecurity-private/collector-error.json',json_encode(['time'=>date('c'),'message'=>'Event collection failed. Review PHP server logs.']),LOCK_EX); }
}
