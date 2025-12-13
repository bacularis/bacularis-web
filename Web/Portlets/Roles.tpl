<div>
	<p class="w3-hide-small">
		<%[ Roles define the Bacularis resources to which users can have access. Roles enable to organize the web interface to give selected users limitted access to some resources, pages and functions. One user can have assigned one or more roles. ]%>
	</p>
	<div class="w3-panel">
		<button type="button" id="add_role_btn" class="w3-button w3-green" onclick="oRoles.load_role_window()"><i class="fa fa-plus"></i> &nbsp;<%[ Add new role ]%></a>
	</div>
	<!-- Tag tools -->
	<com:Bacularis.Web.Portlets.TagTools ID="TagToolsRoleList" ViewName="role_list" />
	<table id="role_list_table" class="display w3-table w3-striped w3-hoverable w3-margin-bottom selectable" style="width: 100%">
		<thead>
			<tr>
				<th></th>
				<th><%[ Role name ]%></th>
				<th class="w3-center"><%[ Long name ]%></th>
				<th class="w3-center"><%[ Description ]%></th>
				<th class="w3-center"># <%[ Users ]%></th>
				<th class="w3-center"><%[ Resources ]%></th>
				<th class="w3-center"><%[ Enabled ]%></th>
				<th class="w3-center"><%[ Tag ]%></th>
				<th class="w3-center"><%[ Action ]%></th>
			</tr>
		</thead>
		<tbody id="role_list_body"></tbody>
		<tfoot>
			<tr>
				<th></th>
				<th><%[ Role name ]%></th>
				<th class="w3-center"><%[ Long name ]%></th>
				<th class="w3-center"><%[ Description ]%></th>
				<th class="w3-center"># <%[ Users ]%></th>
				<th class="w3-center"><%[ Resources ]%></th>
				<th class="w3-center"><%[ Enabled ]%></th>
				<th class="w3-center"><%[ Tag ]%></th>
				<th class="w3-center"><%[ Action ]%></th>
			</tr>
		</tfoot>
	</table>
	<p class="info w3-hide-medium w3-hide-small"><%[ Tip: Use left-click to select table row. Use CTRL + left-click to multiple row selection. Use SHIFT + left-click to add a range of rows to selection. ]%></p>
