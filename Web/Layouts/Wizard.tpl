<!DOCTYPE html>
<html lang="en">
	<com:THead Title="Baculum - Bacula Web Interface">
	<meta charset="utf-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<link rel="icon" href="<%=$this->getPage()->getTheme()->getBaseUrl()%>/favicon.ico" type="image/x-icon" />
	</com:THead>
	<body  class="w3-light-grey">
		<script type="text/javascript">
			var SIZE_VALUES_UNIT = '<%=(count($this->web_config) > 0 && key_exists('size_values_unit', $this->web_config['baculum'])) ? $this->web_config['baculum']['size_values_unit'] : WebConfig::DEF_SIZE_VAL_UNIT%>';
			var DATE_TIME_FORMAT = '<%=(count($this->web_config) > 0 && key_exists('date_time_format', $this->web_config['baculum'])) ? $this->web_config['baculum']['date_time_format'] : WebConfig::DEF_DATE_TIME_FORMAT%>';
		</script>
		<com:TForm>
			<com:TClientScript PradoScripts="ajax, effects" />
			<com:BClientScript ScriptUrl=<%~ ../../vendor/bower-asset/datatables.net/js/jquery.dataTables.min.js %> />
			<com:BClientScript ScriptUrl=<%~ ../../vendor/bower-asset/datatables.net-responsive/js/dataTables.responsive.min.js %> />
			<com:BClientScript ScriptUrl=<%~ ../../vendor/bower-asset/datatables.net-buttons/js/dataTables.buttons.min.js %> />
			<com:BClientScript ScriptUrl=<%~ ../../vendor/bower-asset/datatables.net-buttons/js/buttons.html5.min.js %> />
			<com:BClientScript ScriptUrl=<%~ ../../vendor/bower-asset/datatables.net-buttons/js/buttons.colVis.min.js %> />
			<com:BClientScript ScriptUrl=<%~ ../../Common/JavaScript/misc.js %> />
			<com:BClientScript ScriptUrl=<%~ ../JavaScript/misc.js %> />
			<com:BClientScript ScriptUrl=<%~ ../JavaScript/bacula-config.js %> />
			<com:BClientScript ScriptUrl=<%~ ../../vendor/bower-asset/opentip/downloads/opentip-jquery.min.js %> />
			<com:BStyleSheet StyleSheetUrl=<%~ ../../vendor/w3css/w3.css %> />
			<com:BStyleSheet StyleSheetUrl=<%~ ../../vendor/bower-asset/datatables.net-dt/css/jquery.dataTables.min.css %> />
			<com:BStyleSheet StyleSheetUrl=<%~ ../../vendor/bower-asset/datatables.net-responsive-dt/css/responsive.dataTables.min.css %> />
			<com:BStyleSheet StyleSheetUrl=<%~ ../../vendor/bower-asset/datatables.net-buttons-dt/css/buttons.dataTables.min.css %> />
			<com:BStyleSheet StyleSheetUrl=<%~ ../../vendor/bower-asset/opentip/css/opentip.css %> />
			<com:BStyleSheet StyleSheetUrl=<%~ ../../../themes/Baculum-v2/css/baculum.css %> />
			<com:Application.Common.Portlets.TableDefaults />
			<!-- Top container -->
				<com:TContentPlaceHolder ID="Wizard" />
		</com:TForm>
	<script type="text/javascript">
		Formatters.set_formatters();
	</script>
	</body>
</html>
