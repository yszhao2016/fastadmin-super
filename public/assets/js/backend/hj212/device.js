define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'hj212/device/index' + location.search,
                    add_url: 'hj212/device/add',
                    edit_url: 'hj212/device/edit',
                    del_url: 'hj212/device/del',
                    multi_url: 'hj212/device/multi',
                    import_url: 'hj212/device/import',
                    table: 'hj212_device',
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
                        {field: 'id', title: __('Id')},
                        {field: 'device_code', title: __('Device_code'), operate: 'LIKE'},
                        {field: 'device_pwd', title: __('Device_pwd'), operate: 'LIKE'},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate,
                                 buttons:[
                                     {
                                      'name':'bindsite',
                                      'title':function(row){
                                          return '绑定站点[ '+row.device_code+']';
                                      },
                                      'icon':'fa fa-pencil',
                                      'text':'绑定站点',
                                      'classname': 'btn btn-xs btn-info btn-dialog',
                                      'url':'hj212/pollutionsite/index/deviceId/{ids}',
                                      'extend': 'data-area=\'["95%","95%"]\''
                                     },
                                 ]}
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
