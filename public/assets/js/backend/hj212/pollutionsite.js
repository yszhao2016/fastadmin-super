define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'hj212/pollutionsite/index/deviceId/'+Config.deviceId + location.search,
                    add_url: 'hj212/pollutionsite/add',
                    edit_url: 'hj212/pollutionsite/edit',
                    del_url: 'hj212/pollutionsite/del',
                    multi_url: 'hj212/pollutionsite/multi',
                    import_url: 'hj212/pollutionsite/import',
                    table: 'hj212_site',
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
                        {field: 'deviceId', title: __('Deviceid')},
                        {field: 'site_name', title: __('SiteName'), operate: 'LIKE'},
                        {field: 'address', title: __('Address'), operate: 'LIKE'},
                        {field: 'lon', title: __('Lon'), operate: 'LIKE'},
                        {field: 'lat', title: __('Lat'), operate: 'LIKE'},
                        {field: 'industrial_park', title: __('Industrial_park'), operate: 'LIKE'},
                        {field: 'contact', title: __('Contact'), operate: 'LIKE'},
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
