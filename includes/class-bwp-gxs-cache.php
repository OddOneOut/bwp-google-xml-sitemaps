<?php
/**
 * Copyright (c) 2011 Khang Minh <betterwp.net>
 * @license http://www.gnu.org/licenses/gpl.html GNU GENERAL PUBLIC LICENSE
 */

class BWP_GXS_CACHE {

	var $module = '';
	var $cache_dir = '';
	var $cache_file = '';
	var $cache_time = 0;
	var $gzip = false;
	var $has_cache = false;
	var $options;
	var $now;
	var $cache_header = array();
	var $cache_ok = false;

	function __construct($config = array())
	{
		global $bwp_gxs;

		// Init necessary config to work with the cache
		$this->options = $bwp_gxs->get_options();
		$this->module = $config['module'];
		$this->module_name = $config['module_name'];
		$this->cache_dir = $this->options['input_cache_dir'];
		$this->gzip = ('yes' == $this->options['enable_gzip']) ? true : false;
		$this->cache_time = (int) $this->options['input_cache_age'] * (int) $this->options['select_time_type'];
		$this->now = time();
		
		if (empty($this->cache_dir) || !@is_writable($this->cache_dir))
			$bwp_gxs->elog(sprintf(__('Cache directory ("%s") does not exist or is not writable.', 'bwp-simple-gxs'), $this->cache_dir));
		else
			$this->setup_cache();
	}

	function get_header()
	{
		return $this->cache_header;
	}

	function get_cache_file()
	{
		return $this->cache_file;
	}

	function check_cache()
	{
		global $bwp_gxs;

		// If the cache file is not found
		if (!@file_exists($this->cache_file))
		{
			$bwp_gxs->nlog(sprintf(__('Cache file for module <em>%s</em> is not found and will be built right away.', 'bwp-simple-gxs'), $this->module));
			return false;
		}
		$this->has_cache = true;

		return true;
	}

	function setup_cache()
	{
		global $bwp_gxs;
		// Build cache file name, WPMS compatible
		$file_name = 'gxs_' . md5($this->module . '_' . get_option('home'));
		// $file_name .= (true == $this->gzip) ? '.xml.gz' : '.xml';
		// Use gz all the time to save space
		$file_name .= '.xml.gz';
		$this->cache_file = trailingslashit($this->cache_dir) . $file_name;
		$this->cache_ok = true;

		if ($this->check_cache())
		{
			// So cache is found, check if we can still use it
			$filemtime = @filemtime($this->cache_file);
			if ($filemtime + $this->cache_time <= $this->now && 'yes' == $this->options['enable_cache_auto_gen'])
			{
				@unlink($this->cache_file);
				$this->has_cache = false;
			}

			if (!$this->has_cache)
			{
				$header['lastmod'] = $bwp_gxs->format_header_time($this->now);
				$header['expires'] = $bwp_gxs->format_header_time($this->now + $this->cache_time);
				$header['etag'] = md5($header['expires'] . $this->cache_file);
			}
			else
			{
				$header['lastmod'] = $bwp_gxs->format_header_time($filemtime);
				$header['expires'] = $bwp_gxs->format_header_time($filemtime + $this->cache_time);
				$header['etag'] = md5($header['expires'] . $this->cache_file);
			}
			
			$this->cache_header = $header;
			
			if ('yes' == $this->options['enable_debug'])
				return;

			if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) || isset($_SERVER['HTTP_IF_NONE_MATCH']))
			{
				if ((isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && $_SERVER['HTTP_IF_MODIFIED_SINCE'] == $header['lastmod']) || 
					(isset($_SERVER['HTTP_IF_NONE_MATCH']) && str_replace('"', '', stripslashes($_SERVER['HTTP_IF_NONE_MATCH'])) == $header['etag']))
				{
					$bwp_gxs->slog(sprintf(__('Successfully served a cached version of <em>%s.xml</em>.', 'bwp-simple-gxs'), $this->module_name), true);
					$bwp_gxs->commit_logs();
					header('HTTP/1.1 304 Not Modified');
					exit();
				}
			}
		}
	}

	function write_cache()
	{
		global $bwp_gxs;

		$file = $this->cache_file;

		$handle = @gzopen($file, 'wb');
		@flock($handle, LOCK_EX);
		@gzwrite($handle, $bwp_gxs->output);
		@flock($handle, LOCK_UN);
		@gzclose($handle);

		@umask(0000);
		@chmod($file, 0666);

		return true;
	}
}

?>