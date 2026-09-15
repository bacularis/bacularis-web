<div>
	<p class="w3-hide-small" style="margin-bottom: 0;"><%[ Restore destinations define safe test environments for restore verification. Each destination specifies the Bacula client and the restore path where test data will be restored. ]%></p>
	<div class="w3-container" style="margin: 10px 0">
		<button type="button" id="add_restore_destination_btn" class="w3-button w3-green" onclick="oRestoreDestinations.load_restore_destination_window()"><i class="fa fa-plus"></i> &nbsp;<%[ Add restore destination ]%></button>
	</div>
	<!-- Tag tools -->
	<com:Bacularis.Web.Portlets.TagTools ID="TagToolsRestoreDestinationList" ViewName="restore_destination_list" />
	<table id="restore_destination_list_table" class="display w3-table w3-striped w3-hoverable w3-margin-bottom selectable" style="width: 100%;">
		<thead>
			<tr>
				<th></th>
				<th><%[ Name ]%></th>
				<th class="w3-center"><%[ Client ]%></th>
				<th class="w3-center"><%[ Restore mode ]%></th>
				<th class="w3-center"><%[ Path ]%></th>
				<th class="w3-center"><%[ Capabilities ]%></th>
				<th class="w3-center"><%[ Used by ]%></th>
				<th class="w3-center"><%[ Enabled ]%></th>
				<th class="w3-center"><%[ Tag ]%></th>
				<th class="w3-center"><%[ Actions ]%></th>
			</tr>
		</thead>
		<tbody id="restore_destination_list_body"></tbody>
		<tfoot>
			<tr>
				<th></th>
				<th><%[ Name ]%></th>
				<th class="w3-center"><%[ Client ]%></th>
				<th class="w3-center"><%[ Restore mode ]%></th>
				<th class="w3-center"><%[ Path ]%></th>
				<th class="w3-center"><%[ Capabilities ]%></th>
				<th class="w3-center"><%[ Used by ]%></th>
				<th class="w3-center"><%[ Enabled ]%></th>
				<th class="w3-center"><%[ Tag ]%></th>
				<th class="w3-center"><%[ Actions ]%></th>
			</tr>
		</tfoot>
	</table>
	<p class="info w3-hide-medium w3-hide-small"><%[ Tip: Use left-click to select table row. Use CTRL + left-click to multiple row selection. Use SHIFT + left-click to add a range of rows to selection. ]%></p>
