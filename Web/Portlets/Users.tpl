<div>
	<div class="w3-panel">
		<button type="button" id="add_user_btn" class="w3-button w3-green" onclick="oUsers.load_user_window()"><i class="fa fa-plus"></i> &nbsp;<%[ Add new user ]%></button>
		<button type="button" id="new_user_wizard_btn" class="w3-button w3-green<%=!$this->getApplication()->getSession()->itemAt('dir') ? ' hide': ''%>" onclick="document.location.href='<%=$this->Service->constructUrl('NewUserWizard')%>';"><i class="fa fa-magic"></i> &nbsp;<%[ New user ]%></button>
	</div>
	<!-- Tag tools -->
	<com:Bacularis.Web.Portlets.TagTools ID="TagToolsUserList" ViewName="user_list" />
	<table id="user_list_table" class="display w3-table w3-striped w3-hoverable w3-margin-bottom selectable" style="width: 100%">
		<thead>
			<tr>
				<th></th>
				<th><%[ Username ]%></th>
				<th class="w3-center"><%[ Long name ]%></th>
				<th class="w3-center"><%[ Description ]%></th>
				<th class="w3-center"><%[ E-mail ]%></th>
				<th class="w3-center"><%[ Roles ]%></th>
				<th class="w3-center">Org ID</th>
				<th class="w3-center">Org name</th>
				<th class="w3-center"><%[ API host method ]%></th>
				<th class="w3-center"><%[ API hosts ]%></th>
				<th class="w3-center"><%[ API host groups ]%></th>
				<th class="w3-center"><%[ IP address restrictions ]%></th>
				<th class="w3-center"><%[ Enabled ]%></th>
				<th class="w3-center"><%[ Tag ]%></th>
				<th class="w3-center"><%[ Action ]%></th>
			</tr>
		</thead>
		<tbody id="user_list_body"></tbody>
		<tfoot>
			<tr>
				<th></th>
				<th><%[ Username ]%></th>
				<th class="w3-center"><%[ Long name ]%></th>
				<th class="w3-center"><%[ Description ]%></th>
				<th class="w3-center"><%[ E-mail ]%></th>
				<th class="w3-center"><%[ Roles ]%></th>
				<th class="w3-center"><%[ Org ID ]%></th>
				<th class="w3-center"><%[ Org name ]%></th>
				<th class="w3-center"><%[ API host method ]%></th>
				<th class="w3-center"><%[ API hosts ]%></th>
				<th class="w3-center"><%[ API host groups ]%></th>
				<th class="w3-center"><%[ IP address restrictions ]%></th>
				<th class="w3-center"><%[ Enabled ]%></th>
				<th class="w3-center"><%[ Tag ]%></th>
				<th class="w3-center"><%[ Action ]%></th>
			</tr>
		</tfoot>
	</table>
	<p class="info w3-hide-medium w3-hide-small"><%[ Tip: Use left-click to select table row. Use CTRL + left-click to multiple row selection. Use SHIFT + left-click to add a range of rows to selection. ]%></p>
