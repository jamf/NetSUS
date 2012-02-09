
var DOMBROWSER = "default";


function browserObject(objid)
{
	if (DOMBROWSER == "default")
	{
		return document.getElementById(objid);
	} else if (DOMBROWSER == "NS4") {
		return document.layers[objid];
	} else if (DOMBROWSER == "IE4") {
		return document.all[objid];
	}
}

function CustomOver(text, id, id2) {
	var o1 = browserObject(id); var o2 = browserObject(id2);
	if (!o1 || !o2)
	{
		return;
	}

	return overlib(text, FOLLOWMOUSE, WIDTH, 100, FGCLASS, "infoPanelBackground", BGCLASS, "infoPanelBorder", TEXTFONTCLASS, "infoPanelFont", CAPTIONFONTCLASS, "infoPanelFont", CLOSEFONTCLASS, "infoPanelFont");
}

