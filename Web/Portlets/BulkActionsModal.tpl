<div id="bulk_actions_modal" class="w3-modal" style="display: none">
	<div class="w3-modal-content w3-card-4 w3-animate-zoom" style="width: 830px">
		<header class="w3-container w3-green">
			<span onclick="oBulkActionsModal.show_output(false);" class="w3-button w3-display-topright">×</span>
			<h2><%[ Bulk action ]%></h2>
		</header>
		<div class="w3-margin-left w3-margin-right" style="max-height: 400px; overflow-x: auto;">
			<div class="w3-code">
				<pre><com:TActiveLabel ID="BulkActionsOutput" /></pre>
			</div>
		</div>
		<footer class="w3-container w3-center w3-border-top">
			<button type="button" class="w3-button w3-section w3-green" onclick="oBulkActionsModal.show_output(false);"><i class="fa fa-check"></i> &nbsp;<%[ OK ]%></button>
			<button id="bulk_actions_refresh_page" type="button" class="w3-button w3-section w3-green" style="display: none" onclick="oBulkActionsModal.refresh_page();"><i class="fas fa-redo-alt"></i> &nbsp;<%[ Refresh page ]%></button>
			<i id="bulk_actions_loader" class="fa fa-sync w3-spin w3-margin-left"></i>
		</footer>
	</div>
</div>
<div id="bulk_actions_validation_error_modal" class="w3-modal" style="display: none">
	<div class="w3-modal-content w3-card-4 w3-animate-zoom" style="width: 600px">
		<header class="w3-container w3-red">
			<span onclick="oBulkActionsModal.show_error(false);" class="w3-button w3-display-topright">×</span>
			<h2><%[ Validation error ]%></h2>
		</header>
		<div class="w3-margin-left w3-margin-right" style="max-height: 400px; overflow-x: auto;">
			<p id="bulk_actions_validation_error_txt"></p>
		</div>
		<footer class="w3-container w3-center w3-border-top">
			<button type="button" class="w3-button w3-section w3-green" onclick="oBulkActionsModal.show_error(false);"><i class="fas fa-check"></i> &nbsp;<%[ OK ]%></button>
		</footer>
	</div>
</div>
<script>
var oBulkActionsModal = {
	show_loader: function(show) {
		document.getElementById('bulk_actions_loader').style.display = show ? '' : 'none';
	},
	clear_output: function(id) {
		document.getElementById(id).textContent = '';
	},
	show_output: function(show) {
		document.getElementById('bulk_actions_modal').style.display = show ? 'block' : '';
	},
	set_error: function(emsg) {
		document.getElementById('bulk_actions_validation_error_txt').innerHTML = emsg;
		this.show_error(true);
	},
	show_error: function(show) {
		document.getElementById('bulk_actions_validation_error_modal').style.display = show ? 'block' : '';
	},
	refresh_page: function() {
		document.location.reload();
	},
	show_refresh_btn: function(show) {
		document.getElementById('bulk_actions_refresh_page').style.display = show ? 'block' : '';
	},
};
</script>
