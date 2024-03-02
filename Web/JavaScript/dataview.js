class BaseType {
	constructor() {
		this.operators = {};
	}
	equal(a, b) {
		return a == b;
	}
	identical(a, b) {
		return a === b;
	}
	notequal(a, b) {
		return a != b;
	}
	notidentical(a, b) {
		return a !== b;
	}
	less(a, b) {
		return a < b;
	}
	lessequal(a, b) {
		return a <= b;
	}
	greater(a, b) {
		return a > b;
	}
	greaterequal(a, b) {
		return a >= b;
	}
	empty(a, b) {
		return (typeof(a) == 'string' && a.length == 0 || !a);
	}
	regexp(a, b) {
		const regexp = new RegExp(b);
		return regexp.test(a);
	}
	check(a, b, operator) {
		let ret = false;
		if (!Array.isArray(b)) {
			b = [b];
		}
		if (!Array.isArray(operator)) {
			operator = [operator];
		}
		let op;
		for (let i = 0; i < b.length; i++) {
			if (this.operators.hasOwnProperty(operator[i])) {
				op = this.operators[operator[i]];
				ret = op.fn(a, b[i]);
				if (ret) {
					// result found, stop here
					break;
				}
			} else {
				console.error('Unsupported operator:', operator);
			}
		}
		return ret;
	}
	set_operators(operators) {
		this.operators = operators;
	}
	get_operators() {
		return this.operators;
	}
}

class BaseDateType extends BaseType {
	check(a, b, operator) {
		const aa = iso_date_to_timestamp(a);
		const bb = b.map((t) => iso_date_to_timestamp(t));
		return super.check(aa, bb, operator);
	}
}

class StringType extends BaseType {
	constructor(prop) {
		super();
		this.prop = prop;
		const operators = {
			'==': {name: 'equal', fn: this.equal, field: 'select'},
			'!=': {name: 'not equal', fn: this.notequal, field: 'select'},
			'empty()': {name: 'empty value', fn: this.empty, field: 'none'},
			'regexp': {name: 'regular rexpression', fn: this.regexp, field: 'text'}
		};
		this.set_operators(operators);
	}
}

class BooleanType extends BaseType {
	constructor(prop) {
		super();
		this.prop = prop;
		this.operators = {
			'==': {name: 'equal', fn: this.equal, field: 'yesno'},
			'!=': {name: 'not equal', fn: this.notequal, field: 'yesno'}
		};
	}
}

class NumberType extends BaseType {
	constructor(prop) {
		super();
		this.prop = prop;
		this.operators = {
			'==': {name: 'equal', fn: this.equal, field: 'text'},
			'!=': {name: 'not equal', fn: this.notequal, field: 'text'},
			'<': {name: 'less than', fn: this.less, field: 'text'},
			'<=': {name: 'less than or equal to', fn: this.lessequal, field: 'text'},
			'>': {name: 'greater than', fn: this.greater, field: 'text'},
			'>=': {name: 'greater than or equal to', fn: this.greaterequal, field: 'text'}
		};
	}
}

class UnixTimeType extends BaseType {
	constructor(prop) {
		super();
		this.prop = prop;
		this.operators = {
			'==': {name: 'equal', fn: this.equal, field: 'calendar_unix'},
			'!=': {name: 'not equal', fn: this.notequal, field: 'calendar_unix'},
			'<': {name: 'less than', fn: this.less, field: 'calendar_unix'},
			'<=': {name: 'less than or equal to', fn: this.lessequal, field: 'calendar_unix'},
			'>': {name: 'greater than', fn: this.greater, field: 'calendar_unix'},
			'>=': {name: 'greater than or equal to', fn: this.greaterequal, field: 'calendar_unix'}
		};
	}
}

