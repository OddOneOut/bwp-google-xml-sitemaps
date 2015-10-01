<?php

function _bwp_gxs_autoloader($class_name)
{
	$class_maps = include dirname(__FILE__) . '/vendor/composer/autoload_classmap.php';

	// only load BWP Sitemaps and GXS classes
	if (stripos($class_name, 'BWP_Sitemaps') === false && stripos($class_name, 'GXS') === false) {
		return;
	}

	if (array_key_exists($class_name, $class_maps)) {
		require $class_maps[$class_name];
	}
}

spl_autoload_register('_bwp_gxs_autoloader');
