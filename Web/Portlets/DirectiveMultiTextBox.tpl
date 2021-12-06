<com:TActiveRepeater ID="MultiTextBoxRepeater" OnItemDataBound="createMultiTextBoxElement">
	<prop:ItemTemplate>
		<div class="directive_field w3-row<%=!isset($this->Data['show']) ? ' hide' : '';%>">
			<div class="w3-col w3-quarter"><com:TActiveLabel ID="Label" ActiveControl.EnableUpdate="false" />:</div>
			<div class="w3-col w3-threequarter directive_value">
				<com:TActiveTextBox ID="Directive"
					CssClass="w3-input w3-border w3-twothird"
					ActiveControl.EnableUpdate="false"
					AutoTrim="true"
				/> &nbsp;<com:TActiveLinkButton ID="AddFieldBtn"
						OnCommand="SourceTemplateControl.addField"
						CommandParameter="save"
						ClientSide.OnComplete="var el = $('#<%=$this->SourceTemplateControl->MultiTextBoxRepeater->ClientID%>_Container').find('input[type=\'text\']'); el[el.length-1].focus();"
					><i class="fa fa-plus plus_btn" title="<%[ Add directive ]%>" alt="<%[ Add directive ]%>"></i></com:TActiveLinkButton>
				<i class="fas fa-info-circle help_icon w3-text-green" style="display: <%=($this->SourceTemplateControl->doc ? 'inline-block': 'none')%>;" onclick="var h = $(this).nextAll('div.directive_help'); var disp = h.get(0).style.display; $('div.directive_help').slideUp('fast'); if (disp == 'none') { h.slideDown('fast'); }"></i>
				<i class="fa fa-trash-alt remove_btn" onclick="document.getElementById('<%=$this->Directive->ClientID%>').value = '';" alt="<%[ Remove directive ]%>" title="<%[ Remove directive ]%>"></i>
				<div class="directive_help" style="clear: left; display: none"><%=$this->SourceTemplateControl->doc%></div>
			</div>
		</div>
	</prop:ItemTemplate>
</com:TActiveRepeater>
