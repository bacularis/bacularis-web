<div>
	<p class="w3-hide-small"><%[ The API host groups are named sets of API hosts which can be used to assign to users instead of assigning single API hosts. The API group function is more flexible solution than assigning API hosts directly and may be useful in environments where users have access to more than one API host. ]%></p>
	<div class="w3-panel">
		<button type="button" id="add_api_host_group_btn" class="w3-button w3-green" onclick="oAPIHostGroups.load_api_host_group_window()"><i class="fa fa-plus"></i> &nbsp;<%[ Add new API host group ]%></button>
	</div>
	<!-- Tag tools -->
	<com:Bacularis.Web.Portlets.TagTools ID="TagToolsAPIHostGroupList" ViewName="api_host_group_list" />
	<table id="api_host_group_list_table" class="display w3-table w3-striped w3-hoverable w3-margin-bottom selectable" style="width: 100%">
		<thead>
			<tr>
				<th></th>
				<th><%[ API group name ]%></th>
				<th class="w3-center"># <%[ API hosts ]%></th>
				<th class="w3-center"><%[ API hosts ]%></th>
				<th class="w3-center"><%[ Tag ]%></th>
				<th class="w3-center"><%[ Action ]%></th>
			</tr>
		</thead>
		<tbody id="api_host_group_list_body"></tbody>
		<tfoot>
			<tr>
				<th></th>
				<th><%[ API group name ]%></th>
				<th class="w3-center"># <%[ API hosts ]%></th>
				<th class="w3-center"><%[ API hosts ]%></th>
				<th class="w3-center"><%[ Tag ]%></th>
				<th class="w3-center"><%[ Action ]%></th>
			</tr>
		</tfoot>
	</table>
	<p class="info w3-hide-medium w3-hide-small"><%[ Tip: Use left-click to select table row. Use CTRL + left-click to multiple row selection. Use SHIFT + left-click to add a range of rows to selection. ]%></p>
