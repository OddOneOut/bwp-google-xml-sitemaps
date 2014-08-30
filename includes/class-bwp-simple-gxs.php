<?php
/**
 * Main Sitemap class that provides all logics.
 *
 * Copyright (c) 2014 Khang Minh <betterwp.net>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Khang Minh <contact@betterwp.net>
 * @link http://betterwp.net/wordpress-plugins/google-xml-sitemaps/
 * @link https://github.com/OddOneOut/Better-WordPress-Google-XML-Sitemaps
 */

if (!class_exists('BWP_FRAMEWORK_IMPROVED'))
	require_once(dirname(__FILE__) . '/class-bwp-framework-improved.php');

class BWP_SIMPLE_GXS extends BWP_FRAMEWORK_IMPROVED
{
	/**
	 * Sitemap generation log
	 *
	 * @var array
	 */
	public $logs = array(
		'log'     => array(),
		'sitemap' => array()
	);

	/**
	 * Whether generator log is empty
	 *
	 * @var bool
	 */
	private $_is_log_empty = false;

	/**
	 * Modules to load when generating sitemapindex
	 *
	 * @var array
	 */
	public $modules = array(), $requested_modules = array();

	/**
	 * Directories to load modules from
	 *
	 * @var string
	 */
	public $module_directory = '', $custom_module_directory = '';

	/**
	 * Directory to store cached sitemap
	 *
	 * @var string
	 * @since 1.2.4
	 */
	public $cache_directory = '';

	/**
	 * The permalink structure for sitemap files
	 */
	public $module_url = array();

	/**
	 * Url updating frequencies
	 *
	 * @var array
	 */
	public $frequencies = array(
		'always',
		'hourly',
		'daily',
		'weekly',
		'monthly',
		'yearly',
		'never'
	);

	/**
	 * Url crawling priorties
	 *
	 * @var array
	 */
	public $priorities = array(
		'0.1' => 0.1,
		'0.2' => 0.2,
		'0.3' => 0.3,
		'0.4' => 0.4,
		'0.5' => 0.5,
		'0.6' => 0.6,
		'0.7' => 0.7,
		'0.8' => 0.8,
		'0.9' => 0.9,
		'1.0' => 1.0
	);

	/**
	 * Other properties
	 */
	public $cache_time, $num_log = 25;
	public $sitemap_alias = array(), $use_permalink = true, $query_var_non_perma = '';

	/**
	 * Urls to ping
	 *
	 * @var array
	 * @since 1.2.4
	 */
	private $_ping_urls = array(
		'google' => 'http://www.google.com/webmasters/sitemaps/ping?sitemap=%s',
		'bing'   => 'http://www.bing.com/webmaster/ping.aspx?siteMap=%s',
		//'ask' => 'http://submissions.ask.com/ping?sitemap=%s'),
	);

	/**
	 * Name of the sitemap to ping with
	 *
	 * @var string
	 */
	private $_ping_sitemap = 'sitemapindex';

	/**
	 * Whether debug mode/debug extra mode is enabled
	 *
	 * @var bool
	 * @since 1.2.4
	 */
	private $_debug = false, $_debug_extra = false;

	/**
	 * The maximum number of times to ping per day for each SE
	 *
	 * @var int
	 */
	public $pings_per_day = 100;

	/**
	 * Timeout for ping request
	 *
	 * @var int
	 */
	public $ping_timeout = 3;

	/**
	 * A list of post type objects
	 *
	 * @var array
	 */
	public $post_types;

	/**
	 * A list of taxonomy objects
	 *
	 * @var array
	 */
	public $taxonomies;

	/**
	 * A list of term objects
	 *
	 * @var array
	 */
	public $terms;

	/**
	 * Sitemap templates
	 *
	 * @var array
	 */
	public $templates = array();

	/**
	 * Module data for a specific sitemap
	 *
	 * @var array
	 */
	public $module_data = array();

	/**
	 * Mapping data for a module/sub-module
	 *
	 * @var array
	 */
	public $module_map = array();

	/**
	 * Sitemap generation stats
	 *
	 * @var array
	 */
	public $build_data = array(
		'time',
		'mem',
		'query'
	);

	/**
	 * Stylesheets for XML sitemaps
	 *
	 * @var string
	 */
	public $xslt = '', $xslt_index = '';

	/**
	 * Holds a sitemap's output
	 *
	 * @var string
	 */
	public $output = '';

	/**
	 * The number of urls found in a sitemap
	 *
	 * @var int
	 */
	public $output_num = 0;

	/**
	 * Holds the GXS cache class
	 *
	 * @var BWP_GXS_CACHE
	 */
	public $cache = false;

	/**
	 * Constructor
	 */
	public function __construct($version = '1.2.4')
	{
		// Plugin's title
		$this->plugin_title = 'Better WordPress Google XML Sitemaps';
		// Plugin's version
		$this->set_version($version);
		// Plugin's language domain
		$this->domain = 'bwp-simple-gxs';

		// Basic version checking
		if (!$this->check_required_versions())
			return;

		// Default options
		$options = array(
			'enable_cache'              => 'yes',
			'enable_cache_auto_gen'     => 'yes',
			'enable_gzip'               => '',
			'enable_xslt'               => 'yes',
			'enable_sitemap_date'       => '',
			'enable_sitemap_taxonomy'   => 'yes',
			'enable_sitemap_external'   => '',
			'enable_sitemap_split_post' => 'yes',
			'enable_sitemap_author'     => '',
			'enable_sitemap_site'       => 'yes',
			'enable_stats'              => 'yes',
			'enable_credit'             => 'yes',
			'enable_ping'               => 'yes',
			'enable_ping_google'        => 'yes',
			'enable_ping_bing'          => 'yes',
			//'enable_ping_ask' => '',
			'enable_log'                => 'yes',
			'enable_debug'              => '',
			'enable_debug_extra'        => '',
			'enable_robots'             => 'yes',
			'enable_global_robots'      => '',
			'enable_gmt'                => 'yes',
			// Google news options
			'enable_news_sitemap'       => '',
			'enable_news_keywords'      => '',
			'enable_news_ping'          => '',
			'enable_news_multicat'      => '',
			'select_news_lang'          => 'en',
			'select_news_keyword_type'  => 'cat',
			'select_news_cat_action'    => 'inc',
			'select_news_cats'          => '',
			'input_news_genres'         => array(),
			// End of Google news options
			'input_exclude_post_type'   => '',
			'input_exclude_taxonomy'    => 'post_tag',
			'input_cache_age'           => 1,
			'input_item_limit'          => 5000,
			'input_split_limit_post'    => 0,
			'input_alt_module_dir'      => $this->_normalize_path_separator(ABSPATH),
			'input_oldest'              => 7,
			'input_sql_limit'           => 1000,
			'input_custom_xslt'         => '',
			'select_output_type'        => 'concise',
			'select_time_type'          => 3600,
			'select_oldest_type'        => 16400,
			'select_default_freq'       => 'daily',
			'select_default_pri'        => 1.0,
			'select_min_pri'            => 0.1,
			'input_cache_dir'           => '',
			'input_sitemap_url'         => '',
			'input_sitemap_struct'      => ''
		);

		// Super admin only options
		$this->site_options = array(
			'enable_robots',
			'enable_global_robots',
			'enable_log',
			'enable_debug',
			'enable_debug_extra',
			'enable_ping',
			'enable_ping_google',
			'enable_ping_bing',
			/* 'enable_ping_ask', */
			'enable_gzip',
			'enable_cache',
			'enable_cache_auto_gen',
			'input_cache_age',
			'input_alt_module_dir',
			'input_sql_limit',
			'input_cache_dir',
			'select_time_type'
		);

		$this->add_option_key('BWP_GXS_OPTION_GENERATOR', 'bwp_gxs_generator',
			__('XML Sitemaps', $this->domain));
		$this->add_option_key('BWP_GXS_GOOGLE_NEWS', 'bwp_gxs_google_news',
			__('Google News Sitemap', $this->domain));
		$this->add_option_key('BWP_GXS_STATS', 'bwp_gxs_stats',
			__('Sitemap Log', $this->domain));

		define('BWP_GXS_LOG', 'bwp_gxs_log');
		define('BWP_GXS_PING', 'bwp_gxs_ping_data');

		$this->build_properties('BWP_GXS', $this->domain, $options,
			'Better WordPress Google XML Sitemaps', dirname(dirname(__FILE__)) . '/bwp-simple-gxs.php',
			'http://betterwp.net/wordpress-plugins/google-xml-sitemaps/', false
		);
	}

	protected function pre_init_properties()
	{
		// set up the default module directory and a custom module directory if applicable
		$this->module_directory = plugin_dir_path($this->plugin_file) . 'includes/modules/';
		$this->custom_module_directory = !empty($this->options['input_alt_module_dir'])
			? $this->options['input_alt_module_dir']
			: false;
		$this->custom_module_directory = trailingslashit(apply_filters('bwp_gxs_module_dir', $this->custom_module_directory));

		$this->templates = array(
			// Sitemap index
			'sitemap'          => "\n\t" . '<sitemap>' . "\n\t\t" . '<loc>%s</loc>%s' . "\n\t" . '</sitemap>',
			// Normal sitemap
			'url'              => "\n\t" . '<url>' . "\n\t\t" . '<loc>%1$s</loc>%2$s%3$s%4$s' . "\n\t" . '</url>',
			'lastmod'          => "\n\t\t" . '<lastmod>%s</lastmod>',
			'changefreq'       => "\n\t\t" . '<changefreq>%s</changefreq>',
			'priority'         => "\n\t\t" . '<priority>%.1f</priority>',
			// Google News Sitemap
			'news'             => "\n\t" . '<url>' . "\n\t\t" . '<loc>%1$s</loc>' . "\n\t\t" . '<news:news>%2$s%3$s%4$s%5$s%6$s' . "\n\t\t" . '</news:news>' . "\n\t" . '</url>',
			'news_publication' => "\n\t\t\t" . '<news:publication>%1$s%2$s</news:publication>',
			'news_name'        => "\n\t\t\t\t" . '<news:name>%s</news:name>',
			'news_language'    => "\n\t\t\t\t" . '<news:language>%s</news:language>',
			'news_genres'      => "\n\t\t\t" . '<news:genres>%s</news:genres>',
			'news_pub_date'    => "\n\t\t\t" . '<news:publication_date>%s</news:publication_date>',
			'news_title'       => "\n\t\t\t" . '<news:title>%s</news:title>',
			'news_keywords'    => "\n\t\t\t" . '<news:keywords>%s</news:keywords>',
			// Misc
			'xslt_style'       => '',
			'stats'            => "\n" . '<!-- ' . __('This sitemap was originally generated in %s second(s) (Memory usage: %s) - %s queries - %s URL(s) listed', $this->domain) . ' -->'
			/*'stats_cached'	=> "\n" . '<!-- ' . __('Served from cache in %s second(s) (Memory usage: %s) - %s queries - %s URL(s) listed', $this->domain) . ' -->'*/
		);

		$this->cache_time  = (int) $this->options['input_cache_age'] * (int) $this->options['select_time_type'];

		// @todo
		$this->options['input_cache_dir'] = plugin_dir_path($this->plugin_file) . 'cache/';
		$this->options['input_cache_dir'] = $this->_normalize_path_separator($this->options['input_cache_dir']);
		$this->cache_directory = $this->options['input_cache_dir'];

		// @todo needdoc
		$module_map       = apply_filters('bwp_gxs_module_mapping', array());
		$this->module_map = wp_parse_args($module_map, array(
			'post_format' => 'post_tag'
		));

		// init debug and debug extra mode
		$this->_init_debug();

		// init sitemap log
		$this->_init_logs();

		// No more than 50000 URLs per sitemap @todo move this check to admin page
		if (50000 < $this->options['input_item_limit'])
			$this->options['input_item_limit'] = 50000;

		// Limit per split sitemap - @since 1.1.0 @todo move this check to admin page
		// Not higher than 50000 URLs and must be >= SQL cycling limit
		if ($this->options['input_split_limit_post'] < $this->options['input_sql_limit'])
			$this->options['input_split_limit_post'] = $this->options['input_sql_limit'];
		if (50000 < (int) $this->options['input_split_limit_post'])
			$this->options['input_split_limit_post'] = 50000;

		// XSLT style sheet @todo
		if ('yes' == $this->options['enable_xslt'])
		{
			// If the host the user is using is different from what we get from
			// 'home' option, we need to use the host so user won't see a style
			// sheet error, which is most of the time mistaken as broken
			// sitemaps - @since 1.1.0
			$user_host = strtolower($_SERVER['HTTP_HOST']);
			$blog_home = @parse_url(home_url());
			$blog_host = strtolower($blog_home['host']);

			$this->xslt = !empty($this->options['input_custom_xslt'])
				? $this->options['input_custom_xslt']
				: plugin_dir_url($this->plugin_file) . 'xsl/bwp-sitemap.xsl';

			$this->xslt = $user_host == $blog_host
				? $this->xslt
				: str_replace($blog_host, $user_host, $this->xslt);
		}

		// Some stats
		$this->build_stats['mem'] = memory_get_usage();
	}

