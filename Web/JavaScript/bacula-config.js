var BaculaConfigClass = jQuery.klass({
	show_item: function(element, show, after_finish) {
		var el = $(element);
		var duration;
		if (show === true && el.is(':visible') === false) {
			el.slideDown({duration: 400, complete: after_finish});
		} else if (show === false && el.is(':visible') === true) {
			el.slideUp({duration: 500, complete: after_finish});
		}
	},
	show_items: function(selector, show, callback) {
		$(selector).each(function(index, el) {
			var cb = function() {
				callback(el);
			}
			this.show_item(el, show, cb);
		}.bind(this));
	},
	show_new_config: function(sender, component_type, component_name, resource_type) {
		var container = $('div.config_directives[rel="' + sender + '"]');
		var h2 = container.find('h2');
		var text = h2[0].getAttribute('rel');
		text = text.replace('%component_type', component_type);
		text = text.replace('%component_name', component_name);
		text = text.replace('%resource_type', resource_type);
		h2[0].textContent = text;
		container.find('div.config_directives').show();
		this.show_item(container, true);
		oBaculaConfigSection.show_sections(true);
		this.scroll_to_element(container);
	},
	scroll_to_element: function(selector, additional_offset, oncomplete) {
		var offset = $(selector).offset().top;
		if (additional_offset) {
			offset += additional_offset;
		}
		const options = {
			scrollTop: offset
		};
		$('html,body,.w3-modal,.w3-sidebar').animate(options, 'slow', 'swing', oncomplete);
	},
	get_child_container: function(sender) {
		var child_container = $('#' + sender).closest('table').next('div');
		return child_container;
	},
	get_element_sender: function(el) {
		var element_sender = el.closest('table').prev('a');
		return element_sender;
	},
	set_config_items: function(id) {
		var child_container = this.get_child_container(id);
		var show = !child_container.is(':visible');
		this.show_item(child_container, show);
		this.loader_stop(id);
		/**
		 * This initialization has to be here because on Configure page buttons to save/cancel/delete
		 * resources are available after unfolding selected resource, not before.
		 * It means that without it these buttons are not adjusted well to the page.
		 */
		W3SideBar.init();
	},
	unset_config_items: function(id) {
		var child_container = this.get_child_container(id);
		var selector = [child_container[0].nodeName.toLowerCase(), child_container[0].className.replace(' ', '.')].join('.');
		var callback = function(el) {
			if (el.style.display === 'none' && !el.classList.contains('new_resource')) {
				// element closed
				var divs = el.getElementsByTagName('DIV');
				if (divs.length > 0) {
					var fc = divs[0];
					// clear by removing elements
					while (fc.firstChild) {
						fc.removeChild(fc.firstChild);
					}
				}
			}
		}
		this.show_items(selector, false, callback);
		/**
		 * Send request about items only if items are invisible to avoid
		 * asking about items when they are visible already.
		 */
		var send = !child_container.is(':visible');
		return send;
	},
	get_loader: function(id) {
		var loader = $('#' + id).next('I');
		return loader;
	},
	loader_start: function(id) {
		var loader = this.get_loader(id);
		$(loader).show();
	},
	loader_stop: function(id) {
		var loader = this.get_loader(id);
		$(loader).hide();
	}
});

var BaculaConfigOptionsClass = jQuery.klass({
	css: {
		options_container: 'directive_setting',
		directive_option: 'directive_option'
	},
	options_id: null,
	action_obj: null,
	initialize: function(opts) {
		if (opts.hasOwnProperty('options_id')) {
			this.options_id = opts.options_id;
		}
		if (opts.hasOwnProperty('action_obj')) {
			this.action_obj = opts.action_obj;
		}
		this.set_events();
	},
	set_events: function() {
		var element = document.getElementById(this.options_id);
		var opts = element.querySelectorAll('.' + this.css.directive_option);
		const do_action_cb = (el) => {
			el.addEventListener('click', () => {
				var action = el.getAttribute('rel');
				this.do_action(action);
			});
		};
		for (var i = 0; i < opts.length; i++) {
			do_action_cb(opts[i]);
		}
	},
	do_action: function(param) {
		if (typeof(this.action_obj) === "object") {
			this.action_obj.setCallbackParameter(param);
			this.action_obj.dispatch();
		}
	}
});

