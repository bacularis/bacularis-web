<!DOCTYPE html>
<html lang="en">
	<com:THead Title="Baculum - Bacula Web Interface">
	<meta charset="utf-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<link rel="icon" href="<%=$this->getPage()->getTheme()->getBaseUrl()%>/favicon.ico" type="image/x-icon" />
	</com:THead>
	<body  class="w3-light-grey">
		<com:TForm>
			<com:BStyleSheet StyleSheetUrl=<%~ ../../vendor/w3css/w3.css %> />
			<!-- Top container -->
			<div class="w3-bar w3-top w3-black w3-large" style="z-index:4">
				<span class="w3-bar-item w3-right">
					<img src="<%=$this->getPage()->getTheme()->getBaseUrl()%>/logo.png" alt="" />
				</span>
			</div>
			<!-- !PAGE CONTENT! -->
			<com:TContentPlaceHolder ID="Main" />
		</com:TForm>
	</body>
</html>