<com:TCallback ID="APIHostGroupList" OnCallback="TemplateControl.setAPIHostGroupList" />
<com:TCallback ID="LoadAPIHostGroup" OnCallback="TemplateControl.loadAPIHostGroupWindow" />
<com:TCallback ID="RemoveAPIHostGroupsAction" OnCallback="TemplateControl.removeAPIHostGroups" />
<script>
var oAPIHostGroupList = {
	ids: {
		api_host_group_list: 'api_host_group_list_table'
	},
	actions: [
		{
			action: 'remove',
			label: '<%[ Remove ]%>',
			value: 'name',
			callback: <%=$this->RemoveAPIHostGroupsAction->ActiveControl->Javascript%>
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
			oAPIHostGroupList.set_filters(this.table);
			this.table_toolbar.style.display = 'none';
		}
	},
	set_events: function() {
		document.getElementById(this.ids.api_host_group_list).addEventListener('click', function(e) {
			$(function() {
				const wa = (this.table.rows({selected: true}).data().length > 0) ? 'show' : 'hide';
				$(this.table_toolbar).animate({
					width: wa
				}, 'fast');
			}.bind(this));
		}.bind(this));
	},
	set_table: function() {
		this.table = $('#' + this.ids.api_host_group_list).DataTable({
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
				{
					data: 'api_hosts',
					render: (data, type, row) => data.length
				},
				{
					data: 'api_hosts_str',
					render: render_string_short
				},
				{
					data: 'name',
					render: (data, type, row) => {
						const id = 'name';
						const tt_obj = oTagTools_<%=$this->TagToolsAPIHostGroupList->ClientID%>;
						const table = 'oAPIHostGroupList.table';
						return render_tags(type, id, data, tt_obj, table);
					}
				},
				{
					data: 'name',
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
						access_btn.setAttribute('onclick', 'oAPIHostGroups.load_access_window("' + data + '")');
						span.appendChild(access_btn);
						span.style.marginRight = '5px';
						btns += span.outerHTML;

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
						btn_edit.setAttribute('onclick', 'oAPIHostGroups.load_api_host_group_window(\'' + data + '\')');
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
				targets: [ 5 ]
			},
			{
				className: "dt-center",
				targets: [ 2, 3, 4 ]
			}],
			select: {
				style:    'os',
				selector: 'td:not(:last-child):not(:first-child):not(:nth-last-child(2))',
				blurable: false
			},
			order: [1, 'asc'],
			initComplete: function () {
				oAPIHostGroupList.set_filters(this.api());
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

var oAPIHostGroups = {
	load_api_host_group_window: function(name) {
		let title_add = document.getElementById('api_host_group_window_title_add');
		let title_edit = document.getElementById('api_host_group_window_title_edit');
		let api_host_group_win_type = document.getElementById('<%=$this->APIHostGroupWindowType->ClientID%>');
		let host_name = document.getElementById('<%=$this->APIHostGroupName->ClientID%>');
		const cb = <%=$this->LoadAPIHostGroup->ActiveControl->Javascript%>;
		cb.setCallbackParameter(name);
		cb.dispatch();
		if (name) {
			// edit existing api_host_group
			title_add.style.display = 'none';
			title_edit.style.display = 'inline-block';
			api_host_group_win_type.value = 'edit';
			host_name.setAttribute('readonly', '');
		} else {
			// add new api_host_group
			title_add.style.display = 'inline-block';
			title_edit.style.display = 'none';
			api_host_group_win_type.value = 'add';
			host_name.removeAttribute('readonly');
			this.clear_api_host_group_window();
		}
		document.getElementById('api_host_group_window').style.display = 'block';
	},
	load_api_host_group_list: function() {
		const cb = <%=$this->APIHostGroupList->ActiveControl->Javascript%>;
		cb.dispatch();
	},
	load_api_host_group_list_cb: function(list) {
		oAPIHostGroupList.data = list;
		oAPIHostGroupList.init();
	},
	clear_api_host_group_window: function() {
		[
			'<%=$this->APIHostGroupName->ClientID%>',
			'<%=$this->APIHostGroupDescription->ClientID%>',
			'<%=$this->APIHostGroupAPIHosts->ClientID%>'
		].forEach(function(id) {
			document.getElementById(id).value = '';
		});
	},
	save_api_host_group_cb: function() {
		document.getElementById('api_host_group_window').style.display = 'none';
	},
	load_access_window: function(name) {
		this.clear_access_window();
		document.getElementById('<%=$this->APIHostGroupAPIHostResourceAccessName->ClientID%>').value = name;
		const cb = <%=$this->LoadAPIHostGroupAPIHostResourceAccess->ActiveControl->Javascript%>;
		cb.setCallbackParameter(name);
		cb.dispatch();
		document.getElementById('api_host_group_access_window_title').textContent = name;
		document.getElementById('api_host_group_access_window').style.display = 'block';
	},
	clear_access_window: function() {
		// empty fields
		[
			'<%=$this->APIHostGroupAPIHostResourceAccessJobs->ClientID%>'
		].forEach((id) => {
			$('#' + id).empty();
		});

		// reset radio buttons
		document.getElementById('<%=$this->APIHostGroupAPIHostResourceAccessAllResources->ClientID%>').checked = true;
		document.getElementById('api_host_group_access_window_error').style.display = 'none';
		document.getElementById('api_host_group_access_window_console').style.display = 'none';
		document.getElementById('api_host_group_access_window_select_jobs').style.display = 'none';
		document.getElementById('api_host_group_access_window_select_access').style.display = 'none';
	},
	unassign_console: function() {
		const api_host = document.getElementById('<%=$this->APIHostGroupAPIHostList->ClientID%>').value;
		const cb = <%=$this->UnassignAPIHostGroupAPIHostConsole->ActiveControl->Javascript%>;
		cb.setCallbackParameter(api_host);
		cb.dispatch();
	}
};

$(function() {
	oAPIHostGroups.load_api_host_group_list();
});
	</script>
</div>
<div id="api_host_group_window" class="w3-modal">
	<div class="w3-modal-content w3-animate-top w3-card-4">
		<header class="w3-container w3-green">
			<span onclick="document.getElementById('api_host_group_window').style.display = 'none';" class="w3-button w3-display-topright">&times;</span>
			<h2 id="api_host_group_window_title_add" style="display: none"><%[ Add API host group ]%></h2>
			<h2 id="api_host_group_window_title_edit" style="display: none"><%[ Edit API host group ]%></h2>
		</header>
		<div class="w3-container w3-margin-left w3-margin-right w3-margin-top">
			<span id="api_host_group_window_group_exists" class="error" style="display: none"><ul><li><%[ API host group with the given name already exists. ]%></li></ul></span>
			<div class="w3-row directive_field">
				<div class="w3-col w3-third"><com:TLabel ForControl="APIHostGroupName" Text="<%[ API host group: ]%>"/></div>
				<div class="w3-half">
					<com:TActiveTextBox
						ID="APIHostGroupName"
						AutoPostBack="false"
						MaxLength="100"
						CssClass="w3-input w3-border"
					/>
					<com:TRequiredFieldValidator
						ValidationGroup="APIHostGroupGroup"
						ControlToValidate="APIHostGroupName"
						ErrorMessage="<%[ Field required. ]%>"
						ControlCssClass="field_invalid"
						Display="Dynamic"
					/>
					<com:TRegularExpressionValidator
						ValidationGroup="APIHostGroupGroup"
						RegularExpression="<%=HostGroupConfig::HOST_GROUP_PATTERN%>"
						ControlToValidate="APIHostGroupName"
						ErrorMessage="<%[ Invalid value. ]%>"
						ControlCssClass="field_invalid"
						Display="Dynamic"
					/>
				</div> &nbsp;<i id="api_host_group_window_required" class="fa fa-asterisk w3-text-red opt_req" style="display none"></i>
			</div>
			<div class="w3-row directive_field">
				<div class="w3-col w3-third"><com:TLabel ForControl="APIHostGroupDescription" Text="<%[ Description: ]%>"/></div>
				<div class="w3-half">
					<com:TActiveTextBox
						ID="APIHostGroupDescription"
						TextMode="MultiLine"
						Rows="3"
						AutoPostBack="false"
						MaxLength="500"
						CssClass="w3-input w3-border"
					/>
				</div>
			</div>
			<div class="w3-row directive_field">
				<div class="w3-col w3-third"><com:TLabel ForControl="APIHostGroupAPIHosts" Text="<%[ API hosts: ]%>"/></div>
				<div class="w3-half">
					<com:TActiveListBox
						ID="APIHostGroupAPIHosts"
						SelectionMode="Multiple"
						Rows="10"
						CssClass="w3-select w3-border"
						AutoPostBack="false"
					/>
					<p style="margin: 0 16px 0 0"><%[ Use CTRL + left-click to multiple item selection ]%></p>
				</div>
			</div>
		</div>
		<footer class="w3-container w3-center">
			<button type="button" class="w3-button w3-red" onclick="document.getElementById('api_host_group_window').style.display = 'none';"><i class="fas fa-times"></i> &nbsp;<%[ Cancel ]%></button>
			<com:TActiveLinkButton
				ID="APIHostGroupSave"
				ValidationGroup="APIHostGroupGroup"
				CausesValidation="true"
				OnCallback="saveAPIHostGroup"
				CssClass="w3-button w3-section w3-green w3-padding"
			>
				<i class="fa fa-save"></i> &nbsp;<%[ Save ]%>
			</com:TActiveLinkButton>
		</footer>
	</div>
	<com:TActiveHiddenField ID="APIHostGroupWindowType" />
</div>
<div id="api_host_group_access_window" class="w3-modal">
	<div class="w3-modal-content w3-animate-top w3-card-4">
		<header class="w3-container w3-green">
			<span onclick="document.getElementById('api_host_group_access_window').style.display = 'none';" class="w3-button w3-display-topright">&times;</span>
			<h2><span id="api_host_group_access_window_title"></span> - <%[ Set API host group access to resources ]%> &nbsp;<i id="get_api_host_group_loader" class="fa fa-sync w3-spin" style="visibility: hidden;"></i></h2>
		</header>
		<div class="w3-container w3-margin-left w3-margin-right w3-margin-top">
			<div class="w3-row directive_field">
				<div class="w3-col w3-third"><com:TLabel ForControl="APIHostGroupAPIHostList" Text="<%[ API host: ]%>"/></div>
				<div class="w3-twothird w3-show-inline-block">
					<com:TActiveDropDownList
						ID="APIHostGroupAPIHostList"
						CssClass="w3-select w3-border"
						OnCallback="setAPIHostGroupAPIHostResourceAccessWindow"
						Attributes.onclick=""
					>
						<prop:ClientSide.OnLoading>
							document.getElementById('get_api_host_group_loader').style.visibility = 'visible';
							const sa = document.getElementById('api_host_group_access_window_select_access');
							sa.style.display = 'none';
						</prop:ClientSide.OnLoading>
						<prop:ClientSide.OnComplete>
							document.getElementById('get_api_host_group_loader').style.visibility = 'hidden';
							const el = document.getElementById('<%=$this->APIHostGroupAPIHostList->ClientID%>');
							const sa = document.getElementById('api_host_group_access_window_select_access');
							const err = document.getElementById('api_host_group_access_window_error');
							if (err.style.display == 'none') {
								sa.style.display =  el.value ? 'block' : 'none';
							} else {
								sa.style.display = 'none';
							}
						</prop:ClientSide.OnComplete>
					</com:TActiveDropDownList>
					<i class="fas fa-info-circle help_icon w3-text-green" onclick="$('#help_group_api_hosts').slideToggle('fast');"></i>
					&nbsp;<i class="fa fa-asterisk w3-text-red opt_req" style="vertical-align: top"></i>
					<br />
					<com:TRequiredFieldValidator
						ControlCssClass="field_invalid"
						Display="Dynamic"
						ControlToValidate="APIHostGroupAPIHostList"
						ValidationGroup="APIHostGroupAPIHostResourceAccessGroup"
						ErrorMessage="<%[ Field required. ]%>"
					/>
					<div id="help_group_api_hosts" class="directive_help" style="display: none">
						<%[ Select an API host to set resources and permissions. Please note that the 'Main' API host is not listed because the 'Main' is the full access API host and it should not be limited. ]%>
					</div>
				</div>
			</div>
			<div id="api_host_group_access_window_select_access" style="display: none">
				<h3><%[ Resource access ]%></h3>
				<div class="w3-row directive_field">
					<p><com:TActiveRadioButton
						ID="APIHostGroupAPIHostResourceAccessAllResources"
						GroupName="APIHostGroupAPIHostResourceAccess"
						CssClass="w3-radio"
						Attributes.onclick="$('#api_host_group_access_window_select_jobs').slideUp();"
						Checked="true"
						/> <label for="<%=$this->APIHostGroupAPIHostResourceAccessAllResources->ClientID%>"><%[ Access to all shared API host resources (all jobs, all clients, all storages...etc.) ]%></label></p>
					<p><com:TActiveRadioButton
						ID="APIHostGroupAPIHostResourceAccessSelectedResources"
						GroupName="APIHostGroupAPIHostResourceAccess"
						CssClass="w3-radio"
						Attributes.onclick="$('#api_host_group_access_window_select_jobs').slideDown();"
						/> <label for="<%=$this->APIHostGroupAPIHostResourceAccessSelectedResources->ClientID%>"><%[ Access to selected resources only ]%></label></p>
				</div>
				<div id="api_host_group_access_window_select_jobs" class="w3-row directive_field" style="display: none">
					<div id="api_host_group_access_window_console" class="w3-section" style="display: none"><%[ Bacula Console ACL ]%>: <strong><%[ Assigned ]%></strong> ( <a href="javascript:void(0)" onclick="oAPIHostGroups.unassign_console();"><%[ unassign ]%></a> )</div>
					<div class="w3-col w3-third"><com:TLabel ForControl="APIHostGroupAPIHostResourceAccessJobs" Text="<%[ API host jobs: ]%>"/></div>
					<div class="w3-twothird">
						<com:TActiveListBox
							ID="APIHostGroupAPIHostResourceAccessJobs"
							SelectionMode="Multiple"
							Rows="10"
							CssClass="w3-select w3-border"
							ValidationGroup="APIHostGroupAPIHostResourceAccessGroup"
							AutoPostBack="false"
						/> &nbsp;<i class="fa fa-asterisk w3-text-red opt_req" style="vertical-align: top"></i>
						<com:TRequiredFieldValidator
							ControlCssClass="field_invalid"
							Display="Dynamic"
							ControlToValidate="APIHostGroupAPIHostResourceAccessJobs"
							ValidationGroup="APIHostGroupAPIHostResourceAccessGroup"
							ErrorMessage="<%[ Field required. ]%>"
						>
							<prop:ClientSide.OnValidate>
								const radio = document.getElementById('<%=$this->APIHostGroupAPIHostResourceAccessSelectedResources->ClientID%>');
								sender.enabled = radio.checked;
							</prop:ClientSide.OnValidate>
						</com:TRequiredFieldValidator>

						<p style="margin: 0 16px 0 0"><%[ Use CTRL + left-click to multiple item selection ]%></p>
					</div>
				</div>
				<h3><%[ Resource permissions ]%></h3>
				<p class="italic"><%[ By default, all resources have read-write permissions. ]%></p>
				<com:Bacularis.Common.Portlets.BaculaResourcePermissions
					ID="APIHostGroupResourcePermissions"
				/>
			</div>
			<div id="api_host_group_access_window_error" class="w3-red" style="display: none"></div>
		</div>
		<footer class="w3-container w3-center">
			<button type="button" class="w3-button w3-red" onclick="document.getElementById('api_host_group_access_window').style.display = 'none';"><i class="fas fa-times"></i> &nbsp;<%[ Cancel ]%></button>
			<com:TActiveLinkButton
				ID="APIHostGroupAPIHostResourceAccessSave"
				ValidationGroup="APIHostGroupAPIHostResourceAccessGroup"
				CausesValidation="true"
				OnCallback="saveAPIHostGroupAPIHostResourceAccess"
				CssClass="w3-button w3-section w3-green w3-padding"
				Attributes.onclick="<%=$this->APIHostGroupResourcePermissions->ClientID%>ResourcePermissions.save_user_props();"
			>
				<i class="fa fa-save"></i> &nbsp;<%[ Save ]%>
			</com:TActiveLinkButton>
		</footer>
		<com:TActiveHiddenField ID="APIHostGroupAPIHostResourceAccessName" />
	</div>
	<com:TCallback ID="LoadAPIHostGroupAPIHostResourceAccess" OnCallback="TemplateControl.loadAPIHostGroupAPIHostResourceAccessWindow">
		<prop:ClientSide.OnLoading>
			document.getElementById('get_api_host_group_loader').style.visibility = 'visible';
		</prop:ClientSide.OnLoading>
		<prop:ClientSide.OnComplete>
			document.getElementById('get_api_host_group_loader').style.visibility = 'hidden';
		</prop:ClientSide.OnComplete>
	</com:TCallback>
	<com:TCallback ID="UnassignAPIHostGroupAPIHostConsole" OnCallback="TemplateControl.unassignAPIHostGroupAPIHostConsole" />
</div>
