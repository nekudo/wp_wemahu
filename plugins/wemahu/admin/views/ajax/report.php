<?php
/**
 * @package	wp_wemahu
 * @license GNU General Public License version 2 or later; see license.txt
 * @copyright nekudo.com
 * @author Simon Samtleben <support@nekudo.com>
 */
?>
<h2>Wemahu Report</h2>
<?php if(empty($this->Report) || empty($this->Report->reportItems)): ?>
	<em>No potentially malicious code found.</em>
<?php else: ?>
	<!-- Filecheck Report -->
	<?php $itemCount = 1; ?>
	<?php if(empty($this->Report->reportItems['filecheck'])): ?>
		<em>Filecheck did not report any suspicious files.</em>
	<?php else: ?>
		<h3>Results of RegEx check</h3>
		<table class="table table-hover" id="wmReportTable">
			<thead>
			<tr>
				<th>#</th>
				<th>Type</th>
				<th>Match</th>
				<th>File</th>
			</tr>
			</thead>
			<tbody>
			<?php foreach($this->Report->getItems('filecheck') as $i => $ReportItem): ?>
				<?php if($ReportItem->checkName !== 'regexCheck'): continue; endif; ?>
				<tr class="wmReportItem" data-reportitemid="<?php echo $i; ?>">
					<td><?php echo $itemCount; ?></td>
					<td><?php echo $ReportItem->matchName; ?></td>
					<td><?php echo htmlentities(substr($ReportItem->matchSnippet, 0, 100)); ?></td>
					<td><?php echo $ReportItem->affectedFile; ?></td>
				</tr>
				<?php $itemCount++; ?>
			<?php endforeach; ?>
			</tbody>
		</table>

		<h3>Results of hash check</h3>
		<table class="table table-hover" id="wmHashcheckReportTable">
			<thead>
			<tr>
				<th>#</th>
				<th>Type</th>
				<th>File</th>
			</tr>
			</thead>
			<tbody>
			<?php foreach($this->Report->getItems('filecheck') as $i => $ReportItem): ?>
				<?php if($ReportItem->checkName !== 'hashCheck'): continue; endif; ?>
				<tr class="wmReportItem" data-reportitemid="<?php echo $i; ?>">
					<td><?php echo $itemCount; ?></td>
					<td>
						<?php if($ReportItem->type === 'new_file'): ?>
							New file
						<?php elseif($ReportItem->type === 'modified_file'): ?>
							Modified file
						<?php endif; ?>
					</td>
					<td><?php echo $ReportItem->affectedFile; ?></td>
				</tr>
				<?php $itemCount++; ?>
			<?php endforeach; ?>
			</tbody>
		</table>
	<?php endif; ?>
	<!-- /Filecheck Report -->

	<div id="wmAjaxModalPlaceholder"></div>
<?php endif; ?>