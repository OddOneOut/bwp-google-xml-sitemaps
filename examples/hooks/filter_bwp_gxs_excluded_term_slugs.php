<?php
// add to theme's functions.php
add_filter('bwp_gxs_excluded_term_slugs', 'bwp_gxs_excluded_term_slugs', 10, 2);
function bwp_gxs_excluded_term_slugs($term_slugs, $taxonomy)
{
    // $taxonomy let you easily exclude terms from specific taxonomies
    switch ($taxonomy)
    {
        case 'category': return array('cat-slug1', 'cat-slug2'); break;
        case 'post_tag': return array('tag-slug1', 'tag-slug2'); break;
    }

    return array();
}
