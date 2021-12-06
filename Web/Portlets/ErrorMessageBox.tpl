<div id="error_message_box" class="w3-modal" style="display: none">
	<div class="w3-modal-content w3-card-4 w3-animate-zoom" style="width:600px">
		<header class="w3-container w3-red">
			<span onclick="document.getElementById('error_message_box').style.display='none'" class="w3-button w3-display-topright">×</span>
			<h2><%[ Error ]%></h2>
		</header>
		<div class="w3-panel w3-padding">
			<p><strong><%[ Error code: ]%></strong> <span id="error_message_error_code"></span></p>
			<p><strong><%[ Message: ]%></strong> <span id="error_message_error_msg"></span></p>
		</div>
		<footer class="w3-container w3-center">
			<button type="button" class="w3-button w3-section w3-red" onclick="document.getElementById('error_message_box').style.display='none'"><i class="fa fa-check"></i> &nbsp;<%[ OK ]%></button>
		</footer>
	</div>
</div>
<script>
function show_error(output, error) {
	var err_box = document.getElementById('error_message_box');
	var err_code = document.getElementById('error_message_error_code');
	var err_msg = document.getElementById('error_message_error_msg');
	err_code.textContent = error;
	err_msg.innerHTML = output;
	err_box.style.display = 'block';
}
</script>
