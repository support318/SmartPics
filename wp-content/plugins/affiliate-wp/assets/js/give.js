/**
 * AffiliateWP - GiveWP Integration
 *
 * Handles AffiliateWP settings integration for GiveWP v3+ FormBuilder
 */

/* eslint-disable */
(function () {
	"use strict";

	// Create our settings component first
	function createAffiliateWPComponent() {
		const { createElement: el, useState } = wp.element;
		const { __ } = wp.i18n;
		const { ToggleControl, TextControl } = wp.components;

		return function AffiliateWPSettings({ settings, setSettings }) {
			// Initialize from either settings or our localized data
			const [allowReferrals, setAllowReferrals] = useState(
				settings.affiliateWPAllowReferrals !== undefined
					? settings.affiliateWPAllowReferrals
					: affwpGiveSettings.allowReferrals,
			);
			const [affiliateRate, setAffiliateRate] = useState(
				settings.affiliateWPRate !== undefined
					? settings.affiliateWPRate
					: affwpGiveSettings.affiliateRate,
			);

			// Common function to update settings
			const updateSettings = (key, value) => {
				if (typeof setSettings === "function") {
					setSettings({
						...settings,
						[key]: value,
					});
				}
			};

			// Use WordPress components but with custom layout to match GiveWP
			return el(
				"div",
				{ className: "givewp-settings-section" },
				el(
					"h3",
					{ className: "givewp-settings-section__title" },
					affwpGiveSettings.strings.sectionTitle,
				),
				el(
					"div",
					{ className: "givewp-settings-section__fields" },
					// Allow Referrals Toggle
					el(
						"div",
						{
							style: {
								display: "flex",
								alignItems: "flex-start",
								marginBottom: "32px",
								gap: "48px",
							},
						},
						// Left side - Title and description
						el(
							"div",
							{ style: { flex: "0 0 300px" } },
							el(
								"h4",
								{
									style: {
										fontSize: "16px",
										fontWeight: "600",
										color: "#1e1e1e",
										margin: "0 0 8px 0",
									},
								},
								affwpGiveSettings.strings.allowReferralsLabel,
							),
							el(
								"p",
								{
									style: {
										margin: "0",
										fontSize: "13px",
										lineHeight: "1.5",
										color: "#757575",
									},
								},
								affwpGiveSettings.strings.allowReferralsDesc,
							),
						),
						// Right side - Toggle with label underneath
						el(
							"div",
							{
								style: {
									flex: "1",
								},
							},
							el(ToggleControl, {
								checked: allowReferrals,
								onChange: (value) => {
									setAllowReferrals(value);
									updateSettings("affiliateWPAllowReferrals", value);
								},
								label: "",
							}),
							el(
								"p",
								{
									style: {
										fontSize: "13px",
										color: "#757575",
										margin: "8px 0 0 0",
										lineHeight: "1.5",
									},
								},
								affwpGiveSettings.strings.allowReferralsDesc,
							),
						),
					),
					// Affiliate Rate Field
					allowReferrals &&
						el(
							"div",
							{
								style: {
									display: "flex",
									alignItems: "flex-start",
									gap: "48px",
								},
							},
							// Left side - Title and description
							el(
								"div",
								{ style: { flex: "0 0 300px" } },
								el(
									"h4",
									{
										style: {
											fontSize: "16px",
											fontWeight: "600",
											color: "#1e1e1e",
											margin: "0 0 8px 0",
										},
									},
									affwpGiveSettings.strings.affiliateRateLabel,
								),
								el(
									"p",
									{
										style: {
											margin: "0",
											fontSize: "13px",
											lineHeight: "1.5",
											color: "#757575",
										},
									},
									affwpGiveSettings.strings.affiliateRateDesc,
								),
							),
							// Right side - Input field
							el(
								"div",
								{ style: { flex: "1", maxWidth: "400px" } },
								el(TextControl, {
									value: affiliateRate,
									onChange: (value) => {
										setAffiliateRate(value);
										updateSettings("affiliateWPRate", value);
									},
									placeholder: "e.g., 20 or 15%",
									label: "",
									help: "",
								}),
							),
						),
				),
			);
		};
	}

	// Initialize when WordPress hooks are ready
	const startTime = Date.now();
	function init() {
		if (!window.wp || !window.wp.hooks || !window.wp.element) {
			if (Date.now() - startTime > 3000) {
				// 3 seconds max wait
				console.error(
					"[AffiliateWP] WordPress APIs not available after 3 seconds",
				);
				return;
			}
			setTimeout(init, 100);
			return;
		}

		const { addFilter } = wp.hooks;
		const AffiliateWPComponent = createAffiliateWPComponent();

		// Make component globally available
		window.AffiliateWPSettingsComponent = AffiliateWPComponent;

		// Register our settings route
		addFilter(
			"givewp_form_builder_settings_additional_routes",
			"affiliatewp/settings-route",
			function (routes) {
				routes.push({
					name: affwpGiveSettings.strings.sectionTitle,
					path: "affiliatewp",
					element: AffiliateWPComponent,
				});

				return routes;
			},
			5, // Higher priority
		);
	}

	// Start initialization
	init();
})();
