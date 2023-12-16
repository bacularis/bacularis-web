<div id="volume_job_list">
	<table id="jobs_on_volume_list" class="w3-table w3-striped w3-hoverable w3-margin-bottom" style="width: 100%">
		<thead>
			<tr>
				<th></th>
				<th><%[ JobId ]%></th>
				<th><%[ Name ]%></th>
				<th><%[ Type ]%></th>
				<th class="w3-center"><%[ Level ]%></th>
				<th class="w3-center">ClientId</th>
				<th class="w3-center"><%[ Client ]%></th>
				<th class="w3-center"><%[ Scheduled time ]%></th>
				<th class="w3-center"><%[ Start time ]%></th>
				<th class="w3-center"><%[ End time ]%></th>
				<th class="w3-center"><%[ Real end time ]%></th>
				<th class="w3-center">JobTDate</th>
				<th class="w3-center">VolSessionId</th>
				<th class="w3-center">VolSessionTime</th>
				<th class="w3-center"><%[ Job status ]%></th>
				<th class="w3-center"><%[ Size ]%></th>
				<th class="w3-center"><%[ Read bytes ]%></th>
				<th class="w3-center"><%[ Files ]%></th>
				<th class="w3-center"><%[ Job errors ]%></th>
				<th class="w3-center"><%[ Job missing files ]%></th>
				<th class="w3-center">PoolId</th>
				<th class="w3-center"><%[ Pool ]%></th>
				<th class="w3-center">FileSetId</th>
				<th class="w3-center"><%[ FileSet ]%></th>
				<th class="w3-center">PriorJobId</th>
				<th class="w3-center"><%[ Purged files ]%></th>
				<th class="w3-center"><%[ Has base ]%></th>
				<th class="w3-center"><%[ Reviewed ]%></th>
				<th class="w3-center"><%[ Comment ]%></th>
				<th class="w3-center"><%[ File table ]%></th>
				<th class="w3-center"><%[ First vol. ]%></th>
				<th class="w3-center"><%[ Vol. count ]%></th>
				<th class="w3-center"><%[ Action ]%></th>
			</tr>
		</thead>
		<tbody id="jobs_on_volume_list_body"></tbody>
		<tfoot>
			<tr>
				<th></th>
				<th><%[ JobId ]%></th>
				<th><%[ Name ]%></th>
				<th><%[ Type ]%></th>
				<th class="w3-center"><%[ Level ]%></th>
				<th class="w3-center">ClientId</th>
				<th class="w3-center"><%[ Client ]%></th>
				<th class="w3-center"><%[ Scheduled time ]%></th>
				<th class="w3-center"><%[ Start time ]%></th>
				<th class="w3-center"><%[ End time ]%></th>
				<th class="w3-center"><%[ Real end time ]%></th>
				<th class="w3-center">JobTDate</th>
				<th class="w3-center">VolSessionId</th>
				<th class="w3-center">VolSessionTime</th>
				<th class="w3-center"><%[ Job status ]%></th>
				<th class="w3-center"><%[ Size ]%></th>
				<th class="w3-center"><%[ Read bytes ]%></th>
				<th class="w3-center"><%[ Files ]%></th>
				<th class="w3-center"><%[ Job errors ]%></th>
				<th class="w3-center"><%[ Job missing files ]%></th>
				<th class="w3-center">PoolId</th>
				<th class="w3-center"><%[ Pool ]%></th>
				<th class="w3-center">FileSetId</th>
				<th class="w3-center"><%[ FileSet ]%></th>
				<th class="w3-center">PriorJobId</th>
				<th class="w3-center"><%[ Purged files ]%></th>
				<th class="w3-center"><%[ Has base ]%></th>
				<th class="w3-center"><%[ Reviewed ]%></th>
				<th class="w3-center"><%[ Comment ]%></th>
				<th class="w3-center"><%[ File table ]%></th>
				<th class="w3-center"><%[ First vol. ]%></th>
				<th class="w3-center"><%[ Vol. count ]%></th>
				<th class="w3-center"><%[ Action ]%></th>
			</tr>
		</tfoot>
	</table>
