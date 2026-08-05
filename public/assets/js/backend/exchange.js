define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'exchange/index' + location.search,
                    add_url: 'exchange/add',
                    del_url: 'exchange/del',
                    multi_url: 'exchange/multi',
                    table: 'exchange',
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
                        {field: 'type_a', title: __('Type_a'), operate:false},
                        {field: 'type_b', title: __('Type_b'), operate:false},
                        {field: 'rate', title: __('Rate'), operate:false},
                        {field: 'amount', title: __('Amount'), operate:false},
                        {field: 'money', title: __('Money'), operate:false},
                        {field: 'usdt_before', title: __('Usdt_before'), operate:false},
                        {field: 'usdt_later', title: __('Usdt_later'), operate:false},
                        {field: 'balance_before', title: __('Balance_before'), operate:false},
                        {field: 'balance_later', title: __('Balance_later'), operate:false},
                        {field: 'add_time', title: __('Add_time'), operate:'RANGE', addclass:'datetimerange'},
                        {field: 'status', title: __('Status'), searchList:{'1':'已通过','0':'审核中'},formatter: Controller.api.formatter.status},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
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
            		return value == 1 ? '<small class="label bg-green">已通过</small>':'<small class="label bg-red">审核中</small>';
                }
                
            }
        }
    };
    return Controller;
});