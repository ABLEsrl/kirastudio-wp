<?php

declare(strict_types=1);

namespace KiraStudio;

final class CacheCompat
{
	public static function purgeAll(): void
	{
		// W3 Total Cache
		if (function_exists('w3tc_flush_all')) {
			w3tc_flush_all();
		}

		// WP Super Cache
		if (function_exists('wp_cache_clear_cache')) {
			wp_cache_clear_cache();
		}

		// WP Fastest Cache
		if (isset($GLOBALS['wp_fastest_cache']) && method_exists($GLOBALS['wp_fastest_cache'], 'deleteCache')) {
			$GLOBALS['wp_fastest_cache']->deleteCache(true);
		}

		// LiteSpeed Cache
		if (class_exists('LiteSpeed_Cache_API') || defined('LSCWP_V')) {
			do_action('litespeed_purge_all');
		}

		// WP Rocket
		if (function_exists('rocket_clean_domain')) {
			rocket_clean_domain();
		}

		// Breeze (Cloudways)
		if (class_exists('Breeze_PurgeCache') || defined('BREEZE_VERSION')) {
			do_action('breeze_clear_all_cache');
		}

		// SG Optimizer (SiteGround)
		if (function_exists('sg_cachepress_purge_cache')) {
			sg_cachepress_purge_cache();
		}

		// Autoptimize
		if (class_exists('autoptimizeCache')) {
			do_action('autoptimize_action_cachepurged');
		}

		// Comet Cache / ZenCache
		if (class_exists('comet_cache')) {
			\comet_cache::clear();
		} elseif (class_exists('zencache')) {
			\zencache::clear();
		}

		// WP Engine (via mu-plugin global)
		if (class_exists('WpeCommon')) {
			if (method_exists('WpeCommon', 'purge_memcached')) {
				\WpeCommon::purge_memcached();
			}
			if (method_exists('WpeCommon', 'clear_maxcdn_cache')) {
				\WpeCommon::clear_maxcdn_cache();
			}
			if (method_exists('WpeCommon', 'purge_varnish_cache')) {
				\WpeCommon::purge_varnish_cache();
			}
		}

		// Kinsta (via mu-plugin global)
		if (isset($GLOBALS['kinsta_cache']) && method_exists($GLOBALS['kinsta_cache'], 'kinsta_cache_purge_all_cache')) {
			$GLOBALS['kinsta_cache']->kinsta_cache_purge_all_cache();
		}

		// WordPress object cache (always last)
		wp_cache_flush();
	}
}
