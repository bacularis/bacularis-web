<div id="plugin_list" class="w3-container w3-padding">
	<div class="w3-row w3-margin-bottom">
		<a href="javascript:void(0)" onclick="W3SubTabs.open('plugin_list_subtab_settings', 'plugin_settings_list', 'plugin_list'); oPluginListSettings.table.responsive.recalc();">
			<div id="plugin_list_subtab_settings" class="subtab_btn w3-third w3-bottombar w3-hover-light-grey w3-padding w3-border-red"><%[ Plugin settings ]%></div>
		</a>
		<a href="javascript:void(0)" onclick="W3SubTabs.open('plugin_list_subtab_plugins', 'plugin_plugins_list', 'plugin_list'); oPluginListPlugins.table.responsive.recalc();">
			<div id="plugin_list_subtab_plugins" class="subtab_btn w3-third w3-bottombar w3-hover-light-grey w3-padding"><%[ Installed plugins ]%></div>
		</a>
	</div>
	<div id="plugin_settings_list" class="subtab_item">
		<div class="w3-row w3-margin-bottom">
			<button id="plugin_list_add_plugin_settings" class="w3-button w3-green" onclick="oPlugins.load_plugin_settings_window(); return false;">
				<i class="fa-solid fa-plus"></i> &nbsp;<%[ Add plugin settings ]%>
			</button>
		</div>
		<div>
			<table id="plugin_list_settings_list" class="w3-table w3-striped w3-hoverable w3-margin-bottom" style="width: 100%">
				<thead>
					<tr>
						<th></th>
						<th><%[ Name ]%></th>
						<th><%[ Plugin name ]%></th>
						<th><%[ Enabled ]%></th>
						<th><%[ Actions ]%></th>
					</tr>
				</thead>
				<tbody id="plugin_list_settings_list_body"></tbody>
				<tfoot>
					<tr>
						<th></th>
						<th><%[ Name ]%></th>
						<th><%[ Plugin name ]%></th>
						<th><%[ Enabled ]%></th>
						<th><%[ Actions ]%></th>
					</tr>
				</tfoot>
			</table>
			<p class="info w3-hide-medium w3-hide-small"><%[ Tip: Use left-click to select table row. Use CTRL + left-click to multiple row selection. Use SHIFT + left-click to add a range of rows to selection. ]%></p>
		</div>
	</div>
	<div id="plugin_plugins_list" class="subtab_item" style="display: none">
		<div>
			<table id="plugin_list_plugins_list" class="w3-table w3-striped w3-hoverable w3-margin-bottom" style="width: 100%">
				<thead>
					<tr>
						<th></th>
						<th><%[ Name ]%></th>
						<th><%[ Type ]%></th>
						<th><%[ Version ]%></th>
					</tr>
				</thead>
				<tbody id="plugin_list_plugins_list_body"></tbody>
				<tfoot>
					<tr>
						<th></th>
						<th><%[ Name ]%></th>
						<th><%[ Type ]%></th>
						<th><%[ Version ]%></th>
					</tr>
				</tfoot>
			</table>
		</div>
	</div>
</div>
<com:TCallback ID="LoadPluginSettingsList" OnCallback="loadPluginSettingsList" />
<com:TCallback ID="SavePluginSettingsForm" OnCallback="savePluginSettingsForm" />
<com:TCallback ID="LoadPluginPluginsList" OnCallback="loadPluginPluginsList" />
<com:TCallback ID="RemovePluginSettingsAction" OnCallback="removePluginSettings">
	<prop:ClientSide.OnComplete>
		oPluginListSettings.table_toolbar.style.display = 'none';
	</prop:ClientSide.OnComplete>
