/*==============首页=======================*/
var Index = function(){
   
   var quotation = function(){
	   
	   initDatas({
           url: 'api/ajax',
           data: {
        	   'request':'getQuotation'
           },
           type: 'post'
       }, function (res) {
           if (res.status == 1) {
               var data = res.data;
               var swiperHtml = '';
               for (var i in data) {
            	   swiperHtml += '<li class="swiper-slide">'+
                       '<a href="/index/index/trades.html?symbol='+data[i].codename+'">'+
                       '<small class="usdt">'+data[i].title+'</small>'+
                       '<h4 style="color:'+(data[i].zf > 0 ? "#e94438":"#00c087")+'">'+data[i].price+'<span>'+(data[i].zf > 0 ? "+":"")+data[i].zf+'%</span></h4>'+
                   '</li>';
               }
               $("#tradeBox").html(swiperHtml);
               var swiper = new Swiper('.trade', {
                   slidesPerView: 3,
                   spaceBetween: 10,
               });
               
               var zfData = data;
               zfData.sort(function (a, b) {
            		if (a.zf > b.zf) {
            			return -1;
            		} else if (a.zf == b.zf) {
            			return 0;
            		} else {
            			return 1;
            		}
            	});
                
               var zfHtml = '';
               for (var i in zfData) {
            	   zfHtml += '<li><a href="/index/index/trades.html?symbol='+zfData[i].codename+'">'+
	                       '<b>'+zfData[i].title+'<span></span></b>'+
	                       '<b class="tc">'+zfData[i].price+'</b>'+
	                       '<button class="btn btn-default" style="background:'+(data[i].zf > 0 ? "#e94438":"#00c087")+'">'+(data[i].zf > 0 ? "+":"")+data[i].zf+'%</button>'+
	                       '</a>'+
	                   '</li>';
               }
               $("#performer").html(zfHtml);
               
               var volData = data;
               volData.sort(function (a, b) {
            		if (a.vol > b.vol) {
            			return -1;
            		} else if (a.vol == b.vol) {
            			return 0;
            		} else {
            			return 1;
            		}
            	});
                
            	var volHtml = '';
                for (var i in volData) {
                	volHtml += '<li><a href="/index/index/trades.html?symbol='+volData[i].codename+'">'+
 	                       '<b>'+zfData[i].title+'<span></span></b>'+
 	                       '<b class="tc">'+zfData[i].price+'</b>'+
 	                       '<button class="btn btn-default" style="background:#588bf7">'+data[i].vol+'</button>'+
 	                       '</a>'+
 	                   '</li>';
                }
                $("#turnover").html(volHtml);

               
           } else {
               layer_msg(res.msg);
           }
       });
	   
   };
   
   var start = function() {
	   setInterval(quotation, 1000);
   }


   return {
        init : function(){
            quotation();
            start();
        },
        
    } 
}();


/*==============行情=======================*/
var market = function(){
   
   var quotation = function(){
	   
	   initDatas({
           url: 'api/ajax',
           data: {
        	   'request':'getQuotation'
           },
           type: 'post'
       }, function (res) {
           if (res.status == 1) {
               var data = res.data;
               
               for (var i in data) {
            	   var code = data[i].code;
            	   $("#"+code+"_price").text(data[i].price);
            	   
            	   $("#"+code+"_vol").text(data[i].vol);
            	   $("#"+code+"_zf").text(data[i].zf+'%');
            	   $("#"+code+"_price").removeClass('rate_red');
            	   $("#"+code+"_price").removeClass('rate_green');
            	   if (data[i].zf < 0) {
            		   $("#"+code+"_price").css("color","#00c087");
            		   $("#"+code+"_zf").css("background","#00c087");
            		   
            	   }
            	   if (data[i].zf > 0) {
            		   $("#"+code+"_zf").css("background","#e94438");
            		   $("#"+code+"_price").css("color","#e94438");
            		   
            	   }
            	   
            	   
               }
               
           } else {
               layer_msg(res.msg);
           }
       });
	   
   };
   
   var start = function() {
	   setInterval(quotation, 1000);
   }

   return {
        init : function(){
        	quotation();
            start();
        },
        
    } 
}();



/*==============密码=======================*/
var pwd = function(){
   
   var pwd = function(){
	   
	   $("#sub_btn").on('click', function(){
		   
		   var opass = $("#opassword").val();
		   if (!opass) {
			   layer_msg(getlg('ysmmbnwk'));
	           return;
		   }
		   
	       if (opass.length < 6 || opass.length > 30) {
	           layer_msg(getlg('plength'));
	           return;
	       }
		   
		   var pass = $("#setpassword").val();
		   
		   if (!pass) {
			   layer_msg(getlg('xmmbnwk'));
	           return;
		   }
	       if (pass.length < 6 || pass.length > 30) {
	           layer_msg(getlg('plength'));
	           return;
	       }
	       
	       if ($("#setpassword").val() != $("#verifypassword").val()) {
	           layer_msg(getlg('twonot'));
	           return;
	       }
	       
		   initDatas({
	           url: 'user/ajax',
	           data: {
	        	   request:'cpwd',
	        	   opass:opass,
	        	   pass:pass
	           },
	           type: 'post'
	       }, function (res) {
	           if (res.status == 1) {
	               layer_msg(res.msg);
	               $("input").val('');
	           } else {
	               layer_msg(res.msg);
	           }
	       });
		   
	   });
	   
   };


   return {
        init : function(){
        	pwd();
        },
        
    } 
}();


/*==============密码=======================*/
var mpwd = function(){
   
   var pwd = function(){
	   
	   $("#sub_btn").on('click', function(){
		   
		   var opass = $("#opassword").val();
		   if (!opass) {
			   layer_msg(getlg('ysmmbnwk'));
	           return;
		   }
		   
	       if (opass.length < 6 || opass.length > 30) {
	           layer_msg(getlg('plength'));
	           return;
	       }
		   
		   var pass = $("#setpassword").val();
		   
		   if (!pass) {
			   layer_msg(getlg('xmmbnwk'));
	           return;
		   }
	       if (pass.length < 6 || pass.length > 30) {
	           layer_msg(getlg('plength'));
	           return;
	       }
	       
	       if ($("#setpassword").val() != $("#verifypassword").val()) {
	           layer_msg(getlg('twonot'));
	           return;
	       }
	       
		   initDatas({
	           url: 'user/ajax',
	           data: {
	        	   request:'cmpwd',
	        	   opass:opass,
	        	   pass:pass
	           },
	           type: 'post'
	       }, function (res) {
	           if (res.status == 1) {
	               layer_msg(res.msg);
	               $("input").val('');
	           } else {
	               layer_msg(res.msg);
	           }
	       });
		   
	   });
	   
   };


   return {
        init : function(){
        	pwd();
        },
        
    } 
}();
