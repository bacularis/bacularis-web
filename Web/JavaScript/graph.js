var JobClass = jQuery.klass({
	job: null,
	job_size: 0,
	start_stamp: 0,
	end_stamp: 0,
	start_point: [],
	end_point: [],

	initialize: function(job, type) {
		this.set_job(job, type);
	},
	set_job: function(job, type) {
		if (typeof(job) == "object") {
			this.job = job;
			this.set_start_stamp();
			this.set_end_stamp();
			this.set_job_by_type(type);
			this.set_start_point();
			this.set_end_point();
		} else {
			alert('Job is not object');
		}
	},
	set_job_by_type: function(type) {
		switch (type) {
			case 'job_size':
			case 'job_size_per_hour':
			case 'job_size_per_day':
			case 'avg_job_size_per_hour':
			case 'avg_job_size_per_day':
				this.set_job_size();
				break;
			case 'job_files':
			case 'job_files_per_hour':
			case 'job_files_per_day':
			case 'avg_job_files_per_hour':
			case 'avg_job_files_per_day':
				this.set_job_files();
				break;
			case 'job_status_per_day':
				this.set_job_status();
				break;
			case 'job_count_per_hour':
			case 'job_count_per_day':
				// nothing to do
				break;
			case 'job_duration':
				this.set_job_duration();
				break;
			case 'avg_job_speed':
				this.set_avg_job_speed();
				break;
		}
	},
	set_start_point: function() {
		var xaxis = this.start_stamp;
		var yaxis = this.job_val;
		this.start_point = [xaxis, yaxis];
	},
	set_end_point: function() {
		var xaxis = this.end_stamp;
		var yaxis = this.job_val;
		this.end_point = [xaxis, yaxis];
	},
	set_job_size: function() {
		this.job_val = this.job.jobbytes;
	},
	set_job_status: function() {
		this.job_val = this.job.jobstatus;
	},
	set_job_files: function() {
		this.job_val = this.job.jobfiles;
	},
	set_job_duration: function() {
		this.job_val = this.end_stamp - this.start_stamp;
	},
	set_avg_job_speed: function() {
		var t = (this.end_stamp - this.start_stamp) / 1000;
		if (t > 0) {
			this.job_val = this.job.jobbytes / t;
		} else {
			this.job_val = 0;
		}
	},
	set_start_stamp: function() {
		/**
		 * NOTE: Start time can be null if job finishes with error before
		 * writing starttime to the Catalog. Noticed for example with staring
		 * migration job in which no SelectionType defined.
		 */
		if (this.job.starttime) {
			this.start_stamp = this.job.starttime_epoch * 1000;
		}
	},
	set_end_stamp: function() {
		if (this.job.endtime) {
			this.end_stamp =  this.job.endtime_epoch * 1000;
		}
	}
});

