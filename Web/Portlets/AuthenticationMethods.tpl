<div>
	<h4><%[ Authentication method ]%></h4>
	<p class="w3-hide-small"><%[ Select one of the authentication methods to provide base authentication. ]%></p>
	<div class="w3-container w3-row w3-padding opt_row">
		<div class="w3-col" style="width: 40px">
			<com:TRadioButton
				ID="LocalAuth"
				GroupName="AuthMethod"
				CssClass="w3-radio"
				Attributes.onclick="oUserSecurity.select_auth_method('local');"
			/>
		</div>
		<label for="<%=$this->LocalAuth->ClientID%>"><%[ Local user authentication ]%></label>
	</div>
	<div id="authentication_method_local" class="w3-container" style="display: none">
		<%[ This type of authentication is fully realized by Bacularis Web. To authenticate it uses the Bacularis Web login form. ]%>
	</div>
	<div class="w3-container w3-row w3-padding opt_row">
		<div class="w3-col" style="width: 40px">
			<com:TRadioButton
				ID="BasicAuth"
				GroupName="AuthMethod"
				CssClass="w3-radio"
				Attributes.onclick="oUserSecurity.select_auth_method('basic');"
			/>
		</div>
		<label for="<%=$this->BasicAuth->ClientID%>"><%[ HTTP Basic authentication ]%></label>
	</div>
	<div id="authentication_method_basic" class="w3-container" style="display: none">
		<%[ This type of authentication is realized by Bacularis Web with using custom user credentials file. To authenticate it uses the Basic authentication. ]%>
		<div>
			<div class="w3-container w3-row opt_row">
				<div class="w3-col" style="width: 40px">
					<com:TCheckBox
						ID="BasicAuthAllowManageUsers"
						CssClass="w3-check"
					/>
				</div>
				<label for="<%=$this->BasicAuthAllowManageUsers->ClientID%>"><%[ Allow Bacularis Web to manage the Basic authentication users (add/remove users and change their passwords) ]%></label>
			</div>
			<div class="w3-container w3-row w3-block">
				<div class="w3-col opt_row" style="width: 220px">
					<%[ Users file path: ]%>
				</div>
				<div class="w3-twothird w3-col">
					<com:TActiveTextBox
						ID="BasicAuthUserFile"
						CssClass="w3-input w3-border w3-show-inline-block"
						CausesValidation="false"
						Width="400px"
					/><com:TActiveLinkButton
						ValidationGroup="AuthMethodGroup"
						CausesValidation="true"
						CssClass="w3-button w3-green"
						OnCallback="doBasicUserFileTest"
					>
						<i class="fas fa-play"></i> &nbsp;<%[ Test ]%>
						<prop:ClientSide.OnLoading>
							document.getElementById('basic_auth_user_file_test_ok').style.display = 'none';
							document.getElementById('basic_auth_user_file_test_error').style.display = 'none';
							document.getElementById('basic_auth_user_file_test_loading').style.visibility = 'visible';
							document.getElementById('<%=$this->BasicAuthUserFileMsg->ClientID%>').style.display = 'none';
						</prop:ClientSide.OnLoading>
						<prop:ClientSide.OnComplete>
							document.getElementById('basic_auth_user_file_test_loading').style.visibility = 'hidden';
							document.getElementById('<%=$this->BasicAuthUserFileMsg->ClientID%>').style.display = '';
						</prop:ClientSide.OnComplete>
					</com:TActiveLinkButton>
					&nbsp;<i id="basic_auth_user_file_test_loading" class="fas fa-sync w3-spin" style="visibility: hidden;"></i>
					<span id="basic_auth_user_file_test_ok" class="w3-text-green" style="display: none;"><i class="fas fa-check w3-text-green"></i> &nbsp;<%[ OK ]%></span>
					<div>
						<i id="basic_auth_user_file_test_error" class="fas fa-times-circle w3-text-red" style="display: none;"></i>
						<com:TActiveLabel
							ID="BasicAuthUserFileMsg"
							CssClass="error"
							ActiveControl.EnableUpdate="true"
						/>
						<com:TRequiredFieldValidator
							ValidationGroup="AuthMethodGroup"
							CssClass="validator-block"
							ControlCssClass="field_invalid"
							Display="Dynamic"
							ControlToValidate="BasicAuthUserFile"
							Text="<%[ Please enter users file path value. ]%>"
						>
							<prop:ClientSide.OnValidate>
								var basic_auth_opt = $('#<%=$this->BasicAuth->ClientID%>')[0];
								var user_file_opt = $('#<%=$this->BasicAuthAllowManageUsers->ClientID%>')[0];
								sender.enabled = (basic_auth_opt.checked && user_file_opt.checked);
							</prop:ClientSide.OnValidate>
						</com:TRequiredFieldValidator>
					</div>
				</div>
			</div>
			<div class="w3-container w3-row w3-padding w3-block">
				<div class="w3-col opt_row" style="width: 220px">
					<%[  Hash algorithm: ]%>
				</div>
				<div class="w3-twothird w3-col">
					<com:TActiveDropDownList
						ID="BasicAuthHashAlgorithm"
						CssClass="w3-select w3-border"
						CausesValidation="false"
						Width="180px"
						Attributes.onchange="basic_auth_change_hash_alg(this.value);"
					>
						<com:TListItem Value="apr1-md5" Text="APR1-MD5" />
						<com:TListItem Value="sha1" Text="SHA-1" />
						<com:TListItem Value="sha256" Text="SHA-256" />
						<com:TListItem Value="sha512" Text="SHA-512" />
						<com:TListItem Value="ssha1" Text="SSHA (salted SHA-1)" />
						<com:TListItem Value="bcrypt" Text="BCrypt" />
					</com:TActiveDropDownList>
					<span id="basic_auth_alg_supp_msg"></span>
				</div>
				<script>
					function basic_auth_change_hash_alg(alg) {
						var msg = '<%[ Requried support from web server side - e.g. %supported_web_server_list ]%>'
						var hash_alg_supp = {
							'apr1-md5': [
								'Apache',
								'Lighttpd 1.4.13+',
								'Nginx 1.0.3+'
							],
							'sha1': [
								'Apache',
								'Lighttpd 1.4.33+',
								'Nginx 1.3.13+'
							],
							'sha256': [
								'<%[ Apache, Lighttpd and Nginx on most UNIX platforms ]%>',
							],
							'sha512': [
								'<%[ Apache, Lighttpd and Nginx on most UNIX platforms ]%>',
							],
							'ssha1': [
								'Nginx 1.0.3+'
							],
							'bcrypt': [
								'<%[ Apache 2.4 with apr-util 1.5+ ]%>',
								'<%[ Nginx on most UNIX platforms ]%>'
							]
						};
						msg = msg.replace('%supported_web_server_list',  hash_alg_supp[alg].join(', '));
						document.getElementById('basic_auth_alg_supp_msg').textContent = '( ' + msg + ' )';
					}
					var basic_auth_hash_alg_sel_val = document.getElementById('<%=$this->BasicAuthHashAlgorithm->ClientID%>').value;
					basic_auth_change_hash_alg(basic_auth_hash_alg_sel_val);
				</script>
			</div>
		</div>
		<div class="w3-container">
			<com:TActiveLinkButton
				ValidationGroup="AuthMethodGroup"
				CausesValidation="true"
				OnCommand="getBasicUsers"
				CommandParameter="load"
				CssClass="w3-button w3-section w3-green w3-padding"
			>
				<i class="fa fa-users"></i> &nbsp;<%[ Manage Basic users ]%>
				<prop:ClientSide.OnLoading>
					document.getElementById('basic_get_users_ok').style.display = 'none';
					document.getElementById('basic_get_users_error').style.display = 'none';
					document.getElementById('basic_get_users_loading').style.visibility = 'visible';
					oUserSecurity.show_user_selected_msg(false);
				</prop:ClientSide.OnLoading>
				<prop:ClientSide.OnComplete>
					document.getElementById('basic_get_users_loading').style.visibility = 'hidden';
				</prop:ClientSide.OnComplete>
			</com:TActiveLinkButton>
			&nbsp;<i id="basic_get_users_loading" class="fas fa-sync w3-spin" style="visibility: hidden;"></i>
			<span id="basic_get_users_ok" class="w3-text-green" style="display: none;"><i class="fas fa-check w3-text-green"></i> &nbsp;<%[ OK ]%></span>
			<i id="basic_get_users_error" class="fas fa-times-circle w3-text-red" style="display: none;"></i>
			<com:TActiveLabel ID="TestBasicGetUsersMsg" CssClass="w3-text-red" Display="None" />
		</div>
	</div>
	<div class="w3-container w3-row w3-padding opt_row">
		<div class="w3-col" style="width: 40px">
			<com:TRadioButton
				ID="LdapAuth"
				GroupName="AuthMethod"
				CssClass="w3-radio"
				Attributes.onclick="oUserSecurity.select_auth_method('ldap');"
			/>
		</div>
		<label for="<%=$this->LdapAuth->ClientID%>"><%[ LDAP authentication ]%></label>
	</div>
	<div id="authentication_method_ldap" class="w3-container w3-half w3-margin-left" style="display: none">
		<%[ This type of authentication is realized by an external directory service. To authenticate it uses the Bacularis Web login form. ]%>
		<h5><%[ LDAP server options ]%></h5>
		<div class="w3-container w3-row directive_field">
			<div class="w3-third w3-col">
				<%[ IP Address/Hostname: ]%>
			</div>
			<div class="w3-twothird w3-col">
				<com:TActiveTextBox
					ID="LdapAuthServerAddress"
					CssClass="w3-input w3-border w3-show-inline-block"
					CausesValidation="false"
					Width="90%"
				/>
				<i class="fas fa-asterisk w3-text-red opt_req"></i>
				<com:TRequiredFieldValidator
					ValidationGroup="AuthMethodGroup"
					CssClass="validator-block"
					Display="Dynamic"
					ControlCssClass="field_invalid"
					ControlToValidate="LdapAuthServerAddress"
					Text="<%[ Please enter IP Address/Hostname. ]%>"
				>
					<prop:ClientSide.OnValidate>
						sender.enabled = $('#<%=$this->LdapAuth->ClientID%>')[0].checked;
					</prop:ClientSide.OnValidate>
				</com:TRequiredFieldValidator>
			</div>
		</div>
		<div class="w3-container w3-row directive_field">
			<div class="w3-third w3-col">
				<%[ Port: ]%>
			</div>
			<div class="w3-col" style="width: 245px;">
				<com:TActiveTextBox
					ID="LdapAuthServerPort"
					CssClass="w3-input w3-border w3-show-inline-block"
					CausesValidation="false"
					Width="70px"
					Text="389"
				/>
				<i class="fas fa-asterisk w3-text-red opt_req"></i><br />
				<com:TRequiredFieldValidator
					ValidationGroup="AuthMethodGroup"
					CssClass="validator-block"
					Display="Dynamic"
					ControlCssClass="field_invalid"
					ControlToValidate="LdapAuthServerPort"
					Text="<%[ Please enter port. ]%>"
				>
					<prop:ClientSide.OnValidate>
						sender.enabled = $('#<%=$this->LdapAuth->ClientID%>')[0].checked;
					</prop:ClientSide.OnValidate>
				</com:TRequiredFieldValidator>
			</div>
		</div>
		<div class="w3-container w3-row directive_field">
			<div class="w3-third w3-col">
				<%[ Protocol version: ]%>
			</div>
			<div class="w3-col" style="width: 160px">
				<com:TActiveDropDownList
					ID="LdapAuthServerProtocolVersion"
					CssClass="w3-input w3-border"
					CausesValidation="false"
					AutoPostBack="false"
				>
					<com:TListItem Value="1" Text="LDAP version 1" />
					<com:TListItem Value="2" Text="LDAP version 2" />
					<com:TListItem Value="3" Text="LDAP version 3" Selected="true" />
				</com:TActiveDropDownList>
			</div>
		</div>
		<strong><%[ TLS/SSL encryption ]%></strong>
		<div class="w3-container w3-row directive_field">
			<div class="w3-third w3-col">
				<label for="<%=$this->LdapAuthServerNoEncryption->ClientID%>">
					<%[ No encryption ]%>:
				</label>
			</div>
			<div class="w3-col" style="width: 70px;">
				<com:TActiveRadioButton
					ID="LdapAuthServerNoEncryption"
					GroupName="LdapAuthServerEncryption"
					CssClass="w3-radio"
					CausesValidation="false"
					AutoPostBack="false"
					Checked="true"
				/>
			</div>
		</div>
		<div class="w3-container w3-row directive_field">
			<div class="w3-third w3-col">
				<label for="<%=$this->LdapAuthServerStartTLS->ClientID%>">
					StartTLS:
				</label>
			</div>
			<div class="w3-col" style="width: 70px;">
				<com:TActiveRadioButton
					ID="LdapAuthServerStartTLS"
					GroupName="LdapAuthServerEncryption"
					CssClass="w3-radio"
					CausesValidation="false"
					AutoPostBack="false"
				/>
			</div>
		</div>
		<div class="w3-container w3-row directive_field">
			<div class="w3-third w3-col">
				<label for="<%=$this->LdapAuthServerLdaps->ClientID%>">
					LDAPS:
				</label>
			</div>
			<div class="w3-col" style="width: 70px;">
				<com:TActiveRadioButton
					ID="LdapAuthServerLdaps"
					GroupName="LdapAuthServerEncryption"
					CssClass="w3-radio"
					CausesValidation="false"
					AutoPostBack="false"
				/>
			</div>
		</div>
		<h5><%[ LDAP authentication method ]%></h5>
		<div class="w3-container w3-row w3-padding opt_row">
			<div class="w3-col" style="width: 40px">
				<com:TRadioButton
					ID="LdapAuthMethodAnonymous"
					GroupName="LdapAuthMethod"
					CssClass="w3-radio"
					Attributes.onclick="oLdapUserSecurity.show_ldap_auth('anon');"
					Checked="true"
				/>
			</div>
			<label for="<%=$this->LdapAuthMethodAnonymous->ClientID%>"><%[ Anonymous authentication ]%></label>
		</div>
		<div class="w3-container w3-row w3-padding opt_row">
			<div class="w3-col" style="width: 40px">
				<com:TRadioButton
					ID="LdapAuthMethodSimple"
					GroupName="LdapAuthMethod"
					CssClass="w3-radio"
					Attributes.onclick="oLdapUserSecurity.show_ldap_auth('simple');"
				/>
			</div>
			<label for="<%=$this->LdapAuthMethodSimple->ClientID%>"><%[ Simple authentication ]%></label>
		</div>
		<div id="authentication_method_ldap_auth_simple" class="w3-container w3-margin-left" style="display: <%=$this->LdapAuthMethodSimple->Checked ? 'block' : 'none'%>">
			<div class="w3-container w3-row directive_field">
				<div class="w3-third w3-col">
					<%[ Manager DN: ]%>
				</div>
				<div class="w3-twothird w3-col">
					<com:TActiveTextBox
						ID="LdapAuthMethodSimpleUsername"
						CssClass="w3-input w3-border w3-show-inline-block"
						CausesValidation="false"
						Width="90%"
					/>
					<i class="fas fa-asterisk w3-text-red opt_req"></i>
					<com:TRequiredFieldValidator
						ValidationGroup="AuthMethodGroup"
						CssClass="validator-block"
						Display="Dynamic"
						ControlCssClass="field_invalid"
						ControlToValidate="LdapAuthMethodSimpleUsername"
						Text="<%[ Please enter username. ]%>"
					>
						<prop:ClientSide.OnValidate>
							var is_ldap_auth = $('#<%=$this->LdapAuth->ClientID%>')[0].checked;
							var is_auth_simple = $('#<%=$this->LdapAuthMethodSimple->ClientID%>')[0].checked;
							sender.enabled = (is_ldap_auth && is_auth_simple);
						</prop:ClientSide.OnValidate>
					</com:TRequiredFieldValidator>
				</div>
			</div>
			<div class="w3-container w3-row directive_field">
				<div class="w3-third w3-col">
					<%[ Password: ]%>
				</div>
				<div class="w3-twothird w3-col">
					<com:TActiveTextBox
						ID="LdapAuthMethodSimplePassword"
						CssClass="w3-input w3-border w3-show-inline-block"
						CausesValidation="false"
						TextMode="Password"
						PersistPassword="true"
						Width="90%"
					/>
					<i class="fas fa-asterisk w3-text-red opt_req"></i>
					<com:TRequiredFieldValidator
						ValidationGroup="AuthMethodGroup"
						CssClass="validator-block"
						Display="Dynamic"
						ControlCssClass="field_invalid"
						ControlToValidate="LdapAuthMethodSimplePassword"
						Text="<%[ Please enter password. ]%>"
					>
						<prop:ClientSide.OnValidate>
							var is_ldap_auth = $('#<%=$this->LdapAuth->ClientID%>')[0].checked;
							var is_auth_simple = $('#<%=$this->LdapAuthMethodSimple->ClientID%>')[0].checked;
							sender.enabled = (is_ldap_auth && is_auth_simple);
						</prop:ClientSide.OnValidate>
					</com:TRequiredFieldValidator>
				</div>
			</div>
		</div>
		<div class="w3-container">
			<com:TActiveLinkButton
				ID="LdapAuthServerConnectionTest"
				ValidationGroup="AuthMethodGroup"
				CausesValidation="true"
				OnCallback="testLdapConnection"
				CssClass="w3-button w3-section w3-green w3-padding"
			>
				<i class="fa fa-plug"></i> &nbsp;<%[ Test LDAP connection ]%>
				<prop:ClientSide.OnLoading>
					document.getElementById('ldap_test_connection_ok').style.display = 'none';
					document.getElementById('ldap_test_connection_error').style.display = 'none';
					document.getElementById('ldap_test_connection_loading').style.visibility = 'visible';
				</prop:ClientSide.OnLoading>
				<prop:ClientSide.OnComplete>
					document.getElementById('ldap_test_connection_loading').style.visibility = 'hidden';
				</prop:ClientSide.OnComplete>
			</com:TActiveLinkButton>
			&nbsp;<i id="ldap_test_connection_loading" class="fas fa-sync w3-spin" style="visibility: hidden;"></i>
			<span id="ldap_test_connection_ok" class="w3-text-green" style="display: none;"><i class="fas fa-check w3-text-green"></i> &nbsp;<%[ OK ]%></span>
			<i id="ldap_test_connection_error" class="fas fa-times-circle w3-text-red" style="display: none;"></i>
			<com:TActiveLabel ID="TestLdapConnectionMsg" CssClass="w3-text-red" Display="None" />
		</div>
		<div class="w3-container w3-row directive_field">
			<div class="w3-third w3-col">
				<%[ Base DN: ]%>
			</div>
			<div class="w3-twothird w3-col">
				<com:TActiveTextBox
					ID="LdapAuthServerBaseDn"
					CssClass="w3-input w3-border w3-show-inline-block"
					CausesValidation="false"
					Width="90%"
				/>
				<i class="fas fa-asterisk w3-text-red opt_req"></i><br />
				<com:TRequiredFieldValidator
					ValidationGroup="AuthMethodGroup"
					CssClass="validator-block"
					Display="Dynamic"
					ControlCssClass="field_invalid"
					ControlToValidate="LdapAuthServerBaseDn"
					Text="<%[ Please enter Base DN. ]%>"
				>
					<prop:ClientSide.OnValidate>
						var is_test_con = (parameter && parameter.options.ID == '<%=$this->LdapAuthServerConnectionTest->ClientID%>');
						var is_ldap_enabled = $('#<%=$this->LdapAuth->ClientID%>')[0].checked;
						sender.enabled = (!is_test_con && is_ldap_enabled);
					</prop:ClientSide.OnValidate>
				</com:TRequiredFieldValidator>
			</div>
		</div>
		<div class="w3-container w3-row directive_field">
			<div class="w3-third w3-col">
				<%[ Filters: ]%>
			</div>
			<div class="w3-twothird w3-col">
				<com:TActiveTextBox
					ID="LdapAuthServerFilters"
					CssClass="w3-input w3-border w3-show-inline-block"
					CausesValidation="false"
					Width="90%"
				/>
				<i class="fa-solid fa-info-circle help_icon w3-text-green" style="display: inline-block;" onclick="const h = $(this).nextAll('div.directive_help'); var disp = h.get(0).style.display; $('div.directive_help').slideUp('fast'); if (disp == 'none') { h.slideDown('fast'); }"></i>
				<div class="directive_help" style="display: none">
					<p><%[ RFC 4515 compliant LDAP search filters are supported, for example: ]%></p>
					<ul>
						<li><small>(uid=m*)</small></li>
						<li><small>(|(uid=gani)(uid=ma*a))</small></li>
						<li><small>(&(objectClass=inetOrgPerson)(|(sn=masmika)(cn=gani*)))</small></li>
						<li><small>(&(objectClass=posixAccount)(objectClass=person)(|(cn=Admin*)(cn=Super*)))</small></li>
					</ul>
				</div>
			</div>
		</div>
		<h5><%[ Attributes ]%></h5>
		<div class="w3-container w3-row directive_field">
			<div class="w3-third w3-col">
				<%[ Username: ]%>
			</div>
			<div class="w3-twothird w3-col">
				<com:TActiveTextBox
					ID="LdapAttributesUsername"
					CssClass="w3-input w3-border w3-show-inline-block"
					CausesValidation="false"
					Text="uid"
					Width="90%"
				/>
				<i class="fas fa-asterisk w3-text-red opt_req"></i>
				<com:TRequiredFieldValidator
					ValidationGroup="AuthMethodGroup"
					CssClass="validator-block"
					Display="Dynamic"
					ControlCssClass="field_invalid"
					ControlToValidate="LdapAttributesUsername"
					Text="<%[ Please enter username attribute. ]%>"
				>
					<prop:ClientSide.OnValidate>
						sender.enabled = $('#<%=$this->LdapAuth->ClientID%>')[0].checked;
					</prop:ClientSide.OnValidate>
				</com:TRequiredFieldValidator>
			</div>
		</div>
		<div class="w3-container w3-row directive_field">
			<div class="w3-third w3-col">
				<%[ Long name: ]%>
			</div>
			<div class="w3-twothird w3-col">
				<com:TActiveTextBox
					ID="LdapAttributesLongName"
					CssClass="w3-input w3-border"
					CausesValidation="false"
					Text="sn"
					Width="90%"
				/>
			</div>
		</div>
		<div class="w3-container w3-row directive_field">
			<div class="w3-third w3-col">
				<%[ E-mail: ]%>
			</div>
			<div class="w3-twothird w3-col">
				<com:TActiveTextBox
					ID="LdapAttributesEmail"
					CssClass="w3-input w3-border"
					CausesValidation="false"
					Text="mail"
					Width="90%"
				/>
			</div>
		</div>
		<div class="w3-container w3-row directive_field">
			<div class="w3-third w3-col">
				<%[ Description: ]%>
			</div>
			<div class="w3-twothird w3-col">
				<com:TActiveTextBox
					ID="LdapAttributesDescription"
					CssClass="w3-input w3-border"
					CausesValidation="false"
					Width="90%"
				/>
			</div>
		</div>
		<div class="w3-container">
			<com:TActiveLinkButton
				ValidationGroup="AuthMethodGroup"
				CausesValidation="true"
				OnCommand="getLdapUsers"
				CommandParameter="load"
				CssClass="w3-button w3-section w3-green w3-padding"
			>
				<i class="fa fa-users"></i> &nbsp;<%[ Manage LDAP users ]%>
				<prop:ClientSide.OnLoading>
					document.getElementById('ldap_get_users_ok').style.display = 'none';
					document.getElementById('ldap_get_users_error').style.display = 'none';
					document.getElementById('ldap_get_users_loading').style.visibility = 'visible';
					oUserSecurity.show_user_selected_msg(false);
				</prop:ClientSide.OnLoading>
				<prop:ClientSide.OnComplete>
					document.getElementById('ldap_get_users_loading').style.visibility = 'hidden';
				</prop:ClientSide.OnComplete>
			</com:TActiveLinkButton>
			&nbsp;<i id="ldap_get_users_loading" class="fas fa-sync w3-spin" style="visibility: hidden;"></i>
			<span id="ldap_get_users_ok" class="w3-text-green" style="display: none;"><i class="fas fa-check w3-text-green"></i> &nbsp;<%[ OK ]%></span>
			<i id="ldap_get_users_error" class="fas fa-times-circle w3-text-red" style="display: none;"></i>
			<com:TActiveLabel ID="TestLdapGetUsersMsg" CssClass="w3-text-red" Display="None" />
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
				document.getElementById('auth_method_save_ok').style.display = 'none';
				document.getElementById('auth_method_save_error').style.display = 'none';
				document.getElementById('auth_method_save_loading').style.visibility = 'visible';
			</prop:ClientSide.OnLoading>
			<prop:ClientSide.OnComplete>
				document.getElementById('auth_method_save_loading').style.visibility = 'hidden';
			</prop:ClientSide.OnComplete>
		</com:TActiveLinkButton>
		&nbsp;<i id="auth_method_save_loading" class="fas fa-sync w3-spin" style="visibility: hidden;"></i>
		<span id="auth_method_save_ok" class="w3-text-green" style="display: none;"><i class="fas fa-check w3-text-green"></i> &nbsp;<%[ OK ]%></span>
		<span id="auth_method_save_error" class="w3-text-red" style="display: none"><i class="fas fa-times-circle w3-text-red"></i><%[ Error ]%></span>
	</div>

	<div id="get_users_modal" class="w3-modal" style="display: none">
		<div class="w3-modal-content w3-card-4 w3-animate-zoom" style="width: 990px">
			<header class="w3-container w3-green">
				<span onclick="oUserSecurity.show_user_modal(false);" class="w3-button w3-display-topright">×</span>
				<h2 id="get_users_table_title"></h2>
			</header>
			<div class="w3-margin-left w3-margin-right" style="max-height: 645px; margin: 10px auto;">
				<table id="get_users_table" class="display w3-table w3-striped w3-hoverable w3-margin-bottom selectable" style="width: 100%;">
					<thead>
						<tr>
							<th><%[ Username ]%></th>
							<th class="w3-center"><%[ Long name ]%></th>
							<th class="w3-center"><%[ Description ]%></th>
							<th class="w3-center"><%[ E-mail ]%></th>
						</tr>
					</thead>
					<tbody id="get_users_body"></tbody>
					<tfoot>
						<tr>
							<th><%[ Username ]%></th>
							<th class="w3-center"><%[ Long name ]%></th>
							<th class="w3-center"><%[ Description ]%></th>
							<th class="w3-center"><%[ E-mail ]%></th>
						</tr>
					</tfoot>
				</table>
				<p class="info w3-hide-medium w3-hide-small" style="margin: 4px 0;"><%[ Tip: Use left-click to select table row. Use CTRL + left-click to multiple row selection. Use SHIFT + left-click to add a range of rows to selection. ]%></p>
			</div>
			<footer class="w3-container w3-border-top">
				<div style="padding-top: 10px">
					<%[ Import options: ]%> <com:TActiveDropDownList
						ID="GetUsersImportOptions"
						CssClass="w3-select w3-border w3-show-inline-block"
						AutoPostBack="false"
						Width="250px"
					>
						<com:TListItem Value="0" Text="<%[ Import all users ]%>" />
						<com:TListItem Value="1" Text="<%[ Import selected users ]%>" />
						<com:TListItem Value="2" Text="<%[ Import users whose fulfill criteria ]%>" />
					</com:TActiveDropDownList>
					<div id="get_users_criteria" style="display: none;">
						<%[ Criteria filter: ]%>
						<com:TActiveDropDownList
							ID="GetUsersCriteria"
							CssClass="w3-select w3-border w3-show-inline-block"
							AutoPostBack="false"
							Width="150px"
						>
							<com:TListItem Attributes.id="get_users_criteria_filter_username" Value="0" Text="<%[ Username ]%>" />
							<com:TListItem Attributes.id="get_users_criteria_filter_long_name" Value="1" Text="<%[ Long name ]%>" />
							<com:TListItem Attributes.id="get_users_criteria_filter_desc" Value="2" Text="<%[ Description ]%>" />
							<com:TListItem Attributes.id="get_users_criteria_filter_email" Value="3" Text="<%[ E-mail ]%>" />
						</com:TActiveDropDownList>
						<%[ contains: ]%>
						<com:TActiveTextBox
							ID="GetUsersCriteriaFilter"
							CssClass="w3-input w3-border w3-show-inline-block"
							Width="130px"
							Attributes.onkeypress="oUserSecurity.on_reload_user_list(event);"
						/>
						<com:TActiveLinkButton
							ID="GetUsersCriteriaTest"
							CssClass="w3-button w3-green w3-show-inline-block"
							OnCallback="getUsers"
						>
							<i class="fa fa-sync-alt"></i> &nbsp;<%[ Reload ]%>
						</com:TActiveLinkButton>
					</div>
					<div id="get_users_selected_msg" style="display: none;">
						<%[ Please select users in table to import. ]%>
					</div>
				</div>
				<i class="fas fa-wrench"></i> &nbsp;<a href="javascript:void(0)" onclick="$('#get_users_advanced_options').toggle('fast');"><%[ Advanced options ]%></a>
				<div id="get_users_advanced_options" style="display: none">
					<div>
						<com:TActiveCheckBox
							ID="GetUsersProtectOverwrite"
							CssClass="w3-check"
							Checked="true"
							AutoPostBack="false"
						/>
						<label for="<%=$this->GetUsersProtectOverwrite->ClientID%>"><%[ Do not overwrite existing Bacularis Web users, if they have the same username ]%></label>
					</div>
					<div class="w3-row w3-section">
						<div class="w3-col w3-third"><com:TLabel ForControl="GetUsersDefaultRole" Text="<%[ Default role for imported users: ]%>" /></div>
						<div class="w3-half">
							<com:TActiveListBox
								ID="GetUsersDefaultRole"
								SelectionMode="Multiple"
								Rows="6"
								CssClass="w3-select w3-border"
								AutoPostBack="false"
							/>
							<p style="margin: 0 16px 0 0"><%[ Use CTRL + left-click to multiple item selection ]%></p>
							<com:TRequiredFieldValidator
								ValidationGroup="GetUsersGroup"
								ControlToValidate="GetUsersDefaultRole"
								ErrorMessage="<%[ At least one role must be selected. ]%>"
								ControlCssClass="field_invalid"
							/>
						</div> &nbsp;<i class="fa fa-asterisk w3-text-red opt_req"></i>
					</div>
					<div class="w3-row directive_field">
						<div class="w3-col w3-third">&nbsp;</div>
						<div class="w3-half">
							<com:TActiveRadioButton
								ID="GetUsersAPIHostsOpt"
								GroupName="GetUsersSelectAPIHosts"
								Checked="true"
								CssClass="w3-radio"
								Attributes.onclick="$('#get_users_default_api_host_groups').hide();$('#get_users_default_api_hosts').show();"
							/>
							<com:TLabel
								ForControl="GetUsersAPIHostsOpt"
								CssClass="normal w3-radio"
								Style="vertical-align: super"
								Text="<%[ Use API hosts ]%>"
							/>
						</div>
					</div>
					<div class="w3-row directive_field">
						<div class="w3-col w3-third">&nbsp;</div>
						<div class="w3-half">
							<com:TActiveRadioButton
								ID="GetUsersAPIHostGroupsOpt"
								GroupName="GetUsersSelectAPIHosts"
								CssClass="w3-radio"
								Attributes.onclick="$('#get_users_default_api_hosts').hide();$('#get_users_default_api_host_groups').show();"
							/>
							<com:TLabel
								ForControl="GetUsersAPIHostGroupsOpt"
								CssClass="normal w3-radio"
								Style="vertical-align: super"
								Text="<%[ Use API host groups ]%>"
							/>
						</div>
					</div>
					<div id="get_users_default_api_hosts" class="w3-row w3-section">
						<div class="w3-col w3-third"><com:TLabel ForControl="GetUsersDefaultAPIHosts" Text="<%[ Default API hosts for imported users: ]%>" /></div>
						<div class="w3-half">
							<com:TActiveListBox
								ID="GetUsersDefaultAPIHosts"
								SelectionMode="Multiple"
								Rows="6"
								CssClass="w3-select w3-border"
								AutoPostBack="false"
							/>
							<p style="margin: 0 16px 0 0"><%[ Use CTRL + left-click to multiple item selection ]%></p>
						</div>
					</div>
					<div id="get_users_default_api_host_groups" class="w3-row w3-section" style="display: none">
						<div class="w3-col w3-third"><com:TLabel ForControl="GetUsersDefaultAPIHostGroups" Text="<%[ Default API host groups for imported users: ]%>" /></div>
						<div class="w3-half">
							<com:TActiveListBox
								ID="GetUsersDefaultAPIHostGroups"
								SelectionMode="Multiple"
								Rows="6"
								CssClass="w3-select w3-border"
								AutoPostBack="false"
							/>
							<com:TRequiredFieldValidator
								ValidationGroup="GetUsersGroup"
								ControlToValidate="GetUsersDefaultAPIHostGroups"
								ErrorMessage="<%[ Field required. ]%>"
								ControlCssClass="field_invalid"
								Display="Dynamic"
							>
								<prop:ClientSide.OnValidate>
									const radio = document.getElementById('<%=$this->GetUsersAPIHostGroupsOpt->ClientID%>');
									sender.enabled = radio.checked;
								</prop:ClientSide.OnValidate>
							</com:TRequiredFieldValidator>
							<p style="margin: 0 16px 0 0"><%[ Use CTRL + left-click to multiple item selection ]%></p>
						</div> &nbsp;<i class="fa fa-asterisk w3-text-red opt_req"></i>
					</div>
					<div class="w3-row w3-section">
						<div class="w3-col w3-third"><com:TLabel ForControl="GetUsersDefaultOrganization" Text="<%[ Default organization for imported users: ]%>" /></div>
						<div class="w3-half">
							<com:TActiveDropDownList
								ID="GetUsersDefaultOrganization"
								CssClass="w3-select w3-border"
								AutoPostBack="false"
							/>
						</div>
					</div>
					<div class="w3-row w3-section" title="<%[ Comma separated IP addresses. Using asterisk character, there is also possible to provide subnet, for example: 192.168.1.* ]%>">
						<div class="w3-col w3-third"><com:TLabel ForControl="GetUsersDefaultIps" Text="<%[ Default IP address restrictions for imported users: ]%>" /></div>
						<div class="w3-half">
							<com:TActiveTextBox
								ID="GetUsersDefaultIps"
								AutoPostBack="false"
								MaxLength="500"
								CssClass="w3-input w3-border"
							/>
							<p><a href="javascript:void(0)" onclick="document.getElementById('<%=$this->GetUsersDefaultIps->ClientID%>').value = '<%=$_SERVER['REMOTE_ADDR']%>';"><%[ Set your IP address ]%></a></p>
							<com:TActiveCustomValidator
								ID="GetUsersDefaultIpsValidator"
								ValidationGroup="GetUsersGroup"
								ControlToValidate="GetUsersDefaultIps"
								OnServerValidate="validateIps"
								Display="Dynamic"
								ErrorMessage="<%[ Invalid IP address restrictions value. This field can have comma separated IP addresses only or subnet addresses like 192.168.1.* ]%>"
								ControlCssClass="field_invalid"
							/>
						</div>
					</div>
				</div>
				<div class="w3-center w3-margin-top w3-margin-bottom w3-border-top" style="padding-top: 10px;">
					<button type="button" class="w3-button w3-red" onclick="oUserSecurity.show_user_modal(false);"><i class="fa fa-times"></i> &nbsp;<%[ Close ]%></button>
					<com:TActiveLinkButton
						ID="GetUsersImport"
						CssClass="w3-button w3-green"
						CausesValidation="true"
						ValidationGroup="GetUsersGroup"
						OnCallback="importUsers"
						Attributes.onclick="const fm = Prado.Validation.getForm(); return (Prado.Validation.validate(fm, 'GetUsersGroup') && oUserSecurity.prepare_import());"
					>
						<prop:ClientSide.OnLoading>
							document.getElementById('get_users_loader').style.visibility = 'visible';
						</prop:ClientSide.OnLoading>
						<prop:ClientSide.OnComplete>
							document.getElementById('get_users_loader').style.visibility = 'hidden';
						</prop:ClientSide.OnComplete>
						<i class="fas fa-download"></i> &nbsp;<%[ Import users ]%>
					</com:TActiveLinkButton>
					<i id="get_users_loader" class="fa fa-sync w3-spin w3-margin-left" style="visibility: hidden"></i>
				</div>
			</footer>
		</div>
	</div>
	<com:TCallback ID="SelectedUsersImport" OnCallback="TemplateControl.importUsers" />
	<script>
