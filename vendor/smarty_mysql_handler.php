<?php

function mysql_cache_handler($action, &$smarty_obj, &$cache_content, $tpl_file=null, $cache_id=null, $compile_id=null, $exp_time=null)
{
    // set db host, user and pass here
    $db_host = DB_SERVER;
    $db_user = DB_SERVER_USERNAME;
    $db_pass = DB_SERVER_PASSWORD;
    $db_name = DB_DATABASE;
    $use_gzip = false;

    // create unique cache id
    $CacheID = md5($tpl_file.$cache_id.$compile_id);

    if(! $link = mysql_pconnect($db_host, $db_user, $db_pass)) {
        $smarty_obj->_trigger_error_msg('cache_handler: could not connect to database');
        return false;
    }
    mysql_select_db($db_name);

    switch ($action) {
        case 'read':
            // read cache from database
            $results = mysql_query("select cache_contents from smarty_cache where cache_id='$CacheID'");
            if(!$results) {
                $smarty_obj->_trigger_error_msg('cache_handler: query failed.');
            }
            $row = mysql_fetch_array($results,MYSQL_ASSOC);

            if($use_gzip && function_exists('gzuncompress')) {
                $cache_content = gzuncompress($row['cache_contents']);
            } else {
                $cache_content = $row['cache_contents'];
            }
            $return = $results;
            break;
        case 'write':
            // save cache to database

            if($use_gzip && function_exists("gzcompress")) {
                // compress the contents for storage efficiency
                $contents = gzcompress($cache_content);
            } else {
                $contents = $cache_content;
            }
            $results = mysql_query("replace into smarty_cache values(
                            '$CacheID',
                            '".addslashes($contents)."')
                        ");
            if(!$results) {
                $smarty_obj->_trigger_error_msg('cache_handler: query failed.');
            }
            $return = $results;
            break;
        case 'clear':
            // clear cache info
            if(empty($cache_id) && empty($compile_id) && empty($tpl_file)) {
                // clear them all
                $results = mysql_query('delete from smarty_cache');
            } else {
                $results = mysql_query("delete from smarty_cache where cache_id='$CacheID'");
            }
            if(!$results) {
                $smarty_obj->_trigger_error_msg('cache_handler: query failed.');
            }
            $return = $results;
            break;
        default:
            // error, unknown action
            $smarty_obj->_trigger_error_msg("cache_handler: unknown action \"$action\"");
            $return = false;
            break;
    }
    mysql_close($link);
    return $return;

}

?>
