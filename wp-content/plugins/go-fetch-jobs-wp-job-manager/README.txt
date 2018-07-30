=== Go Fetch Jobs (for WP Job Manager) ===
Author: SebeT
Contributors: SebeT, freemius
Tags: import, rss, feed, jobs, WP Job Manager, automated imports, scheduled imports, Jobify, Babysitter, Jobseek, WorkScout, Prime Jobs, JobHaus, JobFinder, import jobs, job directory
Requires at least: 3.5
Requires PHP: 5.2.4
Tested up to: 4.9
Stable tag: 1.4.6

Instantly populate your WP Job Manager database using RSS job feeds from the most popular job sites.

== Description ==

> [Official site](http://gofetchjobs.com) / [DEMO site](http://gofetchjobs.com/wp-job-manager/wp-admin/index.php?demo=1)

Instantly populate your [WP Job Manager](https://wpjobmanager.com/) site with jobs from the most popular job sites and/or job aggregators. This handy plugin fetches jobs from RSS feeds and imports them to your jobs database. Pick your favorite job site/job directory, look for the jobs RSS feed, paste it directly to *Go Fetch Jobs* and instantly get fresh jobs in your database!

To help you quickly getting fresh jobs from external sites, *Go Fetch Jobs* comes bundled with ready to use RSS feeds and detailed instructions on how to setup RSS feeds for job sites that provide them, including [jobs.theguardian.com](jobs.theguardian.com).

Easily categorize bulk job imports with job categories, job types, default custom fields values and expiry dates.

Besides the usual *Title* + *Description* + *Date* usually provided by RSS feeds, *Go Fetch Jobs* can also (optionally) extract and auto fill job companies logos, company names and locations, if that information is provided by the RSS feed.

It also comes with the ability to save import rules as templates so you can later recycle/re-use them for new imports.

Upgrade to a *Pro+* plan to keep it automatically updated with fresh jobs added every day, through automatic schedules! (*)

> #### Features include:
>
> * Filter to show imported/user submitted jobs in job listings backend
> * Provider column on job listings backend
> * Import jobs from any valid RSS feed
> * Seamless integration with WPJM jobs
> * Assign job expiry date
> * Save import rules as templates
> * Company logos on select providers
> * Company names and job locations on select providers
> * Ready to use RSS Feeds, including big sites like [jobs.theguardian.com](jobs.theguardian.com), with detailed setup instructions
>
> * And more...

> #### Additional features, exclusive to premium plans include:
>
> * **NEW** Pull jobs from Indeed, Careerjet, ZipRecruiter and AdView API's
> * **NEW** Positive and negative keyword filtering
> * **NEW** Job Types/Categories mappings for Smart Assign
> * Added ability to extract incomplete/missing meta data directly from provider site on select providers (can extract full job descriptions, companies, locations and logos - e.g: Indeed)
> * 'Test' or 'Run' schedules directly from the schedules page
> * Add your Google API key to increase geolocation rate limits
> * Set your own time interval between schedule runs
> * Ready to use RSS feeds from popular job sites including: [monster.com](monster.com), [indeed.com](indeed.com), [careerjet.com](careerjet.com) and [craigslist](cragislist.org)
> * Custom RSS builder for select providers that allows creating custom RSS feeds with specific keywords/location, without leaving your site
> * Extract and auto-fill job company names and locations on select providers
> * Auto assign job types and job categories based on each job content
> * Filter imports using keywords
> * Automated scheduled imports
>
> * And more...

Visit the [official website](http://gofetchjobs.com) for more details on features and available plans.

(*) You can upgrade to any plan right directly from the plugin.

== Installation ==

1. Extract the zip file and just drop the contents in the wp-content/plugins/ directory of your WordPress installation and then activate the Plugin from the Plugins page.
2. A new Menu named 'Go Fetch Jobs' will be available on the left sidebar.

== Frequently Asked Questions ==

= Why are jobs not showing full job descriptions ? =
Some sites don't provide full job descriptions on their RSS feeds. This is usually intended to have users still visit the original site to read the full description.

= Why don't all jobs show a logo after importing ? =
Logos are not available in all RSS feeds and unfortunately, RSS feeds that provide them are still the exception.

= Why do I sometimes see logo thumbnails from imported jobs in the backend but not in frontend ? =
Since some of the logos pulled from RSS feeds are from images stored on CDN's they cannot be stored as native WordPress featured images in the media library.
This means that if your theme does not use the default WPJM `the_company_logo()` function to display jobs on the frontend these logos will not be displayed. You'll need some custom
changes to be able to do that. I can help you on this if you need it.

= How do I import jobs so that they are all automatically assigned to the matching category ? =
Only the *Premium* plans provide this feature through the *Smart Assign* option. In the *Free* version you need to choose one category that will be assigned to all jobs on each bulk import.

= How do I activate my premium plan after purchase ? =
After your purchase, deactivate the Free version and download and activate the premium version. Under *Go Fetch Jobs > Account*, click 'Sync' or the *Activate Pro/Pro+ Plan* button.
If the premium plan is not activated immediately please try again later since it can take a few minutes until the server is updated.


== Screenshots ==

1. Existing RSS Providers List
2. Load Saved Import Templates
3. RSS Feed Setup Detailed Instructions
4. Custom RSS Feed Builder
5. Fetch Job Companies Logos
6. Set Job Providers Information / Optional URL Parameters
7. Set Job Details for Imported Jobs
8. Filters / Save Templates
9. Job Listings for Imported Jobs (Frontend)
10. Single Job Page for Imported Jobs (Frontend)
11. Settings Page
12. Jobs Filter & Provider Column

== Changelog ==

See changelog

== Upgrade Notice ==
This is the first stable version.
