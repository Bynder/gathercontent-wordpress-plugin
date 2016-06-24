module.exports = function( app ) {
	return app.collections.base.extend({
		model : app.models.item,
		totalChecked : 0,
		allChecked : false,
		syncEnabled : false,

		initialize: function() {
			this.listenTo( this, 'checkAll', this.toggleChecked );
			this.listenTo( this, 'change:checked', this.checkChecked );
		},

		checkChecked: function( model ) {
			var render = false;

			if ( model.changed.checked ) {
				this.totalChecked++;
			} else {
				if ( this.totalChecked === this.length ) {
					this.allChecked = false;
					render = true;
				}
				this.totalChecked--;
			}

			var syncWasEnabled = this.syncEnabled;
			this.syncEnabled = this.totalChecked > 0;

			if ( syncWasEnabled !== this.syncEnabled ) {
				this.trigger( 'enabledChange', this.syncEnabled );
			}

			if ( this.totalChecked < this.length ) {
				this.trigger( 'notAllChecked', false );
			}
		},

		toggleChecked: function( checked ) {
			this.allChecked = checked;
			this.each( function( model ) {
				model.set( 'checked', checked ? true : false );
			});
			this.trigger( 'render' );
		}
	});
};
