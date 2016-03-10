$(document).ready(function() {

	$("#logo").mouseover(function() {
		$("#logo-hover").show();
	});

	$("#logo").mouseout(function() {
		$("#logo-hover").hide();
	});

	$("#logs").click(function() {
		$(this).toggleClass("logs-active");
	});

	$("#settings").click(function() {
		$(this).toggleClass("settings-active");
	});

    $("#notifications-link").click(function() {
        $("#notifications-modal").toggle();
    });

    $("#notifications-link-mobile").click(function() {
        $("#notifications-modal-mobile").toggle();
    });

    $("#notifications-modal .handle").click(function() {
        $("#notifications-modal").toggle();
    });

    $("#notifications-modal-mobile .handle").click(function() {
        $("#notifications-modal-mobile").toggle();
    });

	$("#user-link").click(function() {
		$("#user-modal").toggle();
	});

    $("#user-link-mobile").click(function() {
        $("#user-modal-mobile").toggle();
    });

	$("#user-modal .handle").click(function() {
		$("#user-modal").toggle();
	});

    $("#user-modal-mobile .handle").click(function() {
        $("#user-modal-mobile").toggle();
    });

    $("#search-icon").click(function() {
        $("#searchbox").toggle();
        $(".active-page").toggle();
        $("#logo-dash").toggleClass("logo-search");
    });

    $("#search-icon-mobile").click(function() {
        $("#searchbox").toggle();
    });

    $("#close-search").click(function() {
        $("#searchbox").toggle();
        $(".active-page").toggle();
        $("#logo-dash").toggleClass("logo-search");
    });

	(function($) {
    function toggleLabel() {
        var input = $(this);
        setTimeout(function() {
            var def = input.attr('title');
            if (!input.val() || (input.val() == def)) {
                input.prev('span').css('visibility', '');
                if (def) {
                    var dummy = $('<label></label>').text(def).css('visibility','hidden').appendTo('body');
                    input.prev('span').css('margin-left', dummy.width() + 3 + 'px');
                    dummy.remove();
                }
            } else {
                //input.prev('span').css('visibility', 'hidden');
            }
        }, 0);
    };

    function resetField() {
        var def = $(this).attr('title');
        if (!$(this).val() || ($(this).val() == def)) {
            $(this).val(def);
            $(this).prev('span').css('visibility', '');
        }
    };



    
	})(jQuery);

	
});


var currentScrollPosition = 0;


function showDeletePrompt(){
	document.getElementById("form-inside").style.display="none";
	document.getElementById("delete-verification").style.display="block";
	document.getElementById("read-buttons").style.display="none";
	document.getElementById("delete-buttons").style.display="block";
}
function cancelDeletePrompt(){
	document.getElementById("form-inside").style.display="block";
	document.getElementById("delete-verification").style.display="none";
	document.getElementById("read-buttons").style.display="block";
	document.getElementById("delete-buttons").style.display="none";
}

function disableForm(){
	$('#form-inside').find('input, textarea, button, select, submit').attr('disabled','disabled');
}

function changeTab(tab){
	$('#form-wrapper ul.tabs').find('li').attr('class','');
	$('#form-inside').find('div.pane').attr('style','display:none');
	
	document.getElementById(tab).setAttribute("class", "active");
	document.getElementById(tab + "_Pane").style.display="";
	
	if(document.getElementById("tab")!=null){
		if(document.getElementById("tab").value=="null"){
			document.getElementById("tab").value = "";
		}else{
			document.getElementById("tab").value = tab;
		}
	}
}


function getXMLValue(xmlDocument, elementName){
	if(xmlDocument.getElementsByTagName(elementName).length != 0){
		if(xmlDocument.getElementsByTagName(elementName)[0].childNodes[0]!=null){
			return xmlDocument.getElementsByTagName(elementName)[0].childNodes[0].nodeValue;
		}
	}
	return "";
}

function updateFieldText(htmlElementID, content){	
	document.getElementById(htmlElementID).textContent=content;
}

function updateFieldTextFromXML(xmlDocument, elementName){
	updateFieldText(elementName + "_VALUE", getXMLValue(xmlDocument, elementName));
}

