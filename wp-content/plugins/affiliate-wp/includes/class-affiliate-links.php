<?php
/**
 * Affiliate Links.
 *
 * This class handles Affiliate Links actions.
 *
 * @package     AffiliateWP
 * @subpackage  AffiliateArea
 * @copyright   Copyright (c) 2024 Awesome Motive, inc
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       2.25.0
 * @author      Nina Claiborne <nclaiborne@awesomeomotive.com>
 */

namespace AffiliateWP;

#[\AllowDynamicProperties]

class Affiliate_Links {

	/**
	 * Construct.
	 *
	 * @since 2.25.0
	 */
	public function __construct() {

		// Add our hooks.
		$this->hooks();
	}

	/**
	 * Add hooks.
	 *
	 * @since 2.25.0
	 */
	private function hooks() : void {

		add_action( 'wp_enqueue_scripts', [ $this, 'register_scripts' ] );
		add_action( 'wp_footer', [ $this, 'affiliate_link_footer_scripts' ] );
		add_action( 'wp_ajax_update_sharing_options', [ $this, 'update_sharing_options' ] );
	}

	/**
	 * Register necessary scripts.
	 *
	 * @return void
	 */
	public function register_scripts() : void {

		// Return if not the affiliate area account page.
		if ( ! affwp_is_affiliate_area() ) {
			return;
		}

		// Bail if user is not logged in, not an affiliate, or not an active affiliate.
		if ( ! is_user_logged_in() || ! affwp_is_affiliate() || ! affwp_is_active_affiliate() ) {
			return;
		}

		// Bail if not on the URLs or Network tab.
		if (
			'urls' !== affwp_get_active_affiliate_area_tab()
			&& 'network' !== affwp_get_active_affiliate_area_tab()
			|| isset( $_REQUEST['tab'] )
			&& 'urls' !== sanitize_key( $_REQUEST['tab'] )
			&& 'network' !== sanitize_key( $_REQUEST['tab'] )
		) {
			return;
		}

		// Enqueue the modal styles.
		affwp_enqueue_style( 'affiliatewp-modal' );

		// Enqueue the copy tool, QR code, and modal scripts & styles.
		affiliate_wp()->scripts->enqueue( 'affiliatewp-qrcode' );
		affiliate_wp()->scripts->enqueue( 'affiliatewp-modal' );

		$json = wp_json_encode(
			[
				'i18n' => [
					'modalTitle'        => __( 'Share QR Code', 'affiliate-wp' ),
					'modalDesc'         => __( 'Use this QR code to promote your link and earn commissions.', 'affiliate-wp' ),
					'qrCodeLinkText'    => __( 'QR Code link', 'affiliate-wp' ),
					'qrCodeDownloadBtn' => __( 'Download QR Code', 'affiliate-wp' ),
					'copied'            => __( 'Copied!', 'affiliate-wp' ),
					'copyFailed'        => __( 'Could not copy affiliate link: ', 'affiliate-wp' ),
				],
			]
		);

		wp_add_inline_script(
			'affiliatewp-qrcode',
			"const affwpAffiliateLinksVars = {$json}",
			'before'
		);
	}

