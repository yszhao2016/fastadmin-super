define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'flow/department/index' + location.search,
                    add_url: 'flow/department/add',
                    edit_url: 'flow/department/edit',
                    del_url: 'flow/department/del',
                    multi_url: 'flow/department/multi',
                    table: 'flow_department',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                escape:false,
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id')},
                        {field: 'name', title: __('Name'), align: 'left'},
                        {field: 'code', title: __('Code'), align: 'left' },
                        {field: 'managername', title: __('Manager')},
                        {field: 'createtime', title: __('Createtime'), operate:'RANGE',formatter: Table.api.formatter.datetime, addclass:'datetimerange'},
                        {field: 'updatetime', title: __('Updatetime'), operate:'RANGE',formatter: Table.api.formatter.datetime, addclass:'datetimerange'},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: function (value, row, index) {
                            if (row.pid==0) {
                                return '';
                            }
                            return Table.api.formatter.operate.call(this, value, row, index);
                        }}
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