(function($){
	$.fn.SiteSwitcher=function(){
	    var a,b,c;
	    a=$(window).width();
	    b=$(window).height();
	    c=$(document);
	    $(this).click(function(){
	        $("#single_sign_on-panel").show();
	    });
	    c.click(function(e){
	    	if(!$(e.target || e.srcElement).hasClass('singlesignon-holder')) {
	        	$("#single_sign_on-panel").hide();
	        }
	    });
	};
})(jQuery);
$(document).ready(function(){
    $(".singlesignon-holder").SiteSwitcher()
});