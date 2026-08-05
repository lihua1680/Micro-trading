define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'upmark/index' + location.search,
                    add_url: 'upmark/add',
                    edit_url: 'upmark/edit',
                    del_url: 'upmark/del',
                    multi_url: 'upmark/multi',
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
                searchFormVisible: true,
                columns: [
                    [
                        // 
                        {checkbox: true},
                        {field: 'user_id', title: __('User_id'), operate:'LIKE',placeholder:"用户ID|姓名|登录IP搜索"},
                        {field: 'user.username', title: __('Username'), operate:'LIKE'},
                        {field: 'user.real_name', title: '姓名', operate:'LIKE'},
                        {field: 'money', title: __('Money'),operate:false},
                        {field: 'balance_later',  title:'剩余', operate:false},
                        {field: 'pay_type', title: __('Pay_type'), operate:false},
                        {field: 'img', title:'充值凭证', operate:false,events:Table.api.events.image,formatter:Table.api.formatter.image,},
                        {field: 'time', title: __('Time'), operate:'RANGE', addclass:'datetimerange'},
                        {field: 'uremark', title: '会员备注', operate:false},
                        {field: 'ip', title: '来源IP', operate:false},
                        {field: 'resource', title: '来源地址', operate:false},
                        {field: 'status', title: __('Status'),formatter: Controller.api.formatter.status, searchList:{0:'未审核', 1:'审核通过', 2:'审核未通过'}},
                        {field: 'check_account', title: __('Check_account'), operate:false},
                        {field: 'operate', title: __('Operate'), table: table, 
                        	events: Table.api.events.operate, 
                        	buttons: [
                        		{
                        	        name: 'agree',
                        	        title: '同意',
                        	        text:'同意',
                        	        classname: 'btn btn-xs btn-success btn-magic btn-ajax',
                        	        icon: '',
                        	        hidden: function(row){
                        	            return row.status > 0;
                        	        },
                        	        url: function (row, column) {
                                  	    return 'upmark/check/status/1/ids/' + row.id;
                                    },
                        	        success: function (data, ret) {
                        	            table.bootstrapTable('refresh');
                        	        },
                        	    },
                        	    {
                        	        name: 'against',
                        	        title: '拒绝',
                        	        text:'拒绝',
                        	        classname: 'btn btn-xs btn-danger btn-magic btn-ajax',
                        	        icon: '',
                        	        hidden: function(row){
                        	        	return row.status > 0;
                        	        },
                        	        url: function (row, column) {
                        	        	return 'upmark/check/status/2/ids/' + row.id;
                                    },
                        	        success: function (data, ret) {
                        	            table.bootstrapTable('refresh');
                        	        },
                        	    },{
                                    name: 'withdraw',
                                    title: '撤回',
                                    text:'撤回',
                                    classname: 'btn btn-xs btn-danger btn-magic btn-ajax',
                                    icon: 'fa fa-user',
                                    hidden: function(row){
                                        return row.is_show == 1;
                                    },
                                    url: function (row, column) {
                                        return 'upmark/check/withdraw/1/ids/' + row.id;
                                    },

                                    success: function (data, ret) {
                                        table.bootstrapTable('refresh');
                                    },
                                },
                        		],
                        	formatter: Table.api.formatter.buttons
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
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            },
            formatter: {
            	status: function (value, row, index) {
                    return (value == 1 ? '<small class="label bg-green">审核通过</small>' : value == 2 ? '<small class="label bg-red">审核未通过</small>':'<small class="label bg-blue">未审核</small>');
                },
                isagent: function (value, row, index) {
                 	 return (value == 1 ? '<small class="label bg-green">是</small>' : '<small class="label bg-blue">否</small>');
               }
            }
        }
    };
    return Controller;
});