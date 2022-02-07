define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'fastflow/bill/fastflow_demon_leave/index' + location.search,
                    add_url: 'fastflow/bill/fastflow_demon_leave/add',
                    edit_url: 'fastflow/bill/fastflow_demon_leave/edit',
                    del_url: 'fastflow/bill/fastflow_demon_leave/del',
                    multi_url: 'fastflow/bill/fastflow_demon_leave/multi',
                    import_url: 'fastflow/bill/fastflow_demon_leave/import',
                    table: 'fastflow_demon_leave',
                }
            });

            var table = $("#table");

            table.on('post-common-search.bs.table', function (event, table) {
                var form = $("form", table.$commonsearch);
                $("input[name='uid']", form).addClass("selectpage").data("source", "fastflow/flow/flow/getSelectpageWorkers?scope=1").data("primaryKey", "id").data("field", "createuser_id").data("orderBy", "id asc");
                Form.events.selectpage(form);
            });

            table.on('post-body.bs.table', function (e, data) {
                $('#table').bootstrapTable('expandAllRows');
                $('#table').bootstrapTable('collapseAllRows');
                Controller.api.renderbadge();
            });

            $('.btn-expandall').click(function (e) {
                $('#table').bootstrapTable('expandAllRows');
            });

            $('.btn-collapseall').click(function (e) {
                $('#table').bootstrapTable('collapseAllRows');
            });


            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                detailView: true,
                detailFormatter: Controller.api.formatter.detail,
                rowStyle: function (row, index) {
                    return {
                        css: {
                            border: '1px solid #eaeaea'
                        }
                    };
                },
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('ID'), sortable: true, formatter: function (value, row, index) {return '<span class="label label-success" style="font-size: 94%;margin-right: 4px;"><i class="fa fa-check-circle" style="margin-right: 4px;"></i>' + value + '</span>';}},
                        {field: 'type', title: __('Type'), searchList: {"1":__('Type 1'),"2":__('Type 2'),"3":__('Type 3')}, formatter: Table.api.formatter.normal},
                        {field: 'days', title: __('Days')},
                        {field: 'reason', title: __('Reason'), operate: 'LIKE'},
                        {field: 'starttime', title: __('Starttime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false},
                        {field: 'endtime', title: __('Endtime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false},
                        {field: 'addr', title: __('Addr'), operate: 'LIKE'},
                        {field: 'phone', title: __('Phone'), operate: 'LIKE'},
                        {field: 'file', title: __('File'), operate: false},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate},
                        {field: 'uid', title: __('Uid'), visible: false},
                        {field: 'createusername', title: __('流程发起人'), operate: false},
                        {field: 'status', title: __('状态'), searchList: {"0": __('Status 0'),"1": __('Status 1'),"2": __('Status 2'),"3": __('Status 3')}, formatter: Table.api.formatter.status, custom: {'0': 'warning', '1': 'success', '2': 'info', '3': 'danger'}},
                        {field: 'flow_progress', title: __('流程进度'), operate: false, formatter: Controller.api.formatter.progress},
                        {field: '', title: __('流程控制'), operate: false, table: table, events: Controller.api.events.flowinfo, formatter: Controller.api.formatter.flowinfo},
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
            $(document).on("click", ".btn-showcancheck", function () {
                table.bootstrapTable('refresh', {url:$.fn.bootstrapTable.defaults.extend.index_url+'&show=cancheck'});
                $(this).addClass('disabled');
                $(".btn-showall").removeClass('disabled');
                return false;
            });
            // 显示全部
            $(document).on("click", ".btn-showall", function () {
                table.bootstrapTable('refresh', {url:$.fn.bootstrapTable.defaults.extend.index_url});
                $(this).addClass('disabled');
                $(".btn-showcancheck").removeClass('disabled');
                return false;
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
            },
        renderbadge: function () {
                $.ajax({
                    type: "POST",
                    url: "fastflow/flow/bill/getBadge",
                    data: {},
                    dataType: "json",
                    success: function (data) {
                        if (data['code'] == 1) {
                            $('.fastflow-badge', window.parent.document).remove();
                            data['data'].forEach(function (item) {
                                if (item['count'] > 0) {
                                    $('a[addtabs=' + item['id'] + ']', window.parent.document).append('<span class="pull-right-container fastflow-badge" style="margin-right: 20px"> <small class="' + item['shape'] + ' pull-right ' + item['color'] + '">' + item['show'] + '</small></span>');
                                }
                            });
                        }
                    }
                });
            },
            formatter: {
                progress: function (value, row, index) {
                    return '<div class="progress active">'
                        + '<div class="progress-bar progress-bar-success progress-bar-striped" role="progressbar" style="width: '
                        + Math.round(value * 100)
                        +'%"> '
                        + '<b>'
                        + Math.round(value * 100)
                        +'%</b> '
                        + '</div></div>';
                },
                flowinfo: function (value, row, index) {
                    let html = '';
                    if (row['flow_auth']['code'] == -3 || row['flow_auth']['code'] == -1 || row['flow_auth']['code'] == 2 || row['flow_auth']['code'] == 3) {
                        let labelcolor = {
                            '-3': 'label-danger',
                            '-1': 'label-warning',
                            '2': 'label-success',
                            '3': 'label-warning'
                        };
                        html = '<span class="label ' + labelcolor[row['flow_auth']['code']] + '" style="font-size: 94%;margin-right: 4px;"><i class="fa fa-info-circle" style="margin-right: 4px;"></i>' + row['flow_auth']['msg'] + '</span>'
                            + '<a href="fastflow/flow/flow/detail?bill=' + row['bill'] + '&bill_id=' + row['id'] + '" class="btn btn-xs btn-success btn-dialog btn-detail" title="查看" data-table-id="table" style="margin-right: 4px;font-size: 90%;"><i class="fa fa-list" style="margin-right: 4px;"></i>查看</a>'
                            + '<a href="fastflow/flow/flow/viewer?bill=' + row['bill'] + '&bill_id=' + row['id'] + '" class="btn btn-xs btn-success btn-dialog" title="流程图" data-table-id="table" style="margin-right: 4px;font-size: 90%;"><i class="fa fa-sitemap" style="margin-right: 4px;"></i>流程图</a>';
                    } else if (row['flow_auth']['code'] == -2) {
                        html = '<span class="label label-warning" style="font-size: 94%;margin-right: 4px;"><i class="fa fa-info-circle" style="margin-right: 4px;"></i>' + row['flow_auth']['msg'] + '</span>';
                    } else if (row['flow_auth']['code'] == 0) {
                        html = '<a href="fastflow/flow/run/start?bill=' + row['bill'] + '&bill_id=' + row['id'] + '" class="btn btn-xs btn-info btn-dialog btn-start" title="' + row['flow_auth']['msg'] + '" data-table-id="table" style="margin-right: 4px;font-size: 90%;"><i class="fa fa-calendar-plus-o" style="margin-right: 4px;"></i>' + row['flow_auth']['msg'] + '</a>';
                    } else if (row['flow_auth']['code'] == 1) {
                        needCheckCount = 0;
                        row['runthread_info'] = Array.from(row['runthread_info']);
                        row['runthread_info'].forEach(function (item) {
                            if (item['auth_info'] !== false) {
                                needCheckCount += 1;
                            }
                        });
                        html = '<a href="javascript:;" class="btn btn-xs btn-success btn-cancheck" style="position:relative;font-size: 90%;margin-right: 4px;"><i class="fa fa-info-circle" style="margin-right: 4px;"></i><span class="label label-danger" style="position:absolute;text-align:center;font-size:12px;padding:3px 3px;right: -2px;top:-6px;border-radius:1em;width: 18px;height: 18px">'
                            + needCheckCount + '</span>'
                            + row['flow_auth']['msg'] + '</a>'
                            + '<a href="fastflow/flow/flow/detail?bill=' + row['bill'] + '&bill_id=' + row['id'] + '" class="btn btn-xs btn-success btn-dialog btn-detail" title="查看" data-table-id="table" style="margin-right: 4px;font-size: 90%;"><i class="fa fa-list" style="margin-right: 4px;"></i>查看</a>'
                            + '<a href="fastflow/flow/flow/viewer?bill=' + row['bill'] + '&bill_id=' + row['id'] + '" class="btn btn-xs btn-success btn-dialog" title="流程图" data-table-id="table" style="margin-right: 4px;font-size: 90%;"><i class="fa fa-sitemap" style="margin-right: 4px;"></i>流程图</a>';
                    }
                    return html;
                },
                detail: function (index, row, e) {
                    if (row['runthread_info'] == -1 || row['runthread_info'] == 2) {
                        $(e).parent().prev().find('.detail-icon').trigger('click').addClass('hidden');
                        return '';
                    }
                    detailtable = '<table style="width: 96%;margin-left: 4%;" class="table table-hover detailtable"><tbody data-listidx="1"><tr style="background-color: #e9f4fd;"><th style="width: 60px;text-align: center">步骤编号</th><th style="text-align: center;">步骤名称</th><th style="text-align: center;">审批人</th><th style="text-align: center;">会签</th><th style="text-align: center;">回退</th><th style="text-align: center;">上一步骤</th><th style="text-align: center;">下一步骤</th><th style="text-align: center;">被代理人(组)</th><th style="text-align: center;">代理人(组)</th><th style="text-align: center;">审批</th></tr>';
                    row['runthread_info'] = Array.from(row['runthread_info']);
                    row['runthread_info'].forEach(function (item) {
                        checkButton = '';
                        if (item['auth_info'] !== false) {
                            checkButton = (item['auth_info']['agency'] == true ? '<span class="label label-info" style="font-size: 94%;margin-right: 12px;">代理</span>' : '')
                                + '<a href="fastflow/flow/run/agree?thread_id=' + item["id"] + '" class="btn btn-xs btn-success-light btn-dialog" title="同意" data-table-id="table" style="margin-right: 4px;font-size: 90%;"><i class="fa fa-check-square" style="margin-right: 4px;"></i>同意</a>'
                                + (item['can_back'] == 1 ? '<a href="fastflow/flow/run/back?thread_id=' + item["id"] + '" class="btn btn-xs btn-danger-light btn-dialog" title="驳回" data-table-id="table" style="margin-right: 4px;font-size: 90%;"><i class="fa fa-reply" style="margin-right: 4px;"></i>驳回</a>' : '')
                                + (item['can_sign'] == 1 ? '<a href="fastflow/flow/run/sign?thread_id=' + item["id"] + '" class="btn btn-xs btn-info-light btn-dialog" title="会签" data-table-id="table" style="margin-right: 4px;font-size: 90%;"><i class="fa fa-pencil" style="margin-right: 4px;"></i>会签</a>' : '');
                        } else {
                            checkButton = '<span class="label label-warning" style="font-size: 94%;margin-right: 4px;"><i class="fa fa-info-circle" style="margin-right: 4px;"></i>无审批权限</span>';
                        }
                        checkUser = '';
                        item['check_worker_list'].forEach(function (worker) {
                            checked = false;
                            for (i = 0; i < item['checked_worker_list'].length; i++) {
                                if (worker == item['checked_worker_list'][i]) {
                                    checked = true;
                                    break;
                                }
                            }
                            if (checked) {
                                checkUser += '<span class="text-success">' + worker + '</span>，';
                            } else {
                                checkUser += worker+'，';
                            }
                        });
                        detailtable += '<tr style="text-align: center"><td>' + item['id'] + '</td><td>' + item['name'] + '</td><td>'
                                  + (item['checkmode'] == 1 ? '<span class="text-info">联合审批：</span>' : '<span class="text-info">任一审批：</span>')
                                  + checkUser.slice(0, checkUser.length - 1)
                                  + '</td><td>'
                                  + (item['is_sign'] == 1 ? '<span class="text-warning">是</span>' : '否')
                                  + '</td><td>'
                                  + (item['is_back'] == 1 ? '<span class="text-warning">是</span>' : '否')
                                  + '</td><td>'
                                  + item['pre_step']
                                  + '</td><td>'
                                  + item['next_step']
                                  + '</td><td>'
                                  + (item['agency_info'] !== null ? item['agency_info']['principal_name'] : '')
                                  + '</td><td>'
                                  + (item['agency_info'] !== null ? item['agency_info']['agent_name'] : '')
                                  + '</td><td>'
                                  + checkButton
                                  + '</td></tr>';
                    });
                    detailtable += '</tbody></table>';
                    return detailtable;
                },
            },
            events: {
                flowinfo: {
                    'click .btn-start': function (e, value, row, index) {
                        e.stopPropagation();
                        e.preventDefault();
                        var table = $(this).closest('table');
                        var options = table.bootstrapTable('getOptions');
                        var ids = row[options.pk];
                        row = $.extend({}, row ? row : {}, {ids: ids});
                        $(this).data('area', [$(window).width() > 1200 ? '1000px' : '95%', $(window).height() > 800 ? '700px' : '95%']);
                        Fast.api.open(Table.api.replaceurl($(this).attr('href'), row, table), $(this).data("original-title") || $(this).attr("title"), $(this).data() || {});
                    },
                    'click .btn-cancheck': function (e, value, row, index) {
                        e.stopPropagation();
                        $('#table').bootstrapTable('expandRow',index);
                    }
                }
            },
        }
    };
    return Controller;
});