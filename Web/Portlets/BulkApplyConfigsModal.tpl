<com:TPanel ID="BulkApplyConfigsModal" CssClass="w3-modal" Display="None">
	<div class="w3-modal-content w3-card-4 w3-animate-zoom">
		<header class="w3-container w3-green">
			<span onclick="oBulkApplyConfigsModal.show_window(false);" class="w3-button w3-display-topright">×</span>
			<h2><%[ Bulk apply configs ]%></h2>
		</header>
		<div class="w3-margin-left w3-margin-right" style="overflow-x: auto;">
			<p><%[ Please select config(s) to apply to selected resource(s). ]%></p>
			<div class="w3-row directive_field w3-margin-bottom">
				<div class="w3-col w3-third">
					<%[ Component ]%>:
				</div>
				<div rel="component" class="w3-col w3-half bold"></div>
			</div>
			<div class="w3-row directive_field w3-margin-bottom">
				<div class="w3-col w3-third">
					<%[ Resource ]%>:
				</div>
				<div rel="resource" class="w3-col w3-half bold"></div>
			</div>
			<div class="w3-row directive_field">
				<div class="w3-col w3-third">
					<%[ Configs ]%>:
				</div>
				<div class="w3-col w3-half">
					<com:TActiveListBox
						ID="Configs"
						SelectionMode="Multiple"
						CssClass="w3-border w3-select"
						Rows="10"
						Attributes.onchange="oBulkApplyConfigsModal.prepare_variables();"
					/>
					<i class="fa fa-asterisk w3-text-red"></i>
					<p style="margin: 0 16px 0 0"><%[ Use CTRL + left-click to multiple item selection ]%></p>
					<com:TRequiredFieldValidator
						ValidationGroup="ConfigsGroup"
						Display="Dynamic"
						ControlToValidate="Configs"
						FocusOnError="true"
						Text="<%[ Field required. ]%>"
					/>
				</div>
			</div>
			<div class="w3-row">
				<div class="w3-col w3-third">&nbsp;</div>
				<div class="w3-col w3-twothird">
					<a href="javascript:void(0)" onclick="oBulkApplyConfigsModal.toggle_configs_in_patterns(true);">
						<span id="<%=$this->ClientID%>_show_in_pattern"><i class="fa-solid fa-eye"></i> <%[ Show configs used in patterns ]%></span>
						<span id="<%=$this->ClientID%>_hide_in_pattern" style="display: none"><i class="fa-solid fa-eye-slash"></i> <%[ Hide configs used in patterns ]%></span>
					</a>
				</div>
			</div>
			<div id="<%=$this->ClientID%>_variables" class="w3-row"></div>
			<h4><%[ Options ]%></h4>
			<div class="w3-row directive_field w3-margin-bottom">
				<div class="w3-col w3-third"><%[ Stop on error ]%>:</div>
				<div class="w3-col w3-twothird">
					<com:TActiveCheckBox
						ID="StopOnError"
						CssClass="w3-check"
						Checked="true"
					/>
				</div>
			</div>
			<div class="w3-row directive_field">
				<div class="w3-col w3-third"><%[ Overwrite policy ]%>:</div>
				<div class="w3-col w3-twothird">
					<com:TActiveRadioButton
						ID="OverwritePolicyAddNew"
						GroupName="DirectiveOverwritingPolicy"
						CssClass="w3-radio"
						Checked="true"
					/> <%[ Do not overwrite if directives from the configs are already defined in the Bacula resource configuration. Add only new ones. ]%>
				</div>
			</div>
			<div class="w3-row directive_field w3-margin-bottom">
				<div class="w3-col w3-third">&nbsp;</div>
				<div class="w3-col w3-twothird">
					<com:TActiveRadioButton
						ID="OverwritePolicyExisting"
						GroupName="DirectiveOverwritingPolicy"
						CssClass="w3-radio"
					/> <%[ Overwrite if directives from the configs are already defined in the Bacula resource configuration. ]%>
				</div>
			</div>
			<div rel="name_warning" class="w3-row w3-orange w3-padding w3-small" style="display: none">
				<%[ In the selected config(s) is defined the Name resource directive. It means that in case selecting a policy for overwriting resources, process will stop because there cannot exist multiple resources the same type with the same name. ]%>
			</div>
			<div class="w3-row">
				<div class="w3-col w3-third"><%[ Result ]%>:</div>
				<div rel="log" class="w3-col w3-twothird"></div>
			</div>
		</div>
		<footer class="w3-container w3-center w3-border-top">
			<button type="button" class="w3-button w3-section w3-green" onclick="const form = Prado.Validation.getForm(); return (Prado.Validation.validate(form, 'ConfigsGroup') && oBulkApplyConfigsModal.apply_configs(true));"><i class="fa fa-vial-circle-check"></i> &nbsp;<%[ Run simulation ]%></button>
			<button type="button" class="w3-button w3-section w3-green" onclick="const form = Prado.Validation.getForm(); return (Prado.Validation.validate(form, 'ConfigsGroup') && oBulkApplyConfigsModal.apply_configs());"><i class="fa fa-check"></i> &nbsp;<%[ Apply ]%></button>
			<i rel="loader" class="fa fa-sync w3-spin w3-margin-left" style="display: none"></i>
		</footer>
	</div>
