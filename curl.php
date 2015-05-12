<?php
require_once 'functions.php';
class GatherContent_Curl extends GatherContent_Functions {

	var $has_acf = false;
	var $acf_pro = false;
	var $page_count = 0;
	var $page_ids = array();
	var $taxonomies = array();
	var $allows_tags = array();
	var $error = '';
	var $ids_used = array();
	var $files = array();
	var $hierarchical = array();

	function get_field_config( $obj, $files = array() ) {
		if ( $obj->config != '' ) {
			$config = json_decode( base64_decode( $obj->config ) );
			$new_config = array();
			$total_fields = 0;
			if ( $this->foreach_safe( $config ) ) {
				foreach ( $config as $tab_pane ) {
					$new_fields = array();

					if ( $this->foreach_safe( $tab_pane->elements ) ) {
						foreach ( $tab_pane->elements as $element ) {
							switch ( $element->type ) {
								case 'text':
									$val = $element->value;
									if ( !$element->plain_text ) {
										$val = preg_replace_callback(
											'#\<p\>(.+?)\<\/p\>#s',
											create_function(
												'$matches',
												'return "<p>".str_replace(array("\n","\r\n","\r"), " ", $matches[1])."</p>";'
											),
											$val
										);
										$val = str_replace( '</ul><', "</ul>\n<", $val );
										$val = preg_replace( '/<\/p>\s*<p>/m', "</p>\n<p>", $val );
										$val = preg_replace( '/<\/p>\s*</m', "</p>\n<", $val );
										$val = preg_replace( '/<p>\s*<\/p>/m','<p>&nbsp;</p>',$val );
										$val = str_replace( array('<ul><li', '</li><li>', '</li></ul>'), array("<ul>\n\t<li", "</li>\n\t<li>", "</li>\n</ul>"), $val );

										$val = preg_replace( '/<mark[^>]*>/i', '', $val );
										$val = preg_replace( '/<\/mark>/i', '', $val );
									}
									break;

								case 'choice_radio':
									$val = '';
									foreach ( $element->options as $idx => $option ) {
										if ( $option->selected ) {
											if ( isset( $option->value ) ) {
												$val = $option->value;
											}
											else {
												$val = $option->label;
											}
										}
									}
									break;

								case 'choice_checkbox':
									$val = array();
									foreach ( $element->options as $option ) {
										if ( $option->selected ) {
											$val[] = $option->label;
										}
									}
									break;

								case 'files':
									$val = $this->val( $files, $element->name, array() );
									break;

								default:
									continue 2;
									break;
							}
							$new_fields[$element->name] = array(
								'name' => $element->name,
								'label' => $element->label,
								'type' => $element->type,
								'value' => $val,
							);
						}
					}

					$total_fields += count($new_fields);

					$new_config[strtolower( $tab_pane->name )] = array(
						'label' => $tab_pane->label,
						'elements' => $new_fields,
					);
				}
			}

			$new_config['field_count'] = $total_fields;

			return $new_config;
		}
		return array();
	}

	function get_files( $page_id ) {
		$files = $this->get( 'get_files_by_page', array('id' => $page_id) );
		if ( $files && isset( $files->files ) && $this->foreach_safe( $files->files ) ) {
			foreach ( $files->files as $file ) {
				if ( !isset($this->files[$file->page_id]))
					$this->files[$file->page_id] = array();
				if ( !isset($this->files[$file->page_id][$file->field]))
					$this->files[$file->page_id][$file->field] = array();
				$this->files[$file->page_id][$file->field][] = $file;
			}
		}
	}

	function get_projects() {
		$projects = $this->get( 'get_projects' );

		$newprojects = array();
		if ( $projects && is_object( $projects ) && is_array( $projects->projects ) ) {
			foreach ( $projects->projects as $project ) {
				$newprojects[$project->id] = array(
					'name'       => $project->name,
					'page_count' => $project->page_count,
				);
			}
			asort( $newprojects );
		}
		$this->data['projects'] = $newprojects;
	}

	function get_states() {
		$states = $this->get( 'get_custom_states_by_project', array('id' => $this->option( 'project_id' ) ) );
		$new_states = array();
		$count = 5;
		if ( $states && $this->foreach_safe( $states->custom_states ) ) {
			foreach ( $states->custom_states as $state ) {
				$new_states[$state->id] = (object) array(
					'name'     => $state->name,
					'color_id' => $state->color_id,
					'position' => $state->position,
				);
				$count--;
			}
			uasort( $new_states, array(&$this, 'sort_pages') );
		}
		$this->data['states'] = $new_states;
	}

