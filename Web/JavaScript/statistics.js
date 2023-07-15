var Statistics = {
	jobs: null,
	clients: null,
	pools: null,
	jobtotals: null,
	dbsize: null,
	jobs_summary: [],
	jobs_total_bytes: [],
	grab_statistics: function(data, opts) {
		this.jobs = data.jobs;
		this.clients = data.clients;
		this.pools = data.pools;
		this.jobtotals = data.jobtotals;
		this.dbsize = data.dbsize;
		this.opts = opts;
		var jobs_count = this.jobs.length;
		var jobs_summary = {
			ok: [],
			error: [],
			warning: [],
			cancel: [],
			running: []
		};
		const job_bytes_files = {};
		let status_type;
		const start_time = new Date(Date.now() - (this.opts.job_age * 1000));
		const start_time_ts = start_time.getTime();
		let job_time_ts, date;
		for (var i = 0; i < jobs_count; i++) {
			job_time_ts = iso_date_to_timestamp(this.jobs[i].starttime);
			if (this.opts.job_age > 0 && job_time_ts < start_time_ts) {
				continue;
			}
			date = parseInt(job_time_ts / 1000, 10);
			date = (parseInt(date / 86400, 10) * 86400).toString(); // use only full days
			if (!job_bytes_files.hasOwnProperty(date)) {
				job_bytes_files[date] = {files: 0, bytes: 0};
			}
			job_bytes_files[date].files += this.jobs[i].jobfiles;
			job_bytes_files[date].bytes += this.jobs[i].jobbytes;

			if (this.opts.job_states.hasOwnProperty(this.jobs[i].jobstatus)) {
				status_type = this.opts.job_states[this.jobs[i].jobstatus].type;
				if (status_type == 'ok' && this.jobs[i].joberrors > 0) {
					status_type = 'warning';
				}
				if (status_type == 'waiting') {
					status_type = 'running';
				}
				jobs_summary[status_type].push(this.jobs[i]);
			}
		}

		const bytes_label = this.opts.hasOwnProperty('txts') && this.opts.txts.hasOwnProperty('jobbytes') ? this.opts.txts.jobbytes : '';
		const files_label = this.opts.hasOwnProperty('txts') && this.opts.txts.hasOwnProperty('jobfiles') ? this.opts.txts.jobfiles : '';
		const job_total_bytes = {serie: [], opts: {label: bytes_label}};
		const job_total_files = {serie: [], opts: {yaxis: 2, label: files_label}};
		let day_val;
		for (const day in job_bytes_files) {
			day_val = parseInt(day, 10);
			job_total_bytes.serie.push(
				[day_val, job_bytes_files[day].bytes]
			);
			job_total_files.serie.push(
				[day_val, job_bytes_files[day].files]
			);
		}

		this.jobs_summary = jobs_summary;
		this.jobs_total_bytes_files = [
			job_total_bytes,
			job_total_files
		];
	}
};
