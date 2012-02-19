<?php
/**
 * Copyright (c) 2012 Khang Minh <betterwp.net>
 * @license http://www.gnu.org/licenses/gpl.html GNU GENERAL PUBLIC LICENSE
 */

class BWP_GXS_MODULE_SITE extends BWP_GXS_MODULE {

	function __construct()
	{
		$this->set_current_time();
		$this->build_data();
	}

	function generate_data()
	{
		global $wpdb, $blog_id;

		// If this is simply not a multisite installation, or a multisite installation, but not on main site,
		// just show the lonely domain
		if (empty($wpdb->blogs) || (!empty($blog_id) && 1 < $blog_id))
		{
			$last_mod = $wpdb->get_var('SELECT post_modified FROM ' . $wpdb->posts . " WHERE post_status = 'publish' ORDER BY post_modified DESC");
			$data = array();
			$data['location'] = trailingslashit(home_url());
			$data['lastmod'] = $this->format_lastmod(strtotime($last_mod));
			$data['freq'] = apply_filters('bwp_gxs_freq_site', $this->cal_frequency(NULL, $last_mod), $blog_id);
			$data['freq'] = ('always' == $data['freq']) ? 'hourly' : $data['freq'];
			$data['priority'] = 1;
			$this->data[] = $data;
			return false;
		}
		else if (isset($blog_id) && 1 == $blog_id)
		{
			if (!empty($wpdb->dmtable))
				// If domain mapping is active
				$blog_sql = 'SELECT wpblogs.*, wpdm.domain as mapped_domain FROM ' . $wpdb->blogs . ' wpblogs
								LEFT JOIN ' . $wpdb->dmtable . ' wpdm
									ON wpblogs.blog_id = wpdm.blog_id AND wpdm.active = 1
							WHERE wpblogs.public = 1 AND wpblogs.spam = 0 AND wpblogs.deleted = 0';
			else
				// Otherwise
				$blog_sql = 'SELECT * FROM ' . $wpdb->blogs . ' WHERE public = 1 AND spam = 0 AND deleted = 0';

			// Get all blogs
			$blogs = $this->get_results($blog_sql);

			if (!isset($blogs) || 0 == sizeof($blogs))
				return false;

			$data = array();
			for ($i = 0; $i < sizeof($blogs); $i++)
			{
				$blog = $blogs[$i];
				$data = $this->init_data($data);
				// Get the correct domain
				$scheme = is_ssl() ? 'https://' : 'http://';
				$path = $blog->path;
				$data['location'] = (empty($blog->mapped_domain)) ? $scheme . $blog->domain . $path : $scheme . str_replace(array('http', 'https'), '', $blog->mapped_domain) . '/';
				$data['lastmod'] = $this->format_lastmod(strtotime($blog->last_updated));
				$data['freq'] = apply_filters('bwp_gxs_freq_site', $this->cal_frequency(NULL, $blog->last_updated), $blog->blog_id);
				$data['freq'] = ('always' == $data['freq']) ? 'hourly' : $data['freq'];
				// Always give primary blog a priority of 1
				$data['priority'] = (0 == $i) ? 1 : $this->cal_priority(NULL, $data['freq']);
				$this->data[] = $data;
			}

			unset($blogs);

			return true;
		}
		else
			return false;
	}
}
?>