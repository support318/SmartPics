=== WP Fusion ===
Contributors: verygoodplugins
Tags: infusionsoft, crm, marketing automation, user meta, sync, woocommerce, wpfusion
Requires at least: 4.6
Tested up to: 6.8.1
Stable tag: 3.45.10
Requires PHP: 7.3
License: GPLv3 or later
License URI: http://www.gnu.org/licenses/gpl-3.0.html

The only plugin you need for integrating your WordPress site with your CRM.

== Description ==

WP Fusion is a WordPress plugin that connects what happens on your website to your CRM or marketing automation tool. Using WP Fusion you can build a membership site, keep your customers' information in sync with CRM contact records, capture new leads, record ecommerce transactions, and much more.

= Features =

* Automatically create new contacts in your CRM when new users are added in WordPress
	* Assign tags to newly-created users
* Restrict access to content based on a user's CRM tags
	* Option to redirect to alternate page if requested page is locked
	* Shortcodes to selectively hide/show content within posts
* Apply tags when a user visits a certain page (with configurable delay)
* Configurable synchronization of user meta fields with contact fields
	* Update a contact record in your CRM when a user's profile is updated
* LearnDash, Sensei, and LifterLMS integrations for managing online courses
* Integration with numerous membership and ecommerce plugins

== Installation ==

Upload and activate the plugin, then go to Settings >> WP Fusion. Select your desired CRM, enter your API credentials and click "Test Connection" to verify the connection and perform the first synchronization. This may take some time if you have many user accounts on your site. See our [Getting Started Guide](https://wpfusion.com/documentation/#getting-started-guide) for more information on setting up your application.

== Frequently Asked Questions ==
See our [FAQ](https://wpfusion.com/documentation/).

== Changelog ==

= 3.45.10 - 5/19/2025 =
* FixedFixed numeric strings being converted to integers when saving the WP Fusion settings, which removed leading zeros from numeric values
* FixedFixed new users registered via the Gravity Forms User Registration Add-On being updated twice in the CRM
* FixedFixed tags applied via a WP Fusion Gravity Forms feed being removed by Gravity Forms User Registration
* FixedFixed Salesforce integration the default Record Type ID when creating objects (like Leads) that don't have a Record Type
* FixedFixed PHP warning `Undefined property: stdClass::$plugin` when checking for updates
* Devswitched to using `wp_remote_request()` for Salesforce API calls, rather than `wp_safe_remote_request()`, to make debugging malformed URLs easier

= 3.45.9.1 - 5/8/2025 =
* *Attention HighLevel users:* For several hours on May 7th, a bug in the HighLevel API caused refresh tokens to be returned in an invalid format. This update allows WP Fusion to use the new Reconnect API to attempt to automatically reestablish the connection and remove any corrupted data.
* Fixed new Gravity Forms field mapping UI not working with Keap and other CRMs that store the remote field type locally
* Fixed "A valid URL was not provided" error with Zoho when using auto login links

= 3.45.9 - 5/7/2025 =
* Added a [Sender.net CRM integration](https://wpfusion.com/crm/sender/)
* Added `$gclid` field for sync with Zoho
* Improved - The Maropost integration can now load tags from contacts on any list in the account
* Fixed Paid Memberships Pro integration not syncing the membership status fields if a member cancelled a membership and had no remaining memberships
* Fixed warning about Divi Dynamic Module Framework always showing even if it was disabled
* Fixed WP All Import integration not automatically syncing user meta after importing users
* Fixed error "Unable to process feed, no email address found" with Gravity Forms feeds configured using the new field mapping UI

= 3.45.8.1 - 4/29/2025 =
* Fixed new Gravity Forms field mapping not syncing custom merge fields or custom text values

= 3.45.8 - 4/29/2025 =
* Added a [ClickWhale integration](https://wpfusion.com/documentation/affiliates/clickwhale/)
* Added ability to export and import the WP Fusion settings as a .csv file
* Added support for automatic formatting of non-text field values with Forminator
* Added view in CRM links to the Maropost integration
* Improved - Added support for field mapping using the [generic field mapping UI in Gravity Forms](https://wpfusion.com/documentation/lead-generation/gravity-forms/#feed-settings)
* Improved - The WooCommerce email optin setting will now be disabled if the checkout page does not contain the [woocommerce_checkout] shortcode
* Improved - Updated for compatibility with GiveWP 4.0
* Improved - Added a warning to the Divi Builder settings if the Dynamic Module Framework is enabled
* Fixed HTTP API logging not working with all Maropost API calls
* Fixed Forminator field mapping settings not showing with CRMs that don't use custom fields vs. standard fields
* Fixed notices "Function _load_textdomain_just_in_time was called incorrectly" when loading the WP Fusion settings page in the admin since WordPress 6.8
* Fixed JavaScript error "that is not defined" when adding a new tag in the WP Fusion settings page
* Fixed users' first names being synced as 1 if no name was provided at registration and there was a boolean field in the POSTed data that contained "first"
* Developers: Calling `wp_fusion()->crm->add_tag()` will now add the tag and update the local cache of available tags
* Developers: Added function `wpf_update_option()` for updating options in the WP Fusion settings page

= 3.45.7 - 4/14/2025 =
* Added Booking Rescheduled trigger to [FluentBooking integration](https://wpfusion.com/documentation/events/fluentbooking/)
* Improved - The initial HighLevel authorization flow has been updated to use the gray-labelled LeadConnectorHQ Marketplace
* Improved - If a contact is deleted or merged in HighLevel, WP Fusion will attempt to look up the contact by email address and retry the API call if a match is found
* Improved - If a contact is created or updated in HighLevel, and a "Duplicate Email Address" error is encountered, the existing contact will be updated
* Fixed `get_contact_id()` method with ActiveCampaign returning 403 / unauthorized if called before the `init` hook
* Fixed "Invalid URL provided" error when handling webhooks with Maropost
* Fixed fatal error logging the payment note with EDD if an API error was encountered creating a new contact at checkout
* Developers: Added `wpf_get_user_id_by_email()` function for getting a WordPress user ID from an email address

= 3.45.6 - 4/8/2025 =
* Added support for typing new segment names into the "Select List(s)" dropdown when [using a multiselect field for segmentation with HubSpot](https://wpfusion.com/documentation/crm-specific-docs/how-lists-work-with-hubspot/#using-a-multiselect-for-segmentation)
* Added `multiselect (values)` field type for syncing MemberPress multiselect data as their values, instead of their labels
* Improved - If a user accepts a retention offer to skip a renewal payment with [Cancellation Survey and Offers for Woo Subscriptions](https://wpfusion.com/documentation/ecommerce/cancellation-survey-for-woocommerce-subscriptions/), the updated subscription fields will now be synced to the CRM
* Fixed SliceWP registration date field not being synced
* Fixed email address not being synced from the REST API when updating a user
* Fixed custom fields not being synced from the REST API when updating a user
* Fixed auto login links not pulling user meta since 3.44.27
* Fixed PHP warning `undefined array key "status"` when inserting a referral with SliceWP, if the referral had no status

= 3.45.5.1 - 4/3/2025 =
* Fixed fatal error `undefined class "Condition_Base"` when using Elementor Pro versions below 3.19.0 (January 2024 update), since 3.45.5
* Fixed missing tags select dropdown options when editing Elementor Popups conditions since 3.45.5
* Fixed fatal error loading the WooCommerce WP Fusion settings panel when no settings were configured

= 3.45.5 - 3/31/2025 =
* Added an integration with the [Elementor Hotspots element](https://wpfusion.com/documentation/page-builders/elementor/#hotspots)
* Added support for [Elementor display conditions](https://wpfusion.com/documentation/page-builders/elementor/#display-conditions)
* Added "Required tags (all)" option to the [Divi visibility settings](https://wpfusion.com/documentation/page-builders/divi/)
* Added support for importing contacts with GetResponse
* Added support for syncing multiselect fields to GetResponse
* Added Joined Date field to the Paid Memberships Pro integration
* Improved - If a user is synced from a remote site via WP Remote Users Sync, the normal user register / profile update actions will no longer run (i.e. updating the contact in the CRM and applying registration tags)
* Improved - If the "Membership Start Date" field is enabled for sync on an individual Paid Memberships Pro membership level, it will now sync the member's start date for that level, instead of the user's general start date
* Fixed missing "Next Payment Date" field in Paid Memberships Pro since 3.45.3
* Fixed membership expiration date not syncing at checkout or after changing a membership level with Paid Memberships Pro
* Fixed custom Paid Memberships Pro checkout fields missing from the Contact Fields list
* Fixed tags select dropdowns not being automatically initialized when adding a new Easy Digital Downloads price variation with EDD 3.3.7
* Fixed MemberPress Transactions Meta batch operation not working since 3.45.0
* Fixed `wordpress_logged_in_wpfusioncachebuster` cookie not being cleared on auto-login logout
* Fixed disabled Paid Memberships Pro - Approvals Addon integration since 3.45.3
* Fixed GetResponse integration not validating the API key during setup
* Fixed loading contacts and pull user meta operations not working with GetResponse
* Fixed API errors with WS Form showing on the frontend and preventing the form submission
* Fixed multi-checkbox fields not syncing with WS Form
* Fixed upsell and downsell tags not being applied with CartFlows for guest checkouts when using the "Add to main order" option

= 3.45.4 - 3/24/2025 =
* Added a [BookingPress integration](https://wpfusion.com/documentation/events/bookingpress/)
* Fixed Maropost integration not loading more than 200 available tags or custom fields
* Fixed error querying contact IDs or tags with Ortto, "incorrect size 0 of fields, minimum 1 and maximum 100 is required"
* Fixed tags not syncing between sites with WP Remote Users Sync when the sites were connected to different CRM modules (i.e. FluentCRM same site vs REST API)
* Updated the `WPFSelect` React component to version 1.1.6

= 3.45.3 - 3/19/2025 =
* Fixed error `Uncaught TypeError: Argument 2 passed to WPF_Advanced_Ads::check() must be an instance of Advanced_Ads_Ad` with Advanced Ads 2.0
* Fixed ConvertPro integration not saving tag selections on the targeting settings in the editor with CRMs that used numeric tag IDs
* Fixed Subscription Cancelled tags not being applied with MemberPress since 3.45.0
* Developers: Paid Memberships Pro integration has been separated into three classes for better readability

= 3.45.2.2 - 3/12/2025 =
* Fixed fatal error loading custom fields from ProfilePress since 3.45.2

= 3.45.2.1 - 3/11/2025 =
* Fixed fatal error loading custom fields from Checkout Field Editor Pro in the WP Fusion settings when no block-based checkout fields were configured since 3.45.2

= 3.45.2 - 3/10/2025 =
* Added support for custom checkout fields added to the block-based WooCommerce checkout with Checkout Field Editor Pro
* Improved - All custom fields sections in the Contact Fields list now link to their respective documentation pages
* Improved - Long tag names in the single-option tags select dropdown in the block editor will now break onto multiple lines for readability
* Fixed HighLevel sub-account access tokens not refreshing automatically
* Fixed not being able to switch from HighLevel agency-level authorization to sub-account authorization via the Reauthorize with HighLevel link
* Fixed new Backup Service Account Key feature with Keap / Infusionsoft not persisting between API calls
* Fixed fatal error listing Paid Memberships Pro custom user fields on the Contact Fields list since PMPro v3.1x
* Fixed MemberPress expiration date field getting saved with `text` type for field mapping when configured on individual membership plans
* Fixed PHP warnings when submitting a Gravity Forms form if a WP Fusion feed was active but had no fields mapped
* Fixed lists not being assigned when updating an existing subscriber with FluentCRM (REST API)
* Fixed REST API updates to user profiles not being synced to the CRM
* Developers: Removed compatibility with FluentForms v4.x and lower

= 3.45.1 - 3/3/2025 =
* Added support for tickets and RSVPs configured as blocks with [Event Tickets](https://wpfusion.com/documentation/events/the-events-calendar-event-tickets/#ticket-blocks)
* Added support for a [backup Service Account Key for Infusionsoft/Keap](https://wpfusion.com/documentation/installation-guides/how-to-connect-infusionsoft-to-wordpress/#backup-service-account-key). If the primary key is throttled, WP Fusion will automatically switch to the backup key until 12am UTC
* Added option to update existing user's tags and metadata when importing users via the [import tool](https://wpfusion.com/documentation/tutorials/import-users/)
* Improved - The [Memberoni integration](https://wpfusion.com/documentation/learning-management/memberoni/) will now remove any configured tags on the lesson, roadmap step, course, and/or roadmap when a user marks a lesson or roadmap step as incomplete, or when a course/roadmap progress is reset
* Improved - Salesforce will now retry the API call if a contact is not found, to allow for updates to deleted or merged contacts
* Improved - Added support for membership-level-specific field mapping in the Paid Memberships Pro integration, allowing different CRM fields to be used for each membership level's data
* Fixed - After adding a payment to an order with Keap/Infusionsoft, the `invoice_id` will always be saved. Previously the Job ID was saved, which is usually the same as the Invoice ID, but on some accounts they are no longer in sync
* Fixed import tool with Groundhogg not able to import all contacts if a tag wasn't specified
* Fixed "0" custom field values not being loaded from Keao / Infusionsoft
* Fixed users imported via the import tool without a tag filter showing as "Unknown Tag" in the Import Groups log
* Fixed searching for tags in the "WP Fusion - Remove Tags" SureCart integration not filtering the results by the search term
* Fixed error `WPF_MemberPress_Transactions does not have a method "recurrring_transaction_failed"` since 3.45.0
* Fixed PHP warning in the Salesforce integration when the webhook input was empty

= 3.45.0.1 - 2/26/2025 =
* Fixed error `WPF_MemberPress_Transactions does not have a method "recurrring_transaction_completed"` since 3.45.0
* Fixed the timestamp conversion to midnight with HubSpot sometimes having problems if future dates were after a change to or from Daylight Savings Time (DST)

= 3.45.0 - 2/24/2025 =
* Added ability to sync MemberPress subscription and transaction fields to separate CRM fields [on a per-product basis](https://wpfusion.com/documentation/membership/memberpress/#syncing-fields-on-a-per-product-basis)
* Added support for applying tags based on Roadmap and Roadmap Step completion in the Memberoni integration
* Fixed fatal error `undefined function wpf_logo_svg()` when exporting Event Tickets attendees to a report, since 3.44.26
* Fixed - Removed `@abstract` docblock comment from `WPF_Background_Process` class, since it is not an abstract class, and this was causing validation errors on some servers
* Fixed Infusionsoft invoice ID not being returned and saved for free orders
* Developers: To enhance code readability, we are beginning to refactor our larger plugin integrations into smaller classes, starting with MemberPress. If you are making calls to MemberPress methods manually using `wp_fusion()->integrations->memberpress`, you will need to update your code to use the new classes (e.g. `wp_fusion()->integrations->memberpress->transactions`)
* Developers: Added `instructions.md` and `./.cursor/rules` files to aid in development with Cursor IDE
* Developers: Added `addOrderNote()` method to the Infusionsoft integration for adding order notes to orders

= 3.44.27 - 2/17/2025 =
* Added historical orders export tool for SureCart
* Improved - With [Event Tickets Plus](https://wpfusion.com/documentation/events/the-events-calendar-event-tickets/#editing-attendees), if an attendee's email address is changed on the frontend, and "Create New Attendees on Edit" is enabled, a new contact record will be created in the CRM for the new email address and any tags for the event will be applied
* Improved error handling with Klaviyo - if an invalid field is passed, it will be removed from the request body and the request will be retried once
* Fixed error creating temporary user during form auto-login when the CRM is set to Staging
* Fixed Secure Block for Gutenberg not working inside of a column block
* Fixed LearnDash course cloning causing lessons to be lost if the course was protected by WP Fusion and Shared Course Steps was disabled
* Fixed PHP warning `Undefined property: stdClass::$userId` when authorizing a HighLevel account at the agency level
* Fixed: Removed `consented_at` timestamp from Klaviyo marketing consent API calls, as it is only supported for historical imports
* Fixed HighLevel sub-account location tokens not refreshing automatically when the access token expires
* Developers: Added `wpf_infusionsoft_query_args` filter to allow overriding the default query arguments for the Infusionsoft contact ID lookup

= 3.44.26 - 2/11/2025 =
* Added support for editing Event Tickets attendees in the admin, and an option to create a new CRM contact record when an attendee's email address is changed
* Added (beta) support for authorizing HighLevel at the agency level, and switching between sub-locations within WP Fusion
* Improved - Added WP Fusion status column to the Event Tickets attendees list
* Improved - The KlickTipp integration now uses the WordPress HTTP API instead of the KlickTipp SDK, for improved logging and error handling
* Improved - The KlickTipp integration will now return an error message during setup if the account or account user does not have access to the API
* Improved performance of the WP Fusion tags select component in the block editor
* Improved - If the Klaviyo API returns a `duplicate_profile` error during a contact update for a WordPress user, the correct contact ID will now be saved to the user's record in WordPress
* Improved - If the Klaviyo API returns a `not_found` error during a contact update for a WordPress user, WP Fusion will attempt to look up the contact ID again by email address and retry the request if a match is found
* Fixed database error in FluentCRM (same site) when adding a contact with an empty email address
* Fixed restricted posts without redirects appearing as password protected if they had comments
* Fixed MemberPress transaction fields not being synced when clicking "Process WP Fusion actions again" on the single transaction edit screen
* Fixed fatal error in the HubSpot integration when applying tags via a multiselect field, if there was an API error loading the current tags
* Fixed bug in HubSpot integration where countries that use Daylight Savings Time (DST) were not being converted to UTC correctly
* Fixed error adding a new WPForms WP Fusion connection in WPForms Lite since 3.44.25
* Fixed: Moved the CRM initialization to `init` priority 1, so it runs before other init actions, like Ultimate Member's account activation.
* Developers: Updated the Klaviyo API version to the latest v2025-01-15
* Developers: Added function `wpf_get_name_from_full_name()` to split a full name into first and last name components

= 3.44.25 - 2/3/2025 =
* Added support for [retrying failed API calls in the logs](https://wpfusion.com/documentation/getting-started/activity-logs/#retrying-api-calls)
* Added support for syncing WPForms payment fields
* Added compatibility with the new variable price editor in EDD v3.3.6.1
* Added support for syncing dates and dates with times to Klaviyo
* Fixed "Syntax error" API responses with Microsoft Dynamics 365 when looking up the contact ID for a user with an apostrophe in their email address
* Improved - The "A valid URL was not provided." HTTP response was not properly logging the request URI since it was being escaped by `esc_url_raw()`. The request URI is now being sanitized with `sanitize_text_field()`
* Improved - WP Fusion will no longer update a user's cached tags if they visit an auto-login link, as this was causing issues with the LearnDash BuddyBoss group sync feature triggering notifications for a non-existent user
* Improved - With FluentCRM, if a 404 error is encountered while updating a contact or applying tags, WP Fusion will attempt to look up the contact ID again by email address and retry the request if a match is found
* Fixed fatal error connecting to KlickTipp if the account had no tags
* Developers: Added `./cursor/rules` file for Cursor IDE
* Developers: The [Secure Block for Gutenberg](https://wpfusion.com/documentation/page-builders/gutenberg/) has been updated to `apiVersion 3.0`
* Developers: The REST API integrations for FluentCRM, Groundhogg, and FunnelKit will now only use `wp_safe_remote_get()` for the initial connection, rather than all API calls. This helps avoid some legitimate requests occasionally failing `wp_http_validate_url()`

= 3.44.24 - 1/27/2025 =
* Added support for [multi-level donation forms](https://wpfusion.com/documentation/ecommerce/give/#donation-level-settings) in the new GiveWP visual form builder
* Added support for applying tags after offline donations in the new GiveWP visual form builder
* Improved - The Subscription Product Name and SKU fields will now be sent as a comma-separated string (or, optionally, an array) for WooCommerce subscriptions with multiple products
* Fixed custom contact fields created by typing a new field name into the CRM field select dropdown not saving if they had apostrophes in the name
* Fixed "Dynamic Tag Selection" with Fluent Forms not applying multiple tags for multiple matching conditions on the same multiselect field
* Fixed fatal error in the Groundhogg integration in PHP 8.2 when syncing array formatted data
* Fixed PHP warning loading custom fields from the SureCart API if the store has no checkouts
* Fixed "Uncaught TypeError: Argument 1 passed to WP_Fusion\Includes\Admin\WPF_Tags_Select_API::format_tags_to_props() must be an instance of WP_Fusion\Includes\Admin\mixed, array given" in Give visual form builder integration on PHP 7.4 and below
* Updated EngageBay integration to new API endpoint at https://api.engagebay.com/
* Developers: Added `wpf_elementor_forms_integration` action hook to allow adding custom integrations with Elementor Forms
* Developers: Removed the deprecation notice for the `wp_fusion\secure_blocks_for_gutenberg\API` class until we've had time to update all our addons to the new API endpoint
* Developers: Tested for WordPress 6.8

= 3.44.23 - 1/20/2025 =
* Added support for [Presto Player Email Capture forms](https://wpfusion.com/documentation/other/presto-player/)
* Added support for Forminator 1.39+, Removed legacy v1.30 code.
* Improved - If an HTTP API error is encountered while logging an error-level message to the log, the HTTP API error will be logged as well
* Improved - The `wc_total_spent` and `wc_order_count` fields can now be exported to the CRM for existing users
* Improved - With CRMs that support creating custom properties or attributes via the API, the placeholder text in the CRM field select dropdowns will now show "type to add new" when the field is opened
* Improved - When checking in an attendee with Event Tickets, if the attendee email does not match the contact ID created at checkout, WP Fusion will attempt to find the contact ID by email address
* Improved - Added logging when Event Tickets attendees are skipped due to Add Attendees being disabled on the ticket
* Improved - With Customer.io, when looking up a contact ID by email address, any custom properties will be saved into the WP Fusion CRM fields settings
* Improved performance when using the React based tags select component, the tags will be passed to the component rather than loaded over the REST API
* Fixed PHP warning in the logs when filtering by an invalid user ID
* Fixed infinite loop handling HighLevel 401 errors related to missing scopes
* Developers: cleaned up and standardized build processes for React integrations via webpack.config.js

= 3.44.22 - 1/13/2025 =
* Added a link to the CRM contact record [in the attendee details modal with Event Tickets](https://wpfusion.com/documentation/events/the-events-calendar-event-tickets/#managing-attendees)
* Improved - With FluentCRM, if the email optin checkbox is enabled for WooCommerce, EDD, or GiveWP, the corresponding Status field will be enabled for sync
* Improved - Added status icon to Gravity Forms entries list in the admin
* Improved - Status icons in entries and orders lists now link to the contact record in the CRM
* Improved error handling for HighLevel authorization
* Fixed HighLevel integration not saving the location ID during initial setup when using the older [API-key based setup method](https://wpfusion.com/documentation/crm-specific-docs/highlevel-white-labelled-accounts/)
* Fixed GiveWP form builder integration only saving the settings you've edited in that session
* Fixed fatal error in the FluentCommunity when tags were modified for a user immediately after they were enrolled in a course or group
* Developers: Added `wpf_status_icon()` function for outputting a status icon in the admin

= 3.44.21.1 - 1/7/2025 =
* Fixed missing "else" condition in the `insert_setting_after()` method causing settings panels not to appear in the LearnDash integration
* Fixed new GiveWP form builder integration not displaying saved settings

= 3.44.21 - 1/6/2025 =
* Added (very beta) integration with the new GiveWP visual form builder
* Added support for applying lists in supported CRMs from [FluentBooking bookings](https://wpfusion.com/documentation/events/fluentbooking/)
* Added support for [creating and updating Accounts along with contacts in ActiveCampaign](https://wpfusion.com/documentation/crm-specific-docs/activecampaign-accounts/)
* Added "Unsubscribed" as a default status option for new FluentCRM contacts (same site and REST API)
* Added option to set new contacts to either Subscribed or Pending in FluentCRM (same site and REST API) when they check the opt-in checkbox on the WooCommerce checkout (default is Subscribed)
* Fixed JavaScript error when clicking the Test Connection button during the initial setup with Mailchimp

= 3.44.20 - 12/31/2024 =
* Added support for [FluentCommunity](https://wpfusion.com/documentation/membership/fluent-community/)
* Improved - React select components will now be disabled while the list of available tags is being loaded
* Improved - Added a clear selection button to single tag React select components
* Improved - If a new MemberPress transaction is set to remove and then reapply the same tags, no tags will be modified
* Fixed fatal error `Class "Forminator_Integration" not found` with Forminator Pro 1.38.0+
* Fixed the Forminator field mapping modal not scrolling if there were too many fields to fit on the screen
* Fixed: removed the Assistant Name and Assistant Phone fields from the Infusionsoft / Keap integration (they are no longer supported by the API)
* Fixed PHP errors displaying the CRM Field select dropdown if the loaded fields are corrupted or contained unexpected data
* Fixed MemberPress integration running on status changes between the same status (i.e. from pending to pending)
* Fixed "Type to add new" message being appended each time you clicked on the Select Tag(s) dropdown in the WP Fusion settings

= 3.44.19 - 12/17/2024 =
* Added ability to [apply tags to Amelia event bookings](https://wpfusion.com/documentation/events/amelia/#syncing-bookings-and-custom-fields) in addition to appointments
* Added Event Name and Event Date / Time fields to the Amelia Booking integration
* Added Amelia Booking Events batch export operation
* Improved - When exporting the activity logs to .csv, any current filters will now be applied to the export
* Improved - Settings from non-active CRMs will no longer be saved to the database to reduce the size in the options table
* Improved - When linking to a single entry in the logs, the page will now scroll to the correct entry
* Improved - Infusionsoft will log a more descriptive error message when a 500 error is returned
* Improved - If a registered user makes a booking with Amelia, and does not already have a contact record saved in WordPress, it will be processed as a guest booking
* Improved - Updated WooCommerce Subscriptions batch operations to use `wcs_get_subscriptions()` instead of `get_posts()`
* Fixed FluentCRM (REST API and same site) syncing dates in 12-hour format instead of 24-hour format
* Fixed linked tag not being applied on course enrollment with MasterStudy LMS
* Fixed Event Tickets Plus event checkin tags being applied to the user who purchased the ticket, instead of the attendee
* Fixed - MailerLite no longer allows URLs in webhook names, and was returning an error when trying to create a new webhook. The site name is now used instead
* Fixed error "The manual payment amount exceeds the amount due on the invoices being processed" when sending the payments to Infusionsoft if the order total calculation is off by a couple of cents due to taxes and discounts being rounded
* Fixed fatal error in the MailerLite integration when outputting the site tracking script if the MailerLite account was suspended
* Fixed fatal error in the Amelia Booking Appointments batch operation since 3.44.17
* Fixed fatal error processing Amelia bookings without custom fields since 3.44.16

= 3.44.18 - 12/10/2024 =
* Improved - If an invalid attribute is passed to Klaviyo, the request will now be retried without the invalid attribute
* Improved - If a WooCommerce customer created by a registered user checks out with an alternate email address, the order details in the sidebar will now show the link to the customer's contact record and not the user's contact record
* Fixed: PHP classes and objects stored to usermeta will be excluded from sync to fix errors with sanitization via `stripslashes_deep()`
* Fixed: stopped adding notes to Amelia appointments processed by WP Fusion, as it was interfering with the Google meetings feature adding its own note
* Fixed MemberPress syncing the details of the failed transaction after a payment failure, potentially overwriting the user's current transaction / membership level in the CRM
* Fixed Infusionsoft field mapping the Leadsource field to the `source_type` field. Will now sync to Lead Source ID
* Fixed bulk edit of WooCommerce coupons erasing the WP Fusion settings on those coupons
* Fixed WooCommerce lifetime value and total order count not syncing accurately during an initial checkout

= 3.44.17 - 12/2/2024 =
* Added option to apply tags when a user accepts a retention offer, and sync the offer title to the CRM, with the [WooCommerce Cancellation Surveys plugin](https://wpfusion.com/documentation/ecommerce/cancellation-survey-for-woocommerce-subscriptions/)
* Added option to [prevent linked tags from unenrolling users](https://wpfusion.com/documentation/learning-management/learndash/#course-settings-and-auto-enrollment) from LearnDash courses in cases of payment failures
* Added support for connecting to Agency HighLevel accounts and switching between locations
* Added batch operation for tagging refunded EDD orders
* Fixed custom fields not syncing with Amelia event bookings
* Fixed Profile Complete tags with Ultimate Member not being applied if the user's profile was completed before they were approved by an admin
* Improved - Restricted posts will now be filtered in the content of RSS feeds
* Improved - Updated language in the taxonomy term settings to better indicate that users must be logged in to access content when the "Restrict access" checkboxes are checked
* Fixed PHP warnings in the Klaviyo integration when syncing customer data without a phone number
* Fixed fatal error in the Klick Tipp integration when syncing tags for a contact that didn't have any smart tags

= 3.44.16 - 11/26/2024 =
* Added support for the "Defer Until Activation" feature to the Registration Options for BuddyPress plugin
* Added additional validation on phone numbers for Klaviyo to prevent invalid numbers from blocking the API request
* Improved - The `wc_money_spent` field will now be updated with the lifetime value of the customer when an order is refunded or cancelled
* Improved - HighLevel webhooks can now read the tags from the webhook payload, saving an API call when using the `update_tags` or `update` endpoints
* Fixed custom fields not syncing with Amelia 7.9
* Fixed Event Espresso integration not applying tags to new contacts created in the Not Approved status
* Fixed some false detections of custom code from the Enhanced Ecommerce addon on the Advanced tab of the settings
* Fixed - If an Ultimate Member profile is completed before the member is approved, the profile complete tags will now be applied when they are approved
* Fixed Klaviyo duplicate profile handling appending the duplicate ID to the request URL instead of replacing the existing ID
* Fixed HighLevel integration reading tags out of webhooks as a single comma-separated string

= 3.44.15 - 11/18/2024 =
* Added support for ConvesioConvert (formerly Growmatik)
* Added support for syncing AffiliateWP referral data from Easy Digital Downloads payments
* Improved - MemberPress integration will apply tags and sync fields for the previous membership level when a membership expires
* Improved - Added a warning when changing the FluentCRM tag format, since it requires resyncing tags for every user
* Fixed ld_last_lesson_completed field accidentally got removed from the field mapping in 3.44.14
* Fixed transaction failed tags configured on individual WooCommerce products not being applied since 3.44.11
* Fixed new tag not being saved in WP Fusion after creating it over the API with FluentCRM (REST API)
* Fixed country code being prepended twice to phone numbers with Klaviyo if the provided number was less than 8 digits
* Fixed warnings in the HubSpot integration about invalid date formats when syncing dates to read only fields
* Fixed PHP warning: Undefined array key "apply_tags_converted" in the WooCommerce Subscriptions integration
* Developers: Added `wpf_phone_number_to_e164` filter to allow overriding the default country code added to phone numbers when converting to E.164 format
* Developers: The apply_tags(), remove_tags(), push_user_meta(), and user_register() PHP methods now return a WP_Error object if there was an error, instead of false, to aid in logging
* Developers: The get_contact_id() method now returns false if there was an API error, to allow integrations to try to create a new contact as a fallback
* Developers: Added `wpf_disable_api_queue()` function to allow bypassing the API queue for a single request
* Developers: Added a basic framework for unit testing, with more tests to follow. See readme.md for more information.

= 3.44.14 - 11/12/2024 =
* Added support for [Klaviyo webhooks](https://wpfusion.com/documentation/webhooks/klaviyo-webhooks/)
* Added ability to [set a default Record Type](https://wpfusion.com/documentation/installation-guides/how-to-connect-salesforce-to-wordpress/#record-type) for new Salesforce contacts created by WP Fusion
* Added option to [switch between tag IDs and slugs](https://wpfusion.com/documentation/installation-guides/how-to-connect-fluentcrm-rest-api-to-wordpress/#tag-format) in the FluentCRM (REST API) integration. Tag IDs will be used by default for new installs.
* Added Salesforce compatibility to the [api.php webhook endpoint](https://wpfusion.com/documentation/other-common-issues/webhooks-not-being-received-by-wp-fusion/#the-async-endpoint-advanced)
* Added support for Gravity Forms Product Configurator (feeds will only be processed after the WooCommerce order is processed)
* Added Total Order Count and Total Lifetime Value fields for sync with WooCommerce
* Added Last Group Enrolled field to the LearnDash integration
* Added log indicator when user meta was synced due to the Push All setting
* Improved status indicator on the background worker
* Improved - the `wpf_phone_number_to_e164()` function will now remove leading 0s from phone numbers
* Improved - slightly reduced the byte size required to store the CRM field mapping in the database
* Fixed - The "Paid Memberships Pro membership meta" batch operation previously synced the member's last membership level even if it was canceled or expired. Now it will sync the member as inactive and clear any membership level fields in the CRM
* Fixed missing third parameter `$lookup_cid` in `wpf_get_tags()`
* Fixed wildcard symbol in the Site Lockout's "Allowed URLs" setting not respecting query parameters
* Fixed batch operations not working on multisite since 3.44.11
* Fixed methods in namespaced integration classes showing as custom code on the Advanced settings tab
* Fixed Brevo webhooks not working when the subscriber already had a WordPress user record that was not linked to a contact ID

= 3.44.13 - 11/1/2024 =
* Added error handling when an invalid contact ID is passed to wp_fusion()->crm
* Improved error handling for deleted or merged contacts in HubSpot
* Improved labeling in the Event Tickets UI, the Apply Tags setting is now clear that the tags are applied to the attendee, not the purchaser
* Fixed bulk order status changes with WooCommerce being blocked if the order status was not enabled for sync
* Fixed unhandled error recording an entry note in the WPForms Pro integration when the form entry could not be synced to the CRM
* Fixed undefined index notices in the MemberPress integration when resuming a subscription

= 3.44.12 - 10/28/2024 =
* Added a [Cancellation Survey for WooCommerce Subscriptions integration](https://wpfusion.com/documentation/ecommerce/cancellation-survey-for-woocommerce-subscriptions/)
* Added support for displaying Elementor widgets [dynamically based on the access rules configured on the underlying post](https://wpfusion.com/documentation/page-builders/elementor/#inheriting-access-rules-from-posts)
* Added support for displaying Bricks widgets [dynamically based on the access rules configured on the underlying post](https://wpfusion.com/documentation/page-builders/bricks/#inheriting-access-rules-from-posts)
* Added support for applying lists in the Fluent Forms integration (with supported CRMs)
* Added translations for German, Dutch, Spanish, and Portuguese
* Improved - If the full version of WP Fusion is installed, the Lite version will now be deactivated and a notice will be displayed
* Improved - CartFlows upsell and downsell orders will no longer be processed asynchronously
* Fixed auto-enrollments into AffiliateWP groups linked with tags being triggered each time the user's tags were updated
* Fixed event fields not not syncing when a Event Tickets ticket was purchased via the Tickets Commerce gateway
* Fixed - If the Billing Company field was hidden on the WooCommerce checkout via the Customizer settings, it did not show as an available field for sync
* Fixed a fatal error loading the user's tags when they weren't saved as an array in the database
* Fixed a fatal error calling `wpf_clean_tags()` when the CRM object wasn't loaded
* Fixed "Process with WP Fusion" bulk action not showing when using legacy (non-HPOS) WooCommerce order storage
* Translators: Updated .pot file, merged similar strings in the plugin, and fixed dozens of cases where strings were not translatable
* Developers: Added `wpf_should_do_asynchronous_checkout` filter to allow overriding the default logic for determining if a WooCommerce order should be processed asynchronously

= 3.44.11 - 10/21/2024 =
* Added support for conditional logic when applying tags in Fluent Forms
* Added support for dynamic tag selection fields in Fluent Forms
* Improved - If a customer opts in to marketing on the WooCommerce or Easy Digital Downloads checkout, the marketing consent will be synced to the ActiveCampaign Deep Data customer record
* Improved - A background process will only be started on bulk WooCommerce order status changes if the Order Status field (or order status tags) are enabled
* Improved - Added IPv6 debugging information to the activation error message
* Improved - added logging when batch operations are completed
* Improved storage of batch operations queue when running on a specific list of WordPress user IDs or WooCommerce order IDs
* Fixed failed WooCommerce renewal orders having their status synced as "failed" instead of "pending" when automatic retries are enabled
* Fixed the new "Process with WP Fusion" bulk action for WooCommerce orders not working
* Fixed CartFlows orders being marked as "not processed by WP Fusion" if "Run on main order accepted" was enabled, and the order status was transitioned to Processing and then Completed
* Fixed a bug since 3.44.8 where canceling a batch operation would cause it to become orphaned in the options table. Added a cleanup operation to clear out any orphaned batch operations
* Fixed queued batch operations being set to autoload in options (not necessary when we're only working on one operation at a time)
* Fixed WooCommerce Memberships' "Membership Status" field on specific membership plans not syncing when a membership status is changed
* Fixed PHP warning: `Undefined variable $parent_group` in BuddyPress integration
* Fixed fatal error recording the contact ID created from a form submission to an entry in WPForms Lite (WPForms Lite does not support entry meta)
* Developers: Added method `wp_fusion()->crm->get_marketing_consent_from_email()` to allow retrieving the marketing consent status from an email address (with WooCommerce and Easy Digital Downloads)

= 3.44.10 - 10/14/2024 =
* Added support for [webhooks configured via private apps in HubSpot](https://wpfusion.com/documentation/webhooks/hubspot-webhooks/#webhooks-in-private-apps) (works with all HubSpot plans)
* Added support for refunds via the Infusionsoft/Keap XMLRPC API (thanks @GBBourdages!!)
* Added Region and Time Zone fields to the Klaviyo integration
* Improved - Partially reverted the change to Pending Woo order statuses in 3.44.8: now the pending status will be synced as long as the customer already has a contact record (it will still not create a new contact just to sync the status)
* Fixed error `Too few arguments to function WPF_Woocommerce::batch_step_order_statuses()` when running the WooCommerce Order Statuses batch operation via the Advanced settings tab
* Fixed auto-login system being triggered when `&cid` was in the URL, even if the contact ID was empty, and logging an error
* Fixed PHP warning "foreach() argument must be of type array|object, string given" when canceling batch operations, since 3.44.8
* Fixed unhandled `WP_Error` response in the Gravity Forms Entries batch operation when the call to `GFAPI::get_entry()` fails
* Fixed fatal error in the Event Espresso Registrations batch operation if a registration didn't have a primary attendee

= 3.44.9 - 10/8/2024 =
* Improved - The new Infusionsoft/Keap API [does not support refunding orders](https://developer.infusionsoft.com/faqs/add-refund-order-api/), so after a refund WP Fusion will record an order note with a link to the order so it can be refunded manually
* Fixed Account Name field not being loaded from ActiveCampaign
* Fixed field mapping not showing on new WPForms forms since 3.44.4
* Fixed auto-applied coupons with WooCommerce not being applied during cart recovery links from the Abandoned Cart addon
* Fixed fatal error visiting an auto-login URL with an invalid contact ID with FunnelKit Automations (same site)

= 3.44.8 - 9/30/2024 =
* Improved - Stopped syncing the WooCommerce Order Status field when the order status is "Pending", to prevent duplicate contacts at checkout (especially with the Abandoned Cart addon)
* Improved - Numeric states or regions will no longer be synced to Infusionsoft/Keap to prevent an API error
* Improved reliability when canceling background operations via the Cancel button
* Improved logging for auto-login sessions with invalid contact IDs in the URL
* Fixed auto login system trying to start an auto-login session for visitors with a `wpf_contact` cookie set but an empty contact ID
* Fixed background operations started via cron triggring `wp_die()` instead of returning, and blocking subsequent cron jobs
* Fixed profile updates from Ultimate Member not being synced if the profile form did not contain the user's name or email
* Fixed error removing a user from an AffiliateWP group linked to a tag if they were not already an affiliate
* Fixed import tool with Groundhogg (REST API) not loading more than 100 contacts
* Fixed - further checks to ensure tags arrays are re-indexed before being passed to the CRM

= 3.44.7 - 9/23/2024 =
* Added support (via code snippet) for [syncing lead source data when updating a contact](https://wpfusion.com/documentation/tutorials/lead-source-tracking/#sync-lead-source-data-for-existing-contacts), instead of just when adding a new contact
* Improved - If a tag linked to an AffiliateWP group is applied to a pending affiliate, the affiliate will now be activated before being added to the group
* Improved - New auto-login sessions will now record the current URL to the logs
* Improved - Added links to CRM-specific setup documentation to the CRM configuration settings section
* Improved - Added note to HighLevel setup about logging in to the HighLevel app before attempting the connection
* Improved - Moved Mautic tracking script from footer to head to fix some console errors when playing mediaelement.js videos
* Improved - Updated the list tags pagination API call with Infusionsoft/Keap to use the new V2 compliant specification
* Developers: Added `wpf_api_{$method_name}` filter to allow [bypassing / overriding API calls in the CRM classes](https://wpfusion.com/documentation/filters/wpf_api_method_name/)
* Fixed `user_meta` shortcode not properly converting dates stores as timestamps
* Fixed the tags array API call with Infusionsoft/Keap not being reindexed before being sent, which would cause "Input could not be converted" errors in cases where invalid tags had been removed from the payload
* Fixed error "array_keys(): Argument #1 ($array) must be of type array, bool given" in LearnDash admin course list when no tags were available in the CRM
* Fixed error "undefined function affwp_get_affiliate_statuses()" with AffiliateWP versions below 2.3

= 3.44.6.1 - 9/17/2024 =
* Fixed inverted logic on EDD version check for discount functions: discounts tagging functionality was disabled on EDD 3.0 and higher, since 3.44.6
* Fixed lockout redirect URLs saved without a trailing slash causing an infinite redirect when a lockout redirect is triggered

= 3.44.6 - 9/16/2024 =
* Added support for resyncing contact IDs and tags by bulk-selecting users from the All Users page
* Added support for processing WooCommerce orders in bulk by selecting order IDs from the Orders page
* Added a WooCommerce Order Statuses batch operation
* Improved - Bulk order status changes with WooCommerce will now start a new background process and display an indicator at the top of the Orders page
* Improved - With CRMs that support typing new tags into the tags dropdown, the placholder will update to say "(type to add new)" when the dropdown is open
* Improved - The Brevo site tracking feature will now identify visitors to the tracking script after placing a guest order or form submission
* Improved - The legacy Infusionsoft/Keap module at `wp_fusion()->crm->app` is now lazy-loaded, so it will only be loaded when needed instead of on every page load
* Improved - Disabled discounts features on Easy Digital Downloads versions below 3.0.0, and added a notice to the admin
* Fixed course complete tags not applying with WPComplete when a course with multiple buttons on the same page is marked complete by an admin
* Fixed user role changes after a user's initial registration not being synced to the CRM

= 3.44.5 - 9/10/2024 =
* Added support for syncing guest bookings with Amelia
* Improved - The Infusionsoft/Keap integration will now convert all two-digit state abbreviations to uppercase
* Improved - With Infusionsoft/Keap, if a US state is supplied for an address, and the country code is not provided, the country code will automatically be set to USA
* Improved - With Infusionsoft/Keap, if a region code is provided for an address, and the country code is not provided, a notice will be recorded to the logs
* Fixed the `wp_fusion_init_crm` hook not changing the CRM name on the Setup tab (when white-labelling)
* Fixed course complete tags not applying with WPComplete when a course with multiple buttons is marked complete by an admin
* Fixed custom field mapping not working with WS Form and CRMs that use custom field groups
* Fixed PHP warning in the MemberPress integration when registering a new user without a payment method
* Fixed error in the HighLevel integration when removing tags from a deleted contact
* Fixed Infusionsoft/Keap integration logging an error when recording a payment against a free order

= 3.44.4 - 9/3/2024 =
* Added support for [setting a primary connection for field mapping with WPForms](https://wpfusion.com/documentation/lead-generation/wpforms/#conditional-logic), to make it easier to apply tags via conditional logic
* Added support for multiple memberships with Restrict Content Pro
* Added Last Donation Date field for sync with GiveWP
* Added a delay to batch operations with Klaviyo to avoid the 3 requests per second (60 per minute) API limit
* Improved - If you attempt to sync an invlalid country name or code with Infusionsoft/Keap, WP Fusion will remove the data from the API call to avoid an API error
* Improved - If you attempt to sync an invalid Owner ID with Infusionsoft/Keap, this will crash the API (error code 500). We've added a more descriptive error message to the log to indicate when this field is causing the error
* Improved Infusionsoft/Keap error logging
* Improved - Extended the API timeout with Infusionsoft/Keap to 20 seconds
* Improved - If an API call to Infusionsoft/Keap fails with a 503 error ("service unavailable"), WP Fusion will now retry the API call after a 2 second delay
* Improved - WP Fusion will no longer apply timezone offsets to dates synced to Groundhogg (same site) that don't have a time component
* Improved Salesforce error logging for failed access token refreshes
* Fixed deprecated repeater notices in the JavaScript console with the Elementor Pro Forms integration
* Fixed tags not being applied for WPComplete course / button completion when a course is marked complete in the admin
* Fixed tags applied in FluentCRM (same site) automations, which were triggered by WP Fusion applying a tag, not syncing back to WordPress
* Fixed `wp_capabilities` field not syncing after membership level changes with Paid Memberships Pro
* Fixed error "Cannot use array offset of type string on string" with WPComplete on PHP 8.2
* Fixed logged notices with Infusionsoft/Keap integration saying "Custom field addresses/email_addresses/phone_numbers is not a valid custom field"
* Fixed the Nickname field not syncing with Infusionsoft/Keap
* Fixed HighLevel integration not creating contacts added by ThriveCart
* Fixed PHP warning "Automatic conversion of false to array is deprecated" when the shutdown hook runs multiple times
* Fixed PHP error "array_map(): Argument #2 ($array) must be of type array, string given" in If-Menu integration with PHP 8.2

= 3.44.3 - 8/26/2024 =
* Added support for [creating and updating Leads from form submissions with Zoho](https://wpfusion.com/documentation/crm-specific-docs/updating-leads/)
* Improved - When using [the api.php webhook method](https://wpfusion.com/documentation/other-common-issues/webhooks-not-being-received-by-wp-fusion/#the-async-endpoint-advanced), you can now define a custom ABSPATH via php.ini or a bootstrap file (for custom WP directory locations)
* Improved support for syncing country and region codes with Infusionsoft/Keap
* Improved - With the [`user_meta` shortcode](https://wpfusion.com/documentation/getting-started/shortcodes/#displaying-user-meta), input strings of 8 characters or less will no longer be treated as timestamps (allows for dates like 2024 or 20240101 to be formatted correctly as dates)
* Improvements to the [add_object() method in the CRM base class](https://wpfusion.com/documentation/functions/add_object/)
* Improved - When a user is [auto-enrolled into an AffiliateWP group via a linked tag](https://wpfusion.com/documentation/affiliates/affiliate-wp/#linking-tags-to-groups), their affiliate account will be automatically set to active
* Improved - If Sync Leads is enabled and a user is logged in, has a contact ID, and submits a form, their contact record will be updated, no lead record will be created
* Improved logging when a user is synced to the CRM due to a role change
* Fixed errors syncing to Infusionsoft/Keap custom fields with special characters in the CRM field label (like, ?, !, etc)
* Fixed user registration actions running twice when using the Limit User Roles setting
* Fixed warning "Undefined variable $lists" in ActiveCampaign integration when creating a contact without any lists
* Fixed notice "Add to CRM was not checked, the user will not be synced to the CRM." when adding users manually via the WP Admin
* Fixed user role changes on the admin user profile triggering a sync to the CRM even if the role field is not enabled for sync
* Fixed custom post types created by JetEngine not respecting post access rules
* Developers: added functions `wpf_country_to_iso3166()` and `wpf_state_to_iso3166()` to convert country and state codes to ISO 3166-1 alpha-3 and alpha-2 codes
* Developers: added filter `wpf_country_to_iso3166` to allow overriding the default country to ISO 3166-1 alpha-3 code conversion
* Developers: `wpf_is_field_active()` can now take an array of field IDs, it will return true if any of the fields are active

= 3.44.2 - 8/19/2024 =
* Added support for line items (discounts, shipping, taxes, and fees) with the new Infusionsoft/Keap integration
* Added support for [webhooks (aka "outbounds") with KlickTipp](https://wpfusion.com/documentation/webhooks/klicktipp-webhooks/)
* Added a [visibility indicator on elements protected by WP Fusion access rules in the Bricks editor](https://wpfusion.com/documentation/page-builders/bricks/#visibility-indicator)
* Added [AffiliateWP - Referrals batch operation](https://wpfusion.com/documentation/affiliates/affiliate-wp/#export-options) for exporting historical referral data
* Added a text search field to the WP Fusion Logs page
* Improved API performance for applying and removing tags with Infusionsoft/Keap
* Improved - If an Infusionsoft/Keap API call is throttled due to too many requests, WP Fusion will now wait 2 seconds and try again
* Improved - The new Keap/Infusionsoft integration will now append to the existing Person Notes field when syncing notes, instead of replacing it
* Improved - With the new Infusionsoft/Keap integration, if a US state is specified for an address, and the country is not provided, the country code will be set to USA
* Improved - With the new Infusionsoft/Keap integration, if an invalid locale code is synced to the Language field, a notice will be logged and the field will be removed to avoid API errors
* Improved KlickTipp error handling
* Fixed the Membership Expiration Date field enabled on a specific WooCommerce Membership Level not being set to sync as a Date by default

= 3.44.1.1 - 8/15/2024 =
* Added US state name to ISO 3166-2 code conversion for updating billing and shipping addresses with Infusionsoft / Keap
* Improved - Disabled syncing of the "Person Notes" field with Infusionsoft / Keap for existing contacts, since Keap now replaces the notes field when notes are synced, instead of appending to it
* Improved - Custom fields with Infusionsoft / Keap will now be sorted alphabetically in the WP Fusion field dropdowns
* Fixed "date" type fields (like Birthday) being synced to Infusionsoft / Keap as ISO8601 date-time data since 3.44.1, instead of the `Y-m-d` date format
* Fixed date/time fields with Infusionsoft not being formatted into the WordPress date / time format when loaded
* Fixed missing CRM field labels in WPForms feed settings with CRMs that use custom field categories
* Fixed "Creation of dynamic property" PHP warnings in older CRM integrations with PHP 8.2

= 3.44.1 - 8/12/2024 =
* *Note:* Infusionsoft/Keap have removed the standard "Password" and "Username" fields from the new API, due to security concerns. To avoid errors when syncing passwords and usernames, WP Fusion will log a notice when these fields are detected and remove them from the sync. If you need to sync usernames and passwords, please create new custom text fields to store the data.
* Improved Ontraport error handling for duplicate and not found contacts
* Improved - (Infusionsoft / Keap) Added ISO 3166-1 country name conversion for "United States" to "USA" (previously only matched "United States of America")
* Fixed new Infusionsoft integration swapping the Billing and Shipping addresses
* Fixed new Infusionsoft integration not syncing dates in ISO8601 format
* Fixed new Infusionsoft integration not loading more than 10 available products
* Fixed WP Fusion using a pseudo order item "wpf_rest_product" when creating blank orders with the new Infusionsoft REST API integration
* Fixed error "PHP error: Uncaught TypeError: array_flip(): Argument #1 ($array) must be of type array, array given" when syncing new custom fields with the new Infusionsoft REST API integration
* Fixed EDD Subscription End Date field syncing the renewal date, not the subscription end date (for fixed-length subscriptions)
* Fixed "Remove tags specified in 'Apply Tags' if membership is cancelled" setting not working on LifterLMS memberships
* Fixed EDD renewal payments that were processed by WP Fusion not being marked as `wpf_complete` and not displaying the orange success indicator in the EDD orders list
* Fixed Groundhogg (same site) integration immediately loading custom fields that were added when creating a new contact
* Fixed date fields syncing to Groundhogg (REST API) as timestamps instead of dates
* Fixed tags that were removed in a FluentCRM automation (same site) that was triggered by WP Fusion applying a tag not triggering a sync back to the user's tags in WordPress
* Fixed PHP warning "Attempt to read property "post_type" on null" in the Download Monitor integration
* Developers: `add_contact()` will now return a `WP_Error` if no fields are enabled for sync, instead of `false`
* Developers: The WP Fusion logs are now sorted by log ID instead of timestamp, to avoid confusion when changing the site's timezone

= 3.44.0.2 - 8/6/2024 =
* Fixed custom fields with spaces in the labels not migrating to the new Infusionsoft API field mapping
* Fixed "Unprocessable entity" errors when syncing custom fields with spaces in the label to Infusionsoft/Keap since 3.44.0

= 3.44.0.1 - 8/6/2024 =
* Fixed new Keap / Infusionsoft integration not loading more than 1000 each of tags or tag categories
* Fixed Keap / Infusionsoft integration not importing all contacts if no tag was specified for the import

= 3.44.0 - 8/5/2024 =
* Big update: WP Fusion has been updated to use the Infusionsoft REST API, and [Service Account Keys](https://developer.infusionsoft.com/pat-and-sak/) for authentication. Infusionsoft / Keap users will need to update their API credentials to ensure uninterrupted service.
* Improved - `wpf_get_iso8601_date()` will now more forcefully use GMT for the time zone instead of the local time
* Improved - Added logging if the HubSpot token refresh failed to save
* Improved - `wp_fusion()->settings->set()` will now return false if the setting was not successfully saved
* Fixed "Assign Lists" setting with ActiveCampaign applying to all new contacts, not just new user registrations
* Fixed `"generated_password"` field not being synced with WooCommerce when AffiliateWP's "Automatically register new user accounts as affiliates" setting is enabled
* Fixed "Resubscribe unsubscribed subscribers when they are added to new groups" setting not working with MailerLite
* Fixed MailerLite group IDs not saving correctly in the CartFlows UI (floating point values were being saved as integers)
* Fixed missing `crm.schemas.deals.write` scope in the HubSpot integration, which sometimes caused deal properties not to be saved
* Fixed an error processing a WooCommerce renewal order if no valid order was found for the provided order ID
* Fixed fatal error `WPF_AffiliateWP does not have a method "tag_modified"` in the AffiliateWP integration if an affiliate's status was changed and then tags were applied to the affiliate in the same request
* Fixed deprecated use of `DOMNodeInserted` when editing WooCommerce variations
* Fixed PHP warning "Attempt to read property 'referrer' on bool" when syncing AffiliateWP referrer visit data for a recurring payment

= 3.43.20.1 - 7/30/24 =
* Fixed PHP warning "Attempt to read property 'date' on null" in the EDD Recurring Payments integration when processing an initial payment for a subscription, since 3.43.20

= 3.43.20 - 7/29/24 =
* Improved - If the Order Date or Next Payment Date are enabled with Easy Digital Downloads Recurring Payments, these will now be synced after each renewal payment
* Fixed saved MemberDash tag settings not loading
* Fixed duplicated tag select UI in MemberDash access options settings
* Fixed fatal error on the post table list when no tags are available in the CRM
* Fixed notice "Function ID was called incorrectly" when viewing customer's CRM contact record ID in the WooCommerce order sidebar
* Fixed PHP warnings during a SureCart checkout if no customer address was provided
* Fixed - removed deprecated use of `\MailPoet\Models\Subscriber` when updating MailPoet subscribers
* Fixed fatal error with PHP 8.2 when an EDD subscription expired if Remove Tags was checked and no tags were specified in the Apply Tags setting

= 3.43.19 - 7/22/2024 =
* Added a [MemberDash integration](https://wpfusion.com/documentation/membership/memberdash/)
* Improved - If a `$source` is synced for a Klaviyo subscriber, the same `$source` will be used when opt-ing the subscriber in to marketing
* Fixed "Remove Tags" setting on LearnDash courses and groups not respecting the saved value
* Fixed spaces in tag names not working with If Menu v0.17.0+
* Fixed fatal error adding a member to a WooCommerce Memberships for Teams team on PHP 8.2 when a linked tag was set on the team but no "Apply Tags" were specified

= 3.43.18 - 7/16/2024 =
* Added support for syncing custom Event Tickets fields configured on a single post or page
* Adding Landing Page and Referring URL to the [AffiliateWP referral data](https://wpfusion.com/documentation/affiliates/affiliate-wp/#syncing-referrer-meta-fields)
* Improved - With Ontraport [lead source tracking](https://wpfusion.com/documentation/tutorials/lead-source-tracking/), the any enabled lead source fields will be synced to the corresponding Last Referrer fields when a contact is updated
* Improved - WP Fusion will now declare compatibility with the block-based WooCommerce checkout as long as the email optin field is disabled
* Improved - If users do not have permission to access a WPForo forum, they will no longer receive forum and topic notifications
* Fixed custom properties with Klaviyo being treated as system properties if they are prefixed with a dollar sign
* Fixed AffiliateWP referrer data not being synced with new WooCommerce guest checkouts
* Fixed error `Uncaught TypeError: array_merge(): Argument #2 must be of type array` when editing a new registration form with Ultimate Member on PHP 8.2
* Fixed error `Call to a member function get_id() on string` when using the WooCommerce mini cart with the Nika theme
* Removed the "Source" field from the Klaviyo integration (was not a system field)
* Developers: Added filter `wpf_background_process_memory_utilization_percentage` to allow customizing the memory utilization percentage for the background process

= 3.43.17 - 7/2/2024 =
* Fixed Elementor Forms integration treating single-option selects as multiselects since 3.43.16
* Fixed JavaScript error with Asynchronous Checkout (for WooCommerce) when another plugin returns an AJAX response before the payment is processed
* Fixed weekly license status check not running
* Added a notice in the plugins list when the license key is inactive or expired

= 3.43.16 - 7/1/2024 =
* Added Source field for sync with Klaviyo
* Improved - New contacts added to Pipedrive will be automatically have their marketing status set to "subscribed"
* Updated notice for bulk order status changes to mention that the maximum number of orders that can be processed at once is 20
* Fixed tags not being applied for canceled and failing subscriptions with GiveWP since a recent GiveWP update (not sure exactly which)
* Fixed Elementor Forms integration treating commas in select dropdown options as multiple values, since 3.43.12
* Fixed PHP warning `Undefined global variable $product` since 3.43.15
* Fixed "Unknown lists" error when submitting Elementor Forms after switching from a CRM that supports lists to one that doesn't
* Developers: Added filter `wpf_bulk_order_actions_max_orders` for modifying the max number of orders status changes that will be processed at once

= 3.43.15 - 6/24/2024 =
* Added support for Filter Queries - Advanced with the Search & Filter Pro plugin
* Fixed fatal error when tracking events with FluentCRM and the event value is empty
* Fixed WooCommerce Order Status field not being synced after a successful renewal order with WooCommerce Subscriptions
* Fixed quantity select input not being hidden on restricted WooCommerce products in Elementor product loops
* Fixed HTTP API logging option not showing with Customer.io
* Fixed user passwords not syncing to the CRM when adding new users via Uncanny LearnDash Groups
* Developers: added filter `wpf_show_additional_menu_item_settings` to allow enabling the "Required Tags (all)" and "Required Tags (not)" settings on the admin menu editor
* Developers: added property `wp_fusion()->access->filter_queries_priorty` to allow setting Advanced query filtering to a custom priority

= 3.43.14 - 6/17/2024 =
* Added ability to [sync WooCommerce Memberships status and expiration date to separate custom fields per membership plan](https://wpfusion.com/documentation/membership/woocommerce-memberships/#plan-specific-field-mapping)
* Added support for lists with Constant Contact (can be configured for new user registrations in the General settings, or with the Gravity Forms or Elementor Forms integrations)
* Fixed the activation key being synced as the user's password instead of the provided password, with BuddyBoss
* Fixed LearnDash course completion date being synced in local time instead of UTC (caused issues with the timezone offset and HubSpot)
* Fixed the "Duplicate and Delete" [feature for email address changes](https://wpfusion.com/documentation/crm-specific-docs/email-address-changes-with-mailerlite/) not working with Mailerlite 
* Fixed error "Argument #1 ($json) must be of type string, array given" in the LatePoint integration when creating a booking if no settings are configured on the service

= 3.43.13 - 6/10/2024 =
* Added Last Order Status field for sync with WooCommerce (will be synced whenever the status changes for an order)
* Added logging for MemberPress transaction status changes
* Added filter `wpf_show_additional_menu_item_settings` to allow enabling the "Required Tags (all)" and "Required Tags (not)" settings on the admin menu editor
* Improved - The "Required Tags (not)" setting can now be used on menu items in the admin menu editor when the logged-in condtion is set to Everyone
* Fixed - If Push All was enabled, adding a user from the admin could log a notice "no metadata found for user" if individual usermeta keys were updated before `user_register` was triggered
* Fixed tags not being applied to new ActiveCampaign contacts when the "Account Name" field was enabled for sync
* Fixed MemberPress integration treating empty checkbox values in the database as checked when syncing user meta
* Fixed tags showing up twice in the Select Tags dropdowns with Encharge
* Fixed PHP warning in the SureCart integration when no custom fields were available

= 3.43.12 - 6/3/2024 =
* Added ability to apply lists via form integrations with FluentCRM (same site)
* Improved - After a user's WooCommerce Points and Rewards points balance is synced to the CRM by WP Fusion, the `wc_points_balance` usermeta value will also be updated so the points can be displayed
* Fixed Elementor Forms integration not treating dropdowns with multiple values, and checkbox fields with multiple checkboxes, as multiselects
* Fixed WooCommerce auto-generated passwords not being synced with the block-based checkout, since 3.43.11
* Fixed error "Attempt to assign property 'plugin' on bool" when loading the plugins list, if the recent check for updates failed
* Developers: the WP Fusion + Paid Membersips Pro level settings will no longer be autoloaded from `wp_options`

= 3.43.11 - 5/28/2024 =
* Added support for [Encharge webhooks](https://wpfusion.com/documentation/webhooks/encharge-webhooks/)
* Added support for [Drip site tracking](https://wpfusion.com/documentation/tutorials/site-tracking-scripts/#drip) (was removed at the end of 2022 but Drip has re-enabled it)
* Added Total Spent field to the MemberPress integration
* Improved - The "Generated Password" field with WooCommerce will now be synced during the initial `user_regiser` action rather than waiting for the `woocommerce_created_customer` hook
* Improved - The WooCommerce Points & Rewards integration will now sync the user's points balance when exporting user meta
* Improved - If the same tags are specified for "Apply Tags" and "Apply Tags - Refunded" on a WooCommerce product, the tags applied at checkout will not be removed when the order is refunded
* Fixed - If Sync Leads is enabled and a user is logged in, has a contact ID, and submits a form, their contact record will be updated, no lead record will be created
* Fixed tags not being applied for canceled and failing subscriptions with GiveWP since a recent GiveWP update (not sure exactly which)
* Fixed Elementor Forms integration treating commas in select dropdown options as multiple values, since 3.43.12
* Fixed PHP warning `Undefined global variable $product` since 3.43.15
* Fixed "Unknown lists" error when submitting Elementor Forms after switching from a CRM that supports lists to one that doesn't
* Developers: Added filter `wpf_bulk_order_actions_max_orders` for modifying the max number of orders status changes that will be processed at once

= 3.43.10 - 5/20/2024 =
* Added an Encharge CRM integration
* Fixed [auto-applied coupons with WooCommerce](https://wpfusion.com/documentation/ecommerce/woocommerce/#automatic-discounts) not working on subscription purchases when the current cart total was 0
* Fixed "Points earned for account signup" points with WooCommerce Points & Rewards not syncing during new user registrations
* Fixed missing search box in tags select dropdown for imports, since 3.43.3
* Fixed import tool with Salesforce importing all contacts despite specifying a topic, since 3.43.3
* Fixed users loaded twice by the import tool (i.e. from two different contact records with a matching email) counting twice in the import history table
* Fixed warning "Attempt to read property 'name' on bool" when syncing the MemberPress payment method name on free transactions
* Fixed HubSpot access token getting set to blank if there was a timeout or gateway error while connecting to HubSpot to refresh the token
* Fixed deprecation notices in the Infusionsoft iSDK library when using PHP 8.2
* Fixed date fields in Groundhogg being synced in GMT, not local time, which sometimes caused dates with times to sync as the wrong day
* Fixed - If the user's tags were saved to usermeta as a boolean `true` or `false` instead of an array, this could cause that value to be returned from `wpf_get_tags()`, and cause errors with `array_intersect()` and other array functions
* Fixed Object Sync for Salesforce integration not working with v2.2.9
* Developers: Updated Klaviyo API to the `2024-02-15` revision
* Developers: Updated the Salesforce API version to `55.0` (Summer 2022 version)

= 3.43.9 - 5/13/2024 =
* Added support for Forminator 1.30.0+ (re-enables disabled Forminator integration since 3.43.6.2)
* Updated Constant Contact API token to use new API limits of 250,000 calls per day (was previously 10,000 calls per day) - *Requires re-authorizing the connection via the prompt*
* Fixed import tool with HubSpot importing all contacts despite specifying a list, since 3.43.3

= 3.43.8.1 - 5/7/2024 =
* Fixed ActiveCampaign API error "Error while processing request" when adding a contact to multiple lists at the same time, since 3.43.6

= 3.43.8 - 5/6/2024 =
* Fixed error adding subscribers to Klaviyo lists with marketing consent when the site timzeone was set to UTC or Sydney (UTC+10)
* Fixed error removing subscribers from Klaviyo lists that were added with explicit consent
* Fixed fatal error handling updating a contact in ActiveCampaign when a "Email address already exists in the system." error is encountered while creating a contact
* Fixed automatic enrollments into AffiliateWP groups via linked tags not working
* Fixed - Log entries will now be saved using `gmdate()` instead of `date()` to avoid timezone conversion shenanigans

= 3.43.7 - 4/29/2024 =
* Added a [Content Control integration](https://wpfusion.com/documentation/membership/content-control/)
* Improved - The Elementor Forms integration will now use the field types from the form settings rather than guessing the type based on the submitted value
* Improved - If `wpf_get_contact_id()` is called with `$force_update`, and there is an API error, the existing cached contact ID (if any) will be returned instead of false
* Improved - The query to find auto-applied discounts with EDD will now be cached for one week for performance reasons
* Fixed Restrict Contact Pro group name and owner's email not syncing to the owner's contact record when an owner created a new group
* Fixed infinite loop when using the "Refresh if access is denied" setting and the API call to look up the user's contact ID fails
* Fixed fatal error running WP All Import imports when WooCommerce is not installed since 3.43.5

= 3.43.6.2 - 4/25/2024 =
* Fixed - Completely disabled the Forminator integration until we can rebuild the integration using their new API
* Fixed HighLevel tags not being converted to lowercase for the `remove_tags()` API call

= 3.43.6.1 - 4/24/2024 =
* Fixed PHP warning: "foreach() argument must be of type array|object, null given" when processing Contact Form 7 submissions without payment methods since 3.43.5

= 3.43.6 - 4/23/2024 =
* Added support for [resubscribing unsubscribed contacts with ActiveCampaign](https://wpfusion.com/documentation/tutorials/double-opt-ins/#activecampaign) (supports Gravity Forms and Elementor Forms)
* Added Apply Tags and Link With Tag settings for individual [Teams for WooCommerce Memberships teams](https://wpfusion.com/documentation/membership/teams-for-woocommerce-memberships/)
* Added option to skip already processed entries when running the [Event Tickets Attendees batch operation](https://wpfusion.com/documentation/events/the-events-calendar-event-tickets/#exporting-attendees)
* Added support for applying ActiveCampaign lists with Elementor forms
* Improved - Refactored the Event Tickets integration, removed redundant code and standardized the way the attendees are processed
* Improved HighLevel error handling
* Improved - If a `duplicate` error is returned from the ActiveCampaign API, the contact will be looked up again by email address
* Fixed Event Ticket Attendees batch operation not syncing attendees who registered via Tribe Commerce
* Fixed Event Tickets integration not syncing attendees when no settings were configured on the ticket
* Fixed dynamic tags ("Create tag(s) from value") not applying with guest registrations
* Fixed Paid Memberships Pro fields not syncing when a member is cancelled from their final active level
* Fixed date fields enabled for sync on single WooCommerce subscription products getting saved as type `text` and not type `date` for the purposes of formatting for the API
* Fixed event tracking with Klaviyo
* Fixed LearnDash quiz complete tags not applying with GrassBlade xAPI Companion since 3.43.4
* Fixed groups selected in the admin menu editor with MailerLite getting their IDs when they exceeded the PHP max integer value
* Fixed HighLevel integration treating the error "The token does not have access to this location" as an expired access token and requiring a refresh
* Fixed errors not being logged when an invalid contact ID was used as an auto-login URL parameter
* Fixed Klick-Tipp integration storing contacts' smart tags in usermeta (not necessary and was showing up as Unknown Tag(s) in the admin)
* Fixed Save Changes button in the setup not being clickable when using the Staging CRM
* Fixed Groundhogg integration syncing empty dates as 1/1/1970
* Developers: added function `wpf_get_iso8601_date()`

= 3.43.5 - 4/15/2024 =
* Added an integration with the [ZealousWeb payment plugins for Contact Form 7](https://wpfusion.com/documentation/lead-generation/contact-form-7/#payments)
* Improved - The Bento webhook handler will now use the `email` field from the payload for lookups of existing users
* Improved - Added a warning to the WPForo WP Fusion settings page in the admin to indicate when the forum base page is protected by WP Fusion
* Improved - After a WooCommerce order has been imported by WP All Import, "Process Order Actions Again" will be triggered so the complete order data is synced to the CRM
* Fixed fatal errors with Forminator 1.30.0 and higher (but integration is still broken while we wait to hear back from WPMU Dev)
* Fixed missing "Apply tags - Approved" setting on Gravity Forms feeds with GravityView
* Fixed notice `Deprecated: strpos(): Passing null to parameter #1 ($haystack) of type string is deprecated` when other plugins called `__()` without a text domain

= 3.43.4.1 - 4/9/2024 =
* Fixed error `WPF_BuddyPress::groups_access_meta(): Argument #1 ($settings) must be of type array, string given` when accessing a bbPress or BuddyBoss forum topic, since 3.43.4
* Fixed fatal error calling methods that aren't in the WPF_Staging class, while in staging mode (i.e. `Argument #1 ($callback) must be a valid callback, class WPF_Staging does not have a method "get_connection_id"`)
* Added Event Variation SKU field for sync with FooEvents

= 3.43.4 - 4/8/2024 =
* Added ability to [link to individual entries in the activity logs](https://wpfusion.com/documentation/getting-started/activity-logs/#the-logs)
* Improved - When using hierarchical groups with BuddyPress / BuddyBoss, child groups with no access rules will now inherit the parent group's access settings
* Fixed quiz complete tags not being applied with LearnDash 4.12.0+
* Fixed missing slug when creating new tags via WP Fusion in FluentCRM (same site) since 3.43.3
* Fixed fatal error `Argument #1 ($array) must be of type array, string given` in the admin post list table with PHP 8.2 when a required tag on a post was saved with invalid data

= 3.43.3.1 - 4/3/2024 =
* Fixed "Unknown tag" warning in the UI with FluentCRM since 3.43.3
* Fixed tags not being applied for MemberPress members added to free memberships via the admin
* Fixed error "TypeError: date(): Argument #2 ($timestamp) must be of type ?int, string given" with EDD Software Licensing and PHP 8.1+
* Fixed fatal error "Call to member function get_error_message() on int" when importing users via the async webhook endpoint, since 3.43.3
* Fixed "No tag found with name" error when using the import tool with MailChimp since 3.43.3

= 3.43.3 - 4/2/2024 =
* Added option to remove tags applied at enrollment when a user is unenrolled from a LearnDash course or group
* Added Transaction Number field for sync with MemberPress
* Added support for importing all contacts from the CRM via the import tool (rather than a specific segment), [with selected platforms](https://wpfusion.com/documentation/tutorials/import-users/)
* Improved - While in [staging mode](https://wpfusion.com/documentation/faq/staging-sites/) you can now resync the lists of available tags and fields from the CRM
* Improved - Added a warning indicator when an unknown tag (from a previous CRM) is saved to a Select Tag(s) dropdown
* Improved - If staging mode is enabled in wp-config.php, the Staging Mode checkbox will be disabled in the settings
* Fixed API calls before the `init` hook not getting logged with HTTP API logging
* Fixed LearnDash Progress Meta batch operation syncing Last Course Completed date as 0 for courses that were started but not yet completed
* Fixed restricted access redirects not working on Thrive Architect landing pages
* Fixed user IDs imported via Salesforce webhooks getting appended to the most recent manual import on the Import Tool tab in the settings
* Fixed Account Name not syncing with ActiveCampaign since the switch to the v3 API in v3.41.36
* Fixed Gravity Forms feeds being processed twice since 3.43.0
* Fixed tags created by WP Fusion in FluentCRM (same site) using the tag label as the slug (instead of the sanitized title)
* Fixed Contact Form 7 integration running on form entries where no fields were mapped
* Fixed pre-selected / default country codes in phone number fields not syncing with Elementor Forms
* Tested up to Elementor (+ Pro) 3.21.0
* Fixed "Uncaught ArgumentCountError: Too few arguments to function WPF_WPBakery::add_css_class()" since 3.43.0
* Developers - `wp_fusion()->user->apply_tags()` and `wp_fusion()->user->remove_tags()` will now attempt to convert tag labels to IDs if the CRM doesn't support adding tags (helps with switching CRMs)

= 3.43.2 - 3/25/2024 =
* Added support for connecting to FunnelKit Automations via PHP instead of REST API (signiciantly improved performance when both plugins are on the same site)
* Removed fallback support for legacy `/autonami-admin/` REST API endpoint (was removed from FunnelKit in 2.6.0, Sept 2023)
* Improved - If an entry is later edited with Gravity Forms or GravityView, the entry will be processed again and any updated fields will be synced to the CRM
* Improved - Query parameters will now be removed from the "Current Page" and "Landing Page" URLs before being synced to the CRM
* Fixed missing settings page with the free ProfilePress plugin
* Fixed missing warning when connecting to FunnelKit and the Pro plugin isn't active
* Fixed conditions on Elementor Popups not working with tags with special characters in them with some CRMs
* Fixed error `Cannot use object of type WP_Error as array` when logging HTTP API errors since 3.43.1

= 3.43.1.1 - 3/21/2024 =
* Fixed error "/wp-fusion/includes/integrations/class-woocommerce-compatibility.php is not a known WordPress plugin" when using the Email Optin checkbox with WooCommerce, since 3.43.1
* Improved - WP Fusion now declares itself incompatible with the WooCommerce block-based product editor

= 3.43.1 - 3/19/2024 =
* Added a [MasterStudy LMS integration](https://wpfusion.com/documentation/learning-management/masterstudy/)
* Added option to automatically remove [MemberPress corporate account tags](https://wpfusion.com/documentation/membership/memberpress/#corporate-accounts) from sub-account members when the parent membership is cancelled
* Improved - When using [HTTP API logging](https://wpfusion.com/documentation/getting-started/activity-logs/#http-api-logging), the amount of time to perform the API call will be recorded to the logs
* Improved - When an API error is encountered, the full API call and response will be logged
* Improved - When using the Email Optin feature with WooCommerce, WP Fusion will now declare itself incompatible with the new checkout block
* Improved plugin updater - updates will now show even if license key is expired
* Improved - When debugging the admin settings page using the `&debug` URL parameter, the contents of `wpf_import_groups` will now be output with the rest of the debug data
* Fixed `List not found with ID` error when adding contacts to Klavio lists with explicit consent
* Fixed `Invalid consent timestamp` error when adding contacts to Klavio lists with explicit consent, with timezone offsets above GMT
* Fixed WPBakery visibility indicator showing up when not in editing mode for users who have the admin bar visible
* Fixed the import tool tracking failed user imports as successful for purposes of the table of historical imports
* Fixed user IDs getting tracked multiple times in the settings table listing user import history, artifically inflating import counts
* Fixed undefined index warning syncing custom fields with Klaviyo
* Fixed array data not being converted to a string when updating Salesforce contacts using the `update_contact()` method

= 3.43.0 - 3/12/2024 =
* The Great Date update. Prior to this version, dates and timezones across integrations and CRMs were inconsistent. Some plugins used UTC (MemberPress) and some used local time (WooCommerce). For some CRMs we converted the time zone back to local time (HubSpot), some we converted to UTC (Ontraport), and some we left as is (ActiveCampaign). This update attempts to standardize the way dates and times are handled by extracting all dates from plugin integrations in UTC, and then converting them back to local time when syncing to CRMs that require it that way. This will result in more predictable handling of time zones, but there may be unexpected behavior in the initial release.
* Added a [visibility indicator](https://wpfusion.com/documentation/page-builders/wpbakery-page-builder/#visibility-indicator) to protected elements with the WPBakery frontend editor
* Added a [batch export tool for Elementor Forms entries](https://wpfusion.com/documentation/lead-generation/elementor-forms/#syncing-historical-entries)
* Added support for custom fields with SureCart
* Added support for loading and displaying read-only fields with HubSpot
* Added throttling to batch operations with Constant Contact to get around the 4 API calls per second limit
* Improved - If a user is a LearnDash Admin or Group Leader and is automatically enrolled in all courses, they will be ignored for the purposes of WP Fusion's course auto-enrollment
* Improved WP Remote Users Sync integration (better reliability for syncing tag changes)
* Improved - In the admin settings, setting the logs to "Only Errors" will disable the checkbox for "HTTP API Logging"
* (Potentially) improved performance with EngageBay by removing the leftover and unnecessary `host: api.engagebay.com` header from HTTP requests
* Improved - The batch operations list in the admin is now sorted alphabetically
* Fixed notice not being logged when an update webhook was received but no matching user was found
* Fixed dropdown fields with WooCommerce Product Options syncing their internal values instead of the displayed labels
* Fixed Gravity Forms User Registration actions running twice when using Gravity Press and wiping out the local tag cache for the user
* Fixed PHP notices in the Amelia integration
* Developers: If a user has a contact ID and no tags, the tags meta will now be deleted from the usermeta table for that user (reduces database size for users with no tags)

= 3.42.14 - 2/27/2024 =
* Updated expired Microsoft Dynamics 365 app secret
* Improved - API actions will no longer be queued up for the `shutdown` hook when triggered as part of a cron process (should fix timeouts with WooFunnels and Forcefully Switch Order Statuses)
* Improved - Errors during the initial OAuth connection with Dynamics 365 will now be displayed as a banner on the setup screen
* Improved - With Klaviyo, the country code will now be prepended to phone numbers if it's missing during a WooCommerce checkout or profile update
* Fixed duplicate review error when submitting reviews with Easy Digital Downloads since 3.42.10
* Developers: added function `wpf_phone_number_to_e164()` to convert phone numbers to E.164 format

= 3.42.13 - 2/26/2024 =
* Added support for [If-So block editor conditions](https://wpfusion.com/documentation/other/if-so/#blocks)
* Improved - The query to get WooCommerce coupons linked to tags will now be cached in a transient for improved performance
* Improved EngageBay error handling
* Improved EngageBay API performance: will now make calls directly to the account subdomain instead of `https://api.engagebay.com/dev/api/`
* Improved - Reduced some duplicate MemberPress meta syncs when creating a transaction and a subscription at the same time
* Fixed tags not applying for MemberPress transactions created as order bumps
* Fixed tags not applying for MemberPress transactions created via Gravity Press
* Fixed Profile Builder Pro syncing the user avatar field as an attachment ID instead of image URL
* Fixed Ninja Forms batch export operation not working on forms that weren't configured to apply tags
* Fixed missing nonce check on search log users AJAX handler
* Fixed - if an Solid Affiliate activation linked tag and group link tag were applied at the same time, the affiliate wouldn't be added to the group

= 3.42.12 - 2/20/2024 =
* Added a [LatePoint integration](https://wpfusion.com/documentation/events/latepoint/)
* Added an option with Klaviyo to [subscribe contacts to lists with marketing consent](https://wpfusion.com/documentation/crm-specific-docs/klaviyo-marketing-consent/)
* Added support for the Canadian data center with Zoho
* Improved - A user auto-enrolled into a BuddyPress group as an organizer will no longer be unenrolled due to missing the group's general linked tag
* Improved error logging when users fail to be unenrolled from BuddyPress groups
* Improved - Invalid characters will now be removed from phone numbers synced to Zoho to avoid API errors
* Improved - Errors during the initial OAuth connection with Zoho will now be displayed as a banner on the setup screen
* Improved - Empty values will never be sent to Salesforce when creating a contact, to fix `Cannot deserialize instance from VALUE_STRING` errors
* Improved - Date fields in Salesforce can now be erased by syncing an empty value (previously empty dates were ignored)
* Fixed Easy Digital Downloads discount "apply tags" settings getting lost after the discount was first used
* Fixed calling Easy Digital Downloads' `edd_update_discount()` from outside the discount edit screen removing the WP Fusion settings from the discount
* Fixed filters added on `wpf_format_field_value` before `init` being un-hooked with Zoho
* Fixed a fatal error with Uncanny LearnDashy Groups 6.0.0 when enrolling a user into a group if WooCommerce wasn't active
* Fixed a fatal error syncing a MemberPress transaction to FluentCRM when the Enhanced Ecommerce plugin was active (Enhanced Ecommerce doesn't support FluentCRM)
* Fixed error `Timestamp must be of type int` when syncing string timestamps with Groundhogg (same site)
* Developers: updated the Klaviyo API version to `2023-12-15`

= 3.42.11 - 2/10/2024 =
* Fixed for HighLevel API update of Feb 8th (now sending 403 status codes when access tokens expire, was previously 401)
* Improved: Gravity Forms integration will now pass the entry ID to the forms processor class
* Fixed WP Fusion auto-enrolling users into BuddyBoss groups via linked tags even if they were already a moderator of that group
* Fixed fatal error auto-enrolling users into Members membership levels when the user doesn't currently have any user roles, with PHP 8.1+
* Fixed deprecated `utf8_encode()` warnings in the Infusionsoft XMLRPC library
* Fixed fatal error logging 500 status error messages from the Constant Contact API
* Fixed fatal error `Unsupported operand types: null + array` with syncing lead source data without any other contact data present since 3.42.10

= 3.42.10 - 2/5/2024 =
* Added an [Amelia booking integration](https://wpfusion.com/documentation/events/amelia/)
* Added [Customer.io site tracking scripts](https://wpfusion.com/documentation/tutorials/site-tracking-scripts/#customer-io)
* Added [Customer.io webhooks](https://wpfusion.com/documentation/webhooks/customer-io-webhooks/)
* Added a setting to apply tags when a customer leaves a review with Easy Digital Downloads
* Improved - Multi-key FluentCRM events will now be sent JSON-encoded
* Improved - The Save Changes button in the settings will now be disabled until the initial CRM connection has been established
* Improved - The [JavaScript-based lead source tracking method](https://wpfusion.com/documentation/tutorials/lead-source-tracking/#caching) no longer requires jQuery
* Fixed Formidable Forms fields not showing for mapping in the WP Fusion settings if they had a default value
* Fixed lead source data for new user registrations being synced even if there was an existing contact in the CRM
* Fixed missing CRM field labels in dropdowns with Brevo since 3.42.8
* Fixed PHP warning syncing available custom properties with Omnisend when no custom properties were available
* Fixed error syncing available custom fields with Drip when no custom fields were available
* Fixed missing event value for single key FluentCRM events
* Fixed security vulnerability in the [`user_meta_if` shortcode](https://wpfusion.com/documentation/getting-started/shortcodes/#displaying-content-based-on-user-meta-values), an editor could potentially execute arbitrary PHP code by passing a function name to the `field_format` parameter

= 3.42.9 - 1/30/2024 =
* Added support for the [new FluentCRM event tracking module](https://fluentcrm.com/fluentcrm-2-8-40/#dynamic-contact-activity-tracking) (same site and REST API)
* Improved - Updated Omnisend event tracking to use new `/customer-events` endpoint
* Improved HubSpot error handling for failed access token refreshes
* Fixed WP Fusion not showing as compatible with WooCommerce High Performance Order Storage until the initial setup was completed
* Fixed Groundhogg (Same site) integration not loading mapped BuddyPress fields automatically when their corresponding custom fields were edited in Groundhogg
* Fixed FluentCRM and ActiveCampaign treating an invalid contact ID when looking up tags as a contact with no tags (caused auto-login links to unlock content for invalid contacts)

= 3.42.8.1 - 1/25/2024 =
* Improved: The background worker will now `return` instead of `exit` when the queue is empty, to prevent the worker from blocking subsequent cron tasks
* Improved HubSpot error handling
* Improved error logging for composite responses with Salesforce
* Improved Omnisend event tracking (numeric values will no longer be sent as strings)
* Fixed refreshing available Ontraport tags resetting the available tags list since 3.42.8
* Fixed WooCommerce order item refunded tags not being logged when a guest checkout order was refunded
* Fixed auto-applied discounts not working with Easy Digital Downloads 3.0
* Fixed fatal error displaying Select Tag(s) dropdowns after resyncing available tags while WP Fusion was in staging mode

= 3.42.8 - 1/22/2024 =
* Added a CRM integation with [Omnisend](https://wpfusion.com/documentation/installation-guides/how-to-connect-omnisend-to-wordpress/)
* Added Last Order Shipping Method field for sync with WooCommerce
* Improved Customer.io error handling
* Improved - When clicking Process Order Actions Again on a WooCommerce order, the transient that locks the order will be cleared (fixes cases where a prior sync crashed or timed out before finishing)
* Fixed special characters in tag names breaking If-Menu tag condition dropdowns
* Fixed PHP warning `Undefined array key "email"` in Customer.io integration when updating contacts
* Fixed special characters in tags applied by Elementor Forms being synced as HTML entities
* Fixed `Undefined variable $apply_tags` with some CRMs since 3.42.6
* Fixed `Undefined variable $available_tags` with MailPoet integration when loading a subscriber's lists
* Developers: changed the `remote_field` and `remote_type` custom field properties introduced in 3.42.5 to `crm_field` and `crm_type` for consistency (is backwards compatible)

= 3.42.7 - 1/17/2024 =
* Fixed integrations with a missing documentation URL getting disabled when the main settings page was saved, since 3.42.6
* Fixed additional undefined array key warnings in the Gravity Forms integration when editing a form feed
* Developers: extended the ActiveCampaign API timeout to 20 seconds for loading, applying and removing tags

= 3.42.6 - 1/15/2024 =
* Added support for the Paid Memberships Pro [Gift Membership Addon ](https://wpfusion.com/documentation/membership/paid-memberships-pro/#gift-memberships)
* Added an [order status column](https://wpfusion.com/documentation/ecommerce/easy-digital-downloads/#order-status-column) to the Easy Digital Downloads orders table
* Added Apply Tags - Resumed setting to the [MemberPress integration](https://wpfusion.com/documentation/membership/memberpress/#tagging)
* Added support for lists with FluentCRM (REST API)
* Added Created At field for sync with Customer.io
* Added Fatal Error Notify promotional banner to the bottom of the logs page
* Tested for Paid Memberships Pro 3.0
* Tested for FunnelKit Builder 3.0
* Improved - If a WooCommerce order is refunded, and the parent subscription is still active, no tags will be removed
* Improved - Admin notices from other plugins will be hidden on the logs page
* Improved - Individual plugin integrations can now be disabled from the Integrations tab in the settings
* Fixed tags created in FluentCRM (REST API) via WP Fusion being stored as tag IDs and not slugs 
* Fixed (for real this time) `update` and `update_tags` webhook endpoints not working with FluentCRM (REST API) 2.8.3+
* Fixed PHP warning when editing a Formidable Forms action and a previously mapped field was deleted
* Fixed menu item restriction not working on the BuddyPress / BuddyBoss profile and account pages
* Fixed Customer.io integration not syncing dates as Unix timestamps

= 3.42.5 - 1/8/2024 =
* Added support for [double opt-ins with Brevo](https://wpfusion.com/documentation/tutorials/double-opt-ins/#brevo)
* Added support for [setting a default opt-in status with Groundhogg](https://wpfusion.com/documentation/tutorials/double-opt-ins/#groundhogg) (REST API)
* Added support for using Data Driven Segments for content restriction with Customer.io
* Added support for saving the field type for each remote field in the CRM. Improves data format conversion (currently just Brevo)
* Fixed `update` and `update_tags` webhook endpoints not working with FluentCRM (REST API) 2.8.3+
* Fixed Ninja Forms integration not showing as loaded on the Integrations tab in the settings
* Fixed Data Driven Segments being loaded as _Unknown Tag_ with Customer.io
* Fixed excerpts being removed from restricted content when accessed over the REST API since 3.42.3
* Fixed undefined array key warnings in the Gravity Forms integration when editing a form feed
* Developers: removed an `array_filter()` from the `WPF_User::get_user_meta()` function (was preventing exporting empty values to the CRM)

= 3.42.4 - 1/4/2024 =
* Fixed Elementor Forms text fields being treated as dates since 3.42.3
* Fixed "Order Refunded" tags on WooCommerce products not being applied when orders were fully refunded
* Fixed `WPF_User::get_users_with_contact_ids` incorrectly showing up as custom code in the Status panel of the settings

= 3.42.3 - 1/2/2024 =
* Improved - With BuddyBoss, the forums archive can now be protected by editing the Forums page, rather then via the WP Fusion Integrations settings (prior settings copy over automatically)
* Improved - With Brevo, select and multiselect fields will now automatically be synced to their numeric values required by the Brevo API (requires a one-time Refresh Available Tags and Fields via the settings)
* Improved - automatic date format detection with Elementor Forms submission will ignore dates prior to 100 years ago (fixes some weird issues with strings like 7015f0000006jqgAAA being treated as 2,000 BC)
* Fixed the BuddyPress / BuddyBoss user profile page being protected if the forums archive was protected
* Fixed Customer.io integration updating the `created_at` parameter for logged in users on each page load, when site tracking was active
* Fixed Customer.io integration not supporting multi-key event tracking

= 3.42.2 - 12/27/2023 =
* Added a Customer.io CRM integration
* Added a [SureMembers integration](https://wpfusion.com/documentation/membership/suremembers/)
* Added support for [using an API key with HighLevel](https://wpfusion.com/documentation/crm-specific-docs/highlevel-white-labelled-accounts/#overview) instead of OAuth
* Improved - Post content restricted by WP Fusion will no longer be accessible over the REST API to unauthorized users
* Improved - If a redirect is set on a restricted LearnDash lesson or topic, and Lock Lessons is enabled, the lesson will now be clickable (so the user can be redirected)
* Fixed myCred rank tags not being applied for alternate point types
* Fixed custom fields not syncing with Jetpack CRM
* Fixed fatal error trying to sync array/multiselect data to a text field in Jetpack CRM

= 3.42.1 - 12/18/2023 =
* Added support for [EDD Cancellation Survey](https://wpfusion.com/documentation/ecommerce/edd-recurring-payments/#cancellation-surveys)
* Added an option to apply the current user's CRM tags [as CSS classes to the HTML `<body>` element](https://wpfusion.com/documentation/getting-started/access-control/#protecting-content-via-css)
* Fixed bbPress user profiles inheriting access rules from the first post on the site
* Fixed (changed the `class_exists()` order check for Advanced Shipment Tracking Pro to make sure it takes priority over WooCommerce Shipment Tracking
* Fixed saving the WP Fusion settings page after an OAuth token had been refreshed causing the old token to get saved, and breaking the connection
* Fixed PHP warning `foreach() argument must be of type array|object, bool given` after deleting all taxonomy-based access rules
* Fixed PHP notices on WishList Member settings page
* Fixed fatal error tracking events with FluentCRM when the FluentCRM - Events plugin was deactivated after events had been configured

= 3.42.0 - 12/11/2023 =
* Added an integration with [Forminator forms](https://wpfusion.com/documentation/lead-generation/forminator/)
* Added an integration with [WP Software License for WooCommerce](https://wpfusion.com/documentation/ecommerce/wp-software-license/)
* Added support for [linked tags with Gamipress ranks](https://wpfusion.com/documentation/gamification/gamipress/#ranks)
* Added compatibility with Advanced Shipment Tracking Pro (fork of [WooCommerce Shipment Tracking](https://wpfusion.com/documentation/ecommerce/woocommerce-shipment-tracking/))
* Improved - With [Fluent Forms](https://wpfusion.com/documentation/lead-generation/fluent-forms/) and [Lead Source Tracking](https://wpfusion.com/documentation/tutorials/lead-source-tracking/), if the lead source cookies are set, the form will no longer be processed asynchronously (to avoid losing the lead source data)
* Improved - With Gifting for WooCommerce Subscriptions, if Remove Tags from Customer is checked, they will no longer be removed from the customer if the customer has a separate subscription to the same product
* Improved - If staging mode is enabled via wp-config.php (`WPF_STAGING_MODE`) the "It looks like this site has moved or is a duplicate" notice will not be displayed
* Improved - Extended the timeout with ActiveCampaign to 15 seconds
* Fixed users who registered without a first name getting their first name synced to the CRM as their username
* Fixed resync tags and pull user meta batch operations not working since 3.41.48
* Fixed [Filter Course Steps with LearnDash](https://wpfusion.com/documentation/learning-management/learndash/#filter-course-steps) not working with lesson / topic pagination in the course navigation
* Fixed `wpf_get_user_id( $contact_id )` returning the IDs of temporary auto-login users

= 3.41.48 - 12/5/2023 =
* Added Memberships for Teams Team ID field for sync with WooCommerce Memberships for Teams
* Fixed new pending orders added via the WooCommerce admin not being synced to the CRM despite being registered on the `woocommerce_order_status_pending` hook
* Fixed fatal error calling `wpf_get_tag_id()` before WP Fusion had been connected to a CRM
* Fixed PHP error `Parse error: syntax error, unexpected ')'` when gathering the user metadata for a user in PHP < 7.3
* Fixed `Uncaught TypeError: round(): Argument #1 ($num) must be of type int|float, string given` when calculating GiveWP total donor value with PHP 8.1
* Fixed upgrade message in Lite plugin blocking contact fields when WooCommerce and BuddyPress weren't active
* Fixed WP Fusion attempting to sync Give donors with missing email addresses
* Fixed `wpf_get_setting_enable_queue` showing up under the Custom Code list on the Advanced tab in the settings, with Groundhogg and FluentCRM (same site)

= 3.41.47 - 11/27/2023 =
* Fixed `Uncaught Error: Call to undefined function is_user_logged_in()` on some hosts since 3.41.46

= 3.41.46 - 11/27/2023 =
* Added an integration with [WP User Manager](https://wpfusion.com/documentation/membership/wp-user-manager/)
* Added contextual tooltips to some fields in the Contact Fields tab of the WP Fusion settings
* Added a Membership Status field for sync with MemberPress
* Added Required Tags (not) setting to Divi integration (thanks Ted!)
* Improved - The MemberPress Subscription Status field will now be synced any time a subscription status changes, instead of just when it changes to active
* Improved - If a user registers with a missing `first_name` and `last_name` but their `display_name` is set, the Display Name will be used for the first and last names
* Improved - If you try to sync multiselect or array-formatted data to a text field in HighLevel, WP Fusion will automatically combine the items to prevent an API error
* Improved - If you add `define( 'WPF_STAGING_MODE', false );` to wp-config.php, this will disable [automatic staging site detection](https://wpfusion.com/documentation/faq/staging-sites/#automatic-staging-site-detection)
* Improved performance with checking permissions on a post based on access rules configured on a taxonomy term
* Fixed MemberPress transaction expiration dates not syncing when manually edited on the transaction page
* Fixed `apply_tags` parameter in ThriveCart success URL not working for existing users
* Fixed first and last name not being synced for new SureCart customers
* Fixed `PHP Warning:  Undefined array key` when configuring a Remove Tags action on a SureCart product
* Fixed a fatal error `Cannot access offset of type string on string` when updating an ActiveCampaign contact's email to the address of an already existing contact

= 3.41.45 - 11/20/2023 =
* Added support for [Event Tracking with FluentCRM](https://wpfusion.com/documentation/event-tracking/fluentcrm-event-tracking/) (REST API and same site)
* Added support for [webhooks in Mailchimp journeys](https://wpfusion.com/documentation/webhooks/mailchimp-webhooks/#webhooks-in-journeys)
* Added support for syncing multi-part fields (like Name and Address) with Formidable Forms
* Added support for the Gravity Perks Product Configurator addon
* Improved - Give donation forms will now default to being enabled for sync, unless the form is specifically set to Disabled
* Improved - When creating a Salesforce contact, lead, or other object, any missing required fields will be set to `-` to prevent an API error
* Improved - The inline scripts to handle conditional logic on Gravity Forms form fields will now only be loaded if the form uses [WP Fusion's conditional logic](https://wpfusion.com/documentation/lead-generation/gravity-forms/#form-field-conditional-logic) (thanks @karlemilnikka)
* Fixed custom fields not syncing with WP Event Manager since WPEM Registrations v1.6.18
* Fixed PHP notices in the Simply Schedule Appointments integration
* Fixed import tool not loading more than 1000 contacts with Mailchimp

= 3.41.44 - 11/13/2023 =
* Added a status metabox to FooEvents tickets showing the contact ID, with a link to re-sync the ticket to the CRM
* Added support for syncing to date + time fields with FluentCRM (same site and REST API)
* Improved - You can now use "less" and "greater" instead of < and > in the `user_meta_if` shortcode attributes
* Improved - Split MemberPress "Order Total" field into "Subscription Total" and "Transaction Total"
* Fixed WP Event Manager integration not loading since WPEM Registrations v1.6.18
* Fixed an empty Phone and/or Company field on the FooEvents attendee form erasing the Phone and Company provided in the billing information
* Fixed tags not being applied on LearnDash quiz pass or fail since v3.41.35
* Fixed a fatal error `Call to a member function get_tags() on null` following a Gridpane magic login link when Login Tags Sync was enabled
* Developers: All ConvertKit API calls will now be signed with WP Fusion's `integration_key`

= 3.41.43 - 11/6/2023 =
* Added setting Link with Tag - Affiliate Activation to the [Solid Affiliate integration](https://wpfusion.com/documentation/affiliates/solid-affiliate/)
* Added Order Total, Transaction Status, and Subscription Status fields [for sync with MemberPress](https://wpfusion.com/documentation/membership/memberpress/#additional-memberpress-fields)
* Added View in CRM links with Klaviyo
* Improved - The Last Course Progressed field with LearnDash will now be cached and only synced when it changes (rather than every time a course step is completed)
* Improved - With ActiveCampaign, if a duplicate record error is encountered while adding a contact, WP Fusion will attempt to look up the contact by email address and update the contact instead
* Improved - With Klaviyo, if a duplicate record error is encountered while adding a contact, WP Fusion will instead update the existing contact rather than throwing an error
* Fixed dates synced to ActiveCampaign not respecting the account date format since 3.41.36
* Fixed PHP warning `Undefined variable $did_it` when unenrolling a user from a MemberPress membership using a linked tag
* Fixed broken link to Enable Month View Cache setting in the Tribe Events integration
* Fixed error with Brevo `Error while applying lists: listIds should be type array` when applying lists and a user already had one or more of those lists
* Fixed PHP warning saving AffiliateWP affiliates on AffiliateWP versions below 2.13.0
* Fixed a fatal error resetting the WP Fusion settings with ActiveCampaign after enabling Deep Data (`Call to method get_connection_id() on non object`)

= 3.41.42 - 10/30/2023 =
* Added options for applying tags to AffiliateWP affiliates [based on status](https://wpfusion.com/documentation/affiliates/affiliate-wp/#applying-tags)
* Added option to auto-activate and deactivate AffiliateWP affiliates using a linked tag
* Added `awp_affiliate_status` field for sync with AffiliateWP
* Fixed - With WooCommerce Subscribe All the Things, the tags for "Apply tags when subscribed" will no longer be applied when the product is configured as a "One-off subscription"
* Fixed auto-login links not working with FluentCRM (REST API) since 3.41.19
* Fixed deprecated JavaScript console messages
* Fixed Pipedrive error "Name must be given" when updating a contact and the name field was not present
* Fixed an infinite loop on login with the SUMO Subscriptions plugin
* Developers: You can now return `0` from the []`wpf_get_user_id` filter](https://wpfusion.com/documentation/filters/wpf_get_user_id_filter/) to prevent WP Fusion from looking up a user ID based on contact ID
* Developers: The `wpf_get_user_id` filter will now only run before the database query, instead of both before and after

= 3.41.41 - 10/23/2023 =
* Added a [GeoDirectory integration](https://wpfusion.com/documentation/other/geodirectory/)
* Added support for syncing optin statuses with Infusionsoft and Keap
* Added WhatsApp field for sync with Brevo
* Added a FooEvents Tickets batch operation
* Fixed + getting prepended to WhatsApp numbers with Brevo and causing sync errors
* Fixed inverted timezone offset for syncing date fields with HubSpot
* Fixed LearnDash course field mappings (still) not properly saving since 3.41.36
* Fixed error `date(): Argument #2 ($timestamp) must be of type ?int, string given` with PHP 8+ and Brevo when syncing date fields
* Fixed a fatal error `Call to a member function get_tags() on null` following a Gridpane magic login link when Login Tags Sync was enabled
* Developers: When processing a WooCommerce subscription again via the admin sidebar, the `wpf_woocommerce_product_subscription_active` and `wpf_woocommerce_product_subscription_inactive` hooks will now fire, so addons (like WCS Gifting) are triggered

= 3.41.40 - 10/18/2023 =
* Improved - If "Enable Admin Bar" is un-checked in the settings, the WP Fusion admin bar item to refresh tags will also be hidden in the admin
* Fixed FooEvents integration not applying tags to event attendees with the same email address as the WooCommerce customer since 3.41.39
* Fixed `ld_last_course_progressed` field not syncing when it was only enabled globally and not on specific LearnDash course
* Fixed tags not being applied for variable EDD downloads with a price ID of 0
* Developers: You can now return `0` from the []`wpf_get_user_id` filter](https://wpfusion.com/documentation/filters/wpf_get_user_id_filter/) to prevent WP Fusion from looking up a user ID based on contact ID
* Developers: The `wpf_get_user_id` filter will now only run before the database query, instead of both before and after

= 3.41.39 - 10/16/2023 =
* Added a [FluentBooking integration](https://wpfusion.com/documentation/events/fluentbooking/)
* Improved - Added a notice in the logs when a FooEvents checkout creates a processing order, but FooEvents is configured to only create tickets for Completed orders
* Fixed Test Connection button not working with Mailchimp during initial setup since 3.41.19
* Fixed custom attendee fields not syncing with FooEvents since 3.41.36
* Fixed WooCommerce Subscriptions Trial End Date field being synced as 0 for subscriptions with no trial period
* Fixed fatal error loading meta from ActiveCampaign when two fields in a row were in the multiselect format, since 3.41.36
* Fixed shortcode attributes added to the block editor getting saved with curly quotes

= 3.41.38 - 10/11/2023 =
* Fixed LearnDash course progress field mappings not saving since 3.41.36
* Developers: Moved WooCommerce refund actions to the `woocommerce_order_fully_refunded` hook for compatibility with the [WooCommerce Subscriptions - Cancel on Refund extension](https://github.com/woocommerce/woocommerce-subscriptions-cancel-on-refund)

= 3.41.37 - 10/10/2023 =
* Improved - The WooCommerce order notes will now indicate when an order was manually re-processed by an admin
* Fixed LearnDash quiz score field mappings not saving since 3.41.36

= 3.41.36 - 10/9/2023 =
* Added support for [LearnDash quiz question categories](https://wpfusion.com/documentation/learning-management/learndash/#quizzes)
* Added support for [event check-ins with FooEvents](https://wpfusion.com/documentation/events/fooevents/#event-check-ins)
* Added a [Parent and Student Access for LearnDash integration](3)
* Added an integration [with Formidable Forms payments](https://wpfusion.com/documentation/lead-generation/formidable-forms/#payments) (trigger WP Fusion actions based on payment status)
* Added support for checkbox fields with Salesforce
* Improved - Tickets imported with the FooEvents importer will now sync to the CRM
* Improved - FooEvents attendees will now be synced when tickets are created or updated, instead of based on the WooCommerce order
* Improved - Updated the `add`, `update`, and `load` contact methods with ActiveCampaign to use the new v3 API (should be faster)
* Fixed missing Reset Deep Data checkbox on the advanced tab (with ActiveCampaign)
* Fixed `wpf_get_user_id()` picking up the temporary IDs of auto-login users instead of users' actual IDs
* Developers: the `wp_fusion_init` hook has been moved from `init` priority `0` to priority `6` so that `wp_fusion()->crm->init()` has finished

= 3.41.35 - 10/4/2023 =
* Fixed `wpf_get_user_id()` function returning an empty result since 3.41.33
* Fixed fatal error clicking Process WP Fusion Actions Again on a single WooCommerce subscription since 3.41.33
* Fixed WPForms integration syncing date fields as text instead of dates
* Fixed contact ID lookup with Constant Contact failing for deleted contacts

= 3.41.34 - 10/2/2023 =
* Fixed fatal error loading WooCommerce Subscriptions subscription status meta box on stores not using HPOS since 3.41.33

= 3.41.33 - 10/2/2023 =
* Re-added support for syncing empty dates with HighLevel (bug was fixed on their end)
* Improved settings layout on Paid Memberships Pro membership levels
* Improved - Adding logging to Groundhogg, FluentCRM, and FunnelKit when the API request is being blocked by a CloudFlare challenge page
* Improved performance when looking up a user ID from a contact ID
* Improved peformance for the LearnDash Course Progress Meta batch operation
* Improved FluentCRM (REST API) error logging
* Improved logging for invalid contact IDs or email addresses being used to start an auto-login session
* Improved - If an Elementor form with a WP Fusion action is submitted and no email address field is mapped with the CRM, the first email field on the form will be used as a fallback
* Improved Constant Contact error handling
* Fixed API error with Constant Contact when applying tags and the user already had one of those tags
* Fixed access denied redirects not working on individual LearnDash lessons
* Fixed LearnDash Course Progress Meta batch operation not syncing data for courses the user was not currently enrolled in
* Fixed WP Fusion status metabox missing on WooCommerce Subscriptions 5.0+
* Fixed Gravity Forms Entry Date field being synced as text not a date
* Fixed a bug with HubSpot, MailerLite, and HubSpot whereby auto-login links were not being properly detected on the initial page load (only on the second page load)
* Fixed Paid Memberships Pro membership level fields not being synced to the CRM when the user was auto-enrolled into the membership via a linked tag
* Fixed form feeds with no fields mapped sending an empty payload to the CRM for logged-in users

= 3.41.32 - 9/26/2023 =
* Fixed tags not loading with ConvertKit since 3.41.31

= 3.41.31 - 9/25/2023 =
* Added "Hide checkbox if consented" setting with [WooCommerce](https://wpfusion.com/documentation/ecommerce/woocommerce/#email-optins) and [Easy Digital Downloads](https://wpfusion.com/documentation/ecommerce/easy-digital-downloads/#email-optins): if the customer has previously consented to marketing, the email optin box can be hidden
* Improved performance for syncing LearnDash course progress with the CRM
* Improved - The "Admin Permissions" option (only users with `manage_options` can access the WP Fusion settings) will now also apply to the WooCommerce product panel
* Improved - With Brevo, the keys `name` and `id` will now show as reserved and can't be used for event tracking
* Fixed fatal error accessing settings page when using AffiliateWP versions below 2.13.0
* Fixed linked tags not auto-enrolling users into AffiliateWP groups
* Fixed typo in FooEvents SKU field, was `skuu` and is now corrected to `sku`
* Fixed fatal error syncing Event Espresso attendees to the CRM when registrations were tied to a deleted event
* Fixed new tags added to ConvertKit creating a fatal error when they were loaded onto a WordPress user since 3.41.16
* Fixed warning `FLUENTCRM_SKIP_TAG_SYNC already defined` when applying tags since 3.41.29, with FluentCRM (same site)
* Developers - Email optin consent checkboxes with WooCommerce and Easy Digital Downloads will now be saved as a timestamp instead of `true`
* Developers - Deprecated filter `learndash_settings_fields_wpf` in favor of `learndash_course_settings_fields_wpf` and `learndash_quiz_settings_fields_wpf`

= 3.41.30 - 9/18/2023 =
* Fixed modified tags adding the user as an AffiliateWP affiliate since 3.41.29

= 3.41.29 - 9/18/2023 =
* Added [order status tagging with Easy Digital Downloads](https://wpfusion.com/documentation/ecommerce/easy-digital-downloads/#order-statuses)
* Added Link with Tag setting for AffiliateWP groups
* Added Link with Tag setting for Solid Affiliate groups
* Added Event SKU field for sync with FooEvents
* Improved - Added logging when tags are removed from a user due to switching out of a MemberPress membership level on which Remove Tags was checked
* Improved - The HighLevel integration will now also attempt to refresh the access token when it encounters a 401 response
* Improved tags select UI when using the block editor— now supports typing in new tags, tag groups, refreshing tags via an API call, and creating tags via an API call
* Fixed product tags not being applied with FunnelKit when an upsell product was added to the order after the order had been completed by the Forcefully Switch Order Status feature
* Fixed Jan 1st 1970 being synced to the CRM for courses where a user was not enrolled or had free access when running a LearnDash progress meta batch operation
* Fixed Event Espresso registration status changes not being synced to the CRM for registered users (was working for guests)
* Fixed Blockli Streamer integration not detecting the current user to apply tags to
* Fixed accepted WooFunnels upsells no longer triggering Enhanced Ecommerce
* Fixed multi-key/value events accidentally enabled for Groundhogg (REST API)
* Fixed tags applied in FluentCRM (Same site) being immediately synced back to WP Fusion
* Fixed tags applied by FluentCRM automations that were triggered by tags applied by WP Fusion not being synced back
* Fixed fatal error `Argument #1 must be of type array` in PHP 8.1 when a form submission did not apply any tags
* Developers: added filter `wpf_hubspot_redirect_uri`

= 3.41.28 - 9/11/2023 =
* Added a [Blockli Streamer integration](https://wpfusion.com/documentation/membership/blockli-streamer/)
* Improved - If custom fields are enabled for sync with Event Espresso, the custom fields from the primary registrant will be synced to any attendees who didn't specify custom fields
* Improved - When activating a Profile Builder account via activation link, WP Fusion will now wait until the `wppb_activate_user` hook (instead of `user_register`) to ensure any custom field data has been saved
* Fixed un-mapped Elementor form fields being synced with empty keys and causing validation errors with Keap / Infusionsoft
* Fixed WooFunnels offer accepted tags not being applied for guest checkouts with the most recent WooFunnels update
* Fixed Event Espresso Registration Status field not being synced to the CRM when a registration status was changed in the admin
* Fixed an issue with activating Profile Builder accounts via activation link and W3 Total Cache: the user cache will now be cleared before syncing data to the CRM
* Fixed Ultimate Member radio fields being synced as single-item arrays instead of strings
* Fixed PHP warning `Undefined property: stdClass::$tags` when loading an ActiveCampaign contact who had no tags

= 3.41.27 - 9/5/2023 =
* Added Source, Time Zone, and Company Name fields for sync with HighLevel
* Improved - If billing information is enabled for sync with Event Espresso, the billing info from the primary registrant will be synced to any attendees who didn't specify an address
* Improved - New custom field created in Groundhogg (same site) will now appear immediately in WP Fusion (no Resync Fields required)
* Improved - Event Espresso registrations which aren't synced because they aren't the primary attendee on the transaction will now be flagged `wpf_complete` so they no longer show up in the batch export
* Fixed unhandled error when looking up a contact by email address failed during a guest checkout or registration
* Fixed fatal error syncing array-formatted data with WS Form on PHP 8.1+
* Fixed date fields configured for sync on individual EDD recurring downloads being set to `text` format by default instead of `date`
* Fixed duplicate Phone Number field in HighLevel integration
* Fixed not being able to manually test Salesforce webhooks by appending `&contact_id=` to the webhook URL
* Fixed SureCart integration not saving tags with spaces or special characters in them on the product configuration
* Fixed auto login not working with Mailchimp since 3.41.19

= 3.41.26 - 8/23/2023 =
* Improved - Updated to support the new FunnelKit Automations REST API namespace (`autonami-app` instead of `autonami-admin`)
* Improved - The "Enable API Queue" setting will be hidden when connected to Groundhogg (Same site), as it's unnecessary when not sending API calls
* Improved logging when attempting to add a contact to the CRM and no fields are enabled for sync
* Fixed tag search not working with SureCart
* Fixed User Created benchmark not being triggered when WP Fusion created a contact from a WordPress user with Groundhogg (same site)
* Fixed Email not enabled for sync by default when using the Staging CRM
* Fixed tagging not working when connected to the Staging CRM

= 3.41.25 - 8/14/2023 =
* Added option to [use a multi-select instead of static lists with HubSpot](https://wpfusion.com/documentation/crm-specific-docs/how-lists-work-with-hubspot/#using-a-multiselect-for-segmentation)
* Added support for tagging based on [offline donations with GiveWP](https://wpfusion.com/documentation/ecommerce/give/#offline-donations)
* Added `order_id` field for sync to the FooEvents attendee data
* Fixed Elementor Forms field mapping not showing when no tags were specified, since 3.41.24
* Fixed protected menu items not being hidden in BuddyBoss / BuddyPress groups
* Fixed tags not applying via AJAX for auto-login users since 3.41.22
* Fixed `Invalid argument supplied for foreach()` warning when submitting an Elementor form that applies tags but doesn't sync any fields
* Developers: added optional `remove_tags` parameter to `WPF_Forms_Helper::process_form_data()` args

= 3.41.24 - 8/7/2023 =
* Improved - Refactored Elementor Forms integration. Now easier to use and better performance in the admin
* Fixed bug in HighLevel error handling, errors with a `meta` parameter were triggering a fatal error when logged
* Fixed empty dates synced to HighLevel getting converted to 1-1-1970
* Fixed empty dates synced to ActiveCampaign causing an error
* Misc. HighLevel bugfixes
* Added function `wpf_validate_phone_number()`

= 3.41.23 - 7/31/2023 =
* Added support for [multi-value lookup fields with Dynamics 365](https://wpfusion.com/documentation/crm-specific-docs/dynamics-365-associating-entities/#multi-value-lookup-fields)
* Added option to sync with Cases instead of Contacts with Dynamics 365
* Added WP Fusion status column and filters [to the MemberPress transactions list](https://wpfusion.com/documentation/membership/memberpress/#transaction-management)
* Added WP Fusion status section to the MemberPress single transaction page
* Improved - With WordPress 6.3+ the [Bento tracking script](https://wpfusion.com/documentation/tutorials/site-tracking-scripts/#bento) will now be loaded `async` by default
* Improved - The View in CRM links in the logs and elsewhere now work when using custom object types (with CRMs that support custom object types)
* Improved - The Bento tracking script will now wait for the `bento:ready` event
* Fixed warning `Attempt to read subscription_id on bool` when loading the Paid Memberships Pro start date or expiration date for a user who doesn't have any memberships
* Fixed event tracking not sending the value (i.e. `details`) to Brevo
* Fixed MailerLite (Classic) integration giving an Unauthorized error when applying groups via a batch operation
* Fixed FluentCRM (same site) processing tags modified by automations before tags edited on the subscriber record, causing the WP user's tag cache to lose the tags applied by the automation

= 3.41.22 - 7/24/2023 =
* Improved - Contacts synced to Ontraport with `bulk_sms` set to no will also be synced with `force_sms_opt_out` set to true, to ensure they are opted out of bulk SMS
* Fixed empty dates syncing to ConvertKit as 1/1/1970
* Fixed ConvertKit integration removing times from date + time fields
* Fixed the Give donation count and total donated not updating when a recurring donation payment was received
* Fixed MailerLite API URL not accessible via `wp_fusion()->crm->api_url` until after the `init` hook, since 3.41.19
* Developers: Removed `_nopriv` endpoint for applying and removing tags, increases security (and didn't work for logged out users anyway)

= 3.41.21 - 7/18/2023 =
* Improved - The AJAX endpoint for applying tags can now accept form-encoded or JSON encoded data
* Improved - If a MemberPress subscription is cancelled, Remove Tags is checked, and the user still has another active subscription to a membership that applies the same tags, [those tags will not be removed](https://wpfusion.com/documentation/membership/memberpress/#concurrent-subscriptions)
* Improved support for detecting email address changes from non-supported plugins (i.e. JetEngine Forms)
* Fixed Klaviyo integration not loading more than ten lists
* Fixed - Calling `sfwd_lms_has_access()` inside the `wpf_user_can_access` filter created an infinite loop since LearnDash 4.7.0
* Fixed warning `Invalid argument supplied for foreach()` when updating a user's profile via Fluent Forms and BuddyPress was active but no XProfile fields existed
* Fixed Filter Queries - Advanced not working with Search & Filter Pro (changed priority from 10 to 100)
* Developers: added filter `wpf_dynamics_365_lookup_field`

= 3.41.20 - 7/11/2023 =
* Fixed fatal error `Uncaught TypeError: method_exists(): Argument #1 ($object_or_class) must be of type object|string` when first installing WP Fusion, since 3.41.19

= 3.41.19 - 7/11/2023 =
* Added option with Dynamics 365 to sync with Leads instead of Contacts
* Added support for setting the `bulk_mail` and `bulk_sms` fields to Transactional Only with Ontraport
* Improved - The Defer Until Activation setting with WP Members now also works when a user confirms their email address
* Improved - the WP Fusion section on the admin user profile will now require the `edit_users` capability instead of `manage_options`
* Improved - Activating or deactivating a license key will reset license update check lock
* Improved - The GetResponse integration can now trigger autoresponders when a contact is added to a list
* Improved - The GetResponse integration can now remove tags
* Improved HighLevel error reporting for duplicate contact errors
* Fixed Formidable Forms integration not syncing `0` value fields
* Fixed `user_can_access()` against a specific user ID always returning true if the current user is an admin and Exclude Administrators is checked, since 3.41.18
* Fixed dynamic lists showing as options for import with Dynamics 365
* Developers: CRMs with an `init()` method will now run on the `init` action at priority 5, instead of `plugins_loaded`. This makes it easier for custom code added to the theme's `functions.php` to modify CRM parameters such as the API URL or object type

= 3.41.18 - 7/3/2023 =
* Improved - Moved EDD email optin checkbox to the bottom of the checkout form, instead of the Personal Details section
* Improved - Fluent Forms integration module will now be set to enabled by default in the Fluent Forms general settings
* Improved - With WooCommerce Memberships, if a user's membership plan is deleted, and Remove Tags is checked on the plan settings, the Active Member tags will be removed
* Updated Fluent Forms user registration hook from `fluentform_user_registration_completed` to `fluentform/user_registration_completed`
* Fixed inaccurate group counts and pagination when hiding BuddyPress / BuddyBoss groups using the Filter Queries setting
* Fixed first API call after refreshing a HighLevel access token being treated as an error
* Fixed error "Can only be array or string." when removing tags with HighLevel since 3.41.14
* Fixed Constant Contact integration not loading more than 50 tags
* Fixed a fatal error trying to access the CRM PHP API before it was loaded
* Fixed Expiration Date field with MemberPress syncing the date of the next scheduled subscription payment instead of the date of the end of the trial, when using free trials
* Fixed a fatal error calling `wp_fusion()->user->push_user_meta()` on an invalid user ID
* Fixed - the `wpf_admin_override` filter did not apply to the core `user_can_access()` function

= 3.41.17 - 6/26/2023 =
* Tested for Fluent Forms v5.0
* Tested for LearnDash 4.7.0
* Improved styling for locked LearnDash lessons and topics when using the BuddyBoss theme and the [Lock Lessons feature](https://wpfusion.com/documentation/learning-management/learndash/#lock-lessons)
* Improved HubSpot error handling
* Fixed FunnelKit occasionally changing the IDs of tags when they were stored as numbers (tag IDs will now be passed to FunnelKit's UI as strings)
* Fixed restricted BuddyBoss groups not being hidden in the BuddyBoss app when Filter Queries was enabled
* Fixed PHP warnings in SureCart integration
* Fixed PHP warning with BuddyPress groups and Filter Queries since 3.41.17

= 3.41.16 - 6/19/2023 =
* Added option to [subscribe ConvertKit subscribers to a form when applying tags](https://wpfusion.com/documentation/crm-specific-docs/convertkit-unsubscribe-notifications/#re-subscribing-unsubscribed-subscribers)
* Added option to [use a multi-select for segmentation with Zoho](https://wpfusion.com/documentation/crm-specific-docs/zoho-tags/) (instead of tags)
* Added error logging for when a BuddyPress / BuddyBoss group auto-enrollment or un-enrollment fails
* Improved - If a WooCommerce order status is changed from Pending to Cancelled due to non-payment, the refund actions will no longer run (they will only run when a paid order is cancelled)
* Improved - MemberPress subscription status changes from Pending to Cancelled will now be ignored (fixes Cancelled tags being applied during failed initial payments)
* Fixed filter queries not working with BuddyPress / BuddyBoss groups
* Fixed fatal error `Uncaught TypeError: trim(): Argument #1 ($string) must be of type string` when loading empty multiselect fields into BuddyPress / BuddyBoss XProfile fields with PHP 8.1+
* Fixed EDD email optin tags not applying for free download purchases
* Fixed Zoho integration linking users with contact records who had a matching email address on Secondary Email or other email fields than the primary Email field

= 3.41.15 - 6/12/2023 =
* Added a [MemberPress Courses integration](https://wpfusion.com/documentation/membership/memberpress/#memberpress-courses)
* Added a [UsersWP integration](https://wpfusion.com/documentation/membership/userswp/)
* Added support for automatically embedding [the MailerLite tracking script](https://wpfusion.com/documentation/tutorials/site-tracking-scripts/#mailerlite)
* Improved - Added a max-height and scrollbar on the tags list when setting up BuddyBoss member access controls
* Fixed "Lock Lessons" setting not working with LearnDash and BuddyBoss theme in Focus Mode
* Fixed "Exclude Administrators" setting not being respected for the site lockout feature
* Fixed unhandled error refreshing access token with Constant Contact
* Fixed Constant Contact refresh token expiring after two refreshes
* Fixed read-only fields being synced back to Salesforce and causing API errors

= 3.41.14 - 6/5/2023 =
* Improved - With Dynamics 365, dynamic marketing lists will now show as Read Only in the WP Fusion UI, and won't be selectable for "Assign Lists"
* Fixed - If a WooCommerce order item is only partially refunded, the tags applied at purchase will no longer be removed
* Fixed View in CRM links in the logs not working correctly with Dynamics 365
* Fixed Restrict Content Pro memberships batch operation only loading a single user
* Fixed some user roles not auto-enrolling via linked tag into hidden BuddyPress / BuddyBoss groups
* Fixed PHP warning `Undefined index "default"` when configuring BuddyBoss activity access controls
* Fixed custom fields not loading with the v2 HighLevel API
* Fixed custom fields not loading with Maropost
* Misc Maropost bugfixes

= 3.41.13 - 5/30/2023 =
* Added a [Download Manager integration](https://wpfusion.com/documentation/other/download-manager/) for protecting downloadable files
* Added a dotted line around widgets protected by WP Fusion access rules [in the Elementor editor](https://wpfusion.com/documentation/page-builders/elementor/#visibility-indicator)
* Added Company field for sync with GiveWP
* Added a warning when using the Event Espresso Advanced Editor (WP Fusion is currently only compatible with the legacy editor)
* Improved - If an Ultimate Member checkbox field only has a single option, the default field format for WP Fusion will be set to `checkbox` instead of `multiselect`
* Fixed groups not applying with the old MailerLite API since 3.41.11
* Fixed user meta comparison NOT IN not working since 3.41.8
* Fixed Ultimate Member checkbox fields not syncing at all if they were empty
* Fixed warning `Undefined array key "remove_tags"` when processing a WooCommerce subscriptions renewal since 3.41.11

= 3.41.12 - 5/24/2023 =
* Improved - With WooCommerce, "Process order actions again" and the orders export tool will now handle refunded and cancelled orders and apply the tags for those statuses (instead of ignoring them)
* Improved - If a post type or category/term is protected and configured with a redirect, any redirect on the underlying post will now take priority
* Fixed background worker not working on sites protected by HTTP Basic Authentication
* Fixed "Contact with email already exists" error when updating WordPress user profiles with FluentCRM (same site) since 3.41.11
* Fixed Filter Course Steps only running on one `ld_course_steps` meta key per request since 3.41.11 (caused issues with LD sidebars containing multiple course outlines)
* Fixed "Contact not found" being treated as an error with HighLevel integration since 3.41.11

= 3.41.11 - 5/22/2023 =
* Updated HighLevel integration to use the 2.0 API (allows us to add Enhanced Ecommerce and UTM tracking)
* Added "View in CRM" links to HighLevel
* Added [course enrollment date and course enrollment expiry date fields](https://wpfusion.com/documentation/learning-management/learndash/#course-settings-and-auto-enrollment) for sync with LearnDash
* Improved API performance while applying groups with MailerLite
* Improved - Brevo integration will now use numeric contact IDs instead of email addresses as unique identifiers (backwards compatible with users who are still linked via email)
* Improved Maropost error handling
* Improved error handling for failed access token refreshes with Constant Contact
* Improved - Moved Gravity Forms conditional fields logic script to `GFFormDisplay::add_init_script()` (proper way to do it according to Gravity Forms and fixes some compatibility issues)
* Improved - Exported logs will now include the error level string instead of numeric ID
* Fixed email address changes via the Gravity Forms User Registration addon not being synced to the CRM
* Fixed an error whereby if the LearnDash course steps were re-generated on the frontend and saved to `ld_course_steps` while a user was viewing a course with filtered steps, the filtered steps would be saved to the database and the hidden content would be removed from the course (thanks @Jason Ioannides for the fix!)
* Fixed WooCommerce Subscriptions fields not syncing after the customer's initial payment attempt failed, but subsequently succeeded
* Fixed undefined index warning when processing optin forms with FunnelKit and PHP 8.1
* Fixed fatal error in the Custom Code section of the main settings page when a static class method had been attached to a `wpf_*` filter
* Fixed Restrict Content Pro Memberships batch operation not working since a recent RCP update

= 3.41.10 - 5/16/2023 =
* Added support for api.php webhooks with Brevo and FluentCRM
* Fixed global webhooks not working with Brevo since 3.41.9
* Fixed custom args passed to `wp_fusion()->batch->batch_init()` being ignored

= 3.41.9 - 5/15/2023 =
* Added a tool for [exporting historical Fluent Forms entries](https://wpfusion.com/documentation/lead-generation/fluent-forms/#syncing-historical-entries)
* Added an option to apply tags based on assistant to the [Salon Booking integration](https://wpfusion.com/documentation/ecommerce/salon-booking/)
* Improved - [Force killing the background worker](https://wpfusion.com/documentation/tutorials/batch-operations/#cancelling) will now also unlock the process lock so a new operation can be started
* Fixed WP Fusion settings tab not appearing in WP Booking Manager's form settings since WP Booking Manager v2.0
* Fixed active HubSpot lists not available in the dropdown with the WooCommerce auto-apply coupon feature
* Fixed JavaScript bug occasionally blocking render of FunnelKit optin settings in some browsers
* Fixed fatal error connecting to EmailOctopus when there were no lists or tags in the account
* Fixed JavaScript bug on Gravity Forms feed settings that was causing selected CRM lists to get copied into the field mapping
* Fixed error "update webhook received but contact data was not found or in an invalid format" with global webhooks and Brevo (formerly Sendinblue) since 3.41.8
* Fixed import tool not working with Pipedrive

= 3.41.8 - 5/8/2023 =
* Updated Sendinblue integration to Brevo
* Added View in CRM links to Brevo integration
* Added a [Modern Events Calendar Zoom Integration addon integration](https://wpfusion.com/documentation/events/modern-events-calendar/#zoom)
* Added support for multiple parameters using the `IN` operator with the [`user_meta_if` shortcode](https://wpfusion.com/documentation/getting-started/shortcodes/#displaying-content-based-on-user-meta-values)
* Added an `ALL` operator to the [`user_meta_if` shortcode](https://wpfusion.com/documentation/getting-started/shortcodes/#displaying-content-based-on-user-meta-values)
* Added subscription status, start date, end date, and next payment date fields with [EDD Recurring Payments](https://wpfusion.com/documentation/ecommerce/edd-recurring-payments/#syncing-subscription-fields)
* Improved performance for applying and removing lists with Brevo (aka Sendinblue)
* Improved - Brevo integration will now use numeric contact IDs instead of email addresses as unique identifiers (backwards compatible with users who are still linked via email)
* Improved Maropost error handling
* Improved error handling for failed access token refreshes with Constant Contact
* Improved - Moved Gravity Forms conditional fields logic script to `GFFormDisplay::add_init_script()` (proper way to do it according to Gravity Forms and fixes some compatibility issues)
* Improved - Exported logs will now include the error level string instead of numeric ID
* Fixed email address changes via the Gravity Forms User Registration addon not being synced to the CRM
* Fixed an error whereby if the LearnDash course steps were re-generated on the frontend and saved to `ld_course_steps` while a user was viewing a course with filtered steps, the filtered steps would be saved to the database and the hidden content would be removed from the course (thanks @Jason Ioannides for the fix!)
* Fixed WooCommerce Subscriptions fields not syncing after the customer's initial payment attempt failed, but subsequently succeeded
* Fixed undefined index warning when processing optin forms with FunnelKit and PHP 8.1
* Fixed fatal error in the Custom Code section of the main settings page when a static class method had been attached to a `wpf_*` filter
* Fixed Restrict Content Pro Memberships batch operation not working since a recent RCP update

= 3.41.7 - 5/1/2023 =
* Improved - will no longer save an empty `wpf-settings-woo` postmeta value on WooCommerce coupons when no settings had been configured on that coupon
* Fixed tags for price IDs always being removed when an EDD subscription status was changed to non-active (regardless of whether or not Remove Tags was checked) since 3.41.6
* Fixed fatal error loading LearnDash course meta box with PHP 8.1+, for existing courses
* Fixed fatal error displaying tags select dropdown with WP Job Manager and PHP 8.1

= 3.41.6 - 4/24/2023 =
* Added an object type dropdown for Salesforce
* Added HTTP API logging for applying and removing tags with Drip
* Improved - With EDD Recurring Payments, if Remove Tags is checked, [the tags will no longer be removed as soon as the subscription is canceled](https://wpfusion.com/documentation/ecommerce/edd-recurring-payments/#tracking-cancellations), they will be removed once the subscription's access period expires
* Improved - With EDD Recurring Payments, if a cancelled subscription reaches its expiration date, the Expired tags will now be applied at that time
* Improved - WP Fusion will move Gamipress `user_register` actions from priority 10 to 25, so that the contact record has already been created in the CRM and tags can be applied
* Fixed - updated deprecated filter `learndash-lesson-row-class` to `learndash_lesson_row_class`
* Fixed fatal error in CartFlows integration trying to apply upsell tags when no tags had been configured for the main checkout or product
* Fixed error `Argument #2 must be of type array, string given` in Toolset Forms integration with PHP 8+
* Developers - Removed redundant check for `wpf_is_user_logged_in()` in the WP Fusion secure block access logic

= 3.41.5 - 4/17/2023 =
* Improved - With the WooCommerce, GiveWP and EDD email optin checkboxes, existing opted-in customers who don't check the opt-in box will no longer be unsubscribed
* Fixed typo "susbcribed" in default optin status for FluentCRM (REST API)
* Fixed CRM Tags conditional logic dropdown labels on Gravity Forms feeds showing as "undefined"
* Fixed error `Call to a member function get() on null` with LifterLMS in the course builder since 3.41.4
* Fixed `api.php` webhook method not properly looking up user IDs from Drip subscriber IDs (was converting them to integers)
* Fixed fatal error `Call to member function is_edit_mode() on null` when using WooCommerce One-Page Checkout with Elementor

= 3.41.4 - 4/10/2023 =
* Added [Filter Course Steps feature for LifterLMS](https://wpfusion.com/documentation/learning-management/lifterlms/#filter-course-steps)
* Added a [LifterLMS Memberships Meta export tool](https://wpfusion.com/documentation/learning-management/lifterlms/#batch-operations)
* Added "Resubscribe" option with MailerLite— if enabled, and a contact is unsubscribed, they will be resubscribed when they are added to a new group
* Added support for Drip webhooks with the [api.php webhook endpoint](https://wpfusion.com/documentation/other-common-issues/webhooks-not-being-received-by-wp-fusion/#the-async-endpoint-advanced)
* Improved - The LearnDash Locked Lesson text [now works with the BuddyBoss app](https://wpfusion.com/documentation/learning-management/learndash/#lock-lessons)
* Improved - Added a warning when a webhook is received when connected to FluentCRM on the same site (webhooks aren't necessary)
* Improved "Redirect if access is denied" dropdown in admin - Will now only search for pages by title, not content, and will validate external URLs
* Fixed redundant syncing of WooCommerce Subscriptions data after a successful renewal payment when the `sub_renewal_date` field was enabled
* Fixed invalid timestamp error when syncing LifterLMS membership start dates to HubSpot
* Fixed membership level removal actions not working since LifterLMS 6.0
* Fixed Elementor Forms integration treating "1" and "0" as `true` and `false` instead of strings
* Fixed Acceptance field on Elementor Forms not being synced as boolean
* Fixed "Lock Lessons" setting with LearnDash not being respected in the BuddyBoss app
* Improved styling of the WP Fusion metabox when using the Gutenberg editor

= 3.41.3 - 4/3/2023 =
* Added support for AccessAlly 4.0 and up: [WP Fusion can now be used when AccessAlly is in "AccessAlly Managed" mode](https://wpfusion.com/documentation/membership/accessally/#accessally-managed-mode)
* Added [Membership Level Name and Membership Status fields for sync with LifterLMS](https://wpfusion.com/documentation/learning-management/lifterlms/#syncing-meta-fields)
* Improved - When selecting a tag in the AccessAlly tag mapping settings, the row will automatically be enabled for sync, and will be highlighted in green
* Improved - Updated [Sendlane integration](https://wpfusion.com/documentation/installation-guides/how-to-connect-sendlane-to-wordpress/) to use the v2 API (requires generating a new access token)
* Improved - CRMs that use OAuth will now have the refresh token and access token fields disabled in the settings to prevent browsers from accidentally filling the fields
* Improved - When we switched the WP Simple Pay integration to use webhooks in 3.40.52, this broke the integration for Lite users, as well as sites where webhooks are blocked or not configured. WP Fusion will now detect if webhooks are enabled, and if not it will revert to running on the `_simpay_payment_confirmation` hook to ensure data is synced.
* Improved - When updating a user profile in the admin, the log source will say `user-profile` instead of `unknown`
* Fixed custom fields being synced twice when updating an admin user profile with AccessAlly active
* Fixed fatal error during an Easy Digital Downloads guest checkout, when the API call to look up the contact by email address failed
* Fixed fatal error syncing Event Espresso registrations when the event was associated with a deleted venue
* Fixed a fatal error if the WP Fusion settings were reset in the middle of a batch operation
* Fixed an infinite redirect if a guest tried to access a piece of protected content and "Refresh tags if access is denied" was enabled on that content

= 3.41.2 - 3/27/2023 =
* Added [Ortto CRM integration](https://wpfusion.com/documentation/installation-guides/how-to-connect-ortto-to-wordpress/)
* Added option to [completely remove all WP Fusion settings](https://wpfusion.com/documentation/tutorials/switching-crms/#2-reset-wp-fusion) from the database when resetting the main settings page
* Added new scopes to HubSpot integration: timeline, crm.objects.custom.read, and crm.objects.custom.write
* Improved - Removed redundant `name` and `email` custom fields with Bento
* Improved - Thrive Leads and Thrive API integrations will no longer be loaded on versions of Thrive Dashboard less than 3.30.0, to prevent errors with older versions
* Fixed - Pipedrive integration will now use a private app instead of a public app, which will allow setup to complete (previously we were pending a public app review for several months)
* Fixed redirect URI mismatch error with HubSpot (since HubSpot scopes were updated on March 24th)
* Fixed Klaviyo integration sometimes loading "Unknown List" entries from subscribers in cases of deleted or system lists
* Fixed link to edit subscriber in Bento not available immediately after creating a new subscriber
* Fixed GMT calculation with HubSpot dates adding the GMT offset when it should have been subtracing it
* Fixed not being able to sync `active` and `unsubscribed` text values to set the [opt-in status with MailerLite](https://wpfusion.com/documentation/crm-specific-docs/mailerlite-double-opt-ins/)
* Fixed error `Argument #1 ($str) must be of type string, array given` with Gravity Forms when syncing array-formatted data (multiselects, multi-checkboxes, etc)

= 3.41.1 - 3/20/2023 =
* Added Subscription Trial End date field for sync with WooCommerce Subscriptions
* Improved - If a WooCommerce order is changed to Cancelled, the tags applied at purchase will now be removed (same as if it were refunded). The "Refunded" tags will still only be applied if the order is actually refunded
* Improved - Overhauled FunnelKit (WooFunnels) integration. Should now be more reliable with Asynchronous Checkout and syncing order data at the correct time
* Fixed the Defer Until Activation setting with Ultimate Member not working when using the Limit User Roles setting in WP Fusion
* Fixed Event Tracking not working with Groundhogg (This Site)
* Fixed Event Tracking updating the most recent event instead of creating a new one with Groundhogg (REST API)
* Fixed "Action Failed" error when flushing the logs when WooCommmerce's logging was active in database mode

= 3.41.0 - 3/13/2023 =
* Added a [WooCommerce Product Options integration](https://wpfusion.com/documentation/ecommerce/woocommerce-product-options/)
* Added option to [refresh a user's tags when access is denied](https://wpfusion.com/documentation/getting-started/working-with-tags/#when-access-is-denied)
* Added an [Object Sync for Salesforce integration](https://wpfusion.com/documentation/other/object-sync-for-salesforce/)
* Added automatic detection of custom profile fields with WooCommerce Memberships
* Improved - The API Queue will now run on the `shutdown` hook at priority -1 instead of 1, to try and get ahead of any potential redirects in WooCommerce payment gateways
* Improved - With CRMs that support identifying a visitor to a tracking script via JavaScript, a visitor can now be identified in the same page load as a form submission (instead of requiring a redirect or refresh)
* Improved - The "Select a redirect" box in the main WP Fusion metabox can now accept a page or a URL
* Improved - You can now set the `WPF_MULTISITE_PREFIX_KEYS` in wp-config.php to turn on [blog ID prefixes for usermeta keys](https://wpfusion.com/documentation/faq/multisite/)
* Improved - Moved Push All setting to Advanced settings tab
* Fixed - Updated to support latest versions of Thrive Architect, Leads, and Automator (3.18, 3.16, and 1.9 respectively)
* Fixed - In some cases a FunnelKit order would still be pending when the asynchronous checkout script was loaded, causing it not to fire. We'll now enqueue the async checkout script for pending orders as well
* Fixed missing tag labels in Thrive Automator with some CRMs
* Fixed View in CRM link showing on Gravity Forms entries for CRMs that did not support linking directly to the contact's record
* Fixed loading the metadata from the CRM resetting a user's wpForo usergroup to the default
* Fixed fatal error loading the WP Fusion MemberMouse settings page since MemberMouse 

= 3.40.59 - 3/7/2023 =
* Fixed content not unlocking when an initial auto-login URL was visited, since 3.40.57
* Improved - The tag select option on the Messages settings tab with BuddyBoss' member access controls will now be limited to the first 100 tags, to prevent out of memory errors
* Fixed import tool not working with Salesforce picklist-based segmentation

= 3.40.58 - 3/6/2023 =
* Added a [Pretty Links integration](https://wpfusion.com/documentation/affiliates/pretty-links/) for tracking link engagement in your CRM
* Added a [ThirstyAffiliates integration](https://wpfusion.com/documentation/affiliates/thirstyaffiliates/) for tracking link engagement in your CRM
* Added a [WP All Import integration](https://wpfusion.com/documentation/other/wp-all-import/) for syncing generated passwords with your CRM
* Added support for Time fields with Gravity Forms
* Improved - When changing the tag type with Salesforce, all users will automatically have their tags resynced from the CRM
* Improved - When using a Picklist for Salesforce tags, if an unknown is loaded for a user, it will be added to the dropdown of available options
* Improved - The Bento tracking script will now be registered via `wp_enqueue_script()` instead of inline, to play better with caching and optimization plugins
* Fixed Enhanced Ecommerce data not syncing with FunnelKit when an upsell offer was rejected and Asynchronous Checkout was enabled
* Fixed Import Users tool not working with Salesforce picklist-based segmentation
* Fixed Salesforce integration not loading more than 2000 available topics
* Fixed fatal error with Push All when other plugins updated user meta fields before WP Fusion had loaded

= 3.40.57 - 2/27/2023 =
* Added a [WooCommerce Gravity Forms Product Add-ons integration](https://wpfusion.com/documentation/ecommerce/woocommerce-gravity-forms-product-add-ons/)
* Added ability to [apply tags when a new topic or reply are posted](https://wpfusion.com/documentation/forums/bbpress/#forums-activity-tracking) in bbPress (and BuddyBoss) forums and topics
* Added support for [updating lookup fields and associated entities with Microsoft Dynamics 365 Marketing](https://wpfusion.com/documentation/crm-specific-docs/dynamics-365-associating-entities/)
* Improved - When an auto-login session loads the tags for someone who has a user account on the site, those tags will also be saved to their local cache
* Improved - Clicking Cancel on a batch operation / export operation will now cancel the next operation in the queue, even if the key is unknown
* Improved - Some plugins sloppily trigger `wp_login` twice during a single login. WP Fusion will now only run on the first instance
* Improved error handling with Dynamics 365
* Improved - Updated Bento, Engage, and Intercom integrations to support upcoming multi-key event tracking
* Fixed HTML in custom field names being displayed in the Contact Fields list
* Fixed the logs recording an error when a webhook was received from ThriveCart
* Fixed typo "susbcribed" in default optin status for FluentCRM (same site) preventing automation emails from sending since 3.40.40
* Fixed Groundhogg (REST API) integration not loading more than 100 available tags
* Fixed custom fields not syncing with S2Member when using a custom database table prefix
* Fixed a fatal error in FooEvents when a contact ID lookup (by email address) resulted in an error
* Developers: If the background worker / exporter goes rogue, [you can now hard cancel everything](https://wpfusion.com/documentation/tutorials/batch-operations/#cancelling) by appending `&wpf-cancel-batch` to the WPF settings page URL

= 3.40.56 - 2/20/2023 =
* Improved - If the MemberDash plugin is active, a warning will be displayed on LearnDash courses about potential conflicts when using two plugins to manage enrollments into the same course
* Improved - The cookie to track Bento guest form submissions will only be set for one hour (instead of one year). This is plenty of time for the Bento tracking script to pick it up, and will make it easier to cache pages for identified guests
* Fixed Order Date field not syncing with RestroPress orders in the Processing status
* Fixed error `Undefined function wpf_render_tag_multiselect()` when editing an event on the frontend using the Modern Events Calendar Front-end Event Submission addon
* Fixed warning with Ultimate Member `Attempt to read property "ID" on null` when registering meta boxes on some post types
* Fixed the ActiveCampaign integration intercepting API errors from other CRMs during the initial connection process

= 3.40.55 - 2/13/2023 =
* Added `wpf-refresh` query string parameter [to force a refresh of the user's tags and/or meta from the CRM](https://wpfusion.com/documentation/getting-started/shortcodes/#via-url)
* Added a setting to [apply a tag when a specific checkout step is completed in a CartFlows flow](https://wpfusion.com/documentation/ecommerce/cartflows/#checkouts)
* Added support for webhooks with the v2 MailerLite API
* Added links to the settings page with MailerLite to view and delete all registered webhooks (for debugging purposes)
* Added direct link to view a subscriber from WordPress when using the new V2 MailerLite API
* Improved - Guest form submissions and checkouts will now pass the guest's email address to the Bento tracking script
* Improved notifications when an error was encountered saving or validating an option in the WP Fusion settings
* Improved - Added error logging when registering a MailerLite webhook fails
* Improved MailerLite error handling
* Improved - Reverted change from 3.40.54, we found a way to resubscribe unsubscribed subscribers with the V2 MailerLite API
* Fixed Thrive Architect integration syncing leads with an empty email address
* Fixed PHP warning syncing the WooCommerce Memberships user memebership fields when the user's membership was not part of a plan
* Fixed WooCommerce Subscriptions Start date syncing as GMT instead of local time
* Fixed error `Cannot access offset of type string on string` when tracking leadsource data and the visitor's `wpf_leadsource` tracking cookie was an empty string
* Fixed Bento integration returning `false` when a contact had no tags, instead of an empty array. Sometimes caused errors with other integrations.

= 3.40.54 - 2/6/2023 =
* Improved - Using the new V2 MailerLite API, if you attempt to create an Unsubscribed subscriber, [no data will be sent to MailerLite](https://wpfusion.com/documentation/crm-specific-docs/mailerlite-double-opt-ins/#v1-vs-v2-apis)
* Fixed - Tags applied for Pending MemberPress subscriptions were not being removed after a successful checkout using a 100% discount
* Fixed WP Simple Pay actions not running when a new subscription was created via SEPA payment
* Fixed Last Course Enrolled field not syncing when a user was added to a LearnDash course
* Fixed fatal `undefined method` errors with Thrive Architect 3.16+
* Fixed fatal error `Call to undefined function bp_get_member_type_key()` when trying to sync a Member Type field from BuddyPress to the CRM, and the Member Types component was disabled

= 3.40.53 - 1/30/2023 =
* Improved - WP Simple Pay integration will also run on the `payment_intent.processing` Stripe event to sync data for pending SEPA payments
* Improved - ActiveCampaign event tracking API calls updated to use `wp_remote_post()` instead of `wp_safe_remote_post()` to fix occasional `A valid URL was not provided` errors
* Improved - After triggering an automated enrollment into a WPForo usergroup, WP Fusion will delete and rebuild the `_wpf_member_obj` cache for the user
* Fixed WooCommerce email opt-in checkbox appearing right-aligned on some themes
* Fixed custom Billing and Shipping fields added via WooCommerce Checkout Field Editor not showing as available for sync
* Fixed fatal error `Cannot use object of type WP_Post as array` with the Tickets Commerce payment gateway (The Events Calendar) when only a single attendee was on the ticket
* Fixed Event Checkin tags not applying with Event Tickets tickets purchased using the Tickets Commerce gateway
* Fixed Event Checkin tags not applying with Event Tickets Plus when attendees were checked in manually via the Event Tickets Plus app
* Fixed missing log entries for updating contacts and applying tags with Event Tickets event check-ins
* Fixed groups not applying for anonymous subscribers when using the new MailerLite API
* Fixed JavaScript based lead source tracking not working with WP Rocket
* Fixed PHP warning `preg_match(): Passing null to parameter #2 ($subject) of type string is deprecated` when syncing a null value to a CRM field since 3.40.52
* Fixed PHP warning `Undefined array key "order_id"` when using asynchronous checkout with PHP 8+

= 3.40.52 - 1/23/2023 =
* Fixed MemberPress sub-account tags not being applied beyond the first user when importing users from a .csv into a corporate account
* Improved - All WP Simple Pay post-payment actions will now be triggered via webhooks from Stripe. This fixes some issues with tags not being applied if the payment success shortcode wasn't present, or if the payment success page was on another site
* Improved status output for batch operations in the console: will now count items processed in the last batch, and available memory is human-readable
* Fixed subscription tag fields not showing when using All Products for WooCommerce Subscriptions
* Fixed Push User Meta / Export Users operations not picking up the MemberPress Corporate Parent Email field
* Fixed LifterLMS course track complete tags not being applied since LifterLMS 7.0
* Fixed some random access keys (like `183e3486`) being treated as scientific notation and converted to 0
* Fixed warning "Attempt to read `cap_key` on bool" when adding or removing roles with a deleted user ID
* Fixed error `Failed to parse XML-RPC request` when syncing HTML in text fields to Infusionsoft / Keap
* Fixed warnings `Return type of X should be compatible with Y` warnings with PHP 8.1 and the XMLRPC library (Infusionsoft/Keap)

= 3.40.51 - 1/17/2023 =
* Added Next Payment Date field for sync with Paid Memberships Pro
* Added a warning to the LearnDash course settings page when Filter Steps is enabled on a course and the Single Page Courses module is enabled in Uncanny LearnDash Toolkit Pro
* Improved - When upgrading or downgrading between grouped MemberPress memberships, tags from the previous level will now be removed if "Remove Tags" is checked on the membership product
* Improved - Moved WP Simple Pay post-payment actions to the `simpay_charge_created` hook to avoid syncing data to the CRM for failed payments
* Improved - The locking transient will no longer be cleared after a failed incoming webhook
* Fixed LearnDash Quiz Score field syncing the quiz points and not the percentage score.
* Fixed automated enrollments into wpForo usergroups not working since wpForo 2.1
* Fixed Paid Memberships Pro Start Date field not syncing correctly since 3.40.50
* Fixed fatal error syncing string-formatted date timestamps to ActiveCampaign with PHP 8.1
* Fixed Import Users tool with Sendinblue not loading more than 500 subscribers
* Fixed Quentn integration treating a Contact Not Found as a serious error
* Fixed fatal error loading WP Fusion feed settings on Paymattic forms since Paymattic v4.3.2

= 3.40.50 - 1/9/2023 =
* Added an integration with [YITH WooCommerce Booking](https://wpfusion.com/documentation/events/yith-woocommerce-booking/)
* Added an integration with the [Virtual Events addon for The Events Calendar](https://wpfusion.com/documentation/events/the-events-calendar-event-tickets/?c#virtual-events)
* Added a [`sync_if_empty` parameter](https://wpfusion.com/documentation/getting-started/shortcodes/#sync-if-empty) to the `user_meta` shortcode
* Improved: the `wpf_update_meta` and `wpf_update_tags` shortcodes will now output an HTML comment to help locating them within a page
* Improved: When syncing an optin from a supported ecommerce plugin [to MailerLite](https://wpfusion.com/documentation/crm-specific-docs/mailerlite-double-opt-ins/#ecommerce-plugins), if the subscriber's status is `active` or `unconfirmed` it will not be changed
* Updated Elementor Forms integration to no longer use deprecated method `add_form_action()` with Elementor Pro v3.5.0+
* Fixed the `wpf_update_meta` shortcode triggering a pull from the CRM multiple times per page load (if used with multiple blank fields)
* Fixed fatal error syncing the membership start date as a member was deleted in Paid Memberships Pro, with PHP 8

= 3.40.49 - 1/3/2023 =
* Added multi-key/value event tracking support for CRMs that accept that kind of data (Bento, Drip, Engage, Gist, Groundhogg, Intercom, Klaviyo, Mailchimp, and Sendinblue)
* Fixed Event Tracking not working with a recent ActiveCampaign API update (not sure exactly when it started)
* Fixed date fields not syncing to FluentCRM (REST API)
* Fixed opt-in status always showing as No when editing an EDD order in the admin, since 3.40.45
* Fixed fatal error syncing the membership expiration date as a member was deleted in Paid Memberships Pro, with PHP 8

= 3.40.48 - 12/27/2022 =
* Added [SureCart integration](https://wpfusion.com/documentation/ecommerce/surecart/)
* Improved - If a user's contact record is deleted, their cached tags will be deleted as well (previously these were left behind and could clutter up the database)
* Fixed fatal error loading the edit order screen in WooCommerce versions below 6.4, since 3.40.45 
* Fixed WooCommerce Orders batch operaton picking up `shop_order_refund` type orders since 3.40.45
* Fixed error merging lead source data into guest checkouts when the cookie contained invalid JSON
* Fixed WPF not running on multiple user registrations in the same request
* Fixed BuddyPress custom profile type fields being synced as the type ID not the name
* Fixed ACF user fields not showing up on the Contact Fields list when User Form or User Role wasn't the first location rule
* Fixed fatal error `Cannot use object of type WP_Post as array` displaying LearnDash course navigation with BuddyBoss theme 2.2.2 and PHP 8

= 3.40.47 - 12/20/2022 =
* Fixed fatal error manually adding a new WooCommerce Subscription via the admin since 3.40.45

= 3.40.46 - 12/20/2022 =
* Added a global setting for the Lesson Locked Text with LearnDash (at Settings >> WP Fusion >> Integrations)
* Fixed fatal error on CartFlows order received page when using Asynchronous Checkout, since 3.40.45
* Fixed an unhandled error refreshing the Mautic access token when the user's API credentials had changed

= 3.40.45 - 12/19/2022 =
* Added [Studiocart integration](https://wpfusion.com/documentation/ecommerce/studiocart/)
* Added support for [WooCommerce High Performance Order Storage ](https://developer.woocommerce.com/2022/09/14/high-performance-order-storage-progress-report/)
* Added [Optin Status field for sync with MailerLite](https://wpfusion.com/documentation/crm-specific-docs/mailerlite-double-opt-ins/) — you can sync a value of `active`, `unconfirmed`, or `unsubscribed` to update the subscriber's status in MailerLite
* Added "skip already processed" option to Event Espresso Registrations batch operation
* Fixed BuddyBoss App Segment integration not working with CRMs which use tag IDs
* Fixed batch operations getting stuck on the last record, since 3.40.44
* Fixed Enhanced Ecommerce settings not showing on Gravity Forms feeds since Gravity Forms PayPal 2.4.0
* Fixed GiveWP settings not saving if no tags were selected
* Fixed GiveWP integration not creating a contact record in the CRM if the donation was made by a registered user who didn't already have a contact record
* Fixed updating a user profile in WordPress marking MailerLite subscribers as Active even if they hadn't been confirmed yet
* Fixed Processed / Not Processed order status filter showing up for all post types
* Developers: Added filters `wpf_edd_customer_data` and `wpf_give_customer_data`

= 3.40.44 - 12/12/2022 =
* Fixed fatal error `json_decode(): Argument #1 ($json) must be of type string, array given` when parsing leadsource tracking data, since 3.40.43
* Added option for default opt-in status when adding new contacts to MailerLite.
* Added option to filter WooCommerce orders based on whether or not they've been processed by WP Fusion

= 3.40.43 - 12/12/2022 =
* Added [Breakdance Builder integration](https://wpfusion.com/documentation/page-builders/breakdance/)
* Added option to apply tags when a user joins a BuddyPress / BuddyBoss group
* Improved lead source tracking — tracking data will now be stored JSON-encoded inside of a single cookie, instead of across multiple cookies
* Improved - Using the `date-format` parameter in the `user_meta` shortcode will now output the date in the site's language (as opposed to English)
* Fixed `timezone-offset` parameter in the `user_meta` shortcode being treated as minutes, not hours
* Fixed fields being synced as empty with MemberPress when an profile was updated and fields set to "Show In Account" were not present on the Account page
* Fixed removing a linked tag not setting the default BuddyPress profile type when the type name didn't match the type key
* Fixed batch operations started on a single multisite showing the status bar on all other sites in the network
* Fixed fatal error displaying a user's tags on the All Users list when a user's tags were stored as a string, with PHP 8
* Fixed mysterious JavaScript error `tipTip is not a function` when editing Gravity Forms feeds on some sites
* Fixed - Moved FluentForms feed processing back to asynchronous (reverted change from 3.40.42)

= 3.40.42 - 12/5/2022 =
* Added [Engage CRM integration](https://engage.so/)
* Improved - Fluent Forms submissions are no longer processed asynchronously (this fixes lead source tracking and the incorrect form URL showing in the logs)
* Fixed BuddyPress custom profile type fields being synced as the type ID not the name
* Fixed automated profile type un-enrollments with BuddyPress / BuddyBoss not working when the profile type key didn't match the post name
* Fixed API errors syncing multiselect or array data with Gist
* Fixed API errors not being logged when updating an existing contact via a form integration
* Fixed un-checked MemberPress checkboxes not syncing from the admin
* Fixed unenrolling a user from a BuddyPress profile type using a linked tag not working when the type key didn't match the `post_name`
* Fixed HubSpot integration using a 120 second timeout
* Fixed PHP warning during Fluent Forms feed processing when no tags were specified to be applied
* Removed German language translation

= 3.40.41 - 11/29/2022 =
* Added Last Order Type field for sync with WooCommerce Subscriptions
* Added German language translation
* Improved - Set a `max-height` on the debug output for HTTP API logging in the logs
* Improved logging for when a BuddyBoss / BuddyPress profile type auto-enrollment fails
* Fixed custom fields not syncing with Klaviyo since 3.40.40
* Fixed logs warning with Groundhogg when a user updated their email address to have capital letters in it
* Fixed Salesforce tags picklist field selection not saving in the admin since 3.40.40
* Fixed Fluent Forms integration syncing empty form fields
* Fixed PHP error `Cannot use string offset as array` when importing users with PHP 8
* Fixed Fatal Error manually activating Staging Mode when using ActiveCampaign's Deep Data integration
* Fixed MailPoet integration listing a user's segments as "unknown lists"

= 3.40.40 - 11/21/2022 =
* Added [Intercom event tracking](https://wpfusion.com/documentation/event-tracking/intercom-event-tracking/)
* Added [Intercom site tracking](https://wpfusion.com/documentation/tutorials/site-tracking-scripts/#intercom)
* Added [Klaviyo event tracking](https://wpfusion.com/documentation/event-tracking/klaviyo-event-tracking/)
* Added option with FluentCRM (REST API and same site) to set the default status for new contacts to either Subscribed or Pending (and trigger a double opt-in email)
* Added support for using CRM tags in Gravity Forms feed conditions
* Added link to edit the contact in the CRM for HighLevel (requires a Resync Contact IDs operation to load the edit URLs)
* Added Previous User Email field for sync (to track email address changes)
* Improved - Updated Klaviyo integration to use the new v2022-10-17 API, which will greatly improve performance
* Improved - Clicking Process WP Fusion Actions Again on a WooCommerce or EDD order, for a registered user, will force lookup that user's contact ID in the CRM (in case it's changed or been merged)
* Improved - Reorganized FooEvents fields in the settings into two groups, Attendee Fields and Event Fields
* Improved - When a WooCommerce User Membership is deleted, the user's status will be synced as cancelled, and the expiration date will be set to the current time
* Improved - When using the MemberPress offline gateway, and "Admin Must Manually Complete Transactions" is enabled, no tags will be applied until the admin has completed the transaction
* Improved - If a contact was created in staging mode, deactvating staging mode will also remove the user's `staging_xxxx` contact ID
* Fixed custom properties created with the core Groundhogg plugin (not the Better Meta extension) not being available for sync
* Fixed error resyncing available fields when the Groundhogg Better Meta plugin was active
* Fixed all phone numbers getting synced to HighLevel with a +1 country code
* Fixed: Removed Drip site tracking code (Drip no longer supports site tracking)
* Fixed being unable to clear out the User dropdown filter in the logs once a user was selected
* Fixed AccessAlly integration not listing more than 1000 available tags
* Fixed Resync Tags and Fields not working with Mautic over OAuth
* Fixed EDD Recurring Payments Statuses batch operation processing subscriptions from newest to oldest
* Fixed Easy Digital Downloads Orders batch operation processing orders from newest to oldest
* Fixed fatal conflict with Thrive Ultimatum
* Fixed fatal error adding new WP Fusion Thrive Leads API connection
* Developers: Added filter [wpf_event_tickets_apply_tags](https://wpfusion.com/documentation/filters/wpf_event_tickets_apply_tags/)

= 3.40.39 - 11/14/2022 =
* Added [Thrive Leads integration](https://wpfusion.com/documentation/lead-generation/thrive-leads/)
* Added OAuth API integration with Mautic (requires settings reset)
* Improved - The tags specified on a WooCommerce product for "Apply tags when initial transaction failed" will no longer be applied during a failed WooCommerce Subscriptions renewal order
* Fixed Defer Until Activation setting with BuddyPress / BuddyBoss causing tags linked to groups not to be applied
* Fixed Gravity Forms conditonal logic scripts getting output when pages were loaded via the REST API (i.e. by Yoast's link indexer)
* Fixed Thrive Automator integration not applying tags with CRMs that use tag IDs
* Fixed Thrive Automator integration not syncing the name field to the CRM
* Fixed WP Fusion integration showing up as an empty box under Thrive Product Manager >> API Integrations
* Fixed Groundhogg (REST API) integration not loading more than 25 available tags
* Fixed fatal error with Thrive Ultimatum `Call to undefined method WPF_Thrive_Autoresponder_Main::get_email_merge_tag()`

= 3.40.38 - 11/10/2022 =
* Fixed notice "Subscription status was changed to active, but the user is not currently subscribed to the product. No tags will be applied." with MemberPress since 3.40.36
* Fixed PHP warning `Undefined variable $code` in Zoho integration when handling an API error
* Fixed users with no tags showing in Users Insights as `a:0:{}`
* Fixed tags select not initializing properly when adding a new variable price option in Easy Digital Downloads

= 3.40.37 - 11/7/2022 =
* Added support for [AffiliateWP referrer tracking](https://wpfusion.com/documentation/other/affiliate-wp/#syncing-referrer-meta-fields) with Fluent Forms
* Improved - With the [WooCommerce Subscriptions Gifting integration](https://wpfusion.com/documentation/ecommerce/gifting-for-woocommerce-subscriptions/), if a separate shipping address was provided by the customer at checkout, the shipping details will be synced to the gift recipient's contact record
* Improved - With CRMs which require an email address for some API calls, WP Fusion will now check to see if the email can be retrieved from a WooCommerce order before making an API call to load the contact (improves performance)
* Fixed both the WooCommerce and WooCommerce Subscriptions applying the same tags on a new subscription order
* Fixed WP Fusion tag select boxes not usable after adding a new price option on a download product with EDD 3.0+
* Fixed membership meta fields not syncing with WooCommerce Memberships when no tags were configured on the membership plan
* Developers: Added filter `wpf_get_email_from_contact_id`
* [Updated Growmatik integration](https://github.com/verygoodplugins/wp-fusion-lite/pull/20)

= 3.40.36 - 10/31/2022 =
* Tested for WordPress 6.1.0
* Fixed Groundhogg syncing new user registrations to Groundhogg before WP Fusion
* Fixed WooCommerce Memberships Status field not syncing when a paused membership was reactivated due to a successful subscription payment
* Fixed LearnDash lessons protected by "Required Tags (Not)" not being protected when the parent course was set to apply tags on course completion
* Fixed WP Fusion overriding conditional visibility controls on Bricks Builder elements
* Fixed Staging Mode checkbox not un-checkable when the site was in staging mode due to detecting a change in the site's URL
* Fixed default profile type not being set with BuddyBoss when a linked tag was removed, when the profile type directory key didn't match the type name

= 3.40.35 - 10/24/2022 =
* Added [Group Leader Email and Group Name fields for sync with the Restrict Content Pro Group Accounts addon](https://wpfusion.com/documentation/membership/restrict-content-pro/#groups)
* Fixed automated enrollments into BuddyBoss / BuddyPress profile types not working when the type `directory_slug` didn't match the type `post_name`
* Fixed group license tags not being applied in Uncanny LearnDash Groups when a user was added to a group via an enrollment key
* Fixed JetEngine integration crashing the Listing Grid widget when Elementor wasn't active
* Fixed WooCommerce Subscriptions integration syncing 0 as the end date for subscriptions with no expiration
* Fixed variable price settings not displaying in Easy Digital Downloads since EDD 3.0
* Fixed WooCommerce Shipment Tracking tracking link not syncing for guest checkouts
* Fixed WooCommerce Shipment Tracking tracking link only syncing custom tracking links, not standard carriers
* Fixed WooCommerce Memberships membership status field not syncing when a subscription was reactivated in the user's frontend account area
* Fixed deprecated method `isSequential()` in WishListMember integration
* Fixed PHP warnings in Maropost integration
* Updated Autonami to FunnelKit Automations
* Developers: Tested for PHP 8.1.9

= 3.40.34 - 10/17/2022 =
* Fixed form field mappings not saving in the admin since 3.40.33

= 3.40.33 - 10/17/2022 =
* Added [Pipedrive CRM integration](https://wpfusion.com/documentation/installation-guides/how-to-connect-pipedrive-to-wordpress/)
* Improved - Exporting the activity logs will now automatically unserialize any serialized data
* Improved - The Refresh Tags and Fields link in the admin bar will now only be shown to administrators (`manage_options`)
* Improved - Dates will be force to UTC for timestamp conversion before beng synced (fixes issues with other plugins calling `date_default_timezone_set()` and messing up the conversion)
* Improved - The tags list in the All Users list now has a max height set, and can be clicked to expand
* Fixed Learnpress course content protection not working since LearnPress 4.1.6.9
* Fixed Filter Queries not working on LearnPress courses
* Fixed the TipTip JS file getting enqueued twice with WooCommerce (made tooltips not automatically close)
* Fixed password resets via Clean Login not being synced to the CRM
* Fixed expiration date not syncing with Paid Memberships Pro when a member was manually added to a level with a custom end date
* Fixed fatal error registering new users in the admin with ACF multiselect repeater data, when BuddyPress was active

= 3.40.32 - 10/20/2022 =
* Fixed access control system broken in 3.40.31

= 3.40.31 - 10/20/2022 =
* Added Refresh Available Tags & Fields button to the admin toolbar
* Added support for the Groundhogg White Label Branding extension
* Improved - If an Elementor element is protected by an invalid tag (for example a deleted tag, or tag from a prior CRM), the element will no longer be hidden
* Fixed - All links to CRM contact records in the logs will now open in a new browser window
* Fixed Gravity Perks Nested Forms getting processed before the parent entry was synced
* Fixed Event Tracking not working with HubSpot
* Fixed deleting an Event Tickets attendee applying the Deleted tags to the user who made the purchase, not the attendee
* Fixed Invalid JSON Response error when editing pages in Gutenberg and Gravity Forms was active
* Fixed fatal error loading the API Connections panel within Thrive Ovation

= 3.40.30 - 10/3/2022 =
* Added support for the Tickets Commerce payment gateways with Event Tickets and Event Tickets Plus
* Added support for guest checkouts with RestroPress
* Added Subscription Price field for sync with Paid Memberships Pro
* Added Event Ticket ID field for sync with Event Tickets Pro
* Improved - The Bento integration will force all email addresses to lowercase, both for syncing and for contact ID lookups
* **Warning** - Bento users - Note that this change will cause WordPress users to become disconnected from their Bento subscriber records if their email addresses in Bento contain capital letters. To fix this, it's recommended to run a Push User Meta operation one time, and WP Fusion will update all your subscriber records in Bento to use lowercase email addresses
* Improved - If Autonami is running on the same site, tag changes will be synced across to WP Fusion immediately, without requiring an API call
* Improved - If a MemberPress transaction expires, and the user has another active transaction to the same product, the Transaction Expired tags will not be applied
* Fixed lists not loading with Dynamics Marketing 365
* Fixed staging mode only auto-activating in the admin of the staging site
* Fixed Preview With Tag not working with tags with apostrophes in the tag name
* Fixed the BuddyPress integration syncing the profile type slug instead of profile type name when a user was added to a profile type via a linked tag
* Fixed being unable to remove a saved tag in the "Apply tags when a product with this term is purchased" setting with WooCommerce
* Fixed the Delivery Address fields not syncing with RestroPress
* Fixed Event Check-in tags not being applied with Event Tickets Plus when the ticket was sold via WooCommerce
* Fixed Attendee Deleted tags not being applied with Event Tickets Plus when the ticket was sold via WooCommerce
* Fixed the MailerLite integration logging an error when a subscriber record isn't found for an email address (should just be an info message)
* Fixed PHP warning syncing the Gender field with BuddyPress when it was empty
* Fixed custom fields not loading with Autonami 2.2.0
* Fixed PHP warning loading usermeta fields from Autonami (same site)
* Fixed unhandled fatal error when a HubSpot access token refresh failed
* Fixed fatal error starting an auto login session with Dynamics 365 when the contact wasn't on any lists

= 3.40.29 - 9/26/2022 =
* Added Asynchronous Checkout support to CartFlows
* Added support for syncing avatar URLs with wpForo
* Improved Asynchronous Checkout support for WooFunnels
* Improved - The HTTP API logs will now be associated with the user who initiaited the API call, instead of "system"
* Fixed address and phone fields not updating with Groundhogg (REST API)
* Fixed wpForo integration not loading since wpForo 2.0
* Fixed Filter Queries - Advanced not working when no post types were specified, since 3.40.28
* Fixed Filter Course Steps with LearnDash not working correctly when steps from multiple courses were listed on the same page
* Fixed error `The entity "ccedil" was referenced, but not declared.` when syncing a country value of `Curaçao` to Infusionsoft
* Fixed fatal error loading the Contact Fields settings tab when the "Remove WooCommerce Billing Address Fields for Free Checkout" plugin was active
* Fixed error `Uncaught Error: Too few arguments to function WPF_WPBakery::shortcode_output()` when using the Accordion shortcode with WPBakery
* Developers: Fixed WPF_Pods::post_save_user() running when editing users in the admin (was intended to be for frontend edits only)

= 3.40.28 - 9/19/2022 =
* Added Asynchronous Checkout support to WooFunnels
* Added "Add to CRM" checkbox on admin Add New User form
* Added as-you-type filter to Preview With Tags admin bar dropdown when there are more than 20 available tags
* Added [event check-in support to Modern Events Calendar integration](https://wpfusion.com/documentation/events/modern-events-calendar/#event-check-ins)
* Added Corporate Account Parent Email field for sync with MemberPress
* Improved - Filter Queries Advanced will now take into account any `post__in` arguments when building up the array of post IDs to exclude (improves performance)
* Improved - Moved MemberPress transaction expiration process from the `mepr-event-transaction-expired` to the `mepr-txn-expired hook`, should be more reliable
* Fixed Filter Queries - Advanced not working on `post` post type when not speficied as the `post_type` in the `WP_Query` arguments
* Fixed un-selected checkboxes not being loaded from HubSpot
* Fixed PHP warning in Users Insights integration when users did not have a contact record
* Developers: When a user's tags are modified, the memory cache at WPF_Access_Control::$can_access_posts will be cleared
* Developers: `wpf_crm_loaded` action will now pass the active CRM as a parameter

= 3.40.27 - 9/15/2022 =
* Fixed WP Fusion overriding Gravity Forms conditional logic for logged in users since 3.40.24

= 3.40.26 - 9/14/2022 =
* Fixed PHP warning marking content complete in LearnDash integration since 3.40.24
* Fixed missing `use_utm_names` flag with Ontraport when updating existing contacts (prevented lead sources from being set)
* Fixed `WPF_WooCommerce::get_contact_id_from_order()` returning an empty contact ID during guest checkout if the order was just placed
* Fixed MemberPress Membership Statuses batch operation not applying Expired tags for free memberships

= 3.40.25 - 9/12/2022 =
* Fixed fatal error `Call to undefined method WPF_Thrive_Autoresponder_Main::get_data_for_setup()` when editing pages with Thrive Architect since 3.40.24

= 3.40.24 - 9/12/2022 =
* Added [Thrive Automator integration](https://wpfusion.com/documentation/other/thrive-automator/)
* Added [Thrive Apprentice integration](https://wpfusion.com/documentation/learning-management/thrive-apprentice/)
* Added [LearnDash Progress Meta batch operation](https://wpfusion.com/documentation/learning-management/learndash/#progress-meta)
* Improved performance when using Filter Queries in Advanced mode and a query is for multiple post types
* Improved staging site automatic detection on hosts that find/replace the site URL throughout the database when copying from live to staging
* Improved and simplified duplicate site and staging site notices in the admin
* Improved - Stopped saving LearnDash course progress to the `wp_usermeta` table, it will now just be synced as needed as users progress through courses
* Fixed `?wpf-end-auto-login=true` query parameter not working
* Fixed PHP warning applying LearnDash lesson attributes on LearnDash versions below 4.2.0
* Fixed BuddyPress / BuddyBoss profile type names not syncing when the type was granted by a linked tag
* Fixed Gravity Forms conditional logic not saving the condition in the admin if you don't click on the operator dropdown first
* Fixed PHP error trying to sync the `order_notes` field with RestroPress
* Fixed PHP warning `undefined array key user_id` in PeepSo integration
* Fixed Advanced Ads integration settings not saving

= 3.40.23 - 9/6/2022 =
* Added integration with Subscriptions for WooCommerce
* Added integration with YITH WooCommerce Checkout Manager
* Improved support for Lock Lessons feature with LearnDash 4.2.0+ (now works in focus mode)
* Improved - When a product is fully refunded from a partially refunded WooCommerce order, the tags applied with that product will be removed, and the refund tags for that product will be applied
* Improved - With WooFunnels and Drip + ActiveCampaign, if an upsell is accepted after the order has been processed by Enhanced Ecommerce, it will be processed again (i.e. the existing invoice will be updated)
* Improved - Made WP Fusion menu item and settings page title able to be white labelled via the `gettext` filter
* Fixed fatal error viewing WooCommerce order received page, with WooFunnels, using an invalid order ID
* Fixed memory leak when using [the_excerpt] shortcode inside a post's main content area
* Fixed Defer Until Activation setting with WP Members not being respected when using the Limit User Roles feature (in the WP Fusion Advanced settings)
* Fixed fatal error `Too few arguments to function WPF_Access_Control::login_redirect()` with some themes since 3.40.21
* Fixed PHP warning during auto-login session when a Return After Login redirect was attempted

= 3.40.22 - 8/29/2022 =
* Added [tag-based conditional logic to Gravity Forms form fields](https://wpfusion.com/documentation/lead-generation/gravity-forms/#form-field-visibility)
* Added support for [syncing custom profile and registration fields with LearnPress](https://wpfusion.com/documentation/learning-management/learnpress/#syncing-meta-fields)
* Added an integration with the [Modern Events Calendar RSVP Addon](https://wpfusion.com/documentation/events/modern-events-calendar/#RSVPs)
* Added latitude and longitude fields for sync with NationBuilder
* Improved - The WooCommerce Subscriptions Statuses batch operation will now retroactively apply the Free Trial Over tag to subscribers who had a free trial
* Improved layout of Gravity Forms feed settings
* Improved styling of EDD order status metabox with EDD 3.0
* Fixed Apply Tags on View functionality not working when "Restrict Content" was disabled in the General settings
* Fixed WP Fusion's access rules sometimes running on content in Elementor's edit mode for non-admin editors
* Fixed connection settings getting overwritten when calling `wp_fusion()->settings->set()` after having switched to another multisite blog
* Fixed Async Checkout sometimes running on pending orders on the Order Confirmed page with WooFunnels
* Fixed fatal error trying to delete import groups that contained `WP_Error`s
* Fixed EDD order status metabox showing incorrect information since EDD 3.0
* Fixed fatal error auto-enrolling users into wpForo usergroups since wpForo 2.0
* Fixed disabling the API queue also disabling staging mode

= 3.40.21 - 8/23/2022 =
* Added Membership Plan Name field for sync with WooCommerce Memberships
* Added Status field for sync with JetPack CRM
* Improved - Moved WPBakery controls to their own settings tab
* Improved method of hiding content with WPBakery
* Improved Return After Login feature, will also run on the `login_redirect` filter for cases where another plugin takes priority over the login redirect on `wp_login`
* Updated to support Paymattic (used to be WPPayForm Pro), and fixed form feeds not saving
* Fixed creating a new BuddyBoss App Access Group based on a tag processing indefinitely
* Fixed JavaScript lead source tracking not working on some hosts (cookie components were being URI-encoded)
* Fixed PHP warning in Memberoni integration
* Fixed Lesson Locked text not showing with LearnDash 4.3.0+
* Fixed wpForo settings page missing since wpForo 2.0
* Fixed WPBakery tag search returning all tags in the UI

= 3.40.20 - 8/17/2022 =
* Added [Holler Box integration](https://wpfusion.com/documentation/other/holler-box/)
* Added Recruiter ID field for sync with NationBuilder
* Addded Avatar URL for sync with FluentCRM (can update the contact's photo by syncing a URL to an image)
* Added error handling for the Sendinblue Sales CRM API
* Improved - Asynchronous Checkout with WooCommerce will set a cron task for one minute in the future to confirm that the order was synced, for cases where the normal async process fails
* Fixed "Converted" tags not being applied when running the EDD Recurring Payments statuses batch operation
* Fixed Required Tags (Not) setting not working with WPBakery
* Fixed tags displaying as IDs after saving a WPBakery element

= 3.40.19 - 8/8/2022 =
* Added Availability, Support Level, Inferred Support Level, Priority Level, Do Not Call, Mobile Opt-In, and Do Not Contact fields for sync with NationBuilder
* Improved - If an EDD Software Licensing license is re-activated, the Expired tags will be removed
* Improved support for syncing user capabilities when using a custom table prefix
* Improved the UI for activating and deactivating the license on the Setup tab

= 3.40.18 - 8/1/2022 =
* Added Voting District fields for sync with NationBuilder
* Improved - MemberPress active tags will now also be applied on the `mepr-event-non-recurring-transaction-completed` hook
* Fixed a bug with automatic discounts and WooCommerce, where if the user's tags made them eligible for multiple discounts, navigating to the checkout page would apply an additional discount even when the cart total was already 0
* Fixed If-So integration not working with CRMs that use tag IDs
* Fixed tags not being applied properly when a LearnDash quiz with essay responses was graded in the admin
* Fixed Clean Login integration not syncing user_login and user_pass
* Fixed fatal error on the Contact Fields tab with WooCommerce Stripe Gateway 6.5.0
* Fixed removing a user role syncing the user's role as the name of the role that was just removed
* Developers: Fixed `wpf_get_setting_{$id}` filter not updating the option inputs on the settings page

= 3.40.17 - 7/25/2022 =
* Added an integration with the BuddyBoss App's [new Access Controls component](https://wpfusion.com/documentation/membership/buddyboss/#access-controls)
* Added support for Filter Queries on the Jet Engine Listing Grid widget
* Added County fields for sync with NationBuilder
* Improved performance when auto-enrolling users into BuddyPress profile types based on tags
* Fixed WP Event Manager integration not syncing registrations when transitioning an attendee from Waiting to Confirmed
* Fixed HubSpot lists showing as "Array" in If-So's Select A Condition dropdown
* Fixed fatal error saving LifterLMS membership plans on PHP 8+, since 3.40.15

= 3.40.16 - 7/19/2022 =
* Fixed error with Jetpack CRM "Jetpack CRM plugin not active" since 3.40.15
* Fixed staging mode not automatically activating when copying to a staging site on WP Engine and Cloudways
* Fixed tags not loading with MooSend
* Fixed PHP warnings in MooSend integration

= 3.40.15 - 7/18/2022 =
* Added support for [WP Event Manager's Sell Tickets Addon](https://wpfusion.com/documentation/integrations/wp-event-manager/)
* Improved - If the logs are disabled, the logs database table will be dropped
* Improved - Groundhogg integration will now log an error when updating a contact's email to an address that is already in use by another contact
* Fixed WP Event Manager integration not syncing registrations added via the admin
* Fixed GiveWP integration not syncing guest donors
* Fixed tags configured on LearnDash groups not being applied when users were self-enrolled in groups via the Uncanny Toolkit Pro Group Sign Up module
* Fixed tags with quotes in them not saving fully on LifterLMS course and membership settings
* Fixed the `read only` HTML flag showing with HubSpot active lists in the Oxygen conditions builder dropdown
* Fixed PHP warning in Contact Form 7 integration when editing the WP Fusion settings and no fields had been added to the form
* Fixed guest registrations not being synced with WP Event Manager 3.1.30+
* Fixed fatal error on WP Fusion settings page when connected to Jetpack CRM and the Jetpack CRM plugin was deactivated
* Developers - Improved: The `validate_field_` filters in the settings will now only run when an option value has changed instead of on every save

= 3.40.14 - 7/12/2022 =
* Improved - Fluent Forms global settings page will now be hidden from the menu since it doesn't do anything
* Fixed tags not being applied to recipient with Gifting for WooCommerce Subscriptions when user_email and billing_email were mapped to separate fields
* Fixed fatal conflict with older WPBakery versions (Uncaught ArgumentCountError)
* Fixed PHP notice in Advanced Ads integration on PHP 8

= 3.40.13 - 7/7/2022 =
* Fixed WooCommerce Subscriptions integration disabled since 3.40.12
* Fixed Mautic ignoring empty fields
* Fixed fatal error loading the WP Fusion PeepSo Groups settings subpage when no groups had been configured

= 3.40.12 - 7/5/2022 =
* Added an integration with [WPBakery Page Builder](https://wpfusion.com/documentation/page-builders/wpbakery-page-builder/)
* Added an integration with [WooCommerce Payments](https://wpfusion.com/documentation/ecommerce/woocommerce-payments/)
* Added Last Topic Completed field for sync with LearnDash
* Improved - If a Gravity Forms User Registration Update feed runs, only the submitted usermeta fields will be synced to the CRM (instead of all fields in the database)
* Improved - The MemberPress Memberships Statuses batch operation will now apply any tags configured via the Corporate Accounts addon when the user is a member of a sub-account
* Fixed Return After Login feature not working since WordPress 6.0
* Fixed the "Require Admin Permissions" setting (Advanced settings tab) not working
* Fixed conflict with "WooCommerce Fattureincloud Premium" when loading the available WooCommerce checkout fields in the admin
* Developers - Changed the EDD update check from a POST to a GET for improved performance

= 3.40.11 - 6/28/2022 =
* Added [Bricks builder integration](https://wpfusion.com/documentation/page-builders/bricks/)
* Improved - If a user registers and has an existing Lead record in Gist, the Lead will be converted to a User
* Fixed custom attendee fields not syncing with Event Tickets Plus v5.5.0+
* Fixed Advanced Custom Fields multi-checkbox fields not syncing when MemberPress was active
* Fixed MemberPress pending tags not being applied for pending transactions
* Fixed Transaction Expired tags not being removed when running a MemberPress Memberships Statuses batch operation
* Fixed MemberPress Transaction Expired tags not being removed when a new transaction was placed for a membership product that was previously expired
* Fixed some HTML and escaping glitches on the WooCommerce product panel upgrade nag with WP Fusion Lite

= 3.40.10 - 6/20/2022 =
* Added support for WP Global Cart (products configured on Site A will now have their tags applied when purchased on Site B)
* Addded an experimental method for setting the lead source tracking cookies on sites like WP Engine and Flywheel which sanitize UTM parameters out of request URIs
* Fixed calls to `wpf_user_can_access()` failing during a webhook (because the user was not logged in, it was assumed they did not have any tags)
* Fixed fatal error adding a new Solid Affiliate affiliate when the `saff_referral_count` field was enabled for sync
* Fixed special characters in Gravity Forms multiselect options appearing UTF-8 encoded when using the Create Tag(s) from Value option

= 3.40.9 - 6/13/2022 =
* Improved - When searching in the Redirect if Access is Denied dropdown in the main WP Fusion meta box, results will by grouped by post type
* Fixed [WooCommerce automatic discounts](https://wpfusion.com/documentation/ecommerce/woocommerce/#auto-applying-discounts) not applying when logging in using the checkout login form
* Fixed MemberPress membership statuses batch operation not applying tags for expired transactions
* Fixed Gist webhooks not working wih webhooks configured via automation rules
* Fixed CartFlows optin fields not being automatically detected if there was an existing custom checkout field with the same field key
* Fixed incorrect format when syncing dates to Bento

= 3.40.8 - 6/6/2022 =
* Added fields Marital Status and External ID for sync with NationBuilder
* Improved - With the `update` and `update_tags` webhook with Drip, the tags will now be read out of the webhook payload, improving performance and saving an API call
* Improved - If ActiveCampaign Deep Data responds with an error indicating the connection has been deleted, the saved connection ID will also be cleared out in WP Fusion
* Improved - If a timestamp being synced to HubSpot is already a whole date (midnight UTC), it won't be recalculated using the site's timezone offset
* Improved logging with WP Remote User Sync - The remote site that triggered the action will now be added to the log's source trace rather than a separate log entry
* Fixed WP Fusion's LearnDash course settings getting reset when quick editing a LearnDash course in the post list table
* Fixed conflict (`Uncaught ArgumentCountError`) with the auto-register functionality in FluentCRM v2.5.9
* Fixed upsell tags not being applied with CartFlows when Asynchronous Checkout was enabled
* Fixed staging mode activating if the `WPF_STAGING_MODE` constant was defined as `false` since 3.39.5
* Fixed slashes in Mautic API passwords not getting unslashed before saving, and breaking the API connection
* Fixed Ultimate Member integration syncing data back to the CRM after a new user was imported, when Push All was enabled
* Fixed typo in Constant Contact class name

= 3.40.7 - 5/30/2022 =
* Added [`the_excerpt` shortcode for use in the restricted content message](https://wpfusion.com/documentation/getting-started/access-control/#restricted-content-excerpts)
* Added Current Page pseudo-field to [lead source tracking fields](https://wpfusion.com/documentation/tutorials/lead-source-tracking/)
* Improved - If a <!--more--> tag is set for a post (or the More block is used), and the Restricted Content Message is being displayed, the post excerpt (above the <!--more--> tag) will be displayed
* Improved - A notice will be logged if a ThriveCart success URL is detected but the ThriveCart Auto Login setting is disabled
* Improved - If a WooCommerce subscription status is changed to Pending Cancel, the Next Payment Date field will be erased in the CRM
* Improved - If an auto login link is visited, the Return After Login process will be triggered (if enabled)
* Improved - User role won't be synced back to the CRM if it was changed by a webhook (improves performance)
* Improved - If a form submission is triggering a new user registration (via Gravity Forms User Registration, WPForms User Registration, or similar), the tags applied by the form submission will be passed directly to the new user account (this fixes an issue where the tags may not have been fully saved in the CRM by the time the user is logged in)
* Improved - Simplified the language and tooltips in the WP Fusion status meta box on single WooCommerce subscriptions
* Updated to support WPForms User Registration addon v2.0.0+
* Fixed bulk editing access rules not working since WordPress 6.0
* Fixed date fields not syncing to NationBuilder
* Fixed special characters in LearnDash course/lesson/topic titles getting synced to the CRM ASCII-encoded
* Fixed WooCommerce auto-applied discounts not respecting the usage limit per user setting during an auto-login session
* Fixed WPForms multi-select inputs not syncing correctly when set to Create Tag(s) from Value
* Fixed UI saying "Add Topics" instead of "Add Tags" when using a picklist field for tags with Salesforce
* Fixed PHP warning `Expected parameter 2 to be array, null given` when bulk editing WP Fusion access rules and the Merge Changes box was checked
* Developers: Added parameters `$event_id` and `$ticket_id` to the [`wpf_event_tickets_attendee_data` filter](https://wpfusion.com/documentation/filters/wpf_event_tickets_attendee_data/)
* Developers: Added filter `wpf_loaded_tags` when tags are loaded from the CRM for a user

= 3.40.6 - 5/23/2022 =
* Added option to "skip already processed" to the [Event Tickets attendees batch operation](https://wpfusion.com/documentation/integrations/the-events-calendar-event-tickets/#exporting-attendees)
* Fixed `billing_email` getting synced as a the user's `user_email` when a registered user checked out with WooCommerce
* Fixed fatal error `Class 'WPF_Staging' not found` when trying to sync data to the CRM on a multisite install after calling `switch_to_blog()`
* Fixed the Drip integration not loading custom fields with capital letters in the field keys
* Developers: Improved - WP Fusion will not save the main settings if you are currently switched to another blog on a multisite install. This prevents settings from the original site overwriting the site you've switched to.

= 3.40.5 - 5/16/2022 =
* Added [WP Booking System integration](https://wpfusion.com/documentation/events/wp-booking-system/)
* Added support for [Sendinblue event tracking](https://wpfusion.com/documentation/event-tracking/sendinblue-event-tracking/)
* Added support for [Sendinblue site tracking](https://wpfusion.com/documentation/tutorials/site-tracking-scripts/#sendinblue)
* Added the ability to [restrict the purchase of LifterLMS access plans using tags](https://wpfusion.com/documentation/learning-management/lifterlms/#access-plans)
* Improved - Mobile phone numbers synced to NationBuilder will be set to opted in for SMS by default
* Fixed expiration date not syncing and tags not being applied for the pending cancellation status with the Paid Memberships Pro - Cancel on Next Payment Date addon v0.4
* Fixed PHP warning in EventON integration
* Fixed PHP warning (undefined array key) in Restrict Content Pro integration
* Fixed PHP warning (undefined array key) in Uncanny LearnDash Groups integration

= 3.40.4 - 5/8/2022 =
* Added Add Attendees option to [EventON integration](https://wpfusion.com/documentation/events/eventon/)
* Added option to apply tags when an attendee is checked in to an EventON event
* Improved - When a Paid Memberships Pro membership level is cancelled the `pmpro_expiration_date` field will be erased
* Improved - When a Paid Memberships Pro membership is cancelled and the Cancel on Next Payment Date addon is active, the next payment date will be synced to the `pmpro_expiration_date` field
* Fixed missing AccessAlly settings submenu page with latest AccessAlly versions
* Fixed CartFlows optin step settings not saving since 3.39.0
* Fixed conflict with Premmerce Permalink Manager for WooCommerce (WP Fusion settings page not saving)
* Fixed logs not properly displaying the results of a value modified by the `wpf_format_field_value` when the input variable was empty
* Fixed custom fields not syncing with Constant Contact
* Fixed phone numbers and addresses not syncing with Constant Contact
* Fixed dates not syncing with Constant Contact
* Fixed empty dates getting synced to Mailchimp as Jan 1st 1970
* Developers: added action `wpf_crm_loaded`

= 3.40.3 - 4/25/2022 =
* Fixed all content restricted for logged in users since 3.40.2

= 3.40.2 - 4/25/2022 =
* Added [EMPTY and NOT EMPTY comparisons](https://wpfusion.com/documentation/getting-started/shortcodes/#empty-and-not-empty) to the `user_meta_if` shortcode
* Added Status field for sync with FluentCRM (same site)
* Improved - If a contact has been deleted or merged in ActiveCampaign and a "not found" error is triggered, WP Fusion will try to look up the contact again by email address and retry the API call
* Improved - If an invalid timestamp is being synced to HubSpot (+/- 1000 years from today) it will be removed from the payload to avoid an API error
* Fixed fatal error applying tags to event attendees with FooEvents when the initial contact record creation failed due to an API error
* Fixed "Apply tags when refunded" tags not being applied when a WooCommerce renewal order was refunded but the subscription was still active
* Developers: Fixed the `wp_fusion_init_crm` action running too early for code added to functions.php (moved from `plugins_loaded` to `init`)

= 3.40.1 - 4/21/2022 =
* Fixed `wp_fusion()->crm_base` variable not being initialized since 3.40.0
* Fixed some weirdness with Staging Mode since 3.40.0: tags and contact IDs were getting lost when resyncing
* Fixed bbPress archive restriction running when a redirect was saved in the settings but Restrict Archives not checked
* Fixed "Apply tags when purchased" setting missing on WooCommerce variations when Restrict Content was disabled in the General settings
* Fixed conflict with WooCommerce Anti Fraud (edits to users in the admin were syncing the user's email address to the admin's contact record)
* Fixed PHP warning in `WPF_CRM_Base` when viewing an admin user profile before WP Fusion had been set up
* Fixed error in WeGlot integration since 3.40.0

= 3.40.0 - 4/18/2022 =

** Heads up! ** This update cleans up a lot of old and redundant code in the CRM integration classes. It should be safe for regular users, but if you have any custom code or have created custom CRM modules, please test on a staging site before updating.

* Refactored and simplified CRM class structure: removed calls to wp_fusion()->crm_base, removed class `WPF_CRM_Queue`, removed redundancies in calling `WPF_Staging` CRM
* Refactored and simplified [lead source tracking](https://wpfusion.com/documentation/tutorials/lead-source-tracking)
* Removed parameter `$map_meta_fields` in CRM classes. Field mapping is now handled in `__call()` magic method in `WPF_CRM_Base` (i.e. `wp_fusion()->crm`)

* Added [Constant Contact integration](https://wpfusion.com/documentation/installation-guides/how-to-connect-constant-contact-to-wordpress/)
* Improved - WPML, WeGlot, TranslatePress, and GTranslate integrations will now sync the current language preference whenever a contact is created or updated in the CRM (including for guests)
* Improved - When processing actions again for WooCommerce, Woo Subscriptions, GiveWP, EDD, and Gravity Forms, any tags will be applied regardless of the cache in WordPress (bypasses the Prevent Reapplying Tags option)
* Fixed Events Manager integration not detecting cancelled bookings when the plugin language was non-English
* Fixed admin users list showing No Contact ID for users who had a contact ID but no tags
* Fixed WooCommerce Memberships for Teams team meta batch operation crashing when trying to access deleted users
* Fixed updates to existing leads not working with Intercom and Gist

= 3.39.5 - 4/13/2022 =
* Fixed WooCommerce auto-applied coupons not working in AJAX requests since 3.39.3
* Improved - MemberPress emails and receipts will no longer be sent when a user is auto-enrolled into a membership via a linked tag
* Developers - Added function `wpf_is_staging_mode()`

= 3.39.4 - 4/11/2022 =
* Added [WPPayForm integration](https://wpfusion.com/documentation/ecommerce/wppayform/)
* Added option to [use a custom picklist field for tags with Salesforce](https://wpfusion.com/documentation/crm-specific-docs/salesforce-tags/)
* Added option to configure form auto-login per form feed with Gravity Forms
* Added link to view the donor's record in the CRM to the GiveWP / WP Fusion payment meta box
* Improved - Auto login sessions will now be ended on the `set_logged_in_cookie` action instead of `wp_login` and `wp_authenticate` (fixes conflict with Gravity Perks Auto Login)
* Improved - Updated NationBuilder add contact API endpoint to `/people/push` instead of `/people` to better handle merging duplicate records
* Improved logging when syncing dates with invalid formats
* Improved handling of European date formats with Advanced Custom Fields
* Improved ActiveCampaign error handling
* Improved - Updated Mailchimp `add_contact()` API call to `PUT` instead of `POST` to better handle duplicates
* Fixed orders failing to sync when using Asynchronous Checkout and the WooCommerce PayPal Payments gateway, when payment capture was delayed by PayPal
* Fixed PHP warning trying to apply tags via AJAX when an invalid tag name was provided
* Fixed Gist integration not loading more than 50 available tags
* Fixed user passwords getting recorded in the logs when registering a new user during an active auto-login session
* Fixed date fields on ACF Frontend forms syncing the previous value
* Fixed WooCommerce Subscriptions renewal payments getting processed by Asynchronous Checkout (if enabled)
* Fixed PHP warning in Advanced Custom Fields integration when syncing repeaters
* Fixed fatal error calling `wpf_get_current_user()` before the API was initialized
* Fixed `contact data was not found or in an invalid format` error when receiving webhooks from FluentCRM on the same site
* Fixed broken "Reauthorize with NationBuilder" link on the setup panel
* Fixed (Lite) - Integrations settings tab will now be hidden in WP Fusion Lite

= 3.39.3 - 4/4/2022 =
* Added ACF Frontend integration
* Addded support for tag-based visibility controls with new Elementor Container widget
* Improved Elementor visibility controls: protected sections and columns will now be completely removed from the page instead of hidden via CSS
* Improved - WooCommerce automatic discounts will not be applied if the cart total has already been discounted to 0
* Fixed Give email optin checkbox click also selecting the anonymous donation checkbox on donation forms that allow anonymous donations
* Fixed WP Fusion's WooCommerce Subscriptions settings fields showing up on regular (non-subscription) variable products since WooCommerce Subscriptions v4.0.0
* Fixed Capsule integration not returning the contact ID of newly created contacts
* Fixed filtering in the logs not working if headers were already sent by another plugin
* Fixed 401 / unauthorized errors not being correctly handled with Bento
* Fixed dismissing notices on the WPF settings page not being remembered
* Fixed HubSpot integration starting a site tracking session when batch exporting WooCommerce guest orders
* Fixed adding a new list in HubSpot via WP Fusion causing the existing Select A List dropdown to only show `(array)` for each list option
* Developers: Added filter `wpf_woocommerce_order_statuses_for_payment_complete`

= 3.39.2 - 3/28/2022 =
* Fixed "contact not found" being treated as an irrecoverable API error with HubSpot, since v3.39.1
* Fixed missing second parameter `$force` in [wpf_get_tags() function](https://wpfusion.com/documentation/functions/get_tags/)
* Fixed WooCommerce Subscriptions psuedo fields not being declared as pseudo fields (would try to load subscription data from the CRM and save it in usermeta)
* Fixed unhandled exception when updating a contact's email address to an email address already in use by another contact, with FluentCRM (same site)

= 3.39.1 - 3/28/2022 =
* Added License ID and License Key fields for sync with EDD Software Licensing integration
* Added "Skip Already Processed" checkbox option to Ninja Forms entry export batch operation
* Improved - The tooltip for restricted content in the admin post list table will now show if a redirect has been configured on the post
* Improved HubSpot error handling
* Fixed MemberMouse integration not applying tags for new purchases since v3.37.12
* Fixed webhooks with multiple contact records not being successfully processed with Salesforce and MailerLite since 3.38.31
* Fixed import by tag not working with MailChimp and numeric tag IDs
* Fixed Meta Box fields being registered as pseudo fields (only one-way sync)
* Fixed Sync Tags on Login and Sync Meta on Login running at the start of a ThriveCart auto-login and sometimes erasing the user's cached tags
* Fixed "The link you followed has expired" error when bulk deleting users and the Members plugin is active
* Fixed unclosed <table> tag on the Setup tab when connected to NationBuilder
* Fixed fatal error submitting Elementor Forms when a multiselect or multi-checkbox type field was enabled for sync
* Fixed PHP notice in WPF_Integrations_Base::guest_registration()
* Fixed PHP notice in WPF_Simply_Schedule_Appointments::create_update_customer()

= 3.39.0 - 3/21/2021 =
* Added [option to completely disable the access control system](https://wpfusion.com/documentation/getting-started/general-settings/#restrict-content)
* Added [a WP Fusion status metabox](https://wpfusion.com/documentation/ecommerce/woocommerce-subscriptions/#subscription-management) when editing a single WooCommerce subscription
* Added support for syncing custom fields added to a CartFlows optin step
* Improved Sendinblue error handling for failed contact record creation
* Fixed first and last name fields not syncing to Bento
* Fixed SliceWP integration syncing the user's last name as the email address
* Fixed SliceWP integration syncing currency symbol with total earnings
* Fixed Gamipress default ranks not being synced during new user registrations
* Fixed Gravity Forms feeds not processing when set to "Process only if payment is successful", and the initial payment was a subscription payment
* Fixed CartFlows upsell settings missing since CartFlows v1.9.0
* Fixed fatal error "Class name must be a valid object or string" when syncing tags to the remote site with WP Remote Users Sync
* Fixed capabilities being saved to the database with `wp_` as the prefix instead of the current blog prefix
* Fixed some unclosed HTML tags in the single taxonomy term settings table

= 3.38.46 - 3/14/2022 =
* Added setting Remove Tags - Cancelled to [Teams for WooCommerce Memberships integration](https://wpfusion.com/documentation/membership/teams-for-woocommerce-memberships/#tagging-team-members)
* Added Meta Box integration (custom user fields will now be auto-detected and listed for sync)
* Improved - "Automatic tags" (i.e. dynamic tags) with WooCommerce will no longer be removed when an order is refunded (never worked properly and is inconsistent with the other general tag settings)
* Improved error handling with ActiveCampaign (403 errors are now properly handled)
* Fixed SliceWP integration only syncing Paid commissions, not Unpaid
* Fixed Push User Meta action not syncing SliceWP fields
* Fixed Subscription in Trial tags not applying with new EDD subscriptions
* Fixed the AffiliteWP Referral Count field counting pending and rejected referrals
* Fixed un-checked checkboxes not syncing with Sendinblue boolean fields
* Fixed FluentCRM (same site) custom fields not being erased when a null value was synced
* Fixed BuddyBoss Profile Complete tags not applying since 3.38.44
* Fixed BuddyBoss Profile Complete tags applying on every other widget view
* Fixed logs not indicating a value was modified by the `wpf_format_field_value` filter when only the type had changed (fixed `!=` to `!==`)

= 3.38.45 - 3/7/2022 =
* Improved - The Restrict Forums setting with bbPress will now also apply to forums that are displayed within a BuddyBoss / BuddyPress group's discussion tab
* Improved - Bento event tracking can now accept an array for `$event_data`
* Improved - Shortened the URL length when filtering data in the activity logs
* Fixed WooCommerce coupon restriction by tags not working when the WooCommerce PDF Vouchers plugin is active
* Fixed dashes in LearnDash course or lesson titles getting synced to the CRM as HTML characters with the Last Lesson Completed, Last Course Completed, and Last Course Progressed fields
* Fixed dates syncing to HubSpot in UTC not local time
* Fixed Give Donations batch operation not working since 3.38.37
* Fixed custom fields not syncing to Autonami
* Fixed fatal error checking if WooCommerce Subscriptions was running on a duplicate site with Woo Subscriptions versions less than 4.0, since 3.38.44
* Fixed Uncanny Groups integration settings hidden on subscription products
* Fixed fatal error clicking Process WP Fusion Actions Again on a Gravity Forms entry from a deleted form
* Fixed Import Tool not working with Mailchimp since 3.38.35
* Fixed Select a CRM Field dropdown hidden on WPForms feeds (z-index was too low)
* Fixed date filter in the logs not working

= 3.38.44 - 2/28/2022 =
* Added Phone 1 Extension, Phone 1 Type, Phone 2, Phone 2 Extension, Phone 2 Type fields for sync with Infusionsoft
* Added Reauthorize with Dynamics 365 link to the Setup tab when connected to MS Dynamics 365
* Improved Zoho error handling, and made error messages clearer
* Improved - WP Fusion will now track a user's BuddyBoss / BuddyPress profile completion in the database to avoid the complete tags being reapplied every time the widget is loaded
* Fixed gift recipient's contact record getting merged with the gift purchaser with WooCommerce Subscriptions Gifting when the billing_email was enabled for sync
* Fixed update contact method not working with FluentCRM (REST API)
* Fixed contact updates in FluentCRM (same site) triggering data to be loaded back into WP Fusion right away
* Fixed Approved tags not being applied after a successful Stripe payment with Events Manager
* Fixed Cancelled tags not being applied when a booking was rejected or deleted with Events Manager
* Fixed PHP warning when using LearnDash wtih Filter Course Steps on a course that doesn't have any sections, since 3.38.43
* Fixed Contact Form 7 applying "Submit" as a tag when no other tags were specified
* Fixed use of deprecated function WC_Subscriptions::is_duplicate_site() with WooCommerce Subscriptions 4.0
* Fixed `PHP Notice: register_rest_route was called incorrectly` with Beaver Themer integration
* Fixed AffiliateWP affiliate details not syncing when an affiliate was edited in the admin
* Fixed checkboxes in the addon plugins that should be checked by default not being checked by default
* Fixed MemberPress checkbox fields syncing as "on" instead of `true`
* Fixed MemberPress multiselect fields not syncing
* Developers - Added [wpf_admin_override filter](https://wpfusion.com/documentation/filters/wpf_admin_override/)

= 3.38.43 - 2/21/2022 =
* Added Microsoft Dynamics 365 CRM integration
* Added [SliceWP integration](https://wpfusion.com/documentation/affiliates/slicewp/)
* Added Remove Tags and Apply Tags - Cancelled settings [to Events Manager integration](https://wpfusion.com/documentation/events/events-manager/#tagging-attendees)
* Improved (event tracking) - If an event value is sent to Bento [as a valid JSON string](https://wpfusion.com/documentation/event-tracking/bento-event-tracking/#advanced-usage) then that will be used in place of the default `name` and `val` properties in the `details` of the event payload
* Improved - Bento Event Tracking will now send events to `event.details.name` and `event.details.val`
* Fixed Gravity Forms feeds not processing if they were set to only run on a successful Stripe payment, and the Payment Collection Method was set to Stripe Credit Card Field in the Gravity Forms settings
* Fixed tags getting removed during a failed WooCommerce Subscriptions renewal payment, if Remove Tags was checked even though the user still has a separate active subscription to the same product, since 3.38.41
* Fixed restricted content message appearing by default on bbPress search results page
* Fixed checkbox fields not syncing with HubSpot
* Fixed tags not applying using Process WP Fusion Actions Again on a WooCommerce order since 3.38.42
* Fixed Import Users tool not working with FluentCRM (same site)
* Fixed Filter Course Steps with LearnDash not correctly calculating the position of sections after lessons had been removed from those sections (maybe not 100% fixed yet)
* Fixed fatal error approving new users in BuddyPress who were registered via Gravity Forms User Registration, while the Defer Until Activation setting was enabled in WP Fusion

= 3.38.42 - 2/14/2022 =
* Added MooSend CRM integration
* Added [order sync status column](https://wpfusion.com/documentation/ecommerce/woocommerce/#order-status-column) to WooCommerce orders list table
* Added - With HubSpot, Infusionsoft, FluentCRM, and Groundhogg, you can now type new tag names into the Select Tag(s) dropdown, and if the tag doesn't exist WP Fusion can send an API call to create the new tag
* Added links to the logs to go directly to the CRM contact record for each user
* Added Billing First Name and Billing Last Name fields for sync to Paid Memberships Pro integration
* Improved - WooCommerce + WP Fusion order status metabox will now require the `manage_woocommerce` permission (i.e. Shop Manager)
* Improved - Logs will now show "user-login" as the source when data was synced due to Login Meta Sync or Login Tags Sync
* Improved - The load_contact() method with ActiveCampaign will now use the v1 API for improved performance
* Fixed new Events Manager bookings with the Approved status not applying the Approved tags
* Fixed - Running Process WP Fusion Actions again on a WooCommerce order will now remove the `order_action=wpf_process` query parameter after it's finished so orders aren't accidentally exported twice

= 3.38.41 - 2/8/2022 =
* Fixed syntax error with PHP <= 7.2 since 3.38.40
* Fixed redundant WooCommerce integration and WooCommerce Subscriptions integration both applying the same tags for each renewal order
* Fixed - Outgoing API calls to remove and apply the same tag in the same request will be ignored (fixes issue of tag changes getting processed out of order in the CRM)
* Fixed product-specific WooCommerce Subscriptions fields getting orphaned on the main Contact Fields list even after being disabled
* Fixed 422 error with Drip when trying to sync data into a field that had a dash or space in the field ID
* Fixed Defer Until Activation setting with BuddyPress not working when using the Limit User Roles option in the WP Fusion settings
* Fixed error loading Infusionsoft / Keap social media fields
* Fixed failed user_register actions logging the full user POST data to the logs (including plaintext passwords)
* Extended default ActiveCampaign HTTP timeout to 20 seconds (instead of 15)

= 3.38.40 - 2/7/2022 =
* Added [Solid Affiliate integration](https://wpfusion.com/documentation/affiliates/solid-affiliate/)
* Added IP Address field to Contact Fields list
* Added ability to create new tags via the Select Tag(s) dropdown with Ontraport
* Improved - [Staging site detection](https://wpfusion.com/documentation/tutorials/staging-sites/) will now prompt you whether to recognize the new site URL as the main site vs. staying in staging mode (similar to WooCommerce Subscriptions)
* Improved - When creating a new lead in Kartra, the user's IP address will be sent by default
* Fixed updated Next Payment Date not syncing when editing a WooCommerce subscription in the admin (was syncing the previous value)
* Fixed some product-specific fields (Name, SKU, Start Date, End Date) not syncing with WooCommerce Subscriptions
* Fixed deleted tags in Mailchimp not being removed from the dropdowns in WP Fusion
* Fixed LearnDash course sections displaying in the wrong positions when using Filter Course Steps.
* Fixed "contact data not found" error receiving Groundhogg REST webhooks
* Fixed Bento event tracking not working when specifying a value
* Fixed Paid Memberships Pro expiration date not syncing when manually edited on the user's profile
* Fixed error `Call to a member function get_title() on bool` when processing a WooCommerce Subscriptions renewal payment for a deleted product

= 3.38.39 - 2/2/2022 =
* Hopefully fixed the issues with Staging Mode auto-activating since 3.38.35: changes to the home_url() as well as changes between http:// and https:// will no longer activate staging mode
* Improved - Filter Queries will now be bypassed when DOING_CRON is set to true
* Fixed WP Fusion subscription settings hidden on WooCommerce Subscriptions products with Subscriptions 4.0+
* Fixed event value not syncing with Bento event tracking
* Fixed PHP warning calling wp_fusion()->user->get_user_meta() when not logged in
* Fixed bug in the logs where link to edit a WooCommerce order would be replaced by link to edit the contact in the CRM

= 3.38.38 - 1/31/2022 =
* Additional fixes for staging mode auto-activating when the admin language was changed with WPML and TranslatePress, since 3.38.35
* Tested for WordPress 5.9

= 3.38.37 - 1/31/2022 =
* Added [ARMember integration](https://wpfusion.com/documentation/membership/armember/)
* Added [Apply Tags - Check-in setting](https://wpfusion.com/documentation/integrations/the-events-calendar-event-tickets/#event-tickets) to Event Tickets integration
* Added support for syncing to Leads with Intercom (enable from the Integrations tab)
* Added Subscription Product SKU field for sync with WooCommerce Subscriptions
* Improved - Updating subscribers with Bento will now use the UUID as an identifier, not the email address, which fixes issues arising from users changing their email address and getting disconnected from their subscriber record
* Improved - ACF User fields with the Multiple option enabled will now sync an array of full user names (not IDs)
* Improved Autonami error handling
* Fixed query filtering running twice on WooCommerce products when Filter Queries was enabled at the same time as Hide Restricted Products
* Fixed private BuddyPress XProfile fields not being exported with the Push User Meta batch operation
* Fixed staging mode auto-activating when the admin language was changed with WPML and TranslatePress, since 3.38.35

= 3.38.36 - 1/25/2022 =
* Fixed Gravity Forms feed settings menu item not showing since 3.38.35

= 3.38.35 - 1/24/2022 =
* Added social fields for sync with Infusionsoft / Keap (click Refresh Available Fields to load them)
* Added dynamic tagging support to Mailchimp integration (requires resetting the settings for existing installs)
* Improved - WP Fusion will now run before any Gravity Forms User Registration feeds. This allows for a subscriber to update their email address in the CRM via an auto-login link before registering a new account.
* Improved Mailchimp API performance for applying and removing tags
* Improved Mailchimp contact ID lookup — will now only return exact matches (fixes an issue where tags would be applied to the wrong contact when using sub-inboxes with Gmail)
* Improved - The new standalone api.php endpoint will now use wp_cache_set() to improve performance when looking up user IDs from contact ID
* Improved - If the site URL changes, WP Fusion will automatically enable Staging Mode
* Fixed "Apply Lists" option appearing on Gravity Forms feeds when connected to FluentCRM
* Fixed 5 second timeout loading a contact with Maropost (increased to 20s)
* Fixed fatal error with HTTP API Logging when the HTTP response was a WP_Error object, since 3.38.34
* Fixed calls to deprecated function GetOption() in WishListMember integration

= 3.38.34 - 1/18/2022 =
* Added Easy Digital Downloads Checkout Fields Manager integration
* Added support for syncing user profile data from [Advanced Custom Fields flexible content fields](https://wpfusion.com/documentation/other/advanced-custom-fields/#repeaters-and-flexible-content)
* Added [Apply Tags - Pending option](https://wpfusion.com/documentation/membership/memberpress/#tagging) to MemberPress integration
* Added [Link with Tag functionality](https://wpfusion.com/documentation/learning-management/learnpress/) to LearnPress integration
* Added View In CRM link to the user action links on the All Users list in the admin
* Improved - Auto login will set a cookie `wordpress_logged_in_wpfusioncachebuster` which should bypass caching on most configurations
* Improved - If the full WP Fusion is activated, the WP Fusion Lite plugin will be automatically deactivated
* Improved - Removed some redundant data in the logs when HTTP API logging is enabled
* Improved - HTTP API logging will now show JSON-decoded request and response bodies where applicable
* Improved error handling with ActiveCampaign Deep Data
* Improved HubSpot error handling
* Fixed Active lists not showing up as options in the Select A List dropdown for the Import Users tool with HubSpot
* Fixed If-Menu integration not working with CRMs that use tag categories
* Fixed Required Tags (all) and Required Tags (not) settings displaying on admin menu editor even when User Menus was inactive
* Fixed parse error in ActiveCampaign integration with PHP 7.2

= 3.38.33 - 1/10/2022 =
* Added [User Menus integration](https://wpfusion.com/documentation/tutorials/menu-item-visibility/#advanced-usage)
* Added Status section to WP Fusion settings with status and debug information about the plugin
* Added support for Group and [Repeater fields with Advanced Custom Fields](https://wpfusion.com/documentation/other/advanced-custom-fields/#repeaters)
* Added notice to HubSpot integration about enabling marketing contacts for the WP Fusion app
* Improved - ActiveCampaign integration now fully uses the WordPress HTTP API instead of the ActiveCampaign PHP SDK
* Improved - HTML tags will be removed when exporting the activity logs to .csv
* Improved - When resetting the main settings page, the cached contact IDs and tags will be deleted for all users (this fixes "Invalid contact ID" errors when switching between CRM accounts)
* Fixed Quiz Failed tags not being applied since LearnDash 3.6.0
* Fixed taxonomy term protections not working since 3.38.32
* Fixed fatal error loading the `role` field with an array value (now the first array value will be used as the role)
* Fixed ActiveCampaign multiselect fields loaded as text being prepended/appended by ||
* Fixed missing scope `crm.lists.write` with HubSpot (prevented adding contacts to static lists for OAuth apps connected after December 15th 2021)

= 3.38.32 - 1/3/2022 =
* Added support for [Fluent Forms User Registration](https://wpfusion.com/documentation/lead-generation/fluent-forms/#user-registration)
* Added option to [sync WooCommerce Subscriptions details to separate custom fields](https://wpfusion.com/documentation/ecommerce/woocommerce-subscriptions/#syncing-subscription-fields) in the CRM for each subscription product
* Improved logging of authentication errors with Salesforce
* Improved - Stopped ending auto-login sessions on registration (`user_register` hook)
* Updated ActiveCampaign integration to use v3 API for loading contacts
* Fixed the Prevent Reapplying Tags setting not working if at least one of the tags to be applied was new
* Fixed updater license check returning "invalid item ID" message and deactivating license
* Fixed PHP notice "wpdb::prepare was called incorrectly" when checking taxonomy term access rules on posts
* Fixed PHP warning during MemberMouse registration when Advanced Custom Fields is active
* Fixed issue syncing tags with Emercury for subscribers that had upper case letters in their email address

= 3.38.31 - 12/27/2021 =
* Added a new api.php endpoint that [can be used for super fast async webhook processing](https://wpfusion.com/documentation/other-common-issues/webhooks-not-being-received-by-wp-fusion/#the-async-endpoint)
* Removed old wpf_post.php file and API endpoint
* Added an option to process asynchronous webhooks using a cron job instead of trying to start the background worker with each webhook
* Added link to admin user profile to view the logs for that user
* Improved performance when using async=true webhooks. The background worker will no longer attempt to start if it is already running.
* Improved - The process lock time for the background worker will now respect the site's PHP `max_execution_time`. The lock time will be the max time + 30 seconds.
* Improved ActiveCampaign error handling so that it now looks at the response code instead of message (some errors were not being caught properly with non-English accounts)
* Fixed Resync Tags for Every User operation not triggering automated course enrollments
* Fixed PHP warning trying to lookup ActiveCampaign Deep Data customer ID when an existing contact was not yet registered as a Deep Data customer
* Developers: Added filter `wpf_query_filter_cache_time`

= 3.38.30 - 12/20/2021 =
* Added support for [custom objects with HubSpot](https://wpfusion.com/documentation/crm-specific-docs/custom-objects-with-hubspot/)
* Fixed new event tracking integration disabled with Gist and Intercom
* Fixed layout glitch on EDD admin customer profile with CRMs with long names
* Fixed fatal error in Woo Memberships for Teams integration when adding an invalid user ID to a team
* Fixed infinite redirect with LearnDash when using Shared Course Steps + Filter Course Steps, and trying to access a topic via permalink

= 3.38.29 - 12/13/2021 =
* Fixed parse error in Event Tickets integration since 3.38.29
* Fixed missing event value with Gist event tracking

= 3.38.28 - 12/13/2021 =
* Added [If Menu integration](https://wpfusion.com/documentation/other/if-menu/)
* Added event tracking support for Gist
* Added event tracking support for Intercom
* Improved - Invalid characters will now automatically be removed from the event name with ActiveCampaign event tracking
* Improved - If HTTP API logging is enabled, event tracking API calls will be sent `'blocking' => true` so that the responses are logged
* Improved - Select Tags boxes in Appearance >> Menus editor will now lazy load their tags, for improved menu editing performance
* Improved - Stopped syncing user ID when updating Gist subscribers (should cause records to get merged less often)
* Improved - Moved LearnDash course settings to standalone settings tab
* Fixed Gravity Forms not pre-filling during an auto-login session when the form was added via the Elementor "form" widget
* Fixed PHP warning in Event Tickets integration

= 3.38.27 - 12/6/2021 =
* Added user search field to the logs table
* Added lock indicator on locked LearnDash topics when Lock Lessons is enabled
* Added setting to the batch operations to re-process locked records for WooCommerce orders, Easy Digital Downloads payments, GiveWP donations, and Gravity Forms entries
* Added view in CRM link to Easy Digital Downloads customer profile
* Added view in CRM links to Mailchimp integration
* Added view in CRM links to Bento integration
* Improved support for using Create Tag(s) from Value with multi-checkbox inputs on forms
* Improved - If an `email` parameter is provided in a webhook request, WP Fusion will attempt to detect when a contact ID associated with a user may have changed due to a merge
* Improved - If a field type is set to "raw" an empty value loaded over the CRM will erase the value saved in WordPress
* Fixed has_access() check always failing in latest BuddyBoss App versions
* Fixed "The tags must be an array." error message with HighLevel when using Create Tag(s) from Value
* Fixed attendee phone number and company not syncing with FooEvents when the attendee's email is the same as the customer email
* Fixed wpf_infusionsoft_safe_tags filter not stripping invalid characters out of tag category names
* Developers: In cases where posts (i.e. orders) were marked with `wpf_complete` set to `true`, `wpf_complete` will now be set to the time (`current_time( 'Y-m-d H:i:s' )`)

= 3.38.26 - 11/30/2021 =
* Fixed CartFlows settings panel not clickable in CartFlows Pro v1.7.2
* Fixed fatal error in MemberPress Memberships Statuses batch operation when trying to apply Cancelled tags based on transaction status

= 3.38.25 - 11/22/2021 =
* Added option to prefix usermeta keys with the current blog prefix to avoid sharing contact IDs and tags across sub-sites on multisite installs (can be enabled from the Advanced settings tab)
* Added warning to the settings about applying tags for pending WooCommerce orders
* Added [`timezone-offset` attribute](https://wpfusion.com/documentation/getting-started/shortcodes/#user-meta-formatting-timezone-offset) to `user_meta` shortcode
* Added logging for when a date failed to sync to the CRM because the input date format couldn't be converted to a timestamp
* Added error logging for failed Salesforce access token refreshes
* Fixed Join Date fields not syncing with Restrict Content Pro
* Fixed Notes field not syncing with Restrict Content Pro
* Fixed EDD Orders exporter exporting unpaid orders
* Fixed PHP warning tracking events with HubSpot
* Fixed request to refresh Salesforce access token not being recorded by HTTP API Logging
* Fixed fatal error updating a Bento subscriber without an email
* Developers: Added filter `wpf_restricted_terms_for_user`
* Developers: Added filter `wpf_taxonomy_rules`
* Developers: Added constants `WPF_CONTACT_ID_META_KEY` and `WPF_TAGS_META_KEY`

= 3.38.24 - 11/15/2021 =
* Added note to Salesforce setup panel regarding completing the installation of the OAuth app
* Improved - Applying tags with Bento will now trigger events using the `add_tag_via_event` command (thanks @jessehanley)
* Fixed EDD Email Optin tags getting applied regardless of email optin consent checkbox being checked
* Fixed PHP warning when using Uncanny Toolkit Pro and FluentCRM or Groundhogg
* Developers - The active CRM object is now passed by reference via the `wp_fusion_init_crm` action and [can be operated on](https://wpfusion.com/documentation/advanced-developer-tutorials/how-to-use-a-custom-client-id-for-authentication/#using-a-custom-client-id-and-authorization-url)

= 3.38.23 - 11/8/2021 =
* Added `IN` and `NOT IN` comparisons [to the `user_meta_if` shortcode](https://wpfusion.com/documentation/getting-started/shortcodes/#in-and-not-in)
* Added Apply Tags - Trialling and Apply Tags - Converted to EDD Recurring Payments integration
* Added Export to CSV button to Activity Logs
* Improved - Mailchimp Audience select box is moved to the Setup tab and fields and tags can be loaded for a new audience without having to save the settings first
* Improved - Mailchimp setup will now show a warning if you try to connect and there are no audiences in your account
* Improved - Added a notice to the logs when a new ConvertKit subscriber is being created with a random tag due to no default tag being set
* Fixed WooCommerce order status changes in the admin list table not applying tags when Asynchronous Checkout was enabled
* Fixed LearnDash course progress tags not being applied when the Autocomplete Lessons & Topics Pro module was enabled in Uncanny Toolkit Pro for LearnDash
* Fixed MemberPress Memberships Statuses batch operation not applying tags for cancelled, trial, and expired subscription statuses
* Fixed Subscription Cancelled tags not be applied with MemberPress when a subscription is cancelled after its expiration date
* Fixed new users registered via Gravity Forms User Registration not being synced during an auto-login session
* Fixed Intercom rejecting new subscribers without a last name
* Fixed `unknown class FrmRegEntryHelper` error when registering new users on older versions of Formidable Forms
* Fixed PHP warning loading subscriber with no tags from Intercom
* Fixed upgrade to 3.38.22 not setting autoload = yes on `wpf_taxonomy_rules`, which made content protected by taxonomy rules un-protected until saved again
* Developers - Added `wpf_woocommerce_subscription_sync_fields` filter
* Developers - Added function `wpf_get_current_user_email()`

= 3.38.22 - 11/1/2021 =
* Improved performance with checking post access against taxonomy term restrictions
* Improved - If a field type is set to multiselect and it is stored as a comma-separated text value, the value will be synced as an array with supported CRMs
* Improved - If a page using an auto-login query string (?cid=) is refreshed, for example due to a form submission, this will no longer force reload the contact's tags from the CRM
* Improved Zoho error handling
* Fixed tags linked to BuddyBoss profile types not being assigned during registration when new user accounts are auto-activated
* Fixed restricted LearnDash lessons not being hidden by Filter Course Steps in Focus Mode with the BuddyBoss theme
* Fixed Lock Lessons with LearnDash outputting lock icon on lessons that were already locked by LearnDash core

= 3.38.21 - 10/26/2021 =
* Fixed all content being protected when no term taxonomy rules were set since 3.38.20

= 3.38.20 - 10/26/2021 =
* Fixed SQL warning checking term access restrictions since 3.38.17
* Fixed `wpf_salesforce_auth_url` filter (for connecting to sandboxes) not working with new OAuth integration from 3.38.17
* Fixed WP Affiliate Manager integration not applying Approved tags when affiliates are auto-approved at registration

= 3.38.19 - 10/25/2021 =
* Fixed error with WP Remote Users Sync `Cannot redeclare WPF_WP_Remote_Users_Sync::$slug`

= 3.38.18 - 10/25/2021 =
* Fixed error with Advanced Ads `Cannot redeclare WPF_Advanced_Ads::$slug`
* Fixed - Infusionsoft integration will force all numeric values to sync as text to get around "java.lang.Integer cannot be cast to java.lang.String" errors

= 3.38.17 - 10/25/2021 =
* **Added Salesforce OAuth integration - Salesforce users will need to go to the WP Fusion settings page one time and grant OAuth permissions to use the new API**
* Added setting to apply tags when a review is left on a WooCommerce product
* Added option to sync total points earned on a LearnDash quiz to a custom field in the CRM
* Improved - When using Filter Queries - Advanced, posts protected by taxonomy terms will be properly excluded
* Improved performance for Filter Queries with Elementor posts lists
* Improved - If "Create contacts for new users" is disabled, a WooCommerce checkout by a registered user will now correctly apply the product tags directly to the contact record in the CRM
* Improved - Removed "old" WooCommerce asynchronous checkout processor via WP Background Processing in favor of an AJAX request bound to the successful payment response from the gateway
* Improved - If the LearnDash - WooCommerce plugin triggers an enrollment into a course or group which results in tags being applied, this will be indicated in the logs
* Improved - Slowed down batch exporter with Bento to get around API throttling
* Improved - When bulk editing more than 20 WooCommerce orders in the admin, WP Fusion will bypass applying any tags to avoid a timeout
* Fixed fatal error `undefined method FacetWP_Settings::get_field_html()` in FacetWP 3.9
* Fixed read only lists not showing on admin user profile with HubSpot since 3.38.16
* Fixed Infusionsoft not loading more than 1000 available tags per category
* Fixed custom fields not syncing when creating a new Bento contact
* Fixed 429 / "API limits exceeded" errors not being logged with Bento
* Fixed Salesforce automatic access token refresh failing when the password contains an ampersand
* Developers — Added `track_event()` method to supported CRMs in advance of the new Event Tracking addon

= 3.38.16 - 10/18/2021 =
* Added support for syncing to Date/Time fields with Keap and Infusionsoft
* Added option to sync LearnDash course progress percentage with a custom field in the CRM
* Added JetEngine integration
* Improved - Read-only tags and lists will no longer show up in Apply Tags dropdowns (only Required Tags dropdowns)
* Improved - If a user is auto-enrolled into a course via a linked tag, the tags in the Apply Tags - Enrolled setting will now be applied. This can be used in an automation to confirm that the auto-enrollment was successful
* Improved - Dates displayed with the [[user_meta]] shortcode will now use the site's current timezone
* Improved - WP Remote Users Sync integration will no longer sync tag changes to a remote site when they've just been loaded from a remote site (safeguard against infinite loops)
* Improved - WP Remote Users Sync integration will not send updated tags to remote sites more than once per pageload
* Improved - A successful API response from Drip for a subscriber will remove the Inactive badge in the admin
* Fixed not being able to de-select a selected pipeline and stage for ecommerce deals in the WooCommerce Order Status Stages section of the WP Fusion settings
* Fixed automatic WooCommerce Subscriptions duplicate site detection not working
* Fixed Prevent Reapplying Tags setting not being respected
* Fixed an empty API response from Drip marking users as Inactive
* Fixed fatal error "Too few arguments to function" when applying BuddyBoss profile type tags since 3.38.14
* Fixed error syncing array values with Sendinblue
* Fixed Sendinblue error "attributes should be an object" when syncing data without any custom fields
* Fixed PHP notice "Trying to access array offset on value of type null" in Uncanny LearnDash Groups integration during group member enrollment

= 3.38.15 -10/11/2021 =
* Added Emercury site tracking
* Added safety checks against infinite loops when using LearnDash and BuddyBoss auto-enrollments in conjunction with the Group Sync feature
* Fixed bug since 3.38.14 that could cause content to become restricted if it was associated with a deleted taxonomy term
* Fixed HTML not saving in the Default Restricted Content Message since 3.38.0
* Fixed empty date fields being synced as 0 which could evaluate to January 1st 1970 in some CRMs
* Fixed WooCommerce Product Addons integration not syncing Quantity type fields
* Fixed WooCommerce Product Addons integration not syncing Text type fields
* Fixed Async Checkout (New) for WooCommerce applying tags for On Hold orders (i.e. BACS)
* Fixed dynamic tags with a text prefix not getting automatically removed when a WooCommerce order is refunded
* Fixed WPF trying (and failing) to unenroll BuddyPress group moderators from groups when they were missing the group member linked tag
* Fixed WPF settings not saving in CPT-UI since CPT-UI v1.10.0
* Developers - Added function `wpf_clean_tags()` (same as `wpf_clean()` but allows special characters)

= 3.38.14 - 10/5/2021 =
* Added panel in the WP Fusion settings showing the loaded integrations, with links to the documentation for each
* Improved Mailchimp API performance when loading available tags
* Fixed error `Uncaught Error: Class 'WPF_Plugin_Updater' not found` conflict with WPMU Dev Dashboard v4.11.4
* Fixed "Failed to apply tags - no contact ID" message when a registered user without a contact record filled out a form
* Fixed special characters getting synced to the CRM HTML encoded since 3.38.0
* Fixed Filter Course Steps with LearnDash not working when Shared Course Steps was off
* Fixed category-based tag access rules not working
* Fixed BuddyPress XProfile updates not syncing since BuddyPress v9.1.0
* Fixed linked tags not being removed from the previous profile type when switching a user's profile types in BuddyBoss
* Fixed form submissions during an auto-login session not updating the correct contact record when there was no email address on the form
* Fixed error with Gravity Forms when using "Create tag(s) from value" on a form field and no tags had been configured generally for the feed
* Fixed custom fields not syncing with FooEvents when the customer who purchased the ticket is also an attendee
* Fixed Salesforce integration not accepting a new security token until Refresh Topics and Fields was pressed
* Fixed import tool with Drip not importing unsubscribed subscribers
* Fixed import tool with Drip not importing more than 1000 subscribers
* Fixed countries with e-acute symbol in their name not syncing to the Country field with Infusionsoft
* Fixed date values before 1970 not being synced correctly
* Fixed PHP notice Undefined index: step_display in LearnDash integration

= 3.38.13 - 9/22/2021 =
* Fixed Divi modules not respecting tag access rules with Divi Builder 4.10.8+

= 3.38.12 - 9/21/2021 =
* Improved WP Remote Users sync integration (can now detect tag changes that aren't part of a profile update)
* Fixed updated tags not loading from CRM, since 3.38.11

= 3.38.11 - 9/20/2021 =
* Added [WooCommerce Payment Plans integration](https://wpfusion.com/documentation/ecommerce/woocommerce-payment-plans/)
* Improved - Filter Course Steps for LearnDash should now be a lot more reliable in terms of course step counts and progress tracking
* Improved - If a WooCommerce Memberships membership plan is transferred to another user, the tags will be updated for both the previous and new owners
* Added import tool support for Groundhogg (REST API)
* Added support for loading multiselect data from Copper
* Removed "Enable Notifications" setting from ConvertKit integration, in favor of the global "Send Welcome Email" setting
* Maropost bugfixes
* Updated Copper API URL
* Fixed access checks sometimes failing when using tag names with HTML special characters in them
* Fixed a bug whereby LearnDash lessons could become detached from a course if LearnDash tried to rebuild the course steps cache while the Restricted Content Message was being displayed in place of the course content
* Fixed custom fields not syncing with Bento
* Fixed multiselect data not syncing to Copper
* Fixed checkbox data not syncing to Copper
* Fixed PHP warning in Emercury integration

= 3.38.10 - 9/13/2021 =
* Added Groundhogg (REST API) CRM integration
* Added [Simply Schedule Appointments integration](https://wpfusion.com/documentation/events/simply-schedule-appointments/)
* Added option to disable the sync of guest bookings with Events Manager
* Improved - Events Manager dates and times will now be synced in the timezone of the event, not UTC
* Fixed initial REST authentication (Groundhogg, FluentCRM, Autonami) sometimes breaking if there was a trailing slash at the end of the REST URL
* Fixed lookups for ActiveCampaign Deep Data customer IDs sometimes failing (email address in URL wasn't URL encoded)
* Fixed import by tag with ActiveCampaign sometimes importing contacts with the wrong tag ID when the search string matched multiple tags
* Fixed WP Fusion blocking Events Manager registrations when there was an API error creating the attendee contact record
* Fixed ACF return formats not being respected for dates when using a Push User Meta operation
* Fixed - Salesforce dates will now be formatted using gmdate() instead of date() (fixes some time zone issues)
* Fixed - Updated Maropost API calls to use SSL API endpoint
* Fixed admin override not working correctly in wpf_user_can_access() when checking the access for a different user (since 3.38.5)

= 3.38.9 - 9/7/2021 =
* Added [Download Monitor integration](https://wpfusion.com/documentation/other/download-monitor/)
* Added [BuddyBoss group organizer linked tag option](https://wpfusion.com/documentation/membership/buddypress/#group-organizer-auto-assignment)
* Improved - Clicking Process WP Fusion Order Actions Again on a WooCommerce order which contains a subscription renewal will also sync any enabled subscription fields
* Improved - HubSpot's site tracking script is now disabled on the WooCommerce My Account page, to prevent the script from trying to sync account edits with the CRM
* Fixed tags with > and < symbols getting loaded from the CRM HTML-encoded
* Fixed PHP warning in class WPF_User when registering a new user with no first or last name
* Fixed Maropost webhooks not working since 3.38.0

= 3.38.8 - 9/1/2021 =
* Fixed parse error in LearnDash integration on some PHP versions since 3.38.5
* Fixed form integrations not applying tags if no fields were enabled for sync since 3.38.5
* Fixed Incomplete Address error with Mailchimp when syncing United States of America as the country, but not specifying a state
* Updated EDD updater to v1.9

= 3.38.7 - 8/31/2021 =
* Fixed apply tags via AJAX endpoints resulting in a 403 error since 3.38.0, with Media Tools and other addons
* Improved logging with Drip, when an email address is changed to an address that already has a subscriber record
* Fixed PHP warning in the admin when editing a page that has child pages

= 3.38.6 - 8/30/2021 =
* Fixed PHP notice in LearnDash integration since 3.38.5

= 3.38.5 - 8/30/2021 =
* Added [EventON integration](https://wpfusion.com/documentation/events/eventon/)
* Added support for [Bento webhooks](https://wpfusion.com/documentation/webhooks/bento-webhooks/)
* Added [Pay Per Post tagging with WishList Member](https://wpfusion.com/documentation/membership/wishlist-member/#pay-per-post-tagging)
* Added Login With AJAX integration (login redirects will now work with the Return After Login setting)
* Improved - When a contact ID is recorded in the logs, it will include a link to edit that contact in the CRM
* Improved - It's no longer necessary to enable Set Current User to pre-fill Gravity Forms fields with auto-login user data
* Improved LearnDash course settings admin layout
* Fixed - Removed `wp_kses_post()` on restricted content message (was breaking login forms)
* Fixed `http_request_failed` errors from the WordPress HTTP API not being logged as errors
* Fixed PHP warning loading custom fields from Bento
* Fixed PHP warning in wpForo integration
* Fixed fatal error syncing avatars to the CRM from the BuddyBoss app
* Fixed Users Insights search only running on first page of results
* Fixed FooEvents Zoom URL not syncing
* Fixed fatal error in HubSpot integration when using site tracking and an API error was encountered trying to get the tracking ID
* Fixed `Fatal error: Cannot declare class AC_Connector` since 3.38.0
* Fixed memory leak with WPML, post category archives, and the Exclude Administrators setting
* Fixed Ontraport integration not creating new contacts with missing emails (even though Ontraport allows contacts to not have an email address)
* Developers: Added filter `wpf_wp_kses_allowed_html`
* Developers: Data loaded from the CRM will now be passed through `wp_kses_post()` instead of `sanitize_text_field()` (since 3.38), to permit syncing HTML inside of custom fields
* Fixed missing second argument `$force_update` in `wpf_get_contact_id()`

= 3.38.4 - 8/23/2021 =
* Added [Bento marketing automation](https://wpfusion.com/go/bento/) integration
* Fixed updates to existing contacts not working with Klaviyo
* Fixed Bulk Edit box not appearing on LifterLMS lessons
* Fixed JavaScript error with Resync Tags button on admin user profile
* Fixed serialized data not being unserialized during a Push User Meta operation
* Fixed parse error in MemberPress integration on some PHP versions
* Developers: Fixed `wpf_get_contact_id()` sometimes returning an empty string instead of `false` when a contact record wasn't found

= 3.38.3 - 8/19/2021 =
* Improved - Stopped setting 'unknown' for missing Address 2, Country and State fields with Mailchimp
* Fixed webhooks not working with Salesforce since 3.38.0
* Fixed links not displaying in the activity logs since 3.38.0
* Fixed syntax error with some PHP configurations since 3.38.0
* Fixed PHP warning in Infusionsoft integration

= 3.38.2 - 8/18/2021 =
* Fixed error `Call to undefined function get_current_screen()` since 3.38.0 when performing some admin actions
* Fixed warning about missing redirect showing on LearnDash lessons where the redirect was configured on the parent course

= 3.38.1 - 8/17/2021 =
* Fixed auto-login links not working since 3.38.0
* Fixed custom fields not syncing during MemberPress registration since 3.38.0
* Fixed Defer Until Activation setting not working with signups from the BuddyBoss app
* Developers: Removed WPF_* prefix from 3rd party CRM SDK classes (to comply with wordpress.org plugin guidelines)

= 3.38.0 - 8/16/2021 =

** Heads up! ** This update includes a significant refactor of WP Fusion's admin settings, interfaces, and database storage. We've tested the update extensively, but with 3,500+ changes across 200+ files, there are potentially bugs we've missed. If that sounds scary, you may want to wait until v3.38.1 is released before updating.

If there are bugs, they will most likely affect saving WP Fusion settings in the admin (general settings, access rules, product configurations, etc.) and not affect the existing access rules or sync configuration on your site.

* Big cleanup and refactoring with improvements for security, internationalization, and documentation
* Added [If-So Dynamic Content integration](https://wpfusion.com/documentation/other/if-so/)
* Added support for syncing the [Zoom meeting ID and join URL with FooEvents](https://wpfusion.com/documentation/events/fooevents/#zoom)
* Added View in CRM URL for Jetpack CRM
* Added GDPR Consent Date, Agreed to Terms Date, and Marketing Consent Date fields for sync with Groundhogg
* Improved - Guest registrations will log whether a contact is being created or updated
* Fixed XProfile fields First Name and Last Name not syncing during a new BuddyBoss user registration
* Fixed filtering by CRM tag not working in Users Insights
* Fixed user profile updates overwriting Jetpack CRM contacts
* Fixed initial default field mapping not being stored after setup until the settings were saved the first time
* Fixed logs getting flushed when hitting Enter in the pagination box
* Fixed expiration date not being synced after a Restrict Content Pro renewal
* Fixed bbPress forum archive not being protected when Filter Queries was on
* Deleted unused XMLRPC modules in the Infusionsoft iSDK
* Developers: Added function `wpf_get_option()` (alternative for `wp_fusion()->settings->get()`)
* Developers: Added sanitization functionn `wpf_clean()`
* Developers: Deprecated `wp_fusion()->settings->get_all()`
* Developers: Changed `wp_fusion()->settings->set_all( $options )` to `wp_fusion()->settings->set_multiple( $options )`

= 3.37.31 - 8/9/2021 =
* Added [RestroPress integration](https://wpfusion.com/documentation/ecommerce/restropress/)
* Added [Import Trigger tag option for Jetpack CRM ](https://wpfusion.com/documentation/webhooks/jetpack-crm-automatic-imports/)
* Added option to [sync LearnDash quiz scores to a custom field in the CRM](https://wpfusion.com/documentation/learning-management/learndash/#quizzes)
* Added support for WPForms User Registration addon
* Added Picture URL field for sync with CapsuleCRM
* Added nonce verification to Flush All Logs button (improved security)
* Improved - Logs will contain a link to edit the contact record in the CRM after a form submission
* Improved - If Add Attendees is enabled for a Tribe Tickets RSVP ticket, and a registered user RSVPs with a different email address, a new contact record will be created (rather than updating their existing contact record)
* Fixed Ultimate Member `role_select` and `role_radio` fields not syncing during registration
* Fixed Gravity Forms Nested Feeds processing not respecting feed conditions
* Fixed custom fields not syncing with Maropost
* Fixed PHP warning updating contacts with Intercom
* Fixed LearnPress course enrollment tags not being applied when there were multiple course products in an order
* Fixed console errors in the Widgets editor since WP 5.8
* Fixed search input not being auto-focused in CRM field select dropdowns with jQuery 3.6.0
* Developers: Added helper function `WPF_Admin_Interfaces::sanitize_tags_settings( $settings ); for sanitizing tag multiselect data in metaboxes before saving to postmeta
* Developers: Improved sanitization of meta box data in admin

= 3.37.30 - 8/2/2021 =
* Added View In CRM links (direct link to the contact record) for all CRMs that support it
* Added [email optin checkbox and optin tagging for Easy Digital Downloads](https://wpfusion.com/documentation/ecommerce/easy-digital-downloads/#email-optins)
* Added support for [FluentCRM webhooks](https://wpfusion.com/documentation/webhooks/fluentcrm-webhooks/)
* Added [email optin setting to GiveWP integration](https://wpfusion.com/documentation/ecommerce/give/#email-optins)
* Added Job Title field for sync with Capsule CRM
* Improved - Added notice to the setup screen with information on how to connect to Autonami on the same site
* Improved - Added warning for admins when viewing a post that has access rules enabled, but no redirect specified
* Fixed Capsule CRM not loading more than 100 tags
* Fixed Events Manager bookings batch operation not detecting past bookings
* Fixed Events Manager bookings batch operation not exporting more than 20 bookings
* Fixed Events Manager not syncing guest bookings
* Fixed Elementor Forms integration treating some country names as dates
* Fixed undefined index PHP warning loading data from ActiveCampaign
* Fixed "Invalid email address" error with Mailerlite when Email Changes setting was set to Duplicate
* Fixed course enrollment tags not being applied when a LearnPress course was purchased using the WooCommerce Payment Methods Integration extension

= 3.37.29 - 7/26/2021 =
* Added Appointment Time field for sync with WooCommerce Appointments
* Added [event category tagging for Events Manager events](https://wpfusion.com/documentation/events/events-manager/#event-category-tagging)
* Added additional YITH WooCommerce Vendors fields for sync
* Improved - Wildcards * can now be used in the Allowed URLs setting for Site Lockout
* Improved - If a Gravity Forms email field is mapped to the primary CRM email address field, this will take priority over other email fields on the form
* Fixed "Hide if access is denied" setting not working with wpForo categories and some forum layouts
* Fixed GP Nested Forms feeds not running when there was no feed configured on the parent form
* Fixed email address changes for existing contacts not working with Autonami
* Fixed error syncing array formatted data to Intercom
* Fixed PHP warnings in the MemberMouse integration
* Fixed custom fields not syncing during a WP Ultimo registration
* Developers: Added `wpf_get_lookup_field()` function for getting the API name of the CRM field used for contact lookups (usually Email)
* Developers: Added `wpf_infusionsoft_safe_fields` filter (strips out Asian characters loaded over the API in field names to prevent XML parse errors)
* Developers: Added `wpf_beaver_builder_access_meta` filter

= 3.37.28 - 7/19/2021 =
* Fixed new contacts created with Autonami not being opted in to receive emails
* Fixed fatal error with Klick-Tip when making API calls using expired credentials

= 3.37.27 - 7/19/2021 =
* Added Event Categories field for sync with Events Manager
* Improved - Comments forms will be pre-filled with the temporary user's details during an auto-login session
* Improved - Booking dates will be formatted using the sitewide datetime format (set Settings > General) with WooCommerce Bookings and WooCommerce Appointments, when the field format is set to `text`
* Improved - Form submissions will record the page URL of the form in the logs
* Improved - If a field type is set to `text` then arrays will be converted to comma-separated strings for syncing
* Fixed &send_notification=false in a webhook URL triggering the new user welcome email
* Fixed datetime fields being synced to ActiveCampaign in 12h format (fixed to 24h format)
* Fixed fatal error trying to sync multidimensional arrays to the CRM
* Developers - added `wpf_get_users_with_tag( $tag )` function
* Developers - added `wpf_get_datetime_format()` function and `wpf_datetime_format` filter

= 3.37.26 - 7/12/2021 =
* Added Autonami CRM integration
* Added [Upsell Plugin integration](https://wpfusion.com/documentation/ecommerce/upsell-plugin/)
* Added [WooCommerce Memberships for Teams team meta batch operation](https://wpfusion.com/documentation/membership/teams-for-woocommerce-memberships/#syncing-historical-data)
* Improved - Stopped "Unknown Lists" from being loaded from HubSpot
* Fixed CSS classes getting removed from LearnDash lessons in focus mode since v3.37.25
* Fixed profile updates in the BuddyBoss app not syncing to the CRM
* Fixed default fields not being enabled for sync in the settings after first setting up the plugin
* Fixed PHP notice on WooCommerce order received page
* Fixed post types created with Toolset Types bypassing access rules
* Developers - Added wpf_get_tags() function
* Developers - Added action wpf_meta_box_content_{$post->post_type}
* Developers - All Beaver Builder nodes will pass through the wpf_beaver_builder_can_access filter, regardless of if they're protected by WP Fusion or not
* Developers - Refactored user_can_access() function for better performance and readability

= 3.37.25 - 7/6/2021 =
* Added support for LifterLMS Custom Fields addon
* Added support for applying tags with the default (site title) course with WPComplete
* Added Events Manager Registrations batch operation
* Added lock icon on LearnDash lessons that are protected by the Filter Course Steps setting
* Improved - If a FooEvents order is refunded, any tags applied to event attendees will automatically be removed
* Improved - Custom leadsource tracking variables registered via the wpf_leadsource_vars filter will show up on the Contact Fields list automatically
* Fixed fields not being synced when a WooCommerce Subscriptions subscription was renewed early
* Fixed MemberMouse settings page only listing 10 membership levels
* Fixed unique_id not showing up for sync with Ontraport
* Fixed WPComplete integration not detecting courses on custom post types
* Fixed fatal error sending social group invites with BuddyBoss when the Platform Pro plugin wasn't active
* Developers: Removed third parameter $user_tags from wpf_beaver_builder_can_access filter (for consistency with other page builders)
* Developers: Added [wpf_disable_crm_field_select4 and wpf_disable_tag_select4 filters](https://wpfusion.com/documentation/faq/performance/#admin-performance)

= 3.37.24 - 6/28/2021 =
* Added [Event Tickets attendees batch operation](https://wpfusion.com/documentation/events/the-events-calendar-event-tickets/#exporting-attendees)
* Added indicator in the logs when a pseudo field or read only field (i.e. user_registered) has been loaded from the CRM
* Added unique_id field for sync with Ontraport
* Added support for syncing user data from Advanced Custom Fields: Extended frontend forms
* Added Owner ID field for sync with Intercom
* Added Google Analytics fields for sync with Intercom
* Added indicator for email optin status to WooCommerce order sidebar meta box
* Improved - Contact fields settings will default to suggested usermeta / CRM field pairings
* Improved site tracking with Mautic after guest form submission
* Fixed the default owner for new Zoho contacts overriding a custom owner
* Fixed Apply Tags - Assignment Uploaded setting not saving on LearnDash lessons
* Fixed fatal error in admin with WooFunnels 1.5.0
* Fixed fatal error since v3.37.23 with BuddyBoss and registering a new user via MemberPress, when the Limit User Roles setting was active
* Changed WooCommerce function order_has_contact() to get_contact_id_from_order()

= 3.37.23 - 6/21/2021 =
* Added notification badge on WP Fusion Logs admin menu item to indicate when there are unseen API errors in the logs
* Added logging when a site tracking session has been started for a guest, for ActiveCampaign, HubSpot, and EngageBay
* Added Designation field for sync with FooEvents
* Improved - If the Limit User Roles setting is in use, and a user without a CRM contact record has their role changed to a valid role, a new contact record will be created in the CRM
* Fixed linked tags from LifterLMS courses being applied when a student was added to a membership that contains that course
* Fixed custom fields not syncing with FooEvents v5.5+ (Note: you will need to re-map any custom attendee fields in the WP Fusion settings)
* Fixed WooCommerce Memberships integration not applying tags for membership status when user memberships were edited in the admin
* Fixed async=true in an `update` webhook not loading the user's tags
* Fixed PHP warning in the PulseTechnologyCRM integration
* Fixed fatal error loading WooFunnels custom checkout fields for the WP Fusion settings with WooFunnels 1.4.2
* Removed wp_fusion()->access->can_access_terms cache (was causing more trouble than it was worth)

= 3.37.22 - 6/14/2021 =
* Added support for [auto-applied discounts with Easy Digital Downloads](https://wpfusion.com/documentation/ecommerce/easy-digital-downloads/#auto-applied-discounts)
* Improved - Return after login cookies will now be set if access is denied and the restricted content message is shown (previously it only worked after a redirect)
* Fixed auto-login loading the user's tags on every page load
* Fixed settings fields not showing on Easy Digital Downloads discounts
* Fixed Gravity Forms feed setting "Add to Lists" not saving correctly since Gravity Forms 2.5
* Fixed Push User Meta and Pull User Meta batch operations not working since v3.37.21
* Fixed +1 as country code option with Elementor Forms being synced to the CRM as a checkbox
* Fixed fatal error when enabling ActiveCampaign site tracking while WP Fusion is in staging mode
* Fixed PHP warning syncing array values with HighLevel
* Fixed PHP notices in Groundhogg integration
* Added wpf_get_users_with_contact_ids() function

= 3.37.21 - 6/7/2021 =
* Added [Ninja Forms entries batch export tool](https://wpfusion.com/documentation/lead-generation/ninja-forms/#syncing-historical-entries)
* Added [PulseTechCRM integration](https://thepulsespot.com/)
* Added a Send Welcome Email option in the Imported Users settings
* Added WP Fusion icon to Gravity Forms settings menu
* Fixed Gravity Perks Nested Forms feeds not being processed when the main form feed was processed
* Fixed Members integration trying to apply linked tags during registration before the user had been synced to the CRM
* Fixed multi-checkbox fields not syncing from Event Tickets Plus attendee registrations
* Fixed fatal error with Drip SDK and PHP 8

= 3.37.20 - 5/31/2021 =
* Added subscription failed tagging to GiveWP integration
* Added Affiliate Rejected tagging option to AffiliateWP
* Fixed Last Course Completed Date and Last Lesson Completed Date not syncing correctly with LearnDash
* Fixed LearnDash tags not being applied with Uncanny Toolkit's Autocomplete Lessons feature
* Fixed being unable to deactivate the license key if the license had never been activated on the current site
* Developers: removed register_shutdown_function() in API queue in favor of the "shutdown" WordPress action

= 3.37.19 - 5/24/2021 =
* Added [WS Form integration](https://wpfusion.com/documentation/lead-generation/ws-form/)
* Added support for [WooFunnels custom checkout fields](https://wpfusion.com/documentation/ecommerce/woofunnels/#custom-checkout-fields)
* Added option to apply tags when an Event Tickets attendee is deleted from an event
* Added error message when connecting to FluentCRM (REST API) and pretty permalinks aren't enabled on the CRM site
* Added option with WooFunnels to run WP Fusion actions on the Primary Order Accepted status rather than waiting for completed
* Improved - If you have more than 1,000 tags, they will be loaded in the admin via AJAX when you focus on the dropdown (improves admin performance)
* Improved site tracking with EngageBay (logged-in users will now be identified to the tracking script by email address)
* Improved reliability of license activation and deactivation (changed requests to GET to get past CloudFlare's firewall)
* Fixed Event Tickets treating the first attendee email field as the attendee's email address, even if it wasn't enabled for sync
* Fixed WP Fusion settings not saving on new Event Tickets tickets
* Fixed Tickera integration syncing attendees for pending orders
* Fixed Tickera integration not syncing attendees if "Show E-mail for Option For Ticket Owners" was disabled
* Fixed conflict with YITH WooCommerce Frontend Manager trying to access WP Fusion product settings from the frontend
* Developers: wp_fusion_init action will now only fire if WP Fusion is connected to a CRM

= 3.37.18 - 5/17/2021 =
* Added [Tickera integration](https://wpfusion.com/documentation/events/tickera/)
* Added [Give Gift Aid integration](https://wpfusion.com/documentation/ecommerce/give/#gift-aid)
* Fixed error connecting to FluentCRM (REST API) when there were no tags created in FluentCRM
* Fixed PHP warning trying to apply tags on view for deleted taxonomy terms
* Added wp_fusion_hide_upgrade_nags filter

= 3.37.17 - 5/14/2021 =
* Continued bugfixes for Elementor Pro Forms v3.2.0 compatibility — entries from pre-Elementor-3.2 forms sync correctly again, but if you edit the form in Elementor you will still need to re-do the field mapping
* Improved upgrade nags with WP Fusion Lite
* Improved - Moved Lite-specific functionality into class WPF_Lite_Helper
* Fixed PHP warning in FluentCRM REST API integration

= 3.37.16 - 5/12/2021 =
* Fixed tags not applying with FluentCRM since v3.37.14
* Fixed PHP warning in The Events Calendar month view

= 3.37.15 - 5/11/2021 =
* Fixed fatal error with BuddyPress (not BuddyBoss) when updating profiles, from v3.37.14
* Fixed Elementor Forms field maps not saving on new forms
* EngageBay bugfixes

= 3.37.14 - 5/10/2021 =
* Added [FluentCRM (REST API) CRM integration](https://wpfusion.com/plugin-updates/introducing-fluentcrm-rest-api/)
* Added [WooFunnels integration](http://wpfusion.com/documentation/ecommerce/woofunnels/)
* Added support for syncing the WooCommerce Appointments appointment date when an appointment status is changed to Pending or Confirmed
* Added notice to the logs when Filter Queries is running on more than 200 posts of a post type in a single request
* Improved WP Simple Pay logging for subscriptions
* Fixed edits to custom fields in FluentCRM not being synced back to the user record automatically
* Fixed First Name and Last Name fields not syncing with BuddyPress frontend profile updates if the XProfile fields hadn't been enabled for sync
* Fixed Gifting for WooCommerce Subscriptions integration setting the name of the gift recipient to the name of the purchaser
* Fixed "Remove tags from customer" setting being treated as enabled by default in Gifting for WooCommerce Subscriptions integration
* Fixed error loading Elementor Pro editor on sites that hadn't yet updated to Elementor Pro v3.2.0+
* Fixed WooCommerce Memberships batch operation getting hung up on deleted memberships
* Fixed EngageBay add tag / remove tag API endpoints
* Fixed fatal error trying to apply tags to a deleted FluentCRM contact
* Added action [wp_fusion_init](https://wpfusion.com/documentation/actions/wp_fusion_init/)
* Added action wp_fusion_init_crm

= 3.37.13 - 5/3/2021 =
* Added Payment Failed and Subscription Cancelled tagging options to WP Simple Pay integration
* Added Subscription End Date field for sync with WooCommerce Subscriptions
* Improved - user_registered will now be synced back to the CRM after a user is imported via webhook (if enabled)
* Improved - Removed "read only" indicator from HubSpot list name and included it in a label in the select box instead
* Fixed unwanted user meta getting synced back to the CRM when importing users if Push All was enabled
* Fixed feed settings not saving with Gravity Forms 2.5+
* Fixed Next Payment Date not being synced after a successful WooCommerce Subscriptions renewal
* Fixed Elementor Forms integration broken since Elementor Pro v3.2.0 (removed implementation of Fields_Map::CONTROL_TYPE) thanks @techjewel
* Fixed BuddyBoss group invites not working when WP Fusion was in use for groups member access controls
* Added wpf_event_tickets_attendee_data filter

= 3.37.12 - 4/26/2021 =
* Added Auto Login debug mode
* Added support for syncing Gravity Forms meta fields (Embed URL, Entry URL, Form ID, etc) with the CRM
* Added LearnDash Groups Enrollment Statuses batch operation d
* Added LearnDash Course Progress batch operation
* Added WooCommerce Memberships meta batch operation
* Improved - If Return After Login is enabled, and a form submission starts an auto-login session, the redirect will be triggered (Elementor Forms and Gravity Forms)
* Fixed Paid Memberships Pro Approval status not syncing when edited on the admin user profile
* Fixed pmpro_approval field not being picked up by Push User Meta

= 3.37.11 - 4/19/2021 =
* Added support for syncing ACF image fields to the CRM as image URLs instead of attachment IDs
* Improved support for syncing phone numbers with HighLevel
* Reverted change from 3.37.7 - bbPress topics will now use the query filtering mode set in the settings, rather than defaulting to Advanced (for improved performance)
* Fixed Paid Memberships Pro approval_status field not syncing when a membership level was changed
* Fixed "The link you followed has expired" message when deleting users, with Members active

= 3.37.10 - 4/15/2021 =
* Fixed infinite loop when loading bbPress forums index with Filter Queries set to Advanced and Restrict Forum Archives enabled

= 3.37.9 - 4/15/2021 =
* Fixed tags loaded via webhook not triggering automated enrollments since v3.37.8
* Added WP Fusion status metabox to WooCommerce order sidebar
* Added Add Only option to Contact Form 7 integration
* Improved - user_email and user_pass will no longer be loaded from the CRM during login if Login Meta Sync is enabled
* Improved error handing with HubSpot
* Improved - Filter Queries / Advanced will now limit the post query to the first 200 posts of each post type (for improved performance)
* Improved - Filter Queries will be bypassed while WP Fusion is processing a webhook
* Updated EngageBay API URL
* Fixed an empty last_name field at registration defaulting to last_updated (with FluentCRM)
* Fixed fatal error trying to install addon plugins before setting up the CRM API connection

= 3.37.8 - 4/12/2021 =
* Added Emercury CRM integration
* Added support for Easy Digital Downloads 3.0-beta1
* Added a notice to the LearnDash course and group settings panels when the LearnDash - WooCommerce integration plugin is active
* Improved support for Advanced Custom Fields (ACF) date fields
* Improved - If a license key is defined in wp-config.php using WPF_LICENSE_KEY then the site will be auto-activated for updates
* Improved - User-entered fields on the Contact Fields list will now show under their own heading
* Fixed BuddyBoss member type field not syncing during a Push User Meta operation
* Fixed special characters in MemberPress membership level names being synced to the CRM as HTML entities
* Fixed Resync Tags batch operation getting hung up with Ontraport trying to load the tags from a deleted contact
* Fixed fatal error error handling error-level HTTP response code with NationBuilder
* Fixed Capsule not loading more than 50 tags

= 3.37.7 - 4/5/2021 =
* Added WISDM Group Registration for LearnDash integration
* Added support for syncing date-type fields with Elementor forms
* Added support for Filter Queries with The Events Calendar events
* Added support for Filter Queries - Advanced with bbPress topics
* Added WP Fusion logo to Gravity Forms entry note
* Improved Filter Queries performance
* Fixed Filter Queries - Standard not working on search results
* Fixed HTTP API logging not working with MailJet
* Fixed MailJet treating Contact Not Found errors as irrecoverable
* Fixed Email Optin tags not being applied with WooCommerce integration
* Fixed duplicate State field with HighLevel
* Fixed Give donations_count and total_donated fields not syncing accurately during the first donation

__Developers:__
* Re-added wp_fusion()->access->can_access_posts cache
* Added wpf_query_filter_get_posts_args filter
* Added wpf_is_post_type_eligible_for_query_filtering filter
* Added wpf_should_filter_query filter
* Improved - Third parameter ($post_id) to wpf_user_can_access filter will now be false if the item being checked is not a post
* Changed wpf_user_id filter to wpf_get_user_id
* Removed wpf_bypass_filter_queries filter (in favor of wpf_should_filter_query)
* Fixed PHP notices in class-access-control.php

= 3.37.6 - 4/1/2021 =
* Removed wp_fusion()->access->can_access_posts cache (was causing a lot of access problems, needs more testing)
* Fixed wpf_tags_applied and wpf_tags_removed hooks not running when a webhook was received, since 3.37.4

= 3.37.5 - 3/30/2021 =
* Fixed Filter Course Steps setting with LearnDash integration treating Filter Queries as on, on some hosts
* Fixed url_to_postid() causing problems with WPML when Hide From Menus was active

= 3.37.4 - 3/29/2021 =
* Added Piotnet Forms integration
* Added Lock Lessons option to LearnDash courses
* Added Apply Tags - Approved setting to Events Manager events
* Added warning during HubSpot setup if site isn't SSL secured
* Added additional context to the "Can not operate manually on a dynamic list." error with HubSpot
* Improved - Active HubSpot lists will now show as "read only" when selected
* Improved performance with taxonomy term access rules
* Fixed YITH WooCommerce Frontend Manager triggering an error trying to load the WP Fusion settings panel on the frontend
* Fixed Filter Course Steps in LearnDash not properly adjusting the course step count
* Fixed ONTRApages plugin taking redirect priority over WP Fusion
* Added wp_fusion()->access->can_access_posts cache
* Added wp_fusion()->access->can_access_terms cache
* Added filter wpf_user_id
* Added filter wpf_restricted_content_message

= 3.37.3 - 3/22/2021 =

* __Added / Improved:__ 
	* Added Members integration
	* Added logging for when a linked tag is removed due to a user leaving a BuddyPress group
	* Added View in CRM links to admin user profile for FluentCRM and Groundhogg
	* Added View in CRM links to Easy Digital Downloads payment sidebar
	* Added WP Fusion status metabox to Gravity Forms single entry sidebar
	* Improved - Contact records created by guest form submissions or checkouts will now be identified to the ActiveCampaign tracking script
	* Improved upgrade process from pre-3.37 (fixes CRM fields getting lost in admin)
	* Improved - WooCommerce Memberships integration will try to avoid modifying any tags during a successful subscription renewal
	* Improved - Edits to fields on contact records in FluentCRM will now be synced back to the user record automatically
	* Improved - Disabled the "API Queue" with FluentCRM and Groundhogg
	* Improved - If a user is already logged in when coming from a ThriveCart success URL, they won't be logged in again

* __Bugfixes:__
	* Fixed Export Users batch operation not respecting Limit User Roles setting
	* Fixed tag changes not being synced back properly from FluentCRM
	* Fixed Member Access Controls with BuddyBoss denying access to all members if no tags were specified
	* Fixed BuddyBoss app notification segment not working with more than one selected tag
	* Fixed SQL error when searching for The Events Calendar events that are protected by tags, when Filter Queries was set to Advanced mode
	* Fixed WooCommerce Subscriptions meta fields not syncing for subscriptions that have no products
	* Fixed being unable to disable First Name and Last Name fields from sync
	* Fixed On-Hold WooCommerce orders from Bank Transfer payment gateway not being synced despite On-Hold being registered as a valid status
	* Fixed MemberPress integration syncing the details from the expiring transaction when switching between two free lifetime memberships
	* Fixed automated unenrollments not working with MemberPress transactions created using the Manual gateway

* __Developer Updates:__ 
	* Added "wpf_filtering_query" property to WP_Query objects that are being affected by Filter Queries - Advanced
	* Added wpf_leadsource_cookie_name filter
	* Added wpf_referral_cookie_name filter
	* Added wpf_get_current_user() function
	* Fixed fatal error on frontend if you selected Mautic as the CRM in the initial setup and saved the settings without entering API credentials
	* Fixed fatal error when running "EDD Recurring Payments statuses" batch operation
	* Fixed PHP 'WPF_Lead_Source_Tracking' does not have a method 'prepare_meta_fields' warning saving the settings
	* Fixed "Warning: Illegal string offset 'crm_field'"


= 3.37.2 - 3/15/2021 =
* Fixed fatal error with dynamic tagging and Event Tickets in 3.37.0
* Added expiration to cached Filter Queries results (thanks @trainingbusinesspros!)
* Added user_nicename field for sync

= 3.37.1 - 3/15/2021 =
* Fixed fatal error "Call to undefined function bbapp_iap()" in 3.37.0 when BuddyBoss App was active with IAP disabled
* Added "Default Not Logged-In Redirect" setting
* Added logging when wp_capabilities have been modified by data loaded from the CRM
* Fixed roles or capabilities loaded from the CRM being able to remove roles and/or capabilities from administrators
* Fixed wp_capabilities field not saved in correct format when loaded from the CRM

= 3.37.0 - 3/15/2021 =

* __Added / Improved:__
	* Added support for Create Tag(s) from Value with WooCommerce guest checkouts
	* Added support for Create Tag(s) from Value with Tribe Events guest registrations
	* Improved - When an Event Tickets attendee is moved to another ticket, their custom fields will be synced
	* Improved - Updated to support the new CartFlows admin UI
	* Improved - Added a safety check to prevent you from selecting the same tag for both Apply Tags - Enrolled and Link With Tag, on courses
	* Improved - wpForo usergroups will not be linked to tag changes if the user is an administrator (manage_options capability)

* __BuddyPress / BuddyBoss / bbPress:__
	* Added In-App Purchases support with BuddyBoss app (beta)
	* Added integration with BuddyBoss segments for app push notifications (beta)
	* Improved - If the BuddyPress groups directory page is protected, the restricted content message will replace the groups list
	* Improved - Added notice to BuddyPress group meta box to indicate when main groups page is protected by a tag
	* Fixed restricted bbPress topics not being hidden by Filter Queries - Advanced
	* Fixed restricted content message not displaying on restricted BuddyPress groups

* __Performance:__
	* Improved - Available tags and available fields have been moved to their own wp_options keys for improved performance
	* Improved - The wpf_options options key is now set to autoload, for improved performance
	* Improved - AJAX'ified the page redirect select in the meta box for improved admin performance
	* Improved - Moved the license check from a transient to an option to get around transient caching
	* Improved - Removed "Copy to related topics" from LearnDash meta box, for improved performance
	* Removed meta box notice about inheriting permissions from taxonomy terms (for improved performance)

* __Filter Queries:__
	* Improved performance with Filter Queries - Advanced, query results for the same post type will now be cached with wp_cache_set()
	* Fixed bbPress public topics being hidden when Filter Queries was set to Advanced
	* Fixed some post types registered by other plugins not showing as options for Filter Queries - Post Types
	* Added notice when Filter Queries is enabled on The Events Calendar event post types, and the Events Month Cache is enabled

* __Bugfixes:__
	* Fixed fields after a checkbox field on a Ninja Forms form being synced as boolean values
	* Fixed Create Tag(s) from Value creating errors with NationBuilder
	* Fixed tags not being applied to current user during form submission from 3.36.16
	* Fixed ActiveCampaign integration not treating 429 status code as an error
	* Fixed standard fields not being loaded from Autopilot
	* Fixed Autopilot integration creating new contacts when email address wasn't specified in update data
	* Fixed automatic name detection feature from 3.36.12 treating username as first_name
	* Fixed errors not being logged correctly while creating / updating GiveWP donors in the CRM

* __Developer Updates:__
	* Removed masking of ?cid= parameter from auto login URL since 3.36.5
	* Added wpf_bypass_query_filtering filter
	* Added wpf_query_filtering_mode filter
	* Added wpf_configure_setting_{$setting_id} filter

= 3.36.16 - 3/8/2021 =
* Added Filter Course Steps setting with LearnDash 3.4.0+
* Added search filter to select boxes in the admin
* Improved - If Staging Mode is enabled, site tracking scripts will be turned off with supported CRMs
* Improved EngageBay error handling
* Improved support for Filter Queries on LearnDash lessons in LearnDash v3.4.0 beta
* Improved - Elementor Forms integration data upgrades will now only run when the Elementor editor is active
* Fixed individual bbPress topics not respecting global Restrict Forums setting
* Fixed BuddyBoss profile types not being properly set via linked tag when removing and assigning a type in the same action
* Fixed menu items being hidden when Filter Queries was used in Standard mode and limited to specific post types
* Fixed PHP warning in Salesforce integration
* Fixed PHP warning when force-ending an auto login session

= 3.36.15 - 3/2/2021 =
* Fixed admin metabox settings getting reset when editing pages in Elementor since v3.36.12
* Fixed LifterLMS groups settings page not saving

= 3.36.14 - 3/1/2021 =
* Added Read Only indicator on non-writeable Salesforce fields
* Added wpf_hubspot_auth_url filter
* Added wpf_zoho_auth_url filter
* Added support for Datetime fields with ActiveCampaign
* Added Ticket Name field for sync with Tribe Tickets
* Improved support for multiselect fields with EngageBay
* Improved - Data will no longer be synced to Salesforce for read-only fields
* Improved - Users imported via a ThriveCart success URL will use the firstname and lastname parameters from the URL, if available
* Improved - Empty tags will now be filtered out and not applied during a WooCommerce guest checkout
* Improved - Custom fields are now separated from standard fields with Drip
* Improved - Username format for imported users will be set to FirstnameLastname by default on install if BuddyPress or BuddyBoss is active
* Improved - If FirstnameLastname or Firstname12345 are selected for the user import username format, and a user already exists with that username, the username will be randomized further
* Fixed tags from previous (still active) MemberPress memberships being removed when a member purchased a new concurrent membership
* Fixed wpForo custom profile fields not saving when loaded from the CRM
* Fixed ThriveCart success URLs triggering welcome emails to new users
* Fixed Mautic not loading more than 30 tags on some sites
* Fixed Ninja Forms integration using the last email address on a form as the primary email, not the first
* Fixed date-format fields sometimes not syncing correctly to Kartra
* Fixed Add New Field on Contact Fields list not saving when no CRM field was selected

= 3.36.13 - 2/24/2021 =
* Tested for WooCommerce 5.0.0
* Added support for syncing date, checkbox, and and multiselect type fields with Ninja Forms
* Improved error handing with Zoho
* Improved - Admin notices from other plugins will be hidden on the WPF settings page
* Fixed "Create tags from value" not working with form submissions
* Tribe Events Tickets RSVP bugfixes

= 3.36.12 - 2/22/2021 =
* Added tagging based on event registration status with Event Espresso
* Added Ticket Name and Registration Status fields for sync with Event Espresso
* Added support for the Individual Attendee Collection module in Event Tickets Plus
* Added track_event function to ActiveCampaign integration
* Improved HubSpot error logging
* Improved automatic detection for first_name and last_name fields during registration
* Improved performance - wpf-settings postmeta key will now be deleted on post save if there are no WPF settings configured for the post
* Improved - get_customer_id() in ActiveCampaign integration will now read the customer_id from a previously created cart, if available
* Improved - Log messages will now use the correct custom object type (instead of "contact") when a custom object is being edited
* Fixed query filtering not working on queries that used post__in
* Fixed BuddyPress group visibility rules taking priority over menu item visibility
* Fixed conflict with Woo Credits
* Fixed missing email address with Tribe Tickets guest RSVPs

= 3.36.11 - 2/15/2021 =
* Fixed PHP warning during login when Login Tags Sync is enabled

= 3.36.10 - 2/15/2021 =
* Added BuddyBoss Member Access Controls integration
* Added Give Funds & Designations integration
* Added View in Infusionsoft link to admin user profile
* Added support for Users Insights custom fields
* Added Home Page and Login Page options to "Redirect when access is denied" dropdown
* Improved - Automated membership level changes in Restrict Content Pro will now be logged to the Customer notes
* Improved - Login Tags Sync and Login Meta Sync features will now give up after 5 seconds if the CRM API is offline
* Improved - Refactored and standardized ConvertKit integration
* Improved - ConvertKit API timeout is now extended to 15 seconds
* Improved - LearnDash topics will now inherit their parent lesson settings if no access rules have been specified
* Improved - Added second argument $user_meta to wpf_map_meta_fields filter
* Fixed post type rules taking priority over single post access rules for query filtering
* Fixed JavaScript error on settings page when connected to ConvertKit
* Fixed PHP notices in admin
* wpForo bugfixes

= 3.36.9 - 2/9/2021 =
* Fixed undefined index PHP notices in v3.36.8

= 3.36.8 - 2/8/2021 =
* Added linked tags for LearnDash group leaders (thanks @dlinstedt)
* Added WP Fusion sync status meta box to the GiveWP payment admin screen
* Improved - Passwords generated by the LearnDash - ThriveCart extension will now be synced to the CRM after a new user is created if Return Password is enabled
* Improved logging for the Gamipress requirements system	
* Improved API error logging with Mailchimp
* Fixed Prevent Reapplying Tags setting not saving when un-checked
* Fixed Gamipress Requirements not being triggered when tags were loaded via a webhook
* Fixed menu visibility controls sometimes getting output twice
* Fixed some admin-only interfaces getting loading on the bbPress frontend profile and causing errors
* Fixed some bbPress frontend profile updates not syncing
* Fixed bbPress email address changes not syncing
* Fixed Gutenberg block visibility not respecting auto login sessions
* Fixed fatal error running WooCommerce Subscription Statuses batch operation on deleted subscriptions
* Fixed PHP notice "Constant DOING_WPF_BATCH_TASK already defined"
* Fixed deprecated function notice "WC_Subscriptions_Manager::user_has_subscription()"
* Fixed LifterLMS engagement settings not saving

= 3.36.7 - 2/1/2021 =
* Added ability to restrict BuddyPress group visibility based on tags and specify a redirect if access is denied
* Added BuddyPress User Profile Tabs Creator integration for profile tabs visibility control
* Added add_object() update_object() and load_object() methods to Salesforce, HubSpot, and Zoho
* Improved - When a user is removed from a BuddyBoss profile type via a linked tag, they will be given the Default Profile Type if one is set
* Fixed WooCommerce Orders batch exporter not recognizing custom "paid" order statuses for export
* Fixed the logs getting flushed if the filter form was submitted using the enter key
* Fixed error viewing AccessAlly settings page when AccessAlly was connected to ActiveCampaign
* Fixed array values not syncing to AgileCRM since v3.36.5

= 3.36.6 - 1/26/2021 =
* Fixed Gravity Forms integration not loading since v3.36.5
* Fixed tooltips not working in the logs
* Fixed HTTP API logging not showing up for ConvertKit
* Fixed some PHP notices

= 3.36.5 - 1/25/2021 =
* Added [[user_meta_if]] shortcode (thanks @igorbenic!)
* Added View In CRM link to admin user profile (at the moment just for ActiveCampaign)
* Added option to set default format for usernames for imported users
* Added notice in the logs when a user's role was changed via loading a new role from the CRM
* Added support for custom order form fields with WPPizza
* Added note about .csv imports to Import settings tab for Salesforce
* Improved - Divi access controls will now work on all modules (not just Text, Column, and Section)
* Improved - Role slug or name can be loaded from the CRM and used to set a user's role
* Improved - wpf_format_field_value in WPF_CRM_Base will stop imploding arrays (fixes issue syncing picklists options with commas in them to Salesforce and HubSpot)
* Improved Zoho error handling
* Improved - The ?cid= parameter will now be removed from the URL in the browser when using an auto login link
* Improved - Test Connection / Refresh Available Tags errors will now be shown on the top of the settings page (instead of the Setup tab)
* Fixed tag changes in FluentCRM not being synced back to WP Fusion
* Fixed dates loaded from HubSpot being loaded as milliseconds not seconds since the epoch
* Fixed date formatting not running on Ultimate Member standard fields when data was loaded from the CRM
* Fixed Ultimate Member not respecting custom date format when loading data from the CRM
* Fixed Ultimate Member Profile Completeness tags getting applied on every profile page view
* Fixed imported users not respecting "role" field loaded in user meta
* Fixed Gravity Forms and Formidable Forms integrations not being available in the wp_fusion()->integrations array
* Auto login bugfixes

= 3.36.4 - 1/8/2021 =
* Added Webhook Base URL field to general settings tab as a reference
* Added "ignoreSendingWebhook" parameter to EngageBay API calls
* Added Flush All Logs button at top of logs page
* Added debugging message to logs page for when the site runs out of memory building the Users dropdown
* Added third parameter $searchfield to wpf_salesforce_query_args filter
* Added logging for when WP Remote Users Sync is syncing tags to another connected site
* Added warning when curly quotes are detected in shortcode parameters
* Improved - Parentheses can now be used in shortcode attributes to match tags with square brackets in the name
* Improved - Salesforce webhook handler will now properly send a WSDL <Ack>false</Ack> when a webhook fails to be processed
* Improved - Logs will now show when an entry was recorded as a part of a batch operation
* Improved admin style for consistency with the rest of WP
* Improved NationBuilder error handling
* Fixed Ultimate Member not properly loading Unix timestamps from the CRM into Date type fields
* Fixed linked tag enrollments with Restrict Content Pro triggering additional tag changes in the CRM
* Fixed "Subscription Confirmation" type transactions getting picked up by the MemberPress Transactions Meta batch exporter
* Fixed MemberPress corporate account tags not applying
* Fixed MemberPress Subscriptions Meta batch tool syncing incorrect expiration date
* Fixed On Hold tags getting applied and removed when a WooCommerce Subscription was renewed via early renewal
* Fixed fatal error loading admin user profile when WP Fusion was not connected to a CRM
* Fixed PHP notices in Mailjet integration

= 3.36.3 - 1/13/2021 =
* Added First Name and Last Name fields for sync with Intercom
* Added Restrict Content Pro Joined Date fields for sync
* Added support for loading picklist / multiselect fields from Salesforce
* Improved logging for incoming webhooks with missing data
* Fixed broken ThriveCart auto-login from 3.36.1
* Fixed "quick update tags" not working with Mautic and ActiveCa,paign since 3.36.2
* Fixed PHP warning trying to get tag ID from tag label when no tags exist in the CRM
* Fixed returning null from wpf_woocommerce_customer_data marking the order complete

= 3.36.2 - 1/11/2021
* Added Apply Tags - Pending Cancellation with the Paid Memberships Pro Cancel on Next Payment Date addon
* Added "Quick Update Tags" support for Mautic webhooks (improved performance)
* Added indicator in the main access control meta box showing if the post is also protected by a taxonomy term
* Added wpf_woocommerce_sync_customer_data filter
* Improved - Customer meta data will no longer be synced during a WooCommerce renewal order
* Improved performance of the "update" and "update_tags" webhook methods with ActiveCampaign and Mautic
* Fixed HubSpot not converting dates properly
* Fixed contact ID not being passed from WooCommerce to Enhanced Ecommerce addon for registered users (from 3.36.1)

= 3.36.1 - 1/7/2021 =
* Improved - Refactored and optimized WooCommerce integration
* Improved asynchronous checkout for WooCommerce (will now be bypassed during IPN notifications)
* Improved - Refactored class WPF_API / webhooks handler
* Improved - Incoming duplicate webhooks will now be blocked
* Improved - Deactivating a license key will now also remove the license key from the settings page
* Fixed Cancelled tags not being applied with Paid Memberships Pro since v3.35.20
* Fixed WooCommerce billing details taking priority over user details when adding a new user in the admin
* Fixed bug applying and removing tags with Growmatik
* Fixed Pending tags not being applied for WooCommerce orders
* Added wpf_get_contact_id() function

= 3.36 - 1/4/2021 =
* Added HighLevel CRM integration
* Added Growmatik CRM integration
* Added WP Fusion payment status metabox to Easy Digital Downloads payment sidebar
* Added wpf_woocommerce_order_statuses filter
* Added wpf_woocommerce_subscription_status_apply_tags filter
* Added wpf_woocommerce_subscription_status_remove_tags filter
* Improved Asynchronous Checkout performance with CartFlows
* wpf_forms_pre_submission_contact_id will now run before wpf_forms_pre_submission
* Fixed auto-login session setting user to logged in when the contact ID was invalid
* Fixed PHP warning loading available users from Zoho

= 3.35.20 - 12/28/2020 =
* Added YITH WooCommerce Multi Vendor integration
* Added LaunchFlows integration
* Added option to limit Filter Queries to specific post types
* Added support for the Paid Memberships Pro - Cancel on Next Payment Date addon
* Added wpf_gform_settings_after_field_select action
* Fixed last name getting saved to first name field during a WooCommerce checkout
* Fixed WP Fusion trying to handle API error responses from ontraport.com that originated with other plugins
* Fixed LifterLMS billing and phone meta field keys

= 3.35.19 - 12/22/2020 =
* Fixed "Role not enabled for contact creation" notice when users register (from 3.35.17)

= 3.35.18 - 12/22/2020 =
* Fixed error when trying to add an entry to the logs before the CRM connection was configured

= 3.35.17 - 12/21/2020 =
* Added Start Date and End Date filters to the activity logs
* Added logging for when an API call to apply a tag isn't sent because the user already has that tag
* Added 1 second sleep time to Quentn batch operations to get around API throttling
* Added logging when the Import Trigger tags are removed as a part of a ConvertKit webhook
* Added additional Standard Fields for sync with Autopilot
* Improved - the available CRMs are now loaded on plugins_loaded to better support custom CRMs modules in other plugins
* Improved - Elementor form field values of "true" and "false" will now be treated as boolean with supported CRMs
* Fixed apostrophes getting escaped with slashes before being synced
* Fixed gender pronoun prefix getting synced with BuddyPress Gender-type fields
* Fixed header resync button on settings page not resyncing CRM lists
* Fixed Organizer Email overriding Attendee Email with Event Tickets Plus
* Fixed the No Tags filter in the users list showing all users
* Fixed Create Tags from Value on user_role conflicting with Limit User Roles setting
* Fixed Add New Field not working since 3.35.16
* Fixed tags not being applied with EDD Free Downloads addon

= 3.35.16 - 12/14/2020 =
* Added Work Address fields for sync with NationBuilder
* Added admin notice when logs are set to Only Errors mode
* Added link back to main settings page from the logs page
* Added "Apply registration tags" batch operation
* Added wpf_api_preflight_check filter
* Added Referrer's Username field for sync with AffiliateWP
* Added Affiliate's Landing Page field for sync
* Improved - Significantly reduced the amount of memory required for the main settings storage
* Improved error handling with Groundhogg and FluentCRM when those plugins are deactivated
* Improved support for auto login sessions on custom WooCommerce checkout URLs
* Fixed ActiveCampaign not loading more than 100 lists
* Fixed changed link tag warning appearing multiple times
* Fixed the new async checkout for WooCommerce not working with PayPal
* Fixed typo in the tooltip with the new wpf_format_field_value logging

= 3.35.15 - 12/8/2020 =
* Fixed "Invalid argument" warning listing custom fields with some CRMs
* Fixed Required Tags (All) in Elementor integration

= 3.35.14 - 12/7/2020 =
* Tested and updated for WordPress 5.6
* Added additional logging to show when meta values have been modified by wpf_format_field_value before being sent to the CRM
* Added "Additional Actions" to admin user profile (Push User Meta, Pull User Meta, and Show User Meta) for debugging purposes
* Added AffiliateWP Groups integration
* Added Referral Count, Total Earnings, and Custom Slug fields for sync with AffiliateWP
* Added functions wpf_get_crm_field(), wpf_is_field_active(), and wpf_get_field_type()
* Improved - All forms integrations will now use the first email address on the form as the primary email for lookups
* Updated ZeroBS CRM to Jetpack CRM
* Fixed default group not getting assigned in wpForo when a tag linked to a usergroup was removed
* Fixed Tribe Tickets integration treating the event organizer email as an attendee email
* Fixed importer getting hung up on more than 100 contacts with HubSpot
* Fixed bug with mapping Elementor Form fields when WPML was active
* Fixed WooCommerce auto-applied coupons not respecting Allowed Emails usage restrictions
* Fixed PHP warning in LearnDash 3.3.0

= 3.35.13 - 11/30/2020 =
* Added new experimental Asynchronous Checkout option (should be more reliable)
* Added warning when user_pass field is enabled for sync
* Added wpf_set_user_meta filter
* Improved - if no billing name is specified at WooCommerce checkout, the name from the user record will be used instead
* Improved error handling with Drip
* Improved Mailjet error handling
* Improved - If a MemberPress membership level has Remove Tags checked, then the tags will be removed when the member changes to a different membership level
* Fixed ArgumentCountError in WPF_BuddyPress::set_member_type()
* Fixed BuddyPress groups not being auto-assigned during a webhook
* Fixed BuddyPress custom fields not being loaded during a new user import
* Fixed user_registered getting loaded from the CRM when a user was imported
* Fixed Mailjet integration not loading more than 10 custom fields
* Fixed dates not formatted correctly with Mailjet

= 3.35.12 - 11/23/2020 =
* Added support for syncing custom attendee fields from Events Manager Pro
* Added warning in the logs for chaining together multiple automated enrollments based on tag changes
* Added wpf_woocommerce_attendee_data filter
* Added wpf_pmpro_membership_status_apply_tags filter
* Added wpf_pmpro_membership_status_remove_tags filter
* Added Re-Authorize With Hubspot button to re-connect via OAuth
* Improved logging for MailerLite webhooks
* Improved - MailerLite webhooks will now be deleted when resetting the settings
* Fixed Elementor visibility controls not showing on columns
* Fixed usage restriction settings not getting copied when a WooCommerce Smart Coupon was generated from a template
* Fixed PHP warning loading contact data with ActiveCampaign

= 3.35.11 - 11/18/2020 =
* Fixed Elementor widget visibility for "Required Tags (not)" bug from v3.35.9
* Added wpf_auto_apply_coupon_for_user filter
* Improved (No Tags) and (No Contact ID) filters in the All Users list

= 3.35.10 - 11/17/2020 =
* Fixed Elementor widget visibility bug from v3.35.9
* Removed dynamic tagging support from Groundhogg
* Users Insights bugfixes
* Fixed GiveWP not syncing billing address during the initial payment

= 3.35.9 - 11/16/2020 =
* Added WP Remote Users Sync integration
* Added support for FooEvents Bookings
* Added Apply Tags - Enrolled setting for LearnDash groups
* Added Last Order Date field for syncing with Easy Digital Downloads
* Added BuddyPress Profile Type field for sync
* Added logging for WP Fusion plugin updates
* Added Email Optin Default (checked vs un-checked) option for WooCommerce
* Removed "(optional)" string from Email Option checkbox on WooCommerce checkout
* Improved error handling for MailPoet
* Improved Elementor visibility settings migration from pre v3.35.8
* Improved Ninja Forms field mapping interface
* Improved - Moved LearnDash groups settings to Settings panel
* Fixed some issues with Sendinblue and email addresses that had capital letters
* Fixed syncing empty multiselects to EngageBay not erasing the selected values in the CRM
* Fixed multiselect fields not loading from EngageBay
* Fixed wpForo custom fields not loading from the CRM if they didn't start with field_
* Fixed linked tags not being removed when a BuddyPress profile type was changed
* Fixed Apply Tags select on LifterLMS access plans not saving
* Bugfixes for Gravity Forms User Registration

= 3.35.8 - 11/9/2020 =
* Added Tag Applied and Tag Removed as triggers for Gamipress points, ranks, and achievements
* Added option to sync Gamipress rank names when ranks are earned
* Added Required Tags (All) option to Elementor integration
* Added Logged In vs Logged Out setting to Elementor visibility controls
* Added License Renewal URL field for sync with Easy Digital Downloads Software Licensing 
* Added paused subscription tagging with MemberPress
* Added upgraded subscription tagging with MemberPress
* Added downgraded subscription tagging with MemberPress
* Improved - Multiselect values loaded from ActiveCampaign will now be loaded as arrays instead of strings
* Improved - Easy Digital Downloads Recurring Payment tags will no longer be removed and reapplied during a subscription renewal
* Fixed GiveWP integration not syncing renewal orders
* Fixed PHP warning in EngageBay integration when loading a contact with no custom properties
* Fixed - Updated for compatibility with wpForo User Custom Fields addon v2.x
* Fixed Advanced Custom Fields integration converting dates fields from other integrations

= 3.35.7 - 11/2/2020 =
* Added Owner ID field for sync with Groundhogg
* Added support for syncing avatars with BuddyPress and BuddyBoss
* Added option to sync field labels instead of values with Gravity Forms
* Added billing address fields for sync with GiveWP
* Improved handling of simultaneous LearnDash Course and BuddyPress group auto-enrollments
* Improved asynchronous checkout feature with WooCommerce during simultaneous orders
* Improved - Tags will no longer be removed during a refund if a customer has an active subscription to the refunded product
* Improved - Cancelled and On Hold subscription statuses with WooCommerce will now be ignored if the customer still has another active subscription to that product
* Fixed users having to log in twice if they tried to log in during an auto-login session
* Fixed auto-enrollments not working correctly with WPML when tags were loaded while the site was on a different language than the linked course or membership
* Fixed AffiliateWP "Approved" tags not being applied during AffiliateWP batch operation
* Fixed taxonomy term settings not saving when trying to remove protection from a term
* Fixed cancelled tags getting applied when an Easy Digital Downloads subscription was upgraded

= 3.35.6 - 10/26/2020 =
* Added email optin consent checkbox for WooCommerce
* Added support for custom address fields with Mailchimp
* Added Avatar field for sync with Jetpack CRM
* Improved support for syncing First Name and Last Name with Gist
* Improved - Post type archives will now respect wpf_post_type_rules access rules
* Updated updater
* Fixed PHP warnings in EDD Recurring Payments integration
* Fixed post-order actions not running on GiveWP renewal payments
* Fixed Gravity Forms syncing empty form fields
* Fixed widget settings not saving
* Fixed Teams for WooCommerce Memberships team name not syncing if the team was created manually
* Fixed error adding new Zoho Leads without a last name
* Fixed EDD Recurring Payments integration getting product tag settings from oldest renewal payment

= 3.35.5 - 10/19/2020 =
* Added Profile Picture field for sync with Groundhogg
* Added option to disable admin menu editor interfaces
* Added wpf_render_tag_multiselect_args filter
* Added gettext support to wpf-admin.js strings and updated .pot file

= 3.35.4 - 10/14/2020 =
* Fixed tags not saving on Gravity Forms feeds in v3.35.3
* Added event location fields for sync to Events Manager integration
* Added gettext support to wpf-options.js strings and updated .pot file
* Fixed JS bug when editing taxonomy terms
* Removed AWeber integration

= 3.35.3 - 10/12/2020 =
* Added Gravity PDF support
* Added BuddyBoss profile type statuses batch export tool
* Added Phone 3 through Phone 5 fields for syncing with Infusionsoft
* Improved support for Gravity Forms User Registration addon (removed duplicate API call)
* Gravity Forms beta 2.5 bugfixes
* Fixed Import Users tool with FluentCRM
* Fixed some funny stuff when auto-enrolling a user into a LearnDash group and course simultaneously
* Fixed Filter Queries setting not working on WooCommerce Related Products
* Fixed FacetWP JS error when Exclude Restricted Items was enabled
* Fixed settings page requiring an extra refresh after resetting before changing to a new CRM
* Continuing Kartra custom field bugfixes

= 3.35.2 - 10/6/2020 =
* Added doing_wpf_webhook() function
* Added additional validation and logging regarding setting user roles during import via webhook
* Fixed Ultimate Member account activation emails not being sent when a user was imported via a webhook
* Fixed "Wrong custom field format" error adding contacts to Kartra
* Fixed loading dropdown and multi-checkbox type fields from Kartra

= 3.35.1 - 10/5/2020 =
* Added refunded transaction tagging to the MemberPress integration
* Added logging for when an invalid role slug was loaded from the CRM
* Added datetime field support to Zoho integration
* Added support for dropdown and checkbox fields with Kartra
* Removed "Copy to X related lessons and topics" option with LearnDash courses
* Fixed Salesforce not connecting when the password has a slash character in it
* Fixed Gravity Forms PayPal conditional feed running prematurely when set to "Process this feed only if the payment is successful"
* Fixed Pods user forms syncing not detecting the correct user ID
* Fixed tags not applying when a WP E-Signature standalone document was signed
* Fixed FacetWP results filtering not recognizing the current user
* Fixed tags select not saving with FooEvents variations
* Fixed FooEvents event attendee tags not being applied to the WooCommerce customer if the customer was also an attendee
* Fixed Pods data not syncing during frontend form submission
* Fixed custom fields no longer syncing in FooEvents Custom Attendee Fields v1.4.19

= 3.35 - 9/28/2020 =
* Added FluentCRM integration
* Added beta support for Mautic v3
* Refactored wpf_render_tag_multiselect()
* Added support for EngageBay webhooks
* Added conditional logic support to Ninja Forms integration
* Improved - WooCommerce checkout fields will now be pre-filled when an auto-login link is used
* Improved FooEvents logging
* Improved error handling for NationBuilder
* Removed Groundhogg < 2.x compatibility code
* Fixed some funny stuff with Ninja Forms applying tags as numbers instead of names
* Fixed ConvertKit removing the Import Trigger tag after a user was imported
* Fixed custom fields not updating with Kartra
* Fixed FooEvent custom fields not syncing when no email address was provided for primary attendee
* Fixed "add" webhook changing user role to subscriber for existing users
* Fixed bbPress forum archive redirects not working for logged-out users
* Fixed Tribe Tickets not syncing first attendee

= 3.34.7 - 9/21/2020 =
* Added Toolset Types integration
* Added pre_user_{$field} filter on user data being synced to the CRM
* Improved support for custom fields with Kartra
* Fixed XProfile fields loaded from the CRM not being logged
* Fixed MailerLite importing subscribers during an update_tags webhook if multiple subscribers were in the payload
* Fixed date fields not syncing properly with Groundhogg
* Fixed ticket name not syncing with Events Manager
* Fixed applying tags on pageview in AJAX request during an auto login session not working on WP Engine

= 3.34.6 - 9/14/2020 =
* Added LifterLMS course track completion tagging
* Added linked tag indicators in the admin posts table for LearnDash courses and groups
* Added better handling for merged / changed contact IDs to Ontraport
* Improved - stopped syncing user meta during an Easy Digital Downloads renewal payment
* Fixed user meta not syncing when Pods user forms were submitted on the frontend
* Fixed JSON formatting error applying tags with AgileCRM
* Fixed import tool with MailerLite
* Fixed Process WP Fusion Actions Again order action showing on WooCommerce Subscriptions

= 3.34.5 - 9/7/2020 =
* Added Ticket Name field for sync with Events Manager
* Added Required Tags (not) to WooCommerce variations
* Improved support for syncing multi-checkbox fields with Gravity Forms
* Enabled Sequential Upgrade on WishList members added via auto-enrollment tags
* Fixed access controls not working on LearnPress lessons
* Fixed MailerLite integration using a case-sensitive comparison for email address changes
* Fixed Gravity Forms date dropdown-type fields not syncing
* Fixed Contact ID merge field not showing in Gravity Forms notifications editor
* Fixed "update" webhooks being treated as "add" with Salesforce when multiple contacts were in the payload
* Fixed - user_id will no longer be loaded during user imports
* Fixed - Renamed class WPF_Options to WP_Fusion_Options to prevent conflict with WooCommerce Product Filter

= 3.34.4 - 8/31/2020 =
* Added profile completion tagging option to BuddyBoss
* Added LifterLMS course enrollments batch tool
* Added a do_action( 'mepr-signup' ) to the MemberPress auto-enrollment process (for Corporate Accounts Addon compatibility)
* Added support for new background_request flag with the Ontraport API
* Fixed MailEngine SOAP warning when MailEngine wasn't the active CRM
* Fixed unwanted welcome emails with users imported via ConvertKit webhooks

= 3.34.3 - 8/24/2020 =
* Added a Default Account setting for Salesforce
* Improved Hide From Menus setting - will now attempt to get a post ID out of a custom link
* Improved - Add to cart button in WooCommerce will now be hidden by default if the product is restricted
* Moved ActiveCampaign tracking scripts to the footer
* Fixed ThriveCart auto login not setting name on new user
* Fixed user_meta shortcode not displaying field if value was 0
* Fixed PHP warning in WPForo integration
* Fixed Lead Source Tracking not working for guests
* Fixed bbPress / BuddyBoss forum access rules not working when the forum was accessed as a Group Forum

= 3.34.2 - 8/17/2020 =
* Added Events Manager fields for sync
* Added "raw" field type
* Added WooCommerce Points and Rewards integration
* Removed deactivation hook
* Fixed error registering for events with Events Manager
* Fixed LearnDash content inheriting access rules from wrong course ID when using shared steps
* Fixed tags not being applied during EDD checkout for logged in users who didn't yet have a contact record
* Fixed crash with auto login with Set Current User enabled, and BuddyBoss active
* Fixed importer with EngageBay
* Fixed ActiveCampaign not loading more than 100 custom fields

= 3.34.1 - 8/12/2020 =
* WordPress 5.5 compatibility updates

= 3.34 - 8/11/2020 =
* Added EngageBay CRM integration
* Added support for multiselect fields with Mautic
* Improved support for syncing wp_capabilities and role fields
* Asynchronous Checkout for WooCommerce will now be bypassed on subscription renewal orders
* Un-hooked MemberPress checkout actions from mepr-event-transaction-completed
* Fixed ActiveCampaign list Auto Responder campaigns not running on contacts added over the API
* Fixed custom WishList Member fields not loading from the CRM
* Fixed Required Tags (All) tags not showing on lock icon tooltip in post tables
* Fixed ActiveCampaign not loading more than 20 custom fields

= 3.33.20 - 8/3/2020 =
* Fixed broken WishList Member admin menu

= 3.33.19 - 8/3/2020 =
* Added Last Course Progressed field for sync with LearnDash
* Added Last Course Completed Date field for sync with LearnDash
* Added Last Lesson Completed Date field for sync with LearnDash
* Added wpf_user_can_access() function
* Added [[wpf_user_can_access]] shortcode
* Added support for quotes around tag names with Fluent Forms
* Added cancelled tagging to WishList member
* Improved WishList Member logging
* Updated some ActiveCampaign API calls to v3 API
* Fixed LearnDash course settings getting copied to lessons when using the Builder tab in LearnDash v3.2.2
* Fixed Form Auto Login with Fluent Forms
* Fixed wpForo custom fields not loading from CRM

= 3.33.18 - 7/27/2020 =
* Added HTTP API logging option
* Added LifterLMS Groups beta integration
* Added LifterLMS voucher tagging support
* Added X-Redirect-By headers when WP Fusion performs a redirect
* Added unlock utility for re-exporting Event Espresso registrations
* Improved Event Espresso performance
* Fixed Salesforce contact ID lookup failing with emails with + symbols
* Fixed auto-login warning appearing when previewing Gravity Forms forms in the admin

= 3.33.17 - 7/20/2020 =
* Added Organizer fields for syncing with Tribe Events / Event Tickets
* Added support for Groundhogg Advanced Custom Meta Fields
* Added timezone offset back to Ontraport date field conversion
* Added Refresh Tags & Fields button to top of WPF settings page
* Added notice when checking out in WooCommerce as an admin
* Added automatic detection for Formidable Forms User Registration fields
* Added out of memory and script timeout error handling to activity logs
* Added Gravity Forms referral support to AffiliateWP integration
* Added notice to LearnDash course / lesson / topic meta box showing access rules inherited from course
* Removed job_title and social fields from core WP fields on Contact Fields list
* Improved performance of update_tags webhook with ActiveCampaign
* Improved - last_updated usermeta key will be updated via WooCommerce when a user's tags or contact ID are modified (for Metorik compatibility)
* Improved - "Active" tags will no longer be removed when a MemberPress subscription is cancelled
* Improved - If the user_meta shortcode is used for a field that has never been loaded from the CRM, WP Fusion will make an API call to load the field value one time
* Improved - Updated has_tag() function to accept an array or a string
* Fixed restricted posts triggering redirects on a homepage set to Your Latest Posts in Settings >> Reading
* Fixed Groundhogg custom fields updated over the REST API not being synced back to the user record
* Fixed undefined function bp_group_get_group_type_id() in BuddyPress
* Fixed broken import tool with Drip
* Dynamic tagging bugfixes
* AgileCRM timezone tweaks

= 3.33.16 - 7/13/2020 =
* Added "Resync contact IDs for every user" batch operation
* Added "LearnDash course enrollment statuses" batch operation
* Added notice if an auto-login link is visited by a logged-in admin
* Improved query filtering on BuddyPress activity stream
* Improved - LearnDash courses, lessons, and topics will inherit access permissions from the parent course
* Improved - Split Mautic site tracking into two modes (Standard vs. Advanced)
* Improved - If API call to get user tags fails or times out, the local tag cache won't be erased
* Fixed a new WooCommerce subscription not removing the payment failed tags from a prior failed subscription for the same product
* Fixed Preview With Tag not working if the user doesn't have a CRM contact record
* Fixed restricted post category redirects not working if no tags was specified
* Fixed Hide Term on post categories hiding terms in the admin when Exclude Administrators was off
* Fixed import tool not loading more than 1,000 contacts with AgileCRM
* Fixed AgileCRM not properly looking up email addresses for some contacts
* Fixed get_tag_id() returning tag name with Groundhogg since v3.33.15
* Refactored WooCommerce Subscriptions integration and removed cron task
* Memberoni bugfixes
* Updated .pot file

= 3.33.15 - 7/6/2020 =
* Updated User.com integration for new API endpoint
* Added BuddyPress groups statuses batch operation
* Added ability to create new tags in Groundhogg via WP Fusion
* Added setting for additional allowed URLs to Site Lockout feature
* Added Generated Password field for syncing with WooCommerce
* Added Membership Level Name field for syncing with WishList Member
* Improved support for syncing phone numbers with Sendinblue
* Users added to a multisite blog will now be tagged with the Assign Tags setting for that site
* Fixed Zoho field mapping not converting arrays when field type was set to "text"
* Fixed replies from restricted bbPress topics showing up in search results
* Fixed WooCommerce attributes only being detected from first 5 products instead of 100
* Fixed deletion tags not being applied on multisite sites when a user was deleted from a blog
* Fixed MemberPress subscription data being synced when a subscription status changed from Active to Active
* Fixed duplicate tags being applied when a MemberPress subscription and transaction were created from the same registration
* Fixed Filter Queries (Advanced) hiding restricted posts in the admin
* Fixed Contact Form 7 integration running when no feeds were configured
* Fixed Woo Memberships for Teams team name not syncing when a member was added to a team
* Fixed Mautic merging contact records from tracking cookie too aggressively
* Fixed archive restrictions not working if no required tags were specified
* Event Espresso bugfixes

= 3.33.14 - 6/29/2020 =
* Added priority option for Return After Login
* Added option to set a default owner for new contacts with Zoho
* Added Membership Status field for sync with WooCommerce Memberships
* Added product variation tagging for FooEvents attendees
* Improved multiselect support with Zoho
* Improved support for syncing multi-checkbox fields with Formidable Forms
* Fixed refreshing the logs page after flushing the logs flushing them again
* Fixed Group and Group Type tags not being applied in BuddyPress when an admin accepted a member join request
* GiveWP bugfixes

= 3.33.13 - 6/23/2020 =
* Fixed invalid redirect URI connecting to Zoho
* Fixed Loopify and Zoho getting mixed up during OAuth connection process

= 3.33.12 - 6/22/2020 =
* Added Modern Events Calendar integration
* Added status indicator for Inactive people in Drip
* Improved support for Mautic site tracking
* Improved translatability and updated pot files
* Fixed updated phone to primary_phone with Groundhogg
* Fixed Paused tags not getting removed when a WooCommerce membership comes back from Paused status during a Subscriptions renewal
* Fixed "Cancelled" tags getting applied to pending members during MemberPress Membership Statuses batch operation
* Fixed duplicate log entries when updating BuddyPress profiles
* Fixed contact ID not being detected in some Mautic webhooks
* Fixed syncing multi-checkbox fields from WPForms
* Fixes for syncing expiration dates with WooCommerce Memberships
* Fixed PHP warning while submitting Formidable forms with multi-checkbox values

= 3.33.11 - 6/18/2020 =
* Fixed fatal conflict when editing menu items if other plugins hadn't been updated to the WP 5.4 menu syntax
* Fixed AgileCRM tag name validation false positive on underscore characters
* Fixed logs items per page not saving in WP 5.4.2
* Fixed compatibility with Gifting for WooCommerce Subscriptions v2.1.1

= 3.33.10 - 6/15/2020 =
* Added WooCommerce Appointments integration
* Added WP Crowdfunding integration
* Added Subscription Status field for syncing with WooCommerce Subscriptions
* Added Last Order Payment Method field for syncing with WooCommerce
* Added Last Order Total field for syncing with WooCommerce
* Added WishList Membership Statuses batch operation
* Added wpf_get_contact_id_email filter
* Added wpf_batch_objects filter
* Added tag name validation to AgileCRM integration
* Added super secret WooCommerce Subscriptions debug report
* Reduced the amount of data saved by the background worker to help with max_allowed_packet issues
* Fixed address fields not being synced back to WordPress after an admin contact save in Groundhogg
* Fixed bug in loading MemberPress radio field values from the CRM
* Fixed Active tags getting reapplied when a WooCommerce Subscription status changed to Pending Cancel
* Fixed WishList Member v3 custom fields not syncing
* Fixed WishList Member Stripe registration creating contacts with invalid email addresses
* Fixed s2Member custom fields not being synced on profile update

= 3.33.9 - 6/8/2020 =
* Added support for syncing custom event fields with Tribe Events Calendar Pro
* Added support for BuddyPress Username Changer addon
* Added option to apply tags when a user is added to a BuddyPress group type
* Added ld_last_course_enrolled field for syncing with LearnDash
* Added tagging based on assignment upload for LearnDash topics
* Added customer_id field for sync with Easy Digital Downloads
* Added Remove Tags from Customer setting to Gifting for WooCommerce Subscriptions integration
* Fixed essay-type answers not syncing properly from LearnDash quizzes
* Fixed auto-enrollments not working with TutorLMS paid courses
* Fixed import tool not loading more than 10 contacts with Mailchimp
* Fixed import tool not loading more than 100 contacts with MailerLite
* Fixed Gifting for WooCommerce Subscriptions integration not creating a second contact record for the gift recipient when billing_email was enabled for sync

= 3.33.8 - 6/2/2020 =
* WooCommerce Subscribe All the Things tags will now be applied properly during a WooCommerce Subscription Statuses batch operation
* Fixed width of tag select boxes in LearnDash settings panel when Gutenberg was active

= 3.33.7 - 6/1/2020 =
* Added Beaver Themer integration
* Added global Apply Tags to Group Members setting for Restrict Content Pro
* Added support for syncing multiple event attendees with Tribe Tickets
* Added Username for sync with Kartra
* Added wpf_salesforce_lookup_field filter
* Added staging mode notice on the settings page
* Moved LearnDash course settings onto Settings panel
* Refactored WooCommerce Memberships integration and updated tagging logic to match WooCommerce Subscriptions
* Salesforce will now default to the field configured for sync with the user_email field as the lookup field for records
* Improved logging with Salesforce webhooks
* Fixed WooCommerce billing_country not converting to full country name when field type was set to "text"
* Fixed auto-login session from form submission ending if Allow URL Login was disabled
* WishList Member bugfixes
* Fixed SSL error connecting to Zoho's Indian data servers

= 3.33.6 - 5/25/2020 =
* Added setting for Prevent Reapplying Tags (Advanced)
* Added GiveWP Donors and GiveWP Donations batch operations
* Added Total Donated and Donations Count fields for sync with GiveWP
* Added Pending Cancellation and Free Trial status tagging for WooCommerce Memberships
* Added wpf_disable_tag_multiselect filter
* Added CloudFlare detection to webhook testing tool
* Added global Apply Tags to Customers setting for Easy Digital Downloads
* GiveWP integration will now only sync donor data for successful payments
* Improved error handling for invalid tag names with Infusionsoft
* Improved support for multiselect fields with Contact Form 7
* Fixed Filter Queries not working on search results in Advanced mode
* Fixed bug causing failed contact ID lookup to crash form submissions
* Fixed Infusionsoft not loading tag names with Hebrew characters
* Klaviyo bugfixes

= 3.33.5 - 5/18/2020 =
* Added Give Recurring Donations support
* Added Pods user fields support
* Added Remove Tags option to Restrict Content Pro integration
* Added Payment Failed tagging to Paid Memberships Pro
* Added "Paid Memberships Pro membership meta" batch operation
* Refactored and optimized Paid Memberships Pro integration
* Improved error handling for Salesforce access token refresh process
* Improved Restrict Content Pro inline documentation
* Improved filtering tool on the All Users list
* Offending file and line number will now be included on PHP error messages in the logs
* Added alternate method back to the batch exporter for cases when it's being blocked
* Fixed Cancelled tags getting applied in Paid Memberships Pro when a member expires
* Fixed Filter Queries not working on the blog index page
* Maropost bugfixes

= 3.33.4 - 5/11/2020 =
* Facebook OG scraper will now bypass access rules if SEO Show Excerpts is on
* Added validation to custom meta keys registered for sync on the Contact Fields list
* Added compatibility notices in the admin when potential plugin conflicts are detected
* Updated Fluent Forms integration
* Updated MemberPress membership data batch process to look at transactions in addition to subscriptions
* LearnDash enrollment transients are now cleared when a user is auto-enrolled into a group
* Intercom integration will now force the v1.4 API
* Fixed Spanish characters not showing in Infusionsoft tag names
* Fixed logs showing unenrollments from LearnDash courses granted by LearnDash Groups
* Fixed warning when using Restrict Content Pro and the Groups addon wasn't active
* Fixed guest checkout tags not being applied in Maropost
* Fixed set-screen-option filter not returning $status if column wasn't wpf_status_log_items_per_page (thanks @Pebblo)

= 3.33.3 - 5/5/2020 =
* Fixed an empty WooCommerce Subscriptions Gifting recipient email field on checkout overwriting billing_email
* Fixed Infusionsoft form submissions starting auto-login sessions even if the setting was turned off

= 3.33.2 - 5/4/2020 =
* Added WP-Members integration
* Added Users Insights integration
* Added WooCommerce Shipment Tracking integration
* Added event check-in and checkout tagging for Event Espresso
* Added dynamic tagging support for Event Espresso
* Added Remove Tags option for WooCommerce Memberships for Teams integration
* Added Team Name field for sync to WooCommerce Memberships for Teams integration
* Added support for tagging on Stripe Payment Form payments with Gravity Forms
* Fixed "Remove Tags" setting not being respected during a MemberPress Memberships batch operation
* Fixed Ultimate Member linked roles not being assigned when a contact is imported via webhook
* Fixed welcome emails not being sent by users imported from a Salesforce webhook with multiple contacts in the payload

= 3.33.1 - 4/27/2020 =
* Fixed fatal error in Teams for WooCommerce Memberships settings panel

= 3.33 - 4/27/2020 =
* Added WP ERP CRM integration
* Added Gifting for WooCommerce Subscriptions integration
* Added Events Manager integration
* Added WPComplete tagging for course completion
* Added support for WPForo usergroups auto-assignment via tag
* Added PHP error handling to logger
* Added Double Optin setting to Mailchimp integration
* Added Time Zone and Language fields for Infusionsoft
* Badges linked with tags in myCred will now be removed when the linked tag is removed
* Improvements to asynchronous checkout process
* Fixed Hide From Menus filter treating a taxonomy term as a post ID for access control
* Fixed Gravity Forms feeds running prematurely on pending Stripe transactions

= 3.32.3 - 4/20/2020 =
* Added Apply Tags - Profile Complete setting to Ultimate Member
* Updated WishList member integration for v3.x
* Translatepress language code can now be loaded from the CRM
* Removed "Profile Update Tags" setting
* Fixed coupon_code not syncing with WooCommerce
* Fixed unnecessary contact ID lookup in user import process

= 3.32.2 - 4/17/2020 =
* Added wpf_woocommerce_user_id filter
* Fixed MailerLite subscriber IDs not loading properly on servers with PHP_INT_MAX set to 32
* Fixed Status field not updating properly with Drip
* Fixed fatal error checking if order_date field was enabled for sync during a WooCommerce renewal payment

= 3.32.1 - 4/13/2020 =
* Added fallback method for background worker in case it gets blocked
* Added Filter Queries setting to Beaver Builder Posts module
* Added support for defining WPF_LICENSE_KEY in wp-config.php
* Added debug tool for MailerLite webhooks
* Added Status field for syncing with Drip
* Added support for wpForo User Custom Fields
* WooCommerce Subscription renewal dates will now be synced when manually edited by an admin
* Improved importer tool with ActiveCampaign
* Improved logging for MailerLite webhooks
* Fixed Ultimate Member registrations failing to sync data with multidimentional arrays
* Fixed optin_status getting saved to contact meta with Groundhogg
* Fixed "Cancelled" tags getting applied when a WooCommerce subscription was trashed
* Fixed PHP warning in updater when license wasn't active
* Fixed CRM field labels not showing in Caldera Forms
* Fixed Mautic not importing more than 30 contacts using import tool

= 3.32 - 4/6/2020 =
* Added Loopify CRM integration
* Added support for Advanced Forms Pro
* Added Set Current User option to auto-login system
* Added Send Confirmation Emails setting for MailPoet
* Added Enable Notifications option for MailerLite import webhooks
* s2Member membership level tags will now be applied when an s2Member role is changed
* Moved logs to the Tools menu
* Removed bulk actions from logs page
* Updated admin menu visibility interfaces for WP 5.4
* Fixed metadata loaded from the CRM into Toolset Types user fields not saving correctly
* Fixed temporary passwords getting synced when a password reset was requested in Ultimate Member
* Fixed sub-menu items not being hidden if parent menu item was hidden
* Fixed Gravity Forms Entries batch operation not detecting all entries
* Fixed order_id and order_date not syncing during a WooCommerce Subscriptions renewal order
* Fixed WooCommerce Subscription product name not being synced when a subscription item is switched
* Fixed email address changes not getting synced after confirmation via the admin user profile

= 3.31 - 3/30/2020 =
* Added Quentn CRM integration
* Improved support for multiselect fields in Gravity Forms
* Improved Trial Converted tagging for MemberPress
* Fixed Defer Until Activation not working with Ultimate Member when a registration tag wasn't specified
* Fixed affiliate cookies not being passed to asynchronous WooCommerce checkouts

= 3.30.4 - 3/23/2020 =
* Added WP Simple Pay integration
* Added Apply Tags on View option for taxonomy terms
* contactId can now be used as a URL parameter for auto-login with Infusionsoft
* Contacts will no longer be created in Ontraport without an email address
* Removed non-editable fields from Ontraport fields dropdowns
* Improved Return After Login feature with LearnDash lessons
* Fixed lead source variables not syncing to Ontraport
* Fixed lead source tracking data not syncing during registration

= 3.30.3 - 3/20/2020 =
* Added additional tagging options for WooCommerce Subscribe All The Things
* Added WPGens Refer A Friend integration
* Fixed issue with saving variations in WooCommerce 4.0.0 causing variations to be hidden
* Fixed Long Text type fields not being detected with WooCommerce Product Addons
* Fixed duplicate content in Gutenberg block

= 3.30.2 - 3/18/2020 =
* Added MemberPress transaction data batch operation
* Fixed payment failures in MemberPress not removing linked tags

= 3.30.1 - 3/16/2020 =
* Added Oxygen page builder integration
* Added support for Formidable Forms Registration addon
* Added WooCommerce Request A Quote integration
* Added Remove Tags option to MemberPress
* Added automatic data conversion for dropdown fields with Ontraport
* Added data-remove-tags option to link click tracking
* Added wpf_woocommerce_billing_email filter
* Added wpf_get_current_user_id() function
* Added wpf_is_user_logged_in() function
* Auto login system no longer sets $current_user global
* Fixed WooCommerce auto-applied coupons not applying when Hide Coupon Field was enabled
* Fixed Duplicate and Delete tool for MailerLite email address changes
* Fixed Formidable Forms entries not getting synced when updated
* Fixed conflict between LearnDash [course_content] shortcode and Elementor for restricted content messages
* Fixed duplicate contact ID lookup API call for new user registrations with existing contact records
* Fixed Paid Memberships Pro membership level settings not saving
* Refactored and optimized MemberPress integration
* Removed WooCommerce v2 backwards compatibility
* Compatibility updates for Advanced Custom Fields Pro v5.8.8
* Stopped loading meta for new user registrations with existing contact records

= 3.30 - 3/9/2020 =
* Added SendFox integration
* Added compatibility with WooCommerce Subscribe All The Things extension
* Added auto-enrollment tags for TutorLMS courses
* Fixed MemberPress membership levels not getting removed when the linked tag is removed
* Tribe Tickets bugfixes and compatibility updates

= 3.29.7 - 3/5/2020 =
* Added support for WooCommerce order status tagging with statuses created by WooCommerce Order Status Manager
* Fixed restricted content message not being output when multiple content areas were on a page
* Fixed New User Benchmark not firing with Groundhogg
* Fixed changed email addresses not syncing to Sendinblue
* Fixed names not syncing with profile updates in BuddyPress

= 3.29.6 - 3/2/2020 =
* Added option to send welcome email to new users imported from ConvertKit
* Added Apply Tags - Trial and Apply Tags - Converted settings to MemberPress
* Added Coupon Used field for sync with MemberPress
* Added Trial Duration field for sync with MemberPress
* Added Default Optin Status option for Groundhogg
* New user welcome emails are now sent after tags and meta data have been loaded
* Expired and Cancelled tags will now be removed when someone joins a Paid Memberships Pro membership level
* Removed admin authentication cookies from background worker
* Stopped converting dates to GMT with Ontraport
* Fixed Tags (Not) visibility bug with Beaver Builder

= 3.29.5 - 2/24/2020 =
* Added optin_status field for syncing with Groundhogg
* Added Defer Until Activation setting to BuddyPress
* Added Defer Until Activation setting to User Meta Pro
* Added wc_memberships_for_teams_team_role field for syncing with WooCommerce Memberships for Teams
* Added bulk edit support to WP Courseware courses and units
* Added wpf_forms_args filter to forms integrations
* New contacts added to Groundhogg will be marked Confirmed by default
* Added "Apply Tags - Enrolled" setting to LearnDash courses
* Fixed WooCommerce auto applied coupons not respecting coupon usage restrictions
* Fixed Recurring Payment Failed tags not being applied with Restrict Content Pro
* Fixed Mautic not listing more than 30 custom fields
* Fixed Mailchimp not loading more than 200 tags

= 3.29.4 - 2/17/2020 =
* Added Last Coupon Used field for syncing with WooCommerce
* Added support for global addons with WooCommerce Product Addons
* Added default fields to MailerLite for initial install
* Leads will now be created in Gist instead of Users if the subscriber doesn't have a user account
* Fixed auto-enrollments not working with more than 20 BuddyBoss groups
* Fixed error with myCRED when the Badges addon was disabled
* Fixed messed up formatting of foreign characters in Gutenberg block
* Fixed conflict between Clean Login and Convert Pro integrations
* Fixed underscores not loading in Infusionsoft tag labels

= 3.29.3 - 2/10/2020 =
* Added support for EDD Custom Prices addon
* Added Required Tags (not) setting to access control meta box
* Added an alert to the status bar of the background worker if API errors were encountered during data export
* Manually changing a WooCommerce subscription status to On Hold will now immediately apply On Hold tags instead of waiting for renewal payment
* Fixed background worker status check getting interrupted by new WooCommerce orders
* Fixed user_activation_key getting reset when importing new users and breaking Better Notifications welcome emails
* Fixed PHP error manually adding a member to a team in WooCommerce Memberships for Teams

= 3.29.2 - 2/4/2020 =
* Added wpf_auto_login_cookie_expiration filter
* Added wpf_salesforce_query_args filter
* Fixed Approved tags getting applied with Event Espresso when registrations are pending
* Fixed tags not applying with Event Espresso

= 3.29.1 - 2/3/2020 =
* Added WP Ultimo integration
* Added notice when linked / auto-enrollment tags are changed on a course or membership
* Added wpf_event_espresso_customer_data filter to Event Espresso
* Added option to Event Espresso to sync attendees in addition to the primary registrant
* Added additional event and venue fields for syncing with FooEvents
* Added additional event and venue fields for syncing with Event Espresso
* Added wp_s2member_auto_eot_time for syncing with s2Member
* Fixed Invalid Data errors when syncing a number to a text field in Zoho
* Fixed "Return After Login" not working with WooCommerce account login
* Maropost bugfixes

= 3.29 - 1/27/2020 =
* Added Klick-Tipp CRM integration
* Logged in users and form submissions will now be identified to the Gist tracking script
* WooCommerce order status tags will now be applied even if the initial payment wasn't processed by WP Fusion
* WooCommerce Subscriptions v3.0 compatibility updates
* Improved webhooks with MailerLite (can now handle multiple subscribers in a single payload)
* Suppressed HTML5 errors in Gutenberg block
* Fixed tags not getting removed from previous variation when a WooCommerce variable subscription was switched
* Groundhogg bugfixes
* Maropost bugfixes
* Sendinblue bugfixes

= 3.28.6 - 1/20/2020 =
* Added linked tags to Ranks with myCred
* Added BuddyPress Account Deactivator integration
* Added Entries Per Page to Screen Options in logs
* Fixed special characters in tag names breaking tags loading with Infusionsoft
* Copper bugfixes

= 3.28.5 - 1/15/2020 =
* Fixed notice with ConvertKit when WP_DEBUG was turned on
* Auto login sessions will now end on the WooCommerce Order Received page

= 3.28.4 - 1/13/2020 =
* Added support for myCred ranks
* Added Event Start Time field for syncing with Event Espresso
* Improved Paid Memberships Pro logging
* Fixed being unable to remove a saved tag on a single AffiliateWP affiliate
* Fixed special characters not getting encoded properly with Contact Form 7 submissions
* Fixed bug in updater and changelog display
* Slowed down batch operations with ConvertKit to get around API throttling
* Added logging for API throttling with ConvertKit
* Added support for dropdown-type fields with Copper
* Copper bugfixes

= 3.28.3 - 1/9/2020 =
* Fixed ActiveCampaign contact ID lookups returning error message when connected to non-English ActiveCampaign accounts

= 3.28.2 - 1/9/2020 =
* Performance improvements with LearnDash auto enrollments
* Improved debugging tools for background worker
* Menu item visibility bugfixes
* Gist compatibility updates for changed API methods

= 3.28.1 - 1/6/2020 =
* Added option for tagging on LearnDash assignment upload
* Added Share Logins Pro integration
* Tags will now be removed from previous status when a membership status is changed in WooCommerce Memberships
* Improved handling for email address changes with Sendinblue
* Give integration bugfixes
* GetResponse bugfixes

= 3.28 - 12/30/2019 =
* Added Zero BS CRM integration
* Added MailEngine CRM integration (thanks @pety-dc and @ebola-dc)
* Added wpf_user_can_access and wpf_divi_can_access filters to Divi integration
* Added option to merge order status into WooCommerce automatic tagging prefix
* Removed extra column in admin list table and moved lock symbol to after the post title
* Ultimate Member roles that are linked with a tag will no longer leave a user with no role if the tag is removed
* Added additional WooCommerce Memberships logging
* Menu item visibility bug fixes

= 3.27.5 - 12/23/2019 =
* Added option to restrict access to individual menu items
* Added FacetWP integration
* Added support for AffiliateWP Signup Referrals addon
* Added export tool for Event Espresso registrations
* Fixed BuddyPress groups not running auto-enrollments when a webhook is received

= 3.27.4 - 12/16/2019 =
* Improved support for custom fields with FooEvents
* Added wpf_aweber_key and wpf_aweber_secret filters
* Logged in users and guest form submissions will now be identified to the Autopilot tracking script
* Event Espresso integration will now sync the event date from the ticket, not the event
* Fixed Elementor Popups triggering on every page for admins
* Autopilot bugfixes

= 3.27.3 - 12/11/2019 =
* Added support for WP Event Manager - Sell Tickets addon
* Added support for Popup Maker subscription forms
* Improvements to applying tags with Kartra using the new Kartra API endpoints
* Fixed billing address fields not syncing with PayPal checkout in s2Member
* Fixed Restrict Content Pro linked tags being removed when a user cancelled their membership before the end of the payment period
* Fixed missing email addresses causing BirdSend API calls to fail
* Fixed issues with non well-formed HTML content causing errors in inner Gutenberg blocks
* Fixed auto un-enrollment from LearnDash courses not working when course access was stored in user meta
* Fixed Advanced Custom Fields integration overriding date formats from WooCommerce

= 3.27.2 - 12/3/2019 =
* Fixed load contact method with Sendinblue
* Gutenberg block will no longer output HTML if there's nothing to display

= 3.27.1 - 12/2/2019 =
* Added GravityView integration
* Added batch tool for Restrict Content Pro members
* Added additional built in Gist fields for syncing
* Added option to tag customers based on WooCommerce order status
* Added support for global webhooks with Sendinblue
* Restrict Content Pro rcp_status field will now be synced when a membership expires
* WooCommerce Smart Coupons bugfixes
* Fixed ACF date fields not converting to CRM date formats properly
* Fixed bug in Import Tool with Sendinblue
* Fixed BirdSend only loading 15 available tags
* Fixed GMT offset calculation with Ontraport date fields

= 3.27 - 11/25/2019 =
* Added BirdSend CRM integration
* Added WP Event Manager integration
* Added support for triggering LifterLMS engagements when a tag is applied
* Fixed WPF settings not saving on CPT-UI post type edit screen
* Fixed Woo Memberships for Teams team member tags not being applied with variable product purchases
* Updated Gist API URL
* Fixed import tool not loading more than 50 contacts with Sendinblue
* wpf_tags_applied and wpf_tags_removed will now run when tags are loaded from the CRM

= 3.26.5 - 11/18/2019 =
* Added Groundhogg company fields for sync
* Added Event Name, Event Venue, and Venue Address fields for sync to Event Espresso
* Improved site tracking with HubSpot for guests
* eLearnCommerce login tokens can now be synced on registration
* Fixed refreshing Zoho access token with Australian data server
* Improved support for Country field with Groundhogg
* Style compatibility updates for WP 5.3

= 3.26.4 - 11/11/2019 =
* Added Toolset Types integration
* Added event_date field to Event Espresso integration
* Added signup_type field to NationBuilder
* Updated LifterLMS auto enrollments to better deal with simultaneous webhooks
* WP E-Signature bugfixes
* Access Key is no longer hidden when connected to MailerLite
* Improved Mautic site tracking
* Improved handling of merged contacts with Mautic
* Improved compatibility with Gravity Forms PayPal Standard addon
* Give integration bugfixes

= 3.26.3 - 11/4/2019 =
* Added Fluent Forms integration
* Added AffiliateWP affiliates export option to batch tools
* Added Australia data server integration to Zoho integration
* Apply Tags on View tags won't be applied for LearnDash lessons that aren't available yet
* Mautic tracking cookie will now be set after a form submission
* Give integration will now only apply tags when a payment status is Complete
* Fixed bug with Intercom API v1.4
* Fixed bug with The Events Calendar Community Tickets addon

= 3.26.2 - 10/28/2019 =
* Added "capabilties" format for syncing capability fields
* Added India data server support to Zoho integration
* Improved handling of multi-select and dropdown field types in PeepSo
* Fixed return after login for redirects on hidden WooCommerce products

= 3.26.1 - 10/21/2019 =
* Added Memberoni integration
* Improved integration with PilotPress login process
* Woo Subscriptions actions will no longer run on staging sites
* Fixed conflict with ThriveCart auto login and UserPro

= 3.26 - 10/14/2019 =
* Added Klaviyo integration
* Fixed PeepSo multi-checkbox fields syncing values instead of labels
* Fixed Elementor Pro bug when Elementor content was stored serialized

= 3.25.17 - 10/9/2019 =
* Added support for Ranks with Gamipress
* Enabled Import Users tab for Intercom
* Added "role" and "send_notification" parameters for ThriveCart auto login
* Performance improvements and bugfixes for background worker

= 3.25.16 - 10/7/2019 =
* Added custom fields support to Give
* Added option to hide restricted wpForo forums
* Added "ucwords" formatting option to user_meta shortcode
* Ultimate Member roles will now be removed when a linked tag is removed
* Fixed special characters getting escaped on admin profile updates

= 3.25.15 - 9/30/2019 =
* Added WP E-Signature integration
* Added UserInsights integration
* Added option to hide WPF meta boxes from non admins
* Added support for syncing multi-input Name fields for WPForms
* Added Filter Queries setting to Elementor Pro Posts and Portfolio widgets
* Updated ActiveCampaign site tracking scripts
* Fixed NationBuilder not loading more than 100 available tags
* Fixed GiveWP recurring payments treating the donor as a guest
* Fixed PeepSo first / last name fields not syncing on registration forms
* Fixed fatal error when initializing GetResponse connection
* All site tracking scripts will now recognize auto login sessions

= 3.25.14 - 9/23/2019 =
* Added WPPizza integration
* Existing Elementor forms will now update available CRM fields automatically
* Added new filters and better session termination to auto login system
* Payment Failed tags will now be removed after a successful payment on a WooCommerce subscription
* Disabled comments during auto login sessions
* Fixed bug with WooCommerce Points and Rewards discounts not applying
* Fixes for HubSpot accounts with over 250 lists
* Sendinblue bugfixes

= 3.25.13 - 9/18/2019 =
* Sendinblue bugfixes
* Bugfixes for syncing LearnDash quiz answers

= 3.25.12 - 9/16/2019 =
* Added support for Woo Checkout Field Editor Pro
* Added CartFlows upsell tagging
* Added support for CartFlows custom fields
* Added ability to sync LearnDash quiz answers to custom fields
* Fixed Gravity Forms entries export issue with Create Tag(s) From Value fields
* Fixed Mailchimp contact ID getting disconnected after email address change
* Fixed BuddyPress fields not being detected on custom profile types
* Fixed WooCommerce automatic coupons not being applied properly when a minimum cart total was set
* Fixed NationBuilder Primary address fields not syncing
* Fixed updating email addresses in WooCommerce / My Account creating duplicate subscribers in Drip

= 3.25.11 - 9/9/2019 =
* Added Site Lockout feature
* Added Ahoy messaging integration
* Added prefix option for WooCommerce automatic tagging
* Added additional AffiliateWP fields
* Gravity Forms batch processor can now process all unprocessed entries
* Increased limit on LifterLMS Memberships Statuses batch operation to 5000
* Salon Booking tweaks
* Fixed restricting Woo coupon usage by tag
* Fixed WooCommerce auto-discounts not being applied when cart quantities updated
* Fixed loading CRM data into Ultimate Member multi-checkbox fields
* Fixed Mailchimp compatibility with other Mailchimp plugins
* Copper bugfixes

= 3.25.10 - 9/4/2019 =
* Fixed home page not respecting access restrictions in 3.25.8

= 3.25.9 - 9/4/2019 =
* Changed order of apply and remove tags in Woo Subscriptions
* Fixed Hold and Pending Cancel tags not being removed in Woo Subscriptions after a successful payment
* Improved MemberPress expired tagging
* FooEvents compatibility updates
* Fixed tags not being removed with Ontraport

= 3.25.8 - 9/3/2019 =
* Added Salon Booking integration
* Added Custom Post Type UI integration
* Added GDPR Consent and Agreed to Terms fields for syncing with Groundhogg
* Enabled welcome email in MailPoet when a contact is subscribed to a list
* WooCommerce will now use the user email address as the primary email for checkouts by registered users
* Made background worker less susceptible to being blocked
* Improved ActiveCampaign eCom customer lookup
* Fixed content protection on blog index page
* Fixed students getting un-enrolled from LearnDash courses if they were enrolled at the group level and didn't have a course linked tag

= 3.25.7 - 8/26/2019 =
* Added Uncanny LearnDash Groups integration
* Added event_name and venue_name to Event Tickets integration
* Event Tickets bugfixes for RSVP attendees
* Fixed "Create tags from value" option for profile updates
* Fixed initial connection to Groundhogg on Groundhogg < 2.0
* Fixed typo in NationBuilder fields dropdown
* WooCommerce deposits compatibility updates

= 3.25.6 - 8/19/2019 =
* Fix for error trying to get coupons from WooCommerce order on versions lower than 3.7

= 3.25.5 - 8/19/2019 =
* Added ability to create new user meta fields from the Contact Fields list
* Added support for Event Tickets Plus custom fields with WooCommerce
* Added ability to sync event check-ins from Event Tickets Plus to a custom field
* Added "Create tag from value" option to WPForms integration
* Added support for sending full country name in WooCommerce
* Added option to restrict WooCommerce coupon usage by tag
* Improved "Source" column in WPF logs
* Fixed event details not syncing on RSVP with Event Tickets
* Fix for Uncanny LearnDash Groups bulk-enrollment adding contacts with multiple names
* Fixed email address changes with Infusionsoft causing opt-outs
* Reverted asynchronous checkouts to use background queue instead of single request
* Performance improvements on sites with Memberium active

= 3.25.4 - 8/12/2019 =
* Added auto-login by email address for MailerLite
* Added Portuguese translation (thanks @João Alexandre)
* MailerLite will now re-subscribe subscribers when they submit a form
* Improved OAuth access token refresh process with Salesforce
* Access control meta box now requires the manage_options capability
* Fixed variable tags not getting removed during Woo subscription hold if no tags were configured for the main product
* Variable tags will now be removed when a Woo subscription is switched and Remove Tags is enabled
* Fix for WooCommerce Orders export process crashing on deleted products

= 3.25.3 - 8/6/2019 =
* Fixed fatal error in BuddyPress integration when Profile Types module was disabled
* Fixed WooCommerce orders exporter crashing when trying to access a deleted product
* Fixed wpf_woocommerce_payment_complete action not firing on renewal orders

= 3.25.2 - 8/5/2019 =
* Added support for tag linking with BuddyBoss Profile Types
* Added support for restricting access to a single bbPress discussion
* Restricted topics in BuddyBoss / bbPress will now be hidden from the Activity Feed if Filter Queries is on
* Performance improvements when editing WooCommerce Variations
* Performance improvements with Drip and WooCommerce guest checkouts
* Added additional monitoring tools for background process worker
* Cartflows bugfixes for Enhanced Ecommerce addon
* Fixed WooCommerce variable subscription tags not being removed on Hold status
* Fixed bug with borders being output on restricted Elementor widgets
* Fixed bug when sending a store credit with WooCommerce Smart Discounts

= 3.25.1 - 7/29/2019 =
* Added CartFlows integration
* Groundhogg 2.0 compatibility
* Drip site tracking will now auto-identify logged in users
* Added WooCommerce Order Notes field for syncing
* Fixed "Affiliate Approved" tags not being added when creating an AffiliateWP affiliate via the admin

= 3.25 - 7/22/2019 =
* Added MailPoet integration
* Added EDD Software Licensing integration
* Added TranslatePress integration
* Added support for MemberPress Corporate Accounts addon
* Added support for BuddyPress fields to the user_meta shortcode
* Additional tweaks to Austrailian state abbreviations with Ontraport
* Groundhogg tags now update without manual sync
* Fixed FooEvents tags getting removed during Woo Subscriptions renewal

= 3.24.17 - 7/15/2019 =
* Added Tutor LMS integration
* Added option to tag AffiliateWP affiliates on first referral
* WooCommerce integration will no longer apply tags / update meta during a Subscriptions renewal
* Groundhogg will now load tags and meta immediately instead of requiring sync
* Fixed incorrect expiration dates with Paid Memberships Pro
* Improved handling for State fields with Ontraport
* Fixed MemberPress coupon settings not saving
* Added LifterLMS membership start date as a field for syncing
* Dynamic name / SKU tags will now be removed when an order is refunded

= 3.24.16 - 7/8/2019 =
* Added GTranslate integration
* Added Customerly webhooks
* Added social media fields to Kartra
* Added option to remove tags when a page is viewed
* Added automatic SKU tagging in WooCommerce for supported CRMs
* Fixed notifications going out when using the built in import tool
* Restrict Content Pro beta 3.1 compatibility
* Better handling for missing last names in Salesforce
* When a PMPro membership is cancelled / expired the membership level name will be erased in the CRM

= 3.24.15 - 7/1/2019 =
* Added option to completely hide a taxonomy term based on tags
* Added support for built in Ultimate Member fields
* Added option to automatically tag customers based on WooCommerce product names
* Capsule bugfixes
* Bugfixes for Preview with Tag feature
* Fixed syncing changed email addresses with BuddyPress

= 3.24.14 - 6/24/2019 =
* Added new default profile fields for Drip
* Added support for catching Salesforce outbound messages with multiple contact IDs
* Added wpf_salesforce_auth_url filter for Salesforce
* Added date_joined field for Kartra
* Added WooCommerce Subscriptions subscription ID field for syncing
* Added multiselect support for HubSpot
* Added support for File Upload field with Formidable Forms
* Fixed Infusionsoft API errors with addWithDupCheck method
* Bugfixes for Restrict Content Pro 3.0
* Formidable Forms 4.0 compatibility updates
* Slowed down HubSpot batch operations to get around API limits

= 3.24.13 - 6/17/2019 =
* Added option to sync eLearnCommerce auto login token to a custom field
* Mautic performance improvements
* Linked tags from the previous level will now be removed when an RCP membership is manually changed
* Fixed Mautic webhooks failing when the contact ID had changed due to a merge
* Intercom bugfixes
* Groundhogg bugfixes

= 3.24.12 - 6/14/2019 =
* Added option to enable HubSpot site tracking scripts
* Added order_id field for syncing with WooCommerce
* Improved auto enrollment for LearnDash courses
* Reduced API calls required during EDD checkout
* Fixed ConvertKit contact ID lookup failing
* Fixed tags from WooCommerce product attributes getting applied when the attribute wasn't selected

= 3.24.11 - 6/10/2019 =
* Added better handling for ACF relationship fields
* Added password update syncing for MemberPress
* Added option to apply tags when a discount is used in Easy Digital Downloads
* Added option to restrict usage of discounts by tags in Easy Digital Downloads
* Added Last Lesson Completed and Last Course Completed fields for syncing with LifterLMS
* Added Last Lesson Completed and Last Course Completed fields for syncing with LearnDash
* Added unsubscribe notifications for ConvertKit
* Added "wpf_salesforce_auth_url" filter for overriding Salesforce authorization URL
* Restrict Content Pro linked tags will now be removed when a member upgrades
* Improvements to "Return after login" feature
* Fixed creating a contact in Zoho without a last name
* Fixed Beaver Builder elements being hidden from admins
* Fixed Event Tickets Plus tags not applying during WooCommerce checkout
* Fixed Filter Queries "Advanced" mode not working on multiple queries
* Fixed slashes getting added to tags with apostrophes in Mautic
* Tweaks to Filter Queries (Advanced) option
* Prevented linked tags from being re-applied when a Woo membership unenrollment is triggered

= 3.24.10 - 6/3/2019 =
* Added details about configured tags to protected content in post list table
* Added ThriveCart auto login / registration
* Added Pending Payment tags for Event Espresso
* Fixed settings getting reset when enabling ActiveCampaign site tracking

= 3.24.9 - 5/28/2019 =
* Added Email Changed event for Drip
* Fix for tags sometimes not appearing in settings dropdowns

= 3.24.8 - 5/27/2019 =
* Added dynamic tagging based on field values (for supported CRMs)
* Added Is X? fields for NationBuilder
* Added GetResponse support
* Enabled Sequential Upgrade for WishList Member
* Preview With Tag now bypasses Exclude Admins setting
* Fixed WooCommerce checkout not applying tags after an auto login session
* Fixed slashes in image URLs with Gravity Forms multi-file upload fields

= 3.24.7 - 5/20/2019 =
* Added WooCommerce Fields Factory integration
* Added support for syncing WooCommerce attribute selections to custom fields
* Added option to apply tags when an AffiliateWP affiliate is approved
* Added option to disable "Preview With Tag" in admin bar
* Added support for date fields in User Meta Pro
* Fixed bug with Login Meta Sync
* Fixed MailChimp looking up contacts from other lists
* Fixed redirect causing multiple API calls with contact ID lookup in Mautic
* Fixed empty date type fields sending 1/1/1970 dates
* Added WooCommerce order date meta field for syncing

= 3.24.6 - 5/13/2019 =
* Added active lists to list dropdowns with HubSpot
* Removed admin bar JS link rewriting
* Fix for sending 0 in Gravity Forms submissions

= 3.24.5 - 5/9/2019 =
* Fixed tags not applying correctly with Async Checkout when a user registered a new account
* Fixed WooCommerce Subscriptions variation tags not applying
* Toolset fixes for profile updates
* Fix for 3.24.4 turning off Filter Queries setting

= 3.24.4 - 5/6/2019 =
* Added WP Affiliate Manager support
* Added customer tagging for AffiliateWP
* Added Organisation field for syncing to Capsule
* Added "Advanced" mode for Filter Queries setting
* Added support for single checkboxes with Formidable Forms
* Added ability to modify field data formats via the Contact Fields list
* Added IP address when adding new contacts with Mautic
* Added "Add Only" option for Elementor forms
* Added option to restrict visibility of EDD price options
* Paid Memberships Pro now sends meta data before applying tags
* Deleting a WooCommerce Subscription will no longer apply Cancelled tags
* Fixed auto-enrollments into MemberPress membership levels via webhook not returning passwords
* Fixed "Expired" tags not applying with MemberPress
* Fixed date formatting with HubSpot
* Fixed syncing date fields with Capsule
* Compatibility updates for custom field formatting with Mailerlite

= 3.24.3 - 4/29/2019 =
* Added option to return people to originally requested content after login
* Added Contact ID merge field to Gravity Forms
* Improved Preview With Tag functionality
* Auto login with Mailchimp now works with email address
* WooCommerce Transaction Failed tags will now be removed after a successful checkout
* Limit logging table to 10,000 rows
* Copper bugfixes
* Fix for error when using GForms User Registration during an auto login session

= 3.24.2 - 4/22/2019 =
* Added Caldera Forms integration
* Added additional status tags for Restrict Content Pro
* Changed Woo taxonomy tagging to just use the Category taxonomy
* Modified async checkouts to use a remote post instead of AJAX
* WPForms bugfixes
* Platform.ly bugfixes
* Consolidated forms functionality into new WPF_Forms_Helper class

= 3.24.1 - 4/16/2019 =
* Fix for Paid Memberships Pro checkout error

= 3.24 - 4/15/2019 =
* Added Sendlane CRM integration
* Added WooCommerce category tagging
* Added AgileCRM site tracking scripts
* Added support for BuddyPress taxonomy multiselect fields
* Fixed expiration tags in Paid Memberships Pro
* Fixed MemberPress auto-enrollments setting expiration date in the past
* Fixes for multiselects in BuddyPress
* Fixes for XProfile fields on secondary field groups

= 3.23.7 - 4/8/2019 =
* Added account deactivation tag trigger for Ultimate Member
* Added WooCommerce Wholesale Lead Capture support
* Toolset forms compatibility updates
* Fixed logic error with "Required Tags (all)" setting
* Fixed Preview With Tag functionality in Beaver Builder
* Updated AWeber subscriber ID lookup to only use selected list

= 3.23.6 - 4/1/2019 =
* Added Teams for WooCommerce Memberships integration
* Added unit completion tagging for WP Courseware
* Added Organization Name field for ActiveCampaign
* LearnPress compatibility updates
* Better AWeber exception handling
* AccessAlly bug fixes
* Bugfixes for PeepSo and auto login sessions
* Fix for changing email addresses with Drip
* Fix for AffiliateWP affiliate data not being synced when Auto Register Affiliates was enabled

= 3.23.5 - 3/25/2019 =
* Added LifterLMS quiz tagging (thanks @thomasplevy)
* Added ability to restrict usage of EDD discount codes (thanks @pjeby)
* Added merge settings option to bulk edit
* Added setting to remove "Additional Fields" section from settings
* Added "hide" option to Convert Pro targeting rules
* Expired / Cancelled / etc tags will now be removed when an EDD subscription is re-activated
* Popup Maker compatibility updates
* AccessAlly bug fixes
* Fix for failed WooCommerce order blocking tagging on subsequent successful re-try
* Fix for Required Tags (all) option greyed out
* Paid Memberships Pro bugfixes

= 3.23.4 - 3/18/2019 =
* Added Convert Pro CTA targeting integation
* Added FooEvents integration
* Added date-format parameter to user_meta shortcode
* Added "Required tags (all)" option to post restriction meta box
* Added option for login meta sync
* Added option for tagging when WooCommerce orders fail on initial payment
* Improved pagination in WPF logs
* Mailerlite bugfixes
* Improved HubSpot error logging
* MemberPress expired tagging bugfixes
* Fix for restricting BuddyPress pages

= 3.23.3 - 3/1/2019 =
* Fixed bug in MailerLite integration

= 3.23.2 - 3/1/2019 =
* Added Event Espresso integration
* Restrict Content Pro v3.0 compatibility fixes
* Added additional status triggers for Mailerlite webhooks
* Fixes for wpf_user_can_access filter
* ConvertKit fixes for unconfirmed subscribers

= 3.23.1 - 2/25/2019 =
* CoursePress integration
* Added incoming webhook test tool
* Added WooCommerce Subscriptions Meta batch operation
* Improved Ontraport site tracking script integration
* MemberPress will now remove the payment fail tag when a payment succeeds
* Bugfixes for CartFlows upsells with WooCommerce
* Fix for syncing checkbox fields in Elementor forms
* Fix for MailerLite accounts syncing more than 100 groups
* Fix for syncing profile updates via Gravity Forms
* Fixes for Free Trial Over tags in WooCommerce Subscriptions

= 3.23 - 2/18/2019 =
* Added Mailjet CRM integration
* Added payment failed tagging for MemberPress
* Javascript bugfix for tags with apostrophes in them
* Changes to WooCommerce variations data storage
* Added option to only allow auto-login after form submission
* Fix for email addresses with + sign in MailChimp
* Fix for changed checkout field names in Paid Memberships Pro
* Fix for contact ID lookup with HubSpot
* Fix for background worker when PHP's memory_limit is set to -1
* Added ability to restrict WooCommerce Shop page
* bbPress template compatibility fixes

= 3.22.3 - 2/12/2019 =
* Added tags for Expired status in MemberPress
* Added admin users column showing user tags
* Added fields for syncing Woo Subscriptions subscription name and next payment date
* Option to hide Woo coupon field on Cart / Checkout (used with auto-applying coupons)
* Fix for restricted WooCommerce products showing "password protected" message

= 3.22.2 - 2/5/2019 =
* Elementor Popups integration
* Added ability to auto-apply discounts via tag with WooCommerce
* Added option to embed Mautic site tracking scripts
* Added Mautic mtc_id cookie tracking for known contacts
* Additional Woo Memberships statuses for tagging
* Comments are now properly hidden when a post is restricted and no redirects are specified
* Set 1 second sleep time for Drip batch processes to avoid API timeouts
* Platform.ly bugfixes
* Platform.ly webhooks added
* Fixes for custom objects with Ontraport
* Fixes for WooCommerce Deposits not tagging properly

= 3.22.1 - 1/31/2019 =
* Groundhogg bugfixes
* Drift tagging bugfixes
* WooCommerce 2.6 compatibility fixes
* Woo Subscriptions tagging bugfixes

= 3.22 - 1/28/2019 =
* NationBuilder CRM integration
* Groundhogg CRM integration
* Added batch processing tool for WooCommerce Memerships
* Added pagination to AccessAlly settings page
* Added additional AffiliateWP registration fields for sync
* Fix for Sendinblue not creating contacts if custom attributes weren't present
* Fix for being unable to remove tags from Woo variations
* Fix for Woo variations not saving correctly with Woo Memberships active
* Fix for imports larger than 50 with Capsule

= 3.21.2 - 1/21/2019 =
* Added Clean Login support
* Added Private Messages integration
* Added custom fields support for Kartra
* Added AffiliateWP referrer ID field for syncing
* Added Toggle field support for Formidable Forms
* Added PeepSo VIP Icons support
* Added Gist webhooks support
* Moved Formidable Forms settings to "Actions" to support conditions
* Fix for custom fields not syncing with MemberMouse registration
* Fix for missing Ninja Forms settings fields
* Fix for syncing multiselects / picklists with Zoho
* Fix for error when processing Woo Subscriptions payment status hold
* Fix for AJAX applying tags by tag ID
* Fix for wpf_update_tags shortcode in auto-login sessions
* Fix for error creating contacts in Intercom without any custom fields
* Additional Capsule fields / Capsule field syncing bugfixes
* Better internationalization support
* Added PHP version notice for sites running less than 5.6

= 3.21.1 = 1/14/2019 =
* Elementor Forms integration
* Advanced Ads support
* WooCommerce Addons v3.0 support
* Additional tagging options for WooCommerce Memberships
* Fix for variation tags sometimes being lost when saving a Woo product
* Support for updating Capsule email/phone/address fields without a type specifier
* Added tagging for when a LearnDash essay is submitted
* Allow for using tag labels in link click tracking

= 3.21 - 1/5/2019 =
* Copper CRM integration
* Fixes for syncing PeepSo account fields
* Fixes for LearnDash quiz results tagging with Essay type questions
* Fix for incomplete address error with MailChimp
* Support for syncing with unsubscribed subscribers in ConvertKit
* Fixes for user IDs in ConvertFox (Gist)
* Bugfix for logged-out behavior in Elementor
* Added "Process WP Fusion actions again" option to WooCommerce Order Actions
* PHP 5.4 fixes

= 3.20.4 - 12/22/2018 =
* Fixed "return value in write context" error in PHP 5.5

= 3.20.3 - 12/22/2018 =
* Added logged-out behavior to Elementor
* Added support for syncing roles when a user has multiple roles
* Added Pull User Meta batch operation
* Added support for picklist fields in Zoho
* Fix for syncing MemberPress membership level name during batch process
* Additional logging for WC Subscriptions status changes
* Added import by Topic for Salesforce
* Admin settings update to support Webhooks

= 3.20.2 - 12/14/2018 =
* Fix for JS error with Gutenberg block

= 3.20.1 - 12/14/2018 =
* Added Gutenberg content restriction block
* Better first name / last name handling for ConvertFox
* Fix for Event Tickets settings not saving

= 3.20 - 12/8/2018 =
* Autopilot CRM integration
* Customerly CRM integration
* Added Ninja Forms integration
* Added option for per-post restricted content messages
* Added user_registered date field for syncing
* Added option to sync MemberPress membership level name at checkout
* Added handling for changed contact IDs with Infusionsoft
* Userengage bugfixes
* Fix for BuddyPress multi-checkbox fields not syncing
* Fix for PeepSo group members not getting fully removed from groups
* Fix for MemberMouse password resets not syncing
* Reverted to earlier method for getting Woo checkout fields to prevent admin errors in WPF settings
* Fixed bug where bulk-editing pages would remove WPF access rules

= 3.19 - 11/29/2018 =
* Drift CRM integration
* wpForo integration
* "Give" plugin integration
* Bugfixes for MemberPress coupons
* Better support for Gravity Forms User Registration
* UserEngage bugfixes
* Fixed compatibility bugs with other plugins using Zoho APIs
* Added wpf_batch_sleep_time filter
* Better user meta handling on auto-login sessions

= 3.18.7 - 11/21/2018 =
* Popup Maker integration
* GamiPress linked tag bugfixes
* Added import tool for Mautic
* Added support for updating email addresses in Kartra

= 3.18.6 - 11/15/2018 =
* WPForms integration
* UserEngage bugfixes
* Ability to set WooCommerce product tags to apply at the taxonomy term level
* Fix for incorrect membership start date with Paid Memberships Pro

= 3.18.5 - 11/12/2018 =
* Fixed bug with WooCommerce that caused WPF settings page not to load

= 3.18.4 - 11/10/2018 =
* WPComplete integration
* Added async method for batch webhook operations
* Fix for restricted WooCommerce variations not showing in admin when Filter Queries is enabled
* Bugfixes for detecting WooCommerce custom checkout fields
* Added payment conditions for Stripe and PayPal for Gravity Forms
* Now allows updating PeepSo role by changing field value in CRM

= 3.18.3 - 10/27/2018 =
* Added batch processing tool for Gravity Forms entries
* Fixed outbound message endpoint creating error messages in Salesforce
* Better support for custom checkout fields in WooCommerce
* LifterLMS course/membership auto-enrollment tweaks
* Added Payment Failed option to Woo Subscriptions

= 3.18.2 - 10/22/18 =
* Added support for Salesforce topics
* Added tagging for MemberPress coupons
* Added option to sync user tags on login
* Added support for multi-checkboxes to Gravity Forms integration
* Capsule bugfixes

= 3.18.1 - 10/14/2018 =
* Added Weglot integration
* Restrict Content Pro bugfixes
* Kartra bugfixes for WooCommerce guest checkouts
* Divi integration bugfixes
* More flexible Staging mode

= 3.18 - 10/4/2018 =
* Added Platform.ly support
* Added logged in / logged out shortcodes
* Added option to choose contact layout for new contacts with Zoho
* Fix for AgileCRM campaign webhooks
* Fixes for checkboxes with Profile Builder
* WooCommerce Addons bugfixes
* Added custom fields support for Intercom

= 3.17.2 - 9/22/2018 =
* Added Divi page builder support
* Added update_tags endpoint for webhooks
* Fix for "restrict access" checkbox not unlocking inputs correctly
* Fix for import button not working in admin
* Cleaned up WooCommerce settings storage

= 3.17.1 - 9/17/2018 =
* Added support for WooCommerce Addons
* Improved leadsource tracking
* Added webhooks support for SalesForce
* Bugfixes for ConvertKit with email addresses containing "+" symbol
* Support for syncing passwords generated by EDD Auto Register
* Fix for MailChimp syncing tags limited to 10 tags
* Additional sanitizing of input data

= 3.17 - 9/4/2018 =
* HubSpot integration
* SendinBlue bugfixes
* Zoho authentication bugfixes
* Profile Builder bugfixes
* Added support for Paid Memberships Pro Approvals
* Added option for applying a tag when a contact record is updated
* Support for Gravity Forms applying local tags during auto-login session

= 3.16 - 8/27/2018 =
* Added MailChimp integration
* Added SendinBlue CRM integration
* Easy Digital Downloads 3.0 support
* Profile Builder Pro bugfixes

= 3.15.3 - 8/23/2018 =
* Added Profile Builder Pro integration
* AccessAlly integration
* WPML integration
* Added "wpf_crm_object_type" filter for Salesforce / Zoho / Ontraport
* Fix for date fields with Salesforce
* Improvements to logging display for API errors
* Added Elementor controls to sections and columns
* Support for multi-checkbox fields with Formidable Forms

= 3.15.2 - 8/12/2018 =
* Fix for applying tags via Gravity Form submissions with ConvertKit
* Fixed authentication error caused by resyncing tags with Salesforce
* Added Job Alerts support for WP Job Manager
* Auto-login session will now end on WooCommerce cart or checkout

= 3.15.1 - 8/3/2018 =
* WooCommerce memberships bugfixes
* Fixed PeepSo groups table limit of 10 groups
* Option to sync expiry date for WooCommerce Memberships
* Beaver Builder fix for visibility issues
* WooCommerce Checkout Field Editor Integration
* Added "remove tags" checkbox for EDD recurring price variations
* Maropost CRM integration

= 3.15 - 7/23/2018 =
* Tubular CRM integration
* Flexie CRM integration
* Added tag links for PeepSo groups
* Elementor integration
* WishList Member bugfixes

= 3.14.2 - 7/15/2018 =
* Added WPLMS support
* Improved syncing of multi-checkboxes with ActiveCampaign
* Added support for Paid Memberships Pro Registration Fields Helper add-on

= 3.14.1 - 7/3/2018 =
* Auto-login tweaks for Gravity Forms
* Added option to apply tags on LearnDash quiz fail
* LearnDash bugfixes
* Improvements to AgileCRM imports by tag
* Kartra API updates
* Allowed loading PMPro membership start date and end date from CRM
* MemberMouse syncing updates from admin edit member profile

= 3.14 - 6/23/2018 =
* UserEngage CRM integration
* Fix for auto-login links with AgileCRM
* Added refund tags for price IDs in Easy Digital Downloads
* Added leadsource tracking support for Gravity Forms form submissions
* Added "not" option for Beaver Builder content visibility
* Added access controls to bbPress topics

= 3.13.2 - 6/17/2018 =
* Added support for tagging on subscription status changes for EDD product variations
* Added support for syncing WooCommerce Smart Coupons coupon codes
* Fixed Salesflare address fields not syncing
* Improvements on handling for changed email addresses in MailerLite
* Fix for LifterLMS access plan tags not displaying correctly
* Fix for foreign characters in state names with Mautic

= 3.13.1 - 6/10/2018 =
* Gravity Forms bugfix

= 3.13 - 6/10/2018 =
* Salesflare CRM integration
* Corrected Kartra App ID
* Added option to show excerpts of restricted content to search engines
* Fix for refund tags not being applied in WooCommerce for guest checkouts
* Fix for issues with linked tags not triggering enrollments while running batch processes
* Ability to pause a MemberMouse membership by removing a linked tag
* Bugfixes for empty tags showing up in select
* Better handling for email address changes with MailerLite
* Salesforce bugfixes

= 3.12.9 - 6/2/2018 =
* Added "apply tags" functionality for Restrict Content Pro
* Added tag link for Gamipress achievements
* Added points syncing for Gamipress
* Added support for WooCommerce Smart Coupons
* Fix for "refund" tags getting applied when a WooCommerce order is set to Cancelled
* Fix for LifterLMS "Tag Link" adding a blank tag
* Removed ability to add tags from within WP for Ontraport
* Gravity Forms bugfix for creating new contacts from form submissions while users are logged in
* Support for Tribe Tickets v4.7.2

= 3.12.8 - 5/27/2018 =
* Added GDPR "Agree to terms" tagging for WooCommerce
* BuddyPress bugfixes
* Added ability to apply tags when a coupon is used in Paid Memberships Pro
* Ultimate Member 2.0 fix for tags not being applied at registration
* Bugfix for tags sometimes not saving correctly on widget controls

= 3.12.7 - 5/19/2018 =
* Beaver Builder integration
* Ultimate Member 2.0 bugfixes
* Added delay to Kartra contact creation to deal with slow API performance
* Fix for Kartra applying tags to non-registered users
* Support creating tags from within WP Fusion for Ontraport
* Added delay in WooCommerce Subscriptions renewal processing so tags aren't removed and reapplied during renewals
* Changed template_redirect priority to 15 so it runs after Force Login plugin

= 3.12.6 - 5/16/2018 =
* Bugfix for errors showing when auto login session starts

= 3.12.5 - 5/15/2018 =
* Added support for WooCommerce Deposits
* Added event location syncing for Tribe Tickets Plus
* Added BadgeOS points syncing
* WP Courseware settings page fix for version 4.3.2
* Added option to only log errors (instead of all activity)
* Bugfix for WooCommerce checkout not working properly during an auto-login session

= 3.12.4 - 5/6/2018 =
* Added event date syncing for Tribe Tickets Plus events with WooCommerce
* Fix for Zoho customers with EU accounts
* Support for syncing passwords automatically generated by LearnDash
* Restrict Content Pro bugfixes
* UM 2.0 bugfixes
* Allowed for auto-login using Drip's native ?__s= tracking link query var
* Fix for syncing to date type custom fields in Ontraport

= 3.12.3 - 4/28/2018 =
* Bugfix for "undefined constant" message on admin dashboard

= 3.12.2 - 4/28/2018 =
* Better support for query filtering for restricted posts
* Fixed a bug that caused tags not to be removed properly in Ontraport
* Fixed a bug that caused tags not to apply properly on LifterLMS membership registration
* Fixed a bug with applying tags when achievements are earned in Gamipress
* Fixed a bug with syncing password fields on ProfilePress registration forms
* Additional error handling for import functions

= 3.12.1 - 4/12/2018 =
* ProfilePress integration
* Added option to apply tags when a user is deleted
* Added setting for widgets to *hide* a widget if a user has a tag
* Added option to apply tags when a LifterLMS access plan is purchased
* More robust API error handling and reporting
* Fixed a bug in MailerLite where contact IDs wouldn't be returned for new users

= 3.12 - 3/28/2018 =
* Added Zoho CRM integration
* Added Kartra CRM integration
* Added ConvertFox CRM integration
* Added WP Courseware integration
* Changed WooCommerce order locking to use transients instead of post meta values
* Added membership role syncing to PeepSo integration
* Added User ID as an available field for sync

= 3.11.1 - 3/21/2018 =
* Added GamiPress integration
* Added PeepSo integration
* Added option to just return generated passwords on import, without requiring ongoing password sync
* "Push user meta" batch operation now pushes Paid Memberships Pro meta data correctly
* Fixed bug where ampersands would fail to send in Infusionsoft contact updates
* Cleaned up scripts and styles in admin settings pages

= 3.11 - 3/15/2018 =
* Capsule CRM integration
* Added LearnPress LMS integration
* Added batch-resync tool for LifterLMS memberships
* Tags linked to LearnDash courses will now be applied / removed when a user is manually added to / removed from a course
* Bugfixes for export batch operation
* Added "Pending Cancellation" tags for WooCommerce Subscriptions
* Improved handling for displaying user meta when using auto-login links
* Fix for AWeber API configuration errors breaking setup tab
* Improved AgileCRM handling for custom fields
* Added filter for overriding WPEP course buttons for restricted courses

= 3.10.1 - 3/3/2018 =
* Fixed a bug where sometimes a contact ID wouldn't be associated with an existing contact when a new user registers
* Added start date syncing for Paid Memberships Pro

= 3.10 - 2/24/2018 =
* MailerLite CRM integration
* Bugfixes for auto-login links with Gravity Forms
* MemberMouse bugfixes

= 3.9.3 - 2/19/2018 =
* Added option for auto-login after Gravity Form submission
* Changed auto-login links to use cookies instead of sessions
* Allowed the [user_meta] shortcode to work with auto-login links
* Modified Infusionsoft contact ID lookup to just use primary email field

= 3.9.2 - 2/15/2018 =
* Proper state and country field handling for Mautic
* Fix for malformed saving of Tag Link field in LifterLMS course settings

= 3.9.1 - 2/12/2018 =
* Added "Apply Tags - Cancelled" to Paid Memberships Pro settings
* Added Ontraport affiliate tracking
* Added Ontraport page tracking
* Improved LearnDash content restriction filtering
* Optimized unnecessary contact ID lookups when Push All User Meta was enabled

= 3.9 - 1/31/2018 =
* Added AWeber CRM integration
* Linked tags now automatically added / removed on LearnDash group assignment
* Added auto-enrollment for LifterLMS courses
* Added post-checkout process locking for WooCommerce to reduce duplicate transactions

= 3.8.1 - 1/21/2018 =
* Added [else] method to shortcodes
* Added loggedout method to shortcodes
* Performance enhancements
* ConvertKit now auto-removes webhook tags
* Added option to apply tags when a WooCommerce subscription converts from free to paid

= 3.8 - 1/8/2018 =
* Intercom CRM integration
* myCRED integration
* Added bulk import for Salesforce
* Added batch processing for s2Member
* Fixed bug with administrators not being able to view content in a tag-restricted taxonomy

= 3.7.6 - 12/30/2017 =
* Added batch processing tool for MemberPress subscriptions
* Added setting to exclude restricted posts from archives / indexes
* Added ActiveCampaign site tracking
* Added Infusionsoft site tracking
* Added Drip site tracking

= 3.7.5 - 12/21/2017 =
* WooCommerce bugfixes

= 3.7.4 - 12/15/2017 =
* Improvements to tag handling with ConvertKit
* Added collapsible table headers to Contact Fields table
* Fixed bug in Mautic with applying tags to new contacts
* UserPro bugfixes

= 3.7.3 =
* Added global setting for tags to apply for all WooCommerce customers
* Fixed issue with restricted WooCommerce variations not being hidden
* Fixed bug with syncing Ultimate Member password updates from the Account screen
* Fixed LifterLMS account updates not being synced

= 3.7.2 =
* UserPro bugfixes
* Fixed hidden Import tab

= 3.7.1 =
* Fix for email addresses not updating on CRED profile forms
* Fix for Hold / Failed / Cancelled tags not being removed on WooCommerce subscription renewal

= 3.7 =
* Added support for the Mautic marketing automation platform
* Toolset CRED integration (for custom registration / profile forms)
* Fix for newly added tags not saving to WooCommerce variations

= 3.6.1 =
* Updated for compatibility with Ontraport API changes

= 3.6 =
* WishList Member integration
* Fixed tag fields sometimes not saving on WooCommerce variations
* Added async checkout for EDD purchases

= 3.5.2 =
* Improvements to filtering products in WooCommerce shop
* Significantly sped up and increased reliability of WooCommerce Asynchronous Checkout functionality
* Added ability to apply tags when refunded in EDD
* Better Tribe Events integration

= 3.5.1 =
* Improvements to auto login link system
* Added duplicating Gravity Forms feeds
* Restrict Content Pro bugfixes
* Added admin tools for resetting wpf_complete hooks on WooCommerce / EDD orders

= 3.5 =
* Added support for Ultimate Member 2.0 beta
* Added Tribe Events Calendar support (including support for Event Tickets and Event Tickets Plus)
* Added list selection options for Gravity Forms with ActiveCampaign
* Fixed variable tag fields not saving in WooCommerce
* Fixed new user notification emails sometimes not going out
* ActiveCampaign API performance enhancements

= 3.4.1 =
* Bugfixes

= 3.4 =
* Added access controls for widgets
* Improved "Preview with Tag" reliability
* WooCommerce now sends country name correctly to Infusionsoft
* Added logging support for Woo Subscriptions
* Support for additional BadgeOS achievement types
* Support for switching subscriptions with Woo Subscriptions
* Added batch processing options for Paid Memberships Pro
* Fixed issue with shortcodes using some visual page builders

= 3.3.3 =
* Added BadgeOS integration
* Staging mode now works with logging tool
* "Apply to children" now applies to nested children
* Added backwards compatibility support for WC < 3.0
* Passwords auto-generated by WooCommerce can now be synced
* Fixed issues with MemberPress non-recurring products
* Updated EDDSL plugin updater
* Fixes for Gravity Forms User Registration add-on
* Cleaned up internal fields from Contact Fields screen
* Sped up Import tool for Drip
* Option to disable API queue framework for debugging

= 3.3.2 =
* ConvertKit imports no longer limited to 50 contacts
* Restrict Content Pro improvements
* Fixed bug when adding new tags via tag select dropdown
* Fixed bug with using tag names in wpf shortcode on some CRMs
* Importing users now respects specified role
* Fixed error saving user profile when running BuddyPress with Groups disabled

= 3.3.1 =
* 3.3 bugfixes

= 3.3 =
* New features:
	* Added new logging / debugging tools
	* Contact Fields list is now organized by related integration
	* Added options for filtering users with no contact ID or no tags
	* Added ability to restrict WooCommerce variations by tag
* New Integrations:
	* WooCommerce Memberships
	* Simple Membership plugin integration
	* WP Execution Plan LMS integration
* New Integration Features:
	* MemberMouse memberships can now be linked with a tag
	* Expiration Date field syncing for Restrict Content Pro subscriptions
	* BuddyPress groups can now be linked with a tag
	* Added Payment Method field for sync with Paid Memberships Pro
	* Expiration Date can now be synced for Paid Memberships Pro
	* Added registration date, expiration date, and payment method for MemberPress subscriptions
	* Added "Apply tags when cancelled" field to MemberPress subscriptions
* Bug fixes:
	* Fixed bugs with editing tags via the user profile
	* user_meta Shortcode now pulls data from wp_users table correctly
	* "Apply on view" tags will no longer be applied if the page is restricted
	* Link with Tag fields no longer allow overlap with Apply Tags fields in certain membership integrations
	* AgileCRM fixes for address fields
* Enhancements:
	* Optimized many duplicate API calls
	* Added Dutch and Spanish translation files

= 3.2.1 =
* Bugfixes

= 3.2 =
* Salesforce integration
* Fixed issue with automatically assigning membership levels in MemberPress via webhook
* Fixed incompatibility with Infusionsoft Form Builder plugin
* Improvements to Drip integration
* Improvements to WooCommerce order batch processing tools
* Numerous bugfixes and performance enhancements

= 3.1.3 =
* Drip CRM can now trigger new user creation via webhook
* User roles now update properly when changed via webhook
* Import tool can now import more than 1000 contacts from Infusionsoft
* Gravity Forms bugfixes
* WP Engine compatibility bugfixes

= 3.1.2 =
* Added filter by tag option in admin Users list
* Added ability to restrict all posts within a restricted category or taxonomy term
* Added ability to restrict all bbPress forums at a global level
* Fixed bug with Ultimate Member's password reset process with Infusionsoft
* Added additional Google Analytics fields to contact fields list
* Bugfix to prevent looping when restricted content is set to redirect to itself

= 3.1.1 =
* Fixed inconsistencies with syncing user roles
* Additional bugfixes for WooCommerce 3.0.3

= 3.1.0 =
* Added built in user meta shortcode system
* Added support for webhooks with ConvertKit
* Updates for WooCommerce 3.0
* Additional built in fields for Agile CRM users
* Fixed bug where incorrect tags would be applied during automated payment renewals
* Fixed debugging log not working

= 3.0.9 =
* Added leadsource tracking to new user registrations for Google Analytics campaigns or custom lead sources
* Link click tracking can now be used on other elements in addition to links
* Agile CRM API improvements
* Misc. bugfixes

= 3.0.8 =
* Drip bugfixes
* Agile CRM improvements and bugfixes
* Added EDD payments to batch processing tools
* Added EDD Recurring Payments to batch processing tools
* Misc. UI improvements
* Bugfixes and speed improvements to batch operations

= 3.0.7 =
* Integration with User Meta plugin
* Fixed bug where restricted page would be shown if no redirect was specified
* Better support for Ultimate Member "checkboxes" fields

= 3.0.6 =
* Import tool has been updated to use new background processing system
* Added WordPress user role to list of meta fields for sync
* Support for additional Webhooks with Agile CRM
* Bugfix for long load times when getting user tags

= 3.0.5 =
* New tags will be loaded from the CRM if a user is given a tag that doesn't exist locally
* Resync contact IDs / Tags moved from Resynchronize button process to Batch Operations
* ActiveCampaign integration can now load all tags from account (no longer limited to first 100)
* Bugfix for LifterLMS memberships tag link

= 3.0.4 =
* Paid Memberships Pro bugfixes

= 3.0.3 =
* WP Job Manager integration
* Added category / taxonomy archive access restrictions
* Tags can now be added/removed from the edit user screen
* Added tooltips with additional information to batch processing tools
* Batch processes now update in real time after reloading WPF settings page

= 3.0.2 =
* Bugfixes for version 3.0

= 3.0.1 =
* Bugfixes for version 3.0

= 3.0 =
* Added Formidable Forms integration
* Added bulk editing tools for content protection
* New admin column for showing restricted content
* New background worker for batch operations on sites with a large number of users
* Tags are now removed properly when WooCommerce order refunded / cancelled
* Added option to remove tags when LifterLMS membership cancelled
* Added "Tag Link" capability for Paid Memberships Pro membership levels
* User roles can now be updated via the Update method in a webhook or HTTP Post
* Introduced beta support for Drip webhooks
* Initial sync process for Drip faster and more comprehensive
* All integration functions are now available via wp_fusion()->integrations
* Updated and improved automatic updates
* Numerous speed optimizations and bugfixes

= 2.9.6 =
* Improved integration with Paid Memberships Pro and Contact Form 7
* Bugfix for Radio type fields with Ultimate Member

= 2.9.5 =
* Added "Staging Mode" - all WP Fusion functions available, but no API calls will be sent
* Added Advanced settings pane with debugging tools

= 2.9.4 =
* LifterLMS bugfixes
* Deeper MemberPress integration

= 2.9.3 =
* Support for Asian character encodings with Infusionsoft
* Improvements to Auto-login links for hosts that don't support SESSION variables

= 2.9.2 =
* Misc. bugfixes

= 2.9.1 =
* Added support for MemberPress
* Updates for WooCommerce Subscriptions 2.x

= 2.9 =
* AgileCRM CRM support
* Added support for Thrive Themes Apprentice LMS
* Added support for auto-login links
* Added ability to apply tags when a link is clicked

= 2.8.3 =
* Allows shortcodes in restricted content message

= 2.8.2 =
* Fix for users being logged out when syncing password fields
* Ontraport bugifxes and performance tweaks
* Better error handling and debugging information for webhooks

= 2.8.1 =
* Added option for customizing restricted product add to cart message
* Misc. bug fixes

= 2.8 =
* ConvertKit CRM support
* LifterLMS updates to support LLMS 3.0+
* Ability to apply tags for LifterLMS membership levels
* Restricted Woo products can no longer be added to cart via URL

= 2.7.5 =
* Fixed Infusionsoft character encoding for foreign characters
* Fixed default field mapping overriding custom field selections

= 2.7.4 =
* Fixed bug where tag select boxes on LearnDash courses were limited to one selection

= 2.7.3 =
* Fixed bugs where ActiveCampaign lists would be overwritten on contact updates
* Restricted menu items no longer hidden in admin menu editor
* Improved s2Member support
* Fix for applying tags with variable WooCommerce subscriptions

= 2.7.2 =
* Added s2Member integration
* Added support for applying tags when WooCommerce coupons are used
* Added support for syncing AffiliateWP affiliate information
* Fixed returning passwords for imported contacts
* Updates for compatibility with plugin integrations

= 2.7.1 =
* Added LifterLMS support
* Fix for password updates not syncing from UM Account page

= 2.7 =
* Added Restrict Content Pro Integration
* Tag mapping for LearnDash Groups
* Can now sync user password from Ultimate Member reset password page

= 2.6.8 =
* Fix for contact fields not getting correct defaults on first install
* Fixed wrong lists getting assigned when updating AC contacts
* Significant API performance optimizations

= 2.6.7 =
* Enabled webhooks from Ontraport

= 2.6.6 =
* Fixed error in GForms integration

= 2.6.5 =
* Added support for syncing PMPro membership level name
* Fixed tags not applying when WooCommerce orders refunded
* Bugfixes and performance optimizations

= 2.6.4 =
* Batch processing tweaks

= 2.6.3 =
* Admin performance optimizations
* Batch processing / export tool

= 2.6.2 =
* Fix for tag select not appearing under Woo variations
* Formatting filters for date fields in ActiveCampaign
* Added quiz support to Gravity Forms
* Optimizations and performance tweaks

= 2.6.1 =
* Drip bugfixes
* Fix for restricted WooCommerce products not being hidden on some themes

= 2.6 =
* Added Drip CRM support
* Option to run Woo checkout actions asynchronously

= 2.5.5 =
* Updates to support Media Tools Addon

= 2.5.4 =
* Added option to push generated passwords back to CRM
* Added ability to apply tags in LearnDash when a quiz is marked complete
* Added ability to link a tag with an Ultimate Member role for automatic role assignment

= 2.5.3 =
* Fixed bug with WooCommerce variations and user-entered tags
* Fixed BuddyPress error when XProfile was disabled

= 2.5.2 =
* Fix for license activations / updates on hosts with outdated CURL
* Updates to support WPF addons
* Re-introduced import tool for ActiveCampaign users
* PHP 7 optimizations

= 2.5.1 =
* Improvements to initial ActiveCampaign sync
* Added instructions for AC import

= 2.5 =
* Added Paid Memberships Pro support
* Added course / tag relationship mapping for LearnDash courses
* Added automatic detection and mapping for BuddyPress profile fields
* Added "Apply tags when refunded" option for WooCommerce products
* Updated HTTP status codes on HTTP Post responses
* Tweaks to Import function for Ontraport users
* Fix for duplicate contacts being created on email address change with ActiveCampaign
* Fix for resyncing contacts with + symbol in email address

= 2.4.1 =
* Bugfixes for Ontraport integration
* Added Contact Type field mapping for Infusionsoft

= 2.4 =
* Added Ontraport CRM integration

= 2.3.2 =
* MemberMouse beta integration
* Fix for license activation for users on outdated versions of CURL / SSL
* Fix for BuddyPress pages not locking properly

= 2.3.1 =
* Fixed error in bbPress integration on old PHP versions

= 2.3 =
* Added Contact Form 7 support
* All bbPress topics now inherit permissions from their forum
* Added ability to lock bbPress forums archive
* Fixed bug with importing users by tag
* Fixed error with shortcodes using Thrive Content Builder
* Removed Add to Cart links for restricted products on the Woo store page
* Added option to hide restricted products from Woo store page entirely
* Added support for applying tags based on EDD variations

= 2.2.2 =
* Fix for tag shortcodes on AC
* Improvements to tag selection on Woo subscriptions / variations
* Woo Subscription fields now show on variable subscriptions as well
* Updated included Select2 libraries
* Restricted content with no tags specified will now be restricted for non-logged-in-users

= 2.2.1 =
* Fixed fatal error with GForms integration on lower PHP versions

= 2.2 =
* Added support for re-syncing contacts in batches for sites with large numbers of users
* Added support for ActiveCampaign webhooks
* Added support for EDD Recurring Payments
* Simplified URL structure for HTTP POST actions and added debugging output
* Fix for "0" tag appearing with ActiveCampaign tags

= 2.1.2 =
* Fixed bug where AC profiles wouldn't update if email address wasn't present in the form
* Fix for redirect rules not being respected for admins
* Fix for user_email and display_name not updating via HTTP Post

= 2.1.1 =
* Fixed bug affecting [wpf] shortcodes with users who had no tags applied

= 2.1 =
* Added support for applying tags in Woo when a subscription expires, is cancelled, or is put on hold
* Added "Push All" option for incompatible plugins and "user_meta" updates triggered via functions
* Fix for ActiveCampaign accounts with no tags
* Isolated AC API to prevent conflicts with plugins using outdated versions of the same API

= 2.0.10 =
* Bugfix when using tag label in shortcode

= 2.0.9 =
* Fix for tag checking logic with shortcode

= 2.0.8 =
* Fix for has_tag() function when using tag label
* Fixes for conflicts with other plugins using older versions of Infusionsoft API
* Support for re-adding contacts if they've been deleted in the CRM

= 2.0.7 =
* Resync contact now deletes local data if contact was deleted in the CRM
* Update license handler to latest version
* Resynchronize now force resets all tags
* Moved upgrade hook to later in the admin load process

= 2.0.6 =
* Support for manually marking WooCommerce payments as completed
* Improved support for servers with limited API tools
* Fixed wp_fusion()->user->get_tag_id() function to work with ActiveCampaign
* Bugfixes to shortcode content restriction system
* Fix for fields with subfields occasionally not showing up in GForms mapping
* Fix for new Ultimate Member field formats

= 2.0.5 =
* Fix for user accounts not created properly when WooCommerce and WooSubscriptions were both installed
* Added "apply to related lessons" feature to Sensei integration
* WooCommerce will now track leadsources and save them to a customer's contact record

= 2.0.4 =
* Bugfix for PHP notices appearing when shortcodes were in use and current user had no CRM tags
* Added SQL escaping for imported tag labels and categories
* Fix for contact address not updating existing contacts on guest checkout
* Fix for ACF not pulling / pushing field data properly

= 2.0.3 =
* Bugfix for importing users where CRM fields were mapped to multiple local fields
* Bugfix for Setup tab not appearing on initial install

= 2.0.2 =
* Bugfix for notices appearing for admins when admin bar was in use

= 2.0.1 =
* Bugfix for "update" action in HTTP Posts

= 2.0 =
* Complete rewrite and refactoring of core code
* Integration with ActiveCampaign, supporting all of the same features as Infusionsoft
* Custom fields are now available as a dynamic dropdown
* Ability to re-sync tags and custom fields within the plugin
* Integration with Sensei LMS
* Infusionsoft integration upgraded to use XMLRPC 4.0
* 100's of bug fixes, performance enhancements, and other improvements

= 1.6.4 =
* Improved compatibility with other plugins that use the iSDK class
* Changes to options framework to support 3rd party addons
* Added backwards compatibility for PHP versions less than 5.3

= 1.6.3 =
* Fix for registering contacts that already exist in Infusionsoft

= 1.6.2 =
* Fix for saving WooCommerce variation configuration
* Added automatic detection for when contacts are merged
* Improvements to wpf_template_redirect filter
* Added ability to apply tags per Ultimate Member registration form
* Ability to defer adding the contact until after the UM account has been activated
* Fixed bug with tags not appearing on admin user profile page
* Added filters for unsetting post types
* Added wpf_tags_applied and wpf_tags_removed actions

= 1.6.1 =
* Added has_tag function
* Added wpf_template_redirect filter
* Improved detection of registration form fields
* Fixed PHP notices appearing when using ACF
* Updates for compatibility with WP 4.3.1

= 1.6 =
* Can feed Gravity Forms data to Infusionsoft even if the user isn't logged in on your site
* Added support for Easy Digital Downloads
* Fixed bug with pulling date fields into Ultimate Member

= 1.5.2 =
* Fixed a bug with the "any" shortcode method
* More robust handling for user creation

= 1.5.1 =
* Fixed bug with account creation and Ultimate Member user roles

= 1.5 =
* LearnDash integration: can now apply tags on course/lesson/topic completion
* Content restrictions can now apply to child content
* New Ultimate Member fields are detected automatically
* Added ability to set user role via HTTP Post 'add'
* Added 'any' option to shortcodes

= 1.4.5 =
* Fixed global redirects not working properly
* Fixed issue with Preview As in admin bar
* Added 'wpf_create_user' filter
* Allowed for creating / updating users manually
* API improvements

= 1.4.4 =
* Misc. bugfixes with last release

= 1.4.3 =
* Improved compatibility of WooCommerce checkout with caching plugins
* Fixed bug with static page redirects
* Improved Ultimate Member integration
* Added support for combining "tag" and "not" in the WPF shortcode
* Added support for separating multiple shortcode tags with a comma
* Reduced API calls when profiles are updated
* Fixed bugs with guest checkout in WooCommerce

= 1.4.2 =
* Fixed bug with Ultimate Member integration in last release

= 1.4.1 =
* "Resync Contact" now pulls meta data as well
* Can now validate custom fields by name as well as label
* Added warning messages for WP Engine users
* Improved support for Ultimate Member membership plugin
* Fixed bug with redirects on Blog page / archive pages

= 1.4 =
* Added support for locking bbPress forums based on tags
* Added wpf_update_tags and wpf_update_meta shortcodes
* Support for overriding the new user welcome email with plugins
* Fixed bug with API Key generation
* Fixed bug with tags not applying after the specified delay
* Improved integration with WooCommerce checkout

= 1.3.5 =
* Added integration with Ultimate Member plugin

= 1.3.4 =
* Added "User Role" selection to import tool
* Added actions for user added and user updated
* Added "lock all" button to preview bar dropdown
* Fixed bug where tag preview wouldn't work on a static home page
* Fixed bug where shortcodes within the `[wpf]` shortcode wouldn't execute

= 1.3.3 =
* Improved integration support for user meta / profile plugins

= 1.3.2 =
* Tags will be removed when a payment is refunded
* Added support for applying tags with product variations
* Fixed bug with pushing ACF meta data on profile save
* Added support for pulling ACF meta data on profile load

= 1.3.1 =
* Added wpf_woocommerce_payment_complete action
* Added search filter to redirect page select dropdown
* Fixed "Class 'WPF_WooCommerce_Integration'" not found bug

= 1.3 =
* Added ability to import contacts from Infusionsoft as new WordPress users
* Added new plugin API methods for updating meta data and creating new users (see the documentation for more information)
* Added "unlock all" option to frontend admin toolbar
* Tags applied by a WooCommerce subscription can be removed when the subscription fails to charge, a trial period ends, or the subscription is put on hold
* Added support for syncing password and username fields
* Fixed a bug with applying tags at WooCommerce checkout when the user isn't logged in

= 1.2.1 =
* Added pull_user_meta() template tag
* Fixed bug with pushing user meta when no contact ID is found

= 1.2 =
* Added support for syncing multiselect fields with a contact record
* Added ability to trigger a campaign goal when a user profile is updated
* Added ability to manually resync a user profile if a contact record is deleted / recreated
* Now supports syncing with Infusionsoft built in fields. See the Infusionsoft "Table Documentation" for field name reference
* Users registered through a UserPro registration form will now have their password saved in Infusionsoft
* Fixed several bugs with user account creation using a UserPro registration form
* Fixed bug where tag categories with over 1,000 tags wouldn't import fully
* Fixed a bug that would cause checkout to fail with WooCommerce if a user is in guest checkout mode
* Numerous other bugfixes, optimizations, and improvements

= 1.1.5 =
* Fixed bug that would cause a user profile to fail to load when an IS contact wasn't found
* "Preview with tag" dropdown now groups tags by category and sorts alphabetically
* Fixed a bug with applying tags at WooCommerce checkout
* Notices for inactive / expired licenses

= 1.1.4 =
* Check for UserPro header on initial sync bug fixed
* Removed PHP notices on meta box when no tags are present
* "Preview with tag" has been removed from admin screens

= 1.1.3 =
* Automatic update bug fixed

= 1.1.2 =
* Fixed bug where users without email address would kill initial sync

= 1.1.1 =
* Changed name to WP Fusion

= 1.1 =
* EDD software licensing added

= 1.0.3 =
* Cleaned up apply_tags function

= 1.0.2 =
* Misc. bugfixes
* Added ability to apply tags to contact on WooCommerce purchase

= 1.0.1 =
* Misc. bugfixes
* Added content selection dropdown on post meta box

= 1.0 =
* Initial release