<com:TCallback ID="RoleList" OnCallback="TemplateControl.setRoleList" />
<com:TCallback ID="LoadRole" OnCallback="TemplateControl.loadRoleWindow" />
<script>
var oRoleList = {
ids: {
	role_list: 'role_list_table',
	role_list_body: 'role_list_body'
},
actions: [
	{
		action: 'remove',
		label: '<%[ Remove ]%>',
		value: 'role',
		callback: <%=$this->RemoveRolesAction->ActiveControl->Javascript%>,
		validate: function(selected) {
			var used_roles = [];
			var predefined_roles = <%=json_encode(array_keys($this->getModule('user_role')->getPreDefinedRoles()))%>;
			var predef_roles = [];
			selected.each(function(v, k) {
				if (predefined_roles.indexOf(v.role) !== -1) {
					predef_roles.push(' - ' + v.role);
				} else if (v.user_count > 0) {
					used_roles.push(' - ' + v.role);
				}
			});
			var emsg = '', msg;
			if (predef_roles.length > 0) {
				msg = '<%[ The following roles are predefined and cannot be removed: %predefined_roles ]%>';
				emsg += msg.replace('%predefined_roles', '<hr />' + predef_roles.join('<br />') + '<hr />');
			}
			if (used_roles.length > 0) {
				msg = '<%[ The following roles are using by users and cannot be removed: %used_roles To remove them, please unassign all users from these roles. ]%>';
				emsg += msg.replace('%used_roles', '<hr />' + used_roles.join('<br />') + '<hr />');
			}
			if (emsg) {
				oBulkActionsModal.set_error(emsg);
				return false;
			}
			return true;
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
		var page = this.table.page();
		this.table.clear().rows.add(oRoleList.data).draw();
		this.table.page(page).draw(false);
		this.set_filters(this.table);
		this.table_toolbar.style.display = 'none';
	}
},
set_events: function() {
	document.getElementById(this.ids.role_list).addEventListener('click', function(e) {
		$(function() {
			const wa = (this.table.rows({selected: true}).data().length > 0) ? 'show' : 'hide';
			$(this.table_toolbar).animate({
				width: wa
			}, 'fast');
		}.bind(this));
	}.bind(this));
},
set_table: function() {
	this.table = $('#' + this.ids.role_list).DataTable({
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
			{data: 'role'},
			{data: 'long_name'},
			{
				data: 'description',
				visible: false
			},
			{data: 'user_count'},
			{
				data: 'resources',
				render: function(data, type, row) {
					ret = data;
					if (type == 'display') {
						var span = document.createElement('SPAN');
						span.title = data;
						if (data.length > 40) {
							span.textContent = data.substring(0, 40) + '...';
						} else {
							span.textContent = data;
						}
						ret = span.outerHTML;
					} else {
						ret = data;
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
				data: 'role',
				render: (data, type, row) => {
					const id = 'role';
					const tt_obj = oTagTools_<%=$this->TagToolsRoleList->ClientID%>;
					const table = 'oRoleList.table';
					return render_tags(type, id, data, tt_obj, table);
				}
			},
			{
				data: 'role',
				render: function (data, type, row) {
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
					btn_edit.setAttribute('onclick', 'oRoles.load_role_window(\'' + data + '\')');
					return btn_edit.outerHTML;
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
			className: 'action_col',
			orderable: false,
			targets: [ 8 ]
		},
		{
			className: "dt-center",
			targets: [ 2, 3, 4, 5, 6, 7, 8 ]
		}],
		select: {
			style:    'os',
			selector: 'td:not(:last-child):not(:first-child):not(:nth-last-child(2))',
			blurable: false
		},
		order: [1, 'asc'],
		initComplete: function () {
			oRoleList.set_filters(this.api());
		}
	});
},
set_filters: function(api) {
	api.columns([6]).every(function () {
		var column = this;
		var select = $('<select class="dt-select"><option value=""></option></select>')
		.appendTo($(column.footer()).empty())
		.on('change', function () {
			var val = dtEscapeRegex(
				$(this).val()
			);
			column
			.search(val ? '^' + val + '$' : '', true, false)
			.draw();
		});
		if (column[0][0] == 6) { // Enabled column
			column.data().unique().sort().each(function (d, j) {
				var ds = '';
				if (d === '1') {
					ds = '<%[ Enabled ]%>';
				} else if (d === '0') {
					ds = '<%[ Disabled ]%>';
				}
				if (column.search() == '^' + dtEscapeRegex(d) + '$') {
					select.append('<option value="' + d + '" title="' + ds + '" selected>' + ds + '</option>');
				} else if (ds) {
					select.append('<option value="' + d + '" title="' + ds + '">' + ds + '</option>');
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

var oRoles = {
load_role_window: function(role) {
	var title_add = document.getElementById('role_window_title_add');
	var title_edit = document.getElementById('role_window_title_edit');
	var role_field_name = document.getElementById('<%=$this->Role->ClientID%>');
	var role_field_req = document.getElementById('role_window_required');
	var role_win_type = document.getElementById('<%=$this->RoleWindowType->ClientID%>');
	var cb = <%=$this->LoadRole->ActiveControl->Javascript%>;
	cb.setCallbackParameter(role);
	cb.dispatch();
	if (role) {
		title_add.style.display = 'none';
		title_edit.style.display = 'inline-block';
		role_field_name.setAttribute('readonly', '');
		role_field_req.style.display = 'none';
		role_win_type.value = 'edit';
	} else {
		title_add.style.display = 'inline-block';
		title_edit.style.display = 'none';
		this.clear_role_window();
		if (role_field_name.hasAttribute('readonly')) {
			role_field_name.removeAttribute('readonly');
		}
		role_field_req.style.display = 'inline';
		role_win_type.value = 'add';
	}
	document.getElementById('role_window_role_exists').style.display = 'none';
	document.getElementById('role_window').style.display = 'block';
	if (!role) {
		role_field_name.focus();
	}
},
clear_role_window: function() {
	[
		'<%=$this->Role->ClientID%>',
		'<%=$this->RoleLongName->ClientID%>',
		'<%=$this->RoleDescription->ClientID%>',
		'<%=$this->RoleResources->ClientID%>',
	].forEach(function(id) {
		document.getElementById(id).value = '';
	});
	document.getElementById('<%=$this->RoleEnabled->ClientID%>').checked = true;
},
load_role_list: function() {
	var cb = <%=$this->RoleList->ActiveControl->Javascript%>;
	cb.dispatch();
},
load_role_list_cb: function(list) {
	oRoleList.data = list;
	oRoleList.init();
},
save_role_cb: function() {
	document.getElementById('role_window').style.display = 'none';
}
}

$(function() {
oRoles.load_role_list();
});
</script>
	<div id="role_window" class="w3-modal">
		<div class="w3-modal-content w3-animate-top w3-card-4">
			<header class="w3-container w3-green">
				<span onclick="document.getElementById('role_window').style.display = 'none';" class="w3-button w3-display-topright">&times;</span>
				<h2 id="role_window_title_add" style="display: none"><%[ Add role ]%></h2>
				<h2 id="role_window_title_edit" style="display: none"><%[ Edit role ]%></h2>
			</header>
			<div class="w3-container w3-margin-left w3-margin-right">
				<com:TValidationSummary
					CssClass="field_invalid-summary"
					ValidationGroup="RoleGroup"
					AutoUpdate="true"
					Display="Dynamic"
					/>
				<span id="role_window_role_exists" class="error" style="display: none"><ul><li><%[ Role with the given name already exists. ]%></li></ul></span>
				<div class="w3-row directive_field">
					<div class="w3-col w3-third"><com:TLabel ForControl="Role" Text="<%[ Role: ]%>"/></div>
					<div class="w3-half">
						<com:TActiveTextBox
							ID="Role"
							AutoPostBack="false"
							MaxLength="100"
							CssClass="w3-input w3-border"
						/>
						<com:TRequiredFieldValidator
							ValidationGroup="RoleGroup"
							ControlToValidate="Role"
							ErrorMessage="<%[ Role field cannot be empty. ]%>"
							ControlCssClass="field_invalid"
							Display="None"
						/>
						<com:TRegularExpressionValidator
							ValidationGroup="RoleGroup"
							RegularExpression="<%=WebRoleConfig::ROLE_PATTERN%>"
							ControlToValidate="Role"
							ErrorMessage="<%[ Invalid role value. ]%>"
							ControlCssClass="field_invalid"
							Display="None"
						/>
					</div> &nbsp;<i id="role_window_required" class="fa fa-asterisk w3-text-red opt_req" style="display none"></i>
				</div>
				<div class="w3-row directive_field">
					<div class="w3-col w3-third"><com:TLabel ForControl="RoleLongName" Text="<%[ Long name: ]%>"/></div>
					<div class="w3-half">
						<com:TActiveTextBox
							ID="RoleLongName"
							AutoPostBack="false"
							MaxLength="100"
							CssClass="w3-input w3-border"
						/>
					</div>
				</div>
				<div class="w3-row directive_field">
					<div class="w3-col w3-third"><com:TLabel ForControl="RoleDescription" Text="<%[ Description: ]%>"/></div>
					<div class="w3-half">
						<com:TActiveTextBox
							ID="RoleDescription"
							TextMode="MultiLine"
							Rows="3"
							AutoPostBack="false"
							MaxLength="500"
							CssClass="w3-input w3-border"
						/>
					</div>
				</div>
				<div class="w3-row directive_field">
					<div class="w3-col w3-third"><com:TLabel ForControl="RoleResources" Text="<%[ Resources: ]%>"/></div>
					<div class="w3-half">
						<com:TActiveListBox
							ID="RoleResources"
							SelectionMode="Multiple"
							Rows="10"
							CssClass="w3-select w3-border"
							AutoPostBack="false"
						/>
						<com:TRequiredFieldValidator
							ValidationGroup="RoleGroup"
							ControlToValidate="RoleResources"
							ErrorMessage="<%[ At least one resource must be selected. ]%>"
							ControlCssClass="field_invalid"
							Display="None"
						/>
						<p style="margin: 0 16px 0 0"><%[ Use CTRL + left-click to multiple item selection ]%></p>
					</div> &nbsp;<i class="fa fa-asterisk w3-text-red opt_req"></i>

				</div>
				<div class="w3-row directive_field">
					<div class="w3-col w3-third"><com:TLabel ForControl="RoleEnabled" Text="<%[ Enabled: ]%>"/></div>
					<div class="w3-half">
						<com:TActiveCheckBox
							ID="RoleEnabled"
							CssClass="w3-check"
							AutoPostBack="false"
						/>
					</div>
				</div>
			</div>
			<com:TActiveLabel
				ID="PreDefinedRoleMsg"
				CssClass="w3-margin-left"
				Display="None"
			>
				<%[ This is native predefined role, that cannot be changed. For having custom roles please use the button to add new role. ]%>
			</com:TActiveLabel>
			<footer class="w3-container w3-center w3-padding">
				<button type="button" class="w3-button w3-red" onclick="document.getElementById('role_window').style.display = 'none';"><i class="fas fa-times"></i> &nbsp;<%[ Cancel ]%></button>
				<com:TActiveLinkButton
					ID="RoleSave"
					ValidationGroup="RoleGroup"
					CausesValidation="true"
					OnCallback="saveRole"
					CssClass="w3-button w3-section w3-green"
				>
					<i class="fa fa-save"></i> &nbsp;<%[ Save ]%>
				</com:TActiveLinkButton>
			</footer>
		</div>
		<com:TActiveHiddenField ID="RoleWindowType" />
	</div>
	<com:TCallback ID="RemoveRolesAction" OnCallback="TemplateControl.removeRoles" />
</div>
