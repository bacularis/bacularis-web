<button type="button" class="w3-button w3-green w3-margin-left" onclick="$('#new_host').slideToggle()"><i class="fa fa-plus"></i> &nbsp;<%[ Add API host]%></button>
<com:Bacularis.Common.Portlets.NewHost ID="AddNewHost" APIRequired="config" ClientMode="true" OnCallback="loadConfig" />
<div class="w3-margin-left config_hosts">
	<com:TActiveRepeater ID="RepeaterHosts" OnItemCreated="createHostListElement">
		<prop:ItemTemplate>
			<com:TPanel ID="HostBox" CssClass="w3-sand w3-margin-top w3-padding-large w3-border config_host w3-large">
				<table class="host" onmousedown="var el = (event.target||event.srcElement); $('div.config_host').removeClass('host_selected'); (el.parentNode.id != '<%=$this->Host->ClientID%>' && el.id != '<%=$this->Host->ClientID%>' && el.id != '<%=$this->RemoveHost->ClientID%>') ? $('#<%=$this->Host->ClientID%>').trigger('click') : '';" style="width: 100%">
					<tr onmouseover="$(this).find('a.action_link').addClass('host_selected');" onmouseout="$(this).find('a.action_link').removeClass('host_selected');" style="cursor: pointer;">
						<td style="width: 30%"><%[ Host: ]%>
						<com:TActiveLinkButton
							ID="Host"
							ActiveControl.EnableUpdate="false"
							OnCommand="SourceTemplateControl.getComponents"
							ClientSide.OnLoading="BaculaConfig.loader_start(sender.options.ID);"
							ClientSide.OnComplete="BaculaConfig.set_config_items(sender.options.ID);"
							ClientSide.OnSuccess="$('#<%=$this->HostBox->ClientID%>').addClass('host_selected');"
							Attributes.onclick="return BaculaConfig.unset_config_items(this.id);"
							Style="text-decoration: none"
						>
							<strong><%=$this->Data%></strong>
						</com:TActiveLinkButton>
							<i class="fa fa-sync w3-spin" style="display: none"><i/>
						</td>
						<td style="width: 45%"><%[ IP Address/Hostname: ]%><strong> <%#$this->getParent()->getParent()->config[$this->Data]['address']%></strong></td>
						<td style="width: 10%"><%[ Port: ]%><strong> <%#$this->getParent()->getParent()->config[$this->Data]['port']%></strong>
						</td>
						<td style="width: 15%"><com:TActiveLinkButton
							ID="RemoveHost"
							OnCommand="SourceTemplateControl.removeHost"
							CssClass="action_link w3-button w3-green w3-right"
						>
							<i class="fa fa-trash-alt"></i> &nbsp;<%[ Remove ]%>
						</com:TActiveLinkButton>
						</td>

					</tr>
				</table>
				<com:Bacularis.Web.Portlets.BaculaConfigComponents />
			</com:TPanel>
		</prop:ItemTemplate>
	</com:TActiveRepeater>
</div>
