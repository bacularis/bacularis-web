<span id="<%=$this->ClientID%>_error_msg" class="w3-text-red" style="display: none">
	<%[ There was a problem with loading the resource configuration. Please check if selected API host is working and if it provides access to the resource configuration. ]%>
</span>
<div id="<%=$this->ClientID%>_container">
	<div class="w3-container">
		<a href="javascript:void(0)" class="w3-button w3-margin-bottom w3-green" onclick="oBaculaConfigResourceWindow<%=$this->ClientID%>.load_resource_window();"><i class="fa fa-plus"></i> &nbsp;<%[ Add ]%> <com:TActiveLabel ID="ResourceTypeAddLink" /></a>
	</div>
	<table id="<%=$this->ClientID%>_list" class="w3-table w3-striped w3-hoverable w3-margin-bottom" style="width: 100%">
		<thead>
			<tr>
				<th></th>
				<com:Bacularis.Common.Portlets.BSimpleRepeater ID="ResourceListHeaderRepeater">
					<prop:ItemTemplate>
						<th class="dt-center"><%=$this->Data['label']%></th>
					</prop:ItemTemplate>
				</com:Bacularis.Common.Portlets.BSimpleRepeater>
				<th><%[ In use by ]%></th>
				<th><%[ Actions ]%></th>
			</tr>
		</thead>
		<tbody id="<%=$this->ClientID%>_list_body"></tbody>
		<tfoot>
			<tr>
				<th></th>
				<com:Bacularis.Common.Portlets.BSimpleRepeater ID="ResourceListFooterRepeater">
					<prop:ItemTemplate>
						<th class="dt-center"><%=$this->Data['label']%></th>
					</prop:ItemTemplate>
				</com:Bacularis.Common.Portlets.BSimpleRepeater>
				<th><%[ In use by ]%></th>
				<th><%[ Actions ]%></th>
			</tr>
		</tfoot>
	</table>
	<p class="info w3-hide-medium w3-hide-small"><%[ Tip: Use left-click to select table row. Use CTRL + left-click to multiple row selection. Use SHIFT + left-click to add a range of rows to selection. ]%></p>
</div>
<div id="<%=$this->ClientID%>_in_use_by_modal" class="w3-modal" style="display: none;">
	<div class="w3-modal-content w3-animate-top w3-card-4" style="min-width: 60%;">
		<header class="w3-container w3-green">
			<span onclick="oBaculaConfigResourceDeps<%=$this->ClientID%>.show_window(false);" class="w3-button w3-display-topright">×</span>
			<h2><%[ Resource usage ]%> - <span id="<%=$this->ClientID%>_in_use_by_modal_title"></span></h2>
		</header>
		<div class="w3-container w3-padding w3-container">
			<table id="<%=$this->ClientID%>_in_use_by_table" class="w3-table w3-striped w3-margin-bottom dataTable dtr-column" style="width: 100%">
				<thead>
					<tr class="row">
						<th></th>
						<th class="w3-center"><%[ Component type ]%></th>
						<th class="w3-center"><%[ Resource ]%></th>
						<th class="w3-center"><%[ Resource name ]%></th>
						<th class="w3-center"><%[ Directive ]%></th>
					</tr>
				</thead>
				<tbody id="<%=$this->ClientID%>_in_use_by_table_body"></tbody>
				<tfoot>
					<tr class="row">
						<th></th>
						<th class="w3-center"><%[ Component type ]%></th>
						<th class="w3-center"><%[ Resource ]%></th>
						<th class="w3-center"><%[ Resource name ]%></th>
						<th class="w3-center"><%[ Directive ]%></th>
					</tr>
				</tfoot>
			</table>
		</div>
		<footer class="w3-container w3-center">
			<button type="button" class="w3-button w3-green w3-section" onclick="oBaculaConfigResourceDeps<%=$this->ClientID%>.show_window(false);"><i class="fas fa-times"></i> &nbsp;<%[ Close ]%></button>
		</footer>
	</div>