<com:TCallback ID="RestoreDestinationList" OnCallback="TemplateControl.setRestoreDestinationList" />
<com:TCallback ID="LoadRestoreDestination" OnCallback="TemplateControl.loadRestoreDestinationWindow" />
<com:TCallback ID="RemoveRestoreDestinationsAction" OnCallback="TemplateControl.removeRestoreDestinations" />
<script>
var oRestoreDestinationList = {
	ids: {
		restore_destination_list: 'restore_destination_list_table'
	},
	actions: [
		{
			action: 'remove',
			label: '<%[ Remove ]%>',
			value: ['name', 'orgs'],
			callback: <%=$this->RemoveRestoreDestinationsAction->ActiveControl->Javascript%>,
			validate: function(selected) {
				const used_restore_destinations = {};
				const escape_html = function(value) {
					const div = document.createElement('DIV');
					div.textContent = value;
					return div.innerHTML;
				};
				selected.each(function(v, k) {
					if (!Array.isArray(v.used_by_restore_tests) || v.used_by_restore_tests.length == 0) {
						return;
					}
					if (!used_restore_destinations.hasOwnProperty(v.name)) {
						used_restore_destinations[v.name] = [];
					}
					for (let i = 0; i < v.used_by_restore_tests.length; i++) {
						const restore_test = v.used_by_restore_tests[i];
						used_restore_destinations[v.name].push(restore_test);
					}
				});
				const restore_destinations = Object.keys(used_restore_destinations);
				if (restore_destinations.length == 0) {
					return true;
				}
				const lines = [];
				for (let i = 0; i < restore_destinations.length; i++) {
					const restore_destination = restore_destinations[i];
					let line = restore_destination + ':';
					for (let j = 0; j < used_restore_destinations[restore_destination].length; j++) {
						line += "\n - " + used_restore_destinations[restore_destination][j];
					}
					lines.push(line);
				}
				const msg = '<%[ The following restore destinations are used by restore tests and cannot be removed: %used_restore_destinations Please unassign these restore destinations from the restore tests and try removing them again. ]%>';
				const emsg = msg.replace('%used_restore_destinations', "\n\n" + lines.join("\n\n") + "\n\n");
				oBulkActionsModal.set_error(emsg);
				return false;
			}
		}
	],
	data: [],
	table: null,
	table_toolbar: null,
	init: function() {
		if (!this.table) {
			this.set_table();
			this.set_bulk_actions();
			this.set_events();
		} else {
			const page = this.table.page();
			this.table.clear().rows.add(this.data).draw();
			this.table.page(page).draw(false);
			oRestoreDestinationList.set_filters(this.table);
			this.table_toolbar.style.display = 'none';
		}
	},
	set_events: function() {
		document.getElementById(this.ids.restore_destination_list).addEventListener('click', function(e) {
			$(function() {
				const wa = (this.table.rows({selected: true}).data().length > 0) ? 'show' : 'hide';
				$(this.table_toolbar).animate({
					width: wa
				}, 'fast');
			}.bind(this));
		}.bind(this));
	},
	set_table: function() {
		this.table = $('#' + this.ids.restore_destination_list).DataTable({
			data: this.data,
			deferRender: true,
			autoWidth: false,
			fixedHeader: {
				header: true,
				headerOffset: $('#main_top_bar').height()
			},
			layout: {
				topStart: [
					{
						pageLength: {}
					},
					{
						buttons: ['copy', 'csv', 'colvis']
					},
					{
						div: {
							className: 'table_toolbar'
						}
					}
				],
				topEnd: [
					'search'
				],
				bottomStart: [
					'info'
				],
				bottomEnd: [
					'paging'
				]
			},
			stateSave: true,
			stateDuration: KEEP_TABLE_SETTINGS,
			columns: [
				{
					orderable: false,
					data: null,
					defaultContent: '<button type="button" class="w3-button w3-blue"><i class="fa fa-angle-down"></i></button>'
				},
				{data: 'name', render: render_text},
				{data: 'restore_client', render: render_text},
				{
					data: 'restore_mode',
					render: (data, type, row) => {
						let ret = '<%[ Prefixed path ]%>';
						switch (data) {
							case '<%=RestoreDestinationConfig::RESTORE_MODE_ORIGINAL_PATH%>': {
								ret = '<%[ Original path ]%>';
								break;
							}
							case '<%=RestoreDestinationConfig::RESTORE_MODE_PREFIXED_PATH%>': {
								ret = '<%[ Prefixed path ]%>';
								break;
							}
						}
						return ret;
					}
				},
				{data: 'restore_path', render: render_text},
				{
					data: 'capability_sets',
					render: render_string_short,
					visible: false
				},
				{
					data: 'used_by',
					render: render_string_short
				},
				{
					data: 'enabled',
					render: function(data, type, row) {
						var ret;
						if (type == 'display') {
							ret = '';
							if (data == 1) {
								var check = document.createElement('I');
								check.className = 'fas fa-check';
								ret = check.outerHTML;
							}
						} else {
							ret = data;
						}
						return ret;
					}
				},
				{
					data: 'name',
					render: (data, type, row) => {
						const id = 'name';
						const tt_obj = oTagTools_<%=$this->TagToolsRestoreDestinationList->ClientID%>;
						const table = oRestoreDestinationList;
						return render_tags(type, id, data, tt_obj, table);
					}
				},
				{
					data: 'name',
					render: function (data, type, row) {
						// Edit button
						const btn_edit = document.createElement('BUTTON');
						btn_edit.className = 'w3-button w3-green';
						btn_edit.type = 'button';
						const i_edit = document.createElement('I');
						i_edit.className = 'fa fa-edit';
						const label_edit = document.createTextNode(' <%[ Edit ]%>');
						btn_edit.appendChild(i_edit);
						btn_edit.innerHTML += '&nbsp';
						btn_edit.style.marginRight = '8px';
						btn_edit.appendChild(label_edit);
						btn_edit.addEventListener('click', () => {
							oRestoreDestinations.load_restore_destination_window(data);
						});
						return btn_edit;
					}
				}
			],
			responsive: {
				details: {
					type: 'column',
					display: DataTable.Responsive.display.childRow
				}
			},
			columnDefs: [{
				className: 'dtr-control-custom',
				orderable: false,
				targets: 0
			},
			{
				className: 'action_col_long',
				orderable: false,
				targets: [ 9 ]
			},
			{
				className: "dt-center",
				targets: [ 2, 3, 5, 6, 7, 8 ]
			}],
			select: {
				style:    'os',
				selector: 'td:not(:last-child):not(:first-child)',
				blurable: false
			},
			order: [1, 'asc'],
			initComplete: function () {
				oRestoreDestinationList.set_filters(this.api());
			}
		});
	},
	set_filters: function(api) {
		api.columns([1, 2, 3, 7]).every(function () {
			const column = this;
			const select = $('<select class="dt-select"><option value=""></option></select>')
			.appendTo($(column.footer()).empty())
			.on('change', function () {
				const val = dtEscapeRegex(
					$(this).val()
				);
				column
				.search(val ? '^' + val + '$' : '', true, false)
				.draw();
			});
			if ([7].indexOf(column[0][0]) != -1) { // Enabled column
				column.data().unique().sort().each(function (d, j) {
					var ds = d;
					if (column[0][0] == 7) { // Enabled column
						if (d === '1') {
							ds = '<%[ Enabled ]%>';
						} else if (d === '0') {
							ds = '<%[ Disabled ]%>';
						}
					}
					if (column.search() == '^' + dtEscapeRegex(d) + '$' || ds) {
						const option = document.createElement('OPTION');
						option.value = d;
						option.textContent = ds;
						option.title = ds;
						option.selected = column.search() == '^' + dtEscapeRegex(d) + '$';
						select.append(option);
					}
				});
			} else {
				column.cells('', column[0]).render('display').unique().sort().each(function(d, j) {
					if (column.search() == '^' + dtEscapeRegex(d) + '$' || d) {
						const option = document.createElement('OPTION');
						option.value = d;
						option.textContent = d;
						option.selected = column.search() == '^' + dtEscapeRegex(d) + '$';
						select.append(option);
					}
				});
			}
		});
	},
	set_bulk_actions: function() {
		this.table_toolbar = get_table_toolbar(this.table, this.actions, {
			actions: '<%[ Select action ]%>',
			ok: '<%[ OK ]%>'
		});
	}
};

