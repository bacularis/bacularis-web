<com:TActivePanel ID="ConsoleContainer" DefaultButton="Enter" Style="text-align: left;">
	<div class="w3-container w3-padding-16">
		<com:TActiveTextBox ID="OutputListing" TextMode="MultiLine" CssClass="w3-input w3-border monospace" style="height: 450px" />
	</div>
	<div class="w3-container">
		<div class="w3-threequarter">
			<com:TActiveTextBox ID="CommandLine" TextMode="SingleLine" CssClass="w3-input w3-border" Style="float: left" Attributes.autofocus="autofocus" Attributes.placeholder="<%[ Type bconsole command ]%>" />
		</div>
		<div class="w3-quarter">
			<com:TActiveLinkButton ID="Enter" OnCommand="sendCommand" CssClass="w3-button w3-green w3-margin-left">
				<prop:ClientSide.OnLoading>
					document.getElementById('<%=$this->CommandLine->ClientID%>').readOnly = true;
				</prop:ClientSide.OnLoading>
				<prop:ClientSide.OnComplete>
					document.getElementById('<%=$this->OutputListing->ClientID%>').scrollTop = document.getElementById('<%=$this->OutputListing->ClientID%>').scrollHeight;
					document.getElementById('<%=$this->CommandLine->ClientID%>').readOnly = false;
					$('#<%=$this->CommandLine->ClientID%>').focus();
				</prop:ClientSide.OnComplete>
				<i class="fa fa-play"></i> &nbsp;<%[ Enter ]%>
			</com:TActiveLinkButton>
			<button class="w3-button w3-red" type="button" onclick="document.getElementById('<%=$this->OutputListing->ClientID%>').value = '';"><i class="fa fa-times"></i> &nbsp;<%[ Clear ]%></button>
		</div>
	</div>
	<script type="text/javascript">
	$('#<%=$this->CommandLine->ClientID%>').off('keydown');
	$('#<%=$this->CommandLine->ClientID%>').on('keydown', function(e) {
		if (e.keyCode == 27) { // close console on pressed ESC key
			show_hide_console();
		}
	});
	</script>
</com:TActivePanel>
