;(function($){
	var itemlist, itemlist_c;
	$(document).ready(function() {
		$('.gc_ajax_submit_button').click(function() {
			$('.gc_ajax_submit_button').addClass('btn-wait').removeClass('btn-success').find('img').show().end().find('span').text('Please wait...');
		});
		$('.gc-ajax-tooltip').tooltip().click(function(e){
			e.preventDefault();
			return false;
		});
		$('#toggle_all').change(function(){
			var checked = $(this).is(':checked');
			$('.gc_checkbox :checkbox').attr('checked',false).filter(':visible').attr('checked',checked).trigger('change');
		});
		$('.gc_search_items .gc_right .dropdown-menu a').click(function(e){
			e.preventDefault();
			if($(this).attr('data-custom-state-name') == 'All'){
				$('table tbody tr:not(:visible)').show();
			} else {
				var selector = '[data-item-state="'+$(this).attr('data-custom-state-id')+'"]';
				$('table tbody tr').filter(':not('+selector+')').hide().end().filter(selector).show();
			}
			$(this).closest('.btn-group').find('> a span:first').text($(this).attr('data-custom-state-name'));
		});
		$('#gc_live_filter').keyup(function(){
			var v = $.trim($(this).val()), items = $('table tbody tr');
			if(!v || v == ''){
				items.show();
			} else {
				v = v.toLowerCase();
				items.find('.gc_itemname label').each(function(){
					var e = $(this), t = e.text().toLowerCase(),
						show = (t.indexOf(v) > -1), func = (show?'show':'hide');
					e.closest('tr')[func]();
				});
			}
		}).change(function(){$(this).trigger('keyup')});

		itemlist = $('.gc_itemlist tr td');
		itemlist_c = $('#gc_itemlist_container');
		itemlist.click(function(e){
			if(!$(e.target).is(':checkbox,label')){
				var el = $(this).closest('tr').find(':checkbox');
				el.attr('checked',(el.is(':checked')?false:true)).trigger('change');
			}
		});

		itemlist.find(':checkbox').change(function(){
			var el = $(this).closest('tr'),
				checked = $(this).is(':checked');
			el[(checked?'addClass':'removeClass')]('checked');
			show_hide((itemlist.find(':checkbox:checked').length > 0));
		}).trigger('change');
	});

	function show_hide(show){
		if(!show && itemlist_c.hasClass('checked')){
			itemlist_c.removeClass('checked')
				.find('.gc_subfooter').slideUp('fast').fadeOut('fast').end()
				.find('.gc_search_items button').animate({
					'opacity': 0,
					'width': 'hide'
				},400);
		} else if(show && !itemlist_c.hasClass('checked')){
			itemlist_c.addClass('checked')
				.find('.gc_subfooter').slideDown('fast').fadeIn('fast').end()
				.find('.gc_search_items button').animate({
					'opacity': 1,
					'width': 'show'
				},400);
		}
	};
})(jQuery);
