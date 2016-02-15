
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

function CustomOver(text, title, id, id2) {
	var o1 = browserObject(id); var o2 = browserObject(id2);
	if (!o1 || !o2)
	{
		return;
	}
	return overlib(text, CAPTION, title, STICKY, WIDTH, 300, CGCOLOR, "#434C55", FGCOLOR, "#F8F8F8", TEXTPADDING, 5, CAPTIONPADDING, 5, BORDER, 0);
}

