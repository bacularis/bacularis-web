<div>
	<p class="w3-hide-small"><%[ The API hosts define connection parameters to hosts with the Bacularis API instances. You can create the API host connections dedicated for specific users by assigning API hosts to them on the Users tab. There is possible to create many API host connections to the same Bacularis API instance. ]%></p>
	<div class="w3-panel">
		<button type="button" id="add_api_host_btn" class="w3-button w3-green" onclick="oAPIHosts.load_api_host_window()"><i class="fa fa-plus"></i> &nbsp;<%[ Add new API host ]%></button>
	</div>
	<!-- Tag tools -->
	<com:Bacularis.Web.Portlets.TagTools ID="TagToolsAPIHostList" ViewName="api_host_list" />
	<table id="api_host_list_table" class="display w3-table w3-striped w3-hoverable w3-margin-bottom selectable" style="width: 100%">
		<thead>
			<tr>
				<th></th>
				<th><%[ Short name ]%></th>
				<th class="w3-center">Protocol</th>
				<th class="w3-center">IP address/hostname</th>
				<th class="w3-center">Port</th>
				<th class="w3-center">Access method</th>
				<th class="w3-center"><%[ Tag ]%></th>
				<th class="w3-center"><%[ Action ]%></th>
			</tr>
		</thead>
		<tbody id="api_host_list_body"></tbody>
		<tfoot>
			<tr>
				<th></th>
				<th><%[ Short name ]%></th>
				<th class="w3-center">Protocol</th>
				<th class="w3-center">IP address/hostname</th>
				<th class="w3-center">Port</th>
				<th class="w3-center">Access method</th>
				<th class="w3-center"><%[ Tag ]%></th>
				<th class="w3-center"><%[ Action ]%></th>
			</tr>
		</tfoot>
	</table>
	<p class="info w3-hide-medium w3-hide-small"><%[ Tip: Use left-click to select table row. Use CTRL + left-click to multiple row selection. Use SHIFT + left-click to add a range of rows to selection. ]%></p>
