<?php

/**
 * NUpload_S3 - Implementation of NUpload using Amazon S3
 *
 * @see NUpload for method documentation
 */
class NUpload_S3 extends NUpload {

  // Set these to your AWS Credentials before using

  /**
   * The S3 bucket to use, eg: NUpload_S3::$bucket = 'example_bucket'
   */
  public static $bucket     = '';
  /**
   * Your AWS API key
   */
  public static $api_key    = '';
  /**
   * Your AWS Secret key
   */
  public static $secret_key = '';

  public static function init($handler) {
    self::$bucket = getenv('UPLOAD_S3_BUCKET');
    self::$api_key = getenv('UPLOAD_S3_KEY');
    self::$secret_key = getenv('UPLOAD_S3_SECRET');
  }

  /**
   * Implementation of NUpload::moveUpload
   */
  public static function moveUpload($src, $target) {
    $s3 = self::s3_connect();

    $target = implode('/', array(self::$prefix, trim($target, '/')));

    $file_opts = array(
      'acl' => AmazonS3::ACL_PUBLIC,
      'contentType' => NFilesystem::getMimeType($src), // fileUpload probably won't require this
      'fileUpload' => $src
    );

    $resp = $s3->create_object(self::$bucket, $target, $file_opts);

    if ($resp->status == 200) {
      $url = $s3->get_object_url(self::$bucket, $target);
      self::debug("File uploaded to s3: $url");
      return $url;
    } else {
      throw new NUploadException("Couldn't upload file to s3: $bucket - $filename", 1);
      return '';
    }
  }

  /**
   * Implementation of NUpload::deleteUpload
   */
  public static function deleteUpload($url) {
    throw new NUploadException("deleteUpload is not implemented for S3 yet", 1);
  }


  private static function s3_connect(){
    CFCredentials::set(array(
      'development' => array(
        'key' => self::$api_key,
        'secret' => self::$secret_key,
        'default_cache_config' => 'apc',
        'certificate_authority' => false
      ),
      '@default' => 'development'
    ));

    $s3 = new AmazonS3();
    return $s3;
  }

}
