<script>
var oMonitor;
var default_refresh_interval = 60000;
var default_fast_refresh_interval = 10000;
var timeout_handler;
var last_callback_time = 0;
var callback_time_offset = 0;
var oData;
var MonitorCalls = [];
var MonitorCallsInterval = [];
$(function() {
	oMonitor = function() {
		return $.ajax('<%=$this->Service->constructUrl("Monitor")%>', {
			dataType: 'json',
			type: 'post',
			data: {
				'params': (typeof(MonitorParams) == 'object' ? MonitorParams : []),
				'use_limit' : <%=$this->Service->getRequestedPagePath() == "Dashboard" ? '0' : '1'%>,
				'use_age' : <%=$this->Service->getRequestedPagePath() == "Dashboard" ? '1' : '0'%>
			},
			beforeSend: function() {
				last_callback_time = new Date().getTime();
			},
			success: function(response) {
				if (timeout_handler) {
					clearTimeout(timeout_handler);
				}
				if (response && response.hasOwnProperty('error') && response.error.error !== 0) {
					show_error(response.error.output, response.error.error);
				}

				oData = response;
				if ('<%=get_class($this->Service->getRequestedPage())%>' == 'Dashboard') {
					const job_age_on_job_status_graph = <%=$this->job_age_on_job_status_graph%>;
					Statistics.grab_statistics(oData, {
						job_states: JobStatus.get_states(),
						job_age: job_age_on_job_status_graph,
						txts: {
							jobfiles: '<%[ Job files ]%>',
							jobbytes: '<%[ Job size ]%>'
						}
					});
					let age_label = '';
					if (job_age_on_job_status_graph > 0) {
						const job_age = Units.format_time_period(job_age_on_job_status_graph);
						const job_age_unit = job_age.format + (job_age.value > 1 ? 's' : '');
						const label_format = ' - <%[ last %time %unit ]%>';
						age_label = label_format.replace('%time', job_age.value);
						age_label = age_label.replace('%unit', job_age_unit);
					}
					Dashboard.set_text({
						js_sum_title: '<%[ Job status summary ]%>' + age_label,
						bytes_files_title: '<%[ Job size and files per day ]%>' + age_label
					});
					Dashboard.update_all(Statistics);
				}
				const running_jobs_len = oData.running_jobs.length
				if (running_jobs_len > 0) {
					refreshInterval =  callback_time_offset + default_fast_refresh_interval;
				} else {
					refreshInterval = default_refresh_interval;
				}
				if (typeof(job_callback_func) == 'function') {
					job_callback_func();
				}
				document.getElementById('running_jobs').textContent = running_jobs_len;
				set_page_title_value(running_jobs_len);
				timeout_handler = setTimeout("oMonitor()", refreshInterval);

				var calls_len = MonitorCalls.length;
				for (var i = 0; i < calls_len; i++) {
					MonitorCalls[i]();
				}
				var calls_interval_len = MonitorCallsInterval.length;
				for (var i = 0; i < calls_interval_len; i++) {
					MonitorCallsInterval[i]();
				}
				if (calls_len > 0) {
					Formatters.set_formatters();
				}
				MonitorCalls = [];
			}
		});
	};
	oMonitor();
});
</script>