</com:TCallback>
<script>
var oPluginListSettings = {
	ids: {
		table: 'plugin_list_settings_list'
	},
	actions: [
		{
			action: 'remove',
			label: '<%[ Remove ]%>',
			value: 'name',
			callback: <%=$this->RemovePluginSettingsAction->ActiveControl->Javascript%>
		}
	],
	table: null,
	table_toolbar: null,
	init: function(data) {
		if (this.table) {
			this.table.clear();
			this.table.rows.add(data);
			this.table.draw(false);
		} else {
			this.set_table(data);
			this.set_bulk_actions();
			this.set_events();
		}
	},
	set_events: function() {
		document.getElementById(this.ids.table).addEventListener('click', function(e) {
			$(function() {
				this.table_toolbar.style.display = this.table.rows({selected: true}).data().length > 0 ? '' : 'none';
			}.bind(this));
		}.bind(this));
	},
	set_table: function(data) {
		this.table = $('#' + this.ids.table).DataTable({
			data: data,
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
				},
				{
					data: 'name'
				},
				{
					data: 'plugin',
					render: function(data, type, row) {
						let plugin = data;
						if (oPlugins.plugins.hasOwnProperty(data)) {
							plugin = oPlugins.plugins[data].name;
						}
						return plugin;
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
						const props = {name: data, plugin: row.plugin, enabled: row.enabled};
						btn_edit.setAttribute('onclick', 'oPlugins.load_plugin_settings_window(' + JSON.stringify(props) + ')');
						return btn_edit.outerHTML;
					}
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
			order: [1, 'asc'],
			drawCallback: function () {
				this.api().columns([2, 3]).every(function () {
					var column = this;
					var select = $('<select><option value=""></option></select>')
					.appendTo($(column.footer()).empty())
					.on('change', function () {
						var val = dtEscapeRegex(
							$(this).val()
						);
						column
						.search(val ? '^' + val + '$' : '', true, false)
						.draw();
					});
					if ([2, 3].indexOf(column[0][0]) > -1) { // Enabled column
						let items = [];
						column.data().unique().sort().each(function (d, j) {
							if (Array.isArray(d)) {
								d = d.toString();
							}
							if (d === '' || items.indexOf(d) > -1) {
								return;
							}
							items.push(d);
						});
						for (let item of items) {
							let ds = '';
							if (column[0][0] == 3) { // enabled flag
								if (item === '1') {
									ds = '<%[ Enabled ]%>';
								} else if (item === '0') {
									ds = '<%[ Disabled ]%>';
								}
							} else if (column[0][0] == 2) { // plugin name
								ds = oPlugins.plugins.hasOwnProperty(item) ? oPlugins.plugins[item].name : '-';
								item = ds;
							} else {
								ds = item;
							}
							if (column.search() == '^' + dtEscapeRegex(item) + '$') {
								select.append('<option value="' + item + '" title="' + ds + '" selected>' + ds + '</option>');
							} else {
								select.append('<option value="' + item + '" title="' + ds + '">' + ds + '</option>');
							}
						}
					} else {
						column.data().sort().unique().each(function(d, j) {
							if (column.search() == '^' + dtEscapeRegex(d) + '$') {
								select.append('<option value="' + d + '" selected>' + d + '</option>');
							} else {
								select.append('<option value="' + d + '">' + d + '</option>');
							}
						});
					}
				});
			}
		});
	},
	set_bulk_actions: function() {
		this.table_toolbar = get_table_toolbar(this.table, this.actions, {
			actions: '<%[ Actions ]%>',
			ok: '<%[ OK ]%>'
		});
	}
};

