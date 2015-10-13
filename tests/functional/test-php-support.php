<?php

/**
 * @author Khang Minh <contact@betterwp.net>
 */
class BWP_Sitemaps_PHP_Support_Functional_Test extends BWP_Framework_PHPUnit_WP_Legacy_Functional_TestCase
{
	public function test_can_initiate_all_classes()
	{
		$class_maps = include dirname(dirname(dirname(__FILE__))) . '/vendor/composer/autoload_classmap.php';

		foreach ($class_maps as $class_name => $class_file) {
			if (stripos($class_name, 'bwp_sitemaps') === false
				&& stripos($class_name, 'gxs') === false
			) {
				continue;
			}

			$not_php_52 = array(
				'BWP_Sitemaps_PHPUnit_Provider_Unit_TestCase',
			);

			// do not load certain testcase classes if PHP version is less than 5.3
			if (in_array($class_name, $not_php_52) && version_compare(PHP_VERSION, '5.3', '<')) {
				continue;
			}

			require_once $class_file;
		}

		$this->assertTrue(true);
	}

	public function get_plugins()
	{
		$root_dir = dirname(dirname(dirname(__FILE__)));

		return array(
			$root_dir . '/bwp-gxs.php' => 'bwp-google-xml-sitemaps/bwp-gxs.php'
		);
	}

	public function test_can_bootstrap_plugin()
	{
		$this->bootstrap_plugin();

		$this->assertTrue(true);
	}
}