	function get_projects_dropdown() {
		$html = '';
		$url = $this->url( 'projects', false );
		$nonce = wp_create_nonce( $this->base_name );
		$project_id = $this->option( 'project_id' );
		$title = '';
		if ( $this->foreach_safe( $this->data['projects'] ) ) {
			foreach ( $this->data['projects'] as $id => $info ) {
				if ( $id == $project_id ) {
					$title = $info['name'];
				} else {
					$html .= '
					<li>
						<a href="' . $url . '&set_project_id=' . $id . '&_wpnonce=' . $nonce . '">' . $info['name'] . '</a>
					</li>';
				}
			}
			if ( $html != '' ) {
				$html = $this->dropdown_html( '<span>' . $title . '</span>', $html );
			}
		}
		$this->data['projects_dropdown'] = $html;
	}

	function get_state_dropdown() {
		$html = '
			<li>
				<a data-custom-state-name="All" href="#change-state"><span class="page-status"></span>  ' . $this->__( 'All' ) . '</a>
			</li>';
		if ( $this->foreach_safe( $this->data['states'] ) ) {
			foreach ( $this->data['states'] as $id => $state ) {
				$html .= '
				<li>
					<a data-custom-state-name="' . $state->name . '" data-custom-state-id="' . $id . '" href="#change-state"><span class="page-status page-state-color-' . $state->color_id . '"></span> ' . $state->name . '</a>
				</li>';
			}
		}
		$this->data['state_dropdown'] = $this->dropdown_html( '<i class="icon-filter"></i> <span>' . $this->__( 'All' ) . '</span>', $html );
	}

	function get_post_types() {
		$post_types = get_post_types( array( 'public' => true ), 'objects' );

		$this->_is_acf_enabled();

		$html = '';
		$new_post_types = array();
		$default = '';
		foreach ( $post_types as $type => $obj ) {
			$taxonomies = get_object_taxonomies( $type );
			if ( ( $id = array_search( 'post_tag', $taxonomies ) ) !== false ) {
				unset($taxonomies[$id]);
				$this->allows_tags[$type] = true;
			}
			if ( ( $id = array_search( 'post_format', $taxonomies ) ) !== false ) {
				unset($taxonomies[$id]);
			}
			$this->taxonomies[$type] = $taxonomies;
			if ( $default == '' ) {
				$default = $type;
			}

			if ( $obj->hierarchical ) {
				$this->hierarchical[$type] = true;
			}

			$html .= '
			<li>
				<a data-value="' . $type . '" href="#">' . $obj->labels->singular_name . '</a>
			</li>';
			$new_post_types[$type] = $obj->labels->singular_name;
		}
		$this->post_types = $new_post_types;
		$this->data['post_types_dropdown'] = $html;
		$this->default_post_type = $default;
	}

	function get_pages( $save_pages = false ) {
		$project_id = $this->option( 'project_id' );
		$pages = $this->get( 'get_pages_by_project', array('id' => $project_id ) );
		$original = array();
		$new_pages = array();
		$parent_array = array();
		if ( $pages && is_array( $pages->pages ) ) {
			foreach ( $pages->pages as $page ) {
				$original[$page->id] = $page;
				$parent_id = $page->parent_id;
				if ( ! isset( $parent_array[$parent_id] ) ) {
					$parent_array[$parent_id] = array();
				}
				$parent_array[$parent_id][$page->id] = $page;

				$this->page_count++;
			}
			foreach ( $parent_array as $parent_id => $page_array ) {
				$array = $page_array;
				uasort( $array, array(&$this, 'sort_pages') );
				$parent_array[$parent_id] = $array;
			}
			if ( isset( $parent_array[0] ) ) {
				foreach ( $parent_array[0] as $id => $page ) {
					$new_pages[$id] = $page;
					$new_pages[$id]->children = $this->sort_recursive( $parent_array, $id );
				}
			}
			else {
				foreach ( $parent_array as $parent => $pages ) {
					$new_pages += $pages;
				}
			}
		}
		$this->pages = $new_pages;
		$this->original_array = $original;

		if ( $save_pages ) {
			$selected = $this->option( 'selected_pages' );
			if ( ! $this->foreach_safe( $selected ) ) {
				$selected = array();
			}

			foreach ( $selected as $id ) {
				if ( isset( $original[$id] ) ) {
					$this->save_gc_page( $id, $project_id, $original[$id] );
				}
			}
		}
	}

	function page_overwrite_dropdown() {
		$html = $this->_get_tree_list();

		if ( $html != '' ) {
			$html = '
			<li class="divider"></li>' . $html;
		}

		$html = '
			<li>
				<a href="#" data-value="0">' . $this->__( 'New entry' ) . '</a>
			</li>' . $html;
		$this->data['overwrite_select'] = $html;
	}

