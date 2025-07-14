<div>
	<h5><%[ General ]%></h5>
	<div class="w3-container w3-row directive_field">
		<div class="w3-third w3-col">
			<%[ Redirect URI ]%>:
		</div>
		<div class="w3-twothird w3-col">
			<com:TActiveTextBox
				ID="IdPOIDCRedirectUri"
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
				ControlToValidate="IdPOIDCRedirectUri"
				Text="<%[ Field required. ]%>"
			>
				<prop:ClientSide.OnValidate>
					const is_oidc_auth = ($('#<%=$this->IdPType->ClientID%>')[0].value == '<%=IdentityProviderConfig::IDP_TYPE_OIDC%>');
					sender.enabled = is_oidc_auth;
				</prop:ClientSide.OnValidate>
			</com:TRequiredFieldValidator>
		</div>
	</div>
	<h5><%[ Endpoints and functions ]%></h5>
	<div class="w3-container w3-row directive_field">
		<div class="w3-third w3-col">
			<%[ Use discovery endpoint ]%>:
		</div>
		<div class="w3-twothird w3-col">
			<com:TActiveCheckBox
				ID="IdPOIDCUseDiscoveryEndpoint"
				CssClass="w3-check"
				AutoPostBack="false"
				CausesValidation="false"
				Checked="true"
				Attributes.onclick="oIdPOIDCUserSecurity.show_discovery();"
			/>
		</div>
	</div>
	<div class="w3-container w3-row directive_field">
		<div class="w3-third w3-col">
			<%[ Discovery URL ]%>:
		</div>
		<div class="w3-twothird w3-col">
			<com:TActiveTextBox
				ID="IdPOIDCDiscoveryEndpoint"
				CssClass="w3-input w3-border w3-show-inline-block"
				CausesValidation="false"
				Width="90%"
			/>
			<i id="idp_method_oidc_discovery_url_req" class="fas fa-asterisk w3-text-red opt_req"></i>
			<com:TRequiredFieldValidator
				ValidationGroup="IdPGroup"
				CssClass="validator-block"
				Display="Dynamic"
				ControlCssClass="field_invalid"
				ControlToValidate="IdPOIDCDiscoveryEndpoint"
				Text="<%[ Field required. ]%>"
			>
				<prop:ClientSide.OnValidate>
					const is_oidc_auth = ($('#<%=$this->IdPType->ClientID%>')[0].value == '<%=IdentityProviderConfig::IDP_TYPE_OIDC%>');
					const is_use_discovery = $('#<%=$this->IdPOIDCUseDiscoveryEndpoint->ClientID%>')[0].checked;
					sender.enabled = (is_oidc_auth && is_use_discovery);
				</prop:ClientSide.OnValidate>
			</com:TRequiredFieldValidator>
		</div>
	</div>
	<div id="idp_method_oidc_disable_discovery" style="display: <%=!$this->IdPOIDCUseDiscoveryEndpoint->Checked ? 'block' : 'none'%>">
		<div class="w3-container w3-row directive_field">
			<div class="w3-third w3-col">
				<%[ Authorization URL ]%>:
			</div>
			<div class="w3-twothird w3-col">
				<com:TActiveTextBox
					ID="IdPOIDCAuthorizationEndpoint"
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
					ControlToValidate="IdPOIDCAuthorizationEndpoint"
					Text="<%[ Field required. ]%>"
				>
					<prop:ClientSide.OnValidate>
						const is_oidc_auth = ($('#<%=$this->IdPType->ClientID%>')[0].value == '<%=IdentityProviderConfig::IDP_TYPE_OIDC%>');
						const is_use_discovery = $('#<%=$this->IdPOIDCUseDiscoveryEndpoint->ClientID%>')[0].checked;
						sender.enabled = (is_oidc_auth && !is_use_discovery);
					</prop:ClientSide.OnValidate>
				</com:TRequiredFieldValidator>
			</div>
		</div>
		<div class="w3-container w3-row directive_field">
			<div class="w3-third w3-col">
				<%[ Token URL ]%>:
			</div>
			<div class="w3-twothird w3-col">
				<com:TActiveTextBox
					ID="IdPOIDCTokenEndpoint"
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
					ControlToValidate="IdPOIDCTokenEndpoint"
					Text="<%[ Field required. ]%>"
				>
					<prop:ClientSide.OnValidate>
						const is_oidc_auth = ($('#<%=$this->IdPType->ClientID%>')[0].value == '<%=IdentityProviderConfig::IDP_TYPE_OIDC%>');
						const is_use_discovery = $('#<%=$this->IdPOIDCUseDiscoveryEndpoint->ClientID%>')[0].checked;
						sender.enabled = (is_oidc_auth && !is_use_discovery);
					</prop:ClientSide.OnValidate>
				</com:TRequiredFieldValidator>
			</div>
		</div>
		<div class="w3-container w3-row directive_field">
			<div class="w3-third w3-col">
				<%[ Logout URL ]%>:
			</div>
			<div class="w3-twothird w3-col">
				<com:TActiveTextBox
					ID="IdPOIDCLogoutEndpoint"
					CssClass="w3-input w3-border w3-show-inline-block"
					CausesValidation="false"
					Width="90%"
				/>
			</div>
		</div>
		<div class="w3-container w3-row directive_field">
			<div class="w3-third w3-col">
				<%[ User Info URL ]%>:
			</div>
			<div class="w3-twothird w3-col">
				<com:TActiveTextBox
					ID="IdPOIDCUserInfoEndpoint"
					CssClass="w3-input w3-border w3-show-inline-block"
					CausesValidation="false"
					Width="90%"
				/>
			</div>
		</div>
		<div class="w3-container w3-row directive_field">
			<div class="w3-third w3-col">
				<%[ Issuer ]%>:
			</div>
			<div class="w3-twothird w3-col">
				<com:TActiveTextBox
					ID="IdPOIDCIssuer"
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
					ControlToValidate="IdPOIDCIssuer"
					Text="<%[ Field required. ]%>"
				>
					<prop:ClientSide.OnValidate>
						const is_oidc_auth = ($('#<%=$this->IdPType->ClientID%>')[0].value == '<%=IdentityProviderConfig::IDP_TYPE_OIDC%>');
						const is_use_discovery = $('#<%=$this->IdPOIDCUseDiscoveryEndpoint->ClientID%>')[0].checked;
						sender.enabled = (is_oidc_auth && !is_use_discovery);
					</prop:ClientSide.OnValidate>
				</com:TRequiredFieldValidator>
			</div>
		</div>
		<div class="w3-container w3-row directive_field">
			<div class="w3-third w3-col">
				<%[ Validate signatures ]%>:
			</div>
			<div class="w3-twothird w3-col">
				<com:TActiveCheckBox
					ID="IdPOIDCValidateSignatures"
					CssClass="w3-check"
					AutoPostBack="false"
					CausesValidation="false"
					Checked="true"
					Attributes.onclick="oIdPOIDCUserSecurity.show_validate_sig_options();"
				/>
			</div>
		</div>
		<div id="idp_method_oidc_enable_validate_sig" style="display: <%=$this->IdPOIDCValidateSignatures->Checked ? 'block' : 'none'%>">
			<div class="w3-container w3-row directive_field">
				<div class="w3-third w3-col">
					<%[ Use JWKS endpoint ]%>:
				</div>
				<div class="w3-twothird w3-col">
					<com:TActiveCheckBox
						ID="IdPOIDCUseJWKSEndpoint"
						CssClass="w3-check"
						AutoPostBack="false"
						CausesValidation="false"
						Attributes.onclick="oIdPOIDCUserSecurity.show_jwks_options();"
					/>
				</div>
			</div>
			<div id="idp_method_oidc_enable_public_key" class="w3-container w3-margin-left" style="display: <%=$this->IdPOIDCValidateSignatures->Checked && !$this->IdPOIDCUseJWKSEndpoint->Checked ? 'block' : 'none'%>">
				<div class="w3-container w3-row directive_field">
					<div class="w3-third w3-col">
						<%[ Public key (PEM format) ]%>:
					</div>
					<div class="w3-twothird w3-col">
						<com:TActiveTextBox
							ID="IdPOIDCPublicKeyString"
							TextMode="MultiLine"
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
							ControlToValidate="IdPOIDCPublicKeyString"
							Text="<%[ Field required. ]%>"
						>
							<prop:ClientSide.OnValidate>
								const is_oidc_auth = ($('#<%=$this->IdPType->ClientID%>')[0].value == '<%=IdentityProviderConfig::IDP_TYPE_OIDC%>');
								const is_use_discovery = $('#<%=$this->IdPOIDCUseDiscoveryEndpoint->ClientID%>')[0].checked;
								const is_validate_signatures = $('#<%=$this->IdPOIDCValidateSignatures->ClientID%>')[0].checked;
								const is_use_jwks = $('#<%=$this->IdPOIDCUseJWKSEndpoint->ClientID%>')[0].checked;
								sender.enabled = (is_oidc_auth && !is_use_discovery && is_validate_signatures && !is_use_jwks);
							</prop:ClientSide.OnValidate>
						</com:TRequiredFieldValidator>
					</div>
				</div>
				<div class="w3-container w3-row directive_field">
					<div class="w3-third w3-col">
						<%[ Public key ID ]%>:
					</div>
					<div class="w3-twothird w3-col">
						<com:TActiveTextBox
							ID="IdPOIDCPublicKeyID"
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
							ControlToValidate="IdPOIDCPublicKeyID"
							Text="<%[ Field required. ]%>"
						>
							<prop:ClientSide.OnValidate>
								const is_oidc_auth = ($('#<%=$this->IdPType->ClientID%>')[0].value == '<%=IdentityProviderConfig::IDP_TYPE_OIDC%>');
								const is_use_discovery = $('#<%=$this->IdPOIDCUseDiscoveryEndpoint->ClientID%>')[0].checked;
								const is_validate_signatures = $('#<%=$this->IdPOIDCValidateSignatures->ClientID%>')[0].checked;
								const is_use_jwks = $('#<%=$this->IdPOIDCUseJWKSEndpoint->ClientID%>')[0].checked;
								sender.enabled = (is_oidc_auth && !is_use_discovery && is_validate_signatures && !is_use_jwks);
							</prop:ClientSide.OnValidate>
						</com:TRequiredFieldValidator>
					</div>
				</div>
			</div>
		</div>
		<div id="idp_method_oidc_enable_jwks" class="w3-container w3-margin-left" style="display: <%=$this->IdPOIDCUseJWKSEndpoint->Checked ? 'block' : 'none'%>">
			<div class="w3-container w3-row directive_field">
				<div class="w3-third w3-col">
					<%[ JWKS URL ]%>:
				</div>
				<div class="w3-twothird w3-col">
					<com:TActiveTextBox
						ID="IdPOIDCJWKSEndpoint"
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
						ControlToValidate="IdPOIDCJWKSEndpoint"
						Text="<%[ Field required. ]%>"
					>
						<prop:ClientSide.OnValidate>
							const is_oidc_auth = ($('#<%=$this->IdPType->ClientID%>')[0].value == '<%=IdentityProviderConfig::IDP_TYPE_OIDC%>');
							const is_use_discovery = $('#<%=$this->IdPOIDCUseDiscoveryEndpoint->ClientID%>')[0].checked;
							const is_use_jwks = $('#<%=$this->IdPOIDCUseJWKSEndpoint->ClientID%>')[0].checked;
							sender.enabled = (is_oidc_auth && !is_use_discovery && is_use_jwks);
						</prop:ClientSide.OnValidate>
					</com:TRequiredFieldValidator>
				</div>
			</div>
		</div>
		<div class="w3-container w3-row directive_field">
			<div class="w3-third w3-col">
				<%[ Use PKCE ]%>:
			</div>
			<div class="w3-twothird w3-col">
				<com:TActiveCheckBox
					ID="IdPOIDCUsePKCE"
					CssClass="w3-check"
					AutoPostBack="false"
					CausesValidation="false"
					Attributes.onclick="oIdPOIDCUserSecurity.show_pkce_options();"
				/>
			</div>
		</div>
		<div id="idp_method_oidc_enable_pkce" class="w3-container w3-margin-left" style="display: <%=$this->IdPOIDCUsePKCE->Checked ? 'block' : 'none'%>">
			<div class="w3-container w3-row directive_field">
				<div class="w3-third w3-col">
					<%[ PKCE method ]%>:
				</div>
				<div class="w3-twothird w3-col">
					<com:TActiveDropDownList
						ID="IdPOIDCPKCEMethod"
						CssClass="w3-input w3-border w3-show-inline-block"
						CausesValidation="false"
						Width="30%"
					>
						<com:TListItem Value="<%=PKCE::CODE_CHALLENGE_METHOD_PLAIN%>" Text="Plain" />
						<com:TListItem Value="<%=PKCE::CODE_CHALLENGE_METHOD_S256%>" Text="S256" Selected="true" />
					</com:TActiveDropDownList>
					<com:TRequiredFieldValidator
						ValidationGroup="IdPGroup"
						CssClass="validator-block"
						Display="Dynamic"
						ControlCssClass="field_invalid"
						ControlToValidate="IdPOIDCPKCEMethod"
						Text="<%[ Field required. ]%>"
					>
						<prop:ClientSide.OnValidate>
							const is_oidc_auth = ($('#<%=$this->IdPType->ClientID%>')[0].value == '<%=IdentityProviderConfig::IDP_TYPE_OIDC%>');
							const is_use_discovery = $('#<%=$this->IdPOIDCUseDiscoveryEndpoint->ClientID%>')[0].checked;
							const is_use_pkce = $('#<%=$this->IdPOIDCUsePKCE->ClientID%>')[0].checked;
							sender.enabled = (is_oidc_auth && !is_use_discovery && is_use_pkce);
						</prop:ClientSide.OnValidate>
					</com:TRequiredFieldValidator>
				</div>
			</div>
		</div>
		<div class="w3-container w3-row directive_field">
			<div class="w3-third w3-col">
				<%[ Scope ]%>:
			</div>
			<div class="w3-twothird w3-col">
				<com:TActiveTextBox
					ID="IdPOIDCScope"
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
					ControlToValidate="IdPOIDCScope"
					Text="<%[ Field required. ]%>"
				>
					<prop:ClientSide.OnValidate>
						const is_oidc_auth = ($('#<%=$this->IdPType->ClientID%>')[0].value == '<%=IdentityProviderConfig::IDP_TYPE_OIDC%>');
						const is_use_discovery = $('#<%=$this->IdPOIDCUseDiscoveryEndpoint->ClientID%>')[0].checked;
						sender.enabled = (is_oidc_auth && !is_use_discovery);
					</prop:ClientSide.OnValidate>
				</com:TRequiredFieldValidator>
			</div>
		</div>
	</div>
	<h5><%[ Credentials ]%></h5>
	<div class="w3-container w3-row directive_field">
		<div class="w3-third w3-col">
			<%[ Client ID ]%>:
		</div>
		<div class="w3-twothird w3-col">
			<com:TActiveTextBox
				ID="IdPOIDCClientID"
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
				ControlToValidate="IdPOIDCClientID"
				Text="<%[ Field required. ]%>"
			>
				<prop:ClientSide.OnValidate>
					const is_oidc_auth = ($('#<%=$this->IdPType->ClientID%>')[0].value == '<%=IdentityProviderConfig::IDP_TYPE_OIDC%>');
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
				ID="IdPOIDCClientSecret"
				CssClass="w3-input w3-border w3-show-inline-block"
				TextMode="Password"
				CausesValidation="false"
				Width="90%"
			/>
			<i class="fas fa-asterisk w3-text-red opt_req"></i>
			<a href="javascript:void(0)" onclick="var el = document.getElementById('<%=$this->IdPOIDCClientSecret->ClientID%>'); el.type = el.type == 'text' ? 'password' : 'text'" title="Show/hide password"><i class="fa fa-eye"></i></a>
			<com:TRequiredFieldValidator
				ValidationGroup="IdPGroup"
				CssClass="validator-block"
				Display="Dynamic"
				ControlCssClass="field_invalid"
				ControlToValidate="IdPOIDCClientSecret"
				Text="<%[ Field required. ]%>"
			>
				<prop:ClientSide.OnValidate>
					const is_oidc_auth = ($('#<%=$this->IdPType->ClientID%>')[0].value == '<%=IdentityProviderConfig::IDP_TYPE_OIDC%>');
					sender.enabled = is_oidc_auth;
				</prop:ClientSide.OnValidate>
			</com:TRequiredFieldValidator>
		</div>
	</div>
	<h5><%[ Attributes ]%></h5>
	<div class="w3-container w3-row directive_field">
		<div class="w3-third w3-col">
			<%[ User attribute source ]%>:
		</div>
		<div class="w3-twothird w3-col">
			<com:TActiveDropDownList
				ID="IdPOIDCUserAttrSource"
				CssClass="w3-select w3-border w3-show-inline-block"
				CausesValidation="false"
				Width="90%"
			>
				<com:TListItem Value="<%=IdentityProviderConfig::OIDC_USER_ATTR_SOURCE_ID_TOKEN%>" Text="ID token" />
				<com:TListItem Value="<%=IdentityProviderConfig::OIDC_USER_ATTR_SOURCE_USERINFO_ENDPOINT%>" Text="User info endpoint" />
			</com:TActiveDropDownList>
			<com:TRequiredFieldValidator
				ValidationGroup="IdPGroup"
				CssClass="validator-block"
				Display="Dynamic"
				ControlCssClass="field_invalid"
				ControlToValidate="IdPOIDCUserAttrSource"
				Text="<%[ Field required. ]%>"
			>
				<prop:ClientSide.OnValidate>
					const is_oidc_auth = ($('#<%=$this->IdPType->ClientID%>')[0].value == '<%=IdentityProviderConfig::IDP_TYPE_OIDC%>');
					sender.enabled = is_oidc_auth;
				</prop:ClientSide.OnValidate>
			</com:TRequiredFieldValidator>
		</div>
	</div>
	<div class="w3-container w3-row directive_field">
		<div class="w3-third w3-col">
			<%[ Username attribute ]%>:
		</div>
		<div class="w3-twothird w3-col">
			<com:TActiveTextBox
				ID="IdPOIDCUserNameAttr"
				CssClass="w3-input w3-border w3-show-inline-block"
				Width="90%"
			/>
			<i class="fas fa-asterisk w3-text-red opt_req"></i>
			<com:TRequiredFieldValidator
				ValidationGroup="IdPGroup"
				CssClass="validator-block"
				Display="Dynamic"
				ControlCssClass="field_invalid"
				ControlToValidate="IdPOIDCUserNameAttr"
				Text="<%[ Field required. ]%>"
			>
				<prop:ClientSide.OnValidate>
					const is_oidc_auth = ($('#<%=$this->IdPType->ClientID%>')[0].value == '<%=IdentityProviderConfig::IDP_TYPE_OIDC%>');
					sender.enabled = is_oidc_auth;
				</prop:ClientSide.OnValidate>
			</com:TRequiredFieldValidator>
		</div>
	</div>
	<div class="w3-container w3-row directive_field">
		<div class="w3-third w3-col">
			<%[ Long name attribute ]%>:
		</div>
		<div class="w3-twothird w3-col">
			<com:TActiveTextBox
				ID="IdPOIDCLongNameAttr"
				CssClass="w3-input w3-border w3-show-inline-block"
				Width="90%"
			/>
		</div>
	</div>
	<div class="w3-container w3-row directive_field">
		<div class="w3-third w3-col">
			<%[ Email attribute ]%>:
		</div>
		<div class="w3-twothird w3-col">
			<com:TActiveTextBox
				ID="IdPOIDCEmailAttr"
				CssClass="w3-input w3-border w3-show-inline-block"
				Width="90%"
			/>
		</div>
	</div>
	<div class="w3-container w3-row directive_field">
		<div class="w3-third w3-col">
			<%[ Description attribute ]%>:
		</div>
		<div class="w3-twothird w3-col">
			<com:TActiveTextBox
				ID="IdPOIDCDescriptionAttr"
				CssClass="w3-input w3-border w3-show-inline-block"
				Width="90%"
			/>
		</div>
	</div>
	<div class="w3-container w3-row directive_field">
		<div class="w3-third w3-col">
			<%[ Attribute sync. policy ]%>:
		</div>
		<div class="w3-twothird w3-col">
			<com:TActiveRadioButton
				ID="IdPOIDCAttrSyncPolicyNoSync"
				GroupName="IdPOIDCAttrSyncPolicy"
				CssClass="w3-radio"
				Checked="true"
			/> <label for="<%=$this->IdPOIDCAttrSyncPolicyNoSync->ClientID%>"><%[ Do not synchronize ]%></label><br />
			<com:TActiveRadioButton
				ID="IdPOIDCAttrSyncPolicyEachLogin"
				GroupName="IdPOIDCAttrSyncPolicy"
				CssClass="w3-radio"
			/> <label for="<%=$this->IdPOIDCAttrSyncPolicyEachLogin->ClientID%>"><%[ Synchronize on each login ]%></label>
		</div>
	</div>
