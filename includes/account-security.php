<?php
function account_mail_queue($db,$student,$kind,$email,$subject,$body,$expires=null){
 if(!$email||!filter_var($email,FILTER_VALIDATE_EMAIL))return;
 $q=$db->prepare('INSERT INTO account_mail(student_number,kind,recipient,subject,body,expires_at) VALUES(?,?,?,?,?,?)');
 $q->execute([$student,$kind,$email,$subject,$body,$expires]);
}
function account_notice($db,$student,$kind,$email,$subject){
 account_mail_queue($db,$student,$kind,$email,$subject,$subject."\nStudent account: ".$student."\nTime: ".date('Y-m-d H:i:s')." PHT\nIP: ".($_SERVER['REMOTE_ADDR']??'unknown')."\n\nIf you did not make this change, sign in to the student portal, change your password, and contact ICT. Never share your password or verification codes.");
}
function cancel_email_verification($db,$student){
 $q=$db->prepare('DELETE FROM email_verifications WHERE student_number=?');$q->execute([$student]);
 $q=$db->prepare("UPDATE account_mail SET status=IF(status IN ('pending','retry'),'cancelled',status),body='' WHERE student_number=? AND kind='email_verification'");$q->execute([$student]);
}
function student_session_start($db,$student){
 $token=bin2hex(random_bytes(32));
 $q=$db->prepare('INSERT INTO student_sessions(student_number,token_hash,source_ip,user_agent,expires_at) VALUES(?,?,?,?,NOW()+INTERVAL 12 HOUR)');
 $q->execute([$student,hash('sha256',$token),$_SERVER['REMOTE_ADDR']??'unknown',substr($_SERVER['HTTP_USER_AGENT']??'',0,500)]);
 return $token;
}
function student_session_valid($db,$student,$token){
 if(!is_string($token)||$token==='')return false;
 $q=$db->prepare('SELECT id FROM student_sessions WHERE student_number=? AND token_hash=? AND revoked_at IS NULL AND expires_at>NOW()');$q->execute([$student,hash('sha256',$token)]);
 $id=$q->fetchColumn();if(!$id)return false;
 $q=$db->prepare('UPDATE student_sessions SET last_seen=NOW() WHERE id=?');$q->execute([$id]);return true;
}
function student_security_state($db,$student,$token){
 $q=$db->prepare('SELECT notification_email,email_verified_at FROM student_accounts WHERE student_number=?');$q->execute([$student]);$account=$q->fetch(PDO::FETCH_ASSOC);
 $q=$db->prepare('SELECT email,expires_at,attempts FROM email_verifications WHERE student_number=? AND expires_at>NOW()');$q->execute([$student]);$pending=$q->fetch(PDO::FETCH_ASSOC);
 $q=$db->prepare('SELECT id,source_ip,user_agent,created_at,last_seen,expires_at,token_hash FROM student_sessions WHERE student_number=? AND revoked_at IS NULL AND expires_at>NOW() ORDER BY last_seen DESC LIMIT 50');$q->execute([$student]);$sessions=$q->fetchAll(PDO::FETCH_ASSOC);
 foreach($sessions as &$session){$session['current']=hash_equals($session['token_hash'],hash('sha256',$token));unset($session['token_hash']);}unset($session);
 return ['email'=>$account['notification_email'],'verified'=>(bool)$account['email_verified_at'],'pending'=>$pending?:null,'sessions'=>$sessions];
}
function student_security_action($db,$student,$token,$kind,$data){
 if(!in_array($kind,['password','revoke_session'],true))throw new InvalidArgumentException('Student email addresses are fixed school records and cannot be changed in the portal.');
 $db->beginTransaction();
 try{
  $q=$db->prepare('SELECT * FROM student_accounts WHERE student_number=? FOR UPDATE');$q->execute([$student]);$account=$q->fetch(PDO::FETCH_ASSOC);
  if(!$account)throw new InvalidArgumentException('Account unavailable.');
  $q=$db->prepare('SELECT id FROM student_sessions WHERE student_number=? AND token_hash=? AND revoked_at IS NULL AND expires_at>NOW() FOR UPDATE');$q->execute([$student,hash('sha256',$token)]);
  if(!$q->fetchColumn())throw new InvalidArgumentException('This session has expired or was signed out. Sign in again.');
  $old=$account['notification_email'];
  if($kind==='verify_email'){
   $code=$data['code']??'';$q=$db->prepare('SELECT *,expires_at>NOW() valid FROM email_verifications WHERE student_number=? FOR UPDATE');$q->execute([$student]);$pending=$q->fetch(PDO::FETCH_ASSOC);
   if(!$pending||!$pending['valid']||$pending['attempts']>=5)throw new InvalidArgumentException('Code expired or too many attempts. Request a new code.');
   if(!is_string($code)||!preg_match('/^\d{6}$/',$code)||!password_verify($code,$pending['code_hash'])){
    $q=$db->prepare('UPDATE email_verifications SET attempts=attempts+1 WHERE student_number=?');$q->execute([$student]);$db->commit();throw new InvalidArgumentException('Incorrect code. You have '.max(0,4-$pending['attempts']).' attempts remaining.');
   }
   $q=$db->prepare('UPDATE student_accounts SET notification_email=?,email_verified_at=NOW() WHERE student_number=?');$q->execute([$pending['email'],$student]);
   if($old&&strcasecmp($old,$pending['email'])!==0)account_notice($db,$student,'email_changed',$old,'Your student notification email was changed');
   account_notice($db,$student,'email_verified',$pending['email'],'Your student notification email is verified');
   cancel_email_verification($db,$student);$message='Email verified. Security alerts will now use this address.';
  }else{
   $q=$db->prepare("SELECT COUNT(*) FROM security_events WHERE user_identifier=? AND event_type='security_change_failed' AND occurred_at>NOW()-INTERVAL 15 MINUTE");$q->execute([$student]);
   if($q->fetchColumn()>=5)throw new InvalidArgumentException('Too many incorrect password confirmations. Try again in 15 minutes.');
   $password=$data[$kind==='password'?'current_pin':'current_password']??'';
   if(!is_string($password)||!password_verify($password,$account['password_hash'])){
    $q=$db->prepare("INSERT INTO security_events(event_type,severity,user_identifier,source_ip,user_agent,event_details,occurred_at) VALUES('security_change_failed','medium',?,?,?,'{}',NOW())");$q->execute([$student,$_SERVER['REMOTE_ADDR']??'unknown',substr($_SERVER['HTTP_USER_AGENT']??'',0,500)]);$db->commit();throw new InvalidArgumentException('Your current password is incorrect.');
   }
   if($kind==='notification_email'){
    $email=$data['notification_email']??null;if(!is_string($email)||strlen($email)>254||!filter_var(trim($email),FILTER_VALIDATE_EMAIL))throw new InvalidArgumentException('Enter a valid email address.');$email=trim($email);
    $config=gmail_config();if(empty($config['enabled'])||empty($config['sender'])||empty($config['password']))throw new InvalidArgumentException('Email delivery is unavailable. Contact ICT; your current email has not changed.');
    $q=$db->prepare("SELECT COUNT(*) FROM account_mail WHERE student_number=? AND kind='email_verification' AND created_at>NOW()-INTERVAL 1 HOUR");$q->execute([$student]);if($q->fetchColumn()>=5)throw new InvalidArgumentException('Verification request limit reached. Try again in one hour.');
    $q=$db->prepare("SELECT COUNT(*) FROM account_mail WHERE student_number=? AND kind='email_verification' AND created_at>NOW()-INTERVAL 1 MINUTE");$q->execute([$student]);if($q->fetchColumn())throw new InvalidArgumentException('Wait one minute before requesting another code.');
    if($old&&strcasecmp($old,$email)===0)throw new InvalidArgumentException('This email is already verified.');
    cancel_email_verification($db,$student);$code=(string)random_int(100000,999999);$expires=date('Y-m-d H:i:s',time()+900);
    $q=$db->prepare('INSERT INTO email_verifications(student_number,email,code_hash,requested_at,expires_at) VALUES(?,?,?,NOW(),?)');$q->execute([$student,$email,password_hash($code,PASSWORD_DEFAULT),$expires]);
    account_mail_queue($db,$student,'email_verification',$email,'Verify your student notification email',"Your verification code is: $code\n\nEnter it in Student Portal > Profile > Contact Info. It expires in 15 minutes and can be used once. Never share this code. If you did not request it, ignore this email.",$expires);
    if($old)account_notice($db,$student,'email_change_requested',$old,'A change to your student notification email was requested');
    $message='Verification code queued for your new email. Enter it below within 15 minutes. Your current verified email remains active until then.';
   }elseif($kind==='cancel_email'){
    cancel_email_verification($db,$student);$message='Pending email change cancelled.';
   }elseif($kind==='password'){
    $new=$data['new_pin']??'';if(!is_string($new)||strlen($new)<8||strlen($new)>128||$new!==($data['confirm_pin']??null)||password_verify($new,$account['password_hash']))throw new InvalidArgumentException('Use a different password of 8–128 characters and confirm it.');
    $q=$db->prepare('UPDATE student_accounts SET password_hash=?,session_version=session_version+1 WHERE student_number=?');$q->execute([password_hash($new,PASSWORD_DEFAULT),$student]);
    $q=$db->prepare('UPDATE student_sessions SET revoked_at=NOW() WHERE student_number=? AND token_hash<>? AND revoked_at IS NULL');$q->execute([$student,hash('sha256',$token)]);
    cancel_email_verification($db,$student);account_notice($db,$student,'password_changed',$old,'Your student portal password was changed');
    $message='Password changed. Other sessions were signed out and any pending email change was cancelled.';
   }elseif($kind==='revoke_session'){
    $id=filter_var($data['session_id']??null,FILTER_VALIDATE_INT);if(!$id)throw new InvalidArgumentException('Choose a session.');
    $q=$db->prepare('UPDATE student_sessions SET revoked_at=NOW() WHERE id=? AND student_number=? AND token_hash<>? AND revoked_at IS NULL');$q->execute([$id,$student,hash('sha256',$token)]);if(!$q->rowCount())throw new InvalidArgumentException('Session unavailable. Use Logout for this session.');$message='Session signed out.';
   }else throw new InvalidArgumentException('Unknown security action.');
  }
  $db->commit();return ['message'=>$message,'version'=>(int)$account['session_version']+($kind==='password'?1:0)];
 }catch(Throwable $e){if($db->inTransaction())$db->rollBack();throw $e;}
}
function process_account_mail($db,$config,$send=null){
 $db->exec("UPDATE account_mail SET status='cancelled',body='' WHERE kind IN ('email_verification','email_change_requested','email_changed','email_verified') AND status IN ('pending','retry')");
 $send=$send??'gmail_send';$counts=['sent'=>0,'failed'=>0];if(empty($config['enabled']))return $counts;
 $db->exec("UPDATE account_mail SET status='expired',body='' WHERE kind='email_verification' AND expires_at<=NOW() AND status IN ('pending','retry')");
 $db->exec("UPDATE account_mail SET body='' WHERE kind='email_verification' AND expires_at<=NOW() AND body<>''");
 foreach($db->query("SELECT id,student_number FROM account_mail WHERE status IN ('pending','retry') AND next_attempt<=NOW() AND attempts<3 ORDER BY id LIMIT 10")->fetchAll(PDO::FETCH_ASSOC) as $item){
  // Lock the account before its mail row, matching the email-change transaction order.
  $db->beginTransaction();
  try{
   $q=$db->prepare('SELECT student_number FROM student_accounts WHERE student_number=? FOR UPDATE');$q->execute([$item['student_number']]);
   $q=$db->prepare("SELECT * FROM account_mail WHERE id=? AND status IN ('pending','retry') AND (expires_at IS NULL OR expires_at>NOW()) FOR UPDATE");$q->execute([$item['id']]);$row=$q->fetch(PDO::FETCH_ASSOC);if(!$row){$db->commit();continue;}
   try{$send($config,$row['recipient'],$row['subject'],$row['body']);$q=$db->prepare("UPDATE account_mail SET status='sent',sent_at=NOW(),attempts=attempts+1,body=IF(kind='email_verification','',body),last_error=NULL WHERE id=?");$q->execute([$row['id']]);$counts['sent']++;}
   catch(Throwable $e){$q=$db->prepare("UPDATE account_mail SET status=IF(attempts>=2,'failed','retry'),attempts=attempts+1,next_attempt=NOW()+INTERVAL 5 MINUTE,last_error='SMTP delivery failed. Check Gmail connection.' WHERE id=?");$q->execute([$row['id']]);$counts['failed']++;}
   $db->commit();
  }catch(Throwable $e){if($db->inTransaction())$db->rollBack();throw $e;}
 }
 return $counts;
}