const oPluginForm = {
	ids: {
		plugin_form: 'plugin_list_plugin_settings_form',
		settings_name: '<%=$this->PluginSettingsName->ClientID%>'
	},
	types: {
		arr: 'array',
		arr_multi: 'array_multiple',
		str: 'string',
		str_long: 'string_long',
		bool: 'boolean',
		int: 'integer'
	},
	name_prefix: 'plugin_form_',
	form_props: null,
	build_form: function(props) {
		this.clear_plugin_settings_form();
		this.form_props = props;
		for (let i = 0; i < this.form_props.parameters.length; i++) {
			this.add_field(this.form_props.parameters[i]);
		}
	},
	set_form_fields: function(props, plugin_fields) {
		let el, type, name, value, def_value;
		for (let i = 0; i < plugin_fields.length; i++) {
			type = plugin_fields[i].type;
			name = plugin_fields[i].name;
			value = props ? props[name] : '';
			def_value = plugin_fields[i].default;
			el = document.getElementById(this.name_prefix + name);
			if (!el) {
				continue;
			}
			if (type == this.types.str || type == this.types.str_long || type == this.types.int || type == this.types.arr) {
				el.value = value || def_value;
			} else if (type == this.types.bool) {
				el.checked = (value && value != def_value) ? (value == 1) : def_value;
			} else if (type == this.types.arr_multi) {
				const vals = value.length > 0 ? value : def_value;
				el.value = '';
				OUTER:
				for (let j = 0; j < vals.length; j++) {
					INNER:
					for (let k = 0; k < el.options.length; k++) {
						if (vals[j] === el.options[k].value) {
							el.options[k].selected = true;
							continue OUTER;
						}
					}
				}
			}
		}
	},
	add_row: function(prop, field) {
		const container = document.getElementById(this.ids.plugin_form);
		const row = document.createElement('DIV');
		row.classList.add('w3-row', 'directive_field');
		const rleft = document.createElement('DIV');
		rleft.classList.add('w3-col', 'w3-third');
		const rright = document.createElement('DIV');
		rright.classList.add('w3-col', 'w3-half');
		const label = document.createElement('DIV');
		label.setAttribute('for', this.name_prefix + prop.name);
		label.textContent = prop.label + ':';
		rleft.appendChild(label);
		rright.appendChild(field);
		row.appendChild(rleft);
		row.appendChild(rright);
		container.appendChild(row);
	},
	add_field: function(prop) {
		let field;
		if (prop.type == this.types.str || prop.type == this.types.int) {
			field = this.get_text_field(prop);
		} else if (prop.type == this.types.str_long) {
			field = this.get_text_long_field(prop);
		} else if (prop.type == this.types.bool) {
			field = this.get_bool_field(prop);
		} else if (prop.type == this.types.arr) {
			field = this.get_list_field(prop);
		} else if (prop.type == this.types.arr_multi) {
			field = this.get_list_multi_field(prop);
		}
		if (field) {
			this.add_row(prop, field);
		}
	},
	get_text_field: function(prop) {
		const input = document.createElement('INPUT');
		input.type = 'text';
		input.id = this.name_prefix + prop.name;
		input.name = this.name_prefix + prop.name;
		input.classList.add('w3-input', 'w3-border');
		return input;
	},
	get_text_long_field: function(prop) {
		const textarea = document.createElement('TEXTAREA');
		textarea.id = this.name_prefix + prop.name;
		textarea.name = this.name_prefix + prop.name;
		textarea.setAttribute('rows', '4');
		textarea.classList.add('w3-input', 'w3-border');
		return textarea;
	},
	get_bool_field: function(prop) {
		const input = document.createElement('INPUT');
		input.type = 'checkbox';
		input.id = this.name_prefix + prop.name;
		input.name = this.name_prefix + prop.name;
		input.classList.add('w3-check');
		return input;
	},
	get_list_field: function(prop) {
		const select = document.createElement('SELECT');
		select.id = this.name_prefix + prop.name;
		select.name = this.name_prefix + prop.name;
		select.classList.add('w3-select', 'w3-border');
		let opt, txt;
		for (let i = 0; i < prop.data.length; i++) {
			opt = document.createElement('OPTION');
			opt.value = prop.data[i];
			txt = document.createTextNode(prop.data[i]);
			opt.appendChild(txt);
			select.appendChild(opt);
		}
		return select;
	},
	get_list_multi_field: function(prop) {
		container = document.createElement('DIV');
		const select = document.createElement('SELECT');
		select.id = this.name_prefix + prop.name;
		select.name = this.name_prefix + prop.name;
		select.classList.add('w3-select', 'w3-border');
		select.setAttribute('multiple', 'multiple');
		let opt, txt;
		for (let i = 0; i < prop.data.length; i++) {
			opt = document.createElement('OPTION');
			opt.value = prop.data[i];
			txt = document.createTextNode(prop.data[i]);
			opt.appendChild(txt);
			select.appendChild(opt);
		}
		const tip = document.createElement('P');
		tip.style.marginTop = '0';
		tip.textContent = '<%[ Use CTRL + left-click to multiple item selection ]%>';
		container.appendChild(select);
		container.appendChild(tip);
		return container;
	},
	clear_plugin_settings_form: function() {
		const form = document.getElementById(this.ids.plugin_form);
		while (form.firstChild) {
			form.removeChild(form.firstChild);
		}
		this.form_props = null;
	},
	validate_form: function() {
		let state = true;
		const name = document.getElementById(this.ids.settings_name);
		if (!name.checkValidity()) {
			name.reportValidity();
			state = false;
		} else {
			name.setCustomValidity('');
		}
		return state;
	},
	save_form: function() {
		if (this.form_props === null || !this.validate_form()) {
			return;
		}
		const fields = {};
		let prop, el;
		for (let i = 0; i < this.form_props.parameters.length; i++) {
			prop = this.form_props.parameters[i];
			el = document.getElementById(this.name_prefix + prop.name);
			if (prop.type == this.types.str || prop.type == this.types.int || prop.type == this.types.str_long) {
				fields[prop.name] = el.value;
			} else if (prop.type == this.types.bool) {
				fields[prop.name] = el.checked ? '1' : '0';
			} else if (prop.type == this.types.arr) {
				fields[prop.name] = el.value;
			} else if (prop.type == this.types.arr_multi) {
				const values = [];
				for (let j = 0; j < el.options.length; j++) {
					if (el.options[j].selected) {
						values.push(el.options[j].value);
					}
				}
				fields[prop.name] = values;
			}
		}
		const cb = <%=$this->SavePluginSettingsForm->ActiveControl->Javascript%>;
		cb.setCallbackParameter(fields);
		cb.dispatch();
	}
};

