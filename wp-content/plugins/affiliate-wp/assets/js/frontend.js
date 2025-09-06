// Define the reCAPTCHA onload callback in the global scope immediately
// Only initialize v2 callbacks if v2 is configured
window.affwpRecaptchaOnload = function () {
	// Check if we should be running v2 callbacks
	if (
		typeof affwp_vars !== "undefined" &&
		affwp_vars.recaptcha_version === "v3"
	) {
		return; // Don't initialize v2 callbacks when v3 is active
	}

	jQuery(document).ready(function ($) {
		$(".g-recaptcha.affwp-recaptcha-v2").each(function () {
			var $el = $(this);
			// Only render if not already rendered
			if (!$el.data("widget-id") && !$el.children().length) {
				try {
					var widgetId = grecaptcha.render($el[0], {
						sitekey: $el.data("sitekey"),
						theme: $el.data("theme") || "light",
						size: $el.data("size") || "normal",
					});
					$el.data("widget-id", widgetId);
					// Store widget ID on the form for easy access
					$el.closest("form").data("recaptcha-widget-id", widgetId);
				} catch (e) {
					console.log("reCAPTCHA render error:", e);
				}
			}
		});
	});
};

jQuery(document).ready(function ($) {
	// Datepicker.
	if ($(".affwp-datepicker").length) {
		$(".affwp-datepicker").datepicker({ dateFormat: "mm/dd/yy" });
	}


	// Business account type input on the payout service registration form.
	var accountTypeInput = $("#affwp-payout-service-account-type"),
		businessNameDiv = $(".affwp-payout-service-business-name-wrap"),
		businessOwnerDiv = $(".affwp-payout-service-business-owner-wrap");

	$(accountTypeInput)
		.change(function () {
			if ($(this).val() === "company") {
				businessNameDiv.show();
				businessOwnerDiv.show();
				$("#affwp-payout-service-business-name").prop("required", true);
				$(".affwp-payout-service-country-wrap label").text(
					affwp_vars.business_account_country_label,
				);
			} else {
				businessNameDiv.hide();
				businessOwnerDiv.hide();
				$("#affwp-payout-service-business-name").prop("required", false);
				$("#affwp-payout-service-business-owner").prop("checked", false);
				$(".affwp-payout-service-country-wrap label").text(
					affwp_vars.personal_account_country_label,
				);
			}
		})
		.change();
});
