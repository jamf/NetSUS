var http;

function getHTTPObj() {
	return new XMLHttpRequest();
}

function ajaxPost(url, data) {
	var http = getHTTPObj();
	http.open("POST", url, false);
	http.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	http.send(data);
	return http.responseText;
}
