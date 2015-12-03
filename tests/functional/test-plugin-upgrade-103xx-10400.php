<?php

class BWP_Sitemaps_Plugin_Upgrade_Functional_Test extends BWP_Sitemaps_PHPUnit_WP_Functional_TestCase
{
	protected static $current_version = '1.3.1';
	protected static $target_version = '1.4.0';

	protected static $wp_options = array(
		'gmt_offset' => 7
	);

	public function setUp()
	{
		parent::setUpForCurrentRequest();

		global $bwp_gxs;
		$this->plugin = $bwp_gxs;

		self::update_option('bwp_gxs_version', self::$current_version);
	}

	public function test_should_upgrade_log_format_correctly()
	{
		$time = 1409717016;

		self::set_options(BWP_GXS_LOG, array(
			'log' => array(
				array(
					'log'   => 'message1',
					'time'  => $time,
					'error' => false
				),
				array(
					'log'   => 'message2',
					'time'  => $time,
					'error' => true
				),
				array(
					'log'   => 'message3',
					'time'  => $time,
					'error' => 'notice'
				)
			),
			'sitemap' => array(
				array(
					'url'  => 'url1',
					'time' => $time
				),
				array(
					'url'  => 'url2',
					'time' => $time
				)
			)
		));

		$time_utc = $time - 7 * HOUR_IN_SECONDS;
		$datetime_utc = new DateTime('@' . $time_utc);
		$datetime_utc_formatted = $datetime_utc->format('Y-m-d H:i:s');

		$this->upgrade_plugin();

		$expected = array(
			'messages' => array(
				array(
					'message'  => 'message1',
					'type'     => 'success',
					'datetime' => $datetime_utc_formatted
				),
				array(
					'message'  => 'message2',
					'type'     => 'error',
					'datetime' => $datetime_utc_formatted
				),
				array(
					'message'  => 'message3',
					'type'     => 'notice',
					'datetime' => $datetime_utc_formatted
				)
			),
			'sitemaps' => array(
				array(
					'slug'     => 'url1',
					'datetime' => $datetime_utc_formatted
				),
				array(
					'slug'     => 'url2',
					'datetime' => $datetime_utc_formatted
				)
			)
		);

		$this->assertEquals($expected, get_option(BWP_GXS_LOG));
	}

	/**
	 * @runInSeparateProcess
	 * @dataProvider get_keyword_type
	 */
	public function test_should_upgrade_google_news_setting_correctly($keyword_type, $expected_keyword_source)
	{
		$this->plugin->options['select_news_keyword_type'] = $keyword_type;

		$this->upgrade_plugin();

		$this->assertEquals($expected_keyword_source, $this->plugin->options['select_news_keyword_source']);

		$new_options = get_option(BWP_GXS_GOOGLE_NEWS);
		$this->assertEquals($expected_keyword_source, $new_options['select_news_keyword_source']);
	}

	public function get_keyword_type()
	{
		return array(
			array('', 'category'),
			array('cat', 'category'),
			array('tag', 'tag')
		);
	}

	protected function upgrade_plugin()
	{
		$this->plugin->upgrade_plugin(self::$current_version, self::$target_version);
	}
}
