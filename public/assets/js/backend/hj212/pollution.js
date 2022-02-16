define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'hj212/pollution/index/' +Config.data_id+ location.search,
                    add_url: 'hj212/pollution/add',
                    edit_url: 'hj212/pollution/edit',
                    del_url: 'hj212/pollution/del',
                    multi_url: 'hj212/pollution/multi',
                    import_url: 'hj212/pollution/import',
                    table: 'hj212_pollution',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                fixedColumns: true,
                fixedRightNumber: 1,
                columns: [
                    [
                        {checkbox: true},
                        {field: 'data_id', title: __('Data_id')},
                        {field: 'code', title: __('Code'), operate: 'LIKE'},
//                        {field: 'cou', title: __('Cou'), operate:'BETWEEN'},
                        {field: 'min', title: __('Min'), operate:'BETWEEN'},
                        {field: 'avg', title: __('Avg'), operate:'BETWEEN'},
                        {field: 'max', title: __('Max'), operate:'BETWEEN'},
                        {field: 'flag', title: __('Flag'), operate: 'LIKE', formatter: Table.api.formatter.flag},
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
