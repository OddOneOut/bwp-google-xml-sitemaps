<?php
/**
 * Copyright (c) 2011 Khang Minh <betterwp.net>
 * @license http://www.gnu.org/licenses/gpl.html GNU GENERAL PUBLIC LICENSE
 */

class BWP_GXS_MODULE_SITE extends BWP_GXS_MODULE
{
	public function __construct()
	{
		// @since 1.3.0 this method is empty
	}

	protected function generate_data()
	{
		global $wpdb, $blog_id;

		if (!BWP_Sitemaps::is_multisite()
			|| BWP_Sitemaps::is_subdomain_install()
			|| (!empty($blog_id) && $blog_id > 1)
		) {
			// if this is not a multisite installation,
			// OR a subdomain multisite installation,
			// OR not on main site, just show the lonely domain
			$last_post = $wpdb->get_row('
				SELECT
					post_date, post_date_gmt,
					post_modified, post_modified_gmt
				FROM ' . $wpdb->posts . "
				WHERE post_status = 'publish'
					AND post_password = ''
				ORDER BY post_modified DESC
				LIMIT 1"
			);

			$data = array();

			$data['location'] = trailingslashit(home_url());
			$data['lastmod']  = $this->get_lastmod($last_post);

			/**
			 * Filter the frequency of a website in the Site Address sitemap.
			 *
			 * @param string $frequency The change frequency to filter.
			 * @param int $blog_id The current blog id.
			 *
			 * @return string Should be one of the change frequencies listed
			 * here: http://www.sitemaps.org/protocol.html#changefreqdef
			 */
			$data['freq'] = apply_filters('bwp_gxs_freq_site',
				$this->cal_frequency(false, $data['lastmod']), $blog_id
			);
			$data['freq'] = 'always' == $data['freq'] ? 'hourly' : $data['freq'];

			$data['priority'] = 1;

			$this->data[] = $data;

			// no SQL cycling needed
			return false;
		}
		else if (isset($blog_id) && 1 == $blog_id)
		{
			if (!empty($wpdb->dmtable))
			{
				// if domain mapping is active, we only get blogs that don't
				// have their domains mapped as this sitemap can only contains
				// links on the same domain @see http://www.sitemaps.org/protocol.html#locdef
				$blog_sql = '
					SELECT
						b.*
					FROM ' . $wpdb->blogs . ' b
					LEFT JOIN ' . $wpdb->dmtable . ' dm
						ON b.blog_id = dm.blog_id
						AND dm.active = 1
					WHERE b.public = 1
						AND b.spam = 0
						AND b.deleted = 0' . "
						AND (b.blog_id = 1 OR b.path <> '/')" . '
						AND dm.id is NULL';
			}
			else
			{
				$blog_sql = '
					SELECT *
					FROM ' . $wpdb->blogs . '
					WHERE public = 1
						AND spam = 0
						AND deleted = 0' . "
						AND (blog_id = 1 OR path <> '/')";
			}

			$blogs = $this->get_results($blog_sql);

			if (!isset($blogs) || 0 == sizeof($blogs))
				return false;

			$data = array();

			for ($i = 0; $i < sizeof($blogs); $i++)
			{
				$blog = $blogs[$i];

				$data = $this->init_data($data);

				$scheme = is_ssl() ? 'https://' : 'http://';
				$path = $blog->path;

				$data['location'] = $scheme . $blog->domain . $path;
				$data['lastmod']  = $this->format_local_datetime($blog->last_updated);

				$data['freq'] = apply_filters('bwp_gxs_freq_site',
					$this->cal_frequency(false, $blog->last_updated), $blog->blog_id
				);
				$data['freq'] = 'always' == $data['freq'] ? 'hourly' : $data['freq'];

				// always give primary blog a priority of 1
				$data['priority'] = 0 == $i ? 1 : $this->cal_priority(false, $data['freq']);

				$this->data[] = $data;
			}

			unset($blogs);

			return true;
		}
		else
			return false;
	}
}
