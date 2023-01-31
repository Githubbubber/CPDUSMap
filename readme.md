# US Map of CPD Organization's Affiliates and General Voting Info
Tags:           US, Politics, Voting, Nonprofit
Requires PHP:   7.4
Contributors:   Ronnette Cox, Mekesia Brown
Tested up to:   6.1

## Description

This plugin adds a widget of a US map to the blocks available for each page. This interactive map shows the location of all CPD affiliates and voting registration locations.

## Installation

1. Upload the plugin files to the `/wp-content/plugins/cpdusmap` directory, or install the plugin through the WordPress plugins screen directly.
2. Add required constants to wp-config.php: TEST_REMOTE_ADDR, IPINFO_TOKEN, GEOLITE2_ACCOUNT_ID, GEOLITE2_LICENSE_KEY, CIVICINFO_API_KEY
3. Activate the plugin through the 'Plugins' screen in WordPress
4. Use the 'Add Block' button to add the CPD US Map block to a page or post.

## Frequently Asked Questions

### Will this plugin work with my theme?

Yes. This plugin is designed to work with any theme.

### Do I have to use the geolocation APIs included?

No. The two I used (GeoLite2 and IPInfo) are free and work fine for development, but your app will probably be a production app. Choose APIs that work for you.

### Any details on the local environment?

This plugin was built on WAMPServer32 and tested on Google Chrome v109.0.5414.120.

## Screenshots

N/A
