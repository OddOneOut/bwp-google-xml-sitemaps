<?php
/**
 * Copyright (c) 2011 Khang Minh <betterwp.net>
 * @license http://www.gnu.org/licenses/gpl.html GNU GENERAL PUBLIC LICENSE
 */

class BWP_GXS_MODULE_PAGE_EXTERNAL extends BWP_GXS_MODULE {

	function __construct()
	{
		global $bwp_gxs;

		$this->set_current_time();
		$this->build_data();
	}

	function build_data()
	{
		global $wpdb, $bwp_gxs;

		// The structure of your external pages should be like the below sample item
		// array('location' => '', 'lastmod' => '', 'priority' => '')
		// Frequency will be calculated based on lastmod
		$sample_pages = array(
			array('location' => home_url('a-page-not-belong-to-wordpress.html'), 'lastmod' => '06/02/2011', 'priority' => '1.0'),
			array('location' => home_url('another-page-not-belong-to-wordpress.html'), 'lastmod' => '05/02/2011', 'priority' => '0.8')
		);
		$external_pages = (array) apply_filters('bwp_gxs_external_pages', $sample_pages);

		$data = array();
		for ($i = 0; $i < sizeof($external_pages); $i++)
		{
			$page = $external_pages[$i];
			$data = $this->init_data($data);
			$data['location'] = $page['location'];
			$data['lastmod'] = $this->format_lastmod(strtotime($page['lastmod']));
			$data['freq'] = $this->cal_frequency(NULL, $page['lastmod']);
			$data['priority'] = $page['priority'];
			$this->data[] = $data;
		}
	}
}
?>