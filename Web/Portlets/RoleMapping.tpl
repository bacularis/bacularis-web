<div>
	<p class="w3-hide-small">
		<%[ Role mappings enable the use of roles provided by the Identity and Access Management (IAM) system in Bacularis. This is achieved by creating mappings that map IAM roles to Bacularis roles. This allows the administrator to manage Bacularis roles from within the IAM system. ]%>
	</p>
	<div class="w3-panel">
		<button type="button" id="add_role_mapping_btn" class="w3-button w3-green" onclick="oRoleMapping.load_role_mapping_window()"><i class="fa fa-plus"></i> &nbsp;<%[ Add new role mapping ]%></a>
	</div>
	<!-- Tag tools -->
	<com:Bacularis.Web.Portlets.TagTools ID="TagToolsRoleMappingList" ViewName="role_mapping_list" />
	<table id="role_mapping_list_table" class="display w3-table w3-striped w3-hoverable w3-margin-bottom selectable" style="width: 100%">
		<thead>
			<tr>
				<th></th>
				<th><%[ Mapping name ]%></th>
				<th class="w3-center"><%[ Description ]%></th>
				<th class="w3-center"># <%[ Mappings ]%></th>
				<th class="w3-center"><%[ Enabled ]%></th>
				<th class="w3-center"><%[ In use by ]%></th>
				<th class="w3-center"><%[ Tag ]%></th>
				<th class="w3-center"><%[ Action ]%></th>
			</tr>
		</thead>
		<tbody id="role_mapping_list_body"></tbody>
		<tfoot>
			<tr>
				<th></th>
				<th><%[ Mapping name ]%></th>
				<th class="w3-center"><%[ Description ]%></th>
				<th class="w3-center"># <%[ Mappings ]%></th>
				<th class="w3-center"><%[ Enabled ]%></th>
				<th class="w3-center"><%[ In use by ]%></th>
				<th class="w3-center"><%[ Tag ]%></th>
				<th class="w3-center"><%[ Action ]%></th>
			</tr>
		</tfoot>
	</table>
	<p class="info w3-hide-medium w3-hide-small"><%[ Tip: Use left-click to select table row. Use CTRL + left-click to multiple row selection. Use SHIFT + left-click to add a range of rows to selection. ]%></p>
