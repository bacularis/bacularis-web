<div>
	<p class="w3-hide-small" style="margin-bottom: 0"><%[ Verification rules define what should be checked after restore and which conditions restored files must meet. Rules can use custom values, operators, and metadata from the Bacula catalog. ]%></p>
	<div class="w3-container" style="margin: 10px 0">
		<button type="button" id="add_verification_rule_btn" class="w3-button w3-green" onclick="oVerificationRules.load_verification_rule_window()"><i class="fa fa-plus"></i> &nbsp;<%[ Add verification rule ]%></button>
	</div>
	<!-- Tag tools -->
	<com:Bacularis.Web.Portlets.TagTools ID="TagToolsVerificationRuleList" ViewName="verification_rule_list" />
	<table id="verification_rule_list_table" class="display w3-table w3-striped w3-hoverable w3-margin-bottom selectable" style="width: 100%;">
		<thead>
			<tr>
				<th></th>
				<th><%[ Name ]%></th>
				<th class="w3-center"><%[ Checkers ]%></th>
				<th class="w3-center"><%[ Used by ]%></th>
				<th class="w3-center"><%[ Enabled ]%></th>
				<th class="w3-center"><%[ Tag ]%></th>
				<th class="w3-center"><%[ Actions ]%></th>
			</tr>
		</thead>
		<tbody id="verification_rule_list_body"></tbody>
		<tfoot>
			<tr>
				<th></th>
				<th><%[ Name ]%></th>
				<th class="w3-center"><%[ Checkers ]%></th>
				<th class="w3-center"><%[ Used by ]%></th>
				<th class="w3-center"><%[ Enabled ]%></th>
				<th class="w3-center"><%[ Tag ]%></th>
				<th class="w3-center"><%[ Actions ]%></th>
			</tr>
		</tfoot>
	</table>
	<p class="info w3-hide-medium w3-hide-small"><%[ Tip: Use left-click to select table row. Use CTRL + left-click to multiple row selection. Use SHIFT + left-click to add a range of rows to selection. ]%></p>
