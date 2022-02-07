define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'flow/delegate/index' + location.search,
                    add_url: 'flow/delegate/add',
                    edit_url: 'flow/delegate/edit',
                    del_url: 'flow/delegate/del',
                    multi_url: 'flow/delegate/multi',
                    table: 'flow_delegate',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id'), operate: false},
                        {field: 'admin_id', title: __('Admin_id'),visible: false, operate: false},
                        {field: 'admin_name', title: __('Admin_name')},
                        {field: 'delegate_name', title: __('Delegate_name')},
                        {field: 'delegate_id', title: __('Delegate_id'),visible: false, operate: false},
                        {field: 'begin_date', title: __('Begin_date'), operate:'RANGE', addclass:'datetimerange'},
                        {field: 'end_date', title: __('End_date'), operate:'RANGE', addclass:'datetimerange'},
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
            }
        }
    };
    return Controller;
});