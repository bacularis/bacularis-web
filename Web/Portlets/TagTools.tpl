<div id="tag_tools_window_<%=$this->ClientID%>" class="w3-modal" style="display: none">
	<div class="w3-modal-content w3-card-4 w3-animate-zoom">
		<header class="w3-container w3-green">
			<span onclick="oTagTools_<%=$this->ClientID%>.show_window(false);" class="w3-button w3-display-topright">×</span>
			<h2><%[ Assign tag ]%> - <span id="tag_tools_title_<%=$this->ClientID%>"></span></h2>
		</header>
		<div class="w3-padding" style="overflow-x: auto; min-height: 150px;">
			<p><%[ Please select existing tags or create new ones. ]%></p>
			<div class="w3-row directive_field">
				<div class="w3-col w3-third">
					<%[ Selected tags ]%>:
				</div>
				<div class="w3-col w3-half">
					<div id="add_tags_selected_<%=$this->ClientID%>">-</div>
				</div>
			</div>
			<div class="w3-row directive_field" style="margin: 0;">
				<div class="w3-col w3-third">
					<%[ Add tag(s) ]%>:
				</div>
				<div class="w3-col w3-half">
					<div class="w3-clear">
						<div id="add_tags_error_<%=$this->ClientID%>" class="w3-text-red" style="display: none"></div>
						<input type="text" id="add_tags_input_<%=$this->ClientID%>" class="w3-input w3-border w3-show-inline-block w3-left" style="width: 320px" value="" placeholder="<%[ Click here to set tags ]%>" pattern="<%=TagConfig::TAG_PATTERN%>" required />
						<button id="add_tags_add_btn_<%=$this->ClientID%>" type="button" class="w3-button w3-green" onclick="oTagTools_<%=$this->ClientID%>.assign();"><i class="fa fa-check"></i> &nbsp;<%[ Assign ]%></button>
					</div>
					<div id="tag_tools_suggestions_<%=$this->ClientID%>" class="w3-col w3-half w3-border w3-white w3-center" style="width: 320px; position: absolute; z-index: 10; display: none; max-height: 290px; overflow-y: auto; padding: 4px;">
						<div id="tag_tools_suggestions_new_tag_<%=$this->ClientID%>" class="w3-green pointer btag" style="width: 290px; margin: 0 auto;" onclick="oTagTools_<%=$this->ClientID%>.show_create_tools(true);">
							<%[ Create a new tag ]%>
						</div>
						<div id="tag_tools_suggestions_existing_tags_<%=$this->ClientID%>">
							<com:TActiveRepeater ID="TagList">
								<prop:ItemTemplate>
									<div class="w3-row">
										<div class="pointer w3-tag btag" style="width: 290px; color: <%#@TagConfig::TAG_COLORS[$this->Data['color']]['fg']%>; background-color: <%#@TagConfig::TAG_COLORS[$this->Data['color']]['bg']%>;" data-tag="<%#$this->Data['tag']%>" onclick="oTagTools_<%#$this->TemplateControl->ClientID%>.mark_selected('<%#$this->Data['tag']%>');">
											<%#$this->Data['tag']%>
											<span class="w3-right w3-small" style="cursor: help" title="<%#(($this->Data['access'] == TagConfig::ACCESSIBILITY_GLOBAL ? Prado::localize('global tag') : Prado::localize('local tag')) . ', ' . Prado::localize('severity') . ': ' . TagConfig::TAG_SEVERITY[$this->Data['severity']]['name'])%>">
												<%#(($this->Data['access'] == TagConfig::ACCESSIBILITY_GLOBAL ? 'G' : 'L') . $this->Data['severity'])%>
											</span>
										</div>
									</div>
								</prop:ItemTemplate>
							</com:TActiveRepeater>
						</div>
					</div>
				</div>
			</div>
			<div class="w3-row directive_field" style="margin: 0;">
				<div class="w3-col w3-third">&nbsp;</div>
			</div>
			<div id="tag_tools_create_tools_<%=$this->ClientID%>" style="display: none;">
				<h4><%[ Color ]%></h4>
				<div class="w3-row directive_field">
					<div id="tag_tools_colors_<%=$this->ClientiD%>" class="w3-padding">
						<com:TRepeater ID="TagColors">
							<prop:ItemTemplate>
								<div class="w3-left pointer" style="width: 50px; height: 30px; margin: 4px; background-color: <%#$this->Data['bg']%>;" data-name="<%#$this->Data['name']%>"></div>
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
						<select id="tag_tools_severity_level_<%=$this->ClientID%>" class="w3-select w3-border" style="width: 200px;">
							<com:TRepeater ID="TagSeverity">
								<prop:ItemTemplate>
									<option value="<%#$this->Data['value']%>"><%#$this->Data['name']%></option>
								</prop:ItemTemplate>
							</com:TRepeater>
						</select>
					</div>
				</div>
				<div style="display: <%=$this->enable_global_tags ? 'block' : 'none'%>">
					<h4><%[ Accessibility ]%></h4>
					<div class="w3-margin-left">
						<div class="w3-row directive_field">
							<div class="w3-col">
								<input type="radio" id="tag_tools_access_local_<%=$this->ClientID%>" name="tag_tools_access[<%=$this->ClientID%>]" value="<%=TagConfig::ACCESSIBILITY_LOCAL%>" class="w3-radio" checked />
								<label for="tag_tools_access_local_<%=$this->ClientID%>">&nbsp;<%[ Local tag - available only for you ]%></label>
							</div>
						</div>
						<div class="w3-row directive_field">
							<div class="w3-col">
								<input type="radio" id="tag_tools_access_global_<%=$this->ClientID%>" name="tag_tools_access[<%=$this->ClientID%>]" value="<%=TagConfig::ACCESSIBILITY_GLOBAL%>" class="w3-radio" />
								<label for="tag_tools_access_global_<%=$this->ClientID%>">&nbsp;<%[ Global tag - available for everybody ]%></label>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<footer class="w3-container w3-center w3-border-top">
			<div id="add_tags_create_btns_<%=$this->ClientID%>" style="display: none">
				<button type="button" class="w3-button w3-section w3-red" onclick="oTagTools_<%=$this->ClientID%>.show_create_tools(false);"><i class="fa fa-times"></i> &nbsp;<%[ Cancel ]%></button>
				<button type="button" class="w3-green w3-button" onclick="oTagTools_<%=$this->ClientID%>.create();">
					<i class="fa-solid fa-plus"></i> &nbsp;<%[ Create ]%>
				</button>
			</div>
		</footer>
	</div>
