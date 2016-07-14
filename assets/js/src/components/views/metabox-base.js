module.exports = function( app, $, gc ) {
	return app.views.base.extend({
		el : '#gc-related-data',

		ajax: function( args, successcb ) {
			return $.post( window.ajaxurl, $.extend( {
				action      : '',
				post        : this.model.toJSON(),
				nonce       : gc.$id( 'gc-edit-nonce' ).val(),
				flush_cache : gc.queryargs.flush_cache ? 1 : 0
			}, args ), successcb.bind( this ) );
		}
	});
};
