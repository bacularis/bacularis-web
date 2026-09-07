<div>
	<p class="w3-hide-small" style="margin-bottom: 0"><%[ Restore policies define when restore tests should run. A policy can run tests after a successful backup, according to a Bacula schedule, or only when started manually. ]%></p>
	<div class="w3-container" style="margin: 10px 0">
		<button type="button" id="add_restore_policy_btn" class="w3-button w3-green" onclick="oRestorePolicies.load_restore_policy_window()"><i class="fa fa-plus"></i> &nbsp;<%[ Add restore policy ]%></button>
	</div>
	<!-- Tag tools -->
	<com:Bacularis.Web.Portlets.TagTools ID="TagToolsRestorePolicyList" ViewName="restore_policy_list" />
	<table id="restore_policy_list_table" class="display w3-table w3-striped w3-hoverable w3-margin-bottom selectable" style="width: 100%;">
		<thead>
			<tr>
				<th></th>
				<th><%[ Name ]%></th>
				<th class="w3-center"><%[ Trigger ]%></th>
				<th class="w3-center"><%[ Used by ]%></th>
				<th class="w3-center"><%[ Enabled ]%></th>
				<th class="w3-center"><%[ Tag ]%></th>
				<th class="w3-center"><%[ Actions ]%></th>
			</tr>
		</thead>
		<tbody id="restore_policy_list_body"></tbody>
		<tfoot>
			<tr>
				<th></th>
				<th><%[ Name ]%></th>
				<th class="w3-center"><%[ Trigger ]%></th>
				<th class="w3-center"><%[ Used by ]%></th>
				<th class="w3-center"><%[ Enabled ]%></th>
				<th class="w3-center"><%[ Tag ]%></th>
				<th class="w3-center"><%[ Actions ]%></th>
			</tr>
		</tfoot>
	</table>
	<p class="info w3-hide-medium w3-hide-small"><%[ Tip: Use left-click to select table row. Use CTRL + left-click to multiple row selection. Use SHIFT + left-click to add a range of rows to selection. ]%></p>
