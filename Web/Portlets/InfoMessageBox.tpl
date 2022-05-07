<div id="info_message_box" class="w3-modal" style="display: none">
	<div class="w3-modal-content w3-card-4 w3-animate-top">
		<header class="w3-container w3-green">
			<span onclick="document.getElementById('info_message_box').style.display='none'" class="w3-button w3-display-topright">×</span>
			<h2><%[ Information ]%></h2>
		</header>
		<div class="w3-panel w3-padding">
			<p id="info_message_info_msg"></p>
		</div>
		<footer class="w3-container w3-center">
			<button type="button" id="info_message_btn" class="w3-button w3-section w3-green"><i class="fa fa-check"></i> &nbsp;<%[ OK ]%></button>
		</footer>
	</div>
</div>
<script>
function show_info(text, cb, back) {
	const info_box = document.getElementById('info_message_box');
	const info_msg = document.getElementById('info_message_info_msg');
	const info_btn = $('#info_message_btn');
	info_msg.innerHTML = text;
	info_box.style.display = 'block';
	info_btn.off('click');
	info_btn.on('click', (e) => {
		info_box.style.display = 'none';
		if (typeof(cb) == 'function') {
			cb(e);
		}
		if (back) {
			window.location.href = document.referrer;
		}
	});
}
</script>
