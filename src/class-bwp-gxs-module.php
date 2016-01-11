<?php
/**
 * Copyright (c) 2011 Khang Minh <betterwp.net>
 * @license http://www.gnu.org/licenses/gpl.html GNU GENERAL PUBLIC LICENSE VERSION 3.0 OR LATER
 * @package BWP Google XML Sitemaps
 */

class BWP_GXS_MODULE
{
	/**
	 * Sitemap type, either 'url', 'news', or 'index'
	 *
	 * @var string
	 */
	public $type = 'url';

	/**
	 * The actual requested sitemap module
	 *
	 * @var string
	 */
	public $requested;

	/**
	 * Hold module data coming from main GXS class
	 *
	 * @var array
	 */
	protected $module_data;

	/**
	 * Requested sitemap part, used for split sitemaps
	 *
	 * @var integer
	 */
	protected $part;

	/**
	 * Holds sitemap items
	 *
	 * @var array
	 */
	protected $data = array();

	/**
	 * @var bool
	 */
	protected $image_allowed;

	/**
	 * Priority mapping
	 *
	 * @var array
	 */
	public $freq_to_pri = array(
		'always'  => 1.0,
		'hourly'  => 0.8,
		'daily'   => 0.7,
		'weekly'  => 0.6,
		'monthly' => 0.4,
		'yearly'  => 0.3,
		'never'   => 0.2
	);

	public $comment_count = 0, $now, $offset = 0, $url_sofar = 0, $circle = 0;
	public $perma_struct = '', $post_type = NULL;
	public $limit = 0;

	/**
	 * Column to sort sitemap items by
	 *
	 * @since 1.3.0
	 * @var string
	 */
	public $sort_column = false;

	/**
	 * Allows you to init the current item with previous item’s data.
	 *
	 * @param array $pre_data Previous item's data
	 * @return array
	 */
	protected function init_data($pre_data = array())
	{
		global $bwp_gxs;

		if (empty($this->now))
			$this->set_current_time();

		$data['location'] = '';
		$data['lastmod']  = ''; // @since 1.3.0 no more assumption of last mod

		if (empty($pre_data) || sizeof($pre_data) == 0)
			return $data;

		if (isset($pre_data['freq'])) $data['freq'] = $pre_data['freq'];
		if (isset($pre_data['priority'])) $data['priority'] = $pre_data['priority'];

		return $data;
	}

	/**
	 * @deprecated 1.3.0 in favor of ::get_sitemap_url
	 */
	protected function get_xml_link($slug)
	{
		return $this->get_sitemap_url($slug);
	}

	/**
	 * Get a sitemap's URL based on its slug.
	 *
	 * @param string $slug The sitemap slug, which is actually your module's
	 *                     name (e.g. 'post', 'post_movie', etc.)
	 *
	 * @return string A valid sitemap URL with `.xml` extension appended.
	 *
	 * @since 1.3.0
	 */
	protected function get_sitemap_url($slug)
	{
		global $bwp_gxs;

		return $bwp_gxs->get_sitemap_url($slug);
	}

	/**
	 * Calculate change frequency.
	 *
	 * This allows you to calculate change frequency based on item's last
	 * modified time or a specific last modified time.
	 *
	 * @param object|null $item
	 * @param string|null $lastmod Last modified datetime in local timezone.
	 *                             This parameter is only used when `$item` is
	 *                             not set.
	 *
	 * @copyright (c) 2006 - 2009 www.phpbb-seo.com
	 */
	protected function cal_frequency($item = null, $lastmod = null)
	{
		global $bwp_gxs;

		if (empty($this->now))
			$this->now = $this->set_current_time();

		$lastmod = $item && is_object($item) ? $item->post_modified : $lastmod;

		if (empty($lastmod))
		{
			$freq = $bwp_gxs->options['select_default_freq'];
		}
		else
		{
			$time = $this->now - strtotime($lastmod);

			$freq = $time <= 30000000
				? ($time > 2592000 ? 'monthly'
					: ($time > 604800 ? 'weekly'
					: ($time > 86400 ? 'daily'
					: ($time > 43200 ? 'hourly'
					: 'always'))))
				: 'yearly';
		}

		/**
		 * Filter the change frequency of a sitemap entry.
		 *
		 * @param string $frequency The change frequency to filter.
		 * @param object|null $item The current item object or null
		 *
		 * @return string Should be one of the change frequencies listed
		 * here: http://www.sitemaps.org/protocol.html#changefreqdef
		 */
		return apply_filters('bwp_gxs_freq', $freq, $item);
	}

