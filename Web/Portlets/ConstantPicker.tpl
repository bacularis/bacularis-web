<div id="constant_picker_window" class="w3-white w3-border item_picker" style="display: none;">
	<div class="w3-container">
		<span onclick="oConstantPicker.show_window(false);" class="w3-button w3-display-topright">×</span>
		<h5><%[ Constants ]%></h5>
		<div rel="list" id="constant_picker_consts_list" style="max-height: 365px; overflow-x: scroll; overflow-x: hidden;"></div>
	</div>
</div>
<com:TCallback ID="LoadConstants" OnCallback="loadConstants" />
<script>
var oConstantPicker = {
	ids: {
		win: 'constant_picker_window',
		list: 'constant_picker_consts_list',
		root: '<%=$this->getRootHTMLElementID()%>'
	},
	input: null,
	filter_timeout: null,
	consts_len: 0,
	init: function() {
		this.load_constants();
		this.set_events();
	},
	set_events: function() {
		const root_el = document.getElementById(this.ids.root) || window;
		root_el.addEventListener('keyup', (e) => {
			if (this.consts_len == 0 || (e.target.type != 'text' && e.target.nodeName != 'TEXTAREA')) {
				return;
			}
			if (e.key === '<%=ConstantConfig::SPECIAL_CHAR%>') {
				this.input = e.target;
				this.set_window_pos();
				this.show_window(true);
			} else if ([' ', '}', '<%=VariableConfig::SPECIAL_CHAR%>'].indexOf(e.key) != -1 || (this.input && this.input.value.length == 0)) {
				this.show_window(false);
			} else if (this.is_window_opened()) {
				if (this.filter_timeout) {
					clearTimeout(this.filter_timeout);
				}
				this.filter_timeout = setTimeout(() => {
					this.filter_constants();
				}, 250);
			}
		});
	},
	show_window: function(show) {
		const win = $('#' + this.ids.win);
		if (show) {
			win.slideDown('fast');
		} else {
			win.slideUp('fast');
		}
	},
	is_window_opened: function() {
		const win = document.getElementById(this.ids.win);
		return (win.style.display == '');
	},
	set_window_pos: function() {
		const win = document.getElementById(this.ids.win);
		const ipos = this.input.getBoundingClientRect();
		const scroll_el = getClosestScrollEl(this.input);
		let scroll_val = scroll_el ? scroll_el.scrollTop : 0;
		win.style.top = (ipos.y + ipos.height) + 'px';
		win.style.left = ipos.x + 'px';
	},
	load_constants: function() {
		const cb = <%=$this->LoadConstants->ActiveControl->Javascript%>;
		cb.dispatch();
	},
	load_constants_cb: function(consts) {
		oConstantPicker.consts_len = consts.length;
		oConstantPicker.build_constant_list(consts);
	},
	build_constant_list: function(consts) {
		this.clear_constant_list();

		const container = document.getElementById(this.ids.list);

		const set_event = (el, constant) => {
			el.addEventListener('click', () => {
				this.set_input_value(constant);
			});
		};
		let row, left, right;
		for (const v of consts) {
			row = document.createElement('DIV');
			row.classList.add('w3-row', 'pointer');
			row.setAttribute('data-constant', v.name);
			set_event(row, v.value);

			left = document.createElement('DIV');
			left.classList.add('w3-show-inline-block', 'bold');
			left.style.minWidth = '50px';
			left.style.padding = '1px';
			left.textContent = '<%=ConstantConfig::SPECIAL_CHAR%>{' + v.name + '}';

			right = document.createElement('DIV');
			right.classList.add('w3-show-inline-block');
			right.style.minWidth = '50px';
			right.style.paddingLeft = '10px';
			if (v.description) {
				right.textContent = ' - ' + v.description;
			}

			row.appendChild(left);
			row.appendChild(right);
			container.appendChild(row);
		}
	},
	clear_constant_list: function() {
		const container = document.getElementById(this.ids.list);
		while (container.firstChild) {
			container.removeChild(container.firstChild);
		}
	},
	filter_constants: function() {
		const word = this.get_input_keyword();
		const container = document.getElementById(this.ids.list);
		const consts = container.querySelectorAll('div.w3-row[data-constant]');
		let constant;
		let keyword = word.replace(/[\{\}]/g, '');
		const regex = new RegExp(keyword, 'i');
		for (const c of consts) {
			constant = c.getAttribute('data-constant');
			if (regex.test(constant)) {
				c.style.display = 'block';
			} else {
				c.style.display = 'none';
			}
		}
	},
	get_input_keyword: function() {
		const cpos = this.input.selectionStart;
		const value = this.input.value;
		const vpart = value.substr(0, cpos);
		let chrs = [];
		let character;
		for (let i = vpart.length; i > 0; i--) {
			character = vpart.charAt(i);
			if (character == '<%=ConstantConfig::SPECIAL_CHAR%>') {
				break;
			}
			chrs.unshift(character);
		}
		return chrs.join('');
	},
	set_input_value: function(constant) {
		const keyword = this.get_input_keyword();
		const cpos = this.input.selectionStart;
		const before = this.input.value.slice(0, cpos - keyword.length - 1);
		const end = this.input.value.slice(cpos);
		this.input.value = before + constant + end;
		this.input.focus();
		this.input.selectionStart = this.input.selectionEnd = (before + constant).length;
		this.show_window(false);
	}
};
$(() => {
	oConstantPicker.init();
});
</script>
