<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Remove plugin settings
remove_option('brightcove_importer_limit');
remove_option('brightcove_importer_offset');
remove_option('brightcove_importer_account_id');
remove_option('brightcove_importer_client_id');
remove_option('brightcove_importer_client_secret');
?>
