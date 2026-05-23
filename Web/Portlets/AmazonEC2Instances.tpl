<div>
	<p class="w3-hide-small"><%[ Configure EC2 instance backups. Backing up an EC2 instance automatically includes all attached volumes, volume AWS metadata, and EC2 instance metadata. After restore, the recovered EC2 instance remains fully consistent with the original instance. ]%></p>
	<!-- Tag tools -->
	<com:Bacularis.Web.Portlets.TagTools ID="TagToolsAmazonEC2InstanceList" ViewName="amazon_ec2_instance_list" />
	<table id="amazon_ec2_instance_list_table" class="display w3-table w3-striped w3-hoverable w3-margin-bottom selectable" style="width: 100%;">
		<thead>
			<tr>
				<th></th>
				<th><%[ Instance name ]%></th>
				<th><%[ Instance ID ]%></th>
				<th class="w3-center"><%[ Type ]%></th>
				<th class="w3-center"><%[ Platform ]%></th>
				<th class="w3-center"><%[ Avail. zone ]%></th>
				<th class="w3-center"><%[ Public IP addr. ]%></th>
				<th class="w3-center"><%[ Private IP addr. ]%></th>
				<th class="w3-center"><%[ Root dev. name ]%></th>
				<th class="w3-center"><%[ AWS tags ]%></th>
				<th class="w3-center"><%[ Actions ]%></th>
			</tr>
		</thead>
		<tbody id="amazon_ec2_instance_list_body"></tbody>
		<tfoot>
			<tr>
				<th></th>
				<th><%[ Instance name ]%></th>
				<th><%[ Instance ID ]%></th>
				<th class="w3-center"><%[ Type ]%></th>
				<th class="w3-center"><%[ Platform ]%></th>
				<th class="w3-center"><%[ Avail. zone ]%></th>
				<th class="w3-center"><%[ Public IP addr. ]%></th>
				<th class="w3-center"><%[ Private IP addr. ]%></th>
				<th class="w3-center"><%[ Root dev. name ]%></th>
				<th class="w3-center"><%[ AWS tags ]%></th>
				<th class="w3-center"><%[ Actions ]%></th>
			</tr>
		</tfoot>
	</table>
	<p class="info w3-hide-medium w3-hide-small"><%[ Tip: Use left-click to select table row. Use CTRL + left-click to multiple row selection. Use SHIFT + left-click to add a range of rows to selection. ]%></p>
