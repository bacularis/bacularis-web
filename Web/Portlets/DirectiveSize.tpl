<div class="directive_field w3-row<%=!$this->display_directive ? ' hide' : '';%>">
	<div class="w3-col w3-quarter"><com:TActiveLabel ID="Label" ActiveControl.EnableUpdate="false" Visible="<%=$this->display_directive%>" />:</div>
	<div class="w3-col w3-threequarter directive_value">
		<com:TActiveTextBox ID="Directive"
			OnTextChanged="saveValue"
			CssClass="w3-input w3-border w3-half"
			Visible="<%=$this->display_directive%>"
		/>
		<com:TActiveDropDownList
			ID="SizeFormat"
			CssClass="w3-select w3-border w3-half"
			DataTextField="label"
			DataValueField="format"
			Visible="<%=$this->display_directive%>"
			AutoPostBack="false"
			OnSelectedIndexChanged="saveValue"
		/> &nbsp;<i class="fa fa-asterisk w3-text-red directive_required" style="visibility: <%=$this->getRequired() ? 'visible' : 'hidden'%>;"></i>
		<i class="fas fa-info-circle help_icon w3-text-green" style="display: <%=($this->doc ? 'inline-block': 'none')%>;" onclick="var h = $(this).nextAll('div.directive_help'); var disp = h.get(0).style.display; $('div.directive_help').slideUp('fast'); if (disp == 'none') { h.slideDown('fast'); }"></i>
		<i class="fa fa-undo reset_btn<%=!$this->ShowResetButton ? ' hide' : ''%>" onclick="var fsize = Units.format_size(parseInt('<%=$this->getDefaultValue()%>', 10), 'B'); document.getElementById('<%=$this->Directive->ClientID%>').value = fsize.value; document.getElementById('<%=$this->SizeFormat->ClientID%>').value = fsize.format;" alt="<%[ Reset to default value ]%>" title="<%[ Reset to default value ]%>"></i>
		<i class="fa fa-trash-alt remove_btn<%=!$this->ShowRemoveButton ? ' hide' : ''%>" onclick="document.getElementById('<%=$this->Directive->ClientID%>').value = '';" alt="<%[ Remove directive ]%>" title="<%[ Remove directive ]%>"></i>
		<com:TRequiredFieldValidator
			ID="DirectiveValidator"
			ValidationGroup="<%=$this->getValidationGroup()%>"
			Display="Dynamic"
			ControlToValidate="Directive"
			FocusOnError="true"
			Text="Field required."
			Enabled="<%=$this->getRequired() && $this->getShow()%>"
		/>
		<div class="directive_help" style="clear: left; display: none"><%=$this->doc%></div>
	</div>
</div>
