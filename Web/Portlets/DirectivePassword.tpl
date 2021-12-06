<div class="directive_field w3-row<%=!$this->display_directive ? ' hide' : '';%>">
	<div class="w3-col w3-quarter"><com:TActiveLabel ID="Label" ActiveControl.EnableUpdate="false" Visible="<%=$this->display_directive%>" />:</div>
	<div class="w3-col w3-threequarter directive_value">
		<com:TActiveTextBox ID="Directive"
			TextMode="Password"
			PersistPassword="true"
			OnTextChanged="saveValue"
			CssClass="w3-input w3-border w3-twothird"
			Visible="<%=$this->display_directive%>"
			ActiveControl.EnableUpdate="false"
			AutoTrim="true"
		/> &nbsp;<i class="fa fa-asterisk w3-text-red directive_required" style="visibility: <%=$this->getRequired() ? 'visible' : 'hidden'%>;"></i>
		<i class="fas fa-info-circle help_icon w3-text-green" style="display: <%=($this->doc ? 'inline-block': 'none')%>;" onclick="var h = $(this).nextAll('div.directive_help'); var disp = h.get(0).style.display; $('div.directive_help').slideUp('fast'); if (disp == 'none') { h.slideDown('fast'); }"></i>
		<a href="javascript:void(0)" onclick="var el = document.getElementById('<%=$this->Directive->ClientID%>'); el.type = el.type == 'text' ? 'password' : 'text'" title="<%[ Show/hide password ]%>"><i class="fa fa-eye"></i></a>
		<a href="javascript:void(0)" onclick="document.getElementById('<%=$this->Directive->ClientID%>').value = get_random_string('ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-\/_', 40); return false;" title="<%[ Generate new password ]%>"><i class="fas fa-random"></i></a>
		<i class="fa fa-undo reset_btn<%=!$this->ShowResetButton ? ' hide' : ''%>" onclick="document.getElementById('<%=$this->Directive->ClientID%>').value = '<%=$this->getDefaultValue() === 0 ? '' : $this->getDefaultValue()%>';" alt="<%[ Reset to default value ]%>" title="<%[ Reset to default value ]%>"></i>
		<i class="fa fa-trash-alt remove_btn<%=!$this->ShowRemoveButton ? ' hide' : ''%>" onclick="document.getElementById('<%=$this->Directive->ClientID%>').value = '';" alt="<%[ Remove directive ]%>" title="<%[ Remove directive ]%>"></i>
		<com:TRequiredFieldValidator
			ID="DirectiveValidator"
			ValidationGroup="<%=$this->getValidationGroup()%>"
			Display="Dynamic"
			ControlToValidate="Directive"
			FocusOnError="true"
			Enabled="<%=$this->getRequired() && $this->getShow()%>"
		><%[ Field required. ]%></com:TRequiredFieldValidator>
		<div class="directive_help" style="clear: left; display: none"><%=$this->doc%></div>
	</div>
</div>
