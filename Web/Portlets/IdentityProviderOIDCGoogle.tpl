<div>
	<h5><%[ General ]%></h5>
	<div class="w3-container w3-row directive_field">
		<div class="w3-third w3-col">
			<%[ Redirect URI ]%>:
		</div>
		<div class="w3-twothird w3-col">
			<com:TActiveTextBox
				ID="IdPOIDCGoogleRedirectUri"
				CssClass="w3-input w3-border w3-show-inline-block"
				CausesValidation="false"
				Width="90%"
				Attributes.rel="redirect-uri"
			/>
			<i class="fas fa-asterisk w3-text-red opt_req"></i>
			<com:TRequiredFieldValidator
				ValidationGroup="IdPGroup"
				CssClass="validator-block"
				Display="Dynamic"
				ControlCssClass="field_invalid"
				ControlToValidate="IdPOIDCGoogleRedirectUri"
				Text="<%[ Field required. ]%>"
			>
				<prop:ClientSide.OnValidate>
					const is_oidc_auth = ($('#<%=$this->IdPType->ClientID%>')[0].value == '<%=IdentityProviderConfig::IDP_TYPE_OIDC_GOOGLE%>');
					sender.enabled = is_oidc_auth;
				</prop:ClientSide.OnValidate>
			</com:TRequiredFieldValidator>
		</div>
	</div>
	<h5><%[ Credentials ]%></h5>
	<div class="w3-container w3-row directive_field">
		<div class="w3-third w3-col">
			<%[ Client ID ]%>:
		</div>
		<div class="w3-twothird w3-col">
			<com:TActiveTextBox
				ID="IdPOIDCGoogleClientID"
				CssClass="w3-input w3-border w3-show-inline-block"
				CausesValidation="false"
				Width="90%"
			/>
			<i class="fas fa-asterisk w3-text-red opt_req"></i>
			<com:TRequiredFieldValidator
				ValidationGroup="IdPGroup"
				CssClass="validator-block"
				Display="Dynamic"
				ControlCssClass="field_invalid"
				ControlToValidate="IdPOIDCGoogleClientID"
				Text="<%[ Field required. ]%>"
			>
				<prop:ClientSide.OnValidate>
					const is_oidc_auth = ($('#<%=$this->IdPType->ClientID%>')[0].value == '<%=IdentityProviderConfig::IDP_TYPE_OIDC_GOOGLE%>');
					sender.enabled = is_oidc_auth;
				</prop:ClientSide.OnValidate>
			</com:TRequiredFieldValidator>
		</div>
	</div>
	<div class="w3-container w3-row directive_field">
		<div class="w3-third w3-col">
			<%[ Client secret ]%>:
		</div>
		<div class="w3-twothird w3-col">
			<com:TActiveTextBox
				ID="IdPOIDCGoogleClientSecret"
				CssClass="w3-input w3-border w3-show-inline-block"
				TextMode="Password"
				CausesValidation="false"
				Width="90%"
			/>
			<i class="fas fa-asterisk w3-text-red opt_req"></i>
			<a href="javascript:void(0)" onclick="var el = document.getElementById('<%=$this->IdPOIDCGoogleClientSecret->ClientID%>'); el.type = el.type == 'text' ? 'password' : 'text'" title="Show/hide password"><i class="fa fa-eye"></i></a>
			<com:TRequiredFieldValidator
				ValidationGroup="IdPGroup"
				CssClass="validator-block"
				Display="Dynamic"
				ControlCssClass="field_invalid"
				ControlToValidate="IdPOIDCGoogleClientSecret"
				Text="<%[ Field required. ]%>"
			>
				<prop:ClientSide.OnValidate>
					const is_oidc_auth = ($('#<%=$this->IdPType->ClientID%>')[0].value == '<%=IdentityProviderConfig::IDP_TYPE_OIDC_GOOGLE%>');
					sender.enabled = is_oidc_auth;
				</prop:ClientSide.OnValidate>
			</com:TRequiredFieldValidator>
		</div>
	</div>
	<h5><%[ Other options ]%></h5>
	<div class="w3-container w3-row directive_field">
		<div class="w3-third w3-col">
			<%[ Prompt ]%>:
		</div>
		<div class="w3-twothird w3-col">
			<com:TActiveTextBox
				ID="IdPOIDCGooglePrompt"
				CssClass="w3-input w3-border w3-show-inline-block"
				CausesValidation="false"
				Width="90%"
			/>
		</div>
	</div>
	<div class="w3-container w3-row directive_field">
		<div class="w3-third w3-col">
			<%[ Use refresh token ]%>:
		</div>
		<div class="w3-twothird w3-col">
			<com:TActiveCheckBox
				ID="IdPOIDCGoogleRefreshToken"
				CssClass="w3-check"
				CausesValidation="false"
			/>
		</div>
	</div>
</div>
<script>
const oIdPOIDCGoogle = {
	clear_idp_window: function() {
		// clear inputs and selects
		[
			'<%=$this->IdPOIDCGoogleRedirectUri->ClientID%>',
			'<%=$this->IdPOIDCGoogleClientID->ClientID%>',
			'<%=$this->IdPOIDCGoogleClientSecret->ClientID%>',
			'<%=$this->IdPOIDCGooglePrompt->ClientID%>'
		].forEach(function(id) {
			document.getElementById(id).value = '';
		});
	}
}
</script>
