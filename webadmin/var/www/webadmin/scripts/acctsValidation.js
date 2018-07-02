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

function validWebUser(element, labelId = false) {
	hideSuccess(element);
	if (/^([A-Za-z0-9 ._-]){1,64}$/.test(element.value)) {
		hideError(element, labelId);
	} else {
		showError(element, labelId);
	}
}

function updateWebUser(element, offset = false) {
	if (/^([A-Za-z0-9 ._-]){1,64}$/.test(element.value)) {
		if (document.getElementById("logoutuser").innerText == document.getElementById("webadminuser").value) {
			document.getElementById("logoutuser").innerText = element.value;
		}
		ajaxPost("ajax.php", "webadminuser="+element.value);
		document.getElementById("webadminuser").value = element.value;
		document.getElementById("webadmin-tab-icon").classList.add("hidden");
		document.getElementById("webadmin-alert-msg").classList.add("hidden");
		showSuccess(element);
	} 
}

function verifyWebPass(passId, verifyId) {
	var pass = document.getElementById(passId);
	var verify = document.getElementById(verifyId);
	var passLabelId = passId + "_label";
	var verifyLabelId = verifyId + "_label";
	hideSuccess(pass);
	hideSuccess(verify);
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

function updateWebPass(currentId, passId, verifyId) {
	var current = document.getElementById(currentId);
	var pass = document.getElementById(passId);
	var verify = document.getElementById(verifyId);
	var currentLabelId = currentId + "_label";
	var passLabelId = passId + "_label";
	var verifyLabelId = verifyId + "_label";
	if (/^.{1,128}$/.test(verify.value) && verify.value == pass.value) {
		if (ajaxPost("ajax.php", "confirmold="+current.value) == "true") {
			hideError(current, currentLabelId);
			ajaxPost("ajax.php", "webadminpass="+pass.value);
			document.getElementById("webadmin-tab-icon").classList.add("hidden");
			document.getElementById("webadmin-alert-msg").classList.add("hidden");
			showSuccess(pass);
			showSuccess(verify);
		} else {
			showError(current, currentLabelId);
		}
		current.value = "";
	}
}

function validHost(element, labelId = false) {
	hideSuccess(element);
	if (element.value == "") {
		hideError(element, labelId);
	} else {
		if (/^(([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])\.){3}([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])$|^(?=.{1,253}$)(([a-zA-Z]|[a-zA-Z][a-zA-Z0-9\-]*[a-zA-Z0-9])\.)*([A-Za-z]|[A-Za-z][A-Za-z0-9\-]*[A-Za-z0-9])$/.test(element.value)) {
			hideError(element, labelId);
		} else {
			showError(element, labelId);
		}
	}
}

function updateHost(schemeId, hostId, portId) {
	var scheme = document.getElementById(schemeId);
	var host = document.getElementById(hostId);
	var port = document.getElementById(portId);
	var labelId = hostId + "_label";
	if (host.value == "") {
		ajaxPost("ajax.php", "ldapserver=");
		showSuccess(host);
	} else {
		if (scheme.checked) {
			scheme.value = "ldaps";
		} else {
			scheme.value = "ldap";
		}
		if (port.value == "") {
			if (scheme.checked) {
				port.value = "636";
			} else {
				port.value = "389";
			}
		}
		if (!port.value == parseInt(port.value) && port.value >= 0 && port.value <= 65535) {
			if (scheme.checked) {
				port.value = "636";
			} else {
				port.value = "389";
			}
			showWarning(port);
		}
		if (/^(([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])\.){3}([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])$|^(?=.{1,253}$)(([a-zA-Z]|[a-zA-Z][a-zA-Z0-9\-]*[a-zA-Z0-9])\.)*([A-Za-z]|[A-Za-z][A-Za-z0-9\-]*[A-Za-z0-9])$/.test(host.value)) {
			ajaxPost("ajax.php", "ldapserver="+scheme.value+"://"+host.value+":"+port.value);
			showSuccess(host);
		}
	}
}

function validPort(element, labelId = false) {
	hideSuccess(element);
	if (element.value == "") {
		hideError(element, labelId);
	} else {
		if (element.value == parseInt(element.value) && element.value >= 0 && element.value <= 65535) {
			hideError(element, labelId);
		} else {
			hideWarning(element);
			showError(element, labelId);
		}
	}
}

function updatePort(schemeId, hostId, portId) {
	var scheme = document.getElementById(schemeId);
	var host = document.getElementById(hostId);
	var port = document.getElementById(portId);
	hideWarning(port);
	if (port.value != "" && port.value == parseInt(port.value) && port.value >= 0 && port.value <= 65535) {
		if (scheme.checked) {
			scheme.value = "ldaps";
		} else {
			scheme.value = "ldap";
		}
		if (/|^(([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])\.){3}([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])$|^(?=.{1,253}$)(([a-zA-Z]|[a-zA-Z][a-zA-Z0-9\-]*[a-zA-Z0-9])\.)*([A-Za-z]|[A-Za-z][A-Za-z0-9\-]*[A-Za-z0-9])$/.test(host.value)) {
			ajaxPost("ajax.php", "ldapserver="+scheme.value+"://"+host.value+":"+port.value);
			showSuccess(port);
		}
	}
}

function updateScheme(schemeId, hostId, portId) {
	var scheme = document.getElementById(schemeId);
	var host = document.getElementById(hostId);
	var port = document.getElementById(portId);
	var labelId = hostId + "_label";
	hideWarning(port);
	if (scheme.checked) {
		scheme.value = "ldaps";
		if (port.value == "" || port.value == "389") {
			port.value = "636";
			hideError(port, labelId);
			showWarning(port);
		}
	} else {
		scheme.value = "ldap";
		if (port.value == "" || port.value == "636") {
			port.value = "389";
			hideError(port, labelId);
			showWarning(port);
		}
	}
	if (/^(([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])\.){3}([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])$|^(?=.{1,253}$)(([a-zA-Z]|[a-zA-Z][a-zA-Z0-9\-]*[a-zA-Z0-9])\.)*([A-Za-z]|[A-Za-z][A-Za-z0-9\-]*[A-Za-z0-9])$/.test(host.value) && port.value == parseInt(port.value) && port.value >= 0 && port.value <= 65535) {
		ajaxPost("ajax.php", "ldapserver="+scheme.value+"://"+host.value+":"+port.value);
	}
}

function validDomain(domainId, hostId) {
	var host = document.getElementById(hostId);
	var domain = document.getElementById(domainId);
	var labelId = domainId + "_label";
	hideSuccess(domain);
	if (host.value == "" && domain.value == "") {
		hideError(domain, labelId);
	} else {
		if (/^(?=.{2,63}$)(([a-zA-Z]|[a-zA-Z][a-zA-Z0-9\-]*[a-zA-Z0-9])\.)*([A-Za-z]|[A-Za-z][A-Za-z0-9\-]*[A-Za-z0-9])$/.test(domain.value)) {
			hideError(domain, labelId);
		} else {
			showError(domain, labelId);
		}
	}
}

function updateDomain(domainId, hostId) {
	var host = document.getElementById(hostId);
	var domain = document.getElementById(domainId);
	if (domain.value == "") {
		ajaxPost("ajax.php", "ldapdomain=");
		if (host.value == "") {
			showSuccess(domain);
		}
	} else {
		if (/^(?=.{2,63}$)(([a-zA-Z]|[a-zA-Z][a-zA-Z0-9\-]*[a-zA-Z0-9])\.)*([A-Za-z]|[A-Za-z][A-Za-z0-9\-]*[A-Za-z0-9])$/.test(domain.value)) {
			ajaxPost("ajax.php", "ldapdomain="+domain.value);
			showSuccess(domain);
		}
	}
}

function validBaseDn(baseId, hostId) {
	var host = document.getElementById(hostId);
	var base = document.getElementById(baseId);
	var labelId = baseId + "_label";
	hideSuccess(base);
	if (host.value == "" && base.value == "") {
		hideError(domain, labelId);
	} else {
		if (/^(?:[A-Za-z][\w-]*|\d+(?:\.\d+)*)=(?:#(?:[\dA-Fa-f]{2})+|(?:[^,=\+<>#;\\"]|\\[,=\+<>#;\\"]|\\[\dA-Fa-f]{2})*|"(?:[^\\"]|\\[,=\+<>#;\\"]|\\[\dA-Fa-f]{2})*")(?:\+(?:[A-Za-z][\w-]*|\d+(?:\.\d+)*)=(?:#(?:[\dA-Fa-f]{2})+|(?:[^,=\+<>#;\\"]|\\[,=\+<>#;\\"]|\\[\dA-Fa-f]{2})*|"(?:[^\\"]|\\[,=\+<>#;\\"]|\\[\dA-Fa-f]{2})*"))*(?:,(?:[A-Za-z][\w-]*|\d+(?:\.\d+)*)=(?:#(?:[\dA-Fa-f]{2})+|(?:[^,=\+<>#;\\"]|\\[,=\+<>#;\\"]|\\[\dA-Fa-f]{2})*|"(?:[^\\"]|\\[,=\+<>#;\\"]|\\[\dA-Fa-f]{2})*")(?:\+(?:[A-Za-z][\w-]*|\d+(?:\.\d+)*)=(?:#(?:[\dA-Fa-f]{2})+|(?:[^,=\+<>#;\\"]|\\[,=\+<>#;\\"]|\\[\dA-Fa-f]{2})*|"(?:[^\\"]|\\[,=\+<>#;\\"]|\\[\dA-Fa-f]{2})*"))*)*$/.test(base.value)) {
			hideError(base, labelId);
		} else {
			showError(base, labelId);
		}
	}
}

function updateBaseDn(baseId, hostId) {
	var host = document.getElementById(hostId);
	var base = document.getElementById(baseId);
	if (base.value == "") {
		ajaxPost("ajax.php", "ldapbase=");
		if (host.value == "") {
			showSuccess(base);
			console.log("ldapbase=");
		}
	} else {
		if (/^(?:[A-Za-z][\w-]*|\d+(?:\.\d+)*)=(?:#(?:[\dA-Fa-f]{2})+|(?:[^,=\+<>#;\\"]|\\[,=\+<>#;\\"]|\\[\dA-Fa-f]{2})*|"(?:[^\\"]|\\[,=\+<>#;\\"]|\\[\dA-Fa-f]{2})*")(?:\+(?:[A-Za-z][\w-]*|\d+(?:\.\d+)*)=(?:#(?:[\dA-Fa-f]{2})+|(?:[^,=\+<>#;\\"]|\\[,=\+<>#;\\"]|\\[\dA-Fa-f]{2})*|"(?:[^\\"]|\\[,=\+<>#;\\"]|\\[\dA-Fa-f]{2})*"))*(?:,(?:[A-Za-z][\w-]*|\d+(?:\.\d+)*)=(?:#(?:[\dA-Fa-f]{2})+|(?:[^,=\+<>#;\\"]|\\[,=\+<>#;\\"]|\\[\dA-Fa-f]{2})*|"(?:[^\\"]|\\[,=\+<>#;\\"]|\\[\dA-Fa-f]{2})*")(?:\+(?:[A-Za-z][\w-]*|\d+(?:\.\d+)*)=(?:#(?:[\dA-Fa-f]{2})+|(?:[^,=\+<>#;\\"]|\\[,=\+<>#;\\"]|\\[\dA-Fa-f]{2})*|"(?:[^\\"]|\\[,=\+<>#;\\"]|\\[\dA-Fa-f]{2})*"))*)*$/.test(base.value)) {
			ajaxPost("ajax.php", "ldapbase="+base.value);
			showSuccess(base);
			console.log("ldapbase="+base.value);
		}
	}
}

function validGroup(element, labelId = false) {
	if (/^[\w- !@#%&'\$\^\(\)\.\{\}]{1,64}$/.test(element.value)) {
		hideError(element, labelId);
		enableButton("addadmin", true);
	} else {
		showError(element, labelId);
		enableButton("addadmin", false);
	}
}

function sysUserModal(userValue, gecosValue, loginValue, homeValue) {
	var current = document.getElementById("currUser");
	var user = document.getElementById("sysUser");
	var gecos = document.getElementById("sysGecos");
	var pass = document.getElementById("sysPass");
	var verify = document.getElementById("sysVerify");
	var home = document.getElementById("sysHome");
	var shell = document.getElementById("sysShell");
	var userLabelId = "sysUser_label";
	var gecosLabelId = "sysGecos_label";
	var passLabelId = "sysPass_label";
	var verifyLabelId = "sysVerify_label";
	var homeLabelId = "sysHome_label";
	var shellLabelId = "sysShell_label";
	hideError(user, userLabelId);
	hideError(gecos, gecosLabelId);
	hideError(pass, passLabelId);
	hideError(verify, verifyLabelId);
	hideError(home, homeLabelId);
	hideError(shell, shellLabelId);
	if (userValue == "smbuser" || userValue == "afpuser") {
		user.readOnly = true;
		gecos.readOnly = true;
	} else {
		user.readOnly = false;
		gecos.readOnly = false;
	}
	current.value = userValue;
	user.value = userValue;
	gecos.value = gecosValue;
	pass.value = "";
	verify.value = "";
	home.value = loginValue;
	shell.value = homeValue;
}

function verifySysUser(currId, userId, passId, verifyId) {
	var curr = document.getElementById(currId);
	var user = document.getElementById(userId);
	var pass = document.getElementById(passId);
	var verify = document.getElementById(verifyId);
	var userLabelId = userId + "_label";
	var passLabelId = passId + "_label";
	var verifyLabelId = verifyId + "_label";
	if (/^[a-z_][a-z0-9_-]{1,31}$/.test(user.value) && sysUsers.indexOf(user.value) == -1 || curr.value == user.value) {
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
		enableButton("saveSysUser", true);
	} else {
		showError(verify, verifyLabelId);
		enableButton("saveSysUser", false);
	}
}

function verifySysPass(passId, verifyId) {
	var pass = document.getElementById(passId);
	var verify = document.getElementById(verifyId);
	var passLabelId = passId + "_label";
	var verifyLabelId = verifyId + "_label";
	hideSuccess(pass);
	hideSuccess(verify);
	if (/^.{1,128}$/.test(pass.value)) {
		hideError(pass, passLabelId);
	} else {
		showError(pass, passLabelId);
	}
	if (/^.{1,128}$/.test(verify.value) && verify.value == pass.value) {
		hideError(verify, verifyLabelId);
		enableButton("saveSysUser", true);
	} else {
		showError(verify, verifyLabelId);
		enableButton("saveSysUser", false);
	}
}
