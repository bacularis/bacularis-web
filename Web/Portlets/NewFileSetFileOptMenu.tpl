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
		<li><com:TActiveLinkButton
			ID="PluginsItem"
			OnCommand="Parent.SourceTemplateControl.newIncludePlugin"
			ClientSide.OnComplete="var el1 = $('#<%=$this->PluginsItem->ClientID%>').parents('div').find('div.incexc')[<%=$this->Parent->ItemIndex%>]; var el2 = $(el1).find('div.directive_field'); BaculaConfig.scroll_to_element(el2[el2.length-1], -80); $(el2[el2.length-1]).find('input')[0].focus();"
			Attributes.onclick="$(this).closest('div.config_new_fileset').hide();"
			>
			<i class='fa fa-plus'></i> &nbsp;<%[ Add plugin ]%>
		</com:TActiveLinkButton>
		</li>
	</ul>
</div>
