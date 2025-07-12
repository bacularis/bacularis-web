<div>
	<p class="w3-hide-small"><%[ The OAuth2 clients are configured on the API hosts. To create the OAuth2 clients from this page, you need to have an 'oauth2' scope in OAuth2 account related to your current admin user account. The OAuth2 client accounts are also possible to create directly in the panel of the Bacularis API. ]%></p>
	<div id="oauth2_client_btn" class="w3-panel">
		<button type="button" id="add_oauth2_client_btn" class="w3-button w3-green" onclick="oOAuth2Clients.load_oauth2_client_window()"><i class="fa fa-plus"></i> &nbsp;<%[ Add new OAuth2 client ]%></button>
	</div>
	<table id="oauth2_client_list_table" class="display w3-table w3-striped w3-hoverable w3-margin-bottom selectable" style="width: 100%">
		<thead>
			<tr>
				<th></th>
				<th><%[ Short name ]%></th>
				<th class="w3-center">OAuth2 Client ID</th>
				<th class="w3-center">Redirect URI</th>
				<th class="w3-center">Scopes</th>
				<th class="w3-center">Dedicated BConsole config</th>
				<th class="w3-center"><%[ Action ]%></th>
			</tr>
		</thead>
		<tbody id="oauth2_client_list_body"></tbody>
		<tfoot>
			<tr>
				<th></th>
				<th><%[ Short name ]%></th>
				<th class="w3-center">OAuth2 Client ID</th>
				<th class="w3-center">Redirect URI</th>
				<th class="w3-center">Scopes</th>
				<th class="w3-center">Dedicated BConsole config</th>
				<th class="w3-center"><%[ Action ]%></th>
			</tr>
		</tfoot>
	</table>
	<p id="oauth2_client_table_footer" class="info w3-hide-medium w3-hide-small"><%[ Tip: Use left-click to select table row. Use CTRL + left-click to multiple row selection. Use SHIFT + left-click to add a range of rows to selection. ]%></p>
