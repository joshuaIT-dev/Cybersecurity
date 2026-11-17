<?php
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require __DIR__.'/detection-check.php';
foreach(['email_verifications','account_mail'] as $table){$sql=$db->query("SHOW CREATE TABLE $table")->fetch(PDO::FETCH_NUM)[1];$db->exec(str_replace('CREATE TABLE','CREATE TEMPORARY TABLE',$sql));}
$q=$db->prepare('INSERT INTO student_accounts(student_number,password_hash,notification_email) VALUES(?,?,?)');$q->execute(['FIXED_TEST',password_hash('12345',PASSWORD_DEFAULT),'fixed@example.com']);
$_SERVER['REMOTE_ADDR']='192.0.2.100';$token=student_session_start($db,'FIXED_TEST');$other=student_session_start($db,'FIXED_TEST');
foreach(['notification_email','verify_email','cancel_email'] as $kind){$rejected=false;try{student_security_action($db,'FIXED_TEST',$token,$kind,['current_password'=>'12345','notification_email'=>'changed@example.com']);}catch(InvalidArgumentException $e){$rejected=true;}check($rejected,'Email action accepted');}
gmail_queue($db,9001,'FIXED_TEST');check($db->query("SELECT recipient FROM mail_deliveries WHERE incident_id=9001 AND audience='student'")->fetchColumn()==='fixed@example.com','Fixed email not routed');
student_security_action($db,'FIXED_TEST',$token,'password',['current_pin'=>'12345','new_pin'=>'ChangedTest123','confirm_pin'=>'ChangedTest123']);
check($db->query("SELECT recipient FROM account_mail WHERE kind='password_changed'")->fetchColumn()==='fixed@example.com','Password notice missing');
check(!student_session_valid($db,'FIXED_TEST',$other)&&student_session_valid($db,'FIXED_TEST',$token),'Password session revocation failed');
check($db->query("SELECT notification_email FROM student_accounts WHERE student_number='FIXED_TEST'")->fetchColumn()==='fixed@example.com','Fixed email changed');
echo "PASS fixed email immutability, incident routing without verification, password notification and session revocation. No live mail sent.\n";
