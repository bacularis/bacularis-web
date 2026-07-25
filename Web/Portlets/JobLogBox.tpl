<div id="job_log_box<%=$this->ClientID%>">
	<div class="w3-white" style="display: flex; justify-content: space-between; position: sticky; top: 0; width: 100%; padding-top: 4px; line-height: 22px;">
		<div id="job_log_pagination_toolbar<%=$this->ClientID%>" style="width: 350px; display: flex; visibility: hidden; justify-content: flex-start; height: 35px;">
			<div class="w3-bar-item" style="padding: 7px 8px;" title="<%[ Go to previous page ]%>">
				<a href="javascript:void(0)" class="raw" onclick="oJobLogBox<%=$this->ClientID%>.go_prev();">
					<i id="job_log_pagination_prev<%=$this->ClientID%>" class="fa-solid fa-chevron-left fa-fw pointer w3-animate-opacity"></i> <span class="w3-hide-small"><%[ Prev ]%></span>
				</a>
			</div>
			<div class="w3-bar-item" style="padding: 7px 8px;" title="<%[ Go to next page ]%>">
				<a href="javascript:void(0)" class="raw" onclick="oJobLogBox<%=$this->ClientID%>.go_next();">
					<span class="w3-hide-small"><%[ Next ]%></span> <i id="job_log_pagination_next<%=$this->ClientID%>" class="fa-solid fa-chevron-right fa-fw pointer w3-animate-opacity"></i>
				</a>
			</div>
			<div class="w3-padding-small" title="<%[ Log page offset and limit ]%>" style="margin-top 2px;">
				<span class="w3-hide-small" ><%[ Offset: ]%></span> <input type="text" id="job_log_pagination_offset<%=$this->ClientID%>" value="0" class="w3-input w3-border w3-tiny w3-show-inline-block" style="width: 50px; height: 26px;" />
				<span class="w3-hide-small"><%[ Limit: ]%></span> <input type="text" id="job_log_pagination_limit<%=$this->ClientID%>" value="500" class="w3-input w3-border w3-tiny w3-show-inline-block" style="width: 50px; height: 26px;" />
			</div>
		</div>
		<div style="width: calc(100% - 350px); display: flex; justify-content: flex-end; height: 35px;">
			<div class="w3-padding-small" style="width: 36px">
				<i id="job_log_pagination_loader<%=$this->ClientID%>" class="fa-solid fa-sync-alt fa-fw w3-spin w3-hide-small" style="display: none;"></i>
			</div>
			<div class="w3-bar-item" style="padding: 7px 8px;" title="<%[ Copy to clipboard ]%>">
				<a href="javascript:void(0)" class="raw" onclick="oJobLogBox<%=$this->ClientID%>.copy_to_clipboard();">
					<i id="job_log_clipboard_copy<%=$this->ClientID%>" class="fa-solid fa-copy fa-fw pointer w3-animate-opacity"></i>
					<i id="job_log_clipboard_copied<%=$this->ClientID%>" class="fa-solid fa-check fa-fw w3-animate-opacity" style="display: none;"></i>
					<span class="w3-hide-small">&nbsp;<%[ Copy ]%></span>
				</a>
			</div>
			<div class="w3-bar-item" style="padding: 7px 8px;" title="<%[ Save log in file ]%>">
				<a href="javascript:void(0)" class="raw" onclick="oJobLogBox<%=$this->ClientID%>.save_file();">
					<i class="fa-solid fa-download pointer w3-animate-opacity"></i>
					<span class="w3-hide-small">&nbsp;<%[ Save ]%></span>
				</a>
			</div>
			<div class="w3-bar-item" style="padding: 7px 8px;" title="<%[ Refresh job log ]%>">
				<a href="javascript:void(0)" class="raw" onclick="oJobLogBox<%=$this->ClientID%>.get_log(); oJobLogBox<%=$this->ClientID%>.show_refresh_loader(true);">
					<i id="job_log_refresh_btn<%=$this->ClientiD%>" class="fa-solid fa-sync"></i>
					<span class="w3-hide-small">&nbsp;<%[ Refresh ]%></span>
				</a>
			</div>
			<div class="w3-bar-item" style="padding: 7px 8px;" title="<%[ Set job log order (ascending/descending) ]%>">
				<a href="javascript:void(0)" class="raw" onclick="oJobLogBox<%=$this->ClientID%>.change_log_order();">
					<i id="job_log_order_btn<%=$this->ClientiD%>" class="fa-solid fa-arrow-down-short-wide"></i>
					<span class="w3-hide-small">&nbsp;<%[ Log order ]%></span>
				</a>
			</div>
			<div style="padding: 7px 8px;" title="<%[ Job state ]%>">
				<span class="w3-hide-small"><%[ Job status ]%>:&nbsp;</span>
				<span id="job_running_status<%=$this->ClientID%>">
					<i class="fa-solid fa-question"></i>
				</span>
			</div>
		</div>
	</div>
	<div class="w3-code" style="margin-top: 0 !important;">
		<pre<%=$this->getLogCSS() ? ' class="' . $this->getLogCSS() . '"' : ''%> style="margin-top: 0 !important;"><span id="job_log<%=$this->ClientID%>"></span></pre>
	</div>
