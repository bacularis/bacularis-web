<com:TPanel ID="SaveComponentToPatternModal" CssClass="w3-modal" Display="None" Style="z-index: 5;">
	<div class="w3-modal-content w3-card-4 w3-animate-zoom">
		<header class="w3-container w3-green">
			<span onclick="oSaveComponentToPattern.show_window(false);" class="w3-button w3-display-topright">×</span>
			<h2><%[ Save Bacula component configuration to pattern ]%></h2>
		</header>
		<div class="w3-margin-left w3-margin-right" style="overflow-x: auto;">
			<p><%[ The saving component configuration to pattern feature allows to save entire (or selected) Bacula component configuration to pattern and configs. This enables to easily reuse this configuration on new Bacula hosts. This helps to configure multiple new Bacula hosts with the same or similar configuration using a single action. The configuration is divided into Bacula resources (configs) that can be applied to new hosts at once (by appying pattern) or you can select which Bacula resources you want to apply to new host (by applying configs). ]%></p>
			<div class="w3-row w3-margin-bottom">
				<div class="w3-col w3-quarter"><%[ Pattern name ]%>:</div>
				<div class="w3-col w3-threequarter">
					<com:TActiveTextBox
						ID="SaveComponentToPatternPatternName"
						CssClass="w3-input w3-border w3-show-inline-block"
						Width="470px"
					/> <i class="fa fa-asterisk w3-text-red directive_required" style="visibility: visible;"></i>
					<com:TRequiredFieldValidator
						ValidationGroup="SaveToPatternGroup"
						Display="Dynamic"
						ControlToValidate="SaveComponentToPatternPatternName"
						FocusOnError="true"
						Text="<%[ Field required. ]%>"
					/>
					<com:TRegularExpressionValidator
						ValidationGroup="SaveToPatternGroup"
						RegularExpression="<%=PatternConfig::NAME_PATTERN%>"
						ClientSidePatternModifiers="u"
						ControlToValidate="SaveComponentToPatternPatternName"
						ErrorMessage="<%[ Invalid value. ]%>"
						FocusOnError="true"
						Display="None"
					/>
				</div>
			</div>
			<div class="w3-row w3-margin-bottom">
				<div class="w3-col w3-quarter"><%[ Config name ]%>:</div>
				<div class="w3-col w3-threequarter">
					<com:TActiveTextBox
						ID="SaveComponentToPatternConfigName"
						CssClass="w3-input w3-border w3-show-inline-block"
						Width="470px"
						Text="Config - %component_name - %resource_type - %resource_name"
					/> <i class="fa fa-asterisk w3-text-red directive_required" style="visibility: visible;"></i>
					<com:TRequiredFieldValidator
						ValidationGroup="SaveToPatternGroup"
						Display="Dynamic"
						ControlToValidate="SaveComponentToPatternConfigName"
						FocusOnError="true"
						Text="<%[ Field required. ]%>"
					/>
					<com:TRegularExpressionValidator
						ValidationGroup="SaveToPatternGroup"
						RegularExpression="<%=ConfigConfig::NAME_PATTERN%>"
						ClientSidePatternModifiers="u"
						ControlToValidate="SaveComponentToPatternConfigName"
						ErrorMessage="<%[ Invalid value. ]%>"
						FocusOnError="true"
						Display="Dynamic"
					/>
					<i class="fas fa-info-circle help_icon w3-text-green" onclick="$(this).parents('div.w3-row').next().slideToggle('fast');"></i>
				</div>
			</div>
			<div class="directive_help" style="display: none">
				<dd>
					<p><%[ The following placeholders can be used in the config name ]%>:</p>
					<ul>
						<li><strong>%component_type</strong> - <%[ Bacula component type (ex: dir, sd, fd) ]%></li>
						<li><strong>%component_name</strong> - <%[ Bacula component name (ex: bacula-dir, debian-fd, hostxyz-sd) ]%></li>
						<li><strong>%resource_type</strong> - <%[ Bacula resource type (ex: Job, Client, Pool) ]%></li>
						<li><strong>%resource_name</strong> - <%[ Bacula resource name (ex: BackupClient1, BackupCatalog, verify_job_321) ]%></li>
					</ul>
				</dd>
			</div>
			<div class="w3-row">
				<div class="w3-col w3-quarter"><%[ Overwrite config if exists ]%>:</div>
				<div class="w3-col w3-threequarter">
					<com:TActiveCheckBox
						ID="SaveComponentToPatternOverwriteConfig"
						CssClass="w3-check"
					/>
					<i class="fas fa-info-circle help_icon w3-text-green" onclick="$(this).parents('div.w3-row').next().slideToggle('fast');"></i>
				</div>
			</div>
			<div class="directive_help" style="display: none">
				<dd><%[ Save the config even if a config with the same name already exists. In this case, the config will be overwritten by the new one. ]%></dd>
			</div>
			<h4 style="margin-bottom: 2px;"><%[ Select Bacula resources to save to pattern configs ]%>:</h4>
			<div class="w3-right w3-right-align" style="width: 50%; margin-top: 20px;">
				<a href="javascript:void(0)" class="raw" onclick="oSaveComponentToPattern.global_check_uncheck_all_resources(this);"><i class="fa-solid fa-check-double"></i> <%[ Global check/uncheck all ]%></a>
			</div>
			<div id="save_component_to_pattern_modal_table_container"></div>
			<p id="save_component_to_pattern_warning_msg" class="w3-orange w3-padding" style="display: none"><p>
			<p id="save_component_to_pattern_error_msg" class="w3-red w3-padding" style="display: none"><p>
		</div>
		<footer class="w3-container w3-center w3-border-top">
			<com:TActiveLinkButton
				CssClass="w3-button w3-section w3-green"
				ValidationGroup="SaveToPatternGroup"
				CausesValidation="true"
				Attributes.onclick="oSaveComponentToPattern.prepare_to_save(); return true;"
				OnCallback="saveComponentToPattern"
			>
				<i class="fa fa-save"></i> &nbsp;<%[ Save ]%>
			</com:TActiveLinkButton>
		</footer>
		<com:TActiveHiddenField ID="SavePatternValues" />
	</div>
