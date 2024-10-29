<?php
/**
 * Plugin Name: ACF Image Sizes
 * Description: Define the sizes of images created when uploading new images to ACFs image and gallery fields
 * Version: 1.0.0
 */

class Image_Size_ACF {

	public $image_sizes = array();


	public function __construct() {
		add_action( 'acf/render_field_settings/type=image', array( $this, 'add_acf_image_sizes' ) );
		add_action( 'acf/render_field_settings/type=gallery', array( $this, 'add_acf_image_sizes' ) );
		add_action( 'admin_menu', array( $this, 'add_admin_pages' ) );

		add_filter( 'acf/upload_prefilter/type=image', array( $this, 'acf_preupload' ), 10, 3 );
		add_filter( 'acf/upload_prefilter/type=gallery', array( $this, 'acf_preupload' ), 10, 3 );


		add_action( 'admin_enqueue_scripts', array( $this, 'image_sizes_acf_scripts' ) );
		add_action( 'wp_ajax_cleanup_acf_discovery_ajax', array( $this, 'cleanup_acf_discovery_ajax' ) );
		add_action( 'wp_ajax_cleanup_image_discovery_ajax', array( $this, 'cleanup_image_discovery_ajax' ) );
		add_action( 'wp_ajax_cleanup_acf_images_ajax', array( $this, 'cleanup_acf_images_ajax' ) );
		add_action( 'wp_ajax_generate_acf_images_ajax', array( $this, 'generate_acf_images_ajax' ) );

	}

	public function image_sizes_acf_scripts () {
		wp_enqueue_script( 'image_sizes_acf', plugin_dir_url( __FILE__ ) . '/image-sizes-acf.js', array('jquery'), 1, TRUE );

		$script_text = array(
			'standingBy'		=> __( 'Standing By', 'image-sizes-acf' ),
			'fetchingFields' 	=> __( 'Fetching Fields', 'image-sizes-acf' ),
			'fetchingImages' 	=> __( 'Fetching Images', 'image-sizes-acf' ),
			'creatingImages' 	=> __( 'Generating New Images', 'image-sizes-acf' ),
			'cleaningImages' 	=> __( 'Deleting Unused Images', 'image-sizes-acf' ),
			'finished'			=> __( 'Finished', 'image-sizes-acf' ),
			'cleanupConfirm'	=> __( 'Are you sure you want to stop the cleanup?', 'image-sizes-acf' ),
			'spaceSaved'		=> __( 'Space Saved', 'image-sizes-acf' ),
			'spaceUsed'			=> __( 'Space Used', 'image-sizes-acf' ),
		);

		wp_localize_script( 'image_sizes_acf', 'image_sizes_acf', array( 'adminUrl' => admin_url( 'admin-ajax.php' ), 'nonce' => wp_create_nonce( 'image-sizes-acf-nonce' ), 'text' => $script_text ) );
		wp_enqueue_style( 'admin-styles', plugin_dir_url( __FILE__ ) . 'image-sizes-acf.css' );
	}

	public function add_admin_pages() {

		add_submenu_page(
			'tools.php',
			'ACF Image Sizes',
			'ACF Image Sizes',
			'manage_options',
			'acf-image-sizes',
			array( $this, 'image_size_cleanup' )
		);

	}

	public function image_size_cleanup() {
		include ( __DIR__ . '/admin-image-sizes-acf.php' );
	}

	/**
	 * Collect all ACF image and gallery fields and return the field names with the size data
	 * @since 1.0.0
	 */
	public function cleanup_acf_discovery_ajax() {

		if ( ! check_ajax_referer( 'image-sizes-acf-nonce', 'imageSizesACF_nonce', FALSE ) ) {

			echo json_encode( array( 'error' => __( 'There was an error making your request. Please refresh the page and try again.', 'image-sizes-acf' ) ) );
			exit;

   		}

		// Get all ACF field posts
		$posts = get_posts( array(
			'post_type' => 'acf-field',
			'posts_per_page' => -1
		) );

		$return_data = array();

		foreach ( $posts as $post ) {

			$content = unserialize( $post->post_content );
			if ( 'image' == $content['type'] || 'gallery' == $content['type'] ) {

				$return_data[ $post->post_name ] = ( ! empty( $content['image_sizes'] ) ) ? $content['image_sizes'] : get_intermediate_image_sizes();

			}

		}

		echo json_encode( $return_data );
		die;

	}

