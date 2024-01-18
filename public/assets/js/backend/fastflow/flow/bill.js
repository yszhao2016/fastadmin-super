define(['jquery', 'bootstrap', 'backend', 'table', 'form', 'template', 'bootstrap-select-lang', 'selectpage'], function ($, undefined, Backend, Table, Form, Template, undefined, undefined) {

    var Controller = {
        index: function () {
            Table.api.init({
                extend: {
                    index_url: 'fastflow/flow/bill/index' + location.search,
                    add_url: 'fastflow/flow/bill/add',
                    edit_url: 'fastflow/flow/bill/edit',
                    del_url: 'fastflow/flow/bill/del',
                    multi_url: 'fastflow/flow/bill/multi',
                    import_url: 'fastflow/flow/bill/import',
                    table: 'fastflow_bill',
                }
            });

            var table = $("#table");


            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                columns: [
                    [
                        {field: 'id', title: __('Id')},
                        {field: 'bill_name', title: __('Bill_name'), operate: 'LIKE'},
                        {field: 'bill_table', title: __('Bill_table'), operate: 'LIKE'},
                        {field: 'controller', title: __('Controller'), operate: 'LIKE'},
                        {
                            field: 'createtime',
                            title: __('Createtime'),
                            operate: 'RANGE',
                            addclass: 'datetimerange',
                            autocomplete: false,
                            formatter: Table.api.formatter.datetime
                        },
                        {
                            field: 'updatetime',
                            title: __('Updatetime'),
                            operate: 'RANGE',
                            addclass: 'datetimerange',
                            autocomplete: false,
                            formatter: Table.api.formatter.datetime
                        },
                        {field: '', title: __('BillAuth'), formatter: Controller.api.formatter.billauth},
                        {
                            field: 'operate',
                            title: __('Operate'),
                            table: table,
                            events: Controller.api.events.operate,
                            formatter: Controller.api.formatter.operate
                        }
                    ]
                ]
            });

            Table.api.bindevent(table);
        },

        add: function () {
            var table = [];
            var tablefields = [];
            $("select[name=table] option").each(function () {
                table.push({'value': $(this).val(), 'name': $(this).html()});
            });
            $("select[name='table']").on('change', function () {
                var that = this;
                Fast.api.ajax({
                    url: "fastflow/flow/bill/getFieldsWithComment",
                    data: {table: $(that).val()},
                }, function (data, ret) {
                    tablefields = data;
                    Controller.api.buildoptions($("#fields"), data, 'fields');
                    return false;
                });
                var controller = '';
                $(that).val().replace(/_([a-zA-Z])+/g, function (arg) {
                    arg = arg.replace('_', '');
                    controller += arg.replace(arg[0], arg[0].toUpperCase());
                })
                $('input[name=controller]').val('fastflow/bill/' + controller);
                return false;
            });
            $(document).on('click', "a.btn-newrelation", function () {
                var that = this;
                $('select', $(that).closest('tr').prev()).selectpicker();
                var exists = [];
                exists.push($("select[name='table']").val());
                $("select.relationtable").each(function () {
                    exists.push($(this).val());
                });
                relationtable = [];
                table.forEach(function (item) {
                    if ($.inArray(item['value'], exists) < 0) {
                        relationtable.push(item);
                    }
                });
                Controller.api.buildoptions($("select.relationtable", $(that).closest('tr').prev()), relationtable, 'table');
                $("select.relationtable", $(that).closest('tr').prev()).trigger("change");
            });
            $(document).on('change', "select.relationmode", function () {
                var relationtable = $("select.relationtable", $(this).closest("tr")).val();
                var that = this;
                Fast.api.ajax({
                    url: "fastflow/flow/bill/getFieldsWithComment",
                    data: {table: relationtable},
                }, function (data, ret) {
                    Controller.api.buildoptions($(that).closest("tr").find("select.relationprimarykey"), $(that).val() == 'belongsto' ? data : tablefields, type = 'fields');
                    Controller.api.buildoptions($(that).closest("tr").find("select.relationforeignkey"), $(that).val() == 'hasone' ? data : tablefields, type = 'fields');
                    return false;
                });
            });
            $(document).on('change', "select.relationtable", function () {
                var that = this;
                Fast.api.ajax({
                    url: "fastflow/flow/bill/getFieldsWithComment",
                    data: {table: $(that).val()},
                }, function (data, ret) {
                    Controller.api.buildoptions($(that).closest("tr").find("select.relationmode"), ["belongsto", "hasone"], 'item');
                    Controller.api.buildoptions($(that).closest("tr").find("select.relationfields"), data, type = 'fields');
                    $(that).closest("tr").find("select.relationmode").trigger('change');
                    return false;
                });
            });

            $("select[name='table']").trigger("change");

            var iconlist = [];
            var iconfunc = function () {
                Layer.open({
                    type: 1,
                    title: '图标',
                    area: ['99%', '98%'],
                    content: Template('chooseicontpl', {iconlist: iconlist})
                });
            };

            $(document).on('click', ".btn-search-icon", function () {
                if (iconlist.length == 0) {
                    $.get(Config.site.cdnurl + "/assets/libs/font-awesome/less/variables.less", function (ret) {
                        var exp = /fa-var-(.*):/ig;
                        var result;
                        while ((result = exp.exec(ret)) != null) {
                            iconlist.push(result[1]);
                        }
                        iconfunc();
                    });
                } else {
                    iconfunc();
                }
            });
            $(document).on('click', '#chooseicon ul li', function () {
                $("input[name='icon']").val('fa-' + $(this).data("font"));
                Layer.closeAll();
            });
            $(document).on('keyup', 'input.js-icon-search', function () {
                $("#chooseicon ul li").show();
                if ($(this).val() != '') {
                    $("#chooseicon ul li:not([data-font*='" + $(this).val() + "'])").hide();
                }
            });

            Controller.api.bindevent();
        },
        edit: function () {
            Controller.api.bindevent();
        },
        auth: function () {
            $('.nav.nav-tabs a').on('click', function (e) {
                $(this).addClass('active');
                $(this).siblings().removeClass('active');
            });
            Config.flowids.forEach(function (flowid) {
                let flowRule = Config.rules[flowid];
                if (flowRule != undefined) {
                    $('textarea[name="rule[' + flowid + ']"]').val(JSON.stringify(flowRule));
                    $('textarea[name="rule[' + flowid + ']"]').trigger("fa.event.refreshfieldlist");
                }
            });

            $(document).on("fa.event.appendfieldlist", ".btn-newrule", function (e, obj) {
                let that = this;
                let flowid = $(that).closest('table').data('flowid');
                Form.events.selectpicker(obj);
                Fast.api.ajax({
                    url: "fastflow/flow/bill/getAuthSelectOptionData",
                    data: {"flowid":flowid},
                }, function (data, ret) {
                    let ruleBehavior = $(obj).find("select.rule-behavior");
                    let ruleSteps = $(obj).find("select.rule-steps");
                    let ruleControl = $(obj).find("select.rule-control");
                    Controller.api.buildoptions(ruleBehavior, data['behavior'], 'table', [ruleBehavior.attr('value')]);
                    Controller.api.buildoptions(ruleSteps, data['steps'], 'table', ruleSteps.attr('value').split(','));
                    Controller.api.buildoptions(ruleControl, data['control'], 'table', [ruleControl.attr('value')]);
                    return false;
                });
                Fast.api.ajax({
                    url: "fastflow/flow/bill/getFieldsWithComment",
                    data: {table: Config.bill_table},
                }, function (data, ret) {
                    let ruleFields = $(obj).find("select.rule-fields");
                    Controller.api.buildoptions(ruleFields, data, 'fields', ruleFields.attr('value').split(','));
                    return false;
                });
            });

            Controller.api.bindevent();
        },
        api: {
            buildoptions: function (select, data, type, value = null) {
                var html = '';
                data.forEach(function (e) {
                    if (type == 'fields') {
                        let selected = $.inArray(e['field'].toString(), value) != -1 ? 'selected' : '';
                        html += "<option value='" + e['field'] + "' " + selected + ">" + e['field'] + '(' + e['comment'] + ')' + "</option>";
                    } else if (type == 'table') {
                        let selected = $.inArray(e['value'].toString(), value) != -1 ? 'selected' : '';
                        html += "<option value='" + e['value'] + "' " + selected + ">" + e['name'] + "</option>";
                    } else if (type == 'item') {
                        let selected = $.inArray(e.toString(), value) != -1 ? 'selected' : '';
                        html += "<option value='" + e + "' " + selected + ">" + e + "</option>";
                    }
                });
                $(select).html(html);
                select.trigger("change");
                if (select.data("selectpicker")) {
                    select.selectpicker('refresh');
                }
            },
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"), function (data) {
                    Fast.api.refreshmenu();
                });
            },
            formatter: {
                billauth: function (value, row, index) {
                    return '<a href="fastflow/flow/bill/auth?bill=' + row['bill_table'] + '" class="btn btn-xs btn-info btn-dialog" data-area=\'["70%", "80%"]\' data-toggle="tooltip" title="权限配置" data-table-id="table"  data-original-title="权限配置"><i class="fa fa-confluence">权限配置</i></a>';
                },
                operate: function (value, row, index) {
                    return '<a href="fastflow/flow/bill/del?ids=' + row['id'] + '" class="btn btn-xs btn-danger btn-delone" data-toggle="tooltip" title="删除单据" data-table-id="table" data-field-index="12" data-row-index="0" data-button-index="2" data-original-title="删除单据"><i class="fa fa-trash">删除单据</i></a>';
                },
            },
            events: {
                operate: {
                    'click .btn-delone': function (e, value, row, index) {
                        e.stopPropagation();
                        e.preventDefault();
                        var that = this;
                        var offsettop = $(that).offset().top - $(window).scrollTop();
                        var offsetleft = $(that).offset().left - $(window).scrollLeft() - 260;
                        if (offsettop + 154 > $(window).height()) {
                            offsettop = offsettop - 154;
                        }
                        if ($(window).width() < 480) {
                            offsettop = offsetleft = undefined;
                        }
                        var table = $(that).closest('table');
                        Layer.confirm(
                            __('删除单据会同时删除对应的控制器、模型、JS等文件，确认删除该单据吗?'),
                            {
                                icon: 3,
                                title: __('Warning'),
                                offset: [offsettop, offsetleft],
                                shadeClose: true,
                                btn: [__('OK'), __('Cancel')]
                            },
                            function (index) {
                                var options = table.bootstrapTable('getOptions');
                                Fast.api.ajax({
                                    url: $(that).attr('href'),
                                    data: {},
                                }, function (data, ret) {
                                    Layer.alert(ret.msg);
                                    table.bootstrapTable('refresh');
                                    top.window.$(".sidebar-menu").trigger("refresh");
                                    return false;
                                });
                                Layer.close(index);
                            }
                        );
                    }
                }
            },
        }
    };
    return Controller;
});