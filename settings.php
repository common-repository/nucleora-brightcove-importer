<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Add menu page
function brightcove_importer_add_menu_page() {
  add_menu_page('Brightcove Importer', 'Brightcove Importer', 'manage_options', 'nucleora-brightcove-importer','brightcove_importer_settings_page', 'dashicons-format-video');
}
add_action('admin_menu', 'brightcove_importer_add_menu_page');

// Settings page callback function
function brightcove_importer_settings_page() {
  // check user capabilities
  if (!current_user_can('manage_options')) {
    return;
  }

  // add error/update messages

  // check if the user have submitted the settings
  // wordpress will add the "settings-updated" $_GET parameter to the url
  if ( isset( $_GET['settings-updated'] ) ) {
    // add settings saved message with the class of "updated"
    add_settings_error( 'wporg_messages', 'wporg_message', __( 'Settings Saved', 'wporg' ), 'updated' );
  }

  // show error/update messages
  settings_errors( 'wporg_messages' );
  ?>
  <div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    <form action="options.php" method="post">
      <?php
      // output security fields for the registered setting "wporg"
      settings_fields('nucleora-brightcove-importer');
      // output setting sections and their fields
      // (sections are registered for "wporg", each field is registered to a specific section)
      do_settings_sections('nucleora-brightcove-importer');
      // output save settings button
      submit_button( 'Save Settings' );
      ?>
    </form>
    <button id="brightcove-import" type="button" class="button button-secondary">Run the Import</button>
    <h5>Output: </h5>
    <div id="brightcove-import-output" style="height: 300px; overflow-y: scroll; background-color: #DDD; color: #000;" class="widefat"></div>
  </div>
  <script>
  var $ = jQuery;
  async function brightcove_importer_download_video(url, title, downloadCount) {
    var data = {
      action: 'brightcove_importer_download_video',
      security: '<?php echo wp_create_nonce( "nucleora-brightcove-importer-security" ); ?>',
      videoURL: url,
      title: title
    }
    // Download, and await download
    return $.post(ajaxurl, data, function(response) {
      $("#brightcove-import-output").prepend('Download attempt #'+downloadCount+' complete. ' + response + '<br /><br />');
    }).promise();
  }
  async function brightcove_importer_download_videos(response, limit) {
    // Parse response
    var urls = JSON.parse(response);
    var downloadCount = 1;

    for (key in urls) {
      $("#brightcove-import-output").prepend('Trying to download url: ' + urls[key] + '<br /><br />');
      var downloadedVideo = await brightcove_importer_download_video(urls[key], key, downloadCount);
      if (downloadCount === limit) {
        $("#brightcove-import-output").prepend("Import finished.<br /><br />");
      }
      downloadCount++;
    }
  }
  function brightcove_importer_get_video_urls(data, limit, callback) {
    // Fetch videos urls from brightcove
    $.post(ajaxurl, data, function(response) {
      callback(response, limit);
    });
  }
  async function brightcove_importer_import() {
    $("#brightcove-import-output").html("Starting migration...<br />");

    // Get params
    var limit = $("#brightcove_importer_limit").val();
    var offset = $("#brightcove_importer_offset").val();
    var account_id = $("#brightcove_importer_account_id").val();
    var client_id = $("#brightcove_importer_client_id").val();
    var client_secret = $("#brightcove_importer_client_secret").val();

    // Validate params
    if (account_id === "" || client_id === "" || client_secret === "") {
      alert("Please provide an account ID, client ID and client secret.");
      return;
    }

    var data = {
      action: 'brightcove_importer_import',
      security: '<?php echo wp_create_nonce( "nucleora-brightcove-importer-security" ); ?>',
      limit: limit,
      offset: offset,
      account_id: account_id,
      client_id: client_id,
      client_secret: client_secret
    }

    brightcove_importer_get_video_urls(data, parseInt(limit), brightcove_importer_download_videos);
  }
  $(function() {
    $("#brightcove-import").click(function(e) {
      brightcove_importer_import();
    });
  });
  </script>
  <?php
}

