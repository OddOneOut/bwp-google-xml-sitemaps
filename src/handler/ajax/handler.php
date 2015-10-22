<?php

/**
 * Copyright (c) 2015 Khang Minh <contact@betterwp.net>
 * @license http://www.gnu.org/licenses/gpl.html GNU GENERAL PUBLIC LICENSE VERSION 3.0 OR LATER
 */

/**
 * @author Khang Minh <contact@betterwp.net>
 */
abstract class BWP_Sitemaps_Handler_AjaxHandler
{
	/**
	 * @var BWP_WP_Bridge
	 */
	protected $bridge;

	protected function response_with(array $data)
	{
		@header('Content-Type: application/json');

		echo json_encode($data);

		exit;
	}

	protected function fail()
	{
		echo 0; exit;
	}

	protected function succeed()
	{
		echo 1; exit;
	}
}
