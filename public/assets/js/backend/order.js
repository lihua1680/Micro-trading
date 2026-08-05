define(['jquery', 'bootstrap', 'backend', 'table', 'form', 'editable'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'order/index' + location.search,
                    edit_url: 'order/edit',
                    del_url: 'order/del',
                    multi_url: 'order/multi',
                    table: 'order',
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
                searchFormVisible: true,
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id'), operate:false},
                        {field: 'user_id', title: __('User_id'), operate:'LIKE',placeholder:"用户ID|姓名|登录IP搜索"},
                        {field: 'user.account', title: __('User.account'), operate:false},
                        {field: 'user.real_name', title: '真实姓名', operate:false},
                        {field: 'user.remark', title: '备注', operate:false},
                        {field: 'product.title', title: __('Product.title'), operate:false},
                        {field: 'o_style', title: __('O_style'), searchList:{'1':'买涨','0':'买跌'},formatter: Controller.api.formatter.o_style},
                        {field: 'buy_money', title: __('Buy_money'), operate:false},
                        {field: 'balance_buy_after', title: __('Balance_buy_after'), operate:false},
                        {field: 'buy_price', title: __('Buy_price'), operate:false},
                        {field: 'sell_price', title: __('Sell_price'), operate:false},
                        {field: 'buy_time', title: __('Buy_time'), operate:'RANGE', addclass:'datetimerange'},
                        {field: 'sell_time', title: __('Sell_time'), operate:'RANGE', addclass:'datetimerange'},
                        {field: 'order_type', title: '类型', operate:false},
                        {field: 'end_profit', title: __('Ploss'), operate:false},//editable: {type: 'select',pk: 1, disabled:false,source: [{value: '0', text: '默认  ▼'},{value: '1', text: '赢  ▼'},{value: '2', text: '亏  ▼'},]},
                        {field: 'kong_type', title: __('Kong_type'),formatter:Controller.api.formatter.kong_type,  searchList:{'0':'默认', '1':'赢', '2':'亏'}},
                        {field: 'status', title: __('Status'),visible: false, searchList:{'1':'未结算','3':'已结算'}},
                        {field: 'b', title: "下单提醒", table: table, 
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
                                    url:'order/close?id={id}',
                                    confirm:'关闭提示音',
                                    success:function(ret){
                                        if (window.parent && typeof window.parent.stopAudio === 'function') {
                                            window.parent.stopAudio1();
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
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
            
            var fresh = '';
            
            if ($("#auto_refresh").attr('data-status') == 1) {
            	$("#auto_refresh").html('取消自动刷新');
            	fresh = setInterval(function(){
                    $(".btn-refresh").trigger("click");
                }, 10000);
            } else {
            	
            }

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
            
            // 启动和暂停按钮
            $(document).on("click", ".btn-start", function () {
            	var status = $("#auto_refresh").attr('data-status');
            	
            	if (status == 1) {
            		clearInterval(fresh); 
            		$("#auto_refresh").html('开启自动刷新');
            		
            	} else {
            		fresh = setInterval(function(){
                        $(".btn-refresh").trigger("click");
                    }, 10000);
            		$("#auto_refresh").html('取消自动刷新');
            	}
            	$("#auto_refresh").attr('data-status', status == 1 ? 0 : 1);
                Table.api.multi("status", status == 1 ? 0 : 1, table, this);
            });
            
            
         // 启动和暂停按钮
            $(document).on("change", ".kong_btn", function () {
            	var kong_type = $(this).val();
            	var ids = $(this).attr('data-id');
                Table.api.multi("kong_type", ids+'_'+kong_type, table, this);
            });
            
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
            	o_style: function (value, row, index) {
            		return value == 1 ? '<small class="label bg-red">买涨</small>':'<small class="label bg-green">买跌</small>';
                },
                kong_type: function (value, row, index) {
                	
                	if (row.status == 1) {
                		var str = '<select class="kong_btn" data-id="'+row.id+'">'+
                    	'<option value="0"'+(value == 0 ? 'selected':'')+'>默认</option>'+
                    	'<option value="1"'+(value == 1 ? 'selected':'')+'>赢</option>'+
                    	'<option value="2"'+(value == 2 ? 'selected':'')+'>亏</option>'+ 
                    	'</select>';
                		return str;
                	} else {
                		return '<small class="label bg-red">已平仓</small>';
                	}

                },
                status: function (value, row, index) {
                	if (value == 1) {
                		return '<small class="label bg-green">建仓</small>';
                	} else if(value == 3) {
                		return '<small class="label bg-green">平仓</small>';
                	}
            		
                },
                get_status: function (value, row, index) {
                	
                	return false;
                },
                o_status: function (value, row, index) {
                	
                	if (row.status == 3) {
                		return '<small class="label bg-green">平仓</small>'; 
                	}
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