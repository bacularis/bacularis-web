<div>
	<p class="w3-hide-small" style="margin-bottom: 0"><%[ Configure restore tests that automatically restore selected backup data and verify the result. A restore test defines what should be tested, where the data should be restored, which policy should be used, and which verification rules should check the restored files. ]%></p>
	<div class="w3-container" style="margin: 10px 0">
		<button type="button" id="add_restore_test_btn" class="w3-button w3-green" onclick="oRestoreTests.load_restore_test_window()"><i class="fa fa-plus"></i> &nbsp;<%[ Add restore test ]%></button>
	</div>
	<!-- Tag tools -->
	<com:Bacularis.Web.Portlets.TagTools ID="TagToolsRestoreTestList" ViewName="restore_test_list" />
	<table id="restore_test_list_table" class="display w3-table w3-striped w3-hoverable w3-margin-bottom selectable" style="width: 100%;">
		<thead>
			<tr>
				<th></th>
				<th><%[ Name ]%></th>
				<th class="w3-center"><%[ Backup source ]%></th>
				<th class="w3-center"><%[ Policy ]%></th>
				<th class="w3-center"><%[ Destination ]%></th>
				<th class="w3-center"><%[ Last result ]%></th>
				<th class="w3-center"><%[ Last run ]%></th>
				<th class="w3-center"><%[ Last run date ]%></th>
				<th class="w3-center"><%[ Next run ]%></th>
				<th class="w3-center"><%[ Starts in ]%></th>
				<th class="w3-center"><%[ Enabled ]%></th>
				<th class="w3-center"><%[ Tag ]%></th>
				<th class="w3-center"><%[ Actions ]%></th>
			</tr>
		</thead>
		<tbody id="restore_test_list_body"></tbody>
		<tfoot>
			<tr>
				<th></th>
				<th><%[ Name ]%></th>
				<th class="w3-center"><%[ Backup source ]%></th>
				<th class="w3-center"><%[ Policy ]%></th>
				<th class="w3-center"><%[ Destination ]%></th>
				<th class="w3-center"><%[ Last result ]%></th>
				<th class="w3-center"><%[ Last run ]%></th>
				<th class="w3-center"><%[ Last run date ]%></th>
				<th class="w3-center"><%[ Next run ]%></th>
				<th class="w3-center"><%[ Starts in ]%></th>
				<th class="w3-center"><%[ Enabled ]%></th>
				<th class="w3-center"><%[ Tag ]%></th>
				<th class="w3-center"><%[ Actions ]%></th>
			</tr>
		</tfoot>
	</table>
	<p class="info w3-hide-medium w3-hide-small"><%[ Tip: Use left-click to select table row. Use CTRL + left-click to multiple row selection. Use SHIFT + left-click to add a range of rows to selection. ]%></p>
<com:TCallback ID="RestoreTestList" OnCallback="TemplateControl.setRestoreTestList" />
<com:TCallback ID="LoadRestoreTest" OnCallback="TemplateControl.loadRestoreTestWindow">
	<prop:ClientSide.OnLoading>
		oRestoreTests.show_window_loader(true);
	</prop:ClientSide.OnLoading>
	<prop:ClientSide.OnComplete>
		oRestoreTests.show_window_loader(false);
	</prop:ClientSide.OnComplete>
