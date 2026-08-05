define(['jquery', 'bootstrap', 'backend', 'table', 'form', 'editable'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'product/product/index' + location.search,
                    add_url: 'product/product/add',
                    edit_url: 'product/product/edit',
                    del_url: 'product/product/del',
                    multi_url: 'product/product/multi',
                    table: 'product',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                search:false,
                searchFormVisible: true,
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id'),operate:false},
                        {field: 'rank', title:'排序', editable:true,operate:false},
                        {field: 'code', title: __('Code'),operate:'LIKE'},
                        {field: 'title', title: __('Title'),operate:'LIKE'},
                        {field: 'image', title: __('Image'),operate:false, events: Table.api.events.image, formatter: Table.api.formatter.image},
                        {field: 'producttype.name', title: __('Producttype.name'),operate:false},
                        {field: 'price', title: '当前价格',operate:false},
                        {field: 'price_update', title: '更新时间',operate:false},
                        {field: 'is_open', title: __('Is_open'), searchList:{'1':'开启','0':'关闭'},formatter: Controller.api.formatter.is_open,operate:false},
                        {field: 'status', title: __('Status'), searchList:{'1':'启用','0':'禁用'},formatter: Controller.api.formatter.status},
                        {field: 'ctime', title: __('Ctime'), operate:'RANGE',operate:false, addclass:'datetimerange', formatter: Table.api.formatter.datetime},
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
                },
                is_open: function (value, row, index) {
                    return value == 1 ? '<small class="label bg-green">开启</small>':'<small class="label bg-red">关闭</small>';
                }

            }
        }
    };
    return Controller;
});