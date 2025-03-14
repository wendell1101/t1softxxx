var Adjusted_deposits_game_totals = Adjusted_deposits_game_totals || {};

Adjusted_deposits_game_totals.uri_list = {};
Adjusted_deposits_game_totals.uri_list.updateAdjustedDepositsGameTotals = '/api/update_adjusted_deposits_game_totals';
Adjusted_deposits_game_totals.uri_list.getPlayerUsernames = '/player_management/getPlayerUsernames';

Adjusted_deposits_game_totals.langs = {};
Adjusted_deposits_game_totals.langs.modal_title = 'Adjusted Deposits / Game Totals.';
Adjusted_deposits_game_totals.langs.csv_export = 'CSV Export.';
Adjusted_deposits_game_totals.langs.na = 'N/A.';
Adjusted_deposits_game_totals.langs.processing = 'Processing.';
Adjusted_deposits_game_totals.langs.is_required = 'is required.';
Adjusted_deposits_game_totals.langs.invalid_amount = 'Invalid amount.';

// mainModal
// syncResultModal
// bulkResultModal
// deleteConfirmModal

Adjusted_deposits_game_totals.defaults = {}
Adjusted_deposits_game_totals.defaults.isEnabledFeature = {};
Adjusted_deposits_game_totals.defaults.isEnabledFeature.export_excel_on_queue = false;
Adjusted_deposits_game_totals.defaults.permissions = {};
Adjusted_deposits_game_totals.defaults.permissions.export_adjusted_deposits_game_totals = false;

Adjusted_deposits_game_totals.batchPlayers = [];

Adjusted_deposits_game_totals.initialize = function (options, langs) {
	var _this = this;
	_this.options = $.extend(true, {}, _this.defaults, options);
	_this.langs = $.extend(true, {}, _this.langs, langs);
	return _this;
}
Adjusted_deposits_game_totals.onReady = function () {
	var _this = this;
	_this.eventsHandle();
	$('[name="checkbox_is_enabled_date"]').trigger('change');
	_this.doDataTable();
	// auto-complete
	_this.do_select2_player_username();


}; // EOF Adjusted_deposits_game_totals.onReady = function () {...

Adjusted_deposits_game_totals.clearInputFieldsModal = function (parentModalSelector) {
	var _this = this;
	if (typeof (parentModalSelector) === 'undefined') {
		parentModalSelector = '#mainModal';
	}

	_this.batchPlayers = [];
	$("#player_username_select", parentModalSelector).val("").trigger("change");

	$(".clear-fields", parentModalSelector).val("");
};

Adjusted_deposits_game_totals.clearHelpBlocksModal = function (parentModalSelector) {
	var _this = this;
	if (typeof (parentModalSelector) === 'undefined') {
		parentModalSelector = '#mainModal';
	}

	Adjusted_deposits_game_totals.showNextHelpBlocksModal(parentModalSelector, '.clear-fields', '');
}

Adjusted_deposits_game_totals.showNextHelpBlocksModal = function (parentModalSelector, currFieldSelector, helpString) {
	var _this = this;
	if (typeof (parentModalSelector) === 'undefined') {
		parentModalSelector = '#mainModal';
	}
	var currField$El = $(parentModalSelector).find(currFieldSelector);
	var currHelpBlock$El = currField$El.parent().find(".help-block");
	currHelpBlock$El.html(helpString);
	if (helpString.length > 0) {
		currHelpBlock$El.removeClass('hide');
	} else {
		currHelpBlock$El.addClass('hide');
	}
} // EOF showNextHelpBlocksModal



Adjusted_deposits_game_totals.assignInputFieldsInSyncModal = function (username
	, total_bet_amount
	, total_deposit_amount
	, data_id
) {
	var _this = this;
	var mainModal$El = $('#mainModal');
	mainModal$El.find('[name="player_username"]').val(username);
	if (_this.currSyncModal == 'row_update') {
		mainModal$El.find('#player_username_text').val(username);
	} else {
		mainModal$El.find('#player_username_select').val(username).trigger('change');
	}

	mainModal$El.find('[name="total_bet_amount"]').val(total_bet_amount);
	mainModal$El.find('[name="total_deposit_amount"]').val(total_deposit_amount);
	if (typeof (data_id) !== 'undefined') {
		mainModal$El.find('[name="data_id"]').val(data_id);
	}
};


