<div id="<%=$this->ClientID%>_new_schedule" class="w3-card w3-padding config_new_schedule left" style="display: none">
	<i class="fa fa-times w3-right" onclick="$('#<%=$this->ClientID%>_new_schedule').hide();" /></i>
	<ul class="w3-ul new_element_menu">
		<li><com:TActiveLinkButton
			ID="RunscriptItem"
			OnCommand="Parent.SourceTemplateControl.newScheduleDirective"
			ClientSide.OnComplete="var el = $('#<%=$this->RunscriptItem->ClientID%>').parents('div').find('h2.schedule_options'); BaculaConfig.scroll_to_element(el[el.length-1], -50);"
			Attributes.onclick="$(this).closest('div.config_new_schedule').hide();"
			>
				<i class='fa fa-plus'></i> &nbsp;<%=$this->SourceTemplateControl->ComponentType == 'dir' ? Prado::localize('Add Run directive') : ($this->SourceTemplateControl->ComponentType == 'fd' ? Prado::localize('Add Connect directive') : '')%>
		</com:TActiveLinkButton>
		</li>
	</ul>
</div>
