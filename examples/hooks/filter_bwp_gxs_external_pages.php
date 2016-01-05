<?php
// add to theme's functions.php
add_filter('bwp_gxs_external_pages', 'bwp_gxs_external_pages');
function bwp_gxs_external_pages($pages)
{
	return array(
		array('location' => home_url('link-to-page.html'), 'lastmod' => '06/02/2011', 'frequency' => 'auto', 'priority' => '1.0'),
		array('location' => home_url('another-page.html'), 'lastmod' => '05/02/2011', 'frequency' => 'auto', 'priority' => '0.8')
	);
}
