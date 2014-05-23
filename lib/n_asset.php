<?php
/**
 * require PEAR:Cache_Lite
 */
require_once 'Cache/Lite.php';

/**
 * NAsset is a web asset compiler/cacher
 */
class NAsset {

  var $cache_group = 'asset';
  var $filename = '';
  var $key = '';
  var $cache = '';

  var $minify = false;
  var $nocache = false;

  function __construct($filename = '') {
    $this->key = urlencode($filename);

    $this->filename = str_replace(array('.css','.js'), '', ltrim($filename, '/'));

    $this->filename_trim('&');
    $this->filename_options('nocache', 'nocache');
    $this->filename_trim('?');
    $this->filename_options('minify', '.min');

    $this->cache = new Cache_Lite(array('cacheDir'=>CACHE_DIR . '/ntercache/', 'lifeTime'=>60*24*24));
  }

  /**
   * Prints a cached version of a compiled asset or compiles the source
   * and returns that.
   */
  function render(){

    // Determine what kind of asset is being requested
    $handler = $this->handler($this->filename);

    // Set the proper content-type in the headers
    header('Content-Type: ' . $this->content_type($handler));

    $content = null;

    // Look for the compiled asset in the cache
    if (!$this->nocache) $content = $this->get($this->key);

    if (!$content){
      // Compile the asset
      $content = $this->compile($this->filename, $handler);
      if (!$this->nocache) $this->set($this->key, $content);
    }

    // Send it out!
    echo $content;
  }

  /**
   * Examines the file extension and determines which handler to call
   */
  function handler($filename='') {
    switch (end(explode('.',$filename))) {
      case "less": return "less";
      case "coffee": return "coffee";
      default: return "no_handler";
    }
  }

  /**
   * Examines the handler and determines which content-type to use
   */
  function content_type($handler='') {
    switch ($handler) {
      case "less": return "text/css";
      case "coffee": return "text/javascript";
      default: return "text/plain";
    }
  }

  /**
   * Returns a compiled less or coffee-script file
   */
  function compile($filename='', $handler=''){

    // Check if file can be found
	if (! is_readable(DOCUMENT_ROOT.'/'.$filename)) $handler = "no_file";

    // Return the compiled web asset
    switch ($handler) {

      case "less":
        if ($this->minify)
          $cmd = 'lessc --yui-compress ' . escapeshellarg($filename);
        else
          $cmd = 'lessc ' . escapeshellarg($filename);
        return $this->process($cmd);

      case "coffee":
        $tmp_file = '/tmp/' . basename($filename) . '.js';
        if ($this->minify)
          $cmd = 'importer ' . escapeshellarg($filename) . " $tmp_file; uglifyjs $tmp_file";
        else
          $cmd = 'importer ' . escapeshellarg($filename) . " $tmp_file; cat $tmp_file";
        return $this->process($cmd);

      case "no_file":
        return "/* error: cannot find file {$filename} */";

      case "no_handler":
      default:
        return "/* error: no handler found for {$filename} */";
    }
  }

  /**
   * Similar to exec(), except it appends stderr to stdout on error
   * also sets cwd to the public directory
   */
  function process($command){
    $descriptorspec = array(
       0 => array("pipe", "r"),  // stdin
       1 => array("pipe", "w"),  // stdout
       2 => array("pipe", "w"),  // stderr
    );
	$process = proc_open($command, $descriptorspec, $pipes, DOCUMENT_ROOT, null);
    $stdout = stream_get_contents($pipes[1]);
    fclose($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[2]);

    if ($stderr) $stdout .= "/* error: {$stderr} */";
    return $stdout;
  }

  /**
   * Get a cached record, or not.
   *
   * @param  string  $key  The key for the record, pick your schema
   * @param  int     $ttl The time-to-live for cached versions, in minutes (default=5)
   * @return mixed   The data, or false if the record is expired or doesn't exist
   */
  function get($key){
    if ($data = $this->cache->get($key, $this->cache_group)){
      return unserialize($data);
    }
    return false;
  }

  /**
   * Set a key/value pair
   *
   * @param string $key
   * @param string $value
   */
  function set($key, $value){
    return $this->cache->save(serialize($value), $key, $this->cache_group);
  }

  /**
   * set options based on value at end of filename
   *
   * @param string $option
   * @param string $flag
   */
  function filename_options($option, $flag){
    $filename = $this->filename;

    $flag_length = strlen($flag);
    $filename_length = strlen($filename);

    $filename_end = substr($filename, $filename_length - $flag_length);
    if ($filename_end == $flag) {
      $this->$option = true;
      $filename = substr($filename, 0, ($flag_length * -1));
    }

    $this->filename = $filename;
  }

  /**
   * trim the filename after a string
   *
   * @param string $string
   */
  function filename_trim($string){
    $this->filename = array_shift(explode($string, $this->filename));
  }

}
?>