function updateFieldValue(htmlElementID, content){	
	document.getElementById(htmlElementID).value=content;
}

function updateDateFromXML(xmlDocument, elementName){
	updateFieldValue(elementName + "_month", getXMLValue(xmlDocument, elementName + "_month"));
	updateFieldValue(elementName + "_day", getXMLValue(xmlDocument, elementName + "_day"));
	updateFieldValue(elementName + "_year", getXMLValue(xmlDocument, elementName + "_year"));
}



function drawDefaultSideTab(tab){
	if( $('#inner-navigation').css('display') == 'none' ) {	
		changeSideTab(tab);
	}else{
		showSideNavigation();
	}

	var t = document.getElementById(tab + "_Tab");
	var p = document.getElementById("sideNavigationScrollbox");
	
	var topOfSelection = t.offsetTop - p.offsetTop;
	var heightOfDiv = p.offsetHeight;
	
	if(topOfSelection > heightOfDiv){
		p.scrollTop=topOfSelection;
	}
	
	
	
}


function showPane(identifier){
	$('#contentScrollbox').find('div.pane').attr('style','display:none');
	document.getElementById(identifier).style.display="";
}

function changeSideTab(tab){
	currentScrollPosition = window.pageYOffset;
	window.scrollTo(0,0);
	
	$('#sideNavigationScrollbox ul.sideNavigation').find('li').attr('class','');
	$('#content-inside').find('h2').attr('class','hidemobile');
	
	if( $('#inner-navigation').css('display') != 'none' ) {	
		if(document.getElementById("top-tabs")){
			document.getElementById("top-tabs").style.display="none";
		}
	}
	
	document.getElementById(tab + "_Tab").setAttribute("class", "current");
	
	showPane(tab + "_Pane");
	
	document.getElementById("sideNavigationScrollbox").setAttribute("class", "sideNavigationScrollbox hidemobile");
	document.getElementById("contentScrollbox").setAttribute("class", "contentScrollbox");
	document.getElementById("form-buttons").setAttribute("class", "hidemobile");
	document.getElementById("form-buttons-top").style.display="none";
	
	
	var title = ($("div#" + tab + "_Pane").find('div.payload-heading').text());
	document.getElementById("navtitle").innerHTML = title;
	
	document.getElementById("contentScrollbox").scrollTop=0;
	
	if(document.getElementById("lastSideTab")!=null){
		if(document.getElementById("lastSideTab").value=="null"){
			document.getElementById("lastSideTab").value = "";
		}else{
			document.getElementById("lastSideTab").value = tab;
		}
	}

	

}
function showSideNavigation(){
	$('#sideNavigationScrollbox ul.sideNavigation').find('li').attr('class','');
	$('#contentScrollbox').find('div.pane').attr('style','display:none');
	$('#content-inside').find('h2').attr('class','');

	if(document.getElementById("top-tabs")){
		document.getElementById("top-tabs").style.display="";
	}
	document.getElementById("sideNavigationScrollbox").setAttribute("class", "sideNavigationScrollbox");
	document.getElementById("contentScrollbox").setAttribute("class", "contentScrollbox hidemobile");
	document.getElementById("form-buttons").setAttribute("class", "none");
	document.getElementById("form-buttons-top").style.display="";
	document.getElementById("form-buttons-top").setAttribute("class", "showmobile");
	console.log("Setting scroll position to " + currentScrollPosition);
	window.scrollTo(0,currentScrollPosition);
}


function updateScrollNavigation(){
	if( document.getElementById("sideNavigationScrollbox") !=null ){
			   
		var h = window.innerHeight - 214; // Full browser
		if(document.getElementById("top-tabs")){
			h = h - 55;
		}

		if( $('#inner-navigation').css('display') != 'none' ) {	
			h = window.innerHeight - 180; // iPhone
		    document.getElementById("sideNavigationScrollbox").style.height = "100%";
		}else if( $('#navigation').css('display') == 'none' ) {
		    document.getElementById("sideNavigationScrollbox").style.height = h + "px";
		    document.getElementById("contentScrollbox").style.height = h + "px";
		}else{
		    document.getElementById("sideNavigationScrollbox").style.height = h + "px";
		    document.getElementById("contentScrollbox").style.height = h + "px";
		}


	}
}


