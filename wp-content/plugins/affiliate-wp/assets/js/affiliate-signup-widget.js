document.addEventListener("DOMContentLoaded", function() {

	const widgetContainers = document.querySelectorAll('.affiliate-signup');

	widgetContainers.forEach(function(widgetContainer) {
		widgetContainer.shadowReadyDispatched = false;
		setupWidget(widgetContainer);
	});

	function setupWidget(widgetContainer) {
		if (!widgetContainer.shadowRoot) {
			let shadow = widgetContainer.attachShadow({ mode: 'open' });
			loadInitialWidget(shadow, widgetContainer);
		}
	}

	function loadInitialWidget(shadow, widgetContainer) {

		fetch(affiliateWidgetParams.cssUrl)
			.then(res => res.text())
			.then((css) => {
				const style = document.createElement('style');
				style.textContent = css;
				shadow.appendChild(style);

				const container = document.createElement('div');
				container.innerHTML = affiliateWidgetParams.widgetHtml;
				shadow.appendChild(container);
				widgetAttachFormSubmitEvent(shadow);

			})
			.then(() => {
				if (!widgetContainer.shadowReadyDispatched) {
					window.dispatchEvent(new Event('shadowReady'));
					widgetContainer.shadowReadyDispatched = true;
				}
			});
	}

	function widgetAttachFormSubmitEvent(shadow) {
		const formInsideShadow = shadow.querySelector('form');
		if (formInsideShadow) {
			formInsideShadow.addEventListener('submit', function (e) {
				e.preventDefault();
				const ctaButtonInsideShadow = shadow.querySelector('#signup-affiliate');
				if (ctaButtonInsideShadow) {
					buttonLoadingState(ctaButtonInsideShadow);
					registerAffiliate(shadow);
				}
			});
		}
	}

	/**
	 * Button Loading State.
	 */
	function buttonLoadingState(ctaButtonInsideShadow) {
		const svgSpinner = ctaButtonInsideShadow.querySelector('svg');
		const originalButtonText = ctaButtonInsideShadow.querySelector('#originalButtonText');
		const processingText = ctaButtonInsideShadow.querySelector('#processingText');

		if (svgSpinner) {
			svgSpinner.removeAttribute('hidden');
		}

		if (processingText) {
			processingText.removeAttribute('hidden');
		}

		if (originalButtonText) {
			originalButtonText.setAttribute('hidden', true);
		}

		ctaButtonInsideShadow.disabled = true;
		ctaButtonInsideShadow.classList.add('cursor-not-allowed');
	}

	/**
	 * Register new affiliate via AJAX.
	 */
	function registerAffiliate(shadow) {
		const formInsideShadow = shadow.querySelector('form');
		const formData = new FormData(formInsideShadow);

		// Get the order key from the URL
		const orderKey = getOrderKeyFromURL();

		// Append the order key and current URL to the form data
		formData.append('action', 'affiliate_signup');
		formData.append('order_key', orderKey);

		fetch(affiliateWidgetParams.ajaxUrl, {
			method: 'POST',
			body: formData,
		})
		.then(response => {
			if (!response.ok) {
				showError(shadow);
				throw new Error('Network response was not ok.');
			}
			return response.json();
		})
		.then(data => {
			if (data.success) {
				showConfirmation(data, shadow);
			} else {
				showError(shadow);
			}
		})
		.catch(error => {
			console.error('Error during registration:', error);
			showError(shadow);
		});
	}

	function showError(shadow) {
		const initialViewElement = shadow.querySelector('#initial-view');
		if (initialViewElement) {
			initialViewElement.style.display = 'none';
		}

		const errorViewElement = shadow.querySelector('#error-view');
		if (errorViewElement) {
			errorViewElement.style.display = 'block';
		}
	}

	/**
	 * Extract the order key from the URL.
	 */
	function getOrderKeyFromURL() {
		const urlParams = new URLSearchParams(window.location.search);
		return urlParams.get('key');
	}

	/**
	 * Show Confirmation
	 */
	function showConfirmation(response, shadowRoot) {

		if (!shadowRoot) {
			console.error("shadowRoot is undefined in showConfirmation");
			return;
		}

		// Make sure the data property exists and contains the expected affiliate_link
		if (response && response.data && response.data.affiliate_link) {
			// Show the Confirmation and hide the initial view.
			const confirmationView = shadowRoot.querySelector('#confirmation-view');
			if (confirmationView) {
				// Replace the placeholder with the actual affiliate link
				confirmationView.innerHTML = confirmationView.innerHTML.replace('{affiliateLink}', response.data.affiliate_link);
				confirmationView.style.display = 'block';
			}

			const initialView = shadowRoot.querySelector('#initial-view');
			if (initialView) {
				initialView.style.display = 'none';
			}
		} else {
			// If the affiliate_link is not in the expected format, handle it accordingly (e.g., show an error message)
			console.error("The affiliate_link is not available in the response data:", response);
		}

		// Attach the copy link button event listener
		attachCopyLinkButtonEventListener(shadowRoot);
	};

	/**
	 * Attach event listener to the copy link button
	 */
	function attachCopyLinkButtonEventListener(shadow) {

		// Copy the affiliate link
		function copyAffiliateLink() {
			const affiliateLinkInput = shadow.querySelector('#affiliateLink');
			if (affiliateLinkInput) {
				const affiliateLink = affiliateLinkInput.value;
				navigator.clipboard.writeText(affiliateLink).then(function() {
					affiliateLinkInput.select();
				}).catch(function(error) {
					console.error('Copy failed', error);
				});
			}
		}

		const copyLinkButton = shadow.querySelector('#copyLinkButton');
		if (copyLinkButton) {
			copyLinkButton.addEventListener('click', function() {
				copyAffiliateLink();
				const originalText = copyLinkButton.textContent;
				copyLinkButton.textContent = affiliateWidgetParams.data.button_copied_text;

				setTimeout(() => {
					copyLinkButton.textContent = originalText;
				}, 3000);
			});
		}
	}

	function handleShadowReady(event) {
		widgetContainers.forEach(function(widgetContainer) {
			if (widgetContainer.shadowRoot && !widgetContainer.shadowReadyDispatched) {
				widgetContainer.shadowReadyDispatched = true;
			}
		});
	}

	widgetContainers.forEach(function(widgetContainer) {
		if (!widgetContainer.shadowReadyDispatched) {
			window.addEventListener('shadowReady', () => handleShadowReady(null, widgetContainer));
		} else {
			handleShadowReady(null, widgetContainer);
		}
	});

});