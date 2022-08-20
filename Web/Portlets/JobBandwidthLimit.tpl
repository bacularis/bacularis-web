<div id="job_bandwidth_limit_popup" class="w3-modal">
	<div class="w3-modal-content w3-animate-top w3-card-4">
		<header class="w3-container w3-green">
			<span onclick="oJobBandwidthLimit.close_popup();" class="w3-button w3-display-topright">&times;</span>
			<h2><%[ Set job bandwidth limit ]%> - <%[ JobId: ]%> <com:TActiveLabel ID="JobIdLabel" /></h2>
		</header>
		<div class="w3-container w3-margin-left w3-margin-right">
			<p><%[ Job: ]%> <strong><com:TActiveLabel ID="JobUnameLabel" /></strong></p>
			<p><%[ Here you can set bandwidth limit for job. This change is applied on running job. ]%></p>
			<p><%[ Setting 0 (zero) value disables bandwidth limitation. ]%></p>
			<div class="w3-row w3-section w3-medium">
				<com:Bacularis.Web.Portlets.DirectiveSpeed
					ID="BandwidthLimit"
					DirectiveName="BandwidthLimit"
					DefaultValue="0"
					Show="true"
					Label="<%[ Bandwidth limit ]%>"
					UnitType="decimal"
					Attributes.onkeyup="var kc = (event.which || event.keyCode); if (kc == 13) oJobBandwidthLimit.set_bandwidth(); if (kc == 27) oJobBandwidthLimit.close_popup();"
				/>
			</div>
		</div>
		<footer class="w3-container w3-center">
			<a class="w3-button w3-red" href="javascript:void(0)" onclick="oJobBandwidthLimit.close_popup()"><i class="fas fa-times"></i> &nbsp<%[ Cancel ]%></a>
			<com:TActiveLinkButton
				ID="SetBandwidthLimit"
				OnCallback="setupBandwidthLimit"
				CssClass="w3-button w3-section w3-green w3-padding"
			>
				<prop:ClientSide.OnLoading>
					document.getElementById('job_bandwidth_limit_loading').style.visibility = 'visible';
				</prop:ClientSide.OnLoading>
				<prop:ClientSide.OnComplete>
					document.getElementById('job_bandwidth_limit_loading').style.visibility = 'hidden';
				</prop:ClientSide.OnComplete>
				<i class="fa fa-check"></i> &nbsp;<%[ Set bandwidth ]%>
			</com:TActiveLinkButton>
			<i id="job_bandwidth_limit_loading" class="fas fa-sync w3-spin" style="visibility: hidden;"></i>
		</footer>
		<div id="job_bandwidth_limit_log" class="w3-panel w3-card" style="display: none; max-height: 200px; overflow-x: auto;">
			<div class="w3-code">
				<pre><com:TActiveLabel ID="BandwidthLog" /></pre>
			</div>
		</div>
	</div>
</div>
<script>
var oJobBandwidthLimit = {
	ids: {
		popup: 'job_bandwidth_limit_popup',
		log: 'job_bandwidth_limit_log'
	},
	open_popup: function() {
		document.getElementById(this.ids.popup).style.display = 'block';
		document.getElementById('<%=$this->BandwidthLimit->Directive->ClientID%>').focus();
	},
	close_popup: function() {
		document.getElementById(this.ids.popup).style.display = 'none';
		this.cleanup_log();
	},
	cleanup_log: function() {
		document.getElementById(this.ids.log).style.display = 'none';
		document.getElementById('<%=$this->BandwidthLog->ClientID%>').textContent = '';
	},
	set_value: function(value) {
		var bwlimit_val = document.getElementById('<%=$this->BandwidthLimit->Directive->ClientID%>');
		var bwlimit_unit = document.getElementById('<%=$this->BandwidthLimit->SpeedFormat->ClientID%>');
		var val = Units.format_speed(value, null, false, true);
		bwlimit_val.value = val.value;
		bwlimit_unit.value = Units.get_short_unit_by_long('speed', val.format) || val.format;
	},
	set_bandwidth: function() {
		$('#<%=$this->SetBandwidthLimit->ClientID%>').click();
	}
};
</script>
