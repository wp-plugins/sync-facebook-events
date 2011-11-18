=== Sync Facebook Events ===
Contributors: markpdxt
Donate link: http://pdxt.com/
Tags: facebook, events, synchronize, calendar
Requires at least: 3.0
Tested up to: 3.0
Stable tag: 1.0.1

Sync Facebook Events to The Events Calendar Plugin.

== Description ==

Sync Facebook Events to The Events Calendar Plugin.

== Installation ==

1. Download the plugin archive and expand it
2. Upload the sync-facebook-events folder to your /wp-content/plugins/ directory
3. Go to the plugins page and click 'Activate' for Sync FB Events
4. Navigate to the Settings section within Wordpress and enter your Facebook App ID, App Secret & UID.
5. Ensure The Events Calendar plugin is installed and configured - http://wordpress.org/extend/plugins/the-events-calendar/
5. Press 'Update' to synchronize your current Facebook events for display within The Events Calendar.

== Frequently Asked Questions ==

Q: What is the Facebook App ID and App Secret, and why are they required?
A: The Facebook App ID
 and App Secret are required by Facebook to access data via the Facebook graph API. 
To signup for a developer account or learn more see - http://developers.facebook.com/docs/guides/canvas/

Q: How do I find the Facebook UID of the page for which I wish to synchronize events?

A: Goto the page you're interested in - (ex. https://www.facebook.com/webtrends). 
Copy the URL and replace 'www' with 'graph' - (ex. https://graph.facebook.com/webtrends)
The UID is the first item in the resulting text. In this example it is "54905721286".

Q: Do my Facebook events get updated on a schedule?

A: Currently, your Facebook events are updated only when you press the 'Update' button from the Sync FB Events section within settings.

== Upgrade Notice ==

Upgrade Notice

== Screenshots ==

1. Facebook Event Sync Configuration

== Changelog ==

= 1.0 =
* Initial release