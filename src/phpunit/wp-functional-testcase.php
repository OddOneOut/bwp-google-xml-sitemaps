<?php

/**
 * Copyright (c) 2015 Khang Minh <contact@betterwp.net>
 * @license http://www.gnu.org/licenses/gpl.html GNU GENERAL PUBLIC LICENSE VERSION 3.0 OR LATER
 */

/**
 * @author Khang Minh <contact@betterwp.net>
 */
abstract class BWP_Sitemaps_PHPUnit_WP_Functional_TestCase extends BWP_Framework_PHPUnit_WP_Functional_TestCase
{
	protected $plugin;

	public function setUp()
	{
		parent::setUp();

		global $bwp_gxs;
		$this->plugin = $bwp_gxs;
	}

	public function get_plugin_under_test()
	{
		$root_dir = dirname(dirname(dirname(__FILE__)));

		return array(
			$root_dir . '/bwp-gxs.php' => 'bwp-google-xml-sitemaps/bwp-gxs.php'
		);
	}

	protected function create_post($post_type = 'post')
	{
		return $this->factory->post->create_and_get(array(
			'post_type' => $post_type
		));
	}

	protected function create_posts($post_type = 'post', $count = 5, $post_date_gmt = null)
	{
		return $this->factory->post->create_many($count, array_merge(array(
			'post_type' => $post_type
		), $post_date_gmt ? array('post_date_gmt' => $post_date_gmt) : array()));
	}

	protected function create_terms($taxonomy = 'category', $count = 5)
	{
		return $this->factory->term->create_many($count, array(
			'taxonomy' => $taxonomy,
			'slug'     => $taxonomy
		));
	}
}
