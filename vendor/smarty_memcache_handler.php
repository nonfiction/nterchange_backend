<?php
/**
 * Project: Smarty memcached cache handler function
 * Author: Mads Sülau Jørgensen <php at mads dot sulau dot dk>
 * Updated: 27/3-2007 - rewritten to be more sane.
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */
function memcache_cache_handler($action, &$smarty_obj, &$cache_content, $tpl_file=null, $cache_id=null, $compile_id=null, $exp_time=null) {

	$m = new Memcache;
	$m->connect(MEMCACHED_SERVER, MEMCACHED_SERVER_PORT) or mail(ERROR_EMAIL, SITE_NAME . ' could not connect to memcached', 'Oh no.');

	// unique cache id
	if ($tpl_file != null && $cache_id != null && $compile_id != null) {
		$cache_id = md5($tpl_file.$cache_id.$compile_id);
	}

	$cache_id = PUBLIC_SITE.$cache_id;

	switch ($action) {
	case 'read':
		// grab the key from memcached
		$cache_content = $m->get($cache_id);
		$return = true;
		break;

	case 'write':
		if(!$m->set($cache_id, $cache_contents, 0, (int)$exp_time)) {
			$smarty_obj->trigger_error("cache_handler: set failed.");
		}

		$return = true;
		break;

	case 'clear':
		if($cache_id == null) {
			$m->flush();
		} else {
			$result = $m->delete($cache_id);
		}
		if(!$result) {
			$smarty_obj->trigger_error("cache_handler: query failed.");
		}
		$return = true;
		break;

	default:
		$smarty_obj->trigger_error("cache_handler: unknown action \"$action\"");
		$return = false;
		break;
	}

	return $return;
}
?>
