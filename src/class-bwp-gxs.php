<?php

/**
 * Main Sitemap class that provides all logics.
 *
 * Copyright (c) 2015 Khang Minh <betterwp.net>
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
 * @link https://github.com/OddOneOut/bwp-google-xml-sitemaps
 */

class BWP_Sitemaps extends BWP_Framework_V3
{
	/**
	 * Sitemap generation message logger
	 *
	 * @var BWP_Sitemaps_Logger_MessageLogger
	 */
	protected $message_logger;

	/**
	 * Sitemap generation sitemap logger
	 *
	 * @var BWP_Sitemaps_Logger_SitemapLogger
	 */
	protected $sitemap_logger;

	/**
	 * Post excluder
	 *
	 * @var BWP_Sitemaps_Excluder
	 */
	protected $post_excluder;

	/**
	 * Term excluder
	 *
	 * @var BWP_Sitemaps_Excluder
	 */
	protected $term_excluder;

	/**
	 * Content providers
	 *
	 * @var BWP_Sitemaps_Provider[]
	 */
	protected $providers;

	/**
	 * Ajax action handlers
	 *
	 * @var BWP_Sitemaps_Handler_AjaxHandler[]
	 */
	protected $ajax_handlers;

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
	 * Whether sitemap is generated using a custom module file
	 *
	 * @var bool
	 */
	private $_is_using_custom_module = false;

	/**
	 * Mapping data for a module/sub-module
	 *
	 * @var array
	 */
	public $module_map = array();

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
	 * Urls to ping
	 *
	 * @var array
	 * @since 1.3.0
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
	 * @since 1.3.0
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
	public $xslt, $xslt_index;

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
	public $sitemap_cache;

	/**
	 * Directory to store cached sitemap
	 *
	 * @var string
	 * @since 1.3.0
	 */
	public $cache_directory = '';

	/**
	 * Time to keep cached sitemap files
	 *
	 * @var integer
	 */
	public $cache_time;

	public function __construct(
		array $meta,
		BWP_WP_Bridge $bridge = null,
		BWP_Cache $cache = null)
	{
		parent::__construct($meta, $bridge, $cache);

		// basic version checking
		if (!$this->check_required_versions())
			return;

		// default options
		$options = array(
			'enable_cache'                 => '', // @since 1.3.0 off by default
			'enable_cache_auto_gen'        => 'yes',
			'enable_gzip'                  => '',
			'enable_xslt'                  => '', // @since 1.4.0 off by default
			'enable_sitemap_date'          => '',
			'enable_sitemap_taxonomy'      => 'yes',
			'enable_sitemap_external'      => '',
			'enable_sitemap_split_post'    => 'yes',
			'enable_sitemap_author'        => '',
			'enable_sitemap_site'          => 'yes',
			'enable_stats'                 => 'yes',
			'enable_credit'                => 'yes',
			'enable_ping'                  => 'yes',
			'enable_ping_google'           => 'yes',
			'enable_ping_bing'             => 'yes',
			'enable_log'                   => 'yes',
			'enable_debug'                 => '',
			'enable_debug_extra'           => '', // @since 1.3.0
			'enable_robots'                => 'yes',
			'enable_global_robots'         => '',
			'enable_gmt'                   => 'yes',
			// Google news options
			'enable_news_sitemap'          => '',
			'enable_news_keywords'         => '',
			'enable_news_ping'             => '',
			'enable_news_multicat'         => '',
			'select_news_lang'             => 'en',
			'select_news_post_type'        => 'post', // @since 1.4.0
			'select_news_taxonomy'         => 'category', // @since 1.4.0
			'select_news_keyword_type'     => 'cat',
			'select_news_cat_action'       => 'inc',
			'select_news_cats'             => '',
			'input_news_name'              => '', // @since 1.3.1
			'input_news_genres'            => array(),
			// End of Google news options
			'input_exclude_post_type'      => '',
			'input_exclude_post_type_ping' => '', // @since 1.3.0
			'input_exclude_taxonomy'       => 'post_tag',
			'input_cache_age'              => 1,
			'input_item_limit'             => 5000,
			'input_split_limit_post'       => 0,
			'input_alt_module_dir'         => '', // @since 1.3.0 default to empty
			'input_oldest'                 => 7,
			'input_sql_limit'              => 1000,
			'input_custom_xslt'            => '',
			'input_ping_limit'             => 100, // @since 1.3.0 per day ping limit for each SE
			'select_output_type'           => 'concise',
			'select_time_type'             => 3600,
			'select_oldest_type'           => 16400,
			'select_default_freq'          => 'daily',
			'select_default_pri'           => 1.0,
			'select_min_pri'               => 0.1,
			'input_cache_dir'              => '', // @since 1.3.0 make this editable and allow overriden using constant or filters
		);

		// super admin only options
		$this->site_options = array(
			'enable_global_robots',
			'enable_log',
			'enable_debug',
			'enable_debug_extra',
			'enable_gzip',
			'enable_cache',
			'enable_cache_auto_gen',
			'input_cache_age',
			'input_alt_module_dir',
			'input_sql_limit',
			'input_cache_dir',
			'select_time_type'
		);

		$this->add_option_key('BWP_GXS_GENERATOR', 'bwp_gxs_generator',
			$this->bridge->t('XML Sitemaps', $this->domain));

		$this->add_option_key('BWP_GXS_GOOGLE_NEWS', 'bwp_gxs_google_news',
			$this->bridge->t('Google News Sitemap', $this->domain));

		$this->add_extra_option_key('BWP_GXS_GENERATOR_ADVANCED', 'bwp_gxs_generator_advanced',
			$this->bridge->t('Advanced Options', $this->domain));

		$this->add_option_key('BWP_GXS_STATS', 'bwp_gxs_stats',
			$this->bridge->t('Sitemap Log', $this->domain));

		if (!defined('BWP_GXS_LOG'))
		{
			define('BWP_GXS_LOG', 'bwp_gxs_log');
			define('BWP_GXS_PING', 'bwp_gxs_ping_data');

			// @since 1.4.0 allow excluding posts/terms via admin page
			define('BWP_GXS_EXCLUDED_POSTS', 'bwp_gxs_excluded_posts');
			define('BWP_GXS_EXCLUDED_TERMS', 'bwp_gxs_excluded_terms');

			// @since 1.4.0 allow adding external pages via admin page
			define('BWP_GXS_EXTERNAL_PAGES', 'bwp_gxs_external_pages');

			// @deprecated 1.4.0 use BWP_GXS_GENERATOR instead
			define('BWP_GXS_OPTION_GENERATOR', 'bwp_gxs_generator');
		}

		$this->build_properties('BWP_GXS', $options,
			dirname(dirname(__FILE__)) . '/bwp-gxs.php',
			'http://betterwp.net/wordpress-plugins/google-xml-sitemaps/', false
		);
	}

	protected function pre_init_properties()
	{
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
			'stats'            => "\n" . '<!-- ' . $this->bridge->t('This sitemap was originally generated in %s second(s) (Memory usage: %s) - %s queries - %s URL(s) listed', $this->domain) . ' -->'
			/*'stats_cached'	=> "\n" . '<!-- ' . __('Served from cache in %s second(s) (Memory usage: %s) - %s queries - %s URL(s) listed', $this->domain) . ' -->'*/
		);

		$this->post_excluder = new BWP_Sitemaps_Excluder(
			$this->bridge, $this->cache, 'excluded_posts', BWP_GXS_EXCLUDED_POSTS
		);

		$this->term_excluder = new BWP_Sitemaps_Excluder(
			$this->bridge, $this->cache, 'excluded_terms', BWP_GXS_EXCLUDED_TERMS
		);

		$this->providers = array(
			'post'          => new BWP_Sitemaps_Provider_Post($this, $this->post_excluder),
			'taxonomy'      => new BWP_Sitemaps_Provider_Taxonomy($this, $this->term_excluder),
			'external_page' => new BWP_Sitemaps_Provider_ExternalPage($this, BWP_GXS_EXTERNAL_PAGES)
		);

		$this->ajax_handlers = array(
			'post'          => new BWP_Sitemaps_Handler_Ajax_PostHandler($this->get_provider('post')),
			'taxonomy'      => new BWP_Sitemaps_Handler_Ajax_TaxonomyHandler($this->get_provider('taxonomy')),
			'external_page' => new BWP_Sitemaps_Handler_Ajax_ExternalPageHandler(
				$this->get_provider('external_page'),
				$this->frequencies,
				$this->priorities,
				$this->get_current_timezone()
			),
			'news_terms' => new BWP_Sitemaps_Handler_Ajax_NewsTermsHandler(
				$this->get_provider('taxonomy'),
				explode(',', $this->options['select_news_cats']),
				$this->options['input_news_genres']
			)
		);

		$this->pings_per_day = (int) $this->options['input_ping_limit'];

		// init debug and debug extra mode
		$this->_init_debug();