var oPluginListPlugins = {
	ids: {
		table: 'plugin_list_plugins_list'
	},
	table: null,
	init: function(data) {
		if (this.table) {
			this.table.clear();
			this.table.rows.add(data);
			this.table.draw(false);
		} else {
			this.set_table(data);
		}
	},
	set_table: function(data) {
		this.table = $('#' + this.ids.table).DataTable({
			data: data,
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
					data: 'name'
				},
				{
					data: 'type'
				},
				{
					data: 'version'
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
				targets: [ 2, 3 ]
			}],
			order: [1, 'asc'],
			drawCallback: function () {
				$('#' + oPluginListPlugins.ids.table + ' tbody tr td').css('padding', '10px');
				this.api().columns([2, 3]).every(function () {
					var column = this;
					var select = $('<select><option value=""></option></select>')
					.appendTo($(column.footer()).empty())
					.on('change', function () {
						var val = dtEscapeRegex(
							$(this).val()
						);
						column
						.search(val ? '^' + val + '$' : '', true, false)
						.draw();
					});
					column.data().sort().unique().each(function(d, j) {
						if (column.search() == '^' + dtEscapeRegex(d) + '$') {
							select.append('<option value="' + d + '" selected>' + d + '</option>');
						} else {
							select.append('<option value="' + d + '">' + d + '</option>');
						}
					});
				});
			}
		});
	}
};