Adjusted_deposits_game_totals.changed_csv_file_batch_sync = function (e) {
	var _this = this;
	var target$El = $(e.target);
	var fileName = target$El.val();
	_this.drawSyncModal('csv_file_batch_sync');
}

Adjusted_deposits_game_totals.clicked_csv_file_batch_sync = function (e) {
	var _this = this;
	var target$El = $(e.target);
	var fileName = target$El.val('');
	_this.drawSyncModal('csv_file_batch_sync');
}

Adjusted_deposits_game_totals.drawSyncModal = function (modeStr) {
	var _this = this;
	var mainModal$El = $('#mainModal');

	if (typeof (modeStr) === 'undefined') {
		var modeStr = 'row_update';
	}
	_this.clearHelpBlocksModal('#mainModal');
	_this.currSyncModal = modeStr.toLocaleLowerCase();
	switch (_this.currSyncModal) {
		case 'row_add':
		case 'csv_file_batch_sync':
			mainModal$El.find('.form-group:has("#player_username_text")').addClass('hide');
			mainModal$El.find('.form-group:has("#player_username_select")').removeClass('hide');

			var to_disable_edit_fields = null;
			if (mainModal$El.find('[name="csv_file_batch_sync"]').val() != '') {
				to_disable_edit_fields = true;
			} else {
				to_disable_edit_fields = false;
			}
			if (to_disable_edit_fields) {
				mainModal$El.find('[name="player_username"]').parent().find('select')
					.prop('disabled', true).attr('disabled', 'disabled');
				mainModal$El.find('input:text,input[type="number"]')
					.prop('disabled', true).attr('disabled', 'disabled');
			} else {
				mainModal$El.find('[name="player_username"]').parent().find('select')
					.prop('disabled', false).attr('disabled', false);
				mainModal$El.find('input:text,input[type="number"]')
					.prop('disabled', false).attr('disabled', false);
			}
			mainModal$El.find('.csv_file_batch_sync_row').removeClass('hide');
			mainModal$El.find('[name="csv_file_batch_sync"]').prop('disabled', false).attr('disabled', false);
			break;
		case 'row_update':
			mainModal$El.find('[name="player_username"]').parent().find('select')
				.prop('disabled', false).attr('disabled', false);
			mainModal$El.find('input:text,input[type="number"]')
				.prop('disabled', false).attr('disabled', false);

			mainModal$El.find('.form-group:has("#player_username_text")').removeClass('hide');
			mainModal$El.find('.form-group:has("#player_username_select")').addClass('hide');

			mainModal$El.find('.csv_file_batch_sync_row').addClass('hide');
			mainModal$El.find('[name="csv_file_batch_sync"]').prop('disabled', true).attr('disabled', 'disabled');
			break;
	}
}; // EOF drawSyncModal()



Adjusted_deposits_game_totals.clicked_btn_add_new = function (e) {
	var _this = this;
	_this.clearInputFieldsModal();
	_this.drawSyncModal('row_add');
	$('#mainModalLabel').html(_this.langs.modal_title);

	$('#mainModal').modal('show');
};

Adjusted_deposits_game_totals.clicked_btn_edit = function (e) {
	var _this = this;
	var target$El = $(e.target);

	_this.clearInputFieldsModal();
	_this.drawSyncModal('row_update');
	$('#mainModalLabel').html(_this.langs.modal_title);

	var json_data_String = target$El.closest('tr').find('textarea').val().trim();
	var json_data = JSON.parse(json_data_String);
	_this.assignInputFieldsInSyncModal(json_data.player_username
		, json_data.total_bet_amount
		, json_data.total_deposit_amount
		, json_data.id);

	$('#mainModal').modal('show');
};

