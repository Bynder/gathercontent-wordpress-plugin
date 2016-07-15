<# if ( false === data.current || data.current ) { #>
<span class="gc-status-status <# if ( false === data.current ) { #>not-<# } #>current" title="<# if ( data.current ) { #><?php printf( esc_attr__( '%s is current.', 'gathercontent-importer' ), $this->get( 'label' ) ); ?><# } else { #><?php printf( esc_attr__( '%s is behind.', 'gathercontent-importer' ), $this->get( 'label' ) ); ?><# } #>">{{{ data.updated }}}</span>
<# } else { #>
	{{{ data.updated }}}
<# } #>
