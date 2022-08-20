<div id="<%=$this->ClientID%>_new_resource" class="w3-card w3-padding config_new_resource left" style="display: none">
	<i class="fa fa-times w3-right" onclick="$('#<%=$this->ClientID%>_new_resource').hide();"></i>
	<ul class="w3-ul new_element_menu" style="display: <%=$this->getComponentType() === 'dir' ? 'block': 'none'%>;">
		<li><com:TActiveLinkButton
			OnCommand="Parent.SourceTemplateControl.newResource"
			CommandParameter="Director|<%=$this->getHost()%>|<%=$this->getComponentType()%>|<%=$this->getComponentName()%>"
			ClientSide.OnComplete="BaculaConfig.show_new_config('<%=$this->getHost()%>new_resource', '<%=$this->getComponentType()%>', '<%=$this->getComponentName()%>', 'Director');"
			Attributes.onclick="$(this).closest('div.config_new_resource').hide();$('div.config_directives').slideUp();"
			>
			<i class='fa fa-plus'></i> &nbsp;Director
		</com:TActiveLinkButton>
		</li>
		<li><com:TActiveLinkButton
			OnCommand="Parent.SourceTemplateControl.newResource"
			CommandParameter="JobDefs|<%=$this->getHost()%>|<%=$this->getComponentType()%>|<%=$this->getComponentName()%>"
			ClientSide.OnComplete="BaculaConfig.show_new_config('<%=$this->getHost()%>new_resource', '<%=$this->getComponentType()%>', '<%=$this->getComponentName()%>', 'JobDefs');"
			Attributes.onclick="$(this).closest('div.config_new_resource').hide();$('div.config_directives').slideUp();"
			>
			<i class='fa fa-plus'></i> &nbsp;JobDefs
		</com:TActiveLinkButton>
		</li>
		<li><com:TActiveLinkButton
			OnCommand="Parent.SourceTemplateControl.newResource"
			CommandParameter="Client|<%=$this->getHost()%>|<%=$this->getComponentType()%>|<%=$this->getComponentName()%>"
			ClientSide.OnComplete="BaculaConfig.show_new_config('<%=$this->getHost()%>new_resource', '<%=$this->getComponentType()%>', '<%=$this->getComponentName()%>', 'Client');"
			Attributes.onclick="$(this).closest('div.config_new_resource').hide();$('div.config_directives').slideUp();"
			>
			<i class='fa fa-plus'></i> &nbsp;Client
		</com:TActiveLinkButton>
		</li>
		<li><com:TActiveLinkButton
			OnCommand="Parent.SourceTemplateControl.newResource"
			CommandParameter="Job|<%=$this->getHost()%>|<%=$this->getComponentType()%>|<%=$this->getComponentName()%>"
			ClientSide.OnComplete="BaculaConfig.show_new_config('<%=$this->getHost()%>new_resource', '<%=$this->getComponentType()%>', '<%=$this->getComponentName()%>', 'Job');"
			Attributes.onclick="$(this).closest('div.config_new_resource').hide();$('div.config_directives').slideUp();"
			>
			<i class='fa fa-plus'></i> &nbsp;Job
		</com:TActiveLinkButton>
		</li>
		<li><com:TActiveLinkButton
			OnCommand="Parent.SourceTemplateControl.newResource"
			CommandParameter="Storage|<%=$this->getHost()%>|<%=$this->getComponentType()%>|<%=$this->getComponentName()%>"
			ClientSide.OnComplete="BaculaConfig.show_new_config('<%=$this->getHost()%>new_resource', '<%=$this->getComponentType()%>', '<%=$this->getComponentName()%>', 'Storage');"
			Attributes.onclick="$(this).closest('div.config_new_resource').hide();$('div.config_directives').slideUp();"
			>
			<i class='fa fa-plus'></i> &nbsp;Storage
		</com:TActiveLinkButton>
		</li>
		<li><com:TActiveLinkButton
			OnCommand="Parent.SourceTemplateControl.newResource"
			CommandParameter="Catalog|<%=$this->getHost()%>|<%=$this->getComponentType()%>|<%=$this->getComponentName()%>"
			ClientSide.OnComplete="BaculaConfig.show_new_config('<%=$this->getHost()%>new_resource', '<%=$this->getComponentType()%>', '<%=$this->getComponentName()%>', 'Catalog');"
			Attributes.onclick="$(this).closest('div.config_new_resource').hide();$('div.config_directives').slideUp();"
			>
			<i class='fa fa-plus'></i> &nbsp;Catalog
		</com:TActiveLinkButton>
		</li>
		<li><com:TActiveLinkButton
			OnCommand="Parent.SourceTemplateControl.newResource"
			CommandParameter="Schedule|<%=$this->getHost()%>|<%=$this->getComponentType()%>|<%=$this->getComponentName()%>"
			ClientSide.OnComplete="BaculaConfig.show_new_config('<%=$this->getHost()%>new_resource', '<%=$this->getComponentType()%>', '<%=$this->getComponentName()%>', 'Schedule');"
			Attributes.onclick="$(this).closest('div.config_new_resource').hide();$('div.config_directives').slideUp();"
			>
			<i class='fa fa-plus'></i> &nbsp;Schedule
		</com:TActiveLinkButton>
		</li>
		<li><com:TActiveLinkButton
			OnCommand="Parent.SourceTemplateControl.newResource"
			CommandParameter="Fileset|<%=$this->getHost()%>|<%=$this->getComponentType()%>|<%=$this->getComponentName()%>"
			ClientSide.OnComplete="BaculaConfig.show_new_config('<%=$this->getHost()%>new_resource', '<%=$this->getComponentType()%>', '<%=$this->getComponentName()%>', 'FileSet');"
			Attributes.onclick="$(this).closest('div.config_new_resource').hide();$('div.config_directives').slideUp();"
			>
			<i class='fa fa-plus'></i> &nbsp;FileSet
		</com:TActiveLinkButton>
		</li>
		<li><com:TActiveLinkButton
			OnCommand="Parent.SourceTemplateControl.newResource"
			CommandParameter="Pool|<%=$this->getHost()%>|<%=$this->getComponentType()%>|<%=$this->getComponentName()%>"
			ClientSide.OnComplete="BaculaConfig.show_new_config('<%=$this->getHost()%>new_resource', '<%=$this->getComponentType()%>', '<%=$this->getComponentName()%>', 'Pool');"
			Attributes.onclick="$(this).closest('div.config_new_resource').hide();$('div.config_directives').slideUp();"
			>
			<i class='fa fa-plus'></i> &nbsp;Pool
		</com:TActiveLinkButton>
		</li>
		<li><com:TActiveLinkButton
			OnCommand="Parent.SourceTemplateControl.newResource"
			CommandParameter="Messages|<%=$this->getHost()%>|<%=$this->getComponentType()%>|<%=$this->getComponentName()%>"
			ClientSide.OnComplete="BaculaConfig.show_new_config('<%=$this->getHost()%>new_resource', '<%=$this->getComponentType()%>', '<%=$this->getComponentName()%>', 'Messages');"
			Attributes.onclick="$(this).closest('div.config_new_resource').hide();$('div.config_directives').slideUp();"
			>
			<i class='fa fa-plus'></i> &nbsp;Messages
		</com:TActiveLinkButton>
		</li>
		<li><com:TActiveLinkButton
			OnCommand="Parent.SourceTemplateControl.newResource"
			CommandParameter="Console|<%=$this->getHost()%>|<%=$this->getComponentType()%>|<%=$this->getComponentName()%>"
			ClientSide.OnComplete="BaculaConfig.show_new_config('<%=$this->getHost()%>new_resource', '<%=$this->getComponentType()%>', '<%=$this->getComponentName()%>', 'Console');"
			Attributes.onclick="$(this).closest('div.config_new_resource').hide();$('div.config_directives').slideUp();"
			>
			<i class='fa fa-plus'></i> &nbsp;Console
		</com:TActiveLinkButton>
		</li>
		<li><com:TActiveLinkButton
			OnCommand="Parent.SourceTemplateControl.newResource"
			CommandParameter="Statistics|<%=$this->getHost()%>|<%=$this->getComponentType()%>|<%=$this->getComponentName()%>"
			ClientSide.OnComplete="BaculaConfig.show_new_config('<%=$this->getHost()%>new_resource', '<%=$this->getComponentType()%>', '<%=$this->getComponentName()%>', 'Statistics');"
			Attributes.onclick="$(this).closest('div.config_new_resource').hide();$('div.config_directives').slideUp();"
			>
			<i class='fa fa-plus'></i> &nbsp;Statistics
		</com:TActiveLinkButton>
		</li>
	</ul>
	<ul class="w3-ul new_element_menu" style="display: <%=$this->getComponentType() === 'sd' ? 'block': 'none'%>;">
		<li><com:TActiveLinkButton
			OnCommand="Parent.SourceTemplateControl.newResource"
			CommandParameter="Director|<%=$this->getHost()%>|<%=$this->getComponentType()%>|<%=$this->getComponentName()%>"
			ClientSide.OnComplete="BaculaConfig.show_new_config('<%=$this->getHost()%>new_resource', '<%=$this->getComponentType()%>', '<%=$this->getComponentName()%>', 'Director');"
			Attributes.onclick="$(this).closest('div.config_new_resource').hide();$('div.config_directives').slideUp();"
			>
			<i class='fa fa-plus'></i> &nbsp;Director
		</com:TActiveLinkButton>
		</li>
		<li><com:TActiveLinkButton
			OnCommand="Parent.SourceTemplateControl.newResource"
			CommandParameter="Storage|<%=$this->getHost()%>|<%=$this->getComponentType()%>|<%=$this->getComponentName()%>"
			ClientSide.OnComplete="BaculaConfig.show_new_config('<%=$this->getHost()%>new_resource', '<%=$this->getComponentType()%>', '<%=$this->getComponentName()%>', 'Storage');"
			Attributes.onclick="$(this).closest('div.config_new_resource').hide();$('div.config_directives').slideUp();"
			>
			<i class='fa fa-plus'></i> &nbsp;Storage
		</com:TActiveLinkButton>
		</li>
		<li><com:TActiveLinkButton
			OnCommand="Parent.SourceTemplateControl.newResource"
			CommandParameter="Device|<%=$this->getHost()%>|<%=$this->getComponentType()%>|<%=$this->getComponentName()%>"
			ClientSide.OnComplete="BaculaConfig.show_new_config('<%=$this->getHost()%>new_resource', '<%=$this->getComponentType()%>', '<%=$this->getComponentName()%>', 'Device');"
			Attributes.onclick="$(this).closest('div.config_new_resource').hide();$('div.config_directives').slideUp();"
			>
			<i class='fa fa-plus'></i> &nbsp;Device
		</com:TActiveLinkButton>
		</li>
		<li><com:TActiveLinkButton
			OnCommand="Parent.SourceTemplateControl.newResource"
			CommandParameter="Autochanger|<%=$this->getHost()%>|<%=$this->getComponentType()%>|<%=$this->getComponentName()%>"
			ClientSide.OnComplete="BaculaConfig.show_new_config('<%=$this->getHost()%>new_resource', '<%=$this->getComponentType()%>', '<%=$this->getComponentName()%>', 'Autochanger');"
			Attributes.onclick="$(this).closest('div.config_new_resource').hide();$('div.config_directives').slideUp();"
			>
			<i class='fa fa-plus'></i> &nbsp;Autochanger
		</com:TActiveLinkButton>
		</li>
		<li><com:TActiveLinkButton
			OnCommand="Parent.SourceTemplateControl.newResource"
			CommandParameter="Messages|<%=$this->getHost()%>|<%=$this->getComponentType()%>|<%=$this->getComponentName()%>"
			ClientSide.OnComplete="BaculaConfig.show_new_config('<%=$this->getHost()%>new_resource', '<%=$this->getComponentType()%>', '<%=$this->getComponentName()%>', 'Messages');"
			Attributes.onclick="$(this).closest('div.config_new_resource').hide();$('div.config_directives').slideUp();"
			>
			<i class='fa fa-plus'></i> &nbsp;Messages
		</com:TActiveLinkButton>
		</li>
		<li><com:TActiveLinkButton
			OnCommand="Parent.SourceTemplateControl.newResource"
			CommandParameter="Statistics|<%=$this->getHost()%>|<%=$this->getComponentType()%>|<%=$this->getComponentName()%>"
			ClientSide.OnComplete="BaculaConfig.show_new_config('<%=$this->getHost()%>new_resource', '<%=$this->getComponentType()%>', '<%=$this->getComponentName()%>', 'Statistics');"
			Attributes.onclick="$(this).closest('div.config_new_resource').hide();$('div.config_directives').slideUp();"
			>
			<i class='fa fa-plus'></i> &nbsp;Statistics
		</com:TActiveLinkButton>
		</li>
		<li><com:TActiveLinkButton
			OnCommand="Parent.SourceTemplateControl.newResource"
			CommandParameter="Cloud|<%=$this->getHost()%>|<%=$this->getComponentType()%>|<%=$this->getComponentName()%>"
			ClientSide.OnComplete="BaculaConfig.show_new_config('<%=$this->getHost()%>new_resource', '<%=$this->getComponentType()%>', '<%=$this->getComponentName()%>', 'Cloud');"
			Attributes.onclick="$(this).closest('div.config_new_resource').hide();$('div.config_directives').slideUp();"
			>
			<i class='fa fa-plus'></i> &nbsp;Cloud
		</com:TActiveLinkButton>
		</li>
	</ul>
	<ul class="w3-ul new_element_menu" style="display: <%=$this->getComponentType() === 'fd' ? 'block': 'none'%>;">
		<li><com:TActiveLinkButton
			OnCommand="Parent.SourceTemplateControl.newResource"
			CommandParameter="Director|<%=$this->getHost()%>|<%=$this->getComponentType()%>|<%=$this->getComponentName()%>"
			ClientSide.OnComplete="BaculaConfig.show_new_config('<%=$this->getHost()%>new_resource', '<%=$this->getComponentType()%>', '<%=$this->getComponentName()%>', 'Director');"
			Attributes.onclick="$(this).closest('div.config_new_resource').hide();$('div.config_directives').slideUp();"
			>
			<i class='fa fa-plus'></i> &nbsp;Director
		</com:TActiveLinkButton>
		</li>
		<li><com:TActiveLinkButton
			OnCommand="Parent.SourceTemplateControl.newResource"
			CommandParameter="FileDaemon|<%=$this->getHost()%>|<%=$this->getComponentType()%>|<%=$this->getComponentName()%>"
			ClientSide.OnComplete="BaculaConfig.show_new_config('<%=$this->getHost()%>new_resource', '<%=$this->getComponentType()%>', '<%=$this->getComponentName()%>', 'FileDaemon');"
			Attributes.onclick="$(this).closest('div.config_new_resource').hide();$('div.config_directives').slideUp();"
			>
			<i class='fa fa-plus'></i> &nbsp;FileDaemon
		</com:TActiveLinkButton>
		</li>
		<li><com:TActiveLinkButton
			OnCommand="Parent.SourceTemplateControl.newResource"
			CommandParameter="Messages|<%=$this->getHost()%>|<%=$this->getComponentType()%>|<%=$this->getComponentName()%>"
			ClientSide.OnComplete="BaculaConfig.show_new_config('<%=$this->getHost()%>new_resource', '<%=$this->getComponentType()%>', '<%=$this->getComponentName()%>', 'Messages');"
			Attributes.onclick="$(this).closest('div.config_new_resource').hide();$('div.config_directives').slideUp();"
			>
			<i class='fa fa-plus'></i> &nbsp;Messages
		</com:TActiveLinkButton>
		</li>
		<li><com:TActiveLinkButton
			OnCommand="Parent.SourceTemplateControl.newResource"
			CommandParameter="Schedule|<%=$this->getHost()%>|<%=$this->getComponentType()%>|<%=$this->getComponentName()%>"
			ClientSide.OnComplete="BaculaConfig.show_new_config('<%=$this->getHost()%>new_resource', '<%=$this->getComponentType()%>', '<%=$this->getComponentName()%>', 'Schedule');"
			Attributes.onclick="$(this).closest('div.config_new_resource').hide();$('div.config_directives').slideUp();"
			>
			<i class='fa fa-plus'></i> &nbsp;Schedule
		</com:TActiveLinkButton>
		</li>
		<li><com:TActiveLinkButton
			OnCommand="Parent.SourceTemplateControl.newResource"
			CommandParameter="Console|<%=$this->getHost()%>|<%=$this->getComponentType()%>|<%=$this->getComponentName()%>"
			ClientSide.OnComplete="BaculaConfig.show_new_config('<%=$this->getHost()%>new_resource', '<%=$this->getComponentType()%>', '<%=$this->getComponentName()%>', 'Console');"
			Attributes.onclick="$(this).closest('div.config_new_resource').hide();$('div.config_directives').slideUp();"
			>
			<i class='fa fa-plus'></i> &nbsp;Console
		</com:TActiveLinkButton>
		</li>
		<li><com:TActiveLinkButton
			OnCommand="Parent.SourceTemplateControl.newResource"
			CommandParameter="Statistics|<%=$this->getHost()%>|<%=$this->getComponentType()%>|<%=$this->getComponentName()%>"
			ClientSide.OnComplete="BaculaConfig.show_new_config('<%=$this->getHost()%>new_resource', '<%=$this->getComponentType()%>', '<%=$this->getComponentName()%>', 'Statistics');"
			Attributes.onclick="$(this).closest('div.config_new_resource').hide();$('div.config_directives').slideUp();"
			>
			<i class='fa fa-plus'></i> &nbsp;Statistics
		</com:TActiveLinkButton>
		</li>
	</ul>
	<ul class="w3-ul new_element_menu" style="display: <%=$this->getComponentType() === 'bcons' ? 'block': 'none'%>; min-width: 126px;">
		<li><com:TActiveLinkButton
			OnCommand="Parent.SourceTemplateControl.newResource"
			CommandParameter="Director|<%=$this->getHost()%>|<%=$this->getComponentType()%>|<%=$this->getComponentName()%>"
			ClientSide.OnComplete="BaculaConfig.show_new_config('<%=$this->getHost()%>new_resource', '<%=$this->getComponentType()%>', '<%=$this->getComponentName()%>', 'Director');"
			Attributes.onclick="$(this).closest('div.config_new_resource').hide();$('div.config_directives').slideUp();"
			>
			<i class='fa fa-plus'></i> &nbsp;Director
		</com:TActiveLinkButton>
		</li>
		<li><com:TActiveLinkButton
			OnCommand="Parent.SourceTemplateControl.newResource"
			CommandParameter="Console|<%=$this->getHost()%>|<%=$this->getComponentType()%>|<%=$this->getComponentName()%>"
			ClientSide.OnComplete="BaculaConfig.show_new_config('<%=$this->getHost()%>new_resource', '<%=$this->getComponentType()%>', '<%=$this->getComponentName()%>', 'Console');"
			Attributes.onclick="$(this).closest('div.config_new_resource').hide();$('div.config_directives').slideUp();"
			>
			<i class='fa fa-plus'></i> &nbsp;Console
		</com:TActiveLinkButton>
		</li>
	</ul>
</div>
