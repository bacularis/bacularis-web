<button type="button" class="w3-button w3-green w3-margin-bottom" onmousedown="openElementOnCursor(event, '<%=$this->RunscriptMenu->ClientID%>_new_runscript', 0, 20);"><i class="fa fa-plus"></i> &nbsp;<%[ Add ]%></button>
<com:Bacularis.Web.Portlets.NewRunscriptMenu ID="RunscriptMenu" />
<com:TActiveRepeater ID="RepeaterRunscriptOptions" ItemRenderer="Bacularis.Web.Portlets.JobRunscriptRenderer">
	<prop:HeaderTemplate>
		<div class="w3-card-4 w3-padding w3-margin-bottom directive runscript">
	</prop:HeaderTemplate>
	<prop:FooterTemplate>
		</div>
	</prop:FooterTemplate>
</com:TActiveRepeater>
