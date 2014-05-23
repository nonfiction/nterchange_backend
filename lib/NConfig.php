<?php

/**
 * NConfig - nterchange configuration
 *
 * Config that varies between deploys should be loaded as environment
 * variables, from the php.ini or by using NConfig::jsonConfig($file)
 *
 * Config that is the same betweed deploys should be set on NConfig as
 * static variables or methods. ( eg: NConfig::$protectedPages )
 */
class NConfig {
  /**
   * $protectedPages - define which pages cannot be deleted
   */
  public static $protectedPages = array(1, 4);

  /**
   * load environment variables from a json file
   *
   * @param  string   $file - the full path to the config file
   * @return boolean  success if the file exists and was loaded
   */
  public static function jsonConfig($file) {
    if (!file_exists($file)) return false;

    $settings = json_decode(file_get_contents($file));

    foreach ($settings as $key => $value) {
      putenv("$key=$value");
    }
    return true;
  }
}
