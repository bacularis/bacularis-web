<div id="bulk_cancel_jobs_modal" class="w3-modal" style="display: none">
	<div class="w3-modal-content w3-card-4 w3-animate-zoom" style="width: 830px">
		<header class="w3-container w3-green">
			<span onclick="oBulkCancelJobsModal.show_window(false);" class="w3-button w3-display-topright">×</span>
			<h2><%[ Cancel jobs ]%></h2>
		</header>
		<div class="w3-margin-left w3-margin-right w3-padding">
			<div class="w3-container w3-row w3-margin-bottom">
				<div class="w3-third w3-col"><%[ Component ]%>:</div>
				<div class="w3-half w3-col bold" id="bulk_cancel_jobs_component_type"></div>
			</div>
			<div id="bulk_cancel_jobs_component_names" class="w3-margin-bottom"></div>
			<div id="bulk_cancel_jobs_jobs" class="w3-margin-bottom"></div>
			<div class="w3-container w3-row w3-margin-bottom">
				<div class="w3-third w3-col"><%[ Job types to cancel ]%>:</div>
				<div class="w3-half w3-col">
					<select id="bulk_cancel_jobs_jobs_to_cancel" class="w3-select w3-border">
						<option value="all"><%[ Cancel all jobs ]%></option>
						<option value="running"><%[ Cancel running jobs ]%></option>
						<option value="waiting"><%[ Cancel waiting jobs to run ]%></option>
					</select>
				</div>
			</div>
		</div>
		<footer class="w3-container w3-center w3-border-top">
			<button type="button" class="w3-button w3-section w3-red" onclick="oBulkCancelJobsModal.show_window(false);"><i class="fa-solid fa-times"></i> &nbsp;<%[ Close ]%></button>
			<button type="button" class="w3-button w3-section w3-green" onclick="oBulkCancelJobsModal.cancel_jobs();"><i class="fa-solid fa-stop"></i> &nbsp;<%[ Cancel jobs ]%></button>
			<i id="bulk_cancel_jobs_loader" class="fa fa-sync w3-spin w3-margin-left" style="visibility: hidden;"></i>
		</footer>
	</div>
