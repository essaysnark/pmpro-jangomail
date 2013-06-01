=== PMPro -> JangoMail ===
Contributors: strangerstudios, essaysnark
Tags: memberships, ecommerce, email, jangomail
Requires at least: 3.0
Tested up to: 3.5.1
Stable tag: 1.6.1

Pushes members assigned to a PMPro level into specified JangoMail lists


== Description ==
Plugin functionality is limited. 

== Installation ==

1. Create a `pmpro-jangomail` directory in the `/wp-content/plugins/` directory of your site.
2. Copy pmpro-jangomail.php into this directory.
3. Activate the plugin through the 'Plugins' menu in WordPress.

In order to push your users' first/last names to JangoMail, you must create fields on each JangoMail list called firstname and lastname. If those custom fields don't exist then that data will be discarded and only the email address will get added in JangoMail.

You should also turn on the JangoMail feature to prevent duplicates in the List Settings screen. Do this for each list. Otherwise there's a chance that changes to the user's PMPro membership level will result in duplicate entries in JangoMail.

This plugin does not currently remove users from lists (it's different from the MailChimp PMPro plugin in that regard).






== Frequently Asked Questions ==

None yet.

= 0.1 =
* This is the launch version. No changes yet.