<com:TCallback ID="RestorePolicyList" OnCallback="TemplateControl.setRestorePolicyList" />
<com:TCallback ID="LoadRestorePolicy" OnCallback="TemplateControl.loadRestorePolicyWindow" />
<com:TCallback ID="RemoveRestorePoliciesAction" OnCallback="TemplateControl.removeRestorePolicies" />
<script>
const oRestorePolicyList = {
	ids: {
		restore_policy_list: 'restore_policy_list_table'
	},
	actions: [
		{
			action: 'remove',
			label: '<%[ Remove ]%>',
			value: ['name', 'orgs'],
			callback: <%=$this->RemoveRestorePoliciesAction->ActiveControl->Javascript%>,
			validate: function(selected) {
				const used_restore_policies = {};
				const escape_html = function(value) {
					const div = document.createElement('DIV');
					div.textContent = value;
					return div.innerHTML;
				};
				selected.each(function(v, k) {
					if (!Array.isArray(v.used_by_restore_tests) || v.used_by_restore_tests.length == 0) {
						return;
					}
					if (!used_restore_policies.hasOwnProperty(v.name)) {
						used_restore_policies[v.name] = [];
					}
					for (let i = 0; i < v.used_by_restore_tests.length; i++) {
						const restore_test = v.used_by_restore_tests[i];
						used_restore_policies[v.name].push(restore_test);
					}
				});
				const restore_policies = Object.keys(used_restore_policies);
				if (restore_policies.length == 0) {
					return true;
				}
				const lines = [];
				for (let i = 0; i < restore_policies.length; i++) {
					const restore_policy = restore_policies[i];
					let line = restore_policy + ':';
					for (let j = 0; j < used_restore_policies[restore_policy].length; j++) {
						line += "\n - " + used_restore_policies[restore_policy][j];
					}
					lines.push(line);
				}
				const msg = '<%[ The following restore policies are used by restore tests and cannot be removed: %used_restore_policies Please unassign these restore policies from the restore tests and try removing them again. ]%>';
				const emsg = msg.replace('%used_restore_policies', "\n\n" + lines.join("\n\n") + "\n\n");
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
			oRestorePolicyList.set_filters(this.table);
			this.table_toolbar.style.display = 'none';
		}
	},
	set_events: function() {
		document.getElementById(this.ids.restore_policy_list).addEventListener('click', function(e) {
			$(function() {
				const wa = (this.table.rows({selected: true}).data().length > 0) ? 'show' : 'hide';
				$(this.table_toolbar).animate({
					width: wa
				}, 'fast');
			}.bind(this));
		}.bind(this));
	},
	set_table: function() {
		this.table = $('#' + this.ids.restore_policy_list).DataTable({
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
				{data: 'name'},
				{
					data: 'run_method',
					render: (data, type, row) => {
						let ret = 'Unknown';
						switch (data) {
							case '<%=RestorePolicyConfig::RUN_METHOD_SCHEDULE%>': {
								ret = '<%[ Schedule ]%>';
								break;
							}
							case '<%=RestorePolicyConfig::RUN_METHOD_AFTER_BACKUP%>': {
								ret = '<%[ After backup ]%>';
								break;
							}
							case '<%=RestorePolicyConfig::RUN_METHOD_MANUALLY%>': {
								ret = '<%[ Manually ]%>';
								break;
							}
						}
						return ret;
					}
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
						const tt_obj = oTagTools_<%=$this->TagToolsRestorePolicyList->ClientID%>;
						const table = 'oRestorePolicyList.table';
						return render_tags(type, id, data, tt_obj, table);
					}
				},
				{
					data: 'name',
					render: function (data, type, row) {
						let btns = '';

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
						btn_edit.setAttribute('onclick', 'oRestorePolicies.load_restore_policy_window(\'' + data + '\')');
						btns += btn_edit.outerHTML;

						return btns;
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
				targets: [ 6 ]
			},
			{
				className: "dt-center",
				targets: [ 2, 4, 5 ]
			}],
			select: {
				style:    'os',
				selector: 'td:not(:last-child):not(:first-child)',
				blurable: false
			},
			order: [1, 'asc'],
			initComplete: function () {
				oRestorePolicyList.set_filters(this.api());
			}
		});
	},
	set_filters: function(api) {
		api.columns([1, 2, 4]).every(function () {
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
			if ([4].indexOf(column[0][0]) != -1) { // Enabled column
				column.data().unique().sort().each(function (d, j) {
					var ds = d;
					if (column[0][0] == 4) { // Enabled column
						if (d === '1') {
							ds = '<%[ Enabled ]%>';
						} else if (d === '0') {
							ds = '<%[ Disabled ]%>';
						}
					}
					if (column.search() == '^' + dtEscapeRegex(d) + '$') {
						select.append('<option value="' + d + '" title="' + ds + '" selected>' + ds + '</option>');
					} else if (ds) {
						select.append('<option value="' + d + '" title="' + ds + '">' + ds + '</option>');
					}
				});
			} else {
				column.cells('', column[0]).render('display').unique().sort().each(function(d, j) {
					if (column.search() == '^' + dtEscapeRegex(d) + '$') {
						select.append('<option value="' + d + '" selected>' + d + '</option>');
					} else if(d) {
						select.append('<option value="' + d + '">' + d + '</option>');
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

window.oRestorePolicies = {
	ids: {
		win: 'restore_policy_window',
		win_title_add: 'restore_policy_window_title_add',
		win_title_edit: 'restore_policy_window_title_edit',
		win_type: '<%=$this->RestorePolicyWindowType->ClientID%>',
		policy_name: '<%=$this->RestorePolicyFullName->ClientID%>'
	},
	load_restore_policy_window: function(name) {
		let title_add = document.getElementById(this.ids.win_title_add);
		let title_edit = document.getElementById(this.ids.win_title_edit);
		let restore_policy_win_type = document.getElementById(this.ids.win_type);
		let restore_policy_name = document.getElementById(this.ids.policy_name);
		const cb = <%=$this->LoadRestorePolicy->ActiveControl->Javascript%>;
		cb.setCallbackParameter(name);
		cb.dispatch();
		if (name) {
			// edit existing restore_policy
			title_add.style.display = 'none';
			title_edit.style.display = 'inline-block';
			restore_policy_win_type.value = 'edit';
			restore_policy_name.setAttribute('readonly', '');
		} else {
			// add new restore_policy
			title_add.style.display = 'inline-block';
			title_edit.style.display = 'none';
			restore_policy_win_type.value = 'add';
			restore_policy_name.removeAttribute('readonly');
			this.clear_restore_policy_window();
		}
		const restore_policy_win = document.getElementById(this.ids.win);
		restore_policy_win.style.display = 'block';
		if (!name) {
			restore_policy_name.focus();
		}
	},
	load_restore_policy_list: function() {
		const cb = <%=$this->RestorePolicyList->ActiveControl->Javascript%>;
		cb.dispatch();
	},
	load_restore_policy_list_cb: function(list) {
		oRestorePolicyList.data = list;
		oRestorePolicyList.init();
	},
	clear_restore_policy_window: function() {
		// clear inputs and selects
		[
			'<%=$this->RestorePolicyFullName->ClientID%>',
			'<%=$this->RestorePolicyDescription->ClientID%>',
			'<%=$this->RestorePolicyHowToRunSchedule->ClientID%>'
		].forEach(function(id) {
			document.getElementById(id).value = '';
		});

		// clear checkboxes and radio buttons
		[
			'<%=$this->RestorePolicyEnabled->ClientID%>',
			'<%=$this->RestorePolicyHowToRunScheduleRadio->ClientID%>'
		].forEach(function(id) {
			document.getElementById(id).checked = true;
		});

		[
			'<%=$this->RestorePolicyHowToRunAfterSuccessfulBackupRadio->ClientID%>',
			'<%=$this->RestorePolicyHowToRunManuallyRadio->ClientID%>'
		].forEach(function(id) {
			document.getElementById(id).checked = false;
		});

		const levels = document.getElementById('<%=$this->RestorePolicyHowToRunSuccessfulBackupJobLevels->ClientID%>').options;
		Array.from(levels).forEach(function(option) {
			option.selected = (option.value === 'Full');
		});
		document.getElementById('restore_policy_window_how_to_run_schedule').style.display = 'block';
		document.getElementById('restore_policy_window_how_to_run_successful_backup').style.display = 'none';
	},
	save_restore_policy_cb: function() {
		const self = oRestorePolicies;
		const restore_policy_win = document.getElementById(self.ids.win);
		restore_policy_win.style.display = 'none';
		self.load_restore_policy_list();
	}
}

$(function() {
	oRestorePolicies.load_restore_policy_list();
});
	</script>
</div>
<div id="restore_policy_window" class="w3-modal">
	<div class="w3-modal-content w3-animate-top w3-card-4">
		<header class="w3-container w3-green">
			<span onclick="document.getElementById('restore_policy_window').style.display = 'none';" class="w3-button w3-display-topright">&times;</span>
			<h2 id="restore_policy_window_title_add" style="display: none"><%[ Add restore policy ]%></h2>
			<h2 id="restore_policy_window_title_edit" style="display: none"><%[ Edit restore policy ]%></h2>
		</header>
		<div class="w3-container w3-margin-left w3-margin-right w3-margin-top">
			<com:TActiveLabel ID="RestorePolicyWindowError" CssClass="error" Display="None" />
			<div class="w3-row directive_field">
				<div class="w3-col w3-third"><label for="<%=$this->RestorePolicyFullName->ClientID%>"><%[ Restore policy name ]%>:</label></div>
				<div class="w3-col w3-twothird">
					<com:TActiveTextBox
						ID="RestorePolicyFullName"
						AutoPostBack="false"
						MaxLength="160"
						CssClass="w3-input w3-border w3-show-inline-block"
						Attributes.placeholder="ex: My restore policy"
					/>
					<i class="fas fa-asterisk w3-text-red opt_req"></i>
					<com:TRequiredFieldValidator
						ValidationGroup="RestorePolicyGroup"
						ControlToValidate="RestorePolicyFullName"
						ErrorMessage="<%[ Field required. ]%>"
						ControlCssClass="field_invalid"
						Display="Dynamic"
					/>
					<com:TRegularExpressionValidator
						ValidationGroup="RestorePolicyGroup"
						RegularExpression="<%=RestorePolicyConfig::NAME_PATTERN%>"
						ControlToValidate="RestorePolicyFullName"
						ErrorMessage="<%[ Invalid value. ]%>"
						ControlCssClass="field_invalid"
						Display="Dynamic"
					/>
				</div>
			</div>
			<div class="w3-row directive_field">
				<div class="w3-col w3-third"><label for="<%=$this->RestorePolicyDescription->ClientID%>"><%[ Description ]%>:</label></div>
				<div class="w3-col w3-twothird">
					<com:TActiveTextBox
						ID="RestorePolicyDescription"
						TextMode="MultiLine"
						Rows="3"
						AutoPostBack="false"
						MaxLength="500"
						CssClass="w3-input w3-border"
						Attributes.placeholder="ex: This is D12 server restore policy..."
					/>
				</div>
			</div>
			<div class="w3-row directive_field">
				<div class="w3-col w3-third"><label for="<%=$this->RestorePolicyEnabled->ClientID%>"><%[ Enabled ]%>:</label></div>
				<div class="w3-twothird">
					<com:TActiveCheckBox
						ID="RestorePolicyEnabled"
						CssClass="w3-check w3-border"
						AutoPostBack="false"
						Checked="true"
					/>
				</div>
			</div>
			<h4><%[ How to run ]%></h4>
			<p><%[ Choose how restore tests using this policy should be started. They can run according to a Bacula schedule, automatically after successful backup jobs, or manually. ]%></p>
			<div class="w3-row directive_field w3-margin-bottom">
				<div class="w3-col w3-third">&nbsp;</div>
				<div class="w3-twothird">
					<com:TActiveRadioButton
						ID="RestorePolicyHowToRunScheduleRadio"
						CssClass="w3-check w3-border"
						AutoPostBack="false"
						Checked="true"
						GroupName="RestorePolicyBackupJob"
						Attributes.onclick=" $('#restore_policy_window_how_to_run_successful_backup').slideUp('fast'); $('#restore_policy_window_how_to_run_schedule').slideDown('fast');"
					/> <label for="<%=$this->RestorePolicyHowToRunScheduleRadio->ClientID%>"><%[ Use Bacula schedule ]%></label>
				</div>
			</div>
			<div id="restore_policy_window_how_to_run_schedule" style="margin-left: 92px">
				<div class="w3-row directive_field">
					<div class="w3-col w3-quarter"><label for="<%=$this->RestorePolicyHowToRunSchedule->ClientID%>" class="w3-margin-left"><%[ Select schedule ]%>:</label></div>
					<div class="w3-threequarter">
						<com:TActiveDropDownList
							ID="RestorePolicyHowToRunSchedule"
							CssClass="w3-select w3-border"
							AutoPostBack="false"
						/>
						<i class="fas fa-asterisk w3-text-red opt_req"></i>
						<com:TRequiredFieldValidator
							ValidationGroup="RestorePolicyGroup"
							ControlToValidate="RestorePolicyHowToRunSchedule"
							ErrorMessage="<%[ Field required. ]%>"
							ControlCssClass="field_invalid"
							Display="Dynamic"
						>
							<prop:ClientSide.OnValidate>
								const el = document.getElementById('<%=$this->RestorePolicyHowToRunScheduleRadio->ClientID%>');
								sender.enabled = el.checked;
							</prop:ClientSide.OnValidate>
						</com:TRequiredFieldValidator>
					</div>
				</div>
			</div>
			<div class="w3-row directive_field w3-margin-bottom">
				<div class="w3-col w3-third">&nbsp;</div>
				<div class="w3-twothird">
					<com:TActiveRadioButton
						ID="RestorePolicyHowToRunAfterSuccessfulBackupRadio"
						CssClass="w3-check w3-border"
						AutoPostBack="false"
						GroupName="RestorePolicyBackupJob"
						Attributes.onclick="$('#restore_policy_window_how_to_run_schedule').slideUp('fast'); $('#restore_policy_window_how_to_run_successful_backup').slideDown('fast');"
					/> <label for="<%=$this->RestorePolicyHowToRunAfterSuccessfulBackupRadio->ClientID%>"><%[ After successful backup job ]%></label>
					<p class="w3-small"><%[ Modifies backup jobs used by restore tests with this policy by adding a RunScript entry that starts the test after a successful backup. ]%></p>
				</div>
			</div>
			<div id="restore_policy_window_how_to_run_successful_backup" style="display: none; margin-left: 92px;">
				<div class="w3-row directive_field">
					<div class="w3-col w3-quarter"><label for="<%=$this->RestorePolicyHowToRunSuccessfulBackupJobLevels->ClientID%>"><%[ Run after levels ]%>:</label></div>
					<div class="w3-col w3-threequarter">
						<com:TActiveListBox
							ID="RestorePolicyHowToRunSuccessfulBackupJobLevels"
							CssClass="w3-select w3-border"
							AutoPostBack="false"
							SelectionMode="Multiple"
							Rows="3"
						>
							<com:TListItem Value="F" Text="Full" Selected="true" />
							<com:TListItem Value="I" Text="Incremental" />
							<com:TListItem Value="D" Text="Differential" />
						</com:TActiveListBox>
						<i class="fas fa-asterisk w3-text-red opt_req"></i>
						<com:TRequiredFieldValidator
							ValidationGroup="RestorePolicyGroup"
							ControlToValidate="RestorePolicyHowToRunSuccessfulBackupJobLevels"
							ErrorMessage="<%[ Field required. ]%>"
							ControlCssClass="field_invalid"
							Display="Dynamic"
						>
							<prop:ClientSide.OnValidate>
								const el = document.getElementById('<%=$this->RestorePolicyHowToRunAfterSuccessfulBackupRadio->ClientID%>');
								sender.enabled = el.checked;
							</prop:ClientSide.OnValidate>
						</com:TRequiredFieldValidator>
						<p style="margin-top: 0;"><%[ Use CTRL + left-click to multiple item selection ]%></p>
					</div>
				</div>
			</div>
			<div class="w3-row directive_field w3-margin-bottom">
				<div class="w3-col w3-third">&nbsp;</div>
				<div class="w3-twothird">
					<com:TActiveRadioButton
						ID="RestorePolicyHowToRunManuallyRadio"
						CssClass="w3-check w3-border"
						AutoPostBack="false"
						GroupName="RestorePolicyBackupJob"
						Attributes.onclick="$('#restore_policy_window_how_to_run_successful_backup').slideUp('fast'); $('#restore_policy_window_how_to_run_schedule').slideUp('fast');"
					/> <label for="<%=$this->RestorePolicyHowToRunManuallyRadio->ClientID%>"><%[ Manually ]%></label>
				</div>
			</div>
		</div>
		<footer class="w3-container w3-center">
			<button type="button" class="w3-button w3-red" onclick="document.getElementById('restore_policy_window').style.display = 'none';"><i class="fas fa-times"></i> &nbsp;<%[ Cancel ]%></button>
			<com:TActiveLinkButton
				ID="RestorePolicySave"
				ValidationGroup="RestorePolicyGroup"
				CausesValidation="true"
				OnCallback="saveRestorePolicy"
				CssClass="w3-button w3-section w3-green w3-padding"
			>
				<i class="fa fa-save"></i> &nbsp;<%[ Save ]%>
			</com:TActiveLinkButton>
		</footer>
	</div>
	<com:TActiveHiddenField ID="RestorePolicyWindowType" />
</div>