		// some stats
		$this->build_stats['mem'] = memory_get_usage();
	}

	protected function load_libraries()
	{
		require_once dirname(__FILE__) . '/common-functions.php';

		$this->sitemap_cache = new BWP_GXS_CACHE($this);
	}

	protected function pre_init_hooks()
	{
		add_filter('rewrite_rules_array', array($this, 'insert_rewrite_rules'), 9);
		add_filter('query_vars', array($this, 'insert_query_vars'));
		add_action('parse_request', array($this, 'request_sitemap'));

		// @since 1.4.0 add excluded items from admin, use a relatively low
		// priority so they can be merged with excluded items from user's filters
		add_filter('bwp_gxs_excluded_posts', array($this, 'add_excluded_posts'), 999, 2);
		add_filter('bwp_gxs_excluded_terms', array($this, 'add_excluded_terms'), 999, 2);

		// @since 1.4.0 add external pages from admin, use a relatively low
		// priority so they can be merged with external pages from user's filters
		add_filter('bwp_gxs_external_pages', array($this, 'add_external_pages'), 999);

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

		if (is_admin())
		{
			// handle ajax in admin area
			add_action('wp_ajax_bwp-gxs-get-posts', array($this->get_ajax_handler('post'), 'get_posts_action'));
			add_action('wp_ajax_bwp-gxs-get-excluded-posts', array($this->get_ajax_handler('post'), 'get_excluded_posts_action'));
			add_action('wp_ajax_bwp-gxs-remove-excluded-post', array($this->get_ajax_handler('post'), 'remove_excluded_item_action'));

			add_action('wp_ajax_bwp-gxs-get-terms', array($this->get_ajax_handler('taxonomy'), 'get_terms_action'));
			add_action('wp_ajax_bwp-gxs-get-excluded-terms', array($this->get_ajax_handler('taxonomy'), 'get_excluded_terms_action'));
			add_action('wp_ajax_bwp-gxs-remove-excluded-term', array($this->get_ajax_handler('taxonomy'), 'remove_excluded_item_action'));

			add_action('wp_ajax_bwp-gxs-get-external-pages', array($this->get_ajax_handler('external_page'), 'get_pages_action'));
			add_action('wp_ajax_bwp-gxs-submit-external-page', array($this->get_ajax_handler('external_page'), 'save_external_page_action'));
			add_action('wp_ajax_bwp-gxs-remove-external-page', array($this->get_ajax_handler('external_page'), 'remove_external_page_action'));

			add_action('wp_ajax_bwp-gxs-get-object-taxonomies', array($this->get_ajax_handler('taxonomy'), 'get_taxonomies_action'));
			add_action('wp_ajax_bwp-gxs-get-news-term-genres', array($this->get_ajax_handler('news_terms'), 'get_term_genres_action'));

			// filter post queries in admin
			add_filter('posts_where', array($this, 'add_post_title_like_query_variable'), 10, 2);
		}
	}

	protected function init_properties()
	{
		$this->cache_directory = $this->_get_cache_directory();
		$this->cache_time  = (int) $this->options['input_cache_age'] * (int) $this->options['select_time_type'];

		// init directories where modules live
		$this->_init_module_directories();

		// certain modules can use other modules to build data
		$module_map       = $this->bridge->apply_filters('bwp_gxs_module_mapping', array());
		$this->module_map = $this->bridge->wp_parse_args($module_map, array(
			'post_format' => 'post_tag'
		));

		// init sitemap log
		$this->_init_logs();

		// init xslt stylesheet
		$this->_init_xslt_stylesheet();
	}

	protected function enqueue_media()
	{
		$style_deps = array('bwp-option-page');

		if ($this->is_admin_page(BWP_GXS_GENERATOR))
		{
			$style_deps = array('bwp-select2', 'bwp-datatables', 'bwp-jquery-ui', 'bwp-option-page');

			$this->enqueue_media_file('bwp-gxs-admin',
				BWP_GXS_JS . '/bwp-gxs-admin.js',
				array(
					'bwp-select2',
					'bwp-datatables',
					'bwp-inputmask',
					'jquery-ui-datepicker',
					'bwp-op-modal',
					'bwp-op'
				), false,
				BWP_GXS_DIST . '/js/script.min.js'
			);

			if ($this->is_admin_page(BWP_GXS_GENERATOR))
			{
				wp_localize_script('bwp-gxs-admin', 'bwp_gxs', array(
					'nonce' => array(
						'remove_excluded_item' => wp_create_nonce('bwp_gxs_remove_excluded_item'),
						'remove_external_page' => wp_create_nonce('bwp_gxs_manage_external_page')
					),
					'text'  => array(
						'exclude_items' => array(
							'remove_title'   => __('Remove from exclusion', $this->domain),
							'remove_warning' => __('This action can not be undone, are you sure?', $this->domain)
						),
						'external_pages' => array(
							'edit_title'   => __('Edit this page', $this->domain),
							'remove_title'   => __('Remove this page', $this->domain),
							'remove_warning' => __('This action can not be undone, are you sure?', $this->domain)
						)
					)
				));
			}
		}
		elseif ($this->is_admin_page(BWP_GXS_GOOGLE_NEWS))
		{
			$style_deps = array('bwp-datatables', 'bwp-option-page');

			$this->enqueue_media_file('bwp-gxs-admin-news',
				BWP_GXS_JS . '/admin-news.js',
				array(
					'bwp-datatables',
					'bwp-op'
				), false,
				BWP_GXS_DIST . '/js/script.min.js'
			);
		}

		if ($this->is_admin_page())
		{
			$this->enqueue_media_file('bwp-gxs-admin',
				BWP_GXS_CSS . '/style.css', $style_deps, false,
				BWP_GXS_DIST . '/css/style.min.css'
			);

			wp_enqueue_script('bwp-op');
		}
	}

	public function insert_query_vars($vars)
	{
		if (!$this->_should_use_permalink())
		{
			array_push($vars, $this->_get_non_permalink_query_var());
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

	private function _get_cache_directory_from_constant()
	{
		return defined('BWP_GXS_CACHE_DIR') && BWP_GXS_CACHE_DIR != ''
			? trim(BWP_GXS_CACHE_DIR) : '';
	}

	private function _get_default_cache_directory()
	{
		return $this->bridge->plugin_dir_path($this->plugin_file) . 'cache/';
	}

	/**
	 * Gets cache directory from constant, setting or filters (in that
	 * particular order)
	 *
	 * @since 1.3.0
	 * @access private
	 */
	private function _get_cache_directory()
	{
		// get cache dir from constant
		$cache_dir = $this->_get_cache_directory_from_constant();

		// get cache dir from setting
		$cache_dir = empty($cache_dir) ? trim($this->options['input_cache_dir']) : $cache_dir;

		// get default cache dir
		$cache_dir = empty($cache_dir) ? $this->_get_default_cache_directory() : $cache_dir;

		// allow custom cache dirs using filters
		return $this->bridge->apply_filters('bwp_gxs_cache_dir', $cache_dir);
	}

	/**
	 * Set up the default module directory and a custom module directory if applicable
	 *
	 * @return void
	 * @since 1.3.0
	 * @access private
	 */
	private function _init_module_directories()
	{
		$this->module_directory = $this->bridge->plugin_dir_path($this->plugin_file) . 'src/modules/';

		$this->custom_module_directory = !empty($this->options['input_alt_module_dir'])
			? $this->options['input_alt_module_dir'] : null;

		$this->custom_module_directory = $this->bridge->trailingslashit(
			$this->bridge->apply_filters('bwp_gxs_module_dir', $this->custom_module_directory)
		);
	}

	/**
	 * Constructs a sitemap url (friendly or normal) based on provided slug
	 *
	 * @since 1.3.0
	 * @return string
	 */
	public function get_sitemap_url($slug)
	{
		return sprintf($this->_get_sitemap_url_struct(), $slug);
	}

	/**
	 * Construct the url to the sitemap index
	 *
	 * @since 1.4.0
	 * @return string
	 */
	public function get_sitemap_index_url()
	{
		return $this->get_sitemap_url('sitemapindex');
	}

	/**
	 * @since 1.4.0
	 * @return mixed bool|string false if should not use permalink
	 *                           the permalink structure itself if used
	 */
	private function _should_use_permalink()
	{
		if ($permalink = $this->bridge->get_option('permalink_structure'))
			return $permalink;

		return false;
	}

	private function _get_non_permalink_query_var()
	{
		return $this->bridge->apply_filters('bwp_gxs_query_var_non_perma', 'bwpsitemap');
	}

	/**
	 * @since 1.4.0
	 */
	private function _get_sitemap_url_struct()
	{
		if ($permalink = $this->_should_use_permalink())
		{
			// use friendly sitemap urls such as http://example.com/sitemapindex.xml
			// If user is using index.php in their permalink structure, we will
			// have to include it also
			$indexphp = strpos($permalink, 'index.php') === false ? '' : '/index.php';
			return $this->bridge->home_url() . $indexphp . '/%s.xml';
		}
		else
		{
			return $this->bridge->home_url() . '/?' . $this->_get_non_permalink_query_var() . '=%s';
		}
	}

	/**
	 * Inits XSLT stylesheets used for sitemap's look and feel
	 *
	 * @return void
	 * @since 1.3.0
	 * @access private
	 **/
	private function _init_xslt_stylesheet()
	{
		if ('yes' != $this->options['enable_xslt'])
			return;

		// if the host the user is using is different from what we get from
		// 'home' option, we need to use the host so user won't see a style
		// sheet error, which is most of the time mistaken as broken
		// sitemaps - @since 1.1.0
		$user_host = strtolower($_SERVER['HTTP_HOST']);

		$blog_home = @parse_url($this->bridge->home_url());
		$blog_host = strtolower($blog_home['host']);

		$this->xslt = !empty($this->options['input_custom_xslt'])
			? $this->options['input_custom_xslt']
			: $this->plugin_wp_url . 'assets/xsl/bwp-sitemap.xsl';

		$this->xslt = strcmp($user_host, $blog_host) == 0
			? $this->xslt
			: str_replace($blog_host, $user_host, $this->xslt);

		// @since 1.3.0 allow the use of dynamic xslt stylesheet
		$this->xslt       = $this->bridge->apply_filters('bwp_gxs_xslt', $this->xslt);
		$this->xslt_index = empty($this->xslt) ? '' : substr_replace($this->xslt, 'index', -4, 0);
	}

	private function _init_debug()
	{
		$this->_debug = $this->options['enable_debug'] == 'yes'
			|| $this->options['enable_debug_extra'] == 'yes' ? true : false;

		$this->_debug_extra = $this->options['enable_debug_extra'] == 'yes' ? true : false;
	}

	private function _get_initial_logs()
	{
		return array(
			'messages' => array(),
			'sitemaps' => array()
		);
	}

	/**
	 * Inits sitemap log property
	 *
	 * @since 1.3.0
	 * @access private
	 */
	private function _init_logs()
	{
		$this->message_logger = BWP_Sitemaps_Logger::create_message_logger(25);
		$this->sitemap_logger = BWP_Sitemaps_Logger::create_sitemap_logger();

		// populate logger with logs currently stored in db
		$logs = $this->bridge->get_option(BWP_GXS_LOG);
		$logs = $logs && is_array($logs) ? $logs : $this->_get_initial_logs();

		foreach ($logs as $key => $log)
		{
			// invalid log
			if (!is_array($log))
				continue;

			foreach ($log as $log_item)
			{
				// invalid log item
				if (!is_array($log_item))
					continue;

				if ($key == 'sitemaps')
				{
					try {
						$this->_log_sitemap_item(
							$log_item['slug'],
							$log_item['datetime']
						);
					} catch (Exception $e) {
						continue;
					}
				}
				elseif ($key == 'messages')
				{
					try {
						$this->_log_message_item(
							$log_item['message'],
							$log_item['type'],
							$log_item['datetime']
						);
					} catch (Exception $e) {
						continue;
					}
				}
			}
		}
	}

	private function _reset_logs($keep_sitemaps = true)
	{
		$this->message_logger->reset();

		if (!$keep_sitemaps)
			$this->sitemap_logger->reset();

		$this->commit_logs();
	}

	private static function _flush_rewrite_rules()
	{
		global $wp_rewrite;

		$wp_rewrite->flush_rules();
	}

	public function install()
	{
		self::_flush_rewrite_rules();
	}

	public function uninstall()
	{
		$this->_reset_logs(false);

		/* self::_flush_rewrite_rules(); */
	}

	public function upgrade_plugin($from, $to)
	{
		$upgrade_path = dirname(__FILE__) . '/upgrades/db';

		// @since 1.4.0 change log formats
		if (version_compare($from, '1.4.0', '<')) include_once $upgrade_path . '/r2.php';
	}

	public function init_upgrade_plugin($from, $to)
	{
		// @since 1.3.0 default values of cache directory is empty
		if (!$from || version_compare($from, '1.3.0', '<'))
		{
			$this->update_some_options(BWP_GXS_GENERATOR, array(
				'input_cache_dir' => ''
			));
		}
	}

	protected function build_menus()
	{
		if (!empty($this->_menu_under_settings))
		{
			// use simple menu if instructed to
			add_options_page(
				__('BWP Google XML Sitemaps', $this->domain),
				__('XML Sitemaps', $this->domain),
				BWP_GXS_CAPABILITY,
				BWP_GXS_GENERATOR,
				array($this, 'show_option_pages')
			);
			add_options_page(
				__('BWP Google News XML Sitemap', $this->domain),
				__('Google News Sitemap', $this->domain),
				BWP_GXS_CAPABILITY,
				BWP_GXS_GOOGLE_NEWS,
				array($this, 'show_option_pages')
			);
			add_options_page(
				__('BWP Google XML Sitemaps - Advanced Options', $this->domain),
				__('XML Sitemaps - Advanced Options', $this->domain),
				BWP_GXS_CAPABILITY,
				BWP_GXS_GENERATOR_ADVANCED,
				array($this, 'show_option_pages')
			);
			add_options_page(
				__('BWP Google XML Sitemaps Log', $this->domain),
				__('XML Sitemap Log', $this->domain),
				BWP_GXS_CAPABILITY,
				BWP_GXS_STATS,
				array($this, 'show_option_pages')
			);
		}
		else
		{
			add_menu_page(
				__('Better WordPress Google XML Sitemaps', $this->domain),
				'BWP Sitemaps',
				BWP_GXS_CAPABILITY,
				BWP_GXS_GENERATOR,
				array($this, 'show_option_pages'),
				BWP_GXS_IMAGES . '/icon_menu.png'
			);
			add_submenu_page(
				BWP_GXS_GENERATOR,
				__('Better WordPress Google XML Sitemaps', $this->domain),
				__('XML Sitemaps', $this->domain),
				BWP_GXS_CAPABILITY,
				BWP_GXS_GENERATOR,
				array($this, 'show_option_pages')
			);
			add_submenu_page(
				BWP_GXS_GENERATOR,
				__('Better WordPress Google News XML Sitemap', $this->domain),
				__('Google News Sitemap', $this->domain),
				BWP_GXS_CAPABILITY,
				BWP_GXS_GOOGLE_NEWS,
				array($this, 'show_option_pages')
			);
			add_submenu_page(
				BWP_GXS_GENERATOR,
				__('Better WordPress Google XML Sitemaps Advanced Options', $this->domain),
				__('Advanced Options', $this->domain),
				BWP_GXS_CAPABILITY,
				BWP_GXS_GENERATOR_ADVANCED,
				array($this, 'show_option_pages')
			);
			add_submenu_page(
				BWP_GXS_GENERATOR,
				__('Better WordPress Google XML Sitemaps Log', $this->domain),
				__('Sitemap Log', $this->domain),
				BWP_GXS_CAPABILITY,
				BWP_GXS_STATS,
				array($this, 'show_option_pages')
			);
		}
	}

	private function _add_checkboxes_to_form($for, $key_prefix, &$form, &$form_options)
	{
		$options = &$this->options;

		$exclude_options = array(
			'post_types'      => explode(',', $options['input_exclude_post_type']),
			'post_types_ping' => explode(',', $options['input_exclude_post_type_ping']),
			'taxonomies'      => explode(',', $options['input_exclude_taxonomy'])
		);

		$excluded_post_types = $for == 'sec_post'
			? $exclude_options['post_types']
			: $exclude_options['post_types_ping'];

		switch ($for)
		{
			case 'sec_post':
			case 'sec_post_ping':
				$post_types = $this->get_provider('post')->get_post_types();

				foreach ($post_types as $post_type)
				{
					$key = $key_prefix . $post_type->name;

					$form[$for][] = array('checkbox', 'name' => $key);
					$form['checkbox'][$key] = array(__($post_type->label) => $key);

					$form_options[] = $key;

					if (!in_array($post_type->name, $excluded_post_types))
						$options[$key] = 'yes';
					else
						$options[$key] = '';
				}

				break;

			case 'sec_tax':
				$taxonomies = $this->get_provider('taxonomy')->get_taxonomies();

				foreach ($taxonomies as $taxonomy)
				{
					$key = $key_prefix . $taxonomy->name;

					$form[$for][] = array('checkbox', 'name' => $key);
					$form['checkbox'][$key] = array(__($taxonomy->label) => $key);

					$form_options[] = $key;

					if (!in_array($taxonomy->name, $exclude_options['taxonomies']))
						$options[$key] = 'yes';
					else
						$options[$key] = '';
				}

				break;
		}
	}

	private function _get_frequencies_as_choices()
	{
		$choices = array();

		foreach ($this->frequencies as $freq)
			$choices[ucfirst($freq)] = $freq;

		return $choices;
	}

	private function _get_news_languages_as_choices()
	{
		$languages = include_once BWP_GXS_PLUGIN_SRC . '/provider/google-news/languages.php';

		// @since 1.4.0 allow filtering news languages
		return (array) $this->bridge->apply_filters('bwp_gxs_news_languages', $languages);
	}

	private function _get_formatted_logs($template_file, array $data)
	{
		ob_start();

		include_once $template_file;

		$output = ob_get_clean();

		return $output;
	}

	private function _get_formatted_message_logs()
	{
		if ($this->message_logger->is_empty())
			return strtoupper(__('No log yet!', $this->domain)) . "\n";

		$data = array(
			'items' => array_reverse($this->message_logger->get_log_items())
		);

		return $this->_get_formatted_logs(dirname(__FILE__) . '/templates/logger/admin/message-log.html.php', $data);
	}

	private function _get_formatted_sitemap_logs()
	{
		if ($this->sitemap_logger->is_empty())
		{
			return __('It appears that no sitemap has been generated yet', $this->domain)
				. ', '
				. '<a href="#" target="_blank" class="button-secondary button-inline" '
				. 'onclick="this.href=\'' . $this->get_sitemap_url('sitemapindex') . '\';">'
				. __('click to generate your Sitemap Index', $this->domain)
				. '</a>'
				. ' .'
			;
		}

		$items = $this->sitemap_logger->get_log_items();
		$sitemap_log_times = array();

		/* @var $item BWP_Sitemaps_Logger_Sitemap_LogItem */
		foreach ($items as $item) {
			$sitemap_log_times[] = $item->get_timestamp();
		}

		// sort the log DESC by timestamp
		array_multisort($sitemap_log_times, SORT_DESC, SORT_NUMERIC, $items);

		/* @var $item BWP_Sitemaps_Logger_Sitemap_LogItem */
		foreach ($items as $key => $item)
		{
			// remove sitemapindex log item because we will add it manually
			// later on
			if ($item->get_sitemap_slug() === 'sitemapindex')
			{
				unset($items[$key]);
				break;
			}
		}

		// add sitemapindex log item to the top
		if ($sitemapindex = $this->sitemap_logger->get_sitemap_log_item('sitemapindex'))
			array_unshift($items, $sitemapindex);

		$data = array(
			'items' => $items
		);

		$logs = $this->_get_formatted_logs(dirname(__FILE__) . '/templates/logger/admin/sitemap-log.html.php', $data);

		$logs .= '<p class="bwp-paragraph">'
			. sprintf(__('To proceed, submit your <a href="%s" target="_blank">sitemapindex</a> '
				. 'to major search engines like <a href="%s" target="_blank">Google</a> or '
				. '<a href="%s" target="_blank">Bing</a>.', $this->domain),
				$this->get_sitemap_index_url(),
				'https://www.google.com/webmasters/tools/home?hl=en',
				'http://www.bing.com/toolbox/webmasters/')
			. ' '
			. sprintf(__('For more details, see <a target="_blank" href="%1$s">this article</a>.', $this->domain),
				'https://support.google.com/webmasters/answer/75712?hl=en&ref_topic=4581190')
			. '</p>';

		return $logs;
	}

	private function _get_post_types_as_choices($placeholder = true)
	{
		$choices = $placeholder
			? array(__('Select a post type', $this->domain) => '')
			: array();

		$post_types = $this->get_provider('post')->get_post_types();

		foreach ($post_types as $post_type)
			$choices[$post_type->labels->singular_name] = $post_type->name;

		return $choices;
	}

	private function _get_taxonomies_as_choices($placeholder = true, $post_type = null)
	{
		$choices = $placeholder
			? array(__('Select a taxonomy', $this->domain) => '')
			: array();

		$taxonomies = $this->get_provider('taxonomy')->get_taxonomies($post_type);

		foreach ($taxonomies as $taxonomy)
			$choices[$taxonomy->labels->singular_name] = $taxonomy->name;

		return $choices;
	}

	protected function build_option_page()
	{
		$page        = $this->get_current_admin_page();
		$option_page = $this->current_option_page;

		if (empty($page))
			return;

		$form_options = array();

		if ($page == BWP_GXS_GENERATOR)
		{
			$option_page->set_current_tab(1);

			$form = array(
				'items' => array(
					'heading',
					'heading', // sitemaps to generate
					'section',
					'section',
					'section',
					'heading', // exclude items
					'select',
					'select',
					'heading', // external pages
					'heading', // item limits
					'input',
					'checkbox',
					'input',
					'heading', // default values
					'select',
					'select',
					'select',
					'heading', // ping search engines
					'checkbox',
					'section',
					'section',
					'input',
					'heading', // look and feel
					'checkbox',
					'input',
					'checkbox',
				),
				'item_labels' => array(
					__('Generated Sitemaps', $this->domain),
					__('Sitemaps to generate', $this->domain),
					__('Enable following sitemaps', $this->domain),
					__('Enable following post types', $this->domain),
					__('Enable following taxonomies', $this->domain),
					__('Exclude items', $this->domain),
					__('Exclude posts', $this->domain),
					__('Exclude terms', $this->domain),
					__('External pages', $this->domain),
					__('Item limits', $this->domain),
					__('Global limit', $this->domain),
					__('Split <strong>post-based</strong> sitemaps', $this->domain),
					__('Split limit', $this->domain),
					__('Default values', $this->domain),
					__('Default change frequency', $this->domain),
					__('Default priority', $this->domain),
					__('Minimum priority', $this->domain),
					__('Ping search engines', $this->domain),
					__('Enable pinging', $this->domain),
					__('Search engines to ping', $this->domain),
					__('Enable following post types', $this->domain),
					__('Ping limit', $this->domain),
					__('Look and Feel', $this->domain),
					__('Make sitemaps look pretty', $this->domain),
					__('Custom XSLT stylesheet URL', $this->domain),
					__('Enable credit', $this->domain),
				),
				'item_names' => array(
					'heading_submit',
					'heading_contents',
					'sec_index',
					'sec_post',
					'sec_tax',
					'heading_exclude',
					'select_exclude_post_type',
					'select_exclude_taxonomy',
					'heading_external_pages',
					'heading_limit',
					'input_item_limit',
					'enable_sitemap_split_post',
					'input_split_limit_post',
					'heading_default',
					'select_default_freq',
					'select_default_pri',
					'select_min_pri',
					'heading_ping',
					'enable_ping',
					'sec_ping_vendors',
					'sec_post_ping',
					'input_ping_limit',
					'heading_look',
					'enable_xslt',
					'input_custom_xslt',
					'enable_credit',
				),
				'heading' => array(
					'heading_submit' => '',
					'heading_contents' => '<em>'
						. __('Choose appropriate sitemaps to generate.', $this->domain)
						. '</em>',
					'heading_exclude' => '<em>'
						. sprintf(
							__('Exclude individual items for each sitemap.'
							. ' You can also use '
							. '<a href="%s#exclude-items" target="_blank">filters</a> '
							. 'to exclude items programmatically.', $this->domain),
							$this->plugin_url
						)
						. '</em>',
					'heading_external_pages' => '<em>'
						. sprintf(
							__('Add non-WordPress pages to the %s sitemap. '
							. 'You can also use %s to add external pages programmatically.', $this->domain),
							'<a href="' . $this->get_sitemap_url('page_external') . '" target="_blank">'
								. __('External pages', $this->domain)
								. '</a>',
							'<a href="' . $this->plugin_url . '#external-pages" target="_blank">'
								. __('filter', $this->domain)
								. '</a>'
						)
						.'</em>',
					'heading_limit' => '<em>'
						. __('Limit the number of items to output in one sitemap. ', $this->domain)
						. sprintf(__('Setting too high limits might lead to '
							. 'white page error due to timeout or memory issue. '
							. 'Refer to this plugin\'s <a target="_blank" href="%s">FAQ section</a> for more info.', $this->domain),
							$this->plugin_url . 'faq/')
						. '</em>',
					'heading_default' => '<em>'
						. __('Default values are only used when valid ones can not '
						. 'be calculated.', $this->domain)
						. '</em>',
					'heading_look' => '<em>'
						. __('Customize the look and feel of your sitemaps. '
						. 'Note that no stylesheet will be used '
						. 'for the Google News sitemap.', $this->domain)
						. '</em>',
					'heading_ping' => '<em>'
						. __('Whenever you post something new to your blog, '
						. 'you can <em>ping</em> search engines with your sitemapindex '
						. 'to tell them your blog just got updated.', $this->domain)
						. '</em>',
				),
				'sec_index' => array(
					array('checkbox', 'name' => 'enable_sitemap_site'),
					array('checkbox', 'name' => 'enable_sitemap_taxonomy'),
					array('checkbox', 'name' => 'enable_sitemap_date'),
					array('checkbox', 'name' => 'enable_sitemap_author'),
					array('checkbox', 'name' => 'enable_sitemap_external')
				),
				'sec_post' => array(),
				'sec_post_ping' => array(),
				'sec_tax' => array(),
				'sec_ping_vendors' => array(
					array('checkbox', 'name' => 'enable_ping_google'),
					array('checkbox', 'name' => 'enable_ping_bing')
				),
				'select' => array(
					'select_oldest_type' => array(
						__('second(s)', $this->domain) => 1,
						__('minute(s)', $this->domain) => 60,
						__('hour(s)', $this->domain)   => 3600,
						__('day(s)', $this->domain)    => 86400
					),
					'select_default_freq' => $this->_get_frequencies_as_choices(),
					'select_default_pri' => $this->priorities,
					'select_min_pri' => $this->priorities,
					'select_exclude_post_type' => $this->_get_post_types_as_choices(),
					'select_exclude_taxonomy' => $this->_get_taxonomies_as_choices()
				),
				'checkbox' => array(
					'enable_sitemap_taxonomy'   => array(__('Taxonomy (including custom taxonomies)', $this->domain) => ''),
					'enable_sitemap_date'       => array(__('Date archives', $this->domain) => ''),
					'enable_sitemap_author'     => array(__('Author archives', $this->domain) => ''),
					'enable_sitemap_external'   => array(__('External (non-WordPress) pages.', $this->domain) => ''),
					'enable_credit'             => array(__('some copyrighted info is added to your sitemaps.', $this->domain) => ''),
					'enable_xslt'               => array(__('Default XSLT stylesheets will be used. Set your custom stylesheets below or filter the <code>bwp_gxs_xslt</code> hook.', $this->domain) => ''),
					'enable_sitemap_split_post' => array(__('Sitemaps like <code>post.xml</code> are split into <code>post_part1.xml</code>, <code>post_part2.xml</code>, etc. when limit reached.', $this->domain) => ''),
					'enable_sitemap_site'       => array(__('Site Address', $this->domain) => ''),
					'enable_ping'               => array(__('Ping search engines when you publish new posts.', $this->domain) => ''),
					'enable_ping_google'        => array(__('Google', $this->domain) => ''),
					'enable_ping_bing'          => array(__('Bing', $this->domain) => ''),
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
						'size'  => 91
					),
					'input_oldest' => array(
						'size' => 3,
						'label' => '&mdash;'
					),
					'input_ping_limit' => array(
						'size'  => 5,
						'label' => __('time(s) per search engine per day. '
						. 'Increase this limit if you '
						. 'publish a lot of posts in a single day.', $this->domain)
					),
				),
				'container' => array(
					'heading_submit' => array(
						$this->_get_formatted_sitemap_logs(),
					),
					'select_exclude_post_type' => array(
						$this->get_template_contents('templates/provider/admin/exclude-posts.html.php')
					),
					'select_exclude_taxonomy' => array(
						$this->get_template_contents('templates/provider/admin/exclude-terms.html.php')
					),
					'heading_external_pages' => array(
						$this->get_template_contents('templates/provider/admin/external-pages.html.php')
					)
				),
				'formats' => array(
					'input_item_limit'       => 'int',
					'input_split_limit_post' => 'int',
					'input_ping_limit'       => 'int',
				),
				'helps' => array(
					'enable_sitemap_site' => array(
						'content' => 'For a multi-site installation of WordPress, '
							. 'this sitemap will list all appropriate blogs\' addresses within your network, '
							. 'not just the main blog\'s.',
					),
					'select_default_freq' => array(
						'type'    => 'link',
						'content' => 'http://www.sitemaps.org/protocol.html#xmlTagDefinitions'
					),
					'select_default_pri' => array(
						'type'    => 'link',
						'content' => 'http://www.sitemaps.org/protocol.html#xmlTagDefinitions'
					),
					'select_min_pri' => array(
						'type'    => 'link',
						'content' => 'http://www.sitemaps.org/protocol.html#xmlTagDefinitions'
					),
					'input_custom_xslt' => array(
						'type'      => 'focus',
						'content'   => __('Expect an absolute URL, '
							. 'e.g. <code>http://example.com/my-stylesheet.xsl</code>. '
							. 'You must also have a stylesheet for the sitemapindex '
							. 'that can be accessed through the above URL, '
							. 'e.g. <code>my-stylesheet.xsl</code> and '
							. '<code>my-stylesheetindex.xsl</code>. '
							. 'Leave blank to use provided stylesheets.', $this->domain),
						'size'      => 'medium'
					)
				),
				'attributes' => array(
					'enable_sitemap_external' => array(
						'class'       => 'bwp-switch-select',
						'data-target' => 'external-pages',
					),
					'select_exclude_post_type' => array(
						'class'               => 'bwp-switch-select',
						'data-target'         => 'wrapper-exclude-posts',
						'data-callback-after' => 'bwp_select_exclude_post_cb'
					),
					'select_exclude_taxonomy' => array(
						'class'               => 'bwp-switch-select',
						'data-target'         => 'wrapper-exclude-terms',
						'data-callback-after' => 'bwp_select_exclude_term_cb'
					),
					'enable_sitemap_split_post' => array(
						'class'       => 'bwp-switch-on-load bwp-switch-select',
						'data-target' => 'input_split_limit_post'
					)
				)
			);

			$form_options = array(
				'input_item_limit',
				'input_split_limit_post',
				'input_custom_xslt',
				'input_ping_limit',
				'enable_xslt',
				'enable_credit',
				'enable_sitemap_split_post',
				'enable_sitemap_date',
				'enable_sitemap_taxonomy',
				'enable_sitemap_external',
				'enable_sitemap_author',
				'enable_sitemap_site',
				'enable_ping',
				'enable_ping_google',
				'enable_ping_bing',
				'select_default_freq',
				'select_default_pri',
				'select_min_pri'
			);

			$this->current_option_page->register_custom_submit_action('exclude_posts');
			$this->current_option_page->register_custom_submit_action('exclude_terms');
			add_action('bwp_option_page_custom_action_exclude_posts', array($this, 'handle_exclude_posts'));
			add_action('bwp_option_page_custom_action_exclude_terms', array($this, 'handle_exclude_terms'));

			$this->_add_checkboxes_to_form('sec_post', 'ept_', $form, $form_options);
			$this->_add_checkboxes_to_form('sec_tax', 'etax_', $form, $form_options);
			$this->_add_checkboxes_to_form('sec_post_ping', 'eppt_', $form, $form_options);

			// add extra forms
			add_action('bwp_option_action_after_form', array($this, 'add_external_page_modal'));

			// build options dynamically
			add_filter('bwp_option_page_submit_options', array($this, 'handle_dynamic_generator_options'));
		}
		elseif ($page == BWP_GXS_GOOGLE_NEWS)
		{
			$option_page->set_current_tab(2);

			$form = array(
				'items' => array(
					'heading', // add google news sitemap
					'checkbox',
					'input',
					'select',
					'checkbox',
					'heading', // news contents
					'select',
					'select',
					'select',
					'checkbox',
					'select',
					'checkbox',
				),
				'item_labels' => array
				(
					__('Add Google News Sitemap to your Sitemap Index', $this->domain),
					__('Enable news sitemap', $this->domain),
					__('News name', $this->domain),
					__('News language', $this->domain),
					__('Enable pinging', $this->domain),
					__('Sitemap Contents', $this->domain),
					__('News post type', $this->domain),
					__('News taxonomy', $this->domain),
					__('Generator\'s mode', $this->domain),
					__('Enable keywords support', $this->domain),
					__('Keyword source', $this->domain),
					__('Enable multi-term support', $this->domain),
				),
				'item_names' => array(
					'heading_add',
					'enable_news_sitemap',
					'input_news_name',
					'select_news_lang',
					'enable_news_ping',
					'heading_contents',
					'select_news_post_type',
					'select_news_taxonomy',
					'select_news_cat_action',
					'enable_news_keywords',
					'select_news_keyword_type',
					'enable_news_multicat',
				),
				'heading' => array(
					'heading_add' => '<em>'
						. __('A Google News Sitemap is a file that '
						. 'allows you to control which content '
						. 'you submit to Google News. By creating and '
						. 'submitting a Google News Sitemap, you\'re able '
						. 'to help Google News discover and crawl your site\'s news articles '
						. '&mdash; http://support.google.com/', $this->domain)
						. '</em>',
					'heading_contents' => '<em>'
						. __('Customize how you want to generate your news sitemap.', $this->domain)
						. '</em>'
				),
				'select' => array(
					'select_news_post_type' => $this->_get_post_types_as_choices(),
					'select_news_taxonomy' => $this->_get_taxonomies_as_choices(
						true,
						!empty($this->options['select_news_post_type'])
							? $this->options['select_news_post_type']
							: null
					),
					'select_news_lang' => $this->_get_news_languages_as_choices(),
					'select_news_cat_action' => array(
						__('Include', $this->domain) => 'inc',
						__('Exclude', $this->domain) => 'exc'
					),
					'select_news_keyword_type' => array(
						__('News categories', $this->domain) => 'cat',
						__('News tags', $this->domain) => 'tag'
					)
				),
				'input' => array(
					'input_news_name' => array(
						'size'  => 70
					),
				),
				'checkbox' => array(
					'enable_news_sitemap'  => array(sprintf(__('A <code>post_google_news.xml</code> sitemap will be added to the main <a href="%s" target="_blank">sitemapindex.xml</a>. It is strongly recommended that you take a look at <a href="%s" target="_blank">Google\'s guidelines</a> before enabling this feature.', $this->domain), $this->get_sitemap_index_url(), 'https://support.google.com/news/publisher/answer/74288?hl=en#sitemapguidelines') => ''),
					'enable_news_keywords' => array('' => ''),
					'enable_news_ping'     => array(__('Ping search engines when a news article is published.', $this->domain) => ''),
					'enable_news_multicat' => array(__('Enable this if you have posts assigned to more than one terms.', $this->domain) => '')
				),
				'inline_fields' => array(
				),
				'post' => array(
					'select_news_cat_action' => '&nbsp;<em>'
						. __('selected terms.', $this->domain)
						. '</em>&nbsp; '
						. '<button type="button" class="button-secondary bwp-switch-button" '
							. 'id="button-toggle-selected-term-genres" '
							. 'data-target="wrapper-selected-term-genres" '
							. 'data-loader="loader-selected-term-genres" '
							. 'data-callback="bwp_button_view_selected_term_genres_cb"'
						. '>'
						. __('Show/Hide selected terms', $this->domain)
						. '</button>'
						. '<span style="display: none;" id="loader-selected-term-genres"> '
						. '<em>' . __('... loading', $this->domain) . '</em>'
						. '</span>'
				),
				'container' => array(
					'select_news_cat_action' => $this->get_template_contents('templates/provider/admin/news-contents.html.php')
				),
				'helps' => array(
					'input_news_name' => array(
						'type'    => 'focus',
						'content' => __('Set a different name for your news sitemap. '
							. 'By default, your <em>Site Title</em> is used.', $this->domain)
					),
					'select_news_taxonomy' => array(
						'target'  => 'icon',
						'content' => __('Due to performance reason, it is advised '
							. 'to NOT use a tag-like taxonomy (such as <em>Post Tag</em>) '
							. 'as the news taxonomy.', $this->domain)
					),
					'enable_news_keywords' => array(
						'type'    => 'link',
						'content' => 'https://support.google.com/news/publisher/answer/116037?hl=en&ref_topic=4359874'
					),
					'enable_news_ping' => array(
						'content' => __('This ping works separately from the sitemap index ping, '
							. 'and only occurs when you publish an article '
							. 'in one of the news categories set below.', $this->domain)
					)
				),
				'attributes' => array(
					'input_news_name' => array(
						'placeholder' => get_bloginfo('title')
					),
					'select_news_post_type' => array(
						'class'         => 'bwp-switch-select',
						/* 'data-target'         => 'select_news_taxonomy', */
						'data-callback' => 'bwp_select_news_post_type_cb'
					),
					'select_news_taxonomy' => array(
						'class'         => 'bwp-switch-select',
						'data-target'   => 'select_news_cat_action',
						'data-callback' => 'bwp_select_news_taxonomy_cb'
					),
					'enable_news_keywords' => array(
						'class'       => 'bwp-switch-on-load bwp-switch-select',
						'data-target' => 'select_news_keyword_type'
					)
				)
			);

			$form_options = array(
				'enable_news_sitemap',
				'enable_news_ping',
				'enable_news_keywords',
				'enable_news_multicat',
				'select_news_post_type',
				'select_news_taxonomy',
				'select_news_lang',
				'select_news_keyword_type',
				'select_news_cat_action',
				'select_news_cats',
				'input_news_name',
				'input_news_genres'
			);

			// build options dynamically
			add_filter('bwp_option_page_submit_options', array($this, 'handle_dynamic_google_news_options'));

			// handle option changes
			add_filter('bwp_option_page_action_submitted', array($this, 'handle_google_news_option_changes'));
		}
		elseif ($page == BWP_GXS_GENERATOR_ADVANCED)
		{
			$option_page->set_current_tab(3);

			$form = array(
				'items' => array(
					'heading',
					'checkbox',
					'checkbox',
					'heading', // virtual robots
					'checkbox',
					'checkbox',
					'heading', // caching
					'checkbox',
					'checkbox',
					'input',
					'input',
					'heading', // modules
					'input',
					'input',
					'heading', // debugging
					'checkbox',
					'checkbox',
					'checkbox',
					'checkbox'
				),
				'item_labels' => array(
					__('Formatting', $this->domain),
					__('Use GMT for Last Modified date', $this->domain),
					__('Compress sitemaps', $this->domain),
					__('Robots.txt', $this->domain),
					__('Enable robots.txt support', $this->domain),
					__('Enable multisite robots.txt support', $this->domain),
					__('Sitemap Cache', $this->domain),
					__('Enable caching', $this->domain),
					__('Enable auto cache re-generation', $this->domain),
					__('Cache expiry time', $this->domain),
					__('Cache directory', $this->domain),
					__('Sitemap modules', $this->domain),
					__('Database query limit', $this->domain),
					__('Custom module directory', $this->domain),
					__('Debugging', $this->domain),
					__('Enable build stats', $this->domain),
					__('Enable message log', $this->domain),
					__('Enable debugging mode', $this->domain),
					__('Enable extra debugging mode', $this->domain)
				),
				'item_names' => array(
					'heading_format',
					'enable_gmt',
					'enable_gzip',
					'heading_robot',
					'enable_robots',
					'enable_global_robots',
					'heading_cache',
					'enable_cache',
					'enable_cache_auto_gen',
					'input_cache_age',
					'input_cache_dir',
					'heading_module',
					'input_sql_limit',
					'input_alt_module_dir',
					'heading_debug',
					'enable_stats',
					'enable_log',
					'enable_debug',
					'enable_debug_extra'
				),
				'heading' => array(
					'heading_format' => '',
					'heading_cache' => '<em>'
						. __('Cache your sitemaps for better performance. '
						. 'If you are still configuring the plugin it\'s best to '
						. 'disable caching or you might have to manually flush the cache '
						. 'for any changes to show up.', $this->domain)
						. '</em>',
					'heading_robot' => '<em>'
						. sprintf(__('WordPress generates a %svirtual robots.txt%s '
							. 'file for your blog by default. '
							. 'You can add sitemap index files generated by this plugin '
							. 'to that robots.txt file using settings below.', $this->domain),
							!self::is_multisite() || self::is_subdomain_install()
								? '<a href="' . home_url('robots.txt') . '" target="_blank">'
								: '',
							!self::is_multisite() || self::is_subdomain_install()
								? '</a>'
								: '')
						. '</em>',
					'heading_module' => '<em>'
						. sprintf(__('Extend this plugin using customizable modules. '
							. 'More info <a href="%s#module_api">here</a>.', $this->domain),
							$this->plugin_url)
						. '</em>',
					'heading_debug' => ''
				),
				'checkbox' => array(
					'enable_gmt'            => array(__('Disable this to use the local timezone setting in <em>Settings >> General</em>.', $this->domain) => ''),
					'enable_gzip'           => array(__('Use gzip to make sitemaps ~70% smaller. If you see an error after enabling this, it\'s very likely that you have gzip active on your server already.', $this->domain) => ''),
					'enable_cache'          => array(__('Your sitemaps are generated and then cached to reduce unnecessary work.', $this->domain) => ''),
					'enable_cache_auto_gen' => array(__('Re-generate sitemap cache when expired. If you disable this, remember to manually flush the cache once in a while.', $this->domain) => ''),
					'enable_robots'         => array(__('Add a sitemap index entry to blog\'s robots.txt', $this->domain) => ''),
					'enable_global_robots'  => array(__('Add sitemap index entries from all blogs to primary blog\'s robots.txt', $this->domain) => ''),
					'enable_stats'          => array(__('Print useful information such as build time, memory usage, SQL queries, etc.', $this->domain) => ''),
					'enable_log'            => array(sprintf(__('Log useful messages when sitemaps are generated. The log can be viewed <a href="%s">here</a>.', $this->domain), $this->get_admin_page_url(BWP_GXS_STATS)) => ''),
					'enable_debug'          => array(__('When this is on, NO caching is used and <code>WP_DEBUG</code> is respected, useful when developing new modules.', $this->domain) => ''),
					'enable_debug_extra'    => array(sprintf(__('When this is on, NO headers are sent and sitemaps are NOT compressed, useful when debugging <em>Content Encoding Error</em>. More info <a href="%s#sitemap_log_debug" target="_blank">here</a>.', $this->domain), $this->plugin_url) => ''),
				),
				'input' => array(
					'input_cache_age' => array(
						'size'  => 5,
						'label' => '&mdash;'
					),
					'input_cache_dir' => array(
						'size'     => 91,
						'disabled' => $this->_get_cache_directory_from_constant()
							? ' disabled="disabled" ' : '',
						'value'    => $this->_get_cache_directory_from_constant()
							? strtoupper(__('set via constant (BWP_GXS_CACHE_DIR)', $this->domain)) : null,
					),
					'input_alt_module_dir' => array(
						'size' => 91
					),
					'input_sql_limit' => array(
						'size' => 5,
						'label' => __('Only get this many items when querying from Database. '
							. 'This is to make sure we are not running too heavy queries.', $this->domain)
					),
				),
				'select' => array(
					'select_time_type' => array(
						__('second(s)', $this->domain) => 1,
						__('minute(s)', $this->domain) => 60,
						__('hour(s)', $this->domain)   => 3600,
						__('day(s)', $this->domain)    => 86400
					),
				),
				'inline_fields' => array(
					'input_cache_age' => array('select_time_type' => 'select')
				),
				'inline' => array(
					'cb_enable_autogen' =>  '<br /><br />'
				),
				'helps' => array(
					'enable_robots' => array(
						'type'    => 'switch',
						'target'  => 'icon',
						'content' => sprintf(
							__('If you\'re on a Multi-site installation with <strong>Sub-domain</strong> enabled, '
							. 'each blog will have its own robots.txt. '
							. 'Blogs in <strong>sub-directory</strong> will not, however. '
							. 'Please read the <a href="%s#robots.txt" target="_blank">documentation</a> for more info.', $this->domain),
							$this->plugin_url
						)
					),
					'enable_global_robots' => array(
						'type'    => 'switch',
						'target'  => 'icon',
						'content' => sprintf(
							__('If you have for example 50 blogs, 50 sitemap index entries '
							. 'will be added to your primary blog\'s <a href="%s" target="_blank">robots.txt</a>.', $this->domain),
							get_site_option('home') . '/robots.txt'
						)
					),
					'input_cache_dir' => array(
						'type' => 'focus',
						'content' => __('Expect an absolute path to a writable directory '
							. '(i.e. CHMOD to 755 or 777). ', $this->domain)
							. '<br />'
							. sprintf(
								__('Leave empty to use <code>%s</code>.', $this->domain),
								$this->_get_default_cache_directory()
							),
						'size' => 'medium'
					),
					'input_alt_module_dir' => array(
						'type'    => 'focus',
						'content' => __('Expect an absolute path to the directory '
							. 'where you put your custom modules '
							. '(e.g. <code>/home/mysite/public_html/gxs-modules/</code>). ', $this->domain)
							. '<br />'
							. __('Override a built-in module by having a module '
							. 'with the same filename in specified directory.', $this->domain),
						'size'    => 'large'
					)
				),
				'attributes' => array(
					'input_cache_dir' => array(
						'placeholder' => $this->_get_default_cache_directory()
					)
				),
				'env' => array(
					'enable_global_robots' => 'multisite'
				),
				'role' => array(
					'heading_cache' => 'superadmin',
					'heading_module' => 'superadmin',
					'heading_debug'  => 'superadmin',
				),
				'formats' => array(
					'input_cache_age'  => 'int',
					'select_time_type' => 'int',
					'input_sql_limit'  => 'int',
				)
			);

			$form_options = array(
				'enable_gmt',
				'enable_gzip',
				'enable_cache',
				'enable_cache_auto_gen',
				'input_cache_dir',
				'input_cache_age',
				'select_time_type',
				'enable_robots',
				'enable_global_robots',
				'input_sql_limit',
				'input_alt_module_dir',
				'enable_stats',
				'enable_log',
				'enable_debug',
				'enable_debug_extra',
			);

			// use same option key as XML Sitemaps page
			add_filter('bwp_option_page_submit_form_name', create_function('', "return BWP_GXS_GENERATOR;"));
		}
		elseif ($page == BWP_GXS_STATS)
		{
			$option_page->set_current_tab(4);

			$form = array(
				'items' => array(
					'heading',
				),
				'item_labels' => array
				(
					__('Message Log', $this->domain),
				),
				'item_names' => array(
					'h3',
				),
				'heading' => array(
					'h3' => 'yes' == $this->options['enable_log']
						? '<em>'
							. __('Below are messages logged when your sitemaps were generated, '
							. 'including <span style="color: #999999;">notices</span>, '
							. '<span style="color: #FF0000;">errors</span> and '
							. '<span style="color: #009900;">success messages</span>.', $this->domain)
							. '</em>'
						: '<em>'
							. __('Logging is not currently enabled. '
							. 'You can enable this feature by checking '
							. '"Enable logging" in <strong>XML Sitemaps >> Sitemap log & debug</strong>.', $this->domain)
							. '</em>',
				),
				'container' => array(
					'h3' => 'yes' == $this->options['enable_log'] ? $this->_get_formatted_message_logs() : '',
				)
			);

			if ('yes' != $this->options['enable_log'] || $this->message_logger->is_empty())
			{
				// no log is found, or logging is disabled, hide sidebar and
				// save changes button to save space
				add_filter('bwp_info_showable', create_function('', 'return "";'));
				add_filter('bwp_option_submit_button', create_function('', 'return "";'));
			}
			else
			{
				// add a clear log button, also remove the save changes button
				$this->current_option_page->register_custom_submit_action('clear_log');
				add_filter('bwp_option_submit_button', array($this, 'add_clear_log_button'));
				add_action('bwp_option_page_custom_action_clear_log', array($this, 'handle_clear_log_action'));
			}
		}

		// add flush cache buttons for super admins on main blog
		// @todo allow normal admins to flush the cache as well,
		// but only for sitemaps on their sites
		if (self::can_update_site_option()
			&& (BWP_GXS_GENERATOR == $page || BWP_GXS_GENERATOR_ADVANCED == $page)
		) {
			$this->current_option_page->register_custom_submit_action('flush_cache');
			$this->current_option_page->register_custom_submit_action('save_flush_cache');
			add_filter('bwp_option_submit_button', array($this, 'add_flush_cache_buttons'));
			add_action('bwp_option_page_custom_action_flush_cache', array($this, 'handle_flush_action'));
			add_action('bwp_option_page_custom_action_save_flush_cache', array($this, 'handle_save_flush_action'));
		}

		$option_page->init($form, $form_options);
	}

	public function show_option_pages()
	{
		$page = $this->get_current_admin_page();

		if ($page == BWP_GXS_GENERATOR)
		{
			if ($this->options['enable_cache'] == 'yes')
			{
				// show a warning if caching is enabled but cache directory is
				// not writable
				$this->cache_directory = $this->_get_cache_directory();

				if (!@file_exists($this->cache_directory) || !@is_writable($this->cache_directory))
				{
					$this->add_notice(
						'<strong>' . __('Warning') . ':</strong> '
						. sprintf(__('Cache directory (<code>%s</code>) does not exist or is not writable. '
						. 'Please try CHMODing it to either 755 or 777, or disable caching to hide '
						. 'this warning (not recommended).', $this->domain), $this->cache_directory)
					);
				}
			}
		}

		$this->current_option_page->generate_html_form();
	}

	public function add_external_page_modal()
	{
		echo $this->get_template_contents('templates/provider/admin/external-page-modal.html.php', array(
			'frequencies' => $this->_get_frequencies_as_choices(),
			'priorities'  => $this->priorities
		));
	}

	public function handle_dynamic_generator_options(array $options)
	{
		$post_types = $this->get_provider('post')->get_post_types();
		$taxonomies = $this->get_provider('taxonomy')->get_taxonomies();

		$excluded_post_types      = array();
		$excluded_post_types_ping = array();
		$excluded_taxonomies      = array();

		foreach ($post_types as $post_type)
		{
			if (!array_key_exists('ept_' . $post_type->name, $_POST))
				$excluded_post_types[] = $post_type->name;

			if (!array_key_exists('eppt_' . $post_type->name, $_POST))
				$excluded_post_types_ping[] = $post_type->name;
		}

		foreach ($taxonomies as $taxonomy)
		{
			if (!array_key_exists('etax_' . $taxonomy->name, $_POST))
				$excluded_taxonomies[] = $taxonomy->name;
		}

		$options['input_exclude_post_type']      = implode(',', $excluded_post_types);
		$options['input_exclude_post_type_ping'] = implode(',', $excluded_post_types_ping);
		$options['input_exclude_taxonomy']       = implode(',', $excluded_taxonomies);

		// no more than 50000 URLs per sitemap
		$options['input_item_limit'] = 50000 < $options['input_item_limit']
			? 50000 : $options['input_item_limit'];

		$options['input_split_limit_post'] = 50000 < $options['input_split_limit_post']
			? 50000 : $options['input_split_limit_post'];

		return $options;
	}

	public function handle_dynamic_google_news_options(array $options)
	{
		// only do this if we can actually submit
		if (empty($_POST['term_genre_can_submit']))
			return $options;

		$news_terms  = array();
		$news_genres = array();

		$terms = $this->get_provider('taxonomy')->get_all_terms($options['select_news_taxonomy']);

		foreach ($terms as $term)
		{
			if (!empty($_POST['term_' . $term->term_id]))
				$news_terms[] = $term->term_id;

			$term_genre_post_key = 'term_' . $term->term_id . '_genres';
			if (isset($_POST[$term_genre_post_key])
				&& is_array($_POST[$term_genre_post_key])
			) {
				$genres = $_POST[$term_genre_post_key];
				$genres = array_map('trim', $genres);

				$news_genres['cat_' . $term->term_id] = implode(', ', $genres);
			}
		}

		$options['select_news_cats']  = implode(',', $news_terms);
		$options['input_news_genres'] = $news_genres;

		return $options;
	}

	public function handle_google_news_option_changes()
	{
		// google news sitemap has just been enabled, try generating for the
		// first time to make sure it works
		if ($this->current_options['enable_news_sitemap'] != $this->options['enable_news_sitemap']
			&& $this->options['enable_news_sitemap'] == 'yes'
		) {
			// @todo 2.0.0 use a provider to fetch data here instead
			$response = wp_remote_get($this->get_sitemap_url('post_google_news'));

			$is_news_sitemap_ok = false;

			if (!is_wp_error($response))
			{
				$response_status = wp_remote_retrieve_response_code($response);

				// the news sitemap can be generated successfully
				if ($response_status === 200)
					$is_news_sitemap_ok = true;
			}

			if (!$is_news_sitemap_ok)
			{
				$this->add_error_flash(sprintf(
					__('Google News sitemap could not be generated, '
					. 'please check <a href="%s">Sitemap Log</a> '
					. 'for possible errors.', $this->domain),
					$this->get_admin_page_url(BWP_GXS_STATS)
				));
			}
		}
	}

	public function add_flush_cache_buttons($button)
	{
		$button = str_replace(
			'</p>',
			'&nbsp; <input type="submit" class="button-secondary action" name="save_flush_cache" '
			. 'value="' . __('Save Changes and Flush Cache', $this->domain) . '" />'
			. '&nbsp; <input type="submit" class="button-secondary action" name="flush_cache" '
			. 'value="' . __('Flush Cache', $this->domain) . '" /></p>',
			$button
		);

		return $button;
	}

	public function add_clear_log_button($button)
	{
		$button = '<p>'
			. '<input type="submit" class="button-primary action" name="clear_log" value="'
			. __('Clear Message Log', $this->domain) . '" />'
			. '</p>';

		return $button;
	}

	/**
	 * Flush sitemap cache
	 *
	 * @return mixed int|bool false if there's something wrong
	 *                        int the number of cached sitemaps flushed
	 */
	public function flush_cache()
	{
		$deleted = false;
		$dir     = trailingslashit($this->_get_cache_directory());

		if (is_dir($dir))
		{
			if ($dh = opendir($dir))
			{
				$deleted = 0;

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

	/**
	 * Flush sitemap cache inside admin area
	 *
	 * @since 1.3.0
	 * @internal
	 */
	public function handle_flush_action()
	{
		$deleted = $this->flush_cache();

		if ($deleted !== false)
		{
			$message = $deleted > 0
				? sprintf(
					__('<strong>%d</strong> cached sitemaps have '
					. 'been flushed successfully!', $this->domain),
					$deleted
				)
				: __('There are no cached sitemaps to flush.', $this->domain);

			$this->add_notice_flash($message);

			return true;
		}
		else
		{
			$this->add_error_flash(sprintf(
				__('Could not flush the cache, '
				. 'cache directory is either not found or is not writable. '
				. 'See <a href="%s" target="_blank">this FAQ entry</a> '
				. 'for a possible solution.', $this->domain),
				$this->plugin_url . 'faq/#flush-cache-error'
			));

			return false;
		}
	}

	/**
	 * @internal
	 */
	public function handle_save_flush_action()
	{
		$this->current_option_page->submit_html_form();
		$this->add_notice_flash(__('All options have been saved.', $this->domain));

		return $this->handle_flush_action();
	}

	/**
	 * Clear all sitemap logs including sitemap generation log and sitemap item log
	 *
	 * @since 1.4.0
	 * @internal
	 */
	public function handle_clear_log_action()
	{
		$this->_reset_logs();

		$this->add_notice_flash(
			'<strong>' . __('Notice', $this->domain) . ':</strong> '
			. __('All logs have been cleared successfully!', $this->domain)
		);
	}

	private function _handle_exclude_items($group, array $items_to_exclude, BWP_Sitemaps_Excluder $excluder)
	{
		// merge currently excluded items with new items to exclude
		$excluded_items = $excluder->get_excluded_items($group);
		$excluded_items = array_unique(array_merge($excluded_items, $items_to_exclude));

		$excluder->update_excluded_items($group, $excluded_items);

		$this->add_notice_flash(
			sprintf(__('Successfully excluded <strong>%d</strong> items.', $this->domain),
			count($items_to_exclude))
		);
	}

	public function handle_exclude_posts()
	{
		if (! ($post_type = BWP_Framework_Util::get_request_var('select_exclude_post_type')))
			return;

		if (! ($posts_to_exclude = BWP_Framework_Util::get_request_var('select-exclude-posts')))
		{
			$this->add_error_flash(__('Please select at least one item to exclude.', $this->domain));
			return;
		}

		$this->_handle_exclude_items($post_type, (array) $posts_to_exclude, $this->post_excluder);
	}

	public function handle_exclude_terms()
	{
		if (! ($taxonomy = BWP_Framework_Util::get_request_var('select_exclude_taxonomy')))
			return;

		if (! ($terms_to_exclude = BWP_Framework_Util::get_request_var('select-exclude-terms')))
		{
			$this->add_error_flash(__('Please select at least one item to exclude.', $this->domain));
			return;
		}

		$this->_handle_exclude_items($taxonomy, (array) $terms_to_exclude, $this->term_excluder);
	}

	private static function _format_header_time($time)
	{
		return bwp_gxs_format_header_time($time);
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
	 * @since 1.3.0
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
		$this->bridge->update_option(BWP_GXS_LOG, array(
			'messages' => $this->message_logger->get_log_item_data(),
			'sitemaps' => $this->sitemap_logger->get_log_item_data()
		));
	}

	private function _log_message_item($message, $type, $time = null)
	{
		$time = $time ? $time : current_time('mysql', true);

		$item = new BWP_Sitemaps_Logger_Message_LogItem($message, $type, $time);
		$item->set_datetimezone($this->get_current_timezone());

		$this->message_logger->log($item);
	}

	/**
	 * Log a message when generating a sitemap
	 *
	 * @param string $message
	 * @param string $type @since 1.4.0 before this is a boolean and optional
	 */
	public function log_message($message, $type, $deprecated = null)
	{
		if (isset($sitemap))
			_deprecated_argument(__FUNCTION__, 'BWP Google XML Sitemaps 1.4.0');

		// dont log anything if not allowed
		if ('yes' !== $this->options['enable_log'])
			return;

		// dont log anything if message or type is empty
		if (empty($message) || empty($type))
			return;

		$debug_message = $this->_debug_extra
			? __('extra debugging was on', $this->domain)
			: __('debugging was on', $this->domain);

		$debug = $this->_debug ? ' (' . $debug_message . ')' : '';

		$this->_log_message_item($message . $debug, $type);
	}

	public function elog($message, $die = false, $error_code = 404)
	{
		_deprecated_function(__FUNCTION__, 'BWP Google XML Sitemaps 1.4.0', 'BWP_Sitemaps::log_error');

		$this->log_error($message, $die, $error_code);
	}

	public function log_error($message, $die = false, $error_code = 404)
	{
		$this->log_message($message, BWP_Sitemaps_Logger_Message_LogItem::TYPE_ERROR);

		if (true == $die)
		{
			$this->commit_logs();
			$this->_die($message, $error_code);
		}
	}

	public function slog($message)
	{
		_deprecated_function(__FUNCTION__, 'BWP Google XML Sitemaps 1.4.0', 'BWP_Sitemaps::log_success');

		$this->log_success($message);
	}

	public function log_success($message)
	{
		$this->log_message($message, BWP_Sitemaps_Logger_Message_LogItem::TYPE_SUCCESS);
	}

	public function nlog($message)
	{
		_deprecated_function(__FUNCTION__, 'BWP Google XML Sitemaps 1.4.0', 'BWP_Sitemaps::log_notice');

		$this->log_notice($message);
	}

	public function log_notice($message)
	{
		$this->log_message($message, BWP_Sitemaps_Logger_Message_LogItem::TYPE_NOTICE);
	}

	public function smlog($url)
	{
		_deprecated_function(__FUNCTION__, 'BWP Google XML Sitemaps 1.4.0', 'BWP_Sitemaps::log_sitemap');

		$this->log_sitemap($url);
	}

	private function _log_sitemap_item($url, $time = null)
	{
		$time = $time ? $time : current_time('mysql', true);

		$item = new BWP_Sitemaps_Logger_Sitemap_LogItem($url, $time);
		$item->set_datetimezone($this->get_current_timezone());

		$this->sitemap_logger->log($item);
	}

	public function log_sitemap($url)
	{
		$this->_log_sitemap_item($url);
	}

	public function get_sitemap_logger()
	{
		return $this->sitemap_logger;
	}

	public function do_robots($output, $public)
	{
		global $blog_id, $wpdb;

		if ('0' == $public)
			return $output;

		if (self::is_subdomain_install() || (isset($blog_id) && 1 == $blog_id))
		{
			$output .= "\n";
			$output .= 'Sitemap: ' . $this->get_sitemap_index_url();
			$output .= "\n";
		}

		// add all other sitemapindex within the network into the primary
		// blog's robots.txt, including mapped domains
		if (self::is_multisite() && 'yes' == $this->options['enable_global_robots']
			&& isset($blog_id) && 1 == $blog_id
		) {
			$blogs = empty($wpdb->dmtable)
				? $wpdb->get_results("
					SELECT *
					FROM $wpdb->blogs
					WHERE public = 1
						AND spam = 0
						AND deleted = 0")
				: $wpdb->get_results('
					SELECT
						wpdm.domain as mapped_domain,
						wpblogs.*
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

				$scheme = is_ssl() ? 'https://' : 'http://';
				$path   = rtrim($blog->path, '/');

				// @since 1.3.0 allow mapped domains
				// @see https://support.google.com/webmasters/answer/75712?hl=en&ref_topic=4581190
				// @todo maybe we should use switch_blog here
				$blog_domain = empty($blog->mapped_domain)
					? $blog->domain . $path
					: $blog->mapped_domain;

				if (!empty($blog_domain))
				{
					$output .= 'Sitemap: ' . str_replace(home_url(),
						$scheme . $blog_domain,
						$this->get_sitemap_index_url()) . "\n";

					$num_sites++;
				}
			}

			if (!empty($num_sites))
				$output .= "\n";
		}

		return $output;
	}

	public function add_excluded_posts($excluded_items, $post_type)
	{
		$excluded_items = array_merge(
			$excluded_items, $this->post_excluder->get_excluded_items($post_type)
		);

		return array_values(array_unique($excluded_items));
	}

	public function add_excluded_terms($excluded_items, $taxonomy)
	{
		$excluded_items = array_merge(
			$excluded_items, $this->term_excluder->get_excluded_items($taxonomy)
		);

		return array_values(array_unique($excluded_items));
	}

	public function add_external_pages($pages)
	{
		$items = array();

		foreach ($pages as $page)
		{
			// remove sample pages
			if (!empty($page['sample']))
				continue;

			$items[] = $page;
		}

		// no pages stored in db
		if (! $this->get_provider('external_page')->has_pages())
			return $items;

		$db_pages = $this->get_provider('external_page')->get_pages_for_display();

		foreach ($db_pages as $url => $page)
		{
			$items[] = array(
				'location' => $url,
				'lastmod'  => $page['last_modified'],
				'freq'     => $page['frequency'],
				'priority' => $page['priority']
			);
		}

		return $items;
	}

	public function add_post_title_like_query_variable($where, WP_Query $wp_query)
	{
		global $wpdb;

		if ($post_title_like = $wp_query->get('bwp_post_title_like'))
		{
			$post_title_like = $wpdb->esc_like($post_title_like);
			$where .= " AND LOWER($wpdb->posts.post_title) LIKE '%" . $this->bridge->esc_sql($post_title_like) . "%'";
		}

		return $where;
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
			wp_redirect($this->get_sitemap_url($sitemap_name), 301);
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

		// site home URL sitemap - @since 1.1.5
		if ('yes' == $this->options['enable_sitemap_site'])
			$this->add_module('site');

		// module exclusion list
		$excluded_post_types = explode(',', $this->options['input_exclude_post_type']);
		$excluded_taxonomies = explode(',', $this->options['input_exclude_taxonomy']);

		// add public post types to module list
		$this->post_types = get_post_types(
			array('public' => true), 'objects'
		);

		foreach ($this->post_types as $post_type)
		{
			// Page will have its own
			if ('page' != $post_type->name && !in_array($post_type->name, $excluded_post_types))
				$modules['post'][] = $post_type->name;
		}

		// google News module, @since 1.2.0
		if ('yes' == $this->options['enable_news_sitemap'])
			$this->add_module('post', 'google_news');

		// add pages to module list
		if (!in_array('page', $excluded_post_types))
			$modules['page'] = array('page');

		// add archive pages to module list
		if ('yes' == $this->options['enable_sitemap_date'])
			$modules['archive'] = array('monthly', 'yearly');

		// add taxonomies to module list
		$this->taxonomies = get_taxonomies(array('public' => true), '');
		if ('yes' == $this->options['enable_sitemap_taxonomy'])
		{
			foreach ($this->taxonomies as $taxonomy)
			{
				if (!in_array($taxonomy->name, $excluded_taxonomies))
					$modules['taxonomy'][] = $taxonomy->name;
			}
		}

		// remove some unnecessary sitemaps
		$this->remove_module('post', 'attachment');
		$this->remove_module('taxonomy', 'post_format');
		$this->remove_module('taxonomy', 'nav_menu');

		// add / Remove modules based on users' preferences
		if ('yes' == $this->options['enable_sitemap_author'])
			$this->add_module('author');

		if ('yes' == $this->options['enable_sitemap_external'])
			$this->add_module('page', 'external');

		// hook for a custom module list
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
	 * @since 1.3.0
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
					. '(as per <a href="%s" target="_blank">Google\'s guidelines</a>)', $this->domain),
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

	private static function _get_modules_from_query_var($module)
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
		else if (isset($wp_query->query_vars[$this->_get_non_permalink_query_var()]))
		{
			// non-friendly sitemap url is used, i.e. http://example.com/?bwpsitemap=xxx
			$sitemap_name = $wp_query->query_vars[$this->_get_non_permalink_query_var()];
			$modules      = self::_get_modules_from_query_var($sitemap_name);

			if ($modules && is_array($modules))
				$this->_load_sitemap_module($modules[1], $modules[2]);
			else
				$this->_load_sitemap_module($sitemap_name);
		}
	}

	/**
	 * Checks whether requested sitemap is a BWP sitemap
	 *
	 * @since 1.3.0
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
	 * @since 1.3.0
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
	 * @since 1.3.0
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
		// cache is not enabled or debug is enabled
		if ('yes' != $this->options['enable_cache'] || $this->_debug)
		{
			return false;
		}

		$cache_status = $this->sitemap_cache->get_cache_status($module_name, $sitemap_name);

		// cache is invalid
		if (!$cache_status)
		{
			return false;
		}
		elseif (!empty($_GET['generate']) && current_user_can('manage_options'))
		{
			// @since 1.4.0 admin is regenerating the sitemap, no cache should
			// be used, even when cache status is valid. We need to do this
			// here because we want to regenerate the sitemap, which requires
			// the cache file, but it is only determined after running
			// BWP_GXS_CACHE::get_cache_status(). This should be fixed in
			// future versions.
			return false;
		}
		elseif ($cache_status == '304')
		{
			// http cache can be used, we don't need to output anything except
			// for some headers
			// @link http://edn.embarcadero.com/article/38123
			$this->_send_headers(array_merge(
				array('status' => 304), $this->sitemap_cache->get_headers()
			));
		}
		elseif ($cache_status == '200')
		{
			// file cache is ok, output the cached sitemap
			$this->_send_headers($this->sitemap_cache->get_headers());

			$cache_file = $this->sitemap_cache->get_cache_file();

			// when server or script is not already gzipping, and gzip is
			// allowed, we simply read the cached file without any additional
			// compression because cached sitemap files are stored as gzipped
			// files.
			if ($this->_is_gzip_ok() && !self::is_gzipped())
			{
				readfile($cache_file);
			}
			else
			{
				// if we can't use a gzipped file, decompress before reading it
				readgzfile($cache_file);
			}
		}

		if (in_array($cache_status, array('200', '304')))
		{
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
	 * @since 1.3.0
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

		$lastmod = $this->sitemap_cache->write_cache($this->output);

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

			$this->_is_using_custom_module = true;
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
				// no module available, log an error
				$error_log = sprintf(
					__('<strong>%s</strong> can not be served because of '
					. 'a missing module file: <strong>%s</strong>.', $this->domain)
				, $sitemap_name, $module_filename);

				// issue a WP die with a 500 internal server error response code
				$this->log_error($error_log, true, 500);
			}
		}

		return $module_file;
	}

	/**
	 * Locates correct sitemap module to serve requested sitemap
	 *
	 * @access private
	 */
	private function _load_sitemap_module($module, $sub_module = '')
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

			// @since 1.3.0 log a notice instead of an error
			$this->log_notice($message);
			$this->commit_logs();

			// @since 1.3.0 return this handle to WordPress
			return false;
		}

		$this->_init_sitemap_generation();

		if ($this->_load_sitemap_from_cache($module_name, $sitemap_name))
		{
			// if sitemap can be loaded from cache, no need to do anything else
			exit;
		}

		// global module data for later use
		$this->module_data = array(
			'module'       => $module,
			'sub_module'   => $sub_module,
			'module_key'   => $module_name, // leave this for back-compat
			'module_name'  => $module_name, // @since 1.3.0 this is the same as module_key
			'module_part'  => $part, // leave this for back-compat
			'part'         => $part,
			'sitemap_name' => $sitemap_name // @since 1.3.0 this is the actual sitemap name
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

			include_once $module_file;

			$class_name = 'BWP_GXS_MODULE_' . str_replace('-', '_', $module_name);

			if (class_exists($class_name))
			{
				$module_object = new $class_name();

				$module_object->set_module_data($this->module_data);
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

			include_once $module_file;

			$class_name = 'BWP_GXS_MODULE_INDEX';

			if (class_exists($class_name))
			{
				$this->_prepare_sitemap_modules(); // this should fill $this->requested_modules

				$module_object = new $class_name($this->requested_modules);

				$module_object->set_module_data($this->module_data);
				$module_object->set_current_time();
				$module_object->build_sitemap_data();

				$success = $this->_generate_sitemap_index($module_object->get_data());

				$module_loaded = true;
			}
		}

		$module_filename = $module_name . '.php';

		if (!$module_loaded)
		{
			// required module class can not be found so not loaded, this
			// should issue a WP die with 500 internal server error response code
			$this->log_error(
				sprintf(__('There is no class named <strong>%s</strong> '
					. 'in the module file <strong>%s</strong>.', $this->domain),
					$class_name,
					$module_filename),
				true, 500
			);
		}

		if ($success == true)
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

			$success_message = $this->_is_using_custom_module
				? __('Successfully generated <em>%s.xml</em> using custom module file <em>%s</em>.', $this->domain)
				: __('Successfully generated <em>%s.xml</em> using module file <em>%s</em>.', $this->domain);

			$this->log_success(sprintf($success_message, $sitemap_name, $module_filename));

			$this->log_sitemap($sitemap_name);

			$this->commit_logs();

			exit;
		}
		else
		{
			// @since 1.3.0 commit logs and issue a WP die with 500 internal
			// server response code
			$this->commit_logs();

			$this->log_error(sprintf(
				__('An unknown error occurred when generating <em>%s</em> '
				. 'using module file <em>%s</em>. Try again later.', $this->domain),
			$sitemap_name, $module_filename), true, 500);

			exit;
		}
	}

	private function _send_headers($headers = array())
	{
		if (headers_sent($filename, $linenum))
		{
			// @since 1.3.0 if headers have already been sent, we can't send
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
			// @since 1.3.0 when debug extra is turned on no headers should
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

		if ($this->_debug || $this->options['enable_cache'] != 'yes')
		{
			// if debug is on, or caching is not enabled, send no cache headers
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

		// some headers are only needed when sending a 200 OK response
		if ($headers['status'] == 200)
		{
			// only send a last modified header if debug is NOT on, and caching
			// is enabled
			if (!$this->_debug && $this->options['enable_cache'] == 'yes')
				header('Last-Modified: ' . $headers['lastmod']);

			header('Accept-Ranges: bytes');
			header('Content-Type: ' . $content_types['google'] . '; charset=UTF-8');

			if ($this->_is_gzip_ok())
				header('Content-Encoding: ' . self::_get_gzip_type());
		}

		header('Vary: ' . $headers['vary']);

		// @since 1.4.0 add a noindex header to prevent sitemaps from being
		// indexed by search engines (Google supports this fully)
		// @link https://developers.google.com/webmasters/control-crawl-index/docs/robots_meta_tag?hl=en#using-the-x-robots-tag-http-header
		header('X-Robots-Tag: noindex');

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
				? __('Enable/disable sitemaps via <em>BWP Sitemaps >> XML Sitemaps</em>.', $this->domain)
				: '';

			$error_message_module = $module_label
				? ' ' . sprintf(
						__('There are no public <em>%s</em>.', $this->domain)
						. " $module_guide",
						$module_label)
				: ' ' . $module_guide;

			$error_message_admin = $this->module_data['sitemap_name'] == 'sitemapindex'
				? ' ' . __('Please make sure that you have at least one sitemap enabled '
					. 'in <em>BWP Sitemaps >> XML Sitemaps >> Sitemaps to generate</em>.', $this->domain)
				: $error_message_module;

			// issue a WP die with 404 not found response code
			$this->log_error($error_message . $error_message_admin, true, 404);
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
			. ' (c) ' . date('Y') . ' Khang Minh - betterwp.net' . "\n";

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

		if ('yes' == $this->options['enable_credit'])
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

		// @since 1.3.1 news name can be configured from admin area, but can
		// still be overridden if needed via module or filters
		$news_name = $this->options['input_news_name'];
		$news_name = empty($news_name)
			? apply_filters('bwp_gxs_news_name', htmlspecialchars(get_bloginfo('name')))
			: $news_name;

		foreach ($urls as &$url)
		{
			$url['location'] = !empty($url['location']) ? $url['location'] : '';

			if (empty($url['location']) || !self::_is_url_valid($url['location']))
				// location is empty or it is not valid for a sitemap
				continue;

			$url['name']     = !empty($url['name']) ? htmlspecialchars($url['name']) : $news_name;

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

		if ('yes' == $this->options['enable_credit'])
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

	private function _is_post_pingable($post)
	{
		$post_types = $this->bridge->get_post_types(array('public' => true));

		if (!in_array($post->post_type, $post_types))
		{
			// not a public post type, no pinging
			return false;
		}

		if (!empty($post->post_password))
		{
			// password-protected post, no pinging
			return false;
		}

		$excluded_post_types = explode(',', $this->options['input_exclude_post_type']);

		if (in_array($post->post_type, $excluded_post_types))
		{
			// sitemap for this post type is not enabled, no pinging
			return false;
		}

		$excluded_post_types_from_ping = explode(',', $this->options['input_exclude_post_type_ping']);

		if (in_array($post->post_type, $excluded_post_types_from_ping))
		{
			// pinging for this post type is disabled explicitly
			return false;
		}

		// otherwise pinging is allowed
		return true;
	}

	public function ping_google_news($post)
	{
		if (empty($post->ID))
			return;

		// only carry out the ping if this post is in a news category
		$is_news    = 'inc' == $this->options['select_news_cat_action'] ? false : true;
		$news_cats  = explode(',', $this->options['select_news_cats']);
		$categories = $this->bridge->get_the_category($post->ID);

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
			$this->ping($post);
		}
	}

	public function ping($post)
	{
		$time      = $this->bridge->current_time('timestamp');
		$ping_data = $this->bridge->get_option(BWP_GXS_PING);

		if (!$ping_data || !is_array($ping_data)
			|| isset($ping_data['data_pinged']['yahoo'])
			|| isset($ping_data['data_pinged']['ask'])
		) {
			// remove old data from yahoo and ask, to be removed in 1.4.0
			$ping_data = array(
				'data_pinged'      => array('google' => 0, 'bing' => 0),
				'data_last_pinged' => array('google' => 0, 'bing' => 0)
			);
		}

		if (!$this->_is_post_pingable($post))
		{
			// this post is not suitable for pinging
			return false;
		}

		foreach ($this->_ping_urls as $key => $service)
		{
			if ('yes' == $this->options['enable_ping_' . $key])
			{
				if ($time - $ping_data['data_last_pinged'][$key] > 86400)
				{
					// a day has gone, reset the count
					$ping_data['data_pinged'][$key] = 0;
					$ping_data['data_last_pinged'][$key] = $time;
				}

				if ($this->pings_per_day > $ping_data['data_pinged'][$key])
				{
					// ping limit has not been reached
					$ping_data['data_pinged'][$key]++;

					$url = sprintf($service, urlencode(str_replace(
						'&', '&amp;', $this->get_sitemap_url($this->_ping_sitemap)
					)));

					$response = $this->bridge->wp_remote_post($url,
						array('timeout' => $this->ping_timeout)
					);

					if ($this->bridge->is_wp_error($response))
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
								__('Pinged <em>%s</em> with <em>%s</em> successfully!', $this->domain), ucfirst($key),
								$this->_ping_sitemap . '.xml')
							);
						}
						else
						{
							$errno    = $the_response['code'];
							$errorstr = $the_response['message'];

							$this->log_error(sprintf(
								__('<strong>Error %s</strong> from <em>%s</em>.', $this->domain), $errno, ucfirst($key))
								. ': ' . $errorstr
							);
						}
					}
				}
				else
				{
					// ping limit reached for this particular search engine,
					// log an appropriate error message
					$this->log_error(sprintf(
						__('Ping limit for today to <em>%s</em> has been reached, '
						. 'consider increasing the ping limit via '
						. '<em>XML Sitemaps >> Ping search engines >> "Ping limit for each search engine"</em>', $this->domain),
						ucfirst($key))
					);
				}
			}
		}

		$this->bridge->update_option(BWP_GXS_PING, $ping_data);

		$this->commit_logs();
	}

	/**
	 * @param BWP_Sitemaps_Excluder $excluder
	 */
	public function set_post_excluder(BWP_Sitemaps_Excluder $excluder)
	{
		$this->post_excluder = $excluder;
	}

	/**
	 * @param BWP_Sitemaps_Excluder $excluder
	 */
	public function set_term_excluder(BWP_Sitemaps_Excluder $excluder)
	{
		$this->term_excluder = $excluder;
	}

	/**
	 * Get a content provider
	 *
	 * @param string $name
	 * @return BWP_Sitemaps_Provider
	 */
	public function get_provider($name)
	{
		if (!isset($this->providers[$name]))
			throw new DomainException(sprintf('invalid provider name "%s"', $name));

		return $this->providers[$name];
	}

	/**
	 * Get an ajax action handler
	 *
	 * @param string $name
	 * @return BWP_Sitemaps_Handler_AjaxHandler
	 */
	public function get_ajax_handler($name)
	{
		if (!isset($this->ajax_handlers[$name]))
			throw new DomainException(sprintf('invalid ajax handler name "%s"', $name));

		return $this->ajax_handlers[$name];
	}
}
