//active
function menuactice(sub_num,sub_num_son){
  if(!sub_num){
    var sub_num = 0;
  }
  if(!sub_num_son){
    var sub_num_son = 0;
  }
  var childrens = $('.sidebar-menu').children();
  $(childrens[sub_num]).attr('class','active');
  var sub_childrens = $(childrens[sub_num]).children();
  var sub_childrens = $(sub_childrens[1]).children();
  $(sub_childrens[sub_num_son]).attr('class','active');
}

/**
 * WP ajax post
 * @author lukui  2017-02-15
 * @param  {[type]} formurl post url
 * @param  {[type]} data    post data
 * @param  {[type]} locurl  成功后跳转的url
 */
function WPpost(formurl,data,locurl){
    $.ajaxSetup({ async: false,  dataType: 'json' });
    $.post(formurl,data,function(data){
      if (data.type == 1) {

        layer.msg(data.data, {icon: 1,time: 1000},function(){
          if(locurl){
            window.location.href=locurl;
          }else{
            return true;
          }
          
        }); 

      }else if(data.type == -1){
        layer.msg(data.data, {icon: 2}); 
      }
    });
    
  return false;
}

function WPpost2(formurl,data,locurl,data2){
    $.ajaxSetup({ async: false,  dataType: 'json' });
    $.post(formurl,data,function(data){
      if (data.type == 1) {
        alert(data2);
        
        if(locurl){
          window.location.href=locurl;
        }else{
          return true;
        }
      }else if(data.type == -1){
        layer.msg(data.data, {icon: 2}); 
      }
    });
    
  return false;
}

/**
 * WP ajax get
 * @author lukui  2017-02-16
 * @param  {[type]} geturl [description]
 * @param  {[type]} locurl [description]
 */
function WPget(geturl,locurl){
  $.ajaxSetup({ async: false,  dataType: 'json' });

  $.get(geturl,function(data){
      if (data.type == 1) {
          layer.msg(data.data, {icon: 1,time: 1000},function(){
            if (locurl) {
              window.location.href=locurl;
            }else{
              return data;
            }
            
          }); 

        }else if(data.type == -1){
          layer.msg(data.data, {icon: 2}); 
        }
    });
}

function setCookie(cname,cvalue,exdays){
  var d = new Date();
  d.setTime(d.getTime()+(exdays*24*60*60*1000));
  var expires = "expires="+d.toGMTString();
  document.cookie = cname + "=" + cvalue + "; " + expires;
}

function getCookie(cname){
  var name = cname + "=";
  var ca = document.cookie.split(';');
  for(var i=0; i<ca.length; i++) 
  {
    var c = ca[i].trim();
    if (c.indexOf(name)==0) return c.substring(name.length,c.length);
  }
  return "";
}

//no blank
(function() {
  var a = localStorage.getItem("app_home_uri");
  var b = localStorage.getItem("app_stored_uri");
  var c = parseInt(localStorage.getItem("app_stored_timestamp"));
  var e = new Date();
  var c = new Date(c);
  var f = parseInt((e.getTime() - c.getTime()) / 1000);
  if (!b || f > 600) {
    var g = location.href;
    var h = Date.parse(new Date());
    if (!a) {
      localStorage.setItem("app_home_uri", g)
    }
    localStorage.setItem("app_stored_uri", g);
    localStorage.setItem("app_stored_timestamp", h)
  } else if (location.href == a && b && b != a) {
    location.href = b
  }
  if ("standalone" in window.navigator && window.navigator.standalone) {
    var d, l = false;
    document.addEventListener("click", function(i) {
      d = i.target;
      while (d.nodeName !== "A" && d.nodeName !== "HTML") d = d.parentNode;
      if ("href" in d && d.href.indexOf("http") !== -1 && (d.href.indexOf(document.location.host) !== -1 || l)) {
        i.preventDefault();
        document.location.href = d.href;
        localStorage.setItem("app_stored_uri", d.href);
        setCookie("app_stored_uri", d.href, 30);
      }
    }, false)
  }
})();

if ("standalone" in window.navigator && window.navigator.standalone) {
  document.writeln("<div style=\"display:block;position:fixed;bottom:0;width:100%;height:58px;color:#474747;padding-top:10px;border-top:1px solid #eee;background: linear-gradient(#fffcfc, #e4e4e5);\"><div style=\"width:33%;float:left;cursor:pointer;text-align:center;\"onclick=\"window.history.go(-1)\"><img style=\"height:24px;width:24px;\"src=\"https://ae01.alicdn.com/kf/HTB1ilvdN4naK1RjSZFt762C2VXaI.png\"/><div style=\"height:30px;width:100%;line-height:30px;\">后退</div></div><div style=\"width:33%;float:left;cursor:pointer;text-align:center;\"onclick=\"window.location.reload()\"><img style=\"height:24px;width:24px;\"src=\"https://ae01.alicdn.com/kf/HTB1dKncN4jaK1RjSZKz760VwXXa4.png\"/><div style=\"height:30px;width:100%;line-height:30px;\">刷新</div></div><div style=\"width:33%;float:left;cursor:pointer;text-align:center;\"onclick=\"window.history.go(1)\"><img style=\"height:24px;width:24px;\"src=\"https://ae01.alicdn.com/kf/HTB1PFeTN7PoK1RjSZKb7601IXXa9.png\"/><div style=\"height:30px;width:100%;line-height:30px;\">前进</div></div></div>");
}

  
///////////////////////////////////////////////////////////////////////////////
function sinfo(str){
  console.info(str);
}

//通用
function isCommon(reg, val){
  var re  = new RegExp(reg);
  if (val.search(re) != -1){
    return true;
  } else {
    return false;
  }
}

//电话
function isPhone(val){
  var reg = "^[0-9]{11}$";
  return isCommon(reg, val);
}

//姓名
function isName(val){
  var reg = "^[a-zA-Z\u4e00-\u9fa5]+$";
  return isCommon(reg, val);
}

//isaddr
function isAddr(val){
  var reg = "^[0-9a-zA-Z\u4e00-\u9fa5]+$";
  return isCommon(reg, val);
}

//idcard
function isIdcard(val){
  //var reg = /^[1-9]\d{5}(19|20)\d{2}(((0[1]|0[3-9]|1[012])(0[1-9]|[1-2][0-9]|3[01]))|((02)(0[1-9]|1[0-9]|2[0-9])))\d{3}(\d|X|x)$/;
  var reg = /^[1-9]\d{7}((0\d)|(1[0-2]))(([0|1|2]\d)|3[0-1])\d{3}$|^[1-9]\d{5}[1-9]\d{3}((0\d)|(1[0-2]))(([0|1|2]\d)|3[0-1])\d{3}([0-9]|[Xx])$/;
  return isCommon(reg, val);
}

//pass
function isPass(val){
  var reg = /^(?![0-9]+$)(?![a-zA-Z]+$)[0-9A-Za-z]{6,}$/; //数字和字母
  var reg = "^[A-Za-z0-9]{6,}$"; //数字或字母
  return isCommon(reg, val);
}

//cashpass
function isPassCash(val){
  var reg = "^[0-9]{6}$";
  return isCommon(reg, val);
}

//oid
function isOid(val){
  var reg = "^[0-9]{1,}$";
  return isCommon(reg, val);
}

//isbank
function isBank(val){
  var reg = /^([1-9]{1})(\d{15,18})$/;
  return isCommon(reg, val);
}
