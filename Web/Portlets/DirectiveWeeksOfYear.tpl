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
				ID="AllWeeksOfYear"
				OnCheckedChanged="saveValue"
				CssClass="w3-check"
				AutoPostBack="false"
			/> <label for="<%=$this->AllWeeksOfYear->ClientID%>"><%[ All weeks ]%></label> &nbsp;
		</div>
		<div class="w3-row">
			<com:TActiveCheckBox
				ID="week0"
				OnCheckedChanged="saveValue"
				CssClass="w3-check w3-border w3-show-inline-block woy"
				Visible="<%=$this->display_directive%>"
				ActiveControl.EnableUpdate="false"
				AutoPostBack="false"
			/> <label class="w3-show-inline-block week_of_year" for="<%=$this->week0->ClientID%>">0</label> &nbsp;
			<com:TActiveCheckBox
				ID="week1"
				OnCheckedChanged="saveValue"
				CssClass="w3-check w3-border w3-show-inline-block woy"
				Visible="<%=$this->display_directive%>"
				ActiveControl.EnableUpdate="false"
				AutoPostBack="false"
			/> <label class="w3-show-inline-block week_of_year" for="<%=$this->week1->ClientID%>">1</label> &nbsp;
			<com:TActiveCheckBox
				ID="week2"
				OnCheckedChanged="saveValue"
				CssClass="w3-check w3-border w3-show-inline-block woy"
				Visible="<%=$this->display_directive%>"
				ActiveControl.EnableUpdate="false"
				AutoPostBack="false"
			/> <label class="w3-show-inline-block week_of_year" for="<%=$this->week2->ClientID%>">2</label> &nbsp;
			<com:TActiveCheckBox
				ID="week3"
				OnCheckedChanged="saveValue"
				CssClass="w3-check w3-border w3-show-inline-block woy"
				Visible="<%=$this->display_directive%>"
				ActiveControl.EnableUpdate="false"
				AutoPostBack="false"
			/> <label class="w3-show-inline-block week_of_year" for="<%=$this->week3->ClientID%>">3</label> &nbsp;
			<com:TActiveCheckBox
				ID="week4"
				OnCheckedChanged="saveValue"
				CssClass="w3-check w3-border w3-show-inline-block woy"
				Visible="<%=$this->display_directive%>"
				ActiveControl.EnableUpdate="false"
				AutoPostBack="false"
			/> <label class="w3-show-inline-block week_of_year" for="<%=$this->week4->ClientID%>">4</label> &nbsp;
			<com:TActiveCheckBox
				ID="week5"
				OnCheckedChanged="saveValue"
				CssClass="w3-check w3-border w3-show-inline-block woy"
				Visible="<%=$this->display_directive%>"
				ActiveControl.EnableUpdate="false"
				AutoPostBack="false"
			/> <label class="w3-show-inline-block week_of_year" for="<%=$this->week5->ClientID%>">5</label> &nbsp;
			<com:TActiveCheckBox
				ID="week6"
				OnCheckedChanged="saveValue"
				CssClass="w3-check w3-border w3-show-inline-block woy"
				Visible="<%=$this->display_directive%>"
				ActiveControl.EnableUpdate="false"
				AutoPostBack="false"
			/> <label class="w3-show-inline-block week_of_year" for="<%=$this->week6->ClientID%>">6</label> &nbsp;
		</div>
		<div class="w3-row">
			<com:TActiveCheckBox
				ID="week7"
				OnCheckedChanged="saveValue"
				CssClass="w3-check w3-border w3-show-inline-block woy"
				Visible="<%=$this->display_directive%>"
				ActiveControl.EnableUpdate="false"
				AutoPostBack="false"
			/> <label class="w3-show-inline-block week_of_year" for="<%=$this->week7->ClientID%>">7</label> &nbsp;
			<com:TActiveCheckBox
				ID="week8"
				OnCheckedChanged="saveValue"
				CssClass="w3-check w3-border w3-show-inline-block woy"
				Visible="<%=$this->display_directive%>"
				ActiveControl.EnableUpdate="false"
				AutoPostBack="false"
			/> <label class="w3-show-inline-block week_of_year" for="<%=$this->week8->ClientID%>">8</label> &nbsp;
			<com:TActiveCheckBox
				ID="week9"
				OnCheckedChanged="saveValue"
				CssClass="w3-check w3-border w3-show-inline-block woy"
				Visible="<%=$this->display_directive%>"
				ActiveControl.EnableUpdate="false"
				AutoPostBack="false"
			/> <label class="w3-show-inline-block week_of_year" for="<%=$this->week9->ClientID%>">9</label> &nbsp;
			<com:TActiveCheckBox
				ID="week10"
				OnCheckedChanged="saveValue"
				CssClass="w3-check w3-border w3-show-inline-block woy"
				Visible="<%=$this->display_directive%>"
				ActiveControl.EnableUpdate="false"
				AutoPostBack="false"
			/> <label class="w3-show-inline-block week_of_year" for="<%=$this->week10->ClientID%>">10</label> &nbsp;
			<com:TActiveCheckBox
				ID="week11"
				OnCheckedChanged="saveValue"
				CssClass="w3-check w3-border w3-show-inline-block woy"
				Visible="<%=$this->display_directive%>"
				ActiveControl.EnableUpdate="false"
				AutoPostBack="false"
			/> <label class="w3-show-inline-block week_of_year" for="<%=$this->week11->ClientID%>">11</label> &nbsp;
			<com:TActiveCheckBox
				ID="week12"
				OnCheckedChanged="saveValue"
				CssClass="w3-check w3-border w3-show-inline-block woy"
				Visible="<%=$this->display_directive%>"
				ActiveControl.EnableUpdate="false"
				AutoPostBack="false"
			/> <label class="w3-show-inline-block week_of_year" for="<%=$this->week12->ClientID%>">12</label> &nbsp;
			<com:TActiveCheckBox
				ID="week13"
				OnCheckedChanged="saveValue"
				CssClass="w3-check w3-border w3-show-inline-block woy"
				Visible="<%=$this->display_directive%>"
				ActiveControl.EnableUpdate="false"
				AutoPostBack="false"
			/> <label class="w3-show-inline-block week_of_year" for="<%=$this->week13->ClientID%>">13</label> &nbsp;
		</div>
		<div class="w3-row">
			<com:TActiveCheckBox
				ID="week14"
				OnCheckedChanged="saveValue"
				CssClass="w3-check w3-border w3-show-inline-block woy"
				Visible="<%=$this->display_directive%>"
				ActiveControl.EnableUpdate="false"
				AutoPostBack="false"
			/> <label class="w3-show-inline-block week_of_year" for="<%=$this->week14->ClientID%>">14</label> &nbsp;
			<com:TActiveCheckBox
				ID="week15"
				OnCheckedChanged="saveValue"
				CssClass="w3-check w3-border w3-show-inline-block woy"
				Visible="<%=$this->display_directive%>"
				ActiveControl.EnableUpdate="false"
				AutoPostBack="false"
			/> <label class="w3-show-inline-block week_of_year" for="<%=$this->week15->ClientID%>">15</label> &nbsp;
			<com:TActiveCheckBox
				ID="week16"
				OnCheckedChanged="saveValue"
				CssClass="w3-check w3-border w3-show-inline-block woy"
				Visible="<%=$this->display_directive%>"
				ActiveControl.EnableUpdate="false"
				AutoPostBack="false"
			/> <label class="w3-show-inline-block week_of_year" for="<%=$this->week16->ClientID%>">16</label> &nbsp;
			<com:TActiveCheckBox
				ID="week17"
				OnCheckedChanged="saveValue"
				CssClass="w3-check w3-border w3-show-inline-block woy"
				Visible="<%=$this->display_directive%>"
				ActiveControl.EnableUpdate="false"
				AutoPostBack="false"
			/> <label class="w3-show-inline-block week_of_year" for="<%=$this->week17->ClientID%>">17</label> &nbsp;
			<com:TActiveCheckBox
				ID="week18"
				OnCheckedChanged="saveValue"
				CssClass="w3-check w3-border w3-show-inline-block woy"
				Visible="<%=$this->display_directive%>"
				ActiveControl.EnableUpdate="false"
				AutoPostBack="false"
			/> <label class="w3-show-inline-block week_of_year" for="<%=$this->week18->ClientID%>">18</label> &nbsp;
			<com:TActiveCheckBox
				ID="week19"
				OnCheckedChanged="saveValue"
				CssClass="w3-check w3-border w3-show-inline-block woy"
				Visible="<%=$this->display_directive%>"
				ActiveControl.EnableUpdate="false"
				AutoPostBack="false"
			/> <label class="w3-show-inline-block week_of_year" for="<%=$this->week19->ClientID%>">19</label> &nbsp;
			<com:TActiveCheckBox
				ID="week20"
				OnCheckedChanged="saveValue"
				CssClass="w3-check w3-border w3-show-inline-block woy"
				Visible="<%=$this->display_directive%>"
				ActiveControl.EnableUpdate="false"
				AutoPostBack="false"
			/> <label class="w3-show-inline-block week_of_year" for="<%=$this->week20->ClientID%>">20</label> &nbsp;
		</div>
		<div class="w3-row">
			<com:TActiveCheckBox
				ID="week21"
				OnCheckedChanged="saveValue"
				CssClass="w3-check w3-border w3-show-inline-block woy"
				Visible="<%=$this->display_directive%>"
				ActiveControl.EnableUpdate="false"
				AutoPostBack="false"
			/> <label class="w3-show-inline-block week_of_year" for="<%=$this->week21->ClientID%>">21</label> &nbsp;
			<com:TActiveCheckBox
				ID="week22"
				OnCheckedChanged="saveValue"
				CssClass="w3-check w3-border w3-show-inline-block woy"
				Visible="<%=$this->display_directive%>"
				ActiveControl.EnableUpdate="false"
				AutoPostBack="false"
			/> <label class="w3-show-inline-block week_of_year" for="<%=$this->week22->ClientID%>">22</label> &nbsp;
			<com:TActiveCheckBox
				ID="week23"
				OnCheckedChanged="saveValue"
				CssClass="w3-check w3-border w3-show-inline-block woy"
				Visible="<%=$this->display_directive%>"
				ActiveControl.EnableUpdate="false"
				AutoPostBack="false"
			/> <label class="w3-show-inline-block week_of_year" for="<%=$this->week23->ClientID%>">23</label> &nbsp;
			<com:TActiveCheckBox
				ID="week24"
				OnCheckedChanged="saveValue"
				CssClass="w3-check w3-border w3-show-inline-block woy"
				Visible="<%=$this->display_directive%>"
				ActiveControl.EnableUpdate="false"
				AutoPostBack="false"
			/> <label class="w3-show-inline-block week_of_year" for="<%=$this->week24->ClientID%>">24</label> &nbsp;
			<com:TActiveCheckBox
				ID="week25"
				OnCheckedChanged="saveValue"
				CssClass="w3-check w3-border w3-show-inline-block woy"
				Visible="<%=$this->display_directive%>"
				ActiveControl.EnableUpdate="false"
				AutoPostBack="false"
			/> <label class="w3-show-inline-block week_of_year" for="<%=$this->week25->ClientID%>">25</label> &nbsp;
			<com:TActiveCheckBox
				ID="week26"
				OnCheckedChanged="saveValue"
				CssClass="w3-check w3-border w3-show-inline-block woy"
				Visible="<%=$this->display_directive%>"
				ActiveControl.EnableUpdate="false"
				AutoPostBack="false"
			/> <label class="w3-show-inline-block week_of_year" for="<%=$this->week26->ClientID%>">26</label> &nbsp;
			<com:TActiveCheckBox
				ID="week27"
				OnCheckedChanged="saveValue"
				CssClass="w3-check w3-border w3-show-inline-block woy"
				Visible="<%=$this->display_directive%>"
				ActiveControl.EnableUpdate="false"
				AutoPostBack="false"
			/> <label class="w3-show-inline-block week_of_year" for="<%=$this->week27->ClientID%>">27</label> &nbsp;
		</div>
		<div class="w3-row">
			<com:TActiveCheckBox
				ID="week28"
				OnCheckedChanged="saveValue"
				CssClass="w3-check w3-border w3-show-inline-block woy"
				Visible="<%=$this->display_directive%>"
				ActiveControl.EnableUpdate="false"
				AutoPostBack="false"
			/> <label class="w3-show-inline-block week_of_year" for="<%=$this->week28->ClientID%>">28</label> &nbsp;
			<com:TActiveCheckBox
				ID="week29"
				OnCheckedChanged="saveValue"
				CssClass="w3-check w3-border w3-show-inline-block woy"
				Visible="<%=$this->display_directive%>"
				ActiveControl.EnableUpdate="false"
				AutoPostBack="false"
			/> <label class="w3-show-inline-block week_of_year" for="<%=$this->week29->ClientID%>">29</label> &nbsp;
			<com:TActiveCheckBox
				ID="week30"
				OnCheckedChanged="saveValue"
				CssClass="w3-check w3-border w3-show-inline-block woy"
				Visible="<%=$this->display_directive%>"
				ActiveControl.EnableUpdate="false"
				AutoPostBack="false"
			/> <label class="w3-show-inline-block week_of_year" for="<%=$this->week30->ClientID%>">30</label> &nbsp;
			<com:TActiveCheckBox
				ID="week31"
				OnCheckedChanged="saveValue"
				CssClass="w3-check w3-border w3-show-inline-block woy"
				Visible="<%=$this->display_directive%>"
				ActiveControl.EnableUpdate="false"
				AutoPostBack="false"
			/> <label class="w3-show-inline-block week_of_year" for="<%=$this->week31->ClientID%>">31</label> &nbsp;
			<com:TActiveCheckBox
				ID="week32"
				OnCheckedChanged="saveValue"
				CssClass="w3-check w3-border w3-show-inline-block woy"
				Visible="<%=$this->display_directive%>"
				ActiveControl.EnableUpdate="false"
				AutoPostBack="false"
			/> <label class="w3-show-inline-block week_of_year" for="<%=$this->week32->ClientID%>">32</label> &nbsp;
			<com:TActiveCheckBox
				ID="week33"
				OnCheckedChanged="saveValue"
				CssClass="w3-check w3-border w3-show-inline-block woy"
				Visible="<%=$this->display_directive%>"
				ActiveControl.EnableUpdate="false"
				AutoPostBack="false"
			/> <label class="w3-show-inline-block week_of_year" for="<%=$this->week33->ClientID%>">33</label> &nbsp;
			<com:TActiveCheckBox
				ID="week34"
				OnCheckedChanged="saveValue"
				CssClass="w3-check w3-border w3-show-inline-block woy"
				Visible="<%=$this->display_directive%>"
				ActiveControl.EnableUpdate="false"
				AutoPostBack="false"
			/> <label class="w3-show-inline-block week_of_year" for="<%=$this->week34->ClientID%>">34</label> &nbsp;
		</div>
		<div class="w3-row">
			<com:TActiveCheckBox
				ID="week35"
				OnCheckedChanged="saveValue"
				CssClass="w3-check w3-border w3-show-inline-block woy"
				Visible="<%=$this->display_directive%>"
				ActiveControl.EnableUpdate="false"
				AutoPostBack="false"
			/> <label class="w3-show-inline-block week_of_year" for="<%=$this->week35->ClientID%>">35</label> &nbsp;
			<com:TActiveCheckBox
				ID="week36"
				OnCheckedChanged="saveValue"
				CssClass="w3-check w3-border w3-show-inline-block woy"
				Visible="<%=$this->display_directive%>"
				ActiveControl.EnableUpdate="false"
				AutoPostBack="false"
			/> <label class="w3-show-inline-block week_of_year" for="<%=$this->week36->ClientID%>">36</label> &nbsp;
			<com:TActiveCheckBox
				ID="week37"
				OnCheckedChanged="saveValue"
				CssClass="w3-check w3-border w3-show-inline-block woy"
				Visible="<%=$this->display_directive%>"
				ActiveControl.EnableUpdate="false"
				AutoPostBack="false"
			/> <label class="w3-show-inline-block week_of_year" for="<%=$this->week37->ClientID%>">37</label> &nbsp;
			<com:TActiveCheckBox
				ID="week38"
				OnCheckedChanged="saveValue"
				CssClass="w3-check w3-border w3-show-inline-block woy"
				Visible="<%=$this->display_directive%>"
				ActiveControl.EnableUpdate="false"
				AutoPostBack="false"
			/> <label class="w3-show-inline-block week_of_year" for="<%=$this->week38->ClientID%>">38</label> &nbsp;
			<com:TActiveCheckBox
				ID="week39"
				OnCheckedChanged="saveValue"
				CssClass="w3-check w3-border w3-show-inline-block woy"
				Visible="<%=$this->display_directive%>"
				ActiveControl.EnableUpdate="false"
				AutoPostBack="false"
			/> <label class="w3-show-inline-block week_of_year" for="<%=$this->week39->ClientID%>">39</label> &nbsp;
			<com:TActiveCheckBox
				ID="week40"
				OnCheckedChanged="saveValue"
				CssClass="w3-check w3-border w3-show-inline-block woy"
				Visible="<%=$this->display_directive%>"
				ActiveControl.EnableUpdate="false"
				AutoPostBack="false"
			/> <label class="w3-show-inline-block week_of_year" for="<%=$this->week40->ClientID%>">40</label> &nbsp;
			<com:TActiveCheckBox
				ID="week41"
				OnCheckedChanged="saveValue"
				CssClass="w3-check w3-border w3-show-inline-block woy"
				Visible="<%=$this->display_directive%>"
				ActiveControl.EnableUpdate="false"
				AutoPostBack="false"
			/> <label class="w3-show-inline-block week_of_year" for="<%=$this->week41->ClientID%>">41</label> &nbsp;
		</div>
		<div class="w3-row">
			<com:TActiveCheckBox
				ID="week42"
				OnCheckedChanged="saveValue"
				CssClass="w3-check w3-border w3-show-inline-block woy"
				Visible="<%=$this->display_directive%>"
				ActiveControl.EnableUpdate="false"
				AutoPostBack="false"
			/> <label class="w3-show-inline-block week_of_year" for="<%=$this->week42->ClientID%>">42</label> &nbsp;
			<com:TActiveCheckBox
				ID="week43"
				OnCheckedChanged="saveValue"
				CssClass="w3-check w3-border w3-show-inline-block woy"
				Visible="<%=$this->display_directive%>"
				ActiveControl.EnableUpdate="false"
				AutoPostBack="false"
			/> <label class="w3-show-inline-block week_of_year" for="<%=$this->week43->ClientID%>">43</label> &nbsp;
			<com:TActiveCheckBox
				ID="week44"
				OnCheckedChanged="saveValue"
				CssClass="w3-check w3-border w3-show-inline-block woy"
				Visible="<%=$this->display_directive%>"
				ActiveControl.EnableUpdate="false"
				AutoPostBack="false"
			/> <label class="w3-show-inline-block week_of_year" for="<%=$this->week44->ClientID%>">44</label> &nbsp;
			<com:TActiveCheckBox
				ID="week45"
				OnCheckedChanged="saveValue"
				CssClass="w3-check w3-border w3-show-inline-block woy"
				Visible="<%=$this->display_directive%>"
				ActiveControl.EnableUpdate="false"
				AutoPostBack="false"
			/> <label class="w3-show-inline-block week_of_year" for="<%=$this->week45->ClientID%>">45</label> &nbsp;
			<com:TActiveCheckBox
				ID="week46"
				OnCheckedChanged="saveValue"
				CssClass="w3-check w3-border w3-show-inline-block woy"
				Visible="<%=$this->display_directive%>"
				ActiveControl.EnableUpdate="false"
				AutoPostBack="false"
			/> <label class="w3-show-inline-block week_of_year" for="<%=$this->week46->ClientID%>">46</label> &nbsp;
			<com:TActiveCheckBox
				ID="week47"
				OnCheckedChanged="saveValue"
				CssClass="w3-check w3-border w3-show-inline-block woy"
				Visible="<%=$this->display_directive%>"
				ActiveControl.EnableUpdate="false"
				AutoPostBack="false"
			/> <label class="w3-show-inline-block week_of_year" for="<%=$this->week47->ClientID%>">47</label> &nbsp;
			<com:TActiveCheckBox
				ID="week48"
				OnCheckedChanged="saveValue"
				CssClass="w3-check w3-border w3-show-inline-block woy"
				Visible="<%=$this->display_directive%>"
				ActiveControl.EnableUpdate="false"
				AutoPostBack="false"
			/> <label class="w3-show-inline-block week_of_year" for="<%=$this->week48->ClientID%>">48</label> &nbsp;
		</div>
		<div class="w3-row">
			<com:TActiveCheckBox
				ID="week49"
				OnCheckedChanged="saveValue"
				CssClass="w3-check w3-border w3-show-inline-block woy"
				Visible="<%=$this->display_directive%>"
				ActiveControl.EnableUpdate="false"
				AutoPostBack="false"
			/> <label class="w3-show-inline-block week_of_year" for="<%=$this->week49->ClientID%>">49</label> &nbsp;
			<com:TActiveCheckBox
				ID="week50"
				OnCheckedChanged="saveValue"
				CssClass="w3-check w3-border w3-show-inline-block woy"
				Visible="<%=$this->display_directive%>"
				ActiveControl.EnableUpdate="false"
				AutoPostBack="false"
			/> <label class="w3-show-inline-block week_of_year" for="<%=$this->week50->ClientID%>">50</label> &nbsp;
			<com:TActiveCheckBox
				ID="week51"
				OnCheckedChanged="saveValue"
				CssClass="w3-check w3-border w3-show-inline-block woy"
				Visible="<%=$this->display_directive%>"
				ActiveControl.EnableUpdate="false"
				AutoPostBack="false"
			/> <label class="w3-show-inline-block week_of_year" for="<%=$this->week51->ClientID%>">51</label> &nbsp;
			<com:TActiveCheckBox
				ID="week52"
				OnCheckedChanged="saveValue"
				CssClass="w3-check w3-border w3-show-inline-block woy"
				Visible="<%=$this->display_directive%>"
				ActiveControl.EnableUpdate="false"
				AutoPostBack="false"
			/> <label class="w3-show-inline-block week_of_year" for="<%=$this->week52->ClientID%>">52</label> &nbsp;
			<com:TActiveCheckBox
				ID="week53"
				OnCheckedChanged="saveValue"
				CssClass="w3-check w3-border w3-show-inline-block woy"
				Visible="<%=$this->display_directive%>"
				ActiveControl.EnableUpdate="false"
				AutoPostBack="false"
			/> <label class="w3-show-inline-block week_of_year" for="<%=$this->week53->ClientID%>">53</label> &nbsp;
		</div>
		<script>
			var <%=$this->AllWeeksOfYear->ClientID%>_check_all = function(check) {
				$('#<%=$this->AllWeeksOfYear->ClientID%>').closest('.directive_value').find('input[type=\'checkbox\'].woy').prop('disabled', check);
			}
			<%=$this->AllWeeksOfYear->ClientID%>_check_all($('#<%=$this->AllWeeksOfYear->ClientID%>').is(':checked'));
			document.getElementById('<%=$this->AllWeeksOfYear->ClientID%>').addEventListener('click', function(e) {
				<%=$this->AllWeeksOfYear->ClientID%>_check_all(this.checked);
			});
		</script>
	</div>
</div>
