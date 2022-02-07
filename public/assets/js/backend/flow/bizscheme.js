define(['jquery', 'bootstrap', 'backend', 'table', 'form', 'validator'], function ($, undefined, Backend, Table, Form, Validator) {

    var Controller = {
        index: function () {
            var schemeid = $('#schemeid').val();
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'flow/bizscheme/index?scheme_id=' + schemeid,
                    add_url: 'flow/bizscheme/add?scheme_id=' + schemeid,
                    edit_url: 'flow/bizscheme/edit?scheme_id=' + schemeid,
                    del_url: 'flow/bizscheme/del?scheme_id=' + schemeid,
                    multi_url: 'flow/bizscheme/multi',
                    release_url: 'flow/bizscheme/release',
                    table: 'flow_bizscheme',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'COLUMN_NAME',
                sortName: 'id',
                columns: [
                    [
                        {checkbox: true},
                        {field: 'COLUMN_KEY', title: __('Id'), visible: false},
                        {field: 'COLUMN_NAME', title: __('Fieldcode')},
                        {field: 'COLUMN_TYPE', title: __('Type')},
                        {field: 'COLUMN_COMMENT', title: __('Fieldname')},
                        {field: 'COLUMN_DEFAULT', title: __('Default')},
                        {
                            field: 'operate',
                            title: __('Operate'),
                            table: table,
                            events: Table.api.events.operate,
                            formatter: Table.api.formatter.operate,
                            formatter: function (value, row, index) {
                                if (row.COLUMN_KEY != '') {
                                    return '';
                                }
                                return Table.api.formatter.operate.call(this, value, row, index);
                            }
                        }
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
            $('.btn-release').click(function () {
                var tableName = $('#tableName').text();
                if (tableName === '') {
                    Layer.prompt({title: '请输入表名', formType: 0}, function (tableName, index) {
                        Layer.close(index);
                        var options = {
                            url: $.fn.bootstrapTable.defaults.extend.release_url,
                            data: {bizscheme: tableName, scheme_id: schemeid}
                        };
                        Controller.api.release(options);
                    });
                }
                else {
                    Layer.confirm('确认要发布吗？', {
                        btn: ['发布', '取消'] //按钮
                    }, function () {
                        Layer.close(Layer.index);
                        var options = {
                            url: $.fn.bootstrapTable.defaults.extend.release_url,
                            data: {bizscheme: tableName, scheme_id: schemeid}
                        };
                        Controller.api.release(options);
                    }, function () {
                        Layer.close(Layer.index);
                        //var options = {url:$.fn.bootstrapTable.defaults.extend.release_url, data: {bizscheme: tableName,scheme_id:schemeid,force:true}};
                        //Controller.api.release(options);
                    });
                }

            });
        },
        add: function () {
            document.getElementById("ok").addEventListener('click', function () {
                var res = true;
                if ($('#mobile').isValid()) {
                    var value = $('#c-type').selectpicker('val');
                    if ((value == "set" || value == "enum") && $('#c-default').val() == "") {
                        Toastr.error('请填写默认值,并以逗号隔开');
                        event.preventDefault();
                        res = false;
                    }
                }
                var num = $('#c-fieldcode').val();
                var reg = /^[1-9]\d*$|^0$/;

                if (reg.test(num)) {
                    Toastr.error('字段代码不能为空,全数字');
                    event.preventDefault();
                    res = false;
                }
                return res;
            }, false);
            Controller.api.bindevent();
        },
        edit: function () {
            Controller.api.bindevent();
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            },
            release: function (option) {
                var options = option;
                Fast.api.ajax(options, function (data, ret) {
                    if (ret.code === 1) {
                        $('#tableName').text(ret.bizName);
                        $("#table").bootstrapTable('refresh');
                    }
                    else {
                        Toastr.error(data.msg);
                    }
                })
            }
        }
    };
    return Controller;
});