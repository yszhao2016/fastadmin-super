define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'flow/deptuser/getuserbydept' + location.search,
                    add_url: 'flow/deptuser/add',
                    edit_url: 'flow/deptuser/edit',
                    del_url: 'flow/deptuser/del',
                    multi_url: 'flow/deptuser/multi',
                    table: 'flow_department',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                columns: [
                    [
                        { field: 'state', checkbox: true, },
                        { field: 'id', title: 'ID', operate: false },
                        { field: 'department_id', title: __('department_id'), operate: 'in', formatter: Table.api.formatter.search, visible: false },
                        { field: 'dept_name', title: __('Department'), operate: false },
                        { field: 'username', title: __('Username') },
                        { field: 'nickname', title: __('Nickname') },
                        { field: 'email', title: __('Email') },
                        { field: 'logintime', title: __('Login time'), operate: false, formatter: Table.api.formatter.datetime, addclass: 'datetimerange', sortable: true },
                        { field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate }
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
            Controller.api.rendertree();
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
            rendertree: function (extendConfig) {
                require(['jstree'], function () {
                    //全选和展开
                    $(document).on("click", "#checkall", function () {
                        $("#depttree").jstree($(this).prop("checked") ? "check_all" : "uncheck_all");
                    });
                    $(document).on("click", "#expandall", function () {
                        $("#depttree").jstree($(this).prop("checked") ? "open_all" : "close_all");
                    });
                    $('#depttree').on("changed.jstree", function (e, data) {
                        $(".commonsearch-table input[name=department_id]").val(data.selected.join(","));
                        $("#table").bootstrapTable('refresh', {});
                        return false;
                    });
                    extendConfig = typeof extendConfig === 'undefined' ? {} : extendConfig;
                    var extendConfig = Object.assign(extendConfig,{
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
                        'plugins': ["types","checkbox",],
                        "core": {
                            "multiple": false,
                            'check_callback': true,
                            "data": Config.deptList
                        }
                    });
                    $('#depttree').jstree(extendConfig);
                });
            }
        }
    };
    return Controller;
});