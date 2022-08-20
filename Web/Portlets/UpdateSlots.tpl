<com:TActiveLinkButton
	CssClass="w3-button w3-green"
	OnClick="loadValues"
	Visible="<%=$this->ShowButton%>"
>
	<prop:Attributes.onclick>
		show_update_slots_window();
	</prop:Attributes.onclick>
	<i class="fa fa-retweet"></i> &nbsp;<%[ Update slots ]%>
</com:TActiveLinkButton>
</button>
<div id="update_slots" class="w3-modal">
	<div class="w3-modal-content w3-animate-top w3-card-4">
		<header class="w3-container w3-green"> 
			<span onclick="document.getElementById('update_slots').style.display='none'" class="w3-button w3-display-topright">&times;</span>
			<h2><%[ Update slots ]%></h2>
		</header>
		<div class="w3-padding">
			<com:TValidationSummary
				ID="ValidationSummary"
				CssClass="validation-error-summary"
				ValidationGroup="UpdateSlotsGroup"
				AutoUpdate="true"
				Display="Dynamic"
				HeaderText="<%[ There is not possible to run selected action because: ]%>"
			/>
			<com:TRegularExpressionValidator
				ID="SlotsUpdateValidator"
				ValidationGroup="UpdateSlotsGroup"
				ControlToValidate="SlotsUpdate"
				ErrorMessage="<%[ Slots for update have to contain string value from set [0-9-,]. ]%>"
				ControlCssClass="validation-error"
				Display="Dynamic"
				RegularExpression="[0-9\-\,]+"
			/>
			<com:TRegularExpressionValidator
				ID="DriveUpdateValidator"
				ValidationGroup="UpdateSlotsGroup"
				ControlToValidate="DriveUpdate"
				ErrorMessage="<%[ Drive has to contain digit value from set [0-9]. ]%>"
				ControlCssClass="validation-error"
				Display="Dynamic"
				RegularExpression="[0-9]+"
			/>
			<div class="w3-row directive_field">
				<div class="w3-col w3-half"><com:TLabel ForControl="Barcodes" Text="<%[ Update slots using barcodes ]%>" /></div>
				<div class="w3-col w3-half"><com:TActiveCheckBox ID="Barcodes" CssClass="w3-check" Checked="true" Attributes.onclick="set_update_slots_barcodes();" /></div>
			</div>
			<div class="w3-row directive_field"<%=$this->Storage ? ' style="display: none"' : ''%>>
				<div class="w3-col w3-half"><com:TLabel ForControl="StorageUpdate" Text="<%[ Storage: ]%>" /></div>
				<div class="w3-col w3-half"><com:TActiveDropDownList ID="StorageUpdate" CssClass="w3-select w3-border" /></div>
			</div>
			<div class="w3-row directive_field">
				<div class="w3-col w3-half"><com:TLabel ForControl="SlotsUpdate" Text="<%[ Slots to update (ex. 4 or 1-5 or 2,4,6-10): ]%>" /></div>
				<div class="w3-col w3-half">
					<com:TActiveTextBox ID="SlotsUpdate" CssClass="w3-input w3-border" Text="0" />
					<com:TRequiredFieldValidator
						ValidationGroup="UpdateSlotsGroup"
						ControlToValidate="SlotsUpdate"
						ErrorMessage="<%[ Field required. ]%>"
						Display="Dynamic"
					/>
				</div>
			</div>
			<div class="w3-row directive_field">
				<div class="w3-col w3-half"><com:TLabel ForControl="DriveUpdate" Text="<%[ Drive number: ]%>" /></div>
				<div class="w3-col w3-half">
					<com:TActiveTextBox ID="DriveUpdate" CssClass="w3-input w3-border" Text="0" />
					<com:TRequiredFieldValidator
						ValidationGroup="UpdateSlotsGroup"
						ControlToValidate="DriveUpdate"
						ErrorMessage="<%[ Field required. ]%>"
					/>
				</div>
			</div>
			<div class="w3-row directive_field">
				<div class="w3-col w3-half"><%[ Updating status: ]%></div>
				<div class="w3-col w3-half">
					<i id="update_slots_status_start" class="fa fa-step-forward" title="<%[ Ready to update ]%>"></i>
					<i id="update_slots_status_loading" class="fa fa-sync w3-spin" style="display: none" title="<%[ Updating... ]%>"></i>
					<i id="update_slots_status_finish" class="fa fa-check" style="display: none" title="<%[ Finished ]%>"></i>
				</div>
			</div>
			<div id="update_slots_log" class="w3-panel w3-card" style="display: none; max-height: 200px; overflow-x: auto;">
				<div class="w3-code notranslate">
					<pre><com:TActiveLabel ID="UpdateSlotsLog" /></pre>
				</div>
			</div>
			<div class="w3-container w3-center w3-section">
				<button type="button" class="w3-button w3-red" onclick="document.getElementById('update_slots').style.display='none';"><i class="fa fa-times"></i> &nbsp;<%[ Close ]%></button>
				<com:TActiveLinkButton
					ID="UpdateButton"
					CausesValidation="true"
					ValidationGroup="UpdateSlotsGroup"
					OnClick="update"
					CssClass="w3-button w3-green"
					ClientSide.OnLoading="$('#status_update_slots_loading').show();"
				>
					<prop:ClientSide.OnComplete>
						$('#status_update_slots_loading').css('visibility', 'hidden');
						$('#update_slots_log').show();
						var logbox = document.getElementById('update_slots_log');
						logbox.scrollTo(0, logbox.scrollHeight);
					</prop:ClientSide.OnComplete>
					<i class="fa fa-retweet"></i> &nbsp;<%[ Update slots ]%>
				</com:TActiveLinkButton>
				<i id="status_update_slots_loading" class="fa fa-sync w3-spin" style="visibility: hidden;"></i>
			</div>
		</div>
	</div>
