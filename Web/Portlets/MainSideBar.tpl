<!-- Sidebar/menu -->
<nav class="w3-sidebar w3-animate-left w3-margin-bottom" style="z-index:3;width:250px;" id="sidebar"><br />
	<div class="w3-container w3-row" style="margin-bottom: 8px">
		<div class="w3-col s3">
			<img src="<%~ ../../../../../Common/Images/avatar2.png %>" class="w3-circle w3-margin-right" style="width: 33px" />
		</div>
		<div class="w3-col s9 w3-bar">
			<span><%[ Welcome ]%>, <strong><%=$this->User->getUsername()%></strong></span><br>
			<script>var main_side_bar_reload_url = '<%=$this->reload_url%>';</script>
			<com:TActiveLinkButton
				ID="Logout"
				OnClick="logout"
				CssClass="w3-bar-item w3-button"
				ToolTip="<%[ Logout ]%>"
			>
				<prop:ClientSide.OnComplete>
					if (!window.chrome && window.navigator.userAgent.indexOf('Safari') != -1) {
						// Safari
						var xml_http = new XMLHttpRequest();
						xml_http.open('POST', main_side_bar_reload_url, true, '<%=$this->User->getUsername()%>');
						xml_http.onreadystatechange = function() {
							if (this.readyState == 4 && this.status == 200) {
								window.location.reload();
							}
						}
						xml_http.send();
					} else if (!window.chrome || window.navigator.webdriver)  {
						// Firefox and others
						window.location.href = main_side_bar_reload_url;
					} else if (window.chrome) {
						// Chrome
						window.location.reload();
					}
				</prop:ClientSide.OnComplete>
				<i class="fa fa-power-off"></i>
			</com:TActiveLinkButton>
			<a href="<%=$this->Service->constructUrl('AccountSettings')%>" class="w3-bar-item w3-button<%=$this->getModule('users')->isPageAllowed($this->User, 'AccountSettings') ? '' : ' hide'%>" title="<%[ Account settings ]%>"><i class="fa-solid fa-user-gear"></i></a>
			<a href="<%=$this->Service->constructUrl('ApplicationSettings')%>" class="w3-bar-item w3-button<%=$this->getModule('users')->isPageAllowed($this->User, 'ApplicationSettings') ? '' : ' hide'%>" title="<%[ Application settings ]%>"><i class="fa fa-cog"></i></a>
		</div>
	</div>
	<div class="w3-container w3-black">
		<h5 class="w3-center" style="margin: 6px 0 2px 0">Bacularis Web Menu</h5>
	</div>
	<div class="w3-bar-block" style="margin-bottom: 45px;">
		<!--a href="#" class="w3-bar-item w3-button w3-padding-16 w3-black w3-hover-black w3-hide-large" onclick="W3SideBar.close(); return false;" title="close menu">  <%[ Close Menu ]%> <i class="fa fa-window-close fa-fw w3-right w3-xlarge"></i></a-->
		<div class="w3-black" style="height: 3px"></div>
		<a href="<%=$this->Service->constructUrl('Dashboard')%>" class="w3-bar-item w3-button w3-padding<%=$this->Service->getRequestedPagePath() == 'Dashboard' ? ' w3-blue': ''%><%=$this->getModule('users')->isPageAllowed($this->User, 'Dashboard') ? '' : ' hide'%>"><i class="fa fa-tachometer-alt fa-fw"></i>  <%[ Dashboard ]%></a>
		<a href="<%=$this->Service->constructUrl('JobList')%>" class="w3-bar-item w3-button w3-padding<%=in_array($this->Service->getRequestedPagePath(), array('JobList', 'JobView')) ? ' w3-blue': ''%><%=$this->getModule('users')->isPageAllowed($this->User, 'JobList') ? '' : ' hide'%>"><i class="fa fa-tasks fa-fw"></i>  <%[ Jobs ]%></a>
		<a href="<%=$this->Service->constructUrl('DirectorView', ['director' => $_SESSION['director']])%>" class="w3-bar-item w3-button w3-padding<%=in_array($this->Service->getRequestedPagePath(), array('DirectorView')) ? ' w3-blue': ''%><%=$this->getModule('users')->isPageAllowed($this->User, 'DirectorView') ? '' : ' hide'%>"><i class="fa fa-sitemap fa-fw"></i>  <%[ Director ]%></a>
		<a href="<%=$this->Service->constructUrl('ClientList')%>" class="w3-bar-item w3-button w3-padding<%=in_array($this->Service->getRequestedPagePath(), array('ClientList', 'ClientView')) ? ' w3-blue': ''%><%=$this->getModule('users')->isPageAllowed($this->User, 'ClientList') ? '' : ' hide'%>"><i class="fa fa-desktop fa-fw"></i>  <%[ Clients ]%></a>
		<a href="<%=$this->Service->constructUrl('StorageList')%>" class="w3-bar-item w3-button w3-padding<%=in_array($this->Service->getRequestedPagePath(), array('StorageList', 'StorageView', 'DeviceView')) ? ' w3-blue': ''%><%=$this->getModule('users')->isPageAllowed($this->User, 'StorageList') ? '' : ' hide'%>"><i class="fa fa-database fa-fw"></i>  <%[ Storages ]%></a>
		<a href="<%=$this->Service->constructUrl('PoolList')%>" class="w3-bar-item w3-button w3-padding<%=in_array($this->Service->getRequestedPagePath(), array('PoolList', 'PoolView')) ? ' w3-blue': ''%><%=$this->getModule('users')->isPageAllowed($this->User, 'PoolList') ? '' : ' hide'%>"><i class="fa fa-tape fa-fw"></i>  <%[ Pools ]%></a>
		<a href="<%=$this->Service->constructUrl('VolumeList')%>" class="w3-bar-item w3-button w3-padding<%=in_array($this->Service->getRequestedPagePath(), array('VolumeList', 'VolumeView')) ? ' w3-blue': ''%><%=$this->getModule('users')->isPageAllowed($this->User, 'VolumeList') ? '' : ' hide'%>"><i class="fa fa-hdd fa-fw"></i>  <%[ Volumes ]%></a>
		<a href="<%=$this->Service->constructUrl('ConsoleView')%>" class="w3-bar-item w3-button w3-padding<%=$this->Service->getRequestedPagePath() == 'ConsoleView' ? ' w3-blue': ''%><%=$this->getModule('users')->isPageAllowed($this->User, 'ConsoleView') ? '' : ' hide'%>"><i class="fa fa-terminal fa-fw"></i>  <%[ Console ]%></a>
		<a href="<%=$this->Service->constructUrl('RestoreWizard')%>" class="w3-bar-item w3-button w3-padding<%=$this->Service->getRequestedPagePath() == 'RestoreWizard' ? ' w3-blue': ''%><%=$this->getModule('users')->isPageAllowed($this->User, 'RestoreWizard') ? '' : ' hide'%>"><i class="fa fa-reply fa-fw"></i>  <%[ Restore ]%></a>
		<a href="<%=$this->Service->constructUrl('Graphs')%>" class="w3-bar-item w3-button w3-padding<%=$this->Service->getRequestedPagePath() == 'Graphs' ? ' w3-blue': ''%><%=$this->getModule('users')->isPageAllowed($this->User, 'Graphs') ? '' : ' hide'%>"><i class="fa fa-chart-pie fa-fw"></i>  <%[ Graphs ]%></a>
		<a href="<%=$this->Service->constructUrl('Security')%>" class="w3-bar-item w3-button w3-padding<%=$this->Service->getRequestedPagePath() == 'Security' ? ' w3-blue': ''%><%=$this->getModule('users')->isPageAllowed($this->User, 'Security') ? '' : ' hide'%>"><i class="fa fa-lock fa-fw"></i>  <%[ Security ]%></a>
		<a href="<%=$this->Service->constructUrl('Deployment')%>" class="w3-bar-item w3-button w3-padding<%=$this->Service->getRequestedPagePath() == 'Deployment' ? ' w3-blue': ''%><%=$this->getModule('users')->isPageAllowed($this->User, 'Deployment') ? '' : ' hide'%>"><i class="fa-solid fa-rocket fa-fw"></i>  <%[ Deployment ]%></a>
		<a href="<%=$this->Service->constructUrl('Patterns')%>" class="w3-bar-item w3-button w3-padding<%=$this->Service->getRequestedPagePath() == 'Patterns' ? ' w3-blue': ''%><%=$this->getModule('users')->isPageAllowed($this->User, 'Patterns') ? '' : ' hide'%>"><i class="fa-solid fa-stamp fa-fw"></i>  <%[ Patterns ]%></a>
		<a href="/panel/" class="w3-bar-item w3-button w3-padding<%=(!$this->is_api || !$this->User->isInRole(WebUserRoles::ADMIN)) ? ' hide' : ''%>" target="_blank"><i class="fas fa-exchange-alt fa-rotate-90 fa-fw"></i>  <%[ API Panel ]%></a>
	</div>
</nav>

<!-- Overlay effect when opening sidebar on small screens -->
<div class="w3-overlay w3-hide-large w3-animate-opacity" onclick="W3SideBar.close(); return false;" style="cursor:pointer" title="close side menu" id="overlay_bg"></div>