	function parent_dropdown() {
		$html = '<li>
			<a href="#" data-value="0">' . $this->__( 'NONE' ) . '</a>
		</li>
		<li class="imported-page">
			<a href="#" data-value="_imported_page_">' . $this->__( 'Parent Being Imported' ) . '</a>
		</li>
		<li class="divider"></li>' . $this->_get_tree_list();
		$this->data['parent_select'] = $html;
	}

	function _get_tree_list() {
		if ( isset( $this->data['tree_list'] ) ) {
			return $this->data['tree_list'];
		}

		$html = '';
		$walker = new GC_Walker_PageDropdown();
		foreach ( $this->post_types as $name => $title ) {
			$args = array(
				'posts_per_page'  => -1,
				'offset'          => 0,
				'orderby'         => 'title',
				'order'           => 'ASC',
				'post_type'       => $name,
				'post_status'     => 'any',
			);
			$this->page_ids[$name] = array();
			$pages = get_posts( $args );
			if ( ! empty( $pages ) ) {
				$html .= call_user_func_array( array($walker, 'walk'), array($pages, 0, $args, 0, $this->base_name) );
				foreach ( $pages as $page ) {
					$this->page_ids[$name][] = $page->ID;
				}
			}
		}

		$this->data['tree_list'] = $html;

		return $this->data['tree_list'];
	}

	function _initialize_yoast() {
		wpseo_init();

		wpseo_admin_init();

		wpseo_load_textdomain();

		$options = WPSEO_Options::get_all();

		new WPSEO_Metabox;
		WPSEO_Metabox::translate_meta_boxes();

		if ( $options['opengraph'] === true || $options['twitter'] === true || $options['googleplus'] === true ) {
			new WPSEO_Social_Admin;
			WPSEO_Social_Admin::translate_meta_boxes();
		}
	}

	function _is_acf_enabled() {

		if( function_exists( 'acf_get_field_groups') ) {
			$this->has_acf = true;
			$this->acf_pro = true;
		}
		elseif( function_exists( 'api_acf_get_field_groups' ) ) {
			$this->has_acf = true;
		}
	}

	function _acf_get_field_groups($params) {
		if( $this->acf_pro ) {
			$acf = acf_get_field_groups( $params );

			$post_ids = array();

			foreach($acf as $post) {
				$post_ids[] = $post['ID'];
			}

			return $post_ids;
		}
		else {
			return apply_filters( 'acf/location/match_field_groups', array(), $params );
		}
	}

