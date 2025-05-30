<?php

return [
	/*
	|--------------------------------------------------------------------------
	| Settings page
	|--------------------------------------------------------------------------
	*/
	'title' => 'Settings',
	'small_screen' => 'For better a experience on the Settings page,<br />we recommend you use a larger screen.',
	'tabs' => [
		'basic' => 'Basic',
		'all_settings' => 'All settings',
	],
	'toasts' => [
		'change_saved' => 'Change saved!',
		'details' => 'Settings have been modified as per request',
		'error' => 'Error!',
		'error_load_css' => 'Could not load dist/user.css',
		'error_load_js' => 'Could not load dist/custom.js',
		'error_save_css' => 'Could not save CSS',
		'error_save_js' => 'Could not save JS',
		'thank_you' => 'Thank you for your support.',
		'reload' => 'Reload your page for full functionalities.',
	],
	'system' => [
		'header' => 'System',
		'use_dark_mode' => 'Use dark mode for Lychee',
		'language' => 'Language used by Lychee',
		'nsfw_album_visibility' => 'Make Sensitive albums visible by default.',
		'nsfw_album_explanation' => 'If the album is public, it is still accessible, just hidden from the view and <b>can be revealed by pressing <kbd>H</kbd></b>.',
		'cache_enabled' => 'Enable caching of responses.',
		'cache_enabled_details' => 'This will significantly speed up the response time of Lychee.<br> <i class="pi pi-exclamation-triangle text-warning-600 mr-2"></i>If you are using password protected albums, you should not enable this.',
	],
	'lychee_se' => [
		'header' => 'Lychee <span class="text-primary-emphasis">SE</span>',
		'call4action' => 'Get exclusive features and support the development of Lychee. Unlock the <a href="https://lycheeorg.dev/get-supporter-edition/" class="text-primary-500 underline">SE edition</a>.',
		'preview' => 'Enable preview of Lychee SE features',
		'hide_call4action' => 'Hide this Lychee SE registration form. I am happy with Lychee as-is. :)',
		'hide_warning' => 'If enabled, the only way to register your license key will be via the More tab above. Changes are applied on page reload.',
	],
	'dropbox' => [
		'header' => 'Dropbox',
		'instruction' => 'In order to import photos from your Dropbox, you need a valid drop-ins app key from their website.',
		'api_key' => 'Dropbox API Key',
		'set_key' => 'Set Dropbox Key',
	],
	'gallery' => [
		'header' => 'Gallery',
		'photo_order_column' => 'Default column used for sorting photos',
		'photo_order_direction' => 'Default order used for sorting photos',
		'album_order_column' => 'Default column used for sorting albums',
		'album_order_direction' => 'Default order used for sorting albums',
		'aspect_ratio' => 'Default aspect ratio for album thumbs',
		'photo_layout' => 'Layout for pictures',
		'album_decoration' => 'Show decorations on album cover (sub-album and/or photo count)',
		'album_decoration_direction' => 'Align album decorations horizontally or vertically',
		'photo_overlay' => 'Default image overlay information',
		'license_default' => 'Default license used for albums',
		'license_help' => 'Need help choosing?',
	],
	'geolocation' => [
		'header' => 'Geo-location',
		'map_display' => 'Display the map given GPS coordinates',
		'map_display_public' => 'Allow anonymous users to access the map',
		'map_provider' => 'Defines the map provider',
		'map_include_subalbums' => 'Includes pictures of the sub albums on the map',
		'location_decoding' => 'Use GPS location decoding',
		'location_show' => 'Show location extracted from GPS coordinates',
		'location_show_public' => 'Anonymous users can access the extracted location from GPS coordinates',
	],
	'cssjs' => [
		'header' => 'Custom CSS & Js',
		'change_css' => 'Change CSS',
		'change_js' => 'Change JS',
	],
	'all' => [
		'old_setting_style' => 'Old settings style',
		'expert_settings' => 'Expert Mode',
		'change_detected' => 'Some settings changed.',
		'save' => 'Save',
		'back_to_settings' => 'Back to grouped settings',
	],

	'tool_option' => [
		'disabled' => 'disabled',
		'enabled' => 'enabled',
		'discover' => 'discover',
	],

	'groups' => [
		'general' => 'General',
		'system' => 'System',
		'modules' => 'Modules',
		'advanced' => 'Advanced',
	],
];