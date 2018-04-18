<?php
  header('Content-type: text/plain; charset=utf-8');
  ini_set('upload_tmp_dir', '/Users/xunjianxiang/tmp');

  $action = $_REQUEST['action'];
  $temporary = './temporary';
  $upload = './upload';

  function unlinkdeep ($target) {
    if (is_dir($target)) {
      $items = array_slice(scandir($target), 2);
      foreach ($items as $item) {
        if (is_dir($item)) {
          unlinkdeep($target.'/'.$item);
        } else {
          $status = unlink($target.'/'.$item);
        }
      }
      $status = rmdir($target);
    } else {
      $status = unlink($target);
    }
  }

  if ($action === 'options') {
    $hash = $_POST['hash'];
    $chunk = $_POST['chunk'];
    $size = (int)$_POST['size'];
    $exist = file_exists($temporary.'/'.$hash.'/'.$chunk);
    $filesize = filesize($temporary.'/'.$hash.'/'.$chunk);
    $refuse = $exist && $filesize === $size;
    $response['code'] = $refuse ? 1 : 0;
    $response['message'] = $refuse ? '分片已存在' : '分片不存在或分片不完整';
  } else if ($action === 'chunk') {
    $hash = $_POST['hash'];
    $chunk = $_POST['chunk'];
    $size = (int)$_POST['size'];
    $file = $_FILES['file'];
    $chunk || $chunk = 0;
    $name = $temporary.'/'.$hash.'/'.$chunk;
    file_exists($temporary) || mkdir ($temporary, 0755, true);
    file_exists($temporary.'/'.$hash) || mkdir ($temporary.'/'.$hash, 0755, true);
    $status = move_uploaded_file($file['tmp_name'], $name);
    $response['code'] = $status ? 0 : 1;
    $response['message'] = $status ? '分片 '.$chunk.' 上传成功' : '分片 '.$chunk.' 上传失败';
  } else if ($action === 'merge') {
    $hash = $_POST['hash'];
    $name = $_POST['name'];
    $ext = $_POST['ext'];
    $size = (int)$_POST['size'];
    $filename = $upload.'/'.$name;
    file_exists($filename) && unlink($filename);
    if (is_dir($temporary.'/'.$hash)) {
      $list = array_slice(scandir($temporary.'/'.$hash), 2);
      sort($list, 1);
      file_exists($upload) || mkdir ($upload, 0755, true);
      foreach ($list as $item) {
        $content = file_get_contents($temporary.'/'.$hash.'/'.$item);
        file_put_contents($filename, $content, FILE_APPEND);
      };
      $md5 = md5(file_get_contents($filename));
      if ($md5 === $hash) {
        $response['code'] = 0;
        $response['message'] = '文件上传成功';
        unlinkdeep($temporary.'/'.$hash);
      } else {
        $response['code'] = 1;
        $response['message'] = '文件上传失败';
        unlinkdeep($upload.'/'.$name);
      }
    } else {
      $response['code'] = 1;
    }
  }

  echo json_encode($response);
?>