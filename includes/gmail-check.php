<?php
require __DIR__.'/gmail.php';
$c=['enabled'=>true,'admin'=>'admin@example.invalid','student'=>'student@example.invalid'];
if(count(gmail_recipients($c,''))!==1)throw new Exception('Anonymous recipient isolation failed');
if(count(gmail_recipients($c,'unknown'))!==1)throw new Exception('Unknown account isolation failed');
if(count(gmail_recipients($c,'20261108','student@example.com'))!==2)throw new Exception('Student routing failed');
$c['enabled']=false;if(gmail_recipients($c,'20261108','student@example.com')!==[])throw new Exception('Disabled mail failed');
echo "Recipient routing checks passed; no emails sent.\n";
