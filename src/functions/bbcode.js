tags = new Array();

function getarraysize(thearray) {
	for (i = 0; i < thearray.length; i++) {
		if ((thearray[i] == "undefined") || (thearray[i] == "") || (thearray[i] == null))
			return i;
		}
	return thearray.length;
}

function arraypush(thearray,value) {
	thearraysize = getarraysize(thearray);
	thearray[thearraysize] = value;
}

function arraypop(thearray) {
	thearraysize = getarraysize(thearray);
	retval = thearray[thearraysize - 1];
	delete thearray[thearraysize - 1];
	return retval;
}

// *******************************************************

function setmode(modevalue) {
	document.cookie = "bbcodemode="+modevalue+"; path=/; expires=Wed, 1 Jan 2020 00:00:00 GMT;";
}

function normalmode(theform) {
	if (theform.mode[0].checked) return true;
	else return false;
}

function stat(thevalue) {
	document.bbform.status.value = eval(thevalue+"_text");
}

// *******************************************************

function closetag(theform) {
	if (normalmode(theform))
		stat('enhanced_only');
	else
		if (tags[0]) {
			theform.message.value += "[/"+ arraypop(tags) +"]";
			}
		else {
			stat('no_tags');
			}
	theform.message.focus();
}

function closeall(theform) {
	if (normalmode(theform))
		stat('enhanced_only');
	else {
		if (tags[0]) {
			while (tags[0]) {
				theform.message.value += "[/"+ arraypop(tags) +"]";
				}
			theform.message.value += " ";
			}
		else {
			stat('no_tags');
			}
		}
	theform.message.focus();
}

// *******************************************************
var text = "";
AddTxt = "";
function getActiveText(selectedtext) { 
	text = (document.all) ? document.selection.createRange().text : document.getSelection();
	if (selectedtext.createTextRange) {
    	selectedtext.caretPos = document.selection.createRange().duplicate();
	}
	return true;
}

function AddText(NewCode,theform) {
	if (theform.message.createTextRange && theform.message.caretPos) {
		var caretPos = theform.message.caretPos;
		caretPos.text = caretPos.text.charAt(caretPos.text.length - 1) == ' ' ? NewCode + ' ' : NewCode;
	} else {
		theform.message.value+=NewCode
	}
	setfocus(theform);
	AddTxt = "";
}


function setfocus(theform) {
theform.message.focus();
}

function bbcode(theform,bbcode,prompttext) {
	if ((normalmode(theform)) || (bbcode=="IMG")) {
		if (text) { var dtext=text; } else { var dtext=prompttext; }
		inserttext = prompt(tag_prompt+"\n["+bbcode+"]xxx[/"+bbcode+"]",dtext);
		if ((inserttext != null) && (inserttext != ""))
			AddTxt = "["+bbcode+"]"+inserttext+"[/"+bbcode+"] ";
			AddText(AddTxt,theform);
			
		}
	else {
		donotinsert = false;
		for (i = 0; i < tags.length; i++) {
			if (tags[i] == bbcode)
				donotinsert = true;
			}
		if (donotinsert)
			stat("already_open");
		else {
			theform.message.value += "["+bbcode+"]";
			arraypush(tags,bbcode);
			}
		}
	theform.message.focus();
}

// *******************************************************

function fontformat(theform,thevalue,thetype) {
	if (normalmode(theform)) {
		if (thevalue != 0) {
			if (text) { var dtext=text; } else { var dtext=""; }
			inserttext = prompt(font_formatter_prompt+" "+thetype,dtext);
			if ((inserttext != null) && (inserttext != ""))
				AddTxt = "["+thetype+"="+thevalue+"]"+inserttext+"[/"+thetype+"] ";
				AddText(AddTxt,theform);
				
			}
		}
	else {
		theform.message.value += "["+thetype+"="+thevalue+"]";
		arraypush(tags,thetype);
		}
	theform.sizeselect.selectedIndex = 0;
	theform.fontselect.selectedIndex = 0;
	theform.colorselect.selectedIndex = 0;
	theform.message.focus();
}

// *******************************************************

function namedlink(theform,thetype) {
	if (text) { var dtext=text; } else { var dtext=""; }
	linktext = prompt(link_text_prompt,dtext);
		var prompttext;
		if (thetype == "URL") {
			prompt_text = link_url_prompt;
			prompt_contents = "http://";
			}
		else {
			prompt_text = link_email_prompt;
			prompt_contents = "";
			}
	linkurl = prompt(prompt_text,prompt_contents);
	if ((linkurl != null) && (linkurl != "")) {
		if ((linktext != null) && (linktext != "")) {
			AddTxt = "["+thetype+"="+linkurl+"]"+linktext+"[/"+thetype+"] ";
			AddText(AddTxt,theform);
			
			}
		else{
			AddTxt = "["+thetype+"]"+linkurl+"[/"+thetype+"] ";
			AddText(AddTxt,theform);
			
		}
	}
}

// *******************************************************

function dolist(theform) {
	listtype = prompt(list_type_prompt, "");
	if ((listtype == "a") || (listtype == "1")) {
		thelist = "[list="+listtype+"]\n";
		listend = "[/list="+listtype+"] ";
		}
	else {
		thelist = "[list]\n";
		listend = "[/list] ";
		}
	listentry = "initial";
	while ((listentry != "") && (listentry != null)) {
		listentry = prompt(list_item_prompt, "");
		if ((listentry != "") && (listentry != null))
			thelist = thelist+"[*]"+listentry+"\n";
		}
	AddTxt = thelist+listend;
	AddText(AddTxt,theform);

}

// *******************************************************

function smilie(thesmilie) {
	AddSmile = " "+thesmilie+" ";
	theform = bbform;
	AddText(AddSmile,theform);
}

function opensmiliewindow(x,y) {
		w = window.open("moresmilies.php", "smilies", "toolbar=no,scrollbars=yes,resizable=yes,width="+x+",height="+y);
		w.focus()
}
function openpicturewindow(id) {
		w = window.open("showpicture.php?picture="+id, "Vollbild", "toolbar=no,scrollbars=yes,resizable=yes");
//		w.focus()
}

// *******************************************************

function gethelp() {
	alert(HELPTEXT);
}