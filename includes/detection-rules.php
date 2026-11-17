<?php
function detection_rules(){return [
 ['AUTH-01','Repeated failed logins: 5 in 10 minutes','5 student login failures for one account and IP in 10 minutes','High'],
 ['AUTH-02','Repeated administrator login failures','5 administrator failures from one IP in 5 minutes','High'],
 ['AUTH-03','Unfamiliar sign-in','Successful sign-in from a new IP or browser signature after an earlier successful sign-in','Medium'],
 ['AUTH-04','Successful login after repeated failures','Successful student login after 5 account failures in 10 minutes since its previous successful login','High'],
 ['AUTH-05','Overlapping student sessions','A login while another session used a different IP or browser signature in the last 15 minutes','Medium'],
 ['ACCESS-01','Unauthorized access attempt','Denied protected access, including anonymous protected POST requests','High'],
 ['ACCESS-02','Repeated anonymous access','10 login-required requests from one IP in 1 minute','Medium'],
 ['ACCESS-03','Repeated invalid security tokens','5 rejected CSRF tokens from one account and IP in 10 minutes','High'],
 ['ACCESS-04','Suspicious request pattern','SQL injection, script injection, or traversal signature in a route identifier','High'],
 ['BEHAVIOR-01','Abnormal access volume: 30 pages in one minute','30 portal page views for one account and IP in 1 minute','High'],
 ['BEHAVIOR-02','Repeated sensitive downloads','10 protected document downloads for one account in 5 minutes','Medium']
 ];}
function detection_match($db,$type,$user,$ip,$agent,$id){
 $count=function($event,$minutes,$account=true)use($db,$user,$ip){$q=$db->prepare('SELECT COUNT(*) FROM security_events WHERE event_type=? AND source_ip=? AND occurred_at>=NOW()-INTERVAL '.$minutes.' MINUTE'.($account?' AND user_identifier=?':''));$q->execute($account?[$event,$ip,$user]:[$event,$ip]);return (int)$q->fetchColumn();};
 if($type==='access_denied')return 'ACCESS-01';
 if($type==='suspicious_request')return 'ACCESS-04';
 if($type==='login_failed'&&$count($type,10)>=5)return 'AUTH-01';
 if($type==='admin_login_failed'&&$count($type,5,false)>=5)return 'AUTH-02';
 if($type==='login_required'&&$count($type,1,false)>=10)return 'ACCESS-02';
 if($type==='csrf_failed'&&$count($type,10)>=5)return 'ACCESS-03';
 if($type==='page_view'&&$count($type,1)>=30)return 'BEHAVIOR-01';
 if(in_array($type,['login_success','admin_login_success'],true)){
  $q=$db->prepare('SELECT COUNT(*) total,COALESCE(SUM(source_ip=? AND user_agent=?),0) known FROM security_events WHERE event_type=? AND user_identifier=? AND id<?');$q->execute([$ip,$agent,$type,$user,$id]);$row=$q->fetch(PDO::FETCH_ASSOC);
  if($row['total']>0&&$row['known']==0)return 'AUTH-03';
 }
 return null;
}
function detection_matches($db,$type,$user,$ip,$agent,$id){
 $matches=[];$match=detection_match($db,$type,$user,$ip,$agent,$id);if($match)$matches[]=$match;
 if($type==='login_success'){
  $q=$db->prepare("SELECT COALESCE(MAX(id),0) FROM security_events WHERE event_type='login_success' AND user_identifier=? AND id<?");$q->execute([$user,$id]);$previous=$q->fetchColumn();
  $q=$db->prepare("SELECT COUNT(*) FROM security_events WHERE event_type='login_failed' AND user_identifier=? AND occurred_at>=NOW()-INTERVAL 10 MINUTE AND id>? AND id<?");$q->execute([$user,$previous,$id]);if($q->fetchColumn()>=5)$matches[]='AUTH-04';
  $q=$db->prepare('SELECT COUNT(*) FROM student_sessions WHERE student_number=? AND revoked_at IS NULL AND expires_at>NOW() AND last_seen>=NOW()-INTERVAL 15 MINUTE AND (source_ip<>? OR user_agent<>?)');$q->execute([$user,$ip,$agent]);if($q->fetchColumn())$matches[]='AUTH-05';
 }
 if($type==='document_download'){
  $q=$db->prepare("SELECT COUNT(*) FROM security_events WHERE event_type='document_download' AND user_identifier=? AND occurred_at>=NOW()-INTERVAL 5 MINUTE");$q->execute([$user]);if($q->fetchColumn()>=10)$matches[]='BEHAVIOR-02';
 }
 return $matches;
}
function suspicious_route($value){
 if(!is_string($value))return true;
 return (bool)preg_match('~(?:\.\.[/\\\\]|<\s*script\b|\bunion\s+(?:all\s+)?select\b|\b(?:or|and)\s+[\x27\x22]?\d+[\x27\x22]?\s*=\s*[\x27\x22]?\d+|\b(?:sleep|benchmark)\s*\()~i',rawurldecode($value));
}
function inspect_route($user){
 foreach(['page','action','export'] as $key)if(isset($_GET[$key])&&suspicious_route($_GET[$key])){
  security_event('suspicious_request',$user,['parameter'=>$key]);http_response_code(400);exit('Invalid route');
 }
}
