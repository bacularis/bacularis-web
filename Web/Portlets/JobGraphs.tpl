<div class="w3-quarter">
	<span class="w3-margin-left"><%[ Time range: ]%></span>
	<select id="time_range" name="time_range" class="w3-select w3-border" style="width: 90%; margin-left: 5%;">
		<option value="23400"><%[ Last 6 hours ]%></option>
		<option value="43200"><%[ Last 12 hours ]%></option>
		<option value="86400" selected="selected"><%[ Today ]%></option>
		<option value="172800"><%[ Two days ]%></option>
		<option value="604800"><%[ Last week ]%></option>
		<option value="1209600"><%[ Last two weeks ]%></option>
		<option value="2592000"><%[ Last month ]%></option>
		<option value="7776000"><%[ Last three months ]%></option>
		<option value="15768000"><%[ Last six months ]%></option>
		<option value="31536000"><%[ Last year ]%></option>
	</select>
</div>
<div class="w3-quarter">
	<div class="w3-half">
		<%[ Date From: ]%> <com:TDatePicker ID="DateFrom" DateFormat="yyyy-MM-dd" Style="width: 90%; height: 39px; padding: 6px;" />
	</div>
	<div class="w3-half">
		<%[ Date To: ]%> <com:TDatePicker ID="DateTo" DateFormat="yyyy-MM-dd" Style="width: 90%; height: 39px; padding: 6px;" />
	</div>
</div>
<div class="w3-quarter">
	<span><%[ Client: ]%></span>
	<com:TDropDownList
		ID="Clients"
		CssClass="w3-select w3-border"
		AutoPostBack="false"
		Style="display: inline; width: 90%;"
	/>
</div>
<div class="w3-quarter">
	<span><%[ Job name: ]%></span>
	<select id="graph_jobs" class="w3-select w3-border" style="display: inline; width: 90%;">
		<option value="@"><%[ select job ]%></option>
	</select>
</div>
<div class="w3-right w3-margin-top" style="width: 250px; margin-right: 28px;">
	<span><%[ Graph type: ]%></span><br />
	<select id="graph_type" name="graph_type" class="w3-select w3-border w3-right" style="width: 250px">
		<option value="job_size" selected><%[ Job size ]%></option>
		<option value="job_size_per_hour"><%[ Job size per hour ]%></option>
		<option value="job_size_per_day"><%[ Job size per day ]%></option>
		<option value="avg_job_size_per_hour"><%[ Average job size per hour ]%></option>
		<option value="avg_job_size_per_day"><%[ Average job size per day ]%></option>
		<option value="job_files"><%[ Job files ]%></option>
		<option value="job_files_per_hour"><%[ Job files per hour ]%></option>
		<option value="job_files_per_day"><%[ Job files per day ]%></option>
		<option value="avg_job_files_per_hour"><%[ Average job files per hour ]%></option>
		<option value="avg_job_files_per_day"><%[ Average job files per day ]%></option>
		<option value="job_count_per_hour"><%[ Job count per hour ]%></option>
		<option value="job_count_per_day"><%[ Job count per day ]%></option>
		<option value="job_duration"><%[ Job duration ]%></option>
		<option value="avg_job_speed"><%[ Average job speed ]%></option>
		<option value="job_status_per_day"><%[ Job status per day ]%></option>
	</select>
</div>
<div class="w3-right w3-margin-top w3-margin-right" style="width: 215px">
	<span><%[ Job level: ]%></span><br />
	<select id="job_level" name="job_level" class="w3-select w3-border" style="width: 200px">
		<option value="@" selected><%[ All levels ]%></option>
		<option value="F"><%[ Full ]%></option>
		<option value="I"><%[ Incremental ]%></option>
		<option value="D"><%[ Differential ]%></option>
	</select>
