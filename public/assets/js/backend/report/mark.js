define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'report/mark/index' + location.search,
                    add_url: 'report/mark/add',
                    edit_url: 'report/mark/edit',
                    del_url: 'report/mark/del',
                    multi_url: 'report/mark/multi',
                    table: 'mark',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                search: false,
                searchFormVisible: true,
                columns: [
                    [
                        {checkbox: true},
                        {field: 'headimg', title: '头像', formatter:Table.api.formatter.image,operate:false},
                        {field: 'user_id', title: __('User_id')},
                        {field: 'username', title: '昵称'},
                        {field: 'type', title: __('Type'),operate:'LIKE'},
                        {field: 'money', title: __('Money'),operate:false},
                        {field: 'content', title: __('Content'),operate:'LIKE'},
                        {field: 'balance_before', title: __('Balance_before'),operate:false},
                        {field: 'balance_later', title: __('Balance_later'),operate:false},
                        {field: 'addtime', title: __('Addtime'), operate:'RANGE', addclass:'datetimerange'},
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
            }
        }
    };
    return Controller;
});