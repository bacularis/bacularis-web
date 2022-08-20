 &nbsp;<a class="w3-hover-opacity" href="javascript:void(0)" onclick="oClientBandwidthLimit.open_popup();" title="<%[ Set client bandwidth limit ]%>"><i class="fas fa-tachometer-alt w3-large"></i></a>
<div id="client_bandwidth_limit_popup" class="w3-modal">
	<div class="w3-modal-content w3-animate-top w3-card-4">
		<header class="w3-container w3-green">
			<span onclick="oClientBandwidthLimit.close_popup();" class="w3-button w3-display-topright">&times;</span>
			<h2><%[ Set client bandwidth limit ]%> - <%[ Client: ]%> <%=$this->getClientName()%></h2>
		</header>
		<div class="w3-container w3-margin-left w3-margin-right">
			<p><%[ Here you can set bandwidth limit on client. This change is applied on running client. There is no need to restart it. ]%></p>
			<p><%[ Setting 0 (zero) value disables bandwidth limitation. ]%></p>
			<div class="w3-row w3-section w3-medium">
				<com:Bacularis.Web.Portlets.DirectiveSpeed
					ID="BandwidthLimit"
					DirectiveName="BandwidthLimit"
					DefaultValue="0"
					Show="true"
					Label="<%[ Bandwidth limit ]%>"
					UnitType="decimal"
					Attributes.onkeyup="var kc = (event.which || event.keyCode); if (kc == 13) oClientBandwidthLimit.set_bandwidth(); if (kc == 27) oClientBandwidthLimit.close_popup();"
				/>
			</div>
		</div>
		<footer class="w3-container w3-center">
			<a class="w3-button w3-red" href="javascript:void(0)" onclick="oClientBandwidthLimit.close_popup()"><i class="fas fa-times"></i> &nbsp<%[ Cancel ]%></a>
			<com:TActiveLinkButton
				ID="SetBandwidthLimit"
				OnCallback="setupBandwidthLimit"
				CssClass="w3-button w3-section w3-green w3-padding"
			>
				<prop:ClientSide.OnLoading>
					document.getElementById('client_bandwidth_limit_loading').style.visibility = 'visible';
				</prop:ClientSide.OnLoading>
				<prop:ClientSide.OnComplete>
					document.getElementById('client_bandwidth_limit_loading').style.visibility = 'hidden';
				</prop:ClientSide.OnComplete>
				<i class="fa fa-check"></i> &nbsp;<%[ Set bandwidth ]%>
			</com:TActiveLinkButton>
			<i id="client_bandwidth_limit_loading" class="fa fa-sync w3-spin" style="visibility: hidden;"></i>
		</footer>
		<div id="client_bandwidth_limit_log" class="w3-panel w3-card" style="display: none; max-height: 200px; overflow-x: auto;">
			<div class="w3-code">
				<pre><com:TActiveLabel ID="BandwidthLog" /></pre>
			</div>
		</div>
	</div>
</div>
<script>
var oClientBandwidthLimit = {
	ids: {
		popup: 'client_bandwidth_limit_popup',
		log: 'client_bandwidth_limit_log'
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
