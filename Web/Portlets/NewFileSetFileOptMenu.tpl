<div id="<%=$this->ClientID%>_new_fileset" class="w3-card w3-padding left config_new_fileset" style="display: none">
	<i class="fa fa-times w3-right" onclick="$('#<%=$this->ClientID%>_new_fileset').hide();" /></i>
	<ul class="w3-ul new_element_menu">
		<li><com:TActiveLinkButton
			ID="IncludeFileItem"
			OnCommand="Parent.SourceTemplateControl.newIncludeFile"
			ClientSide.OnComplete="var el1 = $('#<%=$this->IncludeFileItem->ClientID%>').parents('div').find('div.include_file')[<%=$this->Parent->ItemIndex%>]; var el2 = $(el1).find('div'); BaculaConfig.scroll_to_element(el2[el2.length-2], 0); $(el2[el2.length-2]).find('input')[0].focus();"
			Attributes.onclick="$(this).closest('div.config_new_fileset').hide();"
			>
			<i class='fa fa-plus'></i> &nbsp;<%[ Add single file/directory ]%>
		</com:TActiveLinkButton>
		</li>
		<li><com:TLinkButton
			ID="IncludeFileItemByBrowser"
			Attributes.onclick="$(this).closest('div.config_new_fileset').hide(); oFileSetBrowser<%=$this->FileSetBrowserId%>.reset(); $('#<%=$this->FileSetBrowserId%>fileset_browser').show(); return false;"
			>
			<i class='fa fa-plus'></i> &nbsp;<%[ Add files by file browser ]%>
		</com:TLinkButton>
		</li>
		<li><com:TActiveLinkButton
			ID="OptionsItem"
			OnCommand="Parent.SourceTemplateControl.newIncludeOptions"
			ClientSide.OnComplete="var el1 = $('#<%=$this->OptionsItem->ClientID%>').parents('div').find('div.incexc')[<%=$this->Parent->ItemIndex%>]; var el2 = $(el1).find('h3.options'); BaculaConfig.scroll_to_element(el2[el2.length-1], -80);"
			Attributes.onclick="$(this).closest('div.config_new_fileset').hide();"
			>
			<i class='fa fa-plus'></i> &nbsp;<%[ Add options block ]%>
		</com:TActiveLinkButton>
		</li>
		<li><com:TLinkButton
			ID="PluginsItem"
			Attributes.onclick="oPlugins.show_plugin_settings_window(true); return false;"
			>
			<i class='fa fa-plus'></i> &nbsp;<%[ Add plugin ]%>
		</com:TLinkButton>
		</li>
	</ul>
