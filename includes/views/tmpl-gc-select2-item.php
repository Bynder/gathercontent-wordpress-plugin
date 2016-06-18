<span>
	{{ data.text }}
	<# if ( data.color ) { #>
	<span class="gc-status-color" style="background-color:{{ data.color }};"></span>
	<# } #>
</span>
<# if ( data.description ) { #>
<div class="description">{{ data.description }}</div>
<# } #>
