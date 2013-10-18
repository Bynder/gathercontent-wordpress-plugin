<?php
/*
Plugin Name: GatherContent Importer
Plugin URI: http://www.gathercontent.com
Description: Imports pages from GatherContent to your wordpress blog
Version: 2.0.4
Author: Mathew Chapman
Author URI: http://www.gathercontent.com
License: GPL2
*/
require_once 'curl.php';
class GatherContent extends GatherContent_Curl {

	var $version = '2.0';

	function __construct(){
		parent::__construct();
		add_action('admin_menu', array(&$this, 'admin_menu'));
		add_action('init', array(&$this, 'init'));
		add_action('wp_ajax_download_media', array(&$this, 'download_media'));
	}

	function init(){

		load_plugin_textdomain($this->base_name, false, $this->base_name.'/languages' );

		$scripts = array(
			'bootstrap-tooltips' => array(
				'file' => 'bootstrap.min.js',
				'dep' => array('jquery')
			),
			'main' => array(
				'file' => 'main.js',
				'dep' => array($this->base_name.'-bootstrap-tooltips')
			),
			'pages_import' => array(
				'file' => 'pages_import.js',
				'dep' => array('jquery-ui-sortable')
			),
			'media' => array(
				'file' => 'media.js',
				'dep' => array('jquery')
			)
		);
		foreach($scripts as $handle => $vars)
			wp_register_script($this->base_name.'-'.$handle, $this->plugin_url.'js/'.$vars['file'], $vars['dep'], $this->version);

		$styles = array(
			'main' => 'main.css',
			'pages' => 'pages.css',
		);
		foreach($styles as $handle => $file)
			wp_register_style($this->base_name.'-'.$handle, $this->plugin_url.'css/'.$file, false, $this->version);

	}

	function admin_menu(){
		$page = add_menu_page('GatherContent', 'GatherContent', 'publish_pages', $this->base_name, array(&$this,'load_screen'));
		add_action('admin_print_scripts-'.$page, array($this,'admin_print_scripts'));
		add_action('admin_print_styles-'.$page, array($this,'admin_print_styles'));
		add_action('load-'.$page, array(&$this,'save_settings'));
	}

