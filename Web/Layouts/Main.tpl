<!DOCTYPE html>
<html lang="en">
	<com:THead Title="Bacularis - Bacula Web Interface">
	<meta charset="utf-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<meta name="color-scheme" content="dark light" />
	<link rel="icon" href="<%~ ../../../../../Common/Images/favicon.ico %>" type="image/x-icon" />
	</com:THead>
	<body>
		<com:TForm>
			<com:TClientScript PradoScripts="ajax, effects" />
			<com:BClientScript ScriptUrl=<%~ ../../../../../vendor/bower-asset/datatables.net/js/jquery.dataTables.min.js %> />
			<com:BClientScript ScriptUrl=<%~ ../../../../../vendor/bower-asset/datatables.net-responsive/js/dataTables.responsive.min.js %> />
			<com:BClientScript ScriptUrl=<%~ ../../../../../vendor/bower-asset/datatables.net-buttons/js/dataTables.buttons.min.js %> />
			<com:BClientScript ScriptUrl=<%~ ../../../../../vendor/bower-asset/datatables.net-buttons/js/buttons.html5.min.js %> />
			<com:BClientScript ScriptUrl=<%~ ../../../../../vendor/bower-asset/datatables.net-buttons/js/buttons.colVis.min.js %> />
			<com:BClientScript ScriptUrl=<%~ ../../../../../vendor/bower-asset/datatables.net-select/js/dataTables.select.min.js %> />
			<com:BClientScript ScriptUrl=<%~ ../../../../../vendor/npm-asset/flotr2/flotr2.js %> />
			<com:BClientScript ScriptUrl=<%~ ../../../../../vendor/bower-asset/gaugejs/dist/gauge.min.js %> />
			<com:BClientScript ScriptUrl=<%~ ../../../../../Common/JavaScript/misc.js %> />
			<com:BClientScript ScriptUrl=<%~ ../JavaScript/misc.js %> />
			<com:BClientScript ScriptUrl=<%~ ../JavaScript/bacula-config.js %> />
			<com:BClientScript ScriptUrl=<%~ ../JavaScript/graph.js %> />
			<com:BClientScript ScriptUrl=<%~ ../JavaScript/statistics.js %> />
			<com:BClientScript ScriptUrl=<%~ ../JavaScript/dataview.js %> />
			<com:BStyleSheet StyleSheetUrl=<%~ ../../../../../vendor/bower-asset/datatables.net-dt/css/jquery.dataTables.min.css %> />
			<com:BStyleSheet StyleSheetUrl=<%~ ../../../../../vendor/bower-asset/datatables.net-responsive-dt/css/responsive.dataTables.min.css %> />
			<com:BStyleSheet StyleSheetUrl=<%~ ../../../../../vendor/bower-asset/datatables.net-buttons-dt/css/buttons.dataTables.min.css %> />
			<com:BStyleSheet StyleSheetUrl=<%~ ../../../../../Common/CSS/w3css/w3.css %> />
			<com:BStyleSheet StyleSheetUrl=<%~ ../../../../../Common/CSS/baculum.css %> />
			<com:Bacularis.Common.Portlets.TableDefaults />
			<!-- Top container -->
			<div class="w3-bar w3-top w3-black w3-large" style="z-index:4">
				<button type="button" class="w3-bar-item w3-button w3-hover-none w3-hover-text-light-grey" onclick="W3SideBar.open();"><i class="fa fa-bars"></i>  Menu</button>
				<img class="w3-bar-item w3-right" src="<%~ ../../../../../Common/Images/logo.png %>" alt="" style="margin-top: 3px" />
			</div>
			<com:Bacularis.Web.Portlets.MainSideBar />
			<com:Bacularis.Web.Portlets.QuickResourceEdit
				ID="QuickResourceEdit"
				Visible="<%=$this->User->isInRole(WebUserRoles::ADMIN) && in_array($this->Service->getRequestedPagePath(), ['JobList', 'ClientList', 'StorageList', 'PoolList'])%>"
			/>
			<!-- !PAGE CONTENT! -->
			<div class="w3-main page_main_el" id="page_main" style="margin-left: 250px; margin-top: 43px;">
				<span class="w3-tag w3-large w3-purple w3-right w3-padding-small w3-margin-top w3-margin-right" style="border-radius: 3px; line-height: 26px;">
					<i class="fa fa-cogs w3-large"></i> <span class="w3-medium"><%[ Running jobs: ]%> <span id="running_jobs"></span></span>
				</span>
				<span id="msg_envelope" class="w3-tag w3-large w3-green w3-text-white w3-right w3-padding-small w3-margin-top w3-margin-right" style="cursor: pointer; border-radius: 3px; <%=((!$this->Application->getModule('web_config')->isMessagesLogEnabled() || !$this->User->isInRole(WebUserRoles::ADMIN)) ? 'display: none' : '')%>" title="<%[ Display messages log window ]%>">
					<i class="fas fa-envelope w3-large"></i>
				</span>
				<com:TLabel ID="UserAPIHostsContainter"CssClass="w3-right w3-margin-top w3-margin-right">
					<%[ API host: ]%>
					<com:TDropDownList
						ID="UserAPIHosts"
						CssClass="w3-select w3-border w3-small"
						OnTextChanged="setAPIHost"
						AutoPostBack="true"
						Width="200px"
						Height="34px"
						Style="padding: 5px"
					/>
				</com:TLabel>
				<span class="w3-right w3-padding-small w3-margin-top w3-margin-right">
					<label><i class="fa-solid fa-sun"></i>
						<label class="switch small" onclick="ThemeMode.toggle_mode();">
							<input type="checkbox" id="theme_mode_switcher" />
							<span class="slider small round"></span>
						</label> <i class="fa-solid fa-moon"></i>
					</label>
				</span>
				<script type="text/javascript">
					const SIZE_VALUES_UNIT = '<%=(count($this->web_config) > 0 && key_exists('size_values_unit', $this->web_config['baculum'])) ? $this->web_config['baculum']['size_values_unit'] : WebConfig::DEF_SIZE_VAL_UNIT%>';
					const DATE_TIME_FORMAT = '<%=(count($this->web_config) > 0 && key_exists('date_time_format', $this->web_config['baculum'])) ? $this->web_config['baculum']['date_time_format'] : WebConfig::DEF_DATE_TIME_FORMAT%>';
					const KEEP_TABLE_SETTINGS = <%=(count($this->web_config) > 0 && key_exists('keep_table_settings', $this->web_config['baculum'])) ? $this->web_config['baculum']['keep_table_settings'] : WebConfig::DEF_KEEP_TABLE_SETTINGS%>;
				</script>
				<com:TContentPlaceHolder ID="Main" />
				<!-- Footer -->
				<footer class="w3-container w3-right-align w3-small"><%[ Version: ]%> <%=Params::BACULARIS_VERSION%></footer>
			</div>
		</com:TForm>
		<div id="small" class="w3-hide-large"></div>
<com:Bacularis.Web.Portlets.ErrorMessageBox />
<com:Bacularis.Web.Portlets.InfoMessageBox />
<com:Bacularis.Web.Portlets.ResourceMonitor />
<com:Bacularis.Web.Portlets.MsgEnvelope Visible="<%=($this->Application->getModule('web_config')->isMessagesLogEnabled() && $this->User->isInRole(WebUserRoles::ADMIN))%>" />
<script>
var is_small = $('#small').is(':visible');
$(function() {
	if (is_small) {
		W3SideBar.close();
	}
});
</script>
	</body>
</html>
