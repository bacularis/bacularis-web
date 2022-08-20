<com:TActiveLinkButton
	CssClass="w3-button w3-green"
	OnClick="loadValues"
	Visible="<%=$this->ShowButton%>"
>
	<prop:Attributes.onclick>
		show_label_volume_window(true);
	</prop:Attributes.onclick>
	<i class="fa fa-tag"></i> &nbsp;<%[ Label volume(s) ]%>
</com:TActiveLinkButton>
<div id="label_volume" class="w3-modal">
	<div class="w3-modal-content w3-animate-top w3-card-4">
		<header class="w3-container w3-green"> 
			<span onclick="show_label_volume_window(false);" class="w3-button w3-display-topright">&times;</span>
			<h2><%[ Label volume(s) ]%> <%=$this->Storage ? Prado::localize('Storage:') . ' ' . $this->Storage : ''%></h2>
		</header>
		<div class="w3-padding">
			<com:TValidationSummary
				ID="ValidationSummary"
				CssClass="validation-error-summary"
				ValidationGroup="LabelVolumeGroup"
				AutoUpdate="true"
				Display="Dynamic"
				HeaderText="<%[ There is not possible to run selected action because: ]%>"
			/>
			<com:TRegularExpressionValidator
				ValidationGroup="LabelVolumeGroup"
				ControlToValidate="LabelName"
				ErrorMessage="<%[ Label name have to contain string value from set [0-9a-zA-Z_-]. ]%>"
				ControlCssClass="validation-error"
				Display="Dynamic"
				RegularExpression="[0-9a-zA-Z\-_]+"
			 />
			<com:TRegularExpressionValidator
				ID="SlotsLabelValidator"
				ValidationGroup="LabelVolumeGroup"
				ControlToValidate="SlotsLabel"
				ErrorMessage="<%[ Slots for label have to contain string value from set [0-9-,]. ]%>"
				ControlCssClass="validation-error"
				Display="Dynamic"
				RegularExpression="[0-9\-\,]+"
			/>
			<com:TRegularExpressionValidator
				ID="DriveLabelValidator"
				ValidationGroup="LabelVolumeGroup"
				ControlToValidate="DriveLabel"
				ErrorMessage="<%[ Drive has to contain digit value from set [0-9]. ]%>"
				ControlCssClass="validation-error"
				Display="Dynamic"
				RegularExpression="[0-9]+"
			/>
			<div class="w3-row directive_field">
				<div class="w3-col w3-half"><com:TLabel ForControl="Barcodes" Text="<%[ Use barcodes as label: ]%>" /></div>
				<div class="w3-col w3-half"><com:TActiveCheckBox ID="Barcodes" CssClass="w3-check" Attributes.onclick="set_label_volume_barcodes();"/></div>
			</div>
			<div id="label_with_name" class="w3-row directive_field">
				<div class="w3-col w3-half"><com:TLabel ForControl="LabelName" Text="<%[ Label name: ]%>" /></div>
				<div class="w3-col w3-half">
					<com:TActiveTextBox ID="LabelName" CssClass="w3-input w3-border" />
					<com:TRequiredFieldValidator
						ValidationGroup="LabelVolumeGroup"
						ControlToValidate="LabelName"
						ErrorMessage="<%[ Field required. ]%>"
						Display="Dynamic"
					>
						<prop:ClientSide.OnValidate>
							sender.enabled = !document.getElementById('<%=$this->Barcodes->ClientID%>').checked;
						</prop:ClientSide.OnValidate>
 					</com:TRequiredFieldValidator>
				</div>
			</div>
			<div id="label_with_barcodes" class="w3-row directive_field" style="display: none">
				<div class="w3-col w3-half"><com:TLabel ForControl="SlotsLabel" Text="<%[ Slots to label (ex. 4 or 1-5 or 2,4,6-10): ]%>" /></div>
				<div class="w3-col w3-half">
					<com:TActiveTextBox ID="SlotsLabel" CssClass="w3-input w3-border" Text="0" />
					<com:TRequiredFieldValidator
						ValidationGroup="LabelVolumeGroup"
						ControlToValidate="SlotsLabel"
						ErrorMessage="<%[ Field required. ]%>"
						Display="Dynamic"
					>
						<prop:ClientSide.OnValidate>
							sender.enabled = document.getElementById('<%=$this->Barcodes->ClientID%>').checked;
						</prop:ClientSide.OnValidate>
 					</com:TRequiredFieldValidator>
				</div>
			</div>
			<div class="w3-row directive_field">
				<div class="w3-col w3-half"><com:TLabel ForControl="PoolLabel" Text="<%[ Pool: ]%>" /></div>
				<div class="w3-col w3-half"><com:TActiveDropDownList ID="PoolLabel" CssClass="w3-select w3-border" /></div>
			</div>
			<div class="w3-row directive_field"<%=$this->Storage ? ' style="display: none"' : ''%>>
				<div class="w3-col w3-half"><com:TLabel ForControl="StorageLabel" Text="<%[ Storage: ]%>" /></div>
				<div class="w3-col w3-half"><com:TActiveDropDownList ID="StorageLabel" CssClass="w3-select w3-border" /></div>
			</div>
			<div class="w3-row directive_field">
				<div class="w3-col w3-half"><com:TLabel ForControl="DriveLabel" Text="<%[ Drive index: ]%>" /></div>
				<div class="w3-col w3-half">
					<com:TActiveTextBox ID="DriveLabel" CssClass="w3-input w3-border" Text="0" />
					<com:TRequiredFieldValidator
						ValidationGroup="LabelVolumeGroup"
						ControlToValidate="DriveLabel"
						ErrorMessage="<%[ Field required. ]%>"
						Display="Dynamic"
					/>
				</div>
			</div>
			<div id="label_without_barcodes" class="w3-row directive_field">
				<div class="w3-col w3-half"><com:TLabel ForControl="SlotLabel" Text="<%[ Slot number: ]%>" /></div>
				<div class="w3-col w3-half">
					<com:TActiveTextBox ID="SlotLabel" CssClass="w3-input w3-border" Text="0" />
					<com:TRequiredFieldValidator
						ValidationGroup="LabelVolumeGroup"
						ControlToValidate="SlotLabel"
						ErrorMessage="<%[ Field required. ]%>"
						Display="Dynamic"
					>
						<prop:ClientSide.OnValidate>
							sender.enabled = !document.getElementById('<%=$this->Barcodes->ClientID%>').checked;
						</prop:ClientSide.OnValidate>
					</com:TRequiredFieldValidator>
				</div>
			</div>
			<div class="w3-row directive_field">
				<div class="w3-col w3-half"><%[ Labeling status: ]%></div>
				<div class="w3-col w3-half">
					<i id="label_status_start" class="fa fa-step-forward" title="<%[ Ready to label ]%>"></i>
					<i id="label_status_loading" class="fa fa-sync w3-spin" style="display: none" title="<%[ Loading... ]%>"></i>
					<i id="label_status_finish" class="fa fa-check" style="display: none" title="<%[ Finished ]%>"></i>
				</div>
			</div>
			<div id="label_volume_log" class="w3-panel w3-card" style="display: none; max-height: 200px; overflow-x: auto;">
				<div class="w3-code notranslate">
					<pre><com:TActiveLabel ID="LabelVolumeLog" /></pre>
				</div>
			</div>
			<div class="w3-container w3-center w3-section">
				<button type="button" class="w3-button w3-red" onclick="show_label_volume_window(false);"><i class="fa fa-times"></i> &nbsp;<%[ Close ]%></button>
				<com:TActiveLinkButton
					ID="LabelButton"
					CausesValidation="true"
					ValidationGroup="LabelVolumeGroup"
					OnClick="labelVolumes"
					CssClass="w3-button w3-green"
					ClientSide.OnLoading="$('#status_label_volume_loading').css('visibility', 'visible');"
				>
					<prop:ClientSide.OnComplete>
						$('#status_label_volume_loading').css('visibility', 'hidden');
						$('#label_volume_log').show();
						var logbox = document.getElementById('label_volume_log');
						logbox.scrollTo(0, logbox.scrollHeight);
					</prop:ClientSide.OnComplete>
					<i class="fa fa-tag"></i> &nbsp;<%[ Label ]%>
				</com:TActiveLinkButton>
				<i id="status_label_volume_loading" class="fa fa-sync w3-spin" style="visibility: hidden;"></i>
			</div>
		</div>
	</div>
