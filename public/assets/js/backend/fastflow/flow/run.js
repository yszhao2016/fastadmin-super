define(['jquery', 'bootstrap', 'backend', 'form'], function ($, undefined, Backend, Form) {
    var Controller = {
        start: function () {
            $('#c-flow_id').on('change', function (e) {
                src = $('#viewer').attr('src');
                src = src.replace(/(?<=flow_id=)\d*/, $(this).val());
                $('#viewer').attr('src', src);
            });
            $('#c-flow_id').on('change', function (e) {
                $.ajax({
                    type: "POST",
                    url: 'fastflow/flow/run/getStartDynamicSteps',
                    data: {flow_id: $(this).val(), bill: Config.bill, bill_id: Config.bill_id},
                    dataType: "json",
                    success: function (data) {
                        if (data['code'] == 1) {
                            if (data['data'].length > 0) {
                                $('#selectworker').removeClass('hidden');
                                Controller.api.methods.addDynamicStepItem(data['data']);
                            } else {
                                $('#selectworker').addClass('hidden');
                            }
                        } else if (data['code'] == 0) {
                            Toastr.error(data['msg']);
                        }

                    }
                });
            })
            Form.api.bindevent($("form[role=form]"));
        },
        agree: function () {
            Form.api.bindevent($("form[role=form]"));
            let data = Config.dynamic_step_data;
            for (let index in data) {
                $('#scope-' + data[index]['id']).on('change', function () {
                    if ($(this).val() == 5) {
                        $('#span-' + data[index]['id']).addClass('hidden');
                    } else {
                        $('#span-' + data[index]['id']).removeClass('hidden');
                        $('#worker-' + data[index]['id']).selectPageClear();
                    }
                })
                $('#worker-' + data[index]['id']).data("params", function (obj) {
                    return {"scope": $('#scope-' + data[index]['id']).val()};
                });
            }
        },
        sign: function () {
            Form.api.bindevent($("form[role=form]"));
            $('#sign_worker').data("params", function (obj) {
                return {"scope": $('#sign_scope').val()};
            });
            $('#sign_scope').change(function () {
                $('#sign_worker').selectPageClear();
            });
        },
        back: function () {
            Form.api.bindevent($("form[role=form]"));
        },
        api: {
            bindevent: function () {
            },
            formatter: {},
            events: {},
            methods: {
                addDynamicStepItem: function (data) {
                    $('#selectworker table tbody tr:gt(0)').remove();
                    for (let index in data) {
                        $('#selectworker table tbody').append(
                            '<tr>\n' +
                            '    <td style="vertical-align: middle;text-align: center" class="text-success">' + data[index]['name'] + '</td>\n' +
                            '    <td>' +
                            '        <select id="scope-' + data[index]['id'] + '" class="form-control" style="alignment: center" name="row[scope][' + data[index]['id'] + ']">\n' +
                            Controller.api.methods.addScope() +
                            '        </select>' +
                            '    </td>\n' +
                            '    <td>\n' +
                            '        <span id="span-' + data[index]['id'] + '">' +
                            '            <input id="worker-' + data[index]['id'] + '" ' +
                            '                               data-source="fastflow/flow/run/getSelectpageWorkers"\n' +
                            '                               data-field="name" data-primary-key="id" data-multiple="true"\n' +
                            '                               class="form-control selectpage" name="row[worker][' + data[index]['id'] + ']" type="text">\n' +
                            '        </span>' +
                            '     </td>\n' +
                            '     <td>\n' +
                            '         <div class="radio">\n' +
                            '              <label for="checkmode-' + data[index]['id'] + '-1"><input id="checkmode-' + data[index]['id'] + '-1" name="row[checkmode][' + data[index]['id'] + ']" type="radio" value="1" checked="">联合</label>\n' +
                            '              <label for="checkmode-' + data[index]['id'] + '-2"><input id="checkmode-' + data[index]['id'] + '-2" name="row[checkmode][' + data[index]['id'] + ']" type="radio" value="2">任一</label>\n' +
                            '         </div>\n' +
                            '     </td>\n' +
                            '</tr>'
                        );
                        $('#scope-' + data[index]['id']).on('change', function () {
                            if ($(this).val() == 5) {
                                $('#span-' + data[index]['id']).addClass('hidden');
                            } else {
                                $('#span-' + data[index]['id']).removeClass('hidden');
                            }
                        })
                        $('#worker-' + data[index]['id']).data("params", function (obj) {
                            return {"scope": $('#scope-' + data[index]['id']).val()};
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
                }

            },
        }
    };
    return Controller;
});
