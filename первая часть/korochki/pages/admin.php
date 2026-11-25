<?php
require_once __DIR__.'/../helpers/db.php';
if(!isset($_SESSION['user_id']) || (int)$_SESSION['user_role']!==1){http_response_code(403);exit('Доступ запрещён. Вы не администратор.');}
$pageTitle='Панель администратора — Корочки.Есть';
$breadcrumbs=[['title'=>'Главная','url'=>'index.php'],['title'=>'Панель администратора','url'=>'#']];
$error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
 $status=filter_var($_POST['status'] ?? '', FILTER_VALIDATE_INT);$id=filter_var($_POST['request_id'] ?? '', FILTER_VALIDATE_INT);
 if(($_POST['action']??'')==='update_status' && in_array($status,[2,3],true)){
  $stmt=$pdo->prepare('UPDATE requests SET status_id=? WHERE id_request=?');$stmt->execute([$status,$id]);
  if ($stmt->rowCount() === 0) {
      http_response_code(404);
      $error = 'Заявка не найдена.';
  } else {
      $_SESSION['flash'] = 'Статус заявки сохранён.';
      $query = http_build_query(array_intersect_key($_GET, array_flip(['status', 'q', 'p'])));
      header('Location: admin.php' . ($query === '' ? '' : '?' . $query));
      exit;
  }
 }else $error='Выберите корректный статус.';
}
$allStatuses=$pdo->query('SELECT * FROM statuses')->fetchAll();
$filter=(int)($_GET['status']??0);$search=trim($_GET['q']??'');$where='1=1';$params=[];
if(in_array($filter,[1,2,3],true)){$where.=' AND r.status_id=?';$params[]=$filter;}
if($search!==''){$where.=' AND (u.fio LIKE ? OR c.name_course LIKE ?)';$params[]='%'.$search.'%';$params[]='%'.$search.'%';}
$from=' FROM requests r JOIN users u ON r.user_id=u.id_user JOIN courses c ON r.course_id=c.id_course JOIN payments p ON r.payment_id=p.id_payment JOIN statuses s ON r.status_id=s.id_status';
$stmt=$pdo->prepare('SELECT COUNT(*)'.$from.' WHERE '.$where);$stmt->execute($params);$total=(int)$stmt->fetchColumn();$pages=max(1,(int)ceil($total/3));$page=min($pages,max(1,(int)($_GET['p']??1)));$offset=($page-1)*3;
$stmt=$pdo->prepare('SELECT r.*,u.fio,p.method,c.name_course,s.name_status'.$from.' WHERE '.$where.' ORDER BY r.id_request DESC LIMIT 3 OFFSET '.$offset);$stmt->execute($params);$requests=$stmt->fetchAll();
ob_start();?>
<h1 class="page-heading">Панель администратора</h1>
<?php if(isset($_SESSION['flash'])):?><div class="alert alert-success status-popup" role="status"><?=e($_SESSION['flash'])?></div><?php unset($_SESSION['flash']);endif?>
<?php if($error):?><div class="alert alert-danger" role="alert"><?=e($error)?></div><?php endif?>
<form method="get" class="request-filters"><label for="filter-status">Статус<select name="status" id="filter-status"><option value="0">Все статусы</option><?php foreach($allStatuses as $st):?><option value="<?=$st['id_status']?>" <?=$filter===(int)$st['id_status']?'selected':''?>><?=e($st['name_status'])?></option><?php endforeach?></select></label><label for="filter-q">ФИО или курс<input name="q" id="filter-q" value="<?=e($search)?>" maxlength="100"></label><button class="btn btn-primary">Показать</button></form><p>Найдено заявок: <?=$total?></p>
<div class="requests-grid"><?php if(!$requests):?><p>Заявок пока нет.</p><?php endif?>
<?php foreach($requests as $req):?><article class="request-card"><div class="request-card-header">Заявка №<?=$req['id_request']?></div><div class="request-card-body"><p><strong>ФИО:</strong> <?=e($req['fio'])?></p><p><strong>Курс:</strong> <?=e($req['name_course'])?></p><p><strong>Оплата:</strong> <?=e($req['method'])?></p><p><strong>Дата начала:</strong> <?=date('d.m.Y',strtotime($req['data_nach']))?></p><form method="post"><?= csrf() ?><input type="hidden" name="action" value="update_status"><input type="hidden" name="request_id" value="<?=$req['id_request']?>"><label for="status-<?=$req['id_request']?>">Статус</label><select class="status-select" name="status" id="status-<?=$req['id_request']?>"><?php foreach($allStatuses as $st):?><option value="<?=$st['id_status']?>" <?=(int)$st['id_status'] === 1 ? 'disabled' : ''?> <?=(int)$st['id_status']===(int)$req['status_id']?'selected':''?>><?=e($st['name_status'])?></option><?php endforeach?></select><button class="btn btn-primary mt-3">Сохранить статус</button></form></div></article><?php endforeach?></div>
<nav class="pagination" aria-label="Страницы заявок"><?php for($i=max(1,$page-2);$i<=min($pages,$page+2);$i++):?><a class="page-btn <?=$i===$page?'active':''?>" href="?<?=e(http_build_query(['status'=>$filter,'q'=>$search,'p'=>$i]))?>" <?=$i===$page?'aria-current="page"':''?>><?=$i?></a><?php endfor?></nav>
<?php $content=ob_get_clean();require __DIR__.'/layout.php';