Adjusted_deposits_game_totals.isNumeric = function (n) {
	return !isNaN(parseFloat(n)) && isFinite(n);
}
Adjusted_deposits_game_totals.validateBySyncModal = function () {
	var _this = this;
	var syncModalSelector = '#mainModal';
	var mainModal$El = $(syncModalSelector);
	var _return_info = {};
	_return_info.bool = true; // initial
	_return_info.issues = [];
	_this.clearHelpBlocksModal(syncModalSelector);
	switch (_this.currSyncModal) {

		case 'csv_file_batch_sync':

			break;

		case 'row_add':
		case 'row_update':
			var player_username = $(mainModal$El).find('[name="player_username"]').val();
			var total_bet_amount = $(mainModal$El).find('[name="total_bet_amount"]').val();
			var total_deposit_amount = $(mainModal$El).find('[name="total_deposit_amount"]').val();
			if (player_username.length == 0) {
				_return_info.bool = _return_info.bool && false;
				_return_info.issues.push({
					inputSelector: '[name="player_username"]'
					, msg: _this.langs.is_required
				});
			}
			if (!_this.isNumeric(total_bet_amount)) {
				_return_info.bool = _return_info.bool && false;
				_return_info.issues.push({
					inputSelector: '[name="total_bet_amount"]'
					, msg: _this.langs.invalid_amount
				});
			}
			if (!_this.isNumeric(total_deposit_amount)) {
				_return_info.bool = _return_info.bool && false;
				_return_info.issues.push({
					inputSelector: '[name="total_deposit_amount"]'
					, msg: _this.langs.invalid_amount
				});
			}
			break;
	}
	return _return_info;
};

Adjusted_deposits_game_totals.clicked_btn_sync_submit = function (e) {
	var _this = this;
	var target$El = $(e.target);
	var _active_uri = target$El.closest('form').attr('action');
	var syncModalSelector = '#mainModal';
	var bulkResultModalSelector = '#bulkResultModal';

	var mainModal$El = $(syncModalSelector);
	// var _data = target$El.closest('form').serializeArray();

	var formData = new FormData(target$El.closest('form')[0])
	// formData.append('ttname', 'ttt');

	var validated_result = _this.validateBySyncModal();
	if (!validated_result.bool) {
		// display tip via .help-block
		validated_result.issues.forEach(function (_issue, indexNumber, _issues) {
			_this.showNextHelpBlocksModal(syncModalSelector, _issue.inputSelector, _issue.msg);
		});
	} else {
		// clear .help-block
		_this.clearHelpBlocksModal(syncModalSelector);
		// submit ajax
	}



	var is_csv_file_batch_sync = false;
	if (mainModal$El.find('[name="csv_file_batch_sync"]').val() != '') {
		is_csv_file_batch_sync = true;
	}
	if (is_csv_file_batch_sync) {
		// batch sync
		var _beforeSendCB = function (jqXHR, settings) {
			if (validated_result.bool) {
				$(syncModalSelector).modal('hide');
				$(bulkResultModalSelector).modal('show');
			}
			return validated_result.bool;
		}
	} else {
		// Insert Or Update a data
		var _beforeSendCB = function (jqXHR, settings) {
			if (validated_result.bool) {
				_this.clearHelpBlocksModal(syncModalSelector);
				target$El.button('loading');
			}
			return validated_result.bool;
		}
	}


	var _ajax = $.ajax({
		'url': _active_uri,
		'type': 'POST',
		'contentType': false, //required
		'processData': false, // required
		'mimeType': 'multipart/form-data',
		'data': formData,
		'cache': false,
		'dataType': "json",
		beforeSend: function (jqXHR, settings) {
			var cloned_arguments = Array.prototype.slice.call(arguments);
			return _beforeSendCB.apply(_this, cloned_arguments);
		},
	});

	if (is_csv_file_batch_sync) {
		// batch sync
		_ajax.done(function (data, textStatus, jqXHR) {
			if (data.status) {

				$('.total_rows_amount').html(data.totalCount);
				$('.processed_rows_amount').html(data.successCnt);
				$('.status_value').html(data.msg);

				$('.total_players_amount').html(data.totalCount);
				$('.success_number_amount').html(data.successCnt);
				$('.failure_number_amount').html(data.failedCnt);

				$('a.status_link_href').html(data.log_filepath);
				$('a.status_link_href').attr('href', data.log_filepath);

				_this.dataTable.draw(false);
			}
		});
		_ajax.always(function (data_jqXHR, textStatus, jqXHR_errorThrown) {
			if (textStatus == 'error') {
				_this.pause_progress_bulkResultModal(jqXHR_errorThrown);
			} else {
				_this.pause_progress_bulkResultModal(data_jqXHR.msg);
			}
		});
	} else {
		// Insert Or Update a data
		_ajax.done(function (data, textStatus, jqXHR) {
			if (data.status) {
				_this.dataTable.draw(false);
				$(syncModalSelector).modal('hide');
			} else {
				var relatedFieldSelector = '';
				switch (data.result_code) {
					case _this.options.sync_result_code_invalid_bet_amount:
						relatedFieldSelector = 'input[name="total_bet_amount"]';
						break;
					case _this.options.sync_result_code_invalid_deposit_amount:
						relatedFieldSelector = 'input[name="total_deposit_amount"]';
						break;
					case _this.options.sync_result_code_username_not_exist:
						relatedFieldSelector = 'input[name="player_username"]';
						break;
					default:
					case _this.options.sync_result_code_unknown_error:
						relatedFieldSelector = '.sync-result-block';
						break;
				}
				_this.showNextHelpBlocksModal(syncModalSelector, relatedFieldSelector, data.msg);
			}
		});

		_ajax.fail(function (jqXHR, textStatus, errorThrown) {
			// _this.showResponseMessage(errorThrown);
		});

		_ajax.always(function (data_jqXHR, textStatus, jqXHR_errorThrown) {
			target$El.button('reset');
		});

	} // EOF if(is_csv_file_batch_sync){...
};

