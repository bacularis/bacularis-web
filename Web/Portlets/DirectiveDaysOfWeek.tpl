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
				ID="AllDaysOfWeek"
				OnCheckedChanged="saveValue"
				CssClass="w3-check"
				AutoPostBack="false"
			/> <label for="<%=$this->AllDaysOfWeek->ClientID%>"><%[ All days ]%></label> &nbsp;
		</div>
		<div class="w3-row">
			<com:TActiveCheckBox
				ID="Sunday"
				OnCheckedChanged="saveValue"
				CssClass="w3-check w3-border w3-show-inline-block dow"
				Visible="<%=$this->display_directive%>"
				ActiveControl.EnableUpdate="false"
				AutoPostBack="false"
			/> <label for="<%=$this->Sunday->ClientID%>"><%[ Sunday ]%></label> &nbsp;
			<com:TActiveCheckBox
				 ID="Monday"
				OnCheckedChanged="saveValue"
				CssClass="w3-check w3-border w3-show-inline-block dow"
				Visible="<%=$this->display_directive%>"
				ActiveControl.EnableUpdate="false"
				AutoPostBack="false"
			/> <label for="<%=$this->Monday->ClientID%>"><%[ Monday ]%></label> &nbsp;
			<com:TActiveCheckBox
				ID="Tuesday"
				OnCheckedChanged="saveValue"
				CssClass="w3-check w3-border w3-show-inline-block dow"
				Visible="<%=$this->display_directive%>"
				ActiveControl.EnableUpdate="false"
				AutoPostBack="false"
			/> <label for="<%=$this->Tuesday->ClientID%>"><%[ Tuesday ]%></label> &nbsp;
			<com:TActiveCheckBox
				ID="Wednesday"
				OnCheckedChanged="saveValue"
				CssClass="w3-check w3-border w3-show-inline-block dow"
				Visible="<%=$this->display_directive%>"
				ActiveControl.EnableUpdate="false"
				AutoPostBack="false"
			/> <label for="<%=$this->Wednesday->ClientID%>"><%[ Wednesday ]%></label> &nbsp;
			<com:TActiveCheckBox
				ID="Thursday"
				OnCheckedChanged="saveValue"
				CssClass="w3-check w3-border w3-show-inline-block dow"
				Visible="<%=$this->display_directive%>"
				ActiveControl.EnableUpdate="false"
				AutoPostBack="false"
			/> <label for="<%=$this->Thursday->ClientID%>"><%[ Thursday ]%></label> &nbsp;
			<com:TActiveCheckBox
				ID="Friday"
				OnCheckedChanged="saveValue"
				CssClass="w3-check w3-border w3-show-inline-block dow"
				Visible="<%=$this->display_directive%>"
				ActiveControl.EnableUpdate="false"
				AutoPostBack="false"
			/> <label for="<%=$this->Friday->ClientID%>"><%[ Friday ]%></label> &nbsp;
			<com:TActiveCheckBox
				ID="Saturday"
				OnCheckedChanged="saveValue"
				CssClass="w3-check w3-border w3-show-inline-block dow"
				Visible="<%=$this->display_directive%>"
				ActiveControl.EnableUpdate="false"
				AutoPostBack="false"
			/> <label for="<%=$this->Saturday->ClientID%>"><%[ Saturday ]%></label> &nbsp;
		</div>
		<script>
			var <%=$this->AllDaysOfWeek->ClientID%>_check_all = function(check) {
				$('#<%=$this->AllDaysOfWeek->ClientID%>').closest('.directive_value').find('input[type=\'checkbox\'].dow').prop('disabled', check);
			}
			<%=$this->AllDaysOfWeek->ClientID%>_check_all($('#<%=$this->AllDaysOfWeek->ClientID%>').is(':checked'));
			document.getElementById('<%=$this->AllDaysOfWeek->ClientID%>').addEventListener('click', function(e) {
				<%=$this->AllDaysOfWeek->ClientID%>_check_all(this.checked);
			});
		</script>
	</div>
</div>
