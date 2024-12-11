# cf7-snapshot-upload
Contact form 7 camera and from system upload plugin addon


It's easy to use, just select code form from fields in the admin side and camera field will appear on front side.

and if anyone who know the code little bit and want save media into db just uncomment code in the file and you media will be save database

Uncomment this code from function: cf7_camera_handle_images
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

and a Developer who know th little bit code can easily modify this addon plugin as he/she wants.
