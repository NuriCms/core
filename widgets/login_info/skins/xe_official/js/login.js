jQuery(function($){
	// keep signed
	var keep_msg = $('.keep_msg');
	keep_msg.hide();
	$('#keep_signed').change(function(){
		if($(this).is(':checked')){
			keep_msg.slideDown(200);
		} else {
			keep_msg.slideUp(200);
		}
	});
});
