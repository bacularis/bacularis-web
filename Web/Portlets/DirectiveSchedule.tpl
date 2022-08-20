<button type="button" class="w3-button w3-green w3-margin-bottom" onmousedown="openElementOnCursor(event, '<%=$this->ScheduleMenu->ClientID%>_new_schedule', 0, 20);"><i class="fa fa-plus"></i> &nbsp;<%[ Add ]%></button>
<com:Bacularis.Web.Portlets.NewScheduleMenu ID="ScheduleMenu" />
<com:TActiveRepeater ID="RepeaterScheduleRuns" OnItemDataBound="createRunItem">
	<prop:ItemTemplate>
		<div class="w3-card-4 w3-padding w3-margin-bottom directive runscript">
		<com:TActiveLinkButton
			CssClass="w3-button w3-red w3-right"
			OnCommand="SourceTemplateControl.removeSchedule"
			CommandName="<%=$this->ItemIndex%>"
		>
			<i class="fa fa-trash-alt"></i> &nbsp;<%[ Remove ]%>
		</com:TActiveLinkButton>
			<h2 class="schedule_options"><%=$this->SourceTemplateControl->ComponentType == 'dir' ? 'Run' : ($this->SourceTemplateControl->ComponentType == 'fd' ? 'Connect' : '')%> #<%=($this->ItemIndex+1)%></h2>
		<div>
			<div class="status_header schedule" onclick="$('#<%=$this->ScheduleMode->ClientID%>').val('hourly'); $(this).parent().find('.status_content').removeClass('w3-show'); $(this).next().addClass('w3-show');">
				<div class="w3-container w3-cell w3-mobile" style="flex-basis: 65px">
					<i class="w3-margin-right fas fa-chevron-down"></i>
				</div>
				<div class="w3-container w3-cell w3-mobile"><%[ Hourly ]%></div>
				<span class="w3-small w3-padding-small"><%[ Run job every hour at the specified minute ]%></span>
			</div>
			<div class="w3-border w3-hide w3-animate-right status_content <%=$this->ScheduleMode->Value == DirectiveSchedule::SCHEDULE_MODE_HOURLY ? 'w3-show': ''%>">
				<div class="w3-padding">
					<com:Bacularis.Web.Portlets.DirectiveTime
						ID="TimeHourly"
						Label="<%[ Run hourly at minute ]%>"
						InConfig="false"
						ShowHour="false"
						ShowRemoveButton="false"
						ShowResetButton="false"
						Show="true"
						CssClass="xtinybox"
					/>
				</div>
			</div>
			<div class="status_header schedule" onclick="$('#<%=$this->ScheduleMode->ClientID%>').val('daily'); $(this).parent().find('.status_content').removeClass('w3-show'); $(this).next().addClass('w3-show');">
				<div class="w3-container w3-cell w3-mobile" style="flex-basis: 65px">
					<i class="w3-margin-right fas fa-chevron-down"></i>
				</div>
				<div class="w3-container w3-cell w3-mobile"><%[ Daily ]%></div>
				<span class="w3-small w3-padding-small"><%[ Run job every day at the specified time ]%></span>
			</div>
			<div class="w3-border w3-hide w3-animate-right status_content <%=$this->ScheduleMode->Value == DirectiveSchedule::SCHEDULE_MODE_DAILY ? 'w3-show': ''%>">
				<div class="w3-padding">
					<com:Bacularis.Web.Portlets.DirectiveTime
						ID="TimeDaily"
						Label="<%[ Run at ]%>"
						InConfig="false"
						ShowRemoveButton="false"
						ShowResetButton="false"
						Show="true"
						CssClass="xtinybox"
					/>
				</div>
			</div>
			<div class="status_header schedule" onclick="$('#<%=$this->ScheduleMode->ClientID%>').val('weekly'); $(this).parent().find('.status_content').removeClass('w3-show'); $(this).next().addClass('w3-show');">
				<div class="w3-container w3-cell w3-mobile" style="flex-basis: 65px">
					<i class="w3-margin-right fas fa-chevron-down"></i>
				</div>
				<div class="w3-container w3-cell w3-mobile"><%[ Weekly ]%></div>
				<span class="w3-small w3-padding-small"><%[ Run job every week at the specified time on selected days of the week ]%></span>
			</div>
			<div class="w3-border w3-hide w3-animate-right status_content <%=$this->ScheduleMode->Value == DirectiveSchedule::SCHEDULE_MODE_WEEKLY ? 'w3-show': ''%>">
				<div class="w3-padding">
					<com:Bacularis.Web.Portlets.DirectiveTime
						ID="TimeWeekly"
						Label="<%[ Run at ]%>"
						InConfig="false"
						ShowRemoveButton="false"
						ShowResetButton="false"
						Show="true"
						CssClass="xtinybox"
					/>
					<com:Bacularis.Web.Portlets.DirectiveDaysOfWeek
						ID="DaysOfWeekWeekly"
						Label="<%[ Days of the week ]%>"
						InConfig="false"
						ShowRemoveButton="false"
						ShowResetButton="false"
						Show="true"
					/>
				</div>
			</div>
			<div class="status_header schedule" onclick="$('#<%=$this->ScheduleMode->ClientID%>').val('monthly'); $(this).parent().find('.status_content').removeClass('w3-show'); $(this).next().addClass('w3-show');">
				<div class="w3-container w3-cell w3-mobile" style="flex-basis: 65px">
					<i class="w3-margin-right fas fa-chevron-down"></i>
				</div>
				<div class="w3-container w3-cell w3-mobile"><%[ Monthly ]%></div>
				<span class="w3-small w3-padding-small"><%[ Run job every month at the specified time in selected weeks of the month ]%></span>
			</div>
			<div class="w3-border w3-hide w3-animate-right status_content <%=$this->ScheduleMode->Value == DirectiveSchedule::SCHEDULE_MODE_MONTHLY ? 'w3-show': ''%>">
				<div class="w3-padding">
					<com:Bacularis.Web.Portlets.DirectiveTime
						ID="TimeMonthly"
						Label="<%[ Run at ]%>"
						InConfig="false"
						ShowRemoveButton="false"
						ShowResetButton="false"
						Show="true"
						CssClass="xtinybox"
					/>
					<com:Bacularis.Web.Portlets.DirectiveWeeksOfMonth
						ID="WeeksOfMonthMonthly"
						Label="<%[ Weeks of the month ]%>"
						InConfig="false"
						ShowRemoveButton="false"
						ShowResetButton="false"
						Show="true"
					/>
					<com:Bacularis.Web.Portlets.DirectiveDaysOfWeek
						ID="DaysOfWeekMonthly"
						Label="<%[ Days of the week ]%>"
						InConfig="false"
						ShowRemoveButton="false"
						ShowResetButton="false"
						Show="true"
					/>
				</div>
			</div>
			<div class="status_header schedule" onclick="$('#<%=$this->ScheduleMode->ClientID%>').val('custom'); $('.status_content').removeClass('w3-show'); $(this).next().addClass('w3-show');">
				<div class="w3-container w3-cell w3-mobile" style="flex-basis: 65px">
					<i class="w3-margin-right fas fa-chevron-down"></i>
				</div>
				<div class="w3-container w3-cell w3-mobile"><%[ Custom ]%></div>
				<span class="w3-small w3-padding-small"><%[ Setup your custom schedule ]%></span>
			</div>
			<div class="w3-border w3-hide w3-animate-right status_content <%=$this->ScheduleMode->Value == DirectiveSchedule::SCHEDULE_MODE_CUSTOM ? 'w3-show': ''%>">
				<div class="w3-padding">
					<div class="w3-padding w3-margin-bottom">
						<com:TActiveRadioButton
							ID="TimeEveryHourCustomOption"
							CssClass="w3-check"
							AutoPostBack="false"
							GroupName="TimeCustomOptions"
						/> <label for="<%=$this->TimeEveryHourCustomOption->ClientID%>"><%[ Run every full hour ]%></label> &nbsp;
						<com:TActiveRadioButton
							ID="TimeHourlyCustomOption"
							CssClass="w3-check"
							AutoPostBack="false"
							GroupName="TimeCustomOptions"
						/> <label for="<%=$this->TimeHourlyCustomOption->ClientID%>"><%[ Run hourly at minute ]%></label> &nbsp;
						<com:TActiveRadioButton
							ID="TimeAtCustomOption"
							CssClass="w3-check"
							AutoPostBack="false"
							GroupName="TimeCustomOptions"
						/> <label for="<%=$this->TimeAtCustomOption->ClientID%>"><%[ Run at specified HH:MM ]%></label> &nbsp;
						<script>
							document.getElementById('<%=$this->TimeEveryHourCustomOption->ClientID%>').addEventListener('click', function(e) {
								$(this).closest('.status_content').find('.custom_hourly, .custom_time').removeClass('w3-show');
							});
							document.getElementById('<%=$this->TimeHourlyCustomOption->ClientID%>').addEventListener('click', function(e) {
								var sc = $(this).closest('.status_content'); sc.find('.custom_hourly').addClass('w3-show'); sc.find('.custom_time').removeClass('w3-show');
							});
							document.getElementById('<%=$this->TimeAtCustomOption->ClientID%>').addEventListener('click', function(e) {
								var sc = $(this).closest('.status_content'); sc.find('.custom_hourly').removeClass('w3-show'); sc.find('.custom_time').addClass('w3-show');
							});
						</script>
					</div>
					<div class="w3-hide custom_hourly <%=$this->TimeHourlyCustomOption->Checked ? 'w3-show' : 'w3-hide'%>">
						<com:Bacularis.Web.Portlets.DirectiveTime
							ID="TimeHourlyCustom"
							Label="<%[ Run hourly at minute ]%>"
							InConfig="false"
							ShowHour="false"
							ShowRemoveButton="false"
							ShowResetButton="false"
							Show="true"
							CssClass="xtinybox"
						/>
					</div>
					<div class="w3-hide custom_time <%=$this->TimeAtCustomOption->Checked ? 'w3-show' : 'w3-hide'%>">
						<com:Bacularis.Web.Portlets.DirectiveTime
							ID="TimeCustom"
							Label="<%[ Run at ]%>"
							InConfig="false"
							ShowRemoveButton="false"
							ShowResetButton="false"
							Show="true"
							CssClass="xtinybox"
						/>
					</div>
					<com:Bacularis.Web.Portlets.DirectiveWeeksOfMonth
						ID="WeeksOfMonthCustom"
						Label="<%[ Weeks of the month ]%>"
						InConfig="false"
						ShowRemoveButton="false"
						ShowResetButton="false"
						ShowOptions="true"
						Show="true"
					/>
					<com:Bacularis.Web.Portlets.DirectiveDaysOfWeek
						ID="DaysOfWeekCustom"
						Label="<%[ Days of the week ]%>"
						InConfig="false"
						ShowRemoveButton="false"
						ShowResetButton="false"
						ShowOptions="true"
						Show="true"
					/>
					<com:Bacularis.Web.Portlets.DirectiveDaysOfMonth
						ID="DaysOfMonthCustom"
						Label="<%[ Days of the month ]%>"
						InConfig="false"
						ShowRemoveButton="false"
						ShowResetButton="false"
						ShowOptions="true"
						Show="true"
					/>
					<com:Bacularis.Web.Portlets.DirectiveMonthsOfYear
						ID="MonthsOfYearCustom"
						Label="<%[ Months of the year ]%>"
						InConfig="false"
						ShowRemoveButton="false"
						ShowResetButton="false"
						ShowOptions="true"
						Show="true"
					/>
					<com:Bacularis.Web.Portlets.DirectiveWeeksOfYear
						ID="WeeksOfYearCustom"
						Label="<%[ Weeks of the year ]%>"
						InConfig="false"
						ShowRemoveButton="false"
						ShowResetButton="false"
						ShowOptions="true"
						Show="true"
					/>
				</div>
			</div>
		</div>
		<com:TActiveHiddenField ID="ScheduleMode" Value="daily" />
		<h3 class="<%=$this->Level->Show || $this->Pool->Show || $this->Storage->Show || $this->Messages->Show || $this->NextPool->Show || $this->FullPool->Show || $this->DifferentialPool->Show || $this->IncrementalPool->Show || $this->Accurate->Show || $this->Priority->Show || $this->SpoolData->Show || $this->MaxRunSchedTime->Show || $this->MaxConnectTime->Show ? 'w3-show' : 'w3-hide'%>"><%[ Override directives ]%></h3>
		<com:Bacularis\Web\Portlets\DirectiveComboBox
			ID="Level"
		/>
		<com:Bacularis\Web\Portlets\DirectiveComboBox
			ID="Pool"
		/>
		<com:Bacularis\Web\Portlets\DirectiveComboBox
			ID="Storage"
		/>
		<com:Bacularis\Web\Portlets\DirectiveComboBox
			ID="Messages"
		/>
		<com:Bacularis\Web\Portlets\DirectiveComboBox
			ID="NextPool"
		/>
		<com:Bacularis\Web\Portlets\DirectiveComboBox
			ID="FullPool"
		/>
		<com:Bacularis\Web\Portlets\DirectiveComboBox
			ID="DifferentialPool"
		/>
		<com:Bacularis\Web\Portlets\DirectiveComboBox
			ID="IncrementalPool"
		/>
		<com:Bacularis\Web\Portlets\DirectiveCheckBox
			ID="Accurate"
		/>
		<com:Bacularis\Web\Portlets\DirectiveTextBox
			ID="Priority"
			CssClass="smallbox"
		/>
		<com:Bacularis\Web\Portlets\DirectiveCheckBox
			ID="SpoolData"
		/>
		<com:Bacularis\Web\Portlets\DirectiveTimePeriod
			ID="MaxRunSchedTime"
		/>
		<com:Bacularis\Web\Portlets\DirectiveTimePeriod
			ID="MaxConnectTime"
		/>
	</div>
	</prop:ItemTemplate>
</com:TActiveRepeater>
<com:TValidationSummary
        ValidationGroup="Directive"
        Display="None"
	HeaderText="<%[ Validation error ]%>"
 />
<script>
function schedule_required_fields_validator(sender, param) {
	return false;
}
</script>