Adjusted_deposits_game_totals.clicked_btn_delete = function (e) {
	var _this = this;
	_this.clearInputFieldsModal();

	var target$El = $(e.target);

	var json_data_String = target$El.closest('tr').find('textarea').val().trim();
	var json_data = JSON.parse(json_data_String);
	$('#deleteConfirmModal').find('input[name="data_id"]').val(json_data.id);

	$('#deleteConfirmModalLabel').html(_this.langs.modal_title);
	$('#deleteConfirmModal').modal('show');
};

Adjusted_deposits_game_totals.clicked_btn_do_delete = function (e) {
	var _this = this;
	var target$El = $(e.target);

	var _active_uri = target$El.closest('form').attr('action');
	var formData = new FormData(target$El.closest('form')[0])

	var _ajax = $.ajax({
		'url': _active_uri,
		'type': 'POST',
		'contentType': false, //required
		'processData': false, // required
		'mimeType': 'multipart/form-data',
		'data': formData,
		'cache': false,
		'dataType': "json",
		beforeSend: function (jqXHR, settings) {
			target$El.button('loading');
			// beforeSendCB.apply(_this, arguments);
		},
	});
	_ajax.done(function (data, textStatus, jqXHR) {
		if (data.status) {
			_this.dataTable.draw(false);
			$('#deleteConfirmModal').modal('hide');
		}
	});

	_ajax.fail(function (jqXHR, textStatus, errorThrown) {
		// _this.showResponseMessage(errorThrown);
	});

	_ajax.always(function (data_jqXHR, textStatus, jqXHR_errorThrown) {
		target$El.button('reset');
	});
}


