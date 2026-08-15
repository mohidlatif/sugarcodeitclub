<?php
session_start();
$root = dirname(__DIR__);
$photosFile = $root . '/data/photos.json';
$uploadDir = $root . '/uploads';
function readData($path){ if(!is_file($path))return[]; $data=json_decode((string)file_get_contents($path),true); return is_array($data)?$data:[]; }
function saveData($path,$data){ $json=json_encode($data,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES); return file_put_contents($path,$json.PHP_EOL,LOCK_EX)!==false; }
function cleanText($value,$maxLength){ $value=trim((string)$value); return function_exists('mb_substr')?mb_substr($value,0,$maxLength):substr($value,0,$maxLength); }
function makeId(){ return bin2hex(random_bytes(8)); }
function h($value){ return htmlspecialchars((string)$value,ENT_QUOTES,'UTF-8'); }
if(empty($_SESSION['csrf']))$_SESSION['csrf']=bin2hex(random_bytes(24));
$message=''; $error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
 if(!hash_equals($_SESSION['csrf'],(string)($_POST['csrf']??''))){http_response_code(403);exit('Invalid request token.');}
 $action=(string)($_POST['action']??''); $photos=readData($photosFile);
 if($action==='upload_photo'){
  $caption=cleanText($_POST['caption']??'',120); $section=strtolower(cleanText($_POST['section']??'about',20)); $allowed=['about','meetings','projects','impact']; if(!in_array($section,$allowed,true))$section='about';
  if(!isset($_FILES['photo'])||$_FILES['photo']['error']!==UPLOAD_ERR_OK)$error='Choose a photo to upload.';
  elseif($_FILES['photo']['size']>5*1024*1024)$error='Photos must be 5 MB or smaller.';
  else{ $tmp=$_FILES['photo']['tmp_name']; $info=@getimagesize($tmp); $mime=$info['mime']??''; $ext=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];
   if(!$info||!isset($ext[$mime]))$error='Only JPG, PNG and WebP images are allowed.';
   else{ if(!is_dir($uploadDir))mkdir($uploadDir,0755,true); $filename='photo-'.bin2hex(random_bytes(10)).'.'.$ext[$mime]; $target=$uploadDir.'/'.$filename;
    if(move_uploaded_file($tmp,$target)){ $photos[]=['id'=>makeId(),'section'=>$section,'caption'=>$caption,'file'=>'/uploads/'.$filename,'uploaded'=>date('c')]; if(saveData($photosFile,$photos))$message='Photo uploaded.'; else{@unlink($target);$error='The photo record could not be saved.';} }
    else $error='The photo could not be uploaded. Check folder permissions.';
   }
  }
 }
 if($action==='delete_photo'){
  $id=cleanText($_POST['id']??'',40); $kept=[]; $deleted=''; foreach($photos as $photo){ if(isset($photo['id'])&&$photo['id']===$id){$deleted=(string)($photo['file']??'');continue;} $kept[]=$photo; }
  if(saveData($photosFile,$kept)){ if($deleted!==''&&strpos($deleted,'/uploads/')===0){$path=$root.$deleted;if(is_file($path))@unlink($path);} $message='Photo deleted.'; } else $error='The photo could not be deleted.';
 }
}
$photos=readData($photosFile);
?>
<!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Manage Photos | Sugar Code It</title><link rel="stylesheet" href="/styles.css">
  <style>
    .admin-breadcrumb-wrap {
      background: #0d1b2d;
      border-bottom: 1px solid rgba(255, 255, 255, 0.10);
    }

    .admin-breadcrumb {
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 18px 0;
      font-weight: 800;
      flex-wrap: wrap;
    }

    .admin-breadcrumb a,
    .admin-breadcrumb span {
      display: inline-block;
      padding: 9px 16px;
      border-radius: 999px;
      text-decoration: none;
    }

    .admin-breadcrumb a {
      background: #6f86ff;
      border: 1px solid #6f86ff;
      color: #ffffff;
    }

    .admin-breadcrumb a:hover {
      background: #8296ff;
    }

    .admin-breadcrumb .separator {
      padding: 0;
      color: #9fb0c7;
    }

    .admin-breadcrumb .current {
      background: transparent;
      border: 1px solid #6f86ff;
      color: #dce4ff;
    }
  </style>
</head><body class="admin-body">
<header class="nav"><div class="container navin"><a class="brand" href="/index.html"><img src="/assets/logo.png" alt="Sugar Code It logo"><span>Sugar Code It</span></a><div class="admin-nav-note">Photo management</div></div></header>
<div class="admin-breadcrumb-wrap">
  <div class="container admin-breadcrumb" aria-label="Admin breadcrumb">
    <a href="/admin/">Admin Dashboard</a>
    <span class="separator">/</span>
    <span class="current">Photos</span>
  </div>
</div>

<main><section class="page-hero admin-hero"><div class="container"><span class="eyebrow">Photos</span><h1>Manage photos.</h1><p>Upload photos and choose which public section they appear on.</p><div class="form-actions"><a class="button secondary" href="/admin/">Back to Admin</a></div></div></section>
<section><div class="container"><?php if($message!==''):?><div class="admin-message success"><?php echo h($message);?></div><?php endif;?><?php if($error!==''):?><div class="admin-message error"><?php echo h($error);?></div><?php endif;?><div class="admin-panel"><form class="editor-form admin-form" method="post" enctype="multipart/form-data"><input type="hidden" name="csrf" value="<?php echo h($_SESSION['csrf']);?>"><input type="hidden" name="action" value="upload_photo"><label>Show on page<select name="section"><option value="about">About</option><option value="meetings">Meetings</option><option value="projects">Projects</option><option value="impact">Impact</option></select></label><label>Caption<input type="text" name="caption" maxlength="120" placeholder="Arduino workshop, outreach event..."></label><label class="file-label">Photo<input type="file" name="photo" accept="image/jpeg,image/png,image/webp" required><span class="field-help">JPG, PNG or WebP, up to 5 MB.</span></label><button class="button" type="submit">Upload Photo</button></form></div></div></section>
<section class="light-section"><div class="container"><div class="section-head"><div><span class="eyebrow">Photos</span><h2>Uploaded photos</h2></div></div><?php if(count($photos)===0):?><div class="empty-state"><h3>No uploaded photos yet</h3><p>Use the upload form above when you are ready.</p></div><?php else:?><div class="admin-photo-grid"><?php foreach(array_reverse($photos) as $photo):?><figure class="admin-photo-card"><img src="<?php echo h($photo['file']??'');?>" alt="<?php echo h($photo['caption']??'Sugar Code It photo');?>"><figcaption><div class="card-meta"><?php echo h(ucfirst($photo['section']??'about'));?></div><p><?php echo h($photo['caption']??'');?></p><form method="post" onsubmit="return confirm('Delete this photo?');"><input type="hidden" name="csrf" value="<?php echo h($_SESSION['csrf']);?>"><input type="hidden" name="action" value="delete_photo"><input type="hidden" name="id" value="<?php echo h($photo['id']??'');?>"><button class="small-button danger-text" type="submit">Delete</button></form></figcaption></figure><?php endforeach;?></div><?php endif;?></div></section></main>
<footer class="footer"><div class="container footer-grid"><div><strong style="color:white">Sugar Code It</strong><br>Private photo management</div><div><a href="/admin/">Back to Admin</a></div><div>Photos appear on selected pages</div></div></footer></body></html>
