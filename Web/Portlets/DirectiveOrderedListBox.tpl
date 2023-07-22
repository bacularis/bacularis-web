<div class="directive_field w3-row<%=!$this->display_directive ? ' hide' : '';%>">
	<div class="w3-col w3-quarter"><com:TActiveLabel ID="Label" ActiveControl.EnableUpdate="false" Visible="<%=$this->display_directive%>" />:</div>
	<div class="w3-col w3-threequarter directive_value">
		<com:TActiveListBox ID="Directive"
			SelectionMode="Multiple"
			OnSelectedIndexChanged="saveValue"
			CssClass="w3-input w3-border w3-twothird"
			Visible="<%=$this->display_directive%>"
			AutoPostBack="false"
			ActiveControl.EnableUpdate="false"
			ClientSide.OnComplete="oDirectiveOrderedListBox.set_options('<%=$this->Directive->ClientID%>', '<%=$this->DirectiveHidden->ClientID%>');"
		/> &nbsp;<i class="fa fa-asterisk w3-text-red directive_required" style="visibility: <%=$this->getRequired() ? 'visible' : 'hidden'%>;"></i>
		<i class="fas fa-info-circle help_icon w3-text-green" style="display: <%=($this->doc ? 'inline-block': 'none')%>;" onclick="var h = $(this).nextAll('div.directive_help'); var disp = h.get(0).style.display; $('div.directive_help').slideUp('fast'); if (disp == 'none') { h.slideDown('fast'); }"></i>
		<!--i class="fa fa-undo reset_btn<%=!$this->ShowResetButton ? ' hide' : ''%>" onclick="document.getElementById('<%=$this->Directive->ClientID%>').value = '<%=$this->getDefaultValue() === 0 ? '' : $this->getDefaultValue()%>';" alt="<%[ Reset to default value ]%>" title="<%[ Reset to default value ]%>"></i-->
		<i class="fa fa-trash-alt remove_btn<%=!$this->ShowRemoveButton ? ' hide' : ''%>" onclick="oDirectiveOrderedListBox.clear_selection('<%=$this->Directive->ClientID%>', '<%=$this->DirectiveHidden->ClientID%>');" alt="<%[ Remove directive ]%>" title="<%[ Remove directive ]%>"></i>
		<com:TRequiredFieldValidator
			ID="DirectiveValidator"
			ValidationGroup="<%=$this->getValidationGroup()%>"
			Display="Dynamic"
			ControlToValidate="Directive"
			FocusOnError="true"
			Text="Field required."
			Enabled="<%=$this->getRequired() && $this->getShow()%>"
		/>
		<com:TActiveHiddenField ID="DirectiveHidden" />
		<p class="w3-row w3-padding"><%[ Use CTRL + left-click to multiple item selection ]%></p>
		<div class="directive_help" style="clear: left; display: none"><%=$this->doc%></div>
	</div>
	<script>
document.getElementById('<%=$this->Directive->ClientID%>').addEventListener('click', function(e) {
	oDirectiveOrderedListBox.set_items(e, this);
	oDirectiveOrderedListBox.set_options(
		'<%=$this->Directive->ClientID%>',
		'<%=$this->DirectiveHidden->ClientID%>'
	);
});
$('#<%=$this->Directive->ClientID%> option').on('mousemove', function(e) {
	// Disable selecting multiple items by mouse drag
	return false;
});
function <%=$this->ClientID%>_load_ordered_list_box() {
	oDirectiveOrderedListBox.init_items(
		'<%=$this->Directive->ClientID%>',
		'<%=$this->DirectiveHidden->ClientID%>'
	);
}
<%=$this->ClientID%>_load_ordered_list_box();
	</script>
</div>
