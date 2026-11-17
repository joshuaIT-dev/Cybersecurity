<?php
const GMAIL_CONFIG='C:/xampp/cybersecurity-private/gmail.json';
function gmail_config(){return is_file(GMAIL_CONFIG)?json_decode(file_get_contents(GMAIL_CONFIG),true)?:[]:[];}
function gmail_send($config,$recipient,$subject,$body){
require_once __DIR__.'/phpmailer/Exception.php';require_once __DIR__.'/phpmailer/PHPMailer.php';require_once __DIR__.'/phpmailer/SMTP.php';
$mail=new PHPMailer\PHPMailer\PHPMailer(true);$mail->isSMTP();$mail->Host='smtp.gmail.com';$mail->Port=587;$mail->SMTPSecure=PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;$mail->SMTPAuth=true;$mail->Username=$config['sender'];$mail->Password=$config['password'];$mail->Timeout=20;$mail->CharSet='UTF-8';$mail->setFrom($config['sender'],'PLMUN Cyber Watch');$mail->addAddress($recipient);$mail->Subject=$subject;$mail->Body=$body;$mail->send();
}
function gmail_recipients($c,$user,$studentEmail=null){if(empty($c['enabled']))return []; $recipients=[$c['admin']=>'admin'];$email=$studentEmail??'';if($email&&filter_var($email,FILTER_VALIDATE_EMAIL)&&$email!==$c['admin'])$recipients[$email]='student';return $recipients;}
function gmail_queue($db,$incident,$user){$c=gmail_config();$q=$db->prepare('SELECT notification_email FROM student_accounts WHERE student_number=?');$q->execute([$user]);$email=$q->fetchColumn();foreach(gmail_recipients($c,$user,$email===false?'':($email??'')) as $email=>$audience){$q=$db->prepare('INSERT IGNORE INTO mail_deliveries(incident_id,recipient,audience) VALUES(?,?,?)');$q->execute([$incident,$email,$audience]);}}
