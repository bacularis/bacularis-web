<div class="directive_field w3-row w3-border w3-padding w3-margin-bottom<%=!$this->display_directive ? ' hide' : '';%>">
	<div class="w3-col w3-left" style="width: 180px; padding: 8px 0;">
		<com:TActiveLabel
			ID="Label"
			ActiveControl.EnableUpdate="false"
			Visible="<%=$this->display_directive%>"
		 />:
	</div>
	<div class="w3-col w3-left directive_value" style="max-width: 1000px">
		<div class="w3-row<%=$this->ShowOptions === false? ' w3-hide' : ''%>">
			<com:TCheckBox
				ID="AllWeeksOfMonth"
				OnCheckedChanged="saveValue"
				CssClass="w3-check"
				AutoPostBack="false"
			/> <label for="<%=$this->AllWeeksOfMonth->ClientID%>"><%[ All weeks ]%></label> &nbsp;
		</div>
		<div class="w3-row">
			<com:TActiveCheckBox
				ID="first"
				OnCheckedChanged="saveValue"
				CssClass="w3-check w3-border w3-show-inline-block wom"
				Visible="<%=$this->display_directive%>"
				ActiveControl.EnableUpdate="false"
				AutoPostBack="false"
			/> <label for="<%=$this->first->ClientID%>"><%[ first ]%></label> &nbsp;
			<com:TActiveCheckBox
				ID="second"
				OnCheckedChanged="saveValue"
				CssClass="w3-check w3-border w3-show-inline-block wom"
				Visible="<%=$this->display_directive%>"
				ActiveControl.EnableUpdate="false"
				AutoPostBack="false"
			/> <label for="<%=$this->second->ClientID%>"><%[ second ]%></label> &nbsp;
			<com:TActiveCheckBox
				ID="third"
				OnCheckedChanged="saveValue"
				CssClass="w3-check w3-border w3-show-inline-block wom"
				Visible="<%=$this->display_directive%>"
				ActiveControl.EnableUpdate="false"
				AutoPostBack="false"
			/> <label for="<%=$this->third->ClientID%>"><%[ third ]%></label> &nbsp;
			<com:TActiveCheckBox
				ID="fourth"
				OnCheckedChanged="saveValue"
				CssClass="w3-check w3-border w3-show-inline-block wom"
				Visible="<%=$this->display_directive%>"
				ActiveControl.EnableUpdate="false"
				AutoPostBack="false"
			/> <label for="<%=$this->fourth->ClientID%>"><%[ fourth ]%></label> &nbsp;
			<com:TActiveCheckBox
				ID="fifth"
				OnCheckedChanged="saveValue"
				CssClass="w3-check w3-border w3-show-inline-block wom"
				Visible="<%=$this->display_directive%>"
				ActiveControl.EnableUpdate="false"
				AutoPostBack="false"
			/> <label for="<%=$this->fifth->ClientID%>"><%[ fifth ]%></label> &nbsp;
			<com:TActiveCheckBox
				ID="sixth"
				OnCheckedChanged="saveValue"
				CssClass="w3-check w3-border w3-show-inline-block wom"
				Visible="<%=$this->display_directive%>"
				ActiveControl.EnableUpdate="false"
				AutoPostBack="false"
			/> <label for="<%=$this->sixth->ClientID%>"><%[ sixth ]%></label> &nbsp;
		</div>
		<script>
			var <%=$this->AllWeeksOfMonth->ClientID%>_check_all = function(check) {
				$('#<%=$this->AllWeeksOfMonth->ClientID%>').closest('.directive_value').find('input[type=\'checkbox\'].wom').prop('disabled', check);
			};
			<%=$this->AllWeeksOfMonth->ClientID%>_check_all($('#<%=$this->AllWeeksOfMonth->ClientID%>').is(':checked'));
			document.getElementById('<%=$this->AllWeeksOfMonth->ClientID%>').addEventListener('click', function(e) {
				<%=$this->AllWeeksOfMonth->ClientID%>_check_all(this.checked);
			});
		</script>
	</div>
</div>