function handleFormSubmission(){
	if(currentFocus==null){
		console.log("Handle enter key: Returning true 1");
		return false;
	}
	
    if ($('#' + currentFocus.id).is(":visible") && $('#' + currentFocus.id).parents().filter('div.inline-edit').size()!==0) {
    	console.log("Submitting AJAX form...");
    	$('#' + currentFocus.id).parents().filter('div.inline-edit').children().filter('.insideActionButton').click();    	
		currentFocus = undefined;
    	return false;
    }else if ($('#' + currentFocus.id).is(":visible") && $('#' + currentFocus.id).parents().filter('tr.inline-edit').size()!==0) {
    	console.log("Submitting AJAX form for table row...");
    	$('#' + currentFocus.id).parents().filter('tr.inline-edit').children().children().filter('.insideActionButton').click();    	
		currentFocus = undefined;
    	return false;
	}else{
		console.log("Handle enter key: returning true 2");
		return true;
	}
}



function submitAjaxForm(action, inputDiv, completeFunction){

	var url = window.location.href;
	url = url.replace(".html", ".ajax");
    var form = "&ajaxAction=" + action + "&session-token=" + $("#session-token").val();
    
	var parameterArray = $('#' + inputDiv + ' :input');
    for(var i = 0; i<parameterArray.length;i++){
    	if(parameterArray[i].type=="checkbox" || parameterArray[i].type=="radio"){
    		if(parameterArray[i].checked){
        		form = form + "&" + parameterArray[i].name + "=" + parameterArray[i].value;
    		}
    	}else{
    		form = form + "&" + parameterArray[i].name + "=" + parameterArray[i].value;
    	}
    }
    
    $.ajax({
    	url: url,
        type: "POST",
        data: form,
        complete: completeFunction
    });
	
}

function submitAjaxPredefinedForm(action, predefiniedForm, completeFunction){
	
	var url = window.location.href;
	url = url.replace(".html", ".ajax");

	var form = predefiniedForm + "&ajaxAction=" + action + "&session-token=" + $("#session-token").val();
	
    $.ajax({
    	url: url,
        type: "POST",
        data: form,
        complete: completeFunction
    });
	
}

function mobileNavigationChange(){
	elem = document.getElementById("mobile-nav-choice");
	window.location=elem.value;
}

function subscribeToKeys(){
	  $("input, select").keypress(function (e) {
	      var k = e.keyCode || e.which;
	      if (k == 13) {
	    	  return handleFormSubmission();
	      }
	  });
}

function addListenersToFields(){
    $('input, select').focus(function() {
    	console.log("Focus was set on " + this.name);
    	if($("#" + this.id).is(":visible")){
	    	console.log("Setting focus to " + this.id);
	    	currentFocus = this;
    	}
    });
}

window.onorientationchange = updateScrollNavigation;
window.onresize = updateScrollNavigation;
var currentFocus = null;


window.addEventListener("load",function() {
  // Set a timeout...
  setTimeout(function(){
    // Hide the address bar!
    window.scrollTo(0, 1);
    updateScrollNavigation();
    
    subscribeToKeys();
    addListenersToFields();

    
    if(typeof setDefaultTab == 'function') { 
    	setDefaultTab();
    }
    if(typeof setDefaultSideTab == 'function') { 
    	setDefaultSideTab();
    }
    if(typeof setDefaultField == 'function') { 
    	setDefaultField();
    }
    if(typeof scopeFinishLoading == 'function') { 
    	scopeFinishLoading();
    }
    if(typeof pageLoadComplete == 'function') { 
    	pageLoadComplete();
    }

    
    
    
  }, 0);
});



function parseArray(xmlDocument, elementName){
	var values = new Array();
	for(var i=0;i<xmlDocument.getElementsByTagName(elementName).length;i++){
		if(xmlDocument.getElementsByTagName(elementName)[i].childNodes[0]!=null){
			values[i]= xmlDocument.getElementsByTagName(elementName)[i].childNodes[0].nodeValue;
		}else{
			values[i]="";
		}
	}
	return values;
}