	function save_settings(){
		$step = $this->step();
		if(isset($_POST['_wpnonce'])){
			if(wp_verify_nonce($_POST['_wpnonce'],$this->base_name)){
				$gc = isset($_POST['gc']) ? $_POST['gc'] : array();
				switch($step){
					case 'projects':
						if(isset($_POST['project_id'])){
							$this->update('project_id',$_POST['project_id']);
							$step = 'pages';
						}
						break;
					case 'pages':
						if(isset($gc['page_id'])){
							$page_ids = $gc['page_id'];
							$import = array();
							foreach($page_ids as $id){
								if(isset($gc['import_'.$id])){
									$import[] = $id;
								}
							}
							if(count($import) > 0){
								$this->update('selected_pages',$import);
								$step = 'pages_import';
							}
						}
						break;
					case 'pages_import':
						$this->get_post_types();
						$this->get_pages(true);
						$this->get_files();
						$new_pages = array();
						$project_id = $this->option('project_id');
						if(isset($gc['page_id'])){
							$post_fields = array('post_title', 'post_content', 'post_excerpt');

							/*   Field Mapping Data     */
							$map_to = $this->val($gc,'map_to',array());
							$field_tab = $this->val($gc,'field_tab',array());
							$field_name = $this->val($gc,'field_name',array());
							$field_acf = $this->val($gc,'acf',array());
							$field_acf_post = $this->val($gc,'acf_post',array());

							$files = array();
							//$post_ids = array();
							$file_counter = 0;
							$total_file_counter = 0;

							$save_settings = array();

							foreach($gc['page_id'] as $key => $page_id){
								if(isset($gc['import_'.$page_id])){
									$cur_file_counter = 0;
									$cur_total_counter = 0;
									$page = $this->original_array[$page_id];

									$save_settings[$page_id] = array(
										'post_type' => $gc['post_type'][$key],
										'overwrite' => $gc['overwrite'][$key],
										'category' => $gc['category'][$key],
										'fields' => array()
									);

									$custom_fields = $this->get_field_config($page,$this->val($this->files,$page_id,array()));

									$meta_id = 0;
									$meta_fields = array();
									if(isset($this->meta_pages[$page_id])){
										$meta_id = $this->meta_pages[$page_id]->id;
										$meta_fields = $this->get_field_config($this->meta_pages[$page_id],$this->val($this->files,$meta_id,array()));
									}

									$func = 'wp_insert_post';
									$post = array(
										'post_title' => $page->name,
										'post_type' => $save_settings[$page_id]['post_type'],
										'post_status' => 'draft',
										'post_category' => array()
									);
									if($save_settings[$page_id]['category'] > 0){
										$post['post_category'][] = $save_settings[$page_id]['category'];
									}
									if($save_settings[$page_id]['overwrite'] > 0){
										$func = 'wp_update_post';
										$post['ID'] = $save_settings[$page_id]['overwrite'];
									}
									$post['ID'] = $func($post);
									$save_settings[$page_id]['overwrite'] = $post['ID'];

									$new_post_fields = array();
									$new_meta_fields = array();
									$new_acf_fields = array();
									$new_special_fields = array();
									$post_tags = array();
									$post_cats = array();
									if(isset($map_to[$page_id])){
										foreach($map_to[$page_id] as $idx => $fieldname){
											$acf = $field_acf[$page_id][$idx];
											$acf_post = $field_acf_post[$page_id][$idx];
											$tab = $field_tab[$page_id][$idx];
											if($fieldname == '_dont_import_'){
												$save_settings[$page_id]['fields'][$tab.'_'.$field_name[$page_id][$idx]] = $fieldname;
												continue;
											} elseif($tab == 'meta'){
												$field = $meta_fields[$field_name[$page_id][$idx]];
											} elseif($tab == 'content'){
												$field = $custom_fields[$field_name[$page_id][$idx]];
											} else {
												continue;
											}
											$save_settings[$page_id]['fields'][$tab.'_'.$field_name[$page_id][$idx]] = $fieldname;

											$special = ($fieldname == 'gc_featured_image_' || $fieldname == 'gc_media_file_') ? true : false;

											if($fieldname == 'gc_post_cat_'){
												if($field['type'] != 'attach'){
													if(is_array($field['value'])){
														foreach($field['value'] as $cat_name){
															$post_cats[] = $cat_name;
														}
													} else {
														$post_cats[] = $field['value'];
													}
												}
												continue;
											} elseif($fieldname == 'gc_post_tags_'){
												if($field['type'] != 'attach'){
													if(is_array($field['value'])){
														$post_tags += $field['value'];
													} else {
														$tags = array_filter(explode(',', strip_tags($field['value'])));
														foreach($tags as $tag){
															$tag = trim($tag);
															if(!empty($tag)){
																$post_tags[] = $tag;
															}
														}
													}
												}
												continue;
											} elseif($field['type'] == 'attach'){
												if(is_array($field['value']) && count($field['value']) > 0){
													if(!isset($files[$post['ID']])){
														$files[$post['ID']] = array(
															'files' => array(),
															'total_files' => 0
														);
													}

													$new_files = array();
													foreach($field['value'] as $file){
														$file = (array) $file;
														$file['post_id'] = $post['ID'];
														$file['field'] = $fieldname;
														$file['special_field'] = $special;
														$file['counter'] = $file_counter;
														if(!empty($acf)){
															$file['acf'] = array(
																'field_id' => $acf,
																'post_id' => $acf_post
															);
														}
														$new_files[] = $file;
													}
													$cur_total_counter += count($new_files);
													$total_file_counter += count($new_files);
													$files[$post['ID']]['files'][] = $new_files;
													$files[$post['ID']]['total_files'] = $cur_total_counter;

													//$post_ids[$post['ID']] = true;
													$field['value'] = '#_gc_file_name_'.$file_counter.'#';
													$file_counter++;
													$cur_file_counter++;
												} else {
													$field['value'] = '';
												}
											}

											if($special){
											} elseif(empty($acf) && in_array($fieldname,$post_fields)){
												if(!isset($new_post_fields[$fieldname])){
													$new_post_fields[$fieldname] = array();
												}
												if($field['type'] == 'checkbox' && is_array($field['value'])){
													$tmp .= '<ul>';
													foreach($field['value'] as $value){
														$tmp .= '<li>'.$value.'</li>';
													}
													$tmp .= "</ul>\n\n\n";
													$field['value'] = $tmp;
												}
												$new_post_fields[$fieldname][] = $field;
											} else {
												if(!empty($acf)){
													$save_settings[$page_id]['fields'][$tab.'_'.$field_name[$page_id][$idx]] = array($fieldname,$acf,$acf_post);
													$new_acf_fields[$acf] = $field['value'];
												} else {
													if(!isset($new_meta_fields[$fieldname])){
														$new_meta_fields[$fieldname] = '';
													}
													if($field['type'] != 'attach'){
														$new_meta_fields[$fieldname][] = $field['value'];
													}
												}
											}
										}
									}
									foreach($new_post_fields as $name => $values){
										if(count($values) > 1){
											$post[$name] = '';
											foreach($values as $value){
												if($value['value'] != ''){
													$post[$name] .= $value['value']."\n\n\n";
												}
											}
										} else {
											$post[$name] = $values[0]['value'];
										}
									}
									if($cur_file_counter == 0){
										$post['post_status'] = 'publish';
									}
									if(count($post_cats) > 0 && count($this->taxonomies[$post['post_type']]) > 0){
										$taxonomy = $this->taxonomies[$post['post_type']][key($this->taxonomies[$post['post_type']])];
										foreach($post_cats as $cat){
											$exists = term_exists($cat,$taxonomy);
											if($exists){
												$post['post_category'][] = $exists['term_id'];
											} else {
												$term = wp_insert_term($cat,$taxonomy);
												$post['post_category'][] = $term['term_id'];
											}
										}
									}
									if(count($post_tags) > 0 && isset($this->allows_tags[$post['post_type']])){
										$post_tag = '';
										foreach($post_tags as $tag){
											$post_tag .= ($post_tag == ''?'':',').$tag;
										}
										$post['tax_input'] = array(
											'post_tag' => $post_tag
										);
									}
									wp_update_post($post);
									foreach($new_meta_fields as $field => $values){
										delete_post_meta($post['ID'],$field);
										if(is_array($values)){
											foreach($values as $value){
												if(!empty($value)){
													if(is_array($value)){
														foreach($value as $value2){
															add_post_meta($post['ID'],$field,maybe_serialize($value2));
														}
													} else {
														add_post_meta($post['ID'],$field,maybe_serialize($value));
													}
												}
											}
										} elseif(!empty($values)) {
											add_post_meta($post['ID'],$field,maybe_serialize($values));
										}
									}
									foreach($new_acf_fields as $acf => $value){
										update_field($acf,$value,$post['ID']);
									}
									if(count($post_tags) > 0){
										wp_set_post_tags($post['ID'],$post_tags,true);
									}
								}
							}

							//$files['post_ids'] = array_keys($post_ids);
							$files['total_files'] = $total_file_counter;

							$cur_settings = $this->option('saved_settings');
							if(!is_array($cur_settings)){
								$cur_settings = array();
							}
							$cur_settings[$project_id] = $save_settings;
							$this->update('saved_settings',$cur_settings);
							$this->update('media_files',$files);
						}
						$step = 'media';
						break;
					default:
						$url = $this->val($gc,'api_url');
						$this->update('api_url',$url);
						$key = $this->val($gc,'api_key');
						$this->update('api_key',$key);
						if($url != '' && $key != ''){
							$step = 'projects';
						}
						break;
				}
				wp_redirect($this->url($step,false));
			} else {
				$this->error = $this->__('Verification failed, please refreshing the page and try again.');
			}
		} elseif($step == 'projects' && isset($_GET['_wpnonce']) && isset($_GET['set_project_id'])){
			if(wp_verify_nonce($_GET['_wpnonce'],$this->base_name)){
				$this->update('project_id',$_GET['set_project_id']);
				wp_redirect($this->url('pages',false));
			} else {
				$this->error = $this->__('Verification failed, please refreshing the page and try again.');
			}
		} elseif($step == 'media'){
			$media = $this->option('media_files');
			if(!(is_array($media) && isset($media['total_files']) && $media['total_files'] > 0)){
				wp_redirect($this->url('finished',false));
				return;
			}
		}
	}

