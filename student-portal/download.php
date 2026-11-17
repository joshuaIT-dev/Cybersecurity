<?php
require __DIR__.'/auth.php';
if(empty($_SESSION['student'])){security_event('login_required','',['resource'=>'student_document']);http_response_code(401);exit('Sign in to download this document.');}
$files=[
 'enrolled'=>['Enrolled-Subjects-Test-Report.pdf','application/pdf'],
 'qr'=>['student-qr-'.$_SESSION['student'].'.docx','application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
 'qr-image'=>['student-qr-'.$_SESSION['student'].'.png','image/png']
];
$key=$_GET['document']??'';
if(!is_string($key)||!isset($files[$key])){http_response_code(404);exit('Document unavailable.');}
[$file,$mime]=$files[$key];$path=__DIR__.'/assets/'.$file;
if(!is_file($path)){http_response_code(404);exit('Document unavailable.');}
if($key!=='qr-image')security_event('document_download',$_SESSION['student'],['document'=>$key]);
header('Content-Type: '.$mime);header('X-Content-Type-Options: nosniff');header('Content-Length: '.filesize($path));
header('Content-Disposition: '.($key==='qr-image'?'inline':'attachment').'; filename="'.$file.'"');
readfile($path);