</div>
<com:TCallback ID="CreateTag" OnCallback="createTag" />
<com:TCallback ID="AssignTag" OnCallback="assignTag" />
<com:TCallback ID="UnassignTag" OnCallback="unassignTag" />
<script>
oTagInput_<%=$this->ClientID%> = {
	ids: {
		input: 'add_tags_input_<%=$this->ClientID%>',
		suggest: 'tag_tools_suggestions_<%=$this->ClientID%>',
		new_tag_item: 'tag_tools_suggestions_new_tag_<%=$this->ClientID%>',
		existing_tag_item: 'tag_tools_suggestions_existing_tags_<%=$this->ClientID%>'
	},
	keep_suggestions: false,
	add_tag_mode: false,
	init: function() {
		this.add_events();
		return this;
	},
	add_events: function() {
		const input = document.getElementById(this.ids.input);
		input.addEventListener('keydown', (e) => {
			if (e.key == 'Escape') {
				this.show_suggestions(false);
				input.blur();
				e.stopPropagation();
			}
		});
		input.addEventListener('keyup', (e) => {
			const txt = input.value;
			this.search(txt);
		});
		input.addEventListener('focus', (e) => {
			this.show_suggestions(true);
		});
		input.addEventListener('blur', (e) => {
			setTimeout(() => {
				if (!this.keep_suggestions) {
					this.show_suggestions(false);
				} else {
					this.set_focus();
				}
				this.keep_suggestions = false;
			}, 300);
		});
	},
	set_keep_suggestions_flag: function(val) {
		this.keep_suggestions = val;
	},
	enable_add_tag_mode: function(enable) {
		this.add_tag_mode = enable;
		this.set_keep_suggestions_flag(true);
		this.show_suggestions(!enable);
		this.set_placeholder(!enable);
	},
	set_focus: function() {
		const input = document.getElementById(this.ids.input);
		input.focus();
	},
	get_value: function() {
		const input = document.getElementById(this.ids.input);
		return input.value;
	},
	set_value: function(value) {
		const input = document.getElementById(this.ids.input);
		input.value = value;
	},
	show_suggestions: function(show) {
		if (this.add_tag_mode && show) {
			// In add tag mode, suggestions are hidden
			return;
		}
		const suggest = document.getElementById(this.ids.suggest);
		suggest.style.display = show ? 'block' : 'none';
	},
	set_placeholder: function(show) {
		const input = document.getElementById(this.ids.input);
		if (show) {
			input.setAttribute('placeholder', '<%[ Click here to set tags ]%>');
		} else {
			input.setAttribute('placeholder', '<%[ Type a tag name. ]%>');
		}
	},
	search: function(txt) {
		const suggest = document.getElementById(this.ids.suggest);
		const tags = suggest.querySelectorAll('.w3-tag');
		let label;
		const regex = new RegExp(txt, 'i');
		for (const tag of tags) {
			label = tag.getAttribute('data-tag');
			tag.style.display = regex.test(label) ? 'block' : 'none';
		}
	},
	validate: function() {
		const input = document.getElementById(this.ids.input);
		return input.reportValidity();
	}
};

