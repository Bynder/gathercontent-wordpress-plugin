<?php
/*
Plugin Name: GatherContent Importer
Plugin URI: http://www.gathercontent.com
Description: Imports items from GatherContent to your wordpress blog
Version: 2.6.40
Author: Mathew Chapman
Author URI: http://www.gathercontent.com
License: GPL2
*/
require_once 'curl.php';
class GatherContent extends GatherContent_Curl {

	var $version = '2.6.40'; // used for javascript versioning

	function __construct() {
		parent::__construct();
		add_action( 'plugins_loaded', array(&$this, 'update_db_check') );
		add_action( 'admin_menu', array(&$this, 'admin_menu') );
		add_action( 'init', array(&$this, 'init') );
		add_action( 'wp_ajax_gathercontent_download_media', array(&$this, 'download_media') );
		add_action( 'wp_ajax_gathercontent_import_item', array(&$this, 'import_item') );
	}

	function update_db_check() {
		if ( get_option( 'gathercontent_version' ) != $this->version ) {
			$this->install(true);
		}
	}

	function install($networkwide) {
		global $wpdb;

		if( function_exists( 'is_multisite' ) && is_multisite() ) {

			if($networkwide) {
				$old_blog = $wpdb->blogid;
				$blogids = $wpdb->get_col( "SELECT blog_id FROM {$wpdb->blogs}" );
				foreach ( $blogids as $blog_id ) {
					switch_to_blog( $blog_id );
					$this->_install();
				}
				switch_to_blog( $old_blog );
				return;
			}
		}

		$this->_install();
	}

