<?php

function bwp_gxs_get_filename($module_name)
{
	global $bwp_gxs;

	// cache filename, use gz all the time to save space
	// @todo save both .xml version and .xml.gz version
	// append home_url to be WPMS compatible 
	$filename  = 'gxs_' . md5($module_name . '_' . home_url());
	$filename .= '.xml.gz';

	return trailingslashit($bwp_gxs->cache_directory) . $filename;
}
