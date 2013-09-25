;(function($){
	var page_loaded = false;
	$(document).ready(function(){
		$('.gc_settings_col a').click(function(e){
			e.preventDefault();
			var el = $(this).closest('tr').next().find('> td > div');
			if(el.is(':visible')){
				el.slideUp('fast').fadeOut('fast',function(){
					el.parent().hide();
				});
				$(this).find('.caret').addClass('caret-up');
			} else {
				el.parent().show().end().slideDown('fast').fadeIn('fast');
				$(this).find('.caret').removeClass('caret-up');
			}
		});

		$('.gc_field_map input.live_filter').click(function(e){
			e.preventDefault();
			e.stopImmediatePropagation();
		}).keyup(function(e){
			var v = $(this).val(),
				lis = $(this).parent().siblings('li:not(.hidden-item):not(.divider)');
			if(!v || v == ''){
				lis.show();
			} else {
				lis.hide().filter(':icontains_searchable('+$(this).val()+')').show();
			}
		}).focus(function(){
			$(this).trigger('keyup');
		});

		$('.gc_field_map').find('a[data-value="_new_custom_field_"]').click(function(e){
			e.preventDefault();
			e.stopImmediatePropagation();
			var parent = $(this).parent();
			parent.before('<li class="custom-field inputting"><input type="text" /></li>');
			parent.prev().find('input').focus();
		}).end().find('ul.dropdown-menu a:not([data-value="_new_custom_field_"])').click(function(){
			var acf = '', acf_post = '';
			if($(this).hasClass('acf-field')){
				acf = $(this).attr('data-acf-field');
				acf_post = $(this).attr('data-acf-post');
			}
			$(this).closest('.gc_field_map').find('input.acf-field').val(acf).end().find('input.acf-post').val(acf_post);
		});

		$('.gc_field_map').on('keydown','li.inputting input', function(e){
			var key = e.keyCode || e.which;
			if(key == 13){
				$(this).trigger('blur');
			}
		}).on('blur','li.inputting input',function(e){
			var v = $(this).val(),
				li = $(this).parent(),
				prev = li.prev();
			if(!v || v == ''){
				li.remove();
			} else {
				$(this).parent().attr('data-post-type','normal').removeClass('inputting').html('<a href="#" />').find('a').attr('data-value',v).text(v).trigger('click');
			}
		});

		$('.gc_settings_fields > div.gc_settings_field[data-field-tab="meta"]').hide();

		$('.gc_settings_container .has_input').on('click','ul a',function(e){
			e.preventDefault();
			$(this).closest('.has_input').find('a:first span:first').html($(this).html()).siblings('input').val($(this).attr('data-value')).trigger('change');
		});
		$('.gc_import_as input').change(function(){
			var v = $(this).val(),
				c = $(this).closest('.gc_settings_container'),
				to = c.find('.gc_settings_header .gc_import_to'),
				cat = c.find('.gc_category');
			to.find('li[data-post-type]').filter('[data-post-type!="'+v+'"]').hide().addClass('hidden-item').end().filter('[data-post-type="'+v+'"]').show().removeClass('hidden-item');
			set_value(to);
			set_map_to_fields(c,v);
			var length = cat.find('li').filter('[data-post-type!="'+v+'"]').hide().addClass('hidden-item').end().filter('[data-post-type="'+v+'"]').show().removeClass('hidden-item').length;
			set_value(cat[(length>0?'show':'hide')]());
		}).each(function(){
			set_value($(this).closest('.btn-group'));
		});

		$('.gc_import_to input').change(function(){
			var elem = $(this).closest('.gc_settings_container');
			set_map_to_fields(elem,elem.find('.gc_import_as input').val());
		});

		$('.gc_include_meta input').change(function(){
			func = $(this).is(':checked') ? 'show':'hide';
			$(this).closest('.gc_settings_container').find('.gc_settings_fields > .gc_settings_field[data-field-tab="meta"]')[func]();
		}).trigger('change');


		$('.gc_settings_fields').sortable({
			handle: '.gc_move_field'
		});
		page_loaded = true;
	});

	function set_map_to_fields(elem,v){
		var to = to = elem.find('.gc_settings_header .gc_import_to'),
			to_val = to.find('input').val(),
			m = elem.find('.gc_settings_field .gc_field_map')
			text1 = 'attachment', text2 = 'normal';
		if(v == 'attachment'){
			text1 = 'normal';
			text2 = 'attachment';
		}
		m.each(function(){
			var selector = '[data-acf-post-types*="|'+v+'|"][data-acf-post-ids*="|'+to_val+'|"]'+(to_val!='0'?',[data-acf-post-ids*="|'+to_val+'|"]':'');
			$(this).find('li.acf-row').filter(':not('+selector+')')
				.hide().addClass('hidden-item')
			.end().filter(selector)
				.show().removeClass('hidden-item');
			$(this).find('li:not(.acf-row)').filter('[data-post-type*="|'+text1+'|"],:not([data-post-type*="|'+v+'|"])')
				.hide().addClass('hidden-item')
			.end().filter('[data-post-type="all"],[data-post-type*="|'+text2+'|"],[data-post-type*="|'+v+'|"]')
				.show().removeClass('hidden-item');
			set_value($(this),true);
		});
	};

	function set_value(elem,map_field){
		map_field = map_field || false;
		var v = elem.find('input:first').val(),
			el = elem.find('li:not(.hidden-item) a[data-value="'+v+'"]:first');
		if(map_field){
			if(elem.find('input.acf-field').val() != ''){
				el = elem.find('li:not(.hidden-item) a.acf-field[data-value="'+v+'"]:first');
				if(el.length == 0){
					el = elem.find('li:not(.hidden-item) a[data-value="'+v+'"]:first');
				}
			}
		}
		if(elem.not(':visible')){
			elem.find('input:first').val('');
		}
		if(el.length == 0){
			el = elem.find('li:not(.hidden-item) a:first');
		}
		el.trigger('click');
	};
    jQuery.expr[":"].icontains_searchable = jQuery.expr.createPseudo(function(arg) {
        return function( elem ) {
            return jQuery(elem).attr('data-search').toUpperCase().indexOf(arg.toUpperCase()) >= 0;
        };
    });
})(jQuery);
