<div class="wrap">
	<h2>Edit Ruleset</h2>
	<?php if(!empty($message)): ?>
		<?php $msgClass = ($message['type'] === 'error') ? 'error' : 'updated'; ?>
		<div id="system-message" class="<?php echo $msgClass; ?>">
			<p><strong><?php echo esc_html($message['text']); ?></strong></p>
		</div>
	<?php endif; ?>
	<form method="post" action="?page=<?php echo esc_html($_REQUEST['page']); ?>">
		<h3>General</h3>
		<table class="form-table">
			<tr valign="top">
				<th scope="row">
					<label for="ruleset_name">Name</label>
				</th>
				<td>
					<input type="text" id="ruleset_name" class="regular-text ltr" name="ruleset[name]" value="<?php echo esc_html($rulesetData['name']); ?>" />
					<p class="description"> A descriptive name for the ruleset.</p>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row">Enable filecheck</th>
				<td>
					<label for="enable_filecheck">
						<input type="checkbox" value="1" <?php if((int)$rulesetData['filecheck'] === 1): ?> checked="checked" <?php endif; ?> id="enable_filecheck" name="ruleset[filecheck]" />
						Enables the Wemahu filecheck module.
					</label>
				</td>
			</tr>
		</table>

		<h3>Filecheck Settings</h3>
		<table class="form-table">
			<tr valign="top">
				<th scope="row">
					<label for="scandir">Scan path</label>
				</th>
				<td>
					<input type="text" id="scandir" class="regular-text ltr" name="ruleset[scandir]" value="<?php echo esc_html($rulesetData['scandir']); ?>" />
					<p class="description">Here you can define the path which is (recursiv) scanned. By default wordpress root will be used. The path has to be absolut.</p>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row">Use Signature-Check</th>
				<td>
					<label for="regex_check">
						<input type="checkbox" value="1" <?php if((int)$rulesetData['regex_check'] === 1): ?> checked="checked" <?php endif; ?> id="regex_check" name="ruleset[regex_check]" />
						This check will scan files for malicious code using a signature database.
					</label>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row">Use Modification-Check</th>
				<td>
					<label for="hash_check">
						<input type="checkbox" value="1" <?php if((int)$rulesetData['hash_check'] === 1): ?> checked="checked" <?php endif; ?> id="hash_check" name="ruleset[hash_check]" />
						This check will check files for modifications since last scan using hash-values.
					</label>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row">Modification blacklist</th>
				<td>
					<label for="hash_check">
						<textarea type="textarea" id="hash_check_blacklist" name="ruleset[hash_check_blacklist]" class="regular-text ltr" rows="5" cols="40"><?php echo $rulesetData['hash_check_blacklist']; ?></textarea><br />
						<span class="description">In this field you can enter paths which should not be scanned for file modifications like e.g. your cache folder. One path per line. The path has to be relative to your scan folder.</span>
					</label>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row">
					<label for="filetypes">Filetypes to scan</label>
				</th>
				<td>
					<input type="text" id="filetypes" class="regular-text ltr" name="ruleset[filetypes]" value="<?php echo esc_html($rulesetData['filetypes']); ?>" />
					<p class="description">All files which have a suffix from this list will be included in the checks.</p>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row">
					<label for="filesize_max">Max. filesize</label>
				</th>
				<td>
					<input type="text" id="filesize_max" class="regular-text ltr" name="ruleset[filesize_max]" value="<?php echo esc_html($rulesetData['filesize_max']); ?>" />
					<p class="description">Maximum size of files that will be included in checks.</p>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row">
					<label for="max_results_file">Max. results per file</label>
				</th>
				<td>
					<input type="text" id="max_results_file" class="regular-text ltr" name="ruleset[max_results_file]" value="<?php echo esc_html($rulesetData['max_results_file']); ?>" />
					<p class="description">Defines the maximal results the scan-engine will report per file. If the limit is reached the engine skips over to the next file.</p>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row">
					<label for="max_results_total">Max. results total</label>
				</th>
				<td>
					<input type="text" id="max_results_total" class="regular-text ltr" name="ruleset[max_results_total]" value="<?php echo esc_html($rulesetData['max_results_total']); ?>" />
					<p class="description">Defines the maximal results the scan-engine will report in total. If the limit is reached the scan is stopped. If you experience any problems with the session during a scan you should decrease this value.</p>
				</td>
			</tr>
		</table>
		<p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary" value="Save Changes"></p>

		<input type="hidden" name="ruleset[regex_db]" value="regex_complete" />
		<input type="hidden" name="id" value="<?php echo esc_html($rulesetData['id']); ?>" />
		<input type="hidden" name="page" value="<?php echo esc_html($_REQUEST['page']); ?>" />
		<input type="hidden" name="action" value="save" />
		<?php wp_nonce_field('save_ruleset_'.$rulesetData['id']); ?>
	</form>

</div>