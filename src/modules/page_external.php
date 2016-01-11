<?php
/**
 * Copyright (c) 2011 Khang Minh <betterwp.net>
 * @license http://www.gnu.org/licenses/gpl.html GNU GENERAL PUBLIC LICENSE
 * @package BWP Google XML Sitemaps
 */

class BWP_GXS_MODULE_PAGE_EXTERNAL extends BWP_GXS_MODULE
{
	public function __construct()
	{
		// @since 1.3.0 this method is empty
	}

	protected function build_data()
	{
		global $wpdb, $bwp_gxs;

		// The structure of your external pages should be like the below sample item
		// array('location' => '', 'lastmod' => '', 'priority' => '')
		// Frequency will be calculated based on lastmod
		$sample_pages = array(
			array(
				'location' => home_url('a-page-not-belong-to-wordpress.html'),
				'lastmod'  => '06/02/2011',
				'priority' => '1.0',
				'sample'   => true // ignore this
			),
			array(
				'location' => home_url('another-page-not-belong-to-wordpress.html'),
				'lastmod'  => '05/02/2011',
				'priority' => '0.8',
				'sample'   => true // ignore this
			)
		);

		/**
		 * Filter the pages that are added to the external-page sitemap.
		 *
		 * @example hooks/filter_bwp_gxs_external_pages.php 2
		 *
		 * @param array $pages External pages to add.
		 *
		 * @return array List of external pages with following fields:
		 *
		 * * `location`
		 *   - This field is **required**.
		 *   - Must be an absolute url.
		 *   - Have the same scheme and host as your Site Address.
		 * * `lastmod` Should follow the [PHP datetime
		 *   formats](http://php.net/manual/en/datetime.formats.php). It can be a
		 *   date, or a date with time.
		 * * `frequency` See
		 *   http://www.sitemaps.org/protocol.html#changefreqdef. You can also
		 *   set this to `auto`, in that case change frequency will be calculated
		 *   using the last modified date (or date time) you provide.
		 * * `priority` See http://www.sitemaps.org/protocol.html#prioritydef
		 *
		 */
		$external_pages = (array) apply_filters('bwp_gxs_external_pages', $sample_pages);

		$data = array();

		for ($i = 0; $i < sizeof($external_pages); $i++)
		{
			$page = $external_pages[$i];

			$data['location'] = $page['location'];

			$data['lastmod']  = isset($page['lastmod'])
				? $this->format_local_datetime($page['lastmod'])
				: null;

			if (isset($page['freq']))
			{
				$data['freq'] = $page['freq'] == 'auto'
					? $this->cal_frequency(false, $page['lastmod'])
					: $page['freq'];
			}
			else
			{
				$data['freq'] = null;
			}

			$data['priority'] = isset($page['priority']) ? $page['priority'] : null;

			$this->data[] = $data;
		}
	}
}
