<div id="job_log_window" class="w3-modal">
	<div class="w3-modal-content w3-animate-top w3-card-4">
		<header class="w3-container w3-green">
			<i id="job_log_clipboard_copy" class="fa-solid fa-copy fa-fw w3-xxlarge w3-right w3-margin-top pointer w3-animate-opacity" style="margin-right: 48px;" title="<%[ Copy to clipboard ]%>" onclick="oJobLogWindow.copy_to_clipboard();"></i>
			<i id="job_log_clipboard_copied"class="fa-solid fa-check fa-fw w3-xxlarge w3-right w3-margin-top w3-animate-opacity" style="margin-right: 48px; display: none;"></i>
			<i id="job_log_save_file" class="fa-solid fa-download w3-xxlarge w3-right w3-margin-top pointer w3-animate-opacity" style="margin-right: 16px;" title="<%[ Save log in file ]%>" onclick="oJobLogWindow.save_file();"></i>
			<span onclick="oJobLogWindow.show_window(false);" class="w3-button w3-display-topright">&times;</span>
			<h2><%[ Job log ]%> - <span id="job_log_jobname"></span></h2>
		</header>
		<div id="job_log_content" class="w3-padding" style="height: 600px; overflow-y: auto; overflow-x: none;">
			<div class="w3-code">
				<pre id="job_log_output" class="w3-small"></pre>
			</div>
		</div>
	</div>
</div>
<com:TCallback ID="GetJobLog" OnCallback="loadJobLog" />
<script>
var oJobLogWindow = {
	jobid: 0,
	jobname: '',
	log_raw: '',
	refresh: false,
	refresh_interval: 5000,
	ids: {
		win: 'job_log_window',
		content: 'job_log_content',
		out: 'job_log_output',
		name: 'job_log_jobname',
		clipboard_copy: 'job_log_clipboard_copy',
		clipboard_copied: 'job_log_clipboard_copied',
	},
	open_window: function(jobid, jobname, refresh) {
		this.clear_log();
		this.refresh = refresh;
		if (jobname) {
			const jname = document.getElementById(this.ids.name);
			jname.textContent = jobname;
		}
		this.jobid = jobid;
		this.jobname = jobname;
		this.get_log();
		this.show_window(true);
	},
	show_window: function(show) {
		const win = document.getElementById(this.ids.win);
		win.style.display = show ? 'block' : 'none';
	},
	is_open: function() {
		const win = document.getElementById(this.ids.win);
		return (win.style.display == 'block');
	},
	get_log: function(jobid) {
		const cb = <%=$this->GetJobLog->ActiveControl->Javascript%>;
		cb.setCallbackParameter(this.jobid);
		cb.dispatch();
	},
	set_log: function(log, log_raw, force_stop) {
		oJobLogWindow.log_raw = log_raw;
		const is_bottom = oJobLogWindow.is_scroll_bottom();
		const out = document.getElementById(oJobLogWindow.ids.out);
		out.innerHTML = log;
		if (is_bottom) {
			oJobLogWindow.set_scroll_bottom();
		}
		if (oJobLogWindow.is_open() && oJobLogWindow.refresh && !force_stop) {
			setTimeout(oJobLogWindow.get_log.bind(oJobLogWindow), oJobLogWindow.refresh_interval);
		}
	},
	clear_log: function() {
		this.set_log('', '', true);
	},
	set_scroll_bottom: function() {
		const container = document.getElementById(this.ids.content);
		container.scrollTop = container.scrollHeight;
	},
	is_scroll_bottom: function() {
		const container = document.getElementById(this.ids.content);
		return (container.scrollTop === (container.scrollHeight - container.offsetHeight));
	},
	copy_to_clipboard: function() {
		const copy = document.getElementById(this.ids.clipboard_copy);
		const copied = document.getElementById(this.ids.clipboard_copied);
		const log = this.log_raw.join("\n");
		copy_to_clipboard(log);
		copy.style.display = 'none';
		copied.style.display = 'inline-block';
		setTimeout(() => {
			copied.style.display = 'none';
			copy.style.display = 'inline-block';
		}, 1300);
	},
	save_file: function() {
		const log = this.log_raw.join("\n");
		const filename = '%jobid_%jobname_job_log.txt'.replace('%jobid', this.jobid).replace('%jobname', this.jobname);
		save_file(log, filename, 'text/plain');
	}
}
</script>
