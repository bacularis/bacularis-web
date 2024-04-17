<div class="w3-margin-top">
	<div style="display: inline-block; clear: right; margin-right: 5px;" class="w3-left">
		<div class="w3-left" style="display: inline-block">
			<com:TActiveTextBox
				ID="FileListSearch"
				CssClass="w3-input w3-border"
				Style="width: 200px; display: inline-block;"
				Attributes.placeholder="<%[ Find file or directory ]%>"
				Attributes.onkeyup="var keycode = event.keyCode || event.which; if (keycode === 13) { find_job_list_items(); }"
			/>
			<button type="button" class="w3-button w3-dark-grey" onclick="find_job_list_items();"><i class="fas fa-search"></i> &nbsp;<%[ Find ]%></button>
			<button type="button" class="w3-button w3-dark-grey" onclick="clear_job_list_items();" title="<%[ Clear ]%>"><i class="fas fa-times"></i></button>
			<i id="jobfiles_loading" class="fa fa-sync w3-spin w3-margin-left" style="display: none;"></i>
		</div>
		<div style="clear: left"></div>
		<div class="w3-section w3-left" style="display: inline-block">
			<com:TActiveDropDownList
				ID="FileListOrderBy"
				CssClass="w3-select w3-border"
				Style="width: 130px;"
				ClientSide.OnComplete="load_job_list_files();"
			>
				<com:TListItem Value="none" Text="Select order by" />
				<com:TListItem Value="file" Text="File" />
				<com:TListItem Value="size" Text="Size" />
				<com:TListItem Value="mtime" Text="MTIME" />
			</com:TActiveDropDownList>
			<com:TActiveDropDownList
				ID="FileListOrderType"
				CssClass="w3-select w3-border"
				style="width: 100px;"
				ClientSide.OnComplete="load_job_list_files();"
			>
				<com:TListItem Value="asc" Text="Ascending" />
				<com:TListItem Value="desc" Text="Descending" />
			</com:TActiveDropDownList>
			<com:Bacularis.Web.Portlets.JobTopFiles JobId="<%=$this->getJobId()%>" />
		</div>
	</div>
	<button type="button" class="w3-button w3-dark-grey w3-right" onclick="load_job_list_files();"><i class="fa fa-check"></i> &nbsp;<%[ Apply ]%></button>
	<div style="display: inline-block;" class="w3-right w3-margin-right">
		<span><%[ Offset: ]%></span> <com:TActiveTextBox ID="FileListOffset" Width="70px" CssClass="w3-input w3-border" Style="display: inline-block" />
		<span><%[ Limit: ]%></span> <com:TActiveTextBox ID="FileListLimit" Width="70px" CssClass="w3-input w3-border" Style="display: inline-block" />
	</div>
	<div style="display: inline-block;" class="w3-right w3-margin-right">
		<%[ List type: ]%> <com:TActiveDropDownList ID="FileListType" CssClass="w3-select w3-border" style="width: 150px;">
			<com:TListItem Value="" Text="<%[ saved items ]%>" />
			<com:TListItem Value="deleted" Text="<%[ deleted items ]%>" />
			<com:TListItem Value="all" Text="<%[ all ]%>" />
		</com:TActiveDropDownList>
	</div>
	<div style="clear: right"></div>
	<div class="w3-right w3-margin-top">
		<button type="button" class="w3-button w3-dark-grey w3-right w3-margin-left" onclick="get_job_list_files(1);"><%[ Next ]%> &nbsp;<i class="fa fa-arrow-right"></i></button>
		<button type="button" class="w3-button w3-dark-grey w3-right" onclick="get_job_list_files(-1);"><i class="fa fa-arrow-left"></i> &nbsp;<%[ Previous ]%></button>
		<span class="w3-right w3-margin-right" style="line-height: 38px"><%[ Item count: ]%> <com:TActiveLabel ID="FileListCount" /></span>
	</div>
