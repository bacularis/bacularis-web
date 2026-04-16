<span>
	<button type="button" id="bsearch_field" class="w3-white w3-border w3-button" onclick="oBSearchField.show_form(true);">
		<i class="fa-solid fa-magnifying-glass"></i>
		<span class="label"><%[ Search ]%>...</span>
		<span class="shortcut">
			<kbd>Ctrl</kbd>
			+
			<kbd>K</kbd>
		</span>
	</button>
</span>
<div id="bsearch_form" class="w3-modal" style="display: none">
	<div class="w3-modal-content w3-animate-fading" style="animation: opac 0.5s;">
		<div>
			<span>
				<input type="text" id="bsearch_text" placeholder="<%[ Type to search ]%>..." />
				<span id="bsearch_form_loader" style="display: none;">
					<i class="fa-solid fa-sync-alt fa-xl w3-spin"></i>
				</span>
				<span id="bsearch_form_clear" class="pointer">
					<i class="fa-solid fa-xmark fa-xl"></i>
				</span>
			</span>
			<span id="bsearch_form_submit" class="w3-badge w3-border">
				<i class="fa-solid fa-magnifying-glass fa-xl fa-fw"></i>
			</span>
			<div class="w3-dropdown-hover">
				<button id="bsearch_categories" type="button" class="w3-button"><%[ Categories ]%></button>
				<div class="w3-dropdown-content w3-bar-block w3-border">
					<com:TRepeater ID="SearchCategories">
						<prop:ItemTemplate>
							<a href="javascript:void(0)" class="w3-bar-item w3-button" data-category="<%#$this->Data['name']%>" data-default="<%#$this->Data['default'] ? '1' : '0'%>" onclick="oBSearchField.toggle_category(this);"><i class="<%#$this->Data['icon_css']%>"></i> &nbsp;<%#$this->Data['name']%></a>
						</prop:ItemTemplate>
					</com:TRepeater>
				</div>
			</div>
		</div>
		<div id="bsearch_last_search"></div>
	</div>
	<div id="bsearch_results" class="w3-modal-content w3-animate-zoom">
	</div>
	<div id="bsearch_scroll_down" class="w3-modal-content w3-animate-bottom">
		<span>
			<i class="fas fa-exchange-alt fa-rotate-90 fa-fw"></i> <%[ Scroll up/down ]%>
		</span>
	</div>
