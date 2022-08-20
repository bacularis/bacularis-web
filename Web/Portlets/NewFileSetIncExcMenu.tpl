<div id="<%=$this->ClientID%>_new_fileset" class="w3-card w3-padding left config_new_fileset" style="display: none">
	<i class="fa fa-times w3-right" onclick="$('#<%=$this->ClientID%>_new_fileset').hide();" /></i>
	<ul class="w3-ul new_element_menu">
		<li><com:TActiveLinkButton
			ID="NewIncludeBlock"
			OnCommand="Parent.SourceTemplateControl.newIncludeBlock"
			ClientSide.OnComplete="var el = $('#<%=$this->NewIncludeBlock->ClientID%>').parents('div').find('div.incexc'); BaculaConfig.scroll_to_element(el[el.length-1]);"
			Text=""
			Attributes.onclick="$(this).closest('div.config_new_fileset').hide();"
			>
			<i class='fa fa-plus'></i> &nbsp;<%[ Add include block ]%>
		</com:TActiveLinkButton>
		</li>
		<li><com:TActiveLinkButton
			ID="NewExcludeBlock"
			OnCommand="Parent.SourceTemplateControl.newExcludeFile"
			ClientSide.OnComplete="var el = $('#<%=$this->NewExcludeBlock->ClientID%>').parents('div').find('div.exclude_file'); BaculaConfig.scroll_to_element(el[el.length-1]); $(el[el.length-1]).find('input')[0].focus();"
			Text=""
			Attributes.onclick="$(this).closest('div.config_new_fileset').hide();"
			>
			<i class='fa fa-plus'></i> &nbsp;<%[ Add exclude block ]%>
		</com:TActiveLinkButton>
		</li>
	</ul>
</div>