	protected function load_libraries()
	{
		require_once dirname(__FILE__) . '/common-functions.php';
		require_once dirname(__FILE__) . '/class-bwp-gxs-cache.php';

		$this->cache = new BWP_GXS_CACHE($this);
	}

	protected function init_properties()
	{
		$this->xslt       = apply_filters('bwp_gxs_xslt', $this->xslt);
		$this->xslt_index = empty($this->xslt) ? '' : str_replace('.xsl', 'index.xsl', $this->xslt);

		$permalink = get_option('permalink_structure');

		if (!$permalink)
		{
			// do not use friendly sitemap urls
			$this->use_permalink = false;

			$this->query_var_non_perma = apply_filters('bwp_gxs_query_var_non_perma', 'bwpsitemap');

			// @todo recheck https
			$this->options['input_sitemap_url']    = home_url() . '/?' . $this->query_var_non_perma . '=sitemapindex';
			$this->options['input_sitemap_struct'] = home_url() . '/?' . $this->query_var_non_perma . '=%s';
		}
		else
		{
			// use friendly sitemap urls such as http://example.com/sitemapindex.xml
			// If user is using index.php in their permalink structure, we will
			// have to include it also
			$indexphp = strpos($permalink, 'index.php') === false ? '' : '/index.php';

			$this->options['input_sitemap_url']    = home_url() . $indexphp . '/sitemapindex.xml';
			$this->options['input_sitemap_struct'] = home_url() . $indexphp . '/%s.xml';
		}
	}

	protected function enqueue_media()
	{
		if ($this->is_admin_page())
			wp_enqueue_style('bwp-gxs-admin', BWP_GXS_CSS . '/bwp-simple-gxs.css');

		if ($this->is_admin_page(BWP_GXS_OPTION_GENERATOR))
			wp_enqueue_script('bwp-gxs-setting', BWP_GXS_JS . '/bwp-gxs.js');
	}

	public function insert_query_vars($vars)
	{
		if (!$this->use_permalink)
		{
			array_push($vars, $this->query_var_non_perma);
		}
		else
		{
			array_push($vars, 'gxs_module');
			array_push($vars, 'gxs_sub_module');
		}

		return $vars;
	}

	public function insert_rewrite_rules($rules)
	{
		$rewrite_rules = array(
			'sitemap\.xml$'                   => 'index.php?gxs_module=sitemapindex',
			'sitemapindex\.xml$'              => 'index.php?gxs_module=sitemapindex',
			'site\.xml$'                      => 'index.php?gxs_module=site',
			'page\.xml$'                      => 'index.php?gxs_module=page',
			'post\.xml$'                      => 'index.php?gxs_module=post',
			'author\.xml$'                    => 'index.php?gxs_module=author',
			'([a-z0-9]+)_([a-z0-9_-]+)\.xml$' => 'index.php?gxs_module=$matches[1]&gxs_sub_module=$matches[2]'
		);

		// @since 1.0.3
		$custom_rules = apply_filters('bwp_gxs_rewrite_rules', array());
		$rules        = array_merge($custom_rules, $rewrite_rules, $rules);

		return $rules;
	}

	/**
	 * Inits debug and debug extra mode
	 *
	 * @return void
	 * @since 1.2.4
	 * @access private
	 **/
	private function _init_debug()
	{
		$this->_debug       = $this->options['enable_debug'] == 'yes'
			|| $this->options['enable_debug_extra'] == 'yes' ? true : false;
		$this->_debug_extra = $this->options['enable_debug_extra'] == 'yes' ? true : false;
	}

	/**
	 * Inits sitemap log property
	 *
	 * @since 1.2.4
	 * @access private
	 */
	private function _init_logs()
	{
		$this->logs = get_option(BWP_GXS_LOG);

		if (!$this->logs)
		{
			$this->logs = array(
				'log'     => array(),
				'sitemap' => array()
			);
		}

		if (sizeof($this->logs['log']) == 0)
			$this->_is_log_empty = true;

		foreach ($this->logs as $key => $log)
		{
			if (is_array($log) && $this->num_log < sizeof($log))
			{
				$log = array_slice($log, (-1) * $this->num_log);
				$this->logs[$key] = $log;
			}
		}
	}

	private static function _flush_rewrite_rules()
	{
		global $wp_rewrite;

		$wp_rewrite->flush_rules();
	}

	protected function pre_init_hooks()
	{
		add_filter('rewrite_rules_array', array($this, 'insert_rewrite_rules'), 9);
		add_filter('query_vars', array($this, 'insert_query_vars'));
		add_action('parse_request', array($this, 'request_sitemap'));

		if ('yes' == $this->options['enable_ping'])
		{
			// ping search engines with sitemapindex
			// @see `wp_transition_post_status` in wp-includes/post.php
			add_action('auto-draft_to_publish', array($this, 'ping'), 1000);
			add_action('draft_to_publish', array($this, 'ping'), 1000);
			add_action('new_to_publish', array($this, 'ping'), 1000);
			add_action('pending_to_publish', array($this, 'ping'), 1000);
			add_action('future_to_publish', array($this, 'ping'), 1000);
		}

		if ('yes' == $this->options['enable_news_ping'])
		{
			// enable ping for news sitemap
			add_action('auto-draft_to_publish', array($this, 'ping_google_news'), 1000);
			add_action('draft_to_publish', array($this, 'ping_google_news'), 1000);
			add_action('new_to_publish', array($this, 'ping_google_news'), 1000);
			add_action('pending_to_publish', array($this, 'ping_google_news'), 1000);
			add_action('future_to_publish', array($this, 'ping_google_news'), 1000);
		}

		if ('yes' == $this->options['enable_robots'])
			add_filter('robots_txt', array($this, 'do_robots'), 1000, 2);
	}

	public function install()
	{
		self::_flush_rewrite_rules();
	}

	public function uninstall()
	{
		$this->logs = array(
			'log'     => array(),
			'sitemap' => array()
		);

		$this->commit_logs();

		/* self::_flush_rewrite_rules(); */
	}

	/**
	 * Build the Menus
	 */
	protected function build_menus()
	{
		if (!empty($this->_menu_under_settings))
		{
			// use simple menu if instructed to
			add_options_page(
				__('BWP Google XML Sitemaps Statistics', $this->domain),
				__('BWP XML Sitemaps', $this->domain),
				BWP_GXS_CAPABILITY,
				BWP_GXS_OPTION_GENERATOR,
				array($this, 'build_option_pages')
			);
			add_options_page(
				__('BWP Google News XML Sitemap', $this->domain),
				__('BWP Google News Sitemap', $this->domain),
				BWP_GXS_CAPABILITY,
				BWP_GXS_GOOGLE_NEWS,
				array($this, 'build_option_pages')
			);
			add_options_page(
				__('BWP Google XML Sitemaps Generator', $this->domain),
				__('BWP Sitemap Log', $this->domain),
				BWP_GXS_CAPABILITY,
				BWP_GXS_STATS,
				array($this, 'build_option_pages')
			);
		}
		else
		{
			add_menu_page(
				__('Better WordPress Google XML Sitemaps', $this->domain),
				'BWP Sitemaps',
				BWP_GXS_CAPABILITY,
				BWP_GXS_OPTION_GENERATOR,
				array($this, 'build_option_pages'),
				BWP_GXS_IMAGES . '/icon_menu.png'
			);
			add_submenu_page(
				BWP_GXS_OPTION_GENERATOR,
				__('Better WordPress Google XML Sitemaps Statistics', $this->domain),
				__('XML Sitemaps', $this->domain),
				BWP_GXS_CAPABILITY,
				BWP_GXS_OPTION_GENERATOR,
				array($this, 'build_option_pages')
			);
			add_submenu_page(
				BWP_GXS_OPTION_GENERATOR,
				__('Better WordPress Google News XML Sitemap', $this->domain),
				__('Google News Sitemap', $this->domain),
				BWP_GXS_CAPABILITY,
				BWP_GXS_GOOGLE_NEWS,
				array($this, 'build_option_pages')
			);
			add_submenu_page(
				BWP_GXS_OPTION_GENERATOR,
				__('Better WordPress Google XML Sitemaps Generator', $this->domain),
				__('Sitemap Log', $this->domain),
				BWP_GXS_CAPABILITY,
				BWP_GXS_STATS,
				array($this, 'build_option_pages')
			);
		}
	}

