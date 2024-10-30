=== IBS GCAL Events ===
Contributors: hmoore71
Donate link: https://indianbendsolutions.net/donate/
Plugin URI: https://indianbendsolutions.net/documentation/ibs-Calendar/
Author URI: https://indianbendsolutions.net/
Tags: google calendar, calendar, 
Requires at least: 4.0
Tested up to: 4.2.2
Stable tag: 2.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Displays list of a public Google Calendar's events.

== Description ==
* IBS GCal Events displays a Google Calendar's events as a shortcode [ibs-gcal-events] with default or shortcode supplied options. 
* Widget to display Google Calendar events
* See more at https://indianbendsolutions.net/documentation/ 


Presently IBS  GCAL Events is in its Beta phase of development and all testing and reporting of issues is appreciated.

== Installation ==
1. Download ibs-gcal-events and unzip.
2. Upload `ibs-gcal-events` folder to the Wordpress plugin directory
3. Activate the plugin through the ‘Plugins’ menu in WordPress
4 Admin | Settings menu | IBS GCAL Events and configure the plugin.

== Frequently Asked Questions ==
How do I get the Google Calendar id? 
1. Open your Google Calendar and on the left side bar click "My calendars" which should list all of your calendars.
2. To the right of your calendar name is a dropdown indicator; click it and a dialog will display.
3. Click "Calendar settings" and that will open a page with all of your calendar settings on it.
4. Towards the bottom are a set of three buttons XML(orange) ICAL(green) HTML(blue) and a Calendar ID (typically a gmail address). 
5. Copy and paste the calendar id.

What is "Google API Key" ? Google requires every user of the Google Calendar feeds to have their own Google Calendar API Key. IBS GCAL Events has a key that is shared with all users of this and other plugins.
If the use of the key gets too high (500,000 requests per day) the plugin may be denied access. At that point you may want to obtain your own or another key.

Can I display event sources other than Google Calendar? No this plugin is Google Calendar specific.

How can I style the output? The IBS GCAL Events plugin is a very straight forward plugin and easily modified. 

== Screenshots ==
1. Settings tab.
2. Widget settings

== Changelog ==

(initial release)
2015-01-07 Sync base code.
2015-07-06 Version 1.2 change default Google API Key
2015-11-23 Version 2.0 adds to the control of data and presentation.
2015-11-23 Version 2.1 adds multi-calendar feeds support;
== Upgrade Notice ==
version 2.1 has significant changes. Review at https://indeianbendsolutions.com