require.config({
    paths: {
        'echarts': "../addons/fastflow/plugins/echarts/echarts.min",
    }
});
define(['jquery', 'bootstrap', 'backend', 'table', 'form', 'echarts'], function ($, undefined, Backend, Table, Form, ECharts) {

    var Controller = {
        index: function () {
            
            Table.api.init({
                extend: {
                    index_url: 'fastflow/flow/process/index' + location.search,
                    add_url: 'fastflow/flow/process/add',
                    edit_url: 'fastflow/flow/process/edit',
                    del_url: 'fastflow/flow/process/del',
                    multi_url: 'fastflow/flow/process/multi',
                    import_url: 'fastflow/flow/process/import',
                    table: 'fastflow_process',
                }
            });

            var table = $("#table");

            
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'bill',
                columns: [
                    [
                        {field: 'id', title: __('Id')},
                        {field: 'bill', title: __('Bill'), operate: 'LIKE', formatter: Table.api.formatter.normal},
                        {
                            field: 'bill_id', title: __('Bill_id'), formatter: function (value, row, index) {
                                return '<span class="text-info">' + value + '</span>';
                            }
                        },
                        {
                            field: 'flow_name', title: __('FlowName'), formatter: function (value, row, index) {
                                return '<span class="text-success">' + value + '</span>';
                            }
                        },
                        {
                            field: 'flow_id', title: __('Flow_id'), formatter: function (value, row, index) {
                                return '<span class="text-success">' + value + '</span>';
                            }
                        },
                        {field: 'createusername', title: __('流程发起人'), operate: false},
                        {field: 'flow_progress', title: __('流程进度'), operate: false, formatter: Controller.api.formatter.progress},
                        {
                            field: 'status',
                            title: __('Status'),
                            searchList: {"1": __('Status 1'), "2": __('Status 2'), "3": __('Status 3')},
                            formatter: Table.api.formatter.status,
                            custom: {1: 'success', 2: 'warning', 3: 'danger'}
                        },
                        {
                            field: 'operate',
                            title: __('Operate'),
                            table: table,
                            events: Controller.api.events.operate,
                            formatter: Controller.api.formatter.operate
                        }
                    ]
                ]
            });
            var chartDom = document.getElementById('chart');
            var myChart = ECharts.init(chartDom);
            var option;
            option = {
                tooltip: {trigger: 'axis', axisPointer: {type: 'shadow'}},
                legend: {},
                grid: {left: '3%', right: '4%', bottom: '3%', containLabel: true},
                barMaxWidth: 30,
                color: ['#f39c12', '#e74c3c', '#18bc9c'],
                xAxis: {type: 'value'},
                yAxis: {type: 'category', data: Config.chartData.yAxis_data},
                series: [
                    {name: '结束', type: 'bar', stack: 'total', label: {show: true}, emphasis: {focus: 'series'}, data: Config.chartData.finish_data},
                    {name: '终止', type: 'bar', stack: 'total', label: {show: true}, emphasis: {focus: 'series'}, data: Config.chartData.termination_data},
                    {name: '运行', type: 'bar', stack: 'total', label: {show: true}, emphasis: {focus: 'series'}, data: Config.chartData.run_data},
                ]
            };
            option && myChart.setOption(option);
            window.onresize = function () {
                myChart.resize();
            };
            
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
            },
            formatter: {
                progress: function (value, row, index) {
                    return '<div class="progress active">'
                        + '<div class="progress-bar progress-bar-success progress-bar-striped" role="progressbar" style="width: '
                        + Math.round(value * 100)
                        + '%"> '
                        + '<b>'
                        + Math.round(value * 100)
                        + '%</b> '
                        + '</div></div>';
                },
                operate: function (value, row, index) {
                    if (row['status'] == 1) {
                        return '<a href="fastflow/flow/process/termination?ids=' + row['id'] + '" class="btn btn-xs btn-danger btn-termination" data-toggle="tooltip" title="强制终止" data-table-id="table" data-field-index="12" data-row-index="0" data-button-index="2" data-original-title="强制终止"><i class="fa fa-warning">强制终止</i></a>';
                    } else {
                        return '';
                    }
                },
            },
            events: {
                operate: {
                    'click .btn-termination': function (e, value, row, index) {
                        e.stopPropagation();
                        e.preventDefault();
                        var that = this;
                        var offsettop = $(that).offset().top - $(window).scrollTop();
                        var offsetleft = $(that).offset().left - $(window).scrollLeft() - 260;
                        if (offsettop + 154 > $(window).height()) {
                            offsettop = offsettop - 154;
                        }
                        if ($(window).width() < 480) {
                            offsettop = offsetleft = undefined;
                        }
                        var table = $(that).closest('table');
                        Layer.confirm(
                            __('强制终止会导致正在运行的单据流程强制结束，确认终止吗?'),
                            {
                                icon: 3,
                                title: __('Warning'),
                                offset: [offsettop, offsetleft],
                                shadeClose: true,
                                btn: [__('OK'), __('Cancel')]
                            },
                            function (index) {
                                var options = table.bootstrapTable('getOptions');
                                Fast.api.ajax({
                                    url: $(that).attr('href'),
                                    data: {},
                                }, function (data, ret) {
                                    Layer.alert(ret.msg);
                                    table.bootstrapTable('refresh');
                                    return false;
                                });
                                Layer.close(index);
                            }
                        );
                    }
                }
            },
        }
    };
    return Controller;
});