	/**
	 * Build the option pages
	 *
	 * Utilizes BWP Option Page Builder (@see BWP_OPTION_PAGE)
	 */
	public function build_option_pages()
	{
		if (!current_user_can(BWP_GXS_CAPABILITY))
			wp_die(__('You do not have sufficient permissions to access this page.'));

		// Init the class
		$page            = $_GET['page'];
		$bwp_option_page = new BWP_OPTION_PAGE($page, $this->site_options);

		$options         = array();
		$dynamic_options = array();

		if (!empty($page))
		{
			if ($page == BWP_GXS_OPTION_GENERATOR)
			{
				$bwp_option_page->set_current_tab(1);

				$form = array(
					'items' => array(
						'heading',
						'heading',
						'section',
						'section',
						'section',
						'heading',
						'input',
						'checkbox',
						'input',
						'heading',
						'select',
						'select',
						'select',
						'checkbox',
						'checkbox',
						'heading',
						'checkbox',
						'input',
						'checkbox',
						'checkbox',
						'heading',
						'checkbox',
						'checkbox',
						'heading',
						'checkbox',
						'section',
						'heading',
						'checkbox',
						'checkbox',
						'input',
						'input',
						'heading',
						'input',
						'input',
						'heading',
						'checkbox',
						'checkbox',
						'checkbox'
					),
					'item_labels' => array(
						__('Your sitemaps', $this->domain),
						__('Sitemaps to generate', $this->domain),
						__('<strong>Enable</strong> following sitemaps', $this->domain),
						__('For post-based sitemaps, <strong>disable</strong> following post types:', $this->domain),
						__('For taxonomy-based sitemaps, <strong>disable</strong> following taxonomies:', $this->domain),
						__('Item limits', $this->domain),
						__('Global limit', $this->domain),
						__('Split <strong>post-based</strong> sitemaps', $this->domain),
						__('Split limit', $this->domain),
						__('Default values & Formatting', $this->domain),
						__('Default change frequency', $this->domain),
						__('Default priority', $this->domain),
						__('Minimum priority', $this->domain),
						__('Use GMT for Last Modified date', $this->domain),
						__('Compress sitemaps', $this->domain),
						__('Look and Feel', $this->domain),
						__('Make sitemaps look pretty', $this->domain),
						__('Custom XSLT stylesheet URL', $this->domain),
						__('Enable build stats', $this->domain),
						__('Enable credit', $this->domain),
						__('Virtual robots.txt', $this->domain),
						__('Add a sitemapindex entry to each blog\'s robots.txt', $this->domain),
						__('For a multi-site installation, add sitemapindex entries from all blogs to primary blog\'s robots.txt', $this->domain),
						__('Ping search engines', $this->domain),
						__('Enable pinging', $this->domain),
						__('Search engines to ping', $this->domain),
						__('Caching', $this->domain),
						__('Enable caching', $this->domain),
						__('Enable auto cache re-generation', $this->domain),
						__('Cached sitemaps will last for', $this->domain),
						__('Cache directory', $this->domain),
						__('Sitemap modules', $this->domain),
						__('Database query limit', $this->domain),
						__('Custom module directory', $this->domain),
						htmlspecialchars(__('Sitemap log & debug', $this->domain)),
						__('Enable sitemap log', $this->domain),
						__('Enable debug mode', $this->domain),
						__('Enable debug extra mode', $this->domain)
					),
					'item_names' => array(
						'heading_submit',
						'heading_contents',
						'sec_index',
						'sec_post',
						'sec_tax',
						'heading_limit',
						'input_item_limit',
						'cb_enable_split',
						'input_split_limit_post',
						'heading_format',
						'select_default_freq',
						'select_default_pri',
						'select_min_pri',
						'cb14',
						'cb_enable_gzip',
						'heading_look',
						'cb10',
						'input_custom_xslt',
						'cb3',
						'cb6',
						'heading_robot',
						'cb_index_to_blog',
						'cb_index_to_primary',
						'heading_ping',
						'cb_ping',
						'cb_ping_list',
						'heading_cache',
						'cb1',
						'cb_enable_autogen',
						'input_cache_age',
						'input_cache_dir',
						'heading_module',
						'input_sql_limit',
						'input_alt_module_dir',
						'heading_debug',
						'cb_log',
						'cb_debug',
						'cb_debug_extra'
					),
					'heading' => array(
						'heading_submit' => '',
						'heading_contents' => '<em>'
							. sprintf(
								__('Choose appropriate sitemaps to generate. '
								. 'For each sitemap, you can use filters to further '
								. '<a href="%s#exclude_items" target="_blank">exclude items</a> '
								. 'you do not need.', $this->domain),
								$this->plugin_url
							) . '</em>',
						'heading_limit' => '<em>'
							. __('Limit the number of items to output in one sitemap. ', $this->domain)
							. sprintf(__('Avoid setting too high limits that your server '
								. 'can not handle. In such case, you might encounter white page error '
								. 'due to timeout or memory issue. '
								. 'Refer to this plugin\'s <a target="_blank" href="%s">FAQ section</a> for more info.', $this->domain),
								$this->plugin_url . 'faq/')
							. '</em>',
						'heading_format' => '<em>'
							. __('Customize default values and some formating for your sitemaps. '
							. 'Default values are only used when valid ones can not '
							. 'be calculated.', $this->domain)
							. '</em>',
						'heading_look' => '<em>'
							. __('Customize the look and feel of your sitemaps. '
							. 'Note that an XSLT stylesheet will NOT be used '
							. 'for the Google News Sitemap module '
							. 'regardless of any setting in this section.', $this->domain)
							. '</em>',
						'heading_robot' => '<em>'
							. sprintf(__('WordPress generates a <a href="%s" target="_blank">virtual robots.txt</a> '
								. 'file for your site by default. '
								. 'You can add links to sitemapindex files generated by this plugin '
								. 'to that robots.txt file using settings below.', $this->domain),
								home_url('robots.txt'))
							. '</em>',
						'heading_ping' => '<em>'
							. __('Whenever you post something new to your blog, '
							. 'you can <em>ping</em> search engines with your sitemapindex '
							. 'to tell them your blog just got updated.', $this->domain)
							. '</em>',
						'heading_cache' => '<em>'
							. __('Cache your sitemaps for better performance.', $this->domain)
							. '</em>',
						'heading_module' => '<em>'
							. sprintf(__('Extend this plugin using customizable modules. '
								. 'More info <a href="%s#using-modules">here</a>.', $this->domain),
								$this->plugin_url)
							. '</em>',
						'heading_debug' => ''
					),
					'sec_index' => array(
						array('checkbox', 'name' => 'cb17'),
						array('checkbox', 'name' => 'cb7'),
						array('checkbox', 'name' => 'cb9'),
						array('checkbox', 'name' => 'cb16'),
						array('checkbox', 'name' => 'cb13')
					),
					'sec_post' => array(),
					'sec_tax' => array(),
					'cb_ping_list' => array(
						array('checkbox', 'name' => 'cb_ping_google'),
						array('checkbox', 'name' => 'cb_ping_bing')
					),
					'select' => array(
						'select_time_type' => array(
							__('second(s)', $this->domain) => 1,
							__('minute(s)', $this->domain) => 60,
							__('hour(s)', $this->domain)   => 3600,
							__('day(s)', $this->domain)    => 86400
						),
						'select_oldest_type' => array(
							__('second(s)', $this->domain) => 1,
							__('minute(s)', $this->domain) => 60,
							__('hour(s)', $this->domain)   => 3600,
							__('day(s)', $this->domain)    => 86400
						),
						'select_default_freq' => array(),
						'select_default_pri' => $this->priorities,
						'select_min_pri' => $this->priorities
					),
					'post' => array(
						'select_default_freq' => sprintf('<a href="%s" target="_blank">'
							. __('read more', $this->domain)
							. '</a>', 'http://sitemaps.org/protocol.php#xmlTagDefinitions'),
						'select_default_pri'  => sprintf('<a href="%s" target="_blank">'
							. __('read more', $this->domain)
							. '</a>', 'http://sitemaps.org/protocol.php#xmlTagDefinitions'),
						'select_min_pri'      => sprintf('<a href="%s" target="_blank">'
							. __('read more', $this->domain)
							. '</a>', 'http://sitemaps.org/protocol.php#xmlTagDefinitions')
					),
					'checkbox' => array(
						'cb1'                 => array(__('Your sitemaps are generated and then cached to reduce unnecessary work.', $this->domain) => 'enable_cache'),
						'cb_enable_autogen'   => array(__('Re-generate sitemap cache when expired. If you disable this, remember to manually flush the cache once in a while.', $this->domain) => 'enable_cache_auto_gen'),
						'cb3'                 => array(__('print useful information such as build time, memory usage, SQL queries, etc.', $this->domain) => 'enable_stats'),
						'cb_enable_gzip'      => array(__('Use gzip to make sitemaps ~70% smaller. If you see an error after enabling this, it\'s very likely that you have gzip active on your server already.', $this->domain) => 'enable_gzip'),
						'cb_index_to_primary' => array(sprintf(__('If you have like 50 blogs, 50 sitemapindex entries will be added to your primary blog\'s <a href="%s" target="_blank">robots.txt</a>.', $this->domain), get_site_option('home') . '/robots.txt') => 'enable_global_robots'),
						'cb7'                 => array(__('Taxonomy (including custom taxonomies).', $this->domain) => 'enable_sitemap_taxonomy'),
						'cb9'                 => array(__('Date archives.', $this->domain) => 'enable_sitemap_date'),
						'cb16'                => array(__('Author archives.', $this->domain) => 'enable_sitemap_author'),
						'cb13'                => array(sprintf(__('External pages. More info <a href="%s#external_sitemap" target="_blank">here</a>.', $this->domain), $this->plugin_url) => 'enable_sitemap_external'),
						'cb6'                 => array(__('some copyrighted info is also added to your sitemaps. Thanks!', $this->domain) => 'enable_credit'),
						'cb10'                => array(__('A default XSLT stylesheet will be used. You can set a custom style sheet below or filter the <code>bwp_gxs_xslt</code> hook.', $this->domain) => 'enable_xslt'),
						'cb_index_to_blog'    => array(sprintf(__('If you\'re on a Multi-site installation with Sub-domain enabled, each site will have its own robots.txt, sites in sub-directory will not. Please read the <a href="%s#toc-robots" target="_blank">documentation</a> for more info.', $this->domain), $this->plugin_url) => 'enable_robots'),
						'cb_enable_split'     => array(__('Sitemaps like <code>post.xml</code> are split into <code>post_part1.xml</code>, <code>post_part2.xml</code>, etc. when limit reached.', $this->domain) => 'enable_sitemap_split_post'),
						'cb14'                => array(__('Disable this to use the local timezone setting in <em>Settings >> General</em>.', $this->domain) => 'enable_gmt'),
						'cb17'                => array(__('Site Address. For a multi-site installation of WordPress, this sitemap will list all domains within your network, not just the main blog.', $this->domain) => 'enable_sitemap_site'),
						'cb_ping'             => array(__('Selected search engines below will be pinged when you publish new posts.', $this->domain) => 'enable_ping'),
						'cb_ping_google'      => array(__('Google', $this->domain) => 'enable_ping_google'),
						'cb_ping_bing'        => array(__('Bing', $this->domain) => 'enable_ping_bing'),
						'cb_log'              => array(sprintf(__('No additional load is needed so enabling this is highly recommended. You can check the log <a href="%s">here</a>.', $this->domain), $this->get_admin_page(BWP_GXS_STATS)) => 'enable_log'),
						'cb_debug'            => array(__('When this is on, NO caching is used and <code>WP_DEBUG</code> is respected, useful when developing new modules.', $this->domain) => 'enable_debug'),
						'cb_debug_extra'      => array(sprintf(__('When this is on, NO headers are sent and sitemaps are NOT compressed, useful when debugging <em>Content Encoding Error</em>. More info <a href="%s#debug_extra" target="_blank">here</a>.', $this->domain), $this->plugin_url) => 'enable_debug_extra'),
					),
					'input' => array(
						'input_item_limit' => array(
							'size'  => 5,
							'label' => __('Maximum is <strong>50,000</strong>. '
								. 'This setting is applied to all sitemaps.', $this->domain)
						),
						'input_split_limit_post' => array(
							'size'  => 5,
							'label' => __('Maximum is <strong>50,000</strong>. '
								. 'Set to 0 to use the Global limit.', $this->domain)
						),
						'input_custom_xslt' => array(
							'size'  => 91,
							'label' => '<br />'
								. __('expected to be an absolute URL, '
								. 'e.g. <code>http://example.com/my-stylesheet.xsl</code>. '
								. 'You must also have a style sheet for the sitemapindex '
								. 'that can be accessed through the above URL, '
								. 'e.g. <code>my-stylesheet.xsl</code> and '
								. '<code>my-stylesheetindex.xsl</code>. '
								. 'Leave blank to use provided stylesheet.', $this->domain)
						),
						'input_alt_module_dir' => array(
							'size' => 91,
							'label' => '<br />'
								. __('Expect an absolute path to the directory where you put your custom modules '
								. '(e.g. <code>/home/mysite/public_html/gxs-modules/</code>). '
								. 'Override a built-in module by having a module with the same filename in specified directory.', $this->domain)
						),
						'input_cache_dir' => array(
							'size' => 91,
							'disabled' => ' disabled="disabled"',
							'label' => '<br />' . __('This directory must be writable (i.e. CHMOD to 755 or 777).', $this->domain)
						),
						'input_sql_limit' => array(
							'size' => 5,
							'label' => __('Only get this many items when querying from Database. '
								. 'This is to make sure we are not running too heavy queries.', $this->domain)
						),
						'input_oldest' => array(
							'size' => 3,
							'label' => '&mdash;'
						),
						'input_cache_age' => array(
							'size' => 5,
							'label' => '&mdash;'
						)
					),
					'inline_fields' => array(
						'input_cache_age' => array('select_time_type' => 'select')
					),
					'inline' => array(
						'cb_enable_autogen' =>  '<br /><br />'
					),
					'container' => array(
						'heading_submit' => array(
							'<em>'
							. sprintf(__('Submit your <a href="%s" target="_blank">sitemapindex</a> '
								. 'to major search engines like <a href="%s" target="_blank">Google</a>, '
								. '<a href="%s" target="_blank">Bing</a>.', $this->domain),
								$this->options['input_sitemap_url'],
								'https://www.google.com/webmasters/tools/home?hl=en',
								'http://www.bing.com/toolbox/webmasters/')
							. ' '
							. sprintf(__('Only the sitemapindex needs to be submitted '
								. 'as search engines will automatically recognize other included sitemaps. '
								. 'More info can be found <a href="%s">here</a>.', $this->domain),
								'https://support.google.com/webmasters/answer/75712?hl=en&ref_topic=4581190')
							. '</em>',
							$this->get_logs(true)
						)
					)
				);

				foreach ($this->frequencies as $freq)
					$changefreq[ucfirst($freq)] = $freq;

				$form['select']['select_default_freq'] = $changefreq;

				// Get the options
				$options = $bwp_option_page->get_options(array(
					'input_item_limit',
					'input_split_limit_post',
					'input_alt_module_dir',
					'input_cache_dir',
					'input_sql_limit',
					'input_cache_age',
					'input_custom_xslt',
					'input_exclude_post_type',
					'input_exclude_taxonomy',
					'enable_gmt',
					'enable_robots',
					'enable_xslt',
					'enable_cache',
					'enable_cache_auto_gen',
					'enable_stats',
					'enable_credit',
					'enable_sitemap_split_post',
					'enable_global_robots',
					'enable_sitemap_date',
					'enable_sitemap_taxonomy',
					'enable_sitemap_external',
					'enable_sitemap_author',
					'enable_sitemap_site',
					'enable_gzip',
					'enable_ping',
					'enable_ping_google',
					'enable_ping_bing',
					'enable_log',
					'enable_debug',
					'enable_debug_extra',
					'select_time_type',
					'select_default_freq',
					'select_default_pri',
					'select_min_pri'
				), $this->options);

				if (!$this->is_normal_admin())
				{
					add_filter('bwp_option_submit_button', array($this, 'add_flush_cache_buttons'));

					if (isset($_POST['flush_cache']))
					{
						check_admin_referer($page);
						$this->_admin_flush_cache();
					}
				}

				// Get option from the database
				$options = $bwp_option_page->get_db_options($page, $options);

				// Get dynamic options
				if (isset($_POST['submit_' . $bwp_option_page->get_form_name()]))
				{
					check_admin_referer($page);

					$ept = array();
					$etax = array();

					foreach ($_POST as $o => $v)
					{
						if (strpos($o, 'ept_') === 0)
							$ept[] = trim(str_replace('ept_', '', $o));
						else if (strpos($o, 'etax_') === 0)
							$etax[] = trim(str_replace('etax_', '', $o));
					}

					$options['input_exclude_post_type'] = implode(',', $ept);
					$options['input_exclude_taxonomy'] = implode(',', $etax);
				}

				// Build dynamic options
				$post_types = get_post_types(array('public' => true), 'objects');
				$taxonomies = get_taxonomies(array('public' => true), '');

				$exclude_options = array(
					'post_types' => explode(',', $options['input_exclude_post_type']),
					'taxonomies' => explode(',', $options['input_exclude_taxonomy'])
				);

				$dynamic_options = array();

				foreach ($post_types as $post_type)
				{
					if ('attachment' == $post_type->name)
						continue;

					$key = 'ept_' . $post_type->name;

					$form['sec_post'][] = array('checkbox', 'name' => $key);
					$form['checkbox'][$key] = array(__($post_type->label) => $key);

					if (in_array($post_type->name, $exclude_options['post_types']))
						$dynamic_options[$key] = 'yes';
					else
						$dynamic_options[$key] = '';
				}

				foreach ($taxonomies as $taxonomy)
				{
					if ('post_format' == $taxonomy->name)
						continue;

					$key = 'etax_' . $taxonomy->name;

					$form['sec_tax'][] = array('checkbox', 'name' => $key);
					$form['checkbox'][$key] = array(__($taxonomy->label) => $key);

					if (in_array($taxonomy->name, $exclude_options['taxonomies']))
						$dynamic_options[$key] = 'yes';
					else
						$dynamic_options[$key] = '';
				}

				$option_formats = array(
					'input_item_limit'       => 'int',
					'input_split_limit_post' => 'int',
					'input_sql_limit'        => 'int',
					'input_cache_age'        => 'int',
					'select_time_type'       => 'int'
				);
				$option_ignore = array(
					'input_cache_dir',
					'input_exclude_post_type',
					'input_exclude_taxonomy'
				);

				$option_super_admin = $this->site_options;
			}
			elseif ($page == BWP_GXS_GOOGLE_NEWS)
			{
				$bwp_option_page->set_current_tab(2);

				// Save news categories settings
				if (isset($_POST['submit_bwp_gxs_google_news']))
				{
					check_admin_referer($page);

					// News cats & News genres
					$news_cats = array();
					$news_genres = array();
					$categories = get_categories(array('hide_empty' => 0));
					foreach ($categories as $category)
					{
						if (!empty($_POST[$category->slug]))
							$news_cats[] = $category->term_id;
						if (isset($_POST[$category->slug . '_genres']) && is_array($_POST[$category->slug . '_genres']))
						{
							$genres = $_POST[$category->slug . '_genres'];
							$genres_string = array();
							foreach ($genres as $genre)
								$genres_string[] = trim($genre);
							$news_genres['cat_' . $category->term_id] = implode(', ', $genres_string);
						}
					}
					$this->options['select_news_cats'] = implode(',', $news_cats);
					$this->options['input_news_genres'] = $news_genres;
				}

				$form = array(
					'items' => array(
						'heading',
						'checkbox',
						'checkbox',
						'checkbox',
						'checkbox',
						'select',
						'heading',
						'select'
					),
					'item_labels' => array
					(
						__('What is a Google News Sitemap?', $this->domain),
						__('Enable this module?', $this->domain),
						__('Enable Multi-category Mode?', $this->domain),
						__('Ping Search Engines when you publish a news article?', $this->domain),
						__('Use keywords in News Sitemap?', $this->domain),
						__('News Sitemap\'s language', $this->domain),
						__('News Categories', $this->domain),
						__('This module will', $this->domain)
					),
					'item_names' => array(
						'h1',
						'cb1',
						'cb4',
						'cb3',
						'cb2',
						'select_news_lang',
						'h2',
						'select_news_cat_action'
					),
					'heading' => array(
						'h1' => __('A Google News Sitemap is a file that allows you to control which content you submit to Google News. By creating and submitting a Google News Sitemap, you\'re able to help Google News discover and crawl your site\'s articles &mdash; <em>http://support.google.com/</em>', $this->domain),
						'h2' => __('<em>Below you will be able to choose what categories to use (or not use) in the news sitemap. You can also assign genres to a specific category.</em>', $this->domain)
					),
					'post' => array(
						'select_news_cat_action' => __('below selected categories in the news sitemap.', $this->domain)
					),
					'select' => array(
						'select_news_lang' => array(
							/* http://www.loc.gov/standards/iso639-2/php/code_list.php */
							__('English', $this->domain)               => 'en',
							__('Arabic', $this->domain)                => 'ar',
							__('Chinese (simplified)', $this->domain)  => 'zh-cn',
							__('Chinese (traditional)', $this->domain) => 'zh-tw',
							__('Dutch', $this->domain)                 => 'nl',
							__('French', $this->domain)                => 'fr',
							__('German', $this->domain)                => 'de',
							__('Hebrew', $this->domain)                => 'he',
							__('Hindi', $this->domain)                 => 'hi',
							__('Italian', $this->domain)               => 'it',
							__('Japanese', $this->domain)              => 'ja',
							__('Norwegian', $this->domain)             => 'no',
							__('Portuguese', $this->domain)            => 'pt',
							__('Polish', $this->domain)                => 'pl',
							__('Russian', $this->domain)               => 'ru',
							__('Spanish', $this->domain)               => 'es',
							__('Turkish', $this->domain)               => 'tr',
							__('Vietnamese', $this->domain)            => 'vi'
						),
						'select_news_cat_action' => array(
							__('include', $this->domain) => 'inc',
							__('exclude', $this->domain) => 'exc'
						),
						'select_news_keyword_type' => array(
							__('news categories', $this->domain) => 'cat',
							__('news tags', $this->domain) => 'tag'
						)
					),
					'input' => array(
					),
					'checkbox' => array(
						'cb1' => array(__('A new <code>post_google_news.xml</code> sitemap will be added to the main <code>sitemapindex.xml</code>.', $this->domain) => 'enable_news_sitemap'),
						'cb2' => array(__('Keywords are derived from', $this->domain) => 'enable_news_keywords'),
						'cb3' => array(__('This ping works separately from the sitemapindex ping, and only occurs when you publish an article in one of the news categories set below.', $this->domain) => 'enable_news_ping'),
						'cb4' => array(__('This mode is meant for News Blogs that have posts assigned to more than one categories. It is an advanced feature and should only be enabled if you do have similar blogs.', $this->domain) => 'enable_news_multicat')
					),
					'inline_fields' => array(
						'cb2' => array('select_news_keyword_type' => 'select')
					),
					'post' => array(
						'select_news_keyword_type' => __('. Do <strong>NOT</strong> use news tags if your news sitemap contains a lot of posts as it can be very inefficient to do so. This will be improved in future versions.', $this->domain)
					),
					'container' => array(
						'select_news_cat_action' => $this->get_news_cats()
					)
				);

				// Get the options
				$options = $bwp_option_page->get_options(array(
					'enable_news_sitemap',
					'enable_news_ping',
					'enable_news_keywords',
					'enable_news_multicat',
					'select_news_lang',
					'select_news_keyword_type',
					'select_news_cat_action',
					'select_news_cats',
					'input_news_genres'
				), $this->options);

				// Get option from the database
				$options = $bwp_option_page->get_db_options($page, $options);
				$options['select_news_cats'] = $this->options['select_news_cats'];
				$options['input_news_genres'] = $this->options['input_news_genres'];

				$option_ignore = array(
					'select_news_cats',
					'input_news_genres'
				);

				$option_formats = array();

				$option_super_admin = $this->site_options;
			}
			elseif ($page == BWP_GXS_STATS)
			{
				$bwp_option_page->set_current_tab(3);

				if ($this->_is_log_empty || 'yes' != $this->options['enable_log'])
				{
					// no log is found, or logging is disabled, hide sidebar to save space
					add_filter('bwp_info_showable', function() { return false;});
				}

				// Clear logs = @since 1.1.0
				if (isset($_POST['clear_log']) && !$this->is_normal_admin())
				{
					check_admin_referer($page);
					$this->logs = array('log' => array(), 'sitemap' => array());
					$this->commit_logs();
					$this->add_notice('<strong>' . __('Notice', $this->domain) . ':</strong> ' . __("All logs have been cleared successfully!", $this->domain));
				}

				$form = array(
					'items' => array(
						'heading',
					),
					'item_labels' => array
					(
						__('Sitemap Generator\'s Log', $this->domain),
					),
					'item_names' => array(
						'h3',
					),
					'heading' => array(
						'h3' => 'yes' == $this->options['enable_log']
							? '<em>'
								. __('Below are details on how your sitemaps are generated '
								. 'including <span style="color: #999999;">notices</span>, '
								. '<span style="color: #FF0000;">errors</span> and '
								. '<span style="color: #009900;">success messages</span>.', $this->domain)
								. '</em>'
							: '<em>'
								. __('Logging is not currently enabled. '
								. 'You can enable this feature by checking '
								. '"Enable sitemap log" in <strong>XML Sitemaps >> Sitemap log & debug</strong>.', $this->domain)
								. '</em>',
					),
					'container' => array(
						'h3' => 'yes' == $this->options['enable_log'] ? $this->get_logs() : '',
					)
				);

				// Add a clear log button - @since 1.1.0
				if (!$this->is_normal_admin())
					add_filter('bwp_option_submit_button', array($this, 'add_clear_log_button'));

				// Get the options
				$options = array();

				// Get option from the database
				$options = $bwp_option_page->get_db_options($page, $options);
				$option_ignore = array('input_update_services');
				$option_formats = array();

				// [WPMS Compatible]
				$option_super_admin = $this->site_options;
			}
		}

		// Get option from user input
		if ((isset($_POST['submit_' . $bwp_option_page->get_form_name()])
				|| isset($_POST['save_flush_cache']))
			&& isset($options) && is_array($options)
		) {
			check_admin_referer($page);

			$need_flushed = false;

			foreach ($options as $key => &$option)
			{
				$pre_option = $option;
				// Get rid of options that do not have a key
				if (preg_match('/^[0-9]+$/i', $key))
				{
					unset($options[$key]);
					continue;
				}
				// [WPMS Compatible]
				if ($this->is_normal_admin() && in_array($key, $option_super_admin))
				{}
				else if (in_array($key, $option_ignore))
				{}
				else
				{
					if (isset($_POST[$key]))
						$bwp_option_page->format_field($key, $option_formats);
					if (!isset($_POST[$key]))
						$option = '';
					else if (isset($option_formats[$key]) && 0 == $_POST[$key] && 'int' == $option_formats[$key])
						$option = 0;
					else if (isset($option_formats[$key]) && empty($_POST[$key]) && 'int' == $option_formats[$key])
						$option = $this->options_default[$key];
					else if (!empty($_POST[$key]))
						$option = trim(stripslashes($_POST[$key]));
					else
						$option = $this->options_default[$key];
					// Mark that we need to flush rewrite rules
					if (false !== strpos($key, 'enable_sitemap_') && $pre_option != $option)
						$need_flushed = true;
				}
			}

			update_option($page, $options);

			// Flush rewrite rules if needed
			if ($need_flushed)
				self::_flush_rewrite_rules();

			// [WPMS Compatible]
			if (!$this->is_normal_admin())
				update_site_option($page, $options);

			// Update options successfully
			$this->add_notice(__("All options have been saved.", $this->domain));

			// flush cache if needed
			if (isset($_POST['save_flush_cache']))
				$this->_admin_flush_cache();
		}

		// [WPMS Compatible]
		/* if (!$this->is_multisite() && $page == BWP_GXS_OPTION_GENERATOR) */
		/* 	$bwp_option_page->kill_html_fields($form, array(14)); */

		if ($this->is_normal_admin())
		{
			switch ($page)
			{
				case BWP_GXS_OPTION_GENERATOR:
					$bwp_option_page->kill_html_fields($form, array(9,10,13,14,18,19,20,21,22,23,24,25));
				break;

				case BWP_GXS_STATS:
					$bwp_option_page->kill_html_fields($form, array(3,4,5,6,7,8));
					add_filter('bwp_option_submit_button', create_function('', 'return "";'));
				break;
			}
		}

		if (!@file_exists($this->options['input_cache_dir'])
			|| !@is_writable($this->options['input_cache_dir']))
		{
			$this->add_notice(
				'<strong>' . __('Warning') . ':</strong> '
				. __('Cache directory does not exist or is not writable. '
				. 'Please read more about directory permission '
				. '<a href="http://www.zzee.com/solutions/unix-permissions.shtml">here</a> (Unix).', $this->domain)
			);
		}

		// Assign the form and option array
		$bwp_option_page->init($form, $options + $dynamic_options, $this->form_tabs);

		// Build the option page
		echo $bwp_option_page->generate_html_form();
	}

