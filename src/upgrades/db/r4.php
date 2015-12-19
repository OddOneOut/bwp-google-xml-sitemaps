<?php

if (!defined('ABSPATH')) { exit; }

// r4 2015-12-15
// merge google news settings into extensions settings when needed
if (! $this->is_option_key_valid(BWP_GXS_GOOGLE_NEWS)) {
	return;
}

$this->update_some_options(BWP_GXS_EXTENSIONS, get_option(BWP_GXS_GOOGLE_NEWS));
