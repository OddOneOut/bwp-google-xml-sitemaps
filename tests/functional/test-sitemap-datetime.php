<?php

class BWP_Sitemaps_Sitemap_Datetime_Functional_Test extends BWP_Sitemaps_PHPUnit_WP_Functional_TestCase
{
	protected static function set_plugin_default_options()
	{
		$default_options = array(
			'enable_sitemap_external' => 'yes',
			'enable_cache'            => '',
		);

		self::update_option(BWP_GXS_GENERATOR, $default_options);
	}

	/**
	 * @dataProvider get_timezone_settings
	 */
	public function test_sitemap_index_should_format_datetime_correctly($current_timezone_string, $is_gmt_enabled)
	{
		self::set_up_timezone_settings($current_timezone_string, $is_gmt_enabled);

		$datetime = new DateTime(null, new DateTimeZone('UTC'));

		self::update_option(BWP_GXS_LOG, array(
			'sitemaps' => array(
				array(
					'slug'     => 'post',
					'datetime' => $datetime->format('Y-m-d H:i:s')
				)
		)));

		$datetime = $is_gmt_enabled
			? $datetime
			: $datetime->setTimezone(new DateTimeZone($current_timezone_string));

		$datetime_formatted = str_replace('+00:00', 'Z', $datetime->format('c'));

		$crawler = self::get_crawler_from_url($this->plugin->get_sitemap_index_url());

		$this->assertEquals(
			$datetime_formatted,
			$crawler
				->filter('default|sitemapindex default|sitemap default|lastmod')
				->text()
		);
	}

	/**
	 * @dataProvider get_timezone_settings
	 */
	public function test_regular_sitemaps_should_format_datetime_correctly($current_timezone_string, $is_gmt_enabled)
	{
		self::set_up_timezone_settings($current_timezone_string, $is_gmt_enabled);

		$datetime = new DateTime(null, new DateTimeZone('UTC'));

		$post = $this->factory->post->create_object(array(
			'post_title'        => 'post',
			'post_status'       => 'publish',
			'post_modified_gmt' => $datetime->format('Y-m-d H:i:s'),
			'post_type'         => 'post'
		));

		self::commit_transaction();

		$datetime = $is_gmt_enabled
			? $datetime
			: $datetime->setTimezone(new DateTimeZone($current_timezone_string));

		$datetime_formatted = str_replace('+00:00', 'Z', $datetime->format('c'));

		$crawler = self::get_crawler_from_url($this->plugin->get_sitemap_url('post'));

		$this->assertEquals(
			$datetime_formatted,
			$crawler
				->filter('default|urlset default|url default|lastmod')
				->text()
		);
	}

	public function get_timezone_settings()
	{
		return array(
			array(
				'Asia/Bangkok', false
			),
			array(
				'Asia/Bangkok', true
			)
		);
	}

	/**
	 * @dataProvider get_external_pages_with_timezone_settings
	 */
	public function test_external_pages_should_have_correct_lastmod($current_timezone_string, $is_gmt_enabled, array $page)
	{
		self::set_up_timezone_settings($current_timezone_string, $is_gmt_enabled);

		$page['url'] = home_url($page['url']);

		self::update_option(BWP_GXS_EXTERNAL_PAGES, array(
			$page['url'] => $page
		));

		$datetime = new DateTime($page['last_modified'], new DateTimeZone('UTC'));

		if (! $is_gmt_enabled) {
			$datetime->setTimezone(new DateTimeZone($current_timezone_string));
		}

		$datetime_formatted = str_replace('+00:00', 'Z', $datetime->format('c'));

		$crawler = self::get_crawler_from_url($this->plugin->get_sitemap_url('page_external'));

		$this->assertEquals(
			$datetime_formatted,
			$crawler
				->filter('default|urlset default|url default|lastmod')
				->text()
		);
	}

	public function get_external_pages_with_timezone_settings()
	{
		$page_1 = array(
			'url'           => 'a-page',
			'frequency'     => 'daily',
			'priority'      => 0.1,
			'last_modified' => '2015-12-24 12:00:00'
		);

		$page_2 = array(
			'url'           => 'another-page',
			'frequency'     => 'daily',
			'priority'      => 0.1,
			'last_modified' => '2015-12-24'
		);

		return array(
			array('Asia/Bangkok', false, $page_1),
			array('Asia/Bangkok', false, $page_2),
			array('Asia/Bangkok', true, $page_1),
			array('Asia/Bangkok', true, $page_2),
		);
	}

	protected static function set_up_timezone_settings($timezone_string, $gmt)
	{
		self::set_wp_options(array('timezone_string' => $timezone_string));

		self::set_options(BWP_GXS_GENERATOR, array(
			'enable_gmt' => $gmt ? 'yes' : ''
		));
	}
}
