var Statistics = {
	jobs: null,
	clients: null,
	pools: null,
	jobtotals: null,
	dbsize: null,
	clients_occupancy: {},
	pools_occupancy: {},
	jobs_summary: [],
	grab_statistics: function(data, jobstates) {
		this.jobs = data.jobs;
		this.clients = data.clients;
		this.pools = data.pools;
		this.jobtotals = data.jobtotals;
		this.dbsize = data.dbsize;
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
		var status_type;
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
			if (jobstates.hasOwnProperty(this.jobs[i].jobstatus)) {
				status_type = jobstates[this.jobs[i].jobstatus].type;
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

		this.jobs_occupancy = jobs_occupancy;
		this.jobs_summary = jobs_summary;
	}
}
