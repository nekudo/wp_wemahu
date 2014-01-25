<div class="wrap bootstrap-wpadmin">
	<h2>Wemahu Help</h2>

	<h3>General Usage</h3>
	<p>
		Wemahu is ready to work right after installation. Click the green <em>Start</em> button on the dashboard to run your first scan.
		In the <em>Console</em> you'll see what Wemahu is doing. After the scan is finished the results will show up in the <em>Report</em> area.
	</p>

	<h3>Rulesets</h3>
	<p>
		Rulesets can be used to define the settings for your scans. You can define multiple rulesets. You can define the extensions of files that will be scanned for malware, the max. filesize e.g.<br />
		Using multiple rulesets can be used to define different kinds of scans like e.g. a full-scan which is executed by a cronjob once every night and a quick-scan which is done every hour.
	</p>

	<h3>Interpreting Results</h3>
	<p>
		What is displayed in the results depends on the tests you run. It can be code-snippets in case of a regular-expression test or just the information if a file was changed in case of a hashvalue-test.<br />
		Some results like the ones from the RegEx test are clickable to show more information.
	</p>

	<h3>Running Wemahu Cronjobs</h3>
	<p>
		Besides starting a scan directly from the adminpanel you can also setup a cronjob to perform regular scans.<br />
		In the plugin options you can select a ruleset to be used in this case. You can further define an email-address which will be used to receive the reports.<br />
		Please point your cronjob to the following path:
	</p>
	<pre>/path/to/wordpress/wp-content/plugins/wemahu/wemahu_cli.php</pre>
	<p>An example entry in your crontab file could look like this:</p>
	<pre>0 2 * * * php /var/www/wordpress/wp-content/plugins/wemahu/wemahu_cli.php</pre>
	<p>This would run the Wemahu scanner every night at 02:00.</p>
	<p>If you want to run multiple cronjobs with different rulesets you can pass a ruleset id with the --ruleset parameter.</p>
	<pre>0 2 * * * php /var/www/wordpress/wp-content/plugins/wemahu/wemahu_cli.php --ruleset 2</pre>
	<p><span class="label label-warning">Hint</span> You have to use a real cronjob. Calling the script periodically using an URL will not work!</p>
</div>