	/**
	 * Calculate the priority for a specific item.
	 *
	 * Allows you to calculate priority based on item's freshness, comment
	 * count, and change frequency.
	 *
	 * @param object $item
	 * @param string $freq The calculated change frequency.
	 */
	protected function cal_priority($item, $freq = 'daily')
	{
		global $bwp_gxs;

		if (empty($this->now))
			$this->now = $this->set_current_time();

		if (!is_object($item))
		{
			// determine score by change frequency
			$score = $this->freq_to_pri[$freq];
		}
		else
		{
			$comment = !empty($item->comment_count) ? $item->comment_count : 1;
			$last_mod = !empty($item->post_modified) ? strtotime($item->post_modified) : 0;

			// There is no magic behind this, number of comments and freshness
			// define priority yes, 164 is a lucky (and random) number :).
			// Actually this number works well with current Unix Timestamp,
			// which is larger than 13m.
			$score = $this->now + ($this->now - $last_mod) / $comment * 164;
			$score = $this->now / $score;
		}

		$score = $score < $bwp_gxs->options['select_min_pri']
			? $bwp_gxs->options['select_min_pri']
			: $score;

		/**
		 * Filter the priority of a sitemap entry.
		 *
		 * @param float $score The score to filter.
		 * @param array $item The current item.
		 * @param string $frequency The current change frequency.
		 *
		 * @return int See http://www.sitemaps.org/protocol.html#prioritydef
		 */
		return apply_filters('bwp_gxs_priority_score', $score, $item, $freq);
	}

	/**
	 * Get datetime from a post object's field
	 *
	 * @param object $post
	 * @param bool $publish whether to get the publish date or the last
	 *                      modified date, default to false
	 *
	 * @return string
	 * @since 1.4.0
	 */
	protected function get_datetime_from_post($post, $publish = false)
	{
		global $bwp_gxs;

		$utc_timezone     = new DateTimeZone('UTC');
		$current_timezone = $bwp_gxs->get_current_timezone();

		// use the $post's lastmod if already set
		if (isset($post->lastmod))
		{
			$datetime  = $post->lastmod;

			// manual lastmod is expected to be in local time
			$timezone = $current_timezone;
		}
		else
		{
			$datetime_field = $publish ? 'post_date_gmt' : 'post_modified_gmt';

			// get datetime from $datetime_field or fallback to 'post_date_gmt'
			$datetime = isset($post->$datetime_field) && $post->$datetime_field !== '0000-00-00 00:00:00'
				? $post->$datetime_field
				: null;
			$datetime = ! $datetime && isset($post->post_date_gmt) ? $post->post_date_gmt : $datetime;

			$timezone = $utc_timezone;
		}

		// no valid datetime to continue
		if (! $datetime)
			return '';

		// try creating a valid datetime object with proper timezone from datetime
		try {
			$datetime = new DateTime($datetime, $timezone);

			// set timezone to GMT/UTC or local timezone depending on setting
			$datetime->setTimezone(
				$bwp_gxs->options['enable_gmt'] == 'yes' ? $utc_timezone : $current_timezone
			);
		} catch (Exception $e) {
			return '';
		}

		return $this->format_datetime_designator($datetime->format('c'));
	}

