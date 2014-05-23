<?php

/**
 * NUpload_Local - Implementation of NUpload using the filesystem
 *
 * @see NUpload for method documentation
 */
class NUpload_Local extends NUpload {

  public static function moveUpload($src, $target) {
    $rel_path = implode('/', array(trim(self::$prefix, '/'), trim($target, '/')));
    $abs_path = '/'.implode('/', array(trim(DOCUMENT_ROOT, '/'), trim($rel_path, '/')));
    $dir = pathinfo($abs_path, PATHINFO_DIRNAME);

    NFilesystem::buildDirs($dir);

    if (move_uploaded_file($src, $abs_path)) {
      return '/'.$rel_path;
    } else {
      throw new NUploadException("Could not move file: $src to $target", 1);
    }
  }

  public static function deleteUpload($url) {
    throw new NUploadException("Not implemented", 1);
  }
}
