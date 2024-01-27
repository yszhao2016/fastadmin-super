define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'hj212/data/index' + location.search,
/*                    add_url: 'hj212/data/add',
                    edit_url: 'hj212/data/edit',*/
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
                rowStyle:function(row,index){
                    var style = {};
                    style = { css: {'background-color' : '#FFFAF0','color':'red'}}

                    if(row.is_alarm == 1){
                        return style;
                    }else{
                        return false;
                    }
                },
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id'),operate: false},
                        {field: 'qn', title: __('Qn'),operate: 'LIKE'},
                        {
                            field: 'site_name',
                            title: __('SiteName'),
                            operate: 'LIKE'
                        },
                        {field: 'cn', title: __('Cn')},
                        {field: 'mn', title: __('Mn')},
                        // {field: 'cp_datatime', title: __('Cp_datatime'),
                        //     operate: 'RANGE',
                        //     addclass: 'datetimerange',
                        //     formatter: Table.api.formatter.datetime},
                        // {field: 'is_forward', title: __('Is_forward'),searchList:{"0":__('No'),"1":__('YES')},formatter: Table.api.formatter.status },
                        {
                            field: 'is_alarm',
                            title: __('Is_alarm'),
                            searchList:{"0":__('Normal'),"1":__('Is_alarm')},
                            formatter: function(val){
                                if(val == '0'){
                                    return "<span style=\"text-info\"><i class=\"fa fa-circle\"></i>正常</span>";
                                }else if(val == '1'){
                                    return "<span class=\"text-danger\"><i class=\"fa fa-circle\"></i>报警</span>";
                                }
                            }
                        },
                        {field: 'cp_datatime', title: "采集时间",
                            operate: 'RANGE',
                            addclass: 'datetimerange',
                            formatter: Table.api.formatter.datetime},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate,
                                 buttons:[
                                     {
                                         'name':'bindCp',
	                                      'text':'检测数据查询',
	                                      'classname': 'btn btn-xs btn-info btn-dialog',
	                                      'url':'hj212/pollution/index?data_id={ids}&time={qn}',
	                                      'extend': 'data-area=\'["95%","95%"]\''
                                     },
                                     {
	                                    text:"数据分析",
	                                    name:"数据分析",
	                                   'extend': 'data-area=\'["95%","95%"]\'',
	                                    classname:"btn btn-primary btn-xs btn-dialog",
	                                    url:'hj212/data/analysisdata?data_id={ids}&time={qn}&mn={mn}',
	                                },
                                 ]}
                    ]
                ],
                showExport: false,
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
/*        add: function () {
            Controller.api.bindevent();
        },
        edit: function () {
            Controller.api.bindevent();
        },*/
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});