	/**
	 * Adds the affiliate link footer scripts.
	 *
	 * @since 2.25.0
	 * @todo Super WIP. This will be loaded differently & we should be using affiliatewp-utils for the copy tool.
	 *
	 * @return void
	 */
	public function affiliate_link_footer_scripts() : void {

		// Return if not the affiliate area account page.
		if ( ! affwp_is_affiliate_area() ) {
			return;
		}

		// Bail if user is not logged in, not an affiliate, or not an active affiliate.
		if ( ! is_user_logged_in() || ! affwp_is_affiliate() || ! affwp_is_active_affiliate() ) {
			return;
		}

		// Bail if not on the URLs or Network tab.
		if (
			'urls' !== affwp_get_active_affiliate_area_tab()
			&& 'network' !== affwp_get_active_affiliate_area_tab()
			|| isset( $_REQUEST['tab'] )
			&& 'urls' !== sanitize_key( $_REQUEST['tab'] )
			&& 'network' !== sanitize_key( $_REQUEST['tab'] )
		) {
			return;
		}

		?>
		<script>
			/**
			 * Highlight the affiliate link input field when copying.
			 *
			 * @since 2.25.0
			 *
			 * @param {Element} affiliateInput The affiliate link input field.
			 */
			function highlightAndDeselectInput( affiliateInput ) {
				affiliateInput.focus();
				affiliateInput.select();

				// Set timeout to remove selection and highlight
				setTimeout( function() {
					affiliateInput.blur();
					window.getSelection().removeAllRanges();
				}, 1000 );
			}

			/**
			 * Shows the tooltip and copies the text to the clipboard.
			 *
			 * @since 2.25.0
			 *
			 * @param {Element} affiliateInput The affiliate link input field.
			 * @param {string} textToCopy The text to copy to the clipboard.
			 */
			function showTooltipAndCopyText( affiliateInput, textToCopy ) {
				navigator.clipboard.writeText( textToCopy ).then( function() {
					if ( typeof tippy === 'function' ) {
						tippy( affiliateInput, {
							content: affwpAffiliateLinksVars.i18n.copied,
							trigger: 'manual',
							animation: 'fade',
							hideOnClick: false,
							placement: 'top-start',
							onShow: function( instance ) {
								setTimeout( function() {
									instance.hide();
								}, 1000 );
								highlightAndDeselectInput( affiliateInput );
							}
						} ).show();
					}
				} ).catch( function( err ) {
					console.error( affwpAffiliateLinksVars.i18n.copyFailed, err );
				} );
			}

			/**
			 * Show the QR Code modal.
			 *
			 * @since 2.25.0
			 *
			 * @param {string} link The affiliate link from the data URL attribute.
			 */
			function showQRCodeModal( link ) {
				if ( ! link ) {
					return;
				}

				const modalContent = [
					{
						src: `
							<div class="affwp-modal" data-selectable>
								<div class="affwp-modal__header">
									<h2>${ affwpAffiliateLinksVars.i18n.modalTitle }</h2>
									<p>${ affwpAffiliateLinksVars.i18n.modalDesc }</p>
								</div>
								<div class="affwp-modal__body">
									<div class="affwp-modal__qr-code" data-url="${ link }"></div>
								</div>
								<div class="affwp-modal__footer">
									<div class="affwp-modal__actions">
										<div class="affwp-modal__qr-code-url"><strong>${ affwpAffiliateLinksVars.i18n.qrCodeLinkText }</strong>: <div class="affwp-modal__qr-code-url-link">${ link }</div></div>
										<button class="button affwp-modal__button affwp-modal__button--download-qr" data-download="affiliate-link-qr-code.png" data-type="qr_code" data-href="${ link }">${ affwpAffiliateLinksVars.i18n.qrCodeDownloadBtn }</button>
									</div>
								</div>
							</div>
						`,
						type: 'html',
						slug: 'qrcode-modal'
					}
				];

				affiliatewp.modal
					.show( modalContent, {
						dragToClose: false,
						draggable: false,
						autoFocus: false,
					} ).onDone( setupModalContents );
			}

			/**
			 * Setup the modal contents.
			 *
			 * @since 2.25.0
			 */
			function setupModalContents() {
				// Display the QR Code in the modal.
				displayQRCode();

				// Bind the download button for the QR Code.
				const qrCodeModalDownloadButton = document.querySelector( '.affwp-modal__button--download-qr[data-download]' );
				if ( ! qrCodeModalDownloadButton ) {
					return; // Early return if qrCodeModalDownloadButton is not found
				}
				qrCodeModalDownloadButton.addEventListener( 'click', function() {
					handleQRCodeDownload( this );
				} );
			}

			/**
			 * Display the QR Code in the modal.
			 *
			 * @since 2.25.0
			 */
			function displayQRCode() {
				// Check for a visible QR Code to prevent duplicates.
				const qrCodeElement = document.querySelector( '.affwp-modal__qr-code' );

				if (
					! ( qrCodeElement instanceof Element ) ||
					qrCodeElement.classList.contains( 'affwp-qrcode-initialized' )
				) {
					return; // Bail if it is not an Element or already initialized.
				}

				// Generate a new QR Code object.
				affiliatewp.qrcode(
					qrCodeElement,
					qrCodeElement.dataset.url,
					{
						color: {
							dark: '#000000',
							light: '#FFFFFF',
						},
						format: 'png',
					}
				);
			}

			/**
			 * Handle the download button for the QR Code images.
			 *
			 * @since 2.25.0
			 *
			 * @param {Element} clickedEl The clicked element.
			 */
			function handleQRCodeDownload( clickedEl ) {
				const qrCodeElement = document.querySelector( '.affwp-modal__qr-code' );

				// Generate a new QR Code object.
				const qrCodeDownload = affiliatewp.qrcode();

				// Generate a new PNG file in background and downloaded it.
				qrCodeDownload.createPNG(
					qrCodeElement.dataset.url,
					function( downloadUrl ) {
						handleImageDownloadFromButton( clickedEl.dataset.download, downloadUrl );
					}
				);
			}

			/**
			 * Handle the image download from the button.
			 *
			 * @since 2.25.0
			 *
			 * @param {string} filename The filename for the download.
			 * @param {string} downloadUrl The URL to download the image from.
			 */
			function handleImageDownloadFromButton( filename, downloadUrl ) {
				const downloadLink = document.createElement( 'a' );

				downloadLink.download = filename;
				downloadLink.href = downloadUrl;

				// External images cannot be downloaded, so we make sure they at least open in a new tab.
				downloadLink.target = '_blank';

				downloadLink.click();
				downloadLink.remove();
			}

			/**
			 * Attaches click event handlers to QR code icons.
			 *
			 * @since 2.25.0
			 */
			function attachQRCodeClickHandlers() {
				// Get all the QR Code icons.
				const qrCodeIcons = document.querySelectorAll( '.affwp-link-sharing__qrcode' );

				if ( qrCodeIcons.length === 0 ) {
					return;
				}

				qrCodeIcons.forEach( function( icon ) {
					icon.addEventListener( 'click', function() {
						showQRCodeModal( this.dataset.url );
					} );
				} );
			}

			document.addEventListener( 'DOMContentLoaded', function() {
				const toggleLink = document.querySelector( '.affwp-affiliate-link__toggle' );
				const copyButtons = document.querySelectorAll( '.affwp-affiliate-link-copy-link' );
				const sharingOptionsContainer = document.querySelector( '.affwp-affiliate-link .affwp-card__content' );

				let isUsingCustomSlug = false;

				if ( toggleLink ) {
					toggleLink.addEventListener( 'click', function( e ) {
						e.preventDefault();

						const nonce = this.getAttribute( 'data-nonce' );
						const affiliateInput = this.closest( '.affwp-card__content' ).querySelector( '.affwp-affiliate-link__input' );
						const affiliateCopyButton = this.closest( '.affwp-card__content' ).querySelector( '.affwp-affiliate-link-copy-link' );

						// Get the URL to use based on the current state.
						const newUrl = isUsingCustomSlug ? affiliateInput.getAttribute( 'data-standard-url' ) : affiliateInput.getAttribute( 'data-slug-url' );

						// Update the data attribute to keep the copy ability in sync.
						affiliateInput.dataset.content = newUrl;
						affiliateCopyButton.dataset.content = newUrl;

						// Update the input value.
						affiliateInput.value = newUrl;

						// Toggle the custom slug state.
						isUsingCustomSlug = ! isUsingCustomSlug;

						// Update the toggle link text.
						toggleLink.textContent = isUsingCustomSlug ? '<?php esc_html_e( 'Use standard link', 'affiliate-wp' ); ?>' : '<?php esc_html_e( 'Use link with custom slug', 'affiliate-wp' ); ?>';

						jQuery.ajax( {
							url: affwp_scripts.ajaxurl,
							type: 'POST',
							data: {
								action: 'update_sharing_options',
								url: newUrl,
								nonce: nonce
							},
							success: function( response ) {
								const cardContent = toggleLink.closest( '.affwp-card__content' );
								const existingSharingDiv = cardContent.querySelector( '.affwp-link-sharing' );
								if ( existingSharingDiv ) {
									existingSharingDiv.outerHTML = response.data.html;
								} else {
									cardContent.innerHTML = response.data.html;
								}
								// Re-attach the QR Code click handlers.
								attachQRCodeClickHandlers();
							},
							error: function( xhr, status, error ) {
								console.error( error );
							}
						} );
					} );
				}

				// Attach the QR Code click handlers.
				attachQRCodeClickHandlers();

				copyButtons.forEach( function( button ) {
					button.addEventListener( 'click', function() {
						const affiliateInput = this.previousElementSibling;
						showTooltipAndCopyText( affiliateInput, affiliateInput.value );
					} );
				} );

				document.querySelectorAll( '.affwp-affiliate-link__input' ).forEach( function( input ) {
					input.addEventListener( 'click', function() {
						showTooltipAndCopyText( this, this.value );
					} );
				} );

				tippy( '.affwp-card__tooltip[data-tippy-content]', {
					allowHTML: true,
					interactive: true,
					theme: 'affwp',
				} );
			} );
		</script>
		<?php

	}