// Settings
function display_brightcove_importer_limit() {
$brightcove_importer_limit = get_option('brightcove_importer_limit', 10);
?>
<input id="brightcove_importer_limit" min="1" max="100" type="number" class="widefat" name="brightcove_importer_limit" value="<?php esc_attr_e($brightcove_importer_limit); ?>" />
<?php
}

function display_brightcove_importer_offset() {
$brightcove_importer_offset = get_option('brightcove_importer_offset', 10);
?>
<input id="brightcove_importer_offset" min="0" type="number" class="widefat" name="brightcove_importer_offset" value="<?php esc_attr_e($brightcove_importer_offset); ?>" />
<?php
}

function display_brightcove_importer_account_id() {
$brightcove_importer_account_id = get_option('brightcove_importer_account_id', "");
?>
<input id="brightcove_importer_account_id" type="text" class="widefat" name="brightcove_importer_account_id" value="<?php esc_attr_e($brightcove_importer_account_id); ?>" />
<?php
}

function display_brightcove_importer_client_id() {
$brightcove_importer_client_id = get_option('brightcove_importer_client_id', "");
?>
<input id="brightcove_importer_client_id" type="text" class="widefat" name="brightcove_importer_client_id" value="<?php esc_attr_e($brightcove_importer_client_id); ?>" />
<?php
}

function display_brightcove_importer_client_secret() {
$brightcove_importer_client_secret = get_option('brightcove_importer_client_secret', "");
?>
<input id="brightcove_importer_client_secret" type="text" class="widefat" name="brightcove_importer_client_secret" value="<?php esc_attr_e($brightcove_importer_client_secret); ?>" />
<?php
}

function brightcove_importer_settings() {
  // Add a new section in the brightcove_importer page
  add_settings_section('brightcove_importer_import_settings', __('Import Settings', 'nucleora-brightcove-importer'), null, 'nucleora-brightcove-importer');

  // Add setting fields
  add_settings_field('brightcove_importer_limit', __('Limit', 'nucleora-brightcove-importer'), 'display_brightcove_importer_limit', 'nucleora-brightcove-importer', 'brightcove_importer_import_settings');
  add_settings_field('brightcove_importer_offset', __('Offset', 'nucleora-brightcove-importer'), 'display_brightcove_importer_offset', 'nucleora-brightcove-importer', 'brightcove_importer_import_settings');
  add_settings_field('brightcove_importer_account_id', __('Account ID', 'nucleora-brightcove-importer'), 'display_brightcove_importer_account_id', 'nucleora-brightcove-importer', 'brightcove_importer_import_settings');
  add_settings_field('brightcove_importer_client_id', __('Client ID', 'nucleora-brightcove-importer'), 'display_brightcove_importer_client_id', 'nucleora-brightcove-importer', 'brightcove_importer_import_settings');
  add_settings_field('brightcove_importer_client_secret', __('Client Secret', 'nucleora-brightcove-importer'), 'display_brightcove_importer_client_secret', 'nucleora-brightcove-importer', 'brightcove_importer_import_settings');


  // Register for this section
  register_setting('nucleora-brightcove-importer', 'brightcove_importer_limit', 'wp_filter_nohtml_kses');
  register_setting('nucleora-brightcove-importer', 'brightcove_importer_offset', 'wp_filter_nohtml_kses');
  register_setting('nucleora-brightcove-importer', 'brightcove_importer_account_id', 'wp_filter_nohtml_kses');
  register_setting('nucleora-brightcove-importer', 'brightcove_importer_client_id', 'wp_filter_nohtml_kses');
  register_setting('nucleora-brightcove-importer', 'brightcove_importer_client_secret', 'wp_filter_nohtml_kses');
}
add_action('admin_init', 'brightcove_importer_settings');
?>
