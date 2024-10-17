<?php
/*
Plugin Name: Contact Form 7 - Camera Addon
Plugin URI: https://example.com/image-upload-plugin
Description: A plugin for uploading images with filename cleaning and camera support.
Version: 1.0
Author: Naeem Asghar
Author URI: https://example.com
License: GPL2
*/

// Hook to register the plugin
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}


add_action('init', 'cf7_fields_library');
function cf7_fields_library(){
    require_once plugin_dir_path(__FILE__) . 'functions.php';
}

add_action('wp_enqueue_scripts', 'cf7_assets_snapshot_upload');
function cf7_assets_snapshot_upload() {
    global $post;
    $content = $post->post_content;
    if (has_shortcode($content, 'contact-form-7')) {
        wp_enqueue_script('webcamjs', 'https://cdnjs.cloudflare.com/ajax/libs/webcamjs/1.0.26/webcam.min.js', array(), null, true);
        wp_enqueue_script('camera-upload-js', plugin_dir_url(__FILE__) . 'assets/js/app.js', array('jquery'), null, true);
        wp_enqueue_style('camera-upload-css', plugin_dir_url(__FILE__) . 'assets/css/front-style.css');
    }
}

add_action('wp_head', 'cf7_assets_snapshot_upload_preload');
function cf7_assets_snapshot_upload_preload() {
    global $post;
    $content = $post->post_content;
    if (has_shortcode($content, 'contact-form-7')) {
        echo '<link href="https://cdnjs.cloudflare.com/ajax/libs/webcamjs/1.0.26/webcam.min.js" rel="preload" as="script" />';
        echo '<link href="'.plugin_dir_url(__FILE__) . 'assets/js/camera-upload.js" rel="preload" as="script" />';
        echo '<link href="'.plugin_dir_url(__FILE__) . 'assets/css/camera-upload.css" rel="preload" as="style" />';
    }
}


add_action('admin_menu', 'cf7_snapshot_upload_admin_menu');
function cf7_snapshot_upload_admin_menu() {
    add_submenu_page(
        'wpcf7',
        'Snapshot Upload',
        'CF7 Camera Addon',
        'manage_options',
        'image-upload',
        'cf7_snapshot_upload_admin_page',
        'dashicons-upload', 
        20
    );
}

function cf7_snapshot_upload_admin_page() {
    ?>
    <div class="wrap">
        <h1>Camera Settings</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('snapshot-upload-settings-group');
            do_settings_sections('camera-settings');
            submit_button();
            ?>
        </form>
    </div>
    <?php
}



// Register settings
add_action('admin_init', 'snapshot_upload_admin_settings');
function snapshot_upload_admin_settings() {

    register_setting('snapshot-upload-settings-group', 'snapshot_upload_image_width');
    register_setting('snapshot-upload-settings-group', 'snapshot_upload_image_height');
    register_setting('snapshot-upload-settings-group', 'snapshot_upload_media_checkbox');

    add_settings_section('camera-settings-section', 'Camera Settings', null, 'camera-settings');
    add_settings_field('snapshot_upload_image_width', 'Camera Image Width (px)', 'snapshot_upload_image_width_callback', 'camera-settings', 'camera-settings-section');
    add_settings_field('snapshot_upload_image_height', 'Camera Image Height (px)', 'snapshot_upload_image_height_callback', 'camera-settings', 'camera-settings-section');
    add_settings_field('snapshot_upload_media_checkbox', 'Register Media Checkbox', 'snapshot_upload_media_checkbox_callback', 'camera-settings', 'camera-settings-section');
}

// Callback function for camera image height
function snapshot_upload_image_width_callback() {
    $camera_image_width = get_option('camera_image_width');
    echo '<input type="number" name="camera_image_width" value="' . esc_attr($camera_image_width) . '" />';
}

function snapshot_upload_image_height_callback() {
    $camera_image_height = get_option('camera_image_height');
    echo '<input type="number" name="camera_image_height" value="' . esc_attr($camera_image_height) . '" />';
}

// Callback function for media checkbox
function snapshot_upload_media_checkbox_callback() {
    $media_checkbox = get_option('media_checkbox');
    echo '<input type="checkbox" name="media_checkbox" value="1" ' . checked(1, $media_checkbox, false) . '/>';
}



//add_shortcode('webcam_image_upload', 'cf7_camera_frontend_webcam_form');


function imageNameGenrator($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[random_int(0, $charactersLength - 1)];
    }
    return $randomString;
}

