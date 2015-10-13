<?php

if (!defined('ABSPATH')) { exit; }

// r2 2015-10-08

$old_logs = $this->bridge->get_option(BWP_GXS_LOG);

if (!$old_logs || !is_array($old_logs)) {
	return;
}

$new_logs = $this->_get_initial_logs();

foreach ($old_logs as $key => $logs) {
	foreach ($logs as $log) {
		// old logs use time in local timezone, convert it to UTC
		$time = $log['time'];
		$time_utc = $time - $this->bridge->get_option('gmt_offset') * HOUR_IN_SECONDS;

		// '@' notation converts internally to UTC
		// @see http://php.net/manual/en/datetime.formats.compound.php
		$datetime_utc = new DateTime('@' . $time_utc);

		if ($key == 'log') {
			$new_logs['messages'][] = array(
				'message' => $log['log'],
				'type'    => $log['error'] === true
					? BWP_Sitemaps_Logger_Message_LogItem::TYPE_ERROR
					: ($log['error'] === 'notice'
						? BWP_Sitemaps_Logger_Message_LogItem::TYPE_NOTICE
						: BWP_Sitemaps_Logger_Message_LogItem::TYPE_SUCCESS),
				'datetime' => $datetime_utc->format('Y-m-d H:i:s')
			);
		} elseif ($key == 'sitemap') {
			$new_logs['sitemaps'][] = array(
				'slug'     => $log['url'],
				'datetime' => $datetime_utc->format('Y-m-d H:i:s')
			);
		}
	}
}

$this->bridge->update_option(BWP_GXS_LOG, $new_logs);
