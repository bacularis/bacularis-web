<div class="config_resources w3-margin-left" style="display: none">
	<com:TActiveLabel ID="RemoveResourceOk" Display="None" CssClass="w3-text-green" />
	<com:TActiveLabel ID="RemoveResourceError" Display="None" CssClass="w3-text-red" />
	<com:TActiveRepeater ID="RepeaterResources" OnItemCreated="createResourceListElement">
		<prop:ItemTemplate>
			<com:TPanel>
				<script type="text/javascript">
				<%=$this->Resource->ClientID%>_mousedown = function(event) {
					var t = (event.target||event.srcElement);
					var nn = t.nodeName.toUpperCase();
					var res_id = '<%=$this->Resource->ClientID%>';
					if (nn != 'I' && t.parentNode.id != res_id && t.id != res_id && !/^<%=$this->RemoveResource->ClientID%>/.test(t.id)) {
						$('.validate, .validator').hide(); // hide validator messages
						$('#' + res_id).trigger('click');
					}
				};
				document.getElementById('<%=$this->RemoveResource->ClientID%>').onclick = function(event) {
					var t = event.target || event.srcElement;
					var nn = t.nodeName.toUpperCase();
					var cmsg = '<%[ Are you sure that you want to remove %s resource "%s"? ]%>';
					cmsg = cmsg.replace('%s', '<%#$this->Data['resource_type']%>');
					cmsg = cmsg.replace('%s', '<%#$this->Data['resource_name']%>');
					if ((/^<%=$this->RemoveResource->ClientID%>/.test(t.id) || nn == 'I') && confirm(cmsg)) {
						return true;
					}
					return false;
				};
				</script>
				<table class="resource" onmousedown="return <%=$this->Resource->ClientID%>_mousedown(event);" onmouseover="$(this).find('a.action_link').addClass('resource_selected');" onmouseout="$(this).find('a.action_link').removeClass('resource_selected');" style="width: 100%">
					<tr>
						<td style="width:80%; cursor: pointer;"><com:TActiveLinkButton
							ID="Resource"
							ActiveControl.EnableUpdate="false"
							OnCommand="SourceTemplateControl.getDirectives"
							ClientSide.OnLoading="BaculaConfig.loader_start(sender.options.ID);"
							ClientSide.OnComplete="BaculaConfig.set_config_items(sender.options.ID);"
							Attributes.onclick="return BaculaConfig.unset_config_items(this.id);"
							Text="<strong><%#$this->Data['resource_type']%></strong>: <%#$this->Data['resource_name']%>"
							Style="text-decoration: none"
						/>
							<i class="fa fa-sync w3-spin" style="display: none"></i>
						</td>
						<td class="right" style="height: 26px; width: 20%;">
							<com:TActiveLinkButton
								ID="RemoveResource"
								OnCommand="SourceTemplateControl.removeResource"
								CssClass="action_link w3-button w3-green w3-right button_fixed"
							>
							<prop:ClientSide.OnComplete>
								var vid = '<%=$this->SourceTemplateControl->RemoveResourceError->ClientId%>';
								if (document.getElementById(vid).style.display === 'none') {
									var container = $('#<%=$this->RemoveResource->ClientID%>').closest('div')[0];
									container.parentNode.removeChild(container);
								}
								$('html, body').animate({
									scrollTop: $('#' + vid).closest('div').prev().offset().top
								}, 500);
							</prop:ClientSide.OnComplete>
								<i class="fa fa-trash-alt"></i> &nbsp;<%[ Remove ]%>
							</com:TActiveLinkButton>
						</td>
					</tr>
				</table>
				<div class="config_directives w3-khaki" style="display: none">
				<com:Bacularis.Web.Portlets.BaculaConfigDirectives
					Resource="<%#$this->Data['resource_name']%>"
					LoadValues="<%=true%>"
					ShowRemoveButton="false"
				/>
				</div>
			</com:TPanel>
		</prop:ItemTemplate>
	</com:TActiveRepeater>
</div>