</div>
<com:TCallback ID="UpdateSlotsOutputRefresh"
	OnCallback="refreshOutput"
>
	<prop:ClientSide.OnLoading>
		$('#status_update_slots_loading').css('visibility', 'visible');
		var logbox = document.getElementById('update_slots_log');
		if ((logbox.offsetHeight + logbox.scrollTop) === logbox.scrollHeight) {
			update_slots_logbox_scroll = true;
		} else {
			update_slots_logbox_scroll = false;
		}
	</prop:ClientSide.OnLoading>
	<prop:ClientSide.OnComplete>
		$('#status_update_slots_loading').css('visibility', 'hidden');
		if (update_slots_logbox_scroll) {
			var logbox = document.getElementById('update_slots_log');
			logbox.scrollTo(0, logbox.scrollHeight);
		}
	</prop:ClientSide.OnComplete>
</com:TCallback>
<script type="text/javascript">
var update_slots_logbox_scroll = false;
function set_update_slots_barcodes(force_barcodes) {
	var chkb = document.getElementById('<%=$this->Barcodes->ClientID%>');
	if (force_barcodes) {
		chkb.checked = true;
		chkb.setAttribute('disabled', 'disabled');
	}
}
function set_update_slots(force) {
	var chkb = document.getElementById('<%=$this->Barcodes->ClientID%>');
	if (force) {
		chkb.checked = false;
		chkb.setAttribute('disabled', 'disabled');
	}
}
function set_updating_status(status) {
	var start = document.getElementById('update_slots_status_start');
	var loading = document.getElementById('update_slots_status_loading');
	var finish = document.getElementById('update_slots_status_finish');
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

function update_slots_output_refresh(out_id) {
	setTimeout(function() {
		set_update_slots_output(out_id)
	}, 3000);
}

function set_update_slots_output(out_id) {
	var cb = <%=$this->UpdateSlotsOutputRefresh->ActiveControl->Javascript%>;
	cb.setCallbackParameter(out_id);
	cb.dispatch();
}
function show_update_slots_window() {
	var logbox = document.getElementById('<%=$this->UpdateSlotsLog->ClientID%>');
	logbox.innerHTML = '';
	var logbox_container = document.getElementById('update_slots_log');
	logbox_container.style.display = 'none';
	set_updating_status('start');
	document.getElementById('update_slots').style.display = 'block';
}
<%=$this->getBarcodeUpdate() ? 'set_update_slots_barcodes(true);' : ''%>
</script>