var oBaculaConfigSection = {
	sections: [],
	min_tab_sections: 2, // minimum number of sections to display tabs
	intersection_lock: false,
	css: {
		section_tabs: 'div[rel="section_tabs"]',
		section: 'h3.directive_section_header',
		directive_field: 'directive_field',
		subtab: 'subtab_btn',
		col: 'w3-col',
		small: 'w3-tiny',
		bottombar: 'w3-bottombar',
		border_red: 'w3-border-red',
		center: 'w3-center',
		white: 'w3-white',
		lgray: 'w3-light-gray',
		top: 'w3-top',
		modal: 'w3-modal',
		sidebar: 'w3-sidebar'
	},
	attrs: {
		data_section: 'data-section',
		data_section_subtab: 'data-section-subtab'
	},
	get_sections: function(root_el) {
		if (!root_el) {
			console.error('Missing section tab container.');
			return;
		}
		const sections = root_el.querySelectorAll(this.css.section);
		const sects = [];
		let dsect;
		for (let i = 0; i < sections.length; i++) {
			dsect = sections[i].getAttribute(this.attrs.data_section);
			sects.push(dsect);
		}
		return sects;
	},
	remove_subtab_selection: function() {
		// unmark previous selection
		$('div[' + this.attrs.data_section_subtab + ']').removeClass(this.css.border_red);
	},
	create_section_tabs: function(root_id) {
		this.clear_section_tabs(root_id);
		this.intersection_lock = true;
		const root_el = document.getElementById(root_id);
		const sections = this.get_sections(root_el);
		const sect_el = root_el.querySelector(this.css.section_tabs);
		if (sections.length < this.min_tab_sections) {
			sect_el.style.visibility = 'hidden';
			return;
		}
		sect_el.style.visibility = 'visible';
		sect_el.style.top = 0;

		// Determine top position basing on the top bar and if config form is in modal or not.
		const top = document.querySelector('.' + this.css.top);
		if (top) {
			sect_el.style.top = top.offsetHeight;
			let color;
			if ($(sect_el).closest('.' + this.css.modal).length == 1) {
				// in modal
				sect_el.style.top = '-' + (top.offsetHeight + 14) + 'px';
				color = ThemeMode.is_dark() ? ThemeMode.css.light_dark : this.css.white;
			} else if ($(sect_el).closest('.' + this.css.sidebar).length == 1) {
				// in sidebar
				sect_el.style.top = '35px';
				color = ThemeMode.is_dark() ? ThemeMode.css.light_dark : this.css.white;
			} else {
				// in non-modal
				sect_el.style.top = top.offsetHeight + 'px';
				color = ThemeMode.is_dark() ? ThemeMode.css.light_dark : this.css.lgray;
			}
			sect_el.classList.add(color);
		}
		const width = (99 / sections.length).toFixed(2);
		const self = this;

		// callback called after clicking section subtab
		const go_to_section = function(e) {
			self.remove_subtab_selection();

			// mark new selection
			this.classList.add(self.css.border_red);

			// prepare params (target, offset...) to scroll to selected section
			const section = e.target.getAttribute(self.attrs.data_section_subtab);
			const selector = '#' + root_id + ' h3[' + self.attrs.data_section  + '="' + section + '"]';
			const target = root_el.querySelector(selector);
			let offset = -50 - window.scrollY;
			const scroll_el = getClosestScrollEl(sect_el);
			if (scroll_el) {
				offset += scroll_el.scrollTop;
			}
			if (sect_el.style.position != 'fixed') {
				offset -= sect_el.clientHeight;
			}
			self.intersection_lock = true;
			const oncomplete = function() {
				this.intersection_lock = false;
			}.bind(self);
			BaculaConfig.scroll_to_element(target, offset, oncomplete);
		};

		// create section tabs
		let div, sect;
		for (let i = 0; i < sections.length; i++) {
			div = document.createElement('DIV');
			div.classList.add(
				this.css.subtab,
				this.css.col,
				this.css.small,
				this.css.bottombar,
				this.css.center
			);
			if (i == 0) {
				div.classList.add(this.css.border_red);
			}
			div.style.width = width + '%';
			div.style.padding = '5px 0 8px 0';
			div.style.cursor = 'pointer';
			div.style.whiteSpace = 'nowrap';
			sect = sections[i];
			if (sect.length > 19) {
				sect = sect.substr(0, 18) + '...';
			}
			div.title = sections[i];
			div.setAttribute(this.attrs.data_section_subtab, sections[i]);
			div.textContent = sect;
			div.addEventListener('click', go_to_section);
			sect_el.appendChild(div);
		}
		this.add_section_observer(sect_el);
		setTimeout(() => {
			this.intersection_lock = false;
		}, 500);
	},
	add_section_observer: function(el) {
		const io = new IntersectionObserver(entries => {
			entries.forEach((entry) => {
				if (entry.isIntersecting && !this.intersection_lock) {
					const section = entry.target.getAttribute(this.attrs.data_section);
					const selector = 'div[' + this.attrs.data_section_subtab + '="' + section + '"]';
					const section_btn = el.querySelector(selector);
					this.remove_subtab_selection();
					section_btn.classList.add(this.css.border_red);
				}
			})
		})

		const sections = document.querySelectorAll(this.css.section);
		sections.forEach((el) => {
			io.observe(el);
		});
	},
	clear_section_tabs: function(root_id) {
		const root_el = document.getElementById(root_id);
		const sect_el = root_el.querySelector(this.css.section_tabs);
		while (sect_el.firstChild) {
			sect_el.removeChild(sect_el.firstChild);
		}
		sect_el.style.visibility = 'hidden';
	},
	show_sections: function(show, root_id) {
		// this method has to be static
		$(function() {
			if (show) {
				if (root_id) {
					oBaculaConfigSection.create_section_tabs(root_id);
				}
				$(oBaculaConfigSection.css.section).show();
			} else {
				if (root_id) {
					oBaculaConfigSection.clear_section_tabs(root_id);
				}
				$(oBaculaConfigSection.css.section).hide();
			}
		});
	}
};

