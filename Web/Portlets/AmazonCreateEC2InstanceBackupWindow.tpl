<div id="amazon_create_ec2_instance_backup_window" class="w3-modal">
	<div class="w3-modal-content w3-animate-top w3-card-4">
		<header class="w3-container w3-green">
			<span onclick="document.getElementById('amazon_create_ec2_instance_backup_window').style.display = 'none';" class="w3-button w3-display-topright">&times;</span>
			<h2><%[ Create AWS EC2 instance backup ]%></h2>
		</header>
		<div class="w3-container w3-margin-left w3-margin-right w3-margin-top">
			<com:TActiveLabel ID="AmazonCreateEC2InstanceBackupWindowError" CSsClass="error" Display="None" />
			<p style="margin: 3px 0"><%[ In this window, you will create a backup job, configure the plugin settings, and define a fileset. ]%></p>
			<p style="margin: 3px 0"><%[ Once completed, the EC2 instance backup job will be ready to use. ]%></p>
			<div class="w3-row directive_field">
				<div class="directive_field w3-row">
					<div class="w3-col w3-quarter">
						<span>Backup job name</span>:
					</div>
					<div class="w3-col w3-threequarter directive_value">
						<com:TActiveTextBox
							ID="AmazonCreateEC2InstanceBackupName"
							CssClass="w3-input w3-border w3-twothird"
							Attributes.placeholder="ex: main-instance-ec2"
						/>
						<i class="fas fa-asterisk w3-text-red opt_req"></i>
						<com:TRequiredFieldValidator
							ValidationGroup="NewEC2InstanceDirective"
							ControlToValidate="AmazonCreateEC2InstanceBackupName"
							ErrorMessage="<%[ Field required. ]%>"
							ControlCssClass="field_invalid"
							Display="Dynamic"
						/>
						<com:TRegularExpressionValidator
							ValidationGroup="NewEC2InstanceDirective"
							RegularExpression="<%=Bacularis\Web\Modules\JobInfo::JOB_NAME_PATTERN%>"
							ControlToValidate="AmazonCreateEC2InstanceBackupName"
							ErrorMessage="<%[ Invalid value. ]%>"
							ControlCssClass="field_invalid"
							Display="Dynamic"
						/>
					</div>
				</div>
				<com:Bacularis.Web.Portlets.DirectiveTextBox
					ID="AmazonCreateEC2InstanceBackupDescription"
					DirectiveName="Description"
					Label="Description"
					ValidationGroup="NewEC2InstanceDirective"
					Required="false"
					Show="true"
					ShowResetButton="false"
					ShowRemoveButton="false"
					Attributes.placeholder="ex: My main EC2 instance backup"
					/>
				<com:Bacularis.Web.Portlets.DirectiveComboBox
					ID="AmazonCreateEC2InstanceBackupJobDefs"
					DirectiveName="JobDefs"
					Label="JobDefs"
					Show="true"
					ShowResetButton="false"
					ShowRemoveButton="false"
					Attributes.onchange="oAmazonCreateEC2InstanceBackup.set_jobdefs();"
					/>
			</div>
			<h4><%[ What to back up? ]%></h4>
			<div class="w3-row directive_field">
				<div class="w3-col w3-quarter"><%[ Backup type ]%>:</div>
				<div class="w3-col w3-threequarter bold"><%[ EC2 instance backup ]%></div>
			</div>
			<div class="w3-row directive_field">
				<div class="w3-col w3-quarter"><%[ Instances ]%>:</div>
				<div class="w3-col w3-threequarter" id="amazon_create_ec2_instance_backup_instance_ids"></div>
			</div>
			<div class="w3-row directive_field">
				<div class="w3-col w3-quarter"><%[ Backup client ]%>:</div>
				<div class="w3-col w3-threequarter bold" id="amazon_create_ec2_instance_backup_client"></div>
			</div>
			<h4><%[ Where to back up? ]%></h4>
			<div class="w3-row directive_field">
				<com:Bacularis.Web.Portlets.DirectiveOrderedListBox
					ID="AmazonCreateEC2InstanceBackupStorage"
					DirectiveName="Storage"
					Label="Storage"
					Show="true"
					ValidationGroup="NewEC2InstanceDirective"
					Required="true"
					ShowResetButton="false"
					ShowRemoveButton="false"
					/>
				<com:Bacularis.Web.Portlets.DirectiveComboBox
					ID="AmazonCreateEC2InstanceBackupPool"
					DirectiveName="Pool"
					Label="Pool"
					Show="true"
					ValidationGroup="NewEC2InstanceDirective"
					Required="true"
					ShowResetButton="false"
					ShowRemoveButton="false"
					 />
			</div>
			<h4><%[ When to back up? ]%></h4>
			<div class="w3-row directive_field">
				<com:Bacularis.Web.Portlets.DirectiveComboBox
					ID="AmazonCreateEC2InstanceBackupSchedule"
					DirectiveName="Schedule"
					Label="Schedule"
					Show="true"
					ShowResetButton="false"
					ShowRemoveButton="false"
					/>
			</div>
			<div class="w3-row directive_field">
				<div class="w3-col w3-quarter">
					<label for="<%=$this->AmazonCreateEC2InstanceBackupRunJobNow->ClientID%>">
						<%[ Run the new job now ]%>:
					</label>
				</div>
				<div class="w3-col w3-threequarter">
					<com:TActiveCheckBox
						ID="AmazonCreateEC2InstanceBackupRunJobNow"
						CssClass="w3-check w3-border"
						AutoPostBack="false"
					/>
				</div>
			</div>
			<div class="pointer bold w3-margin-bottom" onclick="$('#amazon_create_ec2_instance_backup_plugin_opts').slideToggle('fast');"><i class="fa-solid fa-wrench"></i> &nbsp;<%[ EC2 plugin options ]%></div>
			<div id="amazon_create_ec2_instance_backup_plugin_opts" style="display: none">
				<div class="w3-row directive_field">
					<div class="w3-col w3-quarter">
						<label for="<%=$this->AmazonCreateEC2InstanceBackupAccount->ClientID%>">
							<%[ AWS account ]%>:
						</label>
					</div>
					<div class="w3-col w3-threequarter">
						<com:TActiveDropDownList
							ID="AmazonCreateEC2InstanceBackupAccount"
							CssClass="w3-select w3-border w3-show-inline-block"
							AutoPostBack="false"
							Width="200px"
						/>
					</div>
				</div>
				<div class="w3-row directive_field">
					<div class="w3-col w3-quarter">
						<label for="<%=$this->AmazonCreateEC2InstanceBackupRegion->ClientID%>">
							<%[ Region ]%>:
						</label>
					</div>
					<div class="w3-col w3-threequarter">
						<com:TActiveDropDownList
							ID="AmazonCreateEC2InstanceBackupRegion"
							AutoPostBack="false"
							CssClass="w3-select w3-border w3-show-inline-block"
						/>
					</div>
				</div>
				<div class="w3-row directive_field">
					<div class="w3-col w3-quarter">
						<label for="<%=$this->AmazonCreateEC2InstanceBackupServiceEndpoint->ClientID%>">
							<%[ Service endpoints ]%>:
						</label>
					</div>
					<div class="w3-col w3-threequarter">
						<com:TActiveDropDownList
							ID="AmazonCreateEC2InstanceBackupServiceEndpoint"
							AutoPostBack="false"
							CssClass="w3-select w3-border"
						/>
					</div>
				</div>
				<div class="w3-row directive_field">
					<div class="w3-col w3-quarter">
						<label for="<%=$this->AmazonCreateEC2InstanceBackupMaxBackupWorkers->ClientID%>">
							<%[ Max. backup HTTP workers ]%>:
						</label>
					</div>
					<div class="w3-col w3-threequarter">
						<com:TActiveTextBox
							ID="AmazonCreateEC2InstanceBackupMaxBackupWorkers"
							Text="8"
							CssClass="w3-input w3-border w3-show-inline-block"
							Width="100px"
						/>
						<com:TRegularExpressionValidator
							ValidationGroup="NewEC2InstanceDirective"
							RegularExpression="\d+"
							ControlToValidate="AmazonCreateEC2InstanceBackupMaxBackupWorkers"
							ErrorMessage="<%[ Invalid value. ]%>"
							ControlCssClass="field_invalid"
							Display="Dynamic"
						/>
					</div>
				</div>
				<div class="w3-row directive_field">
					<div class="w3-col w3-quarter">
						<label for="<%=$this->AmazonCreateEC2InstanceBackupExcludeDataVolumeIds->ClientID%>">
							<%[ Exclude data volume IDs ]%> (<%[ comma separated ]%>):
						</label>
					</div>
					<div class="w3-col w3-threequarter">
						<com:TActiveTextBox
							ID="AmazonCreateEC2InstanceBackupExcludeDataVolumeIds"
							CssClass="w3-input w3-border"
							Attributes.placeholder="ex: vol-xxxx,vol-yyyy"
						/>
					</div>
				</div>
				<div class="w3-row directive_field">
					<div class="w3-col w3-quarter">
						<label for="<%=$this->AmazonCreateEC2InstanceBackupSnapshotDescription->ClientID%>">
							<%[ Snapshot description ]%>:
						</label>
					</div>
					<div class="w3-col w3-threequarter">
						<com:TActiveTextBox
							ID="AmazonCreateEC2InstanceBackupSnapshotDescription"
							CssClass="w3-input w3-border w3-show-inline-block"
							Attributes.placeholder="ex: My main EC2 instance EBS snapshots"
						/>
						<com:TRegularExpressionValidator
							ValidationGroup="NewEC2InstanceDirective"
							RegularExpression="[ -~]+"
							ControlToValidate="AmazonCreateEC2InstanceBackupSnapshotDescription"
							ErrorMessage="<%[ Invalid value. ]%>"
							ControlCssClass="field_invalid"
							Display="Dynamic"
						/>
					</div>
				</div>
				<div class="w3-row directive_field">
					<div class="w3-col w3-quarter">
						<label for="<%=$this->AmazonCreateEC2InstanceBackupSnapshotTags->ClientID%>">
							<%[ Snapshot tags ]%> (<%[ comma separated ]%>)
						</label>
					</div>
					<div class="w3-col w3-threequarter">
						<com:TActiveTextBox
							ID="AmazonCreateEC2InstanceBackupSnapshotTags"
							CssClass="w3-input w3-border"
							Attributes.placeholder="ex: TagNameA=ValueA,TagNameB=ValueB"
						/>
					</div>
				</div>
				<div class="w3-row directive_field">
					<div class="w3-col w3-quarter">
						<label for="<%=$this->AmazonCreateEC2InstanceBackupCopyTagsVolumeSnapshot->ClientID%>">
							<%[ Copy tags from the volumes to corresponding snapshots ]%>:
						</label>
					</div>
					<div class="w3-col w3-threequarter">
						<com:TActiveCheckBox
							ID="AmazonCreateEC2InstanceBackupCopyTagsVolumeSnapshot"
							CssClass="w3-check w3-border"
							AutoPostBack="false"
						/>
					</div>
				</div>
			</div>
			<div class="pointer bold" onclick="$('#amazon_create_ec2_instance_backup_advanced_opts').slideToggle('fast');"><i class="fa-solid fa-wrench"></i> &nbsp;<%[ Other job directives ]%></div>
			<div id="amazon_create_ec2_instance_backup_advanced_opts" style="display: none">
				<div class="w3-row directive_field">
					<com:Bacularis.Web.Portlets.DirectiveComboBox
						ID="AmazonCreateEC2InstanceBackupLevel"
						DirectiveName="Level"
						Label="Level"
						Show="true"
						ShowResetButton="false"
						ShowRemoveButton="false"
						/>
					<com:Bacularis.Web.Portlets.DirectiveComboBox
						ID="AmazonCreateEC2InstanceBackupMessages"
						DirectiveName="Messages"
						Label="Messages"
						Show="true"
						ValidationGroup="NewEC2InstanceDirective"
						Required="true"
						ShowResetButton="false"
						ShowRemoveButton="false"
						/>
					<com:Bacularis.Web.Portlets.DirectiveTextBox
						ID="AmazonCreateEC2InstanceBackupPriority"
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
			<button type="button" class="w3-button w3-red" onclick="document.getElementById('amazon_create_ec2_instance_backup_window').style.display = 'none';"><i class="fas fa-times"></i> &nbsp;<%[ Cancel ]%></button>
			<com:TActiveLinkButton
				ID="AmazonCreateEC2InstanceBackupSave"
				ValidationGroup="NewEC2InstanceDirective"
				CausesValidation="true"
				OnCallback="createEC2Backup"
				CssClass="w3-button w3-section w3-green w3-padding"
			>
				<i class="fa fa-plus"></i> &nbsp;<%[ Create ]%>
			</com:TActiveLinkButton>
		</footer>
	</div>
	<com:TActiveHiddenField ID="AmazonCreateEC2InstanceBackupWindowType" />
	<com:TActiveHiddenField ID="AmazonCreateEC2InstanceBackupClient" />
	<com:TActiveHiddenField ID="AmazonCreateEC2InstanceBackupInstanceIds" />
