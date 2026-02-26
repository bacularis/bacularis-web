<com:TDropDownList
	ID="ResourceNames"
	CssClass="w3-select w3-border w3-show-inline-block w3-margin-left header_item"
	Attributes.onchange="if (this.selectedIndex != 0) { const fragment = get_url_fragment(); document.location.href = this.value + (fragment ? '#' + fragment : ''); }"
	Width="300px"
/>
