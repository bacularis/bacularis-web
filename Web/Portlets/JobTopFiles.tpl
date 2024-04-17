<com:TActiveDropDownList
	ID="FileListTops"
	CssClass="w3-select w3-border"
	style="width: 270px;"
	OnCallback="loadTopFileList"
>
	<prop:ClientSide.OnLoading>
		oJobTopFiles<%=$this->ClientID%>.show_loader(true);
	</prop:ClientSide.OnLoading>
	<prop:ClientSide.OnComplete>
		oJobTopFiles<%=$this->ClientID%>.show_loader(false);
	</prop:ClientSide.OnComplete>
	<com:TListItem Value="none:none" Text="<%[ Select top 10 criteria ]%>" />
	<com:TListItem Value="size:desc" Text="<%[ Show 10 largest files ]%>" />
	<com:TListItem Value="size:asc" Text="<%[ Show 10 smallest files ]%>" />
	<com:TListItem Value="mtime:desc" Text="<%[ Show 10 most recent files (MTIME) ]%>" />
	<com:TListItem Value="mtime:asc" Text="<%[ Show 10 oldest files (MTIME) ]%>" />
</com:TActiveDropDownList>
<i id="job_tops_loading" class="fa fa-sync w3-spin w3-margin-left" style="display: none;"></i>
<div id="job_top_files_modal" class="w3-modal" style="display: none;">
	<div class="w3-modal-content w3-animate-top w3-card-4" style="min-width: 90%;">
		<header class="w3-container w3-green">
			<span onclick="oJobTopFiles<%=$this->ClientID%>.show(false);" class="w3-button w3-display-topright">×</span>
			<h2 id="job_top_files_modal_title"></h2>
		</header>
		<div class="w3-container w3-padding w3-container">
			<table id="job_top_files_table" class="w3-table w3-striped w3-margin-bottom dataTable dtr-column" style="width: 100%">
				<thead>
					<tr class="row">
						<th></th>
						<th class="w3-center w3-hide-small" style="width: 65px"><%[ Attributes ]%></th>
						<th class="w3-center w3-hide-small" style="width: 39px; padding: 10px 3px">UID</th>
						<th class="w3-center w3-hide-small" style="width: 39px; padding: 10px 3px">GID</th>
						<th class="w3-center w3-hide-small" style="width: 40px">Size</th>
						<th class="w3-center w3-hide-small" style="width: 135px">MTIME</th>
						<th class="w3-center"><%[ File ]%></th>
						<th class="w3-center w3-hide-small" style="width: 50px"><%[ State ]%></th>
					</tr>
				</thead>
				<tbody id="job_top_files_table_body"></tbody>
				<tfoot>
					<tr class="row">
						<th></th>
						<th class="w3-center w3-hide-small" style="width: 65px"><%[ Attributes ]%></th>
						<th class="w3-center w3-hide-small" style="width: 39px; padding: 10px 3px">UID</th>
						<th class="w3-center w3-hide-small" style="width: 39px; padding: 10px 3px">GID</th>
						<th class="w3-center w3-hide-small" style="width: 40px">Size</th>
						<th class="w3-center w3-hide-small" style="width: 135px">MTIME</th>
						<th class="w3-center"><%[ File ]%></th>
						<th class="w3-center w3-hide-small" style="width: 50px"><%[ State ]%></th>
					</tr>
				</tfoot>
			</table>
		</div>
		<footer class="w3-container w3-center">
			<button type="button" class="w3-button w3-green w3-section" onclick="oJobTopFiles<%=$this->ClientID%>.show(false);"><i class="fas fa-times"></i> &nbsp;Close</button>
		</footer>
	</div>
</div>
<script>
var oJobTopFiles<%=$this->ClientID%> = {
	ids: {
		file_list: 'job_top_files_table',
		file_list_body: 'job_top_files_table_body',
		file_list_modal: 'job_top_files_modal',
		file_list_modal_title: 'job_top_files_modal_title',
		job_tops_loader: 'job_tops_loading'
	},
	data: [],
	table: null,
	enableNestedDataAccess: '.',
	init: function(data) {
		const self = oJobTopFiles<%=$this->ClientID%>;
		if (self.table) {
			self.update_table(data);
		} else {
			self.set_table(data);
		}
		self.show(true);
	},
	update_table: function(data) {
		this.table.clear();
		this.table.rows.add(data);
		this.table.draw(false);
	},
	set_table: function(data) {
		this.table = $('#' + this.ids.file_list).DataTable({
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
				{data: 'lstat.mode'},
				{data: 'lstat.uid'},
				{data: 'lstat.gid'},
				{
					data: 'lstat.size',
					render: render_bytes
				},
				{
					data: 'lstat.mtime',
					render: render_date_ts
				},
				{data: 'file'},
				{
					data: 'fileindex',
					render: (data, type, row) => {
						let ret;
						const saved = '<%[ saved ]%>';
						const deleted = '<%[ deleted ]%>';
						if (type === 'display') {
							const span = document.createElement('SPAN');
							span.classList.add('bold');
							if (data > 0) {
								span.classList.add('w3-text-green');
								span.textContent = saved;
							} else {
								span.classList.add('w3-text-orange');
								span.textContent = deleted;
							}
							ret = span.outerHTML;
						} else {
							if (data > 0) {
								ret = saved;
							} else {
								ret = deleted;
							}
						}
						return ret;
					}
				}
			],
			responsive: {
				details: {
					type: 'column'
				}
			},
			columnDefs: [
				{
					className: 'control',
					orderable: false,
					targets: 0
				},
				{
					className: "dt-center",
					targets: [ 2, 3, 7 ]
				}
			],
			drawCallback: function () {
				this.api().columns([2, 3]).every(function () {
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
					column.cells('', column[0]).render('filter').unique().sort().each(function(d, j) {
						if (column.search() == '^' + dtEscapeRegex(d) + '$') {
							select.append('<option value="' + d + '" selected>' + d + '</option>');
						} else {
							select.append('<option value="' + d + '">' + d + '</option>');
						}
					});
				});
			}
		});
	},
	show: function(show) {
		// set title
		const header = document.getElementById(this.ids.file_list_modal_title);
		const tops_mode = document.getElementById('<%=$this->FileListTops->ClientID%>');
		const title = '<%[ Job top 10 files ]%>';
		const subtitle = tops_mode.options[tops_mode.selectedIndex].textContent;
		header.textContent = title + ' - ' + subtitle;

		// show window
		const win = document.getElementById(this.ids.file_list_modal);
		win.style.display = show ? 'block' : 'none';
		this.table.responsive.recalc();

		// reset tops combobox
		tops_mode.value = 'none:none';
	},
	show_loader: function(show) {
		const loader = document.getElementById(this.ids.job_tops_loader);
		loader.style.display = show ? 'inline-block' : 'none';
	}
};
</script>
