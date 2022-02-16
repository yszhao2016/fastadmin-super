define(['jquery', 'bootstrap', 'backend', 'table', 'form', 'echarts', 'echarts-theme'], function ($, undefined, Backend, Table, Form, Echarts) {

    var Controller = {
        index: function () {
            var option1 = {
                title: {
                    text: '订单统计',
                    subtext: ''
                },
                tooltip: {
                    trigger: 'axis',
                    formatter: "{b}<br>{a0} : {c0} 个 <br>{a1} : {c1} 元"
                },
                legend: {
                    data: ['订单额', '订单数']
                },
                toolbox: {
                    show: true,
                    feature: {
                        dataView: {show: true, readOnly: false},
                        magicType: {show: true, type: ['line', 'bar']},
                        restore: {show: true},
                        saveAsImage: {show: true}
                    }
                },
                calculable: true,
                xAxis: [
                    {
                        type: 'category',
                        data: Config.orderSaleCategory
                    }
                ],
                yAxis: [
                    {
                        type: 'value'
                    }
                ],
                series: [
                    {
                        name: '订单数',
                        type: 'line',
                        data: Config.orderSaleNums,
                        markPoint: {
                            data: [
                                {type: 'max', name: '最大值'},
                                {type: 'min', name: '最小值'}
                            ]
                        },
                        markLine: {
                            data: [
                                {type: 'average', name: '平均值'}
                            ]
                        }
                    },
                    {
                        name: '订单额',
                        type: 'bar',
                        smooth: true,
                        symbol: 'none',
                        data: Config.orderSaleAmount,
                        markPoint: {
                            data: [
                                {type: 'max', name: '最大值'},
                                {type: 'min', name: '最小值'}
                            ]
                        },
                        markLine: {
                            data: [
                                {type: 'average', name: '平均值'}
                            ]
                        }
                    }
                ]
            };

            var myChart1 = Echarts.init($('#echarts1')[0], 'walden');
            myChart1.setOption(option1);

            var option2 = {
                title: {
                    text: '付费占比',
                    subtext: '各模型付费占比',
                    x: 'center'
                },
                tooltip: {
                    trigger: 'item',
                    formatter: "{a} <br/>{b} : {c} ({d}%)"
                },
                legend: {
                    orient: 'vertical',
                    left: 'left',
                    data: Config.orderPercentCategory
                },
                series: [
                    {
                        name: '订单数',
                        type: 'pie',
                        center: ['50%', '50%'],
                        data: Config.orderPercentNums,
                        radius: [0, '30%'],

                        label: {
                            position: 'inner'
                        },
                        itemStyle: {
                            emphasis: {
                                shadowBlur: 10,
                                shadowOffsetX: 0,
                                shadowColor: 'rgba(0, 0, 0, 0.5)'
                            }
                        }
                    },
                    {
                        name: '订单额',
                        type: 'pie',
                        radius: ['40%', '55%'],
                        label2: {
                            formatter: '{a|{a}}{abg|}\n{hr|}\n  {b|{b}：}{c} 元  {per|{d}%}  ',
                            backgroundColor: '#eee',
                            borderColor: '#aaa',
                            borderWidth: 1,
                            borderRadius: 4,
                            rich: {
                                a: {
                                    color: '#999',
                                    lineHeight: 22,
                                    align: 'center'
                                },
                                hr: {
                                    borderColor: '#aaa',
                                    width: '100%',
                                    borderWidth: 0.5,
                                    height: 0
                                },
                                b: {
                                    fontSize: 12,
                                    lineHeight: 33
                                },
                                per: {
                                    color: '#eee',
                                    backgroundColor: '#334455',
                                    padding: [2, 4],
                                    borderRadius: 2
                                }
                            }
                        },
                        data: Config.orderPercentAmount
                    }
                ]
            };

            var myChart2 = Echarts.init($('#echarts2')[0], 'walden');
            myChart2.setOption(option2);

            $(window).on("resize", function () {
                myChart1.resize();
                myChart2.resize();
            });

            // 基于准备好的dom，初始化echarts实例
            var myChart3 = Echarts.init($('#echarts3')[0], 'walden');

            // 指定图表的配置项和数据
            var option3 = {
                title: {
                    text: '',
                    subtext: ''
                },
                tooltip: {
                    trigger: 'axis'
                },
                legend: {},
                toolbox: {
                    show: true,
                    feature: {
                        dataView: {show: true, readOnly: false},
                        magicType: {show: true, type: ['line', 'bar']},
                        restore: {show: true},
                        saveAsImage: {show: true}
                    }
                },
                calculable: true,
                xAxis: {
                    type: 'category',
                    boundaryGap: false,
                    data: Config.orderSaleCategory
                },
                yAxis: {},
                grid: [{
                    left: 'left',
                    top: 'top',
                    right: '10',
                    bottom: 30
                }],
                series: [
                    {
                        name: "交易额",
                        type: 'line',
                        smooth: true,
                        areaStyle: {
                            normal: {}
                        },
                        lineStyle: {
                            normal: {
                                width: 1.5
                            }
                        },
                        data: Config.orderSaleAmount
                    }]
            };

            // 使用刚指定的配置项和数据显示图表。
            myChart3.setOption(option3);

            $(window).resize(function () {
                myChart3.resize();
            });

            if ($("#echarts4").size() > 0) {
                // 基于准备好的dom，初始化echarts实例
                var myChart4 = Echarts.init($('#echarts4')[0], 'walden');

                // 指定图表的配置项和数据
                var option4 = {
                    title: {
                        text: '',
                        subtext: ''
                    },
                    tooltip: {
                        trigger: 'axis'
                    },
                    legend: {},
                    toolbox: {
                        show: true,
                        feature: {
                            dataView: {show: true, readOnly: false},
                            magicType: {show: true, type: ['line', 'bar']},
                            restore: {show: true},
                            saveAsImage: {show: true}
                        }
                    },
                    calculable: true,
                    xAxis: {
                        type: 'category',
                        boundaryGap: false,
                        data: Config.adminArchivesListCategory
                    },
                    yAxis: {},
                    grid: [{
                        left: 'left',
                        top: 'top',
                        right: '10',
                        bottom: 30
                    }],
                    series: Config.adminArchivesListData
                };

                // 使用刚指定的配置项和数据显示图表。
                myChart4.setOption(option4);

                $(window).resize(function () {
                    myChart4.resize();
                });
            }

            $(".datetimerange").data("callback", function (start, end) {
                var date = start.format(this.locale.format) + " - " + end.format(this.locale.format);
                $(this.element).val(date);
                var model_id = $(this.element).closest("form").find(".model_id").val();
                refresh_echart($(this.element).data("type"), date, model_id);
            });

            $(".model_id").on("change", function () {
                var input = $(this).closest("form").find(".datetimerange");
                var type = $(input).data("type");
                var date = $(input).val();
                var model_id = $(this).val();
                refresh_echart(type, date, model_id);
            });

            Form.api.bindevent($("#form1"));
            Form.api.bindevent($("#form2"));

            var si = {};
            var refresh_echart = function (type, date, model_id) {
                si[type] && clearTimeout(si[type]);
                si[type] = setTimeout(function () {
                    Fast.api.ajax({
                        url: 'cms/statistics/index',
                        data: {date: date, type: type, model_id: model_id},
                        loading: false
                    }, function (data) {
                        if (type == 'sale') {
                            option1.xAxis.data = data.orderSaleCategory;
                            option1.series[0].data = data.orderSaleNums;
                            option1.series[1].data = data.orderSaleAmount;
                            myChart1.clear();
                            myChart1.setOption(option1, true);
                        } else if (type == 'percent') {
                            option2.legend.data = data.orderPercentCategory;
                            option2.series[0].data = data.orderPercentNums;
                            option2.series[1].data = data.orderPercentAmount;
                            myChart2.clear();
                            myChart2.setOption(option2, true);
                        } else if (type == 'order') {
                            option3.xAxis.data = data.category;
                            option3.series[0].data = data.data;
                            myChart3.clear();
                            myChart3.setOption(option3, true);
                        } else if (type == 'archives') {
                            option4.xAxis.data = data.category;
                            option4.series = data.data;
                            myChart4.clear();
                            myChart4.setOption(option4, true);
                        }
                        return false;
                    });
                }, 50);
            };

            //点击按钮
            $(document).on("click", ".btn-filter", function () {
                var label = $(this).text();
                var obj = $(this).closest("form").find(".datetimerange").data("daterangepicker");
                var dates = obj.ranges[label];
                obj.startDate = dates[0];
                obj.endDate = dates[1];

                obj.clickApply();
            });

            //点击刷新
            $(document).on("click", "a.btn-refresh", function () {
                if ($(this).data("type")) {
                    refresh_echart($(this).data("type"), "");
                } else {
                    var input = $(this).closest("form").find(".datetimerange");
                    var type = $(input).data("type");
                    var date = $(input).val();
                    var model_id = $(this).closest("form").find(".model_id").val();
                    refresh_echart(type, date, model_id);
                }
            });

            //每隔一分钟定时刷新图表
            setInterval(function () {
                $(".btn-refresh").trigger("click");
            }, 60000);

            //选项卡切入事件
            $(document).on("click", "#resetecharts", function () {
                setTimeout(function () {
                    $(window).trigger("resize");
                }, 50);
            });
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