	/**
	 * Get formatted last modified datetime of a post
	 *
	 * This allows you to get the proper last modified date from a post object.
	 *
	 * @param object $post
	 * @since 1.3.0
	 */
	protected function get_lastmod($post)
	{
		return $this->get_datetime_from_post($post);
	}

	/**
	 * Get formatted published datetime of a post
	 *
	 * @param object $post
	 * @since 1.3.0
	 */
	protected function get_published_datetime($post)
	{
		return $this->get_datetime_from_post($post, true);
	}

	/**
	 * Format a provided datetime with correct timezone info
	 *
	 * @param mixed string|int|DateTime $datetime datetime of all formats that
	 *                                            PHP supports, expected in the
	 *                                            UTC timezone
	 */
	protected function format_datetime($datetime)
	{
		global $bwp_gxs;

		if (! $datetime)
			return '';

		// convert provided datetime to unix timestamp format if it's an integer
		if (is_numeric($datetime))
			$datetime = '@' . $datetime;

		try {
			// convert $datetime to a DateTime object with UTC timezone when
			// $datetime is not already a DateTime object
			$datetime = ! ($datetime instanceof DateTime)
				? new DateTime($datetime, new DateTimeZone('UTC'))
				: $datetime;

			// need local timezone
			if ($bwp_gxs->options['enable_gmt'] != 'yes') {
				$datetime->setTimezone($bwp_gxs->get_current_timezone());
			}

			return $this->format_datetime_designator($datetime->format('c'));
		} catch (Exception $e) {
			return '';
		}
	}

	/**
	 * Format a local datetime with correct timezone info.
	 *
	 * @param string|int|DateTime $datetime Datetime of all formats that PHP
	 * supports, for example you can use `1215282385` (Unix Timestamp) or
	 * `2015-12-12`. See http://php.net/manual/en/datetime.formats.php for
	 * more info. This parameter is expected in **local timezone**.
	 */
	protected function format_local_datetime($datetime)
	{
		global $bwp_gxs;

		$utc_timezone = new DateTimeZone('UTC');
		$current_timezone = $bwp_gxs->get_current_timezone();

		// if $datetime is a Unix Timestamp, need to convert it to a proper
		// datetime first
		if (is_numeric($datetime))
		{
			// WordPress sets the default timezone to UTC (in wp-settings.php) so
			// we need to temporarily switch to the local timezone here because
			// $datetime is in local time
			@date_default_timezone_set($current_timezone->getName());

			$datetime = date('Y-m-d H:i:s', $datetime);

			// switch default timezone back to UTC
			@date_default_timezone_set('UTC');
		}

		// need to convert $datetime to UTC timezone
		$datetime = new DateTime($datetime, $current_timezone);
		$datetime->setTimezone($utc_timezone);

		return $this->format_datetime($datetime);
	}

	/**
	 * This function is here to remain BC only, and is not used in any default modules
	 *
	 * @deprecated 1.4.0 use BWP_GXS_MODULE::format_datetime() instead
	 *
	 * @param int $lastmod last modified datetime in unix timestamp, expected in local time
	 */
	protected function format_lastmod($lastmod)
	{
		return $this->format_local_datetime($lastmod);
	}

	/**
	 * Use Z for timezone designator instead of offset
	 *
	 * @since 1.4.0
	 */
	private function format_datetime_designator($datetime)
	{
		return str_replace('+00:00', 'Z', $datetime);
	}

	protected function post_type_uses($post_type, $taxonomy_object)
	{
		if (isset($taxonomy_object->object_type)
			&& is_array($taxonomy_object->object_type)
			&& in_array($post_type, $taxonomy_object->object_type)
		) {
			return true;
		}

		return false;
	}

	protected function get_post_by_post_type($post_type, $result)
	{
		if (!isset($result) || !is_array($result))
			return false;

		for ($i = 0; $i < sizeof($result); $i++)
		{
			$post = $result[$i];

			if ($post_type == $post->post_type)
				return $post;
		}

		return false;
	}

