<div id="<%=$this->ClientID%>_tab_view_container" class="w3-row">
	<span onclick="W3SubTabs.open('<%=$this->ClientID%>_tab_view_main', null, '<%=$this->ClientID%>_tab_view_container'); <%=$this->ClientID%>_TabViews.tabs.reset_filters(true);" class="w3-show-inline-block pointer">
		<div id="<%=$this->ClientID%>_tab_view_main" class="subtab_btn w3-bottombar w3-hover-light-grey w3-border-red w3-padding w3-show-inline-block" style="max-width: 180px"><%[ Main view ]%></div>
	</span><span id="<%=$this->ClientID%>_tab_views"></span>
	<button type="button" class="w3-button w3-light-gray w3-padding" title="<%[ Add data view ]%>" onclick="<%=$this->ClientID%>_TabViews.form.create_form();"><i class="fa-solid fa-plus"></i></button>
</div>
<div id="<%=$this->ClientID%>_tab_desc" class="w3-container w3-small" style="margin: 3px 0; height: 18px;"></div>
<div id="<%=$this->ClientID%>_form_window" class="w3-modal">
	<div class="w3-modal-content w3-animate-top w3-card-4">
		<header class="w3-container w3-green">
			<span onclick="$('#<%=$this->ClientID%>_form_window').hide();" class="w3-button w3-display-topright">&times;</span>
			<h2 id="<%=$this->ClientID%>_form_view_title_add" style="display: none"><%[ Add data view ]%></h2>
			<h2 id="<%=$this->ClientID%>_form_view_title_edit" style="display: none"><%[ Edit data view ]%></h2>
		</header>
		<div class="w3-container w3-margin-left w3-margin-right">
			<div class="w3-row directive_field w3-margin-bottom">
				<div class="w3-col w3-third">
					<%[ View name ]%>:
				</div>
				<div class="w3-col w3-half">
					<input type="text" id="<%=$this->ClientID%>_form_view_name" class="w3-input w3-border w3-show-inline-block" placeholder="<%[ My view 123 ]%>" pattern="<%=DataViewConfig::VIEW_PATTERN%>" />
				<i class="fa fa-asterisk w3-text-red"></i>
				</div>
			</div>
			<div class="w3-row directive_field w3-margin-bottom">
				<div class="w3-col w3-third">
					<%[ View description ]%>:
				</div>
				<div class="w3-col w3-half">
					<textarea id="<%=$this->ClientID%>_form_view_desc" class="w3-input w3-border" placeholder="<%[ This is My view 123 description. ]%>"></textarea>
				</div>
			</div>
			<i class="fa-solid fa-info-circle help_icon w3-text-green w3-right" onclick="const el = $('#<%=$this->ClientID%>_form_view_help'); el.slideToggle('fast');" style="margin-left: 4px !important"></i>
			<div id="<%=$this->ClientID%>_form_view_help"class="directive_help" style="display: none">
				<dd><%[ Between conditions with different properties is used AND operator. ]%> <%[ Between conditions with the same property is used OR operator.]%></dd>
				<ul>
					<li><strong><%[ Example 1 ]%></strong>: type == Backup AND level == Full AND name == ABC</li>
					<li><strong><%[ Example 2 ]%></strong>: type == Backup OR type == Restore OR type == Copy</li>
					<li><strong><%[ Example 3 ]%></strong>: type == Backup AND (level == Full OR level == Incremental)</li>
				</ul>
			</div>
			<div id="<%=$this->ClientID%>_form_view"></div>
		</div>
		<footer class="w3-container w3-center w3-margin-bottom">
			<button type="button" class="w3-button w3-red w3-section" onclick="$('#<%=$this->ClientID%>_form_window').hide();"><i class="fa-solid fa-times"></i> &nbsp;<%[ Cancel ]%></button>
			<button type="button" class="w3-button w3-green w3-section" onclick="<%=$this->ClientID%>_TabViews.form.save_config();"><i class="fa-solid fa-save"></i> &nbsp;<%[ Save ]%></button>
			<button type="button" id="<%=$this->ClientID%>_form_view_rm_btn" class="w3-button w3-red w3-section w3-right" onclick="<%=$this->ClientID%>_TabViews.form.remove_config();"><i class="fa-solid fa-trash-alt"></i> &nbsp;<%[ Remove ]%></button>
		</footer>
	</div>
</div>
<com:TJuiDatePicker Display="None" />
<com:TCallback ID="SaveViewConfig" OnCallback="saveConfig" />
<com:TCallback ID="RemoveViewConfig" OnCallback="removeConfig" />
<script>
$(function() {
	<%=$this->ClientID%>_TabViews = new DataView({
		main_id: '<%=$this->ClientID%>',
		desc: <%=json_encode($this->getDescription())%>,
		config: <%=json_encode($this->getConfig())%>,
		data_func: <%=$this->getViewDataFunction()%>,
		update_func: <%=$this->getUpdateViewFunction()%>,
		save_func: <%=$this->SaveViewConfig->ActiveControl->Javascript%>,
		remove_func: <%=$this->RemoveViewConfig->ActiveControl->Javascript%>
	});
});
</script>