</div>
<div>
	<div id="job_list_files_no_result" class="w3-panel w3-center" style="display: none"><strong><%[ No item result ]%></strong></div>
	<com:TActiveRepeater ID="FileList">
		<prop:HeaderTemplate>
			<table class="w3-table w3-striped w3-margin-bottom dataTable dtr-column">
				<thead>
					<tr class="row">
						<th class="w3-center w3-hide-small" style="width: 65px"><%[ Attributes ]%></th>
						<th class="w3-center w3-hide-small" style="width: 39px; padding: 10px 3px">UID</th>
						<th class="w3-center w3-hide-small" style="width: 39px; padding: 10px 3px">GID</th>
						<th class="w3-center w3-hide-small" style="width: 40px">Size</th>
						<th class="w3-center w3-hide-small" style="width: 135px">MTIME</th>
						<th class="w3-center"><%[ File ]%></th>
						<th class="w3-center w3-hide-small" style="width: 50px"><%[ State ]%></th>
					</tr>
				</thead>
		</prop:HeaderTemplate>
		<prop:ItemTemplate>
			<tr class="row">
				<td class="w3-hide-small"><%#$this->Data->lstat->mode%></td>
				<td class="w3-center w3-hide-small"><%#$this->Data->lstat->uid%></td>
				<td class="w3-center w3-hide-small"><%#$this->Data->lstat->gid%></td>
				<td class="w3-hide-small"><span class="size w3-right"><%#$this->Data->lstat->size%></span></td>
				<td class="udatetime w3-hide-small"><%#$this->Data->lstat->mtime%></td>
				<td style="word-wrap: break-word"><%#$this->Data->file%></td>
				<td class="<%#$this->Data->fileindex > 0 ? 'w3-text-green' : 'w3-text-orange'%> w3-center w3-hide-small"><strong><%#$this->Data->fileindex > 0 ? Prado::localize('saved') : Prado::localize('deleted')%></strong></td>
			</tr>
		</prop:ItemTemplate>
		<prop:FooterTemplate>
			</table>
		</prop:FooterTemplate>
	</com:TActiveRepeater>
</div>
<com:TCallback
	ID="LoadJobFileList"
	OnCallback="loadFileList"
	ClientSide.OnLoading="show_job_list_files_loader(true)"
	ClientSide.OnComplete="job_list_files_msg(); show_job_list_files_loader(false); Formatters.set_formatters();"
/>
<script>
function get_job_list_files(page_direction) {
	var ofs = document.getElementById('<%=$this->FileListOffset->ClientID%>');
	var lmt = document.getElementById('<%=$this->FileListLimit->ClientID%>');
	var ofs_val =  (parseInt(lmt.value, 10) * page_direction) + parseInt(ofs.value, 10);
	ofs.value = (ofs_val > 0) ? ofs_val : 0;
	load_job_list_files();
}
function find_job_list_items() {
	document.getElementById('<%=$this->FileListOffset->ClientID%>').value = 0;
	document.getElementById('<%=$this->FileListLimit->ClientID%>').value = <%=JobListFiles::DEFAULT_PAGE_SIZE%>;
	load_job_list_files();
}
function clear_job_list_items() {
	document.getElementById('<%=$this->FileListSearch->ClientID%>').value = '';
	find_job_list_items();
}
function load_job_list_files() {
	var request = <%=$this->LoadJobFileList->ActiveControl->Javascript%>;
	request.dispatch();
}
function job_list_files_msg() {
	var flc = document.getElementById('<%=$this->FileListCount->ClientID%>');
	var item_count = parseInt(flc.textContent, 10);
	document.getElementById('job_list_files_no_result').style.display = item_count == 0 ? '' : 'none';
}
function show_job_list_files_loader(show) {
	document.getElementById('jobfiles_loading').style.display = (show ? '' : 'none');
}
job_list_files_msg();
</script>