	function map_to_dropdown() {
		global $wpdb;
		$dont_allow = array('_wp_attachment_image_alt', '_wp_attachment_metadata', '_wp_attached_file', '_edit_lock', '_edit_last', '_wp_page_template');
		$html = '
			<li data-post-type="all" class="live_filter">
				<input type="text" class="live_filter" placeholder="' . esc_attr( $this->__( 'Search... ' ) ) . '" />
			</li>';
		$field_groups = array();
		$supports_custom = array();

		$yoast_field_type_array = array('text', 'textarea', 'select', 'upload', 'radio', 'multiselect');

		$yoast = false;
		if( function_exists( 'wpseo_admin_init' ) ) {
			$this->_initialize_yoast();
			$yoast = true;
		}

		$yoast_prefix = '_yoast_wpseo_';
		$yoast_fields = array();

		foreach ( $this->post_types as $name => $title ) {
			$supports = get_all_post_type_supports( $name );
			if ( isset( $supports['custom-fields'] ) ) {
				$supports_custom[] = $name;
			}
			if ( $name == 'attachment' ) {
				$html .= '
			<li data-post-type="|attachment|" data-search="' . esc_attr( $this->__( 'Title' ) ) . '">
				<a href="#" data-value="post_title">' . $this->__( 'Title' ) . '</a>
			</li>
			<li data-post-type="|attachment|" data-search="' . esc_attr( $this->__( 'Caption' ) ) . '">
				<a href="#" data-value="post_excerpt">' . $this->__( 'Caption' ) . '</a>
			</li>
			<li data-post-type="|attachment|" data-search="' . esc_attr( $this->__( 'Description' ) ) . '">
				<a href="#" data-value="post_content">' . $this->__( 'Description' ) . '</a>
			</li>
			<li data-post-type="|attachment|" data-search="' . esc_attr( $this->__( 'Alt Text' ) ) . '">
				<a href="#" data-value="_wp_attachment_image_alt">' . $this->__( 'Alt Text' ) . '</a>
			</li>';
			} else {
				$labels = array(
					'title'     => sprintf( $this->__( '%s Title' ), $title ),
					'editor'    => sprintf( $this->__( '%s Content' ), $title ),
					'excerpt'   => sprintf( $this->__( '%s Excerpt' ), $title ),
					'thumbnail' => $this->__( 'Featured Image' ),
					'tags'      => $this->__( 'Tags' ),
					'cats'      => $this->__( 'Category' ),
					'author'	=> $this->__( 'Author' ),
				);
				$fields = array(
					'editor'    => 'post_content',
					'title'     => 'post_title',
					'excerpt'   => 'post_excerpt',
					'thumbnail' => 'gc_featured_image_',
					'author'	=> 'post_author',
				);
				foreach ( $fields as $type => $fieldname ) {
					if ( isset( $supports[$type] ) ) {
						$html .= '
			<li data-post-type="|' . $name . '|" data-search="' . esc_attr( $labels[$type] ) . '">
				<a href="#" data-value="' . $fieldname . '">' . esc_html( $labels[$type] ) . '</a>
			</li>';
					}
				}
				if ( count( $this->taxonomies[$name] ) > 0 ) {
					$html .= '
			<li data-post-type="|' . $name . '|" data-search="' . esc_attr( $labels['cats'] ) . '">
				<a href="#" data-value="gc_post_cat_">' . esc_html( $labels['cats'] ) . '</a>
			</li>';
				}
				if ( isset( $this->allows_tags[$name] ) ) {
					$html .= '
			<li data-post-type="|' . $name . '|" data-search="' . esc_attr( $labels['tags'] ) . '">
				<a href="#" data-value="gc_post_tags_">' . esc_html( $labels['tags'] ) . '</a>
			</li>';
				}
			}

			if ( $this->has_acf ) {

				$acf = $this->_acf_get_field_groups( array( 'post_type' => $name ) );

				foreach ( $acf as $post_id ) {
					if ( ! isset( $field_groups[$post_id] ) ) {
						$field_groups[$post_id] = array('types' => array(), 'posts' => array());
					}
					$field_groups[$post_id]['types'][] = $name;
					$field_groups[$post_id]['posts'][] = 0;
				}

				foreach ( $this->page_ids[$name] as $page_id ) {

					$acf = $this->_acf_get_field_groups( array( 'post_id' => $page_id ) );

					foreach ( $acf as $post_id ) {
						if ( ! isset( $field_groups[$post_id] ) ) {
							$field_groups[$post_id] = array( 'posts' => array() );
						} elseif ( ! isset( $field_groups[$post_id]['posts'] ) ) {
							$field_groups[$post_id]['posts'] = array();
						}
						$field_groups[$post_id]['posts'][] = $page_id;
					}
				}
			}

			if( $yoast ) {
				$meta_boxes = array_merge( WPSEO_Meta::get_meta_field_defs( 'general', $name ), WPSEO_Meta::get_meta_field_defs( 'advanced' ), apply_filters( 'wpseo_save_metaboxes', array() ) );

				foreach ( $meta_boxes as $field_name => $meta_box ) {
					if(in_array($meta_box['type'], $yoast_field_type_array)) {
						$yoast_fields[$yoast_prefix . $field_name] = true;

						$html .= '
			<li data-post-type="|' . $name . '|" data-search="' . esc_attr( $meta_box['title'] ) . '">
				<a href="#" data-value="' . $yoast_prefix . esc_attr( $field_name ) . '">' . esc_html( $meta_box['title'] ) . '</a>
			</li>';

					}
				}

			}
		}


		foreach ( $field_groups as $id => $options ) {
			$options['posts'] = array_unique( $options['posts'] );

			if( $this->acf_pro ) {
				$fields = acf_get_fields( $id );
			}
			else {
				$fields = apply_filters( 'acf/field_group/get_fields', array(), $id );
			}

			foreach ( $fields as $field ) {
				$dont_allow[] = $field['key'];
				$text = $field['label'];
				if ( strlen( $text ) > 30 ) {
					$text = substr( $text, 0, 30 ) . '...';
				}
				$ext = '';
				if ( isset( $options['types'] ) && count( $options['types'] ) > 0 ) {
					$ext .= ' data-acf-post-types="|' . implode( '|', $options['types'] ) . '|"';
				}
				if ( isset( $options['posts'] ) && count( $options['posts'] ) > 0 ) {
					$ext .= ' data-acf-post-ids="|' . implode( '|', $options['posts'] ) . '|"';
				}
				$html .= '
			<li data-post-type="all" class="acf-row" data-search="' . esc_attr( $field['label'] ) . '"' . $ext . '>
				<a href="#" data-value="' . esc_attr( $field['name'] ) . '" data-acf-post="' . esc_attr( $id ) . '" data-acf-field="' . esc_attr( $field['key'] ) . '" title="' . esc_attr( $field['label'] ) . '" class="acf-field">' . esc_html( $text ) . '</a>
			</li>';
			}
		}


		if( $yoast ) {
			$yoast_fields[$yoast_prefix . 'linkdex'] = true;
			$dont_allow = array_merge( $dont_allow, array_keys( $yoast_fields ) );
		}


		$dont_allow = "'".implode( "','", $dont_allow )."'";
		$keys = $wpdb->get_col(
			"SELECT meta_key FROM $wpdb->postmeta WHERE meta_key NOT IN($dont_allow) GROUP BY meta_key ORDER BY meta_key"
		);
		$supports_custom = '|' . implode( '|', $supports_custom ) . '|';
		if ( $keys ) {
			natcasesort( $keys );
			foreach ( $keys as $key ) {
				$text = $key;
				if ( strlen( $key ) > 30 ) {
					$text = substr( $key, 0, 30 ) . '...';
				}
				$html .= '
			<li data-post-type="' . $supports_custom . '" class="custom-field" data-search="' . esc_attr( $key ) . '">
				<a href="#" data-value="' . esc_attr( $key ) . '" title="' . esc_attr( $key ) . '">' . esc_html( $text ) . '</a>
			</li>';
			}
		}
		$html .= '
			<li data-post-type="' . $supports_custom . '" data-search="' . $this->__( 'New Custom Field' ) . '">
				<a href="#" data-value="_new_custom_field_">' . $this->__( 'New Custom Field' ) . '</a>
			</li>
			<li class="divider" data-post-type="all"></li>
			<li data-post-type="all" data-search="' . $this->__( 'Do Not Import' ) . '">
				<a href="#" data-value="_dont_import_">' . $this->__( 'Do Not Import' ) . '</a>
			</li>';
		$this->data['map_to_select'] = $html;
	}