</com:TCallback>
<com:TCallback ID="RemoveRestoreTestsAction" OnCallback="TemplateControl.removeRestoreTests" />
<script>
const oRestoreTestList = {
	ids: {
		restore_test_list: 'restore_test_list_table'
	},
	actions: [
		{
			action: 'remove',
			label: '<%[ Remove ]%>',
			value: ['name', 'orgs'],
			callback: <%=$this->RemoveRestoreTestsAction->ActiveControl->Javascript%>
		}
	],
	txts: {
		time: {
			second: ['<%[ second ]%>', '<%[ seconds ]%>'],
			minute: ['<%[ minute ]%>', '<%[ minutes ]%>'],
			hour: ['<%[ hour ]%>', '<%[ hours ]%>'],
			day: ['<%[ day ]%>', '<%[ days ]%>']
		},
		time_ago: '<%[ %time ago ]%>'
	},
	data: [],
	table: null,
	table_toolbar: null,
	job_stats: {},
	source_type: {
		backup_job: '<%=Bacularis\Web\Modules\RestoreTestConfig::SOURCE_TYPE_BACKUP_JOB%>',
		backup_jobid: '<%=Bacularis\Web\Modules\RestoreTestConfig::SOURCE_TYPE_BACKUP_JOBID%>'
	},
	verification_method: {
		rules: '<%=Bacularis\Web\Modules\RestoreTestConfig::RESTORE_VERIFICATION_METHOD_RULES%>',
		verify_job: '<%=Bacularis\Web\Modules\RestoreTestConfig::RESTORE_VERIFICATION_METHOD_VERIFY_JOB%>'
	},
	init: function() {
		this.update_job_stats();
		if (!this.table) {
			this.set_table();
			this.set_bulk_actions();
			this.set_events();
		} else {
			const page = this.table.page();
			this.table.clear().rows.add(this.data).draw();
			this.table.page(page).draw(false);
			oRestoreTestList.set_filters(this.table);
			this.table_toolbar.style.display = 'none';
		}
	},
	set_events: function() {
		document.getElementById(this.ids.restore_test_list).addEventListener('click', function(e) {
			$(function() {
				const wa = (this.table.rows({selected: true}).data().length > 0) ? 'show' : 'hide';
				$(this.table_toolbar).animate({
					width: wa
				}, 'fast');
			}.bind(this));
		}.bind(this));
	},
	set_table: function() {
		this.table = $('#' + this.ids.restore_test_list).DataTable({
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
				{data: 'backup_source'},
				{data: 'restore_policy'},
				{data: 'restore_destination'},
				{
					data: 'name',
					render: (data, type, row) => {
						let ret = '-';
						const fjob = this.get_test_result_job(row);
						if (fjob) {
							ret = render_jobstatus(fjob.jobstatus, type, row);
						}
						return ret;
					},
					responsivePriority: 6,
					width: '15%'
				},
				{
					data: 'name',
					render: (data, type, row) => {
						let ret = '-';
						const fjob = this.get_test_job(row);
						if (fjob) {
							ret = this.render_last_job(fjob, type);
						}
						return ret;
					},
					responsivePriority: 6,
					width: '15%'
				},
				{
					data: 'name',
					render: (data, type, row) => {
						let ret = '-';
						const fjob = this.get_test_job(row);
						if (fjob) {
							ret = render_date_ts(fjob.starttime_epoch, type, row);
						}
						return ret;
					},
					responsivePriority: 6,
					width: '15%',
					visible: false
				},
				{
					data: 'name',
					render: (data, type, row) => {
						let ret = '-';
						let sched;
						let now = Dashboard.get_currtime_epoch();
						if (row.rp_config.run_method == '<%=RestorePolicyConfig::RUN_METHOD_SCHEDULE%>' && oSchedule.schedules.hasOwnProperty(data)) {
							sched = oRestoreTestList.find_next_sched(data, now);
							if (sched) {
								ret = render_date_ts_local(sched, type);
							}
						} else if (row.rp_config.run_method == '<%=RestorePolicyConfig::RUN_METHOD_AFTER_BACKUP%>' && oSchedule.schedules.hasOwnProperty(row.source_backup_job)) {
							const job_levels = row.rp_config.job_levels || null;
							sched = oRestoreTestList.find_next_sched(row.source_backup_job, now, job_levels);
							if (sched) {
								ret = render_date_ts_local(sched, type);
							}
						}
						return ret;
					},
					visible: false
				},
				{
					data: 'name',
					render: (data, type, row) => {
						let ret = '-';
						let now = Dashboard.get_currtime_epoch();
						let sched;
						if (row.rp_config.run_method == '<%=RestorePolicyConfig::RUN_METHOD_SCHEDULE%>' && oSchedule.schedules.hasOwnProperty(data)) {
							sched = oRestoreTestList.find_next_sched(data, now);
						} else if (row.rp_config.run_method == '<%=RestorePolicyConfig::RUN_METHOD_AFTER_BACKUP%>' && oSchedule.schedules.hasOwnProperty(row.source_backup_job)) {
							const job_levels = row.rp_config.job_levels || null;
							sched = oRestoreTestList.find_next_sched(row.source_backup_job, now, job_levels);
						}
						if (sched) {
							if (type == 'display' || type == 'filter') {
								const span = document.createElement('SPAN');
								res = Units.get_time_diff_duration(now, sched);
								span.title = render_date_ts_local(sched, type)
								span.textContent = res;
								ret = span;
							} else {
								ret = sched;
							}
						}
						return ret;
					}
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
						const tt_obj = oTagTools_<%=$this->TagToolsRestoreTestList->ClientID%>;
						const table = 'oRestoreTestList.table';
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
						btn_edit.setAttribute('onclick', 'oRestoreTests.load_restore_test_window(\'' + data + '\')');
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
				targets: [ 12 ]
			},
			{
				className: "dt-center",
				targets: [ 5, 6, 7, 8, 9, 10, 11 ]
			}],
			select: {
				style:    'os',
				selector: 'td:not(:last-child):not(:first-child)',
				blurable: false
			},
			order: [1, 'asc'],
			initComplete: function () {
				oRestoreTestList.set_filters(this.api());
			}
		});
	},
	set_filters: function(api) {
		api.columns([1, 2, 3, 4, 10]).every(function () {
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
			if ([10].indexOf(column[0][0]) != -1) { // Enabled column
				column.data().unique().sort().each(function (d, j) {
					var ds = d;
					if (column[0][0] == 10) { // Enabled column
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
	find_next_sched: function(data, now, levels) {
		let sch, find;
		let len = oSchedule.schedules[data].length;
		len = len > 20 ? 20 : len;
		for (let i = 0; i < len; i++) {
			if (levels && levels.indexOf(oSchedule.schedules[data][i].level) == -1) {
				continue;
			}
			sch = parseInt(oSchedule.schedules[data][i].schedtime_epoch, 10);
			if (sch > now) {
				find = sch;
				break;
			}
		}
		return find;
	},
	set_bulk_actions: function() {
		this.table_toolbar = get_table_toolbar(this.table, this.actions, {
			actions: '<%[ Select action ]%>',
			ok: '<%[ OK ]%>'
		});
	},
	update_job_stats: function() {
		this.job_stats = {};
		for (const job of oData.terminated_jobs) {
			if (['D', 'R', 'V'].indexOf(job.type) == -1) {
				continue;
			}
			if (!this.job_stats.hasOwnProperty(job.name)) {
				this.job_stats[job.name] = [];
			}
			this.job_stats[job.name].push(job);
		}
	},
	render_last_job: function(job, type) {
		const empty = '-';
		let ret = empty;
		const ts = job.starttime_epoch * 1000;
		const d = new Date();
		const tz = d.getTimezoneOffset() * 60 * 1000;
		const now = d.getTime() - tz;
		const tdiff = parseInt((now - ts) / 1000);
		if (type == 'display' || type == 'filter') {
			let span = document.createElement('SPAN');
			const val = Units.format_time_period(tdiff, null, true);
			const t = parseInt(val.value, 10);
			let res;
			if (this.txts.time.hasOwnProperty(val.format)) {
				const tidx = t > 1 ? 1 : 0;
				const ttext = this.txts.time[val.format][tidx];
				res = t + ' ' + this.txts.time_ago.replace('%time', ttext);
				span.title = job.starttime;
			} else {
				res = empty;
			}
			span.textContent = res;
			ret = span;
		} else if (type == 'sort') {
			ret = tdiff || empty;
		}
		return ret;
	},
	get_test_job: function(rtest) {
		let fjob;
		if (this.job_stats.hasOwnProperty(rtest.name) && this.job_stats[rtest.name].length > 0) {
			fjob = this.job_stats[rtest.name][0];
		}
		return fjob;
	},
	get_test_result_job: function(rtest) {
		let fjob;
		if (this.job_stats.hasOwnProperty(rtest.name)) {
			let job_name;
			if (rtest.verification_method == this.verification_method.rules) {
				job_name = rtest.restore_job;
			} else if (rtest.verification_method == this.verification_method.verify_job) {
				job_name = rtest.verification_verify_job;
			}
			if (this.job_stats.hasOwnProperty(job_name)) {
				let pat;
				if (rtest.source_type == this.source_type.backup_job) {
					pat = '^Test Job: ' + rtest.source_backup_job + ' JobId: \\d+ Test Name: ' + rtest.name + '$';
				} else if (rtest.source_type == this.source_type.backup_jobid) {
					pat = '^Test Job: .+ JobId: ' + rtest.source_backup_jobid + ' Test Name: ' + rtest.name + '$';
				}
				const regex = new RegExp(pat);
				const rlen = this.job_stats[job_name].length;
				for (let i = 0; i < rlen; i++) {
					if (regex.test(this.job_stats[job_name][i].comment)) {
						fjob = this.job_stats[job_name][i];
						break;
					}
				}
			}
		}
		return fjob;
	}
};
window.oRestoreTestList = oRestoreTestList;

const oRestoreTests = {
	ids: {
		win: 'restore_test_window',
		win_type: '<%=$this->RestoreTestWindowType->ClientID%>',
		win_loader: 'restore_test_window_loader',
		save_loader: 'restore_test_save_loader',
		test_name: '<%=$this->RestoreTestFullName->ClientID%>',
		title_add: 'restore_test_window_title_add',
		title_edit: 'restore_test_window_title_edit',
		title_name: 'restore_test_window_title_name',
		job_levels: 'restore_test_window_backup_source_job_levels',
		time_range: 'restore_test_window_backup_source_time_range',
		backup_jobid: 'restore_test_window_backup_job_jobid',
		backup_name: 'restore_test_window_backup_job_job_name',
		backup_jobid_info: '<%=$this->RestoreTestBackupJobJobIdInfo->ClientID%>',
		advanced_opts: 'restore_test_window_advanced_options_container',
		restore_scope_native_verify: 'restore_test_window_restore_scope_native_verify',
		restore_scope_options: 'restore_test_window_restore_scope_options',
		verification_rule_sets: 'restore_test_window_verification_rule_sets',
		verification_verify_job: 'restore_test_window_verification_verify_job',
		verification_method_rules: '<%=$this->RestoreTestVerificationMethodRulesRadio->ClientID%>',
		verification_method_verify_job: '<%=$this->RestoreTestVerificationMethodVerifyJobRadio->ClientID%>',
		web_interface_protocol: '<%=$this->RestoreTestWebProtocol->ClientID%>',
		web_interface_address: '<%=$this->RestoreTestWebAddress->ClientID%>',
		web_interface_port: '<%=$this->RestoreTestWebPort->ClientID%>',
		web_allowed_ips: '<%=$this->RestoreTestWebAllowedIPs->ClientID%>'

	},
	load_restore_test_window: function(name) {
		let title_add = document.getElementById(this.ids.title_add);
		let title_edit = document.getElementById(this.ids.title_edit);
		let title_name = document.getElementById(this.ids.title_name);
		let restore_test_win_type = document.getElementById(this.ids.win_type);
		let restore_test_name = document.getElementById(this.ids.test_name);
		let advanced_opts = document.getElementById(this.ids.advanced_opts);
		document.getElementById(this.ids.backup_jobid_info).style.display = 'none';
		const cb = <%=$this->LoadRestoreTest->ActiveControl->Javascript%>;
		cb.setCallbackParameter(name);
		cb.dispatch();
		if (name) {
			// edit existing restore_test
			title_add.style.display = 'none';
			title_edit.style.display = 'inline-block';
			title_name.textContent = name;
			advanced_opts.style.display = 'block';
			restore_test_win_type.value = 'edit';
			restore_test_name.setAttribute('readonly', '');
		} else {
			// add new restore_test
			title_add.style.display = 'inline-block';
			title_edit.style.display = 'none';
			title_name.textContent = '';
			advanced_opts.style.display = 'block';
			restore_test_win_type.value = 'add';
			restore_test_name.removeAttribute('readonly');
			this.clear_restore_test_window();
		}
		const restore_test_win = document.getElementById(this.ids.win);
		restore_test_win.style.display = 'block';
		if (!name) {
			restore_test_name.focus();
		}
	},
	load_restore_test_list: function() {
		const cb = <%=$this->RestoreTestList->ActiveControl->Javascript%>;
		cb.dispatch();
	},
	load_restore_test_list_cb: function(list) {
		oRestoreTestList.data = list;
		oRestoreTestList.init();
	},
	clear_restore_test_window: function() {
		// clear inputs and selects
		[
			'<%=$this->RestoreTestFullName->ClientID%>',
			'<%=$this->RestoreTestDescription->ClientID%>',
			'<%=$this->RestoreTestBackupJobList->ClientID%>',
			'<%=$this->RestoreTestBackupJobJobId->ClientID%>',
			'<%=$this->RestoreTestBackupSourceTimeRangeFrom->ClientID%>',
			'<%=$this->RestoreTestBackupSourceTimeRangeTo->ClientID%>',
			'<%=$this->RestoreTestRestoreDestinationDestination->ClientID%>',
			'<%=$this->RestoreTestRestoreJob->ClientID%>',
			'<%=$this->RestoreTestRestorePolicyPolicy->ClientID%>',
			'<%=$this->RestoreTestVerificationVerifyJob->ClientID%>',
		].forEach(function(id) {
			document.getElementById(id).value = '';
		});

		// clear checkboxes and radio buttons
		[
			'<%=$this->RestoreTestEnabled->ClientID%>',
			'<%=$this->RestoreTestBackupJobRadio->ClientID%>',
			'<%=$this->RestoreTestBackupSourceLatestSuccessfulRadio->ClientID%>',
			'<%=$this->RestoreTestRestoreScopeEntireBackup->ClientID%>',
			'<%=$this->RestoreTestVerificationMethodRulesRadio->ClientID%>'
		].forEach(function(id) {
			document.getElementById(id).checked = true;
		});

		[
			'<%=$this->RestoreTestBackupJobSingleJobIdRadio->ClientID%>',
			'<%=$this->RestoreTestBackupSourceLatestBackupTimeRangeRadio->ClientID%>',
			'<%=$this->RestoreTestBackupSourceLatestJobLevelsRadio->ClientID%>',
			'<%=$this->RestoreTestRestoreScopeFilesRequiredByVerificationRules->ClientID%>',
			'<%=$this->RestoreTestRestoreScopeRandomSample->ClientID%>',
			'<%=$this->RestoreTestVerificationMethodVerifyJobRadio->ClientID%>'
		].forEach(function(id) {
			document.getElementById(id).checked = false;
		});
		document.getElementById('<%=$this->RestoreTestBackupSourceJobLevels->ClientID%>').selectedIndex = -1;
		document.getElementById('<%=$this->RestoreTestVerificationRules->ClientID%>').selectedIndex = -1;
		document.getElementById(this.ids.backup_name).style.display = 'block';
		document.getElementById(this.ids.backup_jobid).style.display = 'none';
		document.getElementById(this.ids.backup_jobid_info).style.display = 'none';
		document.getElementById(this.ids.time_range).style.display = 'none';
		document.getElementById(this.ids.job_levels).style.display = 'none';
		this.set_verification_method('rules', false);
	},
	save_restore_test_cb: function() {
		const self = oRestoreTests;
		self.load_restore_test_list();
	},
	show_window_loader: function(show) {
		const loader = document.getElementById(this.ids.win_loader);
		loader.style.display = show ? 'inline-block' : 'none';
	},
	show_save_loader: function(show) {
		const loader = document.getElementById(this.ids.save_loader);
		loader.style.visibility = show ? 'visible' : 'hidden';
	},
	set_verification_method: function(method, animate) {
		const self = oRestoreTests;
		const fast = animate === false ? 0 : 'fast';
		if (method == 'verify_job') {
			$('#' + self.ids.verification_rule_sets).slideUp(fast);
			$('#' + self.ids.verification_verify_job).slideDown(fast);
			$('#' + self.ids.restore_scope_options).slideUp(fast);
			$('#' + self.ids.restore_scope_native_verify).slideDown(fast);
		} else {
			$('#' + self.ids.verification_verify_job).slideUp(fast);
			$('#' + self.ids.verification_rule_sets).slideDown(fast);
			$('#' + self.ids.restore_scope_native_verify).slideUp(fast);
			$('#' + self.ids.restore_scope_options).slideDown(fast);
		}
	}
};
window.oRestoreTests = oRestoreTests;
	</script>
</div>
<div id="restore_test_window" class="w3-modal">
	<div class="w3-modal-content w3-animate-top w3-card-4">
		<header class="w3-container w3-green">
			<span onclick="document.getElementById('restore_test_window').style.display = 'none';" class="w3-button w3-display-topright">&times;</span>
			<h2 id="restore_test_window_title_add" style="display: none"><%[ Add restore test ]%></h2>
			<h2 id="restore_test_window_title_edit" style="display: none"><%[ Edit restore test ]%> - <span id="restore_test_window_title_name"></span></h2>
			<i id="restore_test_window_loader" class="fa-solid fa-sync-alt w3-spin w3-xlarge w3-margin-left" style="display: none;"></i>
		</header>
		<div class="w3-container w3-margin-left w3-margin-right w3-margin-top">
			<com:TActiveLabel ID="RestoreTestWindowError" CssClass="error" Display="None" />
			<h4 class="w3-border-bottom w3-padding"><%[ General ]%></h4>
			<div class="w3-row directive_field">
				<div class="w3-col w3-third"><label for="<%=$this->RestoreTestFullName->ClientID%>"><%[ Restore test name ]%>:</label></div>
				<div class="w3-col w3-twothird">
					<com:TActiveTextBox
						ID="RestoreTestFullName"
						AutoPostBack="false"
						MaxLength="160"
						CssClass="w3-input w3-border w3-show-inline-block"
						Attributes.placeholder="ex: My restore test"
					/>
					<i class="fa-solid fa-asterisk w3-text-red opt_req"></i>
					<com:TRequiredFieldValidator
						ValidationGroup="RestoreTestGroup"
						ControlToValidate="RestoreTestFullName"
						ErrorMessage="<%[ Field required. ]%>"
						ControlCssClass="field_invalid"
						Display="Dynamic"
					/>
					<com:TRegularExpressionValidator
						ValidationGroup="RestoreTestGroup"
						RegularExpression="<%=RestoreTestConfig::NAME_PATTERN%>"
						ControlToValidate="RestoreTestFullName"
						ErrorMessage="<%[ Invalid value. ]%>"
						ControlCssClass="field_invalid"
						Display="Dynamic"
					/>
				</div>
			</div>
			<div class="w3-row directive_field">
				<div class="w3-col w3-third"><label for="<%=$this->RestoreTestDescription->ClientID%>"><%[ Description ]%>:</label></div>
				<div class="w3-col w3-twothird">
					<com:TActiveTextBox
						ID="RestoreTestDescription"
						TextMode="MultiLine"
						Rows="3"
						AutoPostBack="false"
						MaxLength="500"
						CssClass="w3-input w3-border"
						Attributes.placeholder="ex: This is D12 server restore test..."
					/>
				</div>
			</div>
			<div class="w3-row directive_field">
				<div class="w3-col w3-third"><label for="<%=$this->RestoreTestEnabled->ClientID%>"><%[ Enabled ]%>:</label></div>
				<div class="w3-twothird">
					<com:TActiveCheckBox
						ID="RestoreTestEnabled"
						CssClass="w3-check w3-border"
						AutoPostBack="false"
						Checked="true"
					/>
				</div>
			</div>
			<h4 class="w3-border-bottom w3-padding"><%[ Backup selection ]%></h4>
			<p><%[ Choose which backup should be restored and verified. ]%></p>
			<div class="w3-margin-bottom">
				<div class="w3-row directive_field">
					<div class="w3-col w3-third"><label for="<%=$this->RestoreTestBackupJobRadio->ClientID%>" class="w3-margin-left"><%[ Source type ]%></label></div>
					<div class="w3-twothird">
						<com:TActiveRadioButton
							ID="RestoreTestBackupJobRadio"
							CssClass="w3-check w3-border"
							AutoPostBack="false"
							Checked="true"
							GroupName="RestoreTestBackupJob"
							Attributes.onclick="$('#restore_test_window_backup_job_jobid').slideUp('fast'); $('#restore_test_window_backup_job_job_name').slideDown('fast');"
						/> <label for="<%=$this->RestoreTestBackupJobRadio->ClientID%>"><%[ Backup job ]%></label>
					</div>
				</div>
				<div class="w3-row directive_field">
					<div class="w3-col w3-third">&nbsp;</div>
					<div class="w3-twothird">
						<com:TActiveRadioButton
							ID="RestoreTestBackupJobSingleJobIdRadio"
							CssClass="w3-check w3-border"
							AutoPostBack="false"
							GroupName="RestoreTestBackupJob"
							Attributes.onclick="$('#restore_test_window_backup_job_job_name').slideUp('fast'); $('#restore_test_window_backup_job_jobid').slideDown('fast');"
						/> <label for="<%=$this->RestoreTestBackupJobSingleJobIdRadio->ClientID%>"><%[ Single backup jobid ]%></label>
					</div>
				</div>
			</div>
			<div id="restore_test_window_backup_job_job_name">
				<div class="w3-row directive_field w3-margin-bottom">
					<div class="w3-col w3-third"><label for="<%=$this->RestoreTestBackupJobList->ClientID%>" class="w3-margin-left"><%[ Backup job ]%>:</label></div>
					<div class="w3-twothird">
						<com:TActiveDropDownList
							ID="RestoreTestBackupJobList"
							CssClass="w3-select w3-border"
							AutoPostBack="false"
							PromptText="<%[ Select backup job ]%>"
							PromptValue=" "
						/>
						<i class="fa-solid fa-asterisk w3-text-red opt_req"></i>
						<com:TRequiredFieldValidator
							ValidationGroup="RestoreTestGroup"
							ControlToValidate="RestoreTestBackupJobList"
							ErrorMessage="<%[ Field required. ]%>"
							ControlCssClass="field_invalid"
							Display="Dynamic"
						>
							<prop:ClientSide.OnValidate>
								const el = document.getElementById('<%=$this->RestoreTestBackupJobRadio->ClientID%>');
								sender.enabled = el.checked;
							</prop:ClientSide.OnValidate>
						</com:TRequiredFieldValidator>
					</div>
				</div>
				<div class="w3-row directive_field">
					<div class="w3-col w3-third"><label class="w3-margin-left"><%[ Backup version ]%></div>
					<div class="w3-twothird">
						<com:TActiveRadioButton
							ID="RestoreTestBackupSourceLatestSuccessfulRadio"
							CssClass="w3-check w3-border"
							AutoPostBack="false"
							Checked="true"
							GroupName="RestoreTestBackupSource"
							Attributes.onclick="$('#restore_test_window_backup_source_time_range').slideUp('fast');$('#restore_test_window_backup_source_job_levels').slideUp('fast');"
						/> <label for="<%=$this->RestoreTestBackupSourceLatestSuccessfulRadio->ClientID%>"><%[ Latest successful backup any level ]%></label>
					</div>
				</div>
				<div class="w3-row directive_field">
					<div class="w3-col w3-third">&nbsp;</div>
					<div class="w3-twothird">
						<com:TActiveRadioButton
							ID="RestoreTestBackupSourceLatestBackupTimeRangeRadio"
							CssClass="w3-check w3-border"
							AutoPostBack="false"
							GroupName="RestoreTestBackupSource"
							Attributes.onclick="$('#restore_test_window_backup_source_time_range').slideDown('fast');$('#restore_test_window_backup_source_job_levels').slideUp('fast');"
						/> <label for="<%=$this->RestoreTestBackupSourceLatestBackupTimeRangeRadio->ClientID%>"><%[ Latest successful backup any level within selected time range ]%></label>
					</div>
				</div>
				<div class="w3-row directive_field w3-margin-bottom">
					<div class="w3-col w3-third">&nbsp;</div>
					<div class="w3-twothird">
						<com:TActiveRadioButton
							ID="RestoreTestBackupSourceLatestJobLevelsRadio"
							CssClass="w3-check w3-border"
							AutoPostBack="false"
							GroupName="RestoreTestBackupSource"
							Attributes.onclick="$('#restore_test_window_backup_source_time_range').slideUp('fast');$('#restore_test_window_backup_source_job_levels').slideDown('fast');"
						/> <label for="<%=$this->RestoreTestBackupSourceLatestJobLevelsRadio->ClientID%>"><%[ Latest successful backup matching selected levels ]%></label>
					</div>
				</div>
				<div id="restore_test_window_backup_source_time_range" style="display: none">
					<div class="w3-row directive_field">
						<div class="w3-col w3-third"><label for="<%=$this->RestoreTestBackupSourceTimeRangeFrom->ClientID%>" class="w3-margin-left"><%[ Date from ]%>:</label></div>
						<div class="w3-col w3-twothird">
							<com:TJuiDatePicker
								ID="RestoreTestBackupSourceTimeRangeFrom"
								Options.dateFormat="yy-mm-dd"
								Options.changeYear="true"
								Options.changeMonth="true"
								Options.showAnim="fold"
								Style.Width="120px"
								CssClass="w3-input w3-border w3-show-inline-block"
								Attributes.placeholder="YYYY-MM-DD"
								/>
							<i class="fa-solid fa-asterisk w3-text-red opt_req"></i>
							<com:TRequiredFieldValidator
								ValidationGroup="RestoreTestGroup"
								ControlToValidate="RestoreTestBackupSourceTimeRangeFrom"
								Text="<%[ Field required. ]%>"
								Display="Dynamic"
							>
								<prop:ClientSide.OnValidate>
									const el = document.getElementById('<%=$this->RestoreTestBackupSourceLatestBackupTimeRangeRadio->ClientID%>');
									sender.enabled = el.checked;
								</prop:ClientSide.OnValidate>
							</com:TRequiredFieldValidator>
							<com:TRegularExpressionValidator
								ValidationGroup="RestoreTestGroup"
								ControlToValidate="RestoreTestBackupSourceTimeRangeFrom"
								RegularExpression="\d{4}-\d{2}-\d{2}"
								Text="<%[ Invalid date format. ]%>"
								Display="Dynamic"
							>
								<prop:ClientSide.OnValidate>
									const el = document.getElementById('<%=$this->RestoreTestBackupSourceLatestBackupTimeRangeRadio->ClientID%>');
									sender.enabled = el.checked;
								</prop:ClientSide.OnValidate>
							</com:TRegularExpressionValidator>
						</div>
					</div>
					<div class="w3-row directive_field">
						<div class="w3-col w3-third"><label for="<%=$this->RestoreTestBackupSourceTimeRangeTo->ClientID%>" class="w3-margin-left"><%[ Date to ]%>:</label></div>
						<div class="w3-col w3-twothird">
							<com:TJuiDatePicker
								ID="RestoreTestBackupSourceTimeRangeTo"
								Options.dateFormat="yy-mm-dd"
								Options.changeYear="true"
								Options.changeMonth="true"
								Options.showAnim="fold"
								Style.Width="120px"
								CssClass="w3-input w3-border w3-show-inline-block"
								Attributes.placeholder="YYYY-MM-DD"
								/>
							<i class="fa-solid fa-asterisk w3-text-red opt_req"></i>
							<com:TRequiredFieldValidator
								ValidationGroup="RestoreTestGroup"
								ControlToValidate="RestoreTestBackupSourceTimeRangeTo"
								Text="<%[ Field required. ]%>"
								Display="Dynamic"
							>
								<prop:ClientSide.OnValidate>
									const el = document.getElementById('<%=$this->RestoreTestBackupSourceLatestBackupTimeRangeRadio->ClientID%>');
									sender.enabled = el.checked;
								</prop:ClientSide.OnValidate>
							</com:TRequiredFieldValidator>
							<com:TRegularExpressionValidator
								ValidationGroup="RestoreTestGroup"
								ControlToValidate="RestoreTestBackupSourceTimeRangeTo"
								RegularExpression="\d{4}-\d{2}-\d{2}"
								Text="<%[ Invalid date format. ]%>"
								Display="Dynamic"
							>
								<prop:ClientSide.OnValidate>
									const el = document.getElementById('<%=$this->RestoreTestBackupSourceLatestBackupTimeRangeRadio->ClientID%>');
									sender.enabled = el.checked;
								</prop:ClientSide.OnValidate>
							</com:TRegularExpressionValidator>
						</div>
					</div>
				</div>
				<div id="restore_test_window_backup_source_job_levels" style="display: none;">
					<div class="w3-row directive_field">
						<div class="w3-col w3-third"><label for="<%=$this->RestoreTestBackupSourceJobLevels->ClientID%>" class="w3-margin-left"><%[ Selected levels ]%>:</label></div>
						<div class="w3-col w3-twothird">
							<com:TActiveListBox
								ID="RestoreTestBackupSourceJobLevels"
								CssClass="w3-select w3-border"
								AutoPostBack="false"
								SelectionMode="Multiple"
								Rows="3"
							>
								<com:TListItem Value="F" Text="Full" />
								<com:TListItem Value="I" Text="Incremental" />
								<com:TListItem Value="D" Text="Differential" />
							</com:TActiveListBox>
							<i class="fa-solid fa-asterisk w3-text-red opt_req"></i>
							<com:TRequiredFieldValidator
								ValidationGroup="RestoreTestGroup"
								ControlToValidate="RestoreTestBackupSourceJobLevels"
								ErrorMessage="<%[ Field required. ]%>"
								ControlCssClass="field_invalid"
								Display="Dynamic"
							>
								<prop:ClientSide.OnValidate>
									const el = document.getElementById('<%=$this->RestoreTestBackupSourceLatestJobLevelsRadio->ClientID%>');
									sender.enabled = el.checked;
								</prop:ClientSide.OnValidate>
							</com:TRequiredFieldValidator>
							<p style="margin-top: 0;"><%[ Use CTRL + left-click to multiple item selection ]%></p>
						</div>
					</div>
				</div>
			</div>
			<div id="restore_test_window_backup_job_jobid" style="display: none">
				<div class="w3-row directive_field">
					<div class="w3-col w3-third"><label for="<%=$this->RestoreTestBackupJobJobId->ClientID%>" class="w3-margin-left"><%[ Job identifier (JobId) ]%>:</label></div>
					<div class="w3-col w3-twothird">
						<com:TActiveTextBox
							ID="RestoreTestBackupJobJobId"
							AutoPostBack="false"
							AutoTrim="true"
							MaxLength="20"
							CssClass="w3-input w3-border w3-show-inline-block"
							Attributes.placeholder="<%[ ex: 123321 ]%>"
							Attributes.onkeydown="if (event.key == 'Enter') { $('#<%=$this->RestoreTestBackupJobJobIdCheck->ClientID%>').click(); }"
							Width="120px"
						/>
						<i class="fa-solid fa-asterisk w3-text-red opt_req"></i>
						<com:TRequiredFieldValidator
							ValidationGroup="RestoreTestGroup"
							ControlToValidate="RestoreTestBackupJobJobId"
							ErrorMessage="<%[ Field required. ]%>"
							ControlCssClass="field_invalid"
							Display="Dynamic"
						>
							<prop:ClientSide.OnValidate>
								const el = document.getElementById('<%=$this->RestoreTestBackupJobSingleJobIdRadio->ClientID%>');
								sender.enabled = el.checked;
							</prop:ClientSide.OnValidate>
						</com:TRequiredFieldValidator>
						<com:TRegularExpressionValidator
							ValidationGroup="RestoreTestGroup"
							RegularExpression="\d+"
							ControlToValidate="RestoreTestBackupJobJobId"
							ErrorMessage="<%[ Invalid value. ]%>"
							ControlCssClass="field_invalid"
							Display="Dynamic"
						>
							<prop:ClientSide.OnValidate>
								const el = document.getElementById('<%=$this->RestoreTestBackupJobSingleJobIdRadio->ClientID%>');
								sender.enabled = el.checked;
							</prop:ClientSide.OnValidate>
						</com:TRegularExpressionValidator>
						<com:TActiveLinkButton
							ID="RestoreTestBackupJobJobIdCheck"
							CausesValidation="false"
							OnCallback="checkBackupJobId"
							CssClass="w3-button w3-green w3-tiny w3-margin-left"
						>
							<i class="fa-solid fa-sync-alt"></i> &nbsp;<%[ Check ]%>
						</com:TActiveLinkButton>
						<com:TActivePanel
							ID="RestoreTestBackupJobJobIdInfo"
							CssClass="w3-margin-top"
							Display="None"
						>
							<com:TActiveLabel ID="RestoreTestBackupJobJobIdError" CssClass="w3-text-red" Display="None" />
							<div id="restore_test_backup_jobid_details">
								<div><strong class="w3-quarter"><%[ Job name ]%>:</strong> <com:TActiveLabel ID="RestoreTestBackupJobJobIdName" /></div>
								<div><strong class="w3-quarter"><%[ Job type ]%>:</strong> <com:TActiveLabel ID="RestoreTestBackupJobJobIdType" /></div>
								<div><strong class="w3-quarter"><%[ Job status ]%>:</strong> <com:TActiveLabel ID="RestoreTestBackupJobJobIdStatus" /></div>
								<div><strong class="w3-quarter"><%[ Client ]%>:</strong> <com:TActiveLabel ID="RestoreTestBackupJobJobIdClient" /></div>
								<div><strong class="w3-quarter"><%[ Level ]%>:</strong> <com:TActiveLabel ID="RestoreTestBackupJobJobIdLevel" /></div>
								<div><strong class="w3-quarter"><%[ Start time ]%>:</strong> <com:TActiveLabel ID="RestoreTestBackupJobJobIdStartTime" /></div>
								<com:TActiveLabel
									ID="RestoreTestBackupJobJobIdWarning"
									CssClass="w3-small w3-text-orange"
									Display="None"
								/>
							</div>
						</com:TActivePanel>
					</div>
				</div>
			</div>
			<h4 class="w3-border-bottom w3-padding"><%[ Verification ]%></h4>
			<p><%[ Choose how restored data should be checked. ]%></p>
			<div>
				<div class="w3-row directive_field">
					<div class="w3-col w3-third"><%[ Verification method ]%>:</div>
					<div class="w3-twothird">
						<com:TActiveRadioButton
							ID="RestoreTestVerificationMethodRulesRadio"
							CssClass="w3-check w3-border"
							AutoPostBack="false"
							GroupName="RestoreVerificationMethod"
							Checked="true"
							Attributes.onclick="oRestoreTests.set_verification_method('rules');"
						/> <label for="<%=$this->RestoreTestVerificationMethodRulesRadio->ClientID%>"><%[ Rule-based verification ]%></label>
						<p class="w3-small" style="margin-bottom: 0;"><%[ Use Bacularis verification rules and checker plugins. ]%></p>
						<p class="w3-small" style="margin: 0;"><%[ The selected backup is treated as a backup point in time. Bacularis uses the complete backup chain for that point, while the "What to restore" option below defines which data is actually restored. ]%></p>
						<p class="w3-small" style="margin: 0;"><%[ Works with safe restore destinations using a restore prefix. ]%></p>
					</div>
				</div>
				<div class="w3-row directive_field w3-margin-bottom">
					<div class="w3-col w3-third">&nbsp;</div>
					<div class="w3-twothird">
						<com:TActiveRadioButton
							ID="RestoreTestVerificationMethodVerifyJobRadio"
							CssClass="w3-check w3-border"
							AutoPostBack="false"
							GroupName="RestoreVerificationMethod"
							Attributes.onclick="oRestoreTests.set_verification_method('verify_job');"
						/> <label for="<%=$this->RestoreTestVerificationMethodVerifyJobRadio->ClientID%>"><%[ Native Bacula Verify job ]%></label>
						<p class="w3-small" style="margin-bottom: 0;"><%[ Use a Bacula Verify job with level DiskToCatalog. ]%></p>
						<p class="w3-small" style="margin: 0;"><%[ This method verifies the selected backup one-to-one against the Bacula catalog. ]%></p>
						<p class="w3-small" style="margin-top: 0;"><%[ Requires an isolated destination and original restore paths. ]%></p>
					</div>
				</div>
			</div>
			<div id="restore_test_window_verification_rule_sets" style="display: <%=$this->RestoreTestVerificationMethodRulesRadio->Checked ? 'block' : 'none'%>">
				<div class="w3-row directive_field">
					<div class="w3-col w3-third"><label for="<%=$this->RestoreTestVerificationRules->ClientID%>" class="w3-margin-left"><%[ Verification rules ]%>:</label></div>
					<div class="w3-col w3-twothird">
						<com:TActiveListBox
							ID="RestoreTestVerificationRules"
							CssClass="w3-select w3-border"
							AutoPostBack="false"
							SelectionMode="Multiple"
							Rows="5"
						/>
						<i class="fa-solid fa-asterisk w3-text-red opt_req"></i>
						<com:TRequiredFieldValidator
							ValidationGroup="RestoreTestGroup"
							ControlToValidate="RestoreTestVerificationRules"
							ErrorMessage="<%[ Field required. ]%>"
							ControlCssClass="field_invalid"
							Display="Dynamic"
						>
							<prop:ClientSide.OnValidate>
								const el = document.getElementById('<%=$this->RestoreTestVerificationMethodRulesRadio->ClientID%>');
								sender.enabled = el.checked;
							</prop:ClientSide.OnValidate>
						</com:TRequiredFieldValidator>
						<p style="margin-top: 0;"><%[ Use CTRL + left-click to multiple item selection ]%></p>
					</div>
				</div>
			</div>
			<div id="restore_test_window_verification_verify_job" style="display: <%=$this->RestoreTestVerificationMethodVerifyJobRadio->Checked ? 'block' : 'none'%>">
				<div class="w3-row directive_field">
					<div class="w3-col w3-third"><label for="<%=$this->RestoreTestVerificationVerifyJob->ClientID%>" class="w3-margin-left"><%[ Bacula Verify job ]%>:</label></div>
					<div class="w3-twothird">
						<com:TActiveDropDownList
							ID="RestoreTestVerificationVerifyJob"
							CssClass="w3-select w3-border"
							AutoPostBack="false"
							Width="400px"
							PromptText="<%[ Select Bacula verify job ]%>"
							PromptValue=" "
						/>
						<i class="fa-solid fa-asterisk w3-text-red opt_req"></i>
						<com:TRequiredFieldValidator
							ValidationGroup="RestoreTestGroup"
							ControlToValidate="RestoreTestVerificationVerifyJob"
							ErrorMessage="<%[ Field required. ]%>"
							ControlCssClass="field_invalid"
							Display="Dynamic"
						>
							<prop:ClientSide.OnValidate>
								const el = document.getElementById('<%=$this->RestoreTestVerificationMethodVerifyJobRadio->ClientID%>');
								sender.enabled = el.checked;
							</prop:ClientSide.OnValidate>
						</com:TRequiredFieldValidator>
					</div>
				</div>
			</div>
			<h4 class="w3-border-bottom w3-padding"><%[ Restore plan ]%></h4>
			<p><%[ Choose what to restore, where to restore it and how the test should run. ]%></p>
			<div id="restore_test_window_restore_scope">
				<div class="w3-row directive_field">
					<div class="w3-col w3-third"><label><%[ What to restore ]%></label></div>
					<div class="w3-twothird">
						<div id="restore_test_window_restore_scope_options">
							<com:TActiveRadioButton
								ID="RestoreTestRestoreScopeEntireBackup"
								CssClass="w3-check w3-border"
								AutoPostBack="false"
								Checked="true"
								GroupName="RestoreTestRestoreScope"
							/> <label for="<%=$this->RestoreTestRestoreScopeEntireBackup->ClientID%>"><%[ Entire backup restore point ]%></label>
							<p class="w3-small" style="margin-bottom: 0;"><%[ Restore all data from the selected backup point in time, including the complete backup chain required for that restore point. ]%></p>
							<div class="directive_field">
								<com:TActiveRadioButton
									ID="RestoreTestRestoreScopeFilesRequiredByVerificationRules"
									CssClass="w3-check w3-border"
									AutoPostBack="false"
									GroupName="RestoreTestRestoreScope"
								/> <label for="<%=$this->RestoreTestRestoreScopeFilesRequiredByVerificationRules->ClientID%>"><%[ All paths from verification rules ]%></label>
								<p class="w3-small" style="margin-bottom: 0;"><%[ Restore all paths defined in selected verification rules. Bacularis uses the complete backup chain for the selected backup point, but restores only those paths. ]%></p>
							</div>
							<div class="directive_field">
								<com:TActiveRadioButton
									ID="RestoreTestRestoreScopeRandomSample"
									CssClass="w3-check w3-border"
									AutoPostBack="false"
									GroupName="RestoreTestRestoreScope"
								/> <label for="<%=$this->RestoreTestRestoreScopeRandomSample->ClientID%>"><%[ Random paths from verification rules ]%></label>
								<p class="w3-small" style="margin-bottom: 0;"><%[ Restore a random subset of paths defined in selected verification rules. Bacularis uses the complete backup chain for the selected backup point, but restores only the randomly selected paths. ]%></p>
							</div>
						</div>
						<div id="restore_test_window_restore_scope_native_verify" style="display: none">
							<strong><%[ Selected backup only ]%></strong>
							<p class="w3-small" style="margin-bottom: 0;"><%[ Native Bacula Verify verifies the selected backup one-to-one against the Bacula catalog. ]%></p>
							<p class="w3-small" style="margin-top: 0;"><%[ The full backup chain is not restored for this method. ]%></p>
						</div>
					</div>
				</div>
			</div>
			<p><%[ Where to restore ]%></p>
			<div id="restore_test_restore_destination">
				<div class="w3-row directive_field">
					<div class="w3-col w3-third"><label for="<%=$this->RestoreTestRestoreDestinationDestination->ClientID%>" class="w3-margin-left"><%[ Destination ]%>:</label></div>
					<div class="w3-twothird">
						<com:TActiveDropDownList
							ID="RestoreTestRestoreDestinationDestination"
							CssClass="w3-select w3-border"
							AutoPostBack="false"
							PromptText="<%[ Select restore destination ]%>"
							PromptValue=" "
						/>
						<i class="fa-solid fa-asterisk w3-text-red opt_req"></i>
						<com:TRequiredFieldValidator
							ValidationGroup="RestoreTestGroup"
							ControlToValidate="RestoreTestRestoreDestinationDestination"
							ErrorMessage="<%[ Field required. ]%>"
							ControlCssClass="field_invalid"
							Display="Dynamic"
						/>
					</div>
				</div>
			</div>
			<p><%[ When to run ]%></p>
			<div id="restore_test_restore_policy">
				<div class="w3-row directive_field">
					<div class="w3-col w3-third"><label for="<%=$this->RestoreTestRestorePolicyPolicy->ClientID%>" class="w3-margin-left"><%[ Policy ]%>:</label></div>
					<div class="w3-twothird">
						<com:TActiveDropDownList
							ID="RestoreTestRestorePolicyPolicy"
							CssClass="w3-select w3-border"
							AutoPostBack="false"
							PromptText="<%[ Select restore policy ]%>"
							PromptValue=" "
						/>
						<i class="fa-solid fa-asterisk w3-text-red opt_req"></i>
						<com:TRequiredFieldValidator
							ValidationGroup="RestoreTestGroup"
							ControlToValidate="RestoreTestRestorePolicyPolicy"
							ErrorMessage="<%[ Field required. ]%>"
							ControlCssClass="field_invalid"
							Display="Dynamic"
						/>
					</div>
				</div>
			</div>
			<p><%[ Bacula restore job ]%></p>
			<div class="w3-row directive_field">
				<div class="w3-col w3-third"><label for="<%=$this->RestoreTestRestoreJob->ClientID%>" class="w3-margin-left"><%[ Restore job ]%>:</label></div>
				<div class="w3-twothird">
					<com:TActiveDropDownList
						ID="RestoreTestRestoreJob"
						CssClass="w3-select w3-border"
						PromptText="<%[ Select Bacula restore job ]%>"
						PromptValue=" "
					/>
					<i class="fa-solid fa-asterisk w3-text-red opt_req"></i>
					<com:TRequiredFieldValidator
						ValidationGroup="RestoreTestGroup"
						ControlToValidate="RestoreTestRestoreJob"
						ErrorMessage="<%[ Field required. ]%>"
						ControlCssClass="field_invalid"
						Display="Dynamic"
					/>
				</div>
			</div>
			<div id="restore_test_window_advanced_options_container" style="display: none;">
				<i class="fa-solid fa-wrench"></i> &nbsp;<a href="javascript:void(0)" onclick="$('#restore_test_window_advanced_options').toggle('fast');"><%[ Advanced options ]%></a>
				<div id="restore_test_window_advanced_options" style="display: none">
					<h5 class="w3-border-bottom w3-padding"><%[ Web access interface ]%></h5>
					<p><%[ These settings define how the Bacula Director script connects to Bacularis web interface when starting an automatic restore test. The default values match the standard Bacularis installation settings. If Bacularis uses HTTPS, a different host, or a different port, update these values accordingly. ]%></p>
					<div class="w3-row directive_field">
						<div class="w3-col w3-third"><label for="<%=$this->RestoreTestWebProtocol->ClientID%>" class="w3-margin-left"><%[ Protocol ]%>:</label></div>
						<div class="w3-twothird">
							<com:TActiveDropDownList
								ID="RestoreTestWebProtocol"
								CssClass="w3-select w3-border"
								AutoPostBack="false"
								Width="150px"
							>
								<com:TListItem Value="http" Text="HTTP" />
								<com:TListItem Value="https" Text="HTTPS" />
							</com:TActiveDropDownList>
							<i class="fa-solid fa-asterisk w3-text-red opt_req"></i>
							<com:TRequiredFieldValidator
								ValidationGroup="RestoreTestGroup"
								ControlToValidate="RestoreTestWebProtocol"
								ErrorMessage="<%[ Field required. ]%>"
								ControlCssClass="field_invalid"
								Display="Dynamic"
							/>
						</div>
					</div>
					<div class="w3-row directive_field">
						<div class="w3-col w3-third"><label for="<%=$this->RestoreTestWebAddress->ClientID%>" class="w3-margin-left"><%[ Address ]%>:</label></div>
						<div class="w3-twothird">
							<com:TActiveTextBox
								ID="RestoreTestWebAddress"
								AutoPostBack="false"
								MaxLength="255"
								CssClass="w3-input w3-border w3-show-inline-block"
								Attributes.placeholder="ex: 127.0.0.1"
								Width="250px"
							/>
							<i class="fa-solid fa-asterisk w3-text-red opt_req"></i>
							<com:TRequiredFieldValidator
								ValidationGroup="RestoreTestGroup"
								ControlToValidate="RestoreTestWebAddress"
								ErrorMessage="<%[ Field required. ]%>"
								ControlCssClass="field_invalid"
								Display="Dynamic"
							/>
						</div>
					</div>
					<div class="w3-row directive_field">
						<div class="w3-col w3-third"><label for="<%=$this->RestoreTestWebPort->ClientID%>" class="w3-margin-left"><%[ Port ]%>:</label></div>
						<div class="w3-twothird">
							<com:TActiveTextBox
								ID="RestoreTestWebPort"
								AutoPostBack="false"
								MaxLength="5"
								CssClass="w3-input w3-border w3-show-inline-block"
								Attributes.placeholder="ex: 9097"
								Width="120px"
							/>
							<i class="fa-solid fa-asterisk w3-text-red opt_req"></i>
							<com:TRequiredFieldValidator
								ValidationGroup="RestoreTestGroup"
								ControlToValidate="RestoreTestWebPort"
								ErrorMessage="<%[ Field required. ]%>"
								ControlCssClass="field_invalid"
								Display="Dynamic"
							/>
							<com:TRegularExpressionValidator
								ValidationGroup="RestoreTestGroup"
								RegularExpression="\d+"
								ControlToValidate="RestoreTestWebPort"
								ErrorMessage="<%[ Invalid value. ]%>"
								ControlCssClass="field_invalid"
								Display="Dynamic"
							/>
						</div>
					</div>
					<div class="w3-row directive_field">
						<div class="w3-col w3-third"><label for="<%=$this->RestoreTestWebAllowedIPs->ClientID%>" class="w3-margin-left"><%[ Access for IP addresses ]%>:</label></div>
						<div class="w3-twothird">
							<com:TActiveTextBox
								ID="RestoreTestWebAllowedIPs"
								AutoPostBack="false"
								MaxLength="255"
								CssClass="w3-input w3-border w3-show-inline-block"
								Attributes.placeholder="ex: 127.0.0.1, 192.168.1.2"
								Width="250px"
							/>
						</div>
					</div>
					<h5 class="w3-border-bottom w3-padding"><%[ Admin job directives ]%></h5>
					<p><%[ These directives are used to create Bacula admin job that runs restore verification. They are required by the admin job, but not used because of nature of admin jobs. ]%></p>
					<div>
						<div class="w3-row directive_field">
							<div class="w3-col w3-third"><label for="<%=$this->RestoreTestClient->ClientID%>" class="w3-margin-left"><%[ Client ]%>:</label></div>
							<div class="w3-twothird">
								<com:TActiveDropDownList
									ID="RestoreTestClient"
									CssClass="w3-select w3-border"
									AutoPostBack="false"
									Width="400px"
								/>
								<i class="fa-solid fa-asterisk w3-text-red opt_req"></i>
								<com:TRequiredFieldValidator
									ValidationGroup="RestoreTestGroup"
									ControlToValidate="RestoreTestClient"
									ErrorMessage="<%[ Field required. ]%>"
									ControlCssClass="field_invalid"
									Display="Dynamic"
								/>
							</div>
						</div>
						<div class="w3-row directive_field">
							<div class="w3-col w3-third"><label for="<%=$this->RestoreTestFileset->ClientID%>" class="w3-margin-left"><%[ FileSet ]%>:</label></div>
							<div class="w3-twothird">
								<com:TActiveDropDownList
									ID="RestoreTestFileset"
									CssClass="w3-select w3-border"
									AutoPostBack="false"
									Width="400px"
								/>
								<i class="fa-solid fa-asterisk w3-text-red opt_req"></i>
								<com:TRequiredFieldValidator
									ValidationGroup="RestoreTestGroup"
									ControlToValidate="RestoreTestFileset"
									ErrorMessage="<%[ Field required. ]%>"
									ControlCssClass="field_invalid"
									Display="Dynamic"
								/>
							</div>
						</div>
						<div class="w3-row directive_field">
							<div class="w3-col w3-third"><label for="<%=$this->RestoreTestPool->ClientID%>" class="w3-margin-left"><%[ Pool ]%>:</label></div>
							<div class="w3-twothird">
								<com:TActiveDropDownList
									ID="RestoreTestPool"
									CssClass="w3-select w3-border"
									AutoPostBack="false"
									Width="400px"
								/>
								<i class="fa-solid fa-asterisk w3-text-red opt_req"></i>
								<com:TRequiredFieldValidator
									ValidationGroup="RestoreTestGroup"
									ControlToValidate="RestoreTestPool"
									ErrorMessage="<%[ Field required. ]%>"
									ControlCssClass="field_invalid"
									Display="Dynamic"
								/>
							</div>
						</div>
						<div class="w3-row directive_field">
							<div class="w3-col w3-third"><label for="<%=$this->RestoreTestStorage->ClientID%>" class="w3-margin-left"><%[ Storage ]%>:</label></div>
							<div class="w3-twothird">
								<com:TActiveDropDownList
									ID="RestoreTestStorage"
									CssClass="w3-select w3-border"
									AutoPostBack="false"
									Width="400px"
								/>
								<i class="fa-solid fa-asterisk w3-text-red opt_req"></i>
								<com:TRequiredFieldValidator
									ValidationGroup="RestoreTestGroup"
									ControlToValidate="RestoreTestStorage"
									ErrorMessage="<%[ Field required. ]%>"
									ControlCssClass="field_invalid"
									Display="Dynamic"
								/>
							</div>
						</div>
						<div class="w3-row directive_field">
							<div class="w3-col w3-third"><label for="<%=$this->RestoreTestMessages->ClientID%>" class="w3-margin-left"><%[ Messages ]%>:</label></div>
							<div class="w3-twothird">
								<com:TActiveDropDownList
									ID="RestoreTestMessages"
									CssClass="w3-select w3-border"
									AutoPostBack="false"
									Width="400px"
								/>
								<i class="fa-solid fa-asterisk w3-text-red opt_req"></i>
								<com:TRequiredFieldValidator
									ValidationGroup="RestoreTestGroup"
									ControlToValidate="RestoreTestMessages"
									ErrorMessage="<%[ Field required. ]%>"
									ControlCssClass="field_invalid"
									Display="Dynamic"
								/>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<footer class="w3-container w3-center">
			<button type="button" class="w3-button w3-red" onclick="document.getElementById('restore_test_window').style.display = 'none';"><i class="fa-solid fa-times"></i> &nbsp;<%[ Cancel ]%></button>
			<com:TActiveLinkButton
				ID="RestoreTestSave"
				ValidationGroup="RestoreTestGroup"
				CausesValidation="true"
				OnCallback="saveRestoreTest"
				CssClass="w3-button w3-section w3-green w3-padding"
				ClientSide.OnLoading="oRestoreTests.show_save_loader(true);"
				ClientSide.OnComplete="oRestoreTests.show_save_loader(false);"
			>
				<i class="fa fa-save"></i> &nbsp;<%[ Save ]%>
			</com:TActiveLinkButton>
			<i id="restore_test_save_loader" class="fa-solid fa-sync-alt w3-spin w3-margin-left" style="visibility: hidden;"></i>
		</footer>
	</div>
	<com:TActiveHiddenField ID="RestoreTestWindowType" />
</div>
