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

This plugin does not currently remove users from lists (it's different from the MailChimp PMPro plugin in that regard). Also, if you change the list name in JangoMail, you must re-select the new name in the plugin settings screen.






== Frequently Asked Questions ==

Q: What are the limitations of the current release?
A: Deleting a user  in WP doesn't delete it from your JangoMail lists. Will try to get that fixed in a future release. When you delete a user you need to manually remove them from JangoMail.

Q: I renamed my list in JangoMail and then it stopped receiving member names from PMPro.
A: The integration is one-way, WP -> JangoMail. When you make changes in JangoMail they're not automatically pushed to WordPress. If you change a list name in JangoMail, you'll need to re-assign the appropriate PMPro levels in the WordPress Settings screen.

Q: The firstname/lastname fields on my PMPro checkout screen are being saved to WordPress but they're not going into the JangoMail list.
A: Are the firstname/lastname going into JangoMail when you create a user from the WP admin? If so, then the reason they're not getting pushed during checkout is probably due to a permissions issue with your host and the way PMPro is using session_start(). We will update this answer if we find a solution.

Q: The firstname/lastname fields on my WP User Admin screen are being saved to WordPress but they're not going into the JangoMail list.
The two fields need to be created in JangoMail. They need to be named exactly that: firstname and lastname (all lower case). You need to add those two fields to all of your lists individually if you want the data to be pushed there.

Q: Other custom fields that I've added to my PMPro checkout screen are also not being pushed into JangoMail.
A: The only support we have in pushing fields to JangoMail right now is the firstname/lastname. 


= 0.5 =
* Bug fixes; implemented check if user is in the JangoMail list before adding them to prevent dupes

= 0.1 =
* This is the launch version. No changes yet.
