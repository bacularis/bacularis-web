<div id="<%=$this->ClientID%>_new_runscript" class="w3-card w3-padding config_new_runscript left" style="display: none">
	<i class="fa fa-times w3-right" onclick="$('#<%=$this->ClientID%>_new_runscript').hide();" /></i>
	<ul class="w3-ul new_element_menu">
		<li><com:TActiveLinkButton
			ID="RunscriptItem"
			OnCommand="Parent.SourceTemplateControl.newRunscriptDirective"
			ClientSide.OnComplete="var el = $('#<%=$this->RunscriptItem->ClientID%>').parents('div').find('h3.runscript_options'); BaculaConfig.scroll_to_element(el[el.length-1], -40);"
			Attributes.onclick="$(this).closest('div.config_new_runscript').hide();"
			>
			<i class='fa fa-plus'></i> &nbsp;<%[ Add Runscript block ]%>
		</com:TActiveLinkButton>
		</li>
	</ul>
</div>