</div>
<com:TCallback ID="LabelVolumeOutputRefresh"
	OnCallback="refreshOutput"
>
	<prop:ClientSide.OnLoading>
		$('#status_label_volume_loading').css('visibility', 'visible');
		var logbox = document.getElementById('label_volume_log');
		if ((logbox.offsetHeight + logbox.scrollTop) === logbox.scrollHeight) {
			label_volume_logbox_scroll = true;
		} else {
			label_volume_logbox_scroll = false;
		}
	</prop:ClientSide.OnLoading>
	<prop:ClientSide.OnComplete>
		$('#status_label_volume_loading').css('visibility', 'hidden');
		if (label_volume_logbox_scroll) {
			var logbox = document.getElementById('label_volume_log');
			logbox.scrollTo(0, logbox.scrollHeight);
		}
	</prop:ClientSide.OnComplete>
</com:TCallback>
<script type="text/javascript">
var label_volume_logbox_scroll = false;
function set_label_volume_barcodes(force_barcodes) {
	var chkb = document.getElementById('<%=$this->Barcodes->ClientID%>');
	if (force_barcodes) {
		chkb.checked = true;
		chkb.setAttribute('disabled', 'disabled');
	}
	var name_el = document.getElementById('label_with_name');
	var with_barcodes_el = document.getElementById('label_with_barcodes');
	var without_barcodes_el = document.getElementById('label_without_barcodes');
	if (chkb.checked) {
		name_el.style.display = 'none';
		without_barcodes_el.style.display = 'none';
		with_barcodes_el.style.display = 'block';
	} else {
		without_barcodes_el.style.display = 'block';
		with_barcodes_el.style.display = 'none';
		name_el.style.display = 'block';
	}
}