function parseUsers(xml){
	
    var userElements = xml.responseXML.getElementsByTagName("user");

	var users = new Array();
	
	for(var i=0;i<userElements.length;i++){
		var user = new Object();
		if(userElements[i].getElementsByTagName("FIELD_USERNAME")[0].childNodes[0]!=null){
			user.username=userElements[i].getElementsByTagName("FIELD_USERNAME")[0].childNodes[0].nodeValue;
		}else{
			user.username="";
		}
		if(userElements[i].getElementsByTagName("FIELD_REAL_NAME")[0].childNodes[0]!=null){
			user.fullname=userElements[i].getElementsByTagName("FIELD_REAL_NAME")[0].childNodes[0].nodeValue;
		}else{
			user.fullname="";
		}
		if(userElements[i].getElementsByTagName("FIELD_EMAIL_ADDRESS")[0].childNodes[0]!=null){
			user.email=userElements[i].getElementsByTagName("FIELD_EMAIL_ADDRESS")[0].childNodes[0].nodeValue;
		}else{
			user.email="";
		}
		if(userElements[i].getElementsByTagName("FIELD_PHONE")[0].childNodes[0]!=null){
			user.phone=userElements[i].getElementsByTagName("FIELD_PHONE")[0].childNodes[0].nodeValue;
		}else{
			user.phone="";
		}
		if(userElements[i].getElementsByTagName("FIELD_BUILDING")[0].childNodes[0]!=null){
			user.building="";
		}else{
			user.building="";
		}
		if(userElements[i].getElementsByTagName("FIELD_DEPARTMENT")[0].childNodes[0]!=null){
			user.department="";
		}else{
			user.department="";
		}
		if(userElements[i].getElementsByTagName("FIELD_ROOM")[0].childNodes[0]!=null){
			user.room=userElements[i].getElementsByTagName("FIELD_ROOM")[0].childNodes[0].nodeValue;
		}else{
			user.room="";
		}
		if(userElements[i].getElementsByTagName("FIELD_POSITION")[0].childNodes[0]!=null){
			user.position=userElements[i].getElementsByTagName("FIELD_POSITION")[0].childNodes[0].nodeValue;
		}else{
			user.position="";
		}
		
		if(userElements[i].getElementsByTagName("FIELD_BUILDING_ID")[0].childNodes[0]!=null){
			user.buildingID=userElements[i].getElementsByTagName("FIELD_BUILDING_ID")[0].childNodes[0].nodeValue;
		}else{
			user.buildingID="";
		}
		if(userElements[i].getElementsByTagName("FIELD_DEPARTMENT_ID")[0].childNodes[0]!=null){
			user.departmentID=userElements[i].getElementsByTagName("FIELD_DEPARTMENT_ID")[0].childNodes[0].nodeValue;
		}else{
			user.departmentID="";
		}
		if(userElements[i].getElementsByTagName("FIELD_LDAP_SERVER_ID")[0].childNodes[0]!=null){
			user.ldapServerID=userElements[i].getElementsByTagName("FIELD_LDAP_SERVER_ID")[0].childNodes[0].nodeValue;
		}else{
			user.ldapServerID="";
		}
		if(userElements[i].getElementsByTagName("FIELD_UID")[0].childNodes[0]!=null){
			user.uid=userElements[i].getElementsByTagName("FIELD_UID")[0].childNodes[0].nodeValue;
		}else{
			user.uid="";
		}
				
		
		users[i] = user;
	}
	
	return users;
}

function clearDiv(id){
	document.getElementById(id).innerHTML='';
}

