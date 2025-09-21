<div>
	<div class="w3-section">
		<button type="button" class="w3-button w3-green" onclick="oWebAccessResource.load_window();">
			<i class="fa-solid fa-plus"></i> &nbsp;<%[ Add web access ]%>
		</button>
	</div>
	<table id="web_access_resource_list_table" class="display w3-table w3-striped w3-hoverable w3-margin-bottom selectable" style="width: 100%">
		<thead>
			<tr>
				<th></th>
				<th class="w3-center"><%[ API hosts ]%></th>
				<th class="w3-center"><%[ Component type ]%></th>
				<th class="w3-center"><%[ Component name ]%></th>
				<th class="w3-center"><%[ Resource type ]%></th>
				<th class="w3-center"><%[ Resource name ]%></th>
				<th class="w3-center"><%[ Time access ]%></th>
				<th class="w3-center"><%[ Usage access ]%></th>
				<th class="w3-center"><%[ Source access ]%></th>
				<th class="w3-center"><%[ Create time ]%></th>
				<th class="w3-center"><%[ Access time ]%></th>
				<th class="w3-center"><%[ Resource action ]%></th>
				<th class="w3-center"><%[ Resource params ]%></th>
				<th class="w3-center"><%[ Access URL ]%></th>
				<th class="w3-center"><%[ Stats ]%></th>
				<th class="w3-center"><%[ Action ]%></th>
			</tr>
		</thead>
		<tbody id="web_access_resource_list_body"></tbody>
		<tfoot>
			<tr>
				<th></th>
				<th class="w3-center"><%[ API hosts ]%></th>
				<th class="w3-center"><%[ Component type ]%></th>
				<th class="w3-center"><%[ Component name ]%></th>
				<th class="w3-center"><%[ Resource type ]%></th>
				<th class="w3-center"><%[ Resource name ]%></th>
				<th class="w3-center"><%[ Time access ]%></th>
				<th class="w3-center"><%[ Usage access ]%></th>
				<th class="w3-center"><%[ Source access ]%></th>
				<th class="w3-center"><%[ Create time ]%></th>
				<th class="w3-center"><%[ Access time ]%></th>
				<th class="w3-center"><%[ Resource action ]%></th>
				<th class="w3-center"><%[ Resource params ]%></th>
				<th class="w3-center"><%[ Access URL ]%></th>
				<th class="w3-center"><%[ Stats ]%></th>
				<th class="w3-center"><%[ Action ]%></th>
			</tr>
		</tfoot>
	</table>
</div>
<com:TCallback ID="LoadWebAccessResourceList" OnCallback="TemplateControl.loadWebAccessResourceList" />
<com:TCallback ID="LoadWebAccessResourceWindow" OnCallback="TemplateControl.loadWebAccessResourceWindow">
	<prop:ClientSide.OnLoading>
		oWebAccessResource.show_action_loader(true);
	</prop:ClientSide.OnLoading>
	<prop:ClientSide.OnComplete>
		oWebAccessResource.show_action_loader(false);
	</prop:ClientSide.OnComplete>