class ISODateTimeType extends BaseDateType {
	constructor(prop) {
		super();
		this.prop = prop;
		this.operators = {
			'==': {name: 'equal', fn: this.equal, field: 'calendar_iso'},
			'!=': {name: 'not equal', fn: this.notequal, field: 'calendar_iso'},
			'<': {name: 'less than', fn: this.less, field: 'calendar_iso'},
			'<=': {name: 'less than or equal to', fn: this.lessequal, field: 'calendar_iso'},
			'>': {name: 'greater than', fn: this.greater, field: 'calendar_iso'},
			'>=': {name: 'greater than or equal to', fn: this.greaterequal, field: 'calendar_iso'}
		};
	}
}

class DataViewBase {
	constructor() {
		this.types = {};
		this.str = {
			operator_suffix: '_operator'
		};
		this.default_operator = '==';
	}
	get_type_obj(type) {
		if (!this.types.hasOwnProperty(type)) {
			const cls = this.get_type_class(type);
			this.types[type] = new cls();
		}
		return this.types[type];
	}
	get_type_class(type) {
		let cls;
		switch (type) {
			case 'boolean': cls = BooleanType;
				break;
			case 'string': cls = StringType;
				break;
			case 'number': cls = NumberType;
				break;
			case 'unixtime': cls = UnixTimeType;
				break;
			case 'isodatetime': cls = ISODateTimeType;
				break;
			default:
				console.error('Unknown type:', type);
		}
		return cls;
	}
	get_view_config_by_name(name) {
		let ret;
		if (this.prop.config.hasOwnProperty(name)) {
			ret = this.prop.config[name];
		}
		return ret;
	}
	update_config(config) {
		for (const name in config) {
			this.prop.config[name] = config[name];
		}
	}
	remove_from_config(name) {
		if (this.prop.config.hasOwnProperty(name)) {
			delete(this.prop.config[name]);
		}
	}
}

