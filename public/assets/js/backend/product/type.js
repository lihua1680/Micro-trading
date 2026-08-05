define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'product/type/index' + location.search,
                    add_url: 'product/type/add',
                    edit_url: 'product/type/edit',
                    del_url: 'product/type/del',
                    multi_url: 'product/type/multi',
                    table: 'product_type',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                search:false,
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id'),operate:false},
                        {field: 'name', title: __('Name'),operate:'LIKE'},
                        {field: 'rank', title: __('Rank'),operate:false},
                        {field: 'status', title: __('Status'), searchList:{'1':'启用','0':'禁用'},formatter: Controller.api.formatter.status},
                        {field: 'ctime', title: __('Ctime'), operate:'RANGE', addclass:'datetimerange',operate:false},
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
            		return value == 1 ? '<small class="label bg-green">启用</small>':'<small class="label bg-red">禁用</small>';
                }
                
            }
        }
    };
    return Controller;
});