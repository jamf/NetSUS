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

function restartModal() {
	var msg = '<div style="padding: 8px 0px;">Are you sure you want to restart the Server?</div>';
	var conns = parseInt(ajaxPost('ajax.php', 'getconns'));
	if (conns > 0) {
		if (conns == 1) {
			users = 'is 1 user';
		} else {
			users = 'are ' + conns + ' users';
		}
		msg = msg + '\n<div class="text-muted" style="padding: 8px 0px;"><span class="glyphicon glyphicon-exclamation-sign"></span> There ' + users + ' connected to this server. If you restart they will be disconnected.';
	}
	document.getElementById('restart-body').innerHTML = msg;
}

function restartServer() {
	document.getElementById('restart-cancel').disabled = true;
	document.getElementById('restart-confirm').disabled = true;
	document.getElementById('restart-title').innerHTML = 'Restarting...';
	document.getElementById('restart-body').innerHTML = '<div class="text-center" style="padding: 8px 0px;">\n<img src="images/progress.gif">\n</div>';
	setTimeout('location.href = "index.php"', 60000);
	ajaxPost('ajax.php', 'restart');
}

function shutdownModal() {
	var msg = '<div style="padding: 8px 0px;">Are you sure you want to shut down the Server?<br>The Server will need to be restarted manually.</div>';
	var conns = parseInt(ajaxPost('ajax.php', 'getconns'));
	if (conns > 0) {
		if (conns == 1) {
			users = 'is 1 user';
		} else {
			users = 'are ' + conns + ' users';
		}
		msg = msg + '\n<div class="text-muted" style="padding: 8px 0px;"><span class="glyphicon glyphicon-exclamation-sign"></span> There ' + users + ' connected to this server. If you shut down they will be disconnected.';
	}
	document.getElementById('shutdown-body').innerHTML = msg;
}

function shutdownServer() {
	document.getElementById('shutdown-cancel').disabled = true;
	document.getElementById('shutdown-confirm').disabled = true;
	document.getElementById('shutdown-title').innerHTML = 'Shutting Down...';
	document.getElementById('shutdown-body').innerHTML = '<div class="text-center" style="padding: 8px 0px;">\n<img src="images/progress.gif">\n</div>';
	setTimeout('location.href = "https://www.jamf.com/jamf-nation/third-party-products/180/netboot-sus-appliance?view=info"', 10000);
	ajaxPost('ajax.php', 'shutdown');
}

function disableGUIModal() {
	var msg = '<div style="padding: 8px 0px;">Are you sure you want to disable the web interface for the Server?<br>Command line access is required to re-enable the web interface.</div>';
	document.getElementById('disablegui-body').innerHTML = msg;
}

function disableGUI() {
	document.getElementById('disablegui-cancel').disabled = true;
	document.getElementById('disablegui-confirm').disabled = true;
	document.getElementById('disablegui-title').innerHTML = 'Disabling GUI...';
	document.getElementById('disablegui-body').innerHTML = '<div class="text-center" style="padding: 8px 0px;">\n<img src="images/progress.gif">\n</div>';
	setTimeout('location.href = "index.php"', 3000);
	ajaxPost('ajax.php', 'disablegui');
}