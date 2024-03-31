<div id="network_test_window<%=$this->ClientID%>" class="w3-modal">
	<div class="w3-modal-content w3-animate-top w3-card-4">
		<header class="w3-container w3-green">
			<span onclick="oNetworkTest<%=$this->ClientID%>.show(false);" class="w3-button w3-display-topright">&times;</span>
			<h2><%[ Network test ]%></h2>
		</header>
		<div id="network_test_error<%=$this->ClientID%>" class="w3-container w3-red" style="display: none"></div>
		<div class="w3-container w3-margin-left w3-margin-right w3-padding">
			<div class="w3-row w3-container">
				<div class="w3-col w3-third">
					<label for=""><%[ Client ]%>:<label>
				</div>
				<div class="w3-col w3-third">&nbsp;</div>
				<div class="w3-col w3-third">
					<label for=""><%[ Storage ]%>:<label>
				</div>
			</div>
			<div class="w3-row w3-padding">
				<div class="w3-col w3-third">
					<com:TActiveDropDownList
						ID="NetworkTestClient"
						CssClass="w3-select w3-border"
					/>
				</div>
				<div class="w3-col w3-third w3-center"><i class="w3-xxxlarge fa-solid fa-exchange-alt"></i></div>
				<div class="w3-col w3-third">
					<com:TActiveDropDownList
						ID="NetworkTestStorage"
						CssClass="w3-select w3-border"
					/>
				</div>
			</div>
			<i class="fa-solid fa-wrench"></i> <a href="javascript:void(0)" onclick="$('#network_test_advanced_options<%=$this->ClientID%>').toggle('fast');"><%[ Advanced options ]%></a>
			<div id="network_test_advanced_options<%=$this->ClientID%>" class="w3-row w3-container w3-margin-top w3-center" style="display: none;">
				<com:Bacularis.Web.Portlets.DirectiveSize
					ID="NetworkTestBytes"
					DirectiveName="NetworkTestBytes"
					Label="<%[ Data size to transfer ]%>"
					DefaultValue="<%=Bacularis\Web\Portlets\NetworkTest::DEFAULT_BYTES%>"
					Show="true"
					ShowResetButton="false"
					ShowRemoveButton="false"
				/>
			</div>
			<div class="w3-row w3-container w3-margin-top w3-center">
				<com:TActiveLinkButton
					ID="NetworkTestStart"
					OnCallback="startNetworkTest"
					CssClass="w3-button w3-green"
					Attributes.onclick="oNetworkTest<%=$this->ClientID%>.show_results(true);"
					ActiveControl.ClientSide.RequestTimeOut="20000"
				>
					<prop:ActiveControl.ClientSide.OnLoading>
						oNetworkTest<%=$this->ClientID%>.show_loaders();
					</prop:ActiveControl.ClientSide.OnLoading>
					<prop:ActiveControl.ClientSide.OnFailure>
						const emsg = 'Timeout occured. Please check if selected client and storage are accessible.';
						oNetworkTest<%=$this->ClientID%>.set_error(emsg);
						oNetworkTest<%=$this->ClientID%>.reset_window();
					</prop:ActiveControl.ClientSide.OnFailure>
					<%[ Start test ]%>&nbsp; <i class="fa-solid fa-play"></i>
				</com:TActiveLinkButton>
			</div>
			<div id="network_test_results<%=$this->ClientID%>" style="display: none;">
				<div class="w3-row w3-container">
					<h4><%[ Result ]%></h4>
				</div>
				<div class="w3-row w3-padding w3-card" style="height: 88px">
					<div class="w3-col w3-third" style="margin-top: 21px">
						<div class="w3-right">
							<i class="w3-xlarge fa-solid fa-desktop"></i> &nbsp;<label for=""><%[ Client ]%><label>
						</div>
					</div>
					<div class="w3-col w3-third w3-center"><i class="w3-xxxlarge fa-solid fa-right-long"></i><div id="write_speed<%=$this->ClientID%>"></div></div>
					<div class="w3-col w3-third" style="margin-top: 21px">
						<i class="w3-xlarge fa-solid fa-database"></i> &nbsp;<label for=""><%[ Storage ]%><label>
					</div>
				</div>
				<div class="w3-row w3-padding w3-card" style="height: 88px">
					<div class="w3-col w3-third" style="margin-top: 21px">
						<div class="w3-right">
							<i class="w3-xlarge fa-solid fa-desktop"></i> &nbsp;<label for=""><%[ Client ]%><label>
						</div>
					</div>
					<div class="w3-col w3-third w3-center"><i class="w3-xxxlarge fa-solid fa-left-long"></i><div id="read_speed<%=$this->ClientID%>"></div></div>
					<div class="w3-col w3-third" style="margin-top: 21px">
						<i class="w3-xlarge fa-solid fa-database"></i> &nbsp;<label for=""><%[ Storage ]%><label>
					</div>
				</div>
				<div class="w3-row w3-padding w3-card w3-small w3-center" style="height: 30px">
					<span class="w3-left-align"># <%[ Packets ]%>: <span id="stat_result_packets<%=$this->ClientID%>" class="bold w3-margin-right w3-show-inline-block" style="width: 50px">-</span></span>
					<span class="w3-left-align"><%[ Duration ]%>: <span id="stat_result_duration<%=$this->ClientID%>" class="bold w3-margin-right w3-show-inline-block" style="width: 50px">-</span></span>
					<span class="w3-left-align"><%[ Round-trip time (RTT) ]%>: <span id="stat_result_rtt<%=$this->ClientID%>" class="bold w3-margin-right w3-show-inline-block" style="width: 50px">-</span></span>
					<span class="w3-left-align"><%[ Minimum time ]%>: <span id="stat_result_min<%=$this->ClientID%>" class="bold w3-margin-right w3-show-inline-block" style="width: 50px">-</span></span>
					<span class="w3-left-align"><%[ Maximum time ]%>: <span id="stat_result_max<%=$this->ClientID%>" class="bold w3-margin-right w3-show-inline-block" style="width: 50px">-</span></span>
				</div>
				<div class="w3-row w3-padding w3-card" style="min-height: 30px">
					<i class="fa-solid fa-file-lines"></i> <a href="javascript:void(0)" onclick="$('#network_test_raw_output<%=$this->ClientID%>').toggle('fast');"><%[ Raw output ]%></a>
					<div id="network_test_raw_output<%=$this->ClientID%>" class="w3-code" style="display: none">
						<a id="raw_output_copy<%=$this->ClientID%>" href="javascript:void(0)" class="w3-margin-top w3-margin-right w3-right raw" title="<%[ Copy ]%>" onclick="oNetworkTest<%=$this->ClientID%>.copy_raw();">
							<i class="fa-solid fa-copy"></i>
						</a>
						<i id="raw_output_copied<%=$this->ClientID%>" class="fa-solid fa-check w3-margin-top w3-margin-right w3-right raw" style="display: none"></i>
						<pre class="w3-small"></pre>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
