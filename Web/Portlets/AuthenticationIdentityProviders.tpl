<div>
	<p class="w3-hide-small"><%[ If you use your own identity provider (IdP) to authenticate users, you can configure Bacularis to use it. You can define one or multiple identity providers across different domains. This also enables single sign-on (SSO) support with the OpenID Connect authentication protocol. Identity providers are associated with organizations. Each organization can have its own identity provider. ]%></p>
	<div class="w3-panel">
		<button type="button" id="add_idp_btn" class="w3-button w3-green" onclick="oIdPs.load_idp_window()"><i class="fa fa-plus"></i> &nbsp;<%[ Add new identity provider ]%></button>
	</div>
	<!-- Tag tools -->
	<com:Bacularis.Web.Portlets.TagTools ID="TagToolsIdPList" ViewName="idp_list" />
	<table id="idp_list_table" class="display w3-table w3-striped w3-hoverable w3-margin-bottom selectable" style="width: 100%">
		<thead>
			<tr>
				<th></th>
				<th>ID</th>
				<th><%[ Name ]%></th>
				<th class="w3-center"><%[ Type ]%></th>
				<th class="w3-center"><%[ Enabled ]%></th>
				<th class="w3-center"><%[ In use by ]%></th>
				<th class="w3-center"><%[ Tag ]%></th>
				<th class="w3-center"><%[ Actions ]%></th>
			</tr>
		</thead>
		<tbody id="idp_list_body"></tbody>
		<tfoot>
			<tr>
				<th></th>
				<th>ID</th>
				<th><%[ Name ]%></th>
				<th class="w3-center"><%[ Type ]%></th>
				<th class="w3-center"><%[ Enabled ]%></th>
				<th class="w3-center"><%[ In use by ]%></th>
				<th class="w3-center"><%[ Tag ]%></th>
				<th class="w3-center"><%[ Actions ]%></th>
			</tr>
		</tfoot>
	</table>
	<p class="info w3-hide-medium w3-hide-small"><%[ Tip: Use left-click to select table row. Use CTRL + left-click to multiple row selection. Use SHIFT + left-click to add a range of rows to selection. ]%></p>
