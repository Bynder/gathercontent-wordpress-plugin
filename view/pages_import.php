<h2 class="gc_logo"><span>GatherContent</span></h2>
<?php
if($this->error != ''){
    echo '<p class="gc_error">'.$this->error.'</p>';
}
?>
<div class="gc_container gc_wide">
    <div class="gc_overlay"></div>
    <div class="gc_container gc_modal">
        <h2><?php $this->_e('Importing pages and text content...') ?></h2>
        <img src="<?php echo $this->plugin_url ?>img/ajax_loader_blue.gif" alt="" />
    </div>
    <div class="gc_container-header gc_cf">
        <h2><?php $this->_e('Choose pages to import') ?><a href="<?php $this->url('login') ?>" class="gc_blue_link gc_right"><?php $this->_e('Choose account') ?></a></h2>
    </div>
    <form action="<?php $this->url('pages_import') ?>" method="post" id="gc_importer_step_pages_import">
        <div class="gc_main_content">
            <div class="gc_search_pages gc_cf">
                <div class="gc_left">
                    <a href="<?php $this->url('pages') ?>" class="gc_option"><?php $this->_e('Select different pages') ?></a>
                </div>
                <div class="gc_right">
                    <?php $page_count > 0 && $this->get_submit_button($this->__('Import selected pages')) ?>
                </div>
            </div>
            <table class="gc_pages" cellspacing="0" cellpadding="0">
                <thead>
                    <tr>
                        <th></th>
                        <th class="gc_th_page_name"><?php echo $this->__('Pages'); ?></th>
                        <th></th>
                        <th><input type="checkbox" id="toggle_all" checked="checked" /></th>
                    </tr>
                </thead>
                <tbody>
                    <?php echo $page_settings ?>
                </tbody>
            </table>
        </div>
        <div class="gc_subfooter gc_cf">
            <div class="gc_right">
                <?php $page_count > 0 && $this->get_submit_button($this->__('Import selected pages')) ?>
            </div>
        </div>
        <?php wp_nonce_field($this->base_name) ?>
    </form>
</div>
