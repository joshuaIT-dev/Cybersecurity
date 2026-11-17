<?php
$students=$db->query('SELECT student_number,first_name,last_name,notification_email FROM student_accounts ORDER BY student_number')->fetchAll(PDO::FETCH_ASSOC);
panel_start('Student email recipients','Live student portal records. Email addresses are fixed and are not edited from this dashboard.');
?>
<div class="notice"><b>Administrator recipient</b><span><?=h($config['admin']??'Not configured')?> receives all new incident alerts. Students receive only alerts linked to their own account. When both addresses match, one incident email is sent.</span></div>
<div class="table-scroll"><table><thead><tr><th>STUDENT</th><th>STUDENT NUMBER</th><th>PORTAL EMAIL</th><th>ALERT ROUTING</th></tr></thead><tbody>
<?php foreach($students as $student):$email=$student['notification_email'];$valid=$email&&filter_var($email,FILTER_VALIDATE_EMAIL);?>
<tr><td><?=h(trim($student['first_name'].' '.$student['last_name']))?></td><td><?=h($student['student_number'])?></td><td><?=h($email?:'Not assigned')?></td><td><?=h(!$valid?'Administrator only — no valid student email':(empty($config['enabled'])?'Email delivery paused':($email===($config['admin']??'')?'Student and administrator share this inbox':'Student email and administrator'))) ?></td></tr>
<?php endforeach;?></tbody></table></div>
<div class="notice"><b>What triggers an email</b><span>Detected incidents use these account addresses automatically. Password-change notices go to the affected student. Anonymous events notify only the administrator. Routine page visits do not send an email unless a detection threshold is reached. <a href="?page=rules">View detection rules</a></span></div>
</article>
