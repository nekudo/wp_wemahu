=== Wemahu ===
Contributors: nekudo
Donate link: http://nekudo.com/
Tags: security, malware, hacked
Requires at least: 3.7
Tested up to: 3.8.1
Stable tag: 1.0.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Wemahu is a crowd powered malware scanner for wordpress.

== Description ==

Wemahu is a crowd powered malware scanner for wordpress. The component can help to find malicious code within a "hacked" wordpress installation. All results can be submitted to nekudo.com to constantly improve malware detection.
Additionally it can monitor files for changes to identify malware attacks as soon as possible.

Features:

* Scan files for malware using a regular expression database.
* Monitor files for changes using checksums.
* Run regular scans on your filesystem using cronjobs and receive reports by email.
* Automatically updated signature and whitelist database.
* Define different rulesets for your scans.
* Timeout prevention: Even lots of files can be scanned without running into script timeouts.


== Installation ==

= Using The WordPress Dashboard =

1. Navigate to the 'Add New' in the plugins dashboard
2. Search for 'Wemahu'
3. Click 'Install Now'
4. Activate the plugin on the Plugin dashboard

= Uploading in WordPress Dashboard =

1. Navigate to the 'Add New' in the plugins dashboard
2. Navigate to the 'Upload' area
3. Select `wp_wemahu.zip` from your computer
4. Click 'Install Now'
5. Activate the plugin in the Plugin dashboard

= Using FTP =

1. Download `wp_wemahu.zip`
2. Extract the `plugin-name` directory to your computer
3. Upload the `plugin-name` directory to the `/wp-content/plugins/` directory
4. Activate the plugin in the Plugin dashboard

== Screenshots ==

1. Wemahu scanning a directory for malicious code.
2. Results of a malware scan.
3. Ruleset dialog. Multiple rulsets can be defined to scan specific folder e.g.

== Changelog ==

= 1.0.1 =
* Added blacklist to exclude folders from file-modification check.
* Minor layout improvements.

= 1.0.0 =
* First version.