<div id="job_log_window" class="w3-modal">
	<div class="w3-modal-content w3-animate-top w3-card-4">
		<header class="w3-container w3-green">
			<span onclick="oJobLogWindow.show_window(false);" class="w3-button w3-display-topright">&times;</span>
			<h2><%[ Job log ]%> - <span id="job_log_jobname"></span></h2>
		</header>
		<div id="job_log_content" class="w3-container" style="height: 650px; overflow-y: auto; overflow-x: none;">
			<!--div class="w3-code">
				<pre id="job_log_output" class="w3-small"></pre>
			</div-->
			<com:Bacularis.Web.Portlets.JobLogBox ID="JobLogBox" LogCSS="<%=$this->getLogCSS()%>" />
		</div>
	</div>
</div>
<com:TCallback ID="GetJobLog" OnCallback="loadJobLog" />
<script>
var oJobLogWindow = {
	job_name: 0,
	log_raw: '',
	log_box: oJobLogBox<%=$this->JobLogBox->ClientID%>,
	is_bottom: null,
	ids: {
		win: 'job_log_window',
		content: 'job_log_content',
		out: 'job_log_output',
		job_name: 'job_log_jobname',
		clipboard_copy: 'job_log_clipboard_copy',
		clipboard_copied: 'job_log_clipboard_copied',
	},
	open_window: function(jobid, job_name, refresh) {
		this.set_job_name(job_name);
		this.log_box.clear_log();
		let init = true;
		const pre_set_cb = () => {
			// Check if scroll is at the bottom before providing new log
			if (!init) {
				this.is_bottom = this.is_scroll_bottom();
			} else {
				this.is_bottom = init = false;
			}
			if (!this.is_open()) {
				this.log_box.stop_refreshing();
			}
		};
		const post_set_cb = () => {
			if (this.is_bottom) {
				// Scroll was at the bottom, so set it at the bottom again
				this.set_scroll_bottom();
			}
		};
		this.log_box.init_log(jobid, job_name, refresh, pre_set_cb, post_set_cb);
		this.show_window(true);
	},
	show_window: function(show) {
		const win = document.getElementById(this.ids.win);
		win.style.display = show ? 'block' : 'none';
	},
	set_job_name: function(name) {
		const job_name = document.getElementById(this.ids.job_name);
		job_name.textContent = name;
	},
	is_open: function() {
		const win = document.getElementById(this.ids.win);
		return (win.style.display == 'block');
	},
	set_log: function(log, log_raw, refresh) {
		this.log_box.set_log(log, log_raw, refresh);
	},
	set_scroll_bottom: function() {
		const container = document.getElementById(this.ids.content);
		container.scrollTop = container.scrollHeight;
	},
	is_scroll_bottom: function() {
		const container = document.getElementById(this.ids.content);
		return (container.scrollTop === (container.scrollHeight - container.offsetHeight));
	}
}
</script>