class DataViewTabs extends DataViewBase {
	constructor(prop) {
		super();
		this.ids  = {
			container: prop.main_id + '_tab_view_container',
			tabs: prop.main_id + '_tab_views',
			main: prop.main_id + '_tab_view_main',
			tab_desc: prop.main_id + '_tab_desc'
		};
		this.prop = prop;
		this.resources = {};
		this.current_view = '';
		this.create_tabs(prop.config);
	}
	create_tabs(config) {
		for (const view in config) {
			this.create_tab(view, config[view].description);
		}
	}
	create_tab(name, desc) {
		const container = document.getElementById(this.ids.tabs);

		// new tab
		const tab = document.createElement('DIV');
		tab.classList.add('w3-show-inline-block', 'pointer');
		tab.setAttribute('data-tab', name);
		const tab_inner = document.createElement('DIV');
		tab_inner.classList.add('subtab_btn', 'w3-bottombar', 'w3-hover-light-grey', 'w3-padding');

		tab_inner.textContent = name;
		tab_inner.id = this.prop.main_id + '_' + get_random_string(null, 24);
		tab.appendChild(tab_inner);

		// edit view button
		const edit_btn = document.createElement('I');
		edit_btn.classList.add('fa-solid', 'fa-cog', 'w3-right', 'w3-small');
		edit_btn.style.padding = '5px 0 5px 8px';
		edit_btn.style.visibility = 'hidden';
		edit_btn.addEventListener('click', (e) => {
			this.prop.dvobj.form.edit_form(name);
		});
		tab_inner.appendChild(edit_btn);

		tab.addEventListener('click', (e) => {
			this.current_view = name;
			this.set_tab_description(desc);
			this.apply_filters(true);
			W3SubTabs.open(tab_inner.id, null, this.ids.container);
		});
		tab.addEventListener('mouseover', (e) => {
			edit_btn.style.visibility = 'visible';
		});
		tab.addEventListener('mouseout', (e) => {
			edit_btn.style.visibility = 'hidden';
		});
		container.appendChild(tab);
	}
	update_tabs(view) {
		if (view) {
			this.current_view = view;
		}
		this.clear_tabs();
		this.create_tabs(this.prop.config);
		const container = document.getElementById(this.ids.tabs);
		if (this.current_view) {
			const current_tab = container.querySelector('div[data-tab="' + this.current_view + '"]');
			$(current_tab).click();
		}
	}
	set_tab_description(txt) {
		const desc = document.getElementById(this.ids.tab_desc);
		desc.textContent = txt;
	}
	clear_tabs() {
		const container = document.getElementById(this.ids.tabs);
		while (container.firstChild) {
			container.removeChild(container.firstChild);
		}
	}
	reset_filters(init) {
		this.current_view = '';
		this.set_tab_description('');
		const data = this.prop.data_func();
		this.reset_resource_values();
		data.forEach((item) => {
			for (const prop in this.prop.desc) {
				this.add_resource_value(prop, item[prop]);
			}
		});
		this.prepare_resources();
		this.prop.update_func(data, init);
		W3SubTabs.open(this.ids.main, null, this.ids.container);
	}
	apply_filters(init) {
		if (!this.current_view) {
			// main view, nothing to update, reset and return
			this.reset_filters(init);
			return
		}
		const data = this.get_filtered_data(this.current_view);
		this.prop.update_func(data, init);
	}
	get_filtered_data(view) {
		let desc = {};
		for (const prop in this.prop.desc) {
			// prepare type object
			desc[prop] = this.get_type_obj(this.prop.desc[prop].type);
		}
		const data = this.prop.data_func();
		const data_len = data.length;
		const view_config = this.get_view_config_by_name(view);
		for (const prop in view_config.attr) {
			if (desc[prop] instanceof NumberType || desc[prop] instanceof UnixTimeType) {
				view_config.attr[prop] = view_config.attr[prop].map((val) => parseInt(val, 10));
			}
		}
		let operator, okey;
		let opregex = new RegExp(this.str.operator_suffix + '$', 'i');
		const rdata = [];
		OUTER:
		for (let i = 0; i < data_len; i++) {
			INNER:
			for (const prop in view_config.attr) {
				if (opregex.test(prop)) {
					continue INNER;
				}
				if (!data[i].hasOwnProperty(prop)) {
					// property not exists in data, skip it
					continue OUTER;
				}
				okey = prop + this.str.operator_suffix;
				operator = view_config.attr.hasOwnProperty(okey) ? view_config.attr[okey] : new Array(view_config.attr[prop].length).fill(this.default_operator);
				if (!desc[prop].check(data[i][prop], view_config.attr[prop], operator)) {
					// property does not match the value, filter it
					continue OUTER;
				}
			}
			rdata.push(data[i]);
		}
		return rdata;
	}
	reset_resource_values() {
		this.resources = {};
	}
	add_resource_value(prop, value) {
		if (!this.resources.hasOwnProperty(prop)) {
			this.resources[prop] = [];
		}
		if (this.resources[prop].indexOf(value) === -1 && typeof(value) == 'string') {
			// prepare unique resources
			this.resources[prop].push(value);
		}
	}
	prepare_resources() {
		for (const prop in this.resources) {
			this.resources[prop].sort(function (a, b) {
				return a.toLowerCase().localeCompare(b.toLowerCase());
			});
		}
	}
}

