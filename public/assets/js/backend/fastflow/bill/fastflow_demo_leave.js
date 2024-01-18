define(['jquery', 'bootstrap', 'backend', 'table', 'form', 'fastflowbase'], function ($, undefined, Backend, Table, Form, FastflowBase) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'fastflow/bill/fastflow_demo_leave/index' + location.search,
                    add_url: 'fastflow/bill/fastflow_demo_leave/add',
                    edit_url: 'fastflow/bill/fastflow_demo_leave/edit',
                    del_url: 'fastflow/bill/fastflow_demo_leave/del',
                    multi_url: 'fastflow/bill/fastflow_demo_leave/multi',
                    import_url: 'fastflow/bill/fastflow_demo_leave/import',
                    table: 'fastflow_demo_leave',
                }
            });

            var table = $("#table");

            table.on('post-common-search.bs.table', function (event, table) {
                var form = $("form", table.$commonsearch);
                $("input[name='admin_id']", form).addClass("selectpage").data("source", "fastflow/flow/flow/getSelectpageWorkers?scope=1").data("primaryKey", "id").data("field", "createuser_id").data("orderBy", "id asc");
                Form.events.selectpage(form);
            });

            table.on('post-body.bs.table', function (e, data) {
                $('#table').bootstrapTable('expandAllRows');
                $('#table').bootstrapTable('collapseAllRows');
                FastflowBase.api.renderbadge();
            });

			$(window).resize( function  () {
                table.bootstrapTable('resetView');
            });

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                detailView: true,
                detailFormatter: FastflowBase.api.formatter.detail,
				fixedColumns: true,
                fixedRightNumber: 1,
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
                        {field: 'id', title: __('ID'), sortable: true, formatter: FastflowBase.api.formatter.id},
                        {
                            field: 'type',
                            title: __('Type'),
                            searchList: {"1": __('Type 1'), "2": __('Type 2'), "3": __('Type 3')},
                            formatter: Table.api.formatter.normal
                        },
                        {field: 'days', title: __('Days')},
                        {field: 'reason', title: __('Reason'), operate: 'LIKE'},
                        {
                            field: 'starttime',
                            title: __('Starttime'),
                            operate: 'RANGE',
                            addclass: 'datetimerange',
                            autocomplete: false
                        },
                        {
                            field: 'endtime',
                            title: __('Endtime'),
                            operate: 'RANGE',
                            addclass: 'datetimerange',
                            autocomplete: false
                        },
                        {field: 'addr', title: __('Addr'), operate: 'LIKE'},
                        {field: 'phone', title: __('Phone'), operate: 'LIKE'},
                        {field: 'file', title: __('File'), operate: false, formatter: Table.api.formatter.file},
                        {
                            field: 'operate',
                            title: __('Operate'),
                            table: table,
                            events: Table.api.events.operate,
                            formatter: Table.api.formatter.operate
                        },
                        {field: 'admin_id', title: __('Admin_id'), visible: false},
                        {field: 'createusername', title: __('流程发起人'), operate: false},
                        {
                            field: 'status',
                            title: __('状态'),
                            searchList: {
                                "0": __('Status 0'),
                                "1": __('Status 1'),
                                "2": __('Status 2'),
                                "3": __('Status 3')
                            },
                            formatter: Table.api.formatter.status,
                            custom: {'0': 'warning', '1': 'success', '2': 'info', '3': 'danger'}
                        },
                        {
                            field: 'flow_progress',
                            title: __('流程进度'),
                            operate: false,
                            formatter: FastflowBase.api.formatter.progress
                        },
                        {
                            field: '',
                            title: __('流程控制'),
							width:"250",
                            operate: false,
                            table: table,
                            events: FastflowBase.api.events.flowinfo,
                            formatter: FastflowBase.api.formatter.flowinfo
                        },
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
            FastflowBase.api.bindevent(table);
        },
        add: function () {
            Controller.api.bindevent();
        },
        edit: function () {
            Config.billFileds.forEach(function (item) {
                if ($.inArray(item, Config.canEditFields) === -1) {
                    $('[name="row[' + item + ']"]').attr('disabled','disabled');
                }
            });
            Controller.api.bindevent();
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            },
        }
    };
    return Controller;
});