<div>
	<p class="w3-hide-small"><%[ Configure backups for selected EBS volumes. Use EBS volume backups when you do not need to back up entire EC2 instances or when you want to protect only specific volumes. EBS volume backups save both volume data and metadata, ensuring that restored volumes remain fully consistent with the original volumes. ]%></p>
	<!-- Tag tools -->
	<com:Bacularis.Web.Portlets.TagTools ID="TagToolsAmazonEBSVolumeList" ViewName="amazon_ebs_volume_list" />
	<table id="amazon_ebs_volume_list_table" class="display w3-table w3-striped w3-hoverable w3-margin-bottom selectable" style="width: 100%;">
		<thead>
			<tr>
				<th></th>
				<th><%[ Volume name ]%></th>
				<th><%[ Volume ID ]%></th>
				<th><%[ Size ]%> (GB)</th>
				<th class="w3-center"><%[ State ]%></th>
				<th class="w3-center"><%[ Vol. type ]%></th>
				<th class="w3-center"><%[ Avail. zone ]%></th>
				<th class="w3-center"><%[ Create time  ]%></th>
				<th class="w3-center"><%[ Encrypted  ]%></th>
				<th class="w3-center"><%[ KMS key ID ]%></th>
				<th class="w3-center"><%[ AWS tags ]%></th>
				<th class="w3-center"><%[ Actions ]%></th>
			</tr>
		</thead>
		<tbody id="amazon_ebs_volume_list_body"></tbody>
		<tfoot>
			<tr>
				<th></th>
				<th><%[ Volume name ]%></th>
				<th><%[ Volume ID ]%></th>
				<th><%[ Size ]%> (GB)</th>
				<th class="w3-center"><%[ State ]%></th>
				<th class="w3-center"><%[ Vol. type ]%></th>
				<th class="w3-center"><%[ Avail. zone ]%></th>
				<th class="w3-center"><%[ Create time  ]%></th>
				<th class="w3-center"><%[ Encrypted  ]%></th>
				<th class="w3-center"><%[ KMS key ID ]%></th>
				<th class="w3-center"><%[ AWS tags ]%></th>
				<th class="w3-center"><%[ Actions ]%></th>
			</tr>
		</tfoot>
	</table>
	<p class="info w3-hide-medium w3-hide-small"><%[ Tip: Use left-click to select table row. Use CTRL + left-click to multiple row selection. Use SHIFT + left-click to add a range of rows to selection. ]%></p>
<com:TCallback ID="AmazonEBSVolumeList" OnCallback="TemplateControl.setEBSVolumeList" />
<com:TCallback ID="LoadAmazonEBSVolume" OnCallback="TemplateControl.loadEBSVolume" />
<script>
var oAmazonEBSVolumeList = {
	ids: {
		amazon_ebs_volume_list: 'amazon_ebs_volume_list_table'
	},
	actions: [
		{
			action: 'create-backup',
			label: '<%[ Create backup ]%>',
			value: ['volume_id', 'tags'],
			before: function () {
				const fields = ['volume_id', 'tags'];
				const table = oAmazonEBSVolumeList.table;
				const selected = get_table_action_selected_items(table, fields);
				const vals = [];
				for (const item of selected) {
					if (!Array.isArray(item.tags)) {
						continue;
					}
					const tag = item.tags.find((tag) => tag.Key == 'Name');
					const volume = {
						volume_id: item.volume_id,
						name: (tag.Value || '')
					};
					vals.push(volume);
				}
				oAmazonCreateEBSVolumeBackup.open_window(vals);
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
		document.getElementById(this.ids.amazon_ebs_volume_list).addEventListener('click', function(e) {
			$(function() {
				const wa = (this.table.rows({selected: true}).data().length > 0) ? 'show' : 'hide';
				$(this.table_toolbar).animate({
					width: wa
				}, 'fast');
			}.bind(this));
		}.bind(this));
	},
	set_table: function() {
		this.table = $('#' + this.ids.amazon_ebs_volume_list).DataTable({
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
				{data: 'volume_id'},
				{data: 'size'},
				{data: 'state'},
				{data: 'volume_type'},
				{data: 'availability_zone'},
				{
					data: 'create_time',
					visible: false
				},
				{
					data: 'encrypted',
					render: function(data, type, row) {
						var ret;
						if (type == 'display') {
							ret = '-';
							if (data) {
								var check = document.createElement('I');
								check.className = 'fas fa-check';
								ret = check.outerHTML;
							}
						} else {
							ret = data;
						}
						return ret;
					}
				},
				{
					data: 'kms_key_id',
					visible: false
				},
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
					data: 'volume_id',
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
						const volume = JSON.stringify({
							volume_id: data,
							name: name
						});
						btn_edit.setAttribute('onclick', 'oAmazonCreateEBSVolumeBackup.open_window([' + volume + '])');
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
				className: 'dtr-control-custom',
				orderable: false,
				targets: 0
			},
			{
				className: 'action_col_long',
				orderable: false,
				targets: [ 11 ]
			},
			{
				className: "dt-right",
				targets: [ 3 ]
			},
			{
				className: "dt-center",
				targets: [ 4, 5, 6, 7, 8, 9, 10 ]
			}],
			select: {
				style:    'os',
				selector: 'td:not(:last-child):not(:first-child)',
				blurable: false
			},
			order: [1, 'asc'],
			initComplete: function () {
				oAmazonEBSVolumeList.set_filters(this.api());
			}
		});
	},
	set_filters: function(api) {
		api.columns([1, 3, 4, 5, 6, 8]).every(function () {
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
			if ([8].indexOf(column[0][0]) != -1) {
				column.data().unique().sort().each(function (d, j) {
					var ds = d;
					if (column[0][0] == 8) { // Encrypted column
						if (d === true) {
							ds = '<%[ Encrypted ]%>';
						} else if (d === false) {
							ds = '<%[ Unencrypted ]%>';
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

var oAmazonEBSVolumes = {
	load_ebs_volume_list: function() {
		const cb = <%=$this->AmazonEBSVolumeList->ActiveControl->Javascript%>;
		cb.dispatch();
	},
	load_ebs_volume_list_cb: function(list) {
		oAmazonEBSVolumeList.data = list;
		oAmazonEBSVolumeList.init();
	}
};

$(() => {
	oAmazonEBSVolumes.load_ebs_volume_list();
});
</script>
</div>
<com:Bacularis.Web.Portlets.AmazonCreateEBSVolumeBackupWindow
	ID="AmazonEBSVolumeBackupWindow"
/>
