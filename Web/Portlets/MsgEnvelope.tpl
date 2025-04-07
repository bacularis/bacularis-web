<div id="msg_envelope_modal" class="w3-modal">
	<div class="w3-modal-content w3-animate-top w3-card-4">
		<header class="w3-container w3-green">
			<span onclick="MsgEnvelope.close();" class="w3-button w3-display-topright">&times;</span>
			<h2 class="w3-show-inline-block w3-half"><%[ Messages ]%></h2>
			<div class="w3-margin w3-right-align" style="margin-right: 32px !important;">
				<input type="search" id="msg_envelope_search" name="msg_envelope_search" class="w3-input w3-border" placeholder="<%[ Search... ]%>" value="" />
			</div>
		</header>
		<div class="w3-container w3-margin-left w3-margin-right">
			<div id="msg_envelope_container" class="w3-code" style="font-size: 12px; min-height: 50px; max-height: 610px; overflow-y: scroll; overflow-x: auto; position: relative;">
				<pre id="msg_envelope_content"></pre>
				<div id="msg_envelope_line_indicator" style="position: absolute; display: none;"><i class="fas fa-arrow-right w3-text-red"></i></div>
			</div>
			<div>
				<div id="msg_envelope_nav" class="w3-half">
					<span><%[ Jump to: ]%></span> &nbsp;
					<i id="msg_envelope_nav_down" class="fas fa-angle-down w3-large" style="cursor: pointer; user-select: none;" title="<%[ Previous error/warning ]%>"></i> &nbsp;
					<i id="msg_envelope_nav_up" class="fas fa-angle-up w3-large" style="cursor: pointer; user-select: none;" title="<%[ Next error/warning ]%>"></i>
				</div>
				<div class="w3-right-align">
					<%[ Filter ]%>:
					<span class="w3-tag w3-green w3-opacity pointer" onclick="MsgEnvelope.set_filter(this, 'info');" style="padding: 1px 12px; user-select: none;">
						<%[ info ]%> (<span id="msg_envelope_info_cnt">0</span>)
					</span>
					<span class="w3-tag w3-orange w3-opacity pointer" onclick="MsgEnvelope.set_filter(this, 'warning');" style="padding: 1px 12px; user-select: none;">
						<%[ warning ]%> (<span id="msg_envelope_warning_cnt">0</span>)
					</span>
					<span class="w3-tag w3-red w3-opacity pointer" onclick="MsgEnvelope.set_filter(this, 'error');" style="padding: 1px 12px; user-select: none;">
						<%[ error ]%> (<span id="msg_envelope_error_cnt">0</span>)
					</span>
				</div>
			</div>
		</div>
		<footer class="w3-container w3-center">
			<button class="w3-button w3-green w3-section" onclick="MsgEnvelope.close();"><i class="fas fa-times"></i> &nbsp;<%[ Close ]%></button>
			<button class="w3-button w3-red w3-section w3-margin-right" onclick="msg_envelope_truncate();"><i class="fas fa-cut"></i> &nbsp;<%[ Truncate log ]%></button>
		</footer>
	</div>
</div>
<com:TCallback
	ID="MsgEnvelopeTruncate"
	OnCallback="truncate"
	ClientSide.OnComplete="MsgEnvelope.set_logs([]); MsgEnvelope.mark_envelope_ok();"
/>
<script>
function msg_envelope_truncate() {
	var cb = <%=$this->MsgEnvelopeTruncate->ActiveControl->Javascript%>;
	cb.dispatch();
}
MsgEnvelope.init();
</script>