<com:TCallback ID="APIHostList" OnCallback="TemplateControl.setAPIHostList" />
<com:TCallback ID="LoadAPIHost" OnCallback="TemplateControl.loadAPIHostWindow" />
<com:TCallback ID="RemoveAPIHostsAction" OnCallback="TemplateControl.removeAPIHosts" />
<script>
var oAPIHostList = {
ids: {
	api_host_list: 'api_host_list_table'
},
actions: [
	{
		action: 'remove',
		label: '<%[ Remove ]%>',
		value: 'name',
		callback: <%=$this->RemoveAPIHostsAction->ActiveControl->Javascript%>
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
		oAPIHostList.set_filters(this.table);
		this.table_toolbar.style.display = 'none';
	}
},
set_events: function() {
	document.getElementById(this.ids.api_host_list).addEventListener('click', function(e) {
		$(function() {
			const wa = (this.table.rows({selected: true}).data().length > 0) ? 'show' : 'hide';
			$(this.table_toolbar).animate({
				width: wa
			}, 'fast');
		}.bind(this));
	}.bind(this));
},
set_table: function() {
	this.table = $('#' + this.ids.api_host_list).DataTable({
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
			{data: 'protocol'},
			{data: 'address'},
			{data: 'port'},
			{
				data: 'auth_type',
				render: function (data, type, row) {
					var at = data;
					if (at == 'basic') {
						at = 'Basic';
					} else if (at == 'oauth2') {
						at = 'OAuth2';
					}
					return at;
				}
			},
			{
				data: 'name',
				render: (data, type, row) => {
					const id = 'name';
					const tt_obj = oTagTools_<%=$this->TagToolsAPIHostList->ClientID%>;
					const table = 'oAPIHostList.table';
					return render_tags(type, id, data, tt_obj, table);
				}
			},
			{
				data: 'name',
				render: function (data, type, row) {
					let btns = '';

					// Set access button
					if (data !== 'Main') {
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
						access_btn.setAttribute('onclick', 'oAPIHosts.load_access_window("' + data + '")');
						span.appendChild(access_btn);
						span.style.marginRight = '5px';
						btns += span.outerHTML;
					}

					// Edit button
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
					btn_edit.setAttribute('onclick', 'oAPIHosts.load_api_host_window(\'' + data + '\')');
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
			targets: [ 7 ]
		},
		{
			className: "dt-center",
			targets: [ 2, 3, 4, 5, 6 ]
		}],
		select: {
			style:    'os',
			selector: 'td:not(:last-child):not(:first-child):not(:nth-last-child(2))',
			blurable: false
		},
		order: [1, 'asc'],
		initComplete: function () {
			oAPIHostList.set_filters(this.api());
		}
	});
},
set_filters: function(api) {
	api.columns([2, 3, 4, 5]).every(function () {
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
}
};

var oAPIHosts = {
load_api_host_window: function(name) {
	var title_add = document.getElementById('api_host_window_title_add');
	var title_edit = document.getElementById('api_host_window_title_edit');
	const host_groups = document.getElementById('api_host_groups');
	const host_groups_area = document.getElementById('api_host_api_host_group');
	host_groups_area.style.display = 'none';
	const host_groups_chkb = document.getElementById('<%=$this->APIHostUseHostGroups->ClientID%>');
	host_groups_chkb.checked = false;
	var api_host_win_type = document.getElementById('<%=$this->APIHostWindowType->ClientID%>');
	var host_name = document.getElementById('<%=$this->APIHostName->ClientID%>');
	// callback is sent both for new and edit api_host because there is realized
	// checking if password is allowed to set or not
	var cb = <%=$this->LoadAPIHost->ActiveControl->Javascript%>;
	cb.setCallbackParameter(name);
	cb.dispatch();
	if (name) {
		// edit existing api_host
		title_add.style.display = 'none';
		title_edit.style.display = 'inline-block';
		host_groups.style.display = 'none';
		api_host_win_type.value = 'edit';
		host_name.setAttribute('readonly', '');
	} else {
		// add new api_host
		title_add.style.display = 'inline-block';
		title_edit.style.display = 'none';
		host_groups.style.display = 'block';
		api_host_win_type.value = 'add';
		host_name.removeAttribute('readonly');
		this.clear_api_host_window();
	}
	document.getElementById('api_host_window').style.display = 'block';
},
load_api_host_list: function() {
	var cb = <%=$this->APIHostList->ActiveControl->Javascript%>;
	cb.dispatch();
},
load_api_host_list_cb: function(list) {
	oAPIHostList.data = list;
	oAPIHostList.init();
},
clear_api_host_window: function() {
	[
		'<%=$this->APIHostAddress->ClientID%>',
		'<%=$this->APIHostPort->ClientID%>',
		'<%=$this->APIHostOAuth2ClientId->ClientID%>',
		'<%=$this->APIHostOAuth2ClientSecret->ClientID%>',
		'<%=$this->APIHostOAuth2RedirectURI->ClientID%>',
		'<%=$this->APIHostOAuth2Scope->ClientID%>',
		'<%=$this->APIHostName->ClientID%>',
		'<%=$this->APIHostBasicLogin->ClientID%>',
		'<%=$this->APIHostBasicPassword->ClientID%>'

	].forEach(function(id) {
		document.getElementById(id).value = '';
	});

	document.getElementById('<%=$this->APIHostProtocol->ClientID%>').value = 'https';

	[
		'<%=$this->APIHostTestResultOk->ClientID%>',
		'<%=$this->APIHostTestResultErr->ClientID%>',
		'<%=$this->APIHostCatalogSupportYes->ClientID%>',
		'<%=$this->APIHostCatalogSupportNo->ClientID%>',
		'<%=$this->APIHostConsoleSupportYes->ClientID%>',
		'<%=$this->APIHostConsoleSupportNo->ClientID%>',
		'<%=$this->APIHostConfigSupportYes->ClientID%>',
		'<%=$this->APIHostConfigSupportNo->ClientID%>',
		'<%=$this->APIHostTestLoader->ClientID%>'
	].forEach(function(id) {
		document.getElementById(id).style.display= 'none';
	});
},
save_api_host_cb: function() {
	document.getElementById('api_host_window').style.display = 'none';
},
load_access_window: function(name) {
	this.clear_access_window();
	document.getElementById('api_host_access_window_console').style.display = 'none';
	const cb = <%=$this->LoadAPIHostResourceAccess->ActiveControl->Javascript%>;
	cb.setCallbackParameter(name);
	cb.dispatch();
	document.getElementById('api_host_access_window_title').textContent = name;
	document.getElementById('api_host_access_window').style.display = 'block';
	document.getElementById('<%=$this->APIHostResourceAccessName->ClientID%>').value = name;
},
clear_access_window: function() {
	// empty fields
	[
		'<%=$this->APIHostResourceAccessJobs->ClientID%>'
	].forEach((id) => {
		$('#' + id).empty();
	});

	// reset radio buttons
	document.getElementById('<%=$this->APIHostResourceAccessAllResources->ClientID%>').checked = true;
	document.getElementById('api_host_access_window_select_jobs').style.display = 'none';
	document.getElementById('api_host_access_window_error').style.display = 'none';
},
unassign_console: function() {
	const api_host = document.getElementById('<%=$this->APIHostResourceAccessName->ClientID%>').value;
	const cb = <%=$this->UnassignAPIHostConsole->ActiveControl->Javascript%>;
	cb.setCallbackParameter(api_host);
	cb.dispatch();
}
}

$(function() {
oAPIHosts.load_api_host_list();
});
	</script>
</div>
<div id="api_host_window" class="w3-modal">
	<div class="w3-modal-content w3-animate-top w3-card-4">
		<header class="w3-container w3-green">
			<span onclick="document.getElementById('api_host_window').style.display = 'none';" class="w3-button w3-display-topright">&times;</span>
			<h2 id="api_host_window_title_add" style="display: none"><%[ Add API host ]%></h2>
			<h2 id="api_host_window_title_edit" style="display: none"><%[ Edit API host ]%></h2>
		</header>
		<div class="w3-container w3-margin-left w3-margin-right w3-margin-top">
			<div id="api_host_settings">
				<div class="w3-row directive_field">
					<div class="w3-col w3-third"><com:TLabel ForControl="APIHostSettings" Text="<%[ Get existing API host settings: ]%>" /></div>
					<div class="w3-col w3-third">
						<com:TActiveDropDownList
							ID="APIHostSettings"
							CssClass="w3-select w3-border"
							CausesValidation="false"
							OnCallback="loadAPIHostSettings"
						>
							<prop:ClientSide.OnLoading>
								document.getElementById('api_host_settings_loading').style.visibility = 'visible';
							</prop:ClientSide.OnLoading>
							<prop:ClientSide.OnComplete>
								document.getElementById('api_host_settings_loading').style.visibility = 'hidden';
							</prop:ClientSide.OnComplete>
						</com:TActiveDropDownList>
					</div> &nbsp;<i id="api_host_settings_loading" class="fas fa-sync w3-spin" style="visibility: hidden; margin: 10px 5px"></i>
				</div>
			</div>
			<div class="w3-row directive_field">
				<div class="w3-col w3-third"><com:TLabel ForControl="APIHostProtocol" Text="<%[ Protocol: ]%>" /></div>
				<div class="w3-col w3-third">
					<com:TActiveDropDownList ID="APIHostProtocol" CssClass="w3-select w3-border" Width="150px" CausesValidation="false">
						<com:TListItem Value="http" Text="HTTP" />
						<com:TListItem Value="https" Text="HTTPS" Selected="true"/>
					</com:TActiveDropDownList>&nbsp;<i class="fa fa-asterisk w3-text-red" style="line-height: 40px"></i>
				</div>
			</div>
			<div class="w3-row directive_field">
				<div class="w3-col w3-third"><com:TLabel ForControl="APIHostAddress" Text="<%[ IP Address/Hostname: ]%>" /></div>
				<div class="w3-col w3-half">
					<com:TActiveTextBox ID="APIHostAddress" CssClass="w3-input w3-border" CausesValidation="false" />
					<com:TRequiredFieldValidator ValidationGroup="APIHostGroup" CssClass="validator-block" Display="Dynamic" ControlCssClass="invalidate" ControlToValidate="APIHostAddress" Text="<%[ Please enter API address. ]%>" />
				</div>
				&nbsp;<i class="fa fa-asterisk w3-text-red" style="line-height: 40px"></i>
			</div>
			<div class="w3-row directive_field">
				<div class="w3-col w3-third"><com:TLabel ForControl="APIHostPort" Text="<%[ Port: ]%>" /></div>
				<div class="w3-col w3-third">
					<com:TActiveTextBox ID="APIHostPort" CssClass="w3-input w3-border" CausesValidation="false" Text="9097" Width="70px" Style="display: inline-block" />
					&nbsp;<i class="fa fa-asterisk w3-text-red" style="line-height: 40px"></i>
					<com:TRequiredFieldValidator ValidationGroup="APIHostGroup" CssClass="validator-block" Display="Dynamic" ControlCssClass="invalidate" ControlToValidate="APIHostPort" Text="<%[ Please enter API port. ]%>" />
				</div>
			</div>
			<div class="auth_setting">
				<div class="w3-row directive_field">
					<com:TActiveRadioButton
						ID="APIHostAuthOAuth2"
						GroupName="SelectAuth"
						CssClass="w3-radio"
						Attributes.onclick="$('#configure_basic_auth').hide();$('#configure_oauth2_auth').show();"
					/>
					<com:TLabel
						ForControl="APIHostAuthOAuth2"
						CssClass="normal w3-radio"
						Style="vertical-align: super"
						Text="<%[ Use OAuth2 for authorization and authentication ]%>"
					/>
				</div>
				<div class="w3-row directive_field">
					<com:TActiveRadioButton
						ID="APIHostAuthBasic"
						GroupName="SelectAuth"
						Checked="true"
						CssClass="w3-radio"
						Attributes.onclick="$('#configure_oauth2_auth').hide();$('#configure_basic_auth').show();"
					/>
					<com:TLabel
						ForControl="APIHostAuthBasic"
						CssClass="normal w3-radio"
						Style="vertical-align: super"
						Text="<%[ Use HTTP Basic authentication ]%>"
					/>
				</div>
			</div>
			<div id="configure_basic_auth" class="w3-margin-top" style="display: <%=($this->APIHostAuthBasic->Checked === true) ? '' : 'none';%>">
				<div class="w3-row directive_field">
					<div class="w3-col w3-third"><com:TLabel ForControl="APIHostBasicUserSettings" Text="<%[ Get existing basic user name: ]%>" /></div>
					<div class="w3-col w3-third">
						<com:TActiveDropDownList
							ID="APIHostBasicUserSettings"
							CssClass="w3-select w3-border"
							CausesValidation="false"
							OnCallback="loadAPIBasicUserSettings"
						>
							<prop:ClientSide.OnLoading>
								document.getElementById('api_host_basic_user_settings_loading').style.visibility = 'visible';
							</prop:ClientSide.OnLoading>
							<prop:ClientSide.OnComplete>
								document.getElementById('api_host_basic_user_settings_loading').style.visibility = 'hidden';
							</prop:ClientSide.OnComplete>
						</com:TActiveDropDownList>
					</div> &nbsp;<i id="api_host_basic_user_settings_loading" class="fas fa-sync w3-spin" style="visibility: hidden; margin: 10px 5px"></i>
				</div>
				<div class="w3-row directive_field">
					<div class="w3-col w3-third"><com:TLabel ForControl="APIHostBasicLogin" Text="<%[ API Login: ]%>" /></div>
					<div class="w3-col w3-half">
						<com:TActiveTextBox
							ID="APIHostBasicLogin"
							CssClass="w3-input w3-border"
							CausesValidation="false"
						/>
						<com:TRequiredFieldValidator
							CssClass="validator-block"
							Display="Dynamic"
							ControlCssClass="invalidate"
							ControlToValidate="APIHostBasicLogin"
							ValidationGroup="APIHostBasic"
							Text="<%[ Please enter API login. ]%>"
						 />
					</div>
					&nbsp;<i class="fa fa-asterisk w3-text-red" style="line-height: 40px"></i>
				</div>
				<div class="w3-row directive_field">
					<div class="w3-col w3-third"><com:TLabel ForControl="APIHostBasicPassword" Text="<%[ API Password: ]%>" /></div>
					<div class="w3-col w3-half">
						<com:TActiveTextBox
							ID="APIHostBasicPassword"
							TextMode="Password"
							CssClass="w3-input w3-border"
							CausesValidation="false"
							PersistPassword="true"
						/>
						<com:TRequiredFieldValidator
							CssClass="validator-block"
							Display="Dynamic"
							ControlCssClass="invalidate"
							ControlToValidate="APIHostBasicPassword"
							ValidationGroup="APIHostBasic"
							Text="<%[ Please enter API password. ]%>"
						/>
					</div>
					&nbsp;<i class="fa fa-asterisk w3-text-red" style="line-height: 40px"></i>
				</div>
			</div>
			<div id="configure_oauth2_auth" class="w3-margin-top" style="display: <%=($this->APIHostAuthOAuth2->Checked === true) ? '' : 'none';%>">
				<div class="w3-row directive_field">
					<div class="w3-col w3-third"><com:TLabel ForControl="APIHostOAuth2ClientSettings" Text="<%[ Get existing OAuth2 client settings: ]%>" /></div>
					<div class="w3-col w3-third">
						<com:TActiveDropDownList
							ID="APIHostOAuth2ClientSettings"
							CssClass="w3-select w3-border"
							CausesValidation="false"
							OnCallback="loadOAuth2ClientSettings"
						>
							<prop:ClientSide.OnLoading>
								document.getElementById('api_host_oauth2_client_settings_loading').style.visibility = 'visible';
							</prop:ClientSide.OnLoading>
							<prop:ClientSide.OnComplete>
								document.getElementById('api_host_oauth2_client_settings_loading').style.visibility = 'hidden';
							</prop:ClientSide.OnComplete>
						</com:TActiveDropDownList>
					</div> &nbsp;<i id="api_host_oauth2_client_settings_loading" class="fas fa-sync w3-spin" style="visibility: hidden; margin: 10px 5px"></i>
				</div>
				<div class="w3-row directive_field">
					<div class="w3-col w3-third"><com:TLabel ForControl="APIHostOAuth2ClientId" Text="<%[ OAuth2 Client ID: ]%>" /></div>
					<div class="w3-col w3-half">
						<com:TActiveTextBox
							ID="APIHostOAuth2ClientId"
							CssClass="w3-input w3-border"
							CausesValidation="false"
							MaxLength="32"
						/>
						<com:TRequiredFieldValidator
							CssClass="validator-block"
							Display="Dynamic"
							ControlCssClass="invalidate"
							ControlToValidate="APIHostOAuth2ClientId"
							ValidationGroup="APIHostOAuth2"
							Text="<%[ Please enter Client ID. ]%>"
						/>
						<com:TRegularExpressionValidator
							CssClass="validator-block"
							Display="Dynamic"
							ControlCssClass="invalidate"
							ControlToValidate="APIHostOAuth2ClientId"
							RegularExpression="<%=OAuth2::CLIENT_ID_PATTERN%>"
							ValidationGroup="APIHostOAuth2"
							Text="<%[ Invalid Client ID value. Client ID may contain a-z A-Z 0-9 - _ characters. ]%>"
							/>
					</div>
					&nbsp;<i class="fa fa-asterisk w3-text-red" style="line-height: 40px"></i>
				</div>
				<div class="w3-row directive_field">
					<div class="w3-col w3-third"><com:TLabel ForControl="APIHostOAuth2ClientSecret" Text="<%[ OAuth2 Client Secret: ]%>" /></div>
					<div class="w3-col w3-half">
						<com:TActiveTextBox
							ID="APIHostOAuth2ClientSecret"
							CssClass="w3-input w3-border"
							CausesValidation="false"
							MaxLength="50"
						/>
						<com:TRequiredFieldValidator
							CssClass="validator-block"
							Display="Dynamic"
							ControlCssClass="invalidate"
							ControlToValidate="APIHostOAuth2ClientSecret"
							ValidationGroup="APIHostOAuth2"
							Text="<%[ Please enter Client Secret. ]%>"
						/>
						<com:TRegularExpressionValidator
							CssClass="validator-block"
							Display="Dynamic"
							ControlCssClass="invalidate"
							ControlToValidate="APIHostOAuth2ClientSecret"
							RegularExpression="<%=OAuth2::CLIENT_SECRET_PATTERN%>"
							ValidationGroup="APIHostOAuth2"
							Text="<%[ Invalid Client Secret value. Client Secret may contain any character that is not a whitespace character. ]%>"
						/>
					</div>
					&nbsp;<i class="fa fa-asterisk w3-text-red" style="line-height: 40px"></i>
				</div>
				<div class="w3-row directive_field">
					<div class="w3-col w3-third"><com:TLabel ForControl="APIHostOAuth2RedirectURI" Text="<%[ OAuth2 Redirect URI (example: https://bacularis:9097/web/redirect): ]%>" /></div>
					<div class="w3-col w3-half">
						<com:TActiveTextBox
							ID="APIHostOAuth2RedirectURI"
							CssClass="w3-input w3-border"
							CausesValidation="false"
						/>
						<com:TRequiredFieldValidator
							CssClass="validator-block"
							Display="Dynamic"
							ControlCssClass="invalidate"
							ControlToValidate="APIHostOAuth2RedirectURI"
							ValidationGroup="APIHostOAuth2"
							Text="<%[ Please enter Redirect URI. ]%>"
						/>
					</div>
					&nbsp;<i class="fa fa-asterisk w3-text-red" style="line-height: 40px"></i>
				</div>
				<div class="w3-row directive_field">
					<div class="w3-col w3-third"><com:TLabel ForControl="APIHostOAuth2Scope" Text="<%[ OAuth2 scopes (space separated): ]%>" /></div>
					<div class="w3-col w3-half">
						<com:TActiveTextBox
							ID="APIHostOAuth2Scope"
							CssClass="w3-input w3-border"
							CausesValidation="false"
							TextMode="MultiLine"
						/>
						<com:TRequiredFieldValidator
							CssClass="validator-block"
							Display="Dynamic"
							ControlCssClass="invalidate"
							ControlToValidate="APIHostOAuth2Scope"
							ValidationGroup="APIHostOAuth2"
							Text="<%[ Please enter OAuth2 scopes. ]%>"
						/>
					</div>
					&nbsp;<i class="fa fa-asterisk w3-text-red" style="line-height: 40px"></i>
				</div>
			</div>
			<div class="w3-row w3-section">
				<div class="w3-col w3-third"><com:TLabel ForControl="APIHostConnectionTest" Text="<%[ API connection test: ]%>" /></div>
				<div class="w3-col w3-half">
					<table border="0" cellpadding="1px" id="new_host_status">
						<tr>
							<td align="center" valign="middle">
								<com:TActiveLinkButton ID="APIHostConnectionTest" CausesValidation="true" OnCallback="connectionAPITest" CssClass="w3-button w3-green">
									<prop:ClientSide.OnLoading>
										$('#<%=$this->APIHostTestResultOk->ClientID%>').hide();
										$('#<%=$this->APIHostTestResultErr->ClientID%>').hide();
										$('#<%=$this->APIHostCatalogSupportYes->ClientID%>').hide();
										$('#<%=$this->APIHostCatalogSupportNo->ClientID%>').hide();
										$('#<%=$this->APIHostConsoleSupportYes->ClientID%>').hide();
										$('#<%=$this->APIHostConsoleSupportNo->ClientID%>').hide();
										$('#<%=$this->APIHostConfigSupportYes->ClientID%>').hide();
										$('#<%=$this->APIHostConfigSupportNo->ClientID%>').hide();
										$('#<%=$this->APIHostTestLoader->ClientID%>').show();
									</prop:ClientSide.OnLoading>
									<prop:ClientSide.OnComplete>
										$('#<%=$this->APIHostTestLoader->ClientID%>').hide();
									</prop:ClientSide.OnComplete>
									<i class="fa fa-play"></i> &nbsp;<%[ test ]%>
								</com:TActiveLinkButton>
							</td>
							<td valign="middle">
								<com:TActiveLabel ID="APIHostTestLoader" Display="None"><i class="fa fa-sync w3-spin"></i></com:TActiveLabel>
								<com:TActiveLabel ID="APIHostTestResultOk" Display="None" CssClass="w3-text-green" EnableViewState="false"><i class="fa fa-check"></i> &nbsp;<%[ OK ]%></com:TActiveLabel>
								<com:TActiveLabel ID="APIHostTestResultErr" Display="None" CssClass="w3-text-red" EnableViewState="false"><i class="fa fa-times"></i> &nbsp;<%[ Connection error ]%></com:TActiveLabel>
							</td>
						</tr>
						<tr>
							<td><%[ Catalog support ]%></td>
							<td>
								<com:TActiveLabel ID="APIHostCatalogSupportYes" Display="None" CssClass="w3-text-green" EnableViewState="false"><i class="fa fa-check"></i> &nbsp;<strong><%[ Supported ]%></strong></com:TActiveLabel>
								<com:TActiveLabel ID="APIHostCatalogSupportNo" Display="None" CssClass="w3-text-dark-grey" EnableViewState="false"><i class="fa fa-times"></i> &nbsp;<strong><%[ Not supported ]%></strong></com:TActiveLabel>
							</td>
						</tr>
						<tr>
							<td><%[ Console support ]%></td>
							<td>
								<com:TActiveLabel ID="APIHostConsoleSupportYes" Display="None" CssClass="w3-text-green" EnableViewState="false"><i class="fa fa-check"></i> &nbsp;<strong><%[ Supported ]%></strong></com:TActiveLabel>
								<com:TActiveLabel ID="APIHostConsoleSupportNo" Display="None" CssClass="w3-text-dark-grey" EnableViewState="false"><i class="fa fa-times"></i> &nbsp;<strong><%[ Not supported ]%></strong></com:TActiveLabel>
							</td>
						</tr>
						<tr>
							<td><%[ Config support ]%></td>
							<td>
								<com:TActiveLabel ID="APIHostConfigSupportYes" Display="None" CssClass="w3-text-green" EnableViewState="false"><i class="fa fa-check"></i> &nbsp;<strong><%[ Supported ]%></strong></com:TActiveLabel>
								<com:TActiveLabel ID="APIHostConfigSupportNo" Display="None" CssClass="w3-text-dark-grey" EnableViewState="false"><i class="fa fa-times"></i> &nbsp;<strong><%[ Not supported ]%></strong></com:TActiveLabel>
							</td>
						</tr>
					</table>
				</div>
			</div>
			<div class="w3-row w3-section">
				<div class="w3-col w3-third"><com:TLabel ForControl="APIHostName" Text="<%[ Short name: ]%>" /></div>
				<div class="w3-col w3-half">
					<com:TActiveTextBox
						ID="APIHostName"
						CssClass="w3-input w3-border"
						CausesValidation="false"
					/>
					<com:TRegularExpressionValidator
						ValidationGroup="APIHostGroup"
						RegularExpression="<%=HostConfig::HOST_NAME_PATTERN%>"
						ControlToValidate="APIHostName"
						ErrorMessage="<%[ Invalid value. ]%>"
						ControlCssClass="field_invalid"
						Display="Dynamic"
					/>
				</div>
			</div>
			<div id="api_host_groups" style="display: none">
				<div class="w3-row w3-section">
					<div class="w3-col w3-third"><com:TLabel ForControl="APIHostUseHostGroups" Text="<%[ Assign to API host groups: ]%>"/></div>
					<div class="w3-half">
						<com:TActiveCheckBox
							ID="APIHostUseHostGroups"
							AutoPostBack="false"
							CssClass="w3-check"
							Attributes.onclick="const hg = $('#api_host_api_host_group'); this.checked ? hg.show() : hg.hide();"
						/>
					</div>
				</div>
				<div id="api_host_api_host_group" class="w3-row w3-section" style="display: none">
					<div class="w3-col w3-third"><com:TLabel ForControl="APIHostGroups" Text="<%[ API host groups: ]%>"/></div>
					<div class="w3-col" style="width: 400px">
						<com:TActiveListBox
							ID="APIHostGroups"
							SelectionMode="Multiple"
							Rows="6"
							CssClass="w3-select w3-border"
							AutoPostBack="false"
						/>
						<com:TRequiredFieldValidator
							ValidationGroup="APIHostGroup"
							ControlToValidate="APIHostGroups"
							ErrorMessage="<%[ Field required. ]%>"
							ControlCssClass="field_invalid"
							Display="Dynamic"
						>
							<prop:ClientSide.OnValidate>
								const radio = document.getElementById('<%=$this->APIHostUseHostGroups->ClientID%>');
								sender.enabled = radio.checked;
							</prop:ClientSide.OnValidate>
						</com:TRequiredFieldValidator>
						<p style="margin: 0 16px 0 0"><%[ Use CTRL + left-click to multiple item selection ]%></p>
					</div> &nbsp;<i class="fa fa-asterisk w3-text-red opt_req"></i>
				</div>
			</div>
		</div>
		<footer class="w3-container w3-center">
			<button type="button" class="w3-button w3-red" onclick="document.getElementById('api_host_window').style.display = 'none';"><i class="fas fa-times"></i> &nbsp;<%[ Cancel ]%></button>
			<com:TActiveLinkButton
				ID="APIHostSave"
				ValidationGroup="APIHostGroup"
				CausesValidation="true"
				OnCallback="saveAPIHost"
				CssClass="w3-button w3-section w3-green w3-padding"
				Attributes.onclick="return fields_validation()"
			>
				<i class="fa fa-save"></i> &nbsp;<%[ Save ]%>
			</com:TActiveLinkButton>
		</footer>
	</div>
<script type="text/javascript">
var fields_validation = function() {
	var basic = document.getElementById('<%=$this->APIHostAuthBasic->ClientID%>');
	var oauth2 = document.getElementById('<%=$this->APIHostAuthOAuth2->ClientID%>');
	var validation_group;
	if (basic.checked === true) {
		validation_group = 'APIHostBasic';
	} else if (oauth2.checked === true) {
		validation_group = 'APIHostOAuth2';
	}
	return Prado.Validation.validate(Prado.Validation.getForm(), validation_group);
}
</script>
	<com:TActiveHiddenField ID="APIHostWindowType" />
</div>
<div id="api_host_access_window" class="w3-modal">
	<div class="w3-modal-content w3-animate-top w3-card-4">
		<header class="w3-container w3-green">
			<span onclick="document.getElementById('api_host_access_window').style.display = 'none';" class="w3-button w3-display-topright">&times;</span>
			<h2><span id="api_host_access_window_title"></span> - <%[ Set API host access to resources ]%> &nbsp;<i id="get_api_host_window_loader" class="fa fa-sync w3-spin" style="visibility: hidden;"></i></h2>
		</header>
		<div class="w3-container w3-margin-left w3-margin-right w3-margin-top">
			<h3><%[ Resource access ]%></h3>
			<div class="w3-row directive_field">
				<p><com:TActiveRadioButton
					ID="APIHostResourceAccessAllResources"
					GroupName="APIHostResourceAccess"
					CssClass="w3-radio"
					Attributes.onclick="$('#api_host_access_window_select_jobs').slideUp();"
					Checked="true"
					/> <label for="<%=$this->APIHostResourceAccessAllResources->ClientID%>"><%[ Access to all shared API host resources (all jobs, all clients, all storages...etc.) ]%></label></p>
				<p><com:TActiveRadioButton
					ID="APIHostResourceAccessSelectedResources"
					GroupName="APIHostResourceAccess"
					CssClass="w3-radio"
					Attributes.onclick="$('#api_host_access_window_select_jobs').slideDown();"
					/> <label for="<%=$this->APIHostResourceAccessSelectedResources->ClientID%>"><%[ Access to selected resources only ]%></label></p>
			</div>
			<div id="api_host_access_window_error" class="w3-red" style="display: none"></div>
			<div id="api_host_access_window_select_jobs" class="w3-row directive_field" style="display: none">
				<div id="api_host_access_window_console" class="w3-section" style="display: none"><%[ Bacula Console ACL ]%>: <strong><%[ Assigned ]%></strong> ( <a href="javascript:void(0)" onclick="oAPIHosts.unassign_console();"><%[ unassign ]%></a> )</div>
				<div class="w3-col w3-third"><com:TLabel ForControl="APIHostResourceAccessJobs" Text="<%[ API host jobs: ]%>"/></div>
				<div class="w3-twothird">
					<com:TActiveListBox
						ID="APIHostResourceAccessJobs"
						SelectionMode="Multiple"
						Rows="10"
						CssClass="w3-select w3-border"
						ValidationGroup="APIHostResourceAccessGroup"
						AutoPostBack="false"
					/> &nbsp;<i class="fa fa-asterisk w3-text-red opt_req" style="vertical-align: top"></i>
					<com:TRequiredFieldValidator
						ControlCssClass="field_invalid"
						Display="Dynamic"
						ControlToValidate="APIHostResourceAccessJobs"
						ValidationGroup="APIHostResourceAccessGroup"
						ErrorMessage="<%[ Field required. ]%>"
					>
						<prop:ClientSide.OnValidate>
							const radio = document.getElementById('<%=$this->APIHostResourceAccessSelectedResources->ClientID%>');
							sender.enabled = radio.checked;
						</prop:ClientSide.OnValidate>
					</com:TRequiredFieldValidator>

					<p style="margin: 0 16px 0 0"><%[ Use CTRL + left-click to multiple item selection ]%></p>
				</div>
			</div>
			<h3><%[ Resource permissions ]%></h3>
			<p class="italic"><%[ By default, all resources have read-write permissions. ]%></p>
			<com:Bacularis.Common.Portlets.BaculaResourcePermissions
				ID="APIHostResourcePermissions"
			/>
		</div>
		<footer class="w3-container w3-center">
			<button type="button" class="w3-button w3-red" onclick="document.getElementById('api_host_access_window').style.display = 'none';"><i class="fas fa-times"></i> &nbsp;<%[ Cancel ]%></button>
			<com:TActiveLinkButton
				ID="APIHostResourceAccessSave"
				ValidationGroup="APIHostResourceAccessGroup"
				CausesValidation="true"
				OnCallback="saveAPIHostResourceAccess"
				CssClass="w3-button w3-section w3-green w3-padding"
				Attributes.onclick="<%=$this->APIHostResourcePermissions->ClientID%>ResourcePermissions.save_user_props();"
			>
				<i class="fa fa-save"></i> &nbsp;<%[ Save ]%>
			</com:TActiveLinkButton>
		</footer>
		<com:TActiveHiddenField ID="APIHostResourceAccessName" />
	</div>
	<com:TCallback
		ID="LoadAPIHostResourceAccess"
		OnCallback="TemplateControl.loadAPIHostResourceAccessWindow"
	>
		<prop:ClientSide.OnLoading>
			document.getElementById('get_api_host_window_loader').style.visibility = 'visible';
		</prop:ClientSide.OnLoading>
		<prop:ClientSide.OnComplete>
			document.getElementById('get_api_host_window_loader').style.visibility = 'hidden';
		</prop:ClientSide.OnComplete>
	</com:TCallback>
	<com:TCallback ID="UnassignAPIHostConsole" OnCallback="TemplateControl.unassignAPIHostConsole" />
</div>