var oUserSecurity = {
	ids: {
		auth_method_local: 'authentication_method_local',
		auth_method_basic: 'authentication_method_basic',
		auth_method_ldap: 'authentication_method_ldap',
		get_users: 'get_users_table',
		get_users_body: 'get_users_body',
		get_users_modal: 'get_users_modal',
		get_users_selected_msg: 'get_users_selected_msg',
		get_users_criteria: 'get_users_criteria',
		get_users_criteria_test: '<%=$this->GetUsersCriteriaTest->ClientID%>',
		get_users_import_opts: '<%=$this->GetUsersImportOptions->ClientID%>',
		filter_long_name: 'get_users_criteria_filter_long_name',
		filter_desc: 'get_users_criteria_filter_desc',
		filter_email: 'get_users_criteria_filter_email',
		table_title: 'get_users_table_title'
	},
	import_options: {
		all_users: <%=AuthenticationMethods::IMPORT_OPT_ALL_USERS%>,
		selected_users: <%=AuthenticationMethods::IMPORT_OPT_SELECTED_USERS%>,
		criteria: <%=AuthenticationMethods::IMPORT_OPT_CRITERIA%>
	},
	data: [],
	table: null,
	sel_users_import_cb: <%=$this->SelectedUsersImport->ActiveControl->Javascript%>,
	user_obj: null,
	init: function() {
		this.set_events();
	},
	select_auth_method: function(auth) {
		const auth_local = document.getElementById(this.ids.auth_method_local);
		const auth_basic = document.getElementById(this.ids.auth_method_basic);
		const auth_ldap = document.getElementById(this.ids.auth_method_ldap);

		// hide all auth containers
		[auth_local, auth_basic, auth_ldap].forEach(function(el) {
			el.style.display = 'none';
		});

		switch (auth) {
			case 'local': {
				auth_local.style.display = 'block';
				this.user_obj = oLocalUserSecurity;
				break;
			}
			case 'basic': {
				auth_basic.style.display = 'block';
				this.user_obj = oBasicUserSecurity;
				break;
			}
			case 'ldap': {
				auth_ldap.style.display = 'block';
				this.user_obj = oLdapUserSecurity;
				break;
			}
		}
	},
	set_events: function() {
		document.getElementById(this.ids.get_users).addEventListener('click', function(e) {
			$(function() {
				if (import_opts.value == this.import_options.selected_users) {
					var show = this.table.rows({selected: true}).data().length == 0;
					this.show_user_selected_msg(show);
				}
			}.bind(this));
		}.bind(this));
		var import_opts = document.getElementById(this.ids.get_users_import_opts);
		import_opts.addEventListener('change', function(e) {
			if (import_opts.value == this.import_options.criteria) {
				this.show_user_criteria(true);
			} else {
				this.show_user_criteria(false);
			}

			if (import_opts.value == this.import_options.selected_users && this.table.rows({selected: true}).data().length == 0) {
				this.show_user_selected_msg(true);
			} else {
				this.show_user_selected_msg(false);
			}
		}.bind(this));
	},
	set_table: function() {
		this.table = $('#' + this.ids.get_users).DataTable({
			data: this.data,
			deferRender: true,
			layout: {
				topStart: [
					{
						pageLength: {}
					},
					{
						buttons: ['copy', 'csv', 'colvis']
					}
				],
				topEnd: [
					'search'
				],
				bottomStart: [
					'info'
				],
				bottomEnd: [
					'paging'
				]
			},
			columns: [
				{data: 'username'},
				{
					data: 'long_name',
					visible: (this.user_obj.supported_fields.indexOf('long_name') !== -1)
				},
				{
					data: 'description',
					visible: (this.user_obj.supported_fields.indexOf('description') !== -1)
				},
				{
					data: 'email',
					visible: (this.user_obj.supported_fields.indexOf('email') !== -1)
				}
			],
			responsive: {
				details: {
					type: 'column',
					display: DataTable.Responsive.display.childRow
				}
			},
			columnDefs: [{
				className: "dt-center",
				targets: [ 1, 2, 3 ]
			}],
			select: {
				style:    'os',
				selector: 'td',
				blurable: false
			},
			order: [1, 'asc']
		});
	},
	destroy_table: function() {
		if (this.table) {
			this.table.destroy();
		}
	},
	prepare_import: function() {
		ret = true;
		var import_opts = document.getElementById(this.ids.get_users_import_opts);
		if (import_opts.value == this.import_options.selected_users) {
			var users = this.table.rows({selected: true}).data().toArray();
			this.sel_users_import_cb.setCallbackParameter(users);
			this.sel_users_import_cb.dispatch();
			ret = false; // to stop sending click event in original button
		}
		return ret;
	},
	set_user_table_cb: function(data, obj) {
		oUserSecurity.data = data;
		oUserSecurity.destroy_table();
		oUserSecurity.set_table();
		oUserSecurity.show_user_modal(true);
	},
	show_user_modal: function(show) {
		var modal = document.getElementById(oUserSecurity.ids.get_users_modal);
		if (show) {
			modal.style.display = 'block';
			if (this.user_obj) {
				this.user_obj.show_user_modal();
			}
		} else {
			modal.style.display = 'none';
		}
	},
	show_user_criteria: function(show) {
		document.getElementById(this.ids.get_users_criteria).style.display = (show ? 'inline-block' : 'none');
	},
	show_user_selected_msg: function(show) {
		document.getElementById(this.ids.get_users_selected_msg).style.display = (show ? 'inline-block' : 'none');
	},
	on_reload_user_list: function(e) {
		var x = e.which || e.keyCode;
		if (x === 13) {
			$('#' + this.ids.get_users_criteria_test).click();
		}
	}
};