	public function add_flush_cache_buttons($button)
	{
		$button = str_replace(
			'</p>',
			'&nbsp; <input type="submit" class="button-secondary action" name="save_flush_cache" '
			. 'value="' . __('Save Changes and Flush Cache', $this->domain) . '" />'
			. '&nbsp; <input type="submit" class="button-secondary action" name="flush_cache" '
			. 'value="' . __('Flush Cache', $this->domain) . '" />',
			$button
		);

		return $button;
	}

	public function add_clear_log_button($button)
	{
		$button = str_replace(
			'</p>',
			'&nbsp; <input type="submit" class="button-secondary action" name="clear_log" value="'
			. __('Clear All Logs', $this->domain) . '" /></p>',
			$button
		);

		return $button;
	}

	public function flush_cache()
	{
		$deleted = 0;
		$dir     = trailingslashit($this->options['input_cache_dir']);

		if (is_dir($dir))
		{
			if ($dh = opendir($dir))
			{
				while (($file = readdir($dh)) !== false)
				{
					if (preg_match('/^gxs_[a-z0-9]+\.(xml|xml\.gz)$/i', $file))
					{
						@unlink($dir . $file);
						$deleted++;
					}
				}

				closedir($dh);
			}
		}

		return $deleted;
	}

	public function get_options()
	{
		return $this->options;
	}

