;(function($){
	var page_loaded = false;
	$(document).ready(function(){
		$('.repeat_config input').change(function(){
			var $t = $(this);
			if($t.is(':checked')){
				var c = $t.closest('tr'),
					page_id = c.attr('data-page-id'),
					table = $('#gc_pages'),
					rows = table.find('.gc_table_row'),
					idx = rows.index(c),
					field_rows = c.find('.gc_settings_field'),
					fields = {},
					import_as = $('#gc_import_as_'+page_id+' input').val();
				rows = rows.filter(':gt('+idx+')');
				field_rows.each(function(){
					var $t = $(this).removeClass('not-moved'),
						id = $t.attr('id').split('_')[2];
					fields[field_rows.index($t)] = [$t.find('.gc_field_map input[name*="map_to"]').val(),id];
				});

				rows.each(function(){
					var $t = $(this),
						page_id = $t.attr('data-page-id'),
						c = $('#gc_fields_'+page_id);
					c.find('> .gc_settings_field').addClass('not-moved');
					$('#gc_import_as_'+page_id+' a[data-value="'+import_as+'"]').trigger('click');
					for(var i in fields){
						if(fields.hasOwnProperty(i)){
							$('#gc_field_map_'+page_id+'_'+fields[i][1]+' li:not(.hidden-item) a[data-value="'+fields[i][0]+'"]').trigger('click');
							var field = $('#field_'+page_id+'_'+fields[i][1]).removeClass('not-moved');
							if(i > 0){
								c.find('> .gc_settings_field:eq('+(i-1)+')').after(field);
							} else {
								c.prepend(field);
							}
						}
					};
				});
			}
		});

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
		$('.gc_import_as').on('change', 'input', function(){
			var v = $(this).val(),
				c = $(this).closest('tr'),
				page_id = c.attr('data-page-id'),
				to = $('#gc_import_to_'+page_id),
				cat = $('#gc_category_'+page_id);
			to.find('li[data-post-type]').filter('[data-post-type!="'+v+'"]').hide().addClass('hidden-item').end().filter('[data-post-type="'+v+'"]').show().removeClass('hidden-item');
			set_value(to);
			set_map_to_fields(c,v, page_id);
			var length = cat.find('li').filter('[data-post-type!="'+v+'"]').hide().addClass('hidden-item').end().filter('[data-post-type="'+v+'"]').show().removeClass('hidden-item').length;
			set_value(cat[(length>0?'show':'hide')]());
		}).each(function(){
			set_value($(this).find('.btn-group'));
		});

		$('.gc_import_to').on('change', 'input', function(){
			var elem = $(this).closest('.gc_settings_container');
			set_map_to_fields(elem,$('#gc_import_as_'+elem.attr('data-page-id')+' input').val());
		});

		$('.gc_settings_fields').sortable({
			handle: '.gc_move_field'
		});
		page_loaded = true;
	});

	function set_map_to_fields(elem,v,page_id){
		var to = elem.find('#gc_import_to_'+page_id),
			to_val = to.find('input').val(),
			m = $('#gc_fields_'+page_id+' div.gc_field_map')
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