</div>
<span class="w3-margin-left"><%[ Legend: ]%></span>
<div id="legend_container" class="w3-margin-left"></div>
<div id="graphs_container" style="height: 500px; margin-top: 60px;"></div>
<script type="text/javascript">
	MonitorParams = {jobs: null};
	var graph_lang = {
		job_size: {
			graph_title: '<%[ Graph: ]%> <%[ Job size / Time ]%>',
			xaxis_title: '<%[ Time ]%>',
			yaxis_title: '<%[ Job size ]%>'
		},
		job_size_per_hour: {
			graph_title: '<%[ Graph: ]%> <%[ Job size / Hour ]%>',
			xaxis_title: '<%[ Hours ]%>',
			yaxis_title: '<%[ Job size ]%>'
		},
		job_size_per_day: {
			graph_title: '<%[ Graph: ]%> <%[ Job size / Day ]%>',
			xaxis_title: '<%[ Days ]%>',
			yaxis_title: '<%[ Job size ]%>'
		},
		avg_job_size_per_hour: {
			graph_title: '<%[ Graph: ]%> <%[ Average job size / Hour ]%>',
			xaxis_title: '<%[ Hours ]%>',
			yaxis_title: '<%[ Job size ]%>'
		},
		avg_job_size_per_day: {
			graph_title: '<%[ Graph: ]%> <%[ Average job size / Day ]%>',
			xaxis_title: '<%[ Days ]%>',
			yaxis_title: '<%[ Job size ]%>'
		},
		job_files: {
			graph_title: '<%[ Graph: ]%> <%[ Job files / Time ]%>',
			xaxis_title: '<%[ Time ]%>',
			yaxis_title: '<%[ Files count ]%>'
		},
		job_files_per_hour: {
			graph_title: '<%[ Graph: ]%> <%[ Job files / Hour ]%>',
			xaxis_title: '<%[ Hours ]%>',
			yaxis_title: '<%[ Files count ]%>'
		},
		job_files_per_day: {
			graph_title: '<%[ Graph: ]%> <%[ Job files / Day ]%>',
			xaxis_title: '<%[ Days ]%>',
			yaxis_title: '<%[ Files count ]%>'
		},
		avg_job_files_per_hour: {
			graph_title: '<%[ Graph: ]%> <%[ Average job files / Hour ]%>',
			xaxis_title: '<%[ Hours ]%>',
			yaxis_title: '<%[ Files count ]%>'
		},
		avg_job_files_per_day: {
			graph_title: '<%[ Graph: ]%> <%[ Average job files / Day ]%>',
			xaxis_title: '<%[ Days ]%>',
			yaxis_title: '<%[ Files count ]%>'
		},
		job_count_per_hour: {
			graph_title: '<%[ Graph: ]%> <%[ Job count / Hour ]%>',
			xaxis_title: '<%[ Hours ]%>',
			yaxis_title: '<%[ Job count ]%>'
		},
		job_count_per_day: {
			graph_title: '<%[ Graph: ]%> <%[ Job count / Day ]%>',
			xaxis_title: '<%[ Days ]%>',
			yaxis_title: '<%[ Job count ]%>'
		},
		job_duration: {
			graph_title: '<%[ Graph: ]%> <%[ Jobs duration / Time ]%>',
			xaxis_title: '<%[ Time ]%>',
			yaxis_title: '<%[ Duration ]%>'
		},
		avg_job_speed: {
			graph_title: '<%[ Graph: ]%> <%[ Average job speed / Time ]%>',
			xaxis_title: '<%[ Time ]%>',
			yaxis_title: '<%[ Job speed ]%>'
		},
		job_status_per_day: {
			graph_title: '<%[ Graph: ]%> <%[ Jobs status / Day ]%>',
			xaxis_title: '<%[ Time ]%>',
			yaxis_title: '<%[ Jobs count ]%>'
		},
		filters: {
			custom_time_range: '<%[ Custom time range ]%>'
		}
	};
	var oGraph;
	$(function() {
		MonitorCalls.push(function() {
			oGraph = new GraphClass({
				jobs: oData.jobs,
				txt: graph_lang,
				date_from: '<%=$this->DateFrom->ClientID%>',
				date_to: '<%=$this->DateTo->ClientID%>',
				client_filter: '<%=$this->Clients->ClientID%>'
			});
		});
		ThemeMode.add_cb(() => {
			oGraph.update();
		});
	});
</script>
<p class="info w3-hide-medium w3-hide-small"><%[ Tip: to use zoom, please mark area on graph. ]%></p>
<p class="info w3-hide-medium w3-hide-small"><%[ Tip 2: to exit zoom, please click somewhere on graph. ]%></p>