function set_labeling_status(status) {
	var start = document.getElementById('label_status_start');
	var loading = document.getElementById('label_status_loading');
	var finish = document.getElementById('label_status_finish');
	if (status === 'finish') {
		start.style.display = 'none';
		loading.style.display = 'none';
		finish.style.display = '';
	} else if (status === 'loading') {
		start.style.display = 'none';
		loading.style.display = '';
		finish.style.display = 'none';
	} else if (status === 'start') {
		start.style.display = '';
		loading.style.display = 'none';
		finish.style.display = 'none';
	}
}

function label_volume_output_refresh(out_id) {
	setTimeout(function() {
		set_label_volume_output(out_id)
	}, 3000);
}

function set_label_volume_output(out_id) {
	var cb = <%=$this->LabelVolumeOutputRefresh->ActiveControl->Javascript%>;
	cb.setCallbackParameter(out_id);
	cb.dispatch();
}

function show_label_volume_window(show) {
	var logbox = document.getElementById('<%=$this->LabelVolumeLog->ClientID%>');
	logbox.innerHTML = '';
	var logbox_container = document.getElementById('label_volume_log');
	logbox_container.style.display = 'none';
	set_labeling_status('start');
	document.getElementById('label_volume').style.display = show ? 'block' : 'none';
}

<%=$this->getBarcodeLabel() ? 'set_label_volume_barcodes(true);' : ''%>
</script>