	/**
	 * Render Affiliate Link Sharing options.
	 *
	 * @since 2.25.0
	 *
	 * @param string $affiliate_url The affiliate URL.
	 */
	public function render_link_sharing_options( $affiliate_url ) {

		// Bail if the affiliate URL is empty or not a string.
		if ( empty( $affiliate_url ) || ! is_string( $affiliate_url ) ) {
			return;
		}

		// Get enabled sharing options.
		$sharing_options = affiliate_wp()->settings->get( 'link_sharing_options' );

		// Bail if no sharing options are enabled.
		if ( empty( $sharing_options ) ) {
			return;
		}

		$x_text = affiliate_wp()->settings->get( 'link_sharing_x_text' );
		$x_text = ! empty( $x_text ) ? $x_text : html_entity_decode( get_bloginfo( 'name' ), ENT_QUOTES, 'UTF-8' );

		$email_subject = affiliate_wp()->settings->get( 'link_sharing_email_subject' );
		$email_subject = ! empty( $email_subject ) ? $email_subject : html_entity_decode( get_bloginfo( 'name' ), ENT_QUOTES, 'UTF-8' );

		$email_body = affiliate_wp()->settings->get( 'link_sharing_email_body' );
		$email_body = ! empty( $email_body ) ? $email_body : 'I thought you might be interested in this:';
		$email_body = sprintf( '%s %s', $email_body, $affiliate_url );

		$svg_icons = [
			'x'        => '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
			<path d="M13.9761 10.1624L22.7186 0H20.6469L13.0558 8.82384L6.99289 0H0L9.16837 13.3432L0 24H2.07179L10.0881 14.6817L16.491 24H23.4839L13.9756 10.1624H13.9761ZM11.1385 13.4608L10.2096 12.1321L2.81829 1.55962H6.00044L11.9653 10.0919L12.8942 11.4206L20.6479 22.5113H17.4657L11.1385 13.4613V13.4608Z" fill="black" style="fill:black;fill-opacity:1;"/>
			</svg>',
			'facebook' => '<svg viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
			<path d="M32 16C32 7.16346 24.8365 0 16 0C7.16346 0 0 7.16346 0 16C0 23.5037 5.16576 29.7998 12.1348 31.5288V20.8893H8.83545V16H12.1348V13.8932C12.1348 8.4473 14.5993 5.92314 19.9459 5.92314C20.9595 5.92314 22.7085 6.12186 23.4241 6.32064V10.7529C23.0464 10.7132 22.3905 10.6932 21.5756 10.6932C18.9521 10.6932 17.9384 11.687 17.9384 14.2708V16H23.1645L22.2668 20.8893H17.9384V31.8828C25.8607 30.926 32 24.1804 32 16Z" fill="#0866FF" style="fill:#0866FF;fill:color(display-p3 0.0314 0.4000 1.0000);fill-opacity:1;"/>
			<path d="M22.2667 20.8894L23.1645 16H17.9383V14.2708C17.9383 11.687 18.952 10.6933 21.5755 10.6933C22.3904 10.6933 23.0463 10.7131 23.424 10.7529V6.32066C22.7085 6.12188 20.9594 5.9231 19.9458 5.9231C14.5993 5.9231 12.1347 8.44732 12.1347 13.8932V16H8.83537V20.8894H12.1347V31.5289C13.3726 31.8359 14.667 32 15.9999 32C16.6562 32 17.3028 31.9596 17.9383 31.8828V20.8894H22.2667Z" fill="white" style="fill:white;fill-opacity:1;"/>
			</svg>',
			'linkedin' => '<svg viewBox="0 0 33 32" fill="none" xmlns="http://www.w3.org/2000/svg">
			<path d="M29.7143 0H2.4381C1.21905 0 0.152381 1.06667 0 2.28571V29.7143C0 30.9333 1.06667 32 2.4381 32H29.7143C30.9333 32 32 30.9333 32.1524 29.7143V2.28571C32.1524 1.06667 31.0857 0 29.7143 0ZM9.6 27.2762H4.87619V12.0381H9.6V27.2762ZM7.16191 9.90476C5.6381 9.90476 4.41905 8.68571 4.41905 7.1619C4.41905 5.63809 5.6381 4.41905 7.16191 4.41905C8.68572 4.41905 9.90477 5.63809 9.90477 7.1619C9.90477 8.68571 8.68572 9.90476 7.16191 9.90476ZM27.4286 27.2762H22.7048V19.8095C22.7048 17.981 22.7048 15.6952 20.2667 15.6952C17.8286 15.6952 17.3714 17.6762 17.3714 19.6571V27.2762H12.6476V12.0381H17.219V14.1714C18.1333 12.6476 19.8095 11.581 21.6381 11.7333C26.5143 11.7333 27.2762 14.9333 27.2762 19.0476L27.4286 27.2762Z" fill="#0077B5" style="fill:#0077B5;fill:color(display-p3 0.0000 0.4667 0.7098);fill-opacity:1;"/>
			<path d="M4.87619 27.2762H9.6V12.0381H4.87619V27.2762Z" fill="white" style="fill:white;fill-opacity:1;"/>
			<path d="M4.41905 7.1619C4.41905 8.68571 5.6381 9.90476 7.16191 9.90476C8.68572 9.90476 9.90477 8.68571 9.90477 7.1619C9.90477 5.63809 8.68572 4.41905 7.16191 4.41905C5.6381 4.41905 4.41905 5.63809 4.41905 7.1619Z" fill="white" style="fill:white;fill-opacity:1;"/>
			<path d="M22.7048 27.2762H27.4286L27.2762 19.0476C27.2762 14.9333 26.5143 11.7333 21.6381 11.7333C19.8095 11.581 18.1333 12.6476 17.219 14.1714V12.0381H12.6476V27.2762H17.3714V19.6571C17.3714 17.6762 17.8286 15.6952 20.2667 15.6952C22.7048 15.6952 22.7048 17.981 22.7048 19.8095V27.2762Z" fill="white" style="fill:white;fill-opacity:1;"/>
			</svg>',
			'email'    => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M22.5457 4.33041L12 15.1158L1.45434 4.33041" stroke="currentColor" style="stroke:currentColor;stroke-opacity:1;" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/><path d="M1.45434 19.6696L9.22462 12.4793" stroke="currentColor" style="stroke:currentColor;stroke-opacity:1;" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/><path d="M14.7755 12.4793L22.5457 19.6696" stroke="currentColor" style="stroke:currentColor;stroke-opacity:1;" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/><path d="M22.5457 4.33041H1.45434V19.6696H22.5457V4.33041Z" stroke="currentColor" style="stroke:currentColor;stroke-opacity:1;" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/></svg>
			',
			'qrcode'   => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><g><path d="M1 8h6a1 1 0 0 0 1 -1V1a1 1 0 0 0 -1 -1H1a1 1 0 0 0 -1 1v6a1 1 0 0 0 1 1Zm1 -5.75A0.25 0.25 0 0 1 2.25 2h3.5a0.25 0.25 0 0 1 0.25 0.25v3.5a0.25 0.25 0 0 1 -0.25 0.25h-3.5A0.25 0.25 0 0 1 2 5.75Z" fill="currentColor" stroke-width="1"></path><path d="M7 16H1a1 1 0 0 0 -1 1v6a1 1 0 0 0 1 1h6a1 1 0 0 0 1 -1v-6a1 1 0 0 0 -1 -1Zm-1 5.75a0.25 0.25 0 0 1 -0.25 0.25h-3.5a0.25 0.25 0 0 1 -0.25 -0.25v-3.5a0.25 0.25 0 0 1 0.25 -0.25h3.5a0.25 0.25 0 0 1 0.25 0.25Z" fill="currentColor" stroke-width="1"></path><path d="M23 0h-6a1 1 0 0 0 -1 1v6a1 1 0 0 0 1 1h6a1 1 0 0 0 1 -1V1a1 1 0 0 0 -1 -1Zm-1 5.75a0.25 0.25 0 0 1 -0.25 0.25h-3.5a0.25 0.25 0 0 1 -0.25 -0.25v-3.5a0.25 0.25 0 0 1 0.25 -0.25h3.5a0.25 0.25 0 0 1 0.25 0.25Z" fill="currentColor" stroke-width="1"></path><path d="M10 4.25h1a0.75 0.75 0 0 0 0 -1.5 0.25 0.25 0 0 1 -0.25 -0.25V1a0.75 0.75 0 0 0 -1.5 0v2.5a0.76 0.76 0 0 0 0.75 0.75Z" fill="currentColor" stroke-width="1"></path><path d="M13 1.75a0.25 0.25 0 0 1 0.25 0.25v5.5a0.75 0.75 0 0 0 1.5 0V1A0.76 0.76 0 0 0 14 0.25h-1a0.76 0.76 0 0 0 -0.75 0.75 0.76 0.76 0 0 0 0.75 0.75Z" fill="currentColor" stroke-width="1"></path><path d="M5.75 10.5a0.76 0.76 0 0 0 0.75 0.75H10a0.76 0.76 0 0 0 0.75 -0.75v-4a0.75 0.75 0 0 0 -1.5 0v3a0.25 0.25 0 0 1 -0.25 0.25H6.5a0.76 0.76 0 0 0 -0.75 0.75Z" fill="currentColor" stroke-width="1"></path><path d="M3 9.75a0.76 0.76 0 0 0 -0.75 0.75V13a0.25 0.25 0 0 1 -0.25 0.25H1a0.75 0.75 0 0 0 0 1.5h13a0.76 0.76 0 0 0 0.75 -0.75v-3a0.75 0.75 0 0 0 -1.5 0v2a0.25 0.25 0 0 1 -0.25 0.25H4a0.25 0.25 0 0 1 -0.25 -0.25v-2.5A0.76 0.76 0 0 0 3 9.75Z" fill="currentColor" stroke-width="1"></path><path d="M13.75 17a0.76 0.76 0 0 0 -0.75 -0.75h-3a0.76 0.76 0 0 0 -0.75 0.75v4a0.75 0.75 0 0 0 1.5 0v-3a0.25 0.25 0 0 1 0.25 -0.25h2a0.76 0.76 0 0 0 0.75 -0.75Z" fill="currentColor" stroke-width="1"></path><path d="M23 22.25h-8.5a0.25 0.25 0 0 1 -0.25 -0.25v-2a0.75 0.75 0 0 0 -1.5 0v3a0.76 0.76 0 0 0 0.75 0.75H23a0.75 0.75 0 0 0 0 -1.5Z" fill="currentColor" stroke-width="1"></path><path d="M16.5 20.25h3a0.76 0.76 0 0 0 0.75 -0.75v-3a0.76 0.76 0 0 0 -0.75 -0.75h-3a0.76 0.76 0 0 0 -0.75 0.75v3a0.76 0.76 0 0 0 0.75 0.75Zm0.75 -2.75a0.25 0.25 0 0 1 0.25 -0.25h1a0.25 0.25 0 0 1 0.25 0.25v1a0.25 0.25 0 0 1 -0.25 0.25h-1a0.25 0.25 0 0 1 -0.25 -0.25Z" fill="currentColor" stroke-width="1"></path><path d="M22.5 12.75a0.76 0.76 0 0 0 -0.75 0.75V20a0.75 0.75 0 0 0 1.5 0v-6.5a0.76 0.76 0 0 0 -0.75 -0.75Z" fill="currentColor" stroke-width="1"></path><path d="M23.25 10a0.76 0.76 0 0 0 -0.75 -0.75H17a0.76 0.76 0 0 0 -0.75 0.75v3a0.75 0.75 0 0 0 1.5 0v-2a0.25 0.25 0 0 1 0.25 -0.25h4.5a0.76 0.76 0 0 0 0.75 -0.75Z" fill="currentColor" stroke-width="1"></path></g></svg>',
		];

		$sharing_links = [
			'x'        => [
				'url'   => sprintf( 'https://x.com/intent/tweet?text=%s&url=%s', rawurlencode( $x_text ), rawurlencode( $affiliate_url ) ),
				'title' => 'Share link on X (Formerly Twitter)',
				'class' => 'affwp-link-sharing__x'
			],
			'facebook' => [
				'url'   => sprintf( 'https://www.facebook.com/sharer/sharer.php?u=%s', rawurlencode( $affiliate_url ) ),
				'title' => 'Share link on Facebook',
				'class' => 'affwp-link-sharing__facebook'
			],
			'linkedin' => [
				'url'   => sprintf( 'https://www.linkedin.com/sharing/share-offsite/?url=%s', rawurlencode( $affiliate_url ) ),
				'title' => 'Share link on LinkedIn',
				'class' => 'affwp-link-sharing__linkedin'
			],
			'email'    => [
				'url'   => sprintf( 'mailto:?subject=%s&body=%s', rawurlencode( $email_subject ), rawurlencode( $email_body ) ),
				'title' => 'Share link by Email',
				'class' => 'affwp-link-sharing__email'
			],
			'qrcode'   => [
				'url'   => $affiliate_url,
				'title' => 'Share link by QR Code',
				'class' => 'affwp-link-sharing__qrcode'
			],
		];

		ob_start();

		?>

		<div class="affwp-link-sharing">
			<div class="affwp-link-sharing__text"><?php esc_html_e( 'Share:', 'affiliate-wp' ); ?></div>
			<div class="affwp-link-sharing__options">
				<?php foreach ( $sharing_options as $option => $enabled ) : ?>
					<?php if ( isset( $sharing_links[ $option ] ) ) : ?>
						<?php if ( 'qrcode' === $option ) : ?>
							<span
								title="<?php echo esc_attr( $sharing_links[ $option ]['title'] ); ?>"
								class="<?php echo esc_attr( sprintf( '%s affwp-link-sharing__icon', $sharing_links[ $option ]['class'] ) ); ?>"
								data-url="<?php echo esc_attr( $sharing_links[ $option ]['url'] );?>"
							>
								<?php
									// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Content already escaped.
									echo $svg_icons[ $option ];
								?>
							</span>
						<?php else : ?>
							<a href="<?php echo esc_url( $sharing_links[ $option ]['url'] ); ?>" title="<?php echo esc_attr( $sharing_links[ $option ]['title'] ); ?>" target="_blank" class="<?php echo esc_attr( sprintf( '%s affwp-link-sharing__icon', $sharing_links[ $option ]['class'] ) ); ?>">
								<?php
									// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Content already escaped.
									echo $svg_icons[ $option ];
								?>
							</a>
						<?php endif; ?>
					<?php endif; ?>
				<?php endforeach; ?>
			</div>
		</div>

		<?php

		echo ob_get_clean();
	}

