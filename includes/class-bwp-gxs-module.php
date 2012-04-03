<?php
/**
 * Copyright (c) 2012 Khang Minh <betterwp.net>
 * @license http://www.gnu.org/licenses/gpl.html GNU GENERAL PUBLIC LICENSE VERSION 3.0 OR LATER
 */

class BWP_GXS_MODULE {

	/**
	 * Data used to build a sitemap
	 */
	var $data = array();
	
	/**
	 * Is this a sitemapindex or a url set?
	 */
	var $type = 'url';
	
	/**
	 * Priority mapping
	 */
	var $freq_to_pri = array('always' => 1.0, 'hourly' => 0.8, 'daily' => 0.7, 'weekly' => 0.6, 'monthly' => 0.4, 'yearly' => 0.3, 'never' => 0.2);
	
	var $comment_count = 0, $now, $offset = 0, $url_sofar = 0, $circle = 0;
	var $perma_struct = '', $post_type = NULL;
	var $limit = 0;

	function __contruct()
	{
		/* Intetionally left blank */
	}

	function set_current_time()
	{
		$this->now = (int) time();
	}

	function init_data($pre_data = array())
	{
		global $bwp_gxs;

		if (empty($this->now))
			$this->set_current_time();

		$data['location'] = '';
		$data['lastmod'] = (!empty($pre_data['lastmod'])) ? strtotime($pre_data['lastmod']) - $bwp_gxs->oldest_time : $this->now - $bwp_gxs->oldest_time;
		$data['lastmod'] = $this->format_lastmod($data['lastmod']);

		if (empty($pre_data) || sizeof($pre_data) == 0)
			return $data;

		if (isset($pre_data['freq'])) $data['freq'] = $pre_data['freq'];
		if (isset($pre_data['priority'])) $data['priority'] = $pre_data['priority'];

		return $data;
	}

	function get_xml_link($slug)
	{
		global $bwp_gxs;
		
		if (!$bwp_gxs->use_permalink)
			return home_url() . '/?' . $bwp_gxs->query_var_non_perma . '=' . $slug;
		else
		{
			$permalink = get_option('permalink_structure');
			// If user is using index.php in their permalink structure, we will have to include it also
			$indexphp = (strpos($permalink, 'index.php') === false) ? '' : '/index.php';
			return home_url() . $indexphp . '/' . $slug . '.xml';
		}
	}

	/**
	 * Calculate the change frequency for a specific item.
	 *
	 * @copyright (c) 2006 - 2009 www.phpbb-seo.com
	 */
	function cal_frequency($item = '', $lastmod = '')
	{
		global $bwp_gxs;

		if (empty($this->now))
			$this->now = $this->set_current_time();

		$lastmod = (is_object($item)) ? $item->post_modified : $lastmod;

		if (empty($lastmod))
			$freq = $bwp_gxs->options['select_default_freq'];			
		else
		{
			$time = $this->now - strtotime($lastmod);
			$freq = $time > 30000000 ? 'yearly' : ($time > 2592000 ? 'monthly' : ($time > 604800 ? 'weekly' : ($time > 86400 ? 'daily' : ($time > 43200 ? 'hourly' : 'always'))));
		}
		return apply_filters('bwp_gxs_freq', $freq, $item);
	}

	/**
	 * Calculate the priority for a specific item.
	 *
	 * This is just a basic way to calculate priority and module should use its own function instead.
	 * Search engines don't really care about priority and change frequency much, do they ;)?
	 */	
	function cal_priority($item, $freq = 'daily')
	{
		global $bwp_gxs;

		if (empty($this->now))
			$this->now = $this->set_current_time();

		if (!is_object($item)) // determine score by change frequency
			$score = $this->freq_to_pri[$freq];
		else
		{
			$comment = (!empty($item->comment_count)) ? $item->comment_count : 1;
			// There is no magic behind this, number of comments and freshness define priority
			// yes, 164 is a lucky (and random) number :). Actually this number works well with current Unix Timestamp,
			// which is larger than 13m.
			$score = $this->now + ($this->now - (int) strtotime($item->post_modified)) / $comment * 164;
			$score = $this->now / $score;
		}
		
		$score = ($score < $bwp_gxs->options['select_min_pri']) ? $bwp_gxs->options['select_min_pri'] : $score;

		// For people who doesn't like using module
		return apply_filters('bwp_gxs_priority_score', $score, $item, $freq);
	}

	function format_lastmod($lastmod)
	{
		global $bwp_gxs;
		// Hit or miss :-?
		$lastmod = $lastmod - get_option('gmt_offset') * 3600;
		if ('yes' == $bwp_gxs->options['enable_gmt'])
			return gmdate('c', (int) $lastmod);
		else
			return date('c', (int) $lastmod);
	}