	private static function _format_header_time($time)
	{
		return gmdate('D, d M Y H:i:s \G\M\T', (int) $time);
	}

	private static function _get_current_time()
	{
		return current_time('timestamp');
	}

	/**
	 * Flushes sitemap cache inside admin area
	 *
	 * @since 1.2.4
	 * @access private
	 */
	private function _admin_flush_cache()
	{
		if ($deleted = $this->flush_cache())
		{
			$this->add_notice(
				'<strong>' . __('Notice', $this->domain) . ':</strong> '
				. sprintf(
					__('<strong>%d</strong> cached sitemaps have '
					. 'been flushed successfully!', $this->domain),
					$deleted)
			);

			return true;
		}
		else
		{
			$this->add_notice(
				'<strong>' . __('Notice', $this->domain) . ':</strong> '
				. __('Could not delete any cached sitemaps. '
				. 'Please manually check the cache directory.', $this->domain)
			);

			return false;
		}
	}

	/**
	 * Normalize path separator in different environments
	 *
	 * @access private
	 */
	private function _normalize_path_separator($path = '')
	{
		return str_replace('\\', '/', $path);
	}

	/**
	 * Displays sitemap generation error with an error code
	 *
	 * @since 1.2.4
	 * @access private
	 */
	private function _die($message, $error_code)
	{
		wp_die(__('<strong>BWP Google XML Sitemaps Error:</strong> ', $this->domain)
			. $message, __('BWP Google XML Sitemaps Error', $this->domain),
			array('response' => $error_code)
		);
	}

	public function commit_logs()
	{
		update_option(BWP_GXS_LOG, $this->logs);
	}

	public function do_log($message, $error = true, $sitemap = false)
	{
		/* _deprecated_function(__FUNCTION__, '1.2.4', 'BWP_SIMPLE_GXS::log_message'); */
		$this->log_message($message, $error, $sitemap);
	}

	public function log_message($message, $error = true, $sitemap = false)
	{
		$time = self::_get_current_time();

		$debug_message = $this->_debug_extra
			? __('Debug extra was on', $this->domain)
			: __('Debug was on', $this->domain);

		$debug = $this->_debug ? ' (' . $debug_message . ')' : '';

		if (!$sitemap && 'yes' == $this->options['enable_log']
			&& !empty($message)
		) {
			$this->logs['log'][] = array(
				'log'   => $message . $debug,
				'time'  => $time,
				'error' => $error
			);
		}
		elseif (!is_bool($sitemap))
		{
			$this->logs['sitemap'][$sitemap] = array(
				'time' => $time,
				'url'  => $sitemap
			);
		}
	}

	public function elog($message, $die = false, $error_code = 404)
	{
		$this->log_error($message, $die, $error_code);
	}

	public function log_error($message, $die = false, $error_code = 404)
	{
		$this->log_message($message);

		if (true == $die)
		{
			$this->commit_logs();
			$this->_die($message, $error_code);
		}
	}

	public function slog($message)
	{
		$this->log_success($message);
	}

	public function log_success($message)
	{
		$this->log_message($message, false);
	}

	public function nlog($message)
	{
		$this->log_notice($message);
	}

	public function log_notice($message)
	{
		$this->log_message($message, 'notice');
	}

	public function smlog($url)
	{
		$this->log_sitemap($url);
	}

	public function log_sitemap($url)
	{
		$this->log_message('', false, $url);
	}

	public function get_sitemap_logs()
	{
		$logs = $this->logs['sitemap'];

		if (!$logs || !is_array($logs) || 0 == sizeof($logs))
			return false;

		$return = array();

		foreach ($logs as $log)
			$return[$log['url']] = $log['time'];

		return $return;
	}

	public function get_logs($sitemap = false)
	{
		$logs = !$sitemap ? $this->logs['log'] : $this->logs['sitemap'];

		if (!$logs || !is_array($logs) || 0 == sizeof($logs))
		{
			return $sitemap
				? '<em>' . __('It appears that no sitemap has been generated yet, '
					. 'or you have recently cleared the sitemap log.', $this->domain)
					. '</em>'
				: strtoupper(__('No log yet!', $this->domain)) . "\n";
		}

		$log_class = !$sitemap ? 'bwp-gxs-log bwp-gxs-log-big' : 'bwp-gxs-log bwp-gxs-log-small';
		$log_str   = !$sitemap
			? '<li class="bwp-clear" style="margin-top: 5px; line-height: 1.7;">'
				. '<span style="float: left; margin-right: 5px;">%s &mdash;</span> '
				. '<span style="color: #%s;">%s</span></li>'
			: '<span style="margin-top: 5px; display: inline-block;">'
				. __('<a href="%s" target="_blank">%s</a> was generated on <strong>%s</strong>.', $this->domain)
				. '</span><br />';

		$output = $sitemap
			? '<span style="display:inline-block; margin-bottom: 7px;"><em>'
				. __('Below you can find a list of generated sitemaps:', $this->domain)
				. '</em></span>'
				. '<br />'
			: '';

		$output .= '<ul class="' . $log_class . '">' . "\n";

		if (!$sitemap)
		{
			krsort($logs);
		}
		else
		{
			$log_time = array();

			foreach ($logs as $key => $row)
				$log_time[$key] = $row['time'];

			array_multisort($log_time, SORT_DESC, $logs);
		}

		foreach ($logs as $log)
		{
			if (isset($log['error']))
			{
				$color = !is_bool($log['error']) && 'notice' == $log['error'] ? '999999' : '';

				if ('' ==  $color)
					$color = (!$log['error']) ? '009900' : 'FF0000';

				/* translators: date format, see http://php.net/date */
				$output .= sprintf($log_str,
					date(__('M j, Y : H:i:s', $this->domain), $log['time']),
					$color, $log['log']) . "\n";
			}
			else
			{
				// @since 1.1.5 - check for mapped domain
				global $wpdb, $blog_id;

				if (!empty($wpdb->dmtable) && !empty($wpdb->blogs) && self::is_multisite())
				{
					// @todo 1.3.0 recheck whether this is needed
					$mapped_domain = $wpdb->get_var($wpdb->prepare('
						SELECT wpdm.domain as mapped_domain
						FROM ' . $wpdb->blogs . ' wpblogs
						LEFT JOIN ' . $wpdb->dmtable . ' wpdm
							ON wpblogs.blog_id = wpdm.blog_id AND wpdm.active = 1
						WHERE wpblogs.public = 1 AND wpblogs.spam = 0
							AND wpblogs.deleted = 0 AND wpblogs.blog_id = %d', $blog_id
					));
				}

				// default to the main site's scheme
				$home = @parse_url(home_url());

				$sitemap_struct = !empty($mapped_domain)
					? str_replace($home['host'],
						str_replace(array('http', 'https'), '', $mapped_domain),
						$this->options['input_sitemap_struct'])
					: $this->options['input_sitemap_struct'];

				$sitemap_struct = sprintf($sitemap_struct, $log['url']);

				$output .= sprintf($log_str,
					$sitemap_struct,
					$log['url'],
					date(__('M j, Y : H:i:s', $this->domain),
					$log['time'])) . "\n";
			}
		}

		return $output . '</ul>' . "\n";
	}

