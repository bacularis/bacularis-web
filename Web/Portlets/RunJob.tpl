<div id="run_job" class="w3-modal">
	<div class="w3-modal-content w3-animate-top w3-card-4">
		<header class="w3-container w3-green"> 
			<span onclick="close_run_job_window();" class="w3-button w3-display-topright">&times;</span>
			<h2><%[ Run job ]%><%=$this->getJobName() ? ' - ' . $this->getJobName() : ''%></h2>
		</header>
		<div class="w3-container w3-margin-left w3-margin-right">
			<com:TActivePanel ID="JobToRunLine" CssClass="w3-row directive_field" Display="None">
				<div class="w3-col w3-third"><i class="w3-large fa fa-tasks"></i> &nbsp;<com:TLabel ForControl="JobToRun" Text="<%[ Job to run: ]%>" CssClass="w3-large" /></div>
				<div class="w3-half">
					<com:TActiveDropDownList
						ID="JobToRun"
						CssClass="w3-select w3-border"
						OnCallback="selectJobValues"
					/>
				</div>
			</com:TActivePanel>
			<div class="w3-row directive_field">
				<div class="w3-col w3-third"><i class="w3-large fa fa-compass"></i> &nbsp;<com:TLabel ForControl="Level" Text="<%[ Level: ]%>" CssClass="w3-large"/></div>
				<div class="w3-half">
				<com:TActiveDropDownList ID="Level" CssClass="w3-select w3-border" AutoPostBack="false">
					<prop:Attributes.onchange>
						var job_to_verify = $('#<%=$this->JobToVerifyOptionsLine->ClientID%>');
						var verify_options = $('#<%=$this->JobToVerifyOptionsLine->ClientID%>');
						var verify_by_job_name = $('#<%=$this->JobToVerifyJobNameLine->ClientID%>');
						var verify_by_jobid = $('#<%=$this->JobToVerifyJobIdLine->ClientID%>');
						var accurate = $('#<%=$this->AccurateLine->ClientID%>');
						var verify_current_opt = document.getElementById('<%=$this->JobToVerifyOptions->ClientID%>').value;
						if(/^(<%=implode('|', $this->job_to_verify)%>)$/.test(this.value)) {
							if(/^(<%=implode('|', $this->verify_no_accurate)%>)$/.test(this.value)) {
								accurate.hide();
							} else {
								accurate.show();
							}
							verify_options.show();
							job_to_verify.show();
							if (verify_current_opt == 'jobid') {
								verify_by_job_name.hide();
								verify_by_jobid.show();
							} else if (verify_current_opt == 'jobname') {
								verify_by_job_name.show();
								verify_by_jobid.hide();
							}
						} else if (job_to_verify.is(':visible')) {
							job_to_verify.hide();
							verify_options.hide();
							verify_by_job_name.hide();
							verify_by_jobid.hide();
							accurate.show();
						}
					</prop:Attributes.onchange>
				</com:TActiveDropDownList>
				</div>
			</div>
			<com:TActivePanel ID="JobToVerifyOptionsLine" CssClass="w3-row directive_field" Display="None">
				<div class="w3-col w3-third"><i class="w3-large fa fa-search-plus"></i> &nbsp;<com:TLabel ForControl="JobToVerifyOptions" Text="<%[ Verify option: ]%>" CssClass="w3-large" /></div>
				<div class="w3-half">
					<com:TActiveDropDownList ID="JobToVerifyOptions" AutoPostBack="false" CssClass="w3-select w3-border">
						<prop:Attributes.onchange>
							var verify_by_job_name = $('#<%=$this->JobToVerifyJobNameLine->ClientID%>');
							var verify_by_jobid = $('#<%=$this->JobToVerifyJobIdLine->ClientID%>');
							if (this.value == 'jobname') {
								verify_by_jobid.hide();
								verify_by_job_name.show();
							} else if (this.value == 'jobid') {
								verify_by_job_name.hide();
								verify_by_jobid.show();
							} else {
								verify_by_job_name.hide();
								verify_by_jobid.hide();
							}
						</prop:Attributes.onchange>
					</com:TActiveDropDownList>
				</div>
			</com:TActivePanel>
			<com:TActivePanel ID="JobToVerifyJobNameLine" CssClass="w3-row directive_field" Display="None">
				<div class="w3-col w3-third"><i class="w3-large fa fa-check"></i> &nbsp;<com:TLabel ForControl="JobToVerifyJobName" Text="<%[ Job to Verify: ]%>" CssClass="w3-large" /></div>
				<div class="w3-half">
					<com:TActiveDropDownList ID="JobToVerifyJobName" AutoPostBack="false" CssClass="w3-select w3-border" />
				</div>
			</com:TActivePanel>
			<com:TActivePanel ID="JobToVerifyJobIdLine" CssClass="w3-row directive_field" Display="None">
				<div class="w3-col w3-third"><i class="w3-large fa fa-file-alt"></i> &nbsp;<com:TLabel ForControl="JobToVerifyJobId" Text="<%[ JobId to Verify: ]%>" CssClass="w3-large" /></div>
				<div class="w3-half">
					<com:TActiveTextBox ID="JobToVerifyJobId" CssClass="w3-input w3-border" AutoPostBack="false" />
					<com:TRequiredFieldValidator
						ValidationGroup="JobGroup"
						ControlToValidate="JobToVerifyJobId"
						ErrorMessage="<%[ JobId to Verify value must be integer greather than 0. ]%>"
						ControlCssClass="validation-error"
						Display="Dynamic"
					>
						<prop:ClientSide.OnValidate>
							var verify_opts = document.getElementById('<%=$this->JobToVerifyOptions->ClientID%>');
							sender.enabled = (verify_opts.value === 'jobid');
						</prop:ClientSide.OnValidate>
					</com:TRequiredFieldValidator>
					<com:TDataTypeValidator
						ID="JobToVerifyJobIdValidator"
						ValidationGroup="JobGroup"
						ControlToValidate="JobToVerifyJobId"
						ErrorMessage="<%[ JobId to Verify value must be integer greather than 0. ]%>"
						ControlCssClass="validation-error"
						Display="Dynamic"
						DataType="Integer"
					>
						<prop:ClientSide.OnValidate>
							var verify_opts = document.getElementById('<%=$this->JobToVerifyOptions->ClientID%>');
							sender.enabled = (verify_opts.value === 'jobid');
						</prop:ClientSide.OnValidate>
					</com:TDataTypeValidator>
				</div>
			</com:TActivePanel>
			<div class="w3-row directive_field">
				<div class="w3-col w3-third"><i class="w3-large fa fa-desktop"></i> &nbsp;<com:TLabel ForControl="Client" Text="<%[ Client: ]%>" CssClass="w3-large" /></div>
				<div class="w3-half">
					<com:TActiveDropDownList ID="Client" AutoPostBack="false" CssClass="w3-select w3-border" />
				</div>
			</div>
			<div class="w3-row directive_field">
				<div class="w3-col w3-third"><i class="w3-large fa fa-list-alt"></i> &nbsp;<com:TLabel ForControl="FileSet" Text="<%[ FileSet: ]%>" CssClass="w3-large" /></div>
				<div class="w3-half">
					<com:TActiveDropDownList ID="FileSet" AutoPostBack="false" CssClass="w3-select w3-border" />
				</div>
			</div>
			<div class="w3-row directive_field">
				<div class="w3-col w3-third"><i class="w3-large fa fa-tape"></i> &nbsp;<com:TLabel ForControl="Pool" Text="<%[ Pool: ]%>" CssClass="w3-large" /></div>
				<div class="w3-half">
					<com:TActiveDropDownList ID="Pool" AutoPostBack="false" CssClass="w3-select w3-border" />
				</div>
			</div>
			<div class="w3-row directive_field">
				<div class="w3-col w3-third"><i class="w3-large fa fa-database"></i> &nbsp;<com:TLabel ForControl="Storage" Text="<%[ Storage: ]%>" CssClass="w3-large" /></div>
				<div class="w3-half">
					<com:TActiveDropDownList ID="Storage" AutoPostBack="false" CssClass="w3-select w3-border" />
				</div>
				<div id="run_job_storage_from_config_info" style="line-height: 39px; display: none;">
					&nbsp;&nbsp;<i class="fas fa-info-circle" title="<%[ The storage has been selected basing on job configuration. This item may require adjusting before job run. ]%>"></i>
				</div>
			</div>
			<div class="w3-row directive_field">
				<div class="w3-col w3-third"><i class="w3-large fa fa-sort-numeric-up"></i> &nbsp;<com:TLabel ForControl="Priority" Text="<%[ Priority: ]%>" CssClass="w3-large" /></div>
				<div class="w3-half">
					<com:TActiveTextBox ID="Priority" CssClass="w3-input w3-border w3-quarter" AutoPostBack="false" />
					<com:TRequiredFieldValidator
						ValidationGroup="JobGroup"
						ControlToValidate="Priority"
						ErrorMessage="<%[ Priority value must be integer greather than 0. ]%>"
						ControlCssClass="validation-error"
						Display="Dynamic"
					/>
					<com:TDataTypeValidator
						ID="PriorityValidator"
						ValidationGroup="JobGroup"
						ControlToValidate="Priority"
						ErrorMessage="<%[ Priority value must be integer greather than 0. ]%>"
						ControlCssClass="validation-error"
						Display="Dynamic"
						DataType="Integer"
					/>
				</div>
			</div>
			<com:TActivePanel ID="AccurateLine" CssClass="w3-row directive_field">
				<div class="w3-col w3-third"><i class="w3-large fa fa-balance-scale"></i> &nbsp;<com:TLabel ForControl="Accurate" Text="<%[ Accurate: ]%>" CssClass="w3-large"/></div>
				<div class="field"><com:TActiveCheckBox ID="Accurate" AutoPostBack="false" CssClass="w3-check" /></div>
			</com:TActivePanel>
		</div>
		<div class="w3-row-padding w3-section">
			<div class="w3-col w3-half"><%[ Command status: ]%></div>
			<div class="w3-col w3-half">
				<i id="command_status_start" class="fa fa-step-forward" title="<%[ Ready ]%>"></i>
				<i id="command_status_loading" class="fa fa-sync w3-spin" style="display: none" title="<%[ Loading... ]%>"></i>
				<i id="command_status_finish" class="fa fa-check" style="display: none" title="<%[ Finished ]%>"></i>
			</div>
		</div>
		<div id="run_job_log" class="w3-container" style="display: none;">
			<a href="javascript:void(0)" onclick="W3SubTabs.open('estimate_job_subtab_result', 'estimate_job_result', 'run_job_log');">
				<div id="estimate_job_subtab_result" class="subtab_btn w3-half w3-bottombar w3-padding w3-border-red"><%[ Result ]%></div>
			</a>
			<a href="javascript:void(0)" onclick="W3SubTabs.open('estimate_job_subtab_raw_output', 'estimate_job_raw_output', 'run_job_log');">
				<div id="estimate_job_subtab_raw_output" class="subtab_btn w3-half w3-bottombar w3-padding"><%[ Raw output ]%></div>
			</a>
			<div id="estimate_job_result" class="subtab_item w3-clear">
				<div class="w3-row-padding w3-section">
					<div class="w3-col w3-third"><%[ Estimated job files ]%>:</div>
					<div class="w3-col w3-half" id="estimate_job_files">-</div>
				</div>
				<div class="w3-row-padding w3-section">
					<div class="w3-col w3-third"><%[ Estimated job bytes ]%>:</div>
					<div class="w3-col w3-half" id="estimate_job_bytes">-</div>
				</div>
			</div>
			<div id="estimate_job_raw_output" class="subtab_item w3-panel w3-card" style="display: none; max-height: 200px; overflow-x: auto;">
				<div class="w3-code">
					<pre><com:TActiveLabel ID="RunJobLog" /></pre>
				</div>
			</div>
		</div>
		<div id="run_job_raw_output_container" class="w3-panel w3-card" style="display: none; max-height: 200px; overflow-x: auto;">
			<div class="w3-code">
				<pre><span id="run_job_raw_output"></span></pre>
			</div>
		</div>
		<div class="w3-row-padding w3-section">
			<div class="w3-col">
				<com:TActiveCheckBox ID="GoToJobAfterStart" AutoPostBack="false" CssClass="w3-check" Checked="true" />
				&nbsp;<com:TLabel ForControl="GoToJobAfterStart" Text="<%[ After starting the job, go to the running job status ]%>" />
			</div>
		</div>
		<footer class="w3-container w3-center">
			<com:TActiveLinkButton
				ID="Estimate"
				OnClick="estimate"
				CssClass="w3-button w3-section w3-green w3-padding"
			>
				<prop:Attributes.onclick>
					var mainForm = Prado.Validation.getForm();
					return Prado.Validation.validate(mainForm, 'JobGroup');
				</prop:Attributes.onclick>
				<prop:ClientSide.OnLoading>
					document.getElementById('status_command_loading').style.visibility = 'visible';
				</prop:ClientSide.OnLoading>
				<prop:ClientSide.OnComplete>
					$('#status_update_slots_loading').css('visibility', 'hidden');
					document.getElementById('status_command_loading').style.visibility = 'hidden';
					show_job_log(true);
					scroll_down_job_log();
				</prop:ClientSide.OnComplete>
				<i class="fas fa-weight"></i> &nbsp;<%[ Estimate job ]%>
			</com:TActiveLinkButton>
			<com:TActiveLinkButton
				ID="Run"
				ValidationGroup="JobGroup"
				CausesValidation="true"
				OnClick="runJobAgain"
				CssClass="w3-button w3-section w3-green w3-padding"
			>
				<prop:Attributes.onclick>
					var mainForm = Prado.Validation.getForm();
					return Prado.Validation.validate(mainForm, 'JobGroup');
				</prop:Attributes.onclick>
				<i class="fas fa-paper-plane"></i> &nbsp;<%[ Run job ]%>
			</com:TActiveLinkButton>
			<script>
				var run_job_go_to_running_job = function(jobid) {
					document.location.href = '/web/job/history/' + jobid + '/';
				};
			</script>
			<i id="status_command_loading" class="fa fa-sync w3-spin" style="visibility: hidden;"></i>
		</footer>
	</div>
