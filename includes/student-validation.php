<?php
function validate_student_request($kind,$input,$student){
$catalog=json_decode(file_get_contents(__DIR__.'/student-catalog.json'),true);
$text=function($key,$min=1,$max=190)use($input){$v=$input[$key]??'';if(!is_string($v)||strlen(trim($v))<$min||strlen($v)>$max)throw new InvalidArgumentException('Invalid or missing '.$key.'.');return trim($v);};
if($kind==='consultation'){
$p=$text('professor');$type=$text('concern_type');
if(!in_array($p,['Althea Navarro','Marco Villanueva','Celina Dela Cruz','Rafael Bautista','Bianca Soriano'],true)||!in_array($type,['Activity','Attendance','Assignment','Grade','Google Classroom','Project','Quiz','Other'],true))throw new InvalidArgumentException('Choose a listed professor and concern.');return ['professor'=>$p,'concern_type'=>$type,'concern'=>$text('concern',10,1500)];
}
if($kind==='cross_enroll'){$section=$text('section',4,40);foreach($catalog as $college)foreach($college[2] as $program)for($i=1;$i<=7;$i++)if($section===$program[1].'-'.str_pad($i,2,'0',STR_PAD_LEFT))return ['section'=>$section,'program'=>$program[0],'college'=>$college[1]];throw new InvalidArgumentException('Choose a listed section with available seats.');}
if($kind==='graduation'){
$data=[];foreach(['full_name','student_number','college','program','academic_year','term','contact','email'] as $key)$data[$key]=$text($key);
if($data['student_number']!==$student)throw new InvalidArgumentException('Use your signed-in student number.');
$valid=false;foreach($catalog as $college)if($data['college']===$college[1])foreach($college[2] as $program)if(in_array($data['program'],$program,true))$valid=true;
if(!$valid)throw new InvalidArgumentException('Enter a listed program name or code for the selected college.');
if(!preg_match('/^(20\d{2})-(20\d{2})$/',$data['academic_year'],$m)||(int)$m[2]!==((int)$m[1]+1))throw new InvalidArgumentException('Use an academic year such as 2026-2027.');
if(!in_array($data['term'],['First Semester','Second Semester','Summer'],true)||!preg_match('/^[+0-9 ()-]{6,25}$/',$data['contact'])||!filter_var($data['email'],FILTER_VALIDATE_EMAIL))throw new InvalidArgumentException('Check your term, phone number and email.');
$requirements=$input['requirements']??[];if(!is_array($requirements)||count(array_filter($requirements,'is_string'))!==count($requirements)||array_diff($requirements,['curriculum','capstone','clearance']))throw new InvalidArgumentException('Invalid requirements checklist.');$data['requirements']=array_values($requirements);return $data;
}
throw new InvalidArgumentException('Unknown request type.');
}
