<com:TPanel ID="BulkApplyPatternsModal" CssClass="w3-modal" Display="None" Style="z-index: 5;">
	<div class="w3-modal-content w3-card-4 w3-animate-zoom">
		<header class="w3-container w3-green">
			<span onclick="oBulkApplyPatternsModal.show_window(false);" class="w3-button w3-display-topright">×</span>
			<h2><%[ Bulk apply patterns ]%></h2>
		</header>
		<div class="w3-margin-left w3-margin-right" style="overflow-x: auto;">
			<p><%[ Please select pattern(s) to apply to API host component configuration. ]%></p>
			<div class="w3-row directive_field w3-margin-bottom">
				<div class="w3-col w3-third">
					<%[ API host ]%>:
				</div>
				<div rel="host" class="w3-col w3-half bold"></div>
			</div>
			<div class="w3-row directive_field w3-margin-bottom">
				<div class="w3-col w3-third">
					<%[ Component ]%>:
				</div>
				<div rel="component" class="w3-col w3-half bold"></div>
			</div>
			<div class="w3-row directive_field w3-margin-bottom">
				<div class="w3-col w3-third">
					<%[ Patterns ]%>:
				</div>
				<div class="w3-col w3-half">
					<com:TActiveListBox
						ID="Patterns"
						SelectionMode="Multiple"
						CssClass="w3-border w3-select"
						Rows="10"
					/>
					<i class="fa fa-asterisk w3-text-red"></i>
					<p style="margin: 0 16px 0 0"><%[ Use CTRL + left-click to multiple item selection ]%></p>
					<com:TRequiredFieldValidator
						ValidationGroup="PatternsGroup"
						Display="Dynamic"
						ControlToValidate="Patterns"
						FocusOnError="true"
						Text="<%[ Field required. ]%>"
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
					/> <%[ Do not overwrite the same directives if the config resource already exists in the Bacula component configuration. Add only new directives. ]%>
				</div>
			</div>
			<div class="w3-row directive_field w3-margin-bottom">
				<div class="w3-col w3-third">&nbsp;</div>
				<div class="w3-col w3-twothird">
					<com:TActiveRadioButton
						ID="OverwritePolicyExisting"
						GroupName="DirectiveOverwritingPolicy"
						CssClass="w3-radio"
					/> <%[ Overwrite the same directives if the config resource already exists in the Bacula resource configuration. ]%>
				</div>
			</div>
			<div class="w3-row">
				<div class="w3-col w3-third"><%[ Result ]%>:</div>
				<div rel="log" class="w3-col w3-twothird"></div>
			</div>
		</div>
		<footer class="w3-container w3-center w3-border-top">
			<button type="button" class="w3-button w3-section w3-green" onclick="const form = Prado.Validation.getForm(); return (Prado.Validation.validate(form, 'PatternsGroup') && oBulkApplyPatternsModal.apply_patterns(true));"><i class="fa fa-vial-circle-check"></i> &nbsp;<%[ Run simulation ]%></button>
			<button type="button" class="w3-button w3-section w3-green" onclick="const form = Prado.Validation.getForm(); return (Prado.Validation.validate(form, 'PatternsGroup') && oBulkApplyPatternsModal.apply_patterns());"><i class="fa fa-check"></i> &nbsp;<%[ Apply ]%></button>
			<i rel="loader" class="fa fa-sync w3-spin w3-margin-left" style="display: none"></i>
		</footer>
		<com:TActiveHiddenField ID="PatternAPIHost" />
	</div>
</com:TPanel>
<com:TCallback ID="LoadPatterns" OnCallback="loadPatterns" />
<com:TCallback ID="ApplyPatterns" OnCallback="applyPatterns" />
<com:TCallback ID="SetPatternsWindow" OnCallback="setPatternsWindow" />
<script>
var oBulkApplyPatternsModal = {
	ids: {
		win: '<%=$this->BulkApplyPatternsModal->ClientID%>',
		policy_add_new: '<%=$this->OverwritePolicyAddNew->ClientID%>',
		policy_existing: '<%=$this->OverwritePolicyExisting->ClientID%>'
	},
	rels: {
		loader: 'loader',
		log: 'log',
		component: 'component',
		host: 'host'
	},
	item_cb: null,
	selected: [],
	clear_window: function() {
		this.clear_log();

		// set default values
		const policy_add_new = document.getElementById(this.ids.policy_add_new);
		policy_add_new.checked = true;
		const log = document.querySelector('#' + this.ids.win + ' div[rel="' + this.rels.log + '"]');
		log.textContent = '-';
	},
	set_patterns_window: function(host, component_type) {
		const cb = <%=$this->SetPatternsWindow->ActiveControl->Javascript%>;
		cb.setCallbackParameter([host, component_type]);
		cb.dispatch();
	},
	update_host: function(host) {
		const self = oBulkApplyPatternsModal;
		const loader = document.querySelector('#' + self.ids.win + ' div[rel="' + self.rels.host + '"]');
		loader.textContent = host || 'Main';
	},
	update_component: function(component) {
		const self = oBulkApplyPatternsModal;
		const loader = document.querySelector('#' + self.ids.win + ' div[rel="' + self.rels.component + '"]');
		loader.textContent = component;
	},
	show_window: function(show) {
		if (show) {
			this.clear_window();
			this.load_patterns();
		}
		const win = document.getElementById(this.ids.win);
		win.style.display = show ? 'block' : '';
	},
	load_patterns: function() {
		const cb = <%=$this->LoadPatterns->ActiveControl->Javascript%>;
		cb.dispatch();
	},
	apply_patterns: function(simulate) {
		this.clear_log();
		this.apply_pattern(simulate);

	},
	apply_pattern: function(simulate) {
		const cb = <%=$this->ApplyPatterns->ActiveControl->Javascript%>;
		const item = document.getElementById('<%=$this->PatternAPIHost->ClientID%>').value;
		this.add_log_line(item);
		const parameter = {
			simulate: (simulate || false)
		};
		cb.setCallbackParameter(parameter);
		cb.dispatch();
	},
	add_log_line: function(name) {
		const log = document.querySelector('#' + this.ids.win + ' div[rel="' + this.rels.log + '"]');

		// row line
		const row = document.createElement('DIV');
		row.classList.add('w3-row');

		// column with pattern name
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
		const self = oBulkApplyPatternsModal;
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
	}
};
</script>
