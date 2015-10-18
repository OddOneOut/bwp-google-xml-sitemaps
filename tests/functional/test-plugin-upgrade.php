<?php

class BWP_Sitemaps_Plugin_Upgrade_Functional_Test extends BWP_Sitemaps_PHPUnit_WP_Functional_TestCase
{
	protected static $wp_options = array(
		'gmt_offset' => 7
	);

	public function setUp()
	{
		parent::setUpForCurrentRequest();

		global $bwp_gxs;
		$this->plugin = $bwp_gxs;
	}

	public function test_should_upgrade_from_103xx_to_10400_correctly()
	{
		$old_version = '1.3.1';

		self::update_option('bwp_gxs_version', $old_version);

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

		$this->plugin->upgrade_plugin($old_version, '1.4.0');

		$this->assertEquals(array(
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
		), get_option(BWP_GXS_LOG));
	}
}