oTagColorPalette_<%=$this->ClientID%> = {
	ids: {
		colors: 'tag_tools_colors_<%=$this->ClientiD%>'
	},
	palette: <%=json_encode($this->palette)%>,
	init: function() {
		this.add_events();
		this.set_default_color();
		return this;
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
	unmark_all_colors: function() {
		const container = document.getElementById(this.ids.colors);
		const colors = container.querySelectorAll('div[data-name]');
		for (const color of colors) {
			color.removeAttribute('data-selected');
			color.style.removeProperty('border');
		}
	},
	mark_color_el: function(el) {
		el.setAttribute('data-selected', 'true');
		el.style.border = '3px solid black'
	},
	set_default_color: function() {
		const container = document.getElementById(this.ids.colors);
		const colors = container.querySelectorAll('div[data-name]');
		if (colors.length > 0) {
			this.unmark_all_colors();
			this.mark_color_el(colors[0]);
		}
	},
	get_sel_color: function() {
		const container = document.getElementById(this.ids.colors);
		const color = container.querySelector('div[data-selected]');
		let ret = '';
		if (color) {
			ret = color.getAttribute('data-name');
		}
		return ret;
	},
	get_color: function(color) {
		let ret = {};
		if (this.palette.hasOwnProperty(color)) {
			ret = this.palette[color];
		}
		return ret;
	}
};

oTagSeverity_<%=$this->ClientID%> = {
	ids: {
		severity: 'tag_tools_severity_level_<%=$this->ClientID%>'
	},
	default_severity: 3,
	init: function() {
		this.set_default_severity();
		return this;
	},
	get_severity: function() {
		const severity = document.getElementById(this.ids.severity);
		return severity.value;
	},
	set_severity: function(value) {
		const severity = document.getElementById(this.ids.severity);
		severity.value = value;
	},
	set_default_severity: function() {
		this.set_severity(this.default_severity);
	}
};

oTagAccessibility_<%=$this->ClientID%> = {
	ids: {
		access_local: 'tag_tools_access_local_<%=$this->ClientID%>',
		access_global: 'tag_tools_access_global_<%=$this->ClientID%>'
	},
	init: function() {
		return this;
	},
	get_access: function() {
		const access_local = document.getElementById(this.ids.access_local);
		const access_global = document.getElementById(this.ids.access_global);
		let ret = '<%=TagConfig::ACCESSIBILITY_LOCAL%>'; // default is local
		if (access_local.checked) {
			ret = access_local.value;
		} else if (access_global.checked) {
			ret = access_global.value;
		}
		return ret;
	}
};

oTagTools_<%=$this->ClientID%> = {
	ids: {
		win: 'tag_tools_window_<%=$this->ClientID%>',
		title: 'tag_tools_title_<%=$this->ClientID%>',
		selected: 'add_tags_selected_<%=$this->ClientID%>',
		create_tools: 'tag_tools_create_tools_<%=$this->ClientID%>',
		create_btns: 'add_tags_create_btns_<%=$this->ClientID%>',
		add_btn: 'add_tags_add_btn_<%=$this->ClientID%>',
		error: 'add_tags_error_<%=$this->ClientID%>'
	},
	oname: 'oTagTools_<%=$this->ClientID%>',
	tags: <%=json_encode($this->tags)%>,
	tag_assign: <%=json_encode($this->tag_assign)%>,
	selected: [],
	element: {},
	init: function(props) {
		this.props = props;
		this.init_tags();
		this.init_tag_assign();
	},
	init_tags: function() {
		for (const tag in this.tags) {
			this.tags[tag].color_vals = this.props.palette.get_color(this.tags[tag].color);
		}
	},
	init_tag_assign: function() {
		for (const key in this.tag_assign) {
			this.tag_assign[key]['tag'] = this.tag_assign[key]['tag'].map((tag) => this.get_tag_props(tag));
		}
	},
	update_tags: function(tags) {
		const self = oTagTools_<%=$this->ClientID%>;
		self.tags = tags;
		self.init_tags();
	},
	update_tag_assign: function(tag_assign) {
		const self = oTagTools_<%=$this->ClientID%>;
		self.tag_assign = tag_assign;
		self.init_tag_assign();
	},
	open: function(id, value, table) {
		this.set_element(id, value, table);
		this.set_title(value);
		this.selected = this.get_tags(id, value, true);
		this.create_selected_preview();
		this.show_window(true);
	},
	get_tags: function(id, value, simple) {
		let ret = [];
		const key = id + '_' + value;
		if (this.tag_assign.hasOwnProperty(key)) {
			ret = this.tag_assign[key]['tag'];
		}
		if (simple) {
			ret = ret.map((tag) => tag.tag);
		}
		return ret;
	},
	get_tags_search: function(id, value) {
		let tags = this.get_tags(id, value);
		return tags.map((tag) => '%' + Tag.get_severity_desc(tag.severity) + '#' + tag.tag);
	},
	get_tags_sort: function(id, value) {
		let tags = this.get_tags(id, value);
		return tags.map((tag) => '#' + tag.tag);
	},
	get_tag_props: function(tag) {
		return (this.tags[tag] || '');
	},
	show_window: function(show) {
		const self = oTagTools_<%=$this->ClientID%>;
		const win = document.getElementById(self.ids.win);
		win.style.display = show ? 'block' : 'none';
	},
	set_title: function(title) {
		const container = document.getElementById(this.ids.title);
		container.textContent = title;
	},
	set_element: function(id, value, table) {
		this.element = {
			id: id,
			value: value,
			table: table
		};
	},
	create: function() {
		if (!this.props.input.validate()) {
			return false;
		}
		const tag = this.props.input.get_value();
		const color = this.props.palette.get_sel_color();
		const severity = this.props.severity.get_severity();
		const access = this.props.accessibility.get_access();
		const data = {
			tag: tag,
			color: color,
			color_vals: this.props.palette.get_color(color),
			severity: severity,
			access: access
		};
		this.clear_error();
		if (this.tag_exists(tag)) {
			this.error('<%[ Tag already exists. ]%>');
			return false;
		} else if (!tag) {
			this.error('<%[ Please type new tag name. ]%>');
			return false;
		}
		if (tag) {
			const cb = <%=$this->CreateTag->ActiveControl->Javascript%>;
			cb.setCallbackParameter(data);
			cb.dispatch();

			// hide create tools box
			this.show_create_tools(false);

			// add tag to tag list
			this.tags[tag] = data;

			// add tag to selected
			this.mark_selected(tag);

			// clear the input value
			this.props.input.set_value('');
		}
	},
	tag_exists: function(tag) {
		const props = this.get_tag_props(tag);
		return (typeof(props) == 'object');
	},
	assign: function() {
		/*
		if (this.selected.length == 0) {
			this.error('<%[ Please select tag to assign. ]%>');
			return false;
		}
		*/
		const data = {
			tags: this.selected.map((tag) => this.get_tag_props(tag)),
			id: this.element.id,
			value: this.element.value
		};

		const cb = <%=$this->AssignTag->ActiveControl->Javascript%>;
		cb.setCallbackParameter(data);
		cb.dispatch();
	},
	on_assign_success: function() {
		const self = oTagTools_<%=$this->ClientID%>;
		self.show_window(false);
		if (self.element.table) {
			self.element.table.rows().invalidate('data').draw(false);
		}
	},
	unassign: function(id, value, tag, sender, table) {
		this.set_element(id, value, table);
		const data = {
			tag: this.get_tag_props(tag),
			id: id,
			value: value
		};
		const cb = <%=$this->UnassignTag->ActiveControl->Javascript%>;
		cb.setCallbackParameter(data);
		cb.dispatch();

		// remove tag HTML element in advance
		sender.parentNode.parentNode.removeChild(sender.parentNode);
	},
	on_unassign_success: function() {
		const self = oTagTools_<%=$this->ClientID%>;
		if (self.element.table) {
			self.element.table.rows().invalidate('data').draw(false);
		}
	},
	mark_selected: function(tag) {
		this.props.input.set_keep_suggestions_flag(true);
		if (this.selected.indexOf(tag) === -1) {
			this.selected.push(tag);
			this.create_selected_preview();
		}
	},
	unmark_selected: function(tag) {
		this.props.input.set_keep_suggestions_flag(true);
		const idx = this.selected.indexOf(tag);
		if (idx > -1) {
			this.selected.splice(idx, 1);
			this.create_selected_preview();
		}
	},
	create_selected_preview: function() {
		// first clear container
		this.clear_selected_preview();

		// next (re)create preview items
		const container = document.getElementById(this.ids.selected);
		let item, label, rm_btn, color;
		for (const sel of this.selected) {
			// tag definition
			item = document.createElement('DIV');
			item.classList.add('w3-left', 'btag');
			item.style.color = this.tags[sel].color_vals.fg;
			item.style.backgroundColor = this.tags[sel].color_vals.bg;
			item.style.padding = '4px 6px';

			// tag label
			label = document.createTextNode(this.tags[sel].tag);

			// tag remove (unselect) button
			rm_btn = document.createElement('I');
			rm_btn.classList.add('fa-solid', 'fa-times', 'pointer');
			rm_btn.style.marginRight = '6px';
			rm_btn.addEventListener('click', (e) => {
				this.unmark_selected(sel);
			});

			// create tag element
			item.appendChild(rm_btn);
			item.appendChild(label);
			container.appendChild(item);
		}
		if (this.selected.length == 0) {
			const empty = document.createTextNode('-');
			container.appendChild(empty);
		}
	},
	clear_selected_preview: function() {
		const container = document.getElementById(this.ids.selected);
		while (container.firstChild) {
			container.removeChild(container.firstChild);
		}
	},
	show_create_tools: function(show) {
		// create tag btn
		const create_btns = document.getElementById(this.ids.create_btns);
		create_btns.style.display = show ? 'block' : 'none';

		// add tags btn
		const add_btn = document.getElementById(this.ids.add_btn);
		add_btn.style.display = !show ? 'inline-block' : 'none';

		// create tag tools
		const tools = document.getElementById(this.ids.create_tools);
		if (show) {
			$(tools).slideDown();
		} else {
			$(tools).slideUp();
			this.clear_error();
		}

		// set input focus
		//this.props.input.set_value('');
		this.props.input.set_focus();
		this.props.input.enable_add_tag_mode(show);
	},
	error: function(msg) {
		const container = document.getElementById(this.ids.error);
		container.textContent = msg;
		if (msg) {
			container.style.display = 'block';
		} else {
			container.style.display = 'none';
		}
	},
	clear_error: function() {
		this.error('');
	}
};
$(function() {
	const input = oTagInput_<%=$this->ClientID%>.init();
	const palette = oTagColorPalette_<%=$this->ClientID%>.init();
	const severity = oTagSeverity_<%=$this->ClientID%>.init();
	const accessibility = oTagAccessibility_<%=$this->ClientID%>.init();
	oTagTools_<%=$this->ClientID%>.init({
		input: input,
		palette: palette,
		severity: severity,
		accessibility: accessibility
	});
});
</script>