	/**
	 * Collect all image ids and associated allowed image sizes
	 * @since  1.0.0
	 */
	public function cleanup_image_discovery_ajax() {

		if ( ! check_ajax_referer( 'image-sizes-acf-nonce', 'imageSizesACF_nonce', FALSE ) ) {

			echo json_encode( array( 'error' => __( 'There was an error making your request. Please refresh the page and try again.', 'image-sizes-acf' ) ) );
			exit;

   		}

		global $wpdb;

		$fields = $_POST['field_set'];

		$field_names = array_keys ( $fields );

		$string_placeholder = array_fill(0, count( $field_names ), '%s');


		// Put all the placeholders in one string ‘%s, %s, %s, %s, %s,…’
		$in_placeholder = implode(', ', $string_placeholder);

		// find stored field names based on field ids
		$field_meta = $wpdb->get_results(
			$wpdb->prepare( "SELECT " . $wpdb->postmeta . ".* FROM " . $wpdb->postmeta . " JOIN " . $wpdb->posts . " ON " . $wpdb->postmeta . ".post_id = " . $wpdb->posts . ".ID AND " . $wpdb->posts . ".post_type != 'revision' WHERE " . $wpdb->postmeta . ".meta_value IN (" . $in_placeholder . ")", $field_names )
		);

		$field_named_array = array();

		// Build array of named fields with their image sizes
		foreach ( $field_meta as $field ) {

			$field_named_array[ ltrim( $field->meta_key, '_' ) ] = $fields[ $field->meta_value ];

		}

		$field_named_array_keys = array_keys ( $field_named_array );
		$string_placeholder = array_fill(0, count( $field_named_array ), '%s');


		// Put all the placeholders in one string ‘%s, %s, %s, %s, %s,…’
		$in_placeholder = implode(', ', $string_placeholder);

		// find image ids based on field names
		$image_meta = $wpdb->get_results(
			$wpdb->prepare( "SELECT " . $wpdb->postmeta . ".* FROM " . $wpdb->postmeta . " JOIN " . $wpdb->posts . " ON " . $wpdb->postmeta . ".post_id = " . $wpdb->posts . ".ID AND " . $wpdb->posts . ".post_type != 'revision' WHERE " . $wpdb->postmeta . ".meta_key IN (" . $in_placeholder . ")", $field_named_array_keys )
		);

		$image_array = array();

		// Loop through image results and create an array of all image ids with associated allowed image sizes
		foreach( $image_meta as $image ) {

			if ( empty( $image->meta_value ) ) {

				continue;

			}

			// Gallery fields are serialized arrays so we need to try to unserialize before continuing.
			if ( ! $images_array = unserialize( $image->meta_value ) ) {

				$images_array = array( $image->meta_value );

			}

			foreach ( $images_array as $image_item ) {

				if ( ! is_array( $image_array[ $image_item ] ) ) {

					$image_array[ $image_item ] = array( 'name' => array_pop( explode( '/', get_the_guid( $image_item ) ) ), 'sizes' => array() );

				}

				$image_array[ $image_item ]['sizes'] = array_merge( $image_array[ $image_item ]['sizes'], $field_named_array[ $image->meta_key ] );
				$image_array[ $image_item ]['sizes'] = array_unique( $image_array[ $image_item ]['sizes'] );

			}

		}

		echo json_encode( $image_array );
		die;

	}

	public function cleanup_acf_images_ajax() {

		if ( ! check_ajax_referer( 'image-sizes-acf-nonce', 'imageSizesACF_nonce', FALSE ) ) {

			echo json_encode( array( 'error' => __( 'There was an error making your request. Please refresh the page and try again.', 'image-sizes-acf' ) ) );
			exit;

   		}

		$image_set = $_POST['imageSet'];
		$images_deleted_count = 0;
		$images_deleted = array();
		$space_saved = 0;

		foreach ( $image_set as $image ) {

			$image_id = $image['image'];
			$image_sizes = $image['sizes'];
			$image_meta_data = get_post_meta( $image_id, '_wp_attachment_metadata', true );

			$meta_sizes = $image_meta_data['sizes'];
			$wp_uploads = wp_upload_dir();

			foreach( $meta_sizes as $size => $size_data ) {

				list( $dir_year, $dir_month ) = explode( '/', $image_meta_data['file'] );
				if ( ! in_array( $size, $image_sizes ) && strpos( $size_data['mime-type'], 'image/' ) >= 0 ) {

					$file = $wp_uploads['basedir'] . '/' . $dir_year . '/' . $dir_month . '/' . $size_data[ 'file' ];

					if ( file_exists( $file ) ) {

						$images_deleted_count++;
						$space_saved += filesize( $file );
						$images_deleted[] = $size_data[ 'file' ];
						unlink( $file );
						unset( $image_meta_data['sizes'][ $size ] );

					}

				}

			}

			update_post_meta( $image_id, '_wp_attachment_metadata', $image_meta_data );

		}

		$return = array(
			'deleted_count' => $images_deleted_count,
			'deleted' => $images_deleted,
			'space_saved' => $space_saved,
		);

		echo json_encode( $return );
		die;

	}

