<div>
	<p class="w3-hide-small"><%[ Organizations help divide users into departments or other groups (e.g., sales department, finance department...). This facilitates user management within structures and allows separate authentication type for each organization. They are also useful for enabling single sign-on (SSO) with using the OpenID Connect authentication protocol. ]%></p>
	<div class="w3-panel">
		<button type="button" id="add_organization_btn" class="w3-button w3-green" onclick="oOrganizations.load_organization_window()"><i class="fa fa-plus"></i> &nbsp;<%[ Add new organization ]%></button>
	</div>
	<!-- Tag tools -->
	<com:Bacularis.Web.Portlets.TagTools ID="TagToolsOrganizationList" ViewName="organization_list" />
	<table id="organization_list_table" class="display w3-table w3-striped w3-hoverable w3-margin-bottom selectable" style="width: 100%">
		<thead>
			<tr>
				<th></th>
				<th>ID</th>
				<th><%[ Name ]%></th>
				<th class="w3-center"><%[ Auth type ]%></th>
				<th class="w3-center"><%[ IdP name ]%></th>
				<th class="w3-center"><%[ IdP type ]%></th>
				<th class="w3-center"><%[ Enabled ]%></th>
				<th class="w3-center"><%[ Tag ]%></th>
				<th class="w3-center"><%[ Actions ]%></th>
			</tr>
		</thead>
		<tbody id="organization_list_body"></tbody>
		<tfoot>
			<tr>
				<th></th>
				<th>ID</th>
				<th><%[ Name ]%></th>
				<th class="w3-center"><%[ Auth type ]%></th>
				<th class="w3-center"><%[ IdP name ]%></th>
				<th class="w3-center"><%[ IdP type ]%></th>
				<th class="w3-center"><%[ Enabled ]%></th>
				<th class="w3-center"><%[ Tag ]%></th>
				<th class="w3-center"><%[ Actions ]%></th>
			</tr>
		</tfoot>
	</table>
	<p class="info w3-hide-medium w3-hide-small"><%[ Tip: Use left-click to select table row. Use CTRL + left-click to multiple row selection. Use SHIFT + left-click to add a range of rows to selection. ]%></p>
