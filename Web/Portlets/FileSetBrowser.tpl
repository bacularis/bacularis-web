<div class="w3-container">
	<div class="directive_field w3-container">
		<div class="w3-third"><com:TLabel ForControl="Client" Text="<%[ Client: ]%>" /></div>
		<div class="w3-third">
			<com:TActiveDropDownList
				ID="Client"
				CssClass="w3-select w3-border"
				Width="350px"
				CausesValidation="false"
				OnSelectedIndexChanged="selectClient"
				ClientSide.OnLoading="oFileSetBrowser<%=$this->ClientID%>.show_file_loader(true)"
				ClientSide.OnComplete="oFileSetBrowser<%=$this->ClientID%>.show_file_loader(false)"
				>
			</com:TActiveDropDownList>
		</div>
	</div>
	<p><%[ To browse Windows host please type in text field below drive letter as path, for example: C:/ ]%></p>
	<div class="w3-section w3-half">
		<input type="text" id="<%=$this->ClientID%>fileset_browser_path" class="w3-input w3-twothird w3-border" placeholder="<%[ Go to path ]%>" onkeypress="var k = event.which || event.keyCode; if (k == 13) { oFileSetBrowser<%=$this->ClientID%>.ls_items(document.getElementById('<%=$this->ClientID%>fileset_browser_path').value); }" />
		<button type="button" class="w3-button w3-green" onclick="oFileSetBrowser<%=$this->ClientID%>.ls_items(document.getElementById('<%=$this->ClientID%>fileset_browser_path').value);"><i class="fa fa-check"></i> &nbsp;<%[ OK ]%></button>
		<span id="<%=$this->ClientID%>fileset_browser_file_loader" style="display: none"><i class="fa fa-sync-alt w3-spin"></i></span>
	</div>
	<div class="w3-section w3-half">
		<input type="text" id="<%=$this->ClientID%>fileset_browser_add_include_path" class="w3-input w3-twothird w3-border w3-margin-left" placeholder="<%[ Add new include path ]%>" onkeypress="oFileSetBrowser<%=$this->ClientID%>.add_include_path_by_input(event);" autocomplete="off" />
		<button type="button" class="w3-button w3-green" onclick="oFileSetBrowser<%=$this->ClientID%>.add_include_path();"><i class="fa fa-plus"></i> &nbsp;<%[ Add ]%></button>
	</div>
	<div id="<%=$this->ClientID%>fileset_browser_file_container" class="w3-container w3-half w3-border fileset_browser_file_container"></div>
	<div id="<%=$this->ClientID%>fileset_browser_include_container" class="w3-container w3-half w3-border fileset_browser_include_container"></div>
	<div class="w3-section w3-half">
		<input type="text" id="<%=$this->ClientID%>fileset_browser_add_exclude_path" class="w3-input w3-twothird w3-border w3-margin-left" placeholder="<%[ Add new global exclude path ]%>" onkeypress="oFileSetBrowser<%=$this->ClientID%>.add_exclude_path_by_input(event);" autocomplete="off" />
		<button type="button" class="w3-button w3-green" onclick="oFileSetBrowser<%=$this->ClientID%>.add_exclude_path();"><i class="fa fa-plus"></i> &nbsp;<%[ Add ]%></button>
	</div>
	<div id="<%=$this->ClientID%>fileset_browser_exclude_container" class="w3-container w3-half w3-border fileset_browser_exclude_container"></div>
	<com:TCallback
		ID="FileSetBrowserFiles"
		OnCallback="TemplateControl.getItems"
		ClientSide.OnLoading="oFileSetBrowser<%=$this->ClientID%>.show_file_loader(true)"
		ClientSide.OnComplete="oFileSetBrowser<%=$this->ClientID%>.show_file_loader(false)"
	/>
