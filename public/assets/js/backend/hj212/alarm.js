define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'hj212/alarm/index' + location.search,
                    add_url: 'hj212/alarm/add',
                    edit_url: 'hj212/alarm/edit',
                    del_url: 'hj212/alarm/del',
                    multi_url: 'hj212/alarm/multi',
                    import_url: 'hj212/alarm/import',
                    table: 'hj212_alarm',
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
                        {field: 'id', title: __('Id'),operate:false},
                        {
                            field: 'code',
                            title: __('Code'),
                            visible:false,
                            addclass: 'selectpage',
                            extend: 'data-source="hj212/pollutioncode/index" data-field="name" data-primary-key="code"',
                            operate: 'in',
                            formatter: Table.api.formatter.search
                        },
                        {
                            field: 'codeNm',
                            title: __('Code'),
                            operate: false,
                        },
                        {field: 'warn_min', title: __('Warn_min'), operate:false},
                        {field: 'warn_max', title: __('Warn_max'), operate:false},
                        {field: 'alarm_min', title: __('Alarm_min'), operate:false},
                        {field: 'alarm_max', title: __('Alarm_max'), operate:false},
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
