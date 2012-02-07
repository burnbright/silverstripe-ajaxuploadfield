jQuery(function($){
	
	var allowedextensions = ['jpg','png','jpeg','gif'];
	
	$("div.ajaxfileupload").each(function(){
		new qq.FileUploader({
		    element: $(this)[0],
		    action: DES.designerurl+'uploadimage', //TODO: get this from the form action, and make sure action is passed?
		    //allowedExtensions: allowedextensions,
		    debug: true
		});
	});
	
});

