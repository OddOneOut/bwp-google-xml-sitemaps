<?php
/**
 * Copyright (c) 2011 Khang Minh <betterwp.net>
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
	
	var $comment_count = 0, $now, $url_sofar = 0, $circle = 0;

	function __contruct()
	{
		/*$this->comment_count = wp_count_comments();*/
	}

	function set_current_time()
	{
		$this->now = (int) time();
	}

	function init_data($pre_data = array())
	{
		global $bwp_gxs;

		if (empty($pre_data) || sizeof($pre_data) == 0)
			return array();

		if (empty($this->now))
			$this->set_current_time();

		$data['location'] = '';
		$data['lastmod'] = (!empty($pre_data['lastmod'])) ? strtotime($pre_data['lastmod']) - $bwp_gxs->oldest_time : $this->now - $bwp_gxs->oldest_time;
		$data['lastmod'] = $this->format_lastmod($data['lastmod']);
		if (isset($pre_data['freq'])) $data['freq'] = $pre_data['freq'];
		if (isset($pre_data['priority'])) $data['priority'] = $pre_data['priority'];

		return $data;
	}

	function get_xml_link($slug)
	{
		global $bwp_gxs;
		
		if (!$bwp_gxs->use_permalink)
			return get_option('home') . '/?' . $bwp_gxs->query_var_non_perma . '=' . $slug;
		else
			return get_option('home') . '/' . $slug . '.xml';
	}

	/**
	 * Calculate the change frequency for a specific item.
	 *
	 * @copyright (c) 2006 - 2009 www.phpbb-seo.com
	 */
	function cal_frequency($item = '')
	{
		global $bwp_gxs;

		if (empty($this->now))
			$this->now = $this->set_current_time();

		if (!is_object($item))
			$freq = $bwp_gxs->options['select_default_freq'];
		else
		{
			$time = $this->now - (int) strtotime($item->post_modified);
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
		return gmdate('Y-m-d\TH:i:s' . '+00:00', (int) $lastmod);
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

		foreach ($result as $post)
		{
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
		foreach ($this->data as $key => $row)
			$lastmod[$key] = $row[$column];
		// Sort the data with volume descending, edition ascending
		// Add $data as the last parameter, to sort by the common key
		array_multisort($lastmod, SORT_DESC, $this->data);
	}

	/**
	 * Always call this function when you query for something.
	 *
	 * $query_str should be already escaped using either $wpdb->escape() or $wpdb->prepare().
	 */
	function get_results($query_str)
	{
		global $bwp_gxs, $wpdb;

		$start 		= (!empty($this->url_sofar)) ? (int) $this->url_sofar + $this->circle : (int) $this->url_sofar;
		$end 		= (int) $bwp_gxs->options['input_sql_limit'];
		$query_str  = trim($query_str);
		$query_str .= ' LIMIT ' . $start . ',' . $end;
		$this->circle += 1;
		
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

	function build_data()
	{
		global $bwp_gxs;

		while (false != $this->generate_data() && $this->url_sofar < $bwp_gxs->options['input_item_limit'])
			$this->url_sofar = sizeof($this->data);

		// Sort the data by lastmod
		//$this->sort_data_by();
	}
}
?>