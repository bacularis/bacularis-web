<a href="javascript:void(0);" onmousedown="openElementOnCursor(event, '<%=$this->ClientID%>_component_actions', -80, 20);" style="display: <%=$this->BigButtons ? 'none' : 'inline'%>">
	<i class="fas fa-ellipsis-v fa-lg"></i>
</a>
<div id="<%=$this->ClientID%>_component_actions" class="w3-card w3-white w3-padding left" style="display: none">
	<i class="fa fa-times w3-right" onclick="$('#<%=$this->ClientID%>_component_actions').hide();" style="cursor: pointer"></i>
	<ul class="w3-ul new_element_menu">
		<li><com:TActiveLinkButton
			OnCommand="componentAction"
			CommandParameter="start"
			ClientSide.OnLoading="$('#<%=$this->ClientID%>_component_actions').hide();"
			>
			<i class='fas fa-play'></i> &nbsp;<%[ Start ]%>
		</com:TActiveLinkButton>
		</li>
		<li><com:TActiveLinkButton
			OnCommand="componentAction"
			CommandParameter="stop"
			ClientSide.OnLoading="$('#<%=$this->ClientID%>_component_actions').hide();"
			>
			<i class='fas fa-stop'></i> &nbsp;<%[ Stop ]%>
		</com:TActiveLinkButton>
		</li>
		<li><com:TActiveLinkButton
			OnCommand="componentAction"
			CommandParameter="restart"
			ClientSide.OnLoading="$('#<%=$this->ClientID%>_component_actions').hide();"
			>
			<i class='fas fa-sync'></i> &nbsp;<%[ Restart ]%>
		</com:TActiveLinkButton>
		</li>
	</ul>
</div>
<span id="<%=$this->ClientID%>_big_buttons_component_actions" style="display: <%=$this->BigButtons ? 'inline' : 'none'%>">
	<com:TActiveLinkButton
		OnCommand="componentAction"
		CommandParameter="start"
		CssClass="w3-button w3-green w3-margin-bottom"
		ClientSide.OnLoading="$('#<%=$this->ClientID%>_loader_component_actions').show();"
		ClientSide.OnComplete="$('#<%=$this->ClientID%>_loader_component_actions').hide();"
		>
		<i class='fas fa-play'></i> &nbsp;<%[ Start ]%>
	</com:TActiveLinkButton>
	<com:TActiveLinkButton
		OnCommand="componentAction"
		CommandParameter="stop"
		CssClass="w3-button w3-green w3-margin-bottom"
		ClientSide.OnLoading="$('#<%=$this->ClientID%>_loader_component_actions').show();"
		ClientSide.OnComplete="$('#<%=$this->ClientID%>_loader_component_actions').hide();"
		>
		<i class='fas fa-stop'></i> &nbsp;<%[ Stop ]%>
	</com:TActiveLinkButton>
	<com:TActiveLinkButton
		OnCommand="componentAction"
		CommandParameter="restart"
		CssClass="w3-button w3-green w3-margin-bottom"
		ClientSide.OnLoading="$('#<%=$this->ClientID%>_loader_component_actions').show();"
		ClientSide.OnComplete="$('#<%=$this->ClientID%>_loader_component_actions').hide();"
		>
		<i class='fas fa-sync'></i> &nbsp;<%[ Restart ]%>
	</com:TActiveLinkButton>
	<i id="<%=$this->ClientID%>_loader_component_actions" class="fa fa-sync fa-spin" style="vertical-align: super;display: none;"></i>
</span>
<div id="<%=$this->ClientID%>_component_action_message_box" class="w3-modal" style="display: none">
	<div class="w3-modal-content w3-card-4 w3-animate-zoom" style="width:600px">
		<header class="w3-container w3-red marked">
			<span onclick="$('#<%=$this->ClientID%>_component_action_message_box').hide();" class="w3-button w3-display-topright">×</span>
			<h2><%[ Component action result ]%></h2>
		</header>
		<div class="w3-panel w3-padding">
			<p><strong><%[ Message: ]%></strong> <span id="<%=$this->ClientID%>_component_action_output"></span></p>
			<p onclick="$('#<%=$this->ClientID%>_component_action_details').slideToggle('fast'); $('#<%=$this->ClientID%>_component_action_arrow').toggleClass(function() { return $(this).is('.fa-chevron-down') ? 'fa-chevron-up' : 'fa-chevron-down';});" style="cursor: pointer"><i id="<%=$this->ClientID%>_component_action_arrow" class="fas fa-chevron-down"></i> &nbsp;<%[ Details ]%></p>
			<p id="<%=$this->ClientID%>_component_action_details" style="display: none"><strong><%[ Error code: ]%></strong> <span id="<%=$this->ClientID%>_component_action_message_exit_code"></span></p>
		</div>
		<footer class="w3-container w3-center w3-border-top">
			<button type="button" class="w3-button w3-section w3-red marked" onclick="$('#<%=$this->ClientID%>_component_action_message_box').hide()"><i class="fa fa-check"></i> &nbsp;<%[ OK ]%></button>
		</footer>
	</div>
</div>
<script type="text/javascript">
oComponentAction<%=$this->ClientID%> = {
	ids: {
		message_box: '<%=$this->ClientID%>_component_action_message_box',
		error_code: '<%=$this->ClientID%>_component_action_message_exit_code',
		output: '<%=$this->ClientID%>_component_action_output'
	},
	msgs: {
		start_ok: '<%[ Component start finished successfully. ]%>',
		stop_ok: '<%[ Component stop finished successfully. ]%>',
		restart_ok: '<%[ Component restart finished successfully. ]%>',
	},
	actions: {
		start: 'start',
		stop: 'stop',
		restart: 'restart'
	},
	set_result: function(action, result) {
		if (result.error === 0) {
			this.set_info_view();
		} else {
			this.set_error_view();
		}
		this.set_output(action, result);
		this.set_error_code(result.error);
		this.show_box();
	},
	set_info_view: function() {
		this.switch_box_view('w3-red', 'w3-green');
	},
	set_error_view: function() {
		this.switch_box_view('w3-green', 'w3-red');
	},
	switch_box_view: function(old_class, new_class) {
		var msgbox = document.getElementById(this.ids.message_box);
		var containers = msgbox.querySelectorAll('.marked');
		for (var i = 0; i < containers.length; i++) {
			if (containers[i].classList.contains(old_class)) {
				containers[i].classList.remove(old_class);
			}
			containers[i].classList.add(new_class);
		}
	},
	set_error_code: function(error_code) {
		document.getElementById(this.ids.error_code).textContent = error_code;
	},
	set_output: function(action, result) {
		var out = document.getElementById(this.ids.output);
		if (Array.isArray(result.output)) {
			out.innerHTML = result.output.join('<br />');
		} else if (result.output) {
			out.textContent = result.output;
		} else if (result.error === 0) {
			switch (action) {
				case this.actions.start:
					out.textContent = this.msgs.start_ok;
					break;
				case this.actions.stop:
					out.textContent = this.msgs.stop_ok;
					break;
				case this.actions.restart:
					out.textContent = this.msgs.restart_ok;
					break;
			}
		}
	},
	show_box: function() {
		document.getElementById(this.ids.message_box).style.display = 'block';
	}
};
function <%=$this->ClientID%>_component_action_set_result(action, result) {
	oComponentAction<%=$this->ClientID%>.set_result(action, result);
}
</script>
