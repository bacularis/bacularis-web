<div id="quick_volume_edit" class="w3-sidebar w3-animate-right w3-border-black w3-border-left w3-bar-block w3-padding" style="width: 53%; top: 0; right:0; bottom: 0; z-index: 3; display: none;">
	<h3 style="margin-top: 45px; margin-bottom: 0;"><span id="quick_volume_edit_mediaid"></span> <span id="quick_volume_edit_volumename"></span> <i class="fas fa-times w3-right" style="cursor: pointer; padding: 8px 16px 0 8px;" onclick="show_quick_volume_edit(false);"></i></h3>
	<com:Bacularis.Web.Portlets.VolumeConfig
		ID="QuickVolumeEditDirectives"
		DisplayLog="false"
		SaveVolumeActionOk="show_quick_volume_edit(false); <%=$this->SaveVolumeActionOk%>"
	/>
	<com:TCallback
		ID="QuickVolumeEditCb"
		OnCallback="openQuickVolumeEdit"
	/>
	<script>
	function open_quick_volume_edit(mediaid, volumename) {
		set_quick_volume_edit_header(mediaid, volumename);
		show_quick_volume_edit(true);
		const cb = <%=$this->QuickVolumeEditCb->ActiveControl->Javascript%>;
		cb.setCallbackParameter(mediaid);
		cb.dispatch();
	}
	function set_quick_volume_edit_header(mediaid, volumename) {
		const rc = document.getElementById('quick_volume_edit');
		rc.style.width = is_small ? '100%' : '53%';
		const rt = document.getElementById('quick_volume_edit_mediaid');
		rt.textContent = '[MediaId ' + mediaid + ']';
		const rn = document.getElementById('quick_volume_edit_volumename');
		rn.textContent = volumename;
	}
	function show_quick_volume_edit(show) {
		const container = document.getElementById('quick_volume_edit');
		container.style.display = show ? 'block' : 'none';
	}
	</script>
</div>
