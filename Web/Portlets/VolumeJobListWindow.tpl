<div id="volume_job_list_window" class="w3-modal">
	<div class="w3-modal-content w3-animate-top w3-card-4" style="width: 90%">
		<header class="w3-container w3-green">
			<span onclick="document.getElementById('volume_job_list_window').style.display='none'" class="w3-button w3-display-topright">&times;</span>
			<h2><%[ Volume job list ]%> - <span id="volume_job_list_volumename"></span></h2>
		</header>
		<div class="w3-padding">
			<com:Bacularis.Web.Portlets.VolumeJobList
				ID="VolumeJobList"
			/>
		</div>
	</div>
</div>
<com:TCallback ID="JobsOnVolumeWindow" OnCallback="openWindow" />
<script>
const oJobsOnVolumeWindow = {
	open_window(mediaid, volumename) {
		if (volumename) {
			const volname = document.getElementById('volume_job_list_volumename');
			volname.textContent = volumename;
		}
		const cb = <%=$this->JobsOnVolumeWindow->ActiveControl->Javascript%>;
		cb.setCallbackParameter(mediaid);
		cb.dispatch();
	}
}
</script>
