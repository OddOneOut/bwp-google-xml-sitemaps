<?php
/**
 * Copyright (c) 2011 Khang Minh <betterwp.net>
 * @license http://www.gnu.org/licenses/gpl.html GNU GENERAL PUBLIC LICENSE
 * Taxonomy Linear Mode
 */

class BWP_GXS_MODULE_TAXONOMY extends BWP_GXS_MODULE {

	function __construct()
	{
		$this->set_current_time();
		$this->build_data();
	}

	function build_data()
	{
		global $wpdb, $bwp_gxs;

		$requested = $bwp_gxs->module_data['sub_module'];
		$the_taxonomy = $bwp_gxs->taxonomies[$requested];
		$object_types = $the_taxonomy->object_type;
		$post_type_in = array();
		foreach ($object_types as $post_type)
			$post_type_in[] = "wpost.post_type = '$post_type'";
		$post_type_in = implode(' OR ', $post_type_in);

		$latest_post_query = '
			SELECT * FROM ' . $wpdb->term_relationships . ' wprel
				INNER JOIN ' . $wpdb->posts . ' wpost
					ON wprel.object_id = wpost.ID
				INNER JOIN ' . $wpdb->term_taxonomy . ' wptax
					ON wprel.term_taxonomy_id = wptax.term_taxonomy_id' . "
				WHERE wpost.post_status = 'publish' AND wptax.taxonomy = %s" . '
					AND (' . $post_type_in . ')
			ORDER BY wpost.post_modified DESC
			LIMIT 5000'; // 5000, a rough assumption ;)
		$latest_posts = $wpdb->get_results($wpdb->prepare($latest_post_query, $requested));
		
		$terms = get_terms($requested, array('hierarchical' => false));

		$data = array();
		foreach ($terms as $term)
		{
			$count = 1;
			$data = $this->init_data($data);
			while (isset($latest_posts[$count - 1]) && $term->term_id != $latest_posts[$count - 1]->term_id)
				$count++;
			$data['location'] = get_term_link($term, $requested);
			if (isset($latest_posts[$count - 1]))
			{
				$post = $latest_posts[$count - 1];
				$data['lastmod'] = $this->format_lastmod(strtotime($post->post_modified));
				$data['freq'] = $this->cal_frequency($post);
				$data['priority'] = $this->cal_priority($post, $data['freq']);
			}
			$this->data[] = $data;
		}
		
		// Sort the data by lastmod
		$this->sort_data_by();
	}
}
?>