<h2 class="gc_logo"><span>GatherContent</span></h2>
<?php
if($this->error != ''){
	echo '<p class="gc_error">'.$this->error.'</p>';
}
?>
<div class="gc_container">
	<div class="gc_container-header">
		<h2><?php $this->_e('Choose a project to import content from') ?></h2>
	</div>
	<form action="<?php $this->url('projects') ?>" method="post" id="gc_importer_step_projects">
		<?php if(count($projects) > 0): ?>
		<ul class="gc_list">
		<?php foreach($projects as $id => $info): $fieldid = 'gc_project_'.$id; ?>
			<li>
				<input type="radio" class="gc_radio" name="project_id" id="<?php echo $fieldid ?>" value="<?php echo $id ?>"<?php echo $current == $id ? ' checked="checked"':'' ?> />
				<label for="<?php echo $fieldid ?>" class="gc_label"><?php echo $info['name'] ?> &mdash; <span class="page-count"><?php echo $info['page_count'].' page'.($info['page_count'] == '1'?'':'s') ?></span></label>
			</li>
		<?php endforeach ?>
		</ul>
		<?php else: ?>
			<p class="gc_error"><?php $this->_e('No projects found') ?></p>
		<?php endif ?>
		<div class="gc_subfooter gc_cf">
			<div class="gc_left">
				<a href="<?php $this->url('login') ?>" class="gc_option"><?php $this->_e('Change account settings') ?></a>
			</div>
			<?php if(count($projects) > 0): ?>
			<div class="gc_right">
				<?php $this->get_submit_button($this->__('Import content')) ?>
			</div>
			<?php endif ?>
		</div>
		<?php wp_nonce_field($this->base_name) ?>
	</form>
</div>
