<?php
require __DIR__.'/auth.php';
header('Content-Type: application/json');
function reply($data,$code=200){http_response_code($code);echo json_encode($data);exit;}
if(empty($_SESSION['student'])){security_event($_SERVER['REQUEST_METHOD']==='POST'?'access_denied':'login_required','',['resource'=>'student_api']);reply(['error'=>'Sign in required'],401);}
try{
$db=security_db();$student=$_SESSION['student'];
if($_SERVER['REQUEST_METHOD']==='GET'){
if(($_GET['action']??'')==='photo'){$q=$db->prepare('SELECT photo,photo_type FROM student_accounts WHERE student_number=?');$q->execute([$student]);$row=$q->fetch(PDO::FETCH_ASSOC);reply(['photo'=>$row['photo']?'data:'.$row['photo_type'].';base64,'.base64_encode($row['photo']):null]);}

$q=$db->prepare("SELECT occurred_at,event_type,source_ip,severity FROM security_events WHERE user_identifier=? ORDER BY id DESC LIMIT 30");$q->execute([$student]);$events=$q->fetchAll(PDO::FETCH_ASSOC);
$r=$db->prepare('SELECT id,kind,status,response,created_at FROM student_requests WHERE student_number=? ORDER BY id DESC');$r->execute([$student]);reply(['csrf'=>$_SESSION['csrf'],'events'=>$events,'requests'=>$r->fetchAll(PDO::FETCH_ASSOC),'security'=>student_security_state($db,$student,$_SESSION['session_token'])]);
}
if($_SERVER['REQUEST_METHOD']!=='POST'||!valid_csrf()){security_event('csrf_failed',$student);reply(['error'=>'Invalid request'],403);}
$kind=$_POST['kind']??'';
if(in_array($kind,['notification_email','verify_email','cancel_email'],true))reply(['error'=>'Your email is a fixed school record and cannot be changed in the portal.'],403);
if(in_array($kind,['notification_email','verify_email','cancel_email','password','revoke_session'],true)){
 try{$result=student_security_action($db,$student,$_SESSION['session_token'],$kind,$_POST);}catch(InvalidArgumentException $e){reply(['error'=>$e->getMessage()],422);}
 if($kind==='password'){$_SESSION['session_version']=$result['version'];session_regenerate_id(true);}
 $events=['notification_email'=>'notification_email_change_requested','verify_email'=>'notification_email_verified','cancel_email'=>'notification_email_change_cancelled','password'=>'password_changed','revoke_session'=>'session_revoked'];
 security_event($events[$kind],$student);
 reply(['message'=>$result['message'],'security'=>student_security_state($db,$student,$_SESSION['session_token'])]);
}
if($kind==='photo'){
$file=$_FILES['photo']??null;
if(!$file||$file['error']!==UPLOAD_ERR_OK||$file['size']>2*1024*1024||!is_uploaded_file($file['tmp_name']))reply(['error'=>'Select a JPG or PNG up to 2 MB'],422);
$info=@getimagesize($file['tmp_name']);if(!$info||!in_array($info['mime'],['image/jpeg','image/png'],true)||$info[0]>6000||$info[1]>6000)reply(['error'=>'Invalid image or dimensions too large'],422);
$q=$db->prepare('UPDATE student_accounts SET photo=?,photo_type=? WHERE student_number=?');$q->execute([file_get_contents($file['tmp_name']),$info['mime'],$student]);security_event('profile_photo_changed',$student);reply(['message'=>'Profile photo saved.']);
}
if(!in_array($kind,['consultation','graduation','cross_enroll'],true))reply(['error'=>'Unknown request'],422);
require_once __DIR__.'/../includes/student-validation.php';
try{$payload=validate_student_request($kind,$_POST,$student);}catch(InvalidArgumentException $e){reply(['error'=>$e->getMessage()],422);}
$json=json_encode($payload);
$q=$db->prepare('INSERT INTO student_requests(student_number,kind,payload) VALUES(?,?,?)');$q->execute([$student,$kind,$json]);$id=$db->lastInsertId();security_event('request_submitted',$student,['kind'=>$kind,'request_id'=>$id]);reply(['message'=>'Request #'.$id.' saved.','id'=>$id]);
}catch(Throwable $e){error_log($e->getMessage());reply(['error'=>'Service unavailable. Please retry.'],503);}