	public function generate_acf_images_ajax() {

		if ( ! check_ajax_referer( 'image-sizes-acf-nonce', 'imageSizesACF_nonce', FALSE ) ) {

			echo json_encode( array( 'error' => __( 'There was an error making your request. Please refresh the page and try again.', 'image-sizes-acf' ) ) );
			exit;

   		}

		$image_set = $_POST['imageSet'];
		$image_sizes_stored = $this->get_image_sizes();

		$images_deleted_count = 0;
		$images_deleted = array();
		$space_saved = 0;
		$wp_uploads = wp_upload_dir();
		$out = array( 'images_added' => 0, 'sizes' => array(), 'space_used' => 0 );

		foreach ( $image_set as $image ) {

			$image_id = $image['image'];
			$image_sizes = $image['sizes'];
			$image_meta_data = get_post_meta( $image_id, '_wp_attachment_metadata', true );

			$meta_sizes = $image_meta_data['sizes'];
			$meta_size_list = array_keys( $meta_sizes );

			foreach( $image_sizes_stored as $size => $size_data ) {
				list( $dir_year, $dir_month ) = explode( '/', $image_meta_data['file'] );
				$ref_file = $wp_uploads['basedir'] . '/' . $image_meta_data['file'];
				$file_exist_ref = $wp_uploads['basedir'] . '/' . $dir_year . '/' . $dir_month . '/' . $meta_sizes[ $size ]['file'];
				if ( in_array( $size, $image_sizes ) ) {
						$image = wp_get_image_editor( $ref_file ); // Return an implementation that extends WP_Image_Editor
						if ( ! is_wp_error( $image ) ) {

						    $image->resize( $size_data['width'], $size_data['height'], $size_data['crop'] );
						    $gen_file = $image->generate_filename( null, $wp_uploads['basedir'] . '/' . $dir_year . '/' . $dir_month . '/' );
							if ( ! file_exists( $gen_file ) ) {
							    $image->save( $gen_file );
							    $out['images_added']++;
							    $out['space_used'] += filesize( $gen_file );
								$image_meta_data['sizes'][ $size ] = array(
									'file' => array_pop( explode( '/', $gen_file ) ),
									'width' => $size_data['width'],
									'height' => $size_data['height'],
									'mime-type' => get_post_mime_type( $image_id )
								);

							}

						}

				}

			}

			update_post_meta( $image_id, '_wp_attachment_metadata', $image_meta_data );

		}


		echo json_encode( $out );
		die;

	}

	/**
	 * Add the image size list to the image and gallery field settings
	 * @param array $field
	 * @since  1.0.0
	 */
	public function add_acf_image_sizes( $field ) {

		$image_size_list = array();
		$intermediate_image_sizes = get_intermediate_image_sizes();
		foreach ( $intermediate_image_sizes as $size ) {
			$image_size_list[ $size ] = ucfirst( str_replace( '_', ' ', str_replace( '-', ' ', $size ) ) );
		}
		acf_render_field_setting( $field, array(
			'label'			=> __( 'Image Sizes' ),
			'instructions'	=> __( 'Select image sizes to generate.<br>Selecting none will only store the original image.' ),
			'name'			=> 'image_sizes',
			'type'			=> 'checkbox',
			'choices'    	=> $image_size_list,
			'ui'			=> 1,
			'default_value' => $intermediate_image_sizes,
		), true);

	}

	/**
	 * Store the field image sizes into local variable to be used in image generation. Add the filter to trigger image size filtering
	 * @param  array $errors
	 * @param  array $file
	 * @param  array $field
	 * @return $errors
	 *
	 * @since 1.0.0
	 */
	public function acf_preupload( $errors, $file, $field ) {

		$this->image_sizes = ! empty( $field['image_sizes'] ) ? $field['image_sizes'] : array();
		add_filter( 'intermediate_image_sizes_advanced', array( $this, 'filter_image_sizes' ), 1, 2 );

		return $errors;

	}

	/**
	 * Filter out any unused image sizes
	 * @param  array $sizes
	 * @param  array $meta
	 * @return $sizes
	 */
	public function filter_image_sizes( $sizes, $meta ) {

		foreach( get_intermediate_image_sizes() as $size ) {

			if ( ! in_array( $size, $this->image_sizes ) ) {

				unset( $sizes[ $size ] );

			}

		}

		return $sizes;
	}

	private function get_image_sizes() {
		global $_wp_additional_image_sizes;

		$sizes = array();

		foreach ( get_intermediate_image_sizes() as $_size ) {
			if ( in_array( $_size, array('thumbnail', 'medium', 'medium_large', 'large') ) ) {
				$sizes[ $_size ]['width']  = get_option( "{$_size}_size_w" );
				$sizes[ $_size ]['height'] = get_option( "{$_size}_size_h" );
				$sizes[ $_size ]['crop']   = (bool) get_option( "{$_size}_crop" );
			} elseif ( isset( $_wp_additional_image_sizes[ $_size ] ) ) {
				$sizes[ $_size ] = array(
					'width'  => $_wp_additional_image_sizes[ $_size ]['width'],
					'height' => $_wp_additional_image_sizes[ $_size ]['height'],
					'crop'   => $_wp_additional_image_sizes[ $_size ]['crop'],
				);
			}
		}

		return $sizes;
	}


}

$luis = new Image_Size_ACF();