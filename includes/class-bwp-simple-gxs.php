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
	 * Whether or not debug mode is enabled
	 *
	 * @var bool
	 */
	public $debug = true;

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
	var $cache_time, $num_log = 25;
	var $sitemap_alias = array(), $use_permalink = true, $query_var_non_perma = '';

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
			'enable_php_clean'          => 'yes',
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
			'input_split_limit_post'    => 5000,
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
			'enable_ping',
			'enable_ping_google',
			'enable_ping_bing',
			/* 'enable_ping_ask', */
			'enable_gzip',
			'enable_php_clean',
			'enable_cache',
			'enable_cache_auto_gen',
			'input_cache_age',
			'input_alt_module_dir',
			'input_sql_limit',
			'input_cache_dir',
			'select_time_type'
		);

		$this->add_option_key('BWP_GXS_STATS', 'bwp_gxs_stats',
			__('Sitemap Statistics', $this->domain));
		$this->add_option_key('BWP_GXS_OPTION_GENERATOR', 'bwp_gxs_generator',
			__('XML Sitemap', $this->domain));
		$this->add_option_key('BWP_GXS_GOOGLE_NEWS', 'bwp_gxs_google_news',
			__('Google News Sitemap', $this->domain));

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
		$this->oldest_time = (int) $this->options['input_oldest'] * (int) $this->options['select_oldest_type'];

		// @todo
		$this->options['input_cache_dir'] = plugin_dir_path($this->plugin_file) . 'cache/';
		$this->options['input_cache_dir'] = $this->_normalize_path_separator($this->options['input_cache_dir']);
		$this->cache_directory = $this->options['input_cache_dir'];

		// @todo needdoc
		$module_map       = apply_filters('bwp_gxs_module_mapping', array());
		$this->module_map = wp_parse_args($module_map, array(
			'post_format' => 'post_tag'
		));

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
		// @todo recheck rewrite rules
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

		foreach ($this->logs as $key => $log)
		{
			if (is_array($log) && $this->num_log < sizeof($log))
			{
				$log = array_slice($log, (-1) * $this->num_log);
				$this->logs[$key] = $log;
			}
		}
	}

	private static function flush_rewrite_rules()
	{
		global $wp_rewrite;

		$wp_rewrite->flush_rules();
	}

	protected function pre_init_hooks()
	{
		add_filter('rewrite_rules_array', array($this, 'insert_rewrite_rules'));
		add_filter('query_vars', array($this, 'insert_query_vars'));
		add_action('parse_request', array($this, 'request_sitemap'));

		// @see `wp_transition_post_status` in wp-includes/post.php
		if ('yes' == $this->options['enable_ping'])
		{
			add_action('auto-draft_to_publish', array($this, 'ping'), 1000);
			add_action('draft_to_publish', array($this, 'ping'), 1000);
			add_action('new_to_publish', array($this, 'ping'), 1000);
			add_action('pending_to_publish', array($this, 'ping'), 1000);
			add_action('future_to_publish', array($this, 'ping'), 1000);
		}

		// Enable ping for news sitemap
		if ('yes' == $this->options['enable_news_ping'])
		{
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
		global $wp_rewrite;

		$wp_rewrite->flush_rules();
	}

	public function uninstall()
	{
		global $wp_rewrite;

		$this->logs = array(
			'log' => array(),
			'sitemap' => array()
		);

		$this->commit_logs();

		$wp_rewrite->flush_rules();
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
				__('Sitemap Statistics', $this->domain),
				BWP_GXS_CAPABILITY,
				BWP_GXS_STATS,
				array($this, 'build_option_pages')
			);
			add_options_page(
				__('BWP Google XML Sitemaps Generator', $this->domain),
				__('Sitemap Generator', $this->domain),
				BWP_GXS_CAPABILITY,
				BWP_GXS_OPTION_GENERATOR,
				array($this, 'build_option_pages')
			);
			add_options_page(
				__('BWP Google News XML Sitemap', $this->domain),
				__('News Sitemap', $this->domain),
				BWP_GXS_CAPABILITY,
				BWP_GXS_GOOGLE_NEWS,
				array($this, 'build_option_pages')
			);
		}
		else
		{
			add_menu_page(
				__('Better WordPress Google XML Sitemaps', $this->domain),
				'BWP GXS',
				BWP_GXS_CAPABILITY,
				BWP_GXS_STATS,
				array($this, 'build_option_pages'),
				BWP_GXS_IMAGES . '/icon_menu.png'
			);
			add_submenu_page(
				BWP_GXS_STATS,
				__('Better WordPress Google XML Sitemaps Statistics', $this->domain),
				__('Sitemap Statistics', $this->domain),
				BWP_GXS_CAPABILITY,
				BWP_GXS_STATS,
				array($this, 'build_option_pages')
			);
			add_submenu_page(
				BWP_GXS_STATS,
				__('Better WordPress Google XML Sitemaps Generator', $this->domain),
				__('Sitemap Generator', $this->domain),
				BWP_GXS_CAPABILITY,
				BWP_GXS_OPTION_GENERATOR,
				array($this, 'build_option_pages')
			);
			add_submenu_page(
				BWP_GXS_STATS,
				__('Better WordPress Google News XML Sitemap', $this->domain),
				__('News Sitemap', $this->domain),
				BWP_GXS_CAPABILITY,
				BWP_GXS_GOOGLE_NEWS,
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
			if ($page == BWP_GXS_STATS)
			{
				$bwp_option_page->set_current_tab(1);

				// Clear logs = @since 1.1.0
				if (isset($_POST['clear_log']) && !$this->is_normal_admin())
				{
					check_admin_referer($page);
					$this->logs = array('log' => array(), 'sitemap' => array());
					$this->commit_logs();
					$this->add_notice('<strong>' . __('Notice', $this->domain) . ':</strong> ' . __("All logs have been cleared successfully!", $this->domain));
				}

				$form = array(
					'items'			=> array('heading', 'heading', 'heading', 'heading', 'checkbox', 'section', 'heading', 'checkbox', 'checkbox'),
					'item_labels'	=> array
					(
						__('What are Sitemaps?', $this->domain),
						__('Your sitemaps', $this->domain),
						__('Submit your sitemaps', $this->domain),
						__('Pinging search engines', $this->domain),
						__('Enable pinging functionality?', $this->domain),
						__('Enable pinging individual SE', $this->domain),
						__('Sitemap Generator\'s Log', $this->domain),
						__('Enable logging?', $this->domain),
						__('Enable debugging?', $this->domain)
					),
					'item_names'	=> array('h1', 'h2', 'h4', 'h5', 'cb1', 'sec1', 'h3', 'cb2', 'cb3'),
					'sec1' => array(
							array('checkbox', 'name' => 'cb4'),
							array('checkbox', 'name' => 'cb6')
							//array('checkbox', 'name' => 'cb7')
					),
					'heading'			=> array(
						'h1'	=> __('In its simplest form, a Sitemap is an XML file that lists URLs for a site along with additional metadata about each URL (when it was last updated, how often it usually changes, and how important it is, relative to other URLs in the site) so that search engines can more intelligently crawl the site &mdash; <em>http://www.sitemaps.org/</em>', $this->domain) . '<br /><br />' . __('This plugin helps you generate both Sitemap Index files as well as normal Sitemap files. A Sitemap Index, as its name suggests, is one kind of sitemaps that allows you to group multiple sitemap files inside it.', $this->domain),
						'h2'	=> __('<em>Basic information about all your sitemaps.</em>', $this->domain),
						'h3'	=> __('<em>More detailed information about how your sitemaps are generated including <span style="color: #999999;">notices</span>, <span style="color: #FF0000;">errors</span> and <span style="color: #009900;">success messages</span>.</em>', $this->domain),
						'h4'	=> sprintf(__('<em>Submit your sitemapindex to major search engines like <a href="%s" target="_blank">Google</a>, <a href="%s" target="_blank">Bing</a>.</em>', $this->domain), 'https://www.google.com/webmasters/tools/home?hl=en', 'http://www.bing.com/toolbox/webmasters/'/*, 'http://about.ask.com/en/docs/about/webmasters.shtml#22'*/),
						'h5'	=> __('<em>Now when you post something new to your blog, you can <em>ping</em> those search engines to tell them your blog just got updated. Pinging could be less effective than you think it is but you should enable such feature anyway.</em>', $this->domain)
					),
					'input'		=> array(
					),
					'checkbox'	=> array(
						'cb1' => array(__('Selected SE below will be pinged when you publish new posts.', $this->domain) => 'enable_ping'),
						'cb2' => array(__('No additional load is needed so enabling this is recommended.', $this->domain) => 'enable_log'),
						'cb3' => array(__('Minor errors will be printed on screen. Also, when debug is on, no caching is used, useful when you develop new modules.', $this->domain) => 'enable_debug'),
						'cb4' => array(__('Google', $this->domain) => 'enable_ping_google'),
						'cb6' => array(__('Bing', $this->domain) => 'enable_ping_bing')
						//'cb7' => array(__('Ask.com', $this->domain) => 'enable_ping_ask')
					),
					'container'	=> array(
						'h4' => sprintf(__('After you activate this plugin, all sitemaps should be available right away. The next step is to submit the sitemapindex to major search engines. You only need the <strong>sitemapindex</strong> and nothing else, those search engines will automatically recognize other included sitemaps. You can read a small <a href="%s">How-to</a> if you are interested.', $this->domain), 'http://help.yahoo.com/l/us/yahoo/smallbusiness/store/promote/sitemap/sitemap-06.html'),
						'h3' => $this->get_logs(),
						'h2' => $this->get_logs(true)
					)
				);

				// Add a clear log button - @since 1.1.0
				if (!$this->is_normal_admin())
					add_filter('bwp_option_submit_button', array($this, 'add_clear_log_button'));

				// Get the options
				$options = $bwp_option_page->get_options(array('enable_ping', 'enable_ping_google', 'enable_ping_bing', /*'enable_ping_ask',*/ 'enable_log', 'enable_debug'), $this->options);

				// Get option from the database
				$options = $bwp_option_page->get_db_options($page, $options);
				$option_ignore = array('input_update_services');
				$option_formats = array();
				// [WPMS Compatible]
				$option_super_admin = $this->site_options;
			}
			else if ($page == BWP_GXS_OPTION_GENERATOR)
			{
				$bwp_option_page->set_current_tab(2);

				//add_filter('bwp_ad_showable', function() { return false;});

				$form = array(
					'items'			=> array('input', 'select', 'select', 'select', 'checkbox', 'checkbox', 'input', 'checkbox', 'checkbox', 'checkbox', 'checkbox', 'heading', 'checkbox', 'checkbox', 'checkbox', 'section', 'section', 'section', 'heading', 'input', 'input', 'heading', 'checkbox', 'checkbox', 'input', 'input'),
					'item_labels'	=> array
					(
						__('Output no more than', $this->domain),
						__('Default change frequency', $this->domain),
						__('Default priority', $this->domain),
						__('Minimum priority', $this->domain),
						__('Use GMT for Last Modified date?', $this->domain),
						__('Style your sitemaps with an XSLT stylesheet?', $this->domain),
						__('Custom XSLT stylesheet URL', $this->domain),
						__('Show build stats in sitemaps?', $this->domain),
						__('Enable credit?', $this->domain),
						__('Enable Gzip?', $this->domain),
						__('Clean unexpected output before sitemap generation?', $this->domain),
						__('Sitemap Index Options', $this->domain),
						__('Automatically split post-based sitemaps into smaller sitemaps?', $this->domain),
						__('Add sitemapindex to individual blog\'s virtual robots.txt?', $this->domain),
						__('Add sitemapindex from all blogs within network to primary blog\'s virtual robots.txt?', $this->domain),
						__('In sitemapindex, include', $this->domain),
						__('Exclude following post types:', $this->domain),
						__('Exclude following taxonomies:', $this->domain),
						__('Module Options', $this->domain),
						__('Alternate module directory', $this->domain),
						__('Get no more than', $this->domain),
						__('Caching Options', $this->domain),
						__('Enable caching?', $this->domain),
						__('Enable auto cache re-generation?', $this->domain),
						__('Cached sitemaps will last for', $this->domain),
						__('Cached sitemaps are stored in (auto detected)', $this->domain)
					),
					'item_names'	=> array('input_item_limit', 'select_default_freq', 'select_default_pri', 'select_min_pri', 'cb14', 'cb10', 'input_custom_xslt', 'cb3', 'cb6', 'cb4', 'cb15', 'h5', 'cb12', 'cb11', 'cb5', 'sec1', 'sec2', 'sec3', 'h4', 'input_alt_module_dir', 'input_sql_limit', 'h3', 'cb1', 'cb2', 'input_cache_age', 'input_cache_dir'),
					'heading'			=> array(
						'h3'	=> __('<em>Cache your sitemaps for better performance.</em>', $this->domain),
						'h4'	=> sprintf(__('<em>This plugin uses modules to build sitemap data so it is recommended that you extend this plugin using modules rather than hooks. Some of the settings below only affect modules extending the base module class. Read more about using modules <a href="%s#using-modules">here</a>.</em>', $this->domain), $this->plugin_url),
						'h5'	=> __('<em>Here you can change some settings that affect the default Sitemap Index file.</em>', $this->domain)
					),
					'sec1' => array(
						array('checkbox', 'name' => 'cb7'),
						//array('checkbox', 'name' => 'cb8'),
						array('checkbox', 'name' => 'cb9'),
						array('checkbox', 'name' => 'cb13'),
						array('checkbox', 'name' => 'cb16'),
						array('checkbox', 'name' => 'cb17')
					),
					'sec2' => array(),
					'sec3' => array(),
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
						'select_default_freq' => sprintf('<a href="%s" target="_blank">' . __('read more', $this->domain) . '</a>', 'http://sitemaps.org/protocol.php#xmlTagDefinitions'),
						'select_default_pri' => sprintf('<a href="%s" target="_blank">' . __('read more', $this->domain) . '</a>', 'http://sitemaps.org/protocol.php#xmlTagDefinitions'),
						'select_min_pri' => sprintf('<a href="%s" target="_blank">' . __('read more', $this->domain) . '</a>', 'http://sitemaps.org/protocol.php#xmlTagDefinitions')
					),
					'checkbox'	=> array(
						'cb1' => array(__('your sitemaps are generated and then cached to reduce unnecessary work.', $this->domain) => 'enable_cache'),
						'cb2' => array(__('when a cached sitemap expires, this plugin will try to generate the cache again. If you disable this, remember to manually flush the cache once in a while.', $this->domain) . ' <input type="submit" class="button-secondary action" name="flush_cache" value="' . __('Flush the cache', $this->domain) . '" />' => 'enable_cache_auto_gen'),
						'cb3' => array(__('tell you useful information such as build time, memory usage, SQL queries, etc.', $this->domain) => 'enable_stats'),
						'cb4' => array(__('make your sitemaps ~ 70% smaller. <strong>Important:</strong> If you see an error after enabling this, it\'s very likely that you have gzip active on your server already.', $this->domain) => 'enable_gzip'),
						'cb15' => array(__('only disable this when sitemaps appear in either blank page or plain text.', $this->domain) => 'enable_php_clean'),
						'cb5' => array(sprintf(__("If you have like 50 blogs, 50 <code>Sitemap: http://example.com/sitemapindex.xml</code> entries will be added to your primary blog's robots.txt, i.e. <code>%s</code>.", $this->domain), get_site_option('home') . '/robots.txt') => 'enable_global_robots'),
						'cb7' => array(__("taxonomy archives' sitemaps, including custom taxonomies.", $this->domain) => 'enable_sitemap_taxonomy'),
						//'cb8' => array(__("tag archives' sitemap.", $this->domain) => 'enable_sitemap_tag'),
						'cb9' => array(__("date archives' sitemaps.", $this->domain) => 'enable_sitemap_date'),
						'cb13' => array(__("external pages' sitemap. This allows you to add links to pages that do not belong to WordPress to the sitemap.", $this->domain) => 'enable_sitemap_external'),
						'cb6' => array(__('some copyrighted info is also added to your sitemaps. Thanks!', $this->domain) => 'enable_credit'),
						'cb10' => array(__('This will load the default style sheet provided by this plugin. You can set a custom style sheet below or filter the <code>bwp_gxs_xslt</code> hook.', $this->domain) => 'enable_xslt'),
						'cb11' => array(sprintf(__('If you\'re on a Multi-site installation with Sub-domain enabled, each site will have its own robots.txt, sites in sub-directory will not. Please read the <a href="%s#toc-robots" target="_blank">documentation</a> for more info.', $this->domain), $this->plugin_url) => 'enable_robots'),
						'cb12' => array(__('e.g. post1.xml, post2.xml, etc. And each sitemap will contain', $this->domain) => 'enable_sitemap_split_post'),
						'cb14' => array(__('If you disable this, make sure you also use <code>date_default_timezone_set</code> to correctly set up a timezone for your application.', $this->domain) => 'enable_gmt'),
						'cb16' => array(__('author archives\' sitemap.', $this->domain) => 'enable_sitemap_author'),
						'cb17' => array(__('site\'s home URL sitemap. For a multi-site installation of WordPress, this sitemap will list all domains within your network, not just the main blog. This also supports WPMU Domain Mapping plugin.', $this->domain) => 'enable_sitemap_site')
					),
					'input'	=> array(
						'input_item_limit' => array('size' => 5, 'label' => __('item(s) in one sitemap. You can not go over 50,000.', $this->domain)),
						'input_split_limit_post' => array('size' => 5, 'label' => __('item(s). Again , you can not go over 50,000.', $this->domain)),
						'input_alt_module_dir' => array('size' => 91, 'label' => __('Input a full path to the directory where you put your own modules (e.g. <code>/home/mysite/public_html/gxs-modules/</code>), you can also override a built-in module by having a module with the same filename in this directory. A filter is also available if you would like to use PHP instead.', $this->domain)),
						'input_cache_dir' => array('size' => 91, 'disabled' => ' disabled="disabled"', 'label' => __('The cache directory must be writable (i.e. CHMOD to 755 or 777).', $this->domain)),
						'input_sql_limit' => array('size' => 5, 'label' => __('item(s) in one SQL query. This helps you avoid running too heavy queries.', $this->domain)),
						'input_oldest' => array('size' => 3, 'label' => '&mdash;'),
						'input_cache_age' => array('size' => 5, 'label' => '&mdash;'),
						'input_custom_xslt' => array('size' => 56, 'label' => __('expected to be an absolute URL, e.g. <code>http://example.com/my-stylesheet.xsl</code>. You must also have a style sheet for the sitemapindex that can be accessed through the above URL, e.g. <code>my-stylesheet.xsl</code> and <code>my-stylesheetindex.xsl</code>). Please leave blank if you do not wish to use.', $this->domain))
					),
					'inline_fields' => array(
						'input_cache_age' => array('select_time_type' => 'select'),
						'cb12' => array('input_split_limit_post' => 'input')
					),
					'container' => array(
						'input_item_limit' => sprintf(__('<em><strong>Note:</strong> If you encounter white page problem, please refer to the <a target="_blank" href="%s">FAQ section</a> to know how to change this limit appropriately to make this plugin work. Also note that, for post-based sitemaps, this option will be overridden by the limit you set in the Sitemap Index Options below.</em>', $this->domain), $this->plugin_url . 'faq/')
					)
				);

				foreach ($this->frequencies as $freq)
					$changefreq[ucfirst($freq)] = $freq;
				$form['select']['select_default_freq'] = $changefreq;

				// Get the options
				$options = $bwp_option_page->get_options(array('input_item_limit', 'input_split_limit_post', 'input_alt_module_dir', 'input_cache_dir', 'input_sql_limit', 'input_cache_age', 'input_custom_xslt', 'input_exclude_post_type', 'input_exclude_taxonomy', 'enable_gmt', 'enable_robots', 'enable_xslt', 'enable_cache', 'enable_cache_auto_gen', 'enable_stats', 'enable_credit', 'enable_sitemap_split_post', 'enable_global_robots', 'enable_sitemap_date', 'enable_sitemap_taxonomy', 'enable_sitemap_external', 'enable_sitemap_author', 'enable_sitemap_site', 'enable_gzip', 'enable_php_clean', 'select_time_type', 'select_default_freq', 'select_default_pri', 'select_min_pri'), $this->options);

				// Get option from the database
				$options = $bwp_option_page->get_db_options($page, $options);

				// Get dynamic options
				if (isset($_POST['submit_' . $bwp_option_page->get_form_name()]))
				{
					check_admin_referer($page);
					$ept = array(); $etax = array();
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
					$form['sec2'][] = array('checkbox', 'name' => $key);
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
					$form['sec3'][] = array('checkbox', 'name' => $key);
					$form['checkbox'][$key] = array(__($taxonomy->label) => $key);
					if (in_array($taxonomy->name, $exclude_options['taxonomies']))
						$dynamic_options[$key] = 'yes';
					else
						$dynamic_options[$key] = '';
				}

				$option_formats = array('input_item_limit' => 'int', 'input_split_limit_post' => 'int', 'input_sql_limit' => 'int', 'input_cache_age' => 'int', 'select_time_type' => 'int');
				$option_ignore = array('input_cache_dir', 'input_exclude_post_type', 'input_exclude_taxonomy');
				// [WPMS Compatible]
				$option_super_admin = $this->site_options;
			}
			else if ($page == BWP_GXS_GOOGLE_NEWS)
			{
				$bwp_option_page->set_current_tab(3);

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
					'items'			=> array('heading', 'checkbox', 'checkbox', 'checkbox', 'checkbox', 'select', 'heading', 'select'),
					'item_labels'	=> array
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
					'item_names'	=> array('h1', 'cb1', 'cb4', 'cb3', 'cb2', 'select_news_lang', 'h2', 'select_news_cat_action'),
					'heading'			=> array(
						'h1'	=> __('A Google News Sitemap is a file that allows you to control which content you submit to Google News. By creating and submitting a Google News Sitemap, you\'re able to help Google News discover and crawl your site\'s articles &mdash; <em>http://support.google.com/</em>', $this->domain),
						'h2'	=> __('<em>Below you will be able to choose what categories to use (or not use) in the news sitemap. You can also assign genres to a specific category.</em>', $this->domain)
					),
					'post' => array(
						'select_news_cat_action' => __('below selected categories in the news sitemap.', $this->domain)
					),
					'select' => array(
						'select_news_lang' => array(
							/* http://www.loc.gov/standards/iso639-2/php/code_list.php */
							__('English', $this->domain) => 'en',
							__('Dutch', $this->domain) => 'nl',
							__('French', $this->domain) => 'fr',
							__('German', $this->domain) => 'de',
							__('Italian', $this->domain) => 'it',
							__('Norwegian', $this->domain) => 'no',
							__('Portuguese', $this->domain) => 'pt',
							__('Polish', $this->domain) => 'pl',
							__('Russian', $this->domain) => 'ru',
							__('Simplified Chinese', $this->domain) => 'zh-cn',
							__('Spanish', $this->domain) => 'es',
							__('Turkish', $this->domain) => 'tr',
							__('Vietnamese', $this->domain) => 'vi'
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
					'input'		=> array(
					),
					'checkbox'	=> array(
						'cb1' => array(__('A new <code>post_google_news.xml</code> sitemap will be added to the main <code>sitemapindex.xml</code>.', $this->domain) => 'enable_news_sitemap'),
						'cb2' => array(__('Keywords are derived from', $this->domain) => 'enable_news_keywords'),
						'cb3' => array(__('This ping works separately from the sitemapindex ping, and only occurs when you publish an article in one of the news categories set below.', $this->domain) => 'enable_news_ping'),
						'cb4' => array(__('This mode is meant for News Blogs that have posts assigned to more than one categories. It is an advanced feature and should only be enabled if you do have similar blogs.', $this->domain) => 'enable_news_multicat')
					),
					'inline_fields'	=> array(
						'cb2' => array('select_news_keyword_type' => 'select')
					),
					'post' => array(
						'select_news_keyword_type' => __('. Do <strong>NOT</strong> use news tags if your news sitemap contains a lot of posts as it can be very inefficient to do so. This will be improved in future versions.', $this->domain)
					),
					'container'	=> array(
						'select_news_cat_action' => $this->get_news_cats()
					)
				);

				// Get the options
				$options = $bwp_option_page->get_options(array('enable_news_sitemap', 'enable_news_ping', 'enable_news_keywords', 'enable_news_multicat', 'select_news_lang', 'select_news_keyword_type', 'select_news_cat_action', 'select_news_cats', 'input_news_genres'), $this->options);

				// Get option from the database
				$options = $bwp_option_page->get_db_options($page, $options);
				$options['select_news_cats'] = $this->options['select_news_cats'];
				$options['input_news_genres'] = $this->options['input_news_genres'];
				$option_ignore = array('select_news_cats', 'input_news_genres');
				$option_formats = array();
				$option_super_admin = $this->site_options;
			}
		}

		// Flush the cache
		if (isset($_POST['flush_cache']) && !$this->is_normal_admin())
		{
			check_admin_referer($page);
			if ($deleted = $this->flush_cache())
				$this->add_notice('<strong>' . __('Notice', $this->domain) . ':</strong> ' . sprintf(__("<strong>%d</strong> cached sitemaps have been flushed successfully!", $this->domain), $deleted));
			else
				$this->add_notice('<strong>' . __('Notice', $this->domain) . ':</strong> ' . __("Could not delete any cached sitemaps. Please manually check the cache directory.", $this->domain));
		}

		// Get option from user input
		if (isset($_POST['submit_' . $bwp_option_page->get_form_name()])
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
				self::flush_rewrite_rules();
			// [WPMS Compatible]
			if (!$this->is_normal_admin())
				update_site_option($page, $options);
			// Update options successfully
			$this->add_notice(__("All options have been saved.", $this->domain));
		}

		// [WPMS Compatible]
		if (!$this->is_multisite() && $page == BWP_GXS_OPTION_GENERATOR)
			$bwp_option_page->kill_html_fields($form, array(14));

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
				. __("Cache directory does not exist or is not writable. "
				. "Please read more about directory permission "
				. "<a href='http://www.zzee.com/solutions/unix-permissions.shtml'>here</a> (Unix).", $this->domain)
			);
		}

		// Assign the form and option array
		$bwp_option_page->init($form, $options + $dynamic_options, $this->form_tabs);

		// Build the option page
		echo $bwp_option_page->generate_html_form();
	}

	public function add_clear_log_button($button)
	{
		$button = str_replace(
			'</p>',
			' <input type="submit" class="button-secondary action" name="clear_log" value="'
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

	public function get_current_time()
	{
		return current_time('timestamp');
	}

	public function format_header_time($time)
	{
		return gmdate('D, d M Y H:i:s \G\M\T', (int) $time);
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
		$time = $this->get_current_time();

		$debug = 'yes' == $this->options['enable_debug']
			? ' ' . __('(Debug is on)', $this->domain)
			: '';

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

		if (true == $die && true == $this->debug)
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

	public function get_logs($sitemap = false)
	{
		$logs = !$sitemap ? $this->logs['log'] : $this->logs['sitemap'];

		if (!$logs || !is_array($logs) || 0 == sizeof($logs))
		{
			return $sitemap
				? sprintf(__('Nothing here... yet! Try submitting your '
					. '<a href="%s">sitemapindex</a> first!', $this->domain),
					$this->options['input_sitemap_url'])
				: __('No log yet!', $this->domain);
		}

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

		$log_str = !$sitemap
			? '<li class="clear" style="margin-top: 5px; line-height: 1.7;">'
				. '<span style="float: left; margin-right: 5px;">%s &mdash;</span> '
				. '<span style="color: #%s;">%s</span></li>'
			: '<span style="margin-top: 5px; display: inline-block;">'
				. __('<a href="%s" target="_blank">%s</a> has been '
				. 'successfully built on <strong>%s</strong>.', $this->domain) . '</span><br />';

		$output = '<ul class="bwp-gxs-log">' . "\n";

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

				if (!empty($wpdb->dmtable) && !empty($wpdb->blogs) && $this->is_multisite())
				{
					$mapped_domain = $wpdb->get_var($wpdb->prepare('
							SELECT wpdm.domain as mapped_domain FROM ' . $wpdb->blogs . ' wpblogs
							LEFT JOIN ' . $wpdb->dmtable . ' wpdm
								ON wpblogs.blog_id = wpdm.blog_id AND wpdm.active = 1
							WHERE wpblogs.public = 1 AND wpblogs.spam = 0
								AND wpblogs.deleted = 0 AND wpblogs.blog_id = %d', $blog_id));
				}

				// Default to the main site's scheme
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

	public function format_label($label)
	{
		return str_replace(' ', '_', strtolower($label));
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
				. '(more info <a href="%s" target="_blank">here</a>)', $this->domain),
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
			$this->modules[$module] = array();

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

		foreach ($modules as $module_name => $module)
		{
			foreach ($module as $sub_module)
			{
				if (isset($this->post_types[$sub_module])) // Module is a post type
				{
					// @since 1.0.4 - do not use label anymore, ugh
					$label = $this->format_label($this->post_types[$sub_module]->name);

					if ('post' == $sub_module || 'page' == $sub_module || 'attachment' == $sub_module)
						$data = array($label, array(
							'post' => $this->post_types[$sub_module]->name
						));
					else
						$data = array($module_name . '_' . $label, array(
							'post' => $this->post_types[$sub_module]->name
						));

					$this->requested_modules[] = $data;
				}
				else if ('google_news' == $sub_module)
				{
					// Special post modules
					$this->requested_modules[] = array($module_name . '_' . $sub_module, array(
						'special' => $sub_module
					));
				}
				else if ('yes' == $this->options['enable_sitemap_taxonomy']
					&& isset($this->taxonomies[$sub_module])
				) {
					// Module is a taxonomy
					// $label = $this->format_label($this->taxonomies[$sub_module]->label);
					$label = $this->format_label($this->taxonomies[$sub_module]->name);

					$this->requested_modules[] = array($module_name . '_' . $label, array(
						'taxonomy' => $sub_module
					));
				}
				else if (!empty($sub_module))
				{
					$this->requested_modules[] = array($module_name . '_' . $sub_module, array(
						'archive' => $sub_module
					));
				}
				else
				{
					$this->requested_modules[] = array($module_name);
				}
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
				return __('Google News', $this->domain);
			}

			return $this->post_types[$sub_module]->label;
		}
		elseif ($module == 'taxonomy')
		{
			return $this->taxonomies[$sub_module]->label;
		}
		elseif ($module == 'archive')
		{
			return ucwords($sub_module . ' ' . $module);
		}
		elseif ($module == 'author')
		{
			return sprintf(__('Author %s', $this->domain), '');
		}
		elseif ($module == 'site')
		{
			return __('Site', $this->domain);
		}
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
			'sitemap_index'
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
		$this->_init_stats();

		// Don't let other instrusive plugins mess up our permalnks - @since 1.1.4
		remove_filter('post_link', 'syndication_permalink', 1, 3);
		remove_filter('page_link', 'suffusion_unlink_page', 10, 2);
	}

	private function _load_sitemap_from_cache($module_name, $sitemap_name)
	{
		if ('yes' != $this->options['enable_cache']
			|| 'yes' == $this->options['enable_debug']
		) {
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
			$this->send_header(array(
				'status' => 304
			));

			return true;
		}
		else if ($cache_status == '200')
		{
			// file cache is ok, output the cached sitemap
			$this->send_header($this->cache->get_headers());

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
		if ('yes' != $this->options['enable_cache'])
			return false;

		if (!@is_writable($this->cache_directory))
		{
			$this->log_error(sprintf(
				__('Cache directory <strong>%s</strong> is not writable, no cache file was created.' , $this->domain)
			), $this->cache_directory);

			return false;
		}

		$last_mod = $this->cache->write_cache($this->output);

		if (!$last_mod)
		{
			$this->log_error(sprintf(
				__('Could not write sitemap file to cache directory <strong>%s</strong>.' , $this->domain)
			), $this->cache_directory);

			return false;
		}

		return $last_mod;
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
				__('<strong>%s</strong> will be served using <strong>%s</strong> '
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
					__('<strong>%s</strong> will be served using <strong>%s</strong>.', $this->domain)
				, $sitemap_name, $module_filename));
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
		$success      = false; // can we successfully serve the sitemap?
		$module_found = false; // do we have a sitemap module as requested

		$module      = stripslashes($module);
		$sub_module  = stripslashes($sub_module);
		$part        = 0;
		$module_name = ''; // the final module name used to generate requested sitemap

		// a full sitemap name consists of a module and a sub-module including
		// any split part (`_part1`, `_part2`, etc.) if any
		$sitemap_name = $module . !empty($sub_module) ? '_' . $sub_module : '';

		if (!self::_is_bwp_sitemap($sitemap_name))
		{
			// not a BWP sitemap, return this handle to WordPress
			return false;
		}

		// make sure we're on the canonical domain to avoid styling issue
		$this->_canonical_redirect($sitemap_name);

		if ('yes' == $this->options['enable_sitemap_split_post']
			&& preg_match('/_part([0-9]+)$/i', $sub_module, $matches)
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
			$message = sprintf(__('Requested sitemap (<em>%s.xml</em>) was not found.', $this->domain), $sitemap_name);

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

		if ('sitemapindex' != $sitemap_name)
		{
			// generating a regular sitemap
			$module_file = ''; // path to module file

			if (!empty($sub_module))
			{
				// try generating the sitemap using a sub-module
				if (!empty($this->module_map[$sub_module]))
				{
					// this module is mapped to use another module
					/* $this->log_notice(sprintf( */
					/* 	__('Sub-module <strong>%s</strong> is mapped as <strong>%s</strong>.', $this->domain) */
					/* ), $sub_module, $this->module_map[$sub_module]); */
					$module_name = $module . '_' . $this->module_map[$sub_module];
				}

				$module_file = $this->_get_module_file($module_name, $sitemap_name, true);
			}

			if (empty($module_file))
			{
				// try again with parent module
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

			$this->module_data = array(
				'module'       => $module,
				'sub_module'   => $sub_module,
				'module_key'   => $module_name, // leave this for back-compat
				'module_name'  => $module_name, // @since 1.2.4 this is the same as module_key
				'module_part'  => $part, // leave this for back-compat
				'part'         => $part,
				'sitemap_name' => $sitemap_name // @since 1.2.4 this is the actual sitemap name
			);

			if (class_exists('BWP_GXS_MODULE_' . $module_name))
			{
				$class_name    = 'BWP_GXS_MODULE_' . $module_name;
				$module_object = new $class_name();
				$module_data   = $module_object->get_data();

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
			}
			else
			{
				// required module class can not be found
				$this->log_error(sprintf(
					__('There is no class named <strong>%s</strong> '
					. 'in the module file <strong>%s</strong>.', $this->domain)
				, 'BWP_GXS_MODULE_' . strtoupper($module_name), $module_filename), true);

				// reach here if gxs debug is off
				return false;
			}
		}
		else if ('sitemapindex' == $sitemap_name)
		{
			$module_file = $this->_get_module_file($module_name, $sitemap_name);

			if (class_exists('BWP_GXS_MODULE_INDEX'))
			{
				$module_object = new BWP_GXS_MODULE_INDEX($this->requested_modules);

				$success = $this->generate_sitemap_index($module_object->get_data());
			}
			else
			{
				// @todo should have an error here
				return false;
			}
		}

		if (true == $success)
		{
			// sitemap has been generated and is valid
			$this->_append_sitemap_stats();

			$last_mod = $this->_cache_sitemap();
			$last_mod = $last_mod ? $last_mod : time();
			$expires  = $this->format_header_time($last_mod + $this->cache_time);

			// send proper headers
			$this->send_headers(array(
				'lastmod' => $this->format_header_time($last_mod),
				'expires' => $expires,
				'etag'    => md5($expires . bwp_gxs_get_filename($module_name))
			));

			// display the requested sitemap
			$this->_display_sitemap();

			$this->log_success(sprintf(
				__('Successfully generated <em>%s.xml</em> using module <em>%s</em>.', $this->domain)
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

	public function send_header($headers = array())
	{
		$content_types = array(
			'google' => 'text/xml',
			'yahoo'  => 'text/plain'
		);

		$default_headers = array(
			'status' => 200,
			'vary'   => 'Accept-Encoding'
		);

		$headers = wp_parse_args($headers, $default_headers);

		header('Cache-Control: max-age=' . (int) $this->cache_time);
		header('Expires: ' . $headers['expires']);

		if ($headers['status'] == 200)
		{
			// some headers are only needed when sending a 200 OK response
			header('Last-Modified: ' . $headers['lastmod']);
			header('Accept-Ranges: bytes');
			header('Content-Type: ' . $content_types['google'] . '; charset=UTF-8');

			if ($this->_is_gzip_ok())
				header('Content-Encoding: ' . self::_get_gzip_type());
		}

		if (!empty($headers['etag']))
			header('Etag: ' . $headers['etag']);

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

		// @todo
		if (headers_sent())
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
			$this->log_error(sprintf(
				__('<em>%s.xml</em> does not have any item. '
					. 'Make sure that you have at least one <strong>%s</strong> that is publicly accessible. '
					. 'If you do not have any, consider excluding this sitemap by '
					. 'navigating to <em>BWP GXS >> Sitemap Generator</em> in your admin area '
					. 'and then tick the checkbox next to <strong>%s</strong>.', $this->domain),
				$this->module_data['sitemap_name'], $this->module_data['sub_module'], $this->module_data['sub_module']),
			true, 404);

			return false;
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
		if (!$this->check_output($urls))
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

			$url['freq'] = isset($url['freq']) && in_array($url['freq'], $this->frequencies)
				? $url['freq'] : $this->options['select_default_freq'];

			$url['priority'] = isset($url['priority']) && $url['priority'] <= 1 && $url['priority'] > 0
				? $url['priority'] : $this->options['select_default_pri'];

			$xml .= $this->generate_sitemap_item(
				htmlspecialchars($url['location']), $url['priority'],
				$url['freq'], $url['lastmod']
			);

			$this->output_num++;
		}

		if (!$this->check_output($this->output_num))
			return false;

		$xml .= "\n" . '</urlset>';

		$this->output = $xml;

		return true;
	}

	private function _generate_news_sitemap($urls)
	{
		if (!$this->check_output($urls))
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

			$xml .= $this->generate_news_sitemap_item(
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

		if (!$this->check_output($this->output_num))
			return false;

		$xml .= "\n" . '</urlset>';

		$this->output = $xml;

		return true;
	}

	private function _generate_sitemap_index($sitemaps)
	{
		if (!$this->check_output($sitemaps))
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
		$time      = time();
		$ping_data = get_option(BWP_GXS_PING);

		if (!$ping_data || !is_array($ping_data)
			|| isset($ping_data['data_pinged']['yahoo'])
			|| isset($ping_data['data_pinged']['ask'])
		) {
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
