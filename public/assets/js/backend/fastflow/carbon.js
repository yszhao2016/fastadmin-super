define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {
    var tableBill = $("#table-bill");
    var tableList = $("#table-list");
    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init();
            this.table.bill();
            this.table.list();
        },
        table: {
            bill: function () {
                tableBill.bootstrapTable({
                    url: 'fastflow/carbon/bill',
                    toolbar: '#toolbar-bill',
                    sortName: 'id',
                    search: false,
                    showExport: false,
                    columns: [
                        [
                            {field: 'bill_name', title: __('Bill_name')},
                            {
                                field: 'carbons', title: __('未读抄送'),width:100, formatter: function (value, row, index) {
                                   if (value == 0){
                                       return '';
                                   }
                                   else{
                                       return '<small class="badge bg-red">'+value+'</small>'
                                   }
                                }
                            },
                        ]
                    ]
                });

                // 为表格1绑定事件
                Table.api.bindevent(tableBill);
                tableBill.on('click-cell.bs.table',function (element, field, value, row) {
                    $("#box-list .form-commonsearch input[name='bill']").val(row['bill_table']);
                    $("#box-list .btn-refresh").trigger("click");
                })
            },
            list: function () {
                tableList.bootstrapTable({
                    url: 'fastflow/carbon/index',
                    extend: {
                        index_url: 'fastflow/carbon/index' + location.search,
                        del_url: 'fastflow/carbon/del',
                        multi_url: 'fastflow/carbon/multi',
                        table: 'fastflow_carbon',
                    },
                    toolbar: '#toolbar-list',
                    sortName: 'sendtime',
                    search: false,
                    sortOrder: 'desc',
                    columns: [
                        [
                            {checkbox: true},
                            {field: 'id', title: __('ID')},
                            {field: 'sender_name', title: __('Sender_name'), operate: false},
                            {field: 'bill_name', title: __('Bill_name'), operate: false,},
                            {field: 'bill', title: __('Bill'), visible: false},
                            {field: 'bill_id', title: __('Bill_id'), formatter: function (value, row, index) {
                                   if(row['bill_row_title'] != ''){
                                       return value + '(' + row['bill_row_title'] + ')';
                                   }
                                   else {
                                       return value;
                                   }
                                }},
                            {field: 'sender_id', title: __('Sender_id'), visible: false},
                            {field: 'sendtime', title: __('Sendtime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false},
                            {field: 'is_read', title: __('Is_read'), searchList: {"0": __('Is_read 0'),"1": __('Is_read 1')}, formatter: Table.api.formatter.label, custom: {'0': 'danger', '1': 'success'}},
                            {field: '', title: __('View'), operate: false, table: tableList,events: Controller.api.events.detail, formatter: Controller.api.formatter.detail},
                            {field: 'operate', title: __('Operate'), table: tableList, events: Table.api.events.operate, formatter: Table.api.formatter.operate},
                        ]
                    ]
                });

                // 为表格2绑定事件
                Table.api.bindevent(tableList);
                $('.btn-display-all').on('click', function(e){
                    $("#box-list .btn-default[type='reset']").trigger("click");
                });
            }
        },
        add: function () {
            Controller.api.bindevent();
        },
        edit: function () {
            Controller.api.bindevent();
        },
        detail: function(){
            let index = layer.load();
            $.ajax({
                type: "get",
                url: Config.controller_url + "/edit",
                data: {ids: Config.bill_id, way: 'detail'},
                success: function (data) {
                    if($('#edit-form',data).length == 0){
                        $('.alert').removeClass('hidden');
                    }
                    $('#fastflow-right-content #bill-edit').append($('#edit-form',data));
                    $('[name^="row"]',$('#edit-form','#fastflow-right-content')).attr('disabled','disabled');
                    $('button',$('#edit-form','#fastflow-right-content')).addClass('hidden');
                    Form.api.bindevent($("#edit-form"));
                    $('a.btn',$('#edit-form','#fastflow-right-content')).addClass('hidden');
                    layer.close(index);
                },
                error:function (e) {
                    layer.close(index);
                }
            });
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            },
            formatter: {
                detail: function (value, row, index) {
                    html = '<a href="fastflow/carbon/detail?id=' + row['id']  + '" class="btn btn-xs btn-success btn-dialog btn-detail" data-area=["80%","90%"] title="查阅"><i class="fa fa-envelope"></i></a>';
                    return html;
                },
            },
            events: {
                detail: {
                    'click .btn-detail': function (e, value, row, index) {
                        e.stopPropagation();
                        e.preventDefault();
                        var table = $("#table-bill");
                        var options = table.bootstrapTable('getOptions');
                        var ids = row[options.pk];
                        row = $.extend({}, row ? row : {}, {ids: ids});
                        Fast.api.open(Table.api.replaceurl($(this).attr('href'), row, table), $(this).data("original-title") || $(this).attr("title"), $(this).data() || {});
                        if (row['is_read'] == 0){
                            setTimeout(function (e) {
                                $("#box-list .btn-refresh").trigger("click")
                                tableBill.bootstrapTable('refresh');
                                Controller.api.renderbadge();
                            },1000);
                        }
                    },
                },
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
        }
    };
    return Controller;
});
