<?php
/**
** A base module for the following types of tags:
** 	[snapshot_upload] and [snapshot_upload*] 
# A snapshot capture or upload field
**/

/* form_tag handler */

add_action( 'wpcf7_init', 'cf7_add_snapshot_upload' );
add_action( 'wpcf7_admin_init', 'cf7_add_snapshot_upload_admin' );

/**
 * Function to add a new custom field tag
 */
function cf7_add_snapshot_upload() {
    wpcf7_add_form_tag( 'snapshot_upload', 'cf7_snapshot_upload_render', true );
    //wpcf7_add_form_tag( 'snapshot_upload', 'cf7_camera_frontend_webcam_form' ); 
}

/**
 * Add the custom field to the form editor interface
 */
function cf7_add_snapshot_upload_admin() {
    if ( function_exists( 'wpcf7_add_tag_generator' ) ) {
        $tag_generator = WPCF7_TagGenerator::get_instance();
        $tag_generator->add( 'snapshot_upload',
            __( 'Snapshot Upload', 'contact-form-7' ),
            'cf7_snapshot_upload_tag_generator',
            array( 'version' => '2' )
        );
    }
}

/**
 * Function to render the tag generator for the custom field
 */
function cf7_snapshot_upload_tag_generator( $contact_form, $args = '' ) {
    $field_types = array(
            'snapshot_upload' => array(
                'display_name' => __( 'Custom Field', 'contact-form-7' ),
                'heading' => __( 'Custom Field form-tag generator', 'contact-form-7' ),
                'description' => __( 'Generates a form-tag for a <a href="https://contactform7.com/text-fields/">multi-line plain text input area</a>.', 'contact-form-7' ),
            ),
        );
        $tgg = new WPCF7_TagGeneratorGenerator( $args['content'] );

    ?>
    <header class="description-box">
        <h3><?php
            echo esc_html( $field_types['snapshot_upload']['heading'] );
        ?></h3>

        <p><?php
            $description = wp_kses(
                $field_types['snapshot_upload']['description'],
                array(
                    'a' => array( 'href' => true ),
                    'strong' => array(),
                ),
                array( 'http', 'https' )
            );

            echo $description;
        ?></p>
    </header>

    <div class="control-box">
        <?php
            $tgg->print( 'field_type', array(
                'with_required' => false,
                'select_options' => array(
                    'snapshot_upload' => $field_types['snapshot_upload']['display_name'],
                ),
            ) );

            $tgg->print( 'field_name');

            $tgg->print( 'class_attr' );
            $tgg->print( 'id_attr' );
        ?>
    </div>

    <footer class="insert-box">
        <?php
            $tgg->print( 'insert_box_content' );

            $tgg->print( 'mail_tag_tip' );
        ?>
    </footer>
    <?php
}

/**
 * Function to process the custom field tag and value
 */
function cf7_snapshot_upload_tag_pane( $contact_form, $args = '' ) {
    // Get form field data
    $placeholder = isset( $_POST['snapshot_upload_placeholder'] ) ? sanitize_text_field( $_POST['snapshot_upload_placeholder'] ) : '';
    $css_class   = isset( $_POST['snapshot_upload_class'] ) ? sanitize_text_field( $_POST['snapshot_upload_class'] ) : '';
    $required    = isset( $_POST['snapshot_upload_required'] ) ? ' required' : '';

    // Generate the custom field tag with options
    $tag = sprintf( '[snapshot_upload placeholder="%s" class="%s" %s]', $placeholder, $css_class, $required );

    // Output the tag pane
    echo '<div class="wpcf7-tag-pane">';
    echo '<input type="text" class="wpcf7-tag" value="' . esc_attr( $tag ) . '" readonly />';
    echo '</div>';
}



// Shortcode for frontend image upload form
function cf7_snapshot_upload_render($tag) {

    $arr = (array)$tag;

    $css_class   = !empty( $tag->get_option( 'class', true ) ) ? $tag->get_option( 'class', true ) : '';
    $css_id   = !empty( $tag->get_option( 'id', true ) ) ? $tag->get_option( 'id', true ) : '';
    $name   = !empty( $arr['name'] ) ? $arr['name'] : '';
    $required    = $tag->get_option( 'required' ) ? ' required' : '';

    $form_tag = new WPCF7_FormTag( $attr );
    $attributes = $form_tag->attr;
    preg_match_all('/(\w+)=["\']([^"\']+)["\']/', $attributes, $matches);
    $atts = array();
    if (!empty($matches[1])) {
        foreach ($matches[1] as $index => $key) {
            $atts[$key] = $matches[2][$index];
        }
    }
    

    $random_key = isset($atts['id']) ? $atts['id'] : 'container-' . uniqid();
    $field_name = isset($name) ? $name : 'snapshot_upload';
    ob_start();
    ?>
    <div class="field-wrapper <?php echo $css_class; ?>" id="<?php echo $css_id; ?>">
        <div class="media_files_type">
            <div class="type">
                <input type="radio" value="upload" name="<?php echo $field_name; ?>_select" id="upload-media-local" onchange="media_uploadtype('.upload-media-local', '.upload-media-camera', '<?php echo $random_key; ?>')" checked>
                <label for="upload-media-local" class="upload-media-local">Brows</label>
                <input type="radio" value="camera" name="<?php echo $field_name; ?>_select" id="upload-media-camera" onchange="media_uploadtype('.upload-media-camera', '.upload-media-local', '<?php echo $random_key; ?>')">
                <label for="upload-media-camera" class="upload-media-camera">Camera</label>
            </div>
        </div>
        <div class="media-uploa-wrapper">
            <div class="brows-fields media-control-container upload-media-local">
                <input type="file" id="<?php echo $random_key; ?>_files" onchange="update_media_container(event, '<?php echo $random_key; ?>')" name="files" multiple>
                <label for="<?php echo $random_key; ?>_files" class="brows-cover"></label>
            </div>
            <div class="webcam-upload-form media-control-container" data-capture-container="<?php echo $random_key; ?>" data-capture-field="field-<?php echo $random_key; ?>">
                <div class="upload-media-camera" style="display: none;">
                    <div class="webcame-container" data-web-camera></div>
                    <button class="capture-btn" type="button" data-id="<?php echo $random_key; ?>" onclick="captureCameraImge(this, '<?php echo $random_key; ?>')" data-capture><img src="<?php echo plugin_dir_url(__FILE__) . 'assets/images/camera-icon.svg'; ?>" width="50" height="50"></button>
                </div>
                <div class="captured-images-container" id="<?php echo $random_key; ?>">
                </div>
                <input type="hidden" data-action="<?php echo esc_url(home_url()); ?>" name="snapshot_upload" id="field-<?php echo $random_key; ?>">
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}