	protected function sort_data_by($column = 'lastmod')
	{
		if (!isset($this->data[0][$column]))
			return false;

		// Obtain a list of columns
		$lastmod = array();
		for ($i = 0; $i < sizeof($this->data); $i++)
			$lastmod[$i] = $this->data[$i][$column];

		// Add $data as the last parameter, to sort by the common key
		array_multisort($lastmod, SORT_DESC, $this->data);
	}

	protected function using_permalinks()
	{
		$perma_struct = get_option('permalink_structure');

		return !empty($perma_struct);
	}

	/**
	 * Get term links without using any SQL queries and the cache.
	 */
	protected function get_term_link($term, $taxonomy = '')
	{
		global $wp_rewrite;

		$taxonomy = $term->taxonomy;
		$termlink = $wp_rewrite->get_extra_permastruct($taxonomy);
		$slug     = $term->slug;
		$t        = get_taxonomy($taxonomy);

		if (empty($termlink))
		{
			if ('category' == $taxonomy)
				$termlink = '?cat=' . $term->term_id;
			elseif ($t->query_var)
				$termlink = "?$t->query_var=$slug";
			else
				$termlink = "?taxonomy=$taxonomy&term=$slug";

			$termlink = home_url($termlink);
		}
		else
		{
			if ($t->rewrite['hierarchical'] && !empty($term->parent))
			{
				$hierarchical_slugs = array();
				$ancestors          = get_ancestors($term->term_id, $taxonomy);

				foreach ((array)$ancestors as $ancestor)
				{
					$ancestor_term = get_term($ancestor, $taxonomy);
					$hierarchical_slugs[] = $ancestor_term->slug;
				}

				$hierarchical_slugs   = array_reverse($hierarchical_slugs);
				$hierarchical_slugs[] = $slug;
				$termlink             = str_replace("%$taxonomy%", implode('/', $hierarchical_slugs), $termlink);
			}
			else
			{
				$termlink = str_replace("%$taxonomy%", $slug, $termlink);
			}

			$termlink = home_url( user_trailingslashit($termlink, 'category') );
		}

		// Back Compat filters.
		if ('post_tag' == $taxonomy)
			$termlink = apply_filters('tag_link', $termlink, $term->term_id);
		elseif ('category' == $taxonomy)
			$termlink = apply_filters('category_link', $termlink, $term->term_id);

		return apply_filters('term_link', $termlink, $term, $taxonomy);
	}

	protected function get_post_permalink($leavename = false, $sample = false)
	{
		global $wp_rewrite, $post;

		if (is_wp_error($post))
			return $post;

		$post_link        = $wp_rewrite->get_extra_permastruct($post->post_type);
		$slug             = $post->post_name;
		$draft_or_pending = isset($post->post_status) && in_array($post->post_status, array('draft', 'pending', 'auto-draft'));
		$post_type        = get_post_type_object($post->post_type);

		if (!empty($post_link) && (!$draft_or_pending || $sample))
		{
			if (!$leavename)
				$post_link = str_replace("%$post->post_type%", $slug, $post_link);

			$post_link = home_url(user_trailingslashit($post_link));
		}
		else
		{
			if ($post_type->query_var && (isset($post->post_status) && !$draft_or_pending))
				$post_link = add_query_arg($post_type->query_var, $slug, '');
			else
				$post_link = add_query_arg(array('post_type' => $post->post_type, 'p' => $post->ID), '');

			$post_link = home_url($post_link);
		}

		return apply_filters('post_type_link', $post_link, $post, $leavename, $sample);
	}

