define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    //设置弹窗宽高
    Fast.config.openArea = ['80%', '80%'];

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'cms/order/index',
                    add_url: 'cms/order/add',
                    edit_url: 'cms/order/edit',
                    del_url: 'cms/order/del',
                    multi_url: 'cms/order/multi',
                    table: 'cms_order',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                fixedColumns: true,
                fixedRightNumber: 1,
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id')},
                        {field: 'orderid', title: __('Orderid')},
                        {field: 'user_id', title: __('User_id'), formatter: Table.api.formatter.search},
                        {field: 'user.nickname', title: __('Nickname'), operate: false},
                        {field: 'archives_id', title: __('Archives_id'), formatter: Table.api.formatter.search},
                        {field: 'archives.title', title: __('Archives_title'), operate: false},
                        {field: 'title', title: __('Title'), formatter: Table.api.formatter.search, operate: 'like'},
                        {
                            field: 'archives.url', title: __('Url'), formatter: function (value, row, index) {
                                return '<a href="' + value + '" target="_blank" class="btn btn-default btn-xs"><i class="fa fa-link"></i></a>';
                            }
                        },
                        {field: 'amount', title: __('Amount'), operate: 'BETWEEN'},
                        {field: 'payamount', title: __('Payamount'), operate: 'BETWEEN'},
                        {field: 'paytype', title: __('Paytype')},
                        {field: 'paytime', title: __('Paytime'), operate: 'RANGE', addclass: 'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'ip', title: __('Ip'), formatter: Table.api.formatter.search},
                        {field: 'memo', title: __('Memo')},
                        {field: 'createtime', title: __('Createtime'), sortable: true, operate: 'RANGE', addclass: 'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'updatetime', title: __('Updatetime'), sortable: true, visible: false, operate: 'RANGE', addclass: 'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'status', title: __('Status'), searchList: {"created": __('Created'), "paid": __('Paid'), "expired": __('Expired')}, formatter: Table.api.formatter.status},
                        {field: 'operate', title: __('Operate'), clickToSelect: false, table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
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
