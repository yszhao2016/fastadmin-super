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
                    // multi_url: 'hj212/device/multi',
                    import_url: 'hj212/device/import',
                    table: 'hj212_device',

                },

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
                        {field: 'device_code', title: __('Device_code'), operate: 'LIKE'},
                        {field: 'type', title: "设备监测物", searchList:{"1":"水体","2":"气体"}, formatter: function(val){
                                if(val == '1'){
                                    return "<span ><i ></i>水体</span>";
                                }else if(val == '2'){
                                    return "<span></span>气体</span>";
                                }
                            }},
                        // {field: 'type', title: "设备监测物",searchList:{"1":__('Normal'),"2":__('Is_alarm')}},
                        {
                            field: 'site.site_name',
                            // visible: false,
                            title: __('SiteName'),
                            operate: 'LIKE'
                            // addclass: 'selectpage',
                            // extend: 'data-source="hj212/pollutionsite/index" data-field="site_name" data-primary-key="id"',
                            // operate: '=',
                            // formatter: Table.api.formatter.search
                        },

                        // {
                        //     field: 'site',
                        //     title: __('SiteName'),
                        //     operate: false,
                        // },
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate,
                                 buttons:[
                                     {
                                      'name':'bindsite',
                                      'title':function(row){
                                          return '更换站点[ '+row.device_code+']';
                                      },
                                      'icon':'fa fa-pencil',
                                      'text':'更换站点',
                                      'classname': 'btn btn-xs btn-info btn-dialog',
                                      'url':'hj212/device/bindsite/deviceId/{ids}',
                                      'extend': 'data-area=\'["95%","95%"]\''
                                     },
                                     {
                                      'name':'siteInfo',
                                      'title':function(row){
                                          return '站点信息[ '+row.site+']';
                                      },
                                      'text':'站点信息',
                                      'classname': 'btn btn-xs btn-primary btn-dialog',
                                      'url':function(row){
                                          return 'hj212/pollutionsite/siteinfo/site_id/'+row.site_id;
                                       },
                                      'extend': 'data-area=\'["95%","95%"]\''
                                     },
                                 ]}
                    ]
                ],
                exportTypes: [ 'excel'],
                // showToggle: false,
                showExport: false,
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
        bindsite:function(){
            Controller.api.bindevent();
        },
        api: {
            bindevent: function () {
	                $.validator.config({
                    rules: {
                        checksite: function (element) {
                            return $.ajax({
                                url: 'hj212/device/checksite',
                                type: 'POST',
                                data: {
                                    site_id: $("#c-site_id").val(),
                                    id:$("#c-id").val()
                                },
                                dataType: 'json'
                            });
                        },
                    }
                });
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});