</div>
<script>
const oIdPOIDCUserSecurity = {
	ids: {
		chkb_discovery: '<%=$this->IdPOIDCUseDiscoveryEndpoint->ClientID%>',
		disable_discovery: 'idp_method_oidc_disable_discovery',
		chkb_validate_sig: '<%=$this->IdPOIDCValidateSignatures->ClientID%>',
		enable_validate_sig: 'idp_method_oidc_enable_validate_sig',
		chkb_jwks: '<%=$this->IdPOIDCUseJWKSEndpoint->ClientID%>',
		enable_jwks: 'idp_method_oidc_enable_jwks',
		enable_public_key: 'idp_method_oidc_enable_public_key',
		chkb_pkce: '<%=$this->IdPOIDCUsePKCE->ClientID%>',
		enable_pkce: 'idp_method_oidc_enable_pkce',
		discovery_url_req: 'idp_method_oidc_discovery_url_req',
		use_jwks: '<%=$this->IdPOIDCUseJWKSEndpoint->ClientID%>'
	},
	load_settings: function() {
		this.show_discovery();
		this.show_validate_sig_options();
		this.show_jwks_options();
		this.show_public_key_options();
		this.show_pkce_options();
	},
	show_discovery: function() {
		const chkb_discovery = document.getElementById(this.ids.chkb_discovery); 
		const show = chkb_discovery.checked;
		const disable_discovery = document.getElementById(this.ids.disable_discovery);
		disable_discovery.style.display = show ? 'none' : 'block';
		const discovery_url_req = document.getElementById(this.ids.discovery_url_req);
		discovery_url_req.style.display = show ? 'inline-block' : 'none';
	},
	show_validate_sig_options: function() {
		const chkb_validate_sig = document.getElementById(this.ids.chkb_validate_sig);
		const show = chkb_validate_sig.checked;
		const enable_validate_sig = document.getElementById(this.ids.enable_validate_sig);
		enable_validate_sig.style.display = show ? 'block' : 'none';
		this.show_jwks_options();
		this.show_public_key_options();
	},
	show_public_key_options: function() {
		const chkb_validate_sig = document.getElementById(this.ids.chkb_validate_sig);
		const chkb_jwks = document.getElementById(this.ids.chkb_jwks);
		const show = (chkb_validate_sig.checked && !chkb_jwks.checked);
		const enable_public_key = document.getElementById(this.ids.enable_public_key);
		enable_public_key.style.display = show ? 'block' : 'none';
	},
	show_jwks_options: function() {
		const chkb_jwks = document.getElementById(this.ids.chkb_jwks);
		const chkb_validate_sig = document.getElementById(this.ids.chkb_validate_sig);
		const ashow = (chkb_validate_sig.checked && chkb_jwks.checked);
		const enable_jwks = document.getElementById(this.ids.enable_jwks);
		enable_jwks.style.display = ashow ? 'block' : 'none';
		this.show_public_key_options();
	},
	show_pkce_options: function() {
		const chkb_pkce = document.getElementById(this.ids.chkb_pkce);
		const show = chkb_pkce.checked;
		const enable_pkce = document.getElementById(this.ids.enable_pkce);
		enable_pkce.style.display = show ? 'block' : 'none';
	}
};
var oIdPOIDC = {
	clear_idp_window: function() {
		// clear inputs and selects
		[
			'<%=$this->IdPOIDCRedirectUri->ClientID%>',
			'<%=$this->IdPOIDCDiscoveryEndpoint->ClientID%>',
			'<%=$this->IdPOIDCAuthorizationEndpoint->ClientID%>',
			'<%=$this->IdPOIDCTokenEndpoint->ClientID%>',
			'<%=$this->IdPOIDCLogoutEndpoint->ClientID%>',
			'<%=$this->IdPOIDCUserInfoEndpoint->ClientID%>',
			'<%=$this->IdPOIDCIssuer->ClientID%>',
			'<%=$this->IdPOIDCScope->ClientID%>',
			'<%=$this->IdPOIDCJWKSEndpoint->ClientID%>',
			'<%=$this->IdPOIDCPublicKeyString->ClientID%>',
			'<%=$this->IdPOIDCPublicKeyID->ClientID%>',
			'<%=$this->IdPOIDCClientID->ClientID%>',
			'<%=$this->IdPOIDCClientSecret->ClientID%>',
			'<%=$this->IdPOIDCUserNameAttr->ClientID%>',
			'<%=$this->IdPOIDCLongNameAttr->ClientID%>',
			'<%=$this->IdPOIDCDescriptionAttr->ClientID%>',
			'<%=$this->IdPOIDCEmailAttr->ClientID%>'
		].forEach(function(id) {
			document.getElementById(id).value = '';
		});

		// clear checkboxes and radio buttons
		[
			'<%=$this->IdPOIDCUseDiscoveryEndpoint->ClientID%>',
			'<%=$this->IdPOIDCValidateSignatures->ClientID%>',
			'<%=$this->IdPOIDCUsePKCE->ClientID%>',
			'<%=$this->IdPOIDCUseJWKSEndpoint->ClientID%>',
			'<%=$this->IdPOIDCAttrSyncPolicyNoSync->ClientID%>'
		].forEach(function(id) {
			document.getElementById(id).checked = true;
		});

		const pkce = document.getElementById('<%=$this->IdPOIDCPKCEMethod->ClientID%>');
		pkce.value = '<%=PKCE::CODE_CHALLENGE_METHOD_S256%>';
		const attr_src = document.getElementById('<%=$this->IdPOIDCUserAttrSource->ClientID%>');
		attr_src.value = '<%=IdentityProviderConfig::OIDC_USER_ATTR_SOURCE_ID_TOKEN%>';
	}
}
</script>
