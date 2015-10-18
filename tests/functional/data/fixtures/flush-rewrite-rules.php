<?php

include_once ABSPATH . '/wp-admin/includes/admin.php';

add_action('wp_loaded', 'bwp_gxs_flush_rewrite_rules_hard');

function bwp_gxs_flush_rewrite_rules_hard()
{
	flush_rewrite_rules();
}
