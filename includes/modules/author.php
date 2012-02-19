<?php
/**
 * Copyright (c) 2011 Khang Minh <betterwp.net>
 * @license http://www.gnu.org/licenses/gpl.html GNU GENERAL PUBLIC LICENSE
 */

class BWP_GXS_MODULE_AUTHOR extends BWP_GXS_MODULE {

	function __construct()
	{
		$this->set_current_time();
		$this->build_data();
	}

	function generate_data()
	{
		global $wpdb, $bwp_gxs;

		// An array of what roles to include in sitemap
		$roles = array('administrator', 'editor', 'author', 'contributor');
		// The SQL query
		$author_sql = 'SELECT wp_u.ID, wp_u.user_nicename, MAX(wp_p.post_modified) as lastmod, wp_um.meta_value as role 
						FROM ' . $wpdb->users . ' wp_u
							INNER JOIN ' . $wpdb->usermeta . ' wp_um
								ON wp_um.user_id = wp_u.ID
							INNER JOIN ' . $wpdb->posts . ' wp_p
								ON wp_p.post_author = wp_u.ID' . "
						WHERE wp_p.post_status = 'publish' AND wp_um.meta_key = '" . $wpdb->prefix . "capabilities'" . '
						GROUP BY wp_u.ID, wp_u.user_nicename, wp_um.meta_value
						ORDER BY lastmod DESC';
		// Get all authors
		$authors = $this->get_results($author_sql);

		if (!isset($authors) || 0 == sizeof($authors))
			return false;

		$data = array();
		for ($i = 0; $i < sizeof($authors); $i++)
		{
			// If user is not considered an author, pass
			$author = $authors[$i];
			$data = $this->init_data($data);
			$role = maybe_unserialize($author->role);
			$role = array_keys($role);
			$data['location'] = (!in_array($role[0], $roles)) ? '' : get_author_posts_url($author->ID, $author->user_nicename);
			$data['lastmod'] = $this->format_lastmod(strtotime($author->lastmod));
			$data['freq'] = $this->cal_frequency(NULL, $author->lastmod);
			$data['priority'] = $this->cal_priority(NULL, $data['freq']);
			$this->data[] = $data;
		}

		unset($authors);

		return true;
	}
}
?>