	function categories_dropdown() {
		$html = '';
		foreach ( $this->post_types as $name => $title ) {
			$r = array('hide_empty' => false);

			if ( $name != 'post' ) {
				$taxonomies = $this->taxonomies[$name];
				if ( count( $taxonomies ) > 0 )
				{
					$r['taxonomy'] = $taxonomies;
				} else {
					continue;
				}
			}

			$categories = get_categories( $r );
			if ( $categories ) {
				$html .= '<li class="level-0" data-post-type="'.$name.'"><a href="#" data-value="-1">' . esc_html( $this->__( 'Choose category' ) ) . '</a></li>';
				$r['post_type'] = $name;
				$walker = new GC_Walker_CategoryDropdown;
				$html .= call_user_func_array( array(&$walker, 'walk'), array($categories, 0, $r) );
			}
		}
		$this->data['category_select'] = $html;
	}

	function publish_dropdown() {
		$stati = get_post_stati( array('internal' => false), 'objects' );

		unset( $stati['future'], $stati['private'] );

		$html = '';

		foreach ( $stati as $status => $object ) {
			$html .= '
			<li>
				<a href="#" data-value="' . $status . '">' . $object->label . '</a>
			</li>';
		}
		$this->data['publish_select'] = $html;
	}

	function format_dropdown() {
		$html = '';

		$supported = array();

		if ( current_theme_supports( 'post-formats' ) ) {

			foreach ( $this->post_types as $name => $title ) {

				if( post_type_supports( $name, 'post-formats' ) ) {

					$supported[] = $name;

				}
			}
		}

		$supported_str = implode( '|', $supported );

		if( count( $supported ) > 0 ) {

			$post_formats = get_theme_support( 'post-formats' );

			foreach ( $post_formats[0] as $format ) {
				$html .= '
			<li data-post-type="|' . $supported_str . '|">
				<a href="#" data-value="' . esc_attr( $format ) . '">' . esc_html( get_post_format_string( $format ) ) . '</a>
			</li>';
			}
		}

		if( !empty( $html ) ) {
				$html = '
			<li data-post-type="|' . $supported_str . '|">
				<a href="#" data-value="0">' . get_post_format_string( 'standard' ) . '</a>
			</li>'.$html;
		}

		$this->data['post_format'] = $html;
	}

