<div>
	<p class="w3-hide-small"><%[ The API basic users are configured on the API hosts and they are used to access the API hosts. Changes introduced on this page are directly done on currently used API host. You can use the basic user credentials to connect API hosts to the Bacularis Web on the 'API hosts' tab. The basic user accounts are also possible to create directly in the panel of the Bacularis API. ]%></p>
	<div id="api_basic_user_btn" class="w3-panel">
		<button type="button" id="add_api_basic_user_btn" class="w3-button w3-green" onclick="oAPIBasicUsers.load_api_basic_user_window()"><i class="fa fa-plus"></i> &nbsp;<%[ Add new API basic user ]%></button>
	</div>
	<table id="api_basic_user_list_table" class="display w3-table w3-striped w3-hoverable w3-margin-bottom selectable" style="width: 100%">
		<thead>
			<tr>
				<th></th>
				<th><%[ Username ]%></th>
				<th><%[ Dedicated Bconsole config ]%></th>
				<th><%[ Actions ]%></th>
			</tr>
		</thead>
		<tbody id="api_basic_user_list_body"></tbody>
		<tfoot>
			<tr>
				<th></th>
				<th><%[ Username ]%></th>
				<th><%[ Dedicated Bconsole config ]%></th>
				<th><%[ Actions ]%></th>
			</tr>
		</tfoot>
	</table>
	<p id="api_basic_user_table_footer" class="info w3-hide-medium w3-hide-small"><%[ Tip: Use left-click to select table row. Use CTRL + left-click to multiple row selection. Use SHIFT + left-click to add a range of rows to selection. ]%></p>
