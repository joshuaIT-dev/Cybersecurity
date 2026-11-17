<?php
require __DIR__.'/student-validation.php';
function rejected($kind,$input){try{validate_student_request($kind,$input,'20261108');throw new Exception('Invalid input accepted');}catch(InvalidArgumentException $e){}}
rejected('consultation',['professor'=>'Unknown','concern_type'=>'Other','concern'=>'Long enough concern']);
rejected('cross_enroll',['section'=>'BSIT-08']);rejected('cross_enroll',['section'=>'made-up']);
$v=validate_student_request('cross_enroll',['section'=>'BSIT-01'],'20261108');if($v['section']!=='BSIT-01')throw new Exception('Valid section rejected');
rejected('graduation',['full_name'=>'Test','email'=>'a@example.invalid']);
echo "Request validation checks passed.\n";