	function dropdown_html( $val, $html, $input = false, $real_val = '' ) {
		return '
		<div class="btn-group has_input">
			<a class="btn dropdown-toggle" data-toggle="dropdown" href="#">
				' . $val . '
				<span class="caret"></span>' . ( $input !== false ? '<input type="hidden" name="' . $input . '" value="' . esc_attr( $real_val ) . '" />' : '' ) . '
			</a>
			<ul class="dropdown-menu">
				' . $html . '
			</ul>
		</div>';
	}

	function get_parent_page_id( $parent_id, $selected_pages, $cur_settings ) {
		$new_parent_id = 0;
		if ( isset( $cur_settings['parent_id'] ) ) {
			$new_parent_id = $cur_settings['parent_id'];
		} else {
			if ( $parent_id > 0 ) {
				if ( in_array( $parent_id, $selected_pages ) ) {
					$new_parent_id = '_imported_page_';
				} else {
					$project_id = $this->option( 'project_id' );
					$cur_settings = $this->option( 'saved_settings', array() );
					if ( isset( $cur_settings[$project_id] ) &&
						isset( $cur_settings[$project_id][$parent_id] ) ) {
						$new_parent_id = $cur_settings[$project_id][$parent_id]['overwrite'];
					}
				}
			}
		}

		return $new_parent_id;
	}

	function generate_settings( $array, $index = -1, $show_settings = false ) {
		$out = '';
		$index++;
		$selected = $this->option( 'selected_pages' );
		if ( ! $this->foreach_safe( $selected ) ) {
			$selected = array();
		}
		foreach ( $array as $id => $page ) {
			if ( $show_settings && ! in_array( $id, $selected ) ) {
				if ( isset( $page->children ) && count( $page->children ) > 0 ) {
					$out .= $this->generate_settings( $page->children, $index, $show_settings );
				}
				continue;
			}
			$checked = $show_settings;
			$cur_settings = array();
			if ( isset( $this->data['saved_settings'][$id] ) ) {
				$cur_settings = $this->data['saved_settings'][$id];
			}
			$add = '';

			$parent_id = $page->parent_id;

			$config = $this->get_field_config( $page );

			$field_count = $this->val($config, 'field_count', 0);

			$show_fields = true;
			if ( $show_settings && $field_count == 0 ) {
				$show_fields = false;
			}
			$out .= '
				<tr class="gc_page' . ($checked?' checked':'') . '" data-page-state="' . $page->custom_state_id . '">
					<td class="gc_status"><span class="page-status page-state-color-' . $this->data['states'][$page->custom_state_id]->color_id . '"></span></td>
					<td class="gc_pagename">';


			if ( $index > 0 ) {
				for ( $i = 0; $i < $index; $i++ ) {
					$out .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
				}
				$out .= '↳';
			}

			$out .= ' <label for="import_' . $id . '">' . $page->name . '</label></td>
					<td class="gc_checkbox">' . ($show_fields?'<input type="checkbox" name="gc[import_' . $id . ']" id="import_' . $id . '" value="' . $id . '"' . ($checked?' checked="checked"':'') . ' />':'') . '</td>
				</tr>';

			if ( $show_settings ) {
				if ( $show_fields ) {
					$parent_id_value = $this->get_parent_page_id( $parent_id, $selected, $cur_settings );
					$add = '
					<tr class="gc_table_row" data-page-id="' . $id . '" data-parent-id="' . $parent_id . '">
						<td colspan="3" class="gc_settings_container">
							<div>
								<div class="gc_settings_header gc_cf">
									<div class="gc_cf">
										<div class="gc_setting gc_import_as" id="gc_import_as_' . $id . '">
											<label>' . $this->__( 'Import as' ) . ' </label>
											' . $this->dropdown_html( '<span></span>', $this->data['post_types_dropdown'], 'gc[post_type][]', $this->val( $cur_settings, 'post_type' ) ) . '
										</div>
										<div class="gc_setting gc_import_to" id="gc_import_to_' . $id . '">
											<label>' . $this->__( 'Import to' ) . ' </label>
											' . $this->dropdown_html( '<span></span>', $this->data['overwrite_select'], 'gc[overwrite][]', $this->val( $cur_settings, 'overwrite' ) ) . '
										</div>
										<div class="gc_setting gc_category" id="gc_category_' . $id . '">
											<label>' . $this->__( 'Category' ) . ' </label>
											' . $this->dropdown_html( '<span></span>', $this->data['category_select'], 'gc[category][]', $this->val( $cur_settings, 'category', '-1' ) ) . '
										</div>
										<div class="gc_setting gc_parent" id="gc_parent_' . $id . '">
											<label>' . $this->__( 'Parent' ) . ' </label>
											' . $this->dropdown_html( '<span></span>', $this->data['parent_select'], 'gc[parent][]', $parent_id_value ) . '
										</div>
										<div class="gc_setting gc_state" id="gc_state_' . $id . '">
											<label>' . $this->__( 'Status' ) . ' </label>
											' . $this->dropdown_html( '<span></span>', $this->data['publish_select'], 'gc[state][]', $this->val( $cur_settings, 'state', 'draft' ) ) . '
										</div>
										<div class="gc_setting gc_format" id="gc_format_' . $id . '">
											<label>' . $this->__( 'Format' ) . ' </label>
											' . $this->dropdown_html( '<span></span>', $this->data['post_format'], 'gc[format][]', $this->val( $cur_settings, 'format', '0' ) ) . '
										</div>
										<div class="gc_setting repeat_config">
											<label>' . $this->__( 'Repeat this configuration' ) . ' <input type="checkbox" id="gc_repeat_' . $id . '" name="gc[repeat_' . $id . ']" value="Y" /></label>
										</div>
									</div>
								</div>
								<div class="gc_settings_fields" id="gc_fields_' . $id . '">';

					$field_settings = $this->val( $cur_settings, 'fields', array() );

					if ( count( $field_settings ) > 0 ) {
						foreach ( $field_settings as $name => $value ) {
							list( $tab, $field_name ) = explode( '_', $name, 2 );
							$val = $acf = $acf_post = '';
							if ( is_array( $value ) )
							{
								$val = $value[0];
								$acf = $value[1];
								$acf_post = $value[2];
							} else {
								$val = $value;
							}
							if(isset( $config[$tab] ) && isset( $config[$tab]['elements'][$field_name] ) ) {
								$add .= $this->field_settings( $id, $config[$tab]['elements'][$field_name], $tab, $config[$tab]['label'], $val, $acf, $acf_post );
								unset( $config[$tab]['elements'][$field_name] );
							}
						}
					}

					unset( $config['field_count'] );

					foreach( $config as $tab_name => $tab ) {

						foreach ( $tab['elements'] as $field ) {
							$val = $acf = $acf_post = '';
							$cur = $this->val( $field_settings, $tab_name . '_' . $field['name'] );
							if ( is_array( $cur ) ) {
								$val = $cur[0];
								$acf = $cur[1];
								$acf_post = $cur[2];
							} else {
								$val = $cur;
							}
							$add .= $this->field_settings( $id, $field, $tab_name, $tab['label'], $val, $acf, $acf_post );
						}
					}

					$add .= '
								</div>
							</div>
						</td>
					</tr>';
				} else {
					$message = $this->__( 'This page is empty. You can %sadd some content to this page in GatherContent%s.' );
					$message = sprintf(
						$message,
						'<a href="https://' . $this->option( 'api_url' ) . ' . gathercontent.com/pages/view/' . $this->option( 'project_id' ) . '/' . $id . '" target="_blank">',
						'</a>'
					);
					$add = '
					<tr class="gc_table_row">
						<td colspan="4">
							<div class="alert alert-info">' . $message . '</div>
						</td>
					</tr>';
				}
			}
			$out .= $add;
			if ( isset( $page->children ) && count( $page->children ) > 0 )
			{
				$out .= $this->generate_settings( $page->children, $index, $show_settings );
			}
		}

		return $out;
	}

