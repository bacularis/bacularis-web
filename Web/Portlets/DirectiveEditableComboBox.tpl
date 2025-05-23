<div class="directive_field w3-row<%=!$this->display_directive ? ' hide' : '';%>">
	<div class="w3-col w3-quarter">
		<com:TActiveLabel ID="Label" ActiveControl.EnableUpdate="false" Visible="<%=$this->display_directive%>" />:
		<div class="w3-right" style="width: 24px; padding-top: 9px;"><i class="fa-solid <%=$this->text_mode ? 'fa-list' : 'fa-edit'%> pointer" onclick="oDirectiveEditableComboBox.switch_mode(this);"></i></div>
	</div>
	<div class="w3-col w3-threequarter directive_value">
		<div rel="text_field" style="display: <%=$this->text_mode ? 'block' : 'none'%>;">
			<com:TActiveTextBox ID="DirectiveText"
				OnTextChanged="saveValue"
				CssClass="w3-input w3-border w3-twothird"
				Visible="<%=$this->display_directive%>"
				AutoPostBack="false"
				ActiveControl.EnableUpdate="false"
				AutoTrim="true"
			/>
		</div>
		<div rel="combo_field"<%=$this->text_mode ? ' style="display: none"': ''%>>
			<com:TActiveDropDownList ID="DirectiveCombo"
				OnSelectedIndexChanged="saveValue"
				CssClass="w3-select w3-border w3-twothird"
				Visible="<%=$this->display_directive%>"
				AutoPostBack="false"
				ActiveControl.EnableUpdate="false"
				Attributes.onchange="if (this.value) { $('#<%=$this->DirectiveText->ClientID%>').val(''); }"
			/>
		</div>
		&nbsp;<i class="fa fa-asterisk w3-text-red directive_required" style="visibility: <%=$this->getRequired() ? 'visible' : 'hidden'%>;"></i>
		<i class="fas fa-info-circle help_icon w3-text-green" style="display: <%=($this->doc ? 'inline-block': 'none')%>;" onclick="var h = $(this).nextAll('div.directive_help'); var disp = h.get(0).style.display; $('div.directive_help').slideUp('fast'); if (disp == 'none') { h.slideDown('fast'); }"></i>
		<i class="fa fa-undo reset_btn<%=!$this->ShowResetButton ? ' hide' : ''%>" onclick="document.getElementById('<%=$this->DirectiveCombo->ClientID%>').value = document.getElementById('<%=$this->DirectiveText->ClientID%>').value = '<%=$this->getDefaultValue() === 0 ? '' : $this->getDefaultValue()%>';" alt="<%[ Reset to default value ]%>" title="<%[ Reset to default value ]%>"></i>
		<i class="fa fa-trash-alt remove_btn<%=!$this->ShowRemoveButton ? ' hide' : ''%>" onclick="document.getElementById('<%=$this->DirectiveCombo->ClientID%>').value = document.getElementById('<%=$this->DirectiveText->ClientID%>').value = '';" alt="<%[ Remove directive ]%>" title="<%[ Remove directive ]%>"></i>
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