</div>
<com:TCallback ID="SearchResource" OnCallback="searchResource" />
<script>
var oBSearchField = {
	categories: [],
	last_search_len: 5,
	ids: {
		sform: 'bsearch_form',
		stext: 'bsearch_text',
		results: 'bsearch_results',
		loader: 'bsearch_form_loader',
		submit: 'bsearch_form_submit',
		clear: 'bsearch_form_clear',
		last: 'bsearch_last_search',
		categories: 'bsearch_categories',
		scroll: 'bsearch_scroll_down',
		storage: 'bacularis-search',
	},
	init: function() {
		this.add_events();
		this.init_user_bsearch();
	},
	add_events: function() {
		// Main shortcut to show search form
		window.addEventListener('keydown', (e) => {
			// if (CTRL + K) or (⌘ + K) is pressed and (ALT and SHIFT) are not pressed. This is case-insensitive (works also if CAPSLOCK is enabled)
			if ((((e.ctrlKey && !e.metaKey) || (!e.ctrlKey && e.metaKey)) && (!e.shiftKey && !e.altKey)) && /^k$/i.test(e.key)) {
				const show = !this.is_form_displayed();
				this.show_form(show);
				e.preventDefault();
			}
		});


		// Form search text field
		const stext = document.getElementById(this.ids.stext);
		stext.addEventListener('keydown', (e) => {
			if ((e.key === 'Enter' || e.keyCode === 13)) {
				this.search_by_categories();
				e.preventDefault();
			}
		});

		// Submit form button
		const submit = document.getElementById(this.ids.submit);
		submit.addEventListener('click', (e) => {
			this.search_by_categories();
		});


		// Clear form button
		const clear = document.getElementById(this.ids.clear);
		clear.addEventListener('click', (e) => {
			this.clear_form();
			this.clear_results();
		});
	},
	show_form: function(show) {
		if (show) {
			this.clear_form();
			this.clear_results();
			this.update_last_search();
		}
		const sform = document.getElementById(this.ids.sform);
		sform.style.display = show ? 'flex' : 'none';
		const stext = document.getElementById(this.ids.stext);
		stext.focus();
	},
	is_form_displayed: function() {
		const sform = document.getElementById(this.ids.sform);
		return (sform.style.display == 'flex');
	},
	show_loader: function(show) {
		const loader = document.getElementById(this.ids.loader);
		loader.style.display = show ? 'inline-block' : 'none';
	},
	show_clear: function(show) {
		const clear = document.getElementById(this.ids.clear);
		clear.style.display = show ? 'inline-block' : 'none';
	},
	init_user_bsearch: function() {
		this.set_category_defaults();
	},
	is_user_bsearch: function() {
		return  (window.localStorage.getItem(this.ids.storage) !== null);
	},
	get_user_bsearch: function() {
		const def_bsearch = '{"last_search": [], "categories": []}';
		const bsearch = window.localStorage.getItem(this.ids.storage) || def_bsearch;
		return JSON.parse(bsearch);
	},
	set_user_bsearch: function(data) {
		const bsearch = JSON.stringify(data);
		window.localStorage.setItem(this.ids.storage, bsearch);
	},
	toggle_category: function(el) {
		const category = el.getAttribute('data-category');
		const bsearch = this.get_user_bsearch();
		const enabled = bsearch.categories.indexOf(category) > -1;
		this.set_category_element(el, !enabled);
	},
	sort_categories: function(categories) {
		const cats = document.querySelectorAll('#' + this.ids.categories + ' + div a');
		const cats_order = [];
		let category;
		for (const el of cats) {
			category = el.getAttribute('data-category');
			cats_order.push(category);
		}
		categories.sort((a, b) => {
			if (cats_order.indexOf(a) > cats_order.indexOf(b)) {
				return 1;
			} else if (cats_order.indexOf(a) < cats_order.indexOf(b)) {
				return -1;
			}
			return 0;
		});
	},
	set_category_defaults: function() {
		const sform = document.getElementById(this.ids.sform);
		const categories = sform.querySelectorAll('a.w3-bar-item');
		const bsearch = this.get_user_bsearch();
		const is_bsearch = this.is_user_bsearch();
		let category, enabled;
		for (const el of categories) {
			if (is_bsearch) {
				category = el.getAttribute('data-category');
				enabled = bsearch.categories.indexOf(category) > -1;
			} else {
				enabled = el.getAttribute('data-default') == 1;
			}
			this.set_category_element(el, enabled);
		}
	},
	set_category_element: function(el, enable) {
		const bsearch = this.get_user_bsearch();
		const category = el.getAttribute('data-category');
		const index = bsearch.categories.indexOf(category);
		let img;
		if (enable) {
			if (index == -1) {
				bsearch.categories.push(category);
			}
			img = document.createElement('I');
			img.classList.add('fa-solid', 'fa-check', 'w3-right');
			el.appendChild(img);
		} else if (!enable && index > -1) {
			bsearch.categories.splice(index, 1);
			img = el.querySelectorAll('i');
			if (img.length > 1) {
				el.removeChild(img[1]);
			}
		}
		this.set_user_bsearch(bsearch);
	},
	search_by_categories: function() {
		const keyword = document.getElementById(this.ids.stext);
		if (this.categories.length == 0) {
			// start searching - begining
			if (!keyword.value.trim()) {
				// empty keyword - end
				return;
			}
			this.show_clear(false);
			this.show_loader(true);
			this.clear_results();
			const bsearch = this.get_user_bsearch();
			this.categories = bsearch.categories;
			this.sort_categories(this.categories);
			this.add_to_last_search(keyword.value);
		}
		const category = this.categories.shift();
		this.search(category, keyword.value);
	},
	search: function(category, keyword) {
		const cb = <%=$this->SearchResource->ActiveControl->Javascript%>;
		cb.setCallbackParameter([category, keyword]);
		cb.dispatch();
	},
	search_cb: function(category, result) {
		const self = oBSearchField;
		self.add_results(category, result);
		if (self.categories.length > 0) {
			// get next part of results
			self.search_by_categories();
		} else {
			// searching ended
			if (!self.is_result()) {
				self.add_no_result_found();
			}
			self.show_loader(false);
			self.show_clear(true);
		}
	},
	is_result: function() {
		const container = document.getElementById(this.ids.results);
		return (container.firstChild instanceof HTMLElement);
	},
	add_results: function(category, results) {
		const container = document.getElementById(this.ids.results);
		const ul = document.createElement('UL');
		ul.classList.add('w3-ul');
		ul.style.width = '620px';
		for (const result of results) {
			result.values.unshift({name: 'Category', value: category});
			this.add_result(ul, result);
		}
		if (results.length > 0) {
			container.appendChild(ul);
			this.show_scroll_down();
		}
	},
	add_result: function(container, result) {
		const li = document.createElement('LI');
		li.classList.add('w3-bar');
		const a = document.createElement('A');
		// decode entities
		const page = $("<div/>").html(result.page).text();
		a.href = page;
		a.style.display = 'flex';
		a.style.padding = '0';
		const span_icon = document.createElement('SPAN');
		span_icon.style.marginTop = '5px';
		span_icon.classList.add('w3-bar-item');
		const img_icon = document.createElement('I');
		img_icon.className = 'fa-2xl ' + result.icon_css;
		img_icon.style.verticalAlign = 'bottom';
		const div = document.createElement('DIV');
		div.classList.add('w3-bar-item');
		div.style.flexGrow = '3';
		const span_name = document.createElement('SPAN');
		span_name.classList.add('w3-large');
		span_name.textContent = result.name;
		const br = document.createElement('BR');
		const span_desc = document.createElement('SPAN');
		span_desc.textContent = result.values.map((item) => {
			return [item.name, item.value].join(': ');
		}).join(', ');
		const span_go = document.createElement('SPAN');
		span_go.classList.add('w3-bar-item', 'w3-button', 'w3-xlarge');
		const img_go = document.createElement('I');
		img_go.classList.add('fa-solid', 'fa-chevron-right');
		img_go.style.verticalAlign = 'bottom';

		div.appendChild(span_name);
		div.appendChild(br);
		div.appendChild(span_desc);
		span_icon.appendChild(img_icon);
		span_go.appendChild(img_go);
		a.appendChild(span_icon);
		a.appendChild(div);
		a.appendChild(span_go);
		li.appendChild(a);
		container.appendChild(li);
	},
	add_no_result_found: function() {
		const container = document.getElementById(this.ids.results);
		const ul = document.createElement('UL');
		ul.classList.add('w3-ul');
		ul.style.width = '620px';
		ul.style.marginTop = '16px';
		const li = document.createElement('LI');
		li.classList.add('w3-bar');
		const span_icon = document.createElement('SPAN');
		span_icon.classList.add('w3-bar-item');
		span_icon.style.marginTop = '3px';
		const img_icon = document.createElement('I');
		img_icon.className = 'fa-xl fa-solid fa-circle-xmark fa-fw';
		const div = document.createElement('DIV');
		div.classList.add('w3-bar-item');
		const span_name = document.createElement('SPAN');
		span_name.classList.add('w3-large');
		span_name.textContent = '<%[ No result found. ]%>';

		div.appendChild(span_name);
		span_icon.appendChild(img_icon);
		li.appendChild(span_icon);
		li.appendChild(div);
		container.appendChild(li);
		ul.appendChild(li);
		container.appendChild(ul);
	},
	show_scroll_down: function() {
		const container = document.getElementById(this.ids.results);
		const scroll = document.getElementById(this.ids.scroll);
		if (container.scrollHeight > container.clientHeight) {
			scroll.style.display = 'flex';
		} else {
			scroll.style.display = 'none';
		}
	},
	clear_results: function() {
		const container = document.getElementById(this.ids.results);
		while (container.firstChild) {
			container.removeChild(container.firstChild);
		}
		this.show_scroll_down();
	},
	set_form: function(keyword, focus) {
		const stext = document.getElementById(this.ids.stext);
		stext.value = keyword;
		if (focus) {
			stext.focus();
		}
	},
	clear_form: function() {
		this.set_form('', true);
	},
	add_to_last_search: function(keyword) {
		const bsearch = this.get_user_bsearch();
		const index = bsearch.last_search.indexOf(keyword);
		if (index > -1) {
			// item exists in last search, remove it first
			bsearch.last_search.splice(index, 1);
		}
		if (bsearch.last_search.length >= this.last_search_len) {
			// last search is full, delete one item
			bsearch.last_search.shift();
		}
		bsearch.last_search.push(keyword);
		this.set_user_bsearch(bsearch);
		this.update_last_search();
	},
	update_last_search: function() {
		this.clear_last_search();
		const last = document.getElementById(this.ids.last);
		const label = document.createElement('SPAN');
		label.textContent = '<%[ Recent ]%>:';
		label.classList.add('bold');
		last.appendChild(label);
		const bsearch = this.get_user_bsearch();
		let span;
		const ls_kw_func = (span, kw) => {
			span.addEventListener('click', (e) => {
				this.set_form(kw);
				this.search_by_categories();
			});
		};
		const kw_list = bsearch.last_search.reverse();
		for (const keyword of kw_list) {
			span = document.createElement('SPAN');
			span.classList.add('pointer');
			span.textContent = keyword;
			last.appendChild(span);
			ls_kw_func(span, keyword);
		}
	},
	clear_last_search: function() {
		const last = document.getElementById(this.ids.last);
		while (last.firstChild) {
			last.removeChild(last.firstChild);
		}
	}
};
oBSearchField.init();
</script>