	function field_settings( $id, $field, $tab = 'content', $tab_label = '', $val = '', $acf_val = '', $acf_post = '' ) {
		if ( $field['type'] == 'section' )
		{
			return '';
		}
		$fieldid = $id . '_' . md5( $tab . '_' . $field['label'] );
		$counter = 0;
		while ( isset( $this->ids_used[$fieldid] ) )
		{
			$fieldid = $fieldid.$counter++;
		}
		$this->ids_used[$fieldid] = true;
		$html = '
		<div class="gc_settings_field gc_cf" id="field_' . $fieldid . '">
			<div class="gc_move_field"></div>
			<div class="gc_field_name gc_left"><div class="gc_tab_name gc_tooltip" title="' . esc_attr( $this->__('Tab') ) . '">' . $tab_label . '</div>' . $field['label'] . '</div>
			<div class="gc_field_map gc_right" id="gc_field_map_' . $fieldid . '">
				<span>' . $this->__( 'Map to' ) . '</span>
				' . $this->dropdown_html( '<span></span>', $this->data['map_to_select'], 'gc[map_to][' . $id . '][]', $val ) . '
				<input type="hidden" name="gc[acf][' . $id . '][]" value="' . esc_attr( $acf_val ) . '" class="acf-field" />
				<input type="hidden" name="gc[acf_post][' . $id . '][]" value="' . esc_attr( $acf_post ) . '" class="acf-post" />
			</div>
			<input type="hidden" name="gc[field_tab][' . $id . '][]" value="' . $tab . '" />
			<input type="hidden" name="gc[field_name][' . $id . '][]" value="' . $field['name'] . '" />
		</div>
		';
		return $html;
	}

