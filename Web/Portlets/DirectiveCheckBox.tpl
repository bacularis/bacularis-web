<div class="directive_field w3-row<%=!$this->display_directive ? ' hide' : '';%>">
	<div class="w3-col w3-quarter"><com:TActiveLabel ID="Label" ActiveControl.EnableUpdate="false" Visible="<%=$this->display_directive%>" />:</div>
	<div class="w3-col w3-threequarter directive_value">
		<com:TActiveCheckBox
			ID="Directive"
			CssClass="w3-check"
			AutoPostBack="false"
			Visible="<%=$this->display_directive%>"
			OnCheckedChanged="saveValue"
			ActiveControl.EnableUpdate="false"
		/>
		<i class="fas fa-info-circle help_icon w3-text-green" style="display: <%=($this->doc ? 'inline-block': 'none')%>;" onclick="var h = $(this).nextAll('div.directive_help'); var disp = h.get(0).style.display; $('div.directive_help').slideUp('fast'); if (disp == 'none') { h.slideDown('fast'); }"></i>
		<i class="fa fa-undo reset_btn<%=!$this->ShowResetButton ? ' hide' : ''%>" onclick="document.getElementById('<%=$this->Directive->ClientID%>').checked = <%=$this->getDefaultValue() ? 'true' : 'false'%>;" alt="<%[ Reset to default value ]%>" title="<%[ Reset to default value ]%>"></i>
		<div class="directive_help" style="display: none"><%=$this->doc%></div>
	</div>
</div>
