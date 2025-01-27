<?php

/**
 * This file contains functions that deal with getting and setting cache values.
 *
 * @package   ElkArte Forum
 * @copyright ElkArte Forum contributors
 * @license   BSD http://opensource.org/licenses/BSD-3-Clause (see accompanying LICENSE.txt file)
 *
 * This file contains code covered by:
 * copyright: 2011 Simple Machines (http://www.simplemachines.org)
 *
 * @version 2.0 dev
 *
 */

use ElkArte\Cache\Cache;
use ElkArte\Cache\CacheMethod\AbstractCacheMethod;

/**
 * Try to retrieve a cache entry. On failure, call the appropriate function.
 * This callback is sent as $file to include, and $function to call, with
 * $params parameters.
 *
 * @param string $key cache entry key
 * @param string $file file to include
 * @param string $function function to call
 * @param array $params parameters sent to the function
 * @param int $level = 1
 *
 * @return mixed
 * @deprecated since 2.0
 *
 */
function cache_quick_get($key, $file, $function, $params, $level = 1)
{
	\ElkArte\Errors\Errors::instance()->log_deprecated('cache_quick_get()', '\\ElkArte\\Cache\\Cache::instance()->quick_get');

	return Cache::instance()->quick_get($key, $file, $function, $params, $level);
}

/**
 * Puts value in the cache under key for ttl seconds.
 *
 * - It may "miss" so shouldn't be depended on
 * - Uses the cache engine chosen in the ACP and saved in settings.php
 * - It supports Memcache, Memcached, APCu, Zend, Redis & Filebased engines
 *
 * @param string $key
 * @param string|int|array|null $value
 * @param int $ttl = 120
 * @deprecated since 2.0
 *
 */
function cache_put_data($key, $value, $ttl = 120)
{
	\ElkArte\Errors\Errors::instance()->log_deprecated('cache_put_data()', '\\ElkArte\\Cache\\Cache::instance()->put');
	Cache::instance()->put($key, $value, $ttl);
}

/**
 * Gets the value from the cache specified by key, so long as it is not older than ttl seconds.
 *
 * - It may often "miss", so shouldn't be depended on.
 * - It supports the same as \ElkArte\Cache\Cache::instance()->put().
 *
 * @param string $key
 * @param int $ttl = 120
 *
 * @return bool|null
 * @deprecated since 2.0
 *
 */
function cache_get_data($key, $ttl = 120)
{
	\ElkArte\Errors\Errors::instance()->log_deprecated('cache_get_data()', '\\ElkArte\\Cache\\Cache::instance()->get');

	return Cache::instance()->get($key, $ttl);
}

/**
 * Empty out the cache in use as best it can
 *
 * It may only remove the files of a certain type (if the $type parameter is given)
 * Type can be user, data or left blank
 *  - user clears out user data
 *  - data clears out system / opcode data
 *  - If no type is specified will perform a complete cache clearing
 * For cache engines that do not distinguish on types, a full cache flush will be done
 *
 * @param string $type = ''
 * @deprecated since 2.0
 *
 */
function clean_cache($type = '')
{
	\ElkArte\Errors\Errors::instance()->log_deprecated('clean_cache()', '\\ElkArte\\Cache\\Cache::instance()->clean');
	Cache::instance()->clean($type);
}

/**
 * Finds all the caching engines available and loads some details depending on
 * parameters.
 *
 * - Caching engines must follow the naming convention of CacheName.php and
 * have a class name of CacheName that extends AbstractCacheMethod
 *
 * @param bool $supported_only If true, for each engine supported by the server
 *             an array with 'title' and 'version' is returned.
 *             If false, for each engine available an array with 'title' (string)
 *             and 'supported' (bool) is returned.
 *
 * @return array
 */
function loadCacheEngines($supported_only = true)
{
	global $cache_servers, $cache_uid, $cache_password;

	$engines = [];

	$classes = new GlobIterator(SOURCEDIR . '/ElkArte/Cache/CacheMethod/*.php', FilesystemIterator::SKIP_DOTS);

	$current = '';
	$cache = Cache::instance();
	if ($cache->isEnabled())
	{
		$current = $cache->getAccelerator();
	}

	foreach ($classes as $file_path)
	{
		// Get the engine name from the file name
		$parts = explode('.', $file_path->getBasename());
		$engine_name = $parts[0];
		if (in_array($engine_name, ['AbstractCacheMethod', 'CacheMethodInterface.php']))
		{
		   	continue;
		}
		$class = '\\ElkArte\\Cache\\CacheMethod\\' . $engine_name;

		// Validate the class name exists
		if (class_exists($class))
		{
			$options = [
				'servers' => empty($cache_servers) ? [] : explode(',', $cache_servers),
				'cache_uid' => empty($cache_uid) ? '' : $cache_uid,
				'cache_password' => empty($cache_password) ? '' : $cache_password,
			];

			// Use the current Cache object if its been enabled
			if ($engine_name === $current)
			{
				$obj = $cache->getCacheEngine();
			}
			else
			{
				$obj = new $class($options);
			}

			if ($obj instanceof AbstractCacheMethod)
			{
				if ($supported_only && $obj->isAvailable())
				{
					$engines[strtolower($engine_name)] = $obj->details();
				}
				elseif ($supported_only === false)
				{
					$engines[strtolower($engine_name)] = $obj;
				}
			}
		}
	}

	return $engines;
}
