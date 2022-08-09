<com:TActiveLabel ID="DirectiveOptions" CssClass="directive_setting">
	<span>
		<label><span class="w3-hide-small"><%[ All directives ]%></span>
			<label class="switch" title="<%[ Show/hide all directives ]%>" style="vertical-align: middle; margin-right: 8px;">
				<com:TActiveCheckBox ID="AllDirectives" CssClass="directive_option" Attributes.rel="show_all_directives" />
				<span class="slider round"></span>
			</label>
		</label>
	</span>
	<!--li rel="show_raw_config"><%[ Show the resource raw config ]%></li>
	<li rel="save_multiple_hosts"><%[ Save the resource on multiple hosts ]%></li>
	<li rel="save_addition_path"><%[ Save the resource to additional path ]%></li>
	<li rel="download_resource_config"><%[ Download the resource config ]%></li-->
</com:TActiveLabel>
<com:TCallback
	ID="DirectiveOptionCall"
	OnCallback="setOption"
/>
<script>
	var BaculaConfigOptions = new BaculaConfigOptionsClass({
		options_id: '<%=$this->DirectiveOptions->ClientID%>',
		action_obj: <%=$this->DirectiveOptionCall->ActiveControl->Javascript%>
	});
</script>
