define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'game/room/index' + location.search,
                    add_url: 'game/room/add',
                    edit_url: 'game/room/edit',
                    del_url: 'game/room/del',
                    multi_url: 'game/room/multi',
                    table: 'room',
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
                        {field: 'room_name', title: __('Room_name'),operate:'LIKE'},
                        {field: 'game_code', title: __('Game_code'), searchList: $.getJSON("ajax/gamelist")},
                        {field: 'rank', title: __('Rank'),operate:'LIKE'},
                        {field: 'status', title: __('Status'), searchList: {"1": '启用', "0": '禁用'},formatter: Controller.api.formatter.status},
                  
                        {field: 'operate', title: __('Operate'), table: table,
                        	events: Table.api.events.operate,
                        	buttons: [{
                                name: 'reply',
                                text: '设置',
                                icon: 'fa fa-cogs',
                                classname: 'btn btn-xs btn-success btn-dialog',
                                url: 'game/room/odds' 
                              }],
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
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            },
        	formatter: {
                status: function (value, row, index) {
                    return (value == 1 ? '<small class="label bg-blue">启用</small>' : '<small class="label bg-red">禁用</small>');
                },
            }
        },
        odds: function () {
        	Form.api.bindevent("form");
        	Form.api.submit("form");
        }
    };
    return Controller;
});