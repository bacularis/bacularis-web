<div id="crumbs">
	<com:TRepeater ID="CrumbsNav">
		<prop:HeaderTemplate>
			<ul class="margin-top-small w3-margin-right">
		</prop:HeaderTemplate>
		<prop:ItemTemplate>
				<li id="item<%#$this->getItemIndex()%>">
					<a href="<%#Miscellaneous::html_value($this->Data['page_url'])%>" onmouseover="oBreadCrumbs.show_actions(this);" onmouseout="oBreadCrumbs.hide_actions(this);">
							<%=isset($this->Data['icon']) ? '<i class="' . $this->Data['icon'] . '"></i> ' : ''%> 
							<%#Miscellaneous::html_value($this->Data['label'])%>
							<%=isset($this->Data['sub_label']) ? ': ' . Miscellaneous::html_value($this->Data['sub_label']) : ''%>
							<div class="pointer" style="display: none; vertical-align: middle; height: 20px;" rel="child" onclick="return oBreadCrumbs.prepare_action_list(event, <%#$this->getItemIndex()%>);">
								<i class="fa-solid fa-chevron-down" rel="child"></i>
							</div>
					</a>
					<script>
						$(() => {
							oBreadCrumbs.add_page_data(<%#$this->getItemIndex()%>, <%#Miscellaneous::json_value($this->Data ?? [])%>);
						});
					</script>
				</li>
		</prop:ItemTemplate>
		<prop:FooterTemplate>
			</ul>
		</prop:FooterTemplate>
	</com:TRepeater>
</div>
<div id="crumbs_action_window" class="w3-modal" style="display: none; background-color: transparent;">
	<div class="w3-modal-content w3-card-4 w3-round-large" id="crumbs_action_list" style="animation: opac 0.3s;"></div>
</div>
<script>
const oBreadCrumbs = {
	ids: {
		container: 'crumbs',
		action_window: 'crumbs_action_window',
		action_list: 'crumbs_action_list'
	},
	page_data: {},
	timeout: null,
	add_page_data: function(index, page_data) {
		this.page_data[index] = page_data;
	},
	show_actions: function(el) {
		const action_selector = 'div.pointer';
		const arrow_down = el.querySelector(action_selector);
		if (this.timeout) {
			clearTimeout(this.timeout);
		}
		this.timeout = setTimeout(() => {
			// hide other opened arrow options
			$('#' + this.ids.container + ' ' + action_selector + ':visible').each((index, item) => {
				const a = item.closest('a');
				if (el.href != a.href) {
					this.hide_actions(item.parentNode);
				}
			});
			// show current arrow
			$(arrow_down).css({'display': 'inline-block'}).animate({
				width: '25px'
			}, 150, 'linear');
		}, 350);
	},
	hide_actions: function(el) {
		clearTimeout(this.timeout);
		const arrow_down = el.querySelector('div.pointer');
		this.timeout = setTimeout(() => {
			$(arrow_down).css({'display': 'inline-block'}).animate({
				width: '0'
			}, 100, 'linear', () => {
				arrow_down.style.display = 'none';
			});
		}, 200);
	},
	show_action_window: function(show) {
		const al = document.getElementById(this.ids.action_window);
		al.style.display = show ? 'block' : 'none';
	},
	prepare_action_list: function (e, index) {
		this.clear_action_list();
		const al = document.getElementById(this.ids.action_list);
		const ul = document.createElement('UL');
		if (this.page_data[index].hasOwnProperty('actions')) {
			for (const item of this.page_data[index].actions) {
				this.add_action_item(ul, item);
			}
		}
		al.appendChild(ul);
		this.set_action_list_pos(e);
		this.show_action_window(true);
		return false;
	},
	add_action_item: function(ul, item) {
		const li = document.createElement('LI');
		if (item.hasOwnProperty('visible') && !item.visible) {
			li.classList.add('hide');
		}
		const a = document.createElement('A');
		if (item.hasOwnProperty('address')) {
			const url = this.get_valid_url(item.address);
			if (url) {
				a.href = url.href;
				a.addEventListener('click', (event) => {
					event.preventDefault();
					this.go_to_page(url.href);
				});
			}
		}
		a.classList.add('raw');
		const label = document.createTextNode(item.label);
		if (item.hasOwnProperty('icon')) {
			const icon = document.createElement('I');
			icon.className = item.icon;
			a.appendChild(icon);
		}
		a.appendChild(label);
		li.appendChild(a);
		ul.appendChild(li);
	},
	get_valid_url: function(address) {
		let url = null;
		try {
			const parsed_url = new URL(address, window.location.href);
			if (['http:', 'https:'].indexOf(parsed_url.protocol) !== -1 && parsed_url.origin === window.location.origin) {
				url = parsed_url;
			}
		} catch (error) {
			url = null;
		}
		return url;
	},
	go_to_page: function(address) {
		const url = this.get_valid_url(address);
		if (!url) {
			return;
		}
		if (window.location.pathname == url.pathname) {
			// the same page, do not reload the page, just use hash
			window.location.href = url.hash;
			set_action_by_url_fragment();
		} else {
			// different page, direct to this page
			window.location.href = url.href;
		}
		this.show_action_window(false);
	},
	clear_action_list: function() {
		const al = document.getElementById(this.ids.action_list);
		while (al.firstChild) {
			al.removeChild(al.firstChild);
		}
	},
	set_action_list_pos: function(e) {
		const al = document.getElementById(this.ids.action_list);
		const shift_px = 10;
		al.style.left = (e.clientX + shift_px) + 'px';
		al.style.top = (e.clientY + shift_px) + 'px';
	}
};
</script>
