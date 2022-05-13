<!DOCTYPE html>
<html lang="en">
	<com:THead Title="Bacularis - Bacula Web Interface">
	<meta charset="utf-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<link rel="icon" href="<%~ ../../../../../Common/Images/favicon.ico %>" type="image/x-icon" />
	</com:THead>
	<body  class="w3-light-grey">
		<com:TForm>
			<com:BStyleSheet StyleSheetUrl=<%~ ../../../../../Common/CSS/w3css/w3.css %> />
			<!-- Top container -->
			<div class="w3-bar w3-top w3-black w3-large" style="z-index:4">
				<img class="w3-bar-item w3-right" src="<%~ ../../../../../Common/Images/logo.png %>" alt="" style="margin-top: 3px" />
			</div>
			<!-- !PAGE CONTENT! -->
			<com:TContentPlaceHolder ID="Main" />
		</com:TForm>
	</body>
</html>
