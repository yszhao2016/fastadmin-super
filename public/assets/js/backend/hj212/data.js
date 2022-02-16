define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'hj212/data/index' + location.search,
                    add_url: 'hj212/data/add',
                    edit_url: 'hj212/data/edit',
                    del_url: 'hj212/data/del',
                    multi_url: 'hj212/data/multi',
                    import_url: 'hj212/data/import',
                    table: 'hj212_data',
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
                        {field: 'id', title: __('Id')},
                        {field: 'qn', title: __('Qn')},
                        {field: 'st', title: __('St')},
                        {field: 'cn', title: __('Cn')},
                        {field: 'mn', title: __('Mn')},
                        {field: 'flag', title: __('Flag'), operate: 'LIKE', formatter: Table.api.formatter.flag},
                        {field: 'pnum', title: __('Pnum')},
                        {field: 'pno', title: __('Pno')},
                        {field: 'cp_datatime', title: __('Cp_datatime'), operate: 'LIKE'},
                        {field: 'crc', title: __('Crc'), operate: 'LIKE'},
                        {field: 'is_forward', title: __('Is_forward'),searchList:{"0":__('No'),"1":__('YES')},formatter: Table.api.formatter.status },
//                        {field: 'is_change', title: __('Is_change')},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate,
                                 buttons:[
                                     {
                                         'name':'bindCp',
                                         'title':function(row){
                                         		return '查看数据区[ '+row.id+']';
                                         },
                                          'icon':'fa fa-pencil',
	                                      'text':'数据查询',
	                                      'classname': 'btn btn-xs btn-info btn-dialog',
	                                      'url':'hj212/pollution/index/data_id/{ids}',
	                                      'extend': 'data-area=\'["95%","95%"]\''
                                     },
                                     {
	                                    text:"数据分析",
	                                    name:"数据分析",
	                                   'extend': 'data-area=\'["95%","95%"]\'',
	                                    classname:"btn btn-primary btn-xs btn-dialog",
	                                    url:'hj212/data/analysisdata/data_id/{ids}',
	                                },
                                 ]}
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
//		analysisdata: function () {
//			
//            // 初始化表格参数配置
//            Table.api.init({
//                extend: {
//                    index_url: 'hj212/data/analysisdata/data_id/'+Config.data_id + location.search,
//                    table: 'hj212_data',
//                }
//            });
//
//            var table = $("#analysisdata");
//
//            // 初始化表格
//            table.bootstrapTable({
//                url: $.fn.bootstrapTable.defaults.extend.index_url,
//                pk: 'id',
//                sortName: 'id',
//                fixedColumns: true,
//                fixedRightNumber: 1,
//                columns: [
//                    [
//                        {checkbox: true},
//                        {field: 'mn', title: __('Mn')},
//                        {field: 'site.site_name', title: __('SiteName'), operate: 'LIKE'},
//                        {field: 'site.address', title: __('Address'), operate: 'LIKE'},
//                        {field: 'site.lon', title: __('Lon'), operate: 'LIKE'},
//                        {field: 'site.lat', title: __('Lat'), operate: 'LIKE'},
//                        {field: 'site.industrial_park', title: __('Industrial_park'), operate: 'LIKE'},
//                        {field: 'site.contact', title: __('Contact'), operate: 'LIKE'},
//                    ]
//                ]
//            });
//            // 为表格绑定事件
//            Table.api.bindevent(table);
//        },
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