function resize_image($file_path, $new_width, $new_height, $new_filename = null) {
    list($original_width, $original_height, $image_type) = getimagesize($file_path);
    
    // Handle different image types
    switch ($image_type) {
        case IMAGETYPE_JPEG:
            $image = imagecreatefromjpeg($file_path);
            break;
        case IMAGETYPE_PNG:
            $image = imagecreatefrompng($file_path);
            break;
        case IMAGETYPE_GIF:
            $image = imagecreatefromgif($file_path);
            break;
        default:
            return false;  // Unsupported image type
    }

    // Create a new image canvas with the new dimensions
    $resized_image = imagecreatetruecolor($new_width, $new_height);
    imagecopyresampled($resized_image, $image, 0, 0, 0, 0, $new_width, $new_height, $original_width, $original_height);

    // If no filename is provided, generate a temporary filename
    if ($new_filename === null) {
        $temp_file = tempnam(sys_get_temp_dir(), 'resized_') . '.png';  // Default to .png
    } else {
        // Ensure the filename has the correct extension
        $file_extension = pathinfo($new_filename, PATHINFO_EXTENSION);
        if (empty($file_extension)) {
            $new_filename .= '.png';  // Default to .png if no extension is provided
        }
        $temp_file = $new_filename;
    }

    // Save the resized image as PNG
    imagepng($resized_image, $temp_file);  // Use imagepng to save as PNG
    
    // Clean up memory
    imagedestroy($image);
    imagedestroy($resized_image);

    return $temp_file;
}





function disable_image_sizes($sizes) {
    unset($sizes['thumbnail']);  
    unset($sizes['medium']);  
    unset($sizes['large']);  
    return $sizes;
}

function disable_all_image_sizes($sizes) {
    return []; 
}

function cf7_camera_handle_images() {
    if (empty($_FILES)) {
        wp_send_json_error(['message' => 'No images received.']);
    }
    add_filter('intermediate_image_sizes_advanced', 'disable_image_sizes');
    add_filter('intermediate_image_sizes_advanced', 'disable_all_image_sizes');

    add_filter( 'upload_dir', function( $arr ) use( &$_filter ){
        $arr['path'] = str_replace('/uploads/', '/uploads/cf7/', $arr['path']);
        $arr['url'] = str_replace('/uploads/', '/uploads/cf7/', $arr['url']);
        $arr['subdir'] = str_replace('/uploads/', '/uploads/cf7/', $arr['subdir']);
        return $arr;
    });

    $new_width = 800;
    $new_height = 600;

    $uploaded_images = []; 
    $uploaded_ids = []; 
    foreach ($_FILES as $file_key => $file) {
        $imgName = 'capture-'.time().'-'.imageNameGenrator(12).'.png';
        $temp_file_path = $file['tmp_name'];
        $resized_image_path = resize_image($temp_file_path, $new_width, $new_height, $imgName);
        $upload = wp_upload_bits($imgName, null, file_get_contents($resized_image_path));
        if ($upload['error']) {
            wp_send_json_error(['message' => 'Error uploading file: ' . $upload['error']]);
        } else {
            // $attachment = array(
            //     'post_mime_type' => $file['type'],
            //     'post_title'     => sanitize_file_name($file['name']),
            //     'post_content'   => '',
            //     'post_status'    => 'inherit',
            //     'guid'           => $upload['url'],
            // );
            // $attachment_id = wp_insert_attachment($attachment, $upload['file']);
            // require_once(ABSPATH . 'wp-admin/includes/image.php');
            // $metadata = wp_generate_attachment_metadata($attachment_id, $upload['file']);
            // wp_update_attachment_metadata($attachment_id, $metadata);
            $uploaded_images[] = $upload['file'];
            //$uploaded_ids[] = $attachment_id;
        }
    }
    wp_send_json_success(['uploaded_images' => $uploaded_images]);
}
add_action('wp_ajax_cf7_camera_handle_images', 'cf7_camera_handle_images');
add_action('wp_ajax_nopriv_cf7_camera_handle_images', 'cf7_camera_handle_images');

function cf7_camera_handle_remove_images(){
    $removed = false;
    if(isset($_POST['urls'])){
        $urls = explode(',', $_POST['urls']);
        if(!empty($urls)){
            foreach($urls as $url){
               unlink($url);
            }
            $removed = true;
        }
    }
    wp_send_json_success(['uploaded_images_removed' => $removed]);
}
add_action('wp_ajax_cf7_camera_handle_remove_images', 'cf7_camera_handle_remove_images');
add_action('wp_ajax_nopriv_cf7_camera_handle_remove_images', 'cf7_camera_handle_remove_images');


add_filter('wpcf7_mail_components', 'add_camera_image_to_email', 10, 3);
function add_camera_image_to_email($components, $form, $mail) {
    if(isset($_POST['snapshot_upload']) && !empty($_POST['snapshot_upload'])){
        $arr = explode(',', $_POST['snapshot_upload']);
        $images = array();
        foreach($arr as $src){
            if($src){
                $components['attachments'][] = esc_url($src);
            }
        }
    }
    return $components;
}