Adjusted_deposits_game_totals.eventsHandle = function () {
	var _this = this;
	$('body').on('change', '[name="checkbox_is_enabled_date"]', function (e) {
		var thisEl = this;
		if (thisEl.checked) {
			$('[data-start="#start_date"][data-end="#end_date"]').prop('disabled', false);
			$('#start_date').prop('disabled', false);
			$('#end_date').prop('disabled', false);
			$('[name="is_enabled_date"]').val(1);
		} else {
			$('[data-start="#start_date"][data-end="#end_date"]').prop('disabled', true);
			$('#start_date').prop('disabled', true);
			$('#end_date').prop('disabled', true);
			$('[name="is_enabled_date"]').val(0);
		}
	});

	$('body').on('click', '.btn_add_new', function (e) {
		_this.clicked_btn_add_new(e);
	});

	$('body').on('click', '.btn_edit', function (e) {
		_this.clicked_btn_edit(e);
	});

	$('body').on('click', '.btn_delete', function (e) {
		_this.clicked_btn_delete(e);
	});
	$('body').on('click', '.btn_do_delete', function (e) {
		_this.clicked_btn_do_delete(e);
	});


	$('body').on('click', '.btn_sync_submit', function (e) {
		_this.clicked_btn_sync_submit(e);
	});

	// auto-complete
	// $("#player_username").on("select2:select", function (e) {
	$('body').on("select2:select", "#player_username_select", function (e) {
		_this.select2_select_player_username(e);
	});
	// $("#player_username").on("select2:unselect", function (e) {
	$('body').on("select2:unselect", "#player_username_select", function (e) {
		_this.select2_unselect_player_username(e);
	});


	$('body').on('change', '[name="csv_file_batch_sync"]', function (e) {
		_this.changed_csv_file_batch_sync(e);
	});

	$('body').on('click', '[name="clicked_csv_file_batch_sync"]', function (e) {
		_this.clicked_csv_file_batch_sync(e);
	});

	// $('#bulkResultModal').on('show.bs.modal', function (e) {
	$('body').on('show.bs.modal', '#bulkResultModal', function (e) {
		_this.show_bs_modal_bulkResultModal(e);
	});

} // EOF Adjusted_deposits_game_totals.eventsHandle = function () {...

Adjusted_deposits_game_totals.select2_select_player_username = function (e) {
	var _this = this;
	var p = e.params.data,
		username = p.username || p.text,
		playerId = p.id,
		player = { id: playerId, username: username };
	$('input[name="player_username"]').val(username);

	if (_this.userExistInSelected(player.username)) {
		_this.batchPlayers.push(player);
		// updateDatatableCheckbox();
	}
	// validateSelect2();
}
Adjusted_deposits_game_totals.select2_unselect_player_username = function (e) {
	var _this = this;
	var p = e.params.data,
		username = p.username || p.text,
		playerId = p.id;
	_this.findAndRemove('username', username);
	// updateDatatableCheckbox();
	// validateSelect2();
}
Adjusted_deposits_game_totals.userExistInSelected = function (username) {
	var _this = this;
	var id = _this.batchPlayers.length + 1;
	var found = _this.batchPlayers.some(function (el) {
		return el.username === username;
	});
	if (!found) {
		return true;
	} else {
		return false;
	}
}
Adjusted_deposits_game_totals.findAndRemove = function (property, value) {
	var _this = this;
	_this.batchPlayers.forEach(function (result, index) {
		if (result[property] === value) {
			_this.batchPlayers.splice(index, 1);

		}
	});
}
Adjusted_deposits_game_totals.do_select2_player_username = function () {
	var _this = this;
	$("#player_username_select").select2({
		maximumSelectionLength: 1,
		multiple: true,
		ajax: {
			url: _this.uri_list.getPlayerUsernames,
			dataType: 'json',
			delay: 250,
			data: function (params) {
				var query = {
					q: params.term,
					page: params.page
				}
				// Query paramters will be ?search=[term]&page=[page]
				return query;
			},
			allowClear: true,
			// tags: true,
			processResults: function (data, params) {
				params.page = params.page || 1;
				return {
					results: data.items,
					pagination: {
						more: (params.page * 30) < data.total_count
					}
				};
			},
			cache: true
		},
		escapeMarkup: function (markup) { return markup; },
		minimumInputLength: 1,
		templateResult: _this.formatOption,
		templateSelection: _this.formatOptionSelection
	});
}
Adjusted_deposits_game_totals.formatOption = function (opt) {
	if (opt.loading) {
		return opt.text;
	} else {
		return opt.username;
	}
}
Adjusted_deposits_game_totals.formatOptionSelection = function (opt) {
	return opt.username || opt.text;
}
Adjusted_deposits_game_totals.validateSelect2 = function () {
	var _this = this;
	if (!_this.batchPlayers.length) {
		$(".player-username-help-block").html(_this.langs.is_required); //  '<?=lang("system.word38").lang("lang.is.required")?>');
	} else {
		$(".player-username-help-block").html('');
	}
}
// batchPlayers = Array();
// $("#player_username").val("").trigger("change");

