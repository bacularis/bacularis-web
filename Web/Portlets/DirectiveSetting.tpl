<com:TLabel ID="DirectiveOptions" CssClass="directive_setting">
	<button type="button" class="w3-button w3-green" rel="show_all_directives">
		<i class="fa fa-expand-arrows-alt"></i> &nbsp;<%[ Show/hide all directives ]%>
	</button>
	<!--li rel="show_raw_config"><%[ Show the resource raw config ]%></li>
	<li rel="save_multiple_hosts"><%[ Save the resource on multiple hosts ]%></li>
	<li rel="save_addition_path"><%[ Save the resource to additional path ]%></li>
	<li rel="download_resource_config"><%[ Download the resource config ]%></li-->
</com:TLabel>
<com:TCallback
	ID="DirectiveOptionCall"
	OnCallback="setOption"
/>
<script type="text/javascript">
	var BaculaConfigOptions = new BaculaConfigOptionsClass({
		options_id: '<%=$this->DirectiveOptions->ClientID%>',
		action_obj: <%=$this->DirectiveOptionCall->ActiveControl->Javascript%>
	});
</script>
