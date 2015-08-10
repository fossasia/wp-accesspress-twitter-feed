(function ($) {
    $(function () {
	   //All the backend js for the plugin 
       
       /*
       Settings Tabs Switching 
       */
       $('.aptf-tabs-trigger').click(function(){
           $('.aptf-tabs-trigger').removeClass('aptf-active-trigger');
           $(this).addClass('aptf-active-trigger');
          var attr_id = $(this).attr('id');
          var arr_id = attr_id.split('-');
          var id = arr_id[1];
          $('.aptf-single-board-wrapper').hide();
          $('#aptf-'+id+'-board').show();
       });
       
	   
	});
}(jQuery));