<com:TActiveLabel ID="RemoveResourceError" Display="None" CssClass="w3-text-red" />
<div id="resource_remove_ok" class="w3-modal" style="display: none">
	<div class="w3-modal-content w3-card-4 w3-green w3-padding-large w3-animate-zoom" style="width:600px">
		<span onclick="document.getElementById('resource_remove_ok').style.display='none'; window.history.back();" class="w3-button w3-xlarge w3-hover-red w3-display-topright">&times;</span>
		<p><com:TActiveLabel ID="RemoveResourceOk" Display="None" /></p>
	</div>
</div>
<div class="w3-modal resource_remove_confirm" style="display: none">
	<div class="w3-modal-content w3-card-4 w3-padding-large w3-animate-zoom" style="width:600px">
        	<span onclick="$(this).closest('div.resource_remove_confirm').hide();" class="w3-button w3-xlarge w3-hover-red w3-display-topright">&times;</span>
		<h2><%[ Remove resource ]%></h2>
		<p><%[ Are you sure you want to remove this resource? ]%></p>
		<div class="w3-center">
			<button type="button" class="w3-button w3-red" onclick="$(this).closest('div.resource_remove_confirm').hide();"><i class="fa fa-times"></i> &nbsp;<%[ Cancel ]%></button>
			<com:TActiveLinkButton
				ID="RemoveResource"
				OnCommand="SourceTemplateControl.removeResource"
				CssClass="w3-button w3-green button_fixed"
				Visible="<%=$this->ShowRemoveButton && $this->LoadValues%>"
				Attributes.onclick="$(this).closest('div.resource_remove_confirm').hide();"
			>
				<prop:Text><i class="fa fa-trash-alt"></i> &nbsp;<%=Prado::localize('Remove resource')%></prop:Text>
				<prop:ClientSide.OnComplete>
					var remove_ok = document.getElementById('<%=$this->RemoveResourceOk->ClientID%>');
					if (remove_ok.style.display != 'none') {
						$('#<%=$this->ConfigDirectives->ClientID%>').slideUp();
						document.getElementById('resource_remove_ok').style.display = 'block';
					}
				</prop:ClientSide.OnComplete>
			</com:TActiveLinkButton>
		</div>
	</div>
</div>
<com:TActivePanel ID="ConfigDirectives" Style="margin-bottom: 48px">
	<com:TActiveRepeater
		ID="RepeaterDirectives"
		ItemRenderer="Application.Web.Portlets.DirectiveRenderer"
		>
	</com:TActiveRepeater>
	<div class="w3-row w3-center<%=$this->ShowBottomButtons ? ' w3-border bottom_buttons' : ''%> page_main_el"<%=$this->ShowBottomButtons ? ' style="margin-left: 250px"' : ''%>>
		<com:Application.Web.Portlets.DirectiveSetting
			ID="DirectiveSetting"
			Resource="<%=$this->getResource()%>"
			OnLoadDirectives="loadDirectives"
			Visible="<%=$this->LoadValues && !$this->CopyMode%>"
		/>
		<com:TActiveLinkButton
			CssClass="w3-button w3-red w3-right"
			Attributes.onclick="$(this).parent().parent().prev('div.resource_remove_confirm').show();"
			Visible="<%=$this->ShowRemoveButton && $this->LoadValues%>"
		>
			<prop:Text><i class="fa fa-trash-alt"></i> &nbsp;<%=Prado::localize('Remove resource')%></prop:Text>
		</com:TActiveLinkButton>
		<com:TActiveLinkButton
			ID="Cancel"
			CssClass="w3-button w3-red"
			ActiveControl.EnableUpdate="false"
			OnCommand="TemplateControl.unloadDirectives"
			Attributes.onclick="$('div.config_directives').slideUp();"
		>
			<prop:Text>
				<i class="fa fa-times"></i> &nbsp;<%=Prado::localize('Cancel')%>
			</prop:Text>
		</com:TActiveLinkButton>
		<com:TActiveLinkButton
			ID="Save"
			CssClass="w3-button w3-green"
			ValidationGroup="Directive"
			ActiveControl.EnableUpdate="false"
			OnCommand="SourceTemplateControl.saveResource"
			CommandParameter="save"
		>
			<prop:Text>
				<i class="fa fa-save"></i> &nbsp;<%=$this->getLoadValues() && !$this->getCopyMode() ? Prado::localize('Save') : Prado::localize('Create')%>
			</prop:Text>
			<prop:ClientSide.OnLoading>
				$('.save_progress').css({'visibility': '', 'display': 'inline-block'});
				$('.save_done').css({'visibility': 'visible', 'display': 'none'});
			</prop:ClientSide.OnLoading>
			<prop:ClientSide.OnComplete>
				$('.save_progress').css({'visibility': '',  'display': 'none'});
				$('.save_done').css({'visibility': 'visible', 'display': 'inline-block'});
				var err_el = '<%=$this->SaveDirectiveError->ClientID%>';
				if (document.getElementById(err_el).style.display == 'none') {
					<%=$this->SaveDirectiveActionOk%>
				}
			</prop:ClientSide.OnComplete>
			<prop:ClientSide.OnFailure>
				$('.save_progress').css({'visibility': '', 'display': 'none'});
				$('.save_done').css({'visibility': 'visible', 'display': 'inline-block'});
			</prop:ClientSide.OnFailure>
		</com:TActiveLinkButton>
		<span class="save_progress" style="width: 70px; visibility: hidden; display: inline-block;"><i class="fa fa-sync-alt w3-spin"></i></span>
		<div class="save_done" style="display: none; min-width: 70px;">
			<com:TActiveLabel ID="SaveDirectiveOk" Display="None" CssClass="w3-text-green"><i class="fa fa-check save_done"></i> &nbsp;<%[ OK ]%></com:TActiveLabel>
			<com:TActiveLabel ID="SaveDirectiveError" Display="None" CssClass="w3-text-red"><i class="fa fa-times-circle save_done"></i> &nbsp;<%[ Error ]%></com:TActiveLabel>
			<com:TActiveLabel ID="SaveDirectiveErrMsg" Display="None" CssClass="w3-text-red" />
		</div>
	</div>
</com:TActivePanel>