	/**
	 * Render the Affiliate Link section.
	 *
	 * @since 2.25.0
	 *
	 * @param int $affiliate_id Affiliate ID to fetch data.
	 * @return void
	 */
	public function render_affiliate_link( int $affiliate_id ): void {

		ob_start();

		$affiliate_url    = affwp_get_affiliate_referral_url( $affiliate_id );
		$show_custom_slug = affiliate_wp()->settings->get( 'custom_affiliate_slugs_affiliate_show_slug' );
		$has_custom_slug  = function_exists( 'affiliatewp_custom_affiliate_slugs' ) ? affiliatewp_custom_affiliate_slugs()->base->get_slug( $affiliate_id ) ?? false : false;
		$text_string      = '';

		switch ( affwp_get_referral_format() ) {
			case 'id':
				$text_string = sprintf(
					/* translators: %s: Affiliate ID */
					esc_html__( 'Affiliate ID (#%s)', 'affiliate-wp' ),
					sprintf( '<strong>%s</strong>', esc_html( $affiliate_id ) )
				);
				break;

			case 'username':
				/* translators: %s: Affiliate Username */
				$text_string = sprintf( esc_html__( 'username %s', 'affiliate-wp' ), sprintf( '<strong>%s</strong>', esc_html( affwp_get_affiliate_username() ) ) );
				break;

			case 'slug':
				if ( ! empty( $has_custom_slug ) ) {
					/* translators: %s: Custom Affiliate Slug */
					$text_string = sprintf( esc_html__( 'custom slug %s', 'affiliate-wp' ), sprintf( '<strong>%s</strong>', esc_html( $has_custom_slug ) ) );
				}
				break;

			default:
				$text_string = sprintf(
					/* translators: %s: Affiliate ID */
					esc_html__( 'Affiliate ID (#%s)', 'affiliate-wp' ),
					sprintf( '<strong>%s</strong>', esc_html( $affiliate_id ) )
				);
				break;
		}
		?>

		<div class="affwp-card affwp-affiliate-link">
			<div class="affwp-card__header affwp-affiliate-link__header">

				<div>
					<h3><?php esc_html_e( 'Your Affiliate Link', 'affiliate-wp' ); ?></h3>
					<p><?php esc_html_e( 'Share this link to earn commissions.', 'affiliate-wp' ); ?></p>
				</div>

				<?php
				$content = sprintf(
					'<p>%s</p>',
					sprintf(
						wp_kses(
							/* translators: %s: Affiliate ID or username*/
							__( 'Your %s identifies your account and tracks your referrals.', 'affiliate-wp' ),
							[ 'strong' => [] ]
						),
						sprintf(
							'<span class="affwp-affiliate-link__info">%s</span>',
							$text_string
						)
					)
				);

				if ( 'slug' !== affwp_get_referral_format() && $has_custom_slug && $show_custom_slug ) {
					$content .= sprintf(
						'<p>%s</p>',
						sprintf(
							/* translators: %1$s: Custom Affiliate Slug, %2$s: Use link with custom slug */
							esc_html__( 'Your custom affiliate slug is %1$s and can also be used within your affiliate link. Click %2$s.', 'affiliate-wp' ),
							sprintf( '<strong>%s</strong>', esc_html( $has_custom_slug ) ),
							sprintf( '<strong>%s</strong>', esc_html__( 'Use link with custom slug', 'affiliate-wp' ) )
						)
					);
				}

				$content .= sprintf(
					'<p>%s</p>',
					esc_html__( 'Use your affiliate link to promote and earn commissions for every referral. Share it anywhere: on social media, in emails, on your website, or any other way.', 'affiliate-wp' )
				);
				?>

				<span class="affwp-card__tooltip" data-tippy-content="<?php echo esc_attr( $content ); ?>">
					<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
						<path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" d="M9 9.00004c0.00011 -0.54997 0.15139 -1.08933 0.43732 -1.55913s0.69548 -0.85196 1.18398 -1.10472c0.4884 -0.25275 1.037 -0.36637 1.5856 -0.32843 0.5487 0.03793 1.0764 0.22596 1.5254 0.54353 0.449 0.31757 0.8021 0.75246 1.0206 1.25714 0.2186 0.50468 0.2942 1.05973 0.2186 1.60448 -0.0756 0.54475 -0.2994 1.05829 -0.6471 1.48439 -0.3477 0.4261 -0.8059 0.7484 -1.3244 0.9317 -0.2926 0.1035 -0.5459 0.2951 -0.725 0.5485 -0.1791 0.2535 -0.2752 0.5562 -0.275 0.8665v1.006" stroke-width="1.5"></path><path stroke="currentColor" d="M12 18c-0.2071 0 -0.375 -0.1679 -0.375 -0.375s0.1679 -0.375 0.375 -0.375" stroke-width="1.5"></path><path stroke="currentColor" d="M12 18c0.2071 0 0.375 -0.1679 0.375 -0.375s-0.1679 -0.375 -0.375 -0.375" stroke-width="1.5"></path><path stroke="currentColor" stroke-miterlimit="10" d="M12 23.25c6.2132 0 11.25 -5.0368 11.25 -11.25S18.2132 0.75 12 0.75 0.75 5.7868 0.75 12 5.7868 23.25 12 23.25Z" stroke-width="1.5"></path>
					</svg>
				</span>

			</div>
			<div class="affwp-card__content">
				<div class="affwp-affiliate-link__display">
					<input
						type="text"
						readonly
						class="affwp-affiliate-link__input"
						value="<?php echo esc_url( urldecode( $affiliate_url ) ); ?>"
						data-standard-url="<?php echo esc_url( urldecode( $affiliate_url ) ); ?>"
						data-slug-url="<?php echo esc_url( urldecode( affwp_get_affiliate_referral_url( [ 'format' => 'slug' ] ) ) ); ?>"
					>
					<button
						class="affwp-affiliate-link-copy-link button"
						data-content="<?php echo esc_url( urldecode( $affiliate_url ) ); ?>"
					><?php esc_html_e( 'Copy Link', 'affiliate-wp' ); ?></button>
				</div>
				<?php if ( 'slug' !== affwp_get_referral_format() && $has_custom_slug && $show_custom_slug ): ?>
					<a href="#" class="affwp-affiliate-link__toggle" data-nonce="<?php echo esc_attr( wp_create_nonce( 'update-sharing-options_nonce' ) ); ?>">
						<?php esc_html_e( 'Use link with custom slug', 'affiliate-wp' ); ?>
					</a>
				<?php endif; ?>

				<?php

				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Content already escaped.
				echo $this->render_link_sharing_options( $affiliate_url );

				?>
			</div>
		</div>

		<?php

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Content already escaped.
		echo ob_get_clean();
	}

	/**
	 * Handles AJAX request to update sharing options.
	 *
	 * @since 2.25.0
	 *
	 * @todo Super WIP. This will be loaded elsewhere.
	 *
	 * @return void
	 */
	public function update_sharing_options() : void {

		// Check if nonce is set and verify it.
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'update-sharing-options_nonce' ) ) {
			wp_send_json_error( [ 'message' => esc_html__( 'Nonce verification failed', 'affiliate-wp' ) ] );
			return;
		}

		// Check if URL is set in the POST request.
		if ( isset( $_POST['url'] ) ) {
			// Sanitize the URL to ensure it's a valid URL and prevent XSS attacks.
			$url = esc_url_raw( $_POST['url'] );

			// Start output buffering to capture the output of the rendering function.
			ob_start();

			$this->render_link_sharing_options( $url );

			$sharing_options_html = ob_get_clean();

			// Send a JSON success response with the rendered HTML.
			wp_send_json_success( [ 'html' => $sharing_options_html ] );
		}

		// Send a JSON error response if the URL is not provided.
		wp_send_json_error( [ 'message' => esc_html__( 'URL not provided', 'affiliate-wp' ) ] );
	}
}
