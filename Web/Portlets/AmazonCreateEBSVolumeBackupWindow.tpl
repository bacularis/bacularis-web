<div id="amazon_create_ebs_volume_backup_window" class="w3-modal">
	<div class="w3-modal-content w3-animate-top w3-card-4">
		<header class="w3-container w3-green">
			<span onclick="document.getElementById('amazon_create_ebs_volume_backup_window').style.display = 'none';" class="w3-button w3-display-topright">&times;</span>
			<h2><%[ Create AWS EBS volume backup ]%></h2>
		</header>
		<div class="w3-container w3-margin-left w3-margin-right w3-margin-top">
			<com:TActiveLabel ID="AmazonCreateEBSVolumeBackupWindowError" CSsClass="error" Display="None" />
			<p style="margin: 3px 0"><%[ In this window, you will create a backup job, configure the plugin settings, and define a fileset. ]%></p>
			<p style="margin: 3px 0"><%[ Once completed, the EBS volume backup job will be ready to use. ]%></p>
			<div class="w3-row directive_field">
				<div class="directive_field w3-row">
					<div class="w3-col w3-quarter">
						<span>Backup job name</span>:
					</div>
					<div class="w3-col w3-threequarter directive_value">
						<com:TActiveTextBox
							ID="AmazonCreateEBSVolumeBackupName"
							CssClass="w3-input w3-border w3-twothird"
							Attributes.placeholder="ex: main-vol-ec2"
						/>
						<i class="fas fa-asterisk w3-text-red opt_req"></i>
						<com:TRequiredFieldValidator
							ValidationGroup="NewEBSVolumeDirective"
							ControlToValidate="AmazonCreateEBSVolumeBackupName"
							ErrorMessage="<%[ Field required. ]%>"
							ControlCssClass="field_invalid"
							Display="Dynamic"
						/>
						<com:TRegularExpressionValidator
							ValidationGroup="NewEBSVolumeDirective"
							RegularExpression="<%=Bacularis\Web\Modules\JobInfo::JOB_NAME_PATTERN%>"
							ControlToValidate="AmazonCreateEBSVolumeBackupName"
							ErrorMessage="<%[ Invalid value. ]%>"
							ControlCssClass="field_invalid"
							Display="Dynamic"
						/>
					</div>
				</div>
				<com:Bacularis.Web.Portlets.DirectiveTextBox
					ID="AmazonCreateEBSVolumeBackupDescription"
					DirectiveName="Description"
					Label="Description"
					ValidationGroup="NewEBSVolumeDirective"
					Required="false"
					Show="true"
					ShowResetButton="false"
					ShowRemoveButton="false"
					Attributes.placeholder="ex: My main EC2 volume backup"
					/>
				<com:Bacularis.Web.Portlets.DirectiveComboBox
					ID="AmazonCreateEBSVolumeBackupJobDefs"
					DirectiveName="JobDefs"
					Label="JobDefs"
					Show="true"
					ShowResetButton="false"
					ShowRemoveButton="false"
					Attributes.onchange="oAmazonCreateEBSVolumeBackup.set_jobdefs();"
					/>
			</div>
			<h4><%[ What to back up? ]%></h4>
			<div class="w3-row directive_field">
				<div class="w3-col w3-quarter"><%[ Backup type ]%>:</div>
				<div class="w3-col w3-threequarter bold"><%[ EBS volume backup ]%></div>
			</div>
			<div class="w3-row directive_field">
				<div class="w3-col w3-quarter"><%[ Volumes ]%>:</div>
				<div class="w3-col w3-threequarter" id="amazon_create_ebs_volume_backup_volume_ids"></div>
			</div>
			<div class="w3-row directive_field">
				<div class="w3-col w3-quarter"><%[ Backup client ]%>:</div>
				<div class="w3-col w3-threequarter bold" id="amazon_create_ebs_volume_backup_client"></div>
			</div>
			<h4><%[ Where to back up? ]%></h4>
			<div class="w3-row directive_field">
				<com:Bacularis.Web.Portlets.DirectiveOrderedListBox
					ID="AmazonCreateEBSVolumeBackupStorage"
					DirectiveName="Storage"
					Label="Storage"
					Show="true"
					ValidationGroup="NewEBSVolumeDirective"
					Required="true"
					ShowResetButton="false"
					ShowRemoveButton="false"
					/>
				<com:Bacularis.Web.Portlets.DirectiveComboBox
					ID="AmazonCreateEBSVolumeBackupPool"
					DirectiveName="Pool"
					Label="Pool"
					Show="true"
					ValidationGroup="NewEBSVolumeDirective"
					Required="true"
					ShowResetButton="false"
					ShowRemoveButton="false"
					 />
			</div>
			<h4><%[ When to back up? ]%></h4>
			<div class="w3-row directive_field">
				<com:Bacularis.Web.Portlets.DirectiveComboBox
					ID="AmazonCreateEBSVolumeBackupSchedule"
					DirectiveName="Schedule"
					Label="Schedule"
					Show="true"
					ShowResetButton="false"
					ShowRemoveButton="false"
					/>
			</div>
			<div class="w3-row directive_field">
				<div class="w3-col w3-quarter">
					<label for="<%=$this->AmazonCreateEBSVolumeBackupRunJobNow->ClientID%>">
						<%[ Run the new job now ]%>:
					</label>
				</div>
				<div class="w3-col w3-threequarter">
					<com:TActiveCheckBox
						ID="AmazonCreateEBSVolumeBackupRunJobNow"
						CssClass="w3-check w3-border"
						AutoPostBack="false"
					/>
				</div>
			</div>
			<div class="pointer bold w3-margin-bottom" onclick="$('#amazon_create_ebs_volume_backup_plugin_opts').slideToggle('fast');"><i class="fa-solid fa-wrench"></i> &nbsp;<%[ EC2 plugin options ]%></div>
			<div id="amazon_create_ebs_volume_backup_plugin_opts">
				<div class="w3-row directive_field">
					<div class="w3-col w3-quarter">
						<label for="<%=$this->AmazonCreateEBSVolumeBackupAccount->ClientID%>">
							<%[ AWS account ]%>:
						</label>
					</div>
					<div class="w3-col w3-threequarter">
						<com:TActiveDropDownList
							ID="AmazonCreateEBSVolumeBackupAccount"
							CssClass="w3-select w3-border w3-show-inline-block"
							AutoPostBack="false"
							Width="200px"
						/>
						<i class="fas fa-asterisk w3-text-red opt_req"></i>
						<com:TRequiredFieldValidator
							ValidationGroup="NewEBSVolumeDirective"
							ControlToValidate="AmazonCreateEBSVolumeBackupAccount"
							ErrorMessage="<%[ Field required. ]%>"
							ControlCssClass="field_invalid"
							Display="Dynamic"
						/>
					</div>
				</div>
				<div class="w3-row directive_field">
					<div class="w3-col w3-quarter">
						<label for="<%=$this->AmazonCreateEBSVolumeBackupRegion->ClientID%>">
							<%[ Region ]%>:
						</label>
					</div>
					<div class="w3-col w3-threequarter">
						<com:TActiveDropDownList
							ID="AmazonCreateEBSVolumeBackupRegion"
							AutoPostBack="false"
							CssClass="w3-select w3-border w3-show-inline-block"
						/>
						<i class="fas fa-asterisk w3-text-red opt_req"></i>
						<com:TRequiredFieldValidator
							ValidationGroup="NewEBSVolumeDirective"
							ControlToValidate="AmazonCreateEBSVolumeBackupRegion"
							ErrorMessage="<%[ Field required. ]%>"
							ControlCssClass="field_invalid"
							Display="Dynamic"
						/>
					</div>
				</div>
				<div class="w3-row directive_field">
					<div class="w3-col w3-quarter">
						<label for="<%=$this->AmazonCreateEBSVolumeBackupServiceEndpoint->ClientID%>">
							<%[ Service endpoints ]%>:
						</label>
					</div>
					<div class="w3-col w3-threequarter">
						<com:TActiveDropDownList
							ID="AmazonCreateEBSVolumeBackupServiceEndpoint"
							AutoPostBack="false"
							CssClass="w3-select w3-border"
						/>
					</div>
				</div>
				<div class="w3-row directive_field">
					<div class="w3-col w3-quarter">
						<label for="<%=$this->AmazonCreateEBSVolumeBackupMaxBackupWorkers->ClientID%>">
							<%[ Max. backup HTTP workers ]%>:
						</label>
					</div>
					<div class="w3-col w3-threequarter">
						<com:TActiveTextBox
							ID="AmazonCreateEBSVolumeBackupMaxBackupWorkers"
							Text="8"
							CssClass="w3-input w3-border w3-show-inline-block"
							Width="100px"
						/>
						<com:TRegularExpressionValidator
							ValidationGroup="NewEBSVolumeDirective"
							RegularExpression="\d+"
							ControlToValidate="AmazonCreateEBSVolumeBackupMaxBackupWorkers"
							ErrorMessage="<%[ Invalid value. ]%>"
							ControlCssClass="field_invalid"
							Display="Dynamic"
						/>
					</div>
				</div>
			</div>
			<div class="pointer bold" onclick="$('#amazon_create_ebs_volume_backup_advanced_opts').slideToggle('fast');"><i class="fa-solid fa-wrench"></i> &nbsp;<%[ Other job directives ]%></div>
			<div id="amazon_create_ebs_volume_backup_advanced_opts" style="display: none">
				<div class="w3-row directive_field">
					<com:Bacularis.Web.Portlets.DirectiveComboBox
						ID="AmazonCreateEBSVolumeBackupLevel"
						DirectiveName="Level"
						Label="Level"
						Show="true"
						ShowResetButton="false"
						ShowRemoveButton="false"
						/>
					<com:Bacularis.Web.Portlets.DirectiveComboBox
						ID="AmazonCreateEBSVolumeBackupMessages"
						DirectiveName="Messages"
						Label="Messages"
						Show="true"
						ValidationGroup="NewEBSVolumeDirective"
						Required="true"
						ShowResetButton="false"
						ShowRemoveButton="false"
						/>
					<com:Bacularis.Web.Portlets.DirectiveTextBox
						ID="AmazonCreateEBSVolumeBackupPriority"
						DirectiveName="Priority"
						DefaultValue="10"
						Label="Priority"
						Show="true"
						ShowResetButton="false"
						ShowRemoveButton="false"
						 />
				</div>
			</div>
		</div>
		<footer class="w3-container w3-center">
			<button type="button" class="w3-button w3-red" onclick="document.getElementById('amazon_create_ebs_volume_backup_window').style.display = 'none';"><i class="fas fa-times"></i> &nbsp;<%[ Cancel ]%></button>
			<com:TActiveLinkButton
				ID="AmazonCreateEBSVolumeBackupSave"
				ValidationGroup="NewEBSVolumeDirective"
				CausesValidation="true"
				OnCallback="createEBSBackup"
				CssClass="w3-button w3-section w3-green w3-padding"
			>
				<i class="fa fa-plus"></i> &nbsp;<%[ Create ]%>
			</com:TActiveLinkButton>
		</footer>
	</div>
	<com:TActiveHiddenField ID="AmazonCreateEBSVolumeBackupWindowType" />
	<com:TActiveHiddenField ID="AmazonCreateEBSVolumeBackupClient" />
	<com:TActiveHiddenField ID="AmazonCreateEBSVolumeBackupVolumeIds" />