	public function do_robots($output, $public)
	{
		global $blog_id, $wpdb;

		if ('0' == $public)
			return $output;

		if ((defined('SUBDOMAIN_INSTALL') && true == SUBDOMAIN_INSTALL)
			|| (isset($blog_id) && 1 == $blog_id)
		) {
			$output .= "\n";
			$output .= 'Sitemap: ' . $this->options['input_sitemap_url'];
			$output .= "\n";
		}

		// Add all other sitemapindex within the network into the primary blog's robots.txt,
		// except for ones that have their domains mapped
		if ($this->is_multisite() && 'yes' == $this->options['enable_global_robots']
			&& isset($blog_id) && 1 == $blog_id
		) {
			$blogs = empty($wpdb->dmtable)
				? $wpdb->get_results("SELECT * FROM $wpdb->blogs WHERE public = 1 AND spam = 0 AND deleted = 0")
				: $wpdb->get_results('SELECT wpdm.domain as mapped_domain, wpblogs.*
					FROM ' . $wpdb->blogs . ' wpblogs
					LEFT JOIN ' . $wpdb->dmtable . ' wpdm
						ON wpblogs.blog_id = wpdm.blog_id
						AND wpdm.active = 1
					WHERE wpblogs.public = 1
						AND wpblogs.spam = 0
						AND wpblogs.deleted = 0');

			$num_sites = 0;

			foreach ($blogs as $blog)
			{
				if (1 == $blog->blog_id)
					continue;

				$scheme      = is_ssl() ? 'https://' : 'http://';
				$path        = rtrim($blog->path, '/');
				$blog_domain = empty($blog->mapped_domain) ? $blog->domain . $path : '';

				if (!empty($blog_domain))
				{
					$output .= 'Sitemap: ' . str_replace(home_url(),
						$scheme . $blog_domain,
						$this->options['input_sitemap_url']) . "\n";

					$num_sites++;
				}
			}

			if (!empty($num_sites))
				$output .= "\n";
		}

		return $output;
	}

	private function get_news_cats()
	{
		// News categories
		$news_cats  = explode(',', $this->options['select_news_cats']);
		$categories = get_categories(array('hide_empty' => 0));

		// News genres
		$news_genres = $this->options['input_news_genres'];

		// Genres taken from here: http://support.google.com/news/publisher/bin/answer.py?hl=en&answer=93992
		$genres = array(
			'PressRelease',
			'Satire',
			'Blog',
			'OpEd',
			'Opinion',
			'UserGenerated'
		);

		$return  = '<table class="bwp-table">' . "\n";
		$return .= '<thead>' . "\n"
			. '<tr><th><span>#</span></th><th><span>'
			. __('Category\'s name', $this->domain) . '</span></th><th>'
			. sprintf(__('<span>Genres used for this category</span>'
				. ' (more info <a href="%s" target="_blank">here</a>)', $this->domain),
				'http://support.google.com/news/publisher/bin/answer.py?hl=en&answer=93992')
			. '</th></tr>' . "\n"
			. '</thead>';
		$return .= '<tbody>' . "\n";

		foreach ($categories as $category)
		{
			$return .= '<tr>' . "\n";

			$genres_cbs = '';

			foreach ($genres as $genre)
			{
				$checked = '';

				if (isset($news_genres['cat_' . $category->term_id]))
				{
					$genres_ary = explode(', ', $news_genres['cat_' . $category->term_id]);
					$checked    = in_array($genre, $genres_ary) ? ' checked="checked" ' : '';
				}

				$genres_cbs .= '<input type="checkbox" '
					. 'name="' . esc_attr($category->slug) . '_genres[]" '
					. 'value="' . $genre . '"' . $checked . '/> '
					. $genre . ' &nbsp;&nbsp;&nbsp;&nbsp;';
			}

			$checked = in_array($category->term_id, $news_cats) ? ' checked="checked" ' : '';

			$return .= '<td><input type="checkbox" name="' . esc_attr($category->slug) . '" '
				. 'value="' . esc_attr($category->slug) . '"' . $checked . ' /></td>' . "\n"
				. '<td class="bwp_gxs_news_cat_td">' . esc_html($category->name) . '</td>' . "\n"
				. '<td>' . $genres_cbs . '</td>' . "\n";

			$return .= '</tr>' . "\n";
		}

		$return .= '</tbody>' . "\n";
		$return .= '</table>' . "\n";

		return $return;

	}

	/**
	 * Redirect to correct domain
	 *
	 * This plugin generates sitemaps dynamically and exits before WordPress
	 * does any canonical redirection. This function makes sure non-www domain
	 * is redirected and vice versa.
	 *
	 * @since 1.0.1
	 * @access private
	 */
	private function _canonical_redirect($sitemap_name)
	{
		$requested_url  = is_ssl() ? 'https://' : 'http://';
		$requested_url .= $_SERVER['HTTP_HOST'];
		$requested_url .= $_SERVER['REQUEST_URI'];

		$original = @parse_url($requested_url);
		if (false === $original)
			return;

		// www.example.com vs example.com
		$user_home = @parse_url(home_url());
		if (!empty($user_home['host']))
			$host = $user_home['host'];
		else
			return;

		if (strtolower($original['host']) == strtolower($host)
			|| (strtolower($original['host']) != 'www.' . strtolower($host)
			&& 'www.' . strtolower($original['host']) != strtolower($host))
		) {
			$host = $original['host'];
		}
		else
		{
			wp_redirect(sprintf($this->options['input_sitemap_struct'], $sitemap_name), 301);
			exit;
		}
	}

	/**
	 * A convenient function to add wanted modules or sub modules
	 *
	 * When you filter the 'bwp_gxs_modules' hook it is recommended that you
	 * use this function.
	 *
	 * @access public
	 */
	public function add_module($module, $sub_module = '')
	{
		// Make sure the names are well-formed
		$module = preg_replace('/[^a-z0-9-_\s]/ui', '', $module);
		$module = trim(str_replace(' ', '_', $module));

		$sub_module = preg_replace('/[^a-z0-9-_\s]/ui', '', $sub_module);
		$sub_module = trim(str_replace(' ', '_', $sub_module));

		if (empty($sub_module))
		{
			if (!isset($this->modules[$module]))
			{
				$this->modules[$module] = array();
			}

			return;
		}

		if (!isset($this->modules[$module])
			|| !is_array($this->modules[$module])
		) {
			$this->modules[$module] = array($sub_module);
		}
		else if (!in_array($sub_module, $this->modules[$module]))
		{
			$this->modules[$module][] = $sub_module;
		}
	}

	/**
	 * A convenient function to remove unwanted modules or sub modules
	 *
	 * When you filter the 'bwp_gxs_modules' hook it is recommended that you use this function.
	 *
	 * @access public
	 */
	public function remove_module($module = '', $sub_module = '')
	{
		if (empty($module) || !isset($this->modules[$module]))
			return false;

		if (empty($sub_module))
		{
			unset($this->modules[$module]);
		}
		else
		{
			$module     = trim($module);
			$sub_module = trim($sub_module);
			$temp       = $this->modules[$module];

			foreach ($temp as $key => $subm)
			{
				if ($sub_module == $subm)
				{
					unset($this->modules[$module][$key]);
					return false;
				}
			}
		}
	}

	/**
	 * Builds a list of sitemap modules that can be generated
	 *
	 * @access private
	 */
	private function _build_sitemap_modules()
	{
		$modules       = array();
		$this->modules = &$modules;

		// Site home URL sitemap - @since 1.1.5
		if ('yes' == $this->options['enable_sitemap_site'])
			$this->add_module('site');

		// Module exclusion list
		$excluded_post_types = explode(',', $this->options['input_exclude_post_type']);
		$excluded_taxonomies = explode(',', $this->options['input_exclude_taxonomy']);

		// Add public post types to module list
		$this->post_types = get_post_types(
			array('public' => true), 'objects'
		);

		foreach ($this->post_types as $post_type)
		{
			// Page will have its own
			if ('page' != $post_type->name && !in_array($post_type->name, $excluded_post_types))
				$modules['post'][] = $post_type->name;
		}

		// Google News module, @since 1.2.0
		if ('yes' == $this->options['enable_news_sitemap'])
			$this->add_module('post', 'google_news');

		// Add pages to module list
		if (!in_array('page', $excluded_post_types))
			$modules['page'] = array('page');

		// Add archive pages to module list
		if ('yes' == $this->options['enable_sitemap_date'])
			$modules['archive'] = array('monthly', 'yearly');

		// Add taxonomies to module list
		$this->taxonomies = get_taxonomies(array('public' => true), '');
		if ('yes' == $this->options['enable_sitemap_taxonomy'])
		{
			foreach ($this->taxonomies as $taxonomy)
			{
				if (!in_array($taxonomy->name, $excluded_taxonomies))
					$modules['taxonomy'][] = $taxonomy->name;
			}
		}

		// Remove some unnecessary sitemaps
		$this->remove_module('post', 'attachment');
		$this->remove_module('taxonomy', 'post_format');
		$this->remove_module('taxonomy', 'nav_menu');

		// Add / Remove modules based on users' preferences
		if ('yes' == $this->options['enable_sitemap_author'])
			$this->add_module('author');

		if ('yes' == $this->options['enable_sitemap_external'])
			$this->add_module('page', 'external');

		// Hook for a custom module list
		do_action('bwp_gxs_modules_built', $this->modules, $this->post_types, $this->taxonomies);

		return $this->modules;
	}

	private function _prepare_sitemap_modules()
	{
		$modules = $this->modules;
		$this->requested_modules = array();

		foreach ($modules as $module => $sub_modules)
		{
			if (sizeof($sub_modules) == 0)
			{
				$this->requested_modules[] = array(
					'module'      => $module,
					'sub_module'  => '',
					'module_name' => $module
				);

				continue;
			}

			foreach ($sub_modules as $sub_module)
			{
				$module_name = $module . '_' . $sub_module;

				if (isset($this->post_types[$sub_module]))
				{
					// this is a post type module
					if ('post' == $sub_module || 'page' == $sub_module || 'attachment' == $sub_module)
						$module_name = $module;
				}
				else if ('google_news' == $sub_module)
				{
					// this is the google news sitemap module
				}
				else if ('yes' == $this->options['enable_sitemap_taxonomy']
					&& isset($this->taxonomies[$sub_module])
				) {
					// this is a taxonomy sitemap module
				}
				else if (!empty($sub_module))
				{
					// any sitemap module that has a sub-module
				}

				$this->requested_modules[] = array(
					'module'      => $module,
					'sub_module'  => $sub_module,
					'module_name' => $module_name
				);
			}
		}
	}

	/**
	 * Gets module label to display in friendly log message
	 *
	 * This function needs updating whenever a new sitemap type (new module) is
	 * registered.
	 *
	 * @since 1.2.4
	 * @access private
	 */
	private function _get_module_label($module, $sub_module)
	{
		if ($module == 'post')
		{
			if ($sub_module == 'google_news')
			{
				return sprintf(
					__('Google news posts that are published within last 48 hours '
					. '(as per <a href="%s" target="_blank">Google\'s policy</a>)', $this->domain),
					'https://support.google.com/news/publisher/answer/74288?hl=en#sitemapguidelines'
				);
			}

			if (empty($sub_module))
				return __('Post');

			return $this->post_types[$sub_module]->labels->singular_name;
		}
		elseif ($module == 'page')
		{
			if ($sub_module == 'external')
				return __('External page', $this->domain);

			return __('Page');
		}
		elseif ($module == 'taxonomy')
		{
			return $this->taxonomies[$sub_module]->labels->singular_name;
		}
		elseif ($module == 'archive')
		{
			return __('Date archive', $this->domain);
		}
		elseif ($module == 'author')
		{
			return __('Author archive', $this->domain);
		}

		return false;
	}

	public function convert_module($module)
	{
		preg_match('/([a-z0-9]+)_([a-z0-9_-]+)$/iu', $module, $matches);

		if (0 == sizeof($matches))
			return false;
		else
			return $matches;
	}

	/**
	 * Serves sitemap when needed using correct sitemap module
	 *
	 * @access public
	 */
	public function request_sitemap($wp_query)
	{
		if (isset($wp_query->query_vars['gxs_module']))
		{
			// friendly sitemap url is used
			// sitemap module and sub-module are separated into two different
			// query variables
			$module     = $wp_query->query_vars['gxs_module'];
			$sub_module = isset($wp_query->query_vars['gxs_sub_module'])
				? $wp_query->query_vars['gxs_sub_module'] : '';

			if (!empty($module))
				$this->_load_sitemap_module($module, $sub_module);
		}
		else if (isset($wp_query->query_vars[$this->query_var_non_perma]))
		{
			// non-friendly sitemap url is used, i.e. http://example.com/?bwpsitemap=xxx
			$module        = $wp_query->query_vars[$this->query_var_non_perma];
			$parsed_module = $this->convert_module($module);

			if ($parsed_module && is_array($parsed_module))
				$this->_load_sitemap_module($parsed_module[1], $parsed_module[2]);
			else
				$this->_load_sitemap_module($module, '');
		}
	}

	/**
	 * Checks whether requested sitemap is a BWP sitemap
	 *
	 * @since 1.2.4
	 * @access private
	 */
	private static function _is_bwp_sitemap($sitemap_name)
	{
		$third_party_sitemaps = array(
			'sitemap_index',
			'post_tag-sitemap'
		);

		if (in_array($sitemap_name, $third_party_sitemaps))
			return false;

		return true;
	}

	/**
	 * Inits building some sitemap generation stats
	 *
	 * @since 1.2.4
	 * @access private
	 */
	private function _init_stats()
	{
		// track sitemap generation time
		timer_start();

		// number of queries used to generate a sitemap
		$this->build_stats['query'] = get_num_queries();
	}

	/**
	 * Inits the sitemap generation process
	 *
	 * @since 1.2.4
	 * @access private
	 */
	private function _init_sitemap_generation()
	{
		if (!$this->_debug)
		{
			// don't let error reporting messes up sitemap generation when
			// debug is off
			error_reporting(0);
		}

		$this->_init_stats();

		// don't let other instrusive plugins mess up our permalnks - @since 1.1.4
		remove_filter('post_link', 'syndication_permalink', 1, 3);
		remove_filter('page_link', 'suffusion_unlink_page', 10, 2);
	}

	private function _load_sitemap_from_cache($module_name, $sitemap_name)
	{
		if ('yes' != $this->options['enable_cache'] || $this->_debug)
		{
			// cache is not enabled or debug is enabled
			return false;
		}

		$cache_status = $this->cache->get_cache_status($module_name, $sitemap_name);

		if (!$cache_status)
		{
			// cache is invalid
			return false;
		}
		else if ($cache_status == '304')
		{
			// http cache can be used, we don't need to output anything except
			// for some headers
			$this->_send_headers(array_merge(
				array('status' => 304), $this->cache->get_headers()
			));

			return true;
		}
		else if ($cache_status == '200')
		{
			// file cache is ok, output the cached sitemap
			$this->_send_headers($this->cache->get_headers());

			$cache_file = $this->cache->get_cache_file();

			if ($this->_is_gzip_ok() && !self::is_gzipped())
			{
				// when server or script is not already gzipping, and gzip is
				// allowed, we simply read the cached file without any
				// additional compression because cached sitemap files are
				// stored as gzipped files.
				readfile($cache_file);
			}
			else
			{
				// if we can't use a gzipped file, decompress before reading it
				readgzfile($cache_file);
			}

			$this->log_success(sprintf(
				__('Successfully served a cached version of <em>%s.xml</em>.', $this->domain)
			, $sitemap_name));

			$this->commit_logs();

			return true;
		}
	}

	/**
	 * Puts the current sitemap output in cache
	 *
	 * @return bool|string bool cache file could not be written or read
	 *                     string cache file's modification timestamp
	 * @since 1.2.4
	 * @access private
	 */
	private function _cache_sitemap()
	{
		if ('yes' != $this->options['enable_cache'] || $this->_debug)
		{
			// cache is not enabled or debug is enabled
			return false;
		}

		if (!@is_writable($this->cache_directory))
		{
			$this->log_error(sprintf(
				__('Cache directory <strong>%s</strong> is not writable, '
				. 'no cache file was created.' , $this->domain),
				$this->cache_directory
			));

			return false;
		}

		$lastmod = $this->cache->write_cache($this->output);

		if (!$lastmod)
		{
			$this->log_error(sprintf(
				__('Could not write sitemap file to cache directory <strong>%s</strong>' , $this->domain),
			$this->cache_directory));

			return false;
		}

		return $lastmod;
	}

	/**
	 * Gets correct module file to generate a sitemap
	 *
	 * @access private
	 */
	private function _get_module_file($module_name, $sitemap_name, $is_submodule = false)
	{
		$module_dir        = trailingslashit($this->module_directory);
		$custom_module_dir = $this->custom_module_directory
			? trailingslashit($this->custom_module_directory)
			: '';

		$module_file     = ''; // path to module file
		$module_filename = $module_name . '.php'; // filename of the module

		if (!empty($custom_module_dir)
			&& @file_exists($custom_module_dir . $module_filename)
		) {
			// a module file exists at custom module directory
			$module_file = $custom_module_dir . $module_filename;

			$this->log_notice(sprintf(
				__('<em>%s.xml</em> will be served using module file <em>%s</em> '
				. 'in the custom module directory.', $this->domain)
			, $sitemap_name, $module_filename));
		}
		else if (@file_exists($module_dir . $module_filename))
		{
			// use module at default module directory
			$module_file = $module_dir . $module_filename;
		}
		else
		{
			if ($is_submodule)
			{
				// sub-module file is missing, use parent module file instead
				$this->log_notice(sprintf(
					__('<em>%s.xml</em> will be served using module file <em>%s</em>.', $this->domain)
				, $sitemap_name, preg_replace('/_.*(\.[a-z]+)/ui', '$1', $module_filename)));
			}
			else
			{
				// no module available, show an error
				$error_log = sprintf(
					__('<strong>%s</strong> can not be served because of '
					. 'a missing module file: <strong>%s</strong>.', $this->domain)
				, $sitemap_name, $module_filename);

				$this->log_error($error_log, true);
			}
		}

		return $module_file;
	}

	/**
	 * Locates correct sitemap module to serve requested sitemap
	 *
	 * @access private
	 */
	private function _load_sitemap_module($module, $sub_module)
	{
		$success       = false; // can we successfully serve the sitemap?
		$module_found  = false; // do we have a sitemap module as requested
		$module_loaded = false;

		$module      = stripslashes($module);
		$sub_module  = stripslashes($sub_module);
		$part        = 0;
		$module_name = ''; // the final module name used to generate requested sitemap

		// a full sitemap name consists of a module and a sub-module including
		// any split part (`_part1`, `_part2`, etc.) if any
		$sitemap_name = !empty($sub_module) ? $module . '_' . $sub_module : $module;

		if (!self::_is_bwp_sitemap($sitemap_name))
		{
			// not a BWP sitemap, return this handle to WordPress
			return false;
		}

		// make sure we're on the canonical domain to avoid styling issue
		$this->_canonical_redirect($sitemap_name);

		if ('yes' == $this->options['enable_sitemap_split_post']
			&& (preg_match('/_part([0-9]+)$/i', $sub_module, $matches)
				|| preg_match('/part([0-9]+)$/i', $sub_module, $matches))
		) {
			// Check whether or not splitting is enabled and the sub_module has a
			// 'part' part, if so we strip the part from sub-module name
			$sub_module = str_replace($matches[0], '', $sub_module);

			// save the requested part for later use
			$part = (int) $matches[1];
		}

		$modules = $this->_build_sitemap_modules();

		if ('sitemapindex' != $sitemap_name && isset($modules[$module]))
		{
			// the currently requested sitemap is not the sitemapindex, and a
			// sitemap module is available
			$module_found = true;

			if (!empty($sub_module))
			{
				// a sub-module is being requested, and found
				if (in_array($sub_module, $modules[$module]))
					$module_name  = $module . '_' . $sub_module;
				else
					$module_found = false;
			}
			else
			{
				$module_name = $module;
			}
		}
		else if ('sitemapindex' == $sitemap_name)
		{
			// this is the sitemapindex, use sitemapindex sitemap module
			$module_found = true;
			$module_name  = 'sitemapindex';
		}

		if (!$module_found)
		{
			// no module is available to handle requested sitemap
			$message = sprintf(
				__('Requested sitemap (<em>%s.xml</em>) '
				. 'was not found or not enabled.', $this->domain),
				$sitemap_name
			);

			// @since 1.2.4 log a notice instead of an error
			$this->log_notice($message);
			$this->commit_logs();

			// @since 1.2.4 return this handle to WordPress
			return false;
		}

		$this->_init_sitemap_generation();

		if ($this->_load_sitemap_from_cache($module_name, $sitemap_name))
		{
			// if sitemap can be loaded from cache, no need to do anything else
			exit;
		}

		// load the base module so other modules can extend it
		require_once dirname(__FILE__) . '/class-bwp-gxs-module.php';

		// global module data for later use
		$this->module_data = array(
			'module'       => $module,
			'sub_module'   => $sub_module,
			'module_key'   => $module_name, // leave this for back-compat
			'module_name'  => $module_name, // @since 1.2.4 this is the same as module_key
			'module_part'  => $part, // leave this for back-compat
			'part'         => $part,
			'sitemap_name' => $sitemap_name // @since 1.2.4 this is the actual sitemap name
		);

		if ('sitemapindex' != $sitemap_name)
		{
			// generating a regular sitemap
			$module_file = ''; // path to module file

			if (!empty($sub_module))
			{
				// try generating the sitemap using a sub-module
				if (!empty($this->module_map[$sub_module]))
				{
					// this module is mapped to use another module, no need to
					// update global module data
					$module_name = $module . '_' . $this->module_map[$sub_module];
				}

				$module_file = $this->_get_module_file($module_name, $sitemap_name, true);
			}

			if (empty($module_file))
			{
				// try again with parent module, no need to update global module data
				$module_name = $module;
				$module_file = $this->_get_module_file($module_name, $sitemap_name);
			}

			if (empty($module_file))
			{
				// no luck, let WordPress handles this page
				// reach here if gxs debug is off
				return false;
			}

			// load module data
			include_once $module_file;

			if (class_exists('BWP_GXS_MODULE_' . $module_name))
			{
				$class_name    = 'BWP_GXS_MODULE_' . $module_name;
				$module_object = new $class_name();

				$module_object->set_current_time();
				$module_object->build_sitemap_data();

				$module_data = $module_object->get_data();

				switch ($module_object->get_type())
				{
					case 'url':
						$success = $this->_generate_sitemap($module_data);
						break;
					case 'news':
						$success = $this->_generate_news_sitemap($module_data);
						break;
					case 'index':
						$success = $this->_generate_sitemap_index($module_data);
						break;
				}

				$module_loaded = true;
			}
		}
		else if ('sitemapindex' == $sitemap_name)
		{
			$module_file = $this->_get_module_file($module_name, $sitemap_name);

			// load module data
			include_once $module_file;

			if (class_exists('BWP_GXS_MODULE_INDEX'))
			{
				$this->_prepare_sitemap_modules(); // this should fill $this->requested_modules

				$module_object = new BWP_GXS_MODULE_INDEX($this->requested_modules);

				$module_object->set_current_time();
				$module_object->build_sitemap_data();

				$success = $this->_generate_sitemap_index($module_object->get_data());

				$module_loaded = true;
			}
		}

		$module_filename = $module_name . '.php';

		if (!$module_loaded)
		{
			// required module class can not be found so not loaded
			$this->log_error(
				sprintf(__('There is no class named <strong>%s</strong> '
					. 'in the module file <strong>%s</strong>.', $this->domain),
					'BWP_GXS_MODULE_' . strtoupper($module_name),
					$module_filename), true
			); // result in a WP die
		}

		if (true == $success)
		{
			// sitemap has been generated and is valid
			$this->_append_sitemap_stats();

			$lastmod = $this->_cache_sitemap();
			$lastmod = $lastmod ? $lastmod : time();
			$expires  = self::_format_header_time($lastmod + $this->cache_time);

			// send proper headers
			$this->_send_headers(array(
				'lastmod' => self::_format_header_time($lastmod),
				'expires' => $expires,
				'etag'    => md5($expires . bwp_gxs_get_filename($sitemap_name))
			));

			// display the requested sitemap
			$this->_display_sitemap();

			$this->log_success(sprintf(
				__('Successfully generated <em>%s.xml</em> using module file <em>%s</em>.', $this->domain)
			, $sitemap_name, $module_filename));

			$this->log_sitemap($sitemap_name);

			$this->commit_logs();

			exit;
		}
		else
		{
			// @todo display error message and die?
			$this->commit_logs();
		}
	}

	private function _send_headers($headers = array())
	{
		if (headers_sent($filename, $linenum))
		{
			// @since 1.2.4 if headers have already been sent, we can't send
			// these headers anymore so stop here but log an error
			$this->log_error(sprintf(
				__('<em>%s.xml</em> was successfully generated but '
				. 'could not be served properly because some '
				. 'headers have already been sent '
				. '(something was printed on line <strong>%s</strong> '
				. 'in file <strong>%s</strong>).', $this->domain),
				$this->module_data['sitemap_name'],
				$linenum,
				$filename
			));

			return false;
		}

		if ($this->_debug_extra)
		{
			// @since 1.2.4 when debug extra is turned on no headers should
			// be sent. Sitemap will be displayed as raw text output to avoid
			// Content Encoding Error. The raw text output can then be used to
			// find the cause of the encoding error.
			return false;
		}

		$content_types = array(
			'google' => 'text/xml',
			'yahoo'  => 'text/plain'
		);

		$default_headers = array(
			'status' => 200,
			'vary'   => 'Accept-Encoding'
		);

		$headers = wp_parse_args($headers, $default_headers);

		if ($this->_debug)
		{
			// if debug is on, send no cache headers
			nocache_headers();
		}
		else
		{
			// otherwise send proper cache headers
			header('Cache-Control: max-age=' . (int) $this->cache_time);
			header('Expires: ' . $headers['expires']);

			if (!empty($headers['etag']))
				header('Etag: ' . $headers['etag']);
		}

		if ($headers['status'] == 200)
		{
			// some headers are only needed when sending a 200 OK response
			if (!$this->_debug)
			{
				// only send a last modified header if debug is NOT on
				header('Last-Modified: ' . $headers['lastmod']);
			}

			header('Accept-Ranges: bytes');
			header('Content-Type: ' . $content_types['google'] . '; charset=UTF-8');

			if ($this->_is_gzip_ok())
				header('Content-Encoding: ' . self::_get_gzip_type());
		}

		header('Vary: ' . $headers['vary']);

		status_header($headers['status']);

		return true;
	}

	private function _display_sitemap()
	{
		// compress the output using gzip if needed, but only if no active
		// compressor is active
		if ($this->_is_gzip_ok() && !self::is_gzipped())
			echo gzencode($this->output, 6);
		else
			echo $this->output;
	}

	public static function is_gzipped()
	{
		if (ini_get('zlib.output_compression')
			|| ini_get('output_handler') == 'ob_gzhandler'
			|| in_array('ob_gzhandler', @ob_list_handlers()))
		{
			return true;
		}

		return false;
	}

	private function _is_gzip_ok()
	{
		if ($this->options['enable_gzip'] != 'yes')
			return false;

		if (headers_sent() || $this->_debug_extra)
			// headers sent or debug extra is on, which means we could not send
			// the encoding header, so gzip is not allowed
			return false;

		if (!empty($_SERVER['HTTP_ACCEPT_ENCODING'])
			&& (strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') !== false
				|| strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'x-gzip') !== false)
		) {
			return true;
		}
		else
		{
			return false;
		}
	}

