<?php
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require_once __DIR__.'/security.php';
try{
 $files=['schema.sql','monitoring.sql','student.sql','gmail.sql','hardening.sql','report-schedules.sql','account-security.sql'];
 foreach($files as $file)if(!is_readable(__DIR__.'/../database/'.$file))throw new RuntimeException('Missing database file: '.$file);
 $db=new PDO('mysql:host=127.0.0.1;charset=utf8mb4',getenv('CYBER_DB_USER')?:'root',getenv('CYBER_DB_PASSWORD')?:'',[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
 $db->exec(file_get_contents(__DIR__.'/../database/schema.sql'));
 $hadStudents=$db->query("SHOW TABLES LIKE 'student_accounts'")->fetchColumn();
 $existing=$hadStudents?$db->query('SELECT student_number FROM student_accounts')->fetchAll(PDO::FETCH_COLUMN):[];
 foreach(array_slice($files,1) as $file){$db->exec(file_get_contents(__DIR__.'/../database/'.$file));echo 'Applied '.$file.PHP_EOL;}
 require __DIR__.'/provision-students.php';
 if(!in_array('20261108',$existing,true)){
  $q=$db->prepare('UPDATE student_accounts SET password_hash=?,first_name=?,last_name=?,notification_email=? WHERE student_number=?');
  $q->execute([password_hash('12345',PASSWORD_DEFAULT),'Jordan','Navarro','joshuamalapote21@gmail.com','20261108']);
 }
 $q=$db->prepare('INSERT IGNORE INTO security_admin(id,username,password_hash) VALUES(1,?,?)');$q->execute(['cyber',password_hash('12345',PASSWORD_DEFAULT)]);
 foreach(['security_events','incidents','security_alerts','security_admin','student_accounts','student_requests','student_sessions','account_mail','report_schedule'] as $table)$db->query('SELECT 1 FROM '.$table.' LIMIT 1');
 echo PHP_EOL."Database plmun_cybersecurity is ready.\n";
 echo "New demo accounts use password 12345. Existing passwords were not reset.\n";
 echo "Admin: cyber\nStudents: 20261108, 20261109, 20261110, 20261111, 20261112\n";
 echo "Gmail connection and the Windows mail-worker task must be configured separately on a new computer.\n";
}catch(Throwable $e){
 if($e instanceof PDOException)fwrite(STDERR,"Database setup failed. Ensure XAMPP MySQL is running and CYBER_DB_USER / CYBER_DB_PASSWORD match its credentials. Database error code: ".$e->getCode()."\n");
 else fwrite(STDERR,$e->getMessage()."\n");
 exit(1);
}
