<div id="tag_manager">
	<table id="tag_manager_list" class="w3-table w3-striped w3-hoverable w3-margin-bottom" style="width: 100%">
		<thead>
			<tr>
				<th></th>
				<th><%[ Tag ]%></th>
				<th><%[ Color ]%></th>
				<th><%[ Severity ]%></th>
				<th><%[ Action ]%></th>
			</tr>
		</thead>
		<tbody id="tag_manager_list_body"></tbody>
		<tfoot>
			<tr>
				<th></th>
				<th><%[ Tag ]%></th>
				<th><%[ Color ]%></th>
				<th><%[ Severity ]%></th>
				<th><%[ Action ]%></th>
			</tr>
		</tfoot>
	</table>
</div>
<div id="tag_manager_window" class="w3-modal" style="display: none">
	<div class="w3-modal-content w3-card-4 w3-animate-zoom">
		<header class="w3-container w3-green">
			<span onclick="oTagManagerAction.show_window(false);" class="w3-button w3-display-topright">×</span>
			<h2><%[ Edit tag ]%> - <span id="tag_manager_window_title"></span></h2>
		</header>
		<div class="w3-padding" style="overflow-x: auto; min-height: 150px;">
			<div class="w3-row directive_field" style="margin: 0;">
				<div class="w3-col w3-third">
					<%[ Tag ]%>:
				</div>
				<div class="w3-col w3-half">
					<com:TTextBox
						ID="TagName"
						CssClass="w3-input w3-border"
						ReadOnly="true"
					/>
				</div>
			</div>
			<h4><%[ Color ]%></h4>
			<div class="w3-row directive_field">
				<div id="tag_manager_colors" class="w3-padding">
					<com:TRepeater ID="TagColors">
						<prop:ItemTemplate>
							<div class="w3-left pointer" style="width: 50px; height: 30px; margin: 4px; background-color: <%=$this->Data['bg']%>;" data-name="<%=$this->Data['name']%>"></div>
						</prop:ItemTemplate>
					</com:TRepeater>
				</div>
			</div>
			<h4><%[ Severity ]%></h4>
			<div class="w3-row directive_field" style="margin: 0;">
				<div class="w3-col w3-third">
					<%[ Select severity ]%>:
				</div>
				<div class="w3-col w3-half">
					<select id="tag_manager_severity_level" class="w3-select w3-border" style="width: 200px;">
						<com:TRepeater ID="TagSeverity">
							<prop:ItemTemplate>
								<option value="<%=$this->Data['value']%>"><%=$this->Data['name']%></option>
							</prop:ItemTemplate>
						</com:TRepeater>
					</select>
				</div>
			</div>
		</div>
		<footer class="w3-container w3-center w3-border-top">
			<button type="button" class="w3-button w3-section w3-red" onclick="oTagManagerAction.show_window(false);">
				<i class="fa fa-times"></i> &nbsp;<%[ Cancel ]%>
			</button>
			<button type="button" class="w3-green w3-button" onclick="oTagManagerAction.save();">
				<i class="fa-solid fa-save"></i> &nbsp;<%[ Save ]%>
			</button>
		</footer>
	</div>
