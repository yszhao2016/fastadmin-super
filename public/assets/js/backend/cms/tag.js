define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    //设置弹窗宽高
    Fast.config.openArea = ['80%', '80%'];

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'cms/tag/index',
                    add_url: 'cms/tag/add',
                    edit_url: 'cms/tag/edit',
                    del_url: 'cms/tag/del',
                    multi_url: 'cms/tag/multi',
                    table: 'cms_tag',
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
                        {checkbox: true},
                        {field: 'id', sortable: true, title: __('Id')},
                        {field: 'name', sortable: true, title: __('Name'), operate: 'like'},
                        {field: 'seotitle', sortable: true, title: __('Seotitle'), operate: 'like'},
                        {field: 'nums', sortable: true, title: __('Nums')},
                        {field: 'autolink', title: __('Autolink'), searchList: {"1": __('Yes'), "0": __('No')}, table: table, formatter: Table.api.formatter.toggle},
                        {
                            field: 'url', title: __('Url'), formatter: function (value, row, index) {
                                return '<a href="' + value + '" target="_blank" class="btn btn-default btn-xs"><i class="fa fa-link"></i></a>';
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
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});