<com:TCallback ID="OAuth2ClientList" OnCallback="TemplateControl.setOAuth2ClientList" />
<com:TCallback ID="LoadOAuth2Client" OnCallback="TemplateControl.loadOAuth2ClientWindow" />
<com:TCallback ID="RemoveOAuth2ClientsAction" OnCallback="TemplateControl.removeOAuth2Clients" />
<script>
var oOAuth2ClientList = {
	ids: {
		oauth2_client_list: 'oauth2_client_list_table'
	},
	actions: [
		{
			action: 'remove',
			label: '<%[ Remove ]%>',
			value: 'client_id',
			callback: <%=$this->RemoveOAuth2ClientsAction->ActiveControl->Javascript%>
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
		document.getElementById(this.ids.oauth2_client_list).addEventListener('click', function(e) {
			$(function() {
				const wa = (this.table.rows({selected: true}).data().length > 0) ? 'show' : 'hide';
				$(this.table_toolbar).animate({
					width: wa
				}, 'fast');
			}.bind(this));
		}.bind(this));
	},
	set_table: function() {
		this.table = $('#' + this.ids.oauth2_client_list).DataTable({
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
				{data: 'name'},
				{data: 'client_id'},
				{
					data: 'redirect_uri',
					render: render_string_short
				},
				{
					data: 'scope',
					render: render_string_short
				},
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
					data: 'client_id',
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
						btn_edit.setAttribute('onclick', 'oOAuth2Clients.load_oauth2_client_window(\'' + data + '\')');
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
				targets: [ 6 ]
			},
			{
				className: "dt-center",
				targets: [ 2, 3, 4, 5 ]
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

var oOAuth2Clients = {
load_oauth2_client_window: function(name) {
	var title_add = document.getElementById('oauth2_client_window_title_add');
	var title_edit = document.getElementById('oauth2_client_window_title_edit');
	var client_id = document.getElementById('<%=$this->OAuth2ClientClientId->ClientID%>');
	var generate_client_id = document.getElementById('oauth2_client_generate_client_id_link');
	var oauth2_client_win_type = document.getElementById('<%=$this->OAuth2ClientWindowType->ClientID%>');
	// callback is sent both for new and edit oauth2_client because there is realized
	// checking if password is allowed to set or not
	var cb = <%=$this->LoadOAuth2Client->ActiveControl->Javascript%>;
	cb.setCallbackParameter(name);
	cb.dispatch();
	if (name) {
		// edit existing oauth2_client
		title_add.style.display = 'none';
		title_edit.style.display = 'inline-block';
		oauth2_client_win_type.value = 'edit';
		generate_client_id.style.display = 'none';
		client_id.setAttribute('readonly', '');
	} else {
		// add new oauth2_client
		title_add.style.display = 'inline-block';
		title_edit.style.display = 'none';
		oauth2_client_win_type.value = 'add';
		generate_client_id.style.display = '';
		client_id.removeAttribute('readonly');
		this.clear_oauth2_client_window();
		document.getElementById('<%=$this->OAuth2ClientRedirectURI->ClientID%>').value = document.location.origin + '/web/redirect';
	}
	document.getElementById('<%=$this->OAuth2ClientBconsoleCreate->ClientID%>').checked = false;
	document.getElementById('oauth2_client_console').style.display = 'none';
	document.getElementById('oauth2_client_bconsole_cfg').style.display = '';
	document.getElementById('oauth2_client_window').style.display = 'block';
	document.getElementById('oauth2_client_error').style.display = 'none';
},
load_oauth2_client_list: function() {
	var cb = <%=$this->OAuth2ClientList->ActiveControl->Javascript%>;
	cb.dispatch();
},
load_oauth2_client_list_cb: function(list, error) {
	if (error == 0) {
		oOAuth2ClientList.data = list;
		oOAuth2ClientList.init();
	} else if (error == 7) {
		oOAuth2Clients.hide_oauth2_client_func();
	}
},
clear_oauth2_client_window: function() {
	[
		'<%=$this->OAuth2ClientClientId->ClientID%>',
		'<%=$this->OAuth2ClientClientSecret->ClientID%>',
		'<%=$this->OAuth2ClientRedirectURI->ClientID%>',
		'<%=$this->OAuth2ClientScope->ClientID%>',
		'<%=$this->OAuth2ClientBconsoleCfgPath->ClientID%>',
		'<%=$this->OAuth2ClientBconsoleCfgPath->ClientID%>',
		'<%=$this->OAuth2ClientName->ClientID%>'
	].forEach(function(id) {
		document.getElementById(id).value = '';
	});
},
save_oauth2_client_cb: function(result) {
	if (result.error == 0) {
		document.getElementById('oauth2_client_error').style.display = 'none';
		document.getElementById('oauth2_client_window').style.display = 'none';
	} else {
		document.getElementById('oauth2_client_error').textContent = result.output;
		document.getElementById('oauth2_client_error').style.display = '';
	}
},
hide_oauth2_client_func: function() {
	[
		'oauth2_client_btn',
		'oauth2_client_list_table',
		'oauth2_client_table_footer'
	].forEach(function(id) {
		document.getElementById(id).style.display = 'none';
	});
}
}

$(function() {
oOAuth2Clients.load_oauth2_client_list();
});
	</script>
</div>
<div id="oauth2_client_window" class="w3-modal">
	<div class="w3-modal-content w3-animate-top w3-card-4">
		<header class="w3-container w3-green">
			<span onclick="document.getElementById('oauth2_client_window').style.display = 'none';" class="w3-button w3-display-topright">&times;</span>
			<h2 id="oauth2_client_window_title_add" style="display: none"><%[ Add OAuth2 client account ]%></h2>
			<h2 id="oauth2_client_window_title_edit" style="display: none"><%[ Edit OAuth2 client account ]%></h2>
		</header>
		<div class="w3-container w3-margin-left w3-margin-right w3-margin-top">
			<div class="w3-row directive_field">
				<div class="w3-col w3-third"><com:TLabel ForControl="OAuth2ClientClientId" Text="<%[ OAuth2 Client ID: ]%>" /></div>
				<div class="w3-half">
					<com:TActiveTextBox
						ID="OAuth2ClientClientId"
						AutoPostBack="false"
						CausesValidation="false"
						MaxLength="32"
						CssClass="w3-input w3-border"
					/>
					<com:TRequiredFieldValidator
						ValidationGroup="OAuth2ClientGroup"
						ControlToValidate="OAuth2ClientClientId"
						ErrorMessage="<%[ Please enter Client ID. ]%>"
						ControlCssClass="field_invalid"
						Display="Dynamic"
					>
					</com:TRequiredFieldValidator>
					<com:TRegularExpressionValidator
						ValidationGroup="OAuth2ClientGroup"
						ControlToValidate="OAuth2ClientClientId"
						ControlCssClass="field_invalid"
						RegularExpression="<%=OAuth2::CLIENT_ID_PATTERN%>"
						ErrorMessage="<%[ Invalid Client ID value. Client ID may contain a-z A-Z 0-9 - _ characters. ]%>"
						Display="Dynamic"
						/>
					<a id="oauth2_client_generate_client_id_link" href="javascript:void(0)" onclick="document.getElementById('<%=$this->OAuth2ClientClientId->ClientID%>').value = get_random_string('ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-_', 32);" style="display: none"><%[ generate ]%></a>
				</div> &nbsp;<i class="fa fa-asterisk w3-text-red opt_req"></i>
			</div>
			<div class="w3-row directive_field">
				<div class="w3-col w3-third"><com:TLabel ForControl="OAuth2ClientClientSecret" Text="<%[ OAuth2 Client Secret: ]%>" /></div>
				<div class="w3-half">
					<com:TActiveTextBox
						ID="OAuth2ClientClientSecret"
						CssClass="w3-input w3-border"
						CausesValidation="false"
						MaxLength="50"
					/>
					<com:TRequiredFieldValidator
						ValidationGroup="OAuth2ClientGroup"
						ControlToValidate="OAuth2ClientClientSecret"
						ErrorMessage="<%[ Please enter Client Secret. ]%>"
						ControlCssClass="field_invalid"
						Display="Dynamic"
					/>
					<com:TRegularExpressionValidator
						ValidationGroup="OAuth2ClientGroup"
						ControlToValidate="OAuth2ClientClientSecret"
						ControlCssClass="field_invalid"
						RegularExpression="<%=OAuth2::CLIENT_SECRET_PATTERN%>"
						ErrorMessage="<%[ Invalid Client Secret value. Client Secret may contain any character that is not a whitespace character. ]%>"
						Display="Dynamic"
					/>
					<a href="javascript:void(0)" onclick="document.getElementById('<%=$this->OAuth2ClientClientSecret->ClientID%>').value = get_random_string('ABCDEFabcdef0123456789', 40); return false;"><%[ generate ]%></a>
				</div> &nbsp;<i class="fa fa-asterisk w3-text-red opt_req"></i>
			</div>
			<div class="w3-row directive_field">
				<div class="w3-col w3-third"><com:TLabel ForControl="OAuth2ClientRedirectURI" Text="<%[ OAuth2 Redirect URI (example: https://bacularis:9097/web/redirect): ]%>" /></div>
				<div class="w3-half">
					<com:TActiveTextBox
						ID="OAuth2ClientRedirectURI"
						CssClass="w3-input w3-border"
						CausesValidation="false"
					/>
					<com:TRequiredFieldValidator
						ValidationGroup="OAuth2ClientGroup"
						ControlToValidate="OAuth2ClientRedirectURI"
						ControlCssClass="field_invalid"
						ErrorMessage="<%[ Please enter Redirect URI. ]%>"
						Display="Dynamic"
					/>
				</div> &nbsp;<i class="fa fa-asterisk w3-text-red opt_req"></i>
			</div>
			<div class="w3-row directive_field">
				<div class="w3-col w3-third"><com:TLabel ForControl="OAuth2ClientScope" Text="<%[ OAuth2 scopes (space separated): ]%>" /></div>
				<div class="w3-half">
					<com:TActiveTextBox
						ID="OAuth2ClientScope"
						CssClass="w3-input w3-border"
						CausesValidation="false"
						TextMode="MultiLine"
					/>
					<a href="javascript:void(0)" onclick="set_scopes('<%=$this->OAuth2ClientScope->ClientID%>');" style="vertical-align: top"><%[ set all scopes ]%></a>
					<com:TRequiredFieldValidator
						ValidationGroup="OAuth2ClientGroup"
						ControlToValidate="OAuth2ClientScope"
						ControlCssClass="field_invalid"
						ErrorMessage="<%[ Please enter OAuth2 scopes. ]%>"
						Display="Dynamic"
					/>
				</div> &nbsp;<i class="fa fa-asterisk w3-text-red opt_req"></i>
			</div>
			<div class="w3-row directive_field" id="oauth2_client_bconsole_create">
				<div class="w3-col w3-third"><com:TLabel ForControl="OAuth2ClientBconsoleCreate" Text="<%[ Create dedicated Bconsole config file: ]%>" /></div>
				<div class="w3-half">
					<com:TActiveCheckBox
						ID="OAuth2ClientBconsoleCreate"
						CssClass="w3-check"
						CausesValidation="false"
						Attributes.onclick="$('#oauth2_client_console').slideToggle('fast');$('#oauth2_client_bconsole_cfg').slideToggle('fast');"
						AutoPostBack="false"
					/>
				</div>
			</div>
			<div id="oauth2_client_console" style="display: none">
				<div class="w3-row directive_field">
					<div class="w3-col w3-third"><com:TLabel ForControl="OAuth2ClientConsole" Text="<%[ Console ACL to use in new Bconsole config file: ]%>" /></div>
					<div class="w3-half">
						<com:TActiveDropDownList
							ID="OAuth2ClientConsole"
							CssClass="w3-select w3-border"
							CausesValidation="false"
							AutoPostBack="false"
						/>
					</div>
				</div>
				<div class="w3-row directive_field">
					<div class="w3-col w3-third"><com:TLabel ForControl="OAuth2ClientDirector" Text="<%[ Director for Bconsole config: ]%>" /></div>
					<div class="w3-half">
						<com:TActiveDropDownList
							ID="OAuth2ClientDirector"
							CssClass="w3-select w3-border"
							CausesValidation="false"
							AutoPostBack="false"
						/>
					</div>
				</div>
			</div>
			<div class="w3-row directive_field" id="oauth2_client_bconsole_cfg">
				<div class="w3-col w3-third"><com:TLabel ForControl="OAuth2ClientBconsoleCfgPath" Text="<%[ Dedicated Bconsole config file path: ]%>" /></div>
				<div class="w3-half">
					<com:TActiveTextBox
						ID="OAuth2ClientBconsoleCfgPath"
						CssClass="w3-input w3-border"
						CausesValidation="false"
					/>
				</div>
			</div>
			<div class="w3-row directive_field">
				<div class="w3-col w3-third"><com:TLabel ForControl="OAuth2ClientName" Text="<%[ Short name: ]%>" /></div>
				<div class="w3-half">
					<com:TActiveTextBox
						ID="OAuth2ClientName"
						CssClass="w3-input w3-border"
						CausesValidation="false"
					/>
				</div>
			</div>
		</div>
		<footer class="w3-container w3-center">
			<button type="button" class="w3-button w3-red" onclick="document.getElementById('oauth2_client_window').style.display = 'none';"><i class="fas fa-times"></i> &nbsp;<%[ Cancel ]%></button>
			<com:TActiveLinkButton
				ID="OAuth2ClientSave"
				ValidationGroup="OAuth2ClientGroup"
				CausesValidation="true"
				OnCallback="saveOAuth2Client"
				CssClass="w3-button w3-section w3-green w3-padding"
			>
				<i class="fa fa-save"></i> &nbsp;<%[ Save ]%>
			</com:TActiveLinkButton>
			<div id="oauth2_client_error" class="w3-red" style="display: none"></div>
		</footer>
	</div>
	<com:TActiveHiddenField ID="OAuth2ClientWindowType" />
</div>
