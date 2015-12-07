=== Educator ===
Contributors: educatorteam
Donate link: http://educatorplugin.com
Tags: learning management system, lms, learning, online courses
Requires at least: 4.3
Tested up to: 4.3
Stable tag: 1.7.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Educator is a simple learning management system plugin for WordPress.

== Description ==

You can use this plugin to offer online courses on your WordPress website.

= Links: =

* Documentation: http://educatorplugin.com/
* Learn how this plugin works: http://educatorplugin.com/how-educator-works/
* Official github repository: https://github.com/educatorplugin/ibeducator

= Features: =

* Create courses and add lessons.
* Create quizzes.
* Supports PayPal, cash or check payment methods.
* Create lecturers that can edit their courses and lessons.
* Grade courses and quizzes.
* Email notifications.
* Memberships.
* The courses shortcode.
* Edit the slugs for the courses archive, courses, lessons archive, lessons and course category.
* Course prerequisite.
* Course/membership payment action and filter hooks.
* NEW: taxes feature. Because it is a new feature, please test it carefully before using.

= Important Updates =

* <a href="http://educatorplugin.com/term-taxonomy-splitting-in-wp-4-2-and-how-it-affects-educator/">Term Taxonomy Splitting in WP 4.2 and How It Affects Educator</a>
* <a href="http://educatorplugin.com/updating-to-educator-1-7/">Updating to Educator 1.7 or greater</a>

= Add-ons =

* <a href="http://wordpress.org/plugins/educator-woocommerce-integration/" target="_blank">Educator WooCommerce Integration</a>
* <a href="https://wordpress.org/plugins/educator-certificates/" target="_blank">Educator Certificates</a>

== Installation ==

1. Upload `ibeducator` plugin folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress

== Frequently Asked Questions ==

Coming soon.

== Screenshots ==

1. **Quiz**
2. **Course Entries**
3. **Members**
4. **Membership Levels Page**
5. **Payments**
6. **Student's Progress Page**
7. **Plugin Settings**
8. **Course Settings**

== Changelog ==

= 1.7.1 =
* If the current version of the plugin installed on your website is less than 1.7, please read the changelog for the version 1.7 carefully.
* Fixed: the email to the student is being sent upon registration. It stopped from being sent due to the slight change in WP's api for the "new user email notifications" function.

= 1.7 =
* The version 1.7 introduces major improvements and changes to the plugin's code. Please read the http://educatorplugin.com/updating-to-educator-1-7/ carefully before you update this plugin.
* Changed the order of the arguments of the "Edr_Quizzes::get_attempts_number" method. Now, this method should be used like this: get_attempts_number($lesson_id, $entry_id).
* Refactored the quizzes management JS in order to make it easier to continue the development of this feature. Added the gulp task to build the quizzes management JS into one file.
* Working on the ability to add quizzes to other post types (not only lessons).
* Improved the quizzes code, added new filter hooks to make the quizzes feature easier to extend.
* Added a description and a version number for the templates from the "templates" folder.
* Refactored a lot of code.

= 1.6 =
* Added: set the maximum number of quiz attempts a student can take per quiz.
* Deprecated a list of methods in IB_Educator class: get_questions, get_choices, get_question_choices, add_choice, update_choice, delete_choice, delete_choices, add_student_answer, get_student_answers, add_quiz_grade, update_quiz_grade, is_quiz_submitted, get_quiz_grade, and check_quiz_pending. These methods were moved to the new Edr_Quizzes class (this class can be retrieved by calling Edr_Manager::get( 'edr_quizzes' )).
* Added: the "content" field to the quiz questions. A lecturer can use a number of HTML tags in this field (ul, ol, li, pre, code, a, strong, em, img). The full list of allowed tags can be found in includes/formatting.php, "edr_kses_allowed_tags" function.
* Added: edr_entry_status_change action hook.
* Added: edr_student_courses_headings and edr_student_courses_values filters to the student's courses shortcode.
* Removed the "protect_private_pages" method from the IB_Educator_Main class.

= 1.5.0 =
* Added the Syllabus manager. Now, it is possible to group lessons on the course page.
* Fixed the shortcode that displays the student's courses. This shortcode has used the default posts_per_page setting. Now it displays all entries.
* (IMPORTANT) Improved the template functions and hooks functionality (old template hook processing functions were renamed and moved to the new file).
* Improved the "Educator > Entries" admin screen. Now, it's possible to search entries by a student and a course.
* Improved the autocomplete field.