	function _curl( $url, $curl_opts = array() ) {
		@set_time_limit( 60 );
		$session = curl_init();

		curl_setopt( $session, CURLOPT_URL, $url );
		curl_setopt( $session, CURLOPT_HEADER, false );
		curl_setopt( $session, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $session, CURLOPT_SSL_VERIFYPEER, true );
		curl_setopt( $session, CURLOPT_CAINFO, $this->plugin_path . 'cacert.pem' );

		curl_setopt_array( $session, $curl_opts );

		$response = curl_exec( $session );
		$httpcode = curl_getinfo( $session, CURLINFO_HTTP_CODE );
		curl_close( $session );
		return array('response' => $response, 'httpcode' => $httpcode);
	}

	function get( $command = '', $postfields = array() ) {
		$api_url = 'https://' . $this->option( 'api_url' ) . '.gathercontent.com/api/0.4/' . $command;
		$curl_opts = array(
			CURLOPT_HTTPAUTH   => CURLAUTH_DIGEST,
			CURLOPT_HTTPHEADER => array('Accept: application/json', 'Content-Type: application/x-www-form-urlencoded'),
			CURLOPT_USERPWD    => $this->option( 'api_key' ) . ':x',
			CURLOPT_POST       => true,
			CURLOPT_POSTFIELDS => http_build_query( $postfields ),
		);

		extract( $this->_curl( $api_url, $curl_opts ) );

		try {
			$resp = json_decode( $response );

			if ( isset( $resp->success ) && $resp->success === true ) {
				return $resp;
			} elseif ( isset($resp->error) ) {
				$error = $resp->error;
				if ( $error == 'You have to log in.' )
				{
					$error = $this->auth_error();
				}
				$this->error = $this->__( $error );
			} else {
				$this->error = $this->auth_error();
			}
		} catch(Exception $e) {
			$this->error = $this->__( 'There was a problem contacting the API. Please check your server allows it.' );
		}

		return false;
	}

	function auth_error() {
		return sprintf( $this->__( 'There was a problem contacting the API. Please check your API credentials. %sAuth Settings%s' ), '<a href="' . $this->url( 'login', false ) . '">','</a>' );
	}

	function sort_recursive( $pages, $current = 0 ) {
		$children = array();
		if ( isset( $pages[$current] ) ) {
			$children = $pages[$current];
			foreach ( $children as $id => $page ) {
				$children[$id]->children = $this->sort_recursive( $pages, $id );
			}
		}
		return $children;
	}

	function sort_pages( $a, $b ) {
		if ( $a->position == $b->position ) {
			if ( $a->id == $b->id ) {
				return 0;
			} else {
				return ($a->id < $b->id) ? -1 : 1;
			}
		}
		return ($a->position < $b->position) ? -1 : 1;
	}
}

class GC_Walker_PageDropdown extends Walker {
	var $tree_type = 'page';
	var $db_fields = array('parent' => 'post_parent', 'id' => 'ID');

	function start_el( &$output, $page, $depth = 0, $args = array(), $id = 0, $base_name = 'default' ) {
		$pad = str_repeat( '&nbsp;', $depth * 3 );

		$title = apply_filters( 'list_pages', $page->post_title, $page );
		if ( empty( $title ) ) {
			$title = __( '(no title)', $base_name );
		}
		$title_text = $title;
		if ( strlen( $title_text ) > 30 ) {
			$title_text = substr( $title_text, 0, 30 ) . '...';
		}

		$output .= '<li class="level-' . $depth . '" data-post-type="' . $page->post_type . '"><a href="#" title="' . esc_attr( $title ) . '" data-value="' . $page->ID . '">';
		$output .= $pad . esc_html( $title_text );
		$output .= "</a></li>\n";
	}
}
class GC_Walker_CategoryDropdown extends Walker {
	var $tree_type = 'category';
	var $db_fields = array('parent' => 'parent', 'id' => 'term_id');

	function start_el( &$output, $category, $depth = 0, $args = array(), $id = 0 ) {
		$pad = str_repeat( '&nbsp;', $depth * 3 );

		$output .= '<li class="level-' . $depth . '" data-post-type="' . $args['post_type'] . '"><a href="#" data-value="' . $category->cat_ID . '">';
		$output .= $pad . esc_html( $category->name );
		$output .= "</a></li>\n";
	}
}
