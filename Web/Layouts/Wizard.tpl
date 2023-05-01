<!DOCTYPE html>
<html lang="en">
	<com:THead Title="Bacularis - Bacula Web Interface">
	<meta charset="utf-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<link rel="icon" href="<%~ ../../../../../Common/Images/favicon.ico %>" type="image/x-icon" />
	</com:THead>
	<body>
		<script type="text/javascript">
			const SIZE_VALUES_UNIT = '<%=(count($this->web_config) > 0 && key_exists('size_values_unit', $this->web_config['baculum'])) ? $this->web_config['baculum']['size_values_unit'] : WebConfig::DEF_SIZE_VAL_UNIT%>';
			const DATE_TIME_FORMAT = '<%=(count($this->web_config) > 0 && key_exists('date_time_format', $this->web_config['baculum'])) ? $this->web_config['baculum']['date_time_format'] : WebConfig::DEF_DATE_TIME_FORMAT%>';
			const KEEP_TABLE_SETTINGS = <%=(count($this->web_config) > 0 && key_exists('keep_table_settings', $this->web_config['baculum'])) ? $this->web_config['baculum']['keep_table_settings'] : WebConfig::DEF_KEEP_TABLE_SETTINGS%>;
		</script>
		<com:TForm>
			<com:TClientScript PradoScripts="ajax, effects" />
			<com:BClientScript ScriptUrl=<%~ ../../../../../vendor/bower-asset/datatables.net/js/jquery.dataTables.min.js %> />
			<com:BClientScript ScriptUrl=<%~ ../../../../../vendor/bower-asset/datatables.net-responsive/js/dataTables.responsive.min.js %> />
			<com:BClientScript ScriptUrl=<%~ ../../../../../vendor/bower-asset/datatables.net-buttons/js/dataTables.buttons.min.js %> />
			<com:BClientScript ScriptUrl=<%~ ../../../../../vendor/bower-asset/datatables.net-buttons/js/buttons.html5.min.js %> />
			<com:BClientScript ScriptUrl=<%~ ../../../../../vendor/bower-asset/datatables.net-buttons/js/buttons.colVis.min.js %> />
			<com:BClientScript ScriptUrl=<%~ ../../../../../Common/JavaScript/misc.js %> />
			<com:BClientScript ScriptUrl=<%~ ../JavaScript/misc.js %> />
			<com:BClientScript ScriptUrl=<%~ ../JavaScript/bacula-config.js %> />
			<com:BClientScript ScriptUrl=<%~ ../../../../../vendor/bower-asset/opentip/downloads/opentip-jquery.min.js %> />
			<com:BStyleSheet StyleSheetUrl=<%~ ../../../../../vendor/bower-asset/datatables.net-dt/css/jquery.dataTables.min.css %> />
			<com:BStyleSheet StyleSheetUrl=<%~ ../../../../../vendor/bower-asset/datatables.net-responsive-dt/css/responsive.dataTables.min.css %> />
			<com:BStyleSheet StyleSheetUrl=<%~ ../../../../../vendor/bower-asset/datatables.net-buttons-dt/css/buttons.dataTables.min.css %> />
			<com:BStyleSheet StyleSheetUrl=<%~ ../../../../../vendor/bower-asset/opentip/css/opentip.css %> />
			<com:BStyleSheet StyleSheetUrl=<%~ ../../../../../Common/CSS/w3css/w3.css %> />
			<com:BStyleSheet StyleSheetUrl=<%~ ../../../../../Common/CSS/baculum.css %> />
			<com:Bacularis.Common.Portlets.TableDefaults />
			<!-- Top container -->
				<com:TContentPlaceHolder ID="Wizard" />
		</com:TForm>
	<script type="text/javascript">
		Formatters.set_formatters();
	</script>
	</body>
</html>