</div>
<com:TCallback ID="EstimateOutputRefresh"
	OnCallback="refreshEstimateOutput"
>
	<prop:ClientSide.OnLoading>
		$('#status_command_loading').css('visibility', 'visible');
		var joblog = document.getElementById('run_job_log');
		if ((joblog.offsetHeight + joblog.scrollTop) === joblog.scrollHeight) {
			command_joblog_scroll = true;
		} else {
			command_joblog_scroll = false;
		}
	</prop:ClientSide.OnLoading>
	<prop:ClientSide.OnComplete>
		$('#status_command_loading').css('visibility', 'hidden');
		if (command_joblog_scroll) {
			scroll_down_job_log();
		}
	</prop:ClientSide.OnComplete>
</com:TCallback>
<script type="text/javascript">
var command_joblog_scroll = false;
function close_run_job_window() {
	document.getElementById('run_job').style.display = 'none';
	show_job_log(false);
	set_job_log('');
	set_loading_status('start');
}
function set_job_log(log) {
	document.getElementById('<%=$this->RunJobLog->ClientID%>').textContent = log;
}
function show_job_log(show) {
	var joblog = document.getElementById('run_job_log');
	if (show === true) {
		joblog.style.display = '';
	} else {
		joblog.style.display = 'none';
	}
}
function scroll_down_job_log() {
	var joblog = document.getElementById('run_job_log');
	joblog.scrollTo(0, joblog.scrollHeight);
}
function set_loading_status(status) {
	var start = document.getElementById('command_status_start');
	var loading = document.getElementById('command_status_loading');
	var finish = document.getElementById('command_status_finish');
	if (status === 'finish') {
		start.style.display = 'none';
		loading.style.display = 'none';
		finish.style.display = '';
		check_estimated_results();
	} else if (status === 'loading') {
		start.style.display = 'none';
		loading.style.display = '';
		finish.style.display = 'none';
		set_estimation();
	} else if (status === 'start') {
		start.style.display = '';
		loading.style.display = 'none';
		finish.style.display = 'none';
		set_estimation();
	}
}
function estimate_output_refresh(out_id) {
	setTimeout(function() {
		set_estimate_output(out_id)
	}, 3000);
}
function set_estimate_output(out_id) {
	var cb = <%=$this->EstimateOutputRefresh->ActiveControl->Javascript%>;
	cb.setCallbackParameter(out_id);
	cb.dispatch();
}
function check_estimated_results() {
	const joblog = document.getElementById('<%=$this->RunJobLog->ClientID%>');
	const log = joblog.textContent.split("\n");
	const pattern = /\sestimate files=([\d,]+)\sbytes=([\d,]+)$/;
	let ret;
	for (const l of log) {
		ret = l.match(pattern);
		if (ret) {
			set_estimation(ret[2], ret[1]);
			break;
		}
	}
}
function set_estimation(bytes, files) {
	let bytes_f;
	if (bytes) {
		bytes_f = bytes.replace(/,/g, '');
		bytes_f = Units.get_formatted_size(bytes_f);
	}
	const bytes_el = document.getElementById('estimate_job_bytes');
	bytes_el.textContent = bytes_f ? bytes_f : '-';
	const files_el = document.getElementById('estimate_job_files');
	files_el.textContent = files ? files : '-';
}
function set_run_job_output(output) {
	const container = document.getElementById('run_job_raw_output_container');
	const log = document.getElementById('run_job_raw_output');
	log.textContent = output;
	container.style.display = '';
}
</script>
