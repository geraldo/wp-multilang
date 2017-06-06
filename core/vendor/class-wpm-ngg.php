<?php
/**
 * Class for capability with NextGEN Gallery
 */

namespace WPM\Core\Vendor;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( defined( 'NGG_PLUGIN' ) ) {

	/**
	 * Class WPM_NGG
	 * @package  WPM\Core\Vendor
	 * @category Vendor
	 * @author   VaLeXaR
	 * @since    1.2.0
	 */
	class WPM_NGG {

		/**
		 * WPM_NGG constructor.
		 */
		public function __construct() {
			add_filter( 'localization', 'wpm_translate_string' );
			add_filter( 'wpm_admin_pages', array( $this, 'add_admin_pages' ) );
			add_filter( 'ngg_manage_gallery_fields', array( $this, 'filter_fields' ), 11 );
			add_filter( 'ngg_manage_images_row', array( $this, 'translate_gallery_object' ) );
			add_action( 'admin_init', array( $this, 'save_gallery' ) );
		}


		public function save_gallery() {

			if ( isset ( $_POST['update_album'] ) ) {

				$fields = array( 'album_name' => 'name', 'album_desc' => 'albumdesc' );
				$data   = array();

				foreach ( $fields as $key => $field ) {
					$data[ $key ] = $_POST[ $key ];
				}

				if ( $album = \C_Album_Mapper::get_instance()->find( wpm_clean( $_POST['act_album'] ) ) ) {
					foreach ( $data as $key => $value ) {
						if ( ! wpm_is_ml_string( $value ) ) {
							$old_value     = wpm_value_to_ml_array( $album->{$fields[ $key ]} );
							$value         = wpm_set_language_value( $old_value, $value, array() );
							$_POST[ $key ] = wpm_ml_value_to_string( $value );
						}
					}
				}
			}

			if ( isset( $_POST['page'] ) && $_POST['page'] == 'manage-images' ) {

				if ( isset ( $_POST['updatepictures'] ) ) {

					check_admin_referer( 'ngg_updategallery' );

					if ( ! isset ( $_GET['s'] ) ) {

						$fields = array( 'title', 'galdesc' );
						$data   = array();

						foreach ( $fields as $field ) {
							$data[ $field ] = $_POST[ $field ];
						}

						// Update the gallery
						$mapper = \C_Gallery_Mapper::get_instance();
						if ( $entity = $mapper->find( wpm_clean( $_GET['gid'] ) ) ) {
							foreach ( $data as $key => $value ) {
								if ( ! wpm_is_ml_string( $value ) ) {
									$old_value     = wpm_value_to_ml_array( $entity->$key );
									$value         = wpm_set_language_value( $old_value, $value, array() );
									$_POST[ $key ] = wpm_ml_value_to_string( $value );
								}
							}
						}
					}

					$image_mapper = \C_Image_Mapper::get_instance();

					foreach ( $_POST['images'] as $pid => $image ) {

						$data = array();

						if ( isset( $image['description'] ) ) {
							$data['description'] = stripslashes( $image['description'] );
						}
						if ( isset( $image['alttext'] ) ) {
							$data['alttext'] = stripslashes( $image['alttext'] );
						}

						$old_image = $image_mapper->find( $pid );

						// Update all fields
						foreach ( $data as $key => $value ) {
							if ( ! wpm_is_ml_string( $value ) ) {
								$old_value                       = wpm_value_to_ml_array( $old_image->$key );
								$value                           = wpm_set_language_value( $old_value, $value, array() );
								$_POST['images'][ $pid ][ $key ] = wpm_ml_value_to_string( $value );
							}
						}
					}
				}
			}

		}

		public function add_admin_pages( $pages_config ) {

			$screen    = get_current_screen();
			$screen_id = $screen ? $screen->id : '';

			$admin_pages = array(
				'_page_nggallery-manage-gallery',
				'_page_nggallery-manage-album'
			);

			foreach ( $admin_pages as $admin_page ) {
				if ( strpos( $screen_id, $admin_page ) !== false ) {
					$pages_config[] = $screen_id;
				}
			}

			return $pages_config;
		}


		public function filter_fields( $fields ) {

			$translate_fields = array(
				'title',
				'description'
			);

			foreach ( $translate_fields as $field ) {
				if ( isset( $fields['left'][ $field ] ) ) {
					$fields['left'][ $field ]['callback'][0]->gallery = $this->translate_gallery_object( $fields['left'][ $field ]['callback'][0]->gallery );
				}
			}

			return $fields;
		}


		public function translate_gallery_object( $object ) {

			foreach ( get_object_vars( $object ) as $key => $content ) {
				switch ( $key ) {
					case 'title':
					case 'galdesc':
					case 'alttext':
					case 'description':
						$object->$key = wpm_translate_string( $content );
						break;
				}
			}

			return $object;
		}
	}

	new WPM_NGG();
}
