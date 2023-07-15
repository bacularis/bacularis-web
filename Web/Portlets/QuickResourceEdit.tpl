<div id="quick_resource_edit" class="w3-sidebar w3-animate-right w3-border-black w3-border-left w3-bar-block w3-padding" style="width: 53%; top: 0; right:0; bottom: 0; z-index: 3; display: none;">
	<h3 style="margin-top: 45px; margin-bottom: 0;"><span id="quick_resource_edit_res_type"></span> <span id="quick_resource_edit_res_name"></span> <i class="fas fa-times w3-right" style="cursor: pointer; padding: 8px 16px 0 8px;" onclick="show_quick_resource_edit(false);"></i></h3>
	<com:Bacularis.Web.Portlets.BaculaConfigDirectives
		ID="QuickResourceEditDirectives"
		ShowSectionTabs="true"
		DisableRename="true"
		ShowBottomButtons="false"
		ShowRemoveButton="false"
		SaveDirectiveActionOk="show_quick_resource_edit(false);"
		CancelDirectiveActionOk="show_quick_resource_edit(false);"
	/>
	<com:TCallback ID="QuickResourceEditCb" OnCallback="openQuickResourceEdit" />
	<script>
	function open_quick_resource_edit(comp_type, res_type, res_name) {
		set_quick_resource_edit_header(res_type, res_name);
		show_quick_resource_edit(true);
		const cb = <%=$this->QuickResourceEditCb->ActiveControl->Javascript%>;
		cb.setCallbackParameter([
			comp_type,
			res_type,
			res_name
		]);
		cb.dispatch();
	}
	function set_quick_resource_edit_header(res_type, res_name) {
		const rc = document.getElementById('quick_resource_edit');
		rc.style.width = is_small ? '100%' : '53%';
		const rt = document.getElementById('quick_resource_edit_res_type');
		rt.textContent = (res_type + ':');
		const rn = document.getElementById('quick_resource_edit_res_name');
		rn.textContent = res_name;
	}
	function show_quick_resource_edit(show) {
		const container = document.getElementById('quick_resource_edit');
		container.style.display = show ? 'block' : 'none';
	}
	</script>
</div>
