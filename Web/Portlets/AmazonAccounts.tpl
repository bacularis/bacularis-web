<div>
	<p class="w3-hide-small"><%[ Configure AWS accounts used for backup and restore operations. You can use the same account or different accounts for individual EC2 instance and EBS volume backups. AWS accounts are also used to retrieve and display AWS resources on this Cloud page. ]%></p>
	<div class="w3-panel">
		<button type="button" id="add_amazon_account_btn" class="w3-button w3-green" onclick="oAmazonAccounts.load_account_window();"><i class="fa fa-plus"></i> &nbsp;<%[ Add AWS account ]%></button>
	</div>
	<!-- Tag tools -->
	<com:Bacularis.Web.Portlets.TagTools ID="TagToolsAmazonAccountList" ViewName="amazon_account_list" />
	<table id="amazon_account_list_table" class="display w3-table w3-striped w3-hoverable w3-margin-bottom selectable" style="width: 100%;">
		<thead>
			<tr>
				<th></th>
				<th><%[ Account name ]%></th>
				<th><%[ Description ]%></th>
				<th><%[ Access method ]%></th>
				<th class="w3-center"><%[ Enabled ]%></th>
				<th class="w3-center"><%[ Tag ]%></th>
				<th class="w3-center"><%[ Actions ]%></th>
			</tr>
		</thead>
		<tbody id="amazon_account_list_body"></tbody>
		<tfoot>
			<tr>
				<th></th>
				<th><%[ Account name ]%></th>
				<th><%[ Description ]%></th>
				<th><%[ Access method ]%></th>
				<th class="w3-center"><%[ Enabled ]%></th>
				<th class="w3-center"><%[ Tag ]%></th>
				<th class="w3-center"><%[ Actions ]%></th>
			</tr>
		</tfoot>
	</table>
	<p class="info w3-hide-medium w3-hide-small"><%[ Tip: Use left-click to select table row. Use CTRL + left-click to multiple row selection. Use SHIFT + left-click to add a range of rows to selection. ]%></p>
