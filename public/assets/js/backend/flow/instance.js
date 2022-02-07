define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {
    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'flow/instance/index',
                    add_url: 'flow/instance/add',
                    edit_url: 'flow/instance/edit',
                    del_url: 'flow/instance/del'
                }
            });
            //绑定事件
            $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
                var panel = $($(this).attr("href"));
                if (panel.size() > 0) {
                    Controller.table[panel.attr("id")].call(this);
                    $(this).on('click', function (e) {
                        $($(this).attr("href")).find(".btn-refresh").trigger("click");
                    });
                }
                //移除绑定的事件
                $(this).unbind('shown.bs.tab');
            });

            //必须默认触发shown.bs.tab事件
            $('ul.nav-tabs li.active a[data-toggle="tab"]').trigger("shown.bs.tab");
        },
        table: {
            first: function () {
                // 表格1
                var table1 = $("#table1");
                table1.bootstrapTable({
                    url: 'flow/instance/table1',
                    toolbar: '#toolbar1',
                    sortName: 'createtime',
                    sortOrder: "desc",
                    search: false,
                    columns: [
                        [
                            { field: 'id', title: __('Id'), visible: false, operate: false },
                            { field: 'bizobjectid', title: __('Bizobjectid'), visible: false, operate: false },
                            { field: 'instancecode', title: __('Instancecode'),operate: "LIKE", },
                            { field: 'nickname', title: __('Originator') },
                            { field: 'instancestatus', title: __('Status'), formatter: Controller.api.formatter.instancestatus, searchList: [{ id: 1, name: '审批中' }, { id: 2, name: '已完成' }] },
                            { field: 'scheme', title: __('流程id') },
                            { field: 'createtime', title: __('Createdtime'), operate: 'RANGE', addclass: 'datetimerange' },
                            { field: 'completedtime', title: __('Completedtime'), operate: 'RANGE', addclass: 'datetimerange' },
                        ]
                    ]
                });

                // 为表格1绑定事件
                Table.api.bindevent(table1);
            },
            second: function () {
                // 表格2
                var table2 = $("#table2");
                table2.bootstrapTable({
                    url: 'flow/instance/table2',
                    extend: {
                        index_url: '',
                        add_url: '',
                        edit_url: '',
                        del_url: '',
                        multi_url: '',
                        table: '',
                    },
                    toolbar: '#toolbar2',
                    sortName: 'createtime',
                    sortOrder: "desc",
                    search: false,
                    onLoadSuccess: function (data) {
                        var data = $('#table2').bootstrapTable('getData', true);
                        //合并单元格
                        Controller.api.mergeCells(data, ["instancecode"], 1, $('#table2'));
                    },
                    columns: [
                        [
                            { field: 'bizobjectid', visible: false, operate: false },
                            { field: 'instancecode', title: __('流水号') ,operate: "LIKE",},
                            { field: 'id', title: __('id'), visible: false, operate: false },
                            { field: 'instanceid', title: __('instanceid'), visible: false, operate: false },
                            { field: 'bizscheme', title: __('bizscheme'), visible: false, operate: false },
                            { field: 'flowcode', visible: false, operate: false },
                            { field: 'flowname', title: '流程名称',operate: "LIKE" },
                            { field: 'stepid', visible: false, operate: false },
                            { field: 'stepname', title: '步骤', operate: false },
                            { field: 'receivename', title: __('receiveid') },
                            { field: 'delegatename', title: __('委托人') },
                            { field: 'status', title: '状态', formatter: Controller.api.formatter.status, searchList: [{ id: 1, name: '审批中' }, { id: 2, name: '已完成' },{ id: 3, name: '已取消' }] },
                            { field: 'createtime', title: __('createtime'), operate: 'RANGE', addclass: 'datetimerange' },
                            { field: 'completedtime', title: __('Completedtime'), operate: 'RANGE', addclass: 'datetimerange' },
                            {
                                field: 'operate', title: __('Operate'), table: table2, events: Table.api.events.operate, buttons: [{
                                    name: 'detail',
                                    text: __('Trans'),
                                    icon: 'fa fa-list',
                                    classname: 'btn btn-info btn-xs btn-error btn-dialog',
                                    url: 'flow/instance/trans?ids={id}',
                                    visible: function (row) {
                                        if (row.status != '2' && row.status != '3') {
                                            return true;
                                        } else {
                                            return false;
                                        }
                                    }
                                }], formatter: Table.api.formatter.buttons
                            }
                        ]
                    ]
                });

                // 为表格2绑定事件
                Table.api.bindevent(table2);
            }
        },
        add: function () {
            Controller.api.bindevent();
        },
        edit: function () {
            Controller.api.bindevent();
        },
        trans: function () {
            Controller.api.bindevent();
            $('#ok').click(function () {
                var that = this;
                $("form[role=form]").attr('action', 'flow/instance/trans?ids=' + Config.taskid);
                $(that).closest("form").trigger("submit");
            });
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            },
            formatter: {
                status: function (value, row, index) {
                    var res = '';
                    var className = '';
                    switch (row.status) {
                        case 0:
                        case 1:
                            res = '审批中';
                            className = 'info';
                            break;
                        case 2:
                            res = '已完成';
                            className = 'primary';
                            break;
                        case 3:
                            res = '已取消';
                            className = 'success';
                            break;
                        default:
                            break;
                    }
                    return '<a href="javascript:;" class="searchit" data-toggle="tooltip" "><span class="label label-' + className + '">' + res + '</span></a>';
                },
                instancestatus: function (value, row, index) {
                    var res = '';
                    var className = '';
                    switch (row.instancestatus) {
                        case 0:
                        case 1:
                            res = '审批中';
                            className = 'info';
                            break;
                        case 2:
                            res = '已完成';
                            className = 'primary';
                            break;
                        case 3:
                            res = '已取消';
                            className = 'success';
                            break;
                        default:
                            break;
                    }
                    return '<a href="javascript:;" class="searchit" data-toggle="tooltip" "><span class="label label-' + className + '">' + res + '</span></a>';
                },
                browser: function (value, row, index) {
                    //这里我们直接使用row的数据
                    return '<a class="btn btn-xs btn-browser">' + value + '</a>';
                },
            },
            mergeCells: function (data, fieldNames, colspan, target) {
                for (let g = 0; g < fieldNames.length; g++) {
                    var fieldName = fieldNames[g];
                    if (data.length == 0) {
                        alert("不能传入空数据");
                        return;
                    }
                    var numArr = [];
                    var value = data[0][fieldName];
                    var num = 0;
                    for (var i = 0; i < data.length; i++) {
                        if (value != data[i][fieldName]) {
                            numArr.push(num);
                            value = data[i][fieldName];
                            num = 1;
                            continue;
                        }
                        num++;
                    }
                    if (typeof (value) !== "undefined" && value !== "") {
                        numArr.push(num);
                    }
                    var merIndex = 0;
                    for (var i = 0; i < numArr.length; i++) {
                        $(target).bootstrapTable('mergeCells', { index: merIndex, field: fieldName, colspan: colspan, rowspan: numArr[i] })
                        merIndex += numArr[i];
                    }
                }
            },
        }
    };
    return Controller;
});