<script>
var oNetworkTest<%=$this->ClientID%> = {
	ids: {
		win: 'network_test_window<%=$this->ClientID%>',
		write_speed: 'write_speed<%=$this->ClientID%>',
		read_speed: 'read_speed<%=$this->ClientID%>',
		packets: 'stat_result_packets<%=$this->ClientID%>',
		duration: 'stat_result_duration<%=$this->ClientID%>',
		rtt: 'stat_result_rtt<%=$this->ClientID%>',
		min: 'stat_result_min<%=$this->ClientID%>',
		max: 'stat_result_max<%=$this->ClientID%>',
		results: 'network_test_results<%=$this->ClientID%>',
		raw: 'network_test_raw_output<%=$this->ClientID%>',
		copy: 'raw_output_copy<%=$this->ClientID%>',
		copied: 'raw_output_copied<%=$this->ClientID%>',
		error: 'network_test_error<%=$this->ClientID%>'
	},
	sts: {
		success: 'OK'
	},
	raw: [],
	show: function(show) {
		this.reset_window();
		const win = document.getElementById(this.ids.win);
		win.style.display = show ? 'block' : 'none';
	},
	reset_window: function() {
		this.show_results(false);
		this.set_raw([]);
		const none = '-';
		this.set_write_speed(none);
		this.set_read_speed(none);
		this.reset_stats();
	},
	reset_stats: function() {
		const none = '-';
		this.set_stat_packets(none);
		this.set_stat_duration(none);
		this.set_stat_rtt(none);
		this.set_stat_min(none);
		this.set_stat_max(none);
	},
	set_result: function(result) {
		const self = oNetworkTest<%=$this->ClientID%>;
		if (result.hasOwnProperty('raw')) {
			self.set_raw(result.raw);
		}
		if (result.hasOwnProperty('write')) {
			self.set_write(result.write);
		}
		if (result.hasOwnProperty('read')) {
			self.set_read(result.read);
		}
		if (result.hasOwnProperty('stat')) {
			self.set_stat(result.stat);
		}
	},
	set_write: function(result) {
		if (result.status === this.sts.success) {
			this.set_write_speed(result.write_speed);
		}
	},
	set_read: function(result) {
		if (result.status === this.sts.success) {
			this.set_read_speed(result.read_speed);
		}
	},
	set_stat: function(result) {
		if (result.status === this.sts.success) {
			this.set_stat_packets(result.packets);
			this.set_stat_duration(result.duration);
			this.set_stat_rtt(result.rtt);
			this.set_stat_min(result.min);
			this.set_stat_max(result.max);
		}
	},
	set_write_speed: function(speed) {
		const ws = document.getElementById(this.ids.write_speed);
		ws.innerHTML = speed;
	},
	set_read_speed: function(speed) {
		const rs = document.getElementById(this.ids.read_speed);
		rs.innerHTML = speed;
	},
	set_stat_packets: function(packets) {
		const pk = document.getElementById(this.ids.packets);
		pk.textContent = packets;
	},
	set_stat_duration: function(duration) {
		const dr = document.getElementById(this.ids.duration);
		dr.textContent = duration;
	},
	set_stat_rtt: function(rtt) {
		const rt = document.getElementById(this.ids.rtt);
		rt.textContent = rtt;
	},
	set_stat_min: function(min) {
		const mn = document.getElementById(this.ids.min);
		mn.textContent = min;
	},
	set_stat_max: function(max) {
		const mx = document.getElementById(this.ids.max);
		mx.textContent = max;
	},
	set_raw: function(raw) {
		this.raw = raw;
		const rw = document.getElementById(this.ids.raw).querySelector('pre');
		rw.textContent = raw.join("\n");
	},
	show_loaders: function() {
		this.show_error(false);
		const wloader = document.createElement('I');
		wloader.classList.add('fa-solid', 'fa-sync-alt', 'fa-spin');
		this.set_write_speed(wloader.outerHTML);
		const rloader = document.createElement('I');
		rloader.classList.add('fa-solid', 'fa-sync-alt', 'fa-spin');
		this.set_read_speed(rloader.outerHTML);
		this.reset_stats();
	},
	show_results: function(show) {
		const res = $('#' + this.ids.results);
		if (show) {
			res.slideDown('fast');
		} else {
			res.slideUp('fast');
		}
	},
	copy_raw: function() {
                const copy = document.getElementById(this.ids.copy);
                const copied = document.getElementById(this.ids.copied);
                const log = this.raw.join("\n");
                copy_to_clipboard(log);
                copy.style.display = 'none';
                copied.style.display = 'inline-block';
                setTimeout(() => {
                        copied.style.display = 'none';
                        copy.style.display = 'inline-block';
                }, 1300);
	},
	set_error: function(error) {
		const self = oNetworkTest<%=$this->ClientID%>;
		const err = document.getElementById(self.ids.error);
		err.textContent = error;
		self.show_error(true);
	},
	show_error: function(show) {
		const err = document.getElementById(this.ids.error);
		err.style.display = show ? 'block' : 'none';
	}
};
</script>
