<?php
/*
Plugin Name:  Nucleora Brightcove Importer
Plugin URI:   http://nucleora.com/
Description:  Download videos from brightcove to your media library. Customize the ammount and offset.
Version:      1.0
Author:       Isaac L. Félix
Author URI:   http://isaaclfelix.github.io
License:      GPL2
License URI:  https://www.gnu.org/licenses/gpl-2.0.html
Text Domain:  nucleora-brightcove-importer
Domain Path:  /languages
*/
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Activation Hook
function brightcove_importer_activation() {

}
register_activation_hook(__FILE__, 'brightcove_importer_activation');

// Deactivation Hook
function brightcove_importer_deactivation() {

}
register_deactivation_hook(__FILE__, 'brightcove_importer_deactivation');

// Import function
function brightcove_importer_import() {
  // Check ajax nonce
  check_ajax_referer('nucleora-brightcove-importer-security', 'security');

  // Get ammount, offset, client ID and client secret
  $limit              = sanitize_text_field($_POST['limit']); // limit parameter
  $offset             = sanitize_text_field($_POST['offset']); // offset parameter
  $account_id         = sanitize_text_field($_POST['account_id']);
  $client_id          = sanitize_text_field($_POST['client_id']);
  $client_secret      = sanitize_text_field($_POST['client_secret']);

  // Get access token
  $data = array(
    'client_id' => $client_id,
    'client_secret' => $client_secret
  );
  //$auth_string   = base64_encode($client_id . ":" . $client_secret);
  $request       = "https://oauth.brightcove.com/v4/access_token?grant_type=client_credentials";

  $response = wp_remote_post($request, array(
    'headers' => array(
      'content-type' => 'application/x-www-form-urlencoded',
    ),
    'body' => $data
  ));

  // Check for errors
  if (is_wp_error($response)) {
    $error_message = $response->get_error_message();
    wp_die($error_message);
  }

  // Decode the response
  $responseData = json_decode(wp_remote_retrieve_body($response), true);
  $access_token = $responseData["access_token"];

  // Get videos list from sending offset and limit
  $videos_url = 'https://cms.api.brightcove.com/v1/accounts/' . $account_id . '/videos?limit=' . $limit . '&offset=' . $offset;

  // Send the http request

  $response = wp_remote_get($videos_url, array(
    'headers' => array(
      'Content-Type' => 'application/json',
      'Authorization' => 'Bearer ' . $access_token
    )
  ));

  // Check for errors
  if (is_wp_error($response)) {
    $error_message = $response->get_error_message();
    wp_die($error_message);
  }

  // Decode the response
  $videos = json_decode(wp_remote_retrieve_body($response), true);

  // Build video URLs array
  $videos_urls = [];
  // Iterate over videos
  foreach($videos as $video) {
    // Get the video ID
    $video_id = $video['id'];
    // Get the video name
    $video_title = $video['name'];
    // Build the sources API url
    $video_sources_url = 'https://cms.api.brightcove.com/v1/accounts/' . $account_id . '/videos/' . $video_id . '/sources';

    // Send the http request
    $response = wp_remote_get($video_sources_url, array(
      'headers' => array(
        'Content-Type' => 'application/json',
        'Authorization' => 'Bearer ' . $access_token
      ),
      'body' => json_encode($data)
    ));

    // Check for errors
    if (is_wp_error($response)) {
      $error_message = $response->get_error_message();
      wp_die($error_message);
    }

    $decodedResponse = json_decode(wp_remote_retrieve_body($response), true);

    // Iterate over available sizes
    $sizes = [];
    foreach($decodedResponse as $size) {
      if (array_key_exists('size', $size)) {
        $sizes[] = $size;
      }
    }
    // Get the largest sized source
    $video_url = $sizes[0]['src'];
    // Save it on the video URLs array
    $videos_urls[$video_title] = $video_url;
  }

  // Return encoded array of video URLs
  echo json_encode($videos_urls);

  // Mandatory wp_die();
  wp_die();
}
add_action('wp_ajax_brightcove_importer_import', 'brightcove_importer_import');

function brightcove_importer_download_video() {
  // Check ajax nonce
  check_ajax_referer('nucleora-brightcove-importer-security', 'security');

  // Sideload video from URL. Code based on the core's media_sideload_image function
  if (!function_exists('media_handle_sideload')) {
		require_once(ABSPATH . "wp-admin" . '/includes/image.php');
		require_once(ABSPATH . "wp-admin" . '/includes/file.php');
		require_once(ABSPATH . "wp-admin" . '/includes/media.php');
  }

  // Video URL
  $videoURL = esc_url($_POST['videoURL']);
  $videoTitle = sanitize_text_field($_POST['title']);

  // Use the video tite as the file name to get SEO friendly URLs
  $file_array['name'] = $videoTitle . '.mp4';

  // Download file to temp location.
  $file_array['tmp_name'] = download_url( $videoURL );

  // If error storing temporarily, return the error.
  if ( is_wp_error( $file_array['tmp_name'] ) ) {
    return $file_array['tmp_name'];
  }

  // Do the validation and storage stuff.
  $id = media_handle_sideload( $file_array, 0, null );

  // If error storing permanently, unlink.
  if ( is_wp_error( $id ) ) {
    @unlink( $file_array['tmp_name'] );
    return $id;
  }

  // Get video URL to return it
  $src = wp_get_attachment_url( $id );

  // Mandatory wp_die();
  wp_die($src);
}
add_action('wp_ajax_brightcove_importer_download_video', 'brightcove_importer_download_video');

// Settings
require_once(plugin_dir_path(__FILE__) . 'settings.php');
?>