</div>
<div id="plugin_list_plugin_settings_window" class="w3-modal">
	<div class="w3-modal-content w3-animate-top w3-card-4">
		<header class="w3-container w3-green">
			<span onclick="oPlugins.show_plugin_settings_window(false);" class="w3-button w3-display-topright">&times;</span>
			<h2><%[ Add plugin settings ]%></h2>
		</header>
		<div class="w3-container w3-padding-large" style="padding-bottom: 0 !important;">
			<span id="plugin_list_plugin_settings_exists" class="error" style="display: none"><%[ Plugin settings with the given name already exists. ]%></span>
			<span id="plugin_list_plugin_settings_error" class="error" style="display: none"></span>
			<div class="w3-row directive_field">
				<div class="w3-col">
					<div class="w3-section">
						<input type="radio" id="plugin_setting_new" class="w3-radio" name="plugin_setting" value="1" checked onclick="const el = $('#plugin_settings_list'); $('div[rel=ps]').hide();" /> &nbsp;<label for="plugin_setting_new"><%[ Add blank new Plugin directive ]%></label><br />
					</div>
					<div class="w3-section">
						<input type="radio" id="plugin_setting_existing" class="w3-radio" name="plugin_setting" value="2" onclick="const el = $('#plugin_settings_list'); $('div[rel=ps]').hide(); if (this.checked) { el.slideDown('fast'); }" /> &nbsp;<label for="plugin_setting_existing"><%[ Use existing Plugin configuration ]%></label><br />
						<div id="plugin_settings_list" class="w3-row directive_field" rel="ps" style="display: none">
							<div class="w3-col w3-third"><label for="<%=$this->PluginSettingList->ClientID%>"><%[ Select configuration ]%>:</label></div>
							<div class="w3-half w3-show-inline-block">
								<com:TActiveDropDownList
									ID="PluginSettingList"
									CssClass="w3-select w3-border"
									ActiveControl.EnableUpdate="false"
								/>
							</div>
						</div>
					</div>
					<div class="w3-section">
						<input type="radio" id="plugin_setting_config" class="w3-radio" name="plugin_setting" value="3" onclick="const el = $('#plugin_plugin_list'); $('div[rel=ps]').hide(); if (this.checked) { el.slideDown('fast'); }" /> &nbsp;<label for="plugin_setting_config"><%[ Create a new plugin configuration and use it in the Plugin directive ]%></label><br />
						<div id="plugin_plugin_list" class="w3-row directive_field" rel="ps" style="display: none">
							<div class="w3-row directive_field">
								<div class="w3-col w3-third"><label for="<%=$this->PluginSettingsName->ClientID%>"><%[ Name ]%>:</label></div>
								<div class="w3-half w3-show-inline-block">
									<com:TActiveTextBox
										ID="PluginSettingsName"
										CssClass="w3-input w3-border"
										Attributes.pattern="<%=PluginConfigBase::SETTINGS_NAME_PATTERN%>"
										Attributes.required="required"
									/>
								</div>
							</div>
							<div class="w3-row directive_field">
								<div class="w3-col w3-third"><label for="<%=$this->PluginSettingList->ClientID%>"><%[ Select plugin ]%>:</label></div>
								<div class="w3-half w3-show-inline-block">
									<com:TActiveDropDownList
										ID="PluginPluginList"
										CssClass="w3-select w3-border"
										AutoPostBack="false"
										Attributes.onchange="oPlugins.load_plugin_settings_form(this.value);"
									/>
								</div>
							</div>
							<div id="plugin_list_plugin_settings_form"></div>
						</div>
					</div>
				</div>
			</div>
			<com:TActiveHiddenField ID="PluginSettingsWindowMode" />
			<div id="plugin_list_plugin_settings_form"></div>
		</div>
		<com:TCallback ID="AddPluginSettings" OnCallback="Parent.SourceTemplateControl.newIncludePlugin" />
		<com:TCallback ID="UsePluginSettings" OnCallback="usePluginSettings" />
		<com:TCallback ID="SavePluginSettings" OnCallback="savePluginSettings" />
		<footer class="w3-container w3-padding-large w3-center">
			<button type="button" class="w3-button w3-red" onclick="oPlugins.show_plugin_settings_window(false);"><i class="fas fa-times"></i> &nbsp;<%[ Cancel ]%></button>
			<button type="button" class="w3-button w3-green" onclick="oPlugins.add_plugin_settings();"><i class="fa-solid fa-plus"></i> &nbsp;<%[ Add ]%></button>
		</footer>
	</div>
</div>
<script>
var oPlugins = {
	ids: {
		win: 'plugin_list_plugin_settings_window',
		form: 'plugin_list_plugin_settings_form',
		opt_new: 'plugin_setting_new',
		opt_existing: 'plugin_setting_existing',
		opt_config: 'plugin_setting_config',
		menu: '<%=$this->ClientID%>_new_fileset',
		plugin_list: '<%=$this->PluginSettingList->ClientID%>'
	},
	plugins: <%=json_encode($this->plugins)%>,
	show_plugin_settings_window: function(show) {
		const win = document.getElementById(oPlugins.ids.win);
		win.style.display = show ? 'block' : 'none';
		if (show) {
			const menu = document.getElementById(oPlugins.ids.menu);
			menu.style.display = 'none';
		}
	},
	add_plugin_settings: function() {
		const opt_new = document.getElementById(this.ids.opt_new);
		const opt_existing = document.getElementById(this.ids.opt_existing);
		const opt_config = document.getElementById(this.ids.opt_config);
		const plugin_list = document.getElementById(this.ids.plugin_list);
		if (opt_new.checked) {
			const cb = <%=$this->AddPluginSettings->ActiveControl->Javascript%>;
			cb.dispatch();
		} else if (opt_existing.checked && plugin_list.selectedIndex > 0) {
			const cb = <%=$this->UsePluginSettings->ActiveControl->Javascript%>;
			cb.dispatch();
		} else if (opt_config.checked) {
			oPluginForm.save_form();
		}
	},
	load_plugin_settings_form: function(name) {
		oPluginForm.clear_plugin_settings_form();
		if (name != 'none') {
			const data = oPlugins.plugins[name];
			oPluginForm.build_form(data);
			oPluginForm.set_form_fields('', data.parameters);
		}
	}
};
// Plugin settings
oPluginForm.init({
	ids: {
		plugin_form: 'plugin_list_plugin_settings_form',
		settings_name: '<%=$this->PluginSettingsName->ClientID%>'
	},
	save_cb: <%=$this->SavePluginSettings->ActiveControl->Javascript%>
});
</script>
