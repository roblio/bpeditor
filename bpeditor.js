function openFilesPanel() {
	closePreviewPanel();
	document.getElementById("filesPanel").style.width = "350px";
	document.getElementById("main").style.marginLeft = "350px";
	document.getElementById("filesHamburger").innerHTML = "<b>&times;</b>";
	document.getElementById("filesHamburger").onclick  = function() { closeFilesPanel(); };
}
function closeFilesPanel() {
	document.getElementById("filesPanel").style.width = "0";
	document.getElementById("main").style.marginLeft = "0";
	document.getElementById("filesHamburger").innerHTML = "&#9776;";
	document.getElementById("filesHamburger").onclick = function() { openFilesPanel(); };
}

function openPreviewPanel() {
	closeFilesPanel();
	widthFrame = '994px';
	document.getElementById("path").style.display = "none"; 
	//document.getElementById("reloadPreviewButton").style.display = "inline-block";
	document.getElementById("previewPanel").style.width = "50vw";
	document.getElementById("previewPanel").style.height = "100vh";
	document.getElementById("previewPanel").style.margin = "auto";
	document.getElementById("previewBar").style.display = "flex";
	document.getElementById("main").style.marginRight = "50vw";
	document.getElementById("previewHamburger").innerHTML = "<b>&times;</b>";
	document.getElementById("previewHamburger").onclick  = function() { closePreviewPanel(); };
	document.getElementById("previewDiv").innerHTML = "<iframe id='previewFrame' name='previewFrame' frameborder='0' scrolling='no' " +
		                                                      "src='" + document.getElementById('path').textContent + "' width='" + widthFrame + "' height='100%' " +
		                                                      "style='height:100vh;width:" + widthFrame + ";overflow:hidden;margin:auto;'></iframe>";
	document.getElementById("previewPath").innerHTML = document.getElementById("previewFrame").src;
	editor.refresh();
}
function closePreviewPanel() {
	document.getElementById("previewPath").innerHTML = "";
	document.getElementById("previewDiv").innerHTML = "";
    document.getElementById("previewPanel").style.width = "0";
    document.getElementById("main").style.marginRight = "0";
    document.getElementById("previewHamburger").innerHTML = "&#9776;";
    document.getElementById("previewHamburger").onclick = function() { openPreviewPanel(); };
    //document.getElementById("reloadPreviewButton").style.display = "none";
    document.getElementById("path").style.display = "inline-block"; 
}
function reloadPreviewFrame(){
	document.getElementById("previewFrame").src += "";
}
function resizePreviewFrame(widthFrame){ 
	document.getElementById("previewPath").innerHTML = document.getElementById("previewFrame").src;
	document.getElementById("previewDiv").innerHTML = "<iframe id='previewFrame' name='previewFrame' frameborder='0' scrolling='no' " +
		                                                      "src='" + document.getElementById('path').textContent + "' width='" + widthFrame + "' height='100%' " +
		                                                      "style='height:100vh;width:" + widthFrame + ";overflow:hidden;margin:auto;'></iframe>";
}

function openVideoPanel() {
	closeFilesPanel();
	widthFrame = '562px';
	//document.getElementById("path").style.display = "none"; 
	document.getElementById("previewBar").style.display = "none";
	//document.getElementById("reloadPreviewButton").style.display = "inline-block";
	document.getElementById("previewPanel").style.width = "562px";
	document.getElementById("previewPanel").style.height = "100vh";
	//document.getElementById("previewPanel").style.margin = "auto";
	document.getElementById("main").style.marginRight = "562px";
	document.getElementById("previewHamburger").innerHTML = "<b>&times;</b>";
	document.getElementById("previewHamburger").onclick  = function() { closePreviewPanel(); };
	document.getElementById("previewDiv").innerHTML = "<iframe width='560' height='315' src='https://www.youtube.com/embed/ewZ_YWbIWXI' title='YouTube video player' frameborder='0' allow=''clipboard-write; encrypted-media; 'picture-in-picture; web-share' allowfullscreen></iframe>" + 
"<br>" +
"<iframe width='560' height='315' src='https://www.youtube.com/embed/QqmCs2UTS8s' title='YouTube video player' frameborder='0' allow=''clipboard-write; encrypted-media; 'picture-in-picture; web-share' allowfullscreen></iframe>" + 
"<iframe width='560' height='315' src='https://www.youtube.com/embed/videoseries?list=PL4cUxeGkcC9gksOX3Kd9KPo-O68ncT05o' title='YouTube video player' frameborder='0' allow=''clipboard-write; encrypted-media; 'picture-in-picture; web-share' allowfullscreen></iframe>";
	//document.getElementById("previewPath").innerHTML = document.getElementById("previewFrame").src;
	editor.refresh();
}

//	setInterval(function(){ alert("Hi! You've been using me for 20 minutes, how about taking a break for the eyes?\n\n" + 
//									"20–20–20 rule says that for every 20 minutes spent looking at a screen,\n" + 
//									"a person should look at something 20 feet (6 meters) away for 20 seconds\n\n"); }, 1200000);

$('#font-slider').on('input', function(){
	var v = $(this).val();
	$('.CodeMirror').css('font-size',v + 'px');
	editor.refresh();
});

$('.dropdown-menu a.dropdown-toggle').on('mouseover', function(e) {
	if (!$(this).next().hasClass('show')) {
		$(this).parents('.dropdown-menu').first().find('.show').removeClass('show');
	}
	var $subMenu = $(this).next('.dropdown-menu');
	$subMenu.toggleClass('show');
	$(this).parents('li.nav-item.dropdown.show').on('hidden.bs.dropdown', function(e) {
		$('.dropdown-submenu .show').removeClass('show');
	});
	return false;
});

