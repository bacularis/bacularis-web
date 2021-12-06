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
					Statistics.grab_statistics(oData, JobStatus.get_states());
					Dashboard.set_text({
						js_sum_title: '<%[ Job status summary ]%>'
					});
					Dashboard.update_all(Statistics);
				}

				if (oData.running_jobs.length > 0) {
					refreshInterval =  callback_time_offset + default_fast_refresh_interval;
				} else {
					refreshInterval = default_refresh_interval;
				}
				if (typeof(job_callback_func) == 'function') {
					job_callback_func();
				}
				document.getElementById('running_jobs').textContent = oData.running_jobs.length;
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
