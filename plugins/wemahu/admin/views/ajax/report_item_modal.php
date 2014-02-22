<?php
/**
 * @package	com_wemahu
 * @license GNU General Public License version 2 or later; see license.txt
 * @copyright nekudo.com
 * @author Simon Samtleben <support@nekudo.com>
 */
?>
<?php if($this->ReportItem->checkName === 'regexCheck'): ?>
	<div class="modal fade" id="reportModal" tabindex="-1" role="dialog" aria-labelledby="reportModalLabel" aria-hidden="true">
		<div class="modal-dialog">
			<div class="modal-content">
				<div class="modal-header">
					<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
					<h4 class="modal-title" id="myModalLabel">Report Item Information</h4>
				</div>
				<div class="modal-body">
					<div id="wmAjaxSystemMsg"></div>

					<h4>Affected file</h4>
					<pre><?php echo $this->ReportItem->affectedFile; ?></pre>

					<h4>Explanation</h4>
					<p class="reportItemDesc"><?php echo htmlentities($this->ReportItem->matchDescription); ?></p>

					<h4>Snippet</h4>
					<pre class="reportItemSnippet"><?php echo htmlentities($this->ReportItem->matchSnippet); ?></pre>
				</div>
				<div class="modal-footer">
					<form method="post" action="admin-ajax.php?page=wemahu">
						<input type="hidden" name="report_id" value="<?php echo $this->reportId; ?>" />
						<?php if($this->useApi === true): ?>
							<button class="btn btn-danger wmAjaxAddToList wmAjaxBlacklistButton" data-task="addToBlacklist">Report as malware</button>
						<?php endif; ?>
						<button class="btn btn-success wmAjaxAddToList wmAjaxWhitelistButton" data-task="addToWhitelist">Add to whitelist</button>
						<button class="btn" data-dismiss="modal" aria-hidden="true">Close</button>
					</form>
				</div>
			</div
		</div>
	</div>
<?php endif; ?>

<?php if($this->ReportItem->checkName === 'hashCheck'): ?>
	<div class="modal fade" id="reportModal" tabindex="-1" role="dialog" aria-labelledby="reportModalLabel" aria-hidden="true">
		<div class="modal-dialog">
			<div class="modal-content">
				<div class="modal-header">
					<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
					<h4 class="modal-title" id="myModalLabel">Report Item Information</h4>
				</div>
				<div class="modal-body">
					<div id="wmAjaxSystemMsg"></div>

					<h4>Affected file</h4>
					<pre><?php echo $this->ReportItem->affectedFile; ?></pre>

					<h4>Explanation</h4>
					<p>
						<?php if($this->ReportItem->type === 'new_file'): ?>
							New file
						<?php elseif($this->ReportItem->type === 'modified_file'): ?>
							Modified file
						<?php endif; ?>
					</p>
					<p>
						<strong>Time of last file modification:</strong> <?php echo $this->ReportItem->lastmod; ?>
					</p>
				</div>
				<div class="modal-footer">
					<form method="post" action="admin-ajax.php?page=wemahu">
						<input type="hidden" name="report_id" value="<?php echo $this->reportId; ?>" />
						<?php if($this->useApi === true): ?>
							<button class="btn btn-danger wmAjaxAddToList wmAjaxBlacklistButton" data-task="addToBlacklist">Report as malware</button>
						<?php endif; ?>
						<button class="btn" data-dismiss="modal" aria-hidden="true">Close</button>
					</form>
				</div>
			</div
		</div>
	</div>
<?php endif; ?>