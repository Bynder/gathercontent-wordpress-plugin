<h2 class="gc_logo"><span>GatherContent</span></h2>

<div class="gc_container">
    <div class="gc_container-header">
        <h2><?php $this->_e('Connect to your GatherContent account') ?></h2>
    </div>
    <form action="<?php $this->url('login') ?>" method="post" id="gc_importer_step_auth">
        <div>
            <label for="gc_api_url"><?php $this->_e('Your login URL') ?></label>
            <span class="gc_domainprefix">https://</span>
            <input type="text" name="gc[api_url]" id="gc_api_url" value="<?php echo esc_attr($this->option('api_url')) ?>" class="gc_api_url"/>
            <span class="gc_domain">.gathercontent.com</span>
        </div>
        <div>
            <label for="gc_api_key"><?php $this->_e('API Key') ?><a href="#" class="gc-ajax-tooltip" title="<?php esc_attr($this->_e('You can find your unique API key inside your GatherContent account by going to Personal Settings and opening the API tab.')) ?>"></a></label>
            <input type="text" name="gc[api_key]" id="gc_api_key" value="<?php echo esc_attr($this->option('api_key')) ?>" />
        </div>
        <div class="gc_cf">
            <?php $this->get_submit_button($this->__('Connect account')) ?>
        </div>
        <?php wp_nonce_field($this->base_name) ?>
    </form>
</div>
