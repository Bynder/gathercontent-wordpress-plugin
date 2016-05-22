<a href="#" class="gc-reveal-items dashicons-before dashicons-arrow-right description <?php $this->output( 'class', 'esc_attr' ); ?>"><?php _e( 'Reveal Items', 'gathercontent-import' ); ?> </a>
<ul class="<?php $this->output( 'class', 'esc_attr' ); ?> gc-reveal-items-list hidden">
	<?php foreach ( $this->get( 'items' ) as $item ) : ?>
	<li><a href="<?php $this->output( 'platform_url', 'esc_url' ); ?>item/<?php echo esc_attr( $item->id ); ?>" target="_blank"><?php echo esc_attr( $item->name ); ?></a></li>
	<?php endforeach; ?>
</ul>