	/**
	 * @todo improve this function to rely more on WordPress API
	 */
	protected function get_permalink($leavename = false)
	{
		global $post;

		$rewritecode = array(
			'%year%',
			'%monthnum%',
			'%day%',
			'%hour%',
			'%minute%',
			'%second%',
			$leavename? '' : '%postname%',
			'%post_id%',
			'%category%',
			'%author%',
			$leavename? '' : '%pagename%',
		);

		if (!isset($post) || !is_object($post))
			return '';

		$custom_post_types = get_post_types(array(
			'_builtin' => false,
			'public'   => true
		));

		if (!isset($this->post_type))
			$this->post_type = get_post_type_object($post->post_type);

		if ('post' != $post->post_type && !in_array($post->post_type, $custom_post_types))
			return '';

		if (in_array($post->post_type, $custom_post_types))
		{
			if ($this->post_type->hierarchical)
			{
				wp_cache_add($post->ID, $post, 'posts');

				return get_post_permalink($post->ID, $leavename);
			}
			else
				return $this->get_post_permalink();
		}

		// In case module author doesn't initialize this variable
		$permalink = empty($this->perma_struct) ? get_option('permalink_structure') : $this->perma_struct;
		$permalink = apply_filters('pre_post_link', $permalink, $post, $leavename);

		if ('' != $permalink && !in_array($post->post_status, array('draft', 'pending', 'auto-draft')))
		{
			$unixtime = strtotime($post->post_date);
			$category = '';

			if (strpos($permalink, '%category%') !== false)
			{
				// If this post belongs to a category with a parent, we have to rely on WordPress to build our permalinks
				// This can cause white page for sites that have a lot of posts, don't blame this plugin, blame WordPress ;)
				if (empty($post->slug) || !empty($post->parent))
				{
					$cats = get_the_category($post->ID);

					if ($cats)
					{
						usort($cats, '_usort_terms_by_ID'); // order by ID
						$category = $cats[0]->slug;

						if ($parent = $cats[0]->parent)
							$category = get_category_parents($parent, false, '/', true) . $category;
					}
				}
				else
					$category = (strpos($permalink, '%category%') !== false) ? $post->slug : '';

				if (empty($category))
				{
					$default_category = get_category( get_option( 'default_category' ) );
					$category         = is_wp_error($default_category) ? '' : $default_category->slug;
				}
			}
			$author = '';

			if (strpos($permalink, '%author%') !== false)
			{
				$authordata = get_userdata($post->post_author);
				$author = $authordata->user_nicename;
			}

			$date = explode(' ', date('Y m d H i s', $unixtime));

			$rewritereplace = array(
				$date[0],
				$date[1],
				$date[2],
				$date[3],
				$date[4],
				$date[5],
				$post->post_name,
				$post->ID,
				$category,
				$author,
				$post->post_name
			);

			$permalink = home_url(str_replace($rewritecode, $rewritereplace, $permalink));
			$permalink = user_trailingslashit($permalink, 'single');
		}
		else // if they're not using the fancy permalink option
			$permalink = home_url('?p=' . $post->ID);

		return apply_filters('post_link', $permalink, $post, $leavename);
	}

	/**
	 * Always call this function when you query for something.
	 *
	 * $query_str should be already escaped using either $wpdb->escape() or
	 * $wpdb->prepare().
	 */
	protected function get_results($query_str)
	{
		global $bwp_gxs, $wpdb;

		$start = !empty($this->url_sofar) ? $this->offset + (int) $this->url_sofar : $this->offset;
		$end   = (int) $bwp_gxs->options['input_sql_limit'];

		// @since 1.1.5 if we exceed the actual limit, limit $end to the
		// correct limit
		if ($this->url_sofar + $end > $this->limit)
			$end = $this->limit - $this->url_sofar;

		$query_str  = trim($query_str);
		$query_str .= ' LIMIT ' . $start . ',' . $end;

		return $wpdb->get_results($query_str);
	}

