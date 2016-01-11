<?php
/**
 * Copyright (c) 2011 Khang Minh <betterwp.net>
 * @license http://www.gnu.org/licenses/gpl.html GNU GENERAL PUBLIC LICENSE
 */

class BWP_GXS_CACHE
{
	var $main         = false;
	var $options      = array();

	var $module_name  = '';
	var $sitemap_name = '';

	var $cache_file    = '';
	var $cache_time    = 0;
	var $cache_headers = array();

	var $gzip         = false;
	var $now;

	public function __construct(BWP_Sitemaps $main)
	{
		// init necessary config to work with the cache
		$this->main    = $main;
		$this->options = $main->options;

		$this->gzip       = 'yes' == $this->options['enable_gzip'] ? true : false;
		$this->cache_time = (int) $this->options['input_cache_age'] * (int) $this->options['select_time_type'];

		$this->now = time();
	}

	private function _check_http_cache($lastmod, $etag)
	{
		if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) || isset($_SERVER['HTTP_IF_NONE_MATCH']))
		{
			// a conditional GET
			if ((isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])
					&& $_SERVER['HTTP_IF_MODIFIED_SINCE'] == $lastmod)
				|| (isset($_SERVER['HTTP_IF_NONE_MATCH'])
					&& str_replace('"', '', stripslashes($_SERVER['HTTP_IF_NONE_MATCH'])) == $etag)
			) {
				// if the cached file in browser matches 'modified since'
				// or 'etag' header of server's cached file, we only need to
				// send a 304 Not Modified header back
				return true;
			}
		}

		return false;
	}

	private function _check_cache()
	{
		// cache is found and readable, check if we can still use it based
		// on its last modified timestamp
		$last_modified = @filemtime($this->cache_file);

		if ($last_modified + $this->cache_time <= $this->now
			&& 'yes' == $this->options['enable_cache_auto_gen']
		) {
			// cached sitemap file has expired, and cache auto-regenerate
			// is enabled, remove the cached sitemap file and return to the main
			// handle to re-generate a new sitemap file.
			@unlink($this->cache_file);
			return false;
		}

		$lastmod = bwp_gxs_format_header_time($last_modified);
		$expires = bwp_gxs_format_header_time($last_modified + $this->cache_time);
		$etag    = md5($expires . $this->cache_file);

		// build cached sitemap's headers for later use
		$this->cache_headers = array(
			'lastmod' => $lastmod,
			'expires' => $expires,
			'etag'    => $etag
		);

		if ($this->_check_http_cache($lastmod, $etag))
			return '304';

		return '200';
	}

	public function write_cache($contents)
	{
		$cache_file = $this->cache_file;

		$handle = @gzopen($cache_file, 'wb');

		if ($handle)
		{
			@flock($handle, LOCK_EX);
			@gzwrite($handle, $contents);
			@flock($handle, LOCK_UN);
			@gzclose($handle);

			/* @umask(0000); */
			@chmod($cache_file, 0644);
		}
		else
		{
			return false;
		}

		// return the modification timestamp to construct etag
		return @filemtime($cache_file);
	}

	/**
	 * Gets current cache status for the requested sitemap
	 *
	 * @since BWP GXS 1.3.0
	 * @access public
	 * @return bool|string false if cache is invalid
	 *                     '304' if http cache can be used
	 *                     '200' if file cache must be used
	 */
	public function get_cache_status($module_name, $sitemap_name)
	{
		global $bwp_gxs;

		$this->module_name  = $module_name;
		$this->sitemap_name = $sitemap_name;

		$this->cache_file = bwp_gxs_get_filename($sitemap_name);

		if (!@is_readable($this->cache_file))
			return false;

		return $this->_check_cache();
	}

	public function get_headers()
	{
		return $this->cache_headers;
	}

	public function get_cache_file()
	{
		return $this->cache_file;
	}
}