</div>
<com:TCallback ID="GetJobLog" OnCallback="loadLog" />
<com:TCallback ID="ChangeLogOrder" OnCallback="changeLogOrder" />
<script>
var oJobLogBox<%=$this->ClientID%> = {
	jobid: 0,
	job_name: '',
	log_raw: '',
	refreshable: false,
	refresh_interval: 5000,
	timeout: null,
	pre_set_cb: null,
	post_set_cb: null,
	order_types: {
		asc: <%=JobLogBox::SORT_ASC%>,
		desc: <%=JobLogBox::SORT_DESC%>
	},
	defaults: {
		offset: 0,
		limit: 1000
	},
	ids: {
		out: 'job_log<%=$this->ClientID%>',
		clipboard_copy: 'job_log_clipboard_copy<%=$this->ClientID%>',
		clipboard_copied: 'job_log_clipboard_copied<%=$this->ClientID%>',
		refresh_btn: 'job_log_refresh_btn<%=$this->ClientID%>',
		order_btn: 'job_log_order_btn<%=$this->ClientiD%>',
		running_status: 'job_running_status<%=$this->ClientID%>',
		pagination_prev: 'job_log_pagination_prev<%=$this->ClientID%>',
		pagination_next: 'job_log_pagination_next<%=$this->ClientID%>',
		pagination_offset: 'job_log_pagination_offset<%=$this->ClientID%>',
		pagination_limit: 'job_log_pagination_limit<%=$this->ClientID%>',
		pagination_toolbar: 'job_log_pagination_toolbar<%=$this->ClientID%>',
		loader: 'job_log_pagination_loader<%=$this->ClientID%>'
	},
	init: function() {
		this.set_events();
	},
	init_log: function(jobid, job_name, refreshable, pre_set_cb, post_set_cb) {
		this.jobid = jobid;
		this.job_name = job_name;
		this.refreshable = refreshable;
		this.pre_set_cb = pre_set_cb;
		this.post_set_cb = post_set_cb;
		this.set_pagination(this.defaults.offset, this.defaults.limit);
		this.show_pagination(false);
		this.get_log();
	},
	set_events: function() {
		const func_get_logs = (e) => {
			if (e.key === 'Enter') {
				this.get_log();
			}
		};

		const offset = document.getElementById(this.ids.pagination_offset);
		offset.addEventListener('keyup', func_get_logs)

		const limit = document.getElementById(this.ids.pagination_limit);
		limit.addEventListener('keyup', func_get_logs);
	},
	get_log: function(opts) {
		this.show_loader(true);

		// Define base callback parameters
		const pgn = this.get_pagination();
		const params = {
			jobid: this.jobid,
			offset: pgn.offset,
			limit: pgn.limit
		};
		if (opts) {
			// Add additional parameters
			for (const opt in opts) {
				params[opt] = opts[opt];
			}
		}
		$(() => {
			const cb = <%=$this->GetJobLog->ActiveControl->Javascript%>;
			cb.setCallbackParameter(params);
			cb.dispatch();
		});
	},
	go_prev: function() {
		const pgn = this.get_pagination();
		const offset = document.getElementById(this.ids.pagination_offset);
		const val = pgn.offset - pgn.limit;
		offset.value = val >= 0 ? val : 0;
		this.get_log();
	},
	go_next: function() {
		const pgn = this.get_pagination();
		const offset = document.getElementById(this.ids.pagination_offset);
		offset.value = pgn.offset + pgn.limit;
		this.get_log();
	},
	set_log: function(params) {
		const self = oJobLogBox<%=$this->ClientID%>;
		self.log_raw = params.joblog;
		if (typeof(self.pre_set_cb) == 'function') {
			self.pre_set_cb();
		}
		const out = document.getElementById(self.ids.out);
		out.innerHTML = params.log;
		if (typeof(self.post_set_cb) == 'function') {
			self.post_set_cb();
		}
		const running = (JobStatus.is_running(params.jobstatus) || JobStatus.is_waiting(params.jobstatus));
		if (self.refreshable && running) {
			if (self.timeout) {
				clearTimeout(self.timeout);
			}
			self.timeout = setTimeout(self.get_log.bind(self), self.refresh_interval);
		}
		const pgn = self.get_pagination();
		const show = params.joblog.length >= pgn.limit;
		if (show) {
			self.show_pagination(show);
		}
		self.show_refresh_loader(false);
		self.set_job_running_status(params.jobstatus);
		self.update_log_order_btn(params.order_type);
		self.show_loader(false);
	},
	stop_refreshing: function() {
		this.refreshable = false;
	},
	show_pagination: function(show) {
		const toolbar = document.getElementById(this.ids.pagination_toolbar);
		toolbar.style.visibility = show ? 'visible' : 'hidden';
	},
	show_loader: function(show) {
		const toolbar = document.getElementById(this.ids.loader);
		toolbar.style.display = show ? 'inline-block' : 'none';
	},
	clear_log: function() {
		const params = {
			joblog: [],
			log: '',
			jobstatus: '',
			order_type: ''
		};
		this.set_log(params);
	},
	show_refresh_loader: function(show) {
		const loader = document.getElementById(this.ids.refresh_btn);
		if (show) {
			loader.classList.add('w3-spin');
		} else {
			loader.classList.remove('w3-spin');
		}
	},
	change_log_order: function() {
		const params = {
			change_order: true
		};
		this.get_log(params);
	},
	update_log_order_btn: function(order_type) {
		const btn = document.getElementById(this.ids.order_btn);
		if (order_type == this.order_types.desc) {
			// Descending sort order
			btn.classList.remove('fa-arrow-down-short-wide');
			btn.classList.add('fa-arrow-down-wide-short');
		} else if (order_type == this.order_types.asc) {
			// Ascending sort order
			btn.classList.remove('fa-arrow-down-wide-short');
			btn.classList.add('fa-arrow-down-short-wide');
		}
	},
	get_pagination: function() {
		const offset = document.getElementById(this.ids.pagination_offset);
		const limit = document.getElementById(this.ids.pagination_limit);
		return {
			offset: parseInt(offset.value, 10),
			limit: parseInt(limit.value, 10)
		}
	},
	set_pagination: function(offset, limit) {
		if (typeof(offset) == 'number') {
			const offset_el = document.getElementById(this.ids.pagination_offset);
			offset_el.value = offset;
		}

		if (typeof(limit) == 'number') {
			const limit_el = document.getElementById(this.ids.pagination_limit);
			limit_el.value = limit;
		}
	},
	set_job_running_status: function(jobstatus) {
		if (!jobstatus) {
			return;
		}
		const stat = document.getElementById(this.ids.running_status);
		const oldel = stat.firstChild;
		const newel = JobStatus.get_icon(jobstatus);
		if (oldel.className != newel.className) {
			stat.innerHTML = newel.outerHTML;
		}
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
		const filename = '%jobid_%jobname_job_log.txt'.replace('%jobid', this.jobid).replace('%jobname', this.job_name);
		save_file(log, filename, 'text/plain');
	}
}
$(() => {
	oJobLogBox<%=$this->ClientID%>.init();
	<%=$this->getAutoLoad() ? 'oJobLogBox' . $this->ClientID . '.get_log();' : ''%>
});
</script>