var oRestoreDestinations = {
	ids: {
		restore_destination_win: 'restore_destination_window',
		win_type: '<%=$this->RestoreDestinationWindowType->ClientID%>',
		destination_name: '<%=$this->RestoreDestinationFullName->ClientID%>'
	},
	load_restore_destination_window: function(name) {
		let title_add = document.getElementById('restore_destination_window_title_add');
		let title_edit = document.getElementById('restore_destination_window_title_edit');
		let restore_destination_win_type = document.getElementById(this.ids.win_type);
		let restore_destination_name = document.getElementById(this.ids.destination_name);
		const cb = <%=$this->LoadRestoreDestination->ActiveControl->Javascript%>;
		cb.setCallbackParameter(name);
		cb.dispatch();
		if (name) {
			// edit existing restore_destination
			title_add.style.display = 'none';
			title_edit.style.display = 'inline-block';
			restore_destination_win_type.value = 'edit';
			restore_destination_name.setAttribute('readonly', '');
		} else {
			// add new restore_destination
			title_add.style.display = 'inline-block';
			title_edit.style.display = 'none';
			restore_destination_win_type.value = 'add';
			restore_destination_name.removeAttribute('readonly');
			this.clear_restore_destination_window();
		}
		const restore_destination_win = document.getElementById(this.ids.restore_destination_win);
		restore_destination_win.style.display = 'block';
		if (!name) {
			restore_destination_name.focus();
		}
	},
	load_restore_destination_list: function() {
		const cb = <%=$this->RestoreDestinationList->ActiveControl->Javascript%>;
		cb.dispatch();
	},
	load_restore_destination_list_cb: function(list) {
		oRestoreDestinationList.data = list;
		oRestoreDestinationList.init();
	},
	clear_restore_destination_window: function() {
		// clear inputs and selects
		[
			'<%=$this->RestoreDestinationFullName->ClientID%>',
			'<%=$this->RestoreDestinationDescription->ClientID%>',
			'<%=$this->RestoreDestinationRestoreTargetClient->ClientID%>'
		].forEach(function(id) {
			document.getElementById(id).value = '';
		});

		document.getElementById('<%=$this->RestoreDestinationRestoreModeSafePrefixed->ClientID%>').value = '/tmp/bacularis-restore-tests/{job_name}/{date}/';
		document.getElementById('<%=$this->RestoreDestinationRestoreModeOriginalPath->ClientID%>').value = '/';

		// clear checkboxes and radio buttons
		[
			'<%=$this->RestoreDestinationEnabled->ClientID%>',
			'<%=$this->RestoreDestinationRestoreModeSafePrefixedRadio->ClientID%>'
		].forEach(function(id) {
			document.getElementById(id).checked = true;
		});

		[
			'<%=$this->RestoreDestinationRestoreModeOriginalPathRadio->ClientID%>',
			'<%=$this->RestoreDestinationRestoreModeOriginalPathConfirm->ClientID%>',
			'<%=$this->RestoreDestinationCapabilitiesFile->ClientID%>',
			'<%=$this->RestoreDestinationCapabilitiesBaculaVerify->ClientID%>',
			'<%=$this->RestoreDestinationCapabilitiesPlugin->ClientID%>',
			'<%=$this->RestoreDestinationCapabilitiesCustom->ClientID%>'
		].forEach(function(id) {
			document.getElementById(id).checked = false;
		});

		document.getElementById('restore_destination_window_restore_mode_safe_prefixed').style.display = 'block';
		document.getElementById('restore_destination_window_restore_mode_original_path').style.display = 'none';
	},
	save_restore_destination_cb: function() {
		const self = oRestoreDestinations;
		const restore_destination_win = document.getElementById(self.ids.restore_destination_win);
		restore_destination_win.style.display = 'none';
		self.load_restore_destination_list();
	}
}

