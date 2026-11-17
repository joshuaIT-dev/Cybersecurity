<?php
require_once __DIR__.'/security.php';
session_name('cyber_admin');session_set_cookie_params(['httponly'=>true,'samesite'=>'Strict','secure'=>!empty($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=='off']);session_start();header('Cache-Control: no-store');
inspect_route(!empty($_SESSION['admin'])?'cyber':'');
if(empty($_SESSION['admin'])) {
 // Inspect the student session only after the administrator session is rejected.
 session_write_close();$studentActor='';
 if(is_string($_COOKIE['PHPSESSID']??null)&&preg_match('/^[a-zA-Z0-9,-]{16,128}$/',$_COOKIE['PHPSESSID'])){
  session_name('PHPSESSID');session_id($_COOKIE['PHPSESSID']);session_start(['read_and_close'=>true,'use_cookies'=>false,'use_strict_mode'=>true]);
  if(isset($_SESSION['student'],$_SESSION['session_version'])){$check=security_db()->prepare('SELECT session_version FROM student_accounts WHERE student_number=?');$check->execute([$_SESSION['student']]);$version=$check->fetchColumn();if($version!==false&&(int)$version===(int)$_SESSION['session_version']&&student_session_valid(security_db(),$_SESSION['student'],$_SESSION['session_token']??''))$studentActor=$_SESSION['student'];}
 }
 security_event($studentActor!==''||$_SERVER['REQUEST_METHOD']==='POST'?'access_denied':'login_required',$studentActor,['resource'=>'security_dashboard']);header('Location: admin.php');exit;
}
$_SESSION['csrf']=$_SESSION['csrf']??bin2hex(random_bytes(32));
$db=security_db();
require_once __DIR__.'/scheduled-reports.php';
if($_SERVER['REQUEST_METHOD']==='POST'&&!defined('GMAIL_SETTINGS')) {
 if(!is_string($_POST['csrf']??null)||!hash_equals($_SESSION['csrf'],$_POST['csrf'])){security_event('csrf_failed','cyber');http_response_code(403);exit('Invalid request');}
 if(isset($_POST['read_all'])){
  $through=filter_var($_POST['read_all'],FILTER_VALIDATE_INT);
  if($through===false||$through<0){http_response_code(422);exit('Invalid notification request.');}
  $q=$db->prepare("UPDATE security_alerts SET status='read' WHERE id<=? AND status='recorded'");$q->execute([$through]);
  header('Location: index.php?notifications_read=1');exit;
 }
 if(isset($_POST['save_report_schedule'])){
  require_once __DIR__.'/scheduled-reports.php';
  $frequency=$_POST['frequency']??'';$time=$_POST['send_time']??'';
  if(!in_array($frequency,['daily','weekly'],true)||!is_string($time)||!preg_match('/^(?:[01][0-9]|2[0-3]):[0-5][0-9]$/',$time)){http_response_code(422);exit('Choose a valid report frequency and time.');}
  $enabled=isset($_POST['enabled']);$q=$db->prepare('UPDATE report_schedule SET enabled=?,frequency=?,send_time=?,next_run=? WHERE id=1');
  $q->execute([(int)$enabled,$frequency,$time,$enabled?report_next_run($frequency,$time):null]);
  header('Location: index.php?page=reports&schedule_saved=1#report-schedule');exit;
 }
 if(isset($_POST['request_id'])){
$id=filter_var($_POST['request_id'],FILTER_VALIDATE_INT);$status=$_POST['request_status']??'';$response=$_POST['response']??'';
if(!$id||!in_array($status,['reviewing','approved','rejected','completed'],true)||!is_string($response)||trim($response)===''||strlen($response)>2000){http_response_code(422);exit('Invalid request decision.');}
$db->beginTransaction();$q=$db->prepare('SELECT id FROM student_requests WHERE id=? FOR UPDATE');$q->execute([$id]);if(!$q->fetchColumn()){$db->rollBack();http_response_code(404);exit('Request not found.');}
$q=$db->prepare('UPDATE student_requests SET status=?,response=? WHERE id=?');$q->execute([$status,trim($response),$id]);$q=$db->prepare('INSERT INTO student_request_updates(request_id,status,response) VALUES(?,?,?)');$q->execute([$id,$status,trim($response)]);$db->commit();security_event('student_request_reviewed','cyber',['request_id'=>$id,'status'=>$status]);header('Location: ?page=source');exit;
 }
 if(isset($_POST['incident'])&&is_scalar($_POST['incident'])){
  $id=filter_var($_POST['incident'],FILTER_VALIDATE_INT);$status=$_POST['status']??'';
  if(!$id||!in_array($status,['open','investigating','resolved','closed'],true)){http_response_code(422);exit('Invalid incident status.');}
  $q=$db->prepare('SELECT status FROM incidents WHERE id=?');$q->execute([$id]);$current=$q->fetchColumn();
  if($current===false){http_response_code(404);exit('Incident not found.');}
  if($current===$status){header('Location: ?page=incidents&incident='.$id.'&unchanged=1#investigation');exit;}
  $q=$db->prepare('UPDATE incidents SET status=? WHERE id=?');$q->execute([$status,$id]);
  security_event('incident_updated','cyber',['incident'=>$id,'status'=>$status]);
  header('Location: ?page=incidents&incident='.$id.'&saved=1#investigation');exit;
 }
 header('Location: ?page=incidents');exit;
}
function download_csv($filename,$headers,$rows){
 header('Content-Type: text/csv; charset=utf-8');header('Content-Disposition: attachment; filename="'.$filename.'"');$out=fopen('php://output','w');fwrite($out,"\xEF\xBB\xBF");fputcsv($out,$headers);
 foreach($rows as $row)fputcsv($out,array_map(static function($value){$value=(string)($value??'');return preg_match('/^[=+@\-\t\r]/',$value)?"'".$value:$value;},array_values($row)));exit;
}
function download_docx($filename,$title,$headers,$rows){
 if(!class_exists('PharData')){http_response_code(503);exit('DOCX export is unavailable on this PHP installation.');}
 if($rows instanceof Traversable)$rows=iterator_to_array($rows,false);
 $xmlText=static function($value){$value=preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/','',(string)($value??''));return htmlspecialchars($value,ENT_XML1|ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');};$width=14500;$cellWidth=(int)floor($width/count($headers));
 $cell=static function($value,$header)use($xmlText,$cellWidth){$shade=$header?'1D293A':'F4F6FA';$color=$header?'FFFFFF':'202A3A';return '<w:tc><w:tcPr><w:tcW w:w="'.$cellWidth.'" w:type="dxa"/><w:shd w:fill="'.$shade.'"/><w:tcMar><w:top w:w="90" w:type="dxa"/><w:start w:w="100" w:type="dxa"/><w:bottom w:w="90" w:type="dxa"/><w:end w:w="100" w:type="dxa"/></w:tcMar></w:tcPr><w:p><w:pPr><w:spacing w:after="40"/></w:pPr><w:r><w:rPr><w:rFonts w:ascii="Aptos" w:hAnsi="Aptos"/><w:color w:val="'.$color.'"/><w:sz w:val="18"/>'.($header?'<w:b/>':'').'</w:rPr><w:t xml:space="preserve">'.$xmlText($value).'</w:t></w:r></w:p></w:tc>';};
 $table='<w:tbl><w:tblPr><w:tblW w:w="'.$width.'" w:type="dxa"/><w:tblBorders><w:top w:val="single" w:sz="5" w:color="D5DCE8"/><w:left w:val="single" w:sz="5" w:color="D5DCE8"/><w:bottom w:val="single" w:sz="5" w:color="D5DCE8"/><w:right w:val="single" w:sz="5" w:color="D5DCE8"/><w:insideH w:val="single" w:sz="4" w:color="D5DCE8"/><w:insideV w:val="single" w:sz="4" w:color="D5DCE8"/></w:tblBorders></w:tblPr><w:tr>'.implode('',array_map(fn($value)=>$cell($value,true),$headers)).'</w:tr>';
 foreach($rows as $row)$table.='<w:tr>'.implode('',array_map(fn($value)=>$cell($value,false),array_values($row))).'</w:tr>';
 $landscape=count($headers)>4;$page=$landscape?'<w:pgSz w:w="16838" w:h="11906" w:orient="landscape"/>':'<w:pgSz w:w="11906" w:h="16838"/>';
 $document='<?xml version="1.0" encoding="UTF-8" standalone="yes"?><w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:body><w:p><w:pPr><w:spacing w:after="100"/></w:pPr><w:r><w:rPr><w:rFonts w:ascii="Aptos Display" w:hAnsi="Aptos Display"/><w:b/><w:color w:val="6842A6"/><w:sz w:val="36"/></w:rPr><w:t>'.$xmlText($title).'</w:t></w:r></w:p><w:p><w:r><w:rPr><w:rFonts w:ascii="Aptos" w:hAnsi="Aptos"/><w:color w:val="667085"/><w:sz w:val="20"/></w:rPr><w:t>PLMUN Cybersecurity / Generated '.date('d M Y, g:i A').' PHT</w:t></w:r></w:p><w:p><w:r><w:rPr><w:color w:val="667085"/><w:sz w:val="18"/></w:rPr><w:t>'.count($rows).' records</w:t></w:r></w:p>'.$table.'</w:tbl><w:sectPr>'.$page.'<w:pgMar w:top="720" w:right="720" w:bottom="720" w:left="720" w:header="360" w:footer="360"/></w:sectPr></w:body></w:document>';
 $path=sys_get_temp_dir().DIRECTORY_SEPARATOR.bin2hex(random_bytes(16)).'.docx';try{$zip=new PharData($path,0,null,Phar::ZIP);$zip->addFromString('[Content_Types].xml','<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/></Types>');$zip->addFromString('_rels/.rels','<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/></Relationships>');$zip->addFromString('word/document.xml',$document);unset($zip);}catch(Throwable $e){@unlink($path);error_log('DOCX export failed: '.$e->getMessage());http_response_code(500);exit('Could not create DOCX export.');}
 header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');header('Content-Disposition: attachment; filename="'.$filename.'"');header('Content-Length: '.filesize($path));readfile($path);@unlink($path);exit;
}
$export=$_GET['export']??'';$docx=($_GET['format']??'')==='docx';
if($export==='events'){$headers=['Event ID','Date and time (PHT)','Event type','Account','Source IP address','Severity'];$rows=$db->query('SELECT id,occurred_at,event_type,user_identifier,source_ip,severity FROM security_events ORDER BY id DESC LIMIT 10000',PDO::FETCH_NUM);if($docx)download_docx('security-events.docx','Security Event Log',$headers,$rows);download_csv('security-events.csv',$headers,$rows);}
if($export==='schedules'){$headers=['Report name','Frequency','What it includes','Export format','Availability'];$rows=report_catalog($db);if($docx)download_docx('scheduled-reports.docx','Scheduled Reports and Exports',$headers,$rows);download_csv('scheduled-reports.csv',$headers,$rows);}
if($export==='threats'){$headers=['Detection ID','Detected at (PHT)','Matched rule','Account','Source IP address','Priority','Incident status','Alert ID'];$rows=$db->query("SELECT CONCAT('INC-',i.id),i.created_at,i.title,COALESCE(e.user_identifier,'Unauthenticated'),COALESCE(e.source_ip,''),i.priority,i.status,COALESCE(a.id,'') FROM incidents i LEFT JOIN security_events e ON e.id=i.event_id LEFT JOIN security_alerts a ON a.incident_id=i.id ORDER BY i.id DESC",PDO::FETCH_NUM);if($docx)download_docx('threat-detections.docx','Threat Detection Matches',$headers,$rows);download_csv('threat-detections.csv',$headers,$rows);}
if($export==='incidents'){$headers=['Incident ID','Summary','Account','Source IP address','Event type','Priority','Status','Opened at (PHT)','Updated at (PHT)'];$rows=$db->query("SELECT CONCAT('INC-',i.id),i.title,COALESCE(e.user_identifier,'Unauthenticated'),COALESCE(e.source_ip,''),COALESCE(e.event_type,''),i.priority,i.status,i.created_at,i.updated_at FROM incidents i LEFT JOIN security_events e ON e.id=i.event_id ORDER BY i.id DESC",PDO::FETCH_NUM);if($docx)download_docx('incidents.docx','Incident Register',$headers,$rows);download_csv('incidents.csv',$headers,$rows);}