Adjusted_deposits_game_totals.active_progress_bulkResultModal = function (progress_msg) {
	var _this = this;
	$('.progress .progress-bar', '#bulkResultModal')
		.addClass('progress-bar-striped')
		.addClass('active');
	$('.progress .progress-bar span', '#bulkResultModal').html(progress_msg);
}

Adjusted_deposits_game_totals.pause_progress_bulkResultModal = function (progress_msg) {
	var _this = this;

	$('.progress .progress-bar', '#bulkResultModal')
		.removeClass('progress-bar-striped')
		.removeClass('active');
	$('.progress .progress-bar span', '#bulkResultModal').html(progress_msg);
}

Adjusted_deposits_game_totals.show_bs_modal_bulkResultModal = function (e) {
	var _this = this;
	var target$El = $(e.target);

	_this.active_progress_bulkResultModal(_this.langs.processing);

	$('.total_rows_amount', target$El).html(0);
	$('.processed_rows_amount', target$El).html(0);
	$('.status_value', target$El).html(_this.langs.na);

	$('.total_players_amount', target$El).html(0);
	$('.success_number_amount', target$El).html(0);
	$('.failure_number_amount', target$El).html(0);

	$('a.status_link_href', target$El).html(_this.langs.na);
	$('a.status_link_href', target$El).attr('href', '');
};

Adjusted_deposits_game_totals.doDataTable = function () {
	var _this = this;
	var dataTable_buttons = [];

	var _className = '';
	if (_this.options.use_new_sbe_color) {
		_className += 'btn-linkwater';
	}
	dataTable_buttons.push({
		extend: 'colvis',
		postfixButtons: ['colvisRestore'],
		className: _className,
	});

	if (_this.options.permissions.export_adjusted_deposits_game_totals) {
		var _className = 'btn btn-sm ';
		if (_this.options.use_new_sbe_color) {
			_className += 'btn-portage';
		} else {
			_className += 'btn-primary';
		}

		dataTable_buttons.push({
			text: _this.langs.csv_export,
			className: _className,
			action: function (e, dt, node, config) {
				if (_this.options.isEnabledFeature.export_excel_on_queue) {

					var form_params = $('#search-form').serializeArray();
					var d = {
						'extra_search': form_params, 'export_format': 'csv', 'export_type': 'queue',
						'draw': 1, 'length': -1, 'start': 0
					};
					$("#_export_excel_queue_form").attr('action', site_url('/export_data/adjusted_deposits_game_totals_via_queue'));
					$("#_export_excel_queue_form [name=json_search]").val(JSON.stringify(d));
					$("#_export_excel_queue_form").submit();
				} // EOF if(_this.options.isEnabledFeature.export_excel_on_queue){...
			} // EOF action: function ( e, dt, node, config ) {...
		});
	}

	_this.dataTable = $('#myTable').DataTable({
		autoWidth: false,
		searching: false,
		responsive: false,
		// dom: "<'pasnel-body'<'pull-right'B>l>t<'text-center'r><'panel-body'<'pull-right'p>i>",
		dom: "<'panel-body' <'pull-right'B><'pull-right progress-container'>l><'dt-information-summary1 text-info pull-left' i>t<'panel-body'<'pull-right'p><'dt-information-summary2 text-info pull-left' i>>",
		buttons: dataTable_buttons,
		order: [[1, 'asc']],

		// SERVER-SIDE PROCESSING
		processing: true,
		serverSide: true,
		ajax: function (data, callback, settings) {
			data.extra_search = $('#search-form').serializeArray();
			$.post(base_url + "api/adjusted_deposits_game_totals_list", data, function (data) {
				callback(data);
			}, 'json');
		},

	});
};


