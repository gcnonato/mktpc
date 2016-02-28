head(function() {
	head.js("data/themes/js/libs/head.css.js", function() {
		head.css("data/themes/css/main.css");
		head.css("data/themes/js/libs/stars/jRating.jquery.css");
		head.css("data/themes/js/libs/cle/jquery.cleditor.css");
		head.css("data/themes/js/libs/auto/autocomplete.css");
		head.css("data/themes/js/libs/bbc/bbedit.css");
	});
	head.js(
		{flip:"data/themes/js/libs/jquery.flip.js"},
		{slides:"data/themes/js/libs/slides.jquery.js"},
		{clear: "data/themes/js/libs/jquery-clearinginput.js"},
		{slct:"data/themes/js/libs/jquery.selectBox.js"},
		{tooltip:"data/themes/js/libs/jquery-easytooltip.js"},
		{pass:"data/themes/js/libs/password_strength_plugin.js"},
		{rating:"data/themes/js/libs/stars/jRating.jquery.js"},
		{form:"data/themes/js/libs/jquery.form.js"},
		{auto:"data/themes/js/libs/auto/jquery.autocomplete.min.js"},
		{cle:"data/themes/js/libs/cle/jquery.cleditor.min.js"},
		{easing:"data/themes/js/libs/jquery.easing.1.3.min.js"}, function() {
			head.ready("flip", function(){
				$('#content .items .row .box .flip').bind("click", function() {
					var elem = $(this);
					if(elem.data('flipped')) {
						elem.revertFlip();
						elem.data('flipped', false)
					} else {
						elem.flip({ 
							direction:'lr',
							speed: 250,
							onBefore: function(){
								elem.html(elem.siblings('#content .items .row .box .data').html());
							}
						});
						elem.data('flipped',true);
					}
				});
			});
			
			head.ready("clear", function(){
				$('input[type="text"]').clearOnFocus();
			});
			
			head.ready("slct", function(){
				$("select").not("#category_multi").selectBox();
			});
			
			head.ready("slides", function(){
				$('#slides').slides({
					generateNextPrev: true,
					generatePagination: false
				});
			});
			
			head.ready("form", function() {
				$('#thumb_form').submit(function(e){
					e.preventDefault();
					
					$(this).ajaxSubmit({
						type: 'post',
						url: $(this).attr('action'),
						dataType: 'json',
						beforeSubmit: function() {
							$('#fl').attr('disabled', 'disabled');
							
							$('#bar').css('background','rgba(216, 247, 185, 0.4)');
							$('#progress').show();
							
					        var percentVal = '0%';
					        
					        $('#bar').width(percentVal);
					        $('#percent').html(percentVal);
						},
						uploadProgress: function(event, position, total, percentComplete) {
					        var percentVal = percentComplete + '%';
					        $('#bar').width(percentVal);
					        $('#percent').html(percentVal);
					    },
						success:  function(data) {
							var percentVal = '100%';
							$('#bar').width(percentVal);
							
							if(data.logout) {
								window.location = data.logout;
							}
							
							if(data.msg_success) {
								$('#percent').html(percentVal);
								
								$('#thumb_image').attr('src', data.avatar);
								$('#thumb_image_b').attr('src', data.avatar);
								$('#msg').addClass('box-success').text(data.msg_success);
								
								setTimeout(function() {
									$('#progress').animate({
										height: 'hide'
									}, 400);
								}, 3000);
							}
							
							if(data.msg_error) {
								$('#bar').css('background','rgba(227, 6, 19, 0.2)');
							  	$('#percent').html(data.msg_error);
							  	
								$('#thumb_image_b').attr('src', $('#thumb_image').attr('src'));
								$('#msg').addClass('box-error').text(data.msg_error);
							}
							
							$('#fl').removeAttr('disabled', '');
						}
					});
					
					return false;
				});
				
				$('#form_zip').submit(function(e) {
					e.preventDefault();
					
					$(this).ajaxSubmit({
						type: 'post',
						url: $(this).attr('action'),
						dataType: 'json',
						beforeSend: function() {
							$('#bar').css('background','rgba(216, 247, 185, 0.4)');
							$('#progress').show();
							
					        var percentVal = '0%';
					        
					        $('#bar').width(percentVal);
					        $('#percent').html(percentVal);
					    },
					    uploadProgress: function(event, position, total, percentComplete) {
					        var percentVal = percentComplete + '%';
					        $('#bar').width(percentVal);
					        $('#percent').html(percentVal);
					    },
						success: function(data) {
							var percentVal = '100%';
							$('#bar').width(percentVal);
							
							if(data.logout) {
								window.location = data.logout;
							}
								
							if(data.msg_success) {
								$('#percent').html(percentVal);
								var select_files = $('#theme_preview');
								var select_zip = '';
								
								$.each(data.file, function(i,f) {
									if(f.name != '') {
										$('#theme_preview').append($("<option/>").val(f.filename).text(f.name)).selectBox('refresh');
									}
								});
								
								if(data.file[0].zip_filename != '') {
									$('#theme_preview_zip').append($("<option/>").val(data.file[0].zip_filename).text(data.file[0].zip_name)).selectBox('refresh');
									$('#main_file').append($("<option/>").val(data.file[0].zip_filename).text(data.file[0].zip_name)).selectBox('refresh');
								}
								
								setTimeout(function() {
									$('#progress').animate({
										height: 'hide'
									}, 400);
								}, 3000);
							}
							
							if(data.msg_error){
								$('#bar').css('background','rgba(227, 6, 19, 0.2)');
							  	$('#percent').html(data.msg_error);
							}
						}
					});
				});
			});
			
			head.ready("cle", function(){
				$("#description_editor").cleditor({
					width: 330,
					height: 200,
					controls: "bold italic underline strikethrough | subscript superscript " +
							"style | bullets numbering | image link unlink | removeformat",
					 styles: [["Paragraph", "<p>"], ["Header 1", "<h1>"], ["Header 2", "<h2>"], ["Header 3", "<h3>"]],
					 bodyStyle: "margin:2px; font:12px Verdana, Arial; color: #3c3c3b; cursor:text; color: "
				});
				
				$("#answer_editor").cleditor({
					width: 330,
					height: 200,
					controls: "bold italic underline strikethrough | subscript superscript " +
							"style | bullets numbering | image link unlink | removeformat",
					 styles: [["Paragraph", "<p>"]],
					 bodyStyle: "margin:2px; font:12px Verdana, Arial; color: #3c3c3b; cursor:text; color: "
				});
				
				$(".faq_reply").each(function(i) {
					$('#faq_' + i).cleditor({
						width: 330,
						height: 200,
						controls: "bold italic underline strikethrough | subscript superscript " +
								"style | bullets numbering | image link unlink | removeformat",
						 styles: [["Paragraph", "<p>"]],
						 bodyStyle: "margin:2px; font:12px Verdana, Arial; color: #3c3c3b; cursor:text; color: "
					});
				});
			});
			
			head.ready("auto", function() {
				
				function formatItem(row) {
					return row[0];
				}
				function formatResult(row) {
					return row[0].replace(/(<.+?>)/gi, '');
				}
				
				$('#tags').autocomplete(window.autoPath, {
					width: 400,
					multiple: true,
					matchContains: true, 
					formatItem: formatItem,
					formatResult: formatResult
				});
			});
			
			head.ready("rating", function() {
				$(".basic").jRating({
			        step: true,
			        rateMax: 5,
			        bigStarsPath: 'data/themes/js/libs/stars/icons/stars.png',
			        phpPath: $('#basic_href').attr('href'),
			        rateInfosX: 5,
			        onSuccess : function(dv, rt, data) {
			        	var stars = $('#5_' + data.id);
			         	stars.parent().find('.stars').html(data.message).prev('.total').html(data.votes);
			         	stars.remove();
					}
			   	});
			});
			
	//		head.ready("bbc", function() {
			$("#comment").bbedit({
				highlight: true,
				enableSmileybar: false,
				tags: 'b,i,u,s,url,img,code,quote'
			});
			
			$(".cmmnt_reply").each(function(i) {
				$('#rply_' + i).bbedit({
					highlight: true,
					enableSmileybar: false,
					tags: 'b,i,u,s,url,img,code,quote'
				});
			});
	//		});
			
			head.ready("tooltip", function() {
				tooltip();
			});
			
			var zIndexNumber = 10000;
			$('div').each(function() {
				$(this).css('zIndex', zIndexNumber);
				zIndexNumber -= 10;
			});
			//drugi functii
			$('li').hover(function() {
				if($(this).hasClass('ahover') || this.id == 'lgn' || this.id == 'fm_collection' || $(this).hasClass('cmmnt') || $(this).hasClass('cmmnt_btns')) {
					return false;
				}
				$(this).find(".dropdown").fadeIn(125);
				$(this).find(".arrow").parent().addClass('ahover');
				$(this).find(".arrow").addClass('arrowhover');
			}, function() {
				if($(this).hasClass('ahover') || this.id == 'lgn' || this.id == 'fm_collection' || $(this).hasClass('cmmnt') || $(this).hasClass('cmmnt_btns')) {
					return false;
				}
				$(this).find(".dropdown").fadeOut(125);
				$(this).find(".arrow").parent().removeClass('ahover');
				$(this).find(".arrow").removeClass('arrowhover');
			});
			
			var keyEnterPressed = function(new_page) {
					var url = window.location.href;
					var url_parts = url.split('?');
					
					var vars = url_parts[0].split('/');
					if($.browser.msie) {
						vars = $.grep(vars, function(n,i){ return n != '' && i > 4; });
					} else {
						vars = $.grep(vars, function(n,i){ return n != '' && i > 3; });
					}
					
					for (var i = 0; i < vars.length; i++) {
						if (vars[i] == 'page') {
							vars[i + 1] = new_page;
							break;
						}
					}
					
					if(i == vars.length) {
						vars[i++] = 'page';
						vars[i] = new_page;
					}
					
					url = vars.join('/');
					
					if(url_parts[1] != undefined) {
						url += '?' + url_parts[1]; 
					}
					
					window.location.href = url;
					
					return false;
			}
			
			$('.pagination').find('input').bind('keypress', function(event) {
				if (event.which == 13) {
					keyEnterPressed(this.value);
				}
			});
			
			$('.pagination_bottom').find('input').bind('keypress', function(event) {
				if (event.which == 13) {
					keyEnterPressed(this.value);
				}
			});
			
			$('#select-1').change(function(){
				window.location.href = this.value;
				return false;
			});
			
			$('a[class="tooltip"]').click(function(){ return false; });
			
			$('#passtxt').focus(function(){
				$(this).hide();
				$('#password').show().focus();
			});
			
			$('#password').blur(function(){
				if($(this).val() == '') {
					$(this).hide();
					$('#passtxt').show();
				}
			});
				
			var cnt = 0;
			$('#login_btn').click(function() {
				if(cnt == 0) {
					$('#lgn_form').toggle();
					$(this).addClass('ahover').parent().addClass('ahover');
					$(this).find('span').addClass('arrowhover');
					cnt++;
				} else {
					$('#lgn_form').toggle();
					$(this).removeClass('ahover').parent().removeClass('ahover');
					$(this).find('span').removeClass('arrowhover');
					cnt--;
				}
				
				return false;
			});
			
			var c = 0;
			$('#btn_collection').click(function() {
				if(c == 0) {
					$('#collection_form').toggle();
					$(this).addClass('ahover');
					$(this).find('span').addClass('arrowhover');
					c++;
				} else {
					$('#collection_form').toggle();
					$(this).removeClass('ahover');
					$(this).find('span').removeClass('arrowhover');
					c--;	
				}
				
				return false;
			});
			
			$('#badges').find('img').hover(function(){
				$('#badges_name').text($(this).attr('alt'));
			});
			
			$('#fl').change(function(){ $('#thumb_form').submit(); return false;});
			
			$('input:radio[name="amount"]').click(function(){ $('#pay_via').show(); });
			
			$('.expand').click(function(e){
				e.preventDefault();
				$(this).toggleClass("active");
				var className = $(this).attr('rel');
				$("." + className).toggle();
			});
			
			$('#gerate_referral_link').click(function() {
				
				var a = $('#url').val();
				var re = new RegExp("([?|&])ref=.*?(&|$)", "i"), separator = a.indexOf('?') !== -1 ? "&" : "?";
			
				if (a.match(re)) {
					var url = a.replace(re, '$1ref=' + $('#username').val() + '$2');
				} else {
					var url = a + separator + 'ref=' + $('#username').val();
				}
		  
				$('#referral').val(url);
				
				return false;
			});
			
			$('#zip').change(function() {
				var clone = $(this).clone(true);
				$(this).hide();
				clone.insertAfter($(this));
				$(this).appendTo('#form_zip');
				$('#form_zip').submit(); 
			});
			
			$('#select-2').change(function() {
				var url = $(this).val(); 
				$.ajax({
					type: 'GET',
					cache: false,
					url: url,
					success: function(data) {
						$('.scroll').html(data).scrollTop(0);
						var url_seg = url.split('/');
						$('input[name="category_id"]').val(url_seg.pop());
					}
				});
			});
			
			$('#buy_now_lnk').click(function() {
				$('#item_pay').animate({
					height: 'show'
				}, 400, function() {
					$('#licence').val('personal');
				});
				
				return false;
			});
			
			$('#buy_now_lnk_full').click(function() {
				$('#item_pay').animate({
					height: 'show'
				}, 400, function() {
					$('#licence').val('extended');
				});
				
				return false;
			});
			
			$('#close_payment').click(function() {
				$('#item_pay').animate({
					height: 'hide'
				}, 400);
				
				return false;
			});
			
			$('#pay_member').click(function() {
				if(confirm($(this).attr('rel'))) {
					var url = $(this).attr('href');
					$('#pay_form').attr('action', url).submit();
				}
				return false;
			});
			
			$('#pay_deposit').click(function() {
				if(confirm($(this).attr('rel'))) {
					var url = $(this).attr('href');
					$('#pay_form').attr('action', url).submit();
				}
				return false;
			});
			
			$('#pay_payment').click(function() {
				var url = $(this).attr('href');
				$('#pay_form').attr('action', url).submit();
				return false;
			});
			
			$('.rply').click(function() {
				if($(this).hasClass('ahover')) {
					$(this).removeClass('ahover').next().toggle();
				} else {
					$(this).addClass('ahover').next().toggle();
				}
			 	
			 	return false;
			});
			
			$.each($('a[rel="external"]'), function(i,n) {
				$(this).attr('target', '_blank');
			});
		 	
		 	var list = $('#accordion').children();
			var first_li = list.eq(0);
			var second_li = list.eq(1);
			
			$('#full').click(function() {
				first_li.slideUp(400);
				second_li.slideDown(400);
				return false;
			});
			
			$('#personal').click(function() {
				second_li.slideUp(400);
				first_li.slideDown(400);
				return false;
			});
		 
		 	$('.avt_div').hover(
		 		function() {
		 			$(this).find('.avatar_tooltip').animate({ height: 'show' }, 200);
		 		}, function() {
		 			$(this).find('.avatar_tooltip').animate({ height: 'hide' }, 200);
		 		}
		 	);
		 	
		 	$('.emot').click(function() {
		 		var code = $(this).attr('title');
		 		var tarea = $(this).parent().prev().prev();
		 		
		 		tarea.val(tarea.val() + code);
		 	});
		});
});
