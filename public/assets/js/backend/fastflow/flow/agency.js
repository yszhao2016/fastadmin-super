define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {

            Table.api.init({
                extend: {
                    index_url: 'fastflow/flow/agency/index' + location.search,
                    add_url: 'fastflow/flow/agency/add',
                    edit_url: 'fastflow/flow/agency/edit',
                    del_url: 'fastflow/flow/agency/del',
                    multi_url: 'fastflow/flow/agency/multi',
                    import_url: 'fastflow/flow/agency/import',
                    table: 'fastflow_agency',
                }
            });

            var table = $("#table");


            table.on('post-body.bs.table', function (e, data) {
                Controller.api.renderbadge();
            });


            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id')},
                        {field: 'range', title: __('Range'), searchList: {"1": __('Range 1'), "2": __('Range 2'), "3": __('Range 3')}, formatter: Table.api.formatter.normal},
                        {
                            field: 'bill', title: __('Bill'), operate: 'LIKE', formatter: function (value, row, index) {
                                if (value == '') {
                                    return '';
                                }
                                return '<span class="text-info">' + row['bill_comment'] + '(' + value + ')</span>';
                            }
                        },
                        {
                            field: 'bill_id', title: __('Bill_id'), formatter: function (value, row, index) {
                                if (value == '') {
                                    return '';
                                }
                                return '<span class="text-info">' + value + '</span>';
                            }
                        },
                        {field: 'scope', title: __('Scope'), searchList: {"1": __('Scope 1'), "2": __('Scope 2')}, formatter: Table.api.formatter.normal},
                        {field: 'principal_id', title: __('Principal_id'), operate: 'LIKE', visible: false},
                        {field: 'principal', title: __('Principal_id'), operate: false},
                        {field: 'agent_id', title: __('Agent_id'), operate: 'LIKE', visible: false},
                        {field: 'agent', title: __('Agent_id'), operate: false},
                        {field: 'createtime', title: __('Createtime'), operate: 'RANGE', addclass: 'datetimerange', autocomplete: false, formatter: Table.api.formatter.datetime},
                        {field: 'updatetime', title: __('Updatetime'), operate: 'RANGE', addclass: 'datetimerange', autocomplete: false, formatter: Table.api.formatter.datetime},
                        {field: 'remark', title: __('Remark'), operate: 'LIKE'},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
                    ]
                ]
            });


            Table.api.bindevent(table);
        },
        add: function () {
            Controller.api.bindevent();

            var buildoptions = function (select, data) {
                var html = '';
                data.forEach(function (e) {
                    if(e['name'] == ''){
                        html += "<option value='" + e['id'] + "'>" + e['id'] +  "</option>";
                    }
                    else{
                        html += "<option value='" + e['id'] + "'>" + e['name'] + "（" + e['id'] + "）" + "</option>";
                    }
                });
                $(select).html(html);
                select.trigger("change");
                if (select.data("selectpicker")) {
                    select.selectpicker('refresh');
                }
            };
            $("#c-range").on('change', function () {
                var that = this;
                if ($(that).val() == 1) {
                    $("#bill_area").removeClass('hidden');
                    $("#bill_id_area").removeClass('hidden');
                }
                if ($(that).val() == 2) {
                    $("#bill_area").removeClass('hidden');
                    $("#bill_id_area").addClass('hidden');
                }
                if ($(that).val() == 3) {
                    $("#bill_area").addClass('hidden');
                    $("#bill_id_area").addClass('hidden');
                }
            });
            $("#c-bill").on('change', function () {
                var that = this;
                if ($("select[name='row[range]']").val() == 1) {
                    Fast.api.ajax({
                        url: "fastflow/flow/agency/getBillIds",
                        data: {bill: $(that).val()},
                    }, function (data, ret) {
                        buildoptions($("select[name='row[bill_id]']"), data);
                        return false;
                    });
                }
            });
            $('#c-principal_ids').data("params", function (obj) {
                return {"scope": $('#c-scope').val()};
            });
            $('#c-agent_ids').data("params", function (obj) {
                return {"scope": $('#c-scope').val()};
            });
            $('#c-scope').change(function () {
                $('#c-principal_ids').selectPageClear();
                $('#c-agent_ids').selectPageClear();
            });

            $("#c-bill").trigger('change');
        },
        edit: function () {
            Controller.api.bindevent();
            var buildoptions = function (select, data) {
                var html = '';
                data.forEach(function (e) {
                    if(e['name'] == ''){
                        html += "<option value='" + e['id'] + "'>" + e['id'] +  "</option>";
                    }
                    else{
                        html += "<option value='" + e['id'] + "'>" + e['name'] + "（" + e['id'] + "）" + "</option>";
                    }
                });
                select.html(html);
                select.trigger("change");
                if (select.data("selectpicker")) {
                    select.selectpicker('refresh');
                }
            };
            $("#c-range").on('change', function () {
                var that = this;
                if ($(that).val() == 1) {
                    $("#bill_area").removeClass('hidden');
                    $("#bill_id_area").removeClass('hidden');
                }
                if ($(that).val() == 2) {
                    $("#bill_area").removeClass('hidden');
                    $("#bill_id_area").addClass('hidden');
                }
                if ($(that).val() == 3) {
                    $("#bill_area").addClass('hidden');
                    $("#bill_id_area").addClass('hidden');
                }
            });
            $("#c-bill").on('change', function () {
                var that = this;
                if ($("select[name='row[range]']").val() == 1) {
                    Fast.api.ajax({
                        url: "fastflow/flow/agency/getBillIds",
                        data: {bill: $(that).val()},
                    }, function (data, ret) {
                        buildoptions($("select[name='row[bill_id]']"), data);
                        return false;
                    });
                }
            });
            $('#c-principal_ids').data("params", function (obj) {
                return {"scope": $('#c-scope').val()};
            });
            $('#c-agent_ids').data("params", function (obj) {
                return {"scope": $('#c-scope').val()};
            });
            $('#c-scope').change(function () {
                $('#c-principal_ids').selectPageClear();
                $('#c-agent_ids').selectPageClear();
            });

            $("#c-range").trigger('change');
            $("#c-bill").trigger('change');
        },
        api: {
            renderbadge: function () {
                $.ajax({
                    type: "POST",
                    url: "fastflow/flow/bill/getBadge",
                    data: {},
                    dataType: "json",
                    success: function (data) {
                        if (data['code'] == 1) {
                            $('.fastflow-badge', window.parent.document).remove();
                            data['data'].forEach(function (item) {
                                if (item['count'] > 0) {
                                    $('a[addtabs=' + item['id'] + ']', window.parent.document).append('<span class="pull-right-container fastflow-badge" style="margin-right: 20px"> <small class="' + item['shape'] + ' pull-right ' + item['color'] + '">' + item['show'] + '</small></span>');
                                }
                            });
                        }
                    }
                });
            },
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            },
            formatter:{
            },
        }
    };
    return Controller;
});