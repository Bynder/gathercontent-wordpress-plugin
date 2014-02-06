<?php
class GatherContent_Functions {

	var $base_name;
	var $plugin_path;
	var $plugin_url;
	var $step_error = false;
	var $step;
	var $data = array();

	function __construct(){
		$base_name = plugin_basename(__FILE__);
		$this->base_name = dirname($base_name);
		$this->plugin_url = WP_PLUGIN_URL.'/'.$this->base_name.'/';
		$this->plugin_path = WP_PLUGIN_DIR.'/'.$this->base_name.'/';
	}

	function get_submit_button($text,$tag='button',$ext=''){
		$html = '<'.$tag;
		if($tag == 'button'){
			$html .= ' type="submit"';
		}
		echo $html.' class="btn btn-success gc_ajax_submit_button"'.$ext.'><img src="'.$this->plugin_url.'img/ajax-loader.gif" /> <span>'.$text.'</span></'.$tag.'>';
	}

	function get_page_title_array($post_id){
		$data = array();
		$post = get_post($post_id);
		$title = isset($post->post_title) ? $post->post_title : '';
		$title = empty($title) ? '(no title)' : $title;
		$data['original_title'] = esc_attr(strip_tags(apply_filters('the_title',$title,$post_id)));
		if(strlen($title) > 30){
			$title = substr($title,0,27).'...';
		}
		$data['page_title'] = apply_filters('the_title',$title,$post_id);
		return $data;
	}

	function percent($num,$total){
		return number_format((($num / $total) * 100),2);
	}

	function foreach_safe($arr){
		if(is_array($arr) && count($arr) > 0){
			return true;
		}
		return false;
	}

	function url($key='',$echo=true){
		$url = menu_page_url($this->base_name,false);
		if($key != ''){
			if(strpos($url,'?') === false){
				$url .= '?';
			} else {
				$url .= '&';
			}
			$url .= 'step='.$key;
		}
		if(!$echo){
			return $url;
		}
		echo $url;
	}

	function enqueue($handle,$type='script'){
		if($type == 'script'){
			wp_enqueue_script($this->base_name.'-'.$handle);
		} else {
			wp_enqueue_style($this->base_name.'-'.$handle);
		}
	}

	function admin_print_scripts(){
		$this->enqueue('main');
		$this->enqueue($this->step());
	}

	function admin_print_styles(){
		$this->enqueue('main','style');
		$step = $this->step();
		if($step == 'pages' || $step == 'pages_import'){
			$this->enqueue('pages','style');
		}
	}

	function option($key){
		return get_option($this->base_name.'_'.$key);
	}

	function update($key,$val){
		update_option($this->base_name.'_'.$key,$val);
	}

	function _e($text){
		_e($text,$this->base_name);
	}

	function __($text){
		return __($text,$this->base_name);
	}

	function val($array,$field,$default=''){
		if(is_array($array) && isset($array[$field])){
			return $array[$field];
		}
		return $default;
	}

	function step(){
		if(isset($this->step)){
			return $this->step;
		}
		$steps = array('login','projects','pages','pages_import','media','finished');
		if(!(isset($_GET['step']) && in_array($_GET['step'],$steps))){
			$step = 'login';
			if($this->option('api_key') != '' && $this->option('api_url') != ''){
				$step = 'projects';
			}
		} else {
			$step = $_GET['step'];
		}
		$checks = array(
			'projects' => array('fields'=>array('api_key','api_url'),'prev'=>'login'),
			'pages' => array('fields'=>array('project_id'),'prev'=>'projects'),
			'pages_import' => array('fields'=>array('project_id'),'prev'=>'projects'),
			'media' => array('fields'=>array('project_id'),'prev'=>'projects'),
		);
		if(isset($checks[$step])){
			$error = false;
			foreach($checks[$step]['fields'] as $chk){
				if($this->option($chk) == ''){
					$error = $this->step_error = true;
					break;
				}
			}
			if($error){
				$_GET['step'] = $checks[$step]['prev'];
				return $this->step();
			}
		}
		$this->step = $step;
		return $step;
	}

	function view($file,$vars=array()){
		extract($this->data);
		extract($vars);
		include $this->plugin_path.'view/'.$file.'.php';
	}

	function add_media_to_content($post_id,$file,$more_than_1=false){
		$post_fields = array('post_title', 'post_content', 'post_excerpt');
		$image_file = file_is_displayable_image($file['new_file']);
		$html = $file['url'];
		if(isset($file['acf'])){
			update_field($file['acf']['field_id'],$file['new_id'],$post_id);
		} elseif(in_array($file['field'],$post_fields)){
			$tag = '#_gc_file_name_'.$file['counter'].'#';
			$post = get_post($post_id);
			if($image_file){
				$html = '<a href="'.$file['url'].'"><img src="'.$file['url'].'" alt="'.esc_attr($file['title']).'" /></a>'."\n";
			} else {
				$html = '<a href="'.$file['url'].'">'.$file['title'].'</a>'."\n";
			}
			if($more_than_1){
				$html .= $tag;
			}
			$post = (array) $post;
			$new_post = array(
				'ID' => $post_id,
				$file['field'] => str_replace($tag,$html,$post[$file['field']])
			);
			wp_update_post($new_post);
		} elseif($file['field'] == 'gc_featured_image_'){
			update_post_meta($post_id,'_thumbnail_id',$file['new_id']);
		} else {
			add_post_meta($post_id,$file['field'],$html);
		}

	}

	function get_media_ajax_output($post_id,$media,$cur_post,$page_total,$total){
		$cur_num = $_GET['cur_num'];
		$cur_total = $_GET['cur_total'];

		$next_id = $post_id;
		if($cur_num == $page_total){
			$page_percent = 100;
			$cur_num = 1;
			unset($media[$post_id]);
			$next_id = key($media);
			wp_update_post(array(
				'ID' => $post_id,
				'post_status' => 'publish',
			));
		} else {
			$page_percent = $this->percent($cur_num,$page_total);
			$cur_num++;
			$media[$post_id] = $cur_post;
		}
		$media['total_files'] = $total;
		$this->update('media_files',$media);
		if($cur_total == $total){
			$next_id = $post_id;
			$page_percent = $overall_percent = '100';
		} else {
			$overall_percent = $this->percent($cur_total,$total);
		}
		$cur_total++;

		$data = $this->get_page_title_array($next_id);

		if($overall_percent == 100) {
			$this->update('media_files', array());
		}

		$out = array(
			'page_percent' => $page_percent,
			'overall_percent' => $overall_percent,
			'cur_num' => $cur_num,
			'cur_total' => $cur_total,
			'page_title' => $data['page_title'],
			'original_title' => $data['original_title'],
		);
		return $out;
	}
}
