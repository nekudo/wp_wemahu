jQuery(document).ready(function($) {

	// simple ajax queue:
	var ajaxQueue = $({});
	$.ajaxQueue = function(ajaxOpts) {
		var oldComplete = ajaxOpts.complete;
		ajaxQueue.queue(function(next) {
			ajaxOpts.complete = function() {
				if (oldComplete) {
					oldComplete.apply(this, arguments);
				}
				next();
			};
			$.ajax(ajaxOpts);
		});
	};

	// manages ajax-audits:
	$.handleAjaxAudit = function() {
		$('#wmAjaxAuditSubmit').click(function(event) {
			event.preventDefault();
			$.writeLog('Running audit. Waiting for response...', 'notice1');
			$.submitAuditForm('initWemahu');
		});
	};

	// manages whitelist/blacklist actions:
	$.handleAddToList = function() {
		$('.wmAjaxAddToList').click(function(event) {
			event.preventDefault();
			var task = $(this).data('task');
			var reportId = $(this).closest('form').find('input[name="report_id"]').val();
			var ajaxRand = new Date().getTime();
			$.ajaxQueue({
				type: "POST",
				url: 'admin-ajax.php?page=wemahu',
				dataType: 'json',
				data: 'action=handle_ajax&task=' + task + '&reportId=' + reportId + '&rv=' + ajaxRand,
				success: function(jsonResponse) {
					switch(jsonResponse.type) {
						case 'message':
							$.showSystemMessage(jsonResponse.msgHtml);
							break;

						case 'error':
							$.showSystemMessage(jsonResponse.errorMsgHtml);
							break;
					}
				},
				error: function(data, errormsg) {
					$.writeLog('Server error: ' + errormsg, 'warn');
				}
			});
		});
	};

	$.handleAuditResponse = function(response) {
		switch(response.type) {
			case 'error':
				$.writeLog(response.errorMsgHtml, 'warn');
				break;

			case 'init_success':
				$.writeLog(response.data.init_msg);
				$.setProgress(1);
				$.submitAuditForm('runWemahu');
				break;

			case 'audit_complete':
				$.writeLog(response.data.audit_msg, 'notice1');
				$.setProgress(99);
				$.submitAuditForm('getWemahuReport');
				break;

			case 'audit_incomplete':
				$.writeLog(response.data.audit_msg);
				$.setProgress(response.data.percentDone);
				$.submitAuditForm('runWemahu');
				break;

			case 'report_success':
				$.setProgress(100);
				$('#wmAjaxResponse').html(response.data.reportHtml);
				$('.wmReportItem').click(function(e) {
					var reportId = $(this).data('reportitemid');
					var ajaxRand = new Date().getTime();
					$.ajaxQueue({
						type: "POST",
						url: 'admin-ajax.php?page=wemahu',
						dataType: 'json',
						data: 'action=handle_ajax&task=getReportItemModal&reportId=' + reportId + '&rv=' + ajaxRand,
						success: function(jsonResponse) {
							switch(jsonResponse.type) {
								case 'success':
									$.handleReportModalResponse(jsonResponse);
									break;

								case 'error':
									$.showSystemMessage(jsonResponse.errorMsgHtml);
									break;
							}
						},
						error: function(data, errormsg) {
							$.writeLog('Server error: ' + errormsg, 'warn');
						}
					});
				});
				break;
		}
	};

	$.handleReportModalResponse = function(response) {
		$('#wmAjaxModalPlaceholder').html(response.data.modalHtml);
		$('#reportModal').modal('show');
		$.handleAddToList();
	};

	$.submitAuditForm = function(task) {
		var form = $('#wmAjaxAuditSubmit').closest('form');
		var ajaxRand = new Date().getTime();
		$(form).find('input[name="task"]').val(task);
		$.ajaxQueue({
			type: "POST",
			url: 'admin-ajax.php?page=wemahu',
			dataType: 'json',
			data: form.serialize() + '&rv=' + ajaxRand,
			success: function(jsonResponse) {
				$.handleAuditResponse(jsonResponse);
			},
			error: function(data, errormsg) {
				$.writeLog('Server error: ' + errormsg, 'warn');
			}
		});
	};

	$.handleConsoleToggle = function() {
		$('#wmToggleConsole').click(function(e){
			$('#console').slideToggle();
			if($('#wmToggleConsole i').hasClass('icon-chevron-up')) {
				$('#wmToggleConsole i').removeClass('icon-chevron-up').addClass('icon-chevron-down');
			} else {
				$('#wmToggleConsole i').removeClass('icon-chevron-down').addClass('icon-chevron-up');
			}
		});
	};

	$.showSystemMessage = function(msg) {
		$('#wmAjaxSystemMsg').html(msg);
		$('#wmAjaxSystemMsg').find('.alert').delay(5000).fadeOut('slow');
	};

	$.writeLog = function(msg, msgType) {
		var msgClass, msgHtml, msgTime;
		msgTime = moment().format('HH:mm:ss');
		msgType = typeof msgType !== 'undefined' ? msgType : 'info';
		msgTime = typeof msgTime !== 'undefined' ? msgTime : '';
		switch(msgType) {
			case "info":
				msgClass = "appMsgInfo";
				break;
			case "warn":
				msgClass = "appMsgWarn";
				break;
			case "notice1":
				msgClass = "appMsgNoticeGreen";
				break;
			case "notice2":
				msgClass = "appMsgNoticeBlue";
				break;
			case "notice3":
				msgClass = "appMsgNoticePink";
				break;
		}

		msgHtml = "<span class=\"time\">" + msgTime + "</span> <span class=\"" + msgClass + "\">" + msg + "</span>";
		$('#log').prepend("<div class=\"logMsg\">" + msgHtml + "</div>");
		while ($('.logMsg').length > 2000) {
			$('#log div:last').remove();
		}
		$(".nano").nanoScroller();
	};

	$.setProgress = function(percent) {
		if(percent > 0 && percent < 100) {
			$('#wmProgress .progress').addClass('active');
		}
		if(percent === 100) {
			$('#wmProgress .progress').removeClass('active');
		}
		$('#wmProgress .progress-bar').css('width', percent + '%');
	}

	$.handleAjaxAudit();
	$(".nano").nanoScroller();
	$.handleConsoleToggle();
});