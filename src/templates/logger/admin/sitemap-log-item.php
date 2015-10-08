<span style="display:inline-block; margin-bottom: 7px;">
	<em><?php _e('Below you can find a list of generated sitemaps:', $this->domain); ?></em>
</span>

<br />

<ul class="bwp-gxs-log bwp-gxs-log-small">
<?php
	/* @var $item BWP_Sitemaps_Logger_Sitemap_LogItem */
	foreach ($data['items'] as $item) :
?>
	<span style="margin-top: 5px; display: inline-block;">
		<a href="<?php esc_attr_e(sprintf($data['sitemap_url_struct'], $item->get_sitemap_slug())); ?>" target="_blank"><?php echo esc_html($item->get_sitemap_slug()); ?></a>
		<?php printf(
			__('was generated on <strong>%s</strong>.', $this->domain),
			/* translators: date format, see http://php.net/date */
			esc_html($item->get_formatted_datetime(__('M j, Y : H:i:s', $this->domain)))
		); ?>
	</span>

	<br />
<?php
	endforeach;
?>
</ul>
