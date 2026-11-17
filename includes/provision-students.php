<?php
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require_once __DIR__.'/security.php';
$db=security_db();
$columns=$db->query('SHOW COLUMNS FROM student_accounts')->fetchAll(PDO::FETCH_COLUMN);
foreach(['first_name'=>"VARCHAR(100) NOT NULL DEFAULT ''",'last_name'=>"VARCHAR(100) NOT NULL DEFAULT ''",'notification_email'=>'VARCHAR(254) NULL'] as $column=>$type){
 if(!in_array($column,$columns,true))$db->exec("ALTER TABLE student_accounts ADD $column $type");
}
$q=$db->prepare("UPDATE student_accounts SET first_name='Jordan',last_name='Navarro',notification_email=? WHERE student_number='20261108' AND first_name=''");
$q->execute(['alimaomadelyn@gmail.com']);
$q=$db->prepare('INSERT IGNORE INTO student_accounts(student_number,password_hash,first_name,last_name,notification_email) VALUES(?,?,?,?,?)');
foreach([['20261109','John Erick','Hernandez','hernandezjohnerick73@gmail.com'],['20261110','Jay','Sogaso','jaysogaso@gmail.com'],['20261111','Sofia','Reyes',null],['20261112','Adrian','Cruz',null]] as [$id,$first,$last,$email]){
 $q->execute([$id,password_hash('12345',PASSWORD_DEFAULT),$first,$last,$email]);
}
echo "Student setup complete. Existing accounts and passwords preserved.\n";