</div>
<com:TCallback ID="ConfigResourceDeps" OnCallback="loadResourceDeps" />
<script>
var oBaculaConfigResourceList<%=$this->ClientID%> = {
	ids: {
		list: '<%=$this->ClientID%>_list',
		list_body: '<%=$this->ClientID%>_list_body'
	},
	data: [],
	table: null,
	table_toolbar: null,
	actions: [
		{
			action: 'apply_configs',
			enabled: <%=$this->User->isInRole(WebUserRoles::ADMIN) ? 'true' : 'false'%>,
			label: '<%[ Apply configs ]%>',
			value: 'Name',
			before: function() {
				const cb = () => {
					let selected = [];
					let sel_data = oBaculaConfigResourceList<%=$this->ClientID%>.table.rows({selected: true}).data();
					sel_data.each(function(v, k) {
						selected.push(v.Name);
					});
					return selected;
				};
				oBulkApplyConfigsModal.set_item_cb(cb);
				oBulkApplyConfigsModal.show_window(true);
			}
		}
	],
	init: function(data) {
		var self = oBaculaConfigResourceList<%=$this->ClientID%>;
		self.data = data;
		if (self.table) {
			var page = self.table.page();
			self.table.clear().rows.add(self.data).draw();
			self.table.page(page).draw(false);
			self.table_toolbar.style.display = 'none';
		} else {
			self.set_table();
			self.set_bulk_actions();
			self.set_events();
		}
	},
	set_events: function() {
		document.getElementById(this.ids.list).addEventListener('click', function(e) {
			$(function() {
				this.table_toolbar.style.display = this.table.rows({selected: true}).data().length > 0 ? '' : 'none';
			}.bind(this));
		}.bind(this));
	},
	set_table: function() {
		this.table = $('#' + this.ids.list).DataTable({
			data: this.data,
			deferRender: true,
			dom: 'lB<"table_toolbar">frtip',
			stateSave: true,
			stateDuration: KEEP_TABLE_SETTINGS,
			buttons: [
				'copy', 'csv', 'colvis'
			],
			columns: [
						{
							className: 'details-control',
							orderable: false,
							data: null,
							defaultContent: '<button type="button" class="w3-button w3-blue"><i class="fa fa-angle-down"></i></button>'
						}
						<com:Bacularis.Common.Portlets.BSimpleRepeater ID="ResourceListColumnsRepeater">
							<prop:ItemTemplate>
								,{data: '<%=$this->Data['name']%>'}
							</prop:ItemTemplate>
						</com:Bacularis.Common.Portlets.BSimpleRepeater>
						,{
							data: 'Name',
							render: function (data, type, row) {
								const restype = document.getElementById('<%=$this->ResourceTypeAddLink->ClientID%>').textContent;
								const resname = data;
								const icon = document.createElement('I');
								icon.classList.add('fa-solid', 'fa-arrows-turn-to-dots', 'fa-fw', 'pointer');
								icon.setAttribute('onclick', 'oBaculaConfigResourceDeps<%=$this->ClientID%>.load_deps("' + restype + '", "' + resname + '");');
								return icon.outerHTML;
							},
							width: '100px'
						}
						,{
							data: 'Name',
							render: function (data, type, row) {
								var span = document.createElement('SPAN');
								span.className = 'w3-right';

								var edit_btn = document.createElement('BUTTON');
								edit_btn.className = 'w3-button w3-green w3-margin-right';
								edit_btn.type = 'button';
								var i = document.createElement('I');
								i.className = 'fa fa-edit';
								var label = document.createTextNode(' <%[ Edit ]%>');
								edit_btn.appendChild(i);
								edit_btn.innerHTML += '&nbsp';
								edit_btn.appendChild(label);
								edit_btn.setAttribute('onclick', 'oBaculaConfigResourceWindow<%=$this->ClientID%>.load_resource_window("' + data + '");');

								var del_btn = document.createElement('BUTTON');
								del_btn.className = 'w3-button w3-red';
								del_btn.type = 'button';
								var i = document.createElement('I');
								i.className = 'fa fa-trash-alt';
								var label = document.createTextNode(' <%[ Delete ]%>');
								del_btn.appendChild(i);
								del_btn.innerHTML += '&nbsp';
								del_btn.appendChild(label);
								del_btn.setAttribute('onclick', 'oBaculaConfigResourceWindow<%=$this->ClientID%>.remove_resource("' + data + '")');

								span.appendChild(edit_btn);
								span.appendChild(del_btn);
								return span.outerHTML;
							},
							width: '200px'
						}
			],
			responsive: {
				details: {
					type: 'column'
				}
			},
			columnDefs: [{
				className: 'control',
				orderable: false,
				targets: 0
			},
			{
				className: "dt-center",
				targets: [ 3, 4 ]
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
			actions: '<%[ Actions ]%>',
			ok: '<%[ OK ]%>'
		});
	}
};
//oBaculaConfigResourceList<%=$this->ClientID%>.init();
var oBaculaConfigResourceDeps<%=$this->ClientID%> = {
	ids: {
		list: '<%=$this->ClientID%>_in_use_by_table',
		list_body: '<%=$this->ClientID%>_in_use_by_table_body',
		win: '<%=$this->ClientID%>_in_use_by_modal',
		title: '<%=$this->ClientID%>_in_use_by_modal_title'
	},
	data: [],
	table: null,
	load_deps: function(restype, resname) {
		const cb = <%=$this->ConfigResourceDeps->ActiveControl->Javascript%>;
		cb.setCallbackParameter(
			[restype, resname]
		);
		cb.dispatch();
		const title = restype + ' "' + resname + '"';
		this.set_title(title);
	},
	update: function(data) {
		const self = oBaculaConfigResourceDeps<%=$this->ClientID%>;
		self.data = data;
		if (self.table) {
			var page = self.table.page();
			self.table.clear().rows.add(self.data).draw();
			self.table.page(page).draw(false);
		} else {
			self.set_table();
		}
		self.show_window(true);
		self.table.columns.adjust();
	},
	set_table: function() {
		this.table = $('#' + this.ids.list).DataTable({
			data: this.data,
			deferRender: true,
			dom: 'lBfrtip',
			stateSave: true,
			stateDuration: KEEP_TABLE_SETTINGS,
			buttons: [
				'copy', 'csv', 'colvis'
			],
			columns: [
				{
					className: 'details-control',
					orderable: false,
					data: null,
					defaultContent: '<button type="button" class="w3-button w3-blue"><i class="fa fa-angle-down"></i></button>'
				},
				{
					data: 'component_type'
				},
				{
					data: 'resource_type'
				},
				{
					data: 'resource_name'
				},
				{
					data: 'directive_name'
				}
			],
			responsive: {
				details: {
					type: 'column'
				}
			},
			columnDefs: [{
				className: 'control',
				orderable: false,
				targets: 0
			},
			{
				className: 'dt-center',
				targets: [ 1, 2, 4 ]
			}],
			order: [1, 'asc']
		});
	},
	show_window: function(show) {
		const win = document.getElementById(this.ids.win);
		win.style.display = show ? 'block' : 'none';
	},
	set_title: function(text) {
		const title = document.getElementById(this.ids.title);
		title.textContent = text;
	}
};
</script>
<com:Bacularis.Web.Portlets.BulkApplyConfigsModal
	ID="BulkApplyConfigsJob"
/>
<com:TCallback ID="RemoveResource" OnCallback="removeResource" />
<div id="resource_window<%=$this->ClientID%>" class="w3-modal">
	<div class="w3-modal-content w3-animate-top w3-card-4">
		<header class="w3-container w3-green">
			<span onclick="oBaculaConfigResourceWindow<%=$this->ClientID%>.close_resource_window();" class="w3-button w3-display-topright">&times;</span>
			<h2 id="resource_window_title_add<%=$this->ClientID%>" style="display: none"><%[ Add ]%> <com:TActiveLabel ID="ResourceTypeAddWindowTitle" /></h2>
			<h2 id="resource_window_title_edit<%=$this->ClientID%>" style="display: none"><%[ Edit ]%> <com:TActiveLabel ID="ResourceTypeEditWindowTitle" /></h2>
		</header>
		<div id="resource_window_copy_resource<%=$this->ClientID%>"class="w3-container w3-margin-left w3-margin-right w3-right" style="display: none">
			<span style="vertical-align: super"><%[ Copy configuration from: ]%></span>
			<div class="directive_field w3-show-inline-block w3-margin-bottom" style="vertical-align: middle">
				<com:TActiveDropDownList
					ID="ResourcesToCopy"
					CssClass="w3-select w3-border w3-show-inline-block"
					Style="min-width: 300px"
					OnSelectedIndexChanged="copyConfig"
				/>
			</div>
		</div>
		<div class="w3-container w3-margin-left w3-margin-right w3-margin-top">
			<com:Bacularis.Web.Portlets.BaculaConfigDirectives
				ID="ResourceConfig"
				ShowRemoveButton="false"
				ShowCancelButton="false"
				ShowBottomButtons="false"
				ShowSectionTabs="true"
				SaveDirectiveActionOk="oBaculaConfigResourceWindow<%=$this->ClientID%>.close_resource_window();"
				OnSave="loadResourceListTable"
				OnRename="renameResource"
			/>
		</div>
	</div>
	<com:TActiveHiddenField ID="ResourceWindowType" />
</div>
<div id="resource_error_window<%=$this->ClientID%>" class="w3-modal">
	<div class="w3-modal-content w3-animate-top w3-card-4">
		<header class="w3-container w3-red">
			<span onclick="document.getElementById('resource_error_window<%=$this->ClientID%>').style.display = 'none';" class="w3-button w3-display-topright">&times;</span>
			<h2><%[ Error ]%></h2>
		</header>
		<div class="w3-container w3-margin-left w3-margin">
			<com:TActiveLabel ID="RemoveResourceError" />
		</div>
		<footer class="w3-container w3-center">
			<button type="button" class="w3-button w3-green w3-margin-bottom" onclick="document.getElementById('resource_error_window<%=$this->ClientID%>').style.display = 'none';"><i class="fas fa-times"></i> &nbsp;<%[ Close ]%></button>
		</footer>
	</div>
</div>
<com:TCallback ID="LoadResource" OnCallback="TemplateControl.loadResourceWindow" />
<com:TCallback ID="UnloadResource" OnCallback="TemplateControl.unloadResourceWindow" />
<script>
oBaculaConfigResourceWindow<%=$this->ClientID%> = {
	load_resource_window: function(name) {
		var title_add = document.getElementById('resource_window_title_add<%=$this->ClientID%>');
		var title_edit = document.getElementById('resource_window_title_edit<%=$this->ClientID%>');
		var cb = <%=$this->LoadResource->ActiveControl->Javascript%>;
		cb.setCallbackParameter([null, name]);
		cb.dispatch();
		if (name) {
			title_add.style.display = 'none';
			title_edit.style.display = 'inline-block';
		} else {
			title_edit.style.display = 'none';
			title_add.style.display = 'inline-block';
		}
		document.getElementById('resource_window<%=$this->ClientID%>').style.display = 'block';
	},
	unload_resource_window: function() {
		var cb = <%=$this->UnloadResource->ActiveControl->Javascript%>;
		cb.dispatch();
	},
	close_resource_window: function() {
		document.getElementById('resource_window<%=$this->ClientID%>').style.display = 'none';
		this.unload_resource_window();
	},
	remove_resource: function(name) {
		var cb = <%=$this->RemoveResource->ActiveControl->Javascript%>;
		cb.setCallbackParameter(name);
		cb.dispatch();
	}
};
</script>
