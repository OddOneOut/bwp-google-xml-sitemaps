<?php
// add to theme's functions.php
add_filter('bwp_gxs_excluded_terms', 'bwp_gxs_exclude_terms', 10, 2);
function bwp_gxs_exclude_terms($term_ids, $taxonomy)
{
    // $taxonomy let you easily exclude terms from specific taxonomies
    switch ($taxonomy)
    {
        case 'category': return array(1,2,3,4); break;
        case 'post_tag': return array(5,6,7,8); break;
    }

    return array();
}
