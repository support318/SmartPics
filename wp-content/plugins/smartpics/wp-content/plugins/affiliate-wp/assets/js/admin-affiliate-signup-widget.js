document.addEventListener("DOMContentLoaded", function() {
	let isConfirmationView = false; // default value
	const mockResponse = { data: { affiliate_link: affiliateWidgetParams.affiliateLinkPreview } };
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
			})
			.then(() => {
				if (!widgetContainer.shadowReadyDispatched) {
					let shadowReadyEvent = new CustomEvent('shadowReady', {
						detail: { shadow: shadow }
					});
					widgetContainer.dispatchEvent(shadowReadyEvent);
					widgetContainer.shadowReadyDispatched = true;
				}
			});
	}

	// View toggle event listener.
	const viewToggle = document.getElementById('viewToggle');
	if (viewToggle) {
		viewToggle.addEventListener('change', function() {
			isConfirmationView = viewToggle.checked;
			widgetContainers.forEach(function(widgetContainer) {
				let shadow = widgetContainer.shadowRoot;
				if (shadow) {
					showConfirmation(mockResponse, shadow, isConfirmationView);
				}
			});
		});
	}

	// Form event listener.
	function widgetAttachFormSubmitEvent(shadow) {
		const formInsideShadow = shadow.querySelector('form');
		if (formInsideShadow) {
			formInsideShadow.addEventListener('submit', function (e) {
				e.preventDefault();
				const buttonElement = shadow.querySelector('#signup-affiliate-preview');

				if (buttonElement) {
					simulateAffiliateSignup(buttonElement, shadow);
				}
			});
		}
	}

	// Simulate affiliate signup when CTA button is clicked.
	function simulateAffiliateSignup(buttonElement, shadow) {
		// Show the loading button state
		buttonLoadingState(buttonElement);

		setTimeout(() => {
			showConfirmation(mockResponse, shadow, true);
			if (viewToggle) {
				viewToggle.checked = true;
			}
		}, 1000);
	}

	// Button loading state.
	function buttonLoadingState(buttonElement) {
		const svgSpinner = buttonElement.querySelector('svg');
		const originalButtonText = buttonElement.querySelector('#originalButtonText');
		const processingText = buttonElement.querySelector('#processingText');

		if (svgSpinner) {
			svgSpinner.removeAttribute('hidden');
		}

		if (processingText) {
			processingText.removeAttribute('hidden');
		}

		if (originalButtonText) {
			originalButtonText.setAttribute('hidden', true);
		}

		buttonElement.disabled = true;
		buttonElement.classList.add('cursor-not-allowed');
	}


	// Show Confirmation.
	function showConfirmation(response, shadow, shouldShow) {
		const initialView = shadow.querySelector('#initial-view');
		const confirmationView = shadow.querySelector('#confirmation-view');
		const button = shadow.querySelector('#signup-affiliate-preview');

		if (shouldShow) {
			if (response && response.data && response.data.affiliate_link && confirmationView) {
				confirmationView.innerHTML = confirmationView.innerHTML.replace('{affiliateLink}', response.data.affiliate_link);
				confirmationView.style.display = 'block';
				if (initialView) {
					initialView.style.display = 'none';
				}
			}
		} else {
			if (confirmationView) {
				confirmationView.style.display = 'none';
			}
			if (initialView) {
				initialView.style.display = 'block';
			}
			if (button) {
				resetButtonState(button);
			}
		}
		attachCopyLinkButtonEventListener(shadow);
	}

	// Reset the button to its original state.
	function resetButtonState(button) {
		if (!button) {
			return;
		}
		
		const svgSpinner = button.querySelector('svg');
		const originalButtonText = button.querySelector('#originalButtonText');
		const processingText = button.querySelector('#processingText');

		if (svgSpinner) {
			svgSpinner.setAttribute('hidden', true);
		}

		if (processingText) {
			processingText.setAttribute('hidden', true);
		}

		if (originalButtonText) {
			originalButtonText.removeAttribute('hidden');
		}

		button.disabled = false;
		button.classList.remove('cursor-not-allowed');
	}

	// Attach event listener to the copy link button.
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

	// Listen for changes on the brand color input and generate shades.
	const colorInput = document.getElementById('affwp_settings[affiliate_signup_widget_brand_color]');
	if (colorInput) {
		colorInput.addEventListener('input', (event) => {
			const colorValue = event.target.value;

			widgetContainers.forEach(function(widgetContainer) {
				let shadow = widgetContainer.shadowRoot;
				if(shadow) {
					generateShades(colorValue, shadow);
				}
			});
		});
	}

	// Generate brand color shades.
	function generateShades(brandColor, shadow) {

		const brandColorRGB = brandColor.slice(1);
		const r = parseInt(brandColorRGB.slice(0, 2), 16) / 255;
		const g = parseInt(brandColorRGB.slice(2, 4), 16) / 255;
		const b = parseInt(brandColorRGB.slice(4, 6), 16) / 255;

		// Calculate luminance.
		const luminance = 0.2126 * r + 0.7152 * g + 0.0722 * b;

		// Determine whether the color is dark or light.
		const isDark = luminance < 0.6;

		// Generate shades based on whether the color is dark or light.
		const shades = isDark ? {

			// Initial view (100s)

			// Heading
			100: `color-mix(in srgb, ${brandColor} 15%, white 85%)`,
			// Text
			105: `color-mix(in srgb, ${brandColor} 15%, white 85%)`,
			// CTA button bg
			110: `color-mix(in srgb, ${brandColor} 60%, white 40%)`,
			// CTA button bg hover
			115: `color-mix(in srgb, ${brandColor} 70%, white 30%)`,
			// CTA button text
			120: `color-mix(in srgb, ${brandColor} 10%, white 90%)`,
			// Terms text
			125: `color-mix(in srgb, ${brandColor} 10%, white 90%)`,
			// Terms link
			130: `color-mix(in srgb, ${brandColor} 10%, white 90%)`,

			// Confirmation view (200s)

			// Heading
			200: `color-mix(in srgb, ${brandColor} 15%, white 85%)`,
			// Text
			205: `color-mix(in srgb, ${brandColor} 15%, white 85%)`,
			// Field wrapper
			210: `color-mix(in srgb, ${brandColor} 10%, white 90%)`,
			// Input field
			215: `color-mix(in srgb, ${brandColor} 10%, white 90%)`,
			// Input field text
			220: `color-mix(in srgb, ${brandColor} 30%, black 70%)`,
			// Input field outline
			225: `color-mix(in srgb, ${brandColor} 10%, white 90%)`,
			// Copy button
			230:  `color-mix(in srgb, ${brandColor} 70%, black 30%)`,
			// Copy button hover
			235: `color-mix(in srgb, ${brandColor} 90%, black 10%)`,
			// Copy button text
			240: `color-mix(in srgb, ${brandColor} 10%, white 90%)`,

			// Error view (300s)

			// Heading
			300: `color-mix(in srgb, ${brandColor} 10%, white 90%)`,
			// Text
			305: `color-mix(in srgb, ${brandColor} 10%, white 90%)`,

			// Background/brand color
			500: brandColor,

		} : {
			// Initial view (100s)

			// Heading
			100: `color-mix(in srgb, ${brandColor} 30%, black 70%)`,
			// Text
			105: `color-mix(in srgb, ${brandColor} 30%, black 70%)`,
			// CTA button bg
			110: `color-mix(in srgb, ${brandColor} 30%, black 70%)`,
			// CTA button bg hover
			115: `color-mix(in srgb, ${brandColor} 30%, black 70%)`,
			// CTA button text
			120: `color-mix(in srgb, ${brandColor} 10%, white 90%)`,
			// Terms text
			125: `color-mix(in srgb, ${brandColor} 30%, black 70%)`,
			// Terms link
			130: `color-mix(in srgb, ${brandColor} 30%, black 70%)`,

			// Confirmation view (200s)

			// Heading
			200: `color-mix(in srgb, ${brandColor} 30%, black 70%)`,
			// Text
			205: `color-mix(in srgb, ${brandColor} 30%, black 70%)`,
			// Field wrapper
			210: `color-mix(in srgb, ${brandColor} 10%, white 90%)`,
			// Input field
			215: `color-mix(in srgb, ${brandColor} 10%, white 90%)`,
			// Input field ring
			216: `color-mix(in srgb, ${brandColor} 80%, black 20%)`,
			// Input field text
			220: `color-mix(in srgb, ${brandColor} 30%, black 70%)`,
			// Input field outline
			225: `color-mix(in srgb, ${brandColor} 10%, white 90%)`,
			// Copy button
			230:  `color-mix(in srgb, ${brandColor} 20%, black 80%)`,
			// Copy button hover
			235: `color-mix(in srgb, ${brandColor} 30%, black 70%)`,
			// Copy button text
			240: `color-mix(in srgb, ${brandColor} 10%, white 90%)`,

			// Error view (300s)

			// Heading
			300: `color-mix(in srgb, ${brandColor} 30%, black 70%)`,
			// Text
			305:  `color-mix(in srgb, ${brandColor} 30%, black 70%)`,

			// Background/brand color
			500: brandColor,
		};

		let cssVariables = ':host { ';
		for (const [key, value] of Object.entries(shades)) {
			cssVariables += `--brand-${key}: ${value}; `;
		}
		cssVariables += '}';


		let styleElement = shadow.getElementById('brand-colors');
		if (!styleElement) {
			styleElement = document.createElement('style');
			styleElement.id = 'brand-colors';
			shadow.appendChild(styleElement);
		}
		styleElement.textContent = cssVariables;
	}

	// Reset color link.
	const resetLink = document.querySelector('.affwp-reset-color-link');
	if (resetLink) {
		resetLink.addEventListener('click', function(event) {
			event.preventDefault();
			widgetContainers.forEach(function(widgetContainer) {
				let shadow = widgetContainer.shadowRoot;
				if(shadow) {
					const defaultColor = '#4b64e2';
					if (colorInput) {
						colorInput.value = defaultColor;
					}
					generateShades(defaultColor, shadow);
				}
			});
		});
	}

	/**
	 * Live preview for text and style changes.
	 */
	function attachLivePreview(inputSelector, shadowSelector, propertyToUpdate, isStyle = false, isConfirmationRelated = false, shadow) {
		const inputElement = document.getElementById(inputSelector);
		if (inputElement) {
			const inputHandler = function(e) {
				const newValue = e.target.value;
				const formattedValue = isStyle ? newValue : newValue.replace(/\n/g, '<br>');
				const targetElement = isConfirmationView ? shadow.querySelector('#confirmation-view ' + shadowSelector) : shadow.querySelector(shadowSelector);
				if (targetElement) {
					if (isStyle) {
						targetElement.style[propertyToUpdate] = formattedValue;
					} else {
						targetElement.innerHTML = formattedValue;
					}
				}
			};

			if (inputElement._inputHandler) {
				inputElement.removeEventListener('input', inputElement._inputHandler);
			}
			inputElement.addEventListener('input', inputHandler);
			inputElement._inputHandler = inputHandler;

			const focusHandler = function() {
				if (isConfirmationRelated) {
					switchToConfirmationView(shadow);
				} else {
					resetToInitialView(shadow);
				}
			};

			if (inputElement._focusHandler) {
				inputElement.removeEventListener('focus', inputElement._focusHandler);
			}
			inputElement.addEventListener('focus', focusHandler);
			inputElement._focusHandler = focusHandler;
		}
	}

	function resetToInitialView(shadow) {
		if (viewToggle) {
			viewToggle.checked = false;
		}
		isConfirmationView = false;
		showConfirmation(mockResponse, shadow, false);
	}

	function switchToConfirmationView(shadow) {
		if (viewToggle) {
			viewToggle.checked = true;
		}
		isConfirmationView = true;
		showConfirmation(mockResponse, shadow, true);
	}

	function handleShadowReady(shadow, widgetContainer) {

		if (!shadow) {
			return;
		}

		widgetAttachFormSubmitEvent(shadow);

		// Initial view.
		attachLivePreview('affwp_settings[affiliate_signup_widget_heading_text]', 'h1', 'textContent', false, false, shadow);
		attachLivePreview('affwp_settings[affiliate_signup_widget_text]', 'p', 'textContent', false, false, shadow);
		attachLivePreview('affwp_settings[affiliate_signup_widget_button_color]', 'button', 'backgroundColor', true, false, shadow );
		attachLivePreview('affwp_settings[affiliate_signup_widget_button_text]', 'button', 'textContent', false, false, shadow);

		// Confirmation view.
		attachLivePreview('affwp_settings[affiliate_signup_widget_confirmation_heading_text]', 'h1', 'textContent', false, true, shadow);
		attachLivePreview('affwp_settings[affiliate_signup_widget_confirmation_text]', 'p', 'textContent', false, true, shadow );

		// Update the preview with the selected image.
		document.addEventListener('mediaURLChanged', (event) => {
			const newUrl = event.detail.url;
			updateImagePreview(newUrl, shadow);
		});

		function updateImagePreview(imageUrl, shadow) {
			const imageWrapper = shadow.querySelector('#signup-image');
			let imageContainer;

			if (!imageWrapper) {
				imageContainer = document.createElement('div');
				imageContainer.id = 'signup-image';
				imageContainer.className = 'absolute inset-0 opacity-25';
				imageContainer.setAttribute('aria-hidden', 'true');

				const aspectRatioBox = document.createElement('div');
				aspectRatioBox.className = 'absolute inset-0 overflow-hidden';
				aspectRatioBox.setAttribute('aria-hidden', 'true');

				imageContainer.appendChild(aspectRatioBox);

				const widgetContent = shadow.querySelector('#widget-content');
				widgetContent.insertAdjacentElement('beforebegin', imageContainer);
			} else {
				imageContainer = imageWrapper.querySelector('div');
			}

			// Update or insert the image
			let imageElement = imageContainer.querySelector('img');
			if (!imageElement) {
				imageElement = document.createElement('img');
				imageElement.alt = "";
				imageElement.className = 'h-full w-full object-cover object-center';
				imageContainer.appendChild(imageElement);
			}
			imageElement.src = imageUrl;
		}

	}

	widgetContainers.forEach(function(widgetContainer) {
		if (!widgetContainer.shadowReadyDispatched) {
			widgetContainer.addEventListener('shadowReady', function(event) {
				if (!widgetContainer.shadowReadyDispatched) {
					widgetContainer.shadowReadyDispatched = true;
					handleShadowReady(event.detail.shadow, widgetContainer);
				}
			});
		} else {
			handleShadowReady(widgetContainer.shadowRoot, widgetContainer);
		}
	});

});