</div>
<com:TCallback
	ID="AmazonCreateEC2InstanceBackupSetJobDefs"
	OnCallback="setupJobDefs"
/>
<com:TCallback
	ID="AmazonCreateEC2InstanceBackupAPIClient"
	OnCallback="setBackupClient"
/>
<script>
var oAmazonCreateEC2InstanceBackup = {
	ids: {
		job: '<%=$this->AmazonCreateEC2InstanceBackupName->ClientID%>',
		description: '<%=$this->AmazonCreateEC2InstanceBackupDescription->Directive->ClientID%>',
		jobdefs: '<%=$this->AmazonCreateEC2InstanceBackupJobDefs->Directive->ClientID%>',
		storage: '<%=$this->AmazonCreateEC2InstanceBackupStorage->Directive->ClientID%>',
		storage_vals: '<%=$this->AmazonCreateEC2InstanceBackupStorage->DirectiveHidden->ClientID%>',
		pool: '<%=$this->AmazonCreateEC2InstanceBackupPool->Directive->ClientID%>',
		schedule: '<%=$this->AmazonCreateEC2InstanceBackupSchedule->Directive->ClientID%>',
		messages: '<%=$this->AmazonCreateEC2InstanceBackupMessages->Directive->ClientID%>',
		level: '<%=$this->AmazonCreateEC2InstanceBackupLevel->Directive->ClientID%>',
		priority: '<%=$this->AmazonCreateEC2InstanceBackupPriority->Directive->ClientID%>',
		instances: 'amazon_create_ec2_instance_backup_instance_ids',
		client: 'amazon_create_ec2_instance_backup_client',
		client_val: '<%=$this->AmazonCreateEC2InstanceBackupClient->ClientID%>',
		instance_ids_val: '<%=$this->AmazonCreateEC2InstanceBackupInstanceIds->ClientID%>',
		win: 'amazon_create_ec2_instance_backup_window'
	},
	default_values: {
		priority: 10,
		level: 'Incremental',
		messages: 'Standard'
	},
	instance_ids: [],
	fd_name: '',
	init: function() {
		this.set_default_values();
		this.get_backup_client();
	},
	open_window: function(instances) {
		this.clear_instance_form();
		this.clear_form();

		this.set_instances(instances);

		const win = document.getElementById(this.ids.win);
		win.style.display = 'block';
	},
	close_window: function() {
		const self = oAmazonCreateEC2InstanceBackup;
		const win = document.getElementById(self.ids.win);
		win.style.display = 'none';
	},
	set_instances: function(instances) {
		this.instance_ids = instances.map((item) => item.instance_id);
		const instance_ids_val = document.getElementById(this.ids.instance_ids_val);
		instance_ids_val.value = this.instance_ids.join(',');
		this.set_instance_form(instances);
	},
	set_instance_form: function(instances) {
		const vols = document.getElementById(this.ids.instances);
		const ul = document.createElement('UL');
		ul.style.margin = '3px 0';
		ul.style.paddingLeft = '0';
		ul.style.listStyle = 'none';
		let li, txt, label;
		for (const instance of instances) {
			li = document.createElement('LI');
			li.classList.add('bold');
			txt = instance.instance_id;
			if (instance.name) {
				txt += ' (' + instance.name + ')';
			}
			label = document.createTextNode(txt);
			li.appendChild(label);
			ul.appendChild(li);
		}
		vols.appendChild(ul);
	},
	clear_instance_form: function() {
		// Clear instance list
		const instances = document.getElementById(this.ids.instances);
		while (instances.firstChild) {
			instances.removeChild(instances.firstChild);
		}
	},
	set_jobdefs: function() {
		const cb = <%=$this->AmazonCreateEC2InstanceBackupSetJobDefs->ActiveControl->Javascript%>;
		cb.dispatch();
	},
	set_jobdefs_cb: function(data) {
		const self = oAmazonCreateEC2InstanceBackup;
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
		<%=$this->AmazonCreateEC2InstanceBackupStorage->ClientID%>_load_ordered_list_box();
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
		const cb = <%=$this->AmazonCreateEC2InstanceBackupAPIClient->ActiveControl->Javascript%>;
		cb.dispatch();
	},
	get_backup_client_cb: function(fd_name) {
		const self = oAmazonCreateEC2InstanceBackup;
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
	oAmazonCreateEC2InstanceBackup.init();
});
</script>