var GraphClass = jQuery.klass({
	jobs: [],
	jobs_all: [],
	series: [],
	graph_obj: null,
	txt: {},
	filter_include: {
		type: 'B'
	},
	filter_exclude: {
		start_stamp: 0,
		end_stamp: 0
	},
	filter_all_mark: '@',
	time_range_custom_val: 'custom',
	graph_options: {
		xaxis: {},
		yaxis: {}
	},
	graph_options_orig:  {
		legend: {
			show: true,
			noColumns: 9,
			labelBoxHeight: 10,
			fontColor: '#000000'
		},
		bars: {
			show: true,
			fill: true,
			horizontal : false,
			shadowSize : 0
		},
		xaxis: {
			mode : 'time',
			timeMode: 'UTC',
			labelsAngle : 45,
			autoscale: true,
			showLabels: true
		},
		yaxis: {
			min: 0
		},
		selection: {
			mode : 'x'
		},
		grid: {
			outlineWidth: 0
		},
		HtmlText: false
	},
	default_graph_color: '#63c422',
	ids : {
		graph_type: 'graph_type',
		job_filter: 'graph_jobs',
		graph_container: 'graphs_container',
		legend_container: 'legend_container',
		time_range: 'time_range',
		job_level: 'job_level'
	},
	initialize: function(prop) {
		this.txt = prop.txt;
		this.ids.date_from = prop.date_from;
		this.ids.date_to = prop.date_to;
		this.ids.client_filter = prop.client_filter;
		this.set_jobs(prop.jobs);
		this.set_time_range();
		this.set_job_list();
		this.update();
		this.set_events();
	},
	update: function() {
		this.apply_jobs_filter();
		this.prepare_series();
		this.draw_graph();
	},
	set_events: function() {
		// set graph type change combobox selection event
		$('#' + this.ids.graph_type).on('change', function(e) {
			var jobs = [];
			for (var i = 0; i < this.jobs_all.length; i++) {
				jobs.push(this.jobs_all[i].job);
			}
			this.set_jobs(jobs);
			this.update();
		}.bind(this));

		$('#' + this.ids.job_filter).on('change', function(e) {
			if (e.target.value == this.filter_all_mark) {
				delete this.filter_include['name'];
			} else {
				this.filter_include['name'] = e.target.value;
			}
			this.update();
		}.bind(this));

		$('#' + this.ids.job_level).on('change', function(e) {
			if (e.target.value == this.filter_all_mark) {
				delete this.filter_include['level'];
			} else {
				this.filter_include['level'] = e.target.value;
			}
			this.update();
		}.bind(this));

		// set time range change combobox selection event
		$('#' + this.ids.time_range).on('change', function(e) {
			this.set_time_range();
			this.show_custom_range(false);
			this.update();
		}.bind(this));

		// set 'date from' change combobox selection event
		$('#' + this.ids.date_from).on('change', function(e) {
			var from_stamp = iso_date_to_timestamp(e.target.value);
			this.set_xaxis_min(from_stamp);
			this.show_custom_range(true);
			this.update();
		}.bind(this));

		// set 'date to' change combobox selection event
		$('#' + this.ids.date_to).on('change', function(e) {
			var to_stamp = iso_date_to_timestamp(e.target.value);
			this.set_xaxis_max(to_stamp);
			this.show_custom_range(true);
			this.update();
		}.bind(this));

		// set client filter change combobox selection event
		$('#' + this.ids.client_filter).on('change', function(e) {
			if (e.target.value == this.filter_all_mark) {
				delete this.filter_include['clientid'];
			} else {
				this.filter_include['clientid'] = parseInt(e.target.value, 10);
			}
			this.update();
		}.bind(this));

		var graph_container = document.getElementById(this.ids.graph_container);
		var select_callback = function(area) {
			var graph_options = $.extend({}, this.graph_options);
			var options = $.extend(true, graph_options, {
				xaxis : {
					min : area.x1,
					max : area.x2,
					mode : 'time',
					timeMode: 'UTC',
					labelsAngle : 45,
					autoscale: true
					},
				yaxis : {
					min : area.y1,
					max : area.y2,
					autoscale: true
				}
			});
			if (!this.is_custom_range()) {
				this.set_time_range();
			}
			this.draw_graph(options);
		}.bind(this);

		// set Flotr-specific select area event
		Flotr.EventAdapter.observe(graph_container, 'flotr:select', select_callback);

		// set Flotr-specific click area event (zoom reset)
		Flotr.EventAdapter.observe(graph_container, 'flotr:click', function () {
			this.update();
		}.bind(this));
	},
	set_jobs: function(jobs, filter) {
		if (!filter) {
			this.jobs = this.jobs_all = this.prepare_job_objs(jobs);
		} else {
			this.jobs = jobs;
		}
	},
	prepare_job_objs: function(jobs) {
		var job;
		var job_objs = [];
		var graph_type = document.getElementById(this.ids.graph_type).value;
		for (var i = 0; i < jobs.length; i++) {
			if (jobs[i].jobstatus == 'R' || jobs[i].jobstatus == 'C' || jobs[i].endtime === null) {
				continue;
			}
			job = new JobClass(jobs[i], graph_type);
			job_objs.push(job);
		}
		return job_objs;
	},
	set_job_list: function() {
		const job_list = {};
		for (var i = 0; i < this.jobs_all.length; i++) {
			job_list[this.jobs_all[i].job.name] = 1;
		}
		const jobs = Object.keys(job_list);
		jobs.sort(function (a, b) {
			return a.toLowerCase().localeCompare(b.toLowerCase());
		});
		var job_filter = document.getElementById(this.ids.job_filter);
		for (const job of jobs) {
			var opt = document.createElement('OPTION');
			var label = document.createTextNode(job);
			opt.value = job;
			opt.appendChild(label);
			job_filter.appendChild(opt);
		}
	},
	get_time_range: function() {
		var time_range = document.getElementById(this.ids.time_range).value;
		return parseInt(time_range, 10) * 1000;
	},
	show_custom_range: function(show) {
		var time_range = document.getElementById(this.ids.time_range);
		if (show) {
			if (!this.is_custom_range()) {
				var option = document.createElement('OPTION');
				var label = document.createTextNode(this.txt.filters.custom_time_range);
				option.value = this.time_range_custom_val;
				option.appendChild(label);
				time_range.appendChild(option);
				time_range.value = this.time_range_custom_val;
			}
		} else {
			for (var i = 0; i < time_range.options.length; i++) {
				if (time_range.options[i].value === this.time_range_custom_val) {
					time_range.removeChild(time_range.options[i]);
					break;
				}
			}
		}
	},
	is_custom_range: function() {
		var is_custom = false;
		var time_range = document.getElementById(this.ids.time_range);

		for (var i = 0; i < time_range.options.length; i++) {
			if (time_range.options[i].value === this.time_range_custom_val) {
				is_custom = true;
				break;
			}
		}
		return is_custom;
	},
	set_time_range: function() {
		var to_stamp = Math.round(new Date().getTime());
		this.set_xaxis_max(to_stamp, true);
		var from_stamp = (Math.round(new Date().getTime()) - this.get_time_range());
		this.set_xaxis_min(from_stamp, true);
	},
	set_xaxis_min: function(value, set_range) {
		if (this.graph_options_orig.xaxis.max && value > this.graph_options_orig.xaxis.max) {
			alert('Wrong time range.');
			return;
		}

		if (value == this.graph_options_orig.xaxis.max) {
			value -= 86400000;
		}

		this.graph_options_orig.xaxis.min = value;

		if (set_range) {
			var iso_date = timestamp_to_iso_date(value);
			document.getElementById(this.ids.date_from).value = iso_date;
		}
	},
	set_xaxis_max: function(value, set_range) {
		if (value < this.graph_options_orig.xaxis.min) {
			alert('Wrong time range.');
			return;
		}

		if (value == this.graph_options_orig.xaxis.min) {
			value += 86400000;
		}

		this.graph_options_orig.xaxis.max = value;

		if (set_range) {
			var iso_date = timestamp_to_iso_date(value);
			document.getElementById(this.ids.date_to).value = iso_date;
		}
	},
	apply_jobs_filter: function() {
		var filtred_jobs = [];
		var to_add;
		for (var i = 0; i < this.jobs_all.length; i++) {
			to_add = true;
			for (var key in this.filter_include) {
				if (this.jobs_all[i].hasOwnProperty(key) && this.jobs_all[i][key] != this.filter_include[key]) {
					to_add = false;
					break;
				}
				if (this.jobs_all[i].job.hasOwnProperty(key) && this.jobs_all[i].job[key] != this.filter_include[key]) {
					to_add = false;
					break;
				}
			}
			if (to_add === true) {
				filtred_jobs.push(this.jobs_all[i]);
			}
		}

		var filtred_jobs_copy = filtred_jobs.slice();
		for (var i = 0; i < filtred_jobs.length; i++) {
			for (var key in this.filter_exclude) {
				if (filtred_jobs[i].hasOwnProperty(key) && filtred_jobs[i][key] != this.filter_exclude[key]) {
					continue;
				}
				if (filtred_jobs[i].job.hasOwnProperty(key) && filtred_jobs[i].job[key] != this.filter_exclude[key]) {
					continue;
				}
				delete filtred_jobs_copy[i];
				break;
			}
		}
		this.set_jobs(filtred_jobs_copy, true);
	},
	prepare_series: function() {
		var graph_type = document.getElementById(this.ids.graph_type).value;
		switch (graph_type) {
			case 'job_size':
				this.prepare_series_job_size();
				break;
			case 'job_size_per_hour':
				this.prepare_series_job_size_per_hour();
				break;
			case 'job_size_per_day':
				this.prepare_series_job_size_per_day();
				break;
			case 'avg_job_size_per_hour':
				this.prepare_series_avg_job_size_per_hour();
				break;
			case 'avg_job_size_per_day':
				this.prepare_series_avg_job_size_per_day();
				break;
			case 'job_files':
				this.prepare_series_job_files();
				break;
			case 'job_files_per_hour':
				this.prepare_series_job_files_per_hour();
				break;
			case 'job_files_per_day':
				this.prepare_series_job_files_per_day();
				break;
			case 'avg_job_files_per_hour':
				this.prepare_series_avg_job_files_per_hour();
				break;
			case 'avg_job_files_per_day':
				this.prepare_series_avg_job_files_per_day();
				break;
			case 'job_count_per_hour':
				this.prepare_series_job_count_per_hour();
				break;
			case 'job_count_per_day':
				this.prepare_series_job_count_per_day();
				break;
			case 'job_duration':
				this.prepare_series_job_duration();
				break;
			case 'avg_job_speed':
				this.prepare_series_avg_job_speed();
				break;
			case 'job_status_per_day':
				this.prepare_series_job_status_per_day();
				break;
		}
	},
	prepare_series_job_size: function() {
		var graph_options = $.extend(true, {}, this.graph_options_orig);
		this.graph_options = $.extend(true, graph_options, {
			fontColor: (ThemeMode.is_dark() ? 'white': 'black'),
			title: this.txt.job_size.graph_title,
			xaxis: {
				title: this.txt.job_size.xaxis_title,
				color: (ThemeMode.is_dark() ? 'white': 'black')
			},
			yaxis: {
				title: this.txt.job_size.yaxis_title,
				tickFormatter: function(val, axis_opts) {
					return Units.get_formatted_size(val);
				},
				color: (ThemeMode.is_dark() ? 'white': 'black')
			},
			lines: {
				show: true,
				lineWidth: 0,
				fill: true,
				steps: true
			},
			grid: {
				color: (ThemeMode.is_dark() ? 'white': 'black')
			}
		});

		this.series = [];
		var series_uniq = {};
		for (var i = 0; i < this.jobs.length; i++) {
			if(this.jobs[i].start_stamp < this.graph_options.xaxis.min || this.jobs[i].end_stamp > this.graph_options.xaxis.max) {
				continue;
			}
			if (series_uniq.hasOwnProperty(this.jobs[i].job.name) == false) {
				series_uniq[this.jobs[i].job.name] = [];
			}
			series_uniq[this.jobs[i].job.name].push(this.jobs[i].start_point, this.jobs[i].end_point, [null, null]);

		}
		var serie;
		for (var key in series_uniq) {
			serie = [];
			for (var i = 0; i < series_uniq[key].length; i++) {
				serie.push(series_uniq[key][i]);
			}
			this.series.push({data: serie, label: key});
		}
	},
	prepare_series_job_size_per_hour: function() {
		var graph_options = $.extend(true, {}, this.graph_options_orig);
		this.graph_options = $.extend(true, graph_options, {
			title: this.txt.job_size_per_hour.graph_title,
			legend: {
				show: false
			},
			xaxis: {
				title: this.txt.job_size_per_hour.xaxis_title,
				mode: 'normal',
				tickFormatter: function(val, axis_opts) {
					val = parseInt(val, 10);
					var d = new Date(val);
					return d.toLocaleDateString() + ' ' + d.getHours() + ':00';
				}
			},
			yaxis: {
				title: this.txt.job_size_per_hour.yaxis_title,
				tickFormatter: function(val, axis_opts) {
					return Units.get_formatted_size(val);
				}
			},
			bars: {
				barWidth: 3600000,
			},
			mouse : {
				track : true
			}
		});

		this.series = [];
		var series_uniq = {};
		var d, date;
		for (var i = 0; i < this.jobs.length; i++) {
			if(this.jobs[i].start_stamp < this.graph_options.xaxis.min || this.jobs[i].end_stamp > this.graph_options.xaxis.max) {
				continue;
			}
			d = new Date(this.jobs[i].start_stamp);
			date = (new Date(d.getUTCFullYear(), d.getUTCMonth(), d.getUTCDate(), d.getUTCHours())).getTime();
			if (series_uniq.hasOwnProperty(date) == false) {
				series_uniq[date] = 0;
			}
			series_uniq[date] += this.jobs[i].job_val;

		}
		for (var key in series_uniq) {
			key = parseInt(key, 10);
			this.series.push({data: [[key, series_uniq[key]]], label: key, color: this.default_graph_color});
		}
	},
	prepare_series_job_size_per_day: function() {
		var graph_options = $.extend(true, {}, this.graph_options_orig);
		this.graph_options = $.extend(true, graph_options, {
			title: this.txt.job_size_per_day.graph_title,
			legend: {
				show: false
			},
			xaxis: {
				title: this.txt.job_size_per_day.xaxis_title,
				mode: 'normal',
				tickFormatter: function(val, axis_opts) {
					val = parseInt(val, 10);
					return (new Date(val)).toLocaleDateString();
				}
			},
			yaxis: {
				title: this.txt.job_size_per_day.yaxis_title,
				tickFormatter: function(val, axis_opts) {
					return Units.get_formatted_size(val);
				}
			},
			bars: {
				barWidth: 86400000,
			},
			mouse : {
				track : true
			}
		});

		this.series = [];
		var series_uniq = {};
		var d, date;
		for (var i = 0; i < this.jobs.length; i++) {
			if(this.jobs[i].start_stamp < this.graph_options.xaxis.min || this.jobs[i].end_stamp > this.graph_options.xaxis.max) {
				continue;
			}
			d = new Date(this.jobs[i].start_stamp);
			date = (new Date(d.getUTCFullYear(), d.getUTCMonth(), d.getUTCDate())).getTime();
			if (series_uniq.hasOwnProperty(date) == false) {
				series_uniq[date] = 0;
			}
			series_uniq[date] += this.jobs[i].job_val;

		}
		for (var key in series_uniq) {
			key = parseInt(key, 10);
			this.series.push({data: [[key, series_uniq[key]]], label: key, color: this.default_graph_color});
		}
	},
	prepare_series_avg_job_size_per_hour: function() {
		var graph_options = $.extend(true, {}, this.graph_options_orig);
		this.graph_options = $.extend(true, graph_options, {
			title: this.txt.avg_job_size_per_hour.graph_title,
			legend: {
				show: false
			},
			xaxis: {
				title: this.txt.avg_job_size_per_hour.xaxis_title,
				mode: 'normal',
				tickFormatter: function(val, axis_opts) {
					val = parseInt(val, 10);
					var d = new Date(val);
					return d.toLocaleDateString() + ' ' + d.getHours() + ':00';
				}
			},
			yaxis: {
				title: this.txt.avg_job_size_per_hour.yaxis_title,
				tickFormatter: function(val, axis_opts) {
					return Units.get_formatted_size(val);
				}
			},
			bars: {
				barWidth: 3600000,
			},
			mouse : {
				track : true
			}
		});

		this.series = [];
		var series_uniq = {};
		var series_count = {};
		var d, date;
		for (var i = 0; i < this.jobs.length; i++) {
			if(this.jobs[i].start_stamp < this.graph_options.xaxis.min || this.jobs[i].end_stamp > this.graph_options.xaxis.max) {
				continue;
			}
			d = new Date(this.jobs[i].start_stamp);
			date = (new Date(d.getUTCFullYear(), d.getUTCMonth(), d.getUTCDate(), d.getUTCHours())).getTime();
			if (series_uniq.hasOwnProperty(date) == false) {
				series_uniq[date] = 0;
				series_count[date] = 0;
			}
			series_uniq[date] += this.jobs[i].job_val;
			series_count[date]++;

		}
		var val;
		for (var key in series_uniq) {
			val = series_uniq[key] / series_count[key];
			key = parseInt(key, 10);
			this.series.push({data: [[key, val]], label: key, color: this.default_graph_color});
		}
	},
	prepare_series_avg_job_size_per_day: function() {
		var graph_options = $.extend(true, {}, this.graph_options_orig);
		this.graph_options = $.extend(true, graph_options, {
			title: this.txt.avg_job_size_per_day.graph_title,
			legend: {
				show: false
			},
			xaxis: {
				title: this.txt.avg_job_size_per_day.xaxis_title,
				mode: 'normal',
				tickFormatter: function(val, axis_opts) {
					val = parseInt(val, 10);
					return (new Date(val)).toLocaleDateString();
				}
			},
			yaxis: {
				title: this.txt.avg_job_size_per_day.yaxis_title,
				tickFormatter: function(val, axis_opts) {
					return Units.get_formatted_size(val);
				}
			},
			bars: {
				barWidth: 86400000,
			},
			mouse : {
				track : true
			}
		});

		this.series = [];
		var series_uniq = {};
		var series_count = {};
		var d, date;
		for (var i = 0; i < this.jobs.length; i++) {
			if(this.jobs[i].start_stamp < this.graph_options.xaxis.min || this.jobs[i].end_stamp > this.graph_options.xaxis.max) {
				continue;
			}
			d = new Date(this.jobs[i].start_stamp);
			date = (new Date(d.getUTCFullYear(), d.getUTCMonth(), d.getUTCDate())).getTime();
			if (series_uniq.hasOwnProperty(date) == false) {
				series_uniq[date] = 0;
				series_count[date] = 0;
			}
			series_uniq[date] += this.jobs[i].job_val;
			series_count[date]++;

		}
		var val;
		for (var key in series_uniq) {
			val = series_uniq[key] / series_count[key];
			key = parseInt(key, 10);
			this.series.push({data: [[key, val]], label: key, color: this.default_graph_color});
		}
	},
	prepare_series_job_files: function() {
		var graph_options = $.extend(true, {}, this.graph_options_orig);
		this.graph_options = $.extend(true, graph_options, {
			title: this.txt.job_files.graph_title,
			xaxis: {
				title: this.txt.job_files.xaxis_title
			},
			yaxis: {
				title: this.txt.job_files.yaxis_title,
				tickFormatter: function(val, axis_opts) {
					return parseInt(val, 10);
				}
			},
			lines: {
				show: true,
				lineWidth: 0,
				fill: true,
				steps: true
			}
		});

		this.series = [];
		var series_uniq = {};
		for (var i = 0; i < this.jobs.length; i++) {
			if(this.jobs[i].start_stamp < this.graph_options.xaxis.min || this.jobs[i].end_stamp > this.graph_options.xaxis.max) {
				continue;
			}
			if (series_uniq.hasOwnProperty(this.jobs[i].job.name) == false) {
				series_uniq[this.jobs[i].job.name] = [];
			}
			series_uniq[this.jobs[i].job.name].push(this.jobs[i].start_point, this.jobs[i].end_point, [null, null]);

		}
		var serie;
		for (var key in series_uniq) {
			serie = [];
			for (var i = 0; i < series_uniq[key].length; i++) {
				serie.push(series_uniq[key][i]);
			}
			this.series.push({data: serie, label: key});
		}
	},
	prepare_series_job_files_per_hour: function() {
		var graph_options = $.extend(true, {}, this.graph_options_orig);
		this.graph_options = $.extend(true, graph_options, {
			title: this.txt.job_files_per_hour.graph_title,
			legend: {
				show: false
			},
			xaxis: {
				title: this.txt.job_files_per_hour.xaxis_title,
				mode: 'normal',
				tickFormatter: function(val, axis_opts) {
					val = parseInt(val, 10);
					var d = new Date(val);
					return d.toLocaleDateString() + ' ' + d.getHours() + ':00';
				}
			},
			yaxis: {
				title: this.txt.job_files_per_hour.yaxis_title,
				tickFormatter: function(val, axis_opts) {
					return parseInt(val, 10);
				}
			},
			bars: {
				barWidth: 3600000,
			},
			mouse : {
				track : true
			}
		});

		this.series = [];
		var series_uniq = {};
		var d, date;
		for (var i = 0; i < this.jobs.length; i++) {
			if(this.jobs[i].start_stamp < this.graph_options.xaxis.min || this.jobs[i].end_stamp > this.graph_options.xaxis.max) {
				continue;
			}
			d = new Date(this.jobs[i].start_stamp);
			date = (new Date(d.getUTCFullYear(), d.getUTCMonth(), d.getUTCDate(), d.getUTCHours())).getTime();
			if (series_uniq.hasOwnProperty(date) == false) {
				series_uniq[date] = 0;
			}
			series_uniq[date] += this.jobs[i].job_val;

		}
		for (var key in series_uniq) {
			key = parseInt(key, 10);
			this.series.push({data: [[key, series_uniq[key]]], label: key, color: this.default_graph_color});
		}
	},
	prepare_series_job_files_per_day: function() {
		var graph_options = $.extend(true, {}, this.graph_options_orig);
		this.graph_options = $.extend(true, graph_options, {
			title: this.txt.job_files_per_day.graph_title,
			legend: {
				show: false
			},
			xaxis: {
				title: this.txt.job_files_per_day.xaxis_title,
				mode: 'normal',
				tickFormatter: function(val, axis_opts) {
					val = parseInt(val, 10);
					return (new Date(val)).toLocaleDateString();
				}
			},
			yaxis: {
				title: this.txt.job_files_per_day.yaxis_title,
				tickFormatter: function(val, axis_opts) {
					return parseInt(val, 10);
				}
			},
			bars: {
				barWidth: 86400000,
			},
			mouse : {
				track : true
			}
		});

		this.series = [];
		var series_uniq = {};
		var d, date;
		for (var i = 0; i < this.jobs.length; i++) {
			if(this.jobs[i].start_stamp < this.graph_options.xaxis.min || this.jobs[i].end_stamp > this.graph_options.xaxis.max) {
				continue;
			}
			d = new Date(this.jobs[i].start_stamp);
			date = (new Date(d.getUTCFullYear(), d.getUTCMonth(), d.getUTCDate())).getTime();
			if (series_uniq.hasOwnProperty(date) == false) {
				series_uniq[date] = 0;
			}
			series_uniq[date] += this.jobs[i].job_val;

		}
		for (var key in series_uniq) {
			key = parseInt(key, 10);
			this.series.push({data: [[key, series_uniq[key]]], label: key, color: this.default_graph_color});
		}
	},
	prepare_series_avg_job_files_per_hour: function() {
		var graph_options = $.extend(true, {}, this.graph_options_orig);
		this.graph_options = $.extend(true, graph_options, {
			title: this.txt.avg_job_files_per_hour.graph_title,
			legend: {
				show: false
			},
			xaxis: {
				title: this.txt.avg_job_files_per_hour.xaxis_title,
				mode: 'normal',
				tickFormatter: function(val, axis_opts) {
					val = parseInt(val, 10);
					var d = new Date(val);
					return d.toLocaleDateString() + ' ' + d.getHours() + ':00';
				}
			},
			yaxis: {
				title: this.txt.avg_job_files_per_hour.yaxis_title,
				tickFormatter: function(val, axis_opts) {
					return parseInt(val, 10);
				}
			},
			bars: {
				barWidth: 3600000,
			},
			mouse : {
				track : true
			}
		});

		this.series = [];
		var series_uniq = {};
		var series_count = {};
		var d, date;
		for (var i = 0; i < this.jobs.length; i++) {
			if(this.jobs[i].start_stamp < this.graph_options.xaxis.min || this.jobs[i].end_stamp > this.graph_options.xaxis.max) {
				continue;
			}
			d = new Date(this.jobs[i].start_stamp);
			date = (new Date(d.getUTCFullYear(), d.getUTCMonth(), d.getUTCDate(), d.getUTCHours())).getTime();
			if (series_uniq.hasOwnProperty(date) == false) {
				series_uniq[date] = 0;
				series_count[date] = 0;
			}
			series_uniq[date] += this.jobs[i].job_val;
			series_count[date]++;

		}
		var val;
		for (var key in series_uniq) {
			val = series_uniq[key] / series_count[key];
			key = parseInt(key, 10);
			this.series.push({data: [[key, val]], label: key, color: this.default_graph_color});
		}
	},
	prepare_series_avg_job_files_per_day: function() {
		var graph_options = $.extend(true, {}, this.graph_options_orig);
		this.graph_options = $.extend(true, graph_options, {
			title: this.txt.avg_job_files_per_day.graph_title,
			legend: {
				show: false
			},
			xaxis: {
				title: this.txt.avg_job_files_per_day.xaxis_title,
				mode: 'normal',
				tickFormatter: function(val, axis_opts) {
					val = parseInt(val, 10);
					return (new Date(val)).toLocaleDateString();
				}
			},
			yaxis: {
				title: this.txt.avg_job_files_per_day.yaxis_title,
				tickFormatter: function(val, axis_opts) {
					return parseInt(val, 10);
				}
			},
			bars: {
				barWidth: 86400000,
			},
			mouse : {
				track : true
			}
		});

		this.series = [];
		var series_uniq = {};
		var series_count = {};
		var d, date;
		for (var i = 0; i < this.jobs.length; i++) {
			if(this.jobs[i].start_stamp < this.graph_options.xaxis.min || this.jobs[i].end_stamp > this.graph_options.xaxis.max) {
				continue;
			}
			d = new Date(this.jobs[i].start_stamp);
			date = (new Date(d.getUTCFullYear(), d.getUTCMonth(), d.getUTCDate())).getTime();
			if (series_uniq.hasOwnProperty(date) == false) {
				series_uniq[date] = 0;
				series_count[date] = 0;
			}
			series_uniq[date] += this.jobs[i].job_val;
			series_count[date]++;

		}
		var val;
		for (var key in series_uniq) {
			val = series_uniq[key] / series_count[key];
			key = parseInt(key, 10);
			this.series.push({data: [[key, val]], label: key, color: this.default_graph_color});
		}
	},
	prepare_series_job_count_per_hour: function() {
		var graph_options = $.extend(true, {}, this.graph_options_orig);
		this.graph_options = $.extend(true, graph_options, {
			title: this.txt.job_count_per_hour.graph_title,
			legend: {
				show: false
			},
			xaxis: {
				title: this.txt.job_count_per_hour.xaxis_title,
				mode: 'normal',
				tickFormatter: function(val, axis_opts) {
					val = parseInt(val, 10);
					var d = new Date(val);
					return d.toLocaleDateString() + ' ' + d.getHours() + ':00';
				}
			},
			yaxis: {
				title: this.txt.job_count_per_hour.yaxis_title,
				tickFormatter: function(val, axis_opts) {
					return parseInt(val, 10);
				}
			},
			bars: {
				barWidth: 3600000,
			},
			mouse : {
				track : true
			}
		});

		this.series = [];
		var series_uniq = {};
		var d, date;
		for (var i = 0; i < this.jobs.length; i++) {
			if(this.jobs[i].start_stamp < this.graph_options.xaxis.min || this.jobs[i].end_stamp > this.graph_options.xaxis.max) {
				continue;
			}
			d = new Date(this.jobs[i].start_stamp);
			date = (new Date(d.getUTCFullYear(), d.getUTCMonth(), d.getUTCDate(), d.getUTCHours())).getTime();
			if (series_uniq.hasOwnProperty(date) == false) {
				series_uniq[date] = 0;
			}
			series_uniq[date]++;

		}
		for (var key in series_uniq) {
			key = parseInt(key, 10);
			this.series.push({data: [[key, series_uniq[key]]], label: key, color: this.default_graph_color});
		}
	},
	prepare_series_job_count_per_day: function() {
		var graph_options = $.extend(true, {}, this.graph_options_orig);
		this.graph_options = $.extend(true, graph_options, {
			title: this.txt.job_count_per_day.graph_title,
			legend: {
				show: false
			},
			xaxis: {
				title: this.txt.job_count_per_day.xaxis_title,
				mode: 'normal',
				tickFormatter: function(val, axis_opts) {
					val = parseInt(val, 10);
					return (new Date(val)).toLocaleDateString();
				}
			},
			yaxis: {
				title: this.txt.job_count_per_day.yaxis_title,
				tickFormatter: function(val, axis_opts) {
					return parseInt(val, 10);
				}
			},
			bars: {
				barWidth: 86400000
			},
			mouse : {
				track : true
			}
		});

		this.series = [];
		var series_uniq = {};
		var d, date;
		for (var i = 0; i < this.jobs.length; i++) {
			if(this.jobs[i].start_stamp < this.graph_options.xaxis.min || this.jobs[i].end_stamp > this.graph_options.xaxis.max) {
				continue;
			}
			d = new Date(this.jobs[i].start_stamp);
			date = (new Date(d.getUTCFullYear(), d.getUTCMonth(), d.getUTCDate())).getTime();
			if (series_uniq.hasOwnProperty(date) == false) {
				series_uniq[date] = 0;
			}
			series_uniq[date]++;

		}
		for (var key in series_uniq) {
			key = parseInt(key, 10);
			this.series.push({data: [[key, series_uniq[key]]], label: key, color: this.default_graph_color});
		}
	},
	prepare_series_job_duration: function() {
		var graph_options = $.extend(true, {}, this.graph_options_orig);
		this.graph_options = $.extend(true, graph_options, {
			title: this.txt.job_duration.graph_title,
			xaxis: {
				title: this.txt.job_duration.xaxis_title
			},
			yaxis: {
				title: this.txt.job_duration.yaxis_title,
				tickFormatter: function(val, axis_opts) {
					val = parseInt(val, 10) / 1000;
					var value = Units.format_time_period(val, null, true);
					return (value.value.toFixed(1) + ' ' + value.format + (value.value >= 1 ? 's' : ''));
				}
			},
			lines: {
				show: true,
				lineWidth: 0,
				fill: true,
				steps: true
			}
		});

		this.series = [];
		var series_uniq = {};
		for (var i = 0; i < this.jobs.length; i++) {
			if(this.jobs[i].start_stamp < this.graph_options.xaxis.min || this.jobs[i].end_stamp > this.graph_options.xaxis.max) {
				continue;
			}
			if (series_uniq.hasOwnProperty(this.jobs[i].job.name) == false) {
				series_uniq[this.jobs[i].job.name] = [];
			}
			series_uniq[this.jobs[i].job.name].push(this.jobs[i].start_point, this.jobs[i].end_point, [null, null]);

		}
		var serie;
		for (var key in series_uniq) {
			serie = [];
			for (var i = 0; i < series_uniq[key].length; i++) {
				serie.push(series_uniq[key][i]);
			}
			this.series.push({data: serie, label: key});
		}
	},
	prepare_series_avg_job_speed: function() {
		var graph_options = $.extend(true, {}, this.graph_options_orig);
		this.graph_options = $.extend(true, graph_options, {
			title: this.txt.avg_job_speed.graph_title,
			xaxis: {
				title: this.txt.avg_job_speed.xaxis_title
			},
			yaxis: {
				title: this.txt.avg_job_speed.yaxis_title,
				tickFormatter: function(val, axis_opts) {
					val = parseInt(val);
					var s = Units.format_speed(val, null, true, true);
					return (s.value.toFixed(1) + s.format);
				}
			},
			lines: {
				show: true,
				lineWidth: 0,
				fill: true,
				steps: true
			}
		});

		this.series = [];
		var series_uniq = {};
		for (var i = 0; i < this.jobs.length; i++) {
			if(this.jobs[i].start_stamp < this.graph_options.xaxis.min || this.jobs[i].end_stamp > this.graph_options.xaxis.max) {
				continue;
			}
			if (series_uniq.hasOwnProperty(this.jobs[i].job.name) == false) {
				series_uniq[this.jobs[i].job.name] = [];
			}
			series_uniq[this.jobs[i].job.name].push(this.jobs[i].start_point, this.jobs[i].end_point, [null, null]);

		}
		var serie;
		for (var key in series_uniq) {
			serie = [];
			for (var i = 0; i < series_uniq[key].length; i++) {
				serie.push(series_uniq[key][i]);
			}
			this.series.push({data: serie, label: key});
		}
	},
	prepare_series_job_status_per_day: function() {
		var graph_options = $.extend(true, {}, this.graph_options_orig);
		this.graph_options = $.extend(true, graph_options, {
			title: this.txt.job_status_per_day.graph_title,
			colors: ['#63c422', '#FFFF66', '#d70808'],
			xaxis: {
				title: this.txt.job_status_per_day.xaxis_title
			},
			yaxis: {
				title: this.txt.job_status_per_day.yaxis_title,
				tickFormatter: function(val, axis_opts) {
					return parseInt(val, 10);
				}
			},
			bars: {
				barWidth: 86400000,
				stacked: true
			}
		});
		this.series = [];
		var series_uniq = {};
		var ok = [];
		var error = [];
		var warning = []
		var cancel = []
		var date, d;
		for (var i = 0; i < this.jobs.length; i++) {
			if(this.jobs[i].start_stamp < this.graph_options.xaxis.min || this.jobs[i].end_stamp > this.graph_options.xaxis.max) {
				continue;
			}
			d = new Date(this.jobs[i].start_stamp);
			date = (new Date(d.getUTCFullYear(), d.getUTCMonth(), d.getUTCDate())).getTime();
			if (!series_uniq.hasOwnProperty(date)) {
				series_uniq[date] = {ok: 0, error: 0, cancel: 0};
			}
			if (['T', 'D'].indexOf(this.jobs[i].job_val) != -1) {
				series_uniq[date].ok++;
			} else if (['E', 'e', 'f', 'I'].indexOf(this.jobs[i].job_val) != -1) {
				series_uniq[date].error++;
			} else if (this.jobs[i].job_val === 'A') {
				series_uniq[date].cancel++;
			}

		}
		var d1 = [];
		var d2 = [];
		var d3 = [];
		var d;
		for (var date in series_uniq) {
			if (date <= 0) {
				continue;
			}
			d = parseInt(date, 10);
			d1.push([d, series_uniq[date].ok]);
			d2.push([d, series_uniq[date].cancel]);
			d3.push([d, series_uniq[date].error]);
		}
		this.series = [
			{data: d1, label: 'OK'},
			{data: d2, label: 'Cancel'},
			{data: d3, label: 'Error'}
		];
	},
	draw_graph: function(opts) {
		this.graph_options.legend.container = document.getElementById(this.ids.legend_container);

		var options = Flotr._.extend(Flotr._.clone(this.graph_options), opts || {});

		var graph_container = document.getElementById(this.ids.graph_container);
		this.graph_obj = Flotr.draw(
			graph_container,
			this.series,
			options
		);
	},
});

