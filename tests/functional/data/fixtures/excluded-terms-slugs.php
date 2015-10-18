<?php

if (! get_option('bwp_gxs_generator_exclude_terms_by_slugs')) {
	return;
}

add_filter('bwp_gxs_term_exclude', 'bwp_gxs_exclude_terms_slugs_deprecated', 10, 2);
add_filter('bwp_gxs_excluded_term_slugs', 'bwp_gxs_exclude_terms_slugs', 10, 2);

function bwp_gxs_exclude_terms_slugs_deprecated(array $terms_slugs, $taxonomy)
{
	switch ($taxonomy) {
		case 'category':
			return array('category-3');
			break;

		case 'genre':
			return array('genre-3');
			break;
	}

	return $terms_slugs;
}

function bwp_gxs_exclude_terms_slugs(array $terms_slugs, $taxonomy)
{
	$return = array();

	switch ($taxonomy) {
		case 'category':
			$return = array('category-4', 'category-5');
			break;

		case 'genre':
			$return = array('genre-4', 'genre-5');
			break;
	}

	return array_unique(array_merge($terms_slugs, $return));
}
