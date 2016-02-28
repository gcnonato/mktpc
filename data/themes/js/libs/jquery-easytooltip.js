/*
 * Tooltip script 
 * powered by jQuery (http://www.jquery.com)
 * 
 * written by Alen Grakalic (http://cssglobe.com)
 * 
 * for more info visit http://cssglobe.com/post/1695/easiest-tooltip-and-image-preview-using-jquery
 *
 */
 
this.tooltip=function(){xOffset=-25;yOffset=-40;$(".tooltip").hover(function(e){this.t=this.title;this.title="";$("body").append("<p id='tooltip'>"+this.t+"</p>");$("#tooltip").css("top",(e.pageY-xOffset)+"px").css("left",(e.pageX+yOffset)+"px").fadeIn("fast")},function(){this.title=this.t;$("#tooltip").remove()});$(".tooltip").mousemove(function(e){$("#tooltip").css("top",(e.pageY-xOffset)+"px").css("left",(e.pageX+yOffset)+"px")})};