	function download_media(){
		global $wpdb;
		$out = array('error' => $this->__('Verification failed, please refreshing the page and try again.'));
		if(isset($_GET['_wpnonce'])){
			if(wp_verify_nonce($_GET['_wpnonce'],$this->base_name)){
				$cur_num = $_GET['cur_num'];
				$cur_total = $_GET['cur_total'];
				$retry = $_GET['cur_retry'];

				$media = $this->option('media_files');
				$total = $media['total_files'];
				unset($media['total_files']);

				$post_id = key($media);
				if($this->foreach_safe($media[$post_id]['files'])){
					$cur_post = $media[$post_id];
					$page_total = $cur_post['total_files'];
					$more_than_1 = (count($cur_post['files'][0]) > 1);
					$file = array_shift($cur_post['files'][0]);
					if(!$more_than_1){
						array_shift($cur_post['files']);
					}

					$id = $wpdb->get_col($wpdb->prepare(
						"SELECT post_id FROM $wpdb->postmeta WHERE meta_key='gc_file_id' AND meta_value=%s",
						$file['id']
					));
					if($id){
						$id = $id[0];
						$file['new_file'] = get_attached_file($id);
						$file['title'] = get_the_title($id);
						$file['url'] = wp_get_attachment_url($id);
						$file['new_id'] = $id;
						$this->add_media_to_content($post_id,$file,$more_than_1);

						$out = $this->get_media_ajax_output($post_id,$media,$cur_post,$page_total,$total);
						$out['success'] = true;
						$out['new_file'] = $file['new_file'];

					} else {
						$uploads = wp_upload_dir();
						$filename = wp_unique_filename($uploads['path'],$file['original_filename'],null);
						$new_file = $uploads['path'].'/'.$filename;
						$fp = fopen($new_file,'w');
						$resp = $this->_curl('https://gathercontent.s3.amazonaws.com/'.$file['filename'],array(CURLOPT_FILE => $fp));
						fclose($fp);

						if($resp['httpcode'] == 200){
							extract(wp_check_filetype($new_file));

							$name_parts = pathinfo($filename);
							$name = trim( substr( $filename, 0, -(1 + strlen($name_parts['extension'])) ) );

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
								'guid' => $uploads['url'].'/'.$filename,
								'post_parent' => $post_id,
								'post_title' => $title,
								'post_content' => $content,
								'post_status' => 'publish',
							);
							$id = wp_insert_attachment($object, $new_file);
							add_post_meta($id,'gc_file_id',$file['id']);
							if(!is_wp_error($id)){
								wp_update_attachment_metadata($id, wp_generate_attachment_metadata($id,$new_file));

								$file['new_file'] = $new_file;
								$file['title'] = $title;
								$file['url'] = $uploads['url'].'/'.$filename;
								$file['new_id'] = $id;
								$this->add_media_to_content($post_id,$file,$more_than_1);

								$out = $this->get_media_ajax_output($post_id,$media,$cur_post,$page_total,$total);
								$out['success'] = true;
								$out['new_file'] = $new_file;
							} else {
								if($retry == '1'){
									$out = $this->get_media_ajax_output($post_id,$media,$cur_post,$page_total,$total);
									$out['success'] = false;
									$out['error'] = sprintf($this->__('There was an error with the file (%s)'),$new_file);
								} else {
									$out = array(
										'success'=>false,
										'retry'=>true,
										'msg'=>sprintf($this->__('Retrying to download (%s)'),$file['original_filename'])
									);
								}
								//retry
							}
						} else {
							if($retry == '1'){
								$out = $this->get_media_ajax_output($post_id,$media,$cur_post,$page_total,$total);
								$out['success'] = false;
								$out['error'] = sprintf($this->__('Failed to download the file (%s)'),$file['original_filename']);
							} else {
								$out = array(
									'success'=>false,
									'retry'=>true,
									'msg'=>sprintf($this->__('Retrying to download (%s)'),$file['original_filename'])
								);
							}
							//failed
						}
					}
				}
			}
		}
		echo json_encode($out);
		exit;
	}

	function load_screen(){
		$data = array();
		switch($this->step()){
			case 'projects':
				$this->get_projects();
				$data['current'] = $this->option('project_id');
				break;
			case 'pages':
				$this->get_projects();
				$this->get_states();
				$this->get_pages();
				$this->get_state_dropdown();
				$this->get_projects_dropdown();
				$data['page_count'] = $this->page_count;
				$data['page_settings'] = $this->generate_settings($this->pages);
				break;
			case 'pages_import':
				$this->get_states();
				$this->get_pages(true);
				$this->get_post_types();
				$this->page_overwrite_dropdown();
				$this->map_to_dropdown();
				$this->categories_dropdown();
				$cur_settings = $this->option('saved_settings');
				if(!is_array($cur_settings)){
					$cur_settings = array();
				}
				$this->data['project_id'] = $this->option('project_id');
				$data['page_count'] = $this->page_count;
				$this->data['saved_settings'] = $this->val($cur_settings,$this->option('project_id'),array());
				$data['page_settings'] = $this->generate_settings($this->pages,-1,true);
				break;
			case 'media':
				$media = $this->option('media_files');
				if(!(is_array($media) && isset($media['total_files']) && $media['total_files'] > 0)){
					wp_redirect($this->url('finished',false));
					return;
				}
				unset($media['total_files']);
				$post_id = key($media);
				$data = $this->get_page_title_array($post_id);
				break;
			case 'login':
				$data = array(
					'api_url' => $this->option('api_url'),
					'api_key' => $this->option('api_key')
				);
				break;
		}
		$this->view($this->step, $data);
	}
}
if(is_admin()){
	new GatherContent;
}
