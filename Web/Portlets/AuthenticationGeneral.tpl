<div>
	<h4><%[ General settings ]%></h4>
	<p><%[ Default access setting for logged in users not defined in Bacularis Web: ]%></p>
	<div class="w3-container w3-row w3-padding opt_row">
		<div class="w3-col" style="width: 40px">
			<com:TRadioButton
				ID="GeneralDefaultNoAccess"
				GroupName="GeneralDefault"
				CssClass="w3-radio"
				Attributes.onclick="$('div[rel=general_option]').hide();"
			/>
		</div>
		<label for="<%=$this->GeneralDefaultNoAccess->ClientID%>"><%[ No access ]%></label>
	</div>
	<div class="w3-container w3-row w3-padding opt_row">
		<div class="w3-col" style="width: 40px">
			<com:TRadioButton
				ID="GeneralDefaultAccess"
				GroupName="GeneralDefault"
				CssClass="w3-radio"
				Attributes.onclick="$('div[rel=general_option]').hide(); $('#general_options_default_access').show();"
			/>
		</div>
		<label for="<%=$this->GeneralDefaultAccess->ClientID%>"><%[ Access with default settings ]%></label>
	</div>
	<div id="general_options_default_access" class="w3-container w3-margin-left" rel="general_option" style="display: <%=$this->GeneralDefaultAccess->Checked ? 'block' : 'none'%>">
		<div class="w3-half">
			<div class="w3-container w3-row w3-block">
				<div class="w3-third w3-col">
					<%[ Default role: ]%>
				</div>
				<div class="w3-twothird w3-col">
					<com:TActiveListBox
						ID="GeneralDefaultAccessRole"
						SelectionMode="Multiple"
						Rows="5"
						CssClass="w3-input w3-border"
						CausesValidation="false"
						Width="90%"
					/>
					<p style="margin: 0 16px 0 0"><%[ Use CTRL + left-click to multiple item selection ]%></p>
				</div>
			</div>
			<div class="w3-container w3-row w3-padding">
				<div class="w3-third w3-col">
					<%[ Default API host: ]%>
				</div>
				<div class="w3-twothird w3-col">
					<com:TActiveListBox
						ID="GeneralDefaultAccessAPIHost"
						SelectionMode="Multiple"
						Rows="5"
						CssClass="w3-input w3-border"
						CausesValidation="false"
						Width="90%"
					/>
					<p style="margin: 0 16px 0 0"><%[ Use CTRL + left-click to multiple item selection ]%></p>
				</div>
			</div>
		</div>
	</div>
	<div class="w3-container w3-row w3-padding opt_row">
		<div class="w3-col" style="width: 40px">
			<com:TRadioButton
				ID="GeneralProvisionUserAccess"
				GroupName="GeneralDefault"
				CssClass="w3-radio"
				Attributes.onclick="$('div[rel=general_option]').hide(); $('#general_options_provision_user_access').show();"
			/>
		</div>
		<label for="<%=$this->GeneralProvisionUserAccess->ClientID%>"><%[ Provision user ]%></label>
	</div>
	<div id="general_options_provision_user_access" class="w3-container w3-margin-left" rel="general_option" style="display: <%=$this->GeneralProvisionUserAccess->Checked ? 'block' : 'none'%>">
		<div class="w3-half">
			<div class="w3-container w3-row w3-block">
				<div class="w3-third w3-col">
					<%[ New user role ]%>:
				</div>
				<div class="w3-twothird w3-col">
					<com:TActiveListBox
						ID="GeneralProvisionUserAccessRole"
						SelectionMode="Multiple"
						Rows="5"
						CssClass="w3-input w3-border"
						CausesValidation="false"
						Width="90%"
					/>
					<p style="margin: 0 16px 0 0"><%[ Use CTRL + left-click to multiple item selection ]%></p>
				</div>
			</div>
			<div class="w3-container w3-row w3-padding">
				<div class="w3-third w3-col">
					<%[ New user API host ]%>:
				</div>
				<div class="w3-twothird w3-col">
					<com:TActiveListBox
						ID="GeneralProvisionUserAccessAPIHost"
						SelectionMode="Multiple"
						Rows="5"
						CssClass="w3-input w3-border"
						CausesValidation="false"
						Width="90%"
					/>
					<p style="margin: 0 16px 0 0"><%[ Use CTRL + left-click to multiple item selection ]%></p>
				</div>
			</div>
			<div class="w3-container w3-row w3-padding">
				<div class="w3-third w3-col">
					<%[ New user organization ]%>:
				</div>
				<div class="w3-twothird w3-col">
					<com:TActiveDropDownList
						ID="GeneralProvisionUserAccessOrganization"
						CssClass="w3-select w3-border"
						AutoPostBack="false"
						CausesValidation="false"
						Width="90%"
					/>
				</div>
			</div>
		</div>
	</div>
	<div class="w3-container w3-center">
		<com:TActiveLinkButton
			ID="AuthMethodSave"
			ValidationGroup="AuthMethodGroup"
			CausesValidation="true"
			OnCallback="saveSecurityConfig"
			CssClass="w3-button w3-section w3-green w3-padding"
		>
			<i class="fa fa-save"></i> &nbsp;<%[ Save ]%>
			<prop:ClientSide.OnLoading>
				document.getElementById('auth_general_save_ok').style.display = 'none';
				document.getElementById('auth_general_save_error').style.display = 'none';
				document.getElementById('auth_general_save_loading').style.visibility = 'visible';
			</prop:ClientSide.OnLoading>
			<prop:ClientSide.OnComplete>
				document.getElementById('auth_general_save_loading').style.visibility = 'hidden';
			</prop:ClientSide.OnComplete>
		</com:TActiveLinkButton>
		&nbsp;<i id="auth_general_save_loading" class="fas fa-sync w3-spin" style="visibility: hidden;"></i>
		<span id="auth_general_save_ok" class="w3-text-green" style="display: none;"><i class="fas fa-check w3-text-green"></i> &nbsp;<%[ OK ]%></span>
		<span id="auth_general_save_error" class="w3-text-red" style="display: none"><i class="fas fa-times-circle w3-text-red"></i><%[ Error ]%></span>
	</div>
</div>
