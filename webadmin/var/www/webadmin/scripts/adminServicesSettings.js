function resetProgressIndicator() {
	var e = document.getElementById('restarting');
	e.style.display = 'none';
}

function toggle_visibility(id, service) {
	var a = confirm('Are you sure you want to restart ' +  service + '?');
	if(a) {
		var e = document.getElementById(id);
      	if(e.style.display == 'none') {
          	e.style.display = 'block';
		}
		else {
  	        e.style.display = 'none';
		}
		return true;
	}
	else {
		return false;
	}
}

function yesnoprompt(message) {
	var a = confirm(message);
	if(a) {
		return true;
	}
	else {
		return false;
	}
}

function goTo(shouldGo, URL)
{
	if (shouldGo)
	{
		location.href=URL;
		return shouldGo;
	}
	else
	{
		return shouldGo;
	}
}


function changeServiceType(type)
{
	document.getElementById("SUS").setAttribute("class", "");
    document.getElementById("SUSContent").style.display="none";
	
	document.getElementById("NetBoot").setAttribute("class", "");
    document.getElementById("NetBootContent").style.display="none";

    document.getElementById("AFP").setAttribute("class", "");
    document.getElementById("AFPContent").style.display="none";

    document.getElementById("SMB").setAttribute("class", "");
    document.getElementById("SMBContent").style.display="none";

	document.getElementById(type).setAttribute("class", "current");
	document.getElementById(type+"Content").style.display="block";
	
	document.getElementById("Services").action="admin.php?service=" + type;
}