var oLocalUserSecurity = {
	ids: {},
	name: 'local',
	table_title: '',
	supported_fields: [],
	enabled: <%=$this->LocalAuth->Checked ? 'true' : 'false'%>,
	init: function() {
		jQuery.extend(this.ids, oUserSecurity.ids);
		if (this.enabled) {
			oUserSecurity.select_auth_method(this.name);
		}
	},
	show_user_modal: function() {
	}
};

var oBasicUserSecurity = {
	ids: {},
	name: 'basic',
	table_title: '<%[ Basic user list ]%>',
	supported_fields: ['username'],
	enabled: <%=$this->BasicAuth->Checked ? 'true' : 'false'%>,
	init: function() {
		jQuery.extend(this.ids, oUserSecurity.ids);
		if (this.enabled) {
			oUserSecurity.select_auth_method(this.name);
		}
	},
	show_user_modal: function() {
		document.getElementById(this.ids.table_title).textContent = this.table_title;
		document.getElementById(this.ids.filter_long_name).disabled = true;
		document.getElementById(this.ids.filter_desc).disabled = true;
		document.getElementById(this.ids.filter_email).disabled = true;
	}
};

var oLdapUserSecurity = {
	ids: {
		auth_simple: 'authentication_method_ldap_auth_simple',
	},
	name: 'ldap',
	table_title: '<%[ LDAP user list ]%>',
	supported_fields: ['username', 'long_name', 'description', 'email'],
	enabled: <%=$this->LdapAuth->Checked ? 'true' : 'false'%>,
	init: function() {
		jQuery.extend(this.ids, oUserSecurity.ids);
		if (this.enabled) {
			oUserSecurity.select_auth_method(this.name);
		}
	},
	show_ldap_auth: function(auth) {
		var auth_simple = document.getElementById(this.ids.auth_simple);
		auth_simple.style.display = (auth === 'simple') ? 'block' : 'none';
	},
	show_user_modal: function() {
		document.getElementById(this.ids.table_title).textContent = this.table_title;
		document.getElementById(this.ids.filter_long_name).disabled = false;
		document.getElementById(this.ids.filter_desc).disabled = false;
		document.getElementById(this.ids.filter_email).disabled = false;
	}
};

function validate_ips(sender, parameter) {
	return validate_comma_separated_list(parameter);
}
oUserSecurity.init();
oLocalUserSecurity.init();
oBasicUserSecurity.init();
oLdapUserSecurity.init();
	</script>
</div>
