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
				ID="AllDaysOfMonth"
				OnCheckedChanged="saveValue"
				CssClass="w3-check"
				AutoPostBack="false"
			/> <label for="<%=$this->AllDaysOfMonth->ClientID%>"><%[ All days ]%></label> &nbsp;
		</div>
		<div class="w3-row">
			<com:TActiveCheckBox
				ID="day1"
				OnCheckedChanged="saveValue"
				CssClass="w3-check w3-border w3-show-inline-block dom"
				Visible="<%=$this->display_directive%>"
				ActiveControl.EnableUpdate="false"
				AutoPostBack="false"
			/> <label class="w3-show-inline-block day_of_month" for="<%=$this->day1->ClientID%>">1</label> &nbsp;
			<com:TActiveCheckBox
				ID="day2"
				OnCheckedChanged="saveValue"
				CssClass="w3-check w3-border w3-show-inline-block dom"
				Visible="<%=$this->display_directive%>"
				ActiveControl.EnableUpdate="false"
				AutoPostBack="false"
			/> <label class="w3-show-inline-block day_of_month" for="<%=$this->day2->ClientID%>">2</label> &nbsp;
			<com:TActiveCheckBox
				ID="day3"
				OnCheckedChanged="saveValue"
				CssClass="w3-check w3-border w3-show-inline-block dom"
				Visible="<%=$this->display_directive%>"
				ActiveControl.EnableUpdate="false"
				AutoPostBack="false"
			/> <label class="w3-show-inline-block day_of_month" for="<%=$this->day3->ClientID%>">3</label> &nbsp;
			<com:TActiveCheckBox
				ID="day4"
				OnCheckedChanged="saveValue"
				CssClass="w3-check w3-border w3-show-inline-block dom"
				Visible="<%=$this->display_directive%>"
				ActiveControl.EnableUpdate="false"
				AutoPostBack="false"
			/> <label class="w3-show-inline-block day_of_month" for="<%=$this->day4->ClientID%>">4</label> &nbsp;
			<com:TActiveCheckBox
				ID="day5"
				OnCheckedChanged="saveValue"
				CssClass="w3-check w3-border w3-show-inline-block dom"
				Visible="<%=$this->display_directive%>"
				ActiveControl.EnableUpdate="false"
				AutoPostBack="false"
			/> <label class="w3-show-inline-block day_of_month" for="<%=$this->day5->ClientID%>">5</label> &nbsp;
			<com:TActiveCheckBox
				ID="day6"
				OnCheckedChanged="saveValue"
				CssClass="w3-check w3-border w3-show-inline-block dom"
				Visible="<%=$this->display_directive%>"
				ActiveControl.EnableUpdate="false"
				AutoPostBack="false"
			/> <label class="w3-show-inline-block day_of_month" for="<%=$this->day6->ClientID%>">6</label> &nbsp;
			<com:TActiveCheckBox
				ID="day7"
				OnCheckedChanged="saveValue"
				CssClass="w3-check w3-border w3-show-inline-block dom"
				Visible="<%=$this->display_directive%>"
				ActiveControl.EnableUpdate="false"
				AutoPostBack="false"
			/> <label class="w3-show-inline-block day_of_month" for="<%=$this->day7->ClientID%>">7</label> &nbsp;
		</div>
		<div class="w3-row">
			<com:TActiveCheckBox
				ID="day8"
				OnCheckedChanged="saveValue"
				CssClass="w3-check w3-border w3-show-inline-block dom"
				Visible="<%=$this->display_directive%>"
				ActiveControl.EnableUpdate="false"
				AutoPostBack="false"
			/> <label class="w3-show-inline-block day_of_month" for="<%=$this->day8->ClientID%>">8</label> &nbsp;
			<com:TActiveCheckBox
				ID="day9"
				OnCheckedChanged="saveValue"
				CssClass="w3-check w3-border w3-show-inline-block dom"
				Visible="<%=$this->display_directive%>"
				ActiveControl.EnableUpdate="false"
				AutoPostBack="false"
			/> <label class="w3-show-inline-block day_of_month" for="<%=$this->day9->ClientID%>">9</label> &nbsp;
			<com:TActiveCheckBox
				ID="day10"
				OnCheckedChanged="saveValue"
				CssClass="w3-check w3-border w3-show-inline-block dom"
				Visible="<%=$this->display_directive%>"
				ActiveControl.EnableUpdate="false"
				AutoPostBack="false"
			/> <label class="w3-show-inline-block day_of_month" for="<%=$this->day10->ClientID%>">10</label> &nbsp;
			<com:TActiveCheckBox
				ID="day11"
				OnCheckedChanged="saveValue"
				CssClass="w3-check w3-border w3-show-inline-block dom"
				Visible="<%=$this->display_directive%>"
				ActiveControl.EnableUpdate="false"
				AutoPostBack="false"
			/> <label class="w3-show-inline-block day_of_month" for="<%=$this->day11->ClientID%>">11</label> &nbsp;
			<com:TActiveCheckBox
				ID="day12"
				OnCheckedChanged="saveValue"
				CssClass="w3-check w3-border w3-show-inline-block dom"
				Visible="<%=$this->display_directive%>"
				ActiveControl.EnableUpdate="false"
				AutoPostBack="false"
			/> <label class="w3-show-inline-block day_of_month" for="<%=$this->day12->ClientID%>">12</label> &nbsp;
			<com:TActiveCheckBox
				ID="day13"
				OnCheckedChanged="saveValue"
				CssClass="w3-check w3-border w3-show-inline-block dom"
				Visible="<%=$this->display_directive%>"
				ActiveControl.EnableUpdate="false"
				AutoPostBack="false"
			/> <label class="w3-show-inline-block day_of_month" for="<%=$this->day13->ClientID%>">13</label> &nbsp;
			<com:TActiveCheckBox
				ID="day14"
				OnCheckedChanged="saveValue"
				CssClass="w3-check w3-border w3-show-inline-block dom"
				Visible="<%=$this->display_directive%>"
				ActiveControl.EnableUpdate="false"
				AutoPostBack="false"
			/> <label class="w3-show-inline-block day_of_month" for="<%=$this->day14->ClientID%>">14</label> &nbsp;
		</div>
		<div class="w3-row">
			<com:TActiveCheckBox
				ID="day15"
				OnCheckedChanged="saveValue"
				CssClass="w3-check w3-border w3-show-inline-block dom"
				Visible="<%=$this->display_directive%>"
				ActiveControl.EnableUpdate="false"
				AutoPostBack="false"
			/> <label class="w3-show-inline-block day_of_month" for="<%=$this->day15->ClientID%>">15</label> &nbsp;
			<com:TActiveCheckBox
				ID="day16"
				OnCheckedChanged="saveValue"
				CssClass="w3-check w3-border w3-show-inline-block dom"
				Visible="<%=$this->display_directive%>"
				ActiveControl.EnableUpdate="false"
				AutoPostBack="false"
			/> <label class="w3-show-inline-block day_of_month" for="<%=$this->day16->ClientID%>">16</label> &nbsp;
			<com:TActiveCheckBox
				ID="day17"
				OnCheckedChanged="saveValue"
				CssClass="w3-check w3-border w3-show-inline-block dom"
				Visible="<%=$this->display_directive%>"
				ActiveControl.EnableUpdate="false"
				AutoPostBack="false"
			/> <label class="w3-show-inline-block day_of_month" for="<%=$this->day17->ClientID%>">17</label> &nbsp;
			<com:TActiveCheckBox
				ID="day18"
				OnCheckedChanged="saveValue"
				CssClass="w3-check w3-border w3-show-inline-block dom"
				Visible="<%=$this->display_directive%>"
				ActiveControl.EnableUpdate="false"
				AutoPostBack="false"
			/> <label class="w3-show-inline-block day_of_month" for="<%=$this->day18->ClientID%>">18</label> &nbsp;
			<com:TActiveCheckBox
				ID="day19"
				OnCheckedChanged="saveValue"
				CssClass="w3-check w3-border w3-show-inline-block dom"
				Visible="<%=$this->display_directive%>"
				ActiveControl.EnableUpdate="false"
				AutoPostBack="false"
			/> <label class="w3-show-inline-block day_of_month" for="<%=$this->day19->ClientID%>">19</label> &nbsp;
			<com:TActiveCheckBox
				ID="day20"
				OnCheckedChanged="saveValue"
				CssClass="w3-check w3-border w3-show-inline-block dom"
				Visible="<%=$this->display_directive%>"
				ActiveControl.EnableUpdate="false"
				AutoPostBack="false"
			/> <label class="w3-show-inline-block day_of_month" for="<%=$this->day20->ClientID%>">20</label> &nbsp;
			<com:TActiveCheckBox
				ID="day21"
				OnCheckedChanged="saveValue"
				CssClass="w3-check w3-border w3-show-inline-block dom"
				Visible="<%=$this->display_directive%>"
				ActiveControl.EnableUpdate="false"
				AutoPostBack="false"
			/> <label class="w3-show-inline-block day_of_month" for="<%=$this->day21->ClientID%>">21</label> &nbsp;
		</div>
		<div class="w3-row">
			<com:TActiveCheckBox
				ID="day22"
				OnCheckedChanged="saveValue"
				CssClass="w3-check w3-border w3-show-inline-block dom"
				Visible="<%=$this->display_directive%>"
				ActiveControl.EnableUpdate="false"
				AutoPostBack="false"
			/> <label class="w3-show-inline-block day_of_month" for="<%=$this->day22->ClientID%>">22</label> &nbsp;
			<com:TActiveCheckBox
				ID="day23"
				OnCheckedChanged="saveValue"
				CssClass="w3-check w3-border w3-show-inline-block dom"
				Visible="<%=$this->display_directive%>"
				ActiveControl.EnableUpdate="false"
				AutoPostBack="false"
			/> <label class="w3-show-inline-block day_of_month" for="<%=$this->day23->ClientID%>">23</label> &nbsp;
			<com:TActiveCheckBox
				ID="day24"
				OnCheckedChanged="saveValue"
				CssClass="w3-check w3-border w3-show-inline-block dom"
				Visible="<%=$this->display_directive%>"
				ActiveControl.EnableUpdate="false"
				AutoPostBack="false"
			/> <label class="w3-show-inline-block day_of_month" for="<%=$this->day24->ClientID%>">24</label> &nbsp;
			<com:TActiveCheckBox
				ID="day25"
				OnCheckedChanged="saveValue"
				CssClass="w3-check w3-border w3-show-inline-block dom"
				Visible="<%=$this->display_directive%>"
				ActiveControl.EnableUpdate="false"
				AutoPostBack="false"
			/> <label class="w3-show-inline-block day_of_month" for="<%=$this->day25->ClientID%>">25</label> &nbsp;
			<com:TActiveCheckBox
				ID="day26"
				OnCheckedChanged="saveValue"
				CssClass="w3-check w3-border w3-show-inline-block dom"
				Visible="<%=$this->display_directive%>"
				ActiveControl.EnableUpdate="false"
				AutoPostBack="false"
			/> <label class="w3-show-inline-block day_of_month" for="<%=$this->day26->ClientID%>">26</label> &nbsp;
			<com:TActiveCheckBox
				ID="day27"
				OnCheckedChanged="saveValue"
				CssClass="w3-check w3-border w3-show-inline-block dom"
				Visible="<%=$this->display_directive%>"
				ActiveControl.EnableUpdate="false"
				AutoPostBack="false"
			/> <label class="w3-show-inline-block day_of_month" for="<%=$this->day27->ClientID%>">27</label> &nbsp;
			<com:TActiveCheckBox
				ID="day28"
				OnCheckedChanged="saveValue"
				CssClass="w3-check w3-border w3-show-inline-block dom"
				Visible="<%=$this->display_directive%>"
				ActiveControl.EnableUpdate="false"
				AutoPostBack="false"
			/> <label class="w3-show-inline-block day_of_month" for="<%=$this->day28->ClientID%>">28</label> &nbsp;
		</div>
		<div class="w3-row">
			<com:TActiveCheckBox
				ID="day29"
				OnCheckedChanged="saveValue"
				CssClass="w3-check w3-border w3-show-inline-block dom"
				Visible="<%=$this->display_directive%>"
				ActiveControl.EnableUpdate="false"
				AutoPostBack="false"
			/> <label class="w3-show-inline-block day_of_month" for="<%=$this->day29->ClientID%>">29</label> &nbsp;
			<com:TActiveCheckBox
				ID="day30"
				OnCheckedChanged="saveValue"
				CssClass="w3-check w3-border w3-show-inline-block dom"
				Visible="<%=$this->display_directive%>"
				ActiveControl.EnableUpdate="false"
				AutoPostBack="false"
			/> <label class="w3-show-inline-block day_of_month" for="<%=$this->day30->ClientID%>">30</label> &nbsp;
			<com:TActiveCheckBox
				ID="day31"
				OnCheckedChanged="saveValue"
				CssClass="w3-check w3-border w3-show-inline-block dom"
				Visible="<%=$this->display_directive%>"
				ActiveControl.EnableUpdate="false"
				AutoPostBack="false"
			/> <label class="w3-show-inline-block day_of_month" for="<%=$this->day31->ClientID%>">31</label> &nbsp;
			<com:TActiveCheckBox
				ID="lastday"
				OnCheckedChanged="saveValue"
				CssClass="w3-check w3-border w3-show-inline-block lastday"
				Visible="<%=$this->display_directive%>"
				ActiveControl.EnableUpdate="false"
				AutoPostBack="false"
			/> <label class="w3-show-inline-block day_of_month" for="<%=$this->lastday->ClientID%>" style="width: 300px"><%[ Last day of the month ]%></label> &nbsp;
		</div>
		<script>
			var <%=$this->AllDaysOfMonth->ClientID%>_check_all = function(check) {
				var el = $('#<%=$this->AllDaysOfMonth->ClientID%>');
				var p = $(el).closest('.directive_value');
				var ld = p.find('input[type=\'checkbox\'].lastday');
				if (!ld.get(0).checked) {
					p.find('input[type=\'checkbox\'].dom').prop('disabled', check);
				}
				ld.prop('disabled', check);
			};
			var <%=$this->lastday->ClientID%>_check_lastday = function(check) {
				var el =$('#<%=$this->lastday->ClientID%>');
				el.closest('.directive_value').find('input[type=\'checkbox\'].dom').prop('disabled', check);
			};
			document.getElementById('<%=$this->AllDaysOfMonth->ClientID%>').addEventListener('click', function(e) {
				<%=$this->AllDaysOfMonth->ClientID%>_check_all(this.checked);
			});
			document.getElementById('<%=$this->lastday->ClientID%>').addEventListener('click', function(e) {
				<%=$this->lastday->ClientID%>_check_lastday(this.checked);
			});
			<%=$this->lastday->ClientID%>_check_lastday($('#<%=$this->lastday->ClientID%>').is(':checked'));
			<%=$this->AllDaysOfMonth->ClientID%>_check_all($('#<%=$this->AllDaysOfMonth->ClientID%>').is(':checked'));
		</script>
	</div>
</div>
