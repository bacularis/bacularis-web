<h4 class="view_header bold header_item<%=$this->getCssClass() ? ' ' . $this->getCssClass() : ''%>">
	<i class="<%=$this->getIcon()%>"></i> 
	<%=Prado::localize($this->getTitle())%> 
	<span class="w3-round w3-white<%=$this->getItemName() ? '' : ' hide'%>"><%=$this->getItemName()%> 
		<%=$this->getSubItemName() ? '<span class="w3-small">&nbsp;[' . $this->getSubItemName(). ']</span>' : ''%>
	</span>
</h4>
