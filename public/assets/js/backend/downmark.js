define(['jquery', 'bootstrap', 'backend', 'table', 'form','bootstrap-table-fixed-columns'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'downmark/index' + location.search,
                    add_url: 'downmark/add',
                    edit_url: 'downmark/edit',
                    del_url: 'downmark/del',
                    multi_url: 'downmark/multi',
                    table: 'upmark',
                    
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'upmark.id',
                sortName: 'upmark.id',
                search: false,
                showToggle: false,
                showColumns: false,
                showExport: false,
                showSearch: false,
                fixedColumns:true,//开启表格固定列功能
                fixedRightNumber:5,//表格左侧固定列的数量?
                searchFormVisible: true,
                columns: [
                    [
                        {checkbox: true},
                        {field: 'user_id', title: __('User_id'), operate:'LIKE',placeholder:"用户ID|姓名|登录IP搜索"},
                        {field: 'username', title:  __('Username'), operate:false},
                        {field: 'user.remark', title: '备注', operate:false},
                        {field: 'num', title: '折合数量', operate:false},
                        {field: 'money', title: '金额', operate:false},
                        {field: 'act_money', title: '实际到账', operate:false},
                        {field: 'commission', title: '手续费', operate:false},
                        {field: 'pay_type', title: '提现方式', operate:false},
                        {field: 'user_name', title: '姓名', operate:false},
                        {field: 'account', title: '卡号(账号)', operate:false},
                        {field: 'bank_name', title: '银行名称', operate:false},
                        {field: 'id_card', title: '身份证号', operate:false},
                        {field: 'bank_phone', title: '手机号', operate:false},
                        
                        {field: 'balance_before',  title:'出款前余额', operate:false},
                        {field: 'balance_later',  title:'出款后余额', operate:false},
                        {field: 'ip', title: '来源IP', operate:false},
                        {field: 'resource', title: '来源地址', operate:false},
                        {field: 'time', title: '提交时间', operate:'RANGE', addclass:'datetimerange'},
                        {field: 'b', title: "提现提醒", table: table, 
                        	events: Table.api.events.operate, 
                        	buttons: [
                        	    {
                                    name: 'stopAudio',
                                    text: function(row,column){
                                        if(row.statuss==0){
                                            return '关闭提示音'
                                        }else{
                                            return '已关闭'
                                        }
                                    },
                                    icon: 'fa fa-stop-circle',
                                    classname: 'btn btn-xs btn-success btn-ajax',
                                    url:'downmark/close?id={id}',
                                    confirm:'关闭提示音',
                                    success:function(ret){
                                        if (window.parent && typeof window.parent.stopAudio === 'function') {
                                            window.parent.stopAudio();
                                            table.bootstrapTable('refresh',{})
                                        }
                                    },
                                    disable:function(row){
                                        if(row.statuss==1){
                                            return true;
                                        }
                                        return false;
                                    }
                                },
                        	    
                        		],
                        	formatter: Table.api.formatter.buttons
                        },
                        {field: 'status', title: '状态',formatter: Controller.api.formatter.status, searchList:{0:'未审核', 1:'审核通过', 2:'审核未通过'}},
   
                        {field: 'b', title: "核对", table: table, 
                        	events: Table.api.events.operate, 
                        	buttons: [
                        	     
                        	    {
                                    name: 'viewhd_c',
                                    text: '转账说明',
                                    extend: 'data-area=\'["100%","100%"]\'',
                                    icon: 'fa fa-pencil',
                                    classname: 'btn btn-xs btn-warning btn-dialog',
                                    url: function (row, column) {
                        	        	return 'downmark/viewhd_c?ids=' + row.id;
                                    }
                                 }, 
                        	    {
                                    name: 'viewhd',
                                    text: '转账截图',
                                    extend: 'data-area=\'["100%","100%"]\'',
                                    icon: 'fa fa-pencil',
                                    classname: 'btn btn-xs btn-success btn-dialog',
                                    url: function (row, column) {
                        	        	return 'downmark/viewhd?ids=' + row.id;
                                    }
                                 }, 
                        	    
                        		],
                        	formatter: Table.api.formatter.buttons
                        },
                        {field: 'operate', title: __('Operate'), table: table, 
                        	events: Table.api.events.operate, 
                        	buttons: [
                        // 		{
                        // 	        name: 'agree',
                        // 	        title: '同意',
                        // 	        text:'同意',
                        // 	        classname: 'btn btn-xs btn-success btn-magic btn-ajax',
                        // 	        icon: '',
                        // 	        hidden: function(row){
                        // 	            return row.status > 0;
                        // 	        },
                        // 	        url: function (row, column) {
                        //           	    return 'downmark/check/status/1/ids/' + row.id;
                        //             },
                        // 	        success: function (data, ret) {
                        // 	            table.bootstrapTable('refresh');
                        // 	        },
                        // 	    },
                        // 	    {
                        // 	        name: 'against',
                        // 	        title: '拒绝',
                        // 	        text:'拒绝',
                        // 	        classname: 'btn btn-xs btn-danger btn-magic btn-ajax',
                        // 	        icon: '',
                        // 	        hidden: function(row){
                        // 	        	return row.status > 0;
                        // 	        },
                        // 	        url: function (row, column) {
                        // 	        	return 'downmark/check/status/2/ids/' + row.id;
                        //             },
                        // 	        success: function (data, ret) {
                        // 	            table.bootstrapTable('refresh');
                        // 	        },
                        // 	    },
                                
                        	    {
                                    name: 'check',
                                    text: '审核',
                                    icon: 'fa fa-pencil',
                                    classname: 'btn btn-xs btn-warning btn-dialog',
                                    hidden: function(row){
                        	        	return row.status > 0;
                        	        },
                                    url: function (row, column) {
                        	        	return 'downmark/check/ids/' + row.id;
                                    }
                                 }, 
                                  {
                                    name: 'check',
                                    text: '修改',
                                    icon: 'fa fa-pencil',
                                    classname: 'btn btn-xs btn-info btn-magic btn-ajax',
                                    hidden: function(row){
                        	        	return row.status == 0;
                        	        },
                                    url: function (row, column) {
                        	        	return 'downmark/change_status/ids/' + row.id;
                                    },
                                    success: function (data, ret) {
                        	            table.bootstrapTable('refresh');
                        	        },
                                 }, 
                                  {
                                    name: 'check',
                                    text: '删除',
                                    icon: 'fa fa-pencil',
                                    classname: 'btn btn-xs btn-danger btn-magic btn-ajax',
                                    hidden: function(row){
                        	        	return row.status > 0;
                        	        },
                                    url: function (row, column) {
                        	        	return 'downmark/cx/ids/' + row.id;
                                    },
                                    success: function (data, ret) {
                        	            table.bootstrapTable('refresh');
                        	        },
                                 }, 
                                  
                        	    {
                                    name: 'edit',
                                    text: '编辑',
                                    icon: 'fa fa-pencil',
                                    classname: 'btn btn-xs btn-success btn-dialog',
                                    url: function (row, column) {
                        	        	return 'downmark/edit/ids/' + row.id;
                                    }
                                 }, 
                        	    
                        		],
                        	formatter: Table.api.formatter.buttons
                        }
                    ]
                ]
            });
            // 为表格绑定事件
            Table.api.bindevent(table);

            if ($("#auto_voice").attr('data-status') == 1) {
                $("#auto_voice").html('关闭提示音');
                $("#auto_voice").removeClass("btn-danger")
                $("#auto_voice").addClass("btn-success")
            } else {
                $("#auto_voice").html('开启提示音');
                $("#auto_voice").removeClass("btn-success")
                $("#auto_voice").addClass("btn-danger")
            }

            // 打开关闭声音按钮
            $(document).on("click", "#auto_voice", function () {
                let status = $("#auto_voice").attr('data-status');
                if (status == 1) {
                    $("#auto_voice").html('开启提示音');
                    $("#auto_voice").removeClass("btn-success")
                    $("#auto_voice").addClass("btn-danger")
                } else {
                    $("#auto_voice").html('关闭提示音');
                    $("#auto_voice").removeClass("btn-danger")
                    $("#auto_voice").addClass("btn-success")
                }
                $("#auto_voice").attr('data-status', status == 1 ? 0 : 1);
                Table.api.multi("voice", status == 1 ? 0 : 1, table, this);
            })

        },
        add: function () {
            Controller.api.bindevent();
        },
        edit: function () {
            Controller.api.bindevent();
        },
        check: function () {
            Controller.api.bindevent();
        },


        
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"),function(){
                    window.parent.document.querySelectorAll('button[type=reset]')[0].click()
                });
            },
            formatter: {
            	status: function (value, row, index) {
                    return (value == 1 ? '<small class="label bg-green">审核通过</small>' : value == 2 ? '<small class="label bg-red">审核未通过</small>':'<small class="label bg-blue">未审核</small>');
                },
                isagent: function (value, row, index) {
                	 return (value == 1 ? '<small class="label bg-green">是</small>' : '<small class="label bg-blue">否</small>');
                }
                
            },
        }
    };
    return Controller;
});