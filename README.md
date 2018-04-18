# 断点续传

## url
index.php?action=xxx

## action
* optinos
  检测分片是否存在
* chunk
  上传分片到临时文件
* merge
  合并分片，删除临时文件，至此上传完成

## notice
* php nginx 的大文件上传配置
* php 对项目根目录的写权限