var oPlugins = {
	ids: {
		plugin_win: 'plugin_list_plugin_settings_window',
		plugin_form: 'plugin_list_plugin_settings_form',
		win_title_add: 'plugin_list_plugin_settings_title_add',
		win_title_edit: 'plugin_list_plugin_settings_title_edit',
		settings_name: '<%=$this->PluginSettingsName->ClientID%>',
		settings_enabled: '<%=$this->PluginSettingsEnabled->ClientID%>',
		settings_plugin_name: '<%=$this->PluginSettingsPluginName->ClientID%>',
		window_mode: '<%=$this->PluginSettingsWindowMode->ClientID%>',
		error_settings_exist: 'plugin_list_plugin_settings_exists',
		error_settings_error: 'plugin_list_plugin_settings_error'
	},
	plugins: [],
	settings: [],
	init: function() {
		this.load_plugin_plugins_list();
	},
	load_plugin_settings_list: function() {
		const cb = <%=$this->LoadPluginSettingsList->ActiveControl->Javascript%>;
		cb.dispatch();
	},
	load_plugin_settings_list_cb: function(data) {
		oPlugins.settings = data;
		const tdata = Object.values(data);
		oPluginListSettings.init(tdata);
	},
	load_plugin_plugins_list: function() {
		const cb = <%=$this->LoadPluginPluginsList->ActiveControl->Javascript%>;
		cb.dispatch();
	},
	load_plugin_plugins_list_cb: function(data) {
		oPlugins.plugins = data;
		const tdata = Object.values(data);
		oPluginListPlugins.init(tdata);
		oPlugins.load_plugin_settings_list();
	},
	show_plugin_settings_window: function(show) {
		const win = document.getElementById(oPlugins.ids.plugin_win);
		win.style.display = show ? 'block' : 'none';
	},
	load_plugin_settings_window: function(props) {
		oPluginForm.clear_plugin_settings_form();
		this.clear_plugin_settings_window();
		const window_mode = document.getElementById(this.ids.window_mode);
		const name = document.getElementById(this.ids.settings_name);
		if (props) {
			window_mode.value = 'edit';
			const title_edit = document.getElementById(this.ids.win_title_edit);
			title_edit.style.display = 'inline-block';
			name.value = props.name;
			name.setAttribute('readonly', 'readonly');
			const enabled = document.getElementById(this.ids.settings_enabled);
			enabled.checked = (props.enabled == 1);
			const plugin_name = document.getElementById(this.ids.settings_plugin_name);
			plugin_name.value = props.plugin;
			this.load_plugin_settings_form(props.plugin);
			oPluginForm.set_form_fields(this.settings[props.name].parameters, this.plugins[props.plugin].parameters);
		} else {
			window_mode.value = 'add';
			name.removeAttribute('readonly');
			const title_add = document.getElementById(this.ids.win_title_add);
			title_add.style.display = 'inline-block';
		}
		this.show_plugin_settings_window(true);
	},
	clear_plugin_settings_window: function() {
		const self = oPlugins;
		[
			self.ids.win_title_add,
			self.ids.win_title_edit,
			self.ids.error_settings_exist,
			self.ids.error_settings_error
		].forEach((id) => {
			document.getElementById(id).style.display = 'none';
		});
		const settings_name = document.getElementById(self.ids.settings_name);
		settings_name.value = '';
		const settings_enabled = document.getElementById(self.ids.settings_enabled);
		settings_enabled.checked = true;
		const settings_plugin_name = document.getElementById(self.ids.settings_plugin_name);
		settings_plugin_name.selectedIndex = 0;
	},
	load_plugin_settings_form: function(name) {
		oPluginForm.clear_plugin_settings_form();
		if (name != 'none') {
			const data = oPlugins.plugins[name];
			oPluginForm.build_form(data);
			oPluginForm.set_form_fields('', data.parameters);
		}
	}
};
$(function() {
	oPlugins.init();
});
</script>
<div id="plugin_list_plugin_settings_window" class="w3-modal">
	<div class="w3-modal-content w3-animate-top w3-card-4">
		<header class="w3-container w3-green">
			<span onclick="oPlugins.show_plugin_settings_window(false);" class="w3-button w3-display-topright">&times;</span>
			<h2 id="plugin_list_plugin_settings_title_add" style="display: none"><%[ Add plugin settings ]%></h2>
			<h2 id="plugin_list_plugin_settings_title_edit" style="display: none"><%[ Edit plugin settings ]%></h2>
		</header>
		<div class="w3-container w3-padding-large" style="padding-bottom: 0 !important;">
			<span id="plugin_list_plugin_settings_exists" class="error" style="display: none"><%[ Plugin settings with the given name already exists. ]%></span>
			<span id="plugin_list_plugin_settings_error" class="error" style="display: none"></span>
			<div class="w3-row directive_field">
				<div class="w3-col w3-third"><label for="<%=$this->PluginSettingsName->ClientID%>"><%[ Name ]%>:</label></div>
				<div class="w3-half w3-show-inline-block">
					<com:TActiveTextBox
						ID="PluginSettingsName"
						CssClass="w3-input w3-border"
						Attributes.pattern="<%=PluginConfig::SETTINGS_NAME_PATTERN%>"
						Attributes.required="required"
					/>
				</div>
			</div>
			<div class="w3-row directive_field">
				<div class="w3-col w3-third"><label for="<%=$this->PluginSettingsName->ClientID%>"><%[ Enabled ]%>:</label></div>
				<div class="w3-half w3-show-inline-block">
					<com:TActiveCheckBox
						ID="PluginSettingsEnabled"
						CssClass="w3-check w3-border"
						AutoPostBack="false"
					/>
				</div>
			</div>
			<div class="w3-row directive_field">
				<div class="w3-col w3-third"><label for="<%=$this->PluginSettingsPluginName->ClientID%>"><%[ Plugin ]%>:</label></div>
				<div class="w3-half w3-show-inline-block">
					<com:TActiveDropDownList
						ID="PluginSettingsPluginName"
						CssClass="w3-select w3-border"
						AutoPostBack="false"
						Attributes.onchange="oPlugins.load_plugin_settings_form(this.value);"
					/>
				</div>
			</div>
			<com:TActiveHiddenField ID="PluginSettingsWindowMode" />
			<div id="plugin_list_plugin_settings_form"></div>
		</div>
		<footer class="w3-container w3-padding-large w3-center">
			<button type="button" class="w3-button w3-red" onclick="oPlugins.show_plugin_settings_window(false);"><i class="fas fa-times"></i> &nbsp;<%[ Cancel ]%></button>
			<button type="button" class="w3-button w3-green" onclick="oPluginForm.save_form();"><i class="fa-solid fa-save"></i> &nbsp;<%[ Save ]%></button>
		</footer>
	</div>
</div>
