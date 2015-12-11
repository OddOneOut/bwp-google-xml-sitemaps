<?php

function bwp_gxs_get_filename($sitemap_name)
{
	global $bwp_gxs;

	// cache filename, use gz all the time to save space
	// @todo save both .xml version and .xml.gz version
	// append home_url to be WPMS compatible
	$filename  = 'gxs_' . md5($sitemap_name . '_' . home_url());
	$filename .= '.xml.gz';

	return trailingslashit($bwp_gxs->get_cache_directory()) . $filename;
}

function bwp_gxs_format_header_time($time)
{
	return gmdate('D, d M Y H:i:s \G\M\T', (int) $time);
}