	function post_type_uses($post_type, $taxonomy_object)
	{
		if (isset($taxonomy_object->object_type) && is_array($taxonomy_object->object_type) && in_array($post_type, $taxonomy_object->object_type))
			return true;
		return false;
	}

	function get_post_by_post_type($post_type, $result)
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

	function sort_data_by($column = 'lastmod')
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

	function using_permalinks()
	{
		$perma_struct = get_option('permalink_structure');
		return (!empty($perma_struct));
	}

	/**
	 * Get term links without using any SQL queries and the cache.
	 */	
	function get_term_link($term, $taxonomy = '')
	{
		global $wp_rewrite;

		$taxonomy = $term->taxonomy;
		$termlink = $wp_rewrite->get_extra_permastruct($taxonomy);
		$slug = $term->slug;
		$t = get_taxonomy($taxonomy);

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
				$ancestors = get_ancestors($term->term_id, $taxonomy);
				foreach ((array)$ancestors as $ancestor)
				{
					$ancestor_term = get_term($ancestor, $taxonomy);
					$hierarchical_slugs[] = $ancestor_term->slug;
				}
				$hierarchical_slugs = array_reverse($hierarchical_slugs);
				$hierarchical_slugs[] = $slug;
				$termlink = str_replace("%$taxonomy%", implode('/', $hierarchical_slugs), $termlink);
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

	function get_post_permalink($leavename = false, $sample = false)
	{
		global $wp_rewrite, $post;

		if (is_wp_error($post))
			return $post;

		$post_link = $wp_rewrite->get_extra_permastruct($post->post_type);
		$slug = $post->post_name;
		$draft_or_pending = isset($post->post_status) && in_array($post->post_status, array('draft', 'pending', 'auto-draft'));
		$post_type = get_post_type_object($post->post_type);

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

	function get_permalink($leavename = false)
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

		$custom_post_types = get_post_types(array('_builtin' => false));
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
		$permalink = (empty($this->perma_struct)) ? get_option('permalink_structure') : $this->perma_struct;
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
					$category = is_wp_error($default_category) ? '' : $default_category->slug;
				}
			}
			$author = '';
			if (strpos($permalink, '%author%') !== false)
			{
				$authordata = get_userdata($post->post_author);
				$author = $authordata->user_nicename;
			}
			
			$date = explode(' ', date('Y m d H i s', $unixtime));
			$rewritereplace =
			array(
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
	 * $query_str should be already escaped using either $wpdb->escape() or $wpdb->prepare().
	 */
	function get_results($query_str)
	{
		global $bwp_gxs, $wpdb;

		$start 		= (!empty($this->url_sofar)) ? $this->offset + (int) $this->url_sofar : $this->offset;
		$end 		= (int) $bwp_gxs->options['input_sql_limit'];
		$limit 		= $this->limit;
		// If we exceed the actual limit, limit $end to the correct limit - @since 1.1.5
		if ($this->url_sofar + $end > $limit)
			$end = $limit - $this->url_sofar;
		$query_str  = trim($query_str);
		$query_str .= ' LIMIT ' . $start . ',' . $end;

		return $wpdb->get_results($query_str);
	}

	/**
	 * Always call this function when you query for something.
	 *
	 * $query_str should be similar to what WP_Query accepts.
	 */
	function query_posts($query_str)
	{
		$this->circle += 1;
		if (is_array($query_str))
		{
			$query_str['posts_per_page'] = (int) $bwp_gxs->options['input_sql_limit'];
			$query_str['paged'] = $this->circle;
		}
		else if (is_string($query_str))
		{
			$query_str = trim($query_str);
			$query_str .= '&posts_per_page=' . (int) $bwp_gxs->options['input_sql_limit'];
			$query_str .= '&paged=' . $this->circle;
		}
		$query = new WP_Query($query_str);
		return $query;
	}

	function generate_data()
	{
		return false;
	}

	function build_data($sort_column = '')
	{
		global $bwp_gxs;

		// Use part limit or global item limit - @since 1.1.0
		$this->limit = (empty($this->part)) ? $bwp_gxs->options['input_item_limit'] : $bwp_gxs->options['input_split_limit_post'];
		// If this is a Google News sitemap, limit is 1000
		$this->limit = ('news' == $this->type) ? 1000 : $this->limit;
		$this->offset = (empty($this->part)) ? 0 : ($this->part - 1) * $bwp_gxs->options['input_split_limit_post'];

		while ($this->url_sofar < $this->limit && false != $this->generate_data())
			$this->url_sofar = sizeof($this->data);

		// Sort the data by preference
		if (!empty($sort_column))
			$this->sort_data_by($sort_column);
	}
}
?>