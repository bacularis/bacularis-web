<div class="w3-container">
	<com:TValidationSummary
		ID="ValidationSummary"
		CssClass="validation-error-summary"
		ValidationGroup="VolumeGroup"
		AutoUpdate="true"
		Display="Dynamic"
		HeaderText="<%[ There is not possible to run selected action because: ]%>"
	/>
	<div class="w3-row w3-section directive_field">
		<div class="w3-col w3-quarter"><com:TLabel ForControl="VolumeStatus" Text="<%[ Volume status: ]%>" /></div>
		<div class="w3-half">
			<com:TActiveDropDownList ID="VolumeStatus" AutoPostBack="false" CssClass="w3-select w3-border" />
			<i class="fa fa-asterisk w3-text-red"></i>
		</div>
	</div>
	<div class="w3-row w3-section directive_field">
		<div class="w3-col w3-quarter"><com:TLabel ForControl="Pool" Text="<%[ Pool: ]%>" /></div>
		<div class="w3-half">
			<com:TActiveDropDownList ID="Pool" AutoPostBack="false" CssClass="w3-select w3-border" />
			<i class="fa fa-asterisk w3-text-red"></i>
		</div>
	</div>
	<div class="w3-row w3-section">
		<com:Bacularis.Web.Portlets.DirectiveTimePeriod
			ID="RetentionPeriod"
			DirectiveName="VolumeRetention"
			Label="<%[ Vol. retention ]%>"
			ValidationGroup="VolumeGroup"
			Show="true"
			Required="true"
			ShowResetButton="false"
			ShowRemoveButton="false"
			TimeFormat="hour"
		/>
	</div>
	<div class="w3-row w3-section">
		<com:Bacularis.Web.Portlets.DirectiveTimePeriod
			ID="UseDuration"
			DirectiveName="UseDuration"
			Label="<%[ Vol. use duration ]%>"
			ValidationGroup="VolumeGroup"
			Show="true"
			Required="true"
			ShowResetButton="false"
			ShowRemoveButton="false"
			TimeFormat="hour"
		/>
	</div>
	<div class="w3-row w3-section">
		<div class="w3-col w3-quarter"><%[ Max. vol. jobs: ]%></div>
		<div class="w3-half">
			<com:TActiveTextBox ID="MaxVolJobs" AutoPostBack="false" CssClass="w3-input w3-border w3-show-inline-block smallbox" />
			<i class="fa fa-asterisk w3-text-red"></i>
			<com:TDataTypeValidator
				ValidationGroup="VolumeGroup"
				ControlToValidate="MaxVolJobs"
				ErrorMessage="<%[ Max. vol. jobs value must be integer. ]%>"
				Display="None"
				DataType="Integer"
			/>
			<com:TRequiredFieldValidator
				ValidationGroup="VolumeGroup"
				Display="Dynamic"
				ControlToValidate="MaxVolJobs"
				Text="<%[ Field required. ]%>"
			/>
		</div>
	</div>
	<div class="w3-row w3-section">
		<div class="w3-col w3-quarter"><%[ Max. vol. files: ]%></div>
		<div class="w3-half">
			<com:TActiveTextBox ID="MaxVolFiles" AutoPostBack="false" CssClass="w3-input w3-border w3-show-inline-block smallbox" />
			<i class="fa fa-asterisk w3-text-red"></i>
			<com:TDataTypeValidator
				ValidationGroup="VolumeGroup"
				ControlToValidate="MaxVolFiles"
				ErrorMessage="<%[ Max. vol. files value must be integer. ]%>"
				Display="None"
				DataType="Integer"
			/>
			<com:TRequiredFieldValidator
				ValidationGroup="VolumeGroup"
				Display="Dynamic"
				ControlToValidate="MaxVolFiles"
				Text="<%[ Field required. ]%>"
			/>
		</div>
	</div>
	<div class="w3-row w3-section">
		<com:Bacularis.Web.Portlets.DirectiveSize
			ID="MaxVolBytes"
			DirectiveName="MaxVolBytes"
			Label="<%[ Max. vol. bytes ]%>"
			DefaultValue="0"
			Show="true"
			ShowResetButton="false"
			ShowRemoveButton="false"
		/>
	</div>
	<div class="w3-row w3-section">
		<div class="w3-col w3-quarter"><%[ Slot number: ]%></div>
		<div class="w3-half">
			<com:TActiveTextBox ID="Slot" AutoPostBack="false" CssClass="w3-input w3-border w3-show-inline-block smallbox" />
			<i class="fa fa-asterisk w3-text-red"></i>
			<com:TDataTypeValidator
				ValidationGroup="VolumeGroup"
				ControlToValidate="Slot"
				ErrorMessage="<%[ Slot value must be integer. ]%>"
				Display="None"
				DataType="Integer"
			/>
			<com:TRequiredFieldValidator
				ValidationGroup="VolumeGroup"
				Display="Dynamic"
				ControlToValidate="Slot"
				Text="<%[ Field required. ]%>"
			/>
		</div>
	</div>
	<div class="w3-row w3-section">
		<div class="w3-col w3-quarter"><%[ Recycle: ]%></div>
		<div class="w3-half">
			<com:TActiveCheckBox ID="Recycle" AutoPostBack="false" CssClass="w3-check" />
			<i class="fa fa-asterisk w3-text-red"></i>
		</div>
	</div>
	<div class="w3-row w3-section">
		<div class="w3-col w3-quarter"><%[ Enabled: ]%></div>
		<div class="w3-half">
			<com:TActiveCheckBox ID="Enabled" AutoPostBack="false" CssClass="w3-check" />
			<i class="fa fa-asterisk w3-text-red"></i>
		</div>
	</div>
	<div class="w3-row w3-section">
		<div class="w3-col w3-quarter"><%[ InChanger: ]%></div>
		<div class="w3-half">
			<com:TActiveCheckBox ID="InChanger" AutoPostBack="false" CssClass="w3-check" />
			<i class="fa fa-asterisk w3-text-red"></i>
		</div>
	</div>
	<div id="volume_config_log" class="w3-panel w3-card" style="display: none">
		<div class="w3-code notranslate">
			<pre><com:TActiveLabel ID="VolumeConfigLog" /></pre>
		</div>
	</div>
	<div class="w3-container w3-center">
		<com:TActiveLinkButton
			ValidationGroup="VolumeGroup"
			CausesValidation="true"
			CssClass="w3-button w3-green"
			OnClick="updateVolume"
			ClientSide.OnLoading="$('#status_volume_loading').show();"
			ClientSide.OnSuccess="$('#status_volume_loading').hide();"
			Attributes.onclick="<%=$this->DisplayLog ? 'true' : 'false'%> ? document.getElementById('volume_config_log').style.display = '' : '';"
		>
			<prop:Text>
				<i class="fa fa-save"></i> &nbsp;<%=Prado::localize('Save')%>
			</prop:Text>
			<prop:ClientSide.OnComplete>
				Formatters.set_formatters();
				<%=$this->SaveVolumeActionOk%>
			</prop:ClientSide.OnComplete>
		</com:TActiveLinkButton>
		<i id="status_volume_loading" class="fa fa-sync w3-spin" style="display: none; vertical-align: super;"></i>
	</div>
</div>
