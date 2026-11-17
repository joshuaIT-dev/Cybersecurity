<?php
const GMAIL_SETTINGS=true;
require __DIR__.'/includes/guard.php';
if($_SERVER['REQUEST_METHOD']!=='POST'||!is_string($_POST['csrf']??null)||!hash_equals($_SESSION['csrf'],$_POST['csrf'])){security_event('csrf_failed','cyber');http_response_code(403);exit('Invalid request');}
try{
$c=gmail_config();
if(($_POST['action']??'')==='test'){
if(empty($c['sender'])||empty($c['admin']))throw new RuntimeException('Save sender and recipient settings first.');
gmail_send($c,$c['admin'],'PLMUN Cyber Watch / connection test','This is the test email you requested from the cybersecurity dashboard. Gmail SMTP delivery is working.');
$_SESSION['gmail_notice']='Test message accepted by Gmail. Check the administrator inbox and spam folder.';
}else{
foreach(['sender','admin'] as $field){$value=$_POST[$field]??'';if(!is_string($value)||!filter_var(trim($value),FILTER_VALIDATE_EMAIL))throw new RuntimeException('Enter valid sender and administrator email addresses.');$new[$field]=trim($value);}
$password=$_POST['password']??'';if(!is_string($password))throw new RuntimeException('Invalid app password.');$password=preg_replace('/\s+/','',$password);if($password===''){$password=$c['password']??'';if(($c['sender']??'')!==$new['sender'])throw new RuntimeException('Enter the app password for the new sender.');}if(!preg_match('/^[a-zA-Z0-9]{16}$/',$password))throw new RuntimeException('Enter the 16-character Google app password, not your normal Google password.');
$new['password']=$password;$new['enabled']=isset($_POST['enabled']);
$temp=GMAIL_CONFIG.'.tmp';if(file_put_contents($temp,json_encode($new),LOCK_EX)===false||!rename($temp,GMAIL_CONFIG))throw new RuntimeException('Could not save mail settings.');
$_SESSION['gmail_notice']='Settings saved. Use Send test email to verify the connection. Automatic delivery applies to newly detected incidents when enabled.';

}
}catch(Throwable $e){$_SESSION['gmail_notice']=$e instanceof RuntimeException?$e->getMessage():'Gmail connection failed. Check the sender/app password, internet connection, and TLS certificates. No successful delivery was recorded.';}
header('Location: index.php?page=alerts');exit;
