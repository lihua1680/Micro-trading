define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'game/game/index' + location.search,
                    add_url: 'game/game/add',
                    edit_url: 'game/game/edit',
                    multi_url: 'game/game/multi',
                    table: 'game',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                search : false,
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id')},
                        {field: 'game_code', title: __('Game_code')},
                        {field: 'game_name', title: __('Game_name')},
                        {field: 'game_open', title: __('Game_open'), searchList: {"1": '启用', "0": '禁用'},formatter: Controller.api.formatter.game_open},
                        {field: 'rank', title: __('Rank')},
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
                game_open: function (value, row, index) {
                    return (value == 1 ? '<small class="label bg-blue">启用</small>' : '<small class="label bg-red">禁用</small>');
                },
            }
        }
    };
    return Controller;
});