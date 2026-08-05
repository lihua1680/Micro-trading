$(document).keydown(function (event) {
    if (event.keyCode == 13) {
        logon();
    }
});
var vue = new Vue({
    el: '#app',
    data: {
        langIndex:0,
        eye:0,
        userName: '',
        passwords: '',
        checkboxValue: true,
        areaList:[],
        codeId:'',
        areaCode:'',
        areaText:'',
        listShow:false,
        status:'mobile'
    },
    mounted: function () {
        if(getLocal('language')=="en"){
            this.langIndex=1;
        }else{
            this.langIndex=0;
        }
        let that = this;
        // FastClick.attach(document.body);
        that.userName = localStorage.getItem('userName') || '';
        that.passwords = '';
        that.checkboxValue = localStorage.getItem('loginStute') || false;
        localStorage.removeItem('passwords');
        // if (that.userName != '' && that.passwords != '') {
        //     $("#sendLogin").css("background", "#588bf7");
        // } else {
        //     $("#sendLogin").css("background", "#7d818a")
        // }
        
    },
    methods: {
        langClick(txt){
            if(txt==1){
                txt='en';
            }else{
                txt="zh";
            }
            setLocal('language', txt);
            setLang(txt);
            location.reload();
        },
        checkEye(i){
            this.eye=i;
        },
        // 密码显示或者隐藏
        shpass() {
            $("#text").toggle();
            $("#password").toggle();
            if ($("#imgs").attr('src') == 'images/accountm.png') {
                $("#imgs").attr('src', 'images/eyes.png');
            } else {
                $("#imgs").attr('src', 'images/accountm.png');
            }
        },
        passblur() {
            $("#text").val($("#password").val());
        },

        // 密码验证
        passwordConfirm() {
            var pass = $("#password").val();
            // if ($('#name').val() != '' && $('#password').val() != '') {
            //     $("#sendLogin").css("background", "#588bf7");
            // } else {
            //     $("#sendLogin").css("background", "#7d818a")
            // }
            if (pass.length < 6 || pass.length > 16) {
                $("#mes2").html(getlg('plength'));
            } else {
                $("#mes2").html("");
            }
        },
        // 用户验证
        userConfirm() {
            // if ($('#name').val() != '' && $('#password').val() != '') {
            //     $("#sendLogin").css("background", "#588bf7");
            // } else {
            //     $("#sendLogin").css("background", "#7d818a")
            // }
        },
        // 点击登录
        login() {
            let that = this;
            var reg = /^[0-9]\d*$/;
            if(that.userName || reg.test(that.userName)){
                that.status="mobile";
            } else{
                layer_msg(getlg('phoneandemail'));
                return false;
            }
            if (!that.passwords) {
                layer_msg(getlg('pinpwd'));
                return false;
            } else if (that.passwords.length < 6) {
                layer_msg(getlg('ptpwd'));
                return false;
            }
            if (that.checkboxValue) {
                localStorage.setItem('userName', that.userName);
                localStorage.removeItem('passwords');
                localStorage.setItem('loginStute', that.checkboxValue);
            } else {
                localStorage.setItem('userName', '');
                localStorage.removeItem('passwords');
                localStorage.setItem('loginStute', false);
            }
            var data = {};
            data.account = that.userName;
            data.passwd = that.passwords;
            data.request = 'login';
            if(that.status=="mobile"){
                data.area_code = that.areaCode;
                data.area_code_id = that.codeId;
            }
            initDatas({
                url: 'home/ajax',
                data: data,
                type: 'post'
            }, function (res) {
                if (res.status == 1) {
                    layer_msg(getlg('lgsuccess'));
                    setTimeout(function () {
                        window.location.href = res.url;
                    }, 500);
                } else {
                    layer_msg(res.msg);
                }
            });
        },
        selectInput(val) {
            let that = this;
        },
        selectedTab(){
            var that = this;
            that.listShow = true;
        },
        areaSelected(ids,areaCode,name){
            var that = this;
            that.codeId = ids;
            that.areaCode = areaCode;
            that.areaText = name;
            that.listShow = false;
        },
        slectedTap(){
            var that = this;
            that.status = that.status=='mobile'?'email':'mobile'
        }


    }
});