<com:TCallback ID="AmazonAccountList" OnCallback="TemplateControl.setAccountList" />
<com:TCallback ID="LoadAmazonAccount" OnCallback="TemplateControl.loadAccountWindow" />
<com:TCallback ID="RemoveAmazonAccountsAction" OnCallback="TemplateControl.removeAccounts" />
<script>
var oAmazonAccountList = {
	ids: {
		amazon_account_list: 'amazon_account_list_table'
	},
	actions: [
		{
			action: 'remove',
			label: '<%[ Remove ]%>',
			value: ['name'],
			callback: <%=$this->RemoveAmazonAccountsAction->ActiveControl->Javascript%>
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
			this.set_filters(this.table);
			this.table_toolbar.style.display = 'none';
		}
	},
	set_events: function() {
		document.getElementById(this.ids.amazon_account_list).addEventListener('click', function(e) {
			$(function() {
				const wa = (this.table.rows({selected: true}).data().length > 0) ? 'show' : 'hide';
				$(this.table_toolbar).animate({
					width: wa
				}, 'fast');
			}.bind(this));
		}.bind(this));
	},
	set_table: function() {
		this.table = $('#' + this.ids.amazon_account_list).DataTable({
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
				{data: 'description'},
				{
					data: 'access_method',
					render: function(data, type, row) {
						let ret;
						if (type == 'display' || type == 'filter') {
							if (data == '<%=Bacularis\Common\Modules\Cloud\Amazon\Account::ACCOUNT_ACCESS_METHOD_STATIC_CREDENTIALS%>') {
								ret = '<%[ static credentials ]%>';
							} else if (data == '<%=Bacularis\Common\Modules\Cloud\Amazon\Account::ACCOUNT_ACCESS_METHOD_ASSUME_ROLE%>') {
								ret = '<%[ assume role ]%>';
							}
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
					data: 'name',
					render: (data, type, row) => {
						const id = 'name';
						const tt_obj = oTagTools_<%=$this->TagToolsAmazonAccountList->ClientID%>;
						const table = 'oAmazonAccountList.table';
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
						btn_edit.setAttribute('onclick', 'oAmazonAccounts.load_account_window(\'' + data + '\')');
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
				targets: [ 6 ]
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
				oAmazonAccountList.set_filters(this.api());
			}
		});
	},
	set_filters: function(api) {
		api.columns([1, 2, 3, 4]).every(function () {
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
			if ([4].indexOf(column[0][0]) != -1) { // Enabled and In use by columns
				column.data().unique().sort().each(function (d, j) {
					var ds = d;
					if (column[0][0] == 4) { // Enabled column
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
	set_bulk_actions: function() {
		this.table_toolbar = get_table_toolbar(this.table, this.actions, {
			actions: '<%[ Select action ]%>',
			ok: '<%[ OK ]%>'
		});
	}
};

var oAmazonAccounts = {
	ids: {
		account_win: 'amazon_account_window'
	},
	load_account_window: function(name) {
		let title_add = document.getElementById('amazon_account_window_title_add');
		let title_edit = document.getElementById('amazon_account_window_title_edit');
		let account_win_type = document.getElementById('<%=$this->AmazonAccountWindowType->ClientID%>');
		let account_name = document.getElementById('<%=$this->AmazonAccountName->ClientID%>');
		const cb = <%=$this->LoadAmazonAccount->ActiveControl->Javascript%>;
		cb.setCallbackParameter(name);
		cb.dispatch();
		if (name) {
			// edit existing amazon account
			title_add.style.display = 'none';
			title_edit.style.display = 'inline-block';
			account_win_type.value = '<%=AmazonAccounts::TYPE_EDIT_WINDOW%>';
			account_name.setAttribute('readonly', '');
		} else {
			// add new amazon account
			title_add.style.display = 'inline-block';
			title_edit.style.display = 'none';
			account_win_type.value = '<%=AmazonAccounts::TYPE_ADD_WINDOW%>';
			account_name.removeAttribute('readonly');
			this.clear_account_window();
		}
		const account_win = document.getElementById(this.ids.account_win);
		account_win.style.display = 'block';
	},
	load_account_list: function() {
		const cb = <%=$this->AmazonAccountList->ActiveControl->Javascript%>;
		cb.dispatch();
	},
	load_account_list_cb: function(list) {
		oAmazonAccountList.data = list;
		oAmazonAccountList.init();
	},
	clear_account_window: function() {
		// clear inputs and selects
		[
			'<%=$this->AmazonAccountName->ClientID%>',
			'<%=$this->AmazonAccountDescription->ClientID%>',
			'<%=$this->AmazonAccountAccessKey->ClientID%>',
			'<%=$this->AmazonAccountSecretKey->ClientID%>',
			'<%=$this->AmazonAccountRoleARN->ClientID%>',
			'<%=$this->AmazonAccountAssumeRoleAccessKey->ClientID%>',
			'<%=$this->AmazonAccountAssumeRoleSecretKey->ClientID%>',
			'<%=$this->AmazonAccountAssumeRoleService->ClientID%>',
			'<%=$this->AmazonAccountRegion->ClientID%>'
		].forEach(function(id) {
			document.getElementById(id).value = '';
		});

		// clear checkboxes and radio buttons
		[
			'<%=$this->AmazonAccountEnabled->ClientID%>'
		].forEach(function(id) {
			document.getElementById(id).checked = true;
		});

		// click elements
		[
			'<%=$this->AmazonAccountAccessMethodStaticCredentials->ClientID%>',
			'<%=$this->AmazonAccountAccessMethodAssumeRoleOutAWS->ClientID%>'
		].forEach(function(id) {
			$('#' + id).click();
		});
	},
	save_account_cb: function() {
		const account_win = document.getElementById(this.ids.account_win);
		account_win.style.display = 'none';
	}
}

$(function() {
	oAmazonAccounts.load_account_list();
});
</script>
</div>
<div id="amazon_account_window" class="w3-modal">
	<div class="w3-modal-content w3-animate-top w3-card-4">
		<header class="w3-container w3-green">
			<span onclick="document.getElementById('amazon_account_window').style.display = 'none';" class="w3-button w3-display-topright">&times;</span>
			<h2 id="amazon_account_window_title_add" style="display: none"><%[ Add Amazon AWS account ]%></h2>
			<h2 id="amazon_account_window_title_edit" style="display: none"><%[ Edit Amazon AWS account ]%></h2>
		</header>
		<div class="w3-container w3-margin-left w3-margin-right w3-margin-top">
			<com:TActiveLabel ID="AmazonAccountWindowError" CSsClass="error" Display="None" />
			<div class="w3-row directive_field">
				<div class="w3-col w3-third"><label for="<%=$this->AmazonAccountName->ClientID%>"><%[ Account name ]%>:</label></div>
				<div class="w3-col w3-twothird">
					<com:TActiveTextBox
						ID="AmazonAccountName"
						MaxLength="160"
						CssClass="w3-input w3-border w3-show-inline-block"
						Attributes.placeholder="ex: My main AWS account"
					/>
					<i class="fas fa-asterisk w3-text-red opt_req"></i>
					<com:TRequiredFieldValidator
						ValidationGroup="AmazonAccountGroup"
						ControlToValidate="AmazonAccountName"
						ErrorMessage="<%[ Field required. ]%>"
						ControlCssClass="field_invalid"
						Display="Dynamic"
					/>
					<com:TRegularExpressionValidator
						ValidationGroup="AmazonAccountGroup"
						RegularExpression="<%=Bacularis\Common\Modules\Cloud\Amazon\Account::ACCOUNT_NAME_PATTERN%>"
						ControlToValidate="AmazonAccountName"
						ErrorMessage="<%[ Invalid value. ]%>"
						ControlCssClass="field_invalid"
						Display="Dynamic"
					/>
				</div>
			</div>
			<div class="w3-row directive_field">
				<div class="w3-col w3-third"><label for="<%=$this->AmazonAccountDescription->ClientID%>"><%[ Description ]%>:</label></div>
				<div class="w3-col w3-twothird">
					<com:TActiveTextBox
						ID="AmazonAccountDescription"
						TextMode="MultiLine"
						Rows="3"
						AutoPostBack="false"
						MaxLength="500"
						CssClass="w3-input w3-border"
						Attributes.placeholder="ex: They are credentials for AWS production environment..."
					/>
				</div>
			</div>
			<div class="w3-row directive_field">
				<div class="w3-col w3-third"><label><%[ Access method ]%>:</label></div>
				<div class="w3-twothird">
					<com:TActiveRadioButton
						ID="AmazonAccountAccessMethodStaticCredentials"
						GroupName="AmazonAccountGroup"
						CssClass="w3-radio"
						Checked="true"
						Attributes.onclick="$('#amazon_account_access_method_assume_role').slideUp('fast'); $('#amazon_account_access_method_static_credentials').slideDown('fast');"
					/> &nbsp;<label for="<%=$this->AmazonAccountAccessMethodStaticCredentials->ClientID%>"><%[ Static credentials ]%></label><br />
					<com:TActiveRadioButton
						ID="AmazonAccountAccessMethodAssumeRole"
						GroupName="AmazonAccountGroup"
						CssClass="w3-radio"
						Attributes.onclick="$('#amazon_account_access_method_static_credentials').slideUp('fast'); $('#amazon_account_access_method_assume_role').slideDown('fast');"
					/> &nbsp;<label for="<%=$this->AmazonAccountAccessMethodAssumeRole->ClientID%>">STS assume role</label>
				</div>
			</div>
			<h4><%[ Credentials ]%></h4>
			<div id="amazon_account_access_method_static_credentials" class="w3-container w3-row directive_field" style="display: <%=$this->AmazonAccountAccessMethodStaticCredentials->Checked ? 'block' : 'none'%>;">
				<div class="w3-row directive_field">
					<div class="w3-col w3-third"><label><%[ Credential source ]%>:</label></div>
					<div class="w3-twothird">&nbsp;</div>
				</div>
				<div class="w3-row directive_field">
					<div class="w3-col w3-third">&nbsp;</div>
					<div class="w3-twothird">
						<com:TActiveRadioButton
							ID="AmazonAccountAccessMethodStaticCredentialsOutAWS"
							GroupName="AmazonAccountStaticCredentialsGroup"
							CssClass="w3-radio"
							Checked="true"
							Attributes.onclick="$('#amazon_account_access_method_static_credentials_in_aws').slideUp('fast'); $('#amazon_account_access_method_static_credentials_out_aws').slideDown('fast');"
						/> &nbsp;<label for="<%=$this->AmazonAccountAccessMethodStaticCredentialsOutAWS->ClientID%>"><%[ Access keys - Bacularis outside AWS ]%></label>
					</div>
				</div>
				<div class="w3-row directive_field">
					<div class="w3-col w3-third">&nbsp;</div>
					<div class="w3-twothird">
						<com:TActiveRadioButton
							ID="AmazonAccountAccessMethodStaticCredentialsInAWS"
							GroupName="AmazonAccountStaticCredentialsGroup"
							CssClass="w3-radio"
							Attributes.onclick="$('#amazon_account_access_method_static_credentials_out_aws').slideUp('fast'); $('#amazon_account_access_method_static_credentials_in_aws').slideDown('fast');"
						/> &nbsp;<label for="<%=$this->AmazonAccountAccessMethodStaticCredentialsInAWS->ClientID%>"><%[ Instance role - Bacularis running on AWS service ]%></label>
					</div>
				</div>
				<div id="amazon_account_access_method_static_credentials_out_aws" class="w3-row w3-margin-top directive_field" style="display: <%=$this->AmazonAccountAccessMethodStaticCredentialsOutAWS->Checked ? 'block' : 'none'%>;">
					<div class="w3-row directive_field">
						<div class="w3-col w3-third"><label for="<%=$this->AmazonAccountAccessKey->ClientID%>"><%[ Access key ]%>:</label></div>
						<div class="w3-twothird">
							<com:TActiveTextBox
								ID="AmazonAccountAccessKey"
								AutoPostBack="false"
								MaxLength="160"
								CssClass="w3-input w3-border w3-show-inline-block"
								Attributes.placeholder="ex: AIRAY341ZKBOKCUTVV7A"
							/>
							<i class="fas fa-asterisk w3-text-red opt_req"></i>
							<com:TRequiredFieldValidator
								ValidationGroup="AmazonAccountGroup"
								ControlToValidate="AmazonAccountAccessKey"
								ErrorMessage="<%[ Field required. ]%>"
								ControlCssClass="field_invalid"
								Display="Dynamic"
							>
								<prop:ClientSide.OnValidate>
									const el1 = document.getElementById('<%=$this->AmazonAccountAccessMethodStaticCredentials->ClientID%>');
									const el2 = document.getElementById('<%=$this->AmazonAccountAccessMethodStaticCredentialsOutAWS->ClientID%>');
									sender.enabled = el1.checked && el2.checked;
								</prop:ClientSide.OnValidate>
							</com:TRequiredFieldValidator>
						</div>
					</div>
					<div class="w3-row directive_field">
						<div class="w3-col w3-third"><label for="<%=$this->AmazonAccountSecretKey->ClientID%>"><%[ Secret key ]%>:</label></div>
						<div class="w3-twothird">
							<com:TActiveTextBox
								ID="AmazonAccountSecretKey"
								TextMode="Password"
								AutoPostBack="false"
								MaxLength="160"
								CssClass="w3-input w3-border w3-show-inline-block"
								Attributes.placeholder="ex: wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY"
							/>
							<i class="fas fa-asterisk w3-text-red opt_req"></i>
							<a href="javascript:void(0)" onclick="var el = document.getElementById('<%=$this->AmazonAccountSecretKey->ClientID%>'); el.type = el.type == 'text' ? 'password' : 'text'" title="Show/hide password"><i class="fa fa-eye"></i></a>
							<com:TRequiredFieldValidator
								ValidationGroup="AmazonAccountGroup"
								ControlToValidate="AmazonAccountSecretKey"
								ErrorMessage="<%[ Field required. ]%>"
								ControlCssClass="field_invalid"
								Display="Dynamic"
							>
								<prop:ClientSide.OnValidate>
									const el1 = document.getElementById('<%=$this->AmazonAccountAccessMethodStaticCredentials->ClientID%>');
									const el2 = document.getElementById('<%=$this->AmazonAccountAccessMethodStaticCredentialsOutAWS->ClientID%>');
									sender.enabled = el1.checked && el2.checked;
								</prop:ClientSide.OnValidate>
							</com:TRequiredFieldValidator>
						</div>
					</div>
				</div>
			</div>
			<div id="amazon_account_access_method_assume_role" class="w3-container w3-row directive_field" style="display: <%=$this->AmazonAccountAccessMethodAssumeRole->Checked ? 'block' : 'none'%>;">
				<div class="w3-row directive_field">
					<div class="w3-col w3-third"><label for="<%=$this->AmazonAccountRoleARN->ClientID%>"><%[ Role ARN ]%>:</label></div>
					<div class="w3-col w3-twothird">
						<com:TActiveTextBox
							ID="AmazonAccountRoleARN"
							AutoPostBack="false"
							MaxLength="160"
							CssClass="w3-input w3-border w3-show-inline-block"
							Attributes.placeholder="ex: MyBackupRole"
						/>
						<i class="fas fa-asterisk w3-text-red opt_req"></i>
						<com:TRequiredFieldValidator
							ValidationGroup="AmazonAccountGroup"
							ControlToValidate="AmazonAccountRoleARN"
							ErrorMessage="<%[ Field required. ]%>"
							ControlCssClass="field_invalid"
							Display="Dynamic"
						>
							<prop:ClientSide.OnValidate>
								const el = document.getElementById('<%=$this->AmazonAccountAccessMethodAssumeRole->ClientID%>');
								sender.enabled = el.checked;
							</prop:ClientSide.OnValidate>
						</com:TRequiredFieldValidator>
					</div>
				</div>
				<div class="w3-row directive_field">
					<div class="w3-col w3-third"><label><%[ Credential source ]%>:</label></div>
					<div class="w3-twothird">&nbsp;</div>
				</div>
				<div class="w3-row directive_field">
					<div class="w3-col w3-third">&nbsp;</div>
					<div class="w3-twothird">
						<com:TActiveRadioButton
							ID="AmazonAccountAccessMethodAssumeRoleOutAWS"
							GroupName="AmazonAccountAssumeRoleGroup"
							CssClass="w3-radio"
							Checked="true"
							Attributes.onclick="$('#amazon_account_access_method_assume_role_in_aws').slideUp('fast'); $('#amazon_account_access_method_assume_role_out_aws').slideDown('fast');"
						/> &nbsp;<label for="<%=$this->AmazonAccountAccessMethodAssumeRoleOutAWS->ClientID%>"><%[ Access keys - Bacularis outside AWS ]%></label>
					</div>
				</div>
				<div class="w3-row directive_field">
					<div class="w3-col w3-third">&nbsp;</div>
					<div class="w3-twothird">
						<com:TActiveRadioButton
							ID="AmazonAccountAccessMethodAssumeRoleInAWS"
							GroupName="AmazonAccountAssumeRoleGroup"
							CssClass="w3-radio"
							Attributes.onclick="$('#amazon_account_access_method_assume_role_out_aws').slideUp('fast'); $('#amazon_account_access_method_assume_role_in_aws').slideDown('fast');"
						/> &nbsp;<label for="<%=$this->AmazonAccountAccessMethodAssumeRoleInAWS->ClientID%>"><%[ Instance role - Bacularis running on AWS service ]%></label>
					</div>
				</div>
				<div id="amazon_account_access_method_assume_role_out_aws" class="w3-row w3-margin-top directive_field" style="display: <%=$this->AmazonAccountAccessMethodAssumeRoleOutAWS->Checked ? 'block' : 'none'%>;">
					<div class="w3-row directive_field">
						<div class="w3-col w3-third"><label for="<%=$this->AmazonAccountAssumeRoleAccessKey->ClientID%>"><%[ Access key ]%>:</label></div>
						<div class="w3-twothird">
							<com:TActiveTextBox
								ID="AmazonAccountAssumeRoleAccessKey"
								AutoPostBack="false"
								MaxLength="160"
								CssClass="w3-input w3-border w3-show-inline-block"
								Attributes.placeholder="ex: AIRAY341ZKBOKCUTVV7A"
							/>
							<i class="fas fa-asterisk w3-text-red opt_req"></i>
							<com:TRequiredFieldValidator
								ValidationGroup="AmazonAccountGroup"
								ControlToValidate="AmazonAccountAssumeRoleAccessKey"
								ErrorMessage="<%[ Field required. ]%>"
								ControlCssClass="field_invalid"
								Display="Dynamic"
							>
								<prop:ClientSide.OnValidate>
									const el1 = document.getElementById('<%=$this->AmazonAccountAccessMethodAssumeRole->ClientID%>');
									const el2 = document.getElementById('<%=$this->AmazonAccountAccessMethodAssumeRoleOutAWS->ClientID%>');
									sender.enabled = el1.checked && el2.checked;
								</prop:ClientSide.OnValidate>
							</com:TRequiredFieldValidator>
						</div>
					</div>
					<div class="w3-row directive_field">
						<div class="w3-col w3-third"><label for="<%=$this->AmazonAccountAssumeRoleSecretKey->ClientID%>"><%[ Secret key ]%>:</label></div>
						<div class="w3-twothird">
							<com:TActiveTextBox
								ID="AmazonAccountAssumeRoleSecretKey"
								TextMode="Password"
								AutoPostBack="false"
								MaxLength="160"
								CssClass="w3-input w3-border w3-show-inline-block"
								Attributes.placeholder="ex: wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY"
							/>
							<i class="fas fa-asterisk w3-text-red opt_req"></i>
							<a href="javascript:void(0)" onclick="var el = document.getElementById('<%=$this->AmazonAccountAssumeRoleSecretKey->ClientID%>'); el.type = el.type == 'text' ? 'password' : 'text'" title="Show/hide password"><i class="fa fa-eye"></i></a>
							<com:TRequiredFieldValidator
								ValidationGroup="AmazonAccountGroup"
								ControlToValidate="AmazonAccountAssumeRoleSecretKey"
								ErrorMessage="<%[ Field required. ]%>"
								ControlCssClass="field_invalid"
								Display="Dynamic"
							>
								<prop:ClientSide.OnValidate>
									const el1 = document.getElementById('<%=$this->AmazonAccountAccessMethodAssumeRole->ClientID%>');
									const el2 = document.getElementById('<%=$this->AmazonAccountAccessMethodAssumeRoleOutAWS->ClientID%>');
									sender.enabled = el1.checked && el2.checked;
								</prop:ClientSide.OnValidate>
							</com:TRequiredFieldValidator>
						</div>
					</div>
				</div>
				<div id="amazon_account_access_method_assume_role_in_aws" class="w3-row directive_field" style="display: <%=$this->AmazonAccountAccessMethodAssumeRoleInAWS->Checked ? 'block' : 'none'%>;">
					<div class="w3-row directive_field">
						<div class="w3-col w3-third"><label for="<%=$this->AmazonAccountAssumeRoleService->ClientID%>"><%[ Service ]%>:</label></div>
						<div class="w3-twothird">
							<com:TActiveDropDownList
								ID="AmazonAccountAssumeRoleService"
								AutoPostBack="false"
								CssClass="w3-select w3-border w3-show-inline-block"
								Width="100px"
								SelectedValue="<%=Bacularis\Common\Modules\Cloud\Amazon\EC2\EC2::SERVICE_NAME%>"
							>
								<com:TListItem Value="<%=Bacularis\Common\Modules\Cloud\Amazon\EC2\EC2::SERVICE_NAME%>" Text="EC2" />
							</com:TActiveDropDownList>
							<i class="fas fa-asterisk w3-text-red opt_req"></i>
							<com:TRequiredFieldValidator
								ValidationGroup="AmazonAccountGroup"
								ControlToValidate="AmazonAccountAssumeRoleService"
								ErrorMessage="<%[ Field required. ]%>"
								ControlCssClass="field_invalid"
								Display="Dynamic"
							>
								<prop:ClientSide.OnValidate>
									const el1 = document.getElementById('<%=$this->AmazonAccountAccessMethodAssumeRole->ClientID%>');
									const el2 = document.getElementById('<%=$this->AmazonAccountAccessMethodAssumeRoleInAWS->ClientID%>');
									sender.enabled = el1.checked && el2.checked;
								</prop:ClientSide.OnValidate>
							</com:TRequiredFieldValidator>
						</div>
					</div>
				</div>
			</div>
			<div class="w3-row directive_field">
				<div class="w3-col w3-third"><label for="<%=$this->AmazonAccountEnabled->ClientID%>"><%[ Region ]%>:</label></div>
				<div class="w3-twothird">
					<com:TActiveDropDownList
						ID="AmazonAccountRegion"
						CssClass="w3-select w3-border w3-show-inline-block"
						AutoPostBack="false"
					/>
					<i class="fas fa-asterisk w3-text-red opt_req"></i>
					<com:TRequiredFieldValidator
						ValidationGroup="AmazonAccountGroup"
						ControlToValidate="AmazonAccountRegion"
						ErrorMessage="<%[ Field required. ]%>"
						ControlCssClass="field_invalid"
						Display="Dynamic"
					/>
				</div>
			</div>
			<div class="w3-row directive_field">
				<div class="w3-col w3-third"><label for="<%=$this->AmazonAccountEnabled->ClientID%>"><%[ Enabled ]%>:</label></div>
				<div class="w3-twothird">
					<com:TActiveCheckBox
						ID="AmazonAccountEnabled"
						CssClass="w3-check w3-border"
						AutoPostBack="false"
						Checked="true"
					/>
				</div>
			</div>
		</div>
		<footer class="w3-container w3-center">
			<button type="button" class="w3-button w3-red" onclick="document.getElementById('amazon_account_window').style.display = 'none';"><i class="fas fa-times"></i> &nbsp;<%[ Cancel ]%></button>
			<com:TActiveLinkButton
				ID="AmazonAccountSave"
				ValidationGroup="AmazonAccountGroup"
				CausesValidation="true"
				OnCallback="saveAccount"
				CssClass="w3-button w3-section w3-green w3-padding"
			>
				<i class="fa fa-save"></i> &nbsp;<%[ Save ]%>
			</com:TActiveLinkButton>
		</footer>
	</div>
	<com:TActiveHiddenField ID="AmazonAccountWindowType" />
</div>
