=== Update Sentinel ===
Contributors: yodsira
Tags: updates, health check, rollback, monitoring, ttfb
Requires at least: 6.0
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 0.1.0
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Every update gets an instant health check: site answers, TTFB measured, verdict journaled. Know immediately when an update breaks your site.

== Description ==

"Update and pray" is not a strategy. Update Sentinel watches the moment an update finishes:

* versions before vs after — exactly what changed
* instant smoke check: does the site answer, how fast (TTFB)
* verdict in the admin + journal (healthy / slow / broken)
* email when the site is DOWN after an update, with roll-back hints
* 60-day journal: "when did it start" for any post-mortem

Works alongside automatic updates too.

== Pro Version ==

Pro adds automation, reports and integrations on top of the free version
(one license = one site, 12 months of updates):

https://yodsira.com/buy/update-sentinel

== Installation ==

1. Install and activate.
2. Update plugins as usual — verdicts appear in Update Sentinel.

== Frequently Asked Questions ==

= Does it slow updates? =
One extra request to your own site after the update finishes. Nothing on visitors' page loads.

= Does it auto-rollback? =
Not yet — it tells you exactly what changed and how to roll back in one click's time.

== Changelog ==

= 0.1.0 =
* First release: before/after version diff, post-update smoke check, verdict journal, broken-update email.