class DataViewForm extends DataViewBase {
	constructor(prop) {
		super();
		this.ids  = {
			form: prop.main_id + '_form_view',
			title_add: prop.main_id + '_form_view_title_add',
			title_edit: prop.main_id + '_form_view_title_edit',
			name: prop.main_id + '_form_view_name',
			desc: prop.main_id + '_form_view_desc',
			rm_btn: prop.main_id + '_form_view_rm_btn',
			win: prop.main_id + '_form_window'
		};
		this.prop = prop;
		this.current_view = '';
	}
	create_form() {
		this.clear_form();
		this.create_form_row();
		this.show_form(true);
		this.set_fields('add');
	}
	edit_form(name) {
		this.clear_form();
		this.apply_config(name);
		this.set_fields('edit');
		this.show_form(true);
	}
	show_form(show) {
		const win = document.getElementById(this.ids.win);
		win.style.display = show ? 'block' : 'none';
	}
	set_fields(mode) {
		const name = document.getElementById(this.ids.name);
		const title_add = document.getElementById(this.ids.title_add);
		const title_edit = document.getElementById(this.ids.title_edit);
		const rm_btn = document.getElementById(this.ids.rm_btn);
		if (mode == 'add') {
			title_edit.style.display = 'none';
			title_add.style.display = '';
			rm_btn.style.display = 'none';
			name.removeAttribute('readonly');
			name.setAttribute('required', 'true');
			name.focus();
		} else if (mode == 'edit') {
			name.setAttribute('readonly', 'readonly');
			name.removeAttribute('required');
			title_add.style.display = 'none';
			title_edit.style.display = '';
			rm_btn.style.display = '';
		}
	}
	create_form_row(prop, type, operator, value, total_rows) {
		const container = document.getElementById(this.ids.form);
		const row = document.createElement('DIV');
		row.classList.add('w3-row', 'directive_field');

		container.appendChild(row);

		// property column
		this.create_form_property(row, this.prop.desc, prop);

		// operator column
		this.create_form_operators(row, type, operator);

		// value column
		this.create_form_value(row, type, prop, operator, value);

		// value column buttons
		this.create_form_btns(row, value, total_rows);
	}
	create_form_property(row, desc, prop) {
		// property column
		const col = document.createElement('DIV');
		col.classList.add('w3-col');
		col.style.width = '160px';

		// select with properites
		const select = document.createElement('SELECT');
		select.classList.add('w3-select', 'w3-border');
		select.style.width = '150px';

		// fill property select
		let option = document.createElement('OPTION');
		let label = document.createTextNode('');
		option.value = '';
		option.appendChild(label);
		select.appendChild(option);
		for (const property in desc) {
			option = document.createElement('OPTION');
			option.value = property;
			option.setAttribute('data-type', desc[property].type);
			label = document.createTextNode(desc[property].name);
			option.appendChild(label);
			select.appendChild(option);
		}
		if (prop) {
			select.value = prop;
		}
		const set_operator_cb = () => {
			// remove other nodes in row
			if (row.childNodes.length > 1) {
				for (let i = (row.childNodes.length - 1); i > 0; i--) {
					if (row.childNodes[i].classList.contains('temp')) {
						row.removeChild(row.childNodes[i]);
					}
				}
			}
			// add new operator select
			const type = select.options[select.selectedIndex].getAttribute('data-type');
			this.create_form_operators(row, type);

			const operator = row.childNodes[1].querySelector('select').value;
			this.create_form_value(row, type, select.value, operator);

			this.create_form_btns(row);

		};
		select.addEventListener('change', set_operator_cb);
		col.appendChild(select);
		row.appendChild(col);
	}
	create_form_operators(row, type, value) {
		const col = document.createElement('DIV');
		col.classList.add('w3-col', 'temp');
		col.style.width = '210px';

		const select = document.createElement('SELECT');
		select.classList.add('w3-select', 'w3-border');
		select.style.width = '200px';

		if (type) {
			const obj = this.get_type_obj(type);
			const operators = obj.get_operators();

			let option, label, txt;
			for (const op in operators) {
				option = document.createElement('OPTION');
				option.value = op;
				txt = '%operator %name'.replace('%operator', op).replace('%name', operators[op].name);
				label = document.createTextNode(txt);
				option.appendChild(label);
				select.appendChild(option);
			}
			if (value) {
				select.value = value;
			}
		}

		const set_operator_cb = () => {
			// remove other nodes in row
			let prev_val = '';
			if (row.childNodes.length > 2) {
				for (let i = (row.childNodes.length - 1); i > 1; i--) {
					if (row.childNodes[i].classList.contains('temp')) {
						const val_el = row.childNodes[i].querySelector('.value');
						if (val_el) {
							prev_val = val_el.value;
						}
						row.removeChild(row.childNodes[i]);
					}
				}
			}
			// add new operator select
			const prop = row.childNodes[0].querySelector('select').value;

			this.create_form_value(row, type, prop, select.value, prev_val);

			this.create_form_btns(row);

		};
		select.addEventListener('change', set_operator_cb);

		col.appendChild(select);
		row.appendChild(col);
	}
	create_form_value(row, type, prop, operator, value) {
		const col = document.createElement('DIV');
		col.classList.add('w3-col', 'temp');
		col.style.width = '330px';

		let el;
		if (type) {
			const obj = this.get_type_obj(type);
			const operators = obj.get_operators();
			let field;
			if (operator) {
				field = operators[operator].field;
			} else {
				field = 'text';
			}
			if (field == 'select') {
				el = document.createElement('SELECT');
				el.classList.add('w3-select', 'w3-border', 'value');
				const res = this.prop.dvobj.tabs.resources;
				if (res.hasOwnProperty(prop)) {
					let val_len = res[prop].length;
					let option, label, txt, part;
					const formatter = this.prop.desc.hasOwnProperty(prop) && this.prop.desc[prop].hasOwnProperty('formatter') ? this.prop.desc[prop].formatter : null;
					for (let i = 0; i < val_len; i++) {
						option = document.createElement('OPTION');
						option.value = res[prop][i];
						if (formatter) {
							part = formatter.split('.');
							if (part.length == 1) {
								txt = window[part[0]](res[prop][i]);
							} else if (part.length == 2) {
								txt = window[part[0]][part[1]](res[prop][i]);
							}
						} else {
							txt = res[prop][i];
						}
						label = document.createTextNode(txt);
						option.appendChild(label);
						el.appendChild(option);
					}
				}
			} else if (field == 'text') {
				el = document.createElement('INPUT');
				el.classList.add('w3-input', 'w3-border', 'value');
			} else if (field == 'none') {
				el = document.createElement('INPUT');
				el.classList.add('w3-input', 'w3-border', 'value');
				el.setAttribute('disabled', 'disabled');
			} else if (field == 'yesno') {
				el = document.createElement('SELECT');
				el.classList.add('w3-select', 'w3-border', 'value');
				let option, label;
				const vals = ['No', 'Yes'];
				for (let i = 0; i < vals.length; i++) {
					option = document.createElement('OPTION');
					option.value = i;
					label = document.createTextNode(vals[i]);
					option.appendChild(label);
					el.appendChild(option);
				}
			} else if (field == 'calendar_iso') {
				const date_format = 'yy-mm-dd 0:00:00';
				el = document.createElement('INPUT')
				el.classList.add('w3-input', 'w3-border', 'value');
				el.id = get_random_string(null, 24);
				const set_hour = (d) => {
					if (/^\d{4}-\d{2}-\d{2} \d{1,2}:\d{2}:\d{2}$/.test(d)) {
						const tm = d.split(' ')[1];
						el.setAttribute('tm', tm);
					}
				};
				el.addEventListener('input', (e) => {
					const d = e.target.value;
					set_hour(d);
				});
				$(function() {
					$('#' + el.id).datepicker({
						dateFormat: date_format,
						onSelect: function(d) {
							const tm = el.getAttribute('tm') || '0:00:00';
							el.value = el.value.replace('0:00:00', tm);
						},
					});
				});
				if (value) {
					set_hour(value);
				}
			} else if (field == 'calendar_unix') {
				const date_format = 'yy-mm-dd 0:00:00';
				const ts_extract_time = (ts) => {
					const ts_ms = parseInt(ts, 10) * 1000;
					const t = new Date(ts_ms);
					const h = t.getHours();
					const m = t.getMinutes();
					const s = t.getSeconds();
					const tm = [h, ':', ('0' + m).substr(-2), ':', ('0' + s).substr(-2)].join('');
					return tm;
				};
				el = document.createElement('DIV');
				const field1 = document.createElement('INPUT');
				field1.id = get_random_string(null, 24);
				field1.setAttribute('readonly', 'true');
				field1.classList.add('value');
				field1.style.display = 'none';
				const field2 = document.createElement('INPUT')
				field2.classList.add('w3-input', 'w3-border');
				field2.id = get_random_string(null, 24);
				field2.addEventListener('input', (e) => {
					const d = e.target.value;
					if (/^\d{4}-\d{2}-\d{2} \d{1,2}:\d{2}:\d{2}$/.test(d)) {
						const t = date_time_to_ts(d);
						if (typeof(t) == 'number' && t >= 0) {
							field1.value = parseInt(t / 1000, 10);
							field1.setAttribute('tm', ts_extract_time(field1.value));
						}
					}
				});
				$(function() {
					$('#' + field2.id).datepicker({
						dateFormat: date_format,
						altField: '#' + field1.id,
						altFormat: "@",
						onSelect: function(d) {
							const tm = field1.getAttribute('tm') || '0:00:00';
							field2.value = field2.value.replace('0:00:00', tm);
							const t = date_time_to_ts(field2.value);
							if (typeof(t) == 'number' && t >= 0) {
								field1.value = parseInt(t / 1000, 10);
								field1.setAttribute('tm', ts_extract_time(field1.value));
							}
						},
					});
				});
				if (value) {
					const tm = ts_extract_time(value);
					const ts_ms = parseInt(value, 10) * 1000;
					const t = new Date(ts_ms);
					field2.value = $.datepicker.formatDate(date_format, t);
					field2.value = field2.value.replace('0:00:00', tm);
					field1.setAttribute('tm', tm);
				}
				el.appendChild(field1);
				el.appendChild(field2);
			}
		} else {
			el = document.createElement('INPUT');
			el.classList.add('w3-input', 'w3-border', 'value');
		}
		if (value) {
			let val_el = el;
			if (!val_el.classList.contains('value')) {
				val_el = val_el.querySelector('.value');
			}
			if (val_el && !val_el.hasAttribute('disabled')) {
				val_el.value = value;
			}
		}
		el.style.width = '320px';

		col.appendChild(el);
		row.appendChild(col);
	}
	create_form_btns(row, load, total_rows) {
		const col = document.createElement('DIV');
		col.classList.add('w3-col', 'temp');
		col.style.width = '130px';

		const btn_rm = document.createElement('BUTTON');
		btn_rm.type = 'button';
		btn_rm.classList.add('w3-button', 'w3-green');
		btn_rm.style.marginRight = '4px';
		const img_rm = document.createElement('I');
		img_rm.classList.add('fa-solid', 'fa-minus');
		btn_rm.appendChild(img_rm);
		btn_rm.addEventListener('click', (e) => {
			const pn = row.parentNode;
			pn.removeChild(row);
			// after removing make visible add button on last row
			pn.lastChild.querySelector('button:last-child').style.display = 'inline';
			if (pn.childNodes.length == 1) {
				// after removing row hide remove button if there is only one row
				pn.firstChild.querySelector('button:first-child').style.display = 'none';
			};
		});
		btn_rm.style.display = 'none';
		col.appendChild(btn_rm);
		const row_len = row.parentNode.childNodes.length
		if (row_len > 1 || total_rows > 1) {
			// if there is more than one row, show remove buttom
			btn_rm.style.display = 'inline';
		} else if (!load) {
			// if there is only one row, hide remove button
			btn_rm.style.display = 'none';
		}

		const btn_add = document.createElement('BUTTON');
		btn_add.type = 'button';
		btn_add.classList.add('w3-button', 'w3-green');
		const img_add = document.createElement('I');
		img_add.classList.add('fa-solid', 'fa-plus');
		btn_add.addEventListener('click', (e) => {
			this.create_form_row();
			// hide add button in the current row if a new row is created
			btn_add.style.display = 'none';
			// show remove button on the newly added row
			row.parentNode.firstChild.querySelector('button:first-child').style.display = 'inline';
		});
		btn_add.appendChild(img_add);
		btn_add.style.display = 'none';
		if (!row.nextSibling && (!load || row.parentNode.childNodes.length == total_rows)) {
			btn_add.style.display = 'inline';
		}
		col.appendChild(btn_add);
		row.appendChild(col);
	}
	get_row_number(view) {
		let row_nb = 0;
		if (!view) {
			return row_nb;
		}
		const config = this.get_view_config_by_name(view);
		let opregex = new RegExp(this.str.operator_suffix + '$', 'i');
		for (const prop in config.attr) {
			if (opregex.test(prop)) {
				// skip operator properties
				continue;
			}
			row_nb += config.attr[prop].length;
		}
		return row_nb;
	}
	clear_form() {
		const name = document.getElementById(this.ids.name);
		name.value = '';
		const desc = document.getElementById(this.ids.desc);
		desc.value = '';
		const container = document.getElementById(this.ids.form);
		while (container.firstChild) {
			container.removeChild(container.firstChild);
		}
	}
	validate_form() {
		let state = true;
		const name = document.getElementById(this.ids.name);
		if (!name.checkValidity()) {
			name.reportValidity();
			state = false;
		} else {
			name.setCustomValidity('');
		}
		return state;
	}
	save_config() {
		if (!this.validate_form()) {
			return;
		}
		const name = document.getElementById(this.ids.name);
		const desc = document.getElementById(this.ids.desc);
		const form = document.getElementById(this.ids.form);
		let row, prop, op, val;
		const config = {};
		config[name.value] = {
			description: desc.value,
			attr: {}
		};
		for (let i = 0; i < form.childNodes.length; i++) {
			row = form.childNodes[i];
			prop = row.childNodes[0].firstChild.value;
			op = row.childNodes[1].firstChild.value;
			val = row.childNodes[2].querySelector('.value').value;
			if (!prop || !op) {
				// skip row without value or without operator
				continue;
			}
			if (!config[name.value].attr.hasOwnProperty(prop)) {
				config[name.value].attr[prop] = [];
				config[name.value].attr[prop + this.str.operator_suffix] = [];
			}
			config[name.value].attr[prop].push(val);
			config[name.value].attr[prop + this.str.operator_suffix].push(op);
		}
		this.prop.save_func.setCallbackParameter(config);
		this.prop.save_func.dispatch();
		this.post_save_actions(config);

	}
	post_save_actions(config) {
		this.update_config(config);
		this.prop.dvobj.tabs.update_config(config);
		this.show_form(false);
		const name = document.getElementById(this.ids.name);
		this.prop.dvobj.tabs.update_tabs(name.value);
	}
	open_tab(name) {
		const new_tab = document.querySelector('div[data-tab="', name + '"]');
		if (new_tab) {
			// open newly create tab
			$(new_tab).click();
		}
	}
	apply_config(view) {
		const config = this.get_view_config_by_name(view);
		const name = document.getElementById(this.ids.name);
		const desc = document.getElementById(this.ids.desc);
		this.current_view = view;
		name.value = view;
		desc.value = config.hasOwnProperty('description') ? config.description : '';
		let opregex = new RegExp(this.str.operator_suffix + '$', 'i');
		let type, operator, ops, value;
		const total_rows = this.get_row_number(view);
		for (const prop in config.attr) {
			if (opregex.test(prop)) {
				// skip operator properties
				continue;
			}
			type = this.prop.desc.hasOwnProperty(prop) ? this.prop.desc[prop].type : null;
			ops = config.attr.hasOwnProperty(prop + this.str.operator_suffix) ? config.attr[prop + this.str.operator_suffix] : [];
			for (let i = 0; i < config.attr[prop].length; i++) {
				operator = ops[i];
				value = config.attr[prop][i];
				this.create_form_row(prop, type, operator, value, total_rows);
			}
		}
	}
	remove_config() {
		const name = document.getElementById(this.ids.name);
		this.prop.remove_func.setCallbackParameter(name.value);
		this.prop.remove_func.dispatch();
		this.post_remove_actions(name.value);
	}
	post_remove_actions(view) {
		this.remove_from_config(view);
		this.prop.dvobj.tabs.remove_from_config(view);
		this.prop.dvobj.tabs.reset_filters(true);
		this.show_form(false);
		this.prop.dvobj.tabs.update_tabs();
	}
}

class DataView {
	constructor(prop) {
		prop.dvobj = this;
		this.tabs = new DataViewTabs(prop);
		this.form = new DataViewForm(prop);
	}
}