<com:TCallback ID="AmazonEC2InstanceList" OnCallback="TemplateControl.setEC2InstanceList" />
<com:TCallback ID="LoadAmazonEC2Instance" OnCallback="TemplateControl.loadEC2Instance" />
<script>
var oAmazonEC2InstanceList = {
	ids: {
		amazon_ec2_instance_list: 'amazon_ec2_instance_list_table'
	},
	actions: [
		{
			action: 'create-backup',
			label: '<%[ Create backup ]%>',
			value: ['instance_id', 'tags'],
			before: function () {
				const fields = ['instance_id', 'tags'];
				const table = oAmazonEC2InstanceList.table;
				const selected = get_table_action_selected_items(table, fields);
				const vals = [];
				for (const item of selected) {
					if (!Array.isArray(item.tags)) {
						continue;
					}
					const tag = item.tags.find((tag) => tag.Key == 'Name');
					const instance = {
						instance_id: item.instance_id,
						name: (tag.Value || '')
					};
					vals.push(instance);
				}
				oAmazonCreateEC2InstanceBackup.open_window(vals);
			}
		}
	],
	data: [],
	table: null,
	table_toolbar: null,
	init: function() {
		if (!this.table) {
			this.set_table();
			this.set_bulk_actions();
			this.set_events();
		} else {
			const page = this.table.page();
			this.table.clear().rows.add(this.data).draw();
			this.table.page(page).draw(false);
			this.set_filters(this.table);
			this.table_toolbar.style.display = 'none';
		}
	},
	set_events: function() {
		document.getElementById(this.ids.amazon_ec2_instance_list).addEventListener('click', function(e) {
			$(function() {
				const wa = (this.table.rows({selected: true}).data().length > 0) ? 'show' : 'hide';
				$(this.table_toolbar).animate({
					width: wa
				}, 'fast');
			}.bind(this));
		}.bind(this));
	},
	set_table: function() {
		this.table = $('#' + this.ids.amazon_ec2_instance_list).DataTable({
			data: this.data,
			deferRender: true,
			autoWidth: false,
			fixedHeader: {
				header: true,
				headerOffset: $('#main_top_bar').height()
			},
			layout: {
				topStart: [
					{
						pageLength: {}
					},
					{
						buttons: ['copy', 'csv', 'colvis']
					},
					{
						div: {
							className: 'table_toolbar'
						}
					}
				],
				topEnd: [
					'search'
				],
				bottomStart: [
					'info'
				],
				bottomEnd: [
					'paging'
				]
			},
			stateSave: true,
			stateDuration: KEEP_TABLE_SETTINGS,
			columns: [
				{
					orderable: false,
					data: null,
					defaultContent: '<button type="button" class="w3-button w3-blue"><i class="fa fa-angle-down"></i></button>'
				},
				{
					data: 'tags',
					render: function(data, type, row) {
						let name = '';
						if (type == 'display' || type == 'filter') {
							for (let i = 0; i < data.length; i++) {
								if (data[i].Key == 'Name') {
									name = data[i].Value;
									break;
								}
							}
						}
						return name;
					}
				},
				{data: 'instance_id'},
				{data: 'instance_type'},
				{data: 'platform_details'},
				{data: 'placement_availability_zone'},
				{data: 'public_ip_address'},
				{data: 'private_ip_address'},
				{data: 'root_device_name'},
				{
					data: 'tags',
					render: function(data, type, row) {
						let tags = [];
						let tag;
						for (let i = 0; i < data.length; i++) {
							tag = data[i].Key + ': ' + data[i].Value;
							tags.push(tag);
						}
						const span = document.createElement('SPAN');
						const val = tags.join(', ');
						const val_short = Strings.get_short_label(val, 20);
						span.title = val;
						span.textContent = val_short;
						return span;
					}
				},
				{
					data: 'instance_id',
					render: function (data, type, row) {
						let btns = '';

						// Edit button
						const btn_edit = document.createElement('BUTTON');
						btn_edit.className = 'w3-button w3-green';
						btn_edit.type = 'button';
						const i_edit = document.createElement('I');
						i_edit.className = 'fa-solid fa-plus';
						const label_edit = document.createTextNode(' <%[ Create backup ]%>');
						btn_edit.appendChild(i_edit);
						btn_edit.innerHTML += '&nbsp';
						btn_edit.style.marginRight = '8px';
						btn_edit.appendChild(label_edit);
						const tag = row.tags.find((item) => item.Key == 'Name');
						const name = tag ? tag.Value : '';
						const instance = JSON.stringify({
							instance_id: data,
							name: name
						});
						btn_edit.setAttribute('onclick', 'oAmazonCreateEC2InstanceBackup.open_window([' + instance + '])');
						btns += btn_edit.outerHTML;

						return btns;
					}
				}
			],
			responsive: {
				details: {
					type: 'column',
					display: DataTable.Responsive.display.childRow
				}
			},
			columnDefs: [{
				className: 'dtr-control',
				orderable: false,
				targets: 0
			},
			{
				className: 'action_col_long',
				orderable: false,
				targets: [ 10 ]
			},
			{
				className: "dt-center",
				targets: [ 3, 4, 5, 4, 5, 6, 7, 8, 9 ]
			}],
			select: {
				style:    'os',
				selector: 'td:not(:last-child):not(:first-child)',
				blurable: false
			},
			order: [1, 'asc'],
			initComplete: function () {
				oAmazonEC2InstanceList.set_filters(this.api());
			}
		});
	},
	set_filters: function(api) {
		api.columns([1, 2, 3, 4, 5, 8]).every(function () {
			const column = this;
			const select = $('<select class="dt-select"><option value=""></option></select>')
			.appendTo($(column.footer()).empty())
			.on('change', function () {
				const val = dtEscapeRegex(
					$(this).val()
				);
				column
				.search(val ? '^' + val + '$' : '', true, false)
				.draw();
			});
			if ([4].indexOf(column[0][0]) != -1) { // Enabled and In use by columns
				column.data().unique().sort().each(function (d, j) {
					var ds = d;
					if (column[0][0] == 4) { // Enabled column
						if (d === '1') {
							ds = '<%[ Enabled ]%>';
						} else if (d === '0') {
							ds = '<%[ Disabled ]%>';
						}
					}
					if (column.search() == '^' + dtEscapeRegex(d) + '$') {
						select.append('<option value="' + d + '" title="' + ds + '" selected>' + ds + '</option>');
					} else if (ds) {
						select.append('<option value="' + d + '" title="' + ds + '">' + ds + '</option>');
					}
				});
			} else {
				column.cells('', column[0]).render('display').unique().sort().each(function(d, j) {
					if (column.search() == '^' + dtEscapeRegex(d) + '$') {
						select.append('<option value="' + d + '" selected>' + d + '</option>');
					} else if(d) {
						select.append('<option value="' + d + '">' + d + '</option>');
					}
				});
			}
		});
	},
	set_bulk_actions: function() {
		this.table_toolbar = get_table_toolbar(this.table, this.actions, {
			actions: '<%[ Select action ]%>',
			ok: '<%[ OK ]%>'
		});
	}
};

var oAmazonEC2Instances = {
	load_ec2_instance_list: function() {
		const cb = <%=$this->AmazonEC2InstanceList->ActiveControl->Javascript%>;
		cb.dispatch();
	},
	load_ec2_instance_list_cb: function(list) {
		oAmazonEC2InstanceList.data = list;
		oAmazonEC2InstanceList.init();
	}
};

$(function() {
	oAmazonEC2Instances.load_ec2_instance_list();
});
</script>
</div>
<com:Bacularis.Web.Portlets.AmazonCreateEC2InstanceBackupWindow
	ID="AmazonEC2InstanceBackupWindow"
/>