<com:TCallback ID="VerificationRuleList" OnCallback="TemplateControl.setVerificationRuleList" />
<com:TCallback ID="LoadVerificationRule" OnCallback="TemplateControl.loadVerificationRuleWindow" />
<com:TCallback ID="RemoveVerificationRulesAction" OnCallback="TemplateControl.removeVerificationRules" />
<com:TCallback ID="SaveVerificationRule" OnCallback="TemplateControl.saveVerificationRule" />
<script>
var oVerificationRuleList = {
	ids: {
		verification_rule_list: 'verification_rule_list_table'
	},
	actions: [
		{
			action: 'remove',
			label: '<%[ Remove ]%>',
			value: ['name', 'orgs'],
			callback: <%=$this->RemoveVerificationRulesAction->ActiveControl->Javascript%>,
			validate: function(selected) {
				const used_rules = {};
				const escape_html = function(value) {
					const div = document.createElement('DIV');
					div.textContent = value;
					return div.innerHTML;
				};
				selected.each(function(v, k) {
					if (!Array.isArray(v.used_by_restore_tests) || v.used_by_restore_tests.length == 0) {
						return;
					}
					if (!used_rules.hasOwnProperty(v.name)) {
						used_rules[v.name] = [];
					}
					for (let i = 0; i < v.used_by_restore_tests.length; i++) {
						const restore_test = v.used_by_restore_tests[i];
						used_rules[v.name].push(restore_test);
					}
				});
				const rules = Object.keys(used_rules);
				if (rules.length == 0) {
					return true;
				}
				const lines = [];
				for (let i = 0; i < rules.length; i++) {
					const rule = rules[i];
					let line = rule + ':';
					for (let j = 0; j < used_rules[rule].length; j++) {
						line += "\n - " + used_rules[rule][j];
					}
					lines.push(line);
				}
				const msg = '<%[ The following verification rules are used by restore tests and cannot be removed: %used_rules Please unassign these verification rules from the restore tests and try removing them again. ]%>';
				const emsg = msg.replace('%used_rules', "\n\n" + lines.join("\n\n") + "\n\n");
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
			oVerificationRuleList.set_filters(this.table);
			this.table_toolbar.style.display = 'none';
		}
	},
	set_events: function() {
		document.getElementById(this.ids.verification_rule_list).addEventListener('click', function(e) {
			$(function() {
				const wa = (this.table.rows({selected: true}).data().length > 0) ? 'show' : 'hide';
				$(this.table_toolbar).animate({
					width: wa
				}, 'fast');
			}.bind(this));
		}.bind(this));
	},
	set_table: function() {
		this.table = $('#' + this.ids.verification_rule_list).DataTable({
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
					data: 'checkers',
					render: (data, type, row) => {
						const data_str = data.join(',');
						return render_string_short(data_str, type, row);
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
						const tt_obj = oTagTools_<%=$this->TagToolsVerificationRuleList->ClientID%>;
						const table = 'oVerificationRuleList.table';
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
						btn_edit.setAttribute('onclick', 'oVerificationRules.load_verification_rule_window(\'' + data + '\')');
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
				targets: [ 4, 5 ]
			}],
			select: {
				style:    'os',
				selector: 'td:not(:last-child):not(:first-child)',
				blurable: false
			},
			order: [1, 'asc'],
			initComplete: function () {
				oVerificationRuleList.set_filters(this.api());
			}
		});
	},
	set_filters: function(api) {
		api.columns([1, 4]).every(function () {
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
			if ([4].indexOf(column[0][0]) != -1) { // Enabled columns
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

var oVerificationRules = {
	ids: {
		win: 'verification_rule_window',
		rule_name: '<%=$this->VerificationRuleFullName->ClientID%>'
	},
	load_verification_rule_list: function() {
		const cb = <%=$this->VerificationRuleList->ActiveControl->Javascript%>;
		cb.dispatch();
	},
	load_verification_rule_list_cb: function(list) {
		oVerificationRuleList.data = list;
		oVerificationRuleList.init();
	},
	load_verification_rule_window: function(name) {
		let title_add = document.getElementById('verification_rule_window_title_add');
		let title_edit = document.getElementById('verification_rule_window_title_edit');
		let verification_rule_win_type = document.getElementById('<%=$this->VerificationRuleWindowType->ClientID%>');
		let verification_rule_name = document.getElementById(this.ids.rule_name);
		const cb = <%=$this->LoadVerificationRule->ActiveControl->Javascript%>;
		cb.setCallbackParameter(name);
		cb.dispatch();
		if (name) {
			// edit existing verification_rule
			title_add.style.display = 'none';
			title_edit.style.display = 'inline-block';
			verification_rule_win_type.value = 'edit';
			verification_rule_name.setAttribute('readonly', '');
		} else {
			// add new verification_rule
			title_add.style.display = 'inline-block';
			title_edit.style.display = 'none';
			verification_rule_win_type.value = 'add';
			verification_rule_name.removeAttribute('readonly');
			this.clear_verification_rule_window();
		}
		const verification_rule_win = document.getElementById(this.ids.win);
		verification_rule_win.style.display = 'block';
		if (!name) {
			verification_rule_name.focus();
		}
	},
	load_verification_rule_window_cb: function(rules) {
		oVerificationRulePathList.load(rules);
	},
	clear_verification_rule_window: function() {
		// clear inputs and selects
		[
			'<%=$this->VerificationRuleFullName->ClientID%>',
			'<%=$this->VerificationRuleDescription->ClientID%>'
		].forEach(function(id) {
			document.getElementById(id).value = '';
		});

		// clear ruleboxes and radio buttons
		[
			'<%=$this->VerificationRuleEnabled->ClientID%>',
	].forEach(function(id) {
			document.getElementById(id).checked = true;
		});
	},
	save_verification_rule: function() {
		const rules = oVerificationRulePathList.get_values();
		const cb = <%=$this->SaveVerificationRule->ActiveControl->Javascript%>;
		cb.setCallbackParameter({rules: rules});
		cb.dispatch();
	},
	save_verification_rule_cb: function() {
		const self = oVerificationRules;
		const verification_rule_win = document.getElementById(self.ids.win);
		verification_rule_win.style.display = 'none';
	}
}

$(function() {
	oVerificationRules.load_verification_rule_list();
});
	</script>
</div>
<div id="verification_rule_window" class="w3-modal">
	<div class="w3-modal-content w3-animate-top w3-card-4" style="width: 1200px">
		<header class="w3-container w3-green">
			<span onclick="document.getElementById('verification_rule_window').style.display = 'none';" class="w3-button w3-display-topright">&times;</span>
			<h2 id="verification_rule_window_title_add" style="display: none"><%[ Add verification rule ]%></h2>
			<h2 id="verification_rule_window_title_edit" style="display: none"><%[ Edit verification rule ]%></h2>
		</header>
		<div class="w3-container w3-margin-left w3-margin-right w3-margin-top">
			<com:TActiveLabel ID="VerificationRuleWindowError" CSsClass="error" Display="None" />
			<div class="w3-row directive_field">
				<div class="w3-col w3-third"><label for="<%=$this->VerificationRuleFullName->ClientID%>"><%[ Verification rule name ]%>:</label></div>
				<div class="w3-col w3-twothird">
					<com:TActiveTextBox
						ID="VerificationRuleFullName"
						AutoPostBack="false"
						MaxLength="160"
						CssClass="w3-input w3-border w3-show-inline-block"
						Attributes.placeholder="ex: My verification rule"
					/>
					<i class="fas fa-asterisk w3-text-red opt_req"></i>
					<com:TRequiredFieldValidator
						ValidationGroup="VerificationRuleGroup"
						ControlToValidate="VerificationRuleFullName"
						ErrorMessage="<%[ Field required. ]%>"
						ControlCssClass="field_invalid"
						Display="Dynamic"
					/>
					<com:TRegularExpressionValidator
						ValidationGroup="VerificationRuleGroup"
						RegularExpression="<%=VerificationRuleConfig::NAME_PATTERN%>"
						ControlToValidate="VerificationRuleFullName"
						ErrorMessage="<%[ Invalid value. ]%>"
						ControlCssClass="field_invalid"
						Display="Dynamic"
					/>
				</div>
			</div>
			<div class="w3-row directive_field">
				<div class="w3-col w3-third"><label for="<%=$this->VerificationRuleDescription->ClientID%>"><%[ Description ]%>:</label></div>
				<div class="w3-col w3-twothird">
					<com:TActiveTextBox
						ID="VerificationRuleDescription"
						TextMode="MultiLine"
						Rows="3"
						AutoPostBack="false"
						MaxLength="500"
						CssClass="w3-input w3-border"
						Attributes.placeholder="ex: This is D12 server verification rule..."
					/>
				</div>
			</div>
			<div class="w3-row directive_field">
				<div class="w3-col w3-third"><label for="<%=$this->VerificationRuleEnabled->ClientID%>"><%[ Enabled ]%>:</label></div>
				<div class="w3-twothird">
					<com:TActiveCheckBox
						ID="VerificationRuleEnabled"
						CssClass="w3-check w3-border"
						AutoPostBack="false"
						Checked="true"
					/>
				</div>
			</div>
			<h4><%[ Rules ]%></h4>
			<div id="verification_rule_window_apply_rules" class="w3-margin-bottom" style="display: none">
				<div class="w3-row directive_field">
					<div class="w3-col w3-right-align w3-margin-right bold" style="width: 220px"><label><%[ Apply rules to selected paths ]%>:</label></div>
					<div class="w3-col w3-threequarter">
						<a href="javascript:void(0)" class="raw w3-margin-right" onclick="oVerificationRulePathList.apply_preset('basic');"><i class="fa-solid fa-check"></i> &nbsp;<%[ Basic ]%></a>
						<a href="javascript:void(0)" class="raw w3-margin-right" onclick="oVerificationRulePathList.apply_preset('metadata');"><i class="fa-solid fa-database"></i> &nbsp;<%[ Metadata ]%></a>
						<a href="javascript:void(0)" class="raw w3-margin-right" onclick="oVerificationRulePathList.apply_preset('checksum');"><i class="fa-solid fa-calculator"></i> &nbsp;<%[ Checksum ]%></a>
						<a href="javascript:void(0)" class="raw w3-margin-right" onclick="oVerificationRulePathList.apply_preset('strict');"><i class="fa-solid fa-file-shield"></i> &nbsp;<%[ Strict ]%></a>
						<a href="javascript:void(0)" class="raw w3-margin-right" onclick="const show = $('#verification_rule_window_apply_custom').is(':visible'); oVerificationRulePathList.show_custom_rule(!show);"><i class="fa-solid fa-sliders"></i> &nbsp;<%[ Custom ]%></a>
						<i class="fas fa-info-circle help_icon w3-text-green" style="display: inline-block;" onclick="var h = $(this).nextAll('div.directive_help'); var disp = h.get(0).style.display; $('div.directive_help').slideUp('fast'); if (disp == 'none') { h.slideDown('fast'); }"></i>
						<div class="directive_help w3-padding" style="display: none">
							<dd><strong><%[ Basic ]%>:</strong> <%[ Check that selected files or directories exist and are not empty. ]%></dd>
							<dd><strong><%[ Metadata ]%>:</strong> <%[ Compare restored files with metadata stored in the Bacula catalog. ]%></dd>
							<dd><strong><%[ Checksum ]%>:</strong> <%[ Compare restored file checksums with values stored in the Bacula catalog. ]%></dd>
							<dd><strong><%[ Strict ]%>:</strong> <%[ Compare restored files with Bacula catalog metadata and checksums. ]%></dd>
							<dd><strong><%[ Custom ]%>:</strong> <%[ Choose an attribute, operator and value. ]%></dd>
						</div>
					</div>
				</div>
				<div id="verification_rule_window_apply_custom" class="w3-row directive_field" style="display: none">
					<div  class="w3-col w3-right-align w3-margin-right w3-margin-top bold" style="width: 220px"><%[ Custom rule ]%>:</div>
					<div id="verification_rule_window_apply_custom_fields" class="w3-col" style="width: 660px"></div>
				</div>
				<div id="verification_rule_window_apply_msg" class="w3-panel w3-pale-green w3-leftbar w3-border-green w3-padding" style="display: none"></div>
				<div id="verification_rule_window_apply_error" class="w3-panel w3-pale-yellow w3-leftbar w3-border-orange w3-padding" style="display: none"></div>
			</div>
			<div id="verification_rule_window_rule_path_list" style="display: none;">
				<table class="w3-table w3-striped w3-margin-bottom dataTable dtr-column">
					<thead>
						<tr class="row">
							<th class="w3-center" style="width: 32px; padding: 0 7px 5px 16px;"><input type="checkbox" class="w3-check" onclick="oVerificationRulePathList.toggle_all_paths(this.checked);" /></th>
							<th class="w3-center"><%[ Path ]%></th>
							<th class="w3-center" style="width: 100px;"><%[ Attribute ]%></th>
							<th class="w3-center" style="width: 155px;"><%[ Operator ]%></th>
							<th class="w3-center" style="width: 120px"><%[ Value ]%></th>
							<th class="w3-center" style="width: 112px"><%[ Actions ]%></th>
						</tr>
					</thead>
					<tbody id="verification_rule_window_rule_path_list_tbody"></tbody>
				</table>
			</div>
			<a href="javascript:void(0)" class="w3-button w3-green" onclick="oVerificationRulePathList.add_rule();"><i class="fa-solid fa-plus"></i> &nbsp;<%[ Add path ]%></a>
			<a href="javascript:void(0)" class="w3-button w3-green" onclick="oVerificationPaths.open();"><i class="fa-solid fa-plus"></i> &nbsp;<%[ Add paths from backup ]%></a>
		</div>
		<footer class="w3-container w3-center">
			<button type="button" class="w3-button w3-red" onclick="document.getElementById('verification_rule_window').style.display = 'none';"><i class="fas fa-times"></i> &nbsp;<%[ Cancel ]%></button>
			<button type="button" class="w3-button w3-section w3-green w3-padding" onclick="Prado.Validation.validate(Prado.Validation.getForm(), 'VerificationRuleGroup') && oVerificationRules.save_verification_rule();">
				<i class="fa fa-save"></i> &nbsp;<%[ Save ]%>
			</button>
		</footer>
	</div>
	<com:TActiveHiddenField ID="VerificationRuleWindowType" />
</div>
<div id="verification_paths_window" class="w3-modal">
	<div class="w3-modal-content w3-animate-top w3-card-4" style="width: 1200px">
		<header class="w3-container w3-green">
			<span onclick="oVerificationPaths.show_window(false);" class="w3-button w3-display-topright">&times;</span>
			<h2 id="verification_rule_window_title_add"><%[ Select verification paths ]%></h2>
		</header>
		<div class="w3-container w3-margin">
			<div class="w3-row directive_field">
				<div class="w3-col w3-third"><label for="<%=$this->VerificationPathsClient->ClientID%>" class="w3-right w3-margin-right"><%[ Client ]%>:</label></div>
				<div class="w3-col w3-twothird">
					<com:TActiveDropDownList
						ID="VerificationPathsClient"
						CssClass="w3-select w3-border"
						DataValueField="clientid"
						DataTextField="name"
						PromptText="<%[ Select client ]%>"
						Attributes.onchange="oVerificationPaths.load_jobs();"
					/>
					<i class="fas fa-asterisk w3-text-red opt_req"></i>
					<com:TRequiredFieldValidator
						ValidationGroup="VerificationPathsGroup"
						ControlToValidate="VerificationPathsClient"
						ErrorMessage="<%[ Field required. ]%>"
						ControlCssClass="field_invalid"
						Display="Dynamic"
					/>
				</div>
			</div>
			<div id="verification_paths_window_job">
				<div class="w3-row directive_field">
					<div class="w3-col w3-third"><label for="<%=$this->VerificationPathsJob->ClientID%>" class="w3-right w3-margin-right"><%[ Job ]%>:</label></div>
					<div class="w3-col w3-twothird">
						<com:TActiveDropDownList
							ID="VerificationPathsJob"
							CssClass="w3-select w3-border"
							PromptText="<%[ Select job ]%>"
							OnSelectedIndexChanged="loadJobFiles"
							ClientSide.OnComplete="job_list_files_msg(); show_job_list_files_loader(false); Formatters.set_formatters(); $('#verification_paths_window_file_list').slideDown('fast');"
						/>
						<i class="fas fa-asterisk w3-text-red opt_req"></i>
						<com:TRequiredFieldValidator
							ValidationGroup="VerificationPathsGroup"
							ControlToValidate="VerificationPathsJob"
							ErrorMessage="<%[ Field required. ]%>"
							ControlCssClass="field_invalid"
							Display="Dynamic"
						/>
					</div>
				</div>
			</div>
		</div>
		<div id="verification_paths_window_selected_container" class="w3-container w3-margin-left w3-margin-right w3-margin-top" style="display: none">
			<h4><%[ Selected paths ]%></h4>
			<table class="w3-table w3-striped w3-margin-bottom dataTable dtr-column">
				<thead>
					<tr class="row">
						<th class="w3-center w3-hide-small" style="width:90%"><%[ Path ]%></th>
						<th class="w3-center w3-hide-small"><%[ Action ]%></th>
					</tr>
				</thead>
				<tbody id="verification_paths_window_selected_list"></tbody>
			</table>
			<div class="w3-container w3-center">
				<button type="button" class="w3-button w3-red" onclick="oVerificationPaths.show_window(false);"><i class="fas fa-times"></i> &nbsp;<%[ Close ]%></button>
				<button type="button" class="w3-button w3-section w3-green w3-padding" onclick="oVerificationPaths.use_selected_paths();oVerificationPaths.show_window(false);"><i class="fa-solid fa-check"></i> &nbsp;<%[ Use selected paths ]%></button>
			</div>
		</div>
		<div id="verification_paths_window_file_list" class="w3-container w3-margin-left w3-margin-right w3-margin-top" style="display: none">
			<com:Bacularis.Web.Portlets.JobListFiles
				ID="BackupFiles"
				NameSelectItem="<i class='fa-solid fa-plus'></i> <%= Prado::localize('Select') %>"
				NameUnselectItem="<i class='fa-solid fa-check w3-center'></i>"
				ActionSelectItem="oVerificationPaths.item_action"
			/>
		</div>
	</div>
</div>
<com:TCallback ID="LoadClientList" OnCallback="loadClientList" />
<com:TCallback ID="LoadJobList" OnCallback="loadJobList" />
<com:TCallback ID="ClearJobFiles" OnCallback="clearJobFiles" />
<script>
const oVerificationPaths = {
	ids: {
		win: 'verification_paths_window',
		selected_cont: 'verification_paths_window_selected_container',
		selected_list: 'verification_paths_window_selected_list',
		file_list: 'verification_paths_window_file_list'
	},
	paths: {},
	open: function() {
		this.clear_window();
		this.load_clients();
		this.show_window(true);
	},
	show_window: function(show) {
		const win = document.getElementById(this.ids.win);
		win.style.display = show ? 'block' : 'none';
	},
	load_clients: function() {
		const cb = <%=$this->LoadClientList->ActiveControl->Javascript%>;
		cb.dispatch();
	},
	load_jobs: function() {
		const cb = <%=$this->LoadJobList->ActiveControl->Javascript%>;
		cb.dispatch();
	},
	clear_window: function() {
		[
			'<%=$this->VerificationPathsJob->ClientID%>',
			'<%=$this->VerificationPathsClient->ClientID%>'
		].forEach(function(id) {
			document.getElementById(id).value = '';
		});

		this.paths = {};
		this.update_selected_list();

		this.show_file_list(false);
		this.show_selected_paths(false);

		const cb = <%=$this->ClearJobFiles->ActiveControl->Javascript%>;
		cb.dispatch();
	},
	show_selected_paths: function(show) {
		const cont = document.getElementById(this.ids.selected_cont);
		cont.style.display = show ? 'block' : 'none';
	},
	show_file_list: function(show) {
		const cont = document.getElementById(this.ids.file_list);
		cont.style.display = show ? 'block' : 'none';
	},
	update_selected_list: function() {
		const cont = document.getElementById(this.ids.selected_list);
		// Clear first
		while (cont.firstChild) {
			cont.removeChild(cont.firstChild);
		}
		// Now create a new list
		const add_rm = (btn, path) => {
			btn.addEventListener('click', (e) => {
				if (this.paths.hasOwnProperty(path)) {
					delete(this.paths[path]);
					this.update_selected_list();
				}
			});
		};
		let tr, td, btn;
		for (const path in this.paths) {
			tr = document.createElement('TR');
			td_path = document.createElement('TD');
			td_action = document.createElement('TD');
			btn = document.createElement('BUTTON');
			img = document.createElement('I');

			td_path.style.overflowWrap = 'anywhere';
			td_path.style.wordBreak = 'break-word';
			td_path.textContent = this.paths[path].file;
			btn.type = 'button';
			btn.classList.add('w3-button', 'w3-red');
			btn.appendChild(img);
			img.classList.add('fa-solid', 'fa-trash-alt');
			btn.appendChild(img);
			add_rm(btn, this.paths[path].file);
			td_action.classList.add('w3-center');
			td_action.appendChild(btn);

			tr.appendChild(td_path);
			tr.appendChild(td_action);
			cont.appendChild(tr);
		}

		const show = (Object.keys(this.paths).length > 0);
		this.show_selected_paths(show);
	},
	item_action: function(el, item) {
		if (!this.paths.hasOwnProperty(item.file)) {
			this.select_item(el, item);
			this.paths[item.file] = item;
		}
		this.update_selected_list();
	},
	select_item: function(el) {
		this.item_selected(el);
		setTimeout(() => {
			el.innerHTML = el.getAttribute('data-select');
		}, 900);
	},
	item_selected: function(el) {
		el.innerHTML = el.getAttribute('data-unselect');
	},
	unselect_item: function(el) {
		el.innerHTML = el.getAttribute('data-select');
	},
	use_selected_paths: function() {
		for (const path in this.paths) {
			oVerificationRulePathList.add_rule(path);
		}
	}
};

const oVerificationRulePathList = {
	ids: {
		apply_error: 'verification_rule_window_apply_error',
		apply_msg: 'verification_rule_window_apply_msg',
		apply_rules: 'verification_rule_window_apply_rules',
		custom_fields: 'verification_rule_window_apply_custom_fields',
		custom_rule: 'verification_rule_window_apply_custom',
		path_list: 'verification_rule_window_rule_path_list',
		path_body: 'verification_rule_window_rule_path_list_tbody'
	},
	checkers: {},
	operators: {
		equal_catalog_value: '<%=Bacularis\Web\Modules\VerificationRuleConfig::EQUAL_CATALOG_VALUE%>'
	},
	load: function(rules) {
		const self = oVerificationRulePathList;
		self.clear();
		for (const path in rules) {
			self.add_rule(path, rules[path]);
		}
	},
	show_path_list: function(show) {
		const list = document.getElementById(this.ids.path_list);
		const apply_rules = document.getElementById(this.ids.apply_rules);
		list.style.display = show ? 'block' : 'none';
		apply_rules.style.display = show ? 'block' : 'none';
	},
	clear: function() {
		const container = document.getElementById(this.ids.path_body);
		while (container.firstChild) {
			container.removeChild(container.firstChild);
		}
		this.show_custom_rule(false);
		this.set_apply_error('');
		this.set_apply_msg('');
		this.show_path_list(false);
	},
	add_rule: function(path, rule) {
		this.show_path_list(true);
		const tbody = document.getElementById(this.ids.path_body);
		let tr;
		if (!rule) {
			rule = [null];
		}
		for (let i = 0; i < rule.length; i++) {
			tr = document.createElement('TR');
			this.add_rule_select(tr);
			this.add_rule_path(tr, path, rule[i]);
			this.add_rule_attribute(tr, rule[i]);
			this.add_rule_operator(tr, rule[i]);
			this.add_rule_value(tr, rule[i]);
			this.add_rule_actions(tr, rule[i]);
			tbody.appendChild(tr);
		}
		this.update_select_all_state();
	},
	add_rule_select: function(tr) {
		const td = document.createElement('TD');
		const input = document.createElement('INPUT');
		input.type = 'checkbox';
		input.classList.add('w3-check', 'path_rule_select');
		input.style.verticalAlign = 'super';
		input.addEventListener('change', (e) => {
			this.update_select_all_state();
		});
		td.classList.add('w3-center');
		td.appendChild(input);
		tr.appendChild(td);
	},
	add_rule_path: function(tr, path, rule) {
		const td = document.createElement('TD');
		const input = document.createElement('INPUT');
		input.type = 'text';
		input.classList.add('w3-input', 'w3-border');
		if (path) {
			input.value = path;
		}
		td.appendChild(input);
		tr.appendChild(td);
	},
	add_rule_attribute: function(tr, rule) {
		const td = document.createElement('TD');
		const select = document.createElement('SELECT');
		select.classList.add('w3-select', 'w3-border');
		let option, label;
		option = document.createElement('OPTION');
		option.value = '';
		select.appendChild(option);
		for (const checker in this.checkers) {
			option = document.createElement('OPTION');
			option.value = checker;
			label = document.createTextNode(this.checkers[checker].attr);
			option.appendChild(label);
			select.appendChild(option);
		}
		if (rule) {
			select.value = rule.checker;
		}
		select.addEventListener('change', (e) => {
			const td = select.parentNode;
			const tdn = td.nextElementSibling;
			const sel = tdn.querySelector('select, input');
			const tdnn = tdn.nextElementSibling;
			this.update_rule_operator(sel, rule, select.value);
			this.update_rule_value(tdnn, rule, select.value);
		});
		td.appendChild(select);
		tr.appendChild(td);
		if (rule) {
			$(() => {
				select.dispatchEvent(new Event('change', { bubbles: true }));
			});
		}
	},
	add_rule_operator: function(tr, rule) {
		const td = document.createElement('TD');
		const select = document.createElement('SELECT');
		select.classList.add('w3-select', 'w3-border');
		select.style.minWidth = '120px';
		select.addEventListener('change', (e) => {
			const td = select.parentNode;
			const tdn = td.nextElementSibling;
			const field = tdn.querySelector('select, input');
			const tdnn = tdn.nextElementSibling;
			const enabled = (select.value != this.operators.equal_catalog_value);
			this.enable_rule_value(field, enabled);
		});
		td.appendChild(select);
		tr.appendChild(td);
	},
	update_rule_operator: function(select, rule, checker) {
		this.clear_rule_operator(select);
		const chk = this.checkers.hasOwnProperty(checker) ? this.checkers[checker] : [];
		let option, label;
		option = document.createElement('OPTION');
		option.value = '';
		select.appendChild(option);
		if (!chk.operators) {
			return;
		}
		for (const op in chk.operators) {
			option = document.createElement('OPTION');
			option.value = op;
			label = document.createTextNode(chk.operators[op]);
			option.appendChild(label);
			select.appendChild(option);
		}
		if (rule) {
			select.value = rule.operator;
		}
	},
	clear_rule_operator: function(select) {
		while (select.firstChild) {
			select.removeChild(select.firstChild);
		}
	},
	add_rule_value: function(tr, rule) {
		const td = document.createElement('TD');
		const input = document.createElement('INPUT');
		input.type = 'text';
		input.classList.add('w3-input', 'w3-border');
		input.disabled = true;
		input.style.width = '120px';
		td.appendChild(input);
		tr.appendChild(td);
	},
	update_rule_value: function(td, rule, checker) {
		this.clear_rule_value(td);
		const chk = this.checkers.hasOwnProperty(checker) ? this.checkers[checker] : [];
		if (!chk.values) {
			return;
		}
		switch (chk.values.type) {
			case 'list': {
				this.add_rule_value_list(td, rule, chk.values.values);
				break;
			}
			case 'text': {
				this.add_rule_value_text(td, rule, chk.values.values);
				break;
			}
		}
	},
	clear_rule_value: function(td) {
		while (td.firstChild) {
			td.removeChild(td.firstChild);
		}
	},
	enable_rule_value: function(field, state) {
		if (!field) {
			return;
		}
		if (state) {
			field.removeAttribute('disabled');
		} else {
			field.value = '';
			field.setAttribute('disabled', true);
		}
	},
	add_rule_value_list: function(td, rule, values) {
		const select = document.createElement('SELECT');
		select.classList.add('w3-select', 'w3-border');
		select.style.width = '120px';
		let option, label;
		option = document.createElement('OPTION');
		option.value = '';
		select.appendChild(option);
		for (const val of values) {
			option = document.createElement('OPTION');
			option.value = val;
			label = document.createTextNode(val);
			option.appendChild(label);
			select.appendChild(option);
		}
		td.appendChild(select);
		if (rule) {
			select.value = rule.value;
			if (rule.operator == this.operators.equal_catalog_value) {
				select.setAttribute('disabled', true);
			}
		}
	},
	add_rule_value_text: function(td, rule, values) {
		const input = document.createElement('INPUT');
		input.style.width = '120px';
		input.classList.add('w3-input', 'w3-border');
		input.type = 'text';
		input.value = values;
		td.appendChild(input);
		if (rule) {
			input.value = rule.value;
			if (rule.operator == this.operators.equal_catalog_value) {
				input.setAttribute('disabled', true);
			}
		}
	},
	add_rule_actions: function(tr, rule) {
		const td = document.createElement('TD');
		td.classList.add('w3-center');
		const add_button = document.createElement('BUTTON');
		add_button.type = 'button';
		add_button.classList.add('w3-button', 'w3-green');
		add_button.title = '<%[ Add another rule for this path ]%>';
		add_button.style.marginRight = '8px';
		add_button.addEventListener('click', (e) => {
			this.add_rule_after(tr);
		});
		const add_img = document.createElement('I');
		add_img.classList.add('fa-solid', 'fa-plus');
		add_button.appendChild(add_img);
		td.appendChild(add_button);

		const button = document.createElement('BUTTON');
		button.type = 'button';
		button.classList.add('w3-button', 'w3-red');
		button.addEventListener('click', (e) => {
			const tbody = tr.parentNode;
			tbody.removeChild(tr);
			if (!tbody.firstChild) {
				this.show_path_list(false);
			}
			this.update_select_all_state();
		});
		const img = document.createElement('I');
		img.classList.add('fa-solid', 'fa-trash-alt');
		button.appendChild(img);
		td.appendChild(button);
		tr.appendChild(td);
	},
	add_rule_after: function(tr) {
		const path = this.get_row_path(tr);
		const new_tr = this.create_rule_row(path, null);
		tr.parentNode.insertBefore(new_tr, tr.nextSibling);
		this.update_select_all_state();
	},
	create_rule_row: function(path, rule) {
		const tr = document.createElement('TR');
		this.add_rule_select(tr);
		this.add_rule_path(tr, path, rule);
		this.add_rule_attribute(tr, rule);
		this.add_rule_operator(tr, rule);
		this.add_rule_value(tr, rule);
		this.add_rule_actions(tr, rule);
		return tr;
	},
	set_checkers: function(checkers) {
		const self = oVerificationRulePathList;
		self.checkers = checkers;
		self.build_custom_rule_fields();
	},
	toggle_all_paths: function(checked) {
		const body = document.getElementById(this.ids.path_body);
		const inputs = body.querySelectorAll('input.path_rule_select');
		inputs.forEach(function(input) {
			input.checked = checked;
		});
	},
	update_select_all_state: function() {
		const list = document.getElementById(this.ids.path_list);
		const all = list.querySelector('thead input[type="checkbox"]');
		const body = document.getElementById(this.ids.path_body);
		const inputs = body.querySelectorAll('input.path_rule_select');
		if (!all) {
			return;
		}
		if (inputs.length == 0) {
			all.checked = false;
			all.indeterminate = false;
			return;
		}
		let checked = 0;
		inputs.forEach(function(input) {
			if (input.checked) {
				checked++;
			}
		});
		all.checked = (checked == inputs.length);
		all.indeterminate = (checked > 0 && checked < inputs.length);
	},
	get_row_path: function(tr) {
		const tds = tr.querySelectorAll('td');
		return tds[1].querySelector('input')?.value.trim() || '';
	},
	get_selected_paths: function() {
		const body = document.getElementById(this.ids.path_body);
		const selected = {};
		const inputs = body.querySelectorAll('input.path_rule_select:checked');
		inputs.forEach((input) => {
			const tr = input.closest('tr');
			const path = this.get_row_path(tr);
			if (path) {
				selected[path] = 1;
			}
		});
		return Object.keys(selected);
	},
	ensure_selected_paths: function() {
		const paths = this.get_selected_paths();
		if (paths.length == 0) {
			this.set_apply_msg('');
			this.set_apply_error('<%[ No paths selected. Select one or more paths before applying verification rules. ]%>');
			return false;
		}
		return paths;
	},
	apply_preset: function(type) {
		const paths = this.ensure_selected_paths();
		if (!paths) {
			return;
		}
		const preset = this.get_preset_rules(type);
		if (!preset || preset.rules.length == 0) {
			return;
		}
		this.apply_rules_to_paths(paths, preset.rules);
		this.show_custom_rule(false);
		this.set_apply_error('');
		this.set_apply_msg(preset.message.replace('%count', paths.length));
	},
	get_preset_rules: function(type) {
		const exists_checker = this.get_checker_name(['FileExistsCheck'], 'Exists');
		const size_checker = this.get_checker_name(['FileSizeCheck'], 'Size');
		const type_checker = this.get_checker_name(['TypeCheck'], 'Type');
		const mtime_checker = this.get_checker_name(['MTIMECheck'], 'MTIME');
		const checksum_checker = this.get_checker_name([
			'MD5ChecksumBase64Check',
			'SHA1ChecksumBase64Check',
			'SHA256ChecksumBase64Check',
			'SHA512ChecksumBase64Check'
		], 'checksum');
		const presets = {
			basic: {
				label: '<%[ Basic ]%>',
				message: '<%[ Basic rules were applied to %count selected paths. ]%>',
				rules: [
					{checker: exists_checker, operator: '==', value: 'true'},
					{checker: size_checker, operator: '>', value: '0'}
				]
			},
			metadata: {
				label: '<%[ Metadata ]%>',
				message: '<%[ Metadata rules were applied to %count selected paths. ]%>',
				rules: [
					{checker: exists_checker, operator: '==', value: 'true'},
					{checker: type_checker, operator: this.operators.equal_catalog_value, value: ''},
					{checker: size_checker, operator: this.operators.equal_catalog_value, value: ''},
					{checker: mtime_checker, operator: this.operators.equal_catalog_value, value: ''}
				]
			},
			checksum: {
				label: '<%[ Checksum ]%>',
				message: '<%[ Checksum rules were applied to %count selected paths. ]%>',
				rules: [
					{checker: exists_checker, operator: '==', value: 'true'},
					{checker: checksum_checker, operator: this.operators.equal_catalog_value, value: ''}
				]
			},
			strict: {
				label: '<%[ Strict ]%>',
				message: '<%[ Strict rules were applied to %count selected paths. ]%>',
				rules: [
					{checker: exists_checker, operator: '==', value: 'true'},
					{checker: type_checker, operator: this.operators.equal_catalog_value, value: ''},
					{checker: size_checker, operator: this.operators.equal_catalog_value, value: ''},
					{checker: mtime_checker, operator: this.operators.equal_catalog_value, value: ''},
					{checker: checksum_checker, operator: this.operators.equal_catalog_value, value: ''}
				]
			}
		};
		if (!presets.hasOwnProperty(type)) {
			return null;
		}
		presets[type].rules = presets[type].rules.filter(function(rule) {
			return !!rule.checker;
		});
		return presets[type];
	},
	get_checker_name: function(names, attr) {
		for (let i = 0; i < names.length; i++) {
			for (const checker in this.checkers) {
				if (checker == names[i] || checker.endsWith('\\' + names[i])) {
					return checker;
				}
			}
		}
		const attr_lc = attr.toLowerCase();
		for (const checker in this.checkers) {
			if ((this.checkers[checker].attr || '').toLowerCase().indexOf(attr_lc) !== -1) {
				return checker;
			}
		}
		return '';
	},
	apply_rules_to_paths: function(paths, rules) {
		for (let i = 0; i < paths.length; i++) {
			this.apply_rules_to_path(paths[i], rules);
		}
		this.update_select_all_state();
	},
	apply_rules_to_path: function(path, rules) {
		for (let i = 0; i < rules.length; i++) {
			this.upsert_rule(path, rules[i]);
		}
	},
	upsert_rule: function(path, rule) {
		const tbody = document.getElementById(this.ids.path_body);
		const rows = this.get_path_rows(path);
		for (let i = 0; i < rows.length; i++) {
			const checker = this.get_row_checker(rows[i]);
			if (checker == rule.checker || checker == '') {
				this.set_row_rule(rows[i], rule);
				return;
			}
		}
		const new_tr = this.create_rule_row(path, rule);
		const last = rows.length > 0 ? rows[rows.length - 1] : null;
		if (last) {
			tbody.insertBefore(new_tr, last.nextSibling);
		} else {
			tbody.appendChild(new_tr);
		}
	},
	get_path_rows: function(path) {
		const body = document.getElementById(this.ids.path_body);
		const rows = [];
		body.querySelectorAll('tr').forEach((tr) => {
			if (this.get_row_path(tr) == path) {
				rows.push(tr);
			}
		});
		return rows;
	},
	get_row_checker: function(tr) {
		const tds = tr.querySelectorAll('td');
		return tds[2].querySelector('select')?.value || '';
	},
	set_row_rule: function(tr, rule) {
		const tds = tr.querySelectorAll('td');
		const attr = tds[2].querySelector('select');
		attr.value = rule.checker;
		attr.dispatchEvent(new Event('change', { bubbles: true }));
		const op = tds[3].querySelector('select');
		op.value = rule.operator;
		op.dispatchEvent(new Event('change', { bubbles: true }));
		const val = tds[4].querySelector('select, input');
		if (val && rule.operator != this.operators.equal_catalog_value) {
			val.value = rule.value;
		}
	},
	show_custom_rule: function(show) {
		const custom = document.getElementById(this.ids.custom_rule);
		if (show) {
			$(custom).slideDown('fast');
			this.build_custom_rule_fields();
		} else {
			$(custom).slideUp('fast');
		}
	},
	build_custom_rule_fields: function() {
		const cont = document.getElementById(this.ids.custom_fields);
		if (!cont) {
			return;
		}
		while (cont.firstChild) {
			cont.removeChild(cont.firstChild);
		}
		const table = document.createElement('TABLE');
		table.classList.add('w3-table');
		const tr = document.createElement('TR');
		this.add_rule_attribute(tr, null);
		this.add_rule_operator(tr, null);
		this.add_rule_value(tr, null);
		const td = document.createElement('TD');
		const btn = document.createElement('BUTTON');
		btn.type = 'button';
		btn.classList.add('w3-button', 'w3-green');
		btn.textContent = '<%[ Apply ]%>';
		btn.addEventListener('click', (e) => {
			this.apply_custom_rule(tr);
		});
		td.appendChild(btn);
		tr.appendChild(td);
		table.appendChild(tr);
		cont.appendChild(table);
	},
	apply_custom_rule: function(tr) {
		const paths = this.ensure_selected_paths();
		if (!paths) {
			return;
		}
		const tds = tr.querySelectorAll('td');
		const rule = {
			checker: tds[0].querySelector('select')?.value || '',
			operator: tds[1].querySelector('select')?.value || '',
			value: tds[2].querySelector('select, input')?.value.trim() || ''
		};
		if (!rule.checker || !rule.operator || (!rule.value && rule.operator != this.operators.equal_catalog_value)) {
			return;
		}
		this.apply_rules_to_paths(paths, [rule]);
		this.set_apply_error('');
		const msg = '<%[ Custom rules were applied to %count selected paths. ]%>';
		this.set_apply_msg(msg.replace('%count', paths.length));
	},
	set_apply_msg: function(msg) {
		const el = document.getElementById(this.ids.apply_msg);
		el.textContent = msg;
		if (msg) {
			$(el).slideDown('fast');
			setTimeout(() => {
				$(el).slideUp('fast');
			}, 5000);
		} else {
			el.style.display = 'none';
		}
	},
	set_apply_error: function(msg) {
		const el = document.getElementById(this.ids.apply_error);
		el.textContent = msg;
		if (msg) {
			$(el).slideDown('fast');
		} else {
			el.style.display = 'none';
		}
	},
	get_values: function() {
		const body = document.getElementById(this.ids.path_body);
		const trs = body.querySelectorAll('tr');
		const values = {};
		let tds, path, checker, op, val, rule;
		for (let i = 0; i < trs.length; i++) {
			tds = trs[i].querySelectorAll('td');
			path = tds[1].querySelector('input')?.value;
			checker = tds[2].querySelector('select')?.value;
			op = tds[3].querySelector('select')?.value;
			val = tds[4].querySelector('select, input')?.value.trim();
			if (!path || (!val && op != this.operators.equal_catalog_value)) {
				continue;
			}
			if (!values.hasOwnProperty(path)) {
				values[path] = [];
			}
			rule = {
				checker: checker,
				operator: op,
				value: val
			};
			values[path].push(rule);
		}
		return values;
	}
};
window.oVerificationRulePathList = oVerificationRulePathList;
</script>