<com:TCallback ID="APIBasicUserList" OnCallback="TemplateControl.setAPIBasicUserList" />
<com:TCallback ID="LoadAPIBasicUser" OnCallback="TemplateControl.loadAPIBasicUserWindow" />
<com:TCallback ID="RemoveAPIBasicUsersAction" OnCallback="TemplateControl.removeAPIBasicUsers" />
<script>
var oAPIBasicUserList = {
ids: {
	api_basic_user_list: 'api_basic_user_list_table'
},
actions: [
	{
		action: 'remove',
		label: '<%[ Remove ]%>',
		value: 'username',
		callback: <%=$this->RemoveAPIBasicUsersAction->ActiveControl->Javascript%>
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
		this.table_toolbar.style.display = 'none';
	}
},
set_events: function() {
	document.getElementById(this.ids.api_basic_user_list).addEventListener('click', function(e) {
		$(function() {
			const wa = (this.table.rows({selected: true}).data().length > 0) ? 'show' : 'hide';
			$(this.table_toolbar).animate({
				width: wa
			}, 'fast');
		}.bind(this));
	}.bind(this));
},
set_table: function() {
	this.table = $('#' + this.ids.api_basic_user_list).DataTable({
		data: this.data,
		deferRender: true,
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
			{
				data: 'bconsole_cfg_path',
				render: function(data, type, row) {
					var ret;
					if (type == 'display') {
						ret = '';
						if (data) {
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
				render: function(data, type, row) {
					var span = document.createElement('SPAN');
					var edit_btn = document.createElement('BUTTON');
					edit_btn.className = 'w3-button w3-green';
					edit_btn.type = 'button';
					var i = document.createElement('I');
					i.className = 'fas fa-edit';
					var label = document.createTextNode(' <%[ Edit ]%>');
					edit_btn.appendChild(i);
					edit_btn.innerHTML += '&nbsp';
					edit_btn.appendChild(label);
					edit_btn.setAttribute('onclick', 'oAPIBasicUsers.load_api_basic_user_window("' + data + '")');

					span.appendChild(edit_btn);
					return span.outerHTML;
				}.bind(this)
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
			targets: [ 3 ]
		},
		{
			className: "dt-center",
			targets: [ 2, 3 ]
		}],
		select: {
			style:    'os',
			selector: 'td:not(:last-child):not(:first-child)',
			blurable: false
		},
		order: [1, 'asc']
	});
},
set_bulk_actions: function() {
	this.table_toolbar = get_table_toolbar(this.table, this.actions, {
		actions: '<%[ Select action ]%>',
		ok: '<%[ OK ]%>'
	});
}
};

var oAPIBasicUsers = {
window_mode: '',
load_api_basic_user_window: function(name) {
	var title_add = document.getElementById('api_basic_user_window_title_add');
	var title_edit = document.getElementById('api_basic_user_window_title_edit');
	var username = document.getElementById('<%=$this->APIBasicUserUsername->ClientID%>');
	var password = document.getElementById('<%=$this->APIBasicUserPassword->ClientID%>');
	var retype_password = document.getElementById('<%=$this->RetypeAPIBasicUserPassword->ClientID%>');
	var username_req = document.getElementById('api_basic_user_username_req');
	var pass_req = document.getElementById('api_basic_user_password_req');
	var retype_pass_req = document.getElementById('api_basic_user_retype_password_req');
	var api_basic_user_win_type = document.getElementById('<%=$this->APIBasicUserWindowType->ClientID%>');
	this.clear_api_basic_user_window();
	var cb = <%=$this->LoadAPIBasicUser->ActiveControl->Javascript%>;
	cb.setCallbackParameter(name);
	cb.dispatch();
	if (name) {
		// edit existing api basic user
		title_add.style.display = 'none';
		title_edit.style.display = 'inline-block';
		api_basic_user_win_type.value = 'edit';
		username.setAttribute('readonly', '');
		username_req.style.display = 'none';
		pass_req.style.display = 'none';
		retype_pass_req.style.display = 'none';
		this.window_mode = 'edit';
	} else {
		// add new api basic user
		title_add.style.display = 'inline-block';
		title_edit.style.display = 'none';
		api_basic_user_win_type.value = 'add';
		username.removeAttribute('readonly');
		username_req.style.display = '';
		pass_req.style.display = '';
		retype_pass_req.style.display = '';
		this.window_mode = 'add';
	}
	password.type = retype_password.type = 'password';
	document.getElementById('<%=$this->APIBasicUserBconsoleCreate->ClientID%>').checked = false;
	document.getElementById('api_basic_user_console').style.display = 'none';
	document.getElementById('api_basic_user_bconsole_cfg').style.display = '';
	document.getElementById('api_basic_user_window').style.display = 'block';
	document.getElementById('api_basic_user_error').style.display = 'none';
	if (name) {
		password.focus();
	} else {
		username.focus();
	}
},
load_api_basic_user_list: function() {
	var cb = <%=$this->APIBasicUserList->ActiveControl->Javascript%>;
	cb.dispatch();
},
load_api_basic_user_list_cb: function(list, error) {
	if (error == 0) {
		oAPIBasicUserList.data = list;
		oAPIBasicUserList.init();
	}
},
clear_api_basic_user_window: function() {
	[
		'<%=$this->APIBasicUserUsername->ClientID%>',
		'<%=$this->APIBasicUserPassword->ClientID%>',
		'<%=$this->RetypeAPIBasicUserPassword->ClientID%>',
		'<%=$this->APIBasicUserBconsoleCfgPath->ClientID%>',
		'<%=$this->APIBasicUserBconsoleCfgPath->ClientID%>',
	].forEach(function(id) {
		document.getElementById(id).value = '';
	});
},
save_api_basic_user_cb: function(result) {
	if (result.error == 0) {
		document.getElementById('api_basic_user_error').style.display = 'none';
		document.getElementById('api_basic_user_window').style.display = 'none';
	} else {
		document.getElementById('api_basic_user_error').textContent = result.output;
		document.getElementById('api_basic_user_error').style.display = '';
	}
}
}

$(function() {
	oAPIBasicUsers.load_api_basic_user_list();
});
	</script>
</div>
<div id="api_basic_user_window" class="w3-modal">
	<div class="w3-modal-content w3-animate-top w3-card-4">
		<header class="w3-container w3-green">
			<span onclick="document.getElementById('api_basic_user_window').style.display = 'none';" class="w3-button w3-display-topright">&times;</span>
			<h2 id="api_basic_user_window_title_add" style="display: none"><%[ Add API basic user account ]%></h2>
			<h2 id="api_basic_user_window_title_edit" style="display: none"><%[ Edit API basic user account ]%></h2>
		</header>
		<div class="w3-container w3-margin-left w3-margin-right w3-margin-top">
			<div class="w3-row directive_field">
				<div class="w3-col w3-third"><com:TLabel ForControl="APIBasicUserUsername" Text="<%[ API basic username: ]%>" /></div>
				<div class="w3-half">
					<com:TActiveTextBox
						ID="APIBasicUserUsername"
						AutoPostBack="false"
						CausesValidation="false"
						CssClass="w3-input w3-border"
					/>
					<com:TRequiredFieldValidator
						ValidationGroup="APIBasicUserGroup"
						ControlToValidate="APIBasicUserUsername"
						ErrorMessage="<%[ Please enter username. ]%>"
						ControlCssClass="field_invalid"
						Display="Dynamic"
					/>
					<com:TRegularExpressionValidator
						ValidationGroup="APIBasicUserGroup"
						ControlToValidate="APIBasicUserUsername"
						ControlCssClass="field_invalid"
						RegularExpression="<%=BasicUserConfig::USER_PATTERN%>"
						ErrorMessage="<%[ Invalid user. User may contain a-z A-Z 0-9 characters. ]%>"
						Display="Dynamic"
						/>
				</div> &nbsp;<i id="api_basic_user_username_req" class="fa fa-asterisk w3-text-red opt_req"></i>
			</div>
			<div class="w3-row directive_field">
				<div class="w3-col w3-third"><com:TLabel ForControl="APIBasicUserPassword" Text="<%[ API basic password: ]%>" /></div>
				<div class="w3-half">
					<com:TActiveTextBox
						ID="APIBasicUserPassword"
						CssClass="w3-input w3-border"
						CausesValidation="false"
						TextMode="Password"
						MaxLength="60"
						PersistPassword="true"
					/>
					<com:TRequiredFieldValidator
						ValidationGroup="APIBasicUserGroup"
						ControlToValidate="APIBasicUserPassword"
						ErrorMessage="<%[ Please enter password. ]%>"
						ControlCssClass="field_invalid"
						Display="Dynamic"
					>
						<prop:ClientSide.OnValidate>
							sender.enabled = (oAPIBasicUsers.window_mode == 'add');
						</prop:ClientSide.OnValidate>
					</com:TRequiredFieldValidator>
					<com:TRegularExpressionValidator
						ValidationGroup="APIBasicUserGroup"
						ControlToValidate="APIBasicUserPassword"
						ControlCssClass="field_invalid"
						RegularExpression="<%=BasicUserConfig::PASSWORD_PATTERN%>"
						ErrorMessage="<%[ Password must be longer than 4 chars and not longer than 60 chars. ]%>"
						Display="Dynamic"
					/>
				</div> &nbsp;<i id="api_basic_user_password_req" class="fa fa-asterisk w3-text-red opt_req"></i>
				&nbsp;<a href="javascript:void(0)" onclick="var el = document.getElementById('<%=$this->APIBasicUserPassword->ClientID%>'); el.type = el.type == 'text' ? 'password' : 'text'" title="<%[ Show/hide password ]%>"><i class="fa fa-eye"></i></a>
				&nbsp;<a href="javascript:void(0)" onclick="document.getElementById('<%=$this->APIBasicUserPassword->ClientID%>').value = document.getElementById('<%=$this->RetypeAPIBasicUserPassword->ClientID%>').value = get_random_string('ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-_~!@#$%^&*()_+}{|?[]\\/.,', 40); return false;" title="<%[ Generate new password ]%>"><i class="fas fa-random"></i></a>
			</div>
			<div class="w3-row directive_field">
				<div class="w3-col w3-third"><com:TLabel ForControl="RetypeAPIBasicUserPassword" Text="<%[ Retype password: ]%>" /></div>
				<div class="w3-half">
					<com:TActiveTextBox
						ID="RetypeAPIBasicUserPassword"
						CssClass="w3-input w3-border"
						TextMode="Password"
						MaxLength="60"
						PersistPassword="true"
					/>
					<com:TRequiredFieldValidator
						ControlCssClass="field_invalid"
						Display="Dynamic"
						ControlToValidate="RetypeAPIBasicUserPassword"
						ValidationGroup="APIBasicUserGroup"
						ErrorMessage="<%[ Please retype password. ]%>"
					>
						<prop:ClientSide.OnValidate>
							sender.enabled = (oAPIBasicUsers.window_mode == 'add');
						</prop:ClientSide.OnValidate>
					</com:TRequiredFieldValidator>
					<com:TRegularExpressionValidator
						ControlCssClass="field_invalid"
						Display="Dynamic"
						ControlToValidate="RetypeAPIBasicUserPassword"
						RegularExpression="<%=BasicUserConfig::PASSWORD_PATTERN%>"
						ValidationGroup="APIBasicUserGroup"
						ErrorMessage="<%[ Password must be longer than 4 chars and not longer than 60 chars. ]%>"
					/>
					<com:TCompareValidator
						ControlCssClass="field_invalid"
						Display="Dynamic"
						ControlToValidate="RetypeAPIBasicUserPassword"
						ControlToCompare="APIBasicUserPassword"
						ValidationGroup="APIBasicUserGroup"
						Text="<%[ Passwords must be the same. ]%>"
					/>
				</div> &nbsp;<i id="api_basic_user_retype_password_req" class="fa fa-asterisk w3-text-red opt_req"></i>
				&nbsp;<a href="javascript:void(0)" onclick="var el = document.getElementById('<%=$this->RetypeAPIBasicUserPassword->ClientID%>'); el.type = el.type == 'text' ? 'password' : 'text'" title="<%[ Show/hide password ]%>"><i class="fa fa-eye"></i></a>

			</div>
			<div class="w3-row directive_field" id="api_basic_user_bconsole_create">
				<div class="w3-col w3-third"><com:TLabel ForControl="APIBasicUserBconsoleCreate" Text="<%[ Create dedicated Bconsole config file: ]%>" /></div>
				<div class="w3-half">
					<com:TActiveCheckBox
						ID="APIBasicUserBconsoleCreate"
						CssClass="w3-check"
						CausesValidation="false"
						Attributes.onclick="$('#api_basic_user_console').slideToggle('fast');$('#api_basic_user_bconsole_cfg').slideToggle('fast');"
						AutoPostBack="false"
					/>
				</div>
			</div>
			<div id="api_basic_user_console" style="display: none">
				<div class="w3-row directive_field">
					<div class="w3-col w3-third"><com:TLabel ForControl="APIBasicUserConsole" Text="<%[ Console ACL to use in new Bconsole config file: ]%>" /></div>
					<div class="w3-half">
						<com:TActiveDropDownList
							ID="APIBasicUserConsole"
							CssClass="w3-select w3-border"
							CausesValidation="false"
							AutoPostBack="false"
						/>
					</div>
				</div>
				<div class="w3-row directive_field">
					<div class="w3-col w3-third"><com:TLabel ForControl="APIBasicUserDirector" Text="<%[ Director for Bconsole config: ]%>" /></div>
					<div class="w3-half">
						<com:TActiveDropDownList
							ID="APIBasicUserDirector"
							CssClass="w3-select w3-border"
							CausesValidation="false"
							AutoPostBack="false"
						/>
					</div>
				</div>
			</div>
			<div class="w3-row directive_field" id="api_basic_user_bconsole_cfg">
				<div class="w3-col w3-third"><com:TLabel ForControl="APIBasicUserBconsoleCfgPath" Text="<%[ Dedicated Bconsole config file path: ]%>" /></div>
				<div class="w3-half">
					<com:TActiveTextBox
						ID="APIBasicUserBconsoleCfgPath"
						CssClass="w3-input w3-border"
						CausesValidation="false"
					/>
				</div>
			</div>
		</div>
		<footer class="w3-container w3-center">
			<button type="button" class="w3-button w3-red" onclick="document.getElementById('api_basic_user_window').style.display = 'none';"><i class="fas fa-times"></i> &nbsp;<%[ Cancel ]%></button>
			<com:TActiveLinkButton
				ID="APIBasicUserSave"
				ValidationGroup="APIBasicUserGroup"
				CausesValidation="true"
				OnCallback="saveAPIBasicUser"
				CssClass="w3-button w3-section w3-green w3-padding"
			>
				<i class="fa fa-save"></i> &nbsp;<%[ Save ]%>
			</com:TActiveLinkButton>
			<div id="api_basic_user_error" class="w3-red" style="display: none"></div>
		</footer>
	</div>
	<com:TActiveHiddenField ID="APIBasicUserWindowType" />
</div>
