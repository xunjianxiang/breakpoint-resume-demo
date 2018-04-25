<?php
  header('Content-type: text/plain; charset=utf-8');

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

  if ($action === 'begin') {
    $hash = $_POST['hash'];
    $name = $_POST['name'];
    $size = $_POST['size'];
    // 首先检测 $hash 是否在数据库中存在
    // 是则复制记录并生成新的提取码，提示用户秒传
    // 否则新增记录并生成新的提取码，提示用户上传
    $response['code'] = 0;
    $response['message'] = '提取码生成';
    $response['data']['extract_code'] = substr($hash, 3, 14);
    $response['data']['exist'] = false;
  }

  else if ($action === 'check') {
    $extractcode = $_POST['extractcode'];
    $chunk = $_POST['chunk'];
    $exist = file_exists($temporary.'/'.$extractcode.'/'.$chunk);
    $response['code'] = $exist ? 1 : 0;
    $response['message'] = $exist ? '分片 '.$chunk.' 已存在' : '分片 '.$chunk.' 不存在';
  }

  else if ($action === 'chunk') {
    $extractcode = $_POST['extractcode'];
    $chunk = $_POST['chunk'];
    $hash = $_POST['hash'];
    $file = $_FILES['file'];
    $chunk || $chunk = 0;
    $filename = $temporary.'/'.$extractcode.'/'.$chunk;
    file_exists($temporary) || mkdir ($temporary, 0755, true);
    file_exists($temporary.'/'.$extractcode) || mkdir ($temporary.'/'.$extractcode, 0755, true);
    $operation = move_uploaded_file($file['tmp_name'], $filename);
    if ($operation) {
      $md5 = md5(file_get_contents($filename));
      $status = $md5 === $hash;
      $status || unlink($filename);
    } else {
      $status = false;
    }
    $response['code'] = $status ? 0 : 1;
    $response['message'] = $status ? '分片 '.$chunk.' 上传成功' : '分片 '.$chunk.' 上传失败';
  }

  else if ($action === 'end') {
    $exist = $_POST['exist'];
    if ($exist === 'true') {
      $response['code'] = 0;
      $response['message'] = '文件上传成功';
    } else {
      $extractcode = $_POST['extractcode'];
      $hash = $_POST['hash'];
      $ext = $_POST['ext'];
      $filename = $upload.'/'.$extractcode.'.'.$ext;
      file_exists($filename) && unlink($filename);
      if (is_dir($temporary.'/'.$extractcode)) {
        $list = array_slice(scandir($temporary.'/'.$extractcode), 2);
        sort($list, 1);
        file_exists($upload) || mkdir ($upload, 0755, true);
        foreach ($list as $item) {
          $content = file_get_contents($temporary.'/'.$extractcode.'/'.$item);
          file_put_contents($filename, $content, FILE_APPEND);
        };
        $md5 = md5(file_get_contents($filename));
        if ($md5 === $hash) {
          $response['code'] = 0;
          $response['message'] = '文件上传成功';
          unlinkdeep($temporary.'/'.$extractcode);
        } else {
          $response['code'] = 1;
          $response['message'] = '文件上传失败';
          unlinkdeep($upload.'/'.$filename);
        }
      } else {
        $response['code'] = 1;
      }
    }
  }

  else if ($action === 'search') {
    $extractcode = $_POST['extractcode'];
    $response['code'] = 0;
    $response['message'] = '文件下载成功';
    $response['data'] = array(
      'apk_filename' => '文件名称',
      'encrypted_apk_size' => '文件大小',
      'encrypted_apk_hash' => '文件 hash',
      'url' => '文件下载地址'
    );
  }

  echo json_encode($response);
?>