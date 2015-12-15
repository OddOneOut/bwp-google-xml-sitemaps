<?php if (!defined('ABSPATH')) { exit; } ?>

<?php if (count($data['items']) > 0) : ?>
<ul class="bwp-gxs-log bwp-gxs-log-big">
<?php
	/* @var $item BWP_Sitemaps_Logger_Message_LogItem */
	foreach ($data['items'] as $item) :
		$color = $item->is_error() ? 'FF0000' : ($item->is_success() ? '009900' : '999999');

		/* translators: date format, see http://php.net/date */
		$datetime = $item->get_formatted_datetime(__('M d, Y h:i:s A', $this->domain));
?>
	<li class="bwp-clear" style="margin-top: 5px; line-height: 1.7;">
		<span style="float: left; margin-right: 5px;"><?php echo esc_html($datetime); ?> &mdash;</span>
		<span style="color: #<?php echo $color; ?>;"><?php echo $item->get_message(); ?></span>
	</li>
<?php
	endforeach;
?>
</ul>
<?php else : ?>
<p>
	<?php _e('No log yet!', $this->domain); ?>
</p>
<?php endif; ?>