<com:TCallback ID="RoleMappingList" OnCallback="TemplateControl.setRoleMappingList" />
<com:TCallback ID="LoadRoleMapping" OnCallback="TemplateControl.loadRoleMappingWindow" />
<script>
var oRoleMappingList = {
	ids: {
		role_mapping_list: 'role_mapping_list_table',
		role_mapping_list_body: 'role_mapping_list_body'
	},
	actions: [
		{
			action: 'remove',
			label: '<%[ Remove ]%>',
			value: ['name', 'deps'],
			callback: <%=$this->RemoveRoleMappingsAction->ActiveControl->Javascript%>
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
			this.table.clear().rows.add(oRoleMappingList.data).draw();
			this.table.page(page).draw(false);
			this.set_filters(this.table);
			this.table_toolbar.style.display = 'none';
		}
	},
	set_events: function() {
		document.getElementById(this.ids.role_mapping_list).addEventListener('click', function(e) {
			$(function() {
				const wa = (this.table.rows({selected: true}).data().length > 0) ? 'show' : 'hide';
				$(this.table_toolbar).animate({
					width: wa
				}, 'fast');
			}.bind(this));
		}.bind(this));
	},
	set_table: function() {
		this.table = $('#' + this.ids.role_mapping_list).DataTable({
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
					data: 'description',
					render: function(data, type, row) {
						let ret = data;
						if (type == 'display') {
							var span = document.createElement('SPAN');
							span.title = data;
							if (data.length > 40) {
								span.textContent = data.substring(0, 40) + '...';
							} else {
								span.textContent = data;
							}
							ret = span.outerHTML;
						}
						return ret;
					}
				},
				{
					data: 'roles',
					render: function(data, type, row) {
						return data ? Object.keys(data).length : '0';
					}
				},
				{
					data: 'enabled',
					render: function(data, type, row) {
						let ret;
						if (type == 'display') {
							ret = '';
							if (data == 1) {
								const check = document.createElement('I');
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
					data: 'deps',
					render: function(data, type, row) {
						if (Array.isArray(data) && data.length == 0) {
							data = ['-'];
						}
						return render_string_short(data, type, row);
					}
				},
				{
					data: 'name',
					render: (data, type, row) => {
						const id = 'role';
						const tt_obj = oTagTools_<%=$this->TagToolsRoleMappingList->ClientID%>;
						const table = 'oRoleMappingList.table';
						return render_tags(type, id, data, tt_obj, table);
					}
				},
				{
					data: 'name',
					render: function (data, type, row) {
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
						btn_edit.setAttribute('onclick', 'oRoleMapping.load_role_mapping_window(\'' + data + '\')');
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
				targets: [ 7 ]
			},
			{
				className: "dt-center",
				targets: [ 2, 3, 4, 5, 6, 7 ]
			}],
			select: {
				style:    'os',
				selector: 'td:not(:last-child):not(:first-child):not(:nth-last-child(2))',
				blurable: false
			},
			order: [1, 'asc'],
			initComplete: function () {
				oRoleMappingList.set_filters(this.api());
			}
		});
	},
	set_filters: function(api) {
		api.columns([7]).every(function () {
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
			if (column[0][0] == 7) { // Enabled column
				column.data().unique().sort().each(function (d, j) {
					let ds = '';
					if (d === '1') {
						ds = '<%[ Enabled ]%>';
					} else if (d === '0') {
						ds = '<%[ Disabled ]%>';
					}
					if (column.search() == '^' + dtEscapeRegex(d) + '$') {
						select.append('<option value="' + d + '" title="' + ds + '" selected>' + ds + '</option>');
					} else if (ds) {
						select.append('<option value="' + d + '" title="' + ds + '">' + ds + '</option>');
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

var oRoleMapping = {
	ids: {
		win: 'role_mapping_window',
		title_add: 'role_mapping_window_title_add',
		title_edit: 'role_mapping_window_title_edit',
		name_field: '<%=$this->RoleMappingName->ClientID%>',
		name_req: 'role_mapping_window_required',
		win_type: '<%=$this->RoleMappingWindowType->ClientID%>',
		group_name: 'role_mapping_new_group_name',
		role_list: '<%=$this->RoleMappingRoles->ClientID%>',
		mapping_list: 'role_mapping_mapping_list',
		new_mapping: 'role_mapping_new_mapping',
		new_mapping_body: 'role_mapping_new_mapping_body',
		new_mapping_btn: 'role_mapping_new_mapping_btn',
		add_btn: 'role_mapping_add_btn',
		edit_btn: 'role_mapping_edit_btn',
		values: '<%=$this->RoleMappingValues->ClientID%>'
	},
	mappings: {},
	actions: {
		add: 'add',
		edit: 'edit'
	},
	load_role_mapping_window: function(role_mapping) {
		this.clear_role_mapping_window();
		const title_add = document.getElementById(this.ids.title_add);
		const title_edit = document.getElementById(this.ids.title_edit);
		const role_mapping_field_name = document.getElementById(this.ids.name_field);
		const role_mapping_field_req = document.getElementById(this.ids.name_req);
		const role_mapping_win_type = document.getElementById(this.ids.win_type);
		const cb = <%=$this->LoadRoleMapping->ActiveControl->Javascript%>;
		cb.setCallbackParameter(role_mapping);
		cb.dispatch();
		if (role_mapping) {
			title_add.style.display = 'none';
			title_edit.style.display = 'inline-block';
			role_mapping_field_name.setAttribute('readonly', '');
			role_mapping_field_req.style.display = 'none';
			role_mapping_win_type.value = 'edit';
		} else {
			title_add.style.display = 'inline-block';
			title_edit.style.display = 'none';
			this.clear_role_mapping_window();
			if (role_mapping_field_name.hasAttribute('readonly')) {
				role_mapping_field_name.removeAttribute('readonly');
			}
			role_mapping_field_req.style.display = 'inline';
			role_mapping_win_type.value = 'add';
		}
		document.getElementById('role_mapping_window_role_mapping_exists').style.display = 'none';
		document.getElementById('role_mapping_window').style.display = 'block';
		if (!role_mapping) {
			role_mapping_field_name.focus();
		}
	},
	load_role_mapping_window_cb: function(mappings) {
		const self = oRoleMapping;
		self.mappings = {};
		for (const group in mappings) {
			self.mappings[group] = new RoleMappingItem(group, mappings[group]);
		}
		self.draw_mapping();
	},
	clear_role_mapping_window: function() {
		[
			'<%=$this->RoleMappingName->ClientID%>',
			'<%=$this->RoleMappingDescription->ClientID%>'
		].forEach(function(id) {
			document.getElementById(id).value = '';
		});
		const enabled = document.getElementById('<%=$this->RoleMappingEnabled->ClientID%>');
		enabled.checked = true;
		this.clear_role_mapping();
	},
	load_role_mapping_list: function() {
		const cb = <%=$this->RoleMappingList->ActiveControl->Javascript%>;
		cb.dispatch();
	},
	load_role_mapping_list_cb: function(list) {
		oRoleMappingList.data = list;
		oRoleMappingList.init();
	},
	add_role_mapping: function() {
		const btn = document.getElementById(this.ids.new_mapping_btn);
		btn.style.display = 'none';
		const group_name = document.getElementById(this.ids.group_name);
		group_name.removeAttribute('readonly');
		this.set_mapping_box();
		this.show_new_mapping(true);
	},
	check_validity: function() {
		let valid = false;
		const group_name = document.getElementById(this.ids.group_name);
		const roles = document.getElementById(this.ids.role_list);
		valid = group_name.reportValidity() && roles.reportValidity();
		return valid;
	},
	show_new_mapping: function(show) {
		const new_mapping = document.getElementById(this.ids.new_mapping);
		new_mapping.style.display = show ? 'block' : 'none';
	},
	is_role_mapping_opened: function() {
		const new_mapping_body = $('#' + this.ids.new_mapping_body);
		return new_mapping_body.is(':visible');
	},
	get_current_mapping: function() {
		const group = $('#' + this.ids.new_mapping_body).parents('li[data-group]');
		return group.length == 1 ? group.attr('data-group') : null;
	},
	cancel_role_mapping: function() {
		this.clear_role_mapping();
		const btn = document.getElementById(this.ids.new_mapping_btn);
		btn.style.display = 'block';
		const new_mapping_body = $('#' + this.ids.new_mapping_body);
		new_mapping_body.slideUp('fast', () => {
			this.show_new_mapping(false);
		});
	},
	save_role_mapping: function() {
		if (!this.check_validity()) {
			return;
		}
		const group = document.getElementById(this.ids.group_name).value;
		const roles = $('#' + this.ids.role_list).val();
		const mapping = new RoleMappingItem(group, roles);
		this.mappings[group] = mapping;
		this.draw_mapping();
		this.cancel_role_mapping();
	},
	save_role_mapping_cb: function() {
		const win = document.getElementById(oRoleMapping.ids.win);
		win.style.display = 'none';
	},
	save_all_mappings: function() {
		const mappings = {};
		for (const group in this.mappings) {
			mappings[group] = this.mappings[group].get_roles();
		}
		const vals = document.getElementById(this.ids.values);
		vals.value = JSON.stringify(mappings);
	},
	draw_mapping: function() {
		this.clear_mapping();
		const container = document.getElementById(this.ids.mapping_list);
		for (const group in this.mappings) {
			const row = this.mappings[group].create_item();
			container.appendChild(row);
		}
	},
	clear_mapping: function() {
		this.set_mapping_box();
		const container = document.getElementById(this.ids.mapping_list);
		while (container.firstChild) {
			container.removeChild(container.firstChild);
		}
	},
	clear_role_mapping: function() {
		[
			this.ids.group_name,
			this.ids.role_list
		].forEach((id) => {
			const el = document.getElementById(id);
			el.value = '';
		});
	},
	set_mapping_box: function(name) {
		if (name) {
			this.show_new_mapping(false);
			this.cancel_role_mapping();
			if (this.is_role_mapping_opened() && this.get_current_mapping() == name) {
				// the same group, just close it and end
				return;
			}
		}
		this.clear_role_mapping();
		let dest;
		if (this.mappings.hasOwnProperty(name)) {
			// Edit mapping
			this.show_new_mapping(false);
			dest = this.mappings[name].get_container();

			// Set group name
			const group_name = document.getElementById(this.ids.group_name);
			group_name.setAttribute('readonly', 'readonly');
			group_name.value = name;
			// Set role vals
			const roles = this.mappings[name].get_roles();
			$('#' + this.ids.role_list).val(roles);

			this.show_action_btn(this.actions.edit);
		} else {
			// New mapping
			dest = document.getElementById(this.ids.new_mapping);
			this.show_action_btn(this.actions.add);
		}
		const box = document.getElementById(this.ids.new_mapping_body);
		box.style.display = 'none';
		dest.appendChild(box);
		$(box).slideDown('fast');
	},
	show_action_btn: function(action) {
		const add_btn = document.getElementById(this.ids.add_btn);
		const edit_btn = document.getElementById(this.ids.edit_btn);
		if (action == this.actions.add) {
			add_btn.style.display = 'inline-block';
			edit_btn.style.display = 'none';
		} else if (action == this.actions.edit) {
			edit_btn.style.display = 'inline-block';
			add_btn.style.display = 'none';
		}
	},
	delete_mapping: function(name) {
		if (this.mappings.hasOwnProperty(name)) {
			delete(this.mappings[name]);
		}
		this.draw_mapping();
	}
};
class RoleMappingItem {
	constructor(name, roles) {
		this.set_name(name);
		this.set_roles(roles);
		this.container = null;
	}
	set_name(name) {
		this.name = name || '';
	}
	set_roles(roles) {
		this.roles = roles || [];
	}
	get_roles() {
		return this.roles
	}
	get_container() {
		return this.container;
	}
	create_item() {
		this.container = document.createElement('LI');
		this.container.classList.add('w3-row');
		this.container.setAttribute('data-group', this.name);
		this.create_btns();
		this.create_image();
		this.create_name();
		this.create_roles();
		return this.container;
	}
	create_image() {
		const img = document.createElement('I');
		img.classList.add('fa-solid', 'fa-arrows-left-right-to-line', 'fa-3x', 'w3-left', 'w3-margin-top', 'w3-margin-bottom');
		img.style.marginRight = '16px';
		img.style.marginLeft = '6px';
		this.container.appendChild(img);
	}
	create_name() {
		const lfield = document.createElement('DIV');
		lfield.classList.add('w3-col', 'w3-twothird');
		lfield.style.marginTop = '12px';
		const head = document.createElement('H4');
		head.style.margin = '0';
		head.textContent = this.name;
		lfield.appendChild(head);
		this.container.appendChild(lfield);
	}
	create_roles() {
		const cfield = document.createElement('DIV');
		cfield.classList.add('w3-col', 'w3-twothird');
		cfield.style.marginTop = '1px';
		const head = document.createElement('STRONG');
		head.textContent = '<%[ Roles ]%>: ';
		const val = document.createElement('SPAN');
		val.textContent = this.roles.join(', ');
		cfield.appendChild(head);
		cfield.appendChild(val);
		this.container.appendChild(cfield);
	}
	create_btns() {
		const rfield = document.createElement('DIV');
		rfield.classList.add('w3-col', 'w3-right');
		rfield.style.width = '117px';
		rfield.style.marginTop = '4px';
		const edit_btn = this.get_edit_btn();
		const del_btn = this.get_del_btn();
		rfield.appendChild(edit_btn);
		rfield.appendChild(del_btn);
		this.container.appendChild(rfield);
	}
	get_edit_btn() {
		const btn = document.createElement('BUTTON');
		btn.type = 'button';
		btn.classList.add('w3-button', 'w3-green', 'w3-margin-top', 'w3-margin-bottom', 'w3-margin-right');
		const icon = document.createElement('I');
		icon.classList.add('fa-solid', 'fa-edit');
		btn.appendChild(icon);
		btn.addEventListener('click', (e) => {
			oRoleMapping.set_mapping_box(this.name);
		});
		return btn;
	}
	get_del_btn() {
		const btn = document.createElement('BUTTON');
		btn.type = 'button';
		btn.classList.add('w3-button', 'w3-red', 'w3-margin-top', 'w3-margin-bottom');
		btn.classList.marginLeft = '0';
		const icon = document.createElement('I');
		icon.classList.add('fa-solid', 'fa-trash-alt');
		btn.appendChild(icon);
		btn.addEventListener('click', (e) => {
			oRoleMapping.delete_mapping(this.name);
		});
		return btn;
	}
}

$(function() {
	oRoleMapping.load_role_mapping_list();
});
</script>
	<div id="role_mapping_window" class="w3-modal">
		<div class="w3-modal-content w3-animate-top w3-card-4">
			<header class="w3-container w3-green">
				<span onclick="document.getElementById('role_mapping_window').style.display = 'none';" class="w3-button w3-display-topright">&times;</span>
				<h2 id="role_mapping_window_title_add" style="display: none"><%[ Add role mapping ]%></h2>
				<h2 id="role_mapping_window_title_edit" style="display: none"><%[ Edit role mapping ]%></h2>
			</header>
			<div class="w3-container w3-margin-left w3-margin-right">
				<com:TValidationSummary
					CssClass="field_invalid-summary"
					ValidationGroup="RoleMappingGroup"
					AutoUpdate="true"
					Display="Dynamic"
					/>
				<span id="role_mapping_window_role_mapping_exists" class="error" style="display: none"><ul><li><%[ Role mapping with the given name already exists. ]%></li></ul></span>
				<h3><%[ General options ]%></h3>
				<div class="w3-row directive_field">
					<div class="w3-col w3-third"><com:TLabel ForControl="RoleMappingName" Text="<%[ Mapping name: ]%>"/></div>
					<div class="w3-half">
						<com:TActiveTextBox
							ID="RoleMappingName"
							AutoPostBack="false"
							MaxLength="100"
							CssClass="w3-input w3-border"
						/>
						<com:TRequiredFieldValidator
							ValidationGroup="RoleMappingGroup"
							ControlToValidate="RoleMappingName"
							ErrorMessage="<%[ Role field cannot be empty. ]%>"
							ControlCssClass="field_invalid"
							Display="None"
						/>
						<com:TRegularExpressionValidator
							ValidationGroup="RoleMappingGroup"
							RegularExpression="<%=WebRoleMappingConfig::MAPPING_ID_PATTERN%>"
							ControlToValidate="RoleMappingName"
							ErrorMessage="<%[ Invalid role value. ]%>"
							ControlCssClass="field_invalid"
							Display="None"
						/>
					</div> &nbsp;<i id="role_mapping_window_required" class="fa fa-asterisk w3-text-red opt_req" style="display none"></i>
				</div>
				<div class="w3-row directive_field">
					<div class="w3-col w3-third"><com:TLabel ForControl="RoleMappingDescription" Text="<%[ Description: ]%>"/></div>
					<div class="w3-half">
						<com:TActiveTextBox
							ID="RoleMappingDescription"
							TextMode="MultiLine"
							Rows="3"
							AutoPostBack="false"
							MaxLength="500"
							CssClass="w3-input w3-border"
						/>
					</div>
				</div>
				<div class="w3-margin-top w3-margin-bottom">
					<h3><%[ Mappings ]%></h3>
					<ul id="role_mapping_mapping_list" class="w3-ul w3-card">
					</ul>
				</div>
				<div id="role_mapping_new_mapping_btn" class="w3-margin-top w3-margin-bottom w3-center">
					<button type="button" class="w3-button w3-green" onclick="oRoleMapping.add_role_mapping();"><%[ Add new mapping ]%></button>
				</div>
				<div id="role_mapping_new_mapping" class="w3-card w3-padding w3-margin-top w3-margin-bottom" style="display: none;">
					<h3><%[ New mapping ]%></h3>
					<div id="role_mapping_new_mapping_body" style="clear: both; display: none;">
						<div class="w3-row directive_field">
							<div class="w3-col w3-third"><label for="<%=$this->RoleMappingRoles->ClientID%>"><%[ Role name: ]%></label></div>
							<div class="w3-half">
								<input type="text" id="role_mapping_new_group_name" class="w3-input w3-border" required />
							</div> &nbsp;<i id="role_mapping_group_name_required" class="fa fa-asterisk w3-text-red opt_req"></i>
						</div>
						<div class="w3-row directive_field">
							<div class="w3-col w3-third"><label for="<%=$this->RoleMappingRoles->ClientID%>"><%[ Mapped roles: ]%></label></div>
							<div class="w3-half">
								<com:TActiveListBox
									ID="RoleMappingRoles"
									SelectionMode="Multiple"
									Rows="10"
									CssClass="w3-select w3-border"
									AutoPostBack="false"
									Attributes.required="required"
								/>
								<p style="margin: 0 16px 0 0"><%[ Use CTRL + left-click to multiple item selection ]%></p>
							</div> &nbsp;<i id="role_mapping_role_list_required" class="fa fa-asterisk w3-text-red opt_req"></i>

						</div>
						<div class="w3-center w3-margin-bottom">
							<button type="button" class="w3-button w3-red" onclick="oRoleMapping.cancel_role_mapping();"><i class="fa-solid fa-times"></i> <%[ Cancel ]%></button>
							<button type="button" id="role_mapping_add_btn" class="w3-button w3-green" onclick="oRoleMapping.save_role_mapping();" style="display: none;"><i class="fa-solid fa-plus"></i> &nbsp;<%[ Add mapping ]%></button>
							<button type="button" id="role_mapping_edit_btn" class="w3-button w3-green" onclick="oRoleMapping.save_role_mapping();" style="display: none;"><i class="fa-solid fa-save"></i> &nbsp;<%[ Save ]%></button>
						</div>
					</div>
				</div>
				<div class="w3-row directive_field">
					<div class="w3-col w3-third"><com:TLabel ForControl="RoleMappingEnabled" Text="<%[ Enabled: ]%>"/></div>
					<div class="w3-half">
						<com:TActiveCheckBox
							ID="RoleMappingEnabled"
							CssClass="w3-check"
							AutoPostBack="false"
						/>
					</div>
				</div>
			</div>
			<footer class="w3-container w3-center w3-padding">
				<button type="button" class="w3-button w3-red" onclick="document.getElementById('role_mapping_window').style.display = 'none';"><i class="fas fa-times"></i> &nbsp;<%[ Cancel ]%></button>
				<com:TActiveLinkButton
					ID="RoleMappingSave"
					ValidationGroup="RoleMappingGroup"
					CausesValidation="true"
					OnCallback="saveRoleMapping"
					CssClass="w3-button w3-section w3-green"
					Attributes.onclick="oRoleMapping.save_all_mappings();"
				>
					<i class="fa fa-save"></i> &nbsp;<%[ Save ]%>
				</com:TActiveLinkButton>
			</footer>
			<com:TActiveHiddenField ID="RoleMappingValues" />
		</div>
		<com:TActiveHiddenField ID="RoleMappingWindowType" />
	</div>
	<com:TCallback ID="RemoveRoleMappingsAction" OnCallback="TemplateControl.removeRoleMappings" />
</div>
<div id="role_mapping_action_rm_warning_window" class="w3-modal">
	<div class="w3-modal-content w3-animate-top w3-card-4">
		<header class="w3-container w3-orange">
			<span onclick="document.getElementById('role_mapping_action_rm_warning_window').style.display = 'none';" class="w3-button w3-display-topright">&times;</span>
			<h2 id="role_mapping_action_rm_warning_window_title_add"><%[ Warning ]%></h2>
		</header>
		<div class="w3-container w3-margin-left w3-margin-right w3-margin-top">
			<p><%[ The following role mappings cannot be removed because they are used by identity providers. Please unassign role mappings from the identity providers and try again. ]%></p>
			<com:TActiveRepeater
				ID="RoleMappingFbd"
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
			<button type="button" class="w3-button w3-green w3-margin-bottom" onclick="document.getElementById('role_mapping_action_rm_warning_window').style.display = 'none';"><i class="fas fa-times"></i> &nbsp;<%[ Cancel ]%></button>
		</footer>
	</div>
</div>
