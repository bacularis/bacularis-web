<div class="directive_field w3-row<%=!$this->display_directive ? ' hide' : '';%>">
	<div class="w3-col w3-quarter">
		<com:TActiveLabel ID="Label" ActiveControl.EnableUpdate="false" Visible="<%=$this->display_directive%>" />:
		<div class="w3-right" style="width: 24px; padding-top: 9px;"><i class="fa-solid <%=$this->text_mode ? 'fa-list' : 'fa-edit'%> pointer" onclick="oDirectiveEditableOrderedListBox.switch_mode(this);"></i></div>
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
			<com:TActiveListBox ID="DirectiveCombo"
				SelectionMode="Multiple"
				OnSelectedIndexChanged="saveValue"
				CssClass="w3-input w3-border w3-twothird"
				Visible="<%=$this->display_directive%>"
				AutoPostBack="false"
				ActiveControl.EnableUpdate="false"
				ClientSide.OnComplete="oDirectiveOrderedListBox.set_options('<%=$this->DirectiveCombo->ClientID%>', '<%=$this->DirectiveHidden->ClientID%>');"
				Attributes.onchange="if (this.value) { $('#<%=$this->DirectiveText->ClientID%>').val(''); }"
			/>
		</div>
		 &nbsp;<i class="fa fa-asterisk w3-text-red directive_required" style="visibility: <%=$this->getRequired() ? 'visible' : 'hidden'%>;"></i>
		<i class="fas fa-info-circle help_icon w3-text-green" style="display: <%=($this->doc ? 'inline-block': 'none')%>;" onclick="var h = $(this).nextAll('div.directive_help'); var disp = h.get(0).style.display; $('div.directive_help').slideUp('fast'); if (disp == 'none') { h.slideDown('fast'); }"></i>
		<i class="fa fa-trash-alt remove_btn<%=!$this->ShowRemoveButton ? ' hide' : ''%>" onclick="oDirectiveOrderedListBox.clear_selection('<%=$this->DirectiveCombo->ClientID%>', '<%=$this->DirectiveHidden->ClientID%>'); document.getElementById('<%=$this->DirectiveText->ClientID%>').value = '';" alt="<%[ Remove directive ]%>" title="<%[ Remove directive ]%>"></i>
		<com:TRequiredFieldValidator
			ID="DirectiveValidator"
			ValidationGroup="<%=$this->getValidationGroup()%>"
			Display="Dynamic"
			ControlToValidate="DirectiveCombo"
			FocusOnError="true"
			Text="Field required."
			Enabled="<%=$this->getRequired() && $this->getShow()%>"
		/>
		<com:TActiveHiddenField ID="DirectiveHidden" />
		<p rel="info_combo" class="w3-row w3-padding"><%[ Use CTRL + left-click to multiple item selection ]%></p>
		<p rel="info_text" class="w3-row" style="margin-top: 0; padding-left: 8px; display: none;"><%[ Enter comma separated values to multiple item selection ]%></p>
		<div class="directive_help" style="clear: left; display: none"><%=$this->doc%></div>
	</div>
	<script>
document.getElementById('<%=$this->DirectiveCombo->ClientID%>').addEventListener('click', function(e) {
	oDirectiveOrderedListBox.set_items(e, this);
	oDirectiveOrderedListBox.set_options(
		'<%=$this->DirectiveCombo->ClientID%>',
		'<%=$this->DirectiveHidden->ClientID%>'
	);
});
$('#<%=$this->DirectiveCombo->ClientID%> option').on('mousemove', function(e) {
	// Disable selecting multiple items by mouse drag
	return false;
});
function <%=$this->ClientID%>_load_ordered_list_box() {
	oDirectiveOrderedListBox.init_items(
		'<%=$this->DirectiveCombo->ClientID%>',
		'<%=$this->DirectiveHidden->ClientID%>'
	);
}
<%=$this->ClientID%>_load_ordered_list_box();
	</script>
</div>
