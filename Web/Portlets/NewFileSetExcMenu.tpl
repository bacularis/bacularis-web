<div id="<%=$this->ClientID%>_new_fileset" class="w3-card w3-padding left config_new_fileset" style="display: none">
	<i class="fa fa-times w3-right" onclick="$('#<%=$this->ClientID%>_new_fileset').hide();" /></i>
	<ul class="w3-ul new_element_menu">
		<li><com:TActiveLinkButton
			ID="ExcludeFileItem"
			OnCommand="Parent.SourceTemplateControl.newExcludeFile"
			ClientSide.OnComplete="var el = $('#<%=$this->ExcludeFileItem->ClientID%>').parents('div').find('div.exclude_file');  BaculaConfig.scroll_to_element(el[el.length-1], -80); $(el[el.length-1]).find('input')[0].focus();"
			Attributes.onclick="$(this).closest('div.config_new_fileset').hide();"
			>
			<i class='fa fa-plus'></i> &nbsp;<%[ Add file/directory ]%>
		</com:TActiveLinkButton>
		</li>
	</ul>
</div>
