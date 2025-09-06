/* global wpforms_builder */

/**
 * WPForms Affiliate Registration builder form functions.
 *
 * @since 2.11.0
 */

 'use strict';

 var WPFormsAffiliateRegistration = window.WPFormsAffiliateRegistration || ( function( $ ) {

	 /**
	  * Builder element.
	  *
	  * @since 2.11.0
	  */
	 var $builder;

	 /**
	  * Public functions and properties.
	  *
	  * @since 2.11.0
	  *
	  * @type {object}
	  */
	 var app = {

		 /**
		  * Start the engine.
		  *
		  * @since 2.11.0
		  */
		 init: function() {

			 $( app.ready );
		 },

		 /**
		  * Document ready.
		  *
		  * @since 2.11.0
		  */
		 ready: function() {

			 $builder = $( '#wpforms-builder' );

			 app.bindUIActions();

			 // Affiliate Referrals Toggle.
			 app.referralsToggle();

			 // Affiliate Registration Toggle.
			 app.affiliateRegistrationToggle();

		 },

		 /**
		  * Element bindings.
		  *
		  * @since 2.11.0
		  */
		 bindUIActions: function() {
			$builder
			 .on( 'change', '#wpforms-panel-field-settings-affwp_allow_referrals', app.referralsToggle )
			 .on( 'change', '#wpforms-panel-field-settings-affwp_affiliate_registration_enable', app.affiliateRegistrationToggle )
			 .on( 'change', '#wpforms-panel-field-settings-registration_enable', app.userRegistrationToggle );
		 },

		 /**
		  * Toggle the displaying referral settings depending on if referrals are enabled.
		  *
		  * @since 2.11.0
		  */
		  referralsToggle: function() {
			const $enable   = $( '#wpforms-panel-field-settings-affwp_allow_referrals' ),
				$settings = $( '#wpforms-affiliatewp-referrals-content-block' );

			if ( ! $enable.length ) {
				return;
			}

			if ( $enable.is( ':checked' ) ) {
				$settings.show();
			} else {
				$settings.hide();
			}
		},

		 /**
		  * Toggle the displaying settings depending on if user enabled registration.
		  *
		  * @since 2.11.0
		  */
		 userRegistrationToggle: function() {

			const $enable = $( '#wpforms-panel-field-settings-registration_enable' );

			if ( ! $enable.length ) {
				return;
			}

			app.checkForConflict();
		},

		/**
		  * Toggle the displaying settings depending on if user enabled registration.
		  *
		  * @since 2.11.0
		  */
		affiliateRegistrationToggle: function() {

			const $enable = $( '#wpforms-panel-field-settings-affwp_affiliate_registration_enable' ),
				$settings = $( '#wpforms-affiliatewp-content-block' );

			if ( ! $enable.length ) {
				return;
			}

			app.checkForConflict();

			if ( $enable.is( ':checked' ) ) {
				$settings.show();
			} else {
				$settings.hide();
			}
		},

		checkForConflict: function() {

			const affiliateRegistrationToggle = $('#wpforms-panel-field-settings-affwp_affiliate_registration_enable' ),
				userRegistrationEnabled       = $('#wpforms-panel-field-settings-registration_enable' ).is(':checked'),
				affiliateRegistrationEnabled  = affiliateRegistrationToggle.is(':checked');

			// No conflict.
			if( ! ( affiliateRegistrationEnabled && userRegistrationEnabled ) ) {
				return;
			}

			app.conflictAlert();

		 },

		 conflictAlert: function() {
			$.alert( {
				title: wpforms_builder.heads_up,
				content: wpforms_builder.user_registration_conflict,
				icon: 'fa fa-exclamation-circle',
				type: 'orange',
				buttons: {
					confirm: {
						text: wpforms_builder.ok,
						btnClass: 'btn-confirm',
						keys: [ 'enter' ],
					},
				},
			} );
		 },

	 };

	 return app;

 }( jQuery ) );

 // Initialize.
 WPFormsAffiliateRegistration.init();
