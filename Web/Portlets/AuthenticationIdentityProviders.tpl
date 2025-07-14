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
	ids: {
		idp_win: 'idp_window'
	},
	load_idp_window: function(name) {
		let title_add = document.getElementById('idp_window_title_add');
		let title_edit = document.getElementById('idp_window_title_edit');
		let idp_win_type = document.getElementById('<%=$this->IdPWindowType->ClientID%>');
		let idp_type = document.getElementById('<%=$this->IdPType->ClientID%>');
		let host_name = document.getElementById('<%=$this->IdPName->ClientID%>');
		const cb = <%=$this->LoadIdP->ActiveControl->Javascript%>;
		cb.setCallbackParameter(name);
		cb.dispatch();
		if (name) {
			// edit existing idp
			title_add.style.display = 'none';
			title_edit.style.display = 'inline-block';
			idp_win_type.value = 'edit';
			idp_type.setAttribute('disabled', '');
			host_name.setAttribute('readonly', '');
		} else {
			// add new idp
			title_add.style.display = 'inline-block';
			title_edit.style.display = 'none';
			idp_win_type.value = 'add';
			idp_type.removeAttribute('disabled');
			host_name.removeAttribute('readonly');
			this.clear_idp_window();
		}
		const idp_win = document.getElementById(this.ids.idp_win);
		idp_win.style.display = 'block';
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
		].forEach(function(id) {
			document.getElementById(id).value = '';
		});

		// clear checkboxes and radio buttons
		[
			'<%=$this->IdPEnabled->ClientID%>',
		].forEach(function(id) {
			document.getElementById(id).checked = true;
		});

		const idp_type = document.getElementById('<%=$this->IdPType->ClientID%>');
		idp_type.onchange();

		oIdPUserSecurity.clear_idp_forms();
	},
	save_idp_cb: function() {
		const idp_win = document.getElementById(this.ids.idp_win);
		idp_win.style.display = 'none';
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
			<com:TActiveLabel ID="IdPWindowError" CSsClass="error" Display="None" />
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
						Attributes.onchange="oIdPUserSecurity.show_idp_settings(this.value, true); oIdPUserSecurity.set_default_settings();"
					>
						<com:TListItem
							Value=""
							Text=""
							Attributes.data-defname=""
						/>
						<com:TListItem
							Value="<%=IdentityProviderConfig::IDP_TYPE_OIDC%>"
							Text="<%=IdentityProviderConfig::IDP_TYPE_OIDC_DESC%>"
							Attributes.data-defname="master"
						/>
						<com:TListItem
							Value="<%=IdentityProviderConfig::IDP_TYPE_OIDC_GOOGLE%>"
							Text="<%=IdentityProviderConfig::IDP_TYPE_OIDC_GOOGLE_DESC%>"
							Attributes.data-defname="google"
						/>
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
			<div class="w3-row directive_field">
				<div class="w3-col w3-third"><com:TLabel ForControl="IdPName" Text="<%[ Identity provider identifier ]%>" />:</div>
				<div class="w3-col w3-twothird">
					<com:TActiveTextBox
						ID="IdPName"
						AutoPostBack="false"
						MaxLength="100"
						CssClass="w3-input w3-border w3-show-inline-block"
						Attributes.placeholder="ex: my-main-idp"
						Attributes.onkeyup="if (!this.hasAttribute('readonly')) { oIdPUserSecurity.set_redirect_uri(this.value); }"
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
		</div>
		<div id="idp_method_oidc" class="w3-container" rel="idp_method" style="display: <%=$this->IdPType->SelectedValue == IdentityProviderConfig::IDP_TYPE_OIDC ? 'block' : 'none'%>">
			<%[ This is the Single Sign-On (SSO) authentication method with using the OpenID Connect (OIDC) protocol. It is realized with an external identity provider. ]%>
			<com:Bacularis.Web.Portlets.IdentityProviderOIDC
				ID="IdentityProviderOIDC"
				IdPType="<%=$this->IdPType%>"
			/>
		</div>
		<div id="idp_method_google" class="w3-container" rel="idp_method" style="display: <%=$this->IdPType->SelectedValue == IdentityProviderConfig::IDP_TYPE_OIDC_GOOGLE ? 'block' : 'none'%>">
			<com:Bacularis.Web.Portlets.IdentityProviderOIDCGoogle
				ID="IdentityProviderOIDCGoogle"
				IdPType="<%=$this->IdPType%>"
			/>
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
	ids: {
		idp_type: '<%=$this->IdPType->ClientID%>',
		idp_id: '<%=$this->IdPName->ClientID%>'
	},
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
	set_default_settings: function() {
		const type = document.getElementById(this.ids.idp_type);
		const opt = type.options[type.selectedIndex];
		const defname = opt.getAttribute('data-defname');
		this.set_idp_id(defname);
		this.set_redirect_uri(defname);
	},
	clear_idp_forms: function() {
		oIdPOIDC.clear_idp_window();
		oIdPOIDCGoogle.clear_idp_window();
	},
	load_settings: function() {
		oIdPOIDCUserSecurity.load_settings();
	},
	set_idp_id: function(id) {
		const idp_id = document.getElementById(this.ids.idp_id);
		idp_id.value = id;
	},
	set_redirect_uri: function(name) {
		const pattern = '<%=IdentityProviderConfig::OIDC_REDIRECT_URI_PATTERN%>';
		const protocol = window.location.protocol.replace(/:$/, '');
		const host = window.location.host;
		let uri = pattern.replace(/%protocol/, protocol);
		uri = uri.replace(/%host/, host);
		uri = uri.replace(/%name/, name);
		const redirect_uri = $('input[rel="redirect-uri"]:visible').get(0);
		if (redirect_uri) {
			redirect_uri.value = uri;
		}
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
