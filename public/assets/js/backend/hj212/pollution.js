define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'hj212/pollution/index/' +Config.data_id+ location.search,
                    add_url: 'hj212/pollution/add/data_id/'+Config.data_id,
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
                        {field: 'data_id', title: __('Data_id')},
                        {
                            field: 'pollutioncode.name',
                            title: '检查因子',
                            // searchList: Config.codelist
                            operate:'LIKE'
                        },
                        {
                            field: 'code',
                            title: '因子编码',
                            // searchList: Config.codelist
                            operate:'LIKE'
                        },
                        {field: 'min', title: __('Min'), operate:'BETWEEN'},
                        {field: 'avg', title: __('Avg'), operate:'BETWEEN'},
                        {field: 'max', title: __('Max'), operate:'BETWEEN'},
                        // {field: 'flag', title: __('Flag'), operate: 'LIKE', formatter: Table.api.formatter.flag},
                        {field: 'is_alarm', title: __('Is_alarm'),searchList:{"0":__('Normal'),"1":__('Is_alarm')},
                            formatter: function(val){
                                if(val == '0'){
                                    return "<span style=\"text-info\"><i class=\"fa fa-circle\"></i>正常</span>";
                                }else if(val == '1'){
                                    return "<span class=\"text-danger\"><i class=\"fa fa-circle\"></i>报警</span>";
                                }
                            }
                        },
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
                $("#c-code").change(function(e){
                    $.get('hj212/pollution/getAlarm/code/'+$("#c-code").val(), function (data) {
                        if(data.code == 0){
                            var avg_min = (typeof(data.data.avg_min) !== 'undefined') ? data.data.avg_min : 0;
                            var avg_max = (typeof(data.data.avg_max) !== 'undefined') ? data.data.avg_max : 0;
                            var measures = (typeof(data.data.measures) !== 'undefined') ? data.data.measures : '';

                            $("#avg-notice").html("* 检测时间内平均值范围 "+avg_min+'~'+avg_max+''+measures);
                        }
                        console.log(data);
                    });
                })
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});