var GraphPieClass = jQuery.klass({
	data: [],
	container: null,
	series: null,
	pie: null,
	graph_options_def: {
		colors: ['#63c422', '#d70808', '#FFFF66', 'orange', '#2980B9'],
		HtmlText: false,
		fontColor: '#000000',
		grid: {
			verticalLines : false,
			horizontalLines : false,
			outlineWidth: 0,
			color: 'black'
		},
		xaxis: { showLabels : false },
		yaxis: { showLabels : false },
		pie: {
			show : true,
			explode : 6,
			labelFormatter: (total, value) => { // default job pie formatter
				return PieGraphBase.pie_label_formatter.call(PieGraphJob, total, value);
			},
			shadowSize: 4,
			sizeRatio: 0.77
		},
		mouse: {
			track : true,
			trackFormatter: (e) => { // default job mouse track formatter
				return PieGraphBase.pie_track_formatter.call(PieGraphJob, e);
			},
			relative: false,
			position : 'sw',
			mouseHandler: (e) => { // this is custom non-Flotr2 method
				return PieGraphBase.pie_mouse_handler.call(PieGraphJob, e);
			}
		},
		legend: {
			noColumns: 3,
			position : 'se',
			backgroundColor : '#D2E8FF',
			margin: 0,
			labelFormatter: (label) => { // default job legend formatter
				return PieGraphBase.pie_legend_formatter.call(PieGraphJob, label);
			}
		}
	},
	initialize: function(prop) {
		const self = this;
		this.data = prop.data;
		this.container = document.getElementById(prop.container_id);
		const opts = prop.hasOwnProperty('graph_options') ? prop.graph_options : {};
		this.graph_options = $.extend(true, this.graph_options_def, opts);
		this.series = this.prepare_series();
		this.draw_graph();
	},
	prepare_series: function() {
		var series = [];
		var label, serie;
		var types = Object.keys(this.data);
		var data_count;
		for (var i = 0; i < types.length; i++) {
			label = types[i];
			data_count = this.data[label].length;
			serie = {
				data: [[0, data_count]],
				label: label + ' (' + data_count.toString() + ')',
				pie: {
					explode: 12
				}
			}
			if (this.graph_options.colors && this.graph_options.colors.hasOwnProperty(label)) {
				serie.color = this.graph_options.colors[label];
			}
			series.push(serie);
		}
		return series;
	},
	draw_graph: function() {
		this.pie = Flotr.draw(this.container, this.series, this.graph_options);
		Flotr.EventAdapter.observe(this.container, 'flotr:click', (e) => {
			if (typeof(this.graph_options.mouse.mouseHandler) == 'function') {
				this.graph_options.mouse.mouseHandler(e);
			}
		});
	},
	destroy: function() {
		Flotr.EventAdapter.stopObserving(this.container, 'flotr:click');
		this.pie.destroy();
	}
});