	/**
	 * Always call this function when you query for something.
	 *
	 * $query_str should be similar to what WP_Query accepts.
	 */
	protected function query_posts($query_str)
	{
		$this->circle += 1;

		if (is_array($query_str))
		{
			$query_str['posts_per_page'] = (int) $bwp_gxs->options['input_sql_limit'];
			$query_str['paged']          = $this->circle;
		}
		else if (is_string($query_str))
		{
			$query_str  = trim($query_str);
			$query_str .= '&posts_per_page=' . (int) $bwp_gxs->options['input_sql_limit'];
			$query_str .= '&paged=' . $this->circle;
		}

		$query = new WP_Query($query_str);

		return $query;
	}

	/**
	 * Init any properties that are used in building data
	 *
	 * This must be called after module has been set and right before building
	 * actual sitemap data
	 *
	 * @since 1.3.0
	 */
	protected function init_properties()
	{
		// intentionally left blank
	}

	protected function generate_data()
	{
		return false;
	}

	protected function build_data()
	{
		global $bwp_gxs;

		// @since 1.3.0 for post-based sitemaps, when splitting is enabled use
		// global limit if split limit is empty
		$split_limit = empty($bwp_gxs->options['input_split_limit_post'])
			? $bwp_gxs->options['input_item_limit']
			: $bwp_gxs->options['input_split_limit_post'];

		// use part limit or global item limit - @since 1.1.0
		$this->limit = empty($this->part)
			? $bwp_gxs->options['input_item_limit']
			: $split_limit;

		// if this is a Google News sitemap, limit is 1000
		// @see https://support.google.com/news/publisher/answer/74288?hl=en#sitemapguidelines
		// so use 1000 if current limit is set higher
		$this->limit = 'news' == $this->type && $this->limit > 1000
			? 1000 : $this->limit;

		$this->offset = !empty($this->part)
			? ($this->part - 1) * $split_limit
			: 0;

		while ($this->url_sofar < $this->limit && false != $this->generate_data())
			$this->url_sofar = sizeof($this->data);

		// sort the data by preference
		if (!empty($this->sort_column))
			$this->sort_data_by($this->sort_column);
	}

	/**
	 * Generate sitemap data based on requested input and current module data
	 *
	 * This is meant to be called from main class. To remain back-compat with
	 * custom modules, this function must check whether data has already been
	 * generated.
	 *
	 * @since 1.3.0
	 */
	public function build_sitemap_data()
	{
		if (sizeof($this->data) > 0)
			return true;

		$this->init_properties();

		$this->build_data();
	}

	public function set_current_time()
	{
		$this->now = current_time('timestamp');
	}

	public function set_sort_column($sort_column)
	{
		$this->sort_column = $sort_column;
	}

	public function set_module_data($module_data)
	{
		$this->module_data = $module_data;

		$this->requested = !empty($module_data['sub_module'])
			? $module_data['sub_module']
			: $module_data['module'];

		$this->part = !empty($module_data['part']) ? (int) $module_data['part'] : 0;
	}

	/**
	 * Whether this is a post-based module
	 *
	 * @since 1.4.0
	 */
	public function is_post_module()
	{
		return strpos($this->module_data['module_name'], 'post') === 0
			|| $this->requested == 'page';
	}

	/**
	 * Whether this module allows Google image tag
	 *
	 * @since 1.4.0
	 * @return bool
	 */
	public function is_image_allowed()
	{
		if (! is_null($this->image_allowed))
			return $this->image_allowed;

		if (! $this->is_post_module())
			return false;

		global $bwp_gxs;

		$post_type = $this->type == 'news'
			? $bwp_gxs->options['select_news_post_type']
			: $this->requested;

		// no valid post type could be detected
		if (! $post_type)
			return false;

		$this->image_allowed = $bwp_gxs->is_image_sitemap_allowed_for($post_type);

		return $this->image_allowed;
	}

	public function get_data()
	{
		return $this->data;
	}

	public function get_type()
	{
		return $this->type;
	}
}
