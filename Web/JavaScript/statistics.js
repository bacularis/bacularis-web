var Statistics = {
	jobs: null,
	clients: null,
	pools: null,
	jobtotals: null,
	dbsize: null,
	clients_occupancy: {},
	pools_occupancy: {},
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
		var clients_occupancy = {};
		var pools_occupancy = {};
		var jobs_occupancy = {};
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
			if (typeof(clients_occupancy[this.jobs[i].clientid]) === 'undefined') {
				clients_occupancy[this.jobs[i].clientid] = 1;
			} else {
				clients_occupancy[this.jobs[i].clientid] += 1;
			}

			if (typeof(pools_occupancy[this.jobs[i].poolid]) === 'undefined') {
				pools_occupancy[this.jobs[i].poolid] = 1;
			} else {
				pools_occupancy[this.jobs[i].poolid] += 1;
			}

			if (typeof(jobs_occupancy[this.jobs[i].name]) === 'undefined') {
				jobs_occupancy[this.jobs[i].name] = 1;
			} else {
				jobs_occupancy[this.jobs[i].name] += 1;
			}
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
		var clients_ids = Object.keys(clients_occupancy);
		for (var i = 0; i < clients_ids.length; i++) {
			for (var j = 0; j < this.clients.length; j++) {
				if (clients_ids[i] == this.clients[j].clientid) {
					this.clients_occupancy[this.clients[j].name] = clients_occupancy[clients_ids[i]];
				}
			}
		}

		var pools_ids = Object.keys(pools_occupancy);
		for (var i = 0; i < pools_ids.length; i++) {
			for (var j = 0; j < this.pools.length; j++) {
				if (pools_ids[i] == this.pools[j].poolid) {
					this.pools_occupancy[this.pools[j].name] = pools_occupancy[pools_ids[i]];
				}
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

		this.jobs_occupancy = jobs_occupancy;
		this.jobs_summary = jobs_summary;
		this.jobs_total_bytes_files = [
			job_total_bytes,
			job_total_files
		];
	}
};