</div>
<com:TCallback ID="LoadTagList" OnCallback="loadTagList" />
<com:TCallback ID="EditTag" OnCallback="editTag" />
<com:TCallback ID="SaveTag" OnCallback="saveTag" />
<com:TCallback ID="DeleteTag" OnCallback="deleteTag" />
<script>
oTagManagerAction = {
	ids: {
		win: 'tag_manager_window',
		title: 'tag_manager_window_title',
		colors: 'tag_manager_colors',
		tag: '<%=$this->TagName->ClientID%>',
		severity: 'tag_manager_severity_level'
	},
	palette: <%=json_encode($this->palette)%>,
	init: function() {
		this.add_events();
	},
	add_events: function() {
		const container = document.getElementById(this.ids.colors);
		const colors = container.querySelectorAll('div[data-name]');
		const color_fn = (color) => {
			color.addEventListener('click', (e) => {
				this.unmark_all_colors();
				this.mark_color_el(color);
			});
		}
		for (const color of colors) {
			color_fn(color);
		}
	},
	open: function(tag) {
		this.set_title(tag);
		this.show_window(true);
	},
	show_window: function(show) {
		const win = document.getElementById(this.ids.win);
		win.style.display = show ? 'block' : 'none';
	},
	set_title: function(title) {
		const span = document.getElementById(this.ids.title);
		span.textContent = title;
	},
	edit: function(tag) {
		const cb = <%=$this->EditTag->ActiveControl->Javascript%>;
		cb.setCallbackParameter(tag);
		cb.dispatch();
		this.open(tag);
	},
	edit_cb: function(data) {
		const self = oTagManagerAction;
		self.set_tag_name(data.tag);
		self.mark_color(data.color);
		self.set_severity(data.severity);
	},
	save: function() {
		const tag = this.get_tag_name();
		const color = this.get_color();
		const severity = this.get_severity();
		const data = {
			tag: tag,
			color: color,
			severity: severity
		};

		const cb = <%=$this->SaveTag->ActiveControl->Javascript%>;
		cb.setCallbackParameter(data);
		cb.dispatch();
	},
	save_cb: function() {
		oTagManagerAction.show_window(false);
	},
	delete: function(tag) {
		const cb = <%=$this->DeleteTag->ActiveControl->Javascript%>;
		cb.setCallbackParameter(tag);
		cb.dispatch();
	},
	unmark_all_colors: function() {
		const container = document.getElementById(this.ids.colors);
		const colors = container.querySelectorAll('div[data-name]');
		for (const color of colors) {
			color.removeAttribute('data-selected');
			color.style.removeProperty('border');
		}
	},
	get_tag_name: function() {
		const container = document.getElementById(this.ids.tag);
		return container.value;
	},
	set_tag_name: function(name) {
		const container = document.getElementById(this.ids.tag);
		container.value = name;
	},
	get_severity: function() {
		const container = document.getElementById(this.ids.severity);
		return container.value;
	},
	set_severity: function(severity) {
		const container = document.getElementById(this.ids.severity);
		container.value = severity;
	},
	mark_color: function(color) {
		this.unmark_all_colors();
		const container = document.getElementById(this.ids.colors);
		const el = container.querySelector('div[data-name="' + color + '"]');
		this.mark_color_el(el);
	},
	mark_color_el: function(el) {
		el.setAttribute('data-selected', 'true');
		el.style.border = '3px solid black'
	},
	get_color: function(color) {
		const container = document.getElementById(this.ids.colors);
		const el = container.querySelector('div[data-selected="true"]');
		return el.getAttribute('data-name');
	},
	get_color_val: function(color) {
		return (this.palette[color] || '');
	}
};
oTagManagerList = {
	table: null,
	ids: {
		tag_manager_list: 'tag_manager_list',
		tag_manager_list_body: 'tag_manager_list_body'
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
		oTagManagerList.init(data);
	},
	update_jobs: function() {
		const cb = <%=$this->LoadTagList->ActiveControl->Javascript%>;
		cb.dispatch();
	},
	set_table: function(data) {
		this.table = $('#' + this.ids.tag_manager_list).DataTable({
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
				{data: 'tag'},
				{
					data: 'color',
					render: function (data, type, row) {
						let ret = data;
						if (type == 'display') {
							const container = document.createElement('DIV');
							container.style.width = '140px';
							container.style.height = '18px';
							container.style.margin = '0 auto';
							container.style.fontSize = '13px';
							container.classList.add('btag');
							const color = oTagManagerAction.get_color_val(data);
							container.style.backgroundColor = color.bg;
							container.style.color = color.fg;
							container.textContent = data;
							ret = container.outerHTML;
						}
						return ret;
					}
				},
				{
					data: 'severity',
					render: function (data, type, row) {
						let ret = data;
						if (type == 'display') {
							ret = Tag.get_severity_desc(data);
						}
						return ret;
					}
				},
				{
					data: 'tag',
					render: function (data, type, row) {
						const container = document.createElement('DIV');

						// Edit button
						const edit_btn = document.createElement('BUTTON');
						edit_btn.className = 'w3-button w3-green';
						edit_btn.style.marginRight = '6px';
						edit_btn.type = 'button';
						edit_btn.title = '<%[ Edit ]%>';
						edit_btn.setAttribute('onclick', 'oTagManagerAction.edit("' + data + '");');
						const edit_img = document.createElement('I');
						edit_img.className = 'fa-solid fa-edit';
						edit_btn.appendChild(edit_img);
						container.appendChild(edit_btn);

						// Delete button
						const del_btn = document.createElement('BUTTON');
						del_btn.className = 'w3-button w3-red';
						del_btn.type = 'button';
						del_btn.title = '<%[ Delete ]%>';
						del_btn.setAttribute('onclick', 'oTagManagerAction.delete("' + data + '");');
						del_img = document.createElement('I');
						del_img.className = 'fa-solid fa-trash-alt';
						del_btn.appendChild(del_img);
						container.appendChild(del_btn);

						return container.outerHTML;
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
					targets: [ 2, 3, 4 ]
				}
			],
			order: [1, 'asc'],
			drawCallback: function () {
				this.api().columns([1, 2, 3]).every(function () {
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
					if (column[0][0] == 3) { // severity
						column.data().unique().sort().each(function (d, j) {
							if (column.search() == '^' + dtEscapeRegex(d) + '$') {
								select.append('<option value="' + d + '" selected>' + Tag.get_severity_desc(d) + '</option>');
							} else {
								select.append('<option value="' + d + '">' + Tag.get_severity_desc(d) + '</option>');
							}
						});
					} else {
						column.data().unique().sort().each(function (d, j) {
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
$(function() {
	oTagManagerAction.init();
	oTagManagerList.update_jobs();
})
</script>
