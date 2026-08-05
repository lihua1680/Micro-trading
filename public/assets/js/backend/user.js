define(['jquery', 'bootstrap', 'backend', 'table', 'form', 'editable','bootstrap-table-fixed-columns'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'user/index' + location.search,
                    add_url: 'user/add',
                    edit_url: 'user/edit',
                    del_url: 'user/del',
                    multi_url: 'user/multi',
                    table: 'user',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                search: false,
                showToggle: false,
                showColumns: false,
                showExport: false,
                showSearch: false,
                fixedColumns:true,//开启表格固定列功能
                fixedRightNumber:1,//表格左侧固定列的数量?
                searchFormVisible: true,
                escape:false,
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Uid'), operate:false},
                        {field: 'user_avatar', title: '会员头像', events: Table.api.events.image, formatter: Table.api.formatter.image, operate: false},
                        {field: 'account', title: __('Account'), operate:'LIKE',placeholder:"用户ID|姓名|登录IP搜索"},
                        {field: 'real_name', title: __('Real_name'), operate:false},
                        {field: 'is_auth', title: __('Auth_status'), formatter: Controller.api.formatter.auth, sortable: true, operate:false},
                        {field: 'money', title: __('Money'), operate:false, sortable: true},
                        {field: 'usdt', title: 'USDT余额', operate:false, sortable: true},
                        {field: 'freeze_funds', title:'冻结资金', operate:false},
                        {field: 'credit_score', title:'信誉分', operate:false},
                        {field: 'agent', title: __('Agent'), operate:false},
                        {field: 'is_online', title: __('Is_online'), formatter: Controller.api.formatter.online, sortable: true, operate:false},
                        {field: 'remark', title: __('Remark'), editable: true, operate:false},
                        {field: 'reg_time', title: __('Reg_time'), operate:false, addclass:'datetimerange', sortable: true},
                        {field: 'last_login_time', title: __('Last_login_time'), operate:false, addclass:'datetimerange', sortable: true},
                        {field: 'last_login_ip', title: '登录IP'},
                        {field: 'attach_text', title: '银行账户', formatter: Controller.api.formatter.bank, operate:false},
                        {field: 'kong_style', title: '会员风控', editable: {
                            type: 'select',
                            pk: 1,
                            disabled:false,
                            source: [ 
                                {value: '0', text: '默认  ▼'},
                                {value: '1', text: '赢  ▼'},
                                {value: '2', text: '亏  ▼'},
                            ]
                        }, searchList:{'0':'默认', '1':'赢', '2':'亏'}},
                        {field: 'status', title: __('Status'), formatter: Controller.api.formatter.status, searchList:{'0':'黑名单', '1':'正常'}},
                        {field: 'operate', title: __('Operate'), table: table, 
                        	events: Table.api.events.operate, 
                        	buttons: [
                        		
                        	   {
                                name: 'detail',
                                text: __('Detail'),
                                icon: 'fa fa-list',
                                classname: 'btn btn-xs btn-success btn-dialog',
                                url: 'user/detail' 
                              }, 
                              {
                                name: 'score',
                                text: '分数',
                                icon: 'fa fa-cart-plus',
                                classname: 'btn btn-warning btn-xs btn-detail btn-dialog',
                                url: 'user/score'
                              },
                              {
                                name: 'score',
                                text: '实名认证',
                                icon: 'fa fa-cart-plus',
                                classname: 'btn btn-info btn-xs btn-detail btn-dialog',
                                url: 'user/auth'
                              },
                              {
                                name: 'freeze_funds',
                                text: '冻结资金',
                                icon: 'fa fa-cart-plus',
                                classname: 'btn btn-warning btn-xs btn-detail btn-dialog',
                                url: 'user/freeze_funds'
                              },
                              
                              {
                                name: 'credit_score',
                                text: '信誉分',
                                icon: 'fa fa-cart-plus',
                                classname: 'btn btn-warning btn-xs btn-detail btn-dialog',
                                url: 'user/credit_score'
                              },
                              
                              {
                                  name: 'report',
                                  text: '报表',
                                  icon: 'fa fa-area-chart',
                                  classname: 'btn btn-info btn-xs btn-detail btn-dialog',
                                  extend: 'data-area=\'["100%","100%"]\'',
                                  url: function (row, column) {
                                	  return 'report/mark/index?ids=' + row.id;
                                  }
                                },{
                        	        name: 'limit',
                        	        title: '设置黑名单',
                        	        text:'设置黑名单',
                        	        classname: 'btn btn-xs btn-danger btn-magic btn-ajax',
                        	        icon: 'fa fa-user',
                        	        hidden: function(row){
                        	            return row.status == 0;
                        	        },
                        	        url: function (row, column) {
                                  	    return 'user/check/status/0/ids/' + row.id;
                                    },
                        	        
                        	        success: function (data, ret) {
                        	            table.bootstrapTable('refresh');
                        	        },
                        	    },
                        	    {
                        	        name: 'cancellimit',
                        	        title: '取消黑名单',
                        	        text:'取消黑名单',
                        	        classname: 'btn btn-xs btn-success btn-magic btn-ajax',
                        	        icon: 'fa fa-user-secret',
                        	        hidden: function(row){
                        	        	return row.status == 1;
                        	        },
                        	        url: function (row, column) {
                        	        	return 'user/check/status/1/ids/' + row.id;
                                    },
                        	        
                        	        success: function (data, ret) {
                        	            table.bootstrapTable('refresh');
                        	        },
                        	    },
                        	    {
                        	        name: 'freeze',
                        	        title: '冻结',
                        	        text:'冻结',
                        	        classname: 'btn btn-xs btn-danger btn-magic btn-ajax',
                        	        icon: 'fa fa-user',
                        	        hidden: function(row){
                        	            return row.freeze == 1;
                        	        },
                        	        url: function (row, column) {
                                  	    return 'user/check/freeze/1/ids/' + row.id;
                                    },
                        	        
                        	        success: function (data, ret) {
                        	            table.bootstrapTable('refresh');
                        	        },
                        	    },
                        	    {
                        	        name: 'cancefreeze',
                        	        title: '取消冻结',
                        	        text:'取消冻结',
                        	        classname: 'btn btn-xs btn-success btn-magic btn-ajax',
                        	        icon: 'fa fa-user-secret',
                        	        hidden: function(row){
                        	        	return row.freeze == 0;
                        	        },
                        	        url: function (row, column) {
                        	        	return 'user/check/freeze/0/ids/' + row.id;
                                    },
                        	        
                        	        success: function (data, ret) {
                        	            table.bootstrapTable('refresh');
                        	        },
                        	    }
  	                            
	                          
	                          ],
                                
                                formatter: Table.api.formatter.operate
                        	
                        }
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        add: function () {
            Controller.api.bindevent();
        },
        edit: function () {
            Controller.api.bindevent();
        },
        score: function () {
            Controller.api.bindevent();
        },
        chat: function () {
            Controller.api.bindevent();
        },
        report: function () {
            Controller.api.bindevent();
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            },
            formatter: {
                thumb: function (value, row, index) {
                	return '<a href="' + value + '" target="_blank"><img src="' + value + '"  width="35" height="35" alt=""></a>';
                },
                online: function (value, row, index) {
                	return (value == 0 ? '<small class="label bg-green">在线</small>' : '<small class="label bg-blue">离线</small>');
                },
                auth: function (value, row, index) {
                    if(value == 2){
                        return '<small class="label bg-green">已认证</small>';
                    }else if(value == 1){
                        return '<small class="label bg-red">待审核</small>';
                    }else if(value == -1){
                        return '<small class="label bg-red">审核失败</small>';
                    }else{
                        return '<small class="label bg-primary">未提交</small>';
                    }
                },
                status: function (value, row, index) {
                	return (value == 1 ? '<small class="label bg-green">正常</small>' : '<small class="label bg-blue">黑名单</small>');
                },
                agent: function (value, row, index) {
                 	 return (value ? value: '-');
                },
                bank: function (value, row, index) {
                  	 return (value == 1 ? '<small class="label bg-green">已绑定</small>' : '<small class="label bg-red">未绑定</small>');
                },
                real: function (value, row, index) {
                 	 return (value == 1 ? (row.isagent == 1 ? '<small class="label bg-green">代理</small>' : '<small class="label bg-blue">会员</small>') : '<small class="label bg-blue">带玩</small>');
                },
                kong_style: function (value, row, index) {
                	if (value == 1) {
                		return '<small class="label bg-red">赢</small>';
                	} else if(value == 2) {
                		return '<small class="label bg-green">亏</small>';
                	} else if(value == 0) {
                		return '<small class="label bg-blue">默认</small>';
                	}
            		
                }
               
            }
        }
    };
    return Controller;
});