var BaculaConfig = new BaculaConfigClass();

var oDirectiveOrderedListBox = {
	init_items: function(select_id, hidden_id) {
		const select = document.getElementById(select_id);
		const hidden = document.getElementById(hidden_id);
		if (hidden.value) {
			const vals = hidden.value.split('!');
			OUTER:
			for (let i = 0; i < vals.length; i++) {
				INNER:
				for (let j = 0; j < select.options.length; j++) {
					if (select.options[j].value !== vals[i]) {
						continue INNER;
					}
					this.set_items(
						{
							target: select.options[j]
						},
						select,
						(i+1)
					);
					break INNER;
				}
			}
		}
		this.set_options(select_id, hidden_id);
	},
	set_items: function(event, select, fpos) {
		const el = event.target;
		const selected_len = select.querySelectorAll('select option:checked').length;
		let pos;
		let rm = false;
		if (el.selected) {
			pos = fpos || selected_len;
			el.setAttribute('data-pos', pos);
		} else {
			pos = el.getAttribute('data-pos');
			rm = true;
		}
		if (pos !== null) {
			this.set_list(select, pos, rm);
		}
	},
	set_list: function(select, pos, rm) {
		const els = select.querySelectorAll('option');
		for (let i = 0; i < els.length; i++) {
			epos = els[i].getAttribute('data-pos');
			if (epos === null) {
				continue;
			}
			els[i].textContent = els[i].textContent.replace(/^\[\d+\]\s/, '');
			if (els[i].selected) {
				// element selected
				if (rm) {
					if (epos > pos) {
						// requires update
						epos--;
						els[i].setAttribute('data-pos', epos);
					} else {
						// rest selected options stays untouched
					}
					els[i].textContent = '[' + epos + '] ' + els[i].textContent;
				} else {
					// selected elements
					els[i].textContent = '[' + epos + '] ' + els[i].textContent;
				}
			} else {
				// element not selected
				els[i].removeAttribute('data-pos');
			}
		}
	},
	set_options: function(select_id, hidden_id) {

		const opts = document.querySelectorAll('#' + select_id + ' option:checked');
		const hidden = document.getElementById(hidden_id);
		const vals = [];
		const els = Array.from(opts);
		els.sort((a, b) => {
			const aa = parseInt(a.getAttribute('data-pos'), 10);
			const ab = parseInt(b.getAttribute('data-pos'), 10);
			if (aa < ab) {
				return -1;
			} else if (aa > ab) {
				return 1;
			}
			return 0
		});
		for (let i = 0; i < els.length; i++) {
			vals.push(els[i].value);
		}
		hidden.value = vals.join('!');
	},
	clear_selection: function(select_id, hidden_id) {
		const select = document.getElementById(select_id);
		const opts = select.querySelectorAll('option:checked');
		for (let i = 0; i < opts.length; i++) {
			if (opts[i].selected) {
				opts[i].selected = false;
			}
		}
		this.set_list(select)
		this.set_options(select_id, hidden_id);
	}
};
