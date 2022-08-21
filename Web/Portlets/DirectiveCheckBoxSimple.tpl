<div class="directive_field <%=!$this->display_directive ? ' hide' : '';%>" style="min-width: 100px; float: left;">
	<span class="directive_value">
	<com:TActiveCheckBox
		ID="Directive"
		CssClass="w3-check"
		AutoPostBack="false"
		Visible="<%=$this->display_directive%>"
		OnCheckedChanged="saveValue"
		ActiveControl.EnableUpdate="false"
	/></span> &nbsp;<com:TActiveLabel ID="Label" ForControl="Directive" ActiveControl.EnableUpdate="false" Visible="<%=$this->display_directive%>" />
	<i class="fas fa-info-circle help_icon w3-text-green" style="display: <%=($this->doc ? 'inline-block': 'none')%>;" onclick="var h = $(this).nextAll('div.directive_help'); var disp = h.get(0).style.display; $('div.directive_help').slideUp('fast'); if (disp == 'none') { h.slideDown('fast'); }"></i>
	<i class="fa fa-undo reset_btn<%=!$this->ShowResetButton ? ' hide' : ''%>" onclick="document.getElementById('<%=$this->Directive->ClientID%>').checked = <%=$this->getDefaultValue() ? 'true' : 'false'%>;" alt="<%[ Reset to default value ]%>" title="<%[ Reset to default value ]%>"></i>
	<div class="directive_help" style="display: none"><%=$this->doc%></div>
</div>
