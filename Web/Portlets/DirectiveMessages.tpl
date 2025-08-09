<button type="button" class="w3-button w3-green w3-margin" onclick="openElementOnCursor(event, '<%=$this->MessagesMenu->ClientID%>_new_messages', 0, 20);"><i class="fa fa-plus"></i> &nbsp;<%[ Add ]%></button>
<p class="bold"><%[ Tip: checking 'All' message type causes, that rest checked message types are saved with negation ex. Catalog = All, !Debug, !Saved, !Skipped ]%></p>
<com:Bacularis.Web.Portlets.NewMessagesMenu ID="MessagesMenu" />
<com:TActiveRepeater ID="RepeaterMessages" OnItemCreated="createDirectiveListElement" OnItemDataBound="loadMessageTypes">
	<prop:ItemTemplate>
		<div class="w3-card directive" style="padding: 16px;">
			<com:TActiveLinkButton
				CssClass="w3-right"
				OnCommand="SourceTemplateControl.removeMessages"
				CommandName="<%=$this->ItemIndex%>"
			>
				<i class="fa-solid fa-square-xmark w3-text-red w3-large w3-right" title="<%[ Remove ]%>"></i>
			</com:TActiveLinkButton>
			<h2><%#$this->Data['directive_name']%></h2>
			<com:Bacularis.Web.Portlets.DirectiveTextBox />
			<com:Bacularis.Web.Portlets.MessageTypes ID="Types" />
			<div class="w3-clear"></div>
		</div>
	</prop:ItemTemplate>
</com:TActiveRepeater>
<script>
var oDirectiveMessages = {
	ids: {
		rep: '<%=$this->RepeaterMessages->ClientID%>_Container'
	},
	init: function() {
		$(() => {
			oDirectiveMessages.init_types();
		});
	},
	init_types: function() {
		const rep = document.getElementById(this.ids.rep);
		const all_chkbs = rep.querySelectorAll('input[type="checkbox"]');
		for (let i = 1; i < all_chkbs.length; i++) {
			$(all_chkbs[i]).trigger('change');
		}
	},
	onchange: function(event) {
		const curr = event.target;
		const all = $(curr).closest('div:not(.directive_field)').find('input[type="checkbox"]').get(0);
		const container = $(curr).closest('div[rel="type_container"]').get(0);
		this.set_negation(container, all.checked);
	},
	set_negation: function(container, negation) {
		const chkbs = container.querySelectorAll('input[type="checkbox"]');
		for (let i = 1; i < chkbs.length; i++) {
			if (!chkbs[i].checked) {
				continue;
			}
			chkbs[i].indeterminate = negation;
		}
	}
};
oDirectiveMessages.init();
</script>