var GraphLinesClass = jQuery.klass({
	data: [],
	container: null,
	series: null,
	graph: null,
	graph_options_def: {
		HtmlText: false,
		fontColor: '#000000',
		grid : {
			verticalLines : false,
			backgroundColor : null
		},
		selection: {
			mode: 'x'
		},
		legend: {
			noColumns: 2,
			position : 'se',
			margin: 0
		}
	},
	initialize: function(prop) {
		this.data = prop.data;
		this.container = document.getElementById(prop.container_id);
		const opts = prop.hasOwnProperty('graph_options') ? prop.graph_options : {};
		this.graph_options = $.extend(true, this.graph_options_def, opts);
		this.series = this.prepare_series();
		this.draw_graph();
		this.add_events();
	},
	prepare_series: function() {
		const series = [];
		let serie;
		for (let i = 0; i < this.data.length; i++) {
			serie = {data: this.data[i].serie};
			if (this.data[i].opts) {
				serie = jQuery.extend(true, serie, this.data[i].opts);
			}
			series.push(serie);
		}
		return series;
	},
	add_events: function() {
		const select_callback = (area) => {
			const graph_options = $.extend({}, this.graph_options);
			const options = $.extend(true, graph_options, {
				xaxis : {
					min : area.x1,
					max : area.x2
					},
				yaxis : {
					min : area.y1,
					max : area.y2
				}
			});
			this.draw_graph(options);
		};

		// set Flotr-specific select area event
		Flotr.EventAdapter.observe(this.container, 'flotr:select', select_callback);

		// set Flotr-specific click area event (zoom reset)
		Flotr.EventAdapter.observe(this.container, 'flotr:click', () => {
			const opts = {
				xaxis: {
					min: this.graph_options.xaxis.def_min,
					max: this.graph_options.xaxis.def_max
				},
				yaxis: {
					min: null,
					max: null
				}
			}
			this.draw_graph(opts);
		});
	},
	draw_graph: function(opts) {
		const options = $.extend(true, this.graph_options, opts || {});
		this.graph = Flotr.draw(this.container, this.series, options);
	},
	destroy: function() {
		Flotr.EventAdapter.stopObserving(this.container, 'flotr:click');
		this.graph.destroy();
	}
});

var iso_date_to_timestamp = function(iso_date) {
	if (!iso_date) {
		return 0;
	}
	var date_split = iso_date.split(' ');
	var date = date_split[0].split('-');
	if (date_split[1]) {
		var time = date_split[1].split(':');
		var date_obj = new Date(date[0], (date[1] - 1), date[2], time[0], time[1], time[2]);
	} else {
		var date_obj = new Date(date[0], (date[1] - 1), date[2], 0, 0, 0);
	}
	return date_obj.getTime();
}

var timestamp_to_iso_date = function(timestamp) {
	var iso_date;
	var date = new Date(timestamp);

	var year = date.getFullYear();
	var month = (date.getMonth() + 1).toString();
	month = ('0' + month).substr(-2,2);
	var day = date.getDate().toString();
	day = ('0' + day).substr(-2,2);
	var date_values =  [year, month ,day];

	var iso_date = date_values.join('-');
	return iso_date;
}