</div>
<script type="text/javascript">
var oFileSetBrowser<%=$this->ClientID%> = {
	file_content: null,
	include_content: null,
	path_field: null,
	path: [],
	ids: {
		file_container: '<%=$this->ClientID%>fileset_browser_file_container',
		include_container: '<%=$this->ClientID%>fileset_browser_include_container',
		exclude_container: '<%=$this->ClientID%>fileset_browser_exclude_container',
		path_field: '<%=$this->ClientID%>fileset_browser_path',
		add_include_path_field: '<%=$this->ClientID%>fileset_browser_add_include_path',
		add_exclude_path_field: '<%=$this->ClientID%>fileset_browser_add_exclude_path',
		file_loader: '<%=$this->ClientID%>fileset_browser_file_loader'
	},
	css: {
		item: 'item',
		item_included: 'item_included w3-medium',
		item_excluded: 'item_excluded w3-medium',
		item_inc_exc_btn: 'item_inc_exc_btn w3-medium',
		item_selected_del_btn: 'item_selected_del_btn',
		item_name: 'item_name w3-medium',
		dir_img: 'fas fa-folder w3-text-green item_icon',
		file_img: 'fas fa-file-alt w3-text-gray item_icon',
		link_img: 'fas fa-link w3-text-gray item_icon'
	},
	init: function() {
		this.file_content = document.getElementById(this.ids.file_container);
		this.include_content = document.getElementById(this.ids.include_container);
		this.exclude_content = document.getElementById(this.ids.exclude_container);
		this.path_field = document.getElementById(this.ids.path_field);
		this.file_loader = document.getElementById(this.ids.file_loader);
		this.make_droppable();
	},
	reset: function() {
		this.clear_content();
		this.clear_includes();
		this.path_field.value = '';
		document.getElementById('<%=$this->Client->ClientID%>').value = 'none';
	},
	ls_items: function(path) {
		var dpath;
		if (path) {
			this.set_path(path);
			dpath = path;
		} else {
			dpath = this.get_path();
		}
		var request = <%=$this->FileSetBrowserFiles->ActiveControl->Javascript%>;
		request.setCallbackParameter(dpath);
		request.dispatch();
	},
	set_content: function(content) {
		this.clear_content();

		var items = JSON.parse(content);
		items.sort(function(a, b) {
			if (a.type === 'd' && b.type !== 'd') {
				return -1;
			} else if (a.type !== 'd' && b.type === 'd') {
				return 1;
			}
			return a.item.localeCompare(b.item, undefined, { numeric: true, sensitivity: 'base' });
		});

		for (var i = 0; i < items.length; i++) {
			this.set_item(items[i]);
		}
		this.make_draggable();
	},
	clear_content: function() {
		while (this.file_content.firstChild) {
			this.file_content.removeChild(this.file_content.firstChild);
		}
	},
	make_draggable: function() {
		$('.' + this.css.item).draggable({
			helper: "clone"
		});
	},
	make_droppable: function() {
		$('#' + this.ids.include_container).droppable({
			accept: '.' + this.css.item,
			drop: function(e, ui) {
				var path = ui.helper[0].getAttribute('rel');
				this.add_include(path);
			}.bind(this)
		});
		$('#' + this.ids.exclude_container).droppable({
			accept: '.' + this.css.item,
			drop: function(e, ui) {
				var path = ui.helper[0].getAttribute('rel');
				this.add_exclude(path);
			}.bind(this)
		});
	},
	set_item: function(item) {
		var path = this.get_path();
		var base_path_pattern = new RegExp('^' + this.esc_path_spec_chars(path) + '/?');
		var win_path_pattern = new RegExp('^[A-Z]:/', 'i');
		var win_rootpath_pattern = new RegExp('^[A-Z]:/$', 'i');
		var item_name = item.item;
		if (item_name.substr(0, 1) !== '/' && win_path_pattern.test(item_name) == false) {
			item_name += '/';
		}

		if (item_name !== path) {
			item_name = item.item.replace(base_path_pattern, '');
		} else {
			item_name = '.';
		}
		var el = document.createElement('DIV');
		el.className = this.css.item;
		el.setAttribute('rel', item.item);
		var title = item_name;
		var img = document.createElement('I');
		if (item.type === 'd') {
			img.className = this.css.dir_img;
			el.addEventListener('click', function(e) {
				var path = el.getAttribute('rel');
				this.set_path(path);
				this.ls_items(path);
			}.bind(this));
		} else if (item.type === '-') {
			img.className = this.css.file_img;
		} else if (item.type === 'l') {
			img.className = this.css.link_img;
			title = item_name + item.dest;
		}
		var name = document.createElement('DIV');
		name.className  = this.css.item_name;
		name.textContent = item_name;

		var include_btn = document.createElement('A');
		include_btn.className = this.css.item_inc_exc_btn;
		var include_btn_txt = document.createTextNode('<%[ Include ]%>');
		include_btn.appendChild(include_btn_txt);
		include_btn.addEventListener('click', function(e) {
			e.stopPropagation();
			this.add_include(item.item);
			return false;
		}.bind(this));

		var exclude_btn = document.createElement('A');
		exclude_btn.className = this.css.item_inc_exc_btn;
		var exclude_btn_txt = document.createTextNode('<%[ Exclude ]%>');
		exclude_btn.appendChild(exclude_btn_txt);
		exclude_btn.addEventListener('click', function(e) {
			e.stopPropagation();
			this.add_exclude(item.item);
			return false;
		}.bind(this));

		el.setAttribute('title',title);

		el.appendChild(img);
		el.appendChild(name);
		el.appendChild(exclude_btn);
		el.appendChild(include_btn);
		this.file_content.appendChild(el);

		if (item_name === '.' && path !== '/' && win_rootpath_pattern.test(path) == false) {
			this.set_special_items();
		}
	},
	set_special_items: function() {
		var item_name = '..';
		var el = document.createElement('DIV');
		el.className = this.css.item;
		el.setAttribute('rel', item_name);
		var img = document.createElement('I');
		img.className = this.css.dir_img;
		var name = document.createElement('DIV');
		name.className  = this.css.item_name;
		name.textContent = item_name;

		el.addEventListener('click', function(e) {
			var path = el.getAttribute('rel');
			this.set_path(path);
			this.ls_items();
		}.bind(this));

		el.setAttribute('title', item_name);

		el.appendChild(img);
		el.appendChild(name);
		this.file_content.appendChild(el);
	},
	get_includes: function() {
		var container = document.getElementById(this.ids.include_container);
		var inc_elements = container.querySelectorAll('div.' + this.css.item_included.replace(/ /g, '.'));
		var includes = [];
		for (var i = 0; i < inc_elements.length; i++) {
			includes.push(inc_elements[i].getAttribute('rel'));
		}
		return includes;
	},
	get_excludes: function() {
		var container = document.getElementById(this.ids.exclude_container);
		var exc_elements = container.querySelectorAll('div.' + this.css.item_excluded.replace(/ /g, '.'));
		var excludes = [];
		for (var i = 0; i < exc_elements.length; i++) {
			excludes.push(exc_elements[i].getAttribute('rel'));
		}
		return excludes;
	},
	add_include: function(item) {
		var el = document.createElement('DIV');
		el.className = this.css.item_included;
		el.setAttribute('rel', item);
		var name = document.createElement('DIV');
		name.textContent = item;
		var del_btn_container = document.createElement('DIV');
		var del_btn = document.createElement('I');
		del_btn.className = 'fa fa-trash-alt item_selected_del_btn';
		del_btn_container.appendChild(del_btn);
		del_btn_container.addEventListener('click', function(e) {
			el.parentNode.removeChild(el);
		})
		el.appendChild(del_btn_container);
		el.appendChild(name);
		this.include_content.appendChild(el);
	},
	add_exclude: function(item) {
		var el = document.createElement('DIV');
		el.className = this.css.item_excluded;
		el.setAttribute('rel', item);
		var name = document.createElement('DIV');
		name.textContent = item;
		var del_btn_container = document.createElement('DIV');
		var del_btn = document.createElement('I');
		del_btn.className = 'fa fa-trash-alt item_selected_del_btn';
		del_btn_container.appendChild(del_btn);
		del_btn_container.addEventListener('click', function(e) {
			el.parentNode.removeChild(el);
		})
		el.appendChild(del_btn_container);
		el.appendChild(name);
		this.exclude_content.appendChild(el);
	},
	clear_includes: function() {
		while (this.include_content.firstChild) {
			this.include_content.removeChild(this.include_content.firstChild);
		}
	},
	clear_excludes: function() {
		while (this.exclude_content.firstChild) {
			this.exclude_content.removeChild(this.exclude_content.firstChild);
		}
	},
	set_path: function(item) {
		var path = item.split('/');
		if (path.length === 1) {
			if (item === '..') {
				this.path.pop();
			} else if (/^[A-Z]:$/i.test(path)) {
				this.path = path;
			} else {
				this.path.push(item);
			}
		} else {
			this.path = path;
		}
		this.path_field.value = this.get_path();
	},
	get_path: function() {
		var path = this.path.join('/');
		if (!path || /^[A-Z]:$/i.test(path)) {
			path += '/';
		}
		return path;
	},
	add_include_path_by_input: function(e) {
		var evt = e || window.event;
		var key_code = evt.keyCode || evt.which;
		if (key_code === 13) {
			this.add_include_path();
		}
	},
	add_include_path: function() {
		var el = document.getElementById(this.ids.add_include_path_field);
		if (el.value) {
			this.add_include(el.value);
			el.value = '';
			el.focus();
		}
	},
	add_exclude_path_by_input: function(e) {
		var evt = e || window.event;
		var key_code = evt.keyCode || evt.which;
		if (key_code === 13) {
			this.add_exclude_path();
		}
	},
	add_exclude_path: function() {
		var el = document.getElementById(this.ids.add_exclude_path_field);
		if (el.value) {
			this.add_exclude(el.value);
			el.value = '';
			el.focus();
		}
	},
	show_file_loader: function(show) {
		if (show) {
			this.file_loader.style.display = '';
		} else {
			this.file_loader.style.display = 'none';
		}
	},
	esc_path_spec_chars: function(path) {
		return path.replace('$', '\\$');
	}
};

function FileSetBrowser_set_content<%=$this->ClientID%>(content) {
	oFileSetBrowser<%=$this->ClientID%>.set_content(content);
}
$(function() {
	oFileSetBrowser<%=$this->ClientID%>.init();
});
</script>
