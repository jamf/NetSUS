function enableButton(buttonId, enable) {
	document.getElementById(buttonId).disabled = !enable;
}

function showError(element, labelId = false) {
	element.parentElement.classList.add("has-error");
	if (labelId) {
		document.getElementById(labelId).classList.add("text-danger");
	}
}

function hideError(element, labelId = false) {
	element.parentElement.classList.remove("has-error");
	if (labelId) {
		document.getElementById(labelId).classList.remove("text-danger");
	}
}

function showSuccess(element, offset = false) {
	var span = document.createElement("span");
	span.className = "glyphicon glyphicon-ok form-control-feedback text-success";
	if (offset) {
		span.style.right = offset + "px";
	}
	element.parentElement.appendChild(span);
}

function hideSuccess(element) {
	var span = element.parentElement.getElementsByTagName("span");
	for (var i = 0; i < span.length; i++) {
		if (span[i].classList.contains("form-control-feedback")) {
			element.parentElement.removeChild(span[i]);
		}
	}
}

function showWarning(element, offset = false) {
	var span = document.createElement("span");
	span.className = "glyphicon glyphicon-exclamation-sign form-control-feedback text-muted";
	if (offset) {
		span.style.right = offset + "px";
	}
	element.parentElement.appendChild(span);
}

function hideWarning(element) {
	var span = element.parentElement.getElementsByTagName("span");
	for (var i = 0; i < span.length; i++) {
		if (span[i].classList.contains("form-control-feedback")) {
			element.parentElement.removeChild(span[i]);
		}
	}
}

function validBranch(element, labelId = false) {
	if (existingBranches.indexOf(element.value) == -1 && /^[A-Za-z0-9._+\-]{1,128}$/.test(element.value)) {
		hideError(element, labelId);
		enableButton("addbranch", true);
	} else {
		showError(element, labelId);
		enableButton("addbranch", false);
	}
}

function defaultBranch(element) {
	checked = element.checked;
	elements = document.getElementsByName('rootbranch');
	for (i = 0; i < elements.length; i++) {
		elements[i].checked = false;
	}
	ajaxPost('susCtl.php?branch='+element.value, 'rootbranch='+checked);
	element.checked = checked;
}

function validBaseUrl(element, labelId = false) {
	hideSuccess(element);
	if (/^http:\/\/(([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])\.){3}([0-9]|[0-9][\/]|[1-9][0-9]|[1-9][0-9][\/]|1[0-9]{2}|1[0-9]{2}[\/]|2[0-4][0-9]|2[0-4][0-9][\/]|25[0-5]|25[0-5][\/])$|^http:\/\/(([a-zA-Z]|[a-zA-Z][a-zA-Z0-9\-]*[a-zA-Z0-9])\.)*([A-Za-z]|[A-Za-z][\/]|[A-Za-z][A-Za-z0-9\-]*[A-Za-z0-9]|[A-Za-z][A-Za-z0-9\-]*[A-Za-z0-9][\/])$/.test(element.value)) {
		hideError(element, labelId);
	} else {
		showError(element, labelId);
	}
}

function updateBaseUrl(element, offset = false) {
	hideWarning(element);
	if (/^http:\/\/(([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])\.){3}([0-9]|[0-9][\/]|[1-9][0-9]|[1-9][0-9][\/]|1[0-9]{2}|1[0-9]{2}[\/]|2[0-4][0-9]|2[0-4][0-9][\/]|25[0-5]|25[0-5][\/])$|^http:\/\/(([a-zA-Z]|[a-zA-Z][a-zA-Z0-9\-]*[a-zA-Z0-9])\.)*([A-Za-z]|[A-Za-z][\/]|[A-Za-z][A-Za-z0-9\-]*[A-Za-z0-9]|[A-Za-z][A-Za-z0-9\-]*[A-Za-z0-9][\/])$/.test(element.value)) {
		ajaxPost('susCtl.php', 'baseurl='+element.value);
		showSuccess(element);
	}
}

function setSyncSchedule(element) {
	var syncSch = "Off";
	var checked = element.checked;
	if (checked) {
		syncSch = element.value;
	}
	elements = document.getElementsByName('syncsch');
	for (i = 0; i < elements.length; i++) {
		elements[i].checked = false;
	}
	ajaxPost('susCtl.php', 'syncschedule='+syncSch);
	element.checked = checked;
}