</com:TPanel>
<com:TCallback ID="LoadPatterns" OnCallback="loadPatterns" />
<com:TCallback ID="ApplyPatterns" OnCallback="applyPatterns" />
<com:TCallback ID="SetConfigsWindow" OnCallback="setConfigsWindow" />
<com:TCallback ID="LoadResourceList" OnCallback="loadResourceList" />
<script>
var oSaveComponentToPattern = {
	ids: {
		win: '<%=$this->SaveComponentToPatternModal->ClientID%>',
		tcontainer: 'save_component_to_pattern_modal_table_container',
		save_values: '<%=$this->SavePatternValues->ClientID%>',
		warning_msg: 'save_component_to_pattern_warning_msg',
		error_msg: 'save_component_to_pattern_error_msg'
	},
	theaders: [
		'<%=Prado::localize('Name')%>',
		'<%=Prado::localize('Description')%>',
		'<%=Prado::localize('Save')%>'
	],
	init: function() {
		this.clear_resource_lists();
		this.create_resource_lists();
		this.set_warning('');
		this.set_error('');
	},
	show_window: function(show) {
		const self = oSaveComponentToPattern;
		const win = document.getElementById(self.ids.win);
		if (show) {
			self.init();
			win.style.display = 'block';
		} else {
			win.style.display = 'none';
		}
	},
	set_configs_window: function(host, component_type) {
		const cb = <%=$this->SetConfigsWindow->ActiveControl->Javascript%>;
		cb.setCallbackParameter([host, component_type]);
		cb.dispatch();
	},
	clear_resource_lists: function() {
		const container = document.getElementById(this.ids.tcontainer);
		while (container.firstChild) {
			container.removeChild(container.firstChild);
		}
	},
	create_resource_lists: function() {
		const cb = <%=$this->LoadResourceList->ActiveControl->Javascript%>;
		cb.dispatch();
	},
	create_resource_lists_cb: function(resources) {
		const self = oSaveComponentToPattern;
		for (const resource in resources) {
			self.create_resource_list(resource, resources[resource]);
		}
	},
	create_resource_list: function(resource, config) {
		this.add_resource_header(resource);
		this.add_resource_list(resource, config);
	},
	add_resource_header: function(resource) {
		const container = document.getElementById(this.ids.tcontainer);
		const div = document.createElement('DIV');
		const header = document.createElement('H5');
		header.classList.add('w3-left');
		header.style.width = '50%';
		header.textContent = resource;
		div.appendChild(header);
		const options = this.get_save_option_switcher(resource);
		div.appendChild(options);
		container.appendChild(div);
	},
	add_resource_list: function(resource, config) {
		const container = document.getElementById(this.ids.tcontainer);
		const table = document.createElement('TABLE');
		table.classList.add('w3-table', 'w3-striped', 'w3-hoverable', 'w3-margin-bottom', 'dataTable', 'dtr-column', 'display');
		let header_row, header, row, col;

		// Add table header
		row = this.get_table_row();
		for (let i = 0; i < this.theaders.length; i++) {
			header = this.get_table_header(this.theaders[i]);
			row.appendChild(header);
		}
		header_row = this.get_table_header_row();
		header_row.appendChild(row);
		table.appendChild(header_row);

		// Add table body
		body_row = this.get_table_body_row();
		for (let i = 0; i < config.length; i++) {
			row = this.get_table_row();

			// Add regular columns
			for (const key in config[i]) {
				col = this.get_table_col(config[i][key]);
				row.appendChild(col);
			}

			// Add additional save checkbox column
			col = this.get_checkbox_col(resource, config[i].resource_name);
			row.appendChild(col);

			body_row.appendChild(row);
		}
		table.appendChild(body_row);

		// Add table
		container.appendChild(table);
	},
	get_table_header_row: function(name) {
		const thead = document.createElement('THEAD');
		return thead;
	},
	get_table_body_row: function(name) {
		const tbody = document.createElement('TBODY');
		tbody.classList.add('pointer');
		return tbody;
	},
	get_table_header: function(name) {
		const th = document.createElement('TH');
		th.classList.add('w3-center');
		th.textContent = name;
		return th;
	},
	get_table_row: function(value) {
		const tr = document.createElement('TR');
		tr.classList.add('row');
		tr.textContent = value;
		tr.addEventListener('click', function() {
			$(this).find('input[type="checkbox"]').click();
		});
		return tr;
	},
	get_table_col: function(value) {
		const td = document.createElement('TD');
		td.textContent = value;
		return td;
	},
	get_checkbox_col: function(type, name) {
		const col = this.get_table_col('');
		col.classList.add('w3-center');
		col.style.width = '100px';
		const chkb = document.createElement('INPUT');
		chkb.type = 'checkbox';
		chkb.checked = true;
		chkb.setAttribute('data-type', type);
		chkb.setAttribute('data-resource', name);
		chkb.classList.add('w3-check');
		chkb.style.top = '2px';
		chkb.addEventListener('click', function(e) {
			if (e.isTrusted) {
				/**
				 * This is to avoid two click events if user clicks the checkbox
				 * (one from tr and one from checkbox)
				 */
				e.stopPropagation();
				return false;
			}
		});
		col.appendChild(chkb);
		return col;
	},
	get_save_option_switcher: function(type) {
		const options = document.createElement('DIV');
		options.style.width = '50%';
		options.style.marginTop = '20px';
		options.classList.add('w3-right', 'w3-right-align');
		const check_uncheck_all = document.createElement('A');
		check_uncheck_all.href = 'javascript:void(0)';
		check_uncheck_all.classList.add('raw');
		check_uncheck_all.addEventListener('click', () => {
			const is_check = check_uncheck_all.getAttribute('data-check') == 1;
			const check_all = document.querySelectorAll('input[data-type="' + type + '"]');
			for (let i = 0; i < check_all.length; i++) {
				check_all[i].checked = is_check;
			}
			check_uncheck_all.setAttribute('data-check', (is_check ? '0' : '1'));
		});
		const img = document.createElement('I');
		img.classList.add('fa-solid', 'fa-check');
		const label = document.createTextNode(' <%[ Check/uncheck all ]%>');
		check_uncheck_all.appendChild(img);
		check_uncheck_all.appendChild(label);
		options.appendChild(check_uncheck_all);
		return options;
	},
	prepare_to_save: function() {
		const values = document.getElementById(this.ids.save_values);
		const container = document.getElementById(this.ids.tcontainer);
		const chkbs = container.querySelectorAll('input[type="checkbox"][data-type][data-resource]');
		const vals = [];
		let type, resource;
		for (let i = 0; i < chkbs.length; i++) {
			if (!chkbs[i].checked) {
				continue;
			}
			type = chkbs[i].getAttribute('data-type');
			resource = chkbs[i].getAttribute('data-resource');
			vals.push({type: type, resource: resource});
		}
		values.value = JSON.stringify(vals);

		this.set_warning('');
		this.set_error('');
	},
	global_check_uncheck_all_resources: function(el) {
		const is_check = el.getAttribute('data-check') == 1;
		const tcontainer = document.getElementById(this.ids.tcontainer);
		const chkbs = tcontainer.querySelectorAll('input[type="checkbox"][data-type][data-resource]');
		for (let i = 0; i < chkbs.length; i++) {
			chkbs[i].checked = is_check;
		}
		el.setAttribute('data-check', (is_check ? '0' : '1'));
	},
	set_warning: function(msg) {
		const self = oSaveComponentToPattern;
		const err = document.getElementById(self.ids.warning_msg);
		err.innerHTML = msg;
		err.style.display = msg ? 'block' : 'none';
	},
	set_error: function(msg) {
		const self = oSaveComponentToPattern;
		const err = document.getElementById(self.ids.error_msg);
		err.textContent = msg;
		err.style.display = msg ? 'block' : 'none';
	}
};
</script>