</com:TPanel>
<com:TCallback ID="LoadConfigs" OnCallback="loadConfigs" />
<com:TCallback ID="ApplyConfigs" OnCallback="applyConfigs" />
<com:TCallback ID="PrepareVariables" OnCallback="prepareVariables" />
<script>
var oBulkApplyConfigsModal = {
	ids: {
		win: '<%=$this->BulkApplyConfigsModal->ClientID%>',
		vars: '<%=$this->ClientID%>_variables',
		stop: '<%=$this->StopOnError->ClientID%>',
		policy_add_new: '<%=$this->OverwritePolicyAddNew->ClientID%>',
		policy_existing: '<%=$this->OverwritePolicyExisting->ClientID%>',
		show_in_pattern: '<%=$this->ClientID%>_show_in_pattern',
		hide_in_pattern: '<%=$this->ClientID%>_hide_in_pattern'
	},
	rels: {
		loader: 'loader',
		log: 'log',
		component: 'component',
		resource: 'resource',
		name_warning: 'name_warning'
	},
	item_cb: null,
	selected: [],
	show_name_warning: function(show) {
		const self = oBulkApplyConfigsModal;
		const warning = document.querySelector('#' + self.ids.win + ' div[rel="' + self.rels.name_warning + '"]');
		warning.style.display = show ? '' : 'none';
	},
	clear_window: function() {
		this.clear_vars_form();
		this.clear_log();
		this.clear_configs_in_patterns();

		// set default values
		const stop = document.getElementById(this.ids.stop);
		stop.checked = true;
		const policy_add_new = document.getElementById(this.ids.policy_add_new);
		policy_add_new.checked = true;
		const log = document.querySelector('#' + this.ids.win + ' div[rel="' + this.rels.log + '"]');
		log.textContent = '-';

	},
	update_component: function(component) {
		const self = oBulkApplyConfigsModal;
		const loader = document.querySelector('#' + self.ids.win + ' div[rel="' + self.rels.component + '"]');
		loader.textContent = component;
	},
	update_resource: function(resource) {
		const self = oBulkApplyConfigsModal;
		const loader = document.querySelector('#' + self.ids.win + ' div[rel="' + self.rels.resource + '"]');
		loader.textContent = resource;
	},
	show_window: function(show) {
		if (show) {
			this.clear_window();
			this.load_configs();
		}
		const win = document.getElementById(this.ids.win);
		win.style.display = show ? 'block' : '';
	},
	load_configs: function(params) {
		const cb = <%=$this->LoadConfigs->ActiveControl->Javascript%>;
		cb.setCallbackParameter(params);
		cb.dispatch();
	},
	apply_configs: function(simulate) {
		if (typeof(this.item_cb) != 'function') {
			return;
		}
		this.clear_log();

		this.selected = this.item_cb();
		this.apply_config(simulate);

	},
	apply_config: function(simulate) {
		const item = this.selected.shift();
		const vars = this.get_vars();
		if (item) {
			const cb = <%=$this->ApplyConfigs->ActiveControl->Javascript%>;
			this.add_log_line(item);
			const parameter = {
				name: item,
				vars: vars,
				simulate: (simulate || false)
			};
			cb.setCallbackParameter(parameter);
			cb.dispatch();
		}
	},
	add_log_line: function(name) {
		const log = document.querySelector('#' + this.ids.win + ' div[rel="' + this.rels.log + '"]');

		// row line
		const row = document.createElement('DIV');
		row.classList.add('w3-row');

		// column with config name
		const col_l = document.createElement('DIV');
		col_l.classList.add('w3-col', 'w3-third');
		col_l.textContent = name;

		// column with status
		const col_r = document.createElement('DIV');
		col_r.classList.add('w3-col', 'w3-third');
		col_r.setAttribute('data-item', name);
		const loader = document.createElement('I');
		loader.classList.add('fa-solid', 'fa-sync-alt', 'w3-spin');
		col_r.appendChild(loader);

		row.appendChild(col_l);
		row.appendChild(col_r);
		log.appendChild(row);
	},
	update_log_status: function(name, success, emsg, resource, simulate) {
		const self = oBulkApplyConfigsModal;
		const item_status = document.querySelector('#' + self.ids.win + ' div[data-item="' + name  + '"] > i');
		const row = item_status.parentNode;
		item_status.classList.remove('w3-spin');
		const text = document.createElement('SPAN');
		text.classList.add('w3-small');

		if (success) {
			item_status.classList.replace('fa-sync-alt', 'fa-check');
			item_status.classList.add('w3-text-green');
			text.textContent = ' <%[ OK ]%>';
			row.appendChild(text);
		} else {
			item_status.classList.replace('fa-sync-alt', 'fa-times');
			item_status.classList.add('w3-text-red');
			text.textContent = ' <%[ Error ]%>';
			row.appendChild(text);
			const msg = document.createElement('SPAN');
			msg.classList.add('w3-small');
			msg.textContent = ' - ' + emsg;
			row.appendChild(msg);
		}

		// prepare resource
		const res = self.prepare_resource(resource);
		row.parentNode.appendChild(res);

		const stop = document.getElementById(self.ids.stop);
		if (success || !stop.checked) {
			self.apply_config(simulate);
		}
	},
	prepare_resource: function(resource) {
		const row = document.createElement('DIV');
		const col_r = document.createElement('PRE');
		col_r.textContent = resource;
		col_r.classList.add('w3-small', 'w3-code');
		col_r.style.display = 'none';
		const col_l = document.createElement('SPAN');
		col_l.textContent = resource ? '<%[ Show result ]%>' : '';
		col_l.classList.add('pointer');
		col_l.addEventListener('click', (e) => {
			if (col_r.style.display == 'none') {
				$(col_r).slideDown('fast');
				col_l.textContent = '<%[ Hide result ]%>';
			} else {
				$(col_r).slideUp('fast');
				col_l.textContent = '<%[ Show result ]%>';
			}
		});
		row.appendChild(col_l);
		row.appendChild(col_r);
		return row;
	},
	clear_log: function() {
		const log = document.querySelector('#' + this.ids.win + ' div[rel="' + this.rels.log + '"]');
		while (log.firstChild) {
			log.removeChild(log.firstChild);
		}
	},
	set_item_cb: function(cb) {
		this.item_cb = cb;
	},
	prepare_variables: function() {
		const cb = <%=$this->PrepareVariables->ActiveControl->Javascript%>;
		cb.dispatch();
	},
	prepare_variables_cb: function(variables) {
		const self = oBulkApplyConfigsModal;
		self.build_vars_form(variables);
	},
	build_vars_form: function(variables) {
		this.clear_vars_form();

		if (typeof(variables) != 'object' || Array.isArray(variables)) {
			return;
		}

		const container = document.getElementById(this.ids.vars);

		const header = document.createElement('H4');
		header.textContent = '<%[ Variables ]%>';
		container.appendChild(header);

		let cont, left, right, input;
		for (const variable in variables) {
			cont = document.createElement('DIV');
			cont.classList.add('w3-row', 'directive_field', 'w3-margin-bottom');

			left = document.createElement('DIV');
			left.classList.add('w3-col', 'w3-third', 'bold');
			left.title = variables[variable].description || '';
			left.textContent = '<%=VariableConfig::SPECIAL_CHAR%>{' + variable + '}';

			right = document.createElement('DIV');
			right.classList.add('w3-col', 'w3-twothird');

			input = document.createElement('INPUT');
			input.type = 'text';
			input.setAttribute('data-variable', variable);
			input.classList.add('w3-input', 'w3-border');
			input.value = variables[variable].default_value || '';

			right.appendChild(input);
			cont.appendChild(left);
			cont.appendChild(right);
			container.appendChild(cont);
		}
	},
	clear_vars_form: function() {
		const container = document.getElementById(this.ids.vars);
		while (container.firstChild) {
			container.removeChild(container.firstChild);
		}
	},
	get_vars: function() {
		const container = document.getElementById(this.ids.vars);
		const var_fields = container.querySelectorAll('input[data-variable]');
		const var_len = var_fields.length;
		const variables = {};
		let variable, value;
		for (let i = 0; i < var_len; i++) {
			variable = var_fields[i].getAttribute('data-variable');
			value = var_fields[i].value;
			variables[variable] = value;
		}
		return variables;
	},
	clear_configs_in_patterns: function() {
		const show_in_pattern = document.getElementById(this.ids.show_in_pattern);
		if (show_in_pattern.style.display == 'none') {
			this.toggle_configs_in_patterns(false);
		}
	},
	toggle_configs_in_patterns: function(load_configs) {
		const show_in_pattern = document.getElementById(this.ids.show_in_pattern);
		const hide_in_pattern = document.getElementById(this.ids.hide_in_pattern);
		const show = ['', 'inline'].indexOf(show_in_pattern.style.display) != -1;
		show_in_pattern.style.display = show ? 'none' : 'inline';
		hide_in_pattern.style.display = show ? 'inline' : 'none';
		if (load_configs) {
			this.load_configs({in_pattern: show});
		}
	}
};
</script>
