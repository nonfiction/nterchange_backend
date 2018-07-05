<?php

if (!defined('ENVIRONMENT')) define('ENVIRONMENT', 'development');
if (!defined('BASE_DIR')) define('BASE_DIR', realpath(dirname(__FILE__) . '/..'));
if (!defined('CONF_DIR')) define('CONF_DIR', dirname(__FILE__));
if (!defined('ROOT_DIR')) define('ROOT_DIR', realpath(CONF_DIR . '/..'));
if (!defined('APP_DIR')) define('APP_DIR', BASE_DIR);
if (!defined('LIB_DIR')) define('LIB_DIR', realpath(BASE_DIR . '/lib'));
if (!defined('BIN_DIR')) define('BIN_DIR', realpath(BASE_DIR . '/bin'));
if (!defined('VENDOR_DIR')) define('VENDOR_DIR', realpath(BASE_DIR . '/vendor'));
if (!defined('TEST_DIR')) define('TEST_DIR', realpath(BASE_DIR . '/test'));
if (!defined('PEAR_DIR')) define('PEAR_DIR', (is_dir(LIB_DIR . '/pear/php')?LIB_DIR . '/pear/php':'/usr/local/lib/php'));
if (!defined('SMARTY_DIR')) define('SMARTY_DIR', VENDOR_DIR . '/Smarty/libs/');
if (!defined('NTERCHANGE_VERSION')) define('NTERCHANGE_VERSION', '4.1.1');
if (!defined('DEFAULT_PAGE_EXTENSION')) define('DEFAULT_PAGE_EXTENSION', 'html');
if (!defined('APP_NAME')) define('APP_NAME', 'nterchange');
if (!defined('SITE_WORKFLOW')) define('SITE_WORKFLOW', false);

ini_set('session.use_cookies', 1);
ini_set('session.cache_expire', 10);
ini_set('session.use_trans_sid', 0);
ini_set('arg_separator.output', '&amp;');

ini_set('include_path', ini_get('include_path') . ':' . CONF_DIR . ':' . BASE_DIR . ':' . TEST_DIR . ':' . LIB_DIR . ':' . VENDOR_DIR . ':' . PEAR_DIR . ':' . SMARTY_DIR);

/* Constants for use in test environment */
if (ENVIRONMENT == 'test'){
  // only defining these as necessary for testing
  define('CACHE_DIR', realpath(ROOT_DIR . '/var'));
  define('SITE_WORKFLOW', false);
  define('SECURE_SITE', false);

  // define our database connection
  define('DB_SERVER_DSN', 'mysql://root:@localhost');
  define('DB_DSN', DB_SERVER_DSN.'/'.DB_DATABASE);
  define('DB_DSN_SEARCH', DB_DSN . '_mnogo/?dbmode=multi');
}

/*
 * SET UP PHP ERROR HANDLING
 */
ini_set('display_errors', 1);
if (defined('DEBUG_ENV') && DEBUG_ENV == true) {
  error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT & ~E_NOTICE);
} else {
  error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT & ~E_NOTICE);
}