	function _install() {
		global $wpdb;

		$table_name = $wpdb->prefix . 'gathercontent_items';

		if( $wpdb->get_var( "SHOW TABLES LIKE '{$table_name}'" ) != $table_name ) {

			$sql = "CREATE TABLE IF NOT EXISTS $table_name (
			  `item_id` int(10) NOT NULL,
			  `project_id` int(10) NOT NULL,
			  `config` longblob NOT NULL,
			  UNIQUE KEY `item_id` (`item_id`)
			);";
			require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
			dbDelta( $sql );
			update_option( 'gathercontent_version', $this->version );
		}
	}

	function uninstall($networkwide) {
		global $wpdb;

		if( function_exists( 'is_multisite' ) && is_multisite() ) {

			if($networkwide) {
				$old_blog = $wpdb->blogid;
				$blogids = $wpdb->get_col( "SELECT blog_id FROM {$wpdb->blogs}" );
				foreach ( $blogids as $blog_id ) {
					switch_to_blog( $blog_id );
					$this->_uninstall();
				}
				switch_to_blog( $old_blog );
				return;
			}
		}

		$this->_uninstall();
	}

	function _uninstall() {
		global $wpdb;

		$table_name = $wpdb->prefix . 'gathercontent_items';

		$wpdb->query( "DROP TABLE IF EXISTS $table_name" );
	}

	function init() {

		load_plugin_textdomain( $this->base_name, false, $this->base_name . '/languages' );

		$scripts = array(
			'bootstrap-tooltips' => array(
				'file' => 'bootstrap.min.js',
				'dep' => array('jquery')
			),
			'main' => array(
				'file' => 'main.js',
				'dep' => array($this->base_name . '-bootstrap-tooltips')
			),
			'item_import' => array(
				'file' => 'item_import.js',
				'dep' => array('jquery-ui-sortable')
			),
			'media' => array(
				'file' => 'media.js',
				'dep' => array('jquery')
			)
		);
		foreach ($scripts as $handle => $vars)
			wp_register_script( $this->base_name . '-' . $handle, $this->plugin_url . 'js/' . $vars['file'], $vars['dep'], $this->version );

		$styles = array(
			'main' => 'main.css',
			'items' => 'items.css',
		);
		foreach ($styles as $handle => $file)
			wp_register_style( $this->base_name . '-' . $handle, $this->plugin_url . 'css/' . $file, false, $this->version );

	}

	function admin_menu() {
		$item = add_menu_page( 'GatherContent', 'GatherContent', 'publish_pages', $this->base_name, array(&$this, 'load_screen') );
		add_action( 'admin_print_scripts-' . $item, array($this, 'admin_print_scripts') );
		add_action( 'admin_print_styles-' . $item, array($this, 'admin_print_styles') );
		add_action( 'load-' . $item, array(&$this, 'save_settings') );
	}

	function save_settings() {
		$step = $this->step();
		if ( isset($_POST['_wpnonce']) ) {
			if ( wp_verify_nonce( $_POST['_wpnonce'],$this->base_name ) ) {
				$gc = isset($_POST['gc']) ? $_POST['gc'] : array();
				switch ( $step ) {
					case 'projects':
						if ( isset($_POST['project_id']) ) {
							$this->update( 'project_id', $_POST['project_id'] );
							$step = 'items';
						}
						break;
					case 'items':
						$import = array();
						foreach ( $gc as $key => $val ) {
							if ( substr( $key, 0, 7 ) == 'import_' && !empty( $val ) ) {
								$import[] = $val;
							}
						}
						if ( count( $import ) > 0 ) {
							$this->update( 'selected_items', $import );
							$step = 'item_import';
						}
						break;
					default:
						$url = $this->val( $gc, 'api_url' );
						$this->update( 'api_url', $url );
						$key = $this->val( $gc, 'api_key' );
						$this->update( 'api_key', $key );
						if ( $url != '' && $key != '' ) {
							$step = 'projects';
						}
						break;
				}
				wp_redirect( $this->url( $step, false ) );
			} else {
				$this->error = $this->__( 'Verification failed, please refreshing the item and try again .' );
			}
		} elseif ( $step == 'projects' && isset($_GET['_wpnonce']) && isset($_GET['set_project_id']) ) {
			if ( wp_verify_nonce( $_GET['_wpnonce'], $this->base_name ) ) {
				$this->update( 'project_id', $_GET['set_project_id'] );
				wp_redirect( $this->url( 'items', false ) );
			} else {
				$this->error = $this->__( 'Verification failed, please refreshing the item and try again .' );
			}
		} elseif ( $step == 'media' ) {
			$media = $this->option( 'media_files' );
			if ( !( is_array( $media ) && isset($media['total_files']) && $media['total_files'] > 0 ) ) {
				wp_redirect( $this->url( 'finished', false ) );
				return;
			}
		}
	}

	function download_media() {
		global $wpdb;
		$out = array('error' => $this->__( 'Verification failed, please refreshing the item and try again .' ));
		if ( isset($_GET['_wpnonce']) ) {
			if ( wp_verify_nonce( $_GET['_wpnonce'], $this->base_name ) ) {
				$cur_num = $_GET['cur_num'];
				$cur_total = $_GET['cur_total'];
				$retry = $_GET['cur_retry'];

				$media = $this->option( 'media_files' );
				$total = $media['total_files'];
				unset($media['total_files']);

				$post_id = key( $media );

				$state = 'draft';

				if ( $this->foreach_safe( $media[$post_id]['files'] ) ) {
					$cur_post = $media[$post_id];
					$item_total = $cur_post['total_files'];
					$more_than_1 = ( count( $cur_post['files'][0] ) > 1 );
					$file = array_shift( $cur_post['files'][0] );
					if ( ! $more_than_1 ) {
						array_shift( $cur_post['files'] );
					}

					$cur_settings = $this->option( 'saved_settings' );
					if ( isset( $cur_settings[$file['project_id']] ) &&
						isset( $cur_settings[$file['project_id']][$file['item_id']] ) ) {
						$state = $this->val( $cur_settings[$file['project_id']][$file['item_id']], 'state', 'draft' );
					}

					$id = $wpdb->get_col(
						$wpdb->prepare(
							"SELECT post_id FROM $wpdb->postmeta WHERE meta_key='gc_file_id' AND meta_value=%s",
							$file['id']
						)
					);
					if ( $id ) {
						$id = $id[0];
						$file['new_file'] = get_attached_file( $id );
						$file['title']    = get_the_title( $id );
						$file['url']      = wp_get_attachment_url( $id );
						$file['new_id']   = $id;
						$this->add_media_to_content( $post_id, $file, $more_than_1 );

						$out = $this->get_media_ajax_output( $post_id, $media, $cur_post, $item_total, $total, $state );
						$out['success']  = true;
						$out['new_file'] = $file['new_file'];
					} else {
						$uploads = wp_upload_dir();
						$filename = wp_unique_filename( $uploads['path'], $file['original_filename'], null );
						$new_file = $uploads['path'] . '/' . $filename;
						$fp = fopen( $new_file, 'w' );
						$resp = $this->_curl( 'https://gathercontent.s3.amazonaws.com/' . $file['filename'], array(CURLOPT_FILE => $fp) );
						fclose( $fp );
						if ( $resp['httpcode'] == 200 ) {
							extract( wp_check_filetype( $new_file ) );

							$name_parts = pathinfo( $filename );
							$name = trim( substr( $filename, 0, -( 1 + strlen( $name_parts['extension'] ) ) ) );

							$title = $name;
							$content = '';

							if ( $image_meta = @wp_read_image_metadata( $new_file ) ) {
								if ( trim( $image_meta['title'] ) && ! is_numeric( sanitize_title( $image_meta['title'] ) ) )
									$title = $image_meta['title'];
								if ( trim( $image_meta['caption'] ) )
									$content = $image_meta['caption'];
							}

							$object = array(
								'post_mime_type' => $type,
								'guid' => $uploads['url'] . '/' . $filename,
								'post_parent' => $post_id,
								'post_title' => $title,
								'post_content' => $content,
								'post_status' => 'publish',
							);
							$id = wp_insert_attachment( $object, $new_file );
							add_post_meta( $id, 'gc_file_id', $file['id'] );
							if ( ! is_wp_error( $id ) ) {
								wp_update_attachment_metadata( $id, wp_generate_attachment_metadata( $id, $new_file ) );

								$file['new_file'] = $new_file;
								$file['title'] = $title;
								$file['url'] = $uploads['url'] . '/' . $filename;
								$file['new_id'] = $id;
								$this->add_media_to_content( $post_id, $file, $more_than_1 );

								$out = $this->get_media_ajax_output( $post_id, $media, $cur_post, $item_total, $total, $state );
								$out['success'] = true;
								$out['new_file'] = $new_file;
							} else {
								if ( $retry == '1' ) {
									$out = $this->get_media_ajax_output( $post_id, $media, $cur_post, $item_total, $total );
									$out['success'] = false;
									$out['error'] = sprintf( $this->__( 'There was an error with the file (%s)' ), $new_file );
								} else {
									$out = array(
										'success' => false,
										'retry'   => true,
										'msg'     => sprintf( $this->__( 'Retrying to download (%s)' ), $file['original_filename'] )
									);
								}
								//retry
							}
						} else {
							if ( $retry == '1' ) {
								$out = $this->get_media_ajax_output( $post_id, $media, $cur_post, $item_total, $total );
								$out['success'] = false;
								$out['error'] = sprintf( $this->__( 'Failed to download the file (%s)' ), $file['original_filename'] );
							} else {
								$out = array(
									'success' => false,
									'retry'   => true,
									'msg'     => sprintf( $this->__( 'Retrying to download (%s)' ), $file['original_filename'] )
								);
							}
							//failed
						}
					}
				}
			}
		}
		echo json_encode( $out );
		exit;
	}

	function load_screen() {
		$data = array();
		if ( ! function_exists( 'curl_init' ) ) {
			$this->view( 'curl-error' );
			return;
		}
		$template = $this->step();
		switch ( $this->step() ) {
			case 'projects':
				$this->get_projects();
				$data['current'] = $this->option( 'project_id' );
				break;
			case 'items':
				$this->get_projects();
				$this->get_states();
				$this->get_items();
				$this->get_state_dropdown();
				$this->get_projects_dropdown();
				$data['item_count'] = $this->item_count;
				$data['item_settings'] = $this->generate_settings( $this->items );
				break;
			case 'item_import':
				$this->update( 'media_files', array() );
				$this->get_states();
				$this->get_items( true );
				$this->get_post_types();
				$this->item_overwrite_dropdown();
				$this->map_to_dropdown();
				$this->categories_dropdown();
				$this->parent_dropdown();
				$this->publish_dropdown();
				$this->format_dropdown();
				$cur_settings = $this->option( 'saved_settings' );
				if ( ! is_array( $cur_settings ) ) {
					$cur_settings = array();
				}
				$this->data['project_id'] = $this->option( 'project_id' );
				$data['item_count'] = $this->item_count;
				$this->data['saved_settings'] = $this->val( $cur_settings, $this->option( 'project_id' ), array() );
				$data['item_settings'] = $this->generate_settings( $this->items, -1, true );
				$template = 'item-import';
				break;
			case 'media':
				$media = $this->option( 'media_files' );
				if ( ! ( is_array( $media ) && isset($media['total_files']) && $media['total_files'] > 0 ) ) {
					wp_redirect( $this->url( 'finished', false ) );
					return;
				}
				unset($media['total_files']);
				$post_id = key( $media );
				$data = $this->get_item_title_array( $post_id );
				break;
			case 'login':
				$data = array(
					'api_url' => $this->option( 'api_url' ),
					'api_key' => $this->option( 'api_key' ),
				);
				break;
			case 'finished':
				$project_id = $this->option( 'project_id' );
				$this->delete_gc_items( $project_id );
				break;
		}
		$this->view( $template, $data );
	}

	function import_item() {
		global $wpdb;
		$out = array('error' => $this->__( 'Verification failed, please refreshing the item and try again .' ));
		if ( isset($_POST['_wpnonce']) && wp_verify_nonce( $_POST['_wpnonce'], $this->base_name ) ) {
			if ( isset($_POST['gc']) && isset($_POST['gc']['item_id']) ) {
				$gc = $_POST['gc'];
				$item_id = $gc['item_id'];
				$this->get_post_types();
				$project_id = $this->option( 'project_id' );
				$item = $this->get_gc_item( $item_id );
				$item = $item->config;
				$post_format = '0';
				$file_counter = 0;
				$total_files = 0;
				$files = array(
					'files' => array(),
					'total_files' => 0,
				);
				$save_settings = array();

				if ( $_POST['cur_counter'] == 0 ) {
					$this->update( 'media_files', array() );
				}
				if ( $item !== false ) {
					$this->get_files( $item_id );

					$post_fields = array( 'post_title', 'post_content', 'post_excerpt', 'post_author' );

					$config = $this->get_field_config( $item, $this->val( $this->files, $item_id, array() ) );

					$fields = $this->val( $gc, 'fields', array() );

					$parent_id = 0;
					if ( is_numeric( $gc['parent_id'] ) ) {
						$parent_id = $gc['parent_id'];
					}

					$save_settings = array(
						'post_type' => $gc['post_type'],
						'overwrite' => $gc['overwrite'],
						'category' => $gc['category'],
						'format' => $gc['format'],
						'parent_id' => $parent_id,
						'state' => $gc['state'],
						'fields' => array(),
					);

					$post_format = $gc['format'];

					$func = 'wp_insert_post';
					$post = array(
						'post_title' => $item->name,
						'post_type' => $save_settings['post_type'],
						'post_status' => 'draft',
						'post_category' => array(),
						'post_parent' => $parent_id,
					);
					if ( $save_settings['category'] > 0 ) {
						$post['post_category'][] = $save_settings['category'];
					}
					if ( $save_settings['overwrite'] > 0 ) {
						$func = 'wp_update_post';
						unset($post['post_title']);
						$post['ID'] = $save_settings['overwrite'];
					}
					$post['ID'] = $func($post);
					$save_settings['overwrite'] = $post['ID'];

					$new_post_fields = array();
					$new_meta_fields = array();
					$new_acf_fields = array();
					$post_tags = array();
					$post_cats = array();

					$chks = array(
						'gc_post_cat_' => 'post_cats',
						'gc_post_tags_' => 'post_tags',
					);

					foreach ( $fields as $info ) {
						$acf = $info['acf'];
						$acf_post = $info['acf_post'];
						$tab = $info['field_tab'];
						$map_to = $info['map_to'];
						$field_name = $info['field_name'];

						if ( $map_to == '_dont_import_' ) {
							$save_settings['fields'][$tab . '_' . $field_name] = $map_to;
							continue;
						} elseif ( isset( $config[$tab] ) && isset( $config[$tab]['elements'][$field_name] ) ) {
							$field = $config[$tab]['elements'][$field_name];
						} else {
							continue;
						}

						$save_settings['fields'][$tab . '_' . $field_name] = $map_to;

						$special = ($map_to == 'gc_featured_image_' || $map_to == 'gc_media_file_') ? true : false;

						if ( isset($chks[$map_to]) ) {
							if ( $field['type'] != 'files' ) {
								$values = $field['value'];
								if ( ! is_array( $values ) ) {
									$values = array_filter( explode( ',', strip_tags( $values ) ) );
								}
								foreach ( $values as $val ) {
									$val = trim( $val );
									if ( ! empty( $val ) ) {
										array_push( $$chks[$map_to], $val );
									}
								}
							}
							continue;
						} elseif ( $field['type'] == 'files' ) {
							if ( is_array( $field['value'] ) && count( $field['value'] ) > 0 ) {
								$new_files = array();
								foreach ( $field['value'] as $file ) {
									$file = (array) $file;
									$file['post_id'] = $post['ID'];
									$file['field'] = $map_to;
									$file['special_field'] = $special;
									$file['counter'] = $file_counter;
									if ( ! empty( $acf ) ) {
										$file['acf'] = array(
											'field_id' => $acf,
											'post_id' => $acf_post,
										);
									}
									$new_files[] = $file;
								}

								$total_files += count( $new_files );
								$files['files'][] = $new_files;
								$files['total_files'] = $total_files;

								$field['value'] = '#_gc_file_name_' . $file_counter . '#';
								$file_counter++;
							} else {
								$field['value'] = '';
							}
						}

						if ( $special ) {
						} elseif ( empty( $acf ) && in_array( $map_to, $post_fields ) ) {
							if ( ! isset( $new_post_fields[$map_to] ) ) {
								$new_post_fields[$map_to] = array();
							}
							if ( $field['type'] == 'choice_checkbox' && is_array( $field['value'] ) ) {
								$tmp = '<ul>';
								foreach ( $field['value'] as $value ) {
									$tmp .= '<li>' . $value . '</li>';
								}
								$tmp .= "</ul>\n\n";
								$field['value'] = $tmp;
							}
							$new_post_fields[$map_to][] = $field;
						} else {
							if ( ! empty( $acf ) ) {
								$save_settings['fields'][$tab . '_' . $field_name] = array($map_to, $acf, $acf_post);
								$new_acf_fields[$acf] = $field['value'];
							} else {
								if ( ! isset( $new_meta_fields[$map_to] ) ) {
									$new_meta_fields[$map_to] = '';
								}
								if ( $field['type'] != 'files' ) {
									$new_meta_fields[$map_to][] = $field['value'];
								}
							}
						}
					}

					foreach ( $new_post_fields as $name => $values ) {
						if ( count( $values ) > 1 ) {
							$post[$name] = '';
							foreach ( $values as $value ) {
								if ( $value['value'] != '' ) {
									if ( $name == 'post_title' ) {
										$post[$name] .= strip_tags( $value['value'] );
									}
									elseif ( $name == 'post_author' ) {
										$post[$name] = $this->get_author_id( strip_tags( $value['value'] ) );
									}
									else {
										$post[$name] .= $value['value']."\n\n";
									}
								}
							}
						} else {
							if ( $name == 'post_title' ) {
								$post[$name] = strip_tags( $values[0]['value'] );
							}
							elseif ( $name == 'post_author' ) {
								$post[$name] = $this->get_author_id( strip_tags( $values[0]['value'] ) );
							}
							else {
								$post[$name] = $values[0]['value'];
							}
						}
					}

					if ( $total_files == 0 ){
                        $post['post_status'] = $save_settings['state'];
                    }

					$set_post_terms = false;

					if ( count( $post_cats ) > 0 && count( $this->taxonomies[$post['post_type']] ) > 0 ) {
						$taxonomy = $this->taxonomies[$post['post_type']][key( $this->taxonomies[$post['post_type']] )];
						$set_post_terms = true;
						foreach ( $post_cats as $cat ) {
							$exists = term_exists( $cat, $taxonomy );
							if ( $exists ) {
								$post['post_category'][] = $exists['term_id'];
							} else {
								$term = wp_insert_term( $cat, $taxonomy );
								$post['post_category'][] = $term['term_id'];
							}
						}
					}

					if ( count( $post_tags ) > 0 && isset( $this->allows_tags[$post['post_type']] ) ) {
						$post_tag = '';
						foreach ( $post_tags as $tag ) {
							$post_tag .= ($post_tag == '' ? '':',').$tag;
						}
						$post['tax_input'] = array(
							'post_tag' => $post_tag,
						);
					}

					wp_update_post( $post );

					if ( $set_post_terms === true ) {
						wp_set_post_terms( $post['ID'], $post['post_category'], $taxonomy );
					}

					if ( current_theme_supports( 'post-formats' ) && post_type_supports( $post['post_type'], 'post-formats' ) ) {
						set_post_format( $post['ID'], $post_format );
					}

					foreach ( $new_meta_fields as $field => $values ) {
						delete_post_meta( $post['ID'], $field );
						if ( is_array( $values ) ) {
							foreach ( $values as $value ) {
								if ( ! empty( $value ) ) {
									if ( is_array( $value ) ) {
										foreach ( $value as $value2 ) {
											add_post_meta( $post['ID'], $field, maybe_serialize( $value2 ) );
										}
									} else {
										add_post_meta( $post['ID'], $field, maybe_serialize( $value ) );
									}
								}
							}
						} elseif ( ! empty( $values ) ) {
							add_post_meta( $post['ID'], $field, maybe_serialize( $values ) );
						}
					}

					foreach ( $new_acf_fields as $acf => $value ) {
						update_field( $acf, $value, $post['ID'] );
					}

					$media = $this->option( 'media_files' );
					if ( ! isset( $media['total_files'] ) ) {
						$media['total_files'] = 0;
					}

					if ( $total_files > 0 ) {
						$media[$post['ID']] = $files;
						if ( ! isset( $media['total_files'] ) ) {
							$media['total_files'] = 0;
						}
						$media['total_files'] += $total_files;
						$this->update( 'media_files', $media );
					}

					$cur_settings = $this->option( 'saved_settings' );
					if ( ! is_array( $cur_settings ) ) {
						$cur_settings = array();
					}
					if ( ! isset( $cur_settings[$project_id] ) ) {
						$cur_settings[$project_id] = array();
					}

					$cur_settings[$project_id][$item_id] = $save_settings;
					$this->update( 'saved_settings', $cur_settings );

					$out = array(
						'total_files'   => $total_files,
						'item_id'       => $item_id,
						'success'       => true,
						'item_percent'  => $this->percent( ++$_POST['cur_counter'], $_POST['total'] ),
						'redirect_url'  => ($media['total_files'] > 0 ? 'media' : 'finished'),
						'new_item_id'   => $post['ID'],
						'new_item_html' => '<li data-post-type="' . $save_settings['post_type'] . '" style="display: none;"><a href="#" title="' . esc_attr( $item->name ) . '" data-value="' . $post['ID'] . '">' . esc_html( $item->name ) . '</a></li>',
					);
				} else {
					$out = array(
						'error' => $this->__( 'There was a problem importing the item, please refresh and try again .' ),
					);
				}
			} else {
				$out = array(
					'error' => $this->__( 'There was a problem importing the item, please refresh and try again .' ),
				);
			}
		}
		echo json_encode( $out );
		exit;
	}
}
if ( is_admin() ) {
	$gc = new GatherContent;
	register_activation_hook( __FILE__, array( &$gc, 'install' ) );
	register_deactivation_hook( __FILE__, array( &$gc, 'uninstall' ) );
}