<com:TCallback ID="IdPList" OnCallback="TemplateControl.setIdPList" />
<com:TCallback ID="LoadIdP" OnCallback="TemplateControl.loadIdPWindow" />
<com:TCallback ID="RemoveIdPsAction" OnCallback="TemplateControl.removeIdPs" />
<script>
var oIdPList = {
	ids: {
		idp_list: 'idp_list_table'
	},
	actions: [
		{
			action: 'remove',
			label: '<%[ Remove ]%>',
			value: ['name', 'orgs'],
			callback: <%=$this->RemoveIdPsAction->ActiveControl->Javascript%>
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
			oIdPList.set_filters(this.table);
			this.table_toolbar.style.display = 'none';
		}
	},
	set_events: function() {
		document.getElementById(this.ids.idp_list).addEventListener('click', function(e) {
			$(function() {
				const wa = (this.table.rows({selected: true}).data().length > 0) ? 'show' : 'hide';
				$(this.table_toolbar).animate({
					width: wa
				}, 'fast');
			}.bind(this));
		}.bind(this));
	},
	set_table: function() {
		this.table = $('#' + this.ids.idp_list).DataTable({
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
				{data: 'full_name'},
				{data: 'idp_type'},
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
					data: 'orgs',
					render: render_string_short,
				},
				{
					data: 'name',
					render: (data, type, row) => {
						const id = 'name';
						const tt_obj = oTagTools_<%=$this->TagToolsIdPList->ClientID%>;
						const table = 'oIdPList.table';
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
						btn_edit.setAttribute('onclick', 'oIdPs.load_idp_window(\'' + data + '\')');
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
				targets: [ 3, 4, 5 ]
			}],
			select: {
				style:    'os',
				selector: 'td:not(:last-child):not(:first-child)',
				blurable: false
			},
			order: [1, 'asc'],
			initComplete: function () {
				oIdPList.set_filters(this.api());
			}
		});
	},
	set_filters: function(api) {
		api.columns([2]).every(function () {
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
	}
};

var oIdPs = {
	load_idp_window: function(name) {
		let title_add = document.getElementById('idp_window_title_add');
		let title_edit = document.getElementById('idp_window_title_edit');
		let idp_win_type = document.getElementById('<%=$this->IdPWindowType->ClientID%>');
		let host_name = document.getElementById('<%=$this->IdPName->ClientID%>');
		const cb = <%=$this->LoadIdP->ActiveControl->Javascript%>;
		cb.setCallbackParameter(name);
		cb.dispatch();
		if (name) {
			// edit existing idp
			title_add.style.display = 'none';
			title_edit.style.display = 'inline-block';
			idp_win_type.value = 'edit';
			host_name.setAttribute('readonly', '');
		} else {
			// add new idp
			title_add.style.display = 'inline-block';
			title_edit.style.display = 'none';
			idp_win_type.value = 'add';
			host_name.removeAttribute('readonly');
			this.clear_idp_window();
		}
		document.getElementById('idp_window').style.display = 'block';
	},
	load_idp_list: function() {
		const cb = <%=$this->IdPList->ActiveControl->Javascript%>;
		cb.dispatch();
	},
	load_idp_list_cb: function(list) {
		oIdPList.data = list;
		oIdPList.init();
	},
	clear_idp_window: function() {
		// clear inputs and selects
		[
			'<%=$this->IdPName->ClientID%>',
			'<%=$this->IdPFullName->ClientID%>',
			'<%=$this->IdPDescription->ClientID%>',
			'<%=$this->IdPType->ClientID%>',
			'<%=$this->IdPOIDCRedirectUri->ClientID%>',
			'<%=$this->IdPOIDCDiscoveryEndpoint->ClientID%>',
			'<%=$this->IdPOIDCAuthorizationEndpoint->ClientID%>',
			'<%=$this->IdPOIDCTokenEndpoint->ClientID%>',
			'<%=$this->IdPOIDCLogoutEndpoint->ClientID%>',
			'<%=$this->IdPOIDCUserInfoEndpoint->ClientID%>',
			'<%=$this->IdPOIDCIssuer->ClientID%>',
			'<%=$this->IdPOIDCScope->ClientID%>',
			'<%=$this->IdPOIDCJWKSEndpoint->ClientID%>',
			'<%=$this->IdPOIDCPublicKeyString->ClientID%>',
			'<%=$this->IdPOIDCPublicKeyID->ClientID%>',
			'<%=$this->IdPOIDCClientID->ClientID%>',
			'<%=$this->IdPOIDCClientSecret->ClientID%>',
			'<%=$this->IdPOIDCUserNameAttr->ClientID%>',
			'<%=$this->IdPOIDCLongNameAttr->ClientID%>',
			'<%=$this->IdPOIDCDescriptionAttr->ClientID%>',
			'<%=$this->IdPOIDCEmailAttr->ClientID%>'
		].forEach(function(id) {
			document.getElementById(id).value = '';
		});

		// clear checkboxes and radio buttons
		[
			'<%=$this->IdPEnabled->ClientID%>',
			'<%=$this->IdPOIDCUseDiscoveryEndpoint->ClientID%>',
			'<%=$this->IdPOIDCValidateSignatures->ClientID%>',
			'<%=$this->IdPOIDCUsePKCE->ClientID%>',
			'<%=$this->IdPOIDCUseJWKSEndpoint->ClientID%>',
			'<%=$this->IdPOIDCAttrSyncPolicyNoSync->ClientID%>'
		].forEach(function(id) {
			document.getElementById(id).checked = true;
		});

		const pkce = document.getElementById('<%=$this->IdPOIDCPKCEMethod->ClientID%>');
		pkce.value = '<%=PKCE::CODE_CHALLENGE_METHOD_S256%>';
		const attr_src = document.getElementById('<%=$this->IdPOIDCUserAttrSource->ClientID%>');
		attr_src.value = '<%=IdentityProviderConfig::OIDC_USER_ATTR_SOURCE_ID_TOKEN%>';

		const idp_type = document.getElementById('<%=$this->IdPType->ClientID%>');
		idp_type.onchange();
	},
	save_idp_cb: function() {
		document.getElementById('idp_window').style.display = 'none';
	}
}

$(function() {
	oIdPs.load_idp_list();
});
	</script>
</div>
<div id="idp_window" class="w3-modal">
	<div class="w3-modal-content w3-animate-top w3-card-4">
		<header class="w3-container w3-green">
			<span onclick="document.getElementById('idp_window').style.display = 'none';" class="w3-button w3-display-topright">&times;</span>
			<h2 id="idp_window_title_add" style="display: none"><%[ Add identity provider ]%></h2>
			<h2 id="idp_window_title_edit" style="display: none"><%[ Edit identity provider ]%></h2>
		</header>
		<div class="w3-container w3-margin-left w3-margin-right w3-margin-top">
			<span id="idp_window_idp_exists" class="error" style="display: none"><ul><li><%[ Identity provider with the given name already exists. ]%></li></ul></span>
			<div class="w3-row directive_field">
				<div class="w3-col w3-third"><com:TLabel ForControl="IdPName" Text="<%[ Identity provider identifier ]%>" />:</div>
				<div class="w3-col w3-twothird">
					<com:TActiveTextBox
						ID="IdPName"
						AutoPostBack="false"
						MaxLength="100"
						CssClass="w3-input w3-border w3-show-inline-block"
						Attributes.placeholder="ex: my-main-idp"
					/>
					<i class="fas fa-asterisk w3-text-red opt_req"></i>
					<com:TRequiredFieldValidator
						ValidationGroup="IdPGroup"
						ControlToValidate="IdPName"
						ErrorMessage="<%[ Field required. ]%>"
						ControlCssClass="field_invalid"
						Display="Dynamic"
					/>
					<com:TRegularExpressionValidator
						ValidationGroup="IdPGroup"
						RegularExpression="<%=IdentityProviderConfig::NAME_PATTERN%>"
						ControlToValidate="IdPName"
						ErrorMessage="<%[ Invalid value. ]%>"
						ControlCssClass="field_invalid"
						Display="Dynamic"
					/>
				</div>
			</div>
			<div class="w3-row directive_field">
				<div class="w3-col w3-third"><com:TLabel ForControl="IdPDescription" Text="<%[ Identity provider name ]%>"/>:</div>
				<div class="w3-col w3-twothird">
					<com:TActiveTextBox
						ID="IdPFullName"
						AutoPostBack="false"
						MaxLength="160"
						CssClass="w3-input w3-border w3-show-inline-block"
						Attributes.placeholder="ex: My main IdP"
					/>
					<i class="fas fa-asterisk w3-text-red opt_req"></i>
					<com:TRequiredFieldValidator
						ValidationGroup="IdPGroup"
						ControlToValidate="IdPFullName"
						ErrorMessage="<%[ Field required. ]%>"
						ControlCssClass="field_invalid"
						Display="Dynamic"
					/>
				</div>
			</div>
			<div class="w3-row directive_field">
				<div class="w3-col w3-third"><com:TLabel ForControl="IdPDescription" Text="<%[ Description ]%>"/>:</div>
				<div class="w3-col w3-twothird">
					<com:TActiveTextBox
						ID="IdPDescription"
						TextMode="MultiLine"
						Rows="3"
						AutoPostBack="false"
						MaxLength="500"
						CssClass="w3-input w3-border"
						Attributes.placeholder="ex: This is IT department identity provider..."
					/>
				</div>
			</div>
			<div class="w3-row directive_field">
				<div class="w3-col w3-third"><com:TLabel ForControl="IdPEnabled" Text="<%[ Enabled ]%>"/>:</div>
				<div class="w3-half">
					<com:TActiveCheckBox
						ID="IdPEnabled"
						CssClass="w3-check w3-border"
						AutoPostBack="false"
						Checked="true"
					/>
				</div>
			</div>
			<div class="w3-row directive_field">
				<div class="w3-col w3-third"><com:TLabel ForControl="IdPType" Text="<%[ Type ]%>"/>:</div>
				<div class="w3-col w3-twothird">
					<com:TActiveDropDownList
						ID="IdPType"
						CssClass="w3-select w3-border"
						AutoPostBack="false"
						Attributes.onchange="oIdPUserSecurity.show_idp_settings(this.value, true);"
					>
						<com:TListItem Value="" Text="" />
						<com:TListItem Value="<%=IdentityProviderConfig::IDP_TYPE_OIDC%>" Text="<%=IdentityProviderConfig::IDP_TYPE_OIDC_DESC%>" />
					</com:TActiveDropDownList>
					<i class="fas fa-asterisk w3-text-red opt_req"></i>
					<com:TRequiredFieldValidator
						ValidationGroup="IdPGroup"
						ControlToValidate="IdPType"
						ErrorMessage="<%[ Field required. ]%>"
						ControlCssClass="field_invalid"
						Display="Dynamic"
					/>
				</div>
			</div>
		</div>
		<div id="idp_method_oidc" class="w3-container" rel="idp_method" style="display: <%=$this->IdPType->SelectedValue == IdentityProviderConfig::IDP_TYPE_OIDC ? 'block' : 'none'%>">
			<%[ This is the Single Sign-On (SSO) authentication method with using the OpenID Connect (OIDC) protocol. It is realized with an external identity provider. ]%>
			<h5><%[ General ]%></h5>
			<div class="w3-container w3-row directive_field">
				<div class="w3-third w3-col">
					<%[ Redirect URI ]%>:
				</div>
				<div class="w3-twothird w3-col">
					<com:TActiveTextBox
						ID="IdPOIDCRedirectUri"
						CssClass="w3-input w3-border w3-show-inline-block"
						CausesValidation="false"
						Width="90%"
					/>
					<i class="fas fa-asterisk w3-text-red opt_req"></i>
					<com:TRequiredFieldValidator
						ValidationGroup="IdPGroup"
						CssClass="validator-block"
						Display="Dynamic"
						ControlCssClass="field_invalid"
						ControlToValidate="IdPOIDCRedirectUri"
						Text="<%[ Field required. ]%>"
					>
						<prop:ClientSide.OnValidate>
							const is_oidc_auth = ($('#<%=$this->IdPType->ClientID%>')[0].value == '<%=IdentityProviderConfig::IDP_TYPE_OIDC%>');
							sender.enabled = is_oidc_auth;
						</prop:ClientSide.OnValidate>
					</com:TRequiredFieldValidator>
				</div>
			</div>
			<h5><%[ Endpoints and functions ]%></h5>
			<div class="w3-container w3-row directive_field">
				<div class="w3-third w3-col">
					<%[ Use discovery endpoint ]%>:
				</div>
				<div class="w3-twothird w3-col">
					<com:TActiveCheckBox
						ID="IdPOIDCUseDiscoveryEndpoint"
						CssClass="w3-check"
						AutoPostBack="false"
						CausesValidation="false"
						Checked="true"
						Attributes.onclick="oIdPOIDCUserSecurity.show_discovery();"
					/>
				</div>
			</div>
			<div class="w3-container w3-row directive_field">
				<div class="w3-third w3-col">
					<%[ Discovery URL ]%>:
				</div>
				<div class="w3-twothird w3-col">
					<com:TActiveTextBox
						ID="IdPOIDCDiscoveryEndpoint"
						CssClass="w3-input w3-border w3-show-inline-block"
						CausesValidation="false"
						Width="90%"
					/>
					<i id="idp_method_oidc_discovery_url_req" class="fas fa-asterisk w3-text-red opt_req"></i>
					<com:TRequiredFieldValidator
						ValidationGroup="IdPGroup"
						CssClass="validator-block"
						Display="Dynamic"
						ControlCssClass="field_invalid"
						ControlToValidate="IdPOIDCDiscoveryEndpoint"
						Text="<%[ Field required. ]%>"
					>
						<prop:ClientSide.OnValidate>
							const is_oidc_auth = ($('#<%=$this->IdPType->ClientID%>')[0].value == '<%=IdentityProviderConfig::IDP_TYPE_OIDC%>');
							const is_use_discovery = $('#<%=$this->IdPOIDCUseDiscoveryEndpoint->ClientID%>')[0].checked;
							sender.enabled = (is_oidc_auth && is_use_discovery);
						</prop:ClientSide.OnValidate>
					</com:TRequiredFieldValidator>
					<i class="fa-solid fa-rotate pointer" title="<%[ Load ]%>"></i>
				</div>
			</div>
			<div id="idp_method_oidc_disable_discovery" style="display: <%=!$this->IdPOIDCUseDiscoveryEndpoint->Checked ? 'block' : 'none'%>">
				<div class="w3-container w3-row directive_field">
					<div class="w3-third w3-col">
						<%[ Authorization URL ]%>:
					</div>
					<div class="w3-twothird w3-col">
						<com:TActiveTextBox
							ID="IdPOIDCAuthorizationEndpoint"
							CssClass="w3-input w3-border w3-show-inline-block"
							CausesValidation="false"
							Width="90%"
						/>
						<i class="fas fa-asterisk w3-text-red opt_req"></i>
						<com:TRequiredFieldValidator
							ValidationGroup="IdPGroup"
							CssClass="validator-block"
							Display="Dynamic"
							ControlCssClass="field_invalid"
							ControlToValidate="IdPOIDCAuthorizationEndpoint"
							Text="<%[ Field required. ]%>"
						>
							<prop:ClientSide.OnValidate>
								const is_oidc_auth = ($('#<%=$this->IdPType->ClientID%>')[0].value == '<%=IdentityProviderConfig::IDP_TYPE_OIDC%>');
								const is_use_discovery = $('#<%=$this->IdPOIDCUseDiscoveryEndpoint->ClientID%>')[0].checked;
								sender.enabled = (is_oidc_auth && !is_use_discovery);
							</prop:ClientSide.OnValidate>
						</com:TRequiredFieldValidator>
					</div>
				</div>
				<div class="w3-container w3-row directive_field">
					<div class="w3-third w3-col">
						<%[ Token URL ]%>:
					</div>
					<div class="w3-twothird w3-col">
						<com:TActiveTextBox
							ID="IdPOIDCTokenEndpoint"
							CssClass="w3-input w3-border w3-show-inline-block"
							CausesValidation="false"
							Width="90%"
						/>
						<i class="fas fa-asterisk w3-text-red opt_req"></i>
						<com:TRequiredFieldValidator
							ValidationGroup="IdPGroup"
							CssClass="validator-block"
							Display="Dynamic"
							ControlCssClass="field_invalid"
							ControlToValidate="IdPOIDCTokenEndpoint"
							Text="<%[ Field required. ]%>"
						>
							<prop:ClientSide.OnValidate>
								const is_oidc_auth = ($('#<%=$this->IdPType->ClientID%>')[0].value == '<%=IdentityProviderConfig::IDP_TYPE_OIDC%>');
								const is_use_discovery = $('#<%=$this->IdPOIDCUseDiscoveryEndpoint->ClientID%>')[0].checked;
								sender.enabled = (is_oidc_auth && !is_use_discovery);
							</prop:ClientSide.OnValidate>
						</com:TRequiredFieldValidator>
					</div>
				</div>
				<div class="w3-container w3-row directive_field">
					<div class="w3-third w3-col">
						<%[ Logout URL ]%>:
					</div>
					<div class="w3-twothird w3-col">
						<com:TActiveTextBox
							ID="IdPOIDCLogoutEndpoint"
							CssClass="w3-input w3-border w3-show-inline-block"
							CausesValidation="false"
							Width="90%"
						/>
					</div>
				</div>
				<div class="w3-container w3-row directive_field">
					<div class="w3-third w3-col">
						<%[ User Info URL ]%>:
					</div>
					<div class="w3-twothird w3-col">
						<com:TActiveTextBox
							ID="IdPOIDCUserInfoEndpoint"
							CssClass="w3-input w3-border w3-show-inline-block"
							CausesValidation="false"
							Width="90%"
						/>
					</div>
				</div>
				<div class="w3-container w3-row directive_field">
					<div class="w3-third w3-col">
						<%[ Issuer ]%>:
					</div>
					<div class="w3-twothird w3-col">
						<com:TActiveTextBox
							ID="IdPOIDCIssuer"
							CssClass="w3-input w3-border w3-show-inline-block"
							CausesValidation="false"
							Width="90%"
						/>
						<i class="fas fa-asterisk w3-text-red opt_req"></i>
						<com:TRequiredFieldValidator
							ValidationGroup="IdPGroup"
							CssClass="validator-block"
							Display="Dynamic"
							ControlCssClass="field_invalid"
							ControlToValidate="IdPOIDCIssuer"
							Text="<%[ Field required. ]%>"
						>
							<prop:ClientSide.OnValidate>
								const is_oidc_auth = ($('#<%=$this->IdPType->ClientID%>')[0].value == '<%=IdentityProviderConfig::IDP_TYPE_OIDC%>');
								const is_use_discovery = $('#<%=$this->IdPOIDCUseDiscoveryEndpoint->ClientID%>')[0].checked;
								sender.enabled = (is_oidc_auth && !is_use_discovery);
							</prop:ClientSide.OnValidate>
						</com:TRequiredFieldValidator>
					</div>
				</div>
				<div class="w3-container w3-row directive_field">
					<div class="w3-third w3-col">
						<%[ Validate signatures ]%>:
					</div>
					<div class="w3-twothird w3-col">
						<com:TActiveCheckBox
							ID="IdPOIDCValidateSignatures"
							CssClass="w3-check"
							AutoPostBack="false"
							CausesValidation="false"
							Checked="true"
							Attributes.onclick="oIdPOIDCUserSecurity.show_validate_sig_options();"
						/>
					</div>
				</div>
				<div id="idp_method_oidc_enable_validate_sig" style="display: <%=$this->IdPOIDCValidateSignatures->Checked ? 'block' : 'none'%>">
					<div class="w3-container w3-row directive_field">
						<div class="w3-third w3-col">
							<%[ Use JWKS endpoint ]%>:
						</div>
						<div class="w3-twothird w3-col">
							<com:TActiveCheckBox
								ID="IdPOIDCUseJWKSEndpoint"
								CssClass="w3-check"
								AutoPostBack="false"
								CausesValidation="false"
								Attributes.onclick="oIdPOIDCUserSecurity.show_jwks_options();"
							/>
						</div>
					</div>
					<div id="idp_method_oidc_enable_public_key" class="w3-container w3-margin-left" style="display: <%=$this->IdPOIDCValidateSignatures->Checked && !$this->IdPOIDCUseJWKSEndpoint->Checked ? 'block' : 'none'%>">
						<div class="w3-container w3-row directive_field">
							<div class="w3-third w3-col">
								<%[ Public key (PEM format) ]%>:
							</div>
							<div class="w3-twothird w3-col">
								<com:TActiveTextBox
									ID="IdPOIDCPublicKeyString"
									TextMode="MultiLine"
									CssClass="w3-input w3-border w3-show-inline-block"
									CausesValidation="false"
									Width="90%"
								/>
								<i class="fas fa-asterisk w3-text-red opt_req"></i>
								<com:TRequiredFieldValidator
									ValidationGroup="IdPGroup"
									CssClass="validator-block"
									Display="Dynamic"
									ControlCssClass="field_invalid"
									ControlToValidate="IdPOIDCPublicKeyString"
									Text="<%[ Field required. ]%>"
								>
									<prop:ClientSide.OnValidate>
										const is_oidc_auth = ($('#<%=$this->IdPType->ClientID%>')[0].value == '<%=IdentityProviderConfig::IDP_TYPE_OIDC%>');
										const is_use_discovery = $('#<%=$this->IdPOIDCUseDiscoveryEndpoint->ClientID%>')[0].checked;
										const is_validate_signatures = $('#<%=$this->IdPOIDCValidateSignatures->ClientID%>')[0].checked;
										const is_use_jwks = $('#<%=$this->IdPOIDCUseJWKSEndpoint->ClientID%>')[0].checked;
										sender.enabled = (is_oidc_auth && !is_use_discovery && is_validate_signatures && !is_use_jwks);
									</prop:ClientSide.OnValidate>
								</com:TRequiredFieldValidator>
							</div>
						</div>
						<div class="w3-container w3-row directive_field">
							<div class="w3-third w3-col">
								<%[ Public key ID ]%>:
							</div>
							<div class="w3-twothird w3-col">
								<com:TActiveTextBox
									ID="IdPOIDCPublicKeyID"
									CssClass="w3-input w3-border w3-show-inline-block"
									CausesValidation="false"
									Width="90%"
								/>
								<i class="fas fa-asterisk w3-text-red opt_req"></i>
								<com:TRequiredFieldValidator
									ValidationGroup="IdPGroup"
									CssClass="validator-block"
									Display="Dynamic"
									ControlCssClass="field_invalid"
									ControlToValidate="IdPOIDCPublicKeyID"
									Text="<%[ Field required. ]%>"
								>
									<prop:ClientSide.OnValidate>
										const is_oidc_auth = ($('#<%=$this->IdPType->ClientID%>')[0].value == '<%=IdentityProviderConfig::IDP_TYPE_OIDC%>');
										const is_use_discovery = $('#<%=$this->IdPOIDCUseDiscoveryEndpoint->ClientID%>')[0].checked;
										const is_validate_signatures = $('#<%=$this->IdPOIDCValidateSignatures->ClientID%>')[0].checked;
										const is_use_jwks = $('#<%=$this->IdPOIDCUseJWKSEndpoint->ClientID%>')[0].checked;
										sender.enabled = (is_oidc_auth && !is_use_discovery && is_validate_signatures && !is_use_jwks);
									</prop:ClientSide.OnValidate>
								</com:TRequiredFieldValidator>
							</div>
						</div>
					</div>
				</div>
				<div id="idp_method_oidc_enable_jwks" class="w3-container w3-margin-left" style="display: <%=$this->IdPOIDCUseJWKSEndpoint->Checked ? 'block' : 'none'%>">
					<div class="w3-container w3-row directive_field">
						<div class="w3-third w3-col">
							<%[ JWKS URL ]%>:
						</div>
						<div class="w3-twothird w3-col">
							<com:TActiveTextBox
								ID="IdPOIDCJWKSEndpoint"
								CssClass="w3-input w3-border w3-show-inline-block"
								CausesValidation="false"
								Width="90%"
							/>
							<i class="fas fa-asterisk w3-text-red opt_req"></i>
							<com:TRequiredFieldValidator
								ValidationGroup="IdPGroup"
								CssClass="validator-block"
								Display="Dynamic"
								ControlCssClass="field_invalid"
								ControlToValidate="IdPOIDCJWKSEndpoint"
								Text="<%[ Field required. ]%>"
							>
								<prop:ClientSide.OnValidate>
									const is_oidc_auth = ($('#<%=$this->IdPType->ClientID%>')[0].value == '<%=IdentityProviderConfig::IDP_TYPE_OIDC%>');
									const is_use_discovery = $('#<%=$this->IdPOIDCUseDiscoveryEndpoint->ClientID%>')[0].checked;
									const is_use_jwks = $('#<%=$this->IdPOIDCUseJWKSEndpoint->ClientID%>')[0].checked;
									sender.enabled = (is_oidc_auth && !is_use_discovery && is_use_jwks);
								</prop:ClientSide.OnValidate>
							</com:TRequiredFieldValidator>
						</div>
					</div>
				</div>
				<div class="w3-container w3-row directive_field">
					<div class="w3-third w3-col">
						<%[ Use PKCE ]%>:
					</div>
					<div class="w3-twothird w3-col">
						<com:TActiveCheckBox
							ID="IdPOIDCUsePKCE"
							CssClass="w3-check"
							AutoPostBack="false"
							CausesValidation="false"
							Attributes.onclick="oIdPOIDCUserSecurity.show_pkce_options();"
						/>
					</div>
				</div>
				<div id="idp_method_oidc_enable_pkce" class="w3-container w3-margin-left" style="display: <%=$this->IdPOIDCUsePKCE->Checked ? 'block' : 'none'%>">
					<div class="w3-container w3-row directive_field">
						<div class="w3-third w3-col">
							<%[ PKCE method ]%>:
						</div>
						<div class="w3-twothird w3-col">
							<com:TActiveDropDownList
								ID="IdPOIDCPKCEMethod"
								CssClass="w3-input w3-border w3-show-inline-block"
								CausesValidation="false"
								Width="30%"
							>
								<com:TListItem Value="<%=PKCE::CODE_CHALLENGE_METHOD_PLAIN%>" Text="Plain" />
								<com:TListItem Value="<%=PKCE::CODE_CHALLENGE_METHOD_S256%>" Text="S256" Selected="true" />
							</com:TActiveDropDownList>
							<com:TRequiredFieldValidator
								ValidationGroup="IdPGroup"
								CssClass="validator-block"
								Display="Dynamic"
								ControlCssClass="field_invalid"
								ControlToValidate="IdPOIDCPKCEMethod"
								Text="<%[ Field required. ]%>"
							>
								<prop:ClientSide.OnValidate>
									const is_oidc_auth = ($('#<%=$this->IdPType->ClientID%>')[0].value == '<%=IdentityProviderConfig::IDP_TYPE_OIDC%>');
									const is_use_discovery = $('#<%=$this->IdPOIDCUseDiscoveryEndpoint->ClientID%>')[0].checked;
									const is_use_pkce = $('#<%=$this->IdPOIDCUsePKCE->ClientID%>')[0].checked;
									sender.enabled = (is_oidc_auth && !is_use_discovery && is_use_pkce);
								</prop:ClientSide.OnValidate>
							</com:TRequiredFieldValidator>
						</div>
					</div>
				</div>
				<div class="w3-container w3-row directive_field">
					<div class="w3-third w3-col">
						<%[ Scope ]%>:
					</div>
					<div class="w3-twothird w3-col">
						<com:TActiveTextBox
							ID="IdPOIDCScope"
							CssClass="w3-input w3-border w3-show-inline-block"
							CausesValidation="false"
							Width="90%"
						/>
						<i class="fas fa-asterisk w3-text-red opt_req"></i>
						<com:TRequiredFieldValidator
							ValidationGroup="IdPGroup"
							CssClass="validator-block"
							Display="Dynamic"
							ControlCssClass="field_invalid"
							ControlToValidate="IdPOIDCScope"
							Text="<%[ Field required. ]%>"
						>
							<prop:ClientSide.OnValidate>
								const is_oidc_auth = ($('#<%=$this->IdPType->ClientID%>')[0].value == '<%=IdentityProviderConfig::IDP_TYPE_OIDC%>');
								const is_use_discovery = $('#<%=$this->IdPOIDCUseDiscoveryEndpoint->ClientID%>')[0].checked;
								sender.enabled = (is_oidc_auth && !is_use_discovery);
							</prop:ClientSide.OnValidate>
						</com:TRequiredFieldValidator>
					</div>
				</div>
			</div>
			<h5><%[ Credentials ]%></h5>
			<div class="w3-container w3-row directive_field">
				<div class="w3-third w3-col">
					<%[ Client ID ]%>:
				</div>
				<div class="w3-twothird w3-col">
					<com:TActiveTextBox
						ID="IdPOIDCClientID"
						CssClass="w3-input w3-border w3-show-inline-block"
						CausesValidation="false"
						Width="90%"
					/>
					<i class="fas fa-asterisk w3-text-red opt_req"></i>
					<com:TRequiredFieldValidator
						ValidationGroup="IdPGroup"
						CssClass="validator-block"
						Display="Dynamic"
						ControlCssClass="field_invalid"
						ControlToValidate="IdPOIDCClientID"
						Text="<%[ Field required. ]%>"
					>
						<prop:ClientSide.OnValidate>
							const is_oidc_auth = ($('#<%=$this->IdPType->ClientID%>')[0].value == '<%=IdentityProviderConfig::IDP_TYPE_OIDC%>');
							sender.enabled = is_oidc_auth;
						</prop:ClientSide.OnValidate>
					</com:TRequiredFieldValidator>
				</div>
			</div>
			<div class="w3-container w3-row directive_field">
				<div class="w3-third w3-col">
					<%[ Client secret ]%>:
				</div>
				<div class="w3-twothird w3-col">
					<com:TActiveTextBox
						ID="IdPOIDCClientSecret"
						CssClass="w3-input w3-border w3-show-inline-block"
						TextMode="Password"
						CausesValidation="false"
						Width="90%"
					/>
					<i class="fas fa-asterisk w3-text-red opt_req"></i>
					<a href="javascript:void(0)" onclick="var el = document.getElementById('<%=$this->IdPOIDCClientSecret->ClientID%>'); el.type = el.type == 'text' ? 'password' : 'text'" title="Show/hide password"><i class="fa fa-eye"></i></a>
					<com:TRequiredFieldValidator
						ValidationGroup="IdPGroup"
						CssClass="validator-block"
						Display="Dynamic"
						ControlCssClass="field_invalid"
						ControlToValidate="IdPOIDCClientSecret"
						Text="<%[ Field required. ]%>"
					>
						<prop:ClientSide.OnValidate>
							const is_oidc_auth = ($('#<%=$this->IdPType->ClientID%>')[0].value == '<%=IdentityProviderConfig::IDP_TYPE_OIDC%>');
							sender.enabled = is_oidc_auth;
						</prop:ClientSide.OnValidate>
					</com:TRequiredFieldValidator>
				</div>
			</div>
			<h5><%[ Attributes ]%></h5>
			<div class="w3-container w3-row directive_field">
				<div class="w3-third w3-col">
					<%[ User attribute source ]%>:
				</div>
				<div class="w3-twothird w3-col">
					<com:TActiveDropDownList
						ID="IdPOIDCUserAttrSource"
						CssClass="w3-select w3-border w3-show-inline-block"
						CausesValidation="false"
						Width="90%"
					>
						<com:TListItem Value="<%=IdentityProviderConfig::OIDC_USER_ATTR_SOURCE_ID_TOKEN%>" Text="ID token" />
						<com:TListItem Value="<%=IdentityProviderConfig::OIDC_USER_ATTR_SOURCE_USERINFO_ENDPOINT%>" Text="User info endpoint" />
					</com:TActiveDropDownList>
					<com:TRequiredFieldValidator
						ValidationGroup="IdPGroup"
						CssClass="validator-block"
						Display="Dynamic"
						ControlCssClass="field_invalid"
						ControlToValidate="IdPOIDCUserAttrSource"
						Text="<%[ Field required. ]%>"
					>
						<prop:ClientSide.OnValidate>
							const is_oidc_auth = ($('#<%=$this->IdPType->ClientID%>')[0].value == '<%=IdentityProviderConfig::IDP_TYPE_OIDC%>');
							sender.enabled = is_oidc_auth;
						</prop:ClientSide.OnValidate>
					</com:TRequiredFieldValidator>
				</div>
			</div>
			<div class="w3-container w3-row directive_field">
				<div class="w3-third w3-col">
					<%[ Username attribute ]%>:
				</div>
				<div class="w3-twothird w3-col">
					<com:TActiveTextBox
						ID="IdPOIDCUserNameAttr"
						CssClass="w3-input w3-border w3-show-inline-block"
						Width="90%"
					/>
					<i class="fas fa-asterisk w3-text-red opt_req"></i>
					<com:TRequiredFieldValidator
						ValidationGroup="IdPGroup"
						CssClass="validator-block"
						Display="Dynamic"
						ControlCssClass="field_invalid"
						ControlToValidate="IdPOIDCUserNameAttr"
						Text="<%[ Field required. ]%>"
					>
						<prop:ClientSide.OnValidate>
							const is_oidc_auth = ($('#<%=$this->IdPType->ClientID%>')[0].value == '<%=IdentityProviderConfig::IDP_TYPE_OIDC%>');
							sender.enabled = is_oidc_auth;
						</prop:ClientSide.OnValidate>
					</com:TRequiredFieldValidator>
				</div>
			</div>
			<div class="w3-container w3-row directive_field">
				<div class="w3-third w3-col">
					<%[ Long name attribute ]%>:
				</div>
				<div class="w3-twothird w3-col">
					<com:TActiveTextBox
						ID="IdPOIDCLongNameAttr"
						CssClass="w3-input w3-border w3-show-inline-block"
						Width="90%"
					/>
				</div>
			</div>
			<div class="w3-container w3-row directive_field">
				<div class="w3-third w3-col">
					<%[ Email attribute ]%>:
				</div>
				<div class="w3-twothird w3-col">
					<com:TActiveTextBox
						ID="IdPOIDCEmailAttr"
						CssClass="w3-input w3-border w3-show-inline-block"
						Width="90%"
					/>
				</div>
			</div>
			<div class="w3-container w3-row directive_field">
				<div class="w3-third w3-col">
					<%[ Description attribute ]%>:
				</div>
				<div class="w3-twothird w3-col">
					<com:TActiveTextBox
						ID="IdPOIDCDescriptionAttr"
						CssClass="w3-input w3-border w3-show-inline-block"
						Width="90%"
					/>
				</div>
			</div>
			<div class="w3-container w3-row directive_field">
				<div class="w3-third w3-col">
					<%[ Attribute sync. policy ]%>:
				</div>
				<div class="w3-twothird w3-col">
					<com:TActiveRadioButton
						ID="IdPOIDCAttrSyncPolicyNoSync"
						GroupName="IdPOIDCAttrSyncPolicy"
						CssClass="w3-radio"
						Checked="true"
					/> <label for="<%=$this->IdPOIDCAttrSyncPolicyNoSync->ClientID%>"><%[ Do not synchronize ]%></label><br />
					<com:TActiveRadioButton
						ID="IdPOIDCAttrSyncPolicyEachLogin"
						GroupName="IdPOIDCAttrSyncPolicy"
						CssClass="w3-radio"
					/> <label for="<%=$this->IdPOIDCAttrSyncPolicyEachLogin->ClientID%>"><%[ Synchronize on each login ]%></label>
				</div>
			</div>
		</div>
		<footer class="w3-container w3-center">
			<button type="button" class="w3-button w3-red" onclick="document.getElementById('idp_window').style.display = 'none';"><i class="fas fa-times"></i> &nbsp;<%[ Cancel ]%></button>
			<com:TActiveLinkButton
				ID="IdPSave"
				ValidationGroup="IdPGroup"
				CausesValidation="true"
				OnCallback="saveIdP"
				CssClass="w3-button w3-section w3-green w3-padding"
			>
				<i class="fa fa-save"></i> &nbsp;<%[ Save ]%>
			</com:TActiveLinkButton>
		</footer>
	</div>
	<com:TActiveHiddenField ID="IdPWindowType" />
	<script>
var oIdPUserSecurity = {
	show_idp_settings: function(type, show) {
		const self = oIdPUserSecurity;
		self.hide_all_idp_settings();
		if (type) {
			const idp_method = document.getElementById('idp_method_' + type);
			idp_method.style.display = show ? 'block' : 'none';
		}
	},
	hide_all_idp_settings: function() {
		$('div[rel=idp_method]').hide();
	},
	load_settings: function() {
		oIdPOIDCUserSecurity.load_settings();
	}
};
const oIdPOIDCUserSecurity = {
	ids: {
		chkb_discovery: '<%=$this->IdPOIDCUseDiscoveryEndpoint->ClientID%>',
		disable_discovery: 'idp_method_oidc_disable_discovery',
		chkb_validate_sig: '<%=$this->IdPOIDCValidateSignatures->ClientID%>',
		enable_validate_sig: 'idp_method_oidc_enable_validate_sig',
		chkb_jwks: '<%=$this->IdPOIDCUseJWKSEndpoint->ClientID%>',
		enable_jwks: 'idp_method_oidc_enable_jwks',
		enable_public_key: 'idp_method_oidc_enable_public_key',
		chkb_pkce: '<%=$this->IdPOIDCUsePKCE->ClientID%>',
		enable_pkce: 'idp_method_oidc_enable_pkce',
		discovery_url_req: 'idp_method_oidc_discovery_url_req',
		use_jwks: '<%=$this->IdPOIDCUseJWKSEndpoint->ClientID%>'
	},
	load_settings: function() {
		this.show_discovery();
		this.show_validate_sig_options();
		this.show_jwks_options();
		this.show_public_key_options();
		this.show_pkce_options();
	},
	show_discovery: function() {
		const chkb_discovery = document.getElementById(this.ids.chkb_discovery); 
		const show = chkb_discovery.checked;
		const disable_discovery = document.getElementById(this.ids.disable_discovery);
		disable_discovery.style.display = show ? 'none' : 'block';
		const discovery_url_req = document.getElementById(this.ids.discovery_url_req);
		discovery_url_req.style.display = show ? 'inline-block' : 'none';
	},
	show_validate_sig_options: function() {
		const chkb_validate_sig = document.getElementById(this.ids.chkb_validate_sig);
		const show = chkb_validate_sig.checked;
		const enable_validate_sig = document.getElementById(this.ids.enable_validate_sig);
		enable_validate_sig.style.display = show ? 'block' : 'none';
		this.show_jwks_options();
		this.show_public_key_options();
	},
	show_public_key_options: function() {
		const chkb_validate_sig = document.getElementById(this.ids.chkb_validate_sig);
		const chkb_jwks = document.getElementById(this.ids.chkb_jwks);
		const show = (chkb_validate_sig.checked && !chkb_jwks.checked);
		const enable_public_key = document.getElementById(this.ids.enable_public_key);
		enable_public_key.style.display = show ? 'block' : 'none';
	},
	show_jwks_options: function() {
		const chkb_jwks = document.getElementById(this.ids.chkb_jwks);
		const chkb_validate_sig = document.getElementById(this.ids.chkb_validate_sig);
		const ashow = (chkb_validate_sig.checked && chkb_jwks.checked);
		const enable_jwks = document.getElementById(this.ids.enable_jwks);
		enable_jwks.style.display = ashow ? 'block' : 'none';
		this.show_public_key_options();
	},
	show_pkce_options: function() {
		const chkb_pkce = document.getElementById(this.ids.chkb_pkce);
		const show = chkb_pkce.checked;
		const enable_pkce = document.getElementById(this.ids.enable_pkce);
		enable_pkce.style.display = show ? 'block' : 'none';
	}
};
	</script>
</div>
<div id="idp_action_rm_warning_window" class="w3-modal">
	<div class="w3-modal-content w3-animate-top w3-card-4">
		<header class="w3-container w3-orange">
			<span onclick="document.getElementById('idp_action_rm_warning_window').style.display = 'none';" class="w3-button w3-display-topright">&times;</span>
			<h2 id="idp_action_rm_warning_window_title_add"><%[ Warning ]%></h2>
		</header>
		<div class="w3-container w3-margin-left w3-margin-right w3-margin-top">
			<p><%[ The following identity providers cannot be removed because they are used in organizations. Please unassign identity providers from the organizations and try again. ]%></p>
			<com:TActiveRepeater
				ID="IdPFbd"
			>
				<prop:HeaderTemplate>
					<ul>
				</prop:HeaderTemplate>
				<prop:ItemTemplate>
					<li><%#$this->Data['name']%></li>
				</prop:ItemTemplate>
				<prop:FooterTemplate>
					</ul>
				</prop:FooterTemplate>
			</com:TActiveRepeater>
		</div>
		<footer class="w3-container w3-center">
			<button type="button" class="w3-button w3-green w3-margin-bottom" onclick="document.getElementById('idp_action_rm_warning_window').style.display = 'none';"><i class="fas fa-times"></i> &nbsp;<%[ Cancel ]%></button>
		</footer>
	</div>
</div>