</div>
<com:TCallback
	ID="AmazonCreateEBSVolumeBackupSetJobDefs"
	OnCallback="setupJobDefs"
/>
<com:TCallback
	ID="AmazonCreateEBSVolumeBackupAPIClient"
	OnCallback="setBackupClient"
/>
<script>
var oAmazonCreateEBSVolumeBackup = {
	ids: {
		job: '<%=$this->AmazonCreateEBSVolumeBackupName->ClientID%>',
		description: '<%=$this->AmazonCreateEBSVolumeBackupDescription->Directive->ClientID%>',
		jobdefs: '<%=$this->AmazonCreateEBSVolumeBackupJobDefs->Directive->ClientID%>',
		storage: '<%=$this->AmazonCreateEBSVolumeBackupStorage->Directive->ClientID%>',
		storage_vals: '<%=$this->AmazonCreateEBSVolumeBackupStorage->DirectiveHidden->ClientID%>',
		pool: '<%=$this->AmazonCreateEBSVolumeBackupPool->Directive->ClientID%>',
		schedule: '<%=$this->AmazonCreateEBSVolumeBackupSchedule->Directive->ClientID%>',
		messages: '<%=$this->AmazonCreateEBSVolumeBackupMessages->Directive->ClientID%>',
		level: '<%=$this->AmazonCreateEBSVolumeBackupLevel->Directive->ClientID%>',
		priority: '<%=$this->AmazonCreateEBSVolumeBackupPriority->Directive->ClientID%>',
		volumes: 'amazon_create_ebs_volume_backup_volume_ids',
		client: 'amazon_create_ebs_volume_backup_client',
		client_val: '<%=$this->AmazonCreateEBSVolumeBackupClient->ClientID%>',
		volume_ids_val: '<%=$this->AmazonCreateEBSVolumeBackupVolumeIds->ClientID%>',
		win: 'amazon_create_ebs_volume_backup_window'
	},
	default_values: {
		priority: 10,
		level: 'Incremental',
		messages: 'Standard'
	},
	volume_ids: [],
	fd_name: '',
	init: function() {
		this.set_default_values();
		this.get_backup_client();
	},
	open_window: function(volumes) {
		this.clear_volume_form();
		this.clear_form();

		this.set_volumes(volumes);

		const win = document.getElementById(this.ids.win);
		win.style.display = 'block';
	},
	close_window: function() {
		const self = oAmazonCreateEBSVolumeBackup;
		const win = document.getElementById(self.ids.win);
		win.style.display = 'none';
	},
	set_volumes: function(volumes) {
		this.volume_ids = volumes.map((item) => item.volume_id);
		const volume_ids_val = document.getElementById(this.ids.volume_ids_val);
		volume_ids_val.value = this.volume_ids.join(',');
		this.set_volume_form(volumes);
	},
	set_volume_form: function(volumes) {
		const vols = document.getElementById(this.ids.volumes);
		const ul = document.createElement('UL');
		ul.style.margin = '3px 0';
		ul.style.paddingLeft = '0';
		ul.style.listStyle = 'none';
		let li, txt, label;
		for (const volume of volumes) {
			li = document.createElement('LI');
			li.classList.add('bold');
			txt = volume.volume_id;
			if (volume.name) {
				txt += ' (' + volume.name + ')';
			}
			label = document.createTextNode(txt);
			li.appendChild(label);
			ul.appendChild(li);
		}
		vols.appendChild(ul);
	},
	clear_volume_form: function() {
		// Clear volume list
		const volumes = document.getElementById(this.ids.volumes);
		while (volumes.firstChild) {
			volumes.removeChild(volumes.firstChild);
		}
	},
	set_jobdefs: function() {
		const cb = <%=$this->AmazonCreateEBSVolumeBackupSetJobDefs->ActiveControl->Javascript%>;
		cb.dispatch();
	},
	set_jobdefs_cb: function(data) {
		const self = oAmazonCreateEBSVolumeBackup;
		self.clear_form();
		const resources = {
			storage: document.getElementById(self.ids.storage),
			pool: document.getElementById(self.ids.pool),
			schedule: document.getElementById(self.ids.schedule),
			level: document.getElementById(self.ids.level),
			messages: document.getElementById(self.ids.messages),
			priority: document.getElementById(self.ids.priority)
		};
		let directive;
		for (const d in data) {
			directive = d.toLowerCase();
			if (resources.hasOwnProperty(directive)) {
				if (d == 'Storage') {
					self.set_storage(resources[directive], data[d]);
				} else {
					resources[directive].value = data[d];
				}
			}
		}
	},
	set_storage: function(select, values) {
		OUTER:
		for (const opt of select.options) {
			opt.selected = false;
			INNER:
			for (const storage of values) {
				if (opt.value != storage) {
					continue INNER;
				}
				opt.selected = true;
				continue OUTER;
			}
		}
		storage_vals = document.getElementById(this.ids.storage_vals);
		storage_vals.value = values.join('!');
		<%=$this->AmazonCreateEBSVolumeBackupStorage->ClientID%>_load_ordered_list_box();
	},
	clear_form: function() {
		// Clear directive fields
		[
			document.getElementById(this.ids.pool),
			document.getElementById(this.ids.schedule),
			document.getElementById(this.ids.level),
			document.getElementById(this.ids.messages),
			document.getElementById(this.ids.priority)
		].forEach((el) => {
			el.value = '';
		});

		// Clear storage list box
		oDirectiveOrderedListBox.clear_selection(this.ids.storage, this.ids.storage_vals);

		// Set defaults
		this.set_default_values();
	},
	set_default_values: function() {
		const messages = document.getElementById(this.ids.messages);
		messages.value = this.default_values.messages;
		if (messages.value != this.default_values.messages) {
			// fall back if default messages does not exist
			messages.selectedIndex = 0;
		}
		const priority = document.getElementById(this.ids.priority);
		priority.value = this.default_values.priority;
		const level = document.getElementById(this.ids.level);
		level.value = this.default_values.level;
	},
	get_backup_client: function() {
		const cb = <%=$this->AmazonCreateEBSVolumeBackupAPIClient->ActiveControl->Javascript%>;
		cb.dispatch();
	},
	get_backup_client_cb: function(fd_name) {
		const self = oAmazonCreateEBSVolumeBackup;
		self.fd_name = fd_name;
		self.set_backup_client_form(fd_name);
	},
	set_backup_client_form: function(fd_name) {
		const client_val = document.getElementById(this.ids.client_val);
		client_val.value = fd_name;
		const client = document.getElementById(this.ids.client);
		client.textContent = fd_name;
	}
};
$(() => {
	oAmazonCreateEBSVolumeBackup.init();
});
</script>