function validProxy(hostId, portId, userId, passId, verifyId) {
	var host = document.getElementById(hostId);
	var port = document.getElementById(portId);
	var user = document.getElementById(userId);
	var pass = document.getElementById(passId);
	var verify = document.getElementById(verifyId);
	var hostLabelId = hostId + "_label";
	var portLabelId = hostLabelId;
	var userLabelId = userId + "_label";
	var passLabelId = passId + "_label";
	var verifyLabelId = verifyId + "_label";
	if (host.value == "" && port.value == "") {
		host.placeholder = "[Optional]";
		port.placeholder = "[Optional]";
		user.disabled = true;
		pass.disabled = true;
		verify.disabled = true;
		hideSuccess(user);
		hideSuccess(pass);
		hideSuccess(verify);
		hideError(host, hostLabelId);
		hideError(port, portLabelId);
		hideError(user, userLabelId);
		hideError(pass, passLabelId);
		hideError(verify, verifyLabelId);
	} else {
		host.placeholder = "[Required]";
		port.placeholder = "[Required]";
		user.disabled = false;
		pass.disabled = false;
		verify.disabled = false;
		if (/^(([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])\.){3}([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])$|^(?=.{1,253}$)(([a-zA-Z]|[a-zA-Z][a-zA-Z0-9\-]*[a-zA-Z0-9])\.)*([A-Za-z]|[A-Za-z][A-Za-z0-9\-]*[A-Za-z0-9])$/.test(host.value)) {
			hideError(host, hostLabelId);
		} else {
			showError(host, hostLabelId);
		}
		if (port.value != "" && port.value == parseInt(port.value) && port.value >= 0 && port.value <= 65535) {
			hideError(port, portLabelId);
		} else {
			showError(port, portLabelId);
		}
		if (user.value == "" && pass.value == "" && verify.value == "") {
			user.placeholder = "[Optional]";
			pass.placeholder = "[Optional]";
			verify.placeholder = "[Optional]";
			hideError(user, userLabelId);
			hideError(pass, passLabelId);
			hideError(verify, verifyLabelId);
		} else {
			user.placeholder = "[Required]";
			pass.placeholder = "[Required]";
			verify.placeholder = "[Required]";
			if (/^.{1,128}$/.test(user.value)) {
				hideError(user, userLabelId);
			} else {
				showError(user, userLabelId);
			}
			if (/^.{1,128}$/.test(pass.value)) {
				hideError(pass, passLabelId);
			} else {
				showError(pass, passLabelId);
			}
			if (/^.{1,128}$/.test(verify.value) && verify.value == pass.value) {
				hideError(verify, verifyLabelId);
			} else {
				showError(verify, verifyLabelId);
			}
		}
	}
}

function updateProxy(hostId, portId, userId, passId, verifyId) {
	var host = document.getElementById(hostId);
	var port = document.getElementById(portId);
	var user = document.getElementById(userId);
	var pass = document.getElementById(passId);
	var verify = document.getElementById(verifyId);
	if (host.value == "" && port.value == "") {
		ajaxPost("susCtl.php", "proxy=");
	}
	if (/^(([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])\.){3}([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])$|^(?=.{1,253}$)(([a-zA-Z]|[a-zA-Z][a-zA-Z0-9\-]*[a-zA-Z0-9])\.)*([A-Za-z]|[A-Za-z][A-Za-z0-9\-]*[A-Za-z0-9])$/.test(host.value) && port.value != "" && port.value == parseInt(port.value) && port.value >= 0 && port.value <= 65535) {
		if (user.value == "" && pass.value == "" && verify.value == "") {
			hideSuccess(host);
			hideSuccess(port);
			ajaxPost("susCtl.php", "proxy="+host.value+" "+port.value);
			showSuccess(host);
			showSuccess(port);
		}
		if (/^.{1,128}$/.test(user.value) && /^.{1,128}$/.test(pass.value) && verify.value == pass.value) {
			hideSuccess(user);
			hideSuccess(pass);
			hideSuccess(verify);
			ajaxPost("susCtl.php", "proxy="+host.value+" "+port.value+" "+user.value+" "+pass.value);
			showSuccess(user);
			showSuccess(pass);
			showSuccess(verify);
		}
	}
}

function validCatalogURL(element, labelId = false) {
	if (validCatalogURLs.indexOf(element.value) >= 0 && appleCatalogURLs.indexOf(element.value) == -1) {
		hideError(element, labelId);
		enableButton("addcatalogurl", true);
	} else {
		showError(element, labelId);
		enableButton("addcatalogurl", false);
	}
}

function setCatalogURLs(element) {
	var checkedCatalogURLs = [];
	elements = document.getElementsByName('catalogurl');
	for (i = 0; i < elements.length; i++) {
		if (elements[i].checked) {
			checkedCatalogURLs.push(elements[i].value);
		}
	}
	if (checkedCatalogURLs.length == 1 && otherCatalogURLs.length == 0) {
		for (i = 0; i < elements.length; i++) {
			if (elements[i].checked) {
				elements[i].disabled = true;
			}
		}
	} else {
		for (i = 0; i < elements.length; i++) {
			elements[i].disabled = false;
		}
	}
	if (document.getElementById("delete_other")) {
		document.getElementById("delete_other").disabled = checkedCatalogURLs.length == 0 && otherCatalogURLs.length == 1;
	}
	appleCatalogURLs = checkedCatalogURLs.concat(otherCatalogURLs);
	ajaxPost("susCtl.php", "catalogurls="+appleCatalogURLs);
}