function createTable(parentDivId, tableId, headers){
	var html = '';
	html = html + '<div id="inner-list-mobile-wrapper">';
	html = html + '<table id="' + tableId + '" class="' + tableId + '">';
	html = html + '<thead>';
	html = html + '<tr>';
	
	for(var i=0;i<headers.length;i++){
		html = html + '<th>' + headers[i] + '</th>';
	}
	html = html + '</tr>';
	html = html + '<style>';
	html = html + '<!-- ';
	html = html + '@media (max-width: 600px) { ';
	for(var i=0;i<headers.length;i++){
		html = html + 'table.' + tableId + ' td:nth-of-type(' + (i+1) + '):before { content: "' + headers[i] +'";}';
	}
	html = html + '}';
	html = html + '--> ';
	html = html + '</style>';
	html = html + '</thead>';
	html = html + '<tbody>';
	html = html + '</tbody>';
	html = html + '</table>';
	
	document.getElementById(parentDivId).innerHTML=html;
	
}

function submitForm(action, fieldName, fieldValue){
    var form = document.getElementById("f");

    var actionField = document.createElement('input');
    actionField.setAttribute("type", "hidden");
    actionField.setAttribute("name", "action");
    actionField.setAttribute("value", action);
    form.appendChild(actionField);

	
    var field = document.createElement('input');
    field.setAttribute("type", "hidden");
    field.setAttribute("name", fieldName);
    field.setAttribute("value", fieldValue);
    form.appendChild(field);
    
    document.f.submit();
}

function submitFormAsIs(){
    var form = document.getElementById("f");

    var actionField = document.createElement('input');
    actionField.setAttribute("type", "hidden");
    actionField.setAttribute("name", "action");
    actionField.setAttribute("value", "nothing");
    form.appendChild(actionField);

    
    document.f.submit();
}

function addChoiceToSelect(id, value, option){
    var select = document.getElementById(id);
    select[select.length] = new Option(option, value);
}
function deleteChoicesFromSelect(id){
	   $("#" + id).find("option").remove();
}

function deleteRowsFromTable(tableID){
   $("#" + tableID).find("tr:gt(0)").remove();
}
function addRowToTable(tableId, trID, values, rowClass){
    var table = document.getElementById(tableId);
    var newTR = document.createElement('tr');
    newTR.setAttribute("id", trID);

    if(rowClass){
    	newTR.setAttribute("class", rowClass);
    }
    
    var tbody = table.getElementsByTagName("tbody")[0];
    if(tbody){
        tbody.appendChild(newTR);
    }else{
        table.appendChild(newTR);
    }
    
    for(var i=0;i<values.length;i++){
	    var newTD = document.createElement('td');
	    newTD.appendChild(document.createTextNode(values[i]));
	    newTR.appendChild(newTD);
    }
}

function addButtonToLastTD(tableName, value, javascriptAction, buttonClass){

	var button = document.createElement('input');
	if(buttonClass){
		button.setAttribute("class", buttonClass + " insideTableButton");
	}else{
		button.setAttribute("class", "insideTableButton");
	}
	button.setAttribute("type", "button");
	button.setAttribute("value", value);
	button.setAttribute("onclick", "javascript:" + javascriptAction);
	$("#" + tableName + " td:last").html(button);
}

function addButtonToTD(tableName, tdPosition, value, javascriptAction, buttonClass){

	var button = document.createElement('input');
	if(buttonClass){
		button.setAttribute("class", buttonClass + " insideTableButton");
	}else{
		button.setAttribute("class", "insideTableButton");
	}
	button.setAttribute("type", "button");
	button.setAttribute("value", value);
	button.setAttribute("onclick", "javascript:" + javascriptAction);
	$("#" + tableName + " td:nth-of-type(" + tdPosition + ")").html(button);
}

function addTextInputToTD(tableName, tdPosition, inputName, inputID, inputValue){
	var field = document.createElement('input');
	field.setAttribute("type", "text");
	field.setAttribute("name", inputName);
	field.setAttribute("id", inputID);
	field.setAttribute("value", inputValue);
	$("#" + tableName + " td:nth-of-type(" + tdPosition + ")").html(field);
    subscribeToKeys();
    addListenersToFields();
    
	field.focus();
	currentFocus = field;
	
}

function addHiddenFieldToTD(tableName, inputName, inputValue){
	var field = document.createElement('input');
	field.setAttribute("type", "hidden");
	field.setAttribute("name", inputName);
	field.setAttribute("id", inputName);
	field.setAttribute("value", inputValue);
	var td = $("#" + tableName + " td:first");
	
	td.append(field);
	
}