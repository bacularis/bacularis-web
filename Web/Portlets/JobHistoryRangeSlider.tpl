<div style="width: 900px">
	<div>
		<p>
			<%[ Date From: ]%> <strong id="<%=$this->ClientID%>_job_history_slider_date_from"></strong>, 
			<%[ Date To: ]%> <strong id="<%=$this->ClientID%>_job_history_slider_date_to"></strong>, 
			<%[ Range of days: ]%> <strong id="<%=$this->ClientID%>_job_history_slider_date_days_range"></strong>, 
			<%[ Jobs in range: ]%> <strong id="<%=$this->ClientID%>_job_history_slider_date_jobs_in_range"></strong><br />  
			<%[ Jobs out of range on the right: ]%> <strong id="<%=$this->ClientID%>_job_history_slider_date_jobs_to_keep"></strong>, 
			<%[ Sum of job bytes in range: ]%> <strong id="<%=$this->ClientID%>_job_history_slider_date_bytes_sum"></strong>, 
			<%[ Sum of job files in range: ]%> <strong id="<%=$this->ClientID%>_job_history_slider_date_files_sum"></strong>
		</p>
	</div>
	<div id="<%=$this->ClientID%>_job_history_range_slider">
		<canvas id="<%=$this->ClientID%>_job_history_slider_canvas" width="900" height="50"></canvas>
	</div>
	<div>
		<p>
			<canvas id="<%=$this->ClientID%>_job_history_slider_legend_full" width="40" height="20" style="vertical-align: middle"></canvas> - <%[ Full ]%>, 
			<canvas id="<%=$this->ClientID%>_job_history_slider_legend_inc" width="40" height="20" style="vertical-align: middle; margin-left: 10px;"></canvas> - <%[ Incremental ]%>, 
			<canvas id="<%=$this->ClientID%>_job_history_slider_legend_diff" width="40" height="20" style="vertical-align: middle; margin-left: 10px;"></canvas> - <%[ Differential ]%>
			<button type="button" class="w3-button w3-green w3-small w3-right w3-margin-left" onclick="<%=$this->ClientID%>_job_history_range_slider_obj.zoom_in();"><%[ Zoom in ]%></button>
			<button type="button" class="w3-button w3-green w3-small w3-right" onclick="<%=$this->ClientID%>_job_history_range_slider_obj.reset_zoom();"><%[ Reset zoom ]%></button>
		</p>
	</div>
