<?php

$root_dir = dirname(dirname(__FILE__));

if (version_compare(PHP_VERSION, '5.3.2', '<')) {
	require_once $root_dir . '/autoload.php';
} else {
	require_once $root_dir . '/vendor/autoload.php';
}

require_once $root_dir . '/vendor/kminh/bwp-framework/tests/bootstrap.php';
