module.exports = function( app, $, gc ) {
	var thisView;
	var base = require( './../views/metabox-base.js' )( app, $, gc );
	return base.extend({
		template : wp.template( 'gc-mapping-metabox' ),
		stepArgs : false,
		events : {
			'click #gc-map' : 'step',
			'change #select-gc-next-step' : 'setProperty'
		},

		initialize: function() {
			thisView = this;
			this.listenTo( this.model, 'change:waiting', this.toggleWaitingRender );
			this.listenTo( this.model, 'change', this.maybeEnableAndRender );
			this.listenTo( this.model, 'change:step', this.startedClass );
			this.render();
			this.$el.removeClass( 'no-js' ).addClass( 'gc-mapping-metabox' );
		},

		startedClass: function( model ) {
			if ( 'accounts' === model.changed.step ) {
				this.$el.addClass( 'gc-mapping-started' );
			}

			this.stepArgs = this[ 'step_'+ model.changed.step ]();
		},

		setProperty: function( evt ) {
			var value = $( evt.target ).val();

			this.model.set( this.stepArgs.property, value );
		},

		setMapping: function() {
			var success = function( response ) {
				if ( response.success ) {

					this.model.set( 'waiting', false );

					// Goodbye
					app.reinit( this.model );

				} else {
					this.failMsg( response.data );
				}
			};

			this.ajax( {
				action : 'gc_save_mapping_id',
			}, success ).fail( function() {
				this.failMsg();
			} );
		},

		maybeEnableAndRender: function( model ) {
			if ( model.changed.account || model.changed.project || model.changed.mapping ) {
				this.model.set( 'btnDisabled', false );
				this.render();
			}
		},

		toggleWaitingRender: function( model ) {
			if ( model.changed.waiting ) {
				this.model.set( 'btnDisabled', true );
			}
			this.render();
		},

		step: function() {
			this.model.set( 'waiting', true );

			if ( 'mapping' === this.stepArgs.property ) {
				return this.setMapping();
			}

			this.setStep();

			var success = function( response ) {
				if ( response.success ) {

					var cb = this.stepArgs.success || this.successHandler;
					cb.call( this, response.data );

					this.model.set( 'waiting', false );

				} else {
					this.failMsg( response.data );
				}
			};

			this.ajax( {
				action   : 'gc_wp_filter_mappings',
				property : this.stepArgs.property
			}, success ).fail( function() {
				this.failMsg();
			} );
		},

		failMsg: function( msg ) {
			msg = msg || gc._errors.unknown;
			window.alert( msg );
			thisView.model.set( 'waiting', false );
		},

		successHandler: function( objects ) {
			// var objects = this.get_objects( data );
			this.model.set( this.stepArgs.properties, objects );
			if ( objects.length < 2 ) {
				this.model.set( 'btnDisabled', false );
			}
		},

		setStep: function() {
			if ( ! this.model.get( 'step' ) ) {
				return this.model.set( 'step', 'accounts' );
			}

			if ( 'accounts' === this.model.get( 'step' ) ) {
				return this.model.set( 'step', 'projects' );
			}

			if ( 'projects' === this.model.get( 'step' ) ) {
				return this.model.set( 'step', 'mappings' );
			}
		},

		step_accounts: function() {
			return {
				property   : 'account',
				properties : 'accounts',
			};
		},

		step_projects: function() {
			return {
				property   : 'project',
				properties : 'projects',
			};
		},

		step_mappings: function() {
			return {
				property   : 'mapping',
				properties : 'mappings',
			};
		},

		// get_objects: function( data ) {
		// 	 return _.map( data, function( object ) {
		// 		return {
		// 			id   : object.id,
		// 			name : object.name,
		// 		};
		// 	} );
		// },

		render : function() {
			var json = this.model.toJSON();
			if ( this.stepArgs ) {
				json.label = gc._step_labels[ json.step ];
				json.property = this.stepArgs.property;
			}
			this.$el.html( this.template( json ) );
			return this;
		},

	});
};
