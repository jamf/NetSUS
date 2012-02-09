var http;

function getHTTPObj()
{
	return new XMLHttpRequest();
}

function ajaxPost(url, data)
{
	var http = getHTTPObj();
	http.open("POST", url, false);
	http.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	http.send(data);
	return http.responseText;
}

function getPackageDetails(id)
{
	http = getHTTPObj();
	http.open("GET", "ajax.php?getprodinfo=true&id="+id, false);
	http.send();
	var result = http.responseText;
	return "<table style=\"width: 150px;\"><tr><td><font class=\"infoPanelFontLabel\">"+result+"</font></td></tr></table>";
}
