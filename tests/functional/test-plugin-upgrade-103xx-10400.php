<?php

/**
 * @preserveGlobalState disabled
 */
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

	protected static function set_plugin_default_options()
	{
		self::update_option(BWP_GXS_GOOGLE_NEWS, array(
			'enable_news_sitemap' => 'yes'
		));

		self::update_option(BWP_GXS_EXTENSIONS, array());
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
	 * @dataProvider get_keyword_types
	 */
	public function test_should_upgrade_select_news_keyword_type_correctly($keyword_type, $expected_keyword_source)
	{
		$this->plugin->options['select_news_keyword_type'] = $keyword_type;

		$this->upgrade_plugin();

		$this->assertEquals($expected_keyword_source, $this->plugin->options['select_news_keyword_source']);

		$new_options = get_option(BWP_GXS_GOOGLE_NEWS);
		$this->assertEquals($expected_keyword_source, $new_options['select_news_keyword_source']);
	}

	public function get_keyword_types()
	{
		return array(
			array('', 'category'),
			array('cat', 'category'),
			array('tag', 'tag')
		);
	}

	/**
	 * @runInSeparateProcess
	 * @dataProvider get_input_news_genres
	 */
	public function test_should_upgrade_input_news_genres_correctly($current_news_genres, $expected_news_genres)
	{
		$this->plugin->options['input_news_genres'] = $current_news_genres;

		$this->upgrade_plugin();

		$this->assertEquals($expected_news_genres, $this->plugin->options['input_news_genres']);

		$new_options = get_option(BWP_GXS_GOOGLE_NEWS);
		$this->assertEquals($expected_news_genres, $new_options['input_news_genres']);
	}

	public function get_input_news_genres()
	{
		return array(
			array(
				array(
					'cat_1'   => 'genre1,genre2,genre3',
					'cat_2'   => 'genre1, genre2, genre3',
					'cat_xyz' => 'genre1, genre2, genre3',
					'cat'     => 'genre1, genre2, genre3',
					'cat123'  => 'genre1, genre2, genre3'
				),
				array(
					'term_1' => 'genre1,genre2,genre3',
					'term_2' => 'genre1, genre2, genre3'
				)
			)
		);
	}

	/**
	 * @runInSeparateProcess
	 */
	public function test_should_not_merge_google_news_settings_when_not_needed()
	{
		$extensions_options = array(
			'enable_image_sitemap' => 'yes',
			'enable_news_ping' => 'yes'
		);

		self::update_option(BWP_GXS_GOOGLE_NEWS, false);
		self::update_option(BWP_GXS_EXTENSIONS, $extensions_options);

		$this->upgrade_plugin();

		$this->assertEquals($extensions_options, get_option(BWP_GXS_EXTENSIONS), 'not valid google news settings, no merging should be done');
	}

	/**
	 * @runInSeparateProcess
	 * @depends test_should_not_merge_google_news_settings_when_not_needed
	 */
	public function test_should_merge_google_news_settings_into_extensions_settings_correctly()
	{
		$google_news_settings = array(
			'enable_news_sitemap'        => '',
			'enable_news_keywords'       => '',
			'enable_news_ping'           => '',
			'enable_news_multicat'       => '',
			'select_news_lang'           => 'en',
			'select_news_post_type'      => 'post',
			'select_news_taxonomy'       => 'category',
			'select_news_keyword_type'   => 'cat',
			'select_news_keyword_source' => '',
			'select_news_cat_action'     => 'inc',
			'select_news_cats'           => '',
			'input_news_name'            => '',
			'input_news_genres'          => array()
		);

		self::set_options(BWP_GXS_GOOGLE_NEWS, $google_news_settings);

		$this->plugin->options = array(
			'select_news_keyword_type' => 'cat',
			'input_news_genres' => array(
				'cat_1' => 'genre1,genre2,genre3',
				'cat_2' => 'genre1, genre2, genre3',
			)
		);

		$this->upgrade_plugin();

		// extensions settings should contain the updated options. These
		// options are updated in another upgrade script.
		$google_news_settings['select_news_keyword_source'] = 'category';
		$google_news_settings['input_news_genres'] = array(
			'term_1' => 'genre1,genre2,genre3',
			'term_2' => 'genre1, genre2, genre3'
		);

		$this->assertArraySubset($google_news_settings, get_option(BWP_GXS_EXTENSIONS));
		$this->assertArraySubset($google_news_settings, $this->plugin->options);
	}

	protected function upgrade_plugin()
	{
		$this->plugin->upgrade_plugin(self::$current_version, self::$target_version);
	}
}
