
define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            
            Table.api.init({
                extend: {
                    index_url: 'fastflow/flow/message/index' + location.search,
                    add_url: 'fastflow/flow/message/add',
                    edit_url: 'fastflow/flow/message/edit',
                    del_url: 'fastflow/flow/message/del',
                    multi_url: 'fastflow/flow/message/multi',
                    import_url: 'fastflow/flow/message/import',
                    table: 'fastflow_message',
                }
            });

            var table = $("#table");

            
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id')},
                        {field: 'name', title: __('Name'), operate: 'LIKE'},
                        {field: 'key', title: __('Key'), operate: 'LIKE'},
                        {field: 'status', title: __('Status'), searchList: {"0":__('Status 0'),"1":__('Status 1')}, formatter: Controller.api.formatter.status, events: Controller.api.events.status, custom: {0: 'success', 1: 'danger'}},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
                    ]
                ]
            });

            
            Table.api.bindevent(table);

            $('.btn-add').click(function () {
                
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
            formatter: {
                status: function (value, row, index) {
                    return '<a href="javascript:;" class="btn btn-xs btn-status ' + (value == 0 ? 'btn-danger' : 'btn-success') + '" title="点击切换" data-table-id="table" style="margin-right: 4px;font-size: 90%;">' + (value == 0 ? '禁用(点击启用)' : '启用(点击禁用)') + '</a>';
                },
            },
            events: {
                status: {
                    'click .btn-status': function (e, value, row, index) {
                        e.stopPropagation();
                        e.preventDefault();
                        var that = this;
                        var top = $(that).offset().top - $(window).scrollTop();
                        var left = $(that).offset().left - $(window).scrollLeft() - 260;
                        if (top + 154 > $(window).height()) {
                            top = top - 154;
                        }
                        if ($(window).width() < 480) {
                            top = left = undefined;
                        }
                        var table = $(that).closest('table');
                        if (value == 0) {
                            $(that).data('params', {'status': 1});
                            Table.api.multi(undefined, row['id'], table, that);
                        } else {
                            $(that).data('params', {'status': 0});
                            Layer.confirm(
                                __('确定禁用吗?'),
                                {
                                    icon: 3,
                                    title: __('Warning'),
                                    offset: [top, left],
                                    shadeClose: true,
                                    btn: [__('OK'), __('Cancel')]
                                },
                                function (index) {
                                    var options = table.bootstrapTable('getOptions');
                                    Table.api.multi(undefined, row['id'], table, that);
                                    Layer.close(index);
                                }
                            );
                        }

                    }
                },
            }
        }
    };
    return Controller;
});