<com:TCallback ID="UserList" OnCallback="TemplateControl.setUserList" />
<com:TCallback ID="LoadUser" OnCallback="TemplateControl.loadUserWindow" />
<com:TCallback ID="RemoveUsersAction" OnCallback="TemplateControl.removeUsers" />
<com:TCallback ID="AssignUserRolesAction" OnCallback="TemplateControl.assignUserRoles" />
<script>
var oUserList = {
	ids: {
		user_list: 'user_list_table',
		user_list_body: 'user_list_body'
	},
	actions: [
		{
			action: 'assign_user_roles',
			label: '<%[ Assign roles ]%>',
			value: ['organization_id', 'username'],
			before: function() {
				const cb = () => {
					const fields = ['organization_id', 'username'];
					const table = oUserList.table;
					const selected = get_table_action_selected_items(table, fields);
					return selected;
				};
				oUserRolesWindow.set_user_func(cb);
				oUserRolesWindow.show(true);
			}
		},
		{
			action: 'remove',
			label: '<%[ Remove ]%>',
			value: ['organization_id', 'username'],
			callback: <%=$this->RemoveUsersAction->ActiveControl->Javascript%>
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
			var page = this.table.page();
			this.table.clear().rows.add(this.data).draw();
			this.table.page(page).draw(false);
			oUserList.set_filters(this.table);
			this.table_toolbar.style.display = 'none';
		}
	},
	set_events: function() {
		document.getElementById(this.ids.user_list).addEventListener('click', function(e) {
			$(function() {
				const wa = (this.table.rows({selected: true}).data().length > 0) ? 'show' : 'hide';
				$(this.table_toolbar).animate({
					width: wa
				}, 'fast');
			}.bind(this));
		}.bind(this));
	},
	set_table: function() {
		this.table = $('#' + this.ids.user_list).DataTable({
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
				{data: 'username'},
				{data: 'long_name'},
				{
					data: 'description',
					visible: false
				},
				{
					data: 'email',
					visible: false
				},
				{data: 'roles'},
				{
					data: 'organization_id',
					visible: false
				},
				{
					data: 'organization_name',
					visible: false
				},
				{
					data: 'api_hosts_method',
					render: (data, type, row) => {
						let ret;
						if (type == 'display') {
							ret = '<%[ Hosts ]%>';
							if (data == 'host_groups') {
								ret = '<%[ Host groups ]%>';
							}
						} else {
							ret = data;
						}
						return ret;
					}
				},
				{
					data: 'api_hosts',
					render: (data, type, row) => {
						const data_str = data.join(',');
						return render_string_short(data_str, type, row);
					}
				},
				{
					data: 'api_host_groups',
					render: (data, type, row) => {
						const data_str = data.join(',');
						return render_string_short(data_str, type, row);
					},
					visible: false
				},
				{
					data: 'ips',
					visible: false
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
					data: 'username',
					render: (data, type, row) => {
						const id = 'username';
						const tt_obj = oTagTools_<%=$this->TagToolsUserList->ClientID%>;
						const table = 'oUserList.table';
						return render_tags(type, id, data, tt_obj, table);
					}
				},
				{
					data: 'username',
					render: function (data, type, row) {
						let btns = '';

						// Set access button
						const span = document.createElement('SPAN');
						const access_btn = document.createElement('BUTTON');
						access_btn.className = 'w3-button w3-green';
						access_btn.type = 'button';
						const i = document.createElement('I');
						i.className = 'fa-solid fa-shield';
						const label = document.createTextNode(' <%[ Set access ]%>');
						access_btn.appendChild(i);
						access_btn.innerHTML += '&nbsp';
						access_btn.appendChild(label);
						access_btn.setAttribute('onclick', 'oUsers.load_user_access_window(\'' + row.organization_id + '\', \'' + data + '\')');
						span.appendChild(access_btn);
						span.style.marginRight = '5px';
						btns += span.outerHTML;

						var btn_edit = document.createElement('BUTTON');
						btn_edit.className = 'w3-button w3-green';
						btn_edit.type = 'button';
						var i_edit = document.createElement('I');
						i_edit.className = 'fa fa-edit';
						var label_edit = document.createTextNode(' <%[ Edit ]%>');
						btn_edit.appendChild(i_edit);
						btn_edit.innerHTML += '&nbsp';
						btn_edit.style.marginRight = '8px';
						btn_edit.appendChild(label_edit);
						btn_edit.setAttribute('onclick', 'oUsers.load_user_window(\'' + row.organization_id + '\', \'' + data + '\')');
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
				className: 'dtr-control',
				orderable: false,
				targets: 0
			},
			{
				className: 'action_col_long',
				orderable: false,
				targets: [ 14 ]
			},
			{
				className: "dt-center",
				targets: [ 5, 6, 7, 8, 9, 10, 12, 13, 14 ]
			}],
			select: {
				style:    'os',
				selector: 'td:not(:last-child):not(:first-child):not(:nth-last-child(2))',
				blurable: false
			},
			order: [1, 'asc'],
			drawCallback: function () {
				oUserList.set_filters(this.api());
			}
		});
	},
	set_filters: function(api) {
		api.columns([5, 6, 7, 8, 9, 10, 12]).every(function () {
			var column = this;
			var select = $('<select class="dt-select" style="max-width: 200px"><option value=""></option></select>')
			.appendTo($(column.footer()).empty())
			.on('change', function () {
				var val = dtEscapeRegex(
					$(this).val()
				);
				column
				.search(val ? '^' + val + '$' : '', true, false)
				.draw();
			});
			if ([6, 7, 8, 9, 10, 12].indexOf(column[0][0]) > -1) { // Enabled column
				let items = [];
				column.data().unique().sort().each(function (d, j) {
					if (Array.isArray(d)) {
						d = d.toString();
					}
					if (d === '' || items.indexOf(d) > -1) {
						return;
					}
					items.push(d);
				});
				for (const item of items) {
					let ds = '';
					if (column[0][0] == 8) {
						if (item === '' || item === 'hosts') {
							ds = '<%[ Hosts ]%>';
						} else if (item === 'host_groups') {
							ds = '<%[ Host groups ]%>';
						}
					} else if (column[0][0] == 12) {
						if (item === '1') {
							ds = '<%[ Enabled ]%>';
						} else if (item === '0') {
							ds = '<%[ Disabled ]%>';
						}
					} else {
						ds = item;
					}
					if (column.search() == '^' + dtEscapeRegex(item) + '$') {
						select.append('<option value="' + item + '" title="' + ds + '" selected>' + ds + '</option>');
					} else {
						select.append('<option value="' + item + '" title="' + ds + '">' + ds + '</option>');
					}
				}
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

var oUsers = {
	load_user_window: function(org_id, username) {
		var title_add = document.getElementById('user_window_title_add');
		var title_edit = document.getElementById('user_window_title_edit');
		var user_field_name = document.getElementById('<%=$this->UserName->ClientID%>');
		var user_field_req = document.getElementById('user_window_required');
		var user_win_type = document.getElementById('<%=$this->UserWindowType->ClientID%>');
		// callback is sent both for new and edit user because there is realized
		// checking if password is allowed to set or not
		var cb = <%=$this->LoadUser->ActiveControl->Javascript%>;
		cb.setCallbackParameter({
			org_id: org_id || '',
			user_id: username || ''
		});
		cb.dispatch();
		if (username) {
			title_add.style.display = 'none';
			title_edit.style.display = 'inline-block';
			user_field_name.setAttribute('readonly', '');
			user_field_req.style.display = 'none';
			user_win_type.value = 'edit';
		} else {
			title_add.style.display = 'inline-block';
			title_edit.style.display = 'none';
			this.clear_user_window();
			if (user_field_name.hasAttribute('readonly')) {
				user_field_name.removeAttribute('readonly');
			}
			user_field_req.style.display = 'inline';
			user_win_type.value = 'add';
		}
		document.getElementById('user_window').style.display = 'block';
		user_field_name.focus();
	},
	clear_user_window: function() {
		[
			'<%=$this->UserName->ClientID%>',
			'<%=$this->UserLongName->ClientID%>',
			'<%=$this->UserDescription->ClientID%>',
			'<%=$this->UserEmail->ClientID%>',
			'<%=$this->UserPassword->ClientID%>',
			'<%=$this->UserRoles->ClientID%>',
			'<%=$this->UserAPIHosts->ClientID%>',
			'<%=$this->UserOrganization->ClientID%>',
			'<%=$this->UserIps->ClientID%>'
		].forEach(function(id) {
			document.getElementById(id).value = '';
		});
		document.getElementById('<%=$this->UserEnabled->ClientID%>').checked = true;
	},
	load_user_list: function() {
		var cb = <%=$this->UserList->ActiveControl->Javascript%>;
		cb.dispatch();
	},
	load_user_list_cb: function(list) {
		oUserList.data = list;
		oUserList.init();
	},
	save_user_cb: function() {
		document.getElementById('user_window').style.display = 'none';
	},
	load_user_access_window: function(org_id, user_id) {
		this.clear_access_window();
		document.getElementById('<%=$this->UserAPIHostResourceAccessUserId->ClientID%>').value = user_id;
		document.getElementById('<%=$this->UserAPIHostResourceAccessOrgId->ClientID%>').value = org_id;
		const cb = <%=$this->LoadUserAPIHostResourceAccess->ActiveControl->Javascript%>;
		cb.dispatch();
		document.getElementById('user_access_window_title').textContent = user_id;
		document.getElementById('user_access_window').style.display = 'block';
	},
	clear_access_window: function() {
		// empty fields
		[
			'<%=$this->UserAPIHostResourceAccessJobs->ClientID%>'
		].forEach((id) => {
			$('#' + id).empty();
		});

		// reset radio buttons
		document.getElementById('<%=$this->UserAPIHostResourceAccessAllResources->ClientID%>').checked = true;
		document.getElementById('user_access_window_error').style.display = 'none';
		document.getElementById('user_access_window_console').style.display = 'none';
		document.getElementById('user_access_window_select_jobs').style.display = 'none';
		document.getElementById('user_access_window_select_access').style.display = 'none';
	},
	unassign_console: function() {
		const api_host = document.getElementById('<%=$this->UserAPIHostList->ClientID%>').value;
		const cb = <%=$this->UnassignUserAPIHostConsole->ActiveControl->Javascript%>;
		cb.setCallbackParameter(api_host);
		cb.dispatch();
	}
}

$(function() {
	oUsers.load_user_list();
});
</script>
	<div id="user_window" class="w3-modal">
		<div class="w3-modal-content w3-animate-top w3-card-4">
			<header class="w3-container w3-green">
				<span onclick="document.getElementById('user_window').style.display = 'none';" class="w3-button w3-display-topright">&times;</span>
				<h2 id="user_window_title_add" style="display: none"><%[ Add user ]%></h2>
				<h2 id="user_window_title_edit" style="display: none"><%[ Edit user ]%></h2>
			</header>
			<div class="w3-container w3-margin-left w3-margin-right">
				<com:TValidationSummary
					CssClass="field_invalid-summary"
					ValidationGroup="UserGroup"
					AutoUpdate="true"
					Display="Dynamic"
					/>
				<span id="user_window_username_exists" class="error" style="display: none"><ul><li><%[ Username with the given name already exists. ]%></li></ul></span>
				<span id="user_window_username_org_exists" class="error" style="display: none"><ul><li><%[ Cannot change organization. A username with the same name already exists in the selected organization. ]%></li></ul></span>
				<div class="w3-row directive_field">
					<div class="w3-col w3-third"><com:TLabel ForControl="UserName" Text="<%[ Username: ]%>"/></div>
					<div class="w3-half">
						<com:TActiveTextBox
							ID="UserName"
							AutoPostBack="false"
							MaxLength="100"
							CssClass="w3-input w3-border"
						/>
						<com:TRequiredFieldValidator
							ValidationGroup="UserGroup"
							ControlToValidate="UserName"
							ErrorMessage="<%[ Username field cannot be empty. ]%>"
							ControlCssClass="field_invalid"
							Display="None"
						/>
						<com:TRegularExpressionValidator
							ValidationGroup="UserGroup"
							RegularExpression="<%=WebUserConfig::USER_PATTERN%>"
							ControlToValidate="UserName"
							ErrorMessage="<%[ Invalid username value. ]%>"
							ControlCssClass="field_invalid"
							Display="None"
						/>
					</div> &nbsp;<i id="user_window_required" class="fa fa-asterisk w3-text-red opt_req" style="display none"></i>
				</div>
				<div class="w3-row directive_field">
					<div class="w3-col w3-third"><com:TLabel ForControl="UserLongName" Text="<%[ Long name: ]%>"/></div>
					<div class="w3-half">
						<com:TActiveTextBox
							ID="UserLongName"
							AutoPostBack="false"
							MaxLength="100"
							CssClass="w3-input w3-border"
						/>
					</div>
				</div>
				<div class="w3-row directive_field">
					<div class="w3-col w3-third"><com:TLabel ForControl="UserDescription" Text="<%[ Description: ]%>"/></div>
					<div class="w3-half">
						<com:TActiveTextBox
							ID="UserDescription"
							TextMode="MultiLine"
							Rows="3"
							AutoPostBack="false"
							MaxLength="500"
							CssClass="w3-input w3-border"
						/>
					</div>
				</div>
				<div class="w3-row directive_field">
					<div class="w3-col w3-third"><com:TLabel ForControl="UserEmail" Text="<%[ E-mail: ]%>"/></div>
					<div class="w3-half">
						<com:TActiveTextBox
							ID="UserEmail"
							AutoPostBack="false"
							MaxLength="500"
							CssClass="w3-input w3-border"
						/>
						<com:TRegularExpressionValidator
							ValidationGroup="UserGroup"
							RegularExpression="<%=WebUserConfig::EMAIL_ADDRESS_PATTERN%>"
							ControlToValidate="UserEmail"
							ErrorMessage="<%[ Invalid e-mail address value. ]%>"
							ControlCssClass="field_invalid"
							Display="None"
						/>
					</div>
				</div>
				<div id="user_window_password" class="w3-row directive_field">
					<div class="w3-col w3-third"><com:TLabel ForControl="UserPassword" Text="<%[ Password: ]%>"/></div>
					<div class="w3-half">
						<com:TActiveTextBox
							ID="UserPassword"
							TextMode="Password"
							AutoPostBack="false"
							MaxLength="1000"
							CssClass="w3-input w3-border"
						/>
					</div>
					<a href="javascript:void(0)" onclick="var el = document.getElementById('<%=$this->UserPassword->ClientID%>'); el.type = el.type == 'text' ? 'password' : 'text'" title="<%[ Show/hide password ]%>"><i class="fa fa-eye"></i></a>
					&nbsp;<a href="javascript:void(0)" onclick="document.getElementById('<%=$this->UserPassword->ClientID%>').value = get_random_string('ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-_~!@#$%^&*()_+}{|?[]\\/.,', 10); return false;" title="<%[ Generate new password ]%>"><i class="fas fa-random"></i></a>
				</div>
				<div class="w3-row directive_field">
					<div class="w3-col w3-third"><com:TLabel ForControl="UserRoles" Text="<%[ Roles: ]%>"/></div>
					<div class="w3-half">
						<com:TActiveListBox
							ID="UserRoles"
							SelectionMode="Multiple"
							Rows="6"
							CssClass="w3-select w3-border"
							AutoPostBack="false"
						/>
						<com:TRequiredFieldValidator
							ValidationGroup="UserGroup"
							ControlToValidate="UserRoles"
							ErrorMessage="<%[ At least one role must be selected. ]%>"
							ControlCssClass="field_invalid"
							Display="None"
						/>
						<p style="margin: 0 16px 0 0"><%[ Use CTRL + left-click to multiple item selection ]%></p>
					</div> &nbsp;<i class="fa fa-asterisk w3-text-red opt_req"></i>
				</div>
				<div class="w3-row directive_field">
					<div class="w3-col w3-third">&nbsp;</div>
					<div class="w3-half">
						<com:TActiveRadioButton
							ID="UserAPIHostsOpt"
							GroupName="SelectAPIHosts"
							Checked="true"
							CssClass="w3-radio"
							Attributes.onclick="$('#user_window_api_host_groups').hide();$('#user_window_api_hosts').show();"
						/>
						<com:TLabel
							ForControl="UserAPIHostsOpt"
							CssClass="normal w3-radio"
							Style="vertical-align: super"
							Text="<%[ Use API hosts ]%>"
						/>
					</div>
				</div>
				<div class="w3-row directive_field">
					<div class="w3-col w3-third">&nbsp;</div>
					<div class="w3-half">
						<com:TActiveRadioButton
							ID="UserAPIHostGroupsOpt"
							GroupName="SelectAPIHosts"
							CssClass="w3-radio"
							Attributes.onclick="$('#user_window_api_hosts').hide();$('#user_window_api_host_groups').show();"
						/>
						<com:TLabel
							ForControl="UserAPIHostGroupsOpt"
							CssClass="normal w3-radio"
							Style="vertical-align: super"
							Text="<%[ Use API host groups ]%>"
						/>
					</div>
				</div>
				<div id="user_window_api_hosts" class="w3-row directive_field">
					<div class="w3-col w3-third"><com:TLabel ForControl="UserAPIHosts" Text="<%[ API hosts: ]%>"/></div>
					<div class="w3-half">
						<com:TActiveListBox
							ID="UserAPIHosts"
							SelectionMode="Multiple"
							Rows="6"
							CssClass="w3-select w3-border"
							AutoPostBack="false"
						/>
						<p style="margin: 0 16px 0 0"><%[ Use CTRL + left-click to multiple item selection ]%></p>
					</div>
				</div>
				<div id="user_window_api_host_groups" class="w3-row directive_field" style="display: none">
					<div class="w3-col w3-third"><com:TLabel ForControl="UserAPIHostGroups" Text="<%[ API host groups: ]%>"/></div>
					<div class="w3-half">
						<com:TActiveListBox
							ID="UserAPIHostGroups"
							SelectionMode="Multiple"
							Rows="6"
							CssClass="w3-select w3-border"
							AutoPostBack="false"
						/>
						<com:TRequiredFieldValidator
							ValidationGroup="UserGroup"
							ControlToValidate="UserAPIHostGroups"
							ErrorMessage="<%[ Field required. ]%>"
							ControlCssClass="field_invalid"
							Display="Dynamic"
						>
							<prop:ClientSide.OnValidate>
								const radio = document.getElementById('<%=$this->UserAPIHostGroupsOpt->ClientID%>');
								sender.enabled = radio.checked;
							</prop:ClientSide.OnValidate>
						</com:TRequiredFieldValidator>
						<p style="margin: 0 16px 0 0"><%[ Use CTRL + left-click to multiple item selection ]%></p>
					</div> &nbsp;<i class="fa fa-asterisk w3-text-red opt_req"></i>
				</div>
				<div class="w3-row directive_field">
					<div class="w3-col w3-third"><com:TLabel ForControl="UserOrganization" Text="<%[ Organization: ]%>"/></div>
					<div class="w3-half">
						<com:TActiveDropDownList
							ID="UserOrganization"
							CssClass="w3-select w3-border"
							AutoPostBack="false"
						/>
					</div>
				</div>
				<div class="w3-row directive_field">
					<div class="w3-col w3-third"><com:TLabel ForControl="UserEnabled" Text="<%[ Enabled: ]%>"/></div>
					<div class="w3-half">
						<com:TActiveCheckBox
							ID="UserEnabled"
							CssClass="w3-check"
							AutoPostBack="false"
						/>
					</div>
				</div>
				<i class="fas fa-wrench"></i> &nbsp;<a href="javascript:void(0)" onclick="$('#user_window_advanced_options').toggle('fast');"><%[ Advanced options ]%></a>
				<div id="user_window_advanced_options" style="display: none">
					<div class="w3-row directive_field" title="<%[ Comma separated IP addresses. Using asterisk character, there is also possible to provide subnet, for example: 192.168.1.* ]%>">
						<div class="w3-col w3-third"><com:TLabel ForControl="UserIps" Text="<%[ IP address restrictions: ]%>"/></div>
						<div class="w3-half">
							<com:TActiveTextBox
								ID="UserIps"
								AutoPostBack="false"
								MaxLength="500"
								CssClass="w3-input w3-border"
							/>
							<p><a href="javascript:void(0)" onclick="document.getElementById('<%=$this->UserIps->ClientID%>').value = '<%=$_SERVER['REMOTE_ADDR']%>';"><%[ Set your IP address ]%></a></p>
							<com:TActiveCustomValidator
								ID="UserIpsValidator"
								ValidationGroup="UserGroup"
								ControlToValidate="UserIps"
								OnServerValidate="validateIps"
								Display="Dynamic"
								ErrorMessage="<%[ Invalid IP address restrictions value. This field can have comma separated IP addresses only or subnet addresses like 192.168.1.* ]%>"
								ControlCssClass="field_invalid"
							/>
						</div>
					</div>
				</div>
			</div>
			<footer class="w3-container w3-center">
				<button type="button" class="w3-button w3-red" onclick="document.getElementById('user_window').style.display = 'none';"><i class="fas fa-times"></i> &nbsp;<%[ Cancel ]%></button>
				<com:TActiveLinkButton
					ID="UserSave"
					ValidationGroup="UserGroup"
					CausesValidation="true"
					OnCallback="saveUser"
					CssClass="w3-button w3-section w3-green w3-padding"
					Attributes.onclick="const fm = Prado.Validation.getForm(); return Prado.Validation.validate(fm, 'UserGroup');"
				>
					<i class="fa fa-save"></i> &nbsp;<%[ Save ]%>
				</com:TActiveLinkButton>
			</footer>
		</div>
		<com:TActiveHiddenField ID="UserOrganizationID" />
		<com:TActiveHiddenField ID="UserWindowType" />
	</div>
	<div id="user_access_window" class="w3-modal">
		<div class="w3-modal-content w3-animate-top w3-card-4">
			<header class="w3-container w3-green">
				<span onclick="document.getElementById('user_access_window').style.display = 'none';" class="w3-button w3-display-topright">&times;</span>
				<h2><span id="user_access_window_title"></span> - <%[ Set user API host access to resources ]%> &nbsp;<i id="get_api_host_loader" class="fa fa-sync w3-spin" style="visibility: hidden;"></i></h2>
			</header>
			<div class="w3-container w3-margin-left w3-margin-right w3-margin-top">
				<div class="w3-row directive_field">
					<div class="w3-col w3-third"><com:TLabel ForControl="UserAPIHostList" Text="<%[ API host: ]%>"/></div>
					<div class="w3-twothird w3-show-inline-block">
						<com:TActiveDropDownList
							ID="UserAPIHostList"
							CssClass="w3-select w3-border"
							OnCallback="setUserAPIHostResourceAccessWindow"
							Attributes.onclick=""
						>
							<prop:ClientSide.OnLoading>
								document.getElementById('get_api_host_loader').style.visibility = 'visible';
								const sa = document.getElementById('user_access_window_select_access');
								sa.style.display = 'none';
							</prop:ClientSide.OnLoading>
							<prop:ClientSide.OnComplete>
								document.getElementById('get_api_host_loader').style.visibility = 'hidden';
								const el = document.getElementById('<%=$this->UserAPIHostList->ClientID%>');
								const sa = document.getElementById('user_access_window_select_access');
								const err = document.getElementById('user_access_window_error');
								if (err.style.display == 'none') {
									sa.style.display =  el.value ? 'block' : 'none';
								} else {
									sa.style.display = 'none';
								}
							</prop:ClientSide.OnComplete>
						</com:TActiveDropDownList>
						<i class="fas fa-info-circle help_icon w3-text-green" onclick="$('#help_user_api_hosts').slideToggle('fast');"></i>
						&nbsp;<i class="fa fa-asterisk w3-text-red opt_req" style="vertical-align: top"></i>
						<br />
						<com:TRequiredFieldValidator
							ControlCssClass="field_invalid"
							Display="Dynamic"
							ControlToValidate="UserAPIHostList"
							ValidationGroup="UserAPIHostResourceAccessGroup"
							ErrorMessage="<%[ Field required. ]%>"
						/>
						<div id="help_user_api_hosts" class="directive_help" style="display: none">
							<%[ Select an API host to set resources and permissions. Please note that the 'Main' API host is not listed because the 'Main' is the full access API host and it should not be limited. ]%>
						</div>
					</div>
				</div>
				<div id="user_access_window_select_access" style="display: none">
					<h3><%[ Resource access ]%></h3>
					<div class="w3-row directive_field">
						<p><com:TActiveRadioButton
							ID="UserAPIHostResourceAccessAllResources"
							GroupName="UserAPIHostResourceAccess"
							CssClass="w3-radio"
							Attributes.onclick="$('#user_access_window_select_jobs').slideUp();"
							Checked="true"
							/> <label for="<%=$this->UserAPIHostResourceAccessAllResources->ClientID%>"><%[ Access to all shared API host resources (all jobs, all clients, all storages...etc.) ]%></label></p>
						<p><com:TActiveRadioButton
							ID="UserAPIHostResourceAccessSelectedResources"
							GroupName="UserAPIHostResourceAccess"
							CssClass="w3-radio"
							Attributes.onclick="$('#user_access_window_select_jobs').slideDown();"
							/> <label for="<%=$this->UserAPIHostResourceAccessSelectedResources->ClientID%>"><%[ Access to selected resources only ]%></label></p>
					</div>
					<div id="user_access_window_select_jobs" class="w3-row directive_field" style="display: none">
						<div id="user_access_window_console" class="w3-section" style="display: none"><%[ Bacula Console ACL ]%>: <strong><%[ Assigned ]%></strong> ( <a href="javascript:void(0)" onclick="oUsers.unassign_console();"><%[ unassign ]%></a> )</div>
						<div class="w3-col w3-third"><com:TLabel ForControl="UserAPIHostResourceAccessJobs" Text="<%[ API host jobs: ]%>"/></div>
						<div class="w3-twothird">
							<com:TActiveListBox
								ID="UserAPIHostResourceAccessJobs"
								SelectionMode="Multiple"
								Rows="10"
								CssClass="w3-select w3-border"
								ValidationGroup="UserAPIHostResourceAccessGroup"
								AutoPostBack="false"
							/> &nbsp;<i class="fa fa-asterisk w3-text-red opt_req" style="vertical-align: top"></i>
							<com:TRequiredFieldValidator
								ControlCssClass="field_invalid"
								Display="Dynamic"
								ControlToValidate="UserAPIHostResourceAccessJobs"
								ValidationGroup="UserAPIHostResourceAccessGroup"
								ErrorMessage="<%[ Field required. ]%>"
							>
								<prop:ClientSide.OnValidate>
									const radio = document.getElementById('<%=$this->UserAPIHostResourceAccessSelectedResources->ClientID%>');
									sender.enabled = radio.checked;
								</prop:ClientSide.OnValidate>
							</com:TRequiredFieldValidator>

							<p style="margin: 0 16px 0 0"><%[ Use CTRL + left-click to multiple item selection ]%></p>
						</div>
					</div>
					<h3><%[ Resource permissions ]%></h3>
					<p class="italic"><%[ By default, all resources have read-write permissions. ]%></p>
					<com:Bacularis.Common.Portlets.BaculaResourcePermissions
						ID="UserAPIHostResourcePermissions"
					/>
				</div>
				<div id="user_access_window_error" class="w3-red" style="display: none"></div>
			</div>
			<footer class="w3-container w3-center">
				<button type="button" class="w3-button w3-red" onclick="document.getElementById('user_access_window').style.display = 'none';"><i class="fas fa-times"></i> &nbsp;<%[ Cancel ]%></button>
				<com:TActiveLinkButton
					ID="UserAPIHostResourceAccessSave"
					ValidationGroup="UserAPIHostResourceAccessGroup"
					CausesValidation="true"
					OnCallback="saveUserAPIHostResourceAccess"
					CssClass="w3-button w3-section w3-green w3-padding"
					Attributes.onclick="<%=$this->UserAPIHostResourcePermissions->ClientID%>ResourcePermissions.save_user_props();"
				>
					<i class="fa fa-save"></i> &nbsp;<%[ Save ]%>
				</com:TActiveLinkButton>
			</footer>
			<com:TActiveHiddenField ID="UserAPIHostResourceAccessUserId" />
			<com:TActiveHiddenField ID="UserAPIHostResourceAccessOrgId" />
		</div>
		<com:TCallback ID="LoadUserAPIHostResourceAccess" OnCallback="TemplateControl.loadUserAPIHostResourceAccessWindow">
			<prop:ClientSide.OnLoading>
				document.getElementById('get_api_host_loader').style.visibility = 'visible';
			</prop:ClientSide.OnLoading>
			<prop:ClientSide.OnComplete>
				document.getElementById('get_api_host_loader').style.visibility = 'hidden';
			</prop:ClientSide.OnComplete>
		</com:TCallback>
		<com:TCallback ID="UnassignUserAPIHostConsole" OnCallback="TemplateControl.unassignUserAPIHostConsole" />
	</div>
	<div id="assign_user_roles_window" class="w3-modal">
		<div class="w3-modal-content w3-animate-top w3-card-4">
			<header class="w3-container w3-green">
				<span onclick="oUserRolesWindow.show(false);" class="w3-button w3-display-topright">&times;</span>
				<h2 id="assign_user_roles_window_title_add"><%[ Assign user roles ]%></h2>
			</header>
			<div class="w3-container w3-margin-left w3-margin-right">
				<span id="assign_user_roles_error" class="error" style="display: none"></span>
				<p>
					<%[ Select the roles you want to assign to the selected users. ]%>
				</p>
				<div class="w3-row directive_field">
					<div class="w3-col w3-third"><com:TLabel ForControl="AssignUserRoleList" Text="<%[ Roles: ]%>"/></div>
					<div class="w3-half">
						<com:TActiveListBox
							ID="AssignUserRoleList"
							SelectionMode="Multiple"
							Rows="6"
							CssClass="w3-select w3-border"
							AutoPostBack="false"
						/>
						<com:TRequiredFieldValidator
							ValidationGroup="AssignUserRolesGroup"
							ControlToValidate="AssignUserRoleList"
							ErrorMessage="<%[ Field required. ]%>"
							ControlCssClass="field_invalid"
							Display="Dynamic"
						/>
						<p style="margin: 0 16px 0 0"><%[ Use CTRL + left-click to multiple item selection ]%></p>
					</div> &nbsp;<i id="user_window_required" class="fa fa-asterisk w3-text-red opt_req" style="display none"></i>
				</div>
			</div>
			<footer class="w3-container w3-center w3-padding">
				<button type="button" class="w3-button w3-red w3-margin-bottom" onclick="oUserRolesWindow.show(false);"><i class="fas fa-times"></i> &nbsp;<%[ Cancel ]%></button>
				<button type="button" class="w3-button w3-green w3-margin-bottom" onclick="const fm = Prado.Validation.getForm(); return (Prado.Validation.validate(fm, 'AssignUserRolesGroup') && oUserRolesWindow.save());"><i class="fas fa-save"></i> &nbsp;<%[ Save ]%></button>
			</footer>
		</div>
	</div>
	<script>
var oUserRolesWindow = {
	users_func: null,
	ids: {
		win: 'assign_user_roles_window',
		roles: '<%=$this->AssignUserRoleList->ClientID%>'
	},
	clear: function() {
		const roles = document.getElementById(this.ids.roles);
		for (let i = 0; i < roles.options.length; i++) {
			roles.options[i].selected = false;
		}
	},
	set_user_func: function(func) {
		this.users_func = func;
	},
	save: function() {
		const users = this.users_func();
		const cb = <%=$this->AssignUserRolesAction->ActiveControl->Javascript%>;
		cb.setCallbackParameter(users);
		cb.dispatch();
	},
	show: function(show) {
		const self = oUserRolesWindow;
		self.clear();
		const win = document.getElementById(self.ids.win);
		win.style.display = show ? 'block' : 'none';
	}
};
	</script>
</div>