</div>
<com:TCallback ID="JobsOnVolume" OnCallback="loadJobs" />
<script>
var oJobsOnVolumeList = {
	table: null,
	ids: {
		jobs_on_volume_list: 'jobs_on_volume_list',
		jobs_on_volume_list_body: 'jobs_on_volume_list_body'
	},
	init: function(data) {
		if (this.table) {
			var page = this.table.page();
			this.table.clear().rows.add(data).draw();
			this.table.page(page).draw(false);
		} else {
			this.set_table(data);
		}
	},
	update: function(data) {
		oJobsOnVolumeList.init(data);
	},
	update_jobs: function() {
		const cb = <%=$this->JobsOnVolume->ActiveControl->Javascript%>;
		cb.dispatch();
	},
	set_table: function(data) {
		this.table = $('#' + this.ids.jobs_on_volume_list).DataTable({
			data: data,
			deferRender: true,
			dom: 'lBfrtip',
			stateSave: true,
			stateDuration: KEEP_TABLE_SETTINGS,
			buttons: [
				'copy', 'csv', 'colvis'
			],
			columns: [
				{
					className: 'details-control',
					orderable: false,
					data: null,
					defaultContent: '<button type="button" class="w3-button w3-blue"><i class="fa fa-angle-down"></i></button>'
				},
				{data: 'jobid'},
				{data: 'name'},
				{
					data: 'type',
					render: function(data, type, row) {
						return JobType.get_type(data);
					}
				},
				{
					data: 'level',
					render: function(data, type, row) {
						return (['R', 'D'].indexOf(row.type) === -1 ? JobLevel.get_level(data) : '-');
					}
				},
				{
					data: 'clientid',
					visible: false
				},
				{
					data: 'client',
					visible: false
				},
				{
					data: 'schedtime_epoch',
					render: render_date_ts,
					visible: false
				},
				{
					data: 'starttime_epoch',
					render: render_date_ts
				},
				{
					data: 'endtime_epoch',
					render: render_date_ts
				},
				{
					data: 'realendtime_epoch',
					render: render_date_ts,
					visible: false
				},
				{
					data: 'jobtdate',
					render: render_date_ts_local,
					visible: false
				},
				{
					data: 'volsessionid',
					visible: false
				},
				{
					data: 'volsessiontime',
					render: render_date_ts_local,
					visible: false
				},
				{
					data: 'jobstatus',
					render: render_jobstatus,
					className: 'w3-center'
				},
				{
					data: 'jobbytes',
					render: render_bytes
				},
				{
					data: 'readbytes',
					render: render_bytes,
					visible: false
				},
				{data: 'jobfiles'},
				{
					data: 'joberrors',
					visible: false
				},
				{
					data: 'jobmissingfiles',
					visible: false
				},
				{
					data: 'poolid',
					visible: false
				},
				{
					data: 'pool',
					visible: false
				},
				{
					data: 'filesetid',
					visible: false
				},
				{
					data: 'fileset',
					visible: false
				},
				{
					data: 'priorjobid',
					visible: false
				},
				{
					data: 'purgedfiles',
					visible: false
				},
				{
					data: 'hasbase',
					visible: false
				},
				{
					data: 'reviewed',
					visible: false
				},
				{
					data: 'comment',
					visible: false
				},
				{
					data: 'filetable',
					visible: false,
					defaultContent: ''
				},
				{
					data: 'firstvol',
					visible: false,
					defaultContent: ''
				},
				{
					data: 'volcount',
					visible: false,
					defaultContent: ''
				},
				{
					data: 'jobid',
					render: function (data, type, row) {
						var btn = document.createElement('BUTTON');
						btn.className = 'w3-button w3-green';
						btn.type = 'button';
						btn.title = '<%[ Details ]%>';
						var i = document.createElement('I');
						i.className = 'fa fa-list-ul';
						btn.appendChild(i);
						btn.setAttribute('onclick', "document.location.href = '/web/job/history/" + data + "/'");
						return btn.outerHTML;
					}
				}
			],
			responsive: {
				details: {
					type: 'column'
				}
			},
			columnDefs: [{
				className: 'control',
				orderable: false,
				targets: 0
			},
			{
				className: "dt-center",
				targets: [ 1, 3, 4, 5, 7, 8, 9, 10, 11, 12, 13, 17, 18, 19, 20, 22, 24, 25, 26, 27, 30, 31, 32 ]
			},
			{
				className: "dt-body-right",
				targets: [ 15, 16 ]
			}],
			order: [1, 'desc'],
			drawCallback: function () {
				this.api().columns([2, 3, 4, 14]).every(function () {
					var column = this;
					var select = $('<select><option value=""></option></select>')
					.appendTo($(column.footer()).empty())
					.on('change', function () {
						var val = dtEscapeRegex(
							$(this).val()
						);
						column
						.search(val ? '^' + val + '$' : '', true, false)
						.draw();
					});
					if (column[0][0] == 14) {
						column.data().unique().sort().each(function (d, j) {
							if (column.search() == '^' + dtEscapeRegex(d) + '$') {
								select.append('<option value="' + d + '" title="' + JobStatus.get_desc(d) + '" selected>' + d + '</option>');
							} else {
								select.append('<option value="' + d + '" title="' + JobStatus.get_desc(d) + '">' + d + '</option>');
							}
						});
					} else {
						column.cells('', column[0]).render('display').unique().sort().each(function(d, j) {
							if (column.search() == '^' + dtEscapeRegex(d) + '$') {
								select.append('<option value="' + d + '" selected>' + d + '</option>');
							} else {
								select.append('<option value="' + d + '">' + d + '</option>');
							}
						});
					}
				});
			}
		});
	}
};
</script>
