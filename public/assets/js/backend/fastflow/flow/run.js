define(['jquery', 'bootstrap', 'backend', 'form'], function ($, undefined, Backend, Form) {
    var Controller = {
        start: function () {
            $('#fastflow-c-flow_id').on('change', function (e) {
                src = $('#fastflow-viewer').attr('src');
                src = src.replace(/(?<=flow_id=)\d*/, $(this).val());
                $('#fastflow-viewer').attr('src', src);
            });
            $('#fastflow-c-flow_id').on('change', function (e) {
                $.ajax({
                    type: "POST",
                    url: 'fastflow/flow/run/getStartDynamicSteps',
                    data: {flow_id: $(this).val(), bill: Config.bill, bill_id: Config.bill_id},
                    dataType: "json",
                    success: function (data) {
                        if (data['code'] == 1) {
                            if (data['data'].length > 0) {
                                $('#fastflow-fastflow-selectworker').removeClass('hidden');
                                Controller.api.methods.addDynamicStepItem(data['data']);
                            } else {
                                $('#fastflow-fastflow-selectworker').addClass('hidden');
                            }
                        } else if (data['code'] == 0) {
                            Toastr.error(data['msg']);
                        }

                    }
                });
            });
            $('#fastflow-carbon_receiver').data("params", function (obj) {
                return {"scope": $('#fastflow-carbon_scope').val()};
            });
            $('#fastflow-carbon_scope').change(function () {
                $('#fastflow-carbon_receiver').selectPageClear();
            });
            Form.api.bindevent($("form[role=form]"));
            Controller.api.methods.buildBillEdit();
        },
        agree: function () {
            Form.api.bindevent($("form[role=form]"));
            let data = Config.dynamic_step_data;
            for (let index in data) {
                $('#fastflow-scope-' + data[index]['id']).on('change', function () {
                    if ($(this).val() == 5) {
                        $('#fastflow-span-' + data[index]['id']).addClass('hidden');
                    } else {
                        $('#fastflow-span-' + data[index]['id']).removeClass('hidden');
                        $('#fastflow-worker-' + data[index]['id']).selectPageClear();
                    }
                })
                $('#fastflow-worker-' + data[index]['id']).data("params", function (obj) {
                    return {"scope": $('#fastflow-scope-' + data[index]['id']).val()};
                });
            }
            $('#fastflow-carbon_receiver').data("params", function (obj) {
                return {"scope": $('#fastflow-carbon_scope').val()};
            });
            $('#fastflow-carbon_scope').change(function () {
                $('#fastflow-carbon_receiver').selectPageClear();
            });

            Controller.api.methods.buildBillEdit();
        },
        sign: function () {
            Form.api.bindevent($("form[role=form]"));
            $('#fastflow-sign_worker').data("params", function (obj) {
                return {"scope": $('#fastflow-sign_scope').val()};
            });
            $('#fastflow-sign_scope').change(function () {
                $('#fastflow-sign_worker').selectPageClear();
            });
            $('#fastflow-carbon_receiver').data("params", function (obj) {
                return {"scope": $('#fastflow-carbon_scope').val()};
            });
            $('#fastflow-carbon_scope').change(function () {
                $('#fastflow-carbon_receiver').selectPageClear();
            });
            Controller.api.methods.buildBillEdit();
        },
        back: function () {
            Form.api.bindevent($("form[role=form]"));
            $('#fastflow-carbon_receiver').data("params", function (obj) {
                return {"scope": $('#fastflow-carbon_scope').val()};
            });
            $('#fastflow-carbon_scope').change(function () {
                $('#fastflow-carbon_receiver').selectPageClear();
            });
            Controller.api.methods.buildBillEdit();
        },
        api: {
            bindevent: function () {
            },
            formatter: {},
            events: {},
            methods: {
                addDynamicStepItem: function (data) {
                    $('#fastflow-selectworker table tbody tr:gt(0)').remove();
                    for (let index in data) {
                        $('#fastflow-selectworker table tbody').append(
                            '<tr>\n' +
                            '    <td style="vertical-align: middle;text-align: center" class="text-success">' + data[index]['name'] + '</td>\n' +
                            '    <td>' +
                            '        <select id="fastflow-scope-' + data[index]['id'] + '" class="form-control" style="alignment: center" name="row[scope][' + data[index]['id'] + ']">\n' +
                            Controller.api.methods.addScope() +
                            '        </select>' +
                            '    </td>\n' +
                            '    <td>\n' +
                            '        <span id="fastflow-span-' + data[index]['id'] + '">' +
                            '            <input id="fastflow-worker-' + data[index]['id'] + '" ' +
                            '                               data-source="fastflow/flow/run/getSelectpageWorkers"\n' +
                            '                               data-field="name" data-primary-key="id" data-multiple="true"\n' +
                            '                               class="form-control selectpage" name="row[worker][' + data[index]['id'] + ']" type="text">\n' +
                            '        </span>' +
                            '     </td>\n' +
                            '     <td>\n' +
                            '         <div class="radio">\n' +
                            '              <label for="fastflow-checkmode-' + data[index]['id'] + '-1"><input id="fastflow-checkmode-' + data[index]['id'] + '-1" name="row[checkmode][' + data[index]['id'] + ']" type="radio" value="1" checked="">联合</label>\n' +
                            '              <label for="fastflow-checkmode-' + data[index]['id'] + '-2"><input id="fastflow-checkmode-' + data[index]['id'] + '-2" name="row[checkmode][' + data[index]['id'] + ']" type="radio" value="2">任一</label>\n' +
                            '         </div>\n' +
                            '     </td>\n' +
                            '</tr>'
                        );
                        $('#fastflow-scope-' + data[index]['id']).on('change', function () {
                            if ($(this).val() == 5) {
                                $('#fastflow-span-' + data[index]['id']).addClass('hidden');
                            } else {
                                $('#fastflow-span-' + data[index]['id']).removeClass('hidden');
                            }
                        })
                        $('#fastflow-worker-' + data[index]['id']).data("params", function (obj) {
                            return {"scope": $('#fastflow-scope-' + data[index]['id']).val()};
                        });
                    }
                    Form.api.bindevent($("form[role=form]"));
                },
                addScope: function () {
                    let result = '';
                    let scope = Config.scope;
                    for (let i in scope) {
                        result += '<option value="' + scope[i]['value'] + '">' + scope[i]['name'] + '</option>';
                    }
                    return result;
                },
                buildBillEdit: function () {
                    let index = layer.load();
                    $.ajax({
                        type: "get",
                        url: Config.controller_url + "/edit",
                        data: {ids: Config.bill_id, threadid: Config.thread_id},
                        success: function (data) {
                            $('#fastflow-edit-content #bill-edit').append($('#edit-form',data));
                            if(Config.can_edit_fields !== true){
                                Config.bill_fields.forEach(function (item) {
                                    if ($.inArray(item, Config.can_edit_fields) === -1) {
                                        let input = $('[name="row[' + item + ']"]',$('#edit-form','#fastflow-edit-content'));
                                        input.attr('disabled','disabled');
                                        input.closest('.form-group').find('button').attr('disabled','disabled');
                                    }
                                    else{
                                        $($('[name="row[' + item + ']"]',$('#edit-form','#fastflow-edit-content'))).closest('.form-group').find('label').addClass('text-warning');
                                    }
                                });
                            }
                            Form.api.bindevent($("#edit-form"));
                            $('button[disabled="disabled"]').closest('.form-group').find('a.btn').attr('disabled','disabled');
                            $('button[disabled="disabled"]').closest('.form-group').find('a.btn').click(function () {return false;});
                            layer.close(index);
                        },
                        error:function (e) {
                            layer.close(index);
                        }
                    });
                    $('button[type="submit"]').click(function () {
                        $.ajax({
                            type: "post",
                            url: Config.controller_url + "/edit?ids=" + Config.bill_id + "&threadid=" + Config.thread_id,
                            data: $('#edit-form').serialize(),
                            success: function (data) {

                            }
                        });
                    });
                }

            },
        }
    };
    return Controller;
});
