module.exports = function( app ) {
	return app.collections.base.extend({
		model : app.models.item,

		totalChecked : 0,
		allChecked   : false,
		syncEnabled  : false,
		processing   : false,

		initialize: function() {
			this.listenTo( this, 'checkAll', this.toggleChecked );
			this.listenTo( this, 'checkSome', this.toggleCheckedIf );
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

		toggleCheckedIf: function( cb ) {
			this.processing = true;
			this.each( function( model ) {
				model.set( 'checked', Boolean( 'function' === typeof cb ? cb( model ) : cb ) );
			} );
			this.processing = false;
			this.trigger( 'render' );
		},

		toggleChecked: function( checked ) {
			this.allChecked = checked;
			this.toggleCheckedIf( checked );
		},

		checkedCan: function( pushOrPull ) {
			switch( pushOrPull ) {
				case 'pull' :
					pushOrPull = 'canPull';
					break;
				case 'assign' :
					pushOrPull = 'disabled';
					break;
				// case 'push':
				default :
					pushOrPull = 'canPush';
					break;
			}

			var can = this.find( function( model ){
				return model.get( pushOrPull ) && model.get( 'checked' );
			} );

			return can;
		}

	});
};
