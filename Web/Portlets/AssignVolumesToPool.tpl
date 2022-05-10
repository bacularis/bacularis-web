<com:TActiveLinkButton
	CssClass="w3-button w3-green w3-margin-bottom"
	OnClick="loadValues"
>
	<prop:Attributes.onclick>
		oAssignVolumesToPool.show_window(true);
	</prop:Attributes.onclick>
	<i class="fa fa-pen-alt"></i> &nbsp;<%[ Assign volumes ]%>
</com:TActiveLinkButton>
<div id="assign_volumes_modal" class="w3-modal">
	<div class="w3-modal-content w3-animate-top w3-card-4">
		<header class="w3-container w3-green">
			<span onclick="oAssignVolumesToPool.show_window(false);" class="w3-button w3-display-topright">&times;</span>
			<h2><%[ Assign volumes from selected pool ]%></h2>
		</header>
		<div class="w3-padding">
			<p><%[ This function enables re-assigning volumes from selected pool to this pool. ]%></p>
			<p><%[ Please select a pool from which you would like to assign volumes to this pool. ]%></p>
			<div class="w3-row-padding w3-section">
				<div class="w3-col w3-half"><com:TLabel ForControl="Pool" Text="<%[ Pool: ]%>" /></div>
				<div class="w3-col w3-half">
					<com:TActiveDropDownList ID="Pool" CssClass="w3-input w3-border" />
					<com:TRequiredFieldValidator
						ValidationGroup="AssignVolumesGroup"
						ControlToValidate="Pool"
						ErrorMessage="<%[ Field required. ]%>"
						Display="Dynamic"
					/>
				</div>
			</div>
			<div class="w3-row-padding w3-section">
				<div class="w3-col w3-half"><com:TLabel ForControl="Pool" Text="<%[ Assigned volume count: ]%>" /></div>
				<div class="w3-col w3-half"><strong id="assign_volumes_counter"></strong>/<strong id="assign_volumes_all"></strong></div>
			</div>
			<div class="w3-container w3-center w3-section">
				<button type="button" class="w3-button w3-red" onclick="oAssignVolumesToPool.show_window(false);"><i class="fa fa-times"></i> &nbsp;<%[ Close ]%></button>
				<com:TLinkButton
					ID="AssignVolumesButton"
					CausesValidation="true"
					ValidationGroup="AssignVolumesGroup"
					CssClass="w3-button w3-green"
					Attributes.onclick="oAssignVolumesToPool.load_volume_list(); return false;"
				>
					<i class="fa fa-pen-alt"></i> &nbsp;<%[ Assign volumes ]%>
				</com:TLinkButton>
				&nbsp; <i id="assign_volumes_loading" class="fa fa-sync w3-spin" style="visibility: hidden;"></i>
			</div>
			<div id="assign_volumes_log" class="w3-panel w3-card w3-light-grey" style="display: none; max-height: 200px; overflow-x: auto;">
				<div class="w3-code notranslate">
					<pre><com:TActiveLabel ID="AssignVolumesLog" /></pre>
				</div>
			</div>
		</div>
	</div>
</div>
<com:TCallback ID="LoadVolumeList" OnCallback="loadVolumeList" />
<com:TCallback ID="AssignVolume" OnCallback="assignVolume" />
<script>
var oAssignVolumesToPool = {
	volumes: [],
	logbox_scroll: false,
	ids: {
		modal: 'assign_volumes_modal',
		loader: 'assign_volumes_loading',
		logbox: 'assign_volumes_log',
		counter: 'assign_volumes_counter',
		all: 'assign_volumes_all'
	},
	show_window: function(show) {
		// clean up previous logs (if any)
		this.cleanup_joblog();

		// clear counter
		this.clear_counter();

		var win = document.getElementById(this.ids.modal);
		win.style.display = show ? 'block' : 'none';
	},
	load_volume_list: function() {
		oAssignVolumesToPool.show_loader(true);
		oAssignVolumesToPool.clear_counter();

		var cb = <%=$this->LoadVolumeList->ActiveControl->Javascript%>;
		cb.dispatch();
	},
	load_volume_list_cb: function(volumes) {
		for (var i = 0; i < volumes.length; i++) {
			oAssignVolumesToPool.volumes.push(volumes[i].mediaid);
		}
		if (oAssignVolumesToPool.volumes.length > 0) {
			// prepare log box
			oAssignVolumesToPool.prepare_logbox();

			// start assigning
			oAssignVolumesToPool.assign_volume();
		} else {
			oAssignVolumesToPool.show_loader(false);
		}
	},
	assign_volume: function() {
		oAssignVolumesToPool.prepare_logbox();
		oAssignVolumesToPool.set_counter();
		if (oAssignVolumesToPool.volumes.length > 0) {
			var cb = <%=$this->AssignVolume->ActiveControl->Javascript%>;
			cb.setCallbackParameter(oAssignVolumesToPool.volumes.shift());
			cb.dispatch();
		} else {
			oAssignVolumesToPool.show_loader(false);
		}
	},
	show_loader: function(show) {
		var loader = document.getElementById(oAssignVolumesToPool.ids.loader);
		loader.style.visibility = show ? 'visible' : 'hidden';
	},
	prepare_logbox: function() {
		var logbox = document.getElementById(this.ids.logbox);
		logbox.style.display = '';
		if (this.logbox_scroll) {
			logbox.scrollTo(0, logbox.scrollHeight);
		}
	},
	is_logbox_scroll_end: function() {
		var logbox = document.getElementById(this.ids.logbox);
		var pos = logbox.scrollHeight - logbox.clientHeight - logbox.scrollTop;
		return (pos < 2);
	},
	set_logbox_scroll: function() {
		oAssignVolumesToPool.logbox_scroll = oAssignVolumesToPool.is_logbox_scroll_end();
	},
	cleanup_joblog: function() {
		var logbox = document.getElementById(this.ids.logbox);
		logbox.style.display = 'none';
		var logbox_content = document.getElementById('<%=$this->AssignVolumesLog->ClientID%>');
		logbox_content.textContent = '';
	},
	set_counter: function() {
		var counter = document.getElementById(this.ids.counter);
		var cnt = parseInt(counter.textContent, 10);
		var all = document.getElementById(this.ids.all);
		var cnta = parseInt(all.textContent, 10);
		if (cnt < cnta) {
			counter.textContent = ++cnt;
		}
		if (all.textContent == 0) {
			all.textContent = this.volumes.length;
		}
	},
	clear_counter: function() {
		var counter = document.getElementById(this.ids.counter);
		counter.textContent = 0;
		var all = document.getElementById(this.ids.all);
		all.textContent = 0;
	}
};
</script>
