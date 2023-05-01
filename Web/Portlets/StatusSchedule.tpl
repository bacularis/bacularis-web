<!-- Header -->
<header class="w3-container">
	<h5>
		<b><i class="fa fa-clock"></i> <%[ Schedule status ]%></b>
	</h5>
</header>
<div class="w3-container">
	<div class="w3-card-4 w3-padding w3-margin-bottom">
		<strong><%[ Filters: ]%></strong>
		 <%[ Date from: ]%> <com:TJuiDatePicker
			ID="DatePicker"
			Options.dateFormat="yy-mm-dd"
			Options.changeYear="true",
			Options.changeMonth="true"
			Options.showAnim="fold"
			Style.Width="100px"
			/>
		<com:TRequiredFieldValidator
			ValidationGroup="ScheduleFilters"
			ControlToValidate="DatePicker"
			Text="<%[ Field required. ]%>"
			Display="Dynamic"
		/>
		<com:TRegularExpressionValidator
			ValidationGroup="ScheduleFilters"
			ControlToValidate="DatePicker"
			RegularExpression="\d{4}-\d{2}-\d{2}"
			Text="<%[ Invalid date format. ]%>"
			Display="Dynamic"
		/>
		<%[ Days: ]%> <com:TTextBox
			ID="Days"
			Style.Width="50px"
		/>
		<com:TRequiredFieldValidator
			ValidationGroup="ScheduleFilters"
			ControlToValidate="Days"
			Text="<%[ Field required. ]%>"
			Display="Dynamic"
		/>
		<com:TDataTypeValidator
			ValidationGroup="ScheduleFilters"
			ControlToValidate="Days"
			DataType="Integer"
			Text="<%[ You must enter an integer. ]%>"
			Display="Dynamic"
		/>
		<com:TRangeValidator
			ValidationGroup="ScheduleFilters"
			ControlToValidate="Days"
			DataType="Integer"
			MinValue="1"
			MaxValue="1000"
			Text="<%[ Input must be between 1 and 1000. ]%>"
			Display="Dynamic"
		/>
		<com:TLabel
			ID="ClientLabel"
			ForControl="Client"
			Text="<%[ Client: ]%>"
		/> <com:TDropDownList
			ID="Client"
			Style.Width="150px"
		/>
		<com:TLabel
			ID="ScheduleLabel"
			ForControl="Schedule"
			Text="<%[ Schedule: ]%>"
		/>
		 <com:TDropDownList
			ID="Schedule"
			Style.Width="150px"
		/>
		<com:TActiveLinkButton
			ID="ApplyFilter"
			CausesValidation="true"
			ValidationGroup="ScheduleFilters"
			CssClass="w3-green w3-button w3-margin-left"
			OnClick="applyFilters"
		>
			<i class="fa fa-check"></i> &nbsp;<%[ Apply filters ]%>
		</com:TActiveLinkButton>
	</div>
	<table id="schedule_list" class="w3-table w3-striped w3-hoverable w3-margin-bottom" style="width: 100%">
		<thead>
			<tr>
				<th></th>
				<th><%[ Level ]%></th>
				<th><%[ Type ]%></th>
				<th><%[ Priority ]%></th>
				<th><%[ Scheduled ]%></th>
				<%=empty($this->Job) ? '<th>' . Prado::localize('Job name') . '</th>': ''%>
				<th><%[ Client ]%></th>
				<th><%[ FileSet ]%></th>
				<th><%[ Schedule ]%></th>
			</tr>
		</thead>
		<tbody id="schedule_list_body"></tbody>
		<tfoot>
			<tr>
				<th></th>
				<th><%[ Level ]%></th>
				<th><%[ Type ]%></th>
				<th><%[ Priority ]%></th>
				<th><%[ Scheduled ]%></th>
				<%=empty($this->Job) ? '<th>' . Prado::localize('Job name') . '</th>': ''%>
				<th><%[ Client ]%></th>
				<th><%[ FileSet ]%></th>
				<th><%[ Schedule ]%></th>
			</tr>
		</tfoot>
	</table>
</div>
<script type="text/javascript">
var oJobScheduleList = {
	table: null,
	data: <%=json_encode($this->schedules)%>,
	ids: {
		schedule_list: 'schedule_list',
		schedule_list_body: 'schedule_list_body'
	},
	init: function() {
		this.set_table();
	},
	set_data(data) {
		data = JSON.parse(data);
		this.data = data;
	},
	get_data(data) {
		return this.data;
	},
	set_table: function() {
		this.table = $('#' + this.ids.schedule_list).DataTable({
			data: this.get_data(),
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
				{
					data: 'level',
					render: render_level
				},
				{
					data: 'type',
					render: function(data, type, row) {
						return JobType.get_type(data);
					}
				},
				{data: 'priority'},
				{
					data: 'schedtime_epoch',
					render: render_date_ts_local
				},
				<%=empty($this->Job) ? '{data: "name"},' : ''%>
				{data: 'client'},
				{data: 'fileset'},
				{data: 'schedule'}
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
				targets: [ 1, 3 ]
			},
			<%=empty($this->Job) ? '{responsivePriority: 1,	targets: 5},' : ''%>
			{
				responsivePriority: 2,
				targets: <%=empty($this->Job) ? 8 : 7%>

			},
			{
				responsivePriority: 3,
				targets: 4
			},
			{
				responsivePriority: 4,
				targets: 1
			},
			{
				responsivePriority: 5,
				targets: 2
			}],
			order: [4, 'asc'],
			initComplete: function () {
				this.api().columns().every(function () {
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
					if (column[0][0] == 3) {
						column.cells('', column[0]).render('display').unique().sort(sort_natural).each(function(d, j) {
							if (column.search() == '^' + dtEscapeRegex(d) + '$') {
								select.append('<option value="' + d + '" selected>' + d + '</option>');
							} else {
								select.append('<option value="' + d + '">' + d + '</option>');
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

function set_job_schedule_data(data) {
	oJobScheduleList.set_data(data);
}
function init_job_schedule() {
	if (oJobScheduleList.table) {
		oJobScheduleList.table.destroy();
	}
	oJobScheduleList.init();
}
<%=count($this->schedules) > 0 ? 'init_job_schedule();' : ''%>
</script>
