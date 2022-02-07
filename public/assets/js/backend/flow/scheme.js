define(['jquery', 'bootstrap', 'backend', 'table', 'form',
    'template', 'flow'],
    function ($, undefined, Backend, Table, Form, Template, Flow) {
        var Controller = {
            index: function () {
                // 初始化表格参数配置
                Table.api.init({
                    extend: {
                        index_url: 'flow/scheme/index',
                        add_url: 'flow/scheme/add',
                        edit_url: 'flow/scheme/edit',
                        del_url: 'flow/scheme/del',
                        model_url: 'flow/bizscheme/index',
                        import_url: 'flow/scheme/import',
                        table: 'flow_scheme',
                    }
                });

                var table = $("#table");

                // 初始化表格
                table.bootstrapTable({
                    url: $.fn.bootstrapTable.defaults.extend.index_url,
                    pk: 'id',
                    singleSelect: true,
                    searchFormVisible: true,
                    sortName: 'id',
                    columns: [
                        [
                            { checkbox: true },
                            { field: 'id', title: 'id', visible: false, operate: false },
                            { field: 'isenble', title: '是否可用', visible: false, operate: false },
                            { field: 'flowcode', title: '流程代码', operate: "LIKE" },
                            { field: 'flowname', title: '流程名称', operate: "LIKE" },
                            {
                                field: 'buttons', title: __('Operate'), table: table, operate: false, events: Table.api.events.operate,
                                buttons: [{
                                    name: 'Flow',
                                    text: __('流程图设计'),
                                    icon: 'fa fa-list',
                                    classname: 'btn btn-xs btn-warning btn-addtabs',
                                    url: 'flow/scheme/flow?ids={id}'
                                }, {
                                    name: 'Model',
                                    text: __('字段管理'),
                                    icon: 'fa fa-list',
                                    classname: 'btn btn-xs btn-warning btn-addtabs',
                                    url: $.fn.bootstrapTable.defaults.extend.model_url + '?'
                                }],
                                formatter: Table.api.formatter.buttons
                            },
                            { field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate }
                        ]
                    ]
                });

                // 为表格绑定事件
                Table.api.bindevent(table);
                var submitForm = function (ids, layero) {
                    var options = table.bootstrapTable('getOptions');
                    var columns = [];
                    $.each(options.columns[0], function (i, j) {
                        if (j.field && !j.checkbox && j.visible && j.field != 'operate') {
                            columns.push(j.field);
                        }
                    });
                    var search = options.queryParams({});
                    $("input[name=search]", layero).val(options.searchText);
                    $("input[name=ids]", layero).val(ids);
                    $("input[name=filter]", layero).val(search.filter);
                    $("input[name=op]", layero).val(search.op);
                    $("input[name=columns]", layero).val(columns.join(','));
                    $("form", layero).submit();
                };
                $(document).on("click", ".btn-export", function () {
                    var ids = Table.api.selectedids(table);
                    var page = table.bootstrapTable('getData');
                    var all = table.bootstrapTable('getOptions').totalRows;
                    if (ids.length < 1) {
                        Toastr.warning('请选择一条数据');
                        return;
                    }
                    Layer.confirm("确认导出流程吗?<form action='" + Fast.api.fixurl("flow/scheme/export") + "' method='post' target='_blank'><input type='hidden' name='ids' value='' /><input type='hidden' name='filter' ><input type='hidden' name='op'><input type='hidden' name='search'><input type='hidden' name='columns'></form>", {
                        title: '导出数据',
                        btn: ["确定", "取消"],
                        success: function (layero, index) {
                            $(".layui-layer-btn a", layero).addClass("layui-layer-btn0");
                        }
                        , yes: function (index, layero) {
                            submitForm(ids.join(","), layero);
                            Layer.close(Layer.index);
                            return false;
                        }
                        ,
                        btn2: function (index, layero) {
                            Layer.close(Layer.index);
                        }
                    })
                });
            },
            add: function () {
                Form.api.bindevent($("form[role=form]"));
            },
            edit: function () {
                Form.api.bindevent($("form[role=form]"));
            },
            trans: function () {
                Form.api.bindevent($("form[role=form]"));
                $('#ok').click(function(e){
                    e.preventDefault();
                    //parent.parent.$('#table').bootstrapTable('refresh')
                    $.ajax({
                        url: "flow/scheme/trans" + location.search +"&userid="+$('#txtUser').val(),
                        type: 'post',
                        dataType: 'json',
                        data: {},
                        success: function (ret) {
                            parent.parent.$('#table').bootstrapTable('refresh')
                            parent.parent.layer.closeAll();
                        }, error: function (e) {
                            Backend.api.toastr.error(e.message);
                        }
                    });
                })
            },
            flow: function () {
                Flow.flowChart();
                Form.api.bindevent($("form[role=form]"));
            },
            line: function () {
                Form.api.bindevent($("form[role=form]"));
                var url = location.href;
                if (url.indexOf('ids') > 0) {
                    var ids = url.substr(url.indexOf('ids') + 1, url.length);
                    var value = ids.match(/\d+(\.\d+)?/)[0];
                }
                $.ajax({
                    url: "flow/scheme/line?ids=" + value,
                    type: 'post',
                    dataType: 'json',
                    data: {},
                    success: function (ret) {
                        Controller.api.rendertree(ret);
                    }, error: function (e) {
                        Backend.api.toastr.error(e.message);
                    }
                });
            },
            flowchart: function () {
                debugger;
                var options = JSON.parse(Config.flowcontent);
                var activityId = Config.activityId;
                var taskList = Config.taskList;
                var schemeContent = options;
                Flow.flowChart({
                    isprocessing: true,
                    activityId: Config.activityId,
                    taskList: Config.taskList
                });
            },
            api: {
                bindevent: function () {
                    Form.api.bindevent($("form[role=form]"));
                },
                formatter: {
                    flowName: function (value, row, index) {
                        url = "flow/scheme/edit/ids/" + row.id;

                        //方式一,直接返回class带有addtabsit的链接,这可以方便自定义显示内容
                        return '<a href="' + url + '" class="label label-success addtabsit" title="' + row.flowname + '">' + value + '</a>';
                    }
                },
                rendertree: function (content) {
                    require(['jstree'], function () {
                        $('#treeview').on("changed.jstree", function (e, data) {
                            //$(".commonsearch-table input[name=department_id]").val(data.selected.join(","));
                            //table.bootstrapTable('refresh', {});
                            return false;
                        });
                        var extendConfig = {};
                        Object.assign(extendConfig, {
                            "themes": {
                                "stripes": true
                            },
                            "checkbox": {
                                "keep_selected_style": false,
                            },
                            "types": {
                                "dept": {
                                    "icon": "fa fa-th",
                                },
                                "list": {
                                    "icon": "fa fa-list",
                                },
                                "link": {
                                    "icon": "fa fa-link",
                                },
                                "disabled": {
                                    "check_node": false,
                                    "uncheck_node": false
                                }
                            },
                            'plugins': ["types"],
                            "core": {
                                "multiple": false,
                                'check_callback': true,
                                "data": content
                            }
                        });
                        $('#treeview').jstree(extendConfig);
                    });
                }
            }
        };
        return Controller;
    });