</div>
<com:TCallback ID="CancelJob" OnCallback="cancelJob" />
<script>
var oBulkCancelJobsModal = {
	jobs: [],
	jobs_cancel: [],
	callback: null,
	ids: {
		modal: 'bulk_cancel_jobs_modal',
		component_type: 'bulk_cancel_jobs_component_type',
		component_names: 'bulk_cancel_jobs_component_names',
		jobs_to_cancel: 'bulk_cancel_jobs_jobs_to_cancel',
		jobs: 'bulk_cancel_jobs_jobs',
		jobid_prefix: 'bulk_cancel_jobs_jobid_',
		loader: 'bulk_cancel_jobs_loader'
	},
	open_window: function(component_type, component_names, jobs, cb) {
		this.set_window(component_type, component_names, jobs, cb);
		this.reset_jobs_to_cancel();
		this.show_window(true);
	},
	close_window: function() {
		this.show_window(false);
	},
	show_window: function(show) {
		const win = document.getElementById(this.ids.modal);
		win.style.display = show ? 'block' : 'none';
	},
	show_loader: function(show) {
		const loader = document.getElementById(this.ids.loader);
		loader.style.visibility = show ? 'visible' : 'hidden';
	},
	reset_jobs_to_cancel: function() {
		const jtc = document.getElementById(this.ids.jobs_to_cancel);
		jtc.selectedIndex = 0;
	},
	set_window: function(component_type, component_names, jobs, cb) {
		this.set_component_type(component_type);
		this.set_component_names(component_names);
		this.set_jobs(jobs);
		this.set_callback(cb);
	},
	set_component_type: function(component_type) {
		const el = document.getElementById(this.ids.component_type);
		el.textContent = component_type;
	},
	set_component_names: function(component_names) {
		this.clear_component_names();
		const el = document.getElementById(this.ids.component_names);
		let row, coll, colr;
		for (let i = 0; i < component_names.length; i++) {
			row = document.createElement('DIV');
			row.classList.add('w3-container', 'w3-row');

			coll = document.createElement('DIV');
			coll.classList.add('w3-third', 'w3-col');
			if (i == 0) {
				coll.textContent = '<%[ Component name ]%>:';
			} else {
				coll.innerHTML = '&nbsp;';
			}

			colr = document.createElement('DIV');
			colr.classList.add('w3-half', 'w3-col', 'bold');
			colr.textContent = component_names[i];

			row.appendChild(coll);
			row.appendChild(colr);
			el.appendChild(row);
		}
	},
	clear_component_names: function() {
		const el = document.getElementById(this.ids.component_names);
		while (el.firstChild) {
			el.removeChild(el.firstChild);
		}
	},
	set_jobs: function(jobs) {
		this.jobs = jobs;
		this.clear_jobs();
		const el = document.getElementById(this.ids.jobs);
		let row, coll, colm, colr;
		for (let i = 0; i < jobs.length; i++) {
			row = document.createElement('DIV');
			row.classList.add('w3-container', 'w3-row');

			coll = document.createElement('DIV');
			coll.classList.add('w3-third', 'w3-col');
			if (i == 0) {
				coll.textContent = '<%[ Jobs ]%>:';
			} else {
				coll.innerHTML = '&nbsp;';
			}

			const jobstatus = document.createElement('SPAN');
			jobstatus.style.display = 'inline-block';
			jobstatus.style.width = '20px';
			jobstatus.style.textAlign = 'center';
			jobstatus.innerHTML = JobStatus.get_icon(jobs[i].jobstatus).outerHTML;

			colm = document.createElement('DIV');
			colm.classList.add('w3-half', 'w3-col');
			colm.style.padding = '2px 0';
			colm.innerHTML =  jobstatus.outerHTML + ' [' + jobs[i].jobid + '] ' + jobs[i].name;

			colr = document.createElement('DIV');
			colr.id = this.ids.jobid_prefix + jobs[i].jobid;
			colr.classList.add('w3-col', 'bold');
			colr.style.width = '10%';

			row.appendChild(coll);
			row.appendChild(colm);
			row.appendChild(colr);
			el.appendChild(row);
		}
	},
	clear_jobs: function() {
		const el = document.getElementById(this.ids.jobs);
		while (el.firstChild) {
			el.removeChild(el.firstChild);
		}
	},
	set_callback: function(cb) {
		this.callback = cb;
	},
	get_jobids_by_type: function(type) {
		let jobs = [];
		switch (type) {
			case 'all': {
				jobs = this.jobs;
				break;
			}
			case 'waiting': {
				jobs = this.jobs.filter((job) => JobStatus.is_waiting(job.jobstatus));
				break;
			}
			case 'running': {
				jobs = this.jobs.filter((job) => JobStatus.is_running(job.jobstatus));
				break;
			}
		}
		return jobs;
	},
	cancel_job: function(job) {
		const cb = <%=$this->CancelJob->ActiveControl->Javascript%>;
		cb.setCallbackParameter(job.jobid);
		cb.dispatch();
	},
	cancel_job_cb: function(jobid, stat) {
		const self = oBulkCancelJobsModal;
		self.mark_job(jobid, stat);
		if (self.jobs_cancel.length > 0) {
			self.cancel_job(self.jobs_cancel.shift());
		} else {
			self.run_callback();
			self.show_loader(false);
			self.close_window();
		}
	},
	mark_job: function(jobid, stat) {
		const el = document.getElementById(this.ids.jobid_prefix + jobid);
		if (stat) {
			el.classList.remove('w3-text-red');
			el.classList.add('w3-text-green');
			el.textContent =' <%[ cancelling ]%>';
		} else {
			el.classList.remove('w3-text-green');
			el.classList.add('w3-text-red');
			el.textContent =' <%[ error ]%>';
		}
	},
	cancel_jobs: function() {
		this.show_loader(true);
		const jtc = document.getElementById(this.ids.jobs_to_cancel);
		const type = jtc.value;
		this.jobs_cancel = this.get_jobids_by_type(type);
		if (this.jobs_cancel.length > 0) {
			this.cancel_job(this.jobs_cancel.shift());
		} else {
			this.run_callback();
			this.show_loader(false);
			this.close_window();
		}
	},
	run_callback: function() {
		if (typeof(this.callback) != 'function') {
			return;
		}
		return this.callback();
	}
};
</script>
