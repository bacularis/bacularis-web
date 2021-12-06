<div class="directive_field w3-row w3-border w3-padding w3-margin-bottom<%=!$this->display_directive ? ' hide' : '';%>">
	<div class="w3-col w3-left" style="width: 180px; padding: 8px 0;">
		<com:TActiveLabel
			ID="Label"
			ActiveControl.EnableUpdate="false"
			Visible="<%=$this->display_directive%>"
		 />:
	</div>
	<div class="w3-col w3-left directive_value" style="max-width: 1200px">
		<div class="w3-row<%=$this->ShowOptions === false? ' w3-hide' : ''%>">
			<com:TCheckBox
				ID="AllMonthsOfYear"
				OnCheckedChanged="saveValue"
				CssClass="w3-check"
				AutoPostBack="false"
			/> <label for="<%=$this->AllMonthsOfYear->ClientID%>"><%[ All months ]%></label> &nbsp;
		</div>
		<div class="w3-row">
			<com:TActiveCheckBox
				ID="January"
				OnCheckedChanged="saveValue"
				CssClass="w3-check w3-border w3-show-inline-block dow"
				Visible="<%=$this->display_directive%>"
				ActiveControl.EnableUpdate="false"
				AutoPostBack="false"
			/> <label for="<%=$this->January->ClientID%>"><%[ January ]%></label> &nbsp;
			<com:TActiveCheckBox
				 ID="February"
				OnCheckedChanged="saveValue"
				CssClass="w3-check w3-border w3-show-inline-block dow"
				Visible="<%=$this->display_directive%>"
				ActiveControl.EnableUpdate="false"
				AutoPostBack="false"
			/> <label for="<%=$this->February->ClientID%>"><%[ February ]%></label> &nbsp;
			<com:TActiveCheckBox
				ID="March"
				OnCheckedChanged="saveValue"
				CssClass="w3-check w3-border w3-show-inline-block dow"
				Visible="<%=$this->display_directive%>"
				ActiveControl.EnableUpdate="false"
				AutoPostBack="false"
			/> <label for="<%=$this->March->ClientID%>"><%[ March ]%></label> &nbsp;
			<com:TActiveCheckBox
				ID="April"
				OnCheckedChanged="saveValue"
				CssClass="w3-check w3-border w3-show-inline-block dow"
				Visible="<%=$this->display_directive%>"
				ActiveControl.EnableUpdate="false"
				AutoPostBack="false"
			/> <label for="<%=$this->April->ClientID%>"><%[ April ]%></label> &nbsp;
			<com:TActiveCheckBox
				ID="May"
				OnCheckedChanged="saveValue"
				CssClass="w3-check w3-border w3-show-inline-block dow"
				Visible="<%=$this->display_directive%>"
				ActiveControl.EnableUpdate="false"
				AutoPostBack="false"
			/> <label for="<%=$this->May->ClientID%>"><%[ May ]%></label> &nbsp;
			<com:TActiveCheckBox
				ID="June"
				OnCheckedChanged="saveValue"
				CssClass="w3-check w3-border w3-show-inline-block dow"
				Visible="<%=$this->display_directive%>"
				ActiveControl.EnableUpdate="false"
				AutoPostBack="false"
			/> <label for="<%=$this->June->ClientID%>"><%[ June ]%></label> &nbsp;
			<com:TActiveCheckBox
				ID="July"
				OnCheckedChanged="saveValue"
				CssClass="w3-check w3-border w3-show-inline-block dow"
				Visible="<%=$this->display_directive%>"
				ActiveControl.EnableUpdate="false"
				AutoPostBack="false"
			/> <label for="<%=$this->July->ClientID%>"><%[ July ]%></label> &nbsp;
			<com:TActiveCheckBox
				ID="August"
				OnCheckedChanged="saveValue"
				CssClass="w3-check w3-border w3-show-inline-block dow"
				Visible="<%=$this->display_directive%>"
				ActiveControl.EnableUpdate="false"
				AutoPostBack="false"
			/> <label for="<%=$this->August->ClientID%>"><%[ August ]%></label> &nbsp;
			<com:TActiveCheckBox
				ID="September"
				OnCheckedChanged="saveValue"
				CssClass="w3-check w3-border w3-show-inline-block dow"
				Visible="<%=$this->display_directive%>"
				ActiveControl.EnableUpdate="false"
				AutoPostBack="false"
			/> <label for="<%=$this->September->ClientID%>"><%[ September ]%></label> &nbsp;
			<com:TActiveCheckBox
				ID="October"
				OnCheckedChanged="saveValue"
				CssClass="w3-check w3-border w3-show-inline-block dow"
				Visible="<%=$this->display_directive%>"
				ActiveControl.EnableUpdate="false"
				AutoPostBack="false"
			/> <label for="<%=$this->October->ClientID%>"><%[ October ]%></label> &nbsp;
			<com:TActiveCheckBox
				ID="November"
				OnCheckedChanged="saveValue"
				CssClass="w3-check w3-border w3-show-inline-block dow"
				Visible="<%=$this->display_directive%>"
				ActiveControl.EnableUpdate="false"
				AutoPostBack="false"
			/> <label for="<%=$this->November->ClientID%>"><%[ November ]%></label> &nbsp;
			<com:TActiveCheckBox
				ID="December"
				OnCheckedChanged="saveValue"
				CssClass="w3-check w3-border w3-show-inline-block dow"
				Visible="<%=$this->display_directive%>"
				ActiveControl.EnableUpdate="false"
				AutoPostBack="false"
			/> <label for="<%=$this->December->ClientID%>"><%[ December ]%></label> &nbsp;
		</div>
		<script>
			var <%=$this->AllMonthsOfYear->ClientID%>_check_all = function(check) {
				$('#<%=$this->AllMonthsOfYear->ClientID%>').closest('.directive_value').find('input[type=\'checkbox\'].dow').prop('disabled', check);
			}
			<%=$this->AllMonthsOfYear->ClientID%>_check_all($('#<%=$this->AllMonthsOfYear->ClientID%>').is(':checked'));
			document.getElementById('<%=$this->AllMonthsOfYear->ClientID%>').addEventListener('click', function(e) {
				<%=$this->AllMonthsOfYear->ClientID%>_check_all(this.checked);
			});
		</script>
	</div>
</div>