<com:TCallback ID="OrganizationList" OnCallback="TemplateControl.setOrganizationList" />
<com:TCallback ID="LoadOrganization" OnCallback="TemplateControl.loadOrganizationWindow" />
<com:TCallback ID="RemoveOrganizationsAction" OnCallback="TemplateControl.removeOrganizations" />
<script>
var oOrganizationList = {
	ids: {
		organization_list: 'organization_list_table'
	},
	actions: [
		{
			action: 'remove',
			label: '<%[ Remove ]%>',
			value: 'name',
			callback: <%=$this->RemoveOrganizationsAction->ActiveControl->Javascript%>
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
			oOrganizationList.set_filters(this.table);
			this.table_toolbar.style.display = 'none';
		}
	},
	set_events: function() {
		document.getElementById(this.ids.organization_list).addEventListener('click', function(e) {
			$(function() {
				const wa = (this.table.rows({selected: true}).data().length > 0) ? 'show' : 'hide';
				$(this.table_toolbar).animate({
					width: wa
				}, 'fast');
			}.bind(this));
		}.bind(this));
	},
	set_table: function() {
		this.table = $('#' + this.ids.organization_list).DataTable({
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
				{
					data: 'auth_type',
					render: function(data, type, row) {
						let ret = '-';
						if (data == '<%=OrganizationConfig::AUTH_TYPE_AUTH_METHOD%>') {
							ret = '<%[ Auth method ]%>';
						} else if (data == '<%=OrganizationConfig::AUTH_TYPE_IDP%>') {
							ret = '<%[ Identity provider ]%>';
						}
						return ret;
					}
				},
				{
					data: 'identity_provider',
					render: function(data, type, row) {
						let ret = '-';
						if (row.auth_type == '<%=OrganizationConfig::AUTH_TYPE_IDP%>') {
							ret = data;
						}
						return ret;
					}
				},
				{
					data: 'idp_type',
					render: function(data, type, row) {
						let ret = '-';
						if (row.auth_type == '<%=OrganizationConfig::AUTH_TYPE_IDP%>') {
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
					data: 'name',
					render: (data, type, row) => {
						const id = 'name';
						const tt_obj = oTagTools_<%=$this->TagToolsOrganizationList->ClientID%>;
						const table = 'oOrganizationList.table';
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
						btn_edit.setAttribute('onclick', 'oOrganizations.load_organization_window(\'' + data + '\')');
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
				targets: [ 8 ]
			},
			{
				className: "dt-center",
				targets: [ 3, 4, 5, 6 ]
			}],
			select: {
				style:    'os',
				selector: 'td:not(:last-child):not(:first-child)',
				blurable: false
			},
			order: [1, 'asc'],
			initComplete: function () {
				oOrganizationList.set_filters(this.api());
			}
		});
	},
	set_filters: function(api) {
		api.columns([2, 3, 4, 5]).every(function () {
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

var oOrganizations = {
	load_organization_window: function(name) {
		let title_add = document.getElementById('organization_window_title_add');
		let title_edit = document.getElementById('organization_window_title_edit');
		let organization_win_type = document.getElementById('<%=$this->OrganizationWindowType->ClientID%>');
		let host_name = document.getElementById('<%=$this->OrganizationName->ClientID%>');
		const cb = <%=$this->LoadOrganization->ActiveControl->Javascript%>;
		cb.setCallbackParameter(name);
		cb.dispatch();
		if (name) {
			// edit existing organization
			title_add.style.display = 'none';
			title_edit.style.display = 'inline-block';
			organization_win_type.value = 'edit';
			host_name.setAttribute('readonly', '');
		} else {
			// add new organization
			title_add.style.display = 'inline-block';
			title_edit.style.display = 'none';
			organization_win_type.value = 'add';
			host_name.removeAttribute('readonly');
			this.clear_organization_window();
		}
		document.getElementById('organization_window').style.display = 'block';
	},
	load_organization_list: function() {
		const cb = <%=$this->OrganizationList->ActiveControl->Javascript%>;
		cb.dispatch();
	},
	load_organization_list_cb: function(list) {
		oOrganizationList.data = list;
		oOrganizationList.init();
	},
	clear_organization_window: function() {
		// reset inputs and selects
		[
			'<%=$this->OrganizationName->ClientID%>',
			'<%=$this->OrganizationFullName->ClientID%>',
			'<%=$this->OrganizationDescription->ClientID%>',
			'<%=$this->OrganizationIdP->ClientID%>',
			'<%=$this->OrganizationLoginBtnColor->ClientID%>'
		].forEach(function(id) {
			document.getElementById(id).value = '';
		});

		// reset radio buttons and checkboxes
		[
			'<%=$this->OrganizationAuthMethodOpt->ClientID%>',
			'<%=$this->OrganizationEnabled->ClientID%>'
		].forEach(function(id) {
			document.getElementById(id).checked = true;
		});

		// reset color picker
		const cpi = document.getElementById('<%=$this->OrganizationLoginBtnColor->ClientID%>');
		const cpb = document.getElementById('<%=$this->OrganizationLoginBtnColor->ClientID%>_button');
		cpi.value = cpb.style.backgroundColor = '<%=$this->OrganizationLoginBtnColor->Text%>';
		
	},
	save_organization_cb: function() {
		document.getElementById('organization_window').style.display = 'none';
	}
}

$(function() {
	oOrganizations.load_organization_list();
});
	</script>
</div>
<div id="organization_window" class="w3-modal">
	<div class="w3-modal-content w3-animate-top w3-card-4">
		<header class="w3-container w3-green">
			<span onclick="document.getElementById('organization_window').style.display = 'none';" class="w3-button w3-display-topright">&times;</span>
			<h2 id="organization_window_title_add" style="display: none"><%[ Add organization ]%></h2>
			<h2 id="organization_window_title_edit" style="display: none"><%[ Edit organization ]%></h2>
		</header>
		<div class="w3-container w3-margin-left w3-margin-right w3-margin-top">
			<span id="organization_window_org_exists" class="error" style="display: none"><ul><li><%[ Organization with the given name already exists. ]%></li></ul></span>
			<div class="w3-row directive_field">
				<div class="w3-col w3-third"><com:TLabel ForControl="OrganizationName" Text="<%[ Organization identifier ]%>" />:</div>
				<div class="w3-half">
					<com:TActiveTextBox
						ID="OrganizationName"
						AutoPostBack="false"
						MaxLength="100"
						CssClass="w3-input w3-border"
						Attributes.placeholder="ex: my-favourite-org"
					/>
					<com:TRequiredFieldValidator
						ValidationGroup="OrganizationGroup"
						ControlToValidate="OrganizationName"
						ErrorMessage="<%[ Field required. ]%>"
						ControlCssClass="field_invalid"
						Display="Dynamic"
					/>
					<com:TRegularExpressionValidator
						ValidationGroup="OrganizationGroup"
						RegularExpression="<%=OrganizationConfig::NAME_PATTERN%>"
						ControlToValidate="OrganizationName"
						ErrorMessage="<%[ Invalid value. ]%>"
						ControlCssClass="field_invalid"
						Display="Dynamic"
					/>
				</div> &nbsp;<i id="organization_window_required" class="fa fa-asterisk w3-text-red opt_req" style="display none"></i>
			</div>
			<div class="w3-row directive_field">
				<div class="w3-col w3-third"><com:TLabel ForControl="OrganizationDescription" Text="<%[ Organization name ]%>"/>:</div>
				<div class="w3-half">
					<com:TActiveTextBox
						ID="OrganizationFullName"
						AutoPostBack="false"
						MaxLength="160"
						CssClass="w3-input w3-border"
						Attributes.placeholder="ex: My favourite organization"
					/>
				</div>
			</div>
			<div class="w3-row directive_field">
				<div class="w3-col w3-third"><com:TLabel ForControl="OrganizationDescription" Text="<%[ Description ]%>"/>:</div>
				<div class="w3-half">
					<com:TActiveTextBox
						ID="OrganizationDescription"
						TextMode="MultiLine"
						Rows="3"
						AutoPostBack="false"
						MaxLength="500"
						CssClass="w3-input w3-border"
						Attributes.placeholder="ex: This is my first serious organization..."
					/>
				</div>
			</div>
			<div class="w3-row directive_field">
				<div class="w3-col w3-third">&nbsp;</div>
				<div class="w3-half">
					<com:TActiveRadioButton
						ID="OrganizationAuthMethodOpt"
						GroupName="SelectOrganizationAuthType"
						CssClass="w3-radio"
						Checked="true"
						Attributes.onclick="$('#organization_window_auth_type_idp').hide();"
					/>
					<com:TActiveLabel
						ID="OrganizationCurrentAuthMethod"
						ForControl="OrganizationAuthMethodOpt"
						CssClass="normal"
						Text="<%[ Use current authentication method ]%>"
					/><br />
				</div>
			</div>
			<div class="w3-row directive_field w3-margin-bottom">
				<div class="w3-col w3-third">&nbsp;</div>
				<div class="w3-half">
					<com:TActiveRadioButton
						ID="OrganizationIdPOpt"
						GroupName="SelectOrganizationAuthType"
						CssClass="w3-radio"
						Attributes.onclick="$('#organization_window_auth_type_idp').show();"
					/>
					<com:TLabel
						ForControl="OrganizationIdPOpt"
						CssClass="normal"
						Text="<%[ Use identity provider ]%>"
					/>
				</div>
			</div>
			<div id="organization_window_auth_type_idp" class="w3-row directive_field" style="display: none"">
				<div class="w3-col w3-third"><com:TLabel ForControl="OrganizationIdP" Text="<%[ Identity provider ]%>"/>:</div>
				<div class="w3-half">
					<com:TActiveDropDownList
						ID="OrganizationIdP"
						CssClass="w3-select w3-border"
						AutoPostBack="false"
					/>
					<com:TRequiredFieldValidator
						ValidationGroup="OrganizationGroup"
						ControlToValidate="OrganizationIdP"
						ErrorMessage="<%[ Field required. ]%>"
						ControlCssClass="field_invalid"
						Display="Dynamic"
					>
						<prop:ClientSide.OnValidate>
							const radio = document.getElementById('<%=$this->OrganizationIdPOpt->ClientID%>');
							sender.enabled = radio.checked;
						</prop:ClientSide.OnValidate>
					</com:TRequiredFieldValidator>
				</div> &nbsp;<i class="fa fa-asterisk w3-text-red opt_req"></i>
			</div>
			<div class="w3-row directive_field">
				<div class="w3-col w3-third"><com:TLabel ForControl="OrganizationEnabled" Text="<%[ Enabled ]%>"/>:</div>
				<div class="w3-half">
					<com:TActiveCheckBox
						ID="OrganizationEnabled"
						CssClass="w3-check w3-border"
						AutoPostBack="false"
						Checked="true"
					/>
				</div>
			</div>
			<div class="w3-row directive_field">
				<div class="w3-col w3-third"><com:TLabel ForControl="OrganizationLoginBtnColor" Text="<%[ Login button color ]%>"/>:</div>
				<div class="w3-half">
					<com:TColorPicker
						ID="OrganizationLoginBtnColor"
						CssClass="w3-input w3-border"
						Style="width: 200px; display: inline-block;"
						Text="#FF9900"
						ClientSide.OnColorSelected="$('#<%=$this->OrganizationLoginBtnColor->ClientID%>_picker').hide();"
					/>
				</div>
			</div>
		</div>
		<footer class="w3-container w3-center">
			<button type="button" class="w3-button w3-red" onclick="document.getElementById('organization_window').style.display = 'none';"><i class="fas fa-times"></i> &nbsp;<%[ Cancel ]%></button>
			<com:TActiveLinkButton
				ID="OrganizationSave"
				ValidationGroup="OrganizationGroup"
				CausesValidation="true"
				OnCallback="saveOrganization"
				CssClass="w3-button w3-section w3-green w3-padding"
			>
				<i class="fa fa-save"></i> &nbsp;<%[ Save ]%>
			</com:TActiveLinkButton>
		</footer>
	</div>
	<com:TActiveHiddenField ID="OrganizationWindowType" />
</div>
