<div class="wrap bootstrap-wpadmin">
	<h2>Start new audit</h2>

	<form action="admin.php" method="post" name="ajaxAudit" id="wmAjaxAuditForm" role="form">

		<div class="row">
			<div class="col-xs-3">
				<label id="ruleset-lbl" for="ruleset">Ruleset</label>
				<select id="ruleset" name="ruleset" class="form-control">
					<?php foreach($rulesetValues as $key => $value): ?>
						<option value="<?php echo $key; ?>"><?php echo $value; ?></option>
					<?php endforeach; ?>
				</select>
			</div>
			<div class="col-xs-1 wmNoLabel">
				<button id="wmAjaxAuditSubmit" type="submit" class="btn btn-success">Start</button>
			</div>
		</div>

		<input type="hidden" name="page" value="<?php echo esc_html($_REQUEST['page']); ?>">
		<input type="hidden" name="action" value="handle_ajax" />
		<input type="hidden" name="task" value="ajaxAudit" />
	</form>

	<div id="wmProgress">
		<div class="progress progress-striped">
			<div class="progress-bar"  role="progressbar" aria-valuenow="45" aria-valuemin="0" aria-valuemax="100" style="width: 0%">
				<span class="sr-only">0% Complete</span>
			</div>
		</div>
		<a href="#" id="wmToggleConsole" class="btn btn-xs btn-info">Toggle console</a>
		<div id="console" class="nano" style="display:none;">
			<div id="log" class="content"></div>
		</div>
	</div>

	<div id="wmAjaxResponse"></div>
</div>