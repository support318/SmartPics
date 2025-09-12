/**
 * Affiliate Moderation (with AI) Scripts.
 *
 * @since 2.26.0
 * @author Aubrey Portwood <aportwood@am.co>
 *
 * Note, any functions or variables with a $ prefixed to it
 * means it is intended or created with jQuery.
 *
 * @see /includes/admin/affiliates/includes/review-affiliate.php
 */

/* eslint-disable padded-blocks */
/* eslint-disable no-console */
/* eslint-disable no-alert */
/* eslint-disable operator-linebreak */

/* globals window, affiliatewp, console, jQuery, document */
jQuery( document ).ready( function() {

	// We want to attach to affiliatewp, which should have been enqueued.
	if ( affiliatewp || false ) {

		// Attach an object to affiliatewp...
		affiliatewp.attach(
			'affiliateReview',
			{

				/**
				 * jQuery Object Cache
				 *
				 * When using this.$() we will cache the object
				 * here.
				 *
				 * @since 2.26.0
				 */
				$cache: {},

				/**
				 * How long before cached AI answers expire in Local Storage?
				 *
				 * We keep responses from the AI Proxy in local storage to
				 * avoid repetitive actions.
				 *
				 * @since 2.26.0 Set to one hour.
				 */
				localStorageAIAnswerCacheExpiry: 30,

				/**
				 * Force an AI Request
				 *
				 * Despite how many credits it may use.
				 *
				 * @since 2.26.0
				 */
				force: false,

				/**
				 * Select an element with jQuery (and cache it).
				 *
				 * If you want a fresh object, just use jQuery( selector ).
				 *
				 * e.g.
				 *
				 *     this.$( '.foo' ).addClass( 'bar' );
				 *
				 * @since 2.26.0
				 *
				 * @param {string} selector Selector for the element.
				 *
				 * @return {Object} The jQuery Object (maybe cached).
				 */
				$( selector ) {

					// Use the cached object if it's already there.
					return this.$cache[ selector ] ?? ( this.$cache[ selector ] = jQuery( selector ) );
				},

				/**
				 * DOM Ready.
				 *
				 * On DOMReady we load this method.
				 *
				 * @since 2.26.0
				 */
				$ready() {

					// Track changes in decision (accept, reject, undecided).
					this.$( 'input[name="decision"]' ).on( 'change', this.$showRejectTextAreaOnReject );
					this.$( 'input[name="decision"]' ).on( 'change', this.$switchButtonTexts );
					this.$( 'input[name="decision"]' ).on( 'change', this.$undecidedAffiliate );

					// Review with AI.
					this.$( 'button[name="ask-ai"]' ).on( 'click', this.$askAI );
				},

				/**
				 * Get the AffiliateID being Reviewed.
				 *
				 * @since 2.26.0
				 *
				 * @return {number} The Affiliate ID.
				 */
				getAffiliateID() {
					return this.$( '#affwp_review_affiliate' ).data( 'affiliate-id' );
				},

				/**
				 * Handle Undecided Affiliates (jQuery).
				 *
				 * @since 2.26.0
				 */
				$undecidedAffiliate() {
					affiliatewp.affiliateReview.undecidedAffiliate( jQuery( this ).val() );
				},

				/**
				 * Keep track of undecided affiliates.
				 *
				 * We need this because we don't want the next affiliate
				 * reviewed to be one that we skipped.
				 *
				 * @since 2.26.0
				 *
				 * @param {string} decision The decision, accept or reject.
				 */
				undecidedAffiliate( decision ) {

					const $skip = this.$( 'input[name="undecided"]', false );

					const affiliateID = this.getAffiliateID();

					if ( 'undecided' === decision ) {

						$skip.val( $skip.val() + `,${ affiliateID }` );
						return;
					}

					$skip.val( $skip.val().replace( `,${ affiliateID }`, '' ) );
				},

				/**
				 * Switch Button Text
				 *
				 * @since 2.26.0
				 */
				$switchButtonTexts() {
					affiliatewp.affiliateReview.syncActionButtonTextsWithDecision( jQuery( this ).val() ?? '' );
				},

				/**
				 * Sync Action Button Texts with decision.
				 *
				 * @since 2.26.0
				 *
				 * @param {string} decisionValue The value of the decision.
				 */
				syncActionButtonTextsWithDecision( decisionValue ) {

					const $exit = this.$( 'input[name="exit"][type="submit"]' );
					const $continue = this.$( 'input[name="continue"][type="submit"]' );

					$exit.attr( 'value', $exit.data( `value-${ decisionValue }` ) ?? '' );
					$continue.attr( 'value', $continue.data( `value-${ decisionValue }` ) ?? '' );
				},

				/**
				 * Ask AI on Ask AI button click.
				 *
				 * @since 2.26.0
				 */
				$askAI() {

					affiliatewp.affiliateReview.setAskAIButtonDisabled( true );

					affiliatewp.affiliateReview.reviewAffiliate(
						jQuery( this ).data( 'affiliate-id' ) ?? 0
					);
				},

				/**
				 * Show or Hide the Ask AI Button.
				 *
				 * @since 2.26.0
				 *
				 * @param {boolean} hide Set to false to show it.
				 */
				hideAskAIButton( hide ) {

					if ( hide ) {

						this.$( 'button[name="ask-ai"]' ).hide();
						return;
					}

					this.$( 'button[name="ask-ai"]' ).show();
				},

				/**
				 * Show Rejected Textarea when Reject is selected.
				 *
				 * @since 2.26.0
				 */
				$showRejectTextAreaOnReject() {

					if ( 'reject' === jQuery( this ).val() ) {

						// Show the reject textarea (and focus it).
						affiliatewp.affiliateReview.$( '#affwp-rejection-reason' ).removeClass( 'hidden' );
						affiliatewp.affiliateReview.$( 'textarea[name="affwp_rejection_reason"]' ).trigger( 'focus' );

						return;
					}

					// Hide the reject textarea.
					affiliatewp.affiliateReview.$( '#affwp-rejection-reason' ).addClass( 'hidden' );
				},

				/**
				 * Review an Affiliate
				 *
				 * @since 2.26.0
				 *
				 * @param {number} affiliateID The Affiliate ID.
				 */
				reviewAffiliate( affiliateID ) {

					if ( ! this.hasValidLicense ) {

						this.displayLicenseErrorModal();

						return;
					}

					// Visually indicates that we are doing an AI request...
					this.$( '.decision-container' ).addClass( 'asking-ai' );
					this.setAllButtonsDisabled( true );
					this.setAskingAI( true );

					this.secureDataForProxy(
						this.hash,
						affiliateID,

						// Then do this with the data...
						function( secureJSON, hash ) {

							// We might have cached this in local storage...
							const cachedAIStatus = this.getCachedAIAnswer( affiliateID, 'status' );
							const cachedAIReason = this.getCachedAIAnswer( affiliateID, 'reason' );
							const cachedAIRejectionMessage = this.getCachedAIAnswer( affiliateID, 'rejection_message' );

							if (

								// We have a valid status.
								(
									cachedAIStatus === 'rejected' ||
									cachedAIStatus === 'accepted'
								)

								// Reason is not empty.
								&& '' !== cachedAIReason
							) {

								// Use what is stored in local storage.
								this.displayAIAnswer( cachedAIReason, cachedAIStatus );

								if ( '' !== cachedAIRejectionMessage ) {
									this.setRejectionMessage( cachedAIRejectionMessage );
								}

								this.setAskAIButtonDisabled( true );

								return;
							}

							this.makeProxyAIRequest(
								secureJSON,
								hash,

								// Then do this with the status + reason...
								function(
									aiStatus,
									aiReason,
									aiRejectionMessage,
									applicationsLeft,

									// eslint-disable-next-line no-unused-vars -- We don't use this, but if we want to debug it it needs to be here.
									fullResponse
								) {

									this.updateApplicationsLeft( applicationsLeft );
									this.displayAIAnswer( aiReason, aiStatus );
									this.cacheAIAnswer( affiliateID, aiStatus, aiReason, aiRejectionMessage );
									this.setAskAIButtonDisabled( true );

									if ( 'rejected' !== aiStatus ) {
										return;
									}

									this.setRejectionMessage( aiRejectionMessage );
								}
							);
						}
					);
				},

				/**
				 * Update the number of applications that are left.
				 *
				 * @param {number} applicationsLeft The number that are left from the AJAX request.
				 */
				updateApplicationsLeft( applicationsLeft ) {
					this.$( 'span.ai-reviews-left span.count' ).html( applicationsLeft.toLocaleString() );
				},

				/**
				 * Log AI Usage Information
				 *
				 * @since 2.26.0
				 *
				 * @param {Object} aiResponse AI Response from AJAX call.
				 */
				logUsage( aiResponse ) {

					jQuery.ajax(
						{
							url: this.ajaxURL,
							method: 'POST',
							dataType: 'json',
							data: {
								action: 'log_affiliate_review_ai_usage',
								_ajax_nonce: this.nonce,
								response: aiResponse,
							},
						}
					);
				},

				/**
				 * Set the Rejection Message from AI.
				 *
				 * @since 2.26.0
				 *
				 * @param {string} message The message for the rejection message.
				 */
				setRejectionMessage( message ) {

					if ( 'undefined' === typeof message || '' === message ) {
						return;
					}

					this.setActionButtonsDisabled( true );

					// Wait 1 second then start to stream in the rejection message.
					window.setTimeout( () => this.aiStreamContent(
						'textarea[name="affwp_rejection_reason"]',
						message,
						function( app ) {
							app.setActionButtonsDisabled( false );
						}
					), 1000 );
				},

				/**
				 * Generate a Local Storage key for caching.
				 *
				 * @since 2.26.0
				 *
				 * @param {number} affiliateID The Affiliate ID.
				 * @param {string} dataKey     Something unique about this affiliate, e.g. aiReason.
				 */
				getAICacheKey( affiliateID, dataKey ) {
					return `AffiliateWPReviewWithAICachedResponse:${ dataKey }:${ affiliateID }`;
				},

				/**
				 * Get a Cached AI Answer from Local Storage.
				 *
				 * @since 2.26.0
				 *
				 * @param {number} affiliateID      The Affiliate ID.
				 * @param {string} dataKey          The data key, e.g. status.
				 * @param {number} expiresInSeconds Only return data if it hasn't been there for this long (in seconds).
				 */
				getCachedAIAnswer( affiliateID, dataKey, expiresInSeconds ) {

					if ( ! this.useCaches ) {
						return; // Caches are disabled via PHP CONSTANT.
					}

					if ( 'undefined' === typeof expiresInSeconds ) {

						// Use the default local storage expiry.
						expiresInSeconds = this.localStorageAIAnswerCacheExpiry;
					}

					const updated = window.localStorage.getItem( this.getAICacheKey( affiliateID, 'updated' ) );

					if ( ( Date.now() - Number( updated ) ) > ( 1000 * Number( expiresInSeconds ) ) ) {
						return; // Don't pass the cache, it expired.
					}

					return window.localStorage.getItem( this.getAICacheKey( affiliateID, dataKey ) ) ?? '';
				},

				/**
				 * Cache AI's Answer/Response/Rejection Message.
				 *
				 * This should help keep too many requests being sent to the AI Proxy.
				 *
				 * Utilizes Local Storage.
				 *
				 * @since 2.26.0
				 *
				 * @param {number} affiliateID        The Affiliate ID.
				 * @param {string} aiStatus           The status.
				 * @param {string} aiReason           The reason.
				 * @param {string} aiRejectionMessage The rejection message.
				 */
				cacheAIAnswer( affiliateID, aiStatus, aiReason, aiRejectionMessage ) {

					window.localStorage.setItem(
						this.getAICacheKey( affiliateID, 'reason' ),
						aiReason
					);

					window.localStorage.setItem(
						this.getAICacheKey( affiliateID, 'status' ),
						aiStatus
					);

					window.localStorage.setItem(
						this.getAICacheKey( affiliateID, 'rejection_message' ),
						aiRejectionMessage
					);

					// Helps let the cache expire.
					window.localStorage.setItem(
						this.getAICacheKey( affiliateID, 'updated' ),
						Date.now()
					);
				},

				/**
				 * Display the AI Response.
				 *
				 * @since 2.26.0
				 *
				 * @param {string} aiReason Reason.
				 * @param {string} aiStatus Status.
				 */
				displayAIAnswer( aiReason, aiStatus ) {

					this.setAllButtonsDisabled( false );
					this.setAIError( false );

					// Must do before setAIAnswered().
					this.aiStreamContent( '.ai-reason', aiReason );

					// Store what the AI decided in the form.
					this.$( 'input[name="ai_reason"]' ).val( aiReason );
					this.$( 'input[name="ai_status"]' ).val( aiStatus );

					this.setAIAnswered( true );

					// Returned value does not matter, just want it executed.
					return 'accepted' === aiStatus
						? this.setAIAffiliateAccepted()
						: this.setAIAffiliateRejected();
				},

				/**
				 * Was there an AI Error?
				 *
				 * @since 2.26.0
				 *
				 * @param {boolean} hasError Was there an error?
				 * @param {string}  error    The error code.
				 */
				setAIError( hasError, error ) {

					this.setAskingAI( false );
					this.setAIAnswered( false );

					return hasError

						? this.$( 'input[name="ask-ai"]' )
							.addClass( 'error' )
							.addClass( error )

						: this.$( 'input[name="ask-ai"]' )
							.removeClass( 'error' )
							.removeClass( error );
				},

				/**
				 * AI Answered (or Not).
				 *
				 * @since 2.26.0
				 *
				 * @param {boolean} answered Did AI Answer?
				 */
				setAIAnswered( answered ) {

					this.setAskingAI( answered ? false : true );

					return answered
						? this.$( '.decision-container' ).addClass( 'ai-answered' )
						: this.$( '.decision-container' ).removeClass( 'ai-answered' );
				},

				/**
				 * Are we asking AI?
				 *
				 * If we are sending a request, we want to visually
				 * trigger any animations, etc.
				 *
				 * @since 2.26.0
				 *
				 * @param {boolean} asking Are we asking AI?
				 */
				setAskingAI( asking ) {

					if ( asking ) {

						this.$( '.decision-container' )
							.addClass( 'asking-ai' )
							.removeClass( 'ai-answered' );

						return;
					}

					this.$( '.decision-container' )
						.removeClass( 'asking-ai ai-answered' );
				},

				/**
				 * The AI has Accepted the Affiliate.
				 *
				 * @since 2.26.0
				 */
				setAIAffiliateAccepted() {

					this.$( 'input[type="radio"][value="accept"]' )
						.prop( 'checked', true ) // Check accept.
						.trigger( 'change' ) // Click accept.
						.next( '.recommendation-label' ).show(); // Show recommendation label.

					this.$( 'input[type="radio"][value="reject"]' )
						.prop( 'checked', false ) // Uncheck reject.
						.removeClass( 'ai-decision' ); // Un-highlight.

					this.$( '.decision-container' ).addClass( 'decision-container-accept' ); // Add class.
				},

				/**
				 * The AI has Rejected the Affiliate.
				 *
				 * @since 2.26.0
				 */
				setAIAffiliateRejected() {

					this.$( 'input[type="radio"][value="reject"]' )
						.prop( 'checked', true ) // Check reject.
						.trigger( 'change' ) // Click reject.
						.next( '.recommendation-label' ).show(); // Show recommendation label.

					this.$( 'input[type="radio"][value="accept"]' )
						.prop( 'checked', false ) // Uncheck accept.
						.removeClass( 'ai-decision' ); // Un-highlight accept.

					this.$( '.decision-container' ).addClass( 'decision-container-reject' ); // Add class.
				},

				/**
				 * Disable/Enable All Buttons.
				 *
				 * @since 2.26.0
				 *
				 * @param {boolean} disabled Whether or not it's disabled.
				 */
				setAllButtonsDisabled( disabled ) {

					this.setActionButtonsDisabled( disabled );
					this.setDecisionRadiosDisabled( disabled );
					this.setAskAIButtonDisabled( disabled );
				},

				/**
				 * Disable/Enable Action Buttons.
				 *
				 * @since 2.26.0
				 *
				 * @param {boolean} disabled Whether or not it's disabled.
				 */
				setActionButtonsDisabled( disabled ) {

					this.$( 'input[name="exit"]' ).prop( 'disabled', disabled );
					this.$( 'input[name="continue"]' ).prop( 'disabled', disabled );
				},

				/**
				 * Disable/Enable Radio Decision Buttons.
				 *
				 * @since 2.26.0
				 *
				 * @param {boolean} disabled Whether or not it's disabled.
				 */
				setDecisionRadiosDisabled( disabled ) {

					this.$( 'input[type="radio"][value="reject"]' ).prop( 'disabled', disabled );
					this.$( 'input[type="radio"][value="accept"]' ).prop( 'disabled', disabled );
					this.$( 'input[type="radio"][value="undecided"]' ).prop( 'disabled', disabled );
				},

				/**
				 * Disable/Enable Ask AI Button.
				 *
				 * @since 2.26.0
				 *
				 * @param {boolean} disabled Whether or not it's disabled.
				 */
				setAskAIButtonDisabled( disabled ) {

					const $askAIButton = this.$( 'input[name="ask-ai"]' );

					$askAIButton.prop( 'disabled', disabled );

					this.hideAskAIButton( disabled );

					if ( disabled ) {
						$askAIButton.parent().addClass( 'disabled' );
					} else {
						$askAIButton.parent().removeClass( 'disabled' );
					}
				},

				/**
				 * Stream in content (like AI does) into an element.
				 *
				 * @since 2.26.0
				 *
				 * @param {string}   selector The selector for the element.
				 * @param {string}   content  The content to "stream" in.
				 * @param {Function} func     Function to call when stream is complete.
				 */
				aiStreamContent( selector, content, func ) {

					const $area = this.$( selector );
					const speed = 20;

					let time = speed;

					const words = content.split( ' ' );

					$area.html( '' ); // Reset the area.

					for ( let i = 0; i < words.length; i++ ) {

						window.setTimeout(

							// Append the word to the $area.
							() => $area.append( words[ i ] + ' ' ),

							// Increase time.
							time = time

								+ Math.floor(

									// By a random number of the speed and double the speed.
									Math.random() * (
										( speed * 8 ) // Max
										- ( speed ) // Min

										// Make sure it happens after last word is placed.
										+ speed
									)
								)
						);
					}

					const interval = window.setInterval( () => {

						if ( $area.val().trim() !== content.trim() ) {
							return;
						}

						if ( 'function' !== typeof func ) {

							window.clearInterval( interval );
							return;
						}

						func( this );

					}, 500 );
				},

				/**
				 * Get secure data to pass on to the AI Proxy.
				 *
				 * This makes a request to PHP to secure all the data with the given
				 * hash so we can send it over the internet encrypted and un-readable.
				 *
				 * @since 2.26.0
				 *
				 * @param {string}   hash        The hash to secure data with.
				 * @param {number}   affiliateID The Affiliate's ID.
				 * @param {Function} then        The function to call when we have the data secured.
				 */
				secureDataForProxy( hash, affiliateID, then ) {

					// Get secure information about the Affiliate for the Proxy.
					jQuery.ajax(
						{
							url: this.ajaxURL,
							method: 'GET',
							dataType: 'json',

							data: {
								hash,
								action: 'secure_affiliate_review_data',
								affiliate_id: affiliateID,
								site: this.siteURL,
								_ajax_nonce: this.nonce,
							},

							// There was an error sending the AJAX request.
							error( data ) {

								affiliatewp.affiliateReview.handleError(
									'ai',
									'ajax_failed',
									affiliatewp.affiliateReview.i18n.ajaxError,
									data
								);
							},

							// PHP told us what the Affiliate's moderation data is...
							success( response ) {

								if ( ! response.success ) {

									affiliatewp.affiliateReview.handleError(
										response.data.reason ?? 'unknown',
										response.data.message ?? 'There was an error securing data for AI.',
										response
									);
								}

								then.bind( affiliatewp.affiliateReview )(
									response.data.secureJSON ?? '',
									hash,
								);
							},
						}
					);
				},

				/**
				 * Handle Errors
				 *
				 * Anytime we have an error in this file,
				 * we churn it into this function for handling.
				 *
				 * @since 2.26.0
				 *
				 * @param {string} feature The feature, e.g. `ai`.
				 * @param {string} error   Error.
				 * @param {string} message The message.
				 * @param {any}    data    The data.
				 */
				handleError( feature, error, message, data ) {

					this.setAIError( 'ai' === feature, error ); // Is this an AI error?
					this.setAllButtonsDisabled( false ); // Let them continue without AI.
					this.setAskingAI( false ); // A request is likely not happening.

					if ( 'invalid_license_key' === error ) {
						return this.displayLicenseErrorModal();
					}

					if (
						'not_enough_credits' === error &&

						// They have not purchased the monthly addon.
						false === data.customer_purchased_credit_types.monthly_addon
					) {

						// They have not purchased the monthly addon, let's purchase that.
						return this.displayMonthlyCreditsPurchaseModal( data.purchase_urls.monthly_addon );
					}

					if (
						'not_enough_credits' === error &&

						// They have purchased the monthly addon.
						true === data.customer_purchased_credit_types.monthly_addon
					) {

						// You're credits for monthly must have expired, let's top you up.
						return this.displayRanOutOfCreditsModal();
					}

					if ( 'spending_too_many_credits' === error ) {

						return this.displayMoreCreditModal(
							data.max_credits ?? 0,
							data.required_credits ?? 0
						);
					}

					window.alert( message ); // Let the user know.
					console.warn( data ); // Log something in console for debugging customer site.
				},

				/**
				 * Invalid License Modal
				 *
				 * @since 2.26.0
				 */
				displayLicenseErrorModal() {

					this.setAllButtonsDisabled( false );

					const modal = this.modal(
						this.i18n.modals.invalidLicense.title,
						this.i18n.modals.invalidLicense.description,
						'',
						{
							settings: {
								text: this.i18n.modals.invalidLicense.buttons.settings.title,
								action: () => {

									modal.close();
									window.open( 'admin.php?page=affiliate-wp-settings', '_blank' );
								},
								disabled: false,
								btnClass: 'btn-confirm',
							},
							purchase: {
								text: this.i18n.modals.invalidLicense.buttons.purchase.title,
								action: () => {

									modal.close();
									window.open( 'https://affiliatewp.com/ai/reviews/invalid-license/', '_blank' );
								},
								disabled: false,
								btnClass: 'btn',
							},
							help: {
								text: this.i18n.modals.invalidLicense.buttons.help.title,
								action: () => {

									modal.close();
									window.open( 'https://affiliatewp.com/ai/reviews/invalid-license/support/', '_blank' );
								},
								disabled: false,
								btnClass: 'btn',
							},
						},
						{
							boxWidth: 585,
						}
					);
				},

				/**
				 * Get more Credits (Monthly).
				 *
				 * This shows a modal that has a link to purchase the
				 * credits in it. When the click the link the modal will
				 * automatically close and, if they purchased the credits,
				 * they can try again w/out having to close the modal.
				 *
				 * @param {string} purchaseURL The URL to purchase credits.
				 *
				 * @since 2.26.0
				 */
				displayMonthlyCreditsPurchaseModal( purchaseURL ) {

					this.setAllButtonsDisabled( false );

					const modal = this.modal(
						this.i18n.modals.purchaseMonthly.title,
						this.i18n.modals.purchaseMonthly.description,
						'',
						{
							purchase: {
								text: this.i18n.modals.purchaseMonthly.buttons.purchase.title,
								action: () => {

									window.open( purchaseURL, '_blank' );
									modal.close();
								},
								disabled: false,
								btnClass: 'btn-confirm',
							},
						}
					);
				},

				/**
				 * Display a modal for users who ran out of credits.
				 *
				 * @since 2.26.0
				 */
				displayRanOutOfCreditsModal() {

					this.setAllButtonsDisabled( false );

					const modal = this.modal(
						this.i18n.modals.purchaseTopup.title,
						this.i18n.modals.purchaseTopup.description,
						'',
						{
							purchase: {
								text: this.i18n.modals.purchaseTopup.buttons.purchase.title,
								action: () => {

									window.open( 'https://affiliatewp.com/ai/reviews/no-credits/', '_blank' );
									modal.close();
								},
								disabled: false,
								btnClass: 'btn-confirm',
							},
						}
					);
				},

				/**
				 * This application requires more credits than usual.
				 *
				 * @since 2.26.0
				 *
				 * @param {number} maxCredits      The usual credits we spend per application.
				 * @param {number} requiredCredits The actual credits this application will.
				 */
				displayMoreCreditModal( maxCredits, requiredCredits ) {

					const modal = this.modal(
						this.i18n.modals.requiredCredits.title,
						this.i18n.modals.requiredCredits.description.replace( '${credits}', requiredCredits ),
						'',
						{
							yes: {
								text: this.i18n.modals.requiredCredits.buttons.yes.title.replace( '${credits}', requiredCredits ),
								action: () => {
									this.force = true;
									this.$( 'button[name="ask-ai"]' ).click();
									modal.close();
								},
								disabled: false,
								btnClass: 'btn-confirm',
							},
							no: {
								text: this.i18n.modals.requiredCredits.buttons.no.title,
								action: () => {
									modal.close(); // Don't do anything.
								},
								disabled: false,
								btnClass: 'btn',
							},
						}
					);
				},

				/**
				 * Launch a Modal
				 *
				 * Uses jQuery Confirm.
				 *
				 * @see https://craftpip.github.io/jquery-confirm/
				 *
				 * @since 2.26.0
				 *
				 * @param {string} title   Title.
				 * @param {string} content Content.
				 * @param {string} icon    Icon classes.
				 * @param {Object} buttons Buttons.
				 * @param {Object} args    Overriding Args.
				 *
				 * @return {Object} Modal Object.
				 */
				modal( title, content, icon, buttons, args ) {

					return jQuery.alert(
						jQuery.extend(
							{
								backgroundDismiss: true,
								title,
								icon,
								type: 'lightgreen',
								content,
								boxWidth: 550,
								useBootstrap: false,
								theme: 'modern,affiliatewp-education',
								closeIcon: true,
								draggable: false,
								dragWindowBorder: false,
								buttons,
							},
							args
						)
					);
				},

				/**
				 * Make a request to the AI Proxy.
				 *
				 * @since 2.26.0
				 *
				 * @param {string}   secureJSON The secured and encrypted JSON.
				 * @param {string}   hash       The hash used to secure the data.
				 * @param {Function} then       The function to call when it's done.
				 */
				makeProxyAIRequest( secureJSON, hash, then ) {

					jQuery.ajax(
						{
							url: this.proxyUrl + '/wp-json/ai/v1/affiliate-review/',
							method: 'POST',
							dataType: 'json',

							data: {
								hash,
								secure_json: secureJSON,
								site: this.siteURL,
								language: this.language,
								force: this.force ? 'yes' : 'no',
							},

							// There was an error sending the AJAX request.
							error( data ) {

								affiliatewp.affiliateReview.handleError(
									'ai',
									'ajax_failed',
									affiliatewp.affiliateReview.i18n.ajaxError,
									data
								);
							},

							// The Proxy API sent back a response...
							success( aiResponse ) {

								affiliatewp.affiliateReview.logUsage( aiResponse );

								if ( aiResponse.hasOwnProperty( 'error' ) ) {

									affiliatewp.affiliateReview.handleError(
										'ai',
										aiResponse.error ?? 'unknown',
										aiResponse.message ?? 'There was an unknown error, please try again.',
										aiResponse
									);

									return;
								}

								if (
									! aiResponse.hasOwnProperty( 'ai_reason' ) ||
									! aiResponse.hasOwnProperty( 'ai_status' )
								) {

									affiliatewp.affiliateReview.handleError(
										'ai',
										aiResponse.error ?? 'unknown',
										aiResponse.message ?? 'There was an unknown error, please try again.',
										aiResponse
									);

									return;
								}

								then.bind( affiliatewp.affiliateReview )(
									aiResponse.ai_status ?? '',
									aiResponse.ai_reason ?? '',
									aiResponse.ai_rejection_message ?? '',
									aiResponse.available_applications ?? 0,
									aiResponse
								);
							},
						}
					);
				},
			},
			window.affiliateWPAffiliateReview || {},
			true
		);

		affiliatewp.affiliateReview.$ready();

	} else {
		console.warn( 'Cannot attach to window.affiliatewp, was it properly enqueued?' );
	}
} );