</div>
<com:System.Web.UI.JuiControls.TJuiSlider Display="None" />
<com:TCallback ID="LoadJobHistory" OnCallback="loadJobHistory" />
<script>
class <%=$this->ClientID%>JobHistoryRangeSlider {
	constructor(opts) {
		this.ids = {
			slider: '<%=$this->ClientID%>_job_history_range_slider',
			canvas: '<%=$this->ClientID%>_job_history_slider_canvas',
			legend_full: '<%=$this->ClientID%>_job_history_slider_legend_full',
			legend_inc: '<%=$this->ClientID%>_job_history_slider_legend_inc',
			legend_diff: '<%=$this->ClientID%>_job_history_slider_legend_diff',
			date_from: '<%=$this->ClientID%>_job_history_slider_date_from',
			date_to: '<%=$this->ClientID%>_job_history_slider_date_to',
			jobs_in_range: '<%=$this->ClientID%>_job_history_slider_date_jobs_in_range',
			jobs_to_keep: '<%=$this->ClientID%>_job_history_slider_date_jobs_to_keep',
			bytes_sum: '<%=$this->ClientID%>_job_history_slider_date_bytes_sum',
			files_sum: '<%=$this->ClientID%>_job_history_slider_date_files_sum',
			days_range: '<%=$this->ClientID%>_job_history_slider_date_days_range'
		};
		this.size = {
			width: 900,
			height: 50
		}
		this.colors = {
			F: '#63c422', // full backup
			I: '#2980B9', // incremental backup
			D: '#D68910', // differential backup
			o: 'red'      // other not expected levels
		};
		this.def_opts = {
			range: true,
			min: 0,
			max: 500,
			step: 1
		};
		this.custom_opts = {
			values: [0, 0]
		};
		this.days = 0;
		this.job_name = '';
		this.all_jobs = [];
		this.jobs = [];
		this.slider = null;
		this.set_opts(opts);
		this.load_job_history();
	}
	set_opts(opts) {
		if (typeof(opts) != 'object') {
			console.error('Options are not object.', opts);
			return;
		}
		if (opts.hasOwnProperty('end_time')) {
			this.set_end_time(opts.end_time);
		} else {
			// if no end time given, by default take now
			const et = (new Date()).setHours(23, 59, 59, 999);
			this.set_end_time(et);
		}
		if (opts.hasOwnProperty('start_time')) {
			this.set_start_time(opts.start_time);
		}
		if (opts.hasOwnProperty('days')) {
			this.set_days(opts.days);
		}
		if (opts.hasOwnProperty('job_name')) {
			this.set_job_name(opts.job_name);
		}
	}
	set_days(days) {
		this.days = parseInt(days, 10);
	}
	set_job_name(job_name) {
		this.job_name = job_name;
	}
	set_legend() {
		let ctx;
		// set full backup color
		const full = document.getElementById(this.ids.legend_full);
		ctx = full.getContext('2d');
		ctx.fillStyle = this.colors.F;
		ctx.fillRect(0, 0, full.width, full.height);

		// set incremental backup color
		const inc = document.getElementById(this.ids.legend_inc);
		ctx = inc.getContext('2d');
		ctx.fillStyle = this.colors.I;
		ctx.fillRect(0, 0, inc.width, inc.height);

		// set differential backup color
		const diff = document.getElementById(this.ids.legend_diff);
		ctx = diff.getContext('2d');
		ctx.fillStyle = this.colors.D;
		ctx.fillRect(0, 0, diff.width, diff.height);
	}
	load_job_history() {
		const cb = <%=$this->LoadJobHistory->ActiveControl->Javascript%>;
		cb.setCallbackParameter(this.job_name);
		cb.dispatch();
	}
	set_job_history(data) {
		if (!data || !Array.isArray(data)) {
			console.error('No job data.');
			return;
		}
		const self = <%=$this->ClientID%>_job_history_range_slider_obj;
		self.all_jobs = data;
		self.init();
	}
	init(zoom) {
		this.apply_jobs(this.all_jobs, zoom);
		this.init_slider();
	}
	apply_jobs(data, zoom) {
		const end = parseInt(this.end_time / 1000, 10);
		const start = end - (this.days * 60 * 60 * 24);

		this.jobs = [];
		let closest_full;
		for (let i = 0; i < data.length; i++) {
			if (data[i].type != 'B') {
				// we consider only backup job types
				continue;
			}
			if (['T', 'W'].indexOf(data[i].jobstatus) == -1) {
				// only healthy jobs taken into account
				continue;
			}
			if (data[i].jobtdate < start || data[i].jobtdate > end) {
				// jobs older than 'start' setting and not newer than 'end'
				continue;
			}
			if (data[i].level == 'F' && !closest_full) {
				// remember the closest healthy full
				closest_full = data[i];
			}
			this.jobs.push(data[i]);
		}
		const c = document.getElementById(this.ids.canvas);
		const ctx = c.getContext('2d');

		let day;
		let day_from = this.days;
		for (let i = 0; i < this.jobs.length; i++) {
			day = parseInt((end - this.jobs[i].jobtdate) / 60 / 60 / 24, 10);
			if (!zoom && closest_full && this.jobs[i].jobid == closest_full.jobid) {
				day_from = day + 1;
			}
			this.add_job(ctx, day, this.jobs[i]);
		}
		this.set_date_from(day_from);
		this.set_date_to(0);
		this.set_jobs_in_range(day_from, 0);
	}
	add_job(ctx, day, job) {
		const single_day = this.size.width / this.days;
		const width = single_day / 2;
		const x = this.size.width - (day * single_day) - single_day;
		if (['F', 'I', 'D'].indexOf(job.level) > -1) {
			ctx.fillStyle = this.colors[job.level];
		} else {
			ctx.fillStyle = this.colors.o;
		}
		if (job.level == 'F') {
			ctx.globalCompositeOperation = 'source-over';
		} else {
			ctx.globalCompositeOperation = 'destination-over';
		}
		ctx.fillRect(x, 0, width, this.size.height);
	}
	init_slider() {
		this.set_legend();

		this.slider = $('#' + this.ids.slider);
		// add styles
		this.slider.css(this.size);

		const opts = $.extend({}, this.def_opts, this.custom_opts);
		opts.max = this.days;
		opts.change = (event,  ui) => {
			const day_from = this.days - ui.values[0];
			const day_to = this.days - ui.values[1];
			this.set_date_from(day_from);
			this.set_date_to(day_to);
			this.set_jobs_in_range(day_from, day_to);
		};
		this.slider.slider(opts);

		// add alpha channel to mark area
		this.slider.find('div').css({'opacity': '0.5'});
	}
	set_start_time(t) {
		if (this.end_time) {
			const tdiff = this.end_time - t;
			const days = parseInt(tdiff / 60 / 60 / 24 / 1000, 10);
			this.set_days(days);
		}
	}
	set_end_time(t) {
		this.end_time = new Date(t).setHours(23, 59, 59, 999) + 1000;
	}
	set_jobs_in_range(day_from, day_to) {
		const t_from = parseInt(this.get_date_by_day(day_from, 1) / 1000, 10);
		const t_to = parseInt(this.get_date_by_day(day_to, 1) / 1000, 10);
		let cnt_in = 0;
		let cnt_out = 0;
		let bytes_sum = 0;
		let files_sum = 0;
		for (let i = 0; i < this.jobs.length; i++) {
			if (this.jobs[i].jobtdate >= t_from && this.jobs[i].jobtdate <= t_to) {
				cnt_in++;
				bytes_sum += this.jobs[i].jobbytes;
				files_sum += this.jobs[i].jobfiles;
			}
			if (this.jobs[i].jobtdate >= t_to) {
				cnt_out++;
			}
		}
		const jobs_in_range = document.getElementById(this.ids.jobs_in_range);
		jobs_in_range.textContent = cnt_in;

		const jobs_to_keep = document.getElementById(this.ids.jobs_to_keep);
		jobs_to_keep.textContent = cnt_out;

		const job_bytes_sum = document.getElementById(this.ids.bytes_sum);
		job_bytes_sum.textContent = Units.get_formatted_size(bytes_sum);

		const job_files_sum = document.getElementById(this.ids.files_sum);
		job_files_sum.textContent = files_sum;

		const days_range = document.getElementById(this.ids.days_range);
		days_range.textContent = day_from - day_to;
	}
	get_date_by_day(day, round, end_time) {
		const ts = day * 60 * 60 * 24 * 1000;
		const et = end_time || this.end_time;
		const nd = new Date(et - ts);
		let dt;
		if (round == 1) {
			dt = nd.setHours(0, 0, 0, 0);
		} else if (round == 2) {
			dt = nd.setHours(23, 59, 59, 999);
		} else {
			dt = nd.getTime();
		}
		return dt;
	}
	set_date_from(day) {
		const nd = this.get_date_by_day(day, 1);
		const span = document.getElementById(this.ids.date_from);
		span.textContent = Units.format_date(nd, true);
		this.custom_opts.values[0] = this.days - day;
	}
	set_date_to(day) {
		const nd = this.get_date_by_day(day, 1);
		const span = document.getElementById(this.ids.date_to);
		span.textContent = Units.format_date(nd, true);
		this.custom_opts.values[1] = this.days - day;
	}
	destroy() {
		const c = document.getElementById(this.ids.canvas);
		const ctx = c.getContext('2d');
		ctx.clearRect(0, 0, c.width, c.height);
		this.slider.slider('destroy');
	}
	zoom_in() {
		const values = this.slider.slider('values');
		this.destroy();
		const end = this.get_date_by_day(this.days + 1 - values[1], 1);
		const start = this.get_date_by_day(this.days - values[0], 1);
		this.set_end_time(end);
		this.set_start_time(start);
		this.set_date_from(this.days);
		this.set_date_to(0);
		this.init(true);
	}
	reset_zoom() {
		this.destroy();
		const days = <%=$this->getDays()%>;
		this.set_days(days);
		const end_time = (new Date()).getTime();
		this.set_end_time(end_time);
		this.init();
	}
}
$(function() {
	<%=$this->ClientID%>_job_history_range_slider_obj = new <%=$this->ClientID%>JobHistoryRangeSlider({
		days: <%=$this->getDays()%>,
		job_name: '<%=$this->getJobName()%>'
	});
});
</script>
