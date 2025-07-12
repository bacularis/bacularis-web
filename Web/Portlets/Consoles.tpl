<div>
	<p class="w3-hide-small"><%[ The console ACLs enable to define available resources for users. The consoles are used in the Bacula configuration on the API host side. There is possible to assign the consoles to the API basic users or to the OAuth2 clients. The assign relation for the basic users is: Console ACL -&gt; API basic user -&gt; API host -&gt; User account. For OAuth2 clients the assign relation is: Console ACL -&gt; OAuth2 client -&gt; API host -&gt; User account. ]%></p>
	<div class="w3-panel">
		<button type="button" id="add_console_btn" class="w3-button w3-green" onclick="oConsoles.load_console_window()"><i class="fa fa-plus"></i> &nbsp;<%[ Add new console ]%></button>
	</div>
	<!-- Tag tools -->
	<com:Bacularis.Web.Portlets.TagTools ID="TagToolsConsoleList" ViewName="config_resource_list" />
	<table id="console_list_table" class="display w3-table w3-striped w3-hoverable w3-margin-bottom selectable" style="width: 100%">
		<thead>
			<tr>
				<th></th>
				<th><%[ Name ]%></th>
				<th class="w3-center">Description</th>
				<th class="w3-center">JobACL</th>
				<th class="w3-center">ClientACL</th>
				<th class="w3-center">StorageACL</th>
				<th class="w3-center">ScheduleACL</th>
				<th class="w3-center">RunACL</th>
				<th class="w3-center">PoolACL</th>
				<th class="w3-center">CommandACL</th>
				<th class="w3-center">FileSetACL</th>
				<th class="w3-center">CatalogACL</th>
				<th class="w3-center">WhereACL</th>
				<th class="w3-center">PluginOptionsACL</th>
				<th class="w3-center">BackupClientACL</th>
				<th class="w3-center">RestoreClientACL</th>
				<th class="w3-center">DirectoryACL</th>
				<th class="w3-center"><%[ Tag ]%></th>
				<th class="w3-center"><%[ Action ]%></th>
			</tr>
		</thead>
		<tbody id="console_list_body"></tbody>
		<tfoot>
			<tr>
				<th></th>
				<th><%[ Name ]%></th>
				<th class="w3-center">Description</th>
				<th class="w3-center">JobACL</th>
				<th class="w3-center">ClientACL</th>
				<th class="w3-center">StorageACL</th>
				<th class="w3-center">ScheduleACL</th>
				<th class="w3-center">RunACL</th>
				<th class="w3-center">PoolACL</th>
				<th class="w3-center">CommandACL</th>
				<th class="w3-center">FileSetACL</th>
				<th class="w3-center">CatalogACL</th>
				<th class="w3-center">WhereACL</th>
				<th class="w3-center">PluginOptionsACL</th>
				<th class="w3-center">BackupClientACL</th>
				<th class="w3-center">RestoreClientACL</th>
				<th class="w3-center">DirectoryACL</th>
				<th class="w3-center"><%[ Tag ]%></th>
				<th class="w3-center"><%[ Action ]%></th>
			</tr>
		</tfoot>
	</table>
	<p class="info w3-hide-medium w3-hide-small"><%[ Tip: Use left-click to select table row. Use CTRL + left-click to multiple row selection. Use SHIFT + left-click to add a range of rows to selection. ]%></p>