	private static function _get_gzip_type()
	{
		if (strpos($_SERVER['HTTP_ACCEPT_ENCODING'],'gzip') !== false)
			return 'gzip';
		else if (strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'x-gzip') !== false)
			return 'x-gzip';

		return 'gzip';
	}

	private function _append_sitemap_stats()
	{
		if ('yes' != $this->options['enable_stats'])
			return false;

		$this->output .= $this->_get_sitemap_stats();
	}

	private function _get_sitemap_stats($type = '')
	{
		$time   = timer_stop(0, 3);
		$sql    = get_num_queries() - $this->build_stats['query'];
		$memory = size_format(memory_get_usage() - $this->build_stats['mem'], 2);

		if (empty($type))
			return "\n" . sprintf($this->templates['stats'], $time, $memory, $sql, $this->output_num);
		else
			echo "\n" . sprintf($this->templates['stats_cached'], $time, $memory, $sql);
	}

	private static function _is_local($url)
	{
		static $blog_url;

		if (empty($blog_url))
		{
			$home_url = home_url();
			$blog_url = @parse_url($home_url);
		}

		$url = @parse_url($url);
		if (false === $url)
			return false;

		if (isset($url['scheme']))
		{
			// if scheme is set for the url being checked, the url should be
			// local only when it shares the same host with blog's url

			// normalize all the hosts before comparing
			$url_host  = str_replace('https://', 'http://', $url['host']);
			$blog_host = str_replace('https://', 'http://', $blog_url['host']);

			// according to sitemap protocol the host must be exactly the same
			// @see http://www.sitemaps.org/protocol.html#location
			if (0 <> strcmp($url_host, $blog_host))
				return false;

			return true;
		}
		else
			return true;
	}

	private static function _is_url_valid($url)
	{
		$url = trim($url);

		if ('#' == $url || 0 !== strpos($url, 'http') || !self::_is_local($url))
			return false;

		return true;
	}

	private function _check_output($output)
	{
		if (empty($output) || 0 == sizeof($output))
		{
			// If output is empty we log it so the user knows what's going on,
			// and should die accordingly
			$error_message = sprintf(
				__('<em>%s.xml</em> does not have any item.', $this->domain),
				$this->module_data['sitemap_name']
			);

			$module_label = $this->_get_module_label($this->module_data['module'], $this->module_data['sub_module']);
			$module_guide = 'google_news' != $this->module_data['sub_module']
				? __('Enable/disable sitemaps via <em>BWP Sitemaps >> XML Sitemaps >> Sitemap Contents</em>.', $this->domain)
				: '';

			$error_message_admin_module = $module_label && current_user_can('manage_options')
				? ' ' . sprintf(
						__('There are no public <em>%s</em>.', $this->domain)
						. " $module_guide",
						$module_label)
				: ' ' . $module_guide;

			$error_message_admin = $this->module_data['sitemap_name'] == 'sitemapindex'
				? ' ' . __('Please make sure that you have at least one sitemap enabled '
					. 'in <em>BWP Sitemaps >> XML Sitemaps >> Sitemap Contents</em>.', $this->domain)
				: $error_message_admin_module;

			$this->log_error($error_message . $error_message_admin, true, 404); // die here
		}
		else
		{
			return true;
		}
	}

	private function _generate_sitemap_item($url, $priority = 1.0, $freq = 'always', $lastmod = 0)
	{
		$freq     = sprintf($this->templates['changefreq'], $freq);
		$priority = str_replace(',', '.', sprintf($this->templates['priority'], $priority));
		$lastmod  = !empty($lastmod) ? sprintf($this->templates['lastmod'], $lastmod) : '';

		if (!empty($url))
			return sprintf($this->templates['url'], $url, $lastmod, $freq, $priority);
		else
			return '';
	}

	private function _generate_sitemapindex_item($url = '', $lastmod = 0)
	{
		$lastmod = !empty($lastmod) ? sprintf($this->templates['lastmod'], $lastmod) : '';

		if (!empty($url))
			return sprintf($this->templates['sitemap'], $url, $lastmod);
		else
			return '';
	}

	private function _generate_news_sitemap_item($loc = '', $name = '', $lang = 'en',
		$genres = '', $pub_date = '', $title = '', $keywords = '')
	{
		$name     = sprintf($this->templates['news_name'], $name);
		$lang     = sprintf($this->templates['news_language'], $lang);
		$news_pub = sprintf($this->templates['news_publication'], $name, $lang);

		$genres   = !empty($genres) ? sprintf($this->templates['news_genres'], $genres) : '';
		$pub_date = sprintf($this->templates['news_pub_date'], $pub_date);
		$title    = sprintf($this->templates['news_title'], $title);

		$keywords = !empty($keywords) ? sprintf($this->templates['news_keywords'], $keywords) : '';

		return sprintf($this->templates['news'], $loc, $news_pub, $genres, $pub_date, $title, $keywords);
	}

	private function _get_credit()
	{
		$credit  = '<!-- Generated by BWP Google XML Sitemaps ' . $this->get_version()
			. ' (c) 2014 Khang Minh - betterwp.net' . "\n";

		$credit .= ' Plugin homepage: ' . $this->plugin_url . ' -->' . "\n";

		return $credit;
	}

	private function _generate_sitemap($urls)
	{
		if (!$this->_check_output($urls))
			return false;

		$xml  = '<' . '?xml version="1.0" encoding="UTF-8"?'.'>' . "\n";
		$xml .= !empty($this->xslt)
			? '<?xml-stylesheet type="text/xsl" href="' . $this->xslt . '"?>' . "\n\n"
			: '';
		$xml .= '<urlset xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"'
			. "\n\t" . 'xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9'
			. "\n\t" . 'http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd"'
			. "\n\t" . 'xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

		if ('yes' != $this->options['enable_xslt'] && 'yes' == $this->options['enable_credit'])
			$xml .= $this->_get_credit();

		foreach ($urls as &$url)
		{
			$url['location'] = !empty($url['location']) ? $url['location'] : '';

			if (empty($url['location']) || !self::_is_url_valid($url['location']))
				// location is empty or it is not valid for a sitemap
				continue;

			$url['lastmod'] = !empty($url['lastmod']) ? $url['lastmod'] : '';

			$url['freq'] = isset($url['freq'])
				&& in_array($url['freq'], $this->frequencies)
				? $url['freq']
				: $this->options['select_default_freq'];

			$url['priority'] = isset($url['priority'])
				&& $url['priority'] <= 1 && $url['priority'] > 0
				? $url['priority']
				: $this->options['select_default_pri'];

			$xml .= $this->_generate_sitemap_item(
				htmlspecialchars($url['location']), $url['priority'],
				$url['freq'], $url['lastmod']
			);

			$this->output_num++;
		}

		if (!$this->_check_output($this->output_num))
			return false;

		$xml .= "\n" . '</urlset>';

		$this->output = $xml;

		return true;
	}

	private function _generate_news_sitemap($urls)
	{
		if (!$this->_check_output($urls))
			return false;

		$xml  = '<' . '?xml version="1.0" encoding="UTF-8"?'.'>' . "\n";
		$xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"'
			. "\n\t" . 'xmlns:news="http://www.google.com/schemas/sitemap-news/0.9"'
			. "\n\t" . 'xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"'
			. "\n\t" . 'xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd'
			. "\n\t" . 'http://www.google.com/schemas/sitemap-news/0.9 http://www.google.com/schemas/sitemap-news/0.9/sitemap-news.xsd">'
			. "\n";

		if ('yes' == $this->options['enable_credit'])
			$xml .= $this->_get_credit();

		foreach ($urls as &$url)
		{
			$url['location'] = !empty($url['location']) ? $url['location'] : '';

			if (empty($url['location']) || !self::_is_url_valid($url['location']))
				// location is empty or it is not valid for a sitemap
				continue;

			$url['name']     = !empty($url['name'])
				? htmlspecialchars($url['name'])
				: apply_filters('bwp_gxs_news_name', htmlspecialchars(get_bloginfo('name')));

			$url['language'] = $this->options['select_news_lang'];
			$url['genres']   = !empty($url['genres']) ? $url['genres'] : '';

			$url['pub_date'] = !empty($url['pub_date']) ? $url['pub_date'] : '';
			$url['title']    = !empty($url['title']) ? htmlspecialchars($url['title']) : '';
			$url['keywords'] = !empty($url['keywords']) ? htmlspecialchars($url['keywords']) : '';

			$xml .= $this->_generate_news_sitemap_item(
				htmlspecialchars($url['location']),
				$url['name'],
				$url['language'],
				$url['genres'],
				$url['pub_date'],
				$url['title'],
				$url['keywords']
			);

			$this->output_num++;
		}

		if (!$this->_check_output($this->output_num))
			return false;

		$xml .= "\n" . '</urlset>';

		$this->output = $xml;

		return true;
	}

	private function _generate_sitemap_index($sitemaps)
	{
		if (!$this->_check_output($sitemaps))
			return false;

		$xml  = '<' . '?xml version="1.0" encoding="UTF-8"?'.'>' . "\n";
		$xml .= !empty($this->xslt_index)
			? '<?xml-stylesheet type="text/xsl" href="' . $this->xslt_index . '"?>' . "\n\n"
			: '';
		$xml .= '<sitemapindex xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"'
			. "\n\t" . 'xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9'
			. "\n\t" . 'http://www.sitemaps.org/schemas/sitemap/0.9/siteindex.xsd"'
			. "\n\t" . 'xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

		if ('yes' != $this->options['enable_xslt'] && 'yes' == $this->options['enable_credit'])
			$xml .= $this->_get_credit();

		foreach ($sitemaps as &$sitemap)
		{
			$sitemap['location'] = !empty($sitemap['location']) ? $sitemap['location'] : '';
			$sitemap['lastmod']  = !empty($sitemap['lastmod']) ? $sitemap['lastmod'] : '';

			$xml .= $this->_generate_sitemapindex_item(
				htmlspecialchars($sitemap['location']), $sitemap['lastmod']
			);

			$this->output_num++;
		}

		$xml .= "\n" . '</sitemapindex>';

		$this->output = $xml;

		return true;
	}

	public function ping_google_news($post)
	{
		if (empty($post->ID))
			return;

		// Get categories
		$is_news    = 'inc' == $this->options['select_news_cat_action'] ? false : true;
		$news_cats  = explode(',', $this->options['select_news_cats']);
		$categories = get_the_category($post->ID);

		foreach ($categories as $cat)
		{
			if (in_array($cat->term_id, $news_cats))
			{
				$is_news = 'inc' == $this->options['select_news_cat_action']
					? true : false;

				break;
			}
		}

		if ($is_news)
		{
			$this->_ping_sitemap = 'post_google_news';
			$this->ping();
		}
	}

	public function ping()
	{
		$time      = self::_get_current_time();
		$ping_data = get_option(BWP_GXS_PING);

		if (!$ping_data || !is_array($ping_data)
			|| isset($ping_data['data_pinged']['yahoo'])
			|| isset($ping_data['data_pinged']['ask'])
		) {
			// remove old data from yahoo and ask, to be removed in 1.3.0
			$ping_data = array(
				'data_pinged'      => array('google' => 0, 'bing' => 0),
				'data_last_pinged' => array('google' => 0, 'bing' => 0)
			);
		}

		foreach ($this->_ping_urls as $key => $service)
		{
			if ('yes' == $this->options['enable_ping_' . $key])
			{
				if ($time - $ping_data['data_last_pinged'][$key] > 86400)
				{
					// A day has gone, reset the count
					$ping_data['data_pinged'][$key] = 0;
					$ping_data['data_last_pinged'][$key] = $time;
				}

				if ($this->pings_per_day > $ping_data['data_pinged'][$key])
				{
					// Ping limit has not been reached
					$ping_data['data_pinged'][$key]++;

					$url = sprintf($service, urlencode(str_replace('&', '&amp;', sprintf(
						$this->options['input_sitemap_struct'],
						$this->_ping_sitemap)
					)));

					$response = wp_remote_post($url,
						array('timeout' => $this->ping_timeout)
					);

					if (is_wp_error($response))
					{
						$errno    = $response->get_error_code();
						$errorstr = $response->get_error_message();

						$this->log_error($errorstr);
					}
					else if (isset($response['response']))
					{
						$the_response = $response['response'];

						if (empty($the_response['code']))
						{
							$this->log_error(__('Unknown response code from search engines. Ping failed.', $this->domain));
						}
						else if (200 == $the_response['code'])
						{
							$this->log_success(sprintf(
								__('Pinged %s with %s successfully!', $this->domain), ucfirst($key),
								$this->_ping_sitemap . '.xml')
							);
						}
						else
						{
							$errno    = $the_response['code'];
							$errorstr = $the_response['message'];

							$this->log_error(sprintf(
								__('Error %s from %s', $this->domain), $errno, ucfirst($key))
								. ': ' . $errorstr
							);
						}
					}
				}
				else
				{
					$this->log_error(sprintf(
						__('Ping limit for today to %s has been reached, sorry!', $this->domain),
						ucfirst($key))
					);
				}
			}
		}

		update_option(BWP_GXS_PING, $ping_data);

		$this->commit_logs();
	}
}
