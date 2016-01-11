<?php
/**
 * Copyright (c) 2011 Khang Minh <betterwp.net>
 * @license http://www.gnu.org/licenses/gpl.html GNU GENERAL PUBLIC LICENSE
 * @package BWP Google XML Sitemaps
 */

class BWP_GXS_MODULE_AUTHOR extends BWP_GXS_MODULE
{
	public function __construct()
	{
		// @since 1.3.0 this method is empty
	}

	protected function generate_data()
	{
		global $wpdb, $bwp_gxs;

		// an array of what roles to include in sitemap
		$roles = array(
			'administrator',
			'editor',
			'author',
			'contributor'
		);

		$author_sql = '
			SELECT
				u.ID,
				u.user_nicename,
				MAX(p.post_modified) as lastmod,
				um.meta_value as role
			FROM ' . $wpdb->users . ' u
			INNER JOIN ' . $wpdb->usermeta . ' um
				ON um.user_id = u.ID
			INNER JOIN ' . $wpdb->posts . ' p
				ON p.post_author = u.ID' . "
			WHERE p.post_status = 'publish'
				AND um.meta_key = '" . $wpdb->prefix . "capabilities'" . '
			GROUP BY
				u.ID,
				u.user_nicename,
				um.meta_value
			ORDER BY lastmod DESC';

		$authors = $this->get_results($author_sql);

		if (!isset($authors) || 0 == sizeof($authors))
			return false;

		$data = array();

		for ($i = 0; $i < sizeof($authors); $i++)
		{
			$data   = $this->init_data($data);

			$author = $authors[$i];
			$role   = maybe_unserialize($author->role);
			$role   = array_keys($role);

			$data['location'] = !in_array($role[0], $roles)
				? '' : get_author_posts_url($author->ID, $author->user_nicename);

			$data['lastmod']  = $this->get_lastmod($author);
			$data['freq']     = $this->cal_frequency(false, $data['lastmod']);
			$data['priority'] = $this->cal_priority(false, $data['freq']);

			$this->data[] = $data;
		}

		unset($authors);

		return true;
	}
}