<com:TCallback ID="ConsoleList" OnCallback="TemplateControl.setConsoleList" />
<com:TCallback ID="LoadConsole" OnCallback="TemplateControl.loadConsoleWindow" />
<com:TCallback ID="RemoveConsolesAction" OnCallback="TemplateControl.removeConsoles" />
<script>
var oConsoleList = {
ids: {
	console_list: 'console_list_table'
},
actions: [
	{
		action: 'remove',
		label: '<%[ Remove ]%>',
		value: 'Name',
		callback: <%=$this->RemoveConsolesAction->ActiveControl->Javascript%>
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
	document.getElementById(this.ids.console_list).addEventListener('click', function(e) {
		$(function() {
			const wa = (this.table.rows({selected: true}).data().length > 0) ? 'show' : 'hide';
			$(this.table_toolbar).animate({
				width: wa
			}, 'fast');
		}.bind(this));
	}.bind(this));
},
set_table: function() {
	this.table = $('#' + this.ids.console_list).DataTable({
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
			{data: 'Name'},
			{
				data: 'Description',
				visible: false
			},
			{
				data: 'JobAcl',
				render: render_string_short
			},
			{
				data: 'ClientAcl',
				render: render_string_short
			},
			{
				data: 'StorageAcl',
				render: render_string_short
			},
			{
				data: 'ScheduleAcl',
				render: render_string_short,
				visible: false
			},
			{
				data: 'RunAcl',
				render: render_string_short,
				visible: false
			},
			{
				data: 'PoolAcl',
				render: render_string_short
			},
			{
				data: 'CommandAcl',
				render: render_string_short,
				visible: false
			},
			{
				data: 'FilesetAcl',
				render: render_string_short
			},
			{
				data: 'CatalogAcl',
				render: render_string_short,
				visible: false
			},
			{
				data: 'WhereAcl',
				render: render_string_short,
				visible: false
			},
			{
				data: 'PluginOptionsAcl',
				render: render_string_short,
				visible: false
			},
			{
				data: 'BackupClientAcl',
				render: render_string_short,
				visible: false
			},
			{
				data: 'RestoreClientAcl',
				render: render_string_short,
				visible: false
			},
			{
				data: 'DirectoryAcl',
				render: render_string_short,
				visible: false
			},
			{
				data: 'Name',
				render: (data, type, row) => {
					const id = '<%=$this->User->getDefaultAPIHost()%>_dir_Console';
					const tt_obj = oTagTools_<%=$this->TagToolsConsoleList->ClientID%>;
					const table = 'oConsoleList.table';
					return render_tags(type, id, data, tt_obj, table);
				}
			},
			{
				data: 'Name',
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
					btn_edit.setAttribute('onclick', 'oConsoles.load_console_window(\'' + data + '\')');
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
			targets: [ 18 ]
		},
		{
			className: "dt-center",
			targets: [ 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17 ]
		}],
		select: {
			style:    'os',
			selector: 'td:not(:last-child):not(:first-child):not(:nth-last-child(2))',
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

var oConsoles = {
load_console_window: function(name) {
	var title_add = document.getElementById('console_window_title_add');
	var title_edit = document.getElementById('console_window_title_edit');
	var console_win_type = document.getElementById('<%=$this->ConsoleWindowType->ClientID%>');
	var all_command_acls = document.getElementById('<%=$this->ConsoleBaculumConfig->ClientID%>');
	// callback is sent both for new and edit console because there is realized
	// checking if password is allowed to set or not
	var cb = <%=$this->LoadConsole->ActiveControl->Javascript%>;
	cb.setCallbackParameter(name);
	cb.dispatch();
	if (name) {
		// edit existing console
		title_add.style.display = 'none';
		title_edit.style.display = 'inline-block';
		all_command_acls.style.display = 'none';
		console_win_type.value = 'edit';
	} else {
		// add new console
		title_add.style.display = 'inline-block';
		title_edit.style.display = 'none';
		all_command_acls.style.display = '';
		console_win_type.value = 'add';
	}
	document.getElementById('console_window').style.display = 'block';
},
load_console_list: function() {
	var cb = <%=$this->ConsoleList->ActiveControl->Javascript%>;
	cb.dispatch();
},
load_console_list_cb: function(list) {
	oConsoleList.data = list;
	oConsoleList.init();
},
save_console_cb: function() {
	this.load_console_list();
	document.getElementById('console_window').style.display = 'none';
}
}

$(function() {
oConsoles.load_console_list();
});
	</script>
</div>
<div id="console_window" class="w3-modal">
	<div class="w3-modal-content w3-animate-top w3-card-4">
		<header class="w3-container w3-green">
			<span onclick="document.getElementById('console_window').style.display = 'none';" class="w3-button w3-display-topright">&times;</span>
			<h2 id="console_window_title_add" style="display: none"><%[ Add console ]%></h2>
			<h2 id="console_window_title_edit" style="display: none"><%[ Edit console ]%></h2>
		</header>
		<div class="w3-container w3-margin-left w3-margin-right w3-margin-top">
			<com:TActiveLinkButton
				ID="ConsoleBaculumConfig"
				CssClass="w3-right"
				OnCommand="setAllCommandAcls"
				CommandParameter="save"
			>
				<i class="fas fa-globe"></i> &nbsp;<%[ Set all CommandAcls used by Bacularis Web ]%>
			</com:TActiveLinkButton>
			<com:Bacularis.Web.Portlets.BaculaConfigDirectives
				ID="ConsoleConfig"
				ComponentType="dir"
				ResourceType="Console"
				ShowRemoveButton="false"
				ShowCancelButton="false"
				ShowBottomButtons="false"
				SaveDirectiveActionOk="oConsoles.save_console_cb();"
				OnSave="postSaveConsole"
			/>
		</div>
	</div>
	<com:TActiveHiddenField ID="ConsoleWindowType" />
</div>
