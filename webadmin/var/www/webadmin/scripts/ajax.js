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
	var message = '<div style="padding: 8px 0px;">Are you sure you want to restart the Server?</div>';
	var connections = parseInt(ajaxPost('sharingCtl.php', 'smbconns')) + parseInt(ajaxPost('sharingCtl.php', 'afpconns'));
	if (connections > 0) {
		if (connections == 1) {
			users = 'is 1 user';
		} else {
			users = 'are ' + connections + ' users';
		}
		message = message + '\n<div class="text-muted" style="padding: 8px 0px;"><span class="glyphicon glyphicon-exclamation-sign"></span> There ' + users + ' connected to this server. If you restart they will be disconnected.';
	}
	document.getElementById('restart-message').innerHTML = message;
}

function restartServer() {
	document.getElementById('restart-cancel').disabled = true;
	document.getElementById('restart-confirm').disabled = true;
	document.getElementById('restart-title').innerHTML = 'Restarting...';
	$("#restart-message").addClass('hidden');
	$("#restart-progress").removeClass('hidden');
	setTimeout('location.href = "index.php"', 60000);
	ajaxPost('ajax.php', 'restart');
}

function shutdownModal() {
	var message = '<div style="padding: 8px 0px;">Are you sure you want to shut down the Server?<br>The Server will need to be restarted manually.</div>';
	var connections = parseInt(ajaxPost('sharingCtl.php', 'smbconns')) + parseInt(ajaxPost('sharingCtl.php', 'afpconns'));
	if (connections > 0) {
		if (connections == 1) {
			users = 'is 1 user';
		} else {
			users = 'are ' + connections + ' users';
		}
		message = message + '\n<div class="text-muted" style="padding: 8px 0px;"><span class="glyphicon glyphicon-exclamation-sign"></span> There ' + users + ' connected to this server. If you shut down they will be disconnected.';
	}
	document.getElementById('shutdown-message').innerHTML = message;
}

function shutdownServer() {
	document.getElementById('shutdown-cancel').disabled = true;
	document.getElementById('shutdown-confirm').disabled = true;
	document.getElementById('shutdown-title').innerHTML = 'Shutting Down...';
	$("#shutdown-message").addClass('hidden');
	$("#shutdown-progress").removeClass('hidden');
	setTimeout('location.href = "https://www.jamf.com/jamf-nation/third-party-products/180/netboot-sus-appliance?view=info"', 10000);
	ajaxPost('ajax.php', 'shutdown');
}

function disableGUI() {
	document.getElementById('disablegui-cancel').disabled = true;
	document.getElementById('disablegui-confirm').disabled = true;
	document.getElementById('disablegui-title').innerHTML = 'Disabling GUI...';
	$("#disablegui-message").addClass('hidden');
	$("#disablegui-progress").removeClass('hidden');
	setTimeout('location.href = "index.php"', 3000);
	ajaxPost('ajax.php', 'disablegui');
}