</com:TCallback>
<com:TCallback ID="RemoveWebAccessResources" OnCallback="TemplateControl.removeWebAccessResources" />
<script>
var oWebAccessResourceList = {
	ids: {
		web_access_list: 'web_access_resource_list_table'
	},
	actions: [
		{
			action: 'remove',
			label: '<%[ Remove ]%>',
			value: 'token',
			callback: <%=$this->RemoveWebAccessResources->ActiveControl->Javascript%>
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
			oWebAccessResourceList.set_filters(this.table);
			this.table_toolbar.style.display = 'none';
		}
	},
	set_events: function() {
		document.getElementById(this.ids.web_access_list).addEventListener('click', function(e) {
			$(function() {
				const wa = (this.table.rows({selected: true}).data().length > 0) ? 'show' : 'hide';
				$(this.table_toolbar).animate({
					width: wa
				}, 'fast');
			}.bind(this));
		}.bind(this));
	},
	set_table: function() {
		this.table = $('#' + this.ids.web_access_list).DataTable({
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
				{data: 'api_hosts'},
				{
					data: 'component_type',
					visible: false
				},
				{
					data: 'component_name',
					visible: false
				},
				{
					data: 'resource_type',
					visible: false
				},
				{
					data: 'resource_name',
					visible: false
				},
				{
					data: 'time_method',
					render: function (data, type, row) {
						let ret = data;
						if (type == 'display' || type == 'filter') {
							if (data == '<%=WebAccessConfig::WEB_ACCESS_TIME_METHOD_UNLIMITED%>') {
								ret = '<%[ No limit ]%>';
							} else if (data == '<%=WebAccessConfig::WEB_ACCESS_TIME_METHOD_FOR_DAYS%>') {
								ret = '<%[ For X days ]%>';
							} else if (data == '<%=WebAccessConfig::WEB_ACCESS_TIME_METHOD_DATE_RANGE%>') {
								ret = '<%[ Date range ]%>';
							}
						}
						return ret;
					}
				},
				{
					data: 'usage_method',
					render: function (data, type, row) {
						let ret = data;
						if (type == 'display' || type == 'filter') {
							if (data == '<%=WebAccessConfig::WEB_ACCESS_USAGE_METHOD_UNLIMITED%>') {
								ret = '<%[ No limit ]%>';
							} else if (data == '<%=WebAccessConfig::WEB_ACCESS_USAGE_METHOD_ONE_USE%>') {
								ret = '<%[ One-time use ]%>';
							} else if (data == '<%=WebAccessConfig::WEB_ACCESS_USAGE_METHOD_NUMBER_USES%>') {
								ret = '<%[ Numer of use ]%>';
							}
						}
						return ret;
					}
				},
				{
					data: 'source_access',
					render: function (data, type, row) {
						let ret = data;
						if (type == 'display' || type == 'filter') {
							if (data == '<%=WebAccessConfig::WEB_ACCESS_SOURCE_METHOD_NO_RESTRICTION%>') {
								ret = '<%[ No limit ]%>';
							} else if (data == '<%=WebAccessConfig::WEB_ACCESS_SOURCE_METHOD_IP_RESTRICTION%>') {
								ret = '<%[ IP addr. restriction ]%>';
							}
						}
						return ret;
					}
				},
				{
					data: 'create_time',
					render: function (data, type, row) {
						let ret = data;
						if (type == 'display') {
							const ts = parseInt(data, 10) * 1000;
							ret = Units.format_date(ts, true);
						}
						return ret;
					},
					visible: false
				},
				{
					data: 'access_time',
					render: function (data, type, row) {
						let ret = data;
						if (type == 'display') {
							if (data == 0) {
								ret = '<%[ Not used yet ]%>';
							} else {
								const ts = parseInt(data, 10) * 1000;
								ret = Units.format_date(ts, true);
							}
						}
						return ret;
					},
					visible: false
				},
				{data: 'action'},
				{
					data: 'action_params',
					render: function (data, type, row) {
						const cont = document.createElement('SPAN');
						cont.title = '<%[ See resource params ]%>';
						const img = document.createElement('I');
						img.classList.add('fa-solid', 'fa-fw', 'fa-braille');
						const txt = document.createElement('SPAN');
						txt.textContent = ' <%[ Params ]%>';
						cont.setAttribute('onclick', 'oWebAccessParams.add_params(' + JSON.stringify(data) + '); oWebAccessParams.show_window(true);');

						cont.appendChild(img);
						cont.appendChild(txt);
						return cont.outerHTML;
					}
				},
				{
					data: 'token',
					render: function (data, type, row) {
						const link = [
							window.location.origin,
							'web',
							'access',
							data
						].join('/');
						const cont = document.createElement('SPAN');
						cont.title = '<%[ Copy to clipboard ]%>';
						const img = document.createElement('I');
						img.classList.add('fa-solid', 'fa-fw', 'fa-copy');
						const txt = document.createElement('SPAN');
						txt.textContent = ' <%[ Copy ]%>';
						cont.setAttribute('onclick', 'oWebAccessResourceList.copy_link.call(this, "' + link + '");');

						cont.appendChild(img);
						cont.appendChild(txt);
						return cont.outerHTML;
					}
				},
				{
					data: 'access_time',
					render: function (data, type, row) {
						const cont = document.createElement('SPAN');
						cont.title = '<%[ See statistics ]%>';
						const img = document.createElement('I');
						img.classList.add('fa-solid', 'fa-fw', 'fa-chart-simple');
						const txt = document.createElement('SPAN');
						txt.textContent = ' <%[ Stats ]%>';

						const params = {};

						// Create time
						const ct = parseInt(row.create_time, 10) * 1000;
						let ret = Units.format_date(ct, true);
						params['<%[ Create time ]%>'] = ret;

						// Access time
						if (row.access_time >  0) {
							const at = parseInt(row.access_time, 10) * 1000;
							ret = Units.format_date(at, true);
						} else {
							ret = '<%[ Not used yet ]%>';
						}
						params['<%[ Access time ]%>'] = ret;

						// Time method
						if (row.time_method == '<%=WebAccessConfig::WEB_ACCESS_TIME_METHOD_UNLIMITED%>') {
							ret = '<%[ No limit ]%>';
						} else if (row.time_method == '<%=WebAccessConfig::WEB_ACCESS_TIME_METHOD_FOR_DAYS%>') {
							ret = '<%[ For X days ]%>';
						} else if (row.time_method == '<%=WebAccessConfig::WEB_ACCESS_TIME_METHOD_DATE_RANGE%>') {
							ret = '<%[ Date range ]%>';
						}
						params['<%[ Time access ]%>'] = ret;

						if (row.time_method == '<%=WebAccessConfig::WEB_ACCESS_TIME_METHOD_UNLIMITED%>') {
							params['<%[ Valid from ]%>'] = '-';
							params['<%[ Valid to ]%>'] = '-';
							params['<%[ Number of days ]%>'] = '-';
							params['<%[ Valid for ]%>'] = '-';
							params['<%[ Time state ]%>'] = '-';
						} else {
							const ft = parseInt(row.time_from, 10);
							ret = Units.format_date(ft * 1000, true);
							params['<%[ Valid from ]%>'] = ret;

							const tt = parseInt(row.time_to, 10);
							ret = Units.format_date(tt * 1000, true);
							params['<%[ Valid to ]%>'] = ret;

							const nb_days = (tt - ft) + 1;
							const nod = Units.format_time_duration(nb_days, true);
							params['<%[ Number of days ]%>'] = nod;

							const now_ms = (new Date()).getTime();
							const now_s = parseInt(now_ms / 1000);
							const valid_diff = (now_s >= ft && now_s < tt) ? (tt - now_s) : '-';
							const valid_for = Units.format_time_duration(valid_diff, true);
							params['<%[ Valid for ]%>'] = valid_for;

							const time_state = valid_diff > 0;
							params['<%[ Time state ]%>'] = time_state ? 'ok/<%[ Valid ]%>' : 'error/<%[ Expired ]%>';
						}

						// Usage method
						if (row.usage_method == '<%=WebAccessConfig::WEB_ACCESS_USAGE_METHOD_UNLIMITED%>') {
							ret = '<%[ No limit ]%>';
						} else if (row.usage_method == '<%=WebAccessConfig::WEB_ACCESS_USAGE_METHOD_ONE_USE%>') {
							ret = '<%[ One-time use ]%>';
						} else if (row.usage_method == '<%=WebAccessConfig::WEB_ACCESS_USAGE_METHOD_NUMBER_USES%>') {
							ret = '<%[ Numer of use ]%>';
						}
						params['<%[ Usage access ]%>'] = ret;
						const um = parseInt(row.usage_max, 10);
						const  ul = parseInt(row.usage_left, 10);
						params['<%[ Max. usage ]%>'] = um == -1 ? '-' : um;
						params['<%[ Usage left ]%>'] = ul == -1 ? '-' : ul;

						const usage_state = (ul > -1) ? ul > 0 : true;
						if (um == -1) {
							params['<%[ Usage state ]%>'] = '-';
						} else {
							params['<%[ Usage state ]%>'] = usage_state ? 'ok/<%[ Valid ]%>' : 'error/<%[ Exhausted use ]%>';
						}


						cont.setAttribute('onclick', 'oWebAccessStats.add_params(' + JSON.stringify(params) + '); oWebAccessStats.show_window(true);');

						cont.appendChild(img);
						cont.appendChild(txt);
						return cont.outerHTML;
					}
				},
				{
					data: 'token',
					render: function (data, type, row) {
						let btns = '';

						// Edit button
						const span = document.createElement('SPAN');
						const access_btn = document.createElement('BUTTON');
						access_btn.className = 'w3-button w3-green';
						access_btn.type = 'button';
						const i = document.createElement('I');
						i.className = 'fa-solid fa-edit';
						const label = document.createTextNode(' <%[ Edit ]%>');
						access_btn.appendChild(i);
						access_btn.innerHTML += '&nbsp';
						access_btn.appendChild(label);
						access_btn.setAttribute('onclick', 'oWebAccessResource.load_window("' + data + '")');
						span.appendChild(access_btn);
						span.style.marginRight = '5px';
						btns += span.outerHTML;

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
				className: 'dtr-control',
				orderable: false,
				targets: 0
			},
			{
				className: "dt-center",
				targets: [ 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15 ]
			}],
			select: {
				style:    'os',
				selector: 'td:not(:last-child):not(:first-child):not(:nth-last-child(2)):not(:nth-last-child(3)):not(:nth-last-child(4))',
				blurable: false
			},
			order: [1, 'asc'],
			initComplete: function () {
				oWebAccessResourceList.set_filters(this.api());
			}
		});
	},
	set_filters: function(api) {
		api.columns([1]).every(function () {
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
			column.cells('', column[0]).render('display').unique().sort().each(function(d, j) {
				if (column.search() == '^' + dtEscapeRegex(d) + '$') {
					select.append('<option value="' + d + '" selected>' + d + '</option>');
				} else if(d) {
					select.append('<option value="' + d + '">' + d + '</option>');
				}
			});
		});
	},
	set_bulk_actions: function() {
		this.table_toolbar = get_table_toolbar(this.table, this.actions, {
			actions: '<%[ Select action ]%>',
			ok: '<%[ OK ]%>'
		});
	},
	copy_link: function(link) {
		copy_to_clipboard(link);
		const img = this.querySelector("I");
		img.classList.remove('fa-copy');
		img.classList.add('fa-check');
		setTimeout(() => {
			img.classList.remove('fa-check');
			img.classList.add('fa-copy');
		}, 1000);
	}
};
var oWebAccessResource = {
	ids: {
		win: 'web_access_resource_window',
		title_add: 'web_access_resource_window_title_add',
		title_edit: 'web_access_resource_window_title_edit',
		acc_x_days: 'web_access_resource_access_method_x_days',
		acc_from_to: 'web_access_resource_access_method_from_to_date',
		acc_number_use: 'web_access_resource_access_method_x_number_use',
		run_job_action: '<%=$this->WebAccessResourceActionName->ClientID%>',
		job_params_cont: 'run_job_action_params_line_cont',
		run_job_params: 'run_job_action_params_line',
		time_unlimited: '<%=$this->WebAccessResourceAccessMethodAllTheTime->ClientID%>',
		time_given_days: '<%=$this->WebAccessResourceAccessMethodXDays->ClientID%>',
		time_from_date: '<%=$this->WebAccessResourceAccessMethodFromDate->ClientID%>',
		time_to_date: '<%=$this->WebAccessResourceAccessMethodToDate->ClientID%>',
		use_unlimited: '<%=$this->WebAccessResourceAccessMethodUnlimitedUse->ClientID%>',
		use_number: '<%=$this->WebAccessResourceAccessNumberOfUse->ClientID%>',
		source_access: '<%=$this->WebAccessResourceAccessMethodSourceAccess->ClientID%>',
		source_ips: '<%=$this->WebAccessResourceAccessSourceIPs->ClientID%>',
		action_loader: 'web_access_resource_action_name',
		token: '<%=$this->WebAccessResourceToken->ClientID%>'
	},
	defs: {
		time_given_days: '<%=$this->WebAccessResourceAccessMethodXDays->Text%>',
		use_number: '<%=$this->WebAccessResourceAccessNumberOfUse->Text%>'
	},
	load_web_access_resource_list: function() {
		const cb = <%=$this->LoadWebAccessResourceList->ActiveControl->Javascript%>;
		cb.dispatch();
	},
	load_web_access_resource_list_cb: function(list) {
		oWebAccessResourceList.data = list;
		oWebAccessResourceList.init();
	},
	load_window: function(name) {
		const title_edit = document.getElementById(this.ids.title_edit);
		const title_add = document.getElementById(this.ids.title_add);
		if (name) {
			title_edit.style.display = 'block';
			title_add.style.display = 'none';
		} else {
			title_add.style.display = 'block';
			title_edit.style.display = 'none';
		}

		const token = document.getElementById(this.ids.token);

		const cb = <%=$this->LoadWebAccessResourceWindow->ActiveControl->Javascript%>;

		if (name) {
			token.value = name;
			cb.setCallbackParameter(name);
		} else {
			token.value = '';
		}
		cb.dispatch();

		this.show_window(true);
	},
	show_window: function(show) {
		oWebAccessResource.clear_window();

		const win = document.getElementById(oWebAccessResource.ids.win);
		win.style.display = show ? 'block' : 'none';
	},
	clear_window: function() {
		// Reset action combobox
		const run_job_action = document.getElementById(this.ids.run_job_action);
		run_job_action.value = ' ';

		// Hide fields
		[
			this.ids.job_params_cont,
			this.ids.run_job_params
		].forEach((id) => {
			const el = document.getElementById(id);
			el.style.display = 'none';
		});

		// Set radio buttons
		[
			this.ids.time_unlimited,
			this.ids.use_unlimited
		].forEach((id) => {
			const el = document.getElementById(id);
			$(el).click();
		});

		// Set checkboxes
		[
			this.ids.source_access,
		].forEach((id) => {
			const el = document.getElementById(id);
			if (el.checked) {
				$(el).click();
			}
		});

		// Set text fields empty
		[
			this.ids.time_from_date,
			this.ids.time_to_date,
			this.ids.source_ips
		].forEach((id) => {
			const el = document.getElementById(id);
			el.value = '';
		});

		// Reset text fields to default values
		const time_given_days = document.getElementById(this.ids.time_given_days);
		time_given_days.value = this.defs.time_given_days;
		const use_number = document.getElementById(this.ids.use_number);
		use_number.value = this.defs.use_number;
	},
	hide_access_method_time_options: function() {
		[
			this.ids.acc_x_days,
			this.ids.acc_from_to
		].forEach((id) => {
			$('#' + id).slideUp('fast');
		});
	},
	show_access_method_time_options: function(id) {
		this.hide_access_method_time_options();

		$('#' + id).slideDown('fast');
	},
	hide_access_method_use_options: function() {
		[
			this.ids.acc_number_use,
		].forEach((id) => {
			$('#' + id).slideUp('fast');
		});
	},
	show_access_method_use_options: function(id) {
		this.hide_access_method_use_options();

		$('#' + id).slideDown('fast');
	},
	show_action_loader: function(show) {
		const loader = document.getElementById(this.ids.action_loader);
		loader.style.display = show ? 'inline-block' : 'none';
	},
	set_date: function(obj, def_t) {
		const t_regex = new RegExp("^(\\d{4}-\\d{2}-\\d{2})\\s+(\\d{1,2}\\:\\d{2}\\:\\d{2})\\s*$");
		const val = obj.value.match(t_regex);
		const tm = obj.getAttribute('tm-value');
		if (val) {
			obj.setAttribute('tm-value', val[2]);
		} else if (tm) {
			const d_regex = new RegExp("^(\\d{4}-\\d{2}-\\d{2})\\s*");
			const dval = obj.value.match(d_regex);
			if (dval) {
				obj.value = (dval[1] + ' ' + tm);
			}
		} else {
			const start_tval = def_t || '0:00:00';
			obj.value = (obj.value + ' ' + start_tval);
		}
	}
};
class WebAccessParamsBase {
	show_window(show) {
		const win = document.getElementById(this.ids.win);
		win.style.display = show ? 'block' : 'none';
	}
	clear_window() {
		const params = document.getElementById(this.ids.params);
		while (params.firstChild) {
			params.removeChild(params.firstChild);
		}
	}
	add_params(params) {
		this.clear_window();
		for (const param in params) {
			this.add_param(param, params[param]);
		}
	}
	add_param(key, value) {
		const params = document.getElementById(this.ids.params);

		const container = document.createElement('DIV');
		container.classList.add('w3-row', 'directive_field');
		const name = document.createElement('DIV');
		name.classList.add('w3-col', 'w3-third');
		name.textContent = key + ':';
		const val = document.createElement('DIV');
		val.classList.add('w3-twothird', 'bold');
		if (/ok\//.test(value)) {
			val.classList.add('w3-text-green');
			value = value.replace(/^ok\//, '');
		} else if (/error\//.test(value)) {
			val.classList.add('w3-text-red');
			value = value.replace(/^error\//, '');
		}
		val.textContent = value;

		container.appendChild(name);
		container.appendChild(val);
		params.appendChild(container);
	}
};

class WebAccessStats extends WebAccessParamsBase {
	constructor() {
		super();
		this.ids = {
			win: 'web_access_stats_window',
			params: 'web_access_stats_params'
		};
	}
}

class WebAccessParams extends WebAccessParamsBase {
	constructor() {
		super();
		this.ids = {
			win: 'web_access_params_window',
			params: 'web_access_params_params'
		};
	}
}

const oWebAccessStats = new WebAccessStats();
const oWebAccessParams = new WebAccessParams();

$(function() {
	oWebAccessResource.load_web_access_resource_list();
});
</script>
<div id="web_access_resource_window" class="w3-modal">
	<div class="w3-modal-content w3-animate-top w3-card-4">
		<header class="w3-container w3-green">
			<span onclick="oWebAccessResource.show_window(false);" class="w3-button w3-display-topright">&times;</span>
			<h2 id="web_access_resource_window_title_add" style="display: none"><%[ Add web access ]%> - <%=$this->getResourceType()%>: <%=$this->getResourceName()%></h2>
			<h2 id="web_access_resource_window_title_edit" style="display: none"><%[ Edit web access ]%> - <%=$this->getResourceType()%>: <%=$this->getResourceName()%></h2>
		</header>
		<div class="w3-container w3-margin-left w3-margin-right w3-margin-top">
			<h3><%[ Action target ]%></h3>
			<span id="web_access_resource_window_error" class="error" style="display: none"></span>
			<div class="w3-row directive_field">
				<div class="w3-col w3-third"><label for="<%=$this->WebAccessResourceAPIHosts->ClientID%>"><%[ API hosts: ]%></label></div>
				<div class="w3-half">
					<com:TActiveLabel ID="WebAccessResourceAPIHostsTxt" CssClass="bold" />
					<com:TActiveListBox
						ID="WebAccessResourceAPIHosts"
						SelectionMode="Multiple"
						Rows="5"
						AutoPostBack="false"
						CssClass="w3-select w3-border"
						Visible="false"
					/>
				</div>
			</div>
			<div class="w3-row directive_field">
				<div class="w3-col w3-third"><label for="<%=$this->WebAccessResourceComponentType->ClientID%>"><%[ Component type ]%>:</label></div>
				<div class="w3-half">
					<com:TActiveLabel ID="WebAccessResourceComponentType" CssClass="bold" />
				</div>
			</div>
			<div class="w3-row directive_field">
				<div class="w3-col w3-third"><label for="<%=$this->WebAccessResourceComponentName->ClientID%>"><%[ Component name ]%>:</label></div>
				<div class="w3-half">
					<com:TActiveLabel ID="WebAccessResourceComponentName" CssClass="bold" />
				</div>
			</div>
			<div class="w3-row directive_field">
				<div class="w3-col w3-third"><label for="<%=$this->WebAccessResourceResourceType->ClientID%>"><%[ Resource type ]%>:</label></div>
				<div class="w3-half">
					<com:TActiveLabel ID="WebAccessResourceResourceType" CssClass="bold" />
				</div>
			</div>
			<div class="w3-row directive_field">
				<div class="w3-col w3-third"><label for="<%=$this->WebAccessResourceResourceName->ClientID%>"><%[ Resource name ]%>:</label></div>
				<div class="w3-half">
					<com:TActiveLabel ID="WebAccessResourceResourceName" CssClass="bold" />
				</div>
			</div>
			<div class="w3-row directive_field">
				<div class="w3-col w3-third"><label for="<%=$this->WebAccessResourceActionName->ClientID%>"><%[ Action ]%>:</label></div>
				<div class="w3-half">
					<com:TActiveDropDownList
						ID="WebAccessResourceActionName"
						CssClass="w3-select w3-border w3-show-inline-block"
						OnCallback="loadJobActionParameters"
						Width="200px"
						ClientSide.OnLoading="oWebAccessResource.show_action_loader(true);"
						ClientSide.OnComplete="oWebAccessResource.show_action_loader(false);"
					/> &nbsp;<i class="fas fa-asterisk w3-text-red opt_req"></i> &nbsp;<i id="web_access_resource_action_name" class="fa-solid fa-sync w3-spin" style="display: none"></i>
					<com:TRequiredFieldValidator
						ValidationGroup="WebAccessGroup"
						ControlToValidate="WebAccessResourceActionName"
						Text="<%[ Field required. ]%>"
						Display="Dynamic"
					/>
				</div>
			</div>
			<div id="run_job_action_params_line_cont" style="display: none">
				<i class="fas fa-wrench"></i> &nbsp;<a href="javascript:void(0)" onclick="$('#run_job_action_params_line').toggle('fast');"><%[ Modify action parameters ]%></a>
				<div id="run_job_action_params_line" style="display: none">
					<h3><%[ Run job action params ]%></h3>
					<div class="w3-row directive_field">
						<div class="w3-col w3-third"><label for="<%=$this->WebAccessResourceActionParamLevel->ClientID%>"><%[ Level ]%>:</label></div>
						<div class="w3-half">
							<com:TActiveDropDownList ID="WebAccessResourceActionParamLevel" CssClass="w3-select w3-border" AutoPostBack="false">
								<prop:Attributes.onchange>
									const job_to_verify = $('#<%=$this->WebAccessResourceActionParamJobToVerifyOptionsLine->ClientID%>');
									const verify_options = $('#<%=$this->WebAccessResourceActionParamJobToVerifyOptionsLine->ClientID%>');
									const verify_by_job_name = $('#<%=$this->WebAccessResourceActionParamJobToVerifyJobNameLine->ClientID%>');
									const verify_by_jobid = $('#<%=$this->WebAccessResourceActionParamJobToVerifyJobIdLine->ClientID%>');
									const accurate = $('#<%=$this->WebAccessResourceActionParamAccurateLine->ClientID%>');
									const verify_current_opt = document.getElementById('<%=$this->WebAccessResourceActionParamJobToVerifyOptions->ClientID%>').value;
									if(/^(<%=implode('|', JobInfo::VERIFY_JOBS)%>)$/.test(this.value)) {
										if(/^(<%=implode('|', JobInfo::VERIFY_JOBS_NO_ACCURATE)%>)$/.test(this.value)) {
											accurate.hide();
										} else {
											accurate.show();
										}
										verify_options.show();
										job_to_verify.show();
										if (verify_current_opt == 'jobid') {
											verify_by_job_name.hide();
											verify_by_jobid.show();
										} else if (verify_current_opt == 'jobname') {
											verify_by_job_name.show();
											verify_by_jobid.hide();
										}
									} else if (job_to_verify.is(':visible')) {
										job_to_verify.hide();
										verify_options.hide();
										verify_by_job_name.hide();
										verify_by_jobid.hide();
										accurate.show();
									}
								</prop:Attributes.onchange>
							</com:TActiveDropDownList>
						</div>
					</div>
					<com:TActivePanel ID="WebAccessResourceActionParamJobToVerifyOptionsLine" CssClass="w3-row directive_field" Display="None">
						<div class="w3-col w3-third"><label for="<%=$this->WebAccessResourceActionParamJobToVerifyOptions->ClientID%>"><%[ Verify option: ]%></label></div>
						<div class="w3-half">
							<com:TActiveDropDownList ID="WebAccessResourceActionParamJobToVerifyOptions" AutoPostBack="false" CssClass="w3-select w3-border">
								<prop:Attributes.onchange>
									const verify_by_job_name = $('#<%=$this->WebAccessResourceActionParamJobToVerifyJobNameLine->ClientID%>');
									const verify_by_jobid = $('#<%=$this->WebAccessResourceActionParamJobToVerifyJobIdLine->ClientID%>');
									if (this.value == 'jobname') {
										verify_by_jobid.hide();
										verify_by_job_name.show();
									} else if (this.value == 'jobid') {
										verify_by_job_name.hide();
										verify_by_jobid.show();
									} else {
										verify_by_job_name.hide();
										verify_by_jobid.hide();
									}
								</prop:Attributes.onchange>
							</com:TActiveDropDownList>
						</div>
					</com:TActivePanel>
					<com:TActivePanel ID="WebAccessResourceActionParamJobToVerifyJobNameLine" CssClass="w3-row directive_field" Display="None">
						<div class="w3-col w3-third"><label for="<%=$this->WebAccessResourceActionParamJobToVerifyJobName->ClientID%>"><%[ Job to Verify: ]%></label></div>
						<div class="w3-half">
							<com:TActiveDropDownList ID="WebAccessResourceActionParamJobToVerifyJobName" AutoPostBack="false" CssClass="w3-select w3-border" />
						</div>
					</com:TActivePanel>
					<com:TActivePanel ID="WebAccessResourceActionParamJobToVerifyJobIdLine" CssClass="w3-row directive_field" Display="None">
						<div class="w3-col w3-third"><label for="<%=$this->WebAccessResourceActionParamJobToVerifyJobId->ClientID%>"><%[ JobId to Verify: ]%></label></div>
						<div class="w3-half">
							<com:TActiveTextBox ID="WebAccessResourceActionParamJobToVerifyJobId" CssClass="w3-input w3-border" AutoPostBack="false" />
							<com:TRequiredFieldValidator
								ValidationGroup="WebAccessResourceActionParamJobGroup"
								ControlToValidate="WebAccessResourceActionParamJobToVerifyJobId"
								ErrorMessage="<%[ JobId to Verify value must be integer greather than 0. ]%>"
								ControlCssClass="validation-error"
								Display="Dynamic"
							>
								<prop:ClientSide.OnValidate>
									const verify_opts = document.getElementById('<%=$this->WebAccessResourceActionParamJobToVerifyOptions->ClientID%>');
									sender.enabled = (verify_opts.value === 'jobid');
								</prop:ClientSide.OnValidate>
							</com:TRequiredFieldValidator>
							<com:TDataTypeValidator
								ID="WebAccessResourceActionParamJobToVerifyJobIdValidator"
								ValidationGroup="WebAccessResourceActionParamJobGroup"
								ControlToValidate="WebAccessResourceActionParamJobToVerifyJobId"
								ErrorMessage="<%[ JobId to Verify value must be integer greather than 0. ]%>"
								ControlCssClass="validation-error"
								Display="Dynamic"
								DataType="Integer"
							>
								<prop:ClientSide.OnValidate>
									const verify_opts = document.getElementById('<%=$this->WebAccessResourceActionParamJobToVerifyOptions->ClientID%>');
									sender.enabled = (verify_opts.value === 'jobid');
								</prop:ClientSide.OnValidate>
							</com:TDataTypeValidator>
						</div>
					</com:TActivePanel>
					<div class="w3-row directive_field">
						<div class="w3-col w3-third"><label for="<%=$this->WebAccessResourceActionParamClient->ClientID%>"><%[ Client: ]%></label></div>
						<div class="w3-half">
							<com:TActiveDropDownList ID="WebAccessResourceActionParamClient" AutoPostBack="false" CssClass="w3-select w3-border" />
						</div>
					</div>
					<div class="w3-row directive_field">
						<div class="w3-col w3-third"><label for="<%=$this->WebAccessResourceActionParamFileSet->ClientID%>"><%[ FileSet: ]%></label></div>
						<div class="w3-half">
							<com:TActiveDropDownList ID="WebAccessResourceActionParamFileSet" AutoPostBack="false" CssClass="w3-select w3-border" />
						</div>
					</div>
					<div class="w3-row directive_field">
						<div class="w3-col w3-third"><label for="<%=$this->WebAccessResourceActionParamPool->ClientID%>"><%[ Pool: ]%></label></div>
						<div class="w3-half">
							<com:TActiveDropDownList ID="WebAccessResourceActionParamPool" AutoPostBack="false" CssClass="w3-select w3-border" />
						</div>
					</div>
					<div class="w3-row directive_field">
						<div class="w3-col w3-third"><label for="<%=$this->WebAccessResourceActionParamStorage->ClientID%>"><%[ Storage: ]%></label></div>
						<div class="w3-half">
							<com:TActiveDropDownList ID="WebAccessResourceActionParamStorage" AutoPostBack="false" CssClass="w3-select w3-border" />
						</div>
						<div id="run_job_storage_from_config_info" style="line-height: 39px; display: none;">
							&nbsp;&nbsp;<i class="fas fa-info-circle" title="<%[ The storage has been selected basing on job configuration. This item may require adjusting before job run. ]%>"></i>
						</div>
					</div>
					<div class="w3-row directive_field">
						<div class="w3-col w3-third"><label for="<%=$this->WebAccessResourceActionParamPriority->ClientID%>"><%[ Priority: ]%></label></div>
						<div class="w3-half">
							<com:TActiveTextBox
								ID="WebAccessResourceActionParamPriority"
								CssClass="w3-input w3-border w3-quarter"
								AutoPostBack="false"
							/>
							<com:TRequiredFieldValidator
								ValidationGroup="WebAccessResourceActionParamJobGroup"
								ControlToValidate="WebAccessResourceActionParamPriority"
								ErrorMessage="<%[ Priority value must be integer greather than 0. ]%>"
								ControlCssClass="validation-error"
								Display="Dynamic"
							/>
							<com:TDataTypeValidator
								ID="PriorityValidator"
								ValidationGroup="WebAccessResourceActionParamJobGroup"
								ControlToValidate="WebAccessResourceActionParamPriority"
								ErrorMessage="<%[ Priority value must be integer greather than 0. ]%>"
								ControlCssClass="validation-error"
								Display="Dynamic"
								DataType="Integer"
							/>
						</div>
					</div>
					<com:TActivePanel ID="WebAccessResourceActionParamAccurateLine" CssClass="w3-row directive_field">
						<div class="w3-col w3-third"><label for="<%=$this->WebAccessResourceActionParamAccurate->ClientID%>"><%[ Accurate: ]%></label></div>
						<div class="field"><com:TActiveCheckBox ID="WebAccessResourceActionParamAccurate" AutoPostBack="false" CssClass="w3-check" /></div>
					</com:TActivePanel>
				</div>
			</div>
			<h3><%[ Access limits ]%></h3>
			<div class="w3-content w3-margin-bottom">
				<div class="w3-row directive_field">
					<div class="w3-col w3-third"><label for="<%=$this->WebAccessResourceAccessMethodAllTheTime->ClientID%>"><%[ Time access ]%>:</label></div>
					<div class="w3-twothird">
						<com:TActiveRadioButton
							ID="WebAccessResourceAccessMethodAllTheTime"
							GroupName="WebAccessResourceAccessTime"
							CssClass="w3-check"
							Attributes.onclick="oWebAccessResource.hide_access_method_time_options();"
							Checked="true"
						/>
						&nbsp;<label for="<%=$this->WebAccessResourceAccessMethodAllTheTime->ClientID%>"><%[ valid all the time (until manual remove) ]%></label>
					</div>
				</div>
				<div class="w3-row directive_field">
					<div class="w3-col w3-third">&nbsp;</div>
					<div class="w3-twothird">
						<com:TActiveRadioButton
							ID="WebAccessResourceAccessMethodGivenDays"
							GroupName="WebAccessResourceAccessTime"
							CssClass="w3-check"
							Attributes.onclick="oWebAccessResource.show_access_method_time_options('web_access_resource_access_method_x_days');"
						/>
						&nbsp;<label for="<%=$this->WebAccessResourceAccessMethodGivenDays->ClientID%>"><%[ valid for X days from now ]%></label>
					</div>
				</div>
				<div id="web_access_resource_access_method_x_days" class="w3-row directive_field" style="display: none">
					<div class="w3-col w3-third">&nbsp;</div>
					<div class="w3-twothird">
						<%[ Validity days ]%>: &nbsp;<com:TActiveTextBox
							ID="WebAccessResourceAccessMethodXDays"
							CssClass="w3-input w3-border w3-show-inline-block"
							Width="100px"
							Text="7"
						/> &nbsp;<i class="fas fa-asterisk w3-text-red opt_req"></i>
						<com:TRequiredFieldValidator
							ValidationGroup="WebAccessGroup"
							ControlToValidate="WebAccessResourceAccessMethodXDays"
							Text="<%[ Field required. ]%>"
							Display="Dynamic"
						>
							<prop:ClientSide.OnValidate>
								const opt = document.getElementById('<%=$this->WebAccessResourceAccessMethodGivenDays->ClientID%>');
								sender.enabled = opt.checked;
							</prop:ClientSide.OnValidate>
						</com:TRequiredFieldValidator>
						<com:TRegularExpressionValidator
							ValidationGroup="WebAccessGroup"
							ControlToValidate="WebAccessResourceAccessMethodXDays"
							RegularExpression="\d+"
							Text="<%[ Invalid date format. ]%>"
							Display="Dynamic"
						/>
					</div>
				</div>
				<div class="w3-row directive_field">
					<div class="w3-col w3-third">&nbsp;</div>
					<div class="w3-twothird">
						<com:TActiveRadioButton
							ID="WebAccessResourceAccessMethodDateRange"
							GroupName="WebAccessResourceAccessTime"
							CssClass="w3-check"
							Attributes.onclick="oWebAccessResource.show_access_method_time_options('web_access_resource_access_method_from_to_date');"
						/>
						&nbsp;<label for="<%=$this->WebAccessResourceAccessMethodDateRange->ClientID%>"><%[ valid between date X and Y ]%></label>
					</div>
				</div>
				<div id="web_access_resource_access_method_from_to_date" style="display: none">
					<div class="w3-row directive_field">
						<div class="w3-col w3-third">&nbsp;</div>
						<div class="w3-twothird">
							<div class="w3-show-inline-block" style="width: 100px">
								<%[ From date ]%>:
							</div>
							<com:TJuiDatePicker
								ID="WebAccessResourceAccessMethodFromDate"
								Options.dateFormat="yy-mm-dd"
								Options.changeYear="true",
								Options.changeMonth="true"
								Options.showAnim="fold"
								CssClass="w3-input w3-border w3-show-inline-block"
								Style.Width="200px"
								Attributes.onchange="oWebAccessResource.set_date(this, '0:00:00');"
								/> &nbsp;<i class="fas fa-asterisk w3-text-red opt_req"></i>
							<com:TRequiredFieldValidator
								ValidationGroup="WebAccessGroup"
								ControlToValidate="WebAccessResourceAccessMethodFromDate"
								Text="<%[ Field required. ]%>"
								Display="Dynamic"
							>
								<prop:ClientSide.OnValidate>
									const opt = document.getElementById('<%=$this->WebAccessResourceAccessMethodDateRange->ClientID%>');
									sender.enabled = opt.checked;
								</prop:ClientSide.OnValidate>
							</com:TRequiredFieldValidator>
							<com:TRegularExpressionValidator
								ValidationGroup="WebAccessGroup"
								ControlToValidate="WebAccessResourceAccessMethodFromDate"
								RegularExpression="\d{4}-\d{2}-\d{2} \d{1,2}:\d{2}:\d{2}"
								Text="<%[ Invalid date format. ]%>"
								Display="Dynamic"
							>
								<prop:ClientSide.OnValidate>
									const opt = document.getElementById('<%=$this->WebAccessResourceAccessMethodDateRange->ClientID%>');
									sender.enabled = opt.checked;
								</prop:ClientSide.OnValidate>
							</com:TRegularExpressionValidator>
						</div>
					</div>
					<div class="w3-row directive_field">
						<div class="w3-col w3-third">&nbsp;</div>
						<div class="w3-twothird">
							<div class="w3-show-inline-block" style="width: 100px">
								<%[ To date ]%>:
							</div>
							<com:TJuiDatePicker
								ID="WebAccessResourceAccessMethodToDate"
								Options.dateFormat="yy-mm-dd"
								Options.changeYear="true",
								Options.changeMonth="true"
								Options.showAnim="fold"
								CssClass="w3-input w3-border w3-show-inline-block"
								Style.Width="200px"
								Attributes.onchange="oWebAccessResource.set_date(this, '23:59:59');"
								/> &nbsp;<i class="fas fa-asterisk w3-text-red opt_req"></i>
							<com:TRequiredFieldValidator
								ValidationGroup="WebAccessGroup"
								ControlToValidate="WebAccessResourceAccessMethodToDate"
								Text="<%[ Field required. ]%>"
								Display="Dynamic"
							>
								<prop:ClientSide.OnValidate>
									const opt = document.getElementById('<%=$this->WebAccessResourceAccessMethodDateRange->ClientID%>');
									sender.enabled = opt.checked;
								</prop:ClientSide.OnValidate>
							</com:TRequiredFieldValidator>
							<com:TRegularExpressionValidator
								ValidationGroup="WebAccessGroup"
								ControlToValidate="WebAccessResourceAccessMethodToDate"
								RegularExpression="\d{4}-\d{2}-\d{2} \d{1,2}:\d{2}:\d{2}"
								Text="<%[ Invalid date format. ]%>"
								Display="Dynamic"
							>
								<prop:ClientSide.OnValidate>
									const opt = document.getElementById('<%=$this->WebAccessResourceAccessMethodDateRange->ClientID%>');
									sender.enabled = opt.checked;
								</prop:ClientSide.OnValidate>
							</com:TRegularExpressionValidator>
						</div>
					</div>
				</div>
			</div>
			<div class="w3-content w3-margin-bottom">
				<div class="w3-row directive_field">
					<div class="w3-col w3-third"><%[ Usage access ]%>:</div>
					<div class="w3-twothird">
						<com:TActiveRadioButton
							ID="WebAccessResourceAccessMethodUnlimitedUse"
							GroupName="WebAccessResourceUsageAccess"
							CssClass="w3-check"
							Attributes.onclick="oWebAccessResource.hide_access_method_use_options();"
							Checked="true"
						/>
						&nbsp;<label for="<%=$this->WebAccessResourceAccessMethodUnlimitedUse->ClientID%>"><%[ unlimited number of uses ]%></label>
					</div>
				</div>
				<div class="w3-row directive_field">
					<div class="w3-col w3-third">&nbsp;</div>
					<div class="w3-twothird">
						<com:TActiveRadioButton
							ID="WebAccessResourceAccessMethodOneTimeUse"
							GroupName="WebAccessResourceUsageAccess"
							CssClass="w3-check"
							Attributes.onclick="oWebAccessResource.hide_access_method_use_options();"
						/>
						&nbsp;<label for="<%=$this->WebAccessResourceAccessMethodOneTimeUse->ClientID%>"><%[ one-time use ]%></label>
					</div>
				</div>
				<div class="w3-row directive_field">
					<div class="w3-col w3-third">&nbsp;</div>
					<div class="w3-twothird">
						<com:TActiveRadioButton
							ID="WebAccessResourceAccessMethodNumberOfUse"
							GroupName="WebAccessResourceUsageAccess"
							CssClass="w3-check"
							Attributes.onclick="oWebAccessResource.show_access_method_use_options('web_access_resource_access_method_x_number_use');"
						/>
						&nbsp;<label for="<%=$this->WebAccessResourceAccessMethodNumberOfUse->ClientID%>"><%[ X number of uses ]%></label>
					</div>
				</div>
				<div id="web_access_resource_access_method_x_number_use" class="w3-row directive_field" style="display: none">
					<div class="w3-col w3-third">&nbsp;</div>
					<div class="w3-twothird">
						<%[ Number of uses ]%>: &nbsp;<com:TActiveTextBox
							ID="WebAccessResourceAccessNumberOfUse"
							CssClass="w3-input w3-border w3-show-inline-block"
							Width="100px"
							Text="10"
						/> &nbsp;<i class="fas fa-asterisk w3-text-red opt_req"></i>
						<com:TRequiredFieldValidator
							ValidationGroup="WebAccessGroup"
							ControlToValidate="WebAccessResourceAccessNumberOfUse"
							Text="<%[ Field required. ]%>"
							Display="Dynamic"
						>
							<prop:ClientSide.OnValidate>
								const opt = document.getElementById('<%=$this->WebAccessResourceAccessMethodNumberOfUse->ClientID%>');
								sender.enabled = opt.checked;
							</prop:ClientSide.OnValidate>
						</com:TRequiredFieldValidator>
						<com:TRegularExpressionValidator
							ValidationGroup="WebAccessGroup"
							ControlToValidate="WebAccessResourceAccessNumberOfUse"
							RegularExpression="\d+"
							Text="<%[ Invalid data format. ]%>"
							Display="Dynamic"
						>
							<prop:ClientSide.OnValidate>
								const opt = document.getElementById('<%=$this->WebAccessResourceAccessMethodNumberOfUse->ClientID%>');
								sender.enabled = opt.checked;
							</prop:ClientSide.OnValidate>
						</com:TRegularExpressionValidator>
					</div>
				</div>
			</div>
			<div class="w3-content w3-margin-bottom">
				<div class="w3-row directive_field">
					<div class="w3-col w3-third"><label for="<%=$this->WebAccessResourceAccessMethodSourceAccess->ClientID%>"><%[ Source access ]%>:</label></div>
					<div class="w3-twothird">
						<com:TActiveCheckBox
							ID="WebAccessResourceAccessMethodSourceAccess"
							CssClass="w3-check"
							Attributes.onclick="const el = $('#web_access_resource_access_method_source_ips'); this.checked ? el.slideDown('fast') : el.slideUp('fast');"
						/>
						&nbsp;<label for="<%=$this->WebAccessResourceAccessMethodSourceAccess->ClientID%>"><%[ access for given IP addresses ]%></label>
					</div>
				</div>
				<div id="web_access_resource_access_method_source_ips" class="w3-row directive_field" style="display: none">
					<div class="w3-col w3-third">&nbsp;</div>
					<div class="w3-twothird">
						<%[ Allowed IP addresses (comma separated) ]%>: &nbsp;<com:TActiveTextBox
							ID="WebAccessResourceAccessSourceIPs"
							CssClass="w3-input w3-border w3-show-inline-block"
							Width="350px"
						/> &nbsp;<i class="fas fa-asterisk w3-text-red opt_req"></i>
						<com:TRequiredFieldValidator
							ValidationGroup="WebAccessGroup"
							ControlToValidate="WebAccessResourceAccessSourceIPs"
							Text="<%[ Field required. ]%>"
							Display="Dynamic"
						>
							<prop:ClientSide.OnValidate>
								const opt = document.getElementById('<%=$this->WebAccessResourceAccessMethodSourceAccess->ClientID%>');
								sender.enabled = opt.checked;
							</prop:ClientSide.OnValidate>
						</com:TRequiredFieldValidator>
					</div>
				</div>
			</div>
			<com:TActiveHiddenField ID="WebAccessResourceToken" />
		</div>
		<footer class="w3-container w3-center">
			<button type="button" class="w3-button w3-red" onclick="oWebAccessResource.show_window(false);"><i class="fas fa-times"></i> &nbsp;<%[ Cancel ]%></button>
			<com:TActiveLinkButton
				ID="WebAccessSave"
				ValidationGroup="WebAccessGroup"
				CausesValidation="true"
				OnCallback="saveWebAccessResource"
				CssClass="w3-button w3-section w3-green w3-padding"
			>
				<i class="fa fa-save"></i> &nbsp;<%[ Save ]%>
			</com:TActiveLinkButton>
		</footer>
	</div>
	<com:TActiveHiddenField ID="APIHostGroupWindowType" />
</div>
<div id="web_access_stats_window" class="w3-modal">
	<div class="w3-modal-content w3-animate-top w3-card-4">
		<header class="w3-container w3-green">
			<span onclick="oWebAccessStats.show_window(false);" class="w3-button w3-display-topright">&times;</span>
			<h2><%[ Web access statistics ]%></h2>
		</header>
		<div id="web_access_stats_params" class="w3-container w3-margin-left w3-margin-right w3-margin-top">
		</div>
		<footer class="w3-container w3-center">
			<button type="button" class="w3-button w3-green w3-margin-bottom" onclick="oWebAccessStats.show_window(false);"><i class="fas fa-times"></i> &nbsp;<%[ Close ]%></button>
		</footer>
	</div>
</div>
<div id="web_access_params_window" class="w3-modal">
	<div class="w3-modal-content w3-animate-top w3-card-4">
		<header class="w3-container w3-green">
			<span onclick="oWebAccessParams.show_window(false);" class="w3-button w3-display-topright">&times;</span>
			<h2><%[ Web access parameters ]%></h2>
		</header>
		<div id="web_access_params_params" class="w3-container w3-margin-left w3-margin-right w3-margin-top">
		</div>
		<footer class="w3-container w3-center">
			<button type="button" class="w3-button w3-green w3-margin-bottom" onclick="oWebAccessParams.show_window(false);"><i class="fas fa-times"></i> &nbsp;<%[ Close ]%></button>
		</footer>
	</div>
</div>
