<h4 class="view_header bold header_item<%=$this->getCssClass() ? ' ' . Miscellaneous::html_value($this->getCssClass()) : ''%>">
	<i class="<%=Miscellaneous::html_value($this->getIcon())%>"></i> 
	<%=Prado::localize($this->getTitle())%> 
	<span class="w3-round w3-white<%=$this->getItemName() ? '' : ' hide'%>"><%=Miscellaneous::html_value($this->getItemName())%> 
		<span class="w3-small<%=$this->getSubItemName() ? '' : ' hide'%>">&nbsp;[<%=Miscellaneous::html_value($this->getSubItemName())%>]</span>
	</span>
</h4>
