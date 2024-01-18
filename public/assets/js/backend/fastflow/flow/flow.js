require.config({
    paths: {
        'fastflow': "../addons/fastflow/js/fastflow",
    },
    shim: {
        'fastflow': {
            exports: 'fastflow'
        },
    }
});
define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {
    var Controller = {
        index: function () {

            Table.api.init({
                extend: {
                    index_url: 'fastflow/flow/flow/index' + location.search,
                    add_url: 'fastflow/flow/flow/add',
                    edit_url: 'fastflow/flow/flow/edit',
                    del_url: 'fastflow/flow/flow/del',
                    multi_url: 'fastflow/flow/flow/multi',
                    import_url: 'fastflow/flow/flow/import',
                    table: 'wf_flow',
                }
            });

            var table = $("#table");

            
            table.on('post-common-search.bs.table', function (event, table) {
                var form = $("form", table.$commonsearch);
                $("input[name='createuser_id']", form).addClass("selectpage").data("source", "fastflow/flow/flow/getSelectpageWorkers?scope=1").data("primaryKey", "id").data("field", "createuser_id").data("orderBy", "id asc");
                Form.events.selectpage(form);
            });

            
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                rowStyle: function (row, index) {
                    return {
                        css: {
                            height: '60px'
                        }
                    };
                },
                columns: [
                    [
                        {field: 'id', title: __('Id'), sortable: true,},
                        {field: 'name', title: __('Name'), operate: 'LIKE'},
                        {field: 'bill', title: __('Bill'), formatter: Table.api.formatter.normal},
                        {
                            field: 'description',
                            title: __('Description'),
                            operate: 'LIKE',
                            formatter: Table.api.formatter.content
                        },
                        {
                            field: 'createuser_id',
                            title: __('Createuser_id'),
                            formatter: Table.api.formatter.search,
                            visible: false
                        },
                        {field: 'createusername', title: __('Createuser_name'), operate: false},
                        {
                            field: 'createtime',
                            title: __('Createtime'),
                            operate: 'RANGE',
                            addclass: 'datetimerange',
                            autocomplete: false,
                            formatter: Table.api.formatter.datetime
                        },
                        {
                            field: 'status',
                            title: __('Status'),
                            searchList: {"0": __('Status 0'), "1": __('Status 1')},
                            formatter: Controller.api.formatter.status,
                            events: Controller.api.events.status,
                            custom: {0: 'success', 1: 'danger'}
                        },
                        {
                            field: 'isrun',
                            title: __('Designer'),
                            operate: false,
                            table: table,
                            events: Controller.api.events.opendesigner,
                            formatter: Controller.api.formatter.designerbtn
                        },
                        {
                            field: 'operate',
                            title: __('EDIT'),
                            table: table,
                            events: Table.api.events.operate,
                            formatter: Table.api.formatter.operate
                        },
                        {field: 'remark', title: __('Remark'), operate: false, formatter: Table.api.formatter.content},
                    ]
                ]
            });

            
            Table.api.bindevent(table);

            $('body', document).on('keyup', function(e) {
                if (e.which === 27) {
                    return false;
                }
            });
        },
        add: function () {
            Form.api.bindevent($("form[role=form]"));
        },
        edit: function () {
            Form.api.bindevent($("form[role=form]"));
        },
        designer: function () {
            require(['fastflow'], function (fastflow) {
                fastflow.run(Config.flow.graph, Config.flow.id, config = {saveAction: 'fastflow/flow/Flow/saveGraph'});
                $(document).on("fa.event.appendfieldlist", ".btn-append", function (e, obj) {
                    Form.events.selectpage(obj);
                    fastflow.createStepSelectOption();
                });
            });

            Form.api.bindevent($("form[role=form]"));
            Controller.api.bindevent();
        },
        detail:function(){
            let index = layer.load();
            $.ajax({
                type: "get",
                url: Config.controller_url + "/edit",
                data: {ids: Config.bill_id, way: 'detail'},
                success: function (data) {
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
        viewer:function(){
            
            
            
        },
        api: {
            bindevent: function () {
                $('#c-worker').data("params", function (obj) {
                    return {"scope": $('#c-scope').val()};
                });
            },
            formatter: {
                status: function (value, row, index) {
                    return '<a href="javascript:;" class="btn btn-xs btn-status ' + (value == 0 ? 'btn-danger' : 'btn-success') + '" title="点击切换" data-table-id="table" style="margin-right: 4px;font-size: 90%;">' + (value == 0 ? '禁用(点击启用)' : '启用(点击禁用)') + '</a>';
                },
                designerbtn: function (value, row, index) {
                    let html = '';
                    if (value == 0) {
                        html = '<a href="fastflow/flow/flow/designer?flow_id=' + row['id'] + '" class="btn btn-xs btn-info btn-dialog" title="工作流设计" data-table-id="table" style="margin-right: 4px;font-size: 90%;"><i class="fa fa-list" style="margin-right: 4px;"></i>工作流设计</a>';
                    } else {
                        html = '<span class="label label-warning" style="font-size: 90%">运行中....</span>';
                    }

                    return html;

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
                opendesigner: {
                    'click .btn-dialog': function (e, value, row, index) {
                        e.stopPropagation();
                        e.preventDefault();
                        var table = $(this).closest('table');
                        var options = table.bootstrapTable('getOptions');
                        var ids = row[options.pk];
                        row = $.extend({}, row ? row : {}, {ids: ids});
                        $(this).data('area', ['100%', '100%']);
                        Fast.api.open(Table.api.replaceurl($(this).attr('href'), row, table), $(this).data("original-title") || $(this).attr("title"), $(this).data() || {});
                    }
                }
            }
        }
    };
    return Controller;
});