$(function() {
	oRestoreDestinations.load_restore_destination_list();
});
	</script>
</div>
<div id="restore_destination_window" class="w3-modal">
	<div class="w3-modal-content w3-animate-top w3-card-4">
		<header class="w3-container w3-green">
			<span onclick="document.getElementById('restore_destination_window').style.display = 'none';" class="w3-button w3-display-topright">&times;</span>
			<h2 id="restore_destination_window_title_add" style="display: none"><%[ Add restore destination ]%></h2>
			<h2 id="restore_destination_window_title_edit" style="display: none"><%[ Edit restore destination ]%></h2>
		</header>
		<div class="w3-container w3-margin-left w3-margin-right w3-margin-top">
			<com:TActiveLabel ID="RestoreDestinationWindowError" CssClass="error" Display="None" />
			<div class="w3-row directive_field">
				<div class="w3-col w3-third"><label for="<%=$this->RestoreDestinationFullName->ClientID%>"><%[ Restore destination name ]%>:</label></div>
				<div class="w3-col w3-twothird">
					<com:TActiveTextBox
						ID="RestoreDestinationFullName"
						AutoPostBack="false"
						MaxLength="160"
						CssClass="w3-input w3-border w3-show-inline-block"
						Attributes.placeholder="ex: My restore destination"
					/>
					<i class="fas fa-asterisk w3-text-red opt_req"></i>
					<com:TRequiredFieldValidator
						ValidationGroup="RestoreDestinationGroup"
						ControlToValidate="RestoreDestinationFullName"
						ErrorMessage="<%[ Field required. ]%>"
						ControlCssClass="field_invalid"
						Display="Dynamic"
					/>
					<com:TRegularExpressionValidator
						ValidationGroup="RestoreDestinationGroup"
						RegularExpression="<%=RestoreDestinationConfig::NAME_PATTERN%>"
						ControlToValidate="RestoreDestinationFullName"
						ErrorMessage="<%[ Invalid value. ]%>"
						ControlCssClass="field_invalid"
						Display="Dynamic"
					/>
				</div>
			</div>
			<div class="w3-row directive_field">
				<div class="w3-col w3-third"><label for="<%=$this->RestoreDestinationDescription->ClientID%>"><%[ Description ]%>:</label></div>
				<div class="w3-col w3-twothird">
					<com:TActiveTextBox
						ID="RestoreDestinationDescription"
						TextMode="MultiLine"
						Rows="3"
						AutoPostBack="false"
						MaxLength="500"
						CssClass="w3-input w3-border"
						Attributes.placeholder="ex: This is D12 server restore destination..."
					/>
				</div>
			</div>
			<div class="w3-row directive_field">
				<div class="w3-col w3-third"><label for="<%=$this->RestoreDestinationEnabled->ClientID%>"><%[ Enabled ]%>:</label></div>
				<div class="w3-twothird">
					<com:TActiveCheckBox
						ID="RestoreDestinationEnabled"
						CssClass="w3-check w3-border"
						AutoPostBack="false"
						Checked="true"
					/>
				</div>
			</div>
			<h4><%[ Restore target client ]%></h4>
			<div id="restore_destination_window_restore_target_client">
				<div class="w3-row directive_field">
					<div class="w3-col w3-third"><label for="<%=$this->RestoreDestinationRestoreTargetClient->ClientID%>" class="w3-margin-left"><%[ Restore client ]%>:</label></div>
					<div class="w3-twothird">
						<com:TActiveDropDownList
							ID="RestoreDestinationRestoreTargetClient"
							CssClass="w3-select w3-border"
							AutoPostBack="false"
						/>
						<i class="fas fa-asterisk w3-text-red opt_req"></i>
						<com:TRequiredFieldValidator
							ValidationGroup="RestoreDestinationGroup"
							ControlToValidate="RestoreDestinationRestoreTargetClient"
							ErrorMessage="<%[ Field required. ]%>"
							ControlCssClass="field_invalid"
							Display="Dynamic"
						/>
					</div>
				</div>
			</div>
			<h4><%[ Restore mode ]%></h4>
			<div class="w3-row directive_field w3-margin-bottom">
				<div class="w3-col w3-third">&nbsp;</div>
				<div class="w3-twothird">
					<com:TActiveRadioButton
						ID="RestoreDestinationRestoreModeSafePrefixedRadio"
						CssClass="w3-check w3-border"
						AutoPostBack="false"
						Checked="true"
						GroupName="RestoreDestinationBackupJob"
						Attributes.onclick="$('#restore_destination_window_restore_mode_original_path').slideUp('fast');$('#restore_destination_window_restore_mode_safe_prefixed').slideDown('fast');"
					/> <label for="<%=$this->RestoreDestinationRestoreModeSafePrefixedRadio->ClientID%>"><%[ Safe prefixed restore ]%></label>
				</div>
			</div>
			<div id="restore_destination_window_restore_mode_safe_prefixed">
				<div class="w3-row directive_field">
					<div class="w3-col w3-third"><label for="<%=$this->RestoreDestinationRestoreModeSafePrefixed->ClientID%>" class="w3-margin-left"><%[ Restore path ]%>:</label></div>
					<div class="w3-col w3-twothird">
						<com:TActiveTextBox
							ID="RestoreDestinationRestoreModeSafePrefixed"
							AutoPostBack="false"
							CssClass="w3-input w3-border w3-show-inline-block"
							Text="/tmp/bacularis-restore-tests/{job_name}/{date}/"
						/>
						<i class="fas fa-asterisk w3-text-red opt_req"></i>
						<i class="fas fa-info-circle help_icon w3-text-green" style="display: inline-block;" onclick="var h = $(this).nextAll('div.directive_help'); var disp = h.get(0).style.display; $('div.directive_help').slideUp('fast'); if (disp == 'none') { h.slideDown('fast'); }"></i>
						<com:TRequiredFieldValidator
							ValidationGroup="RestoreDestinationGroup"
							ControlToValidate="RestoreDestinationRestoreModeSafePrefixed"
							ErrorMessage="<%[ Field required. ]%>"
							ControlCssClass="field_invalid"
							Display="Dynamic"
						>
							<prop:ClientSide.OnValidate>
								const el = document.getElementById('<%=$this->RestoreDestinationRestoreModeSafePrefixedRadio->ClientID%>');
								sender.enabled = el.checked;
							</prop:ClientSide.OnValidate>
						</com:TRequiredFieldValidator>
						<div class="directive_help" style="display: none">
							<p><%[ The following keywords can be used in paths: ]%></p>
							<ul>
								<li><strong>{date}</strong> - <%[ current date, ex: 2026-08-19_00:29:18 ]%></li>
								<li><strong>{test_id}</strong> - <%[ test identifier, ex: rt-OjuKleyqh423W5wHGcr0JARiUVm9EzgN ]%></li>
								<li><strong>{job_name}</strong> - <%[ job name ex: My Job 123 ]%></li>
								<li><strong>{restore_test}</strong> - <%[ restore test name ex: MyRestoreTest ]%></li>
								<li><strong>{restore_job}</strong> - <%[ name of job used for restore ex: RestoreFiles ]%></li>
								<li><strong>{restore_policy}</strong> - <%[ restore policy name ex: DailyCycle ]%></li>
								<li><strong>{restore_destination}</strong> - <%[ restore destination name ex: ServerA3 ]%></li>
							</ul>
						</div>
					</div>
				</div>
			</div>
			<div class="w3-row directive_field w3-margin-bottom">
				<div class="w3-col w3-third">&nbsp;</div>
				<div class="w3-twothird">
					<com:TActiveRadioButton
						ID="RestoreDestinationRestoreModeOriginalPathRadio"
						CssClass="w3-check w3-border"
						AutoPostBack="false"
						GroupName="RestoreDestinationBackupJob"
						Attributes.onclick="$('#restore_destination_window_restore_mode_safe_prefixed').slideUp('fast'); $('#restore_destination_window_restore_mode_original_path').slideDown('fast');"
					/> <label for="<%=$this->RestoreDestinationRestoreModeOriginalPathRadio->ClientID%>"><%[ Original-path restore in isolated environment ]%></label>
				</div>
			</div>
			<div id="restore_destination_window_restore_mode_original_path" style="display: none;">
				<div class="w3-row directive_field">
					<div class="w3-col w3-third"><label for="<%=$this->RestoreDestinationRestoreModeOriginalPath->ClientID%>" class="w3-margin-left"><%[ Restore path ]%>:</label></div>
					<div class="w3-col w3-twothird">
						<com:TActiveTextBox
							ID="RestoreDestinationRestoreModeOriginalPath"
							AutoPostBack="false"
							CssClass="w3-input w3-border w3-show-inline-block"
							Text="/"
							ReadOnly="true"
						/>
					</div>
				</div>
				<div class="w3-row directive_field">
					<div class="w3-col w3-third"><label for="<%=$this->RestoreDestinationRestoreModeOriginalPathConfirm->ClientID%>" class="w3-margin-left">&nbsp;</label></div>
					<div class="w3-col w3-twothird">
						<com:TActiveCheckBox
							ID="RestoreDestinationRestoreModeOriginalPathConfirm"
							AutoPostBack="false"
							CssClass="w3-check w3-border w3-show-inline-block"
						/>
						<i class="fas fa-asterisk w3-text-red opt_req"></i>
						<com:TRequiredFieldValidator
							ValidationGroup="RestoreDestinationGroup"
							ControlToValidate="RestoreDestinationRestoreModeOriginalPathConfirm"
							ErrorMessage="<%[ Field required. ]%>"
							ControlCssClass="field_invalid"
							Display="Dynamic"
						>
							<prop:ClientSide.OnValidate>
								const el = document.getElementById('<%=$this->RestoreDestinationRestoreModeOriginalPathRadio->ClientID%>');
								sender.enabled = el.checked;
							</prop:ClientSide.OnValidate>
						</com:TRequiredFieldValidator>
						&nbsp;<label for="<%=$this->RestoreDestinationRestoreModeOriginalPathConfirm->ClientID%>"><%[ I confirm this is an isolated test environment ]%></label>
					</div>
				</div>
			</div>
			<h4><%[ Capabilities ]%></h4>
			<div id="restore_destination_window_capabilities">
				<div class="w3-row directive_field w3-margin-bottom">
					<div class="w3-col w3-third">&nbsp;</div>
					<div class="w3-twothird">
						<com:TActiveCheckBox
							ID="RestoreDestinationCapabilitiesFile"
							CssClass="w3-check w3-border"
							AutoPostBack="false"
						/> &nbsp;<label for="<%=$this->RestoreDestinationCapabilitiesFile->ClientID%>"><%[ File restore checks ]%></label>
					</div>
				</div>
				<div class="w3-row directive_field w3-margin-bottom">
					<div class="w3-col w3-third">&nbsp;</div>
					<div class="w3-twothird">
						<com:TActiveCheckBox
							ID="RestoreDestinationCapabilitiesBaculaVerify"
							CssClass="w3-check w3-border"
							AutoPostBack="false"
						/> &nbsp;<label for="<%=$this->RestoreDestinationCapabilitiesBaculaVerify->ClientID%>"><%[ Native Bacula verify ]%></label>
					</div>
				</div>
				<div class="w3-row directive_field w3-margin-bottom">
					<div class="w3-col w3-third">&nbsp;</div>
					<div class="w3-twothird">
						<com:TActiveCheckBox
							ID="RestoreDestinationCapabilitiesPlugin"
							CssClass="w3-check w3-border"
							AutoPostBack="false"
						/> &nbsp;<label for="<%=$this->RestoreDestinationCapabilitiesPlugin->ClientID%>"><%[ Plugin checks ]%></label>
					</div>
				</div>
				<div class="w3-row directive_field w3-margin-bottom">
					<div class="w3-col w3-third">&nbsp;</div>
					<div class="w3-twothird">
						<com:TActiveCheckBox
							ID="RestoreDestinationCapabilitiesCustom"
							CssClass="w3-check w3-border"
							AutoPostBack="false"
						/> &nbsp;<label for="<%=$this->RestoreDestinationCapabilitiesCustom->ClientID%>"><%[ Custom command checks ]%></label>
					</div>
				</div>
			</div>
		</div>
		<footer class="w3-container w3-center">
			<button type="button" class="w3-button w3-red" onclick="document.getElementById('restore_destination_window').style.display = 'none';"><i class="fas fa-times"></i> &nbsp;<%[ Cancel ]%></button>
			<com:TActiveLinkButton
				ID="RestoreDestinationSave"
				ValidationGroup="RestoreDestinationGroup"
				CausesValidation="true"
				OnCallback="saveRestoreDestination"
				CssClass="w3-button w3-section w3-green w3-padding"
			>
				<i class="fa fa-save"></i> &nbsp;<%[ Save ]%>
			</com:TActiveLinkButton>
		</footer>
	</div>
	<com:TActiveHiddenField ID="RestoreDestinationWindowType" />
</div>