= 1.4.5 =
* Fixed an issue that was appearing when the course categories where not set for a membership level.
* Added the "Show course lecturer on the payment page" checkbox to the "General" tab in Educator > Settings.
* Code cleanup, improvements, and fixes.

= 1.4.4 =
* IMPORTANT UPDATE: added a fix in response to the taxonomy term splitting since WordPress 4.2. Since WP 4.2, when you update a term (e.g., a "Course Category") and if it is shared between multiple taxonomies, a new term is created which replaces the current term and has a different term_id. Educator stores term_id's for memberships (Educator > Membership Levels > Membership Settings > Categories) to give members ability to join courses from specified categories. If the term gets split, it's new term_id won't correspond to the old one that is stored in a membership. Educator 1.4.4 addresses this issue.
* Please read this article carefully: <a href="http://educatorplugin.com/term-taxonomy-splitting-in-wp-4-2-and-how-it-affects-educator/">Term Taxonomy Splitting in WP 4.2 and How It Affects Educator</a>. You might need to update "categories" attribute in the Educator's [courses] shortcode (when you edit categories in Courses > Course Categories), if you use this shortcode somewhere.

= 1.4.3 =
* Added ability to enable comments on lessons.
  - The visibility of the lesson comments on the single lesson page depens on the lesson's "Access" option.
  - Lesson comments are removed from the general comments feed (e.g., example.com/?feed=comments-rss2).
* Other improvements and fixes.

= 1.4.2 =
* Fixed an issue when the plugin didn't load translation (.MO) file from the "languages" directory. It loaded the MO file from "wp-content/languages/plugins" directory.

= 1.4.1 =
* Improved UI for the following admin screens: edit payment, edit entry, edit member.
* Added "Access" option to lessons. This option allows to make a lesson's content viewable without course registration.
* Added ability to open/close registration for a given course (the "Registration" option in the course edit screen).
* Other improvements and fixes.

= 1.4.0 =
* Refactored the payment system. Now, developers can create custom payment gateways.
* Added the taxes feature. Because it is a new feature, please test it carefully before using.
  - Added the "Taxes" tab to the settings page.
  - Enable/disable taxes.
  - Enter prices inclusive of taxes or exclusive.
  - Manage tax rates.
* Added new settings to the Educator > Settings, General.
  - Location.
  - Store customers' IPs on purchases.
* Added billing info on the payment page if taxes are enabled.
  - First name, last name, address, city, state, postcode, country.
* Modified the following pages:
  - Payment page (Modified the HTML output).
  - Payment thank you page (Modified the HTML output and improved the payment summary display).
* Improved the autocomplete fields (for example, "Student" and "Course" select fields on the "Edit Payment" admin page).
* The flush_rewrite_rules function is not called on every plugin update anymore. It's called on plugin activation/deactivation only.
* Improvements to the get_access_status method from IB_Educator class. If entry is pending and payment is pending, the status should be "pending_entry".
* Modified "ib_educator_register_form" action hook. Now, it receives WP_Error object instead of an array of error codes as the first argument.
* Code refactoring.

= 1.3.5 =
* Added the beta version of the Stripe payment gateway. It's a beta version, so please test it before using it on production.
* Lecturers can see which entries have the quizzes that are ready to be graded. The text "quiz pending" is added to the entry row on the Entries admin page.
* Added the "ib_educator_access_status" filter.

= 1.3.4 =
* Improved the lesson content restriction even further.
* Added a filter to the "rss_use_excerpt" option (the setting "For each article in a feed, show" in Settings &raquo; Reading) such that it returns 1 for lessons.
* The lesson content should be empty in the feeds by default (no excerpt should show up in there).

= 1.3.3 =
* IMPORTANT security fix. This update fixes the issue where the lesson content was visible to unregistered visitors in the lessons rss/atom feed, the lessons archive and the search page. On the lessons archive and the search page it was visible only when the lesson didn't have the "more" tag (&lt;!--more--&gt;).
* Please make sure to update the lessons' excerpts and add the more tag in appropriate places (please read the <a href="http://educatorplugin.com/create-a-lesson/">Create a Lesson</a> article).
* Please apply this update as soon as possible.

= 1.3.2 =
* Improved the user registration system/API (the payment page), added the user registration actions and filters. Some user registration form error messages have been changed (the payment page).
* Added the [courses] shortcode.
* Refactored the admin settings code.
* Adding 'current-menu-item' class to a menu item that has URL of the courses archive.
* Added the options to alter the courses and lessons slugs in Settings > Permalinks.
* Don't pause the student's course entries when the current membership is extended with the same membership level.
* Added the 'ib-edu-lesson-locked' to the single lesson's HTML container if the student did not register for the appropriate course.
* Added the functions to get the adjacent lessons and their links: ib_edu_get_adjacent_lesson, ib_edu_get_adjacent_lesson_link.
* Added the previous/next links to the single lesson template.
* Added a couple of new action/filter hooks.

= 1.3.1 =
* Fixed the "Table 'wp_ibeducator_members' doesn't exist" error.
* Moved the plugin update check to an earlier hook (from 'admin_init' to 'init' with priority of 9).
* Please check the changelog for version 1.3.0 too.

= 1.3.0 =
* Added the memberships feature.
* Now, an admin can create membership levels. A membership level gives the students access to courses from the categories specified in this membership level. A membership level can be purchased once, daily, monthly or yearly.
* Admin UI improvements: payments and entries list pages.
* The PayPal txn_id (the merchant's original transaction identification number) is now saved to the "payments" table. Please find it on the payment's edit screen.
* Improved security and data sanitation.
* Removed some old code and deprecated functions (since beta version 0.9.1).
* Added a number of shortcodes: [memberships_page], [user_membership_page], [user_payments_page].
* New filters.
* Now, lessons are searchable by default.

= 1.2.0 =
* Improved student's courses page (added payment instructions, payment number and entry id).
* Added "Bank Transfer" payment gateway.
* New API function: ib_edu_has_quiz( $lesson_id ).
* Added {login_link} placeholder to notification email templates.
* Added simple rich text editor to payment instructions message field on the settings page.
* Bug fixes and improvements.

= 1.1.2 =
* Added ib_edu_has_quiz( $lesson_id ) function and lesson meta (displays 'Quiz' if lesson has a quiz).

= 1.1.1 =
* Added Courses Archive URL information to Educator &raquo; Settings screen to make it easier for users to find and use it.
* Fixed issue: when all payment gateways were being disabled, they were visible on student's payment screen.

= 1.1.0 =
* Added email notifications feature. Student receives email notification when:
  - he/she can start studying the course (PayPal sends payment confirmation or admin changes the student's payment status to "Complete" and checks "Create an entry for this student" on the "Edit Payment" screen).
  - lecturer adds grade to his/her quiz.
* Edit email notifications in Educator &raquo; Settings &raquo; Emails
* Administrator can add payments and entries manually.
* Autocomplete for the 'Course' and 'Student' fields in "Edit Payment" and "Edit Entry" forms.
* Namespaced settings_errors() on settings pages to prevent non-relevant errors from showing up.
* Added capabilities of authors to lecturers (edit_posts, delete_posts) so they can create posts, but not publish them. They will be able to edit their own posts only.
* Fixed bug when Educator created many entries for one payment due to multiple IPN responses from PayPal with status "Complete".
* Added date column to the Entries page.
* Bug fixes and improvements.

= 1.0.0 =
* Did more code refactoring and fixed a couple of bugs.
* Custom post types are now called more descriptively: ib_educator_course, ib_educator_lesson.
* Archive templates are called: archive-ib_educator_lesson.php, archive-ib_educator_course.php, single-ib_educator_lesson.php, single-ib_educator_course.php
* Accepted a convention for CSS classes and IDs: ib-edu-[html_class_name] or ib-edu-[html_id] (IB - Educator (ib-edu)).
* Added currency settings (symbol, position) and updated settings page (select pages in "General" settings tab now).
* If your currency is not on the list, there is a way to add it.
* Fixed a PayPal IPN bug.
* Finally, a stable release.

= 0.9.10 =
* Plugin checks for the version number in the database, and upgrades it's structure (DB, custom post types, taxonomies, etc) when user updates it in WordPress admin.
* Please make sure that you check verion 0.9.9 changelog, as it's very important.

= 0.9.9 =
* Fixed a couple of bugs.
* Added ability to assign courses to categories.
* Added difficulty levels: beginner, intermediate, advanced.
* Many changes to API functions, actions and filters. Please refer to the documentation (http://educatorplugin.com/).
* API (functions, actions and filters) is almost stable.
* Code refactoring.

= 0.9.0 =
* Beta version release.

== Upgrade Notice ==

Coming soon