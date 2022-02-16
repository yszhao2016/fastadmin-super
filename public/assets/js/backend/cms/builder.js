define(['jquery', 'bootstrap', 'backend', 'table', 'form', 'clipboard'], function ($, undefined, Backend, Table, Form, ClipboardJS) {

    var output = $("#output");

    var refresh = function (obj) {
        if (obj.data("selectpicker")) {
            obj.selectpicker('refresh');
        }
    };
    var check = function (data) {
        if (data.id && data.key && data.id == data.key) {
            return "循环变量id和键名变量key不能相同";
        }

        if (data.id && data.id.match(/^[0-9]+/)) {
            return "循环变量id不能以数字开头";
        }

        if (data.key && data.key.match(/^[0-9]+/)) {
            return "键名变量key不能以数字开头";
        }

        if (data.limit && !data.limit.match(/^(([0-9]+)|([0-9]+\,[0-9]+))$/)) {
            return "偏移值格式不正确";
        }

        if (data.cache && !data.cache.match(/^(true|false|[0-9]+)$/)) {
            return "缓存时长只能是true、false或具体的数字";
        }
        return '';
    };
    var flash = function (dom) {

        var flashClass = "flash";
        dom.addClass(flashClass);

        dom.on('animationend', function () {
            dom.removeClass(flashClass);
        });
    };

    $(document).on("click", ".btn-result", function () {
        Fast.api.ajax({
            url: "cms/builder/parse",
            data: {"tag": output.val()}
        }, function (data, ret) {
            data = $.trim(data);
            data = data ? data : "未配置循环字段或返回结果为空";
            $("#result").val(data);
            flash($("#result"));
            return false;
        }, function (data, ret) {
        });
    });


    var Controller = {
        index: function () {
            this.config($("#config-form"));
            this.arclist($("#arclist-form"));
            this.channellist($("#channellist-form"));
            this.spagelist($("#spagelist-form"));
            this.speciallist($("#speciallist-form"));
            this.blocklist($("#blocklist-form"));
            this.userlist($("#userlist-form"));
            this.diydatalist($("#diydatalist-form"));
            this.query($("#query-form"));
            this.api.bindevent();
        },
        config: function (form) {
            form.on("click", ".config-list a", function () {
                $(".config-list a").removeClass("btn-info active");
                $(this).addClass("btn-info active");
                if ($(this).data("type") == "array") {
                    if ($("#func", form).val().indexOf("json_encode") == -1) {
                        $("#func", form).val("json_encode");
                    }
                } else {
                    if ($("#func", form).val().indexOf("json_encode") > -1) {
                        $("#func", form).val("");
                    }
                }
                $(".btn-command", form).trigger("click");
                return false;
            });
            form.on("click", ".btn-command", function () {
                if ($(".config-list a.active").size() == 0) {
                    Layer.msg("请选择")
                    return false;
                }
                var data = $(".config-list a.active").data();
                data.defaultvalue = $("#defaultvalue", form).val() ? "|default='" + $("#defaultvalue", form).val() + "'" : "";
                data.func = $("#func", form).val() ? "|" + $("#func", form).val() : "";
                output.val(Template("configtpl", data));
                flash(output);
            });
        },
        arclist: function (form) {
            var model = $("#model", form);
            var channel = $("#channel", form);
            var channeltype = $("#channeltype", form);
            var field = $("#field", form);
            var addon = $("#addon", form);
            var channelfield = $("#channelfield", form);
            var userfield = $("#userfield", form);

            form.on("click", ".btn-command", function () {

                var attrs = ['id', 'empty', 'key', 'mod', 'row', 'orderby', 'orderway', 'offset', 'limit', 'cache', 'model', 'channel', 'type', 'with', 'field', 'addon'];
                var data = {attrs: []};
                $(form.serializeArray()).each(function (i, j) {
                    if (j.name == 'id' && j.value == '') {
                        j.value = 'item';
                    }
                    if (j.name == 'field' || j.name == 'addon' || j.name == 'with' || j.name == 'channel') {
                        j.value = $.map($("#" + j.name + " option:selected", form).filter(function (key, value) {
                            return ["url", "fullurl", "imglink", "textlink", "img"].indexOf($(value).attr("value")) == -1;
                        }), function (j) {
                            return $(j).attr("value");
                        }).join(",");
                    }
                    if (typeof data[j.name] == 'undefined') {
                        data[j.name] = j.value;

                        if (attrs.indexOf(j.name) > -1 && j.value != '') {
                            data.attrs.push(j.name + '="' + j.value + '"');
                        }
                    }
                });

                var tips = check(data);
                if (tips) {
                    Layer.msg(tips);
                    return;
                }

                var columns = [];

                if (field.find("option[value!='']:selected").size() > 0) {
                    $("option" + (field.find("option:selected").size() ? ":selected" : "") + "[value!='']", field).each(function (i, j) {
                        columns.push("{$" + data.id + "." + $(j).attr("value") + "}");
                    });
                }
                if (addon.find("option[value='']:selected").size() == 0) {
                    $(addon.find("option" + (addon.find("option[value='true']:selected").size() > 0 ? "[value!=''][value!='true']" : ":selected"))).each(function (i, j) {
                        columns.push("{$" + data.id + "." + $(j).attr("value") + "}");
                    });
                }
                if (channelfield.find("option:selected").size() > 0) {
                    $(channelfield.find("option:selected")).each(function (i, j) {
                        columns.push("{$" + data.id + ".channel." + $(j).attr("value") + "}");
                    });
                }
                if (userfield.find("option:selected").size() > 0) {
                    $(userfield.find("option:selected")).each(function (i, j) {
                        columns.push("{$" + data.id + ".user." + $(j).attr("value") + "}");
                    });
                }
                if (columns.length == 0) {
                    columns.push("{//你可以从左侧主表字段、副表字段、栏目字段、会员字段中选择需要显示的字段信息}");
                    columns.push("{//你也可以直接在循环体内添加文字、标签或判断}");
                }
                data.attrs = data.attrs.join(" ");
                data.columns = columns;

                output.val(Template("arclisttpl", data));
                flash(output);
                return false;
            });
            form.on("change", "#model", function () {
                $("option", channel).prop("disabled", false).prop("selected", false);
                addon.find("option").prop("selected", false);
                var id = $(this).val();
                if (!id) {
                    $("option[data-type='link']", channel).prop("disabled", true);
                } else {
                    $("option[data-model!='" + id + "']", channel).prop("disabled", true);
                }
                addon.prop("disabled", false);

                refresh(channel);
                refresh(addon);
                return false;
            });
            form.on("change", "#channel", function () {
                var modelIds = [];
                $("option:selected", channel).each(function () {
                    if (modelIds.indexOf($(this).data("model")) == -1) {
                        modelIds.push($(this).data("model"));
                    }
                });
                if (modelIds.length > 1) {
                    addon.prop("disabled", true).html("");
                    addon.data("id", currentId[name]);
                } else {
                    addon.prop("disabled", false);
                }
                refresh(addon);
                return false;
            });
            var currentId = {channel: '', model: ''};
            form.on("change", "#channel,#model", function () {
                var orderby = $("#orderby", form);
                var name = $(this).attr("name");
                currentId[name] = name == "model" ? $(this).val() : $("option:selected", this).data("model");
                if (currentId[name]) {
                    if (addon.data("id") != currentId[name]) {
                        $("optgroup", orderby).remove();
                        Fast.api.ajax({
                            url: "cms/builder/get_model_fields",
                            data: {"id": currentId[name]}
                        }, function (data, ret) {
                            var fields = data.fields;
                            fields.unshift({name: "", title: "无"}, {name: "true", title: "全部"});
                            var html = [];
                            for (var i = 0; i < fields.length; i++) {
                                html.push("<option value='" + fields[i].name + "' data-subtext='" + fields[i].title + "'>" + fields[i].name + "</option>");
                            }
                            addon.html(html.join(""));
                            addon.data("id", currentId[name]);
                            orderby.append("<optgroup label='副表'>" + html.slice(2).join("") + "</optgroup>");
                            refresh(addon);
                            refresh(orderby);
                            return false;
                        });

                    }
                } else {
                    $("optgroup", orderby).remove();
                    addon.data("id", currentId[name]);
                    refresh(addon);
                    refresh(orderby);
                }
                return false;
            });
            var oldFieldValue = [];
            form.on("change", "#field", function () {
                var value = $(this).val();
                if (value.indexOf("") > -1 && oldFieldValue.indexOf("") == -1) {
                    field.find("option").prop("selected", false);
                    field.find("option[value='']").prop("selected", true);
                } else {
                    field.find("option[value='']").prop("selected", false);
                    field.find("option[value='id'],option[value='user_id'],option[value='channel_id'],option[value='title'],option[value='diyname']").prop("selected", true);
                }
                refresh(field);
                oldFieldValue = field.val();
            });
            form.on("change", "#addon", function () {
                var value = $(this).val();
                if (value.indexOf("") > -1 || value.indexOf("true") > -1) {
                    var selected = value.indexOf("true") > -1 ? "true" : "";
                    addon.find("option").prop("selected", false);
                    addon.find("option[value='" + selected + "']").prop("selected", true);
                    refresh(addon);
                }
            });
        },
        channellist: function (form) {
            var model = $("[name='model']", form);
            var channel = $("[name='typeid']", form);
            var field = $("[name='field']", form);

            form.on("click", ".btn-command", function () {

                var attrs = ['id', 'empty', 'key', 'mod', 'row', 'orderby', 'orderway', 'offset', 'limit', 'cache', 'model', 'typeid', 'type', 'with', 'field'];
                var data = {attrs: []};
                $(form.serializeArray()).each(function (i, j) {
                    if (j.name == 'id' && j.value == '') {
                        j.value = 'item';
                    }
                    if (j.name == 'field' || j.name == 'typeid') {
                        j.value = $.map($("#" + j.name + " option:selected", form).filter(function (key, value) {
                            return ["url", "fullurl", "imglink", "textlink", "img"].indexOf($(value).attr("value")) == -1;
                        }), function (j) {
                            return $(j).attr("value");
                        }).join(",");
                    }
                    if (typeof data[j.name] == 'undefined') {
                        data[j.name] = j.value;

                        if (attrs.indexOf(j.name) > -1 && j.value != '') {
                            data.attrs.push(j.name + '="' + j.value + '"');
                        }
                    }
                });

                var tips = check(data);
                if (tips) {
                    Layer.msg(tips);
                    return;
                }

                var columns = [];

                if (field.find("option[value!='']:selected").size() > 0) {
                    $("option" + (field.find("option:selected").size() ? ":selected" : "") + "[value!='']", field).each(function (i, j) {
                        columns.push("{$" + data.id + "." + $(j).attr("value") + "}");
                    });
                }
                if (columns.length == 0) {
                    columns.push("{//你可以从左侧主表字段中选择需要显示的字段信息}");
                }
                data.attrs = data.attrs.join(" ");
                data.columns = columns;
                output.val(Template("channellisttpl", data));
                flash(output);
                return false;
            });
            model.on("change", function () {
                $("option", channel).prop("disabled", false).prop("selected", false);
                var id = $(this).val();
                if (!id) {
                    $("option[data-type='link']", channel).prop("disabled", true);
                } else {
                    $("option[data-model!='" + id + "']", channel).prop("disabled", true);
                }

                refresh(channel);
                return false;
            });
            var oldFieldValue = [];
            field.on("change", function () {
                var value = $(this).val();
                if (value.indexOf("") > -1 && oldFieldValue.indexOf("") == -1) {
                    field.find("option").prop("selected", false);
                    field.find("option[value='']").prop("selected", true);
                } else {
                    field.find("option[value='']").prop("selected", false);
                    field.find("option[value='id'],option[value='name'],option[value='diyname']").prop("selected", true);
                }
                refresh(field);
                oldFieldValue = field.val();
            });
        },
        spagelist: function (form) {
            var model = $("[name='model']", form);
            var channel = $("[name='typeid']", form);
            var field = $("[name='field']", form);

            form.on("click", ".btn-command", function () {

                var attrs = ['id', 'empty', 'key', 'mod', 'row', 'orderby', 'orderway', 'offset', 'limit', 'cache', 'model', 'type', 'with', 'field'];
                var data = {attrs: []};
                $(form.serializeArray()).each(function (i, j) {
                    if (j.name == 'id' && j.value == '') {
                        j.value = 'item';
                    }
                    if (j.name == 'field' || j.name == 'type') {
                        j.value = $.map($("#" + j.name + " option:selected", form).filter(function (key, value) {
                            return ["url", "fullurl", "imglink", "textlink", "img"].indexOf($(value).attr("value")) == -1;
                        }), function (j) {
                            return $(j).attr("value");
                        }).join(",");
                    }
                    if (typeof data[j.name] == 'undefined') {
                        data[j.name] = j.value;

                        if (attrs.indexOf(j.name) > -1 && j.value != '') {
                            data.attrs.push(j.name + '="' + j.value + '"');
                        }
                    }
                });

                var tips = check(data);
                if (tips) {
                    Layer.msg(tips);
                    return;
                }

                var columns = [];

                if (field.find("option[value!='']:selected").size() > 0) {
                    $("option" + (field.find("option:selected").size() ? ":selected" : "") + "[value!='']", field).each(function (i, j) {
                        columns.push("{$" + data.id + "." + $(j).attr("value") + "}");
                    });
                }
                if (columns.length == 0) {
                    columns.push("{//你可以从左侧主表字段中选择需要显示的字段信息}");
                }
                data.attrs = data.attrs.join(" ");
                data.columns = columns;
                output.val(Template("spagelisttpl", data));
                flash(output);
                return false;
            });
            var oldFieldValue = [];
            field.on("change", function () {
                var value = $(this).val();
                if (value.indexOf("") > -1 && oldFieldValue.indexOf("") == -1) {
                    field.find("option").prop("selected", false);
                    field.find("option[value='']").prop("selected", true);
                } else {
                    field.find("option[value='']").prop("selected", false);
                    field.find("option[value='id'],option[value='title'],option[value='diyname']").prop("selected", true);
                }
                refresh(field);
                oldFieldValue = field.val();
            });
        },
        speciallist: function (form) {
            var model = $("[name='model']", form);
            var channel = $("[name='typeid']", form);
            var field = $("[name='field']", form);

            form.on("click", ".btn-command", function () {

                var attrs = ['id', 'empty', 'key', 'mod', 'row', 'orderby', 'orderway', 'offset', 'limit', 'cache', 'model', 'type', 'with', 'field'];
                var data = {attrs: []};
                $(form.serializeArray()).each(function (i, j) {
                    if (j.name == 'id' && j.value == '') {
                        j.value = 'item';
                    }
                    if (j.name == 'field' || j.name == 'type') {
                        j.value = $.map($("#" + j.name + " option:selected", form).filter(function (key, value) {
                            return ["url", "fullurl", "imglink", "textlink", "img"].indexOf($(value).attr("value")) == -1;
                        }), function (j) {
                            return $(j).attr("value");
                        }).join(",");
                    }
                    if (typeof data[j.name] == 'undefined') {
                        data[j.name] = j.value;

                        if (attrs.indexOf(j.name) > -1 && j.value != '') {
                            data.attrs.push(j.name + '="' + j.value + '"');
                        }
                    }
                });

                var tips = check(data);
                if (tips) {
                    Layer.msg(tips);
                    return;
                }

                var columns = [];

                if (field.find("option[value!='']:selected").size() > 0) {
                    $("option" + (field.find("option:selected").size() ? ":selected" : "") + "[value!='']", field).each(function (i, j) {
                        columns.push("{$" + data.id + "." + $(j).attr("value") + "}");
                    });
                }
                if (columns.length == 0) {
                    columns.push("{//你可以从左侧主表字段中选择需要显示的字段信息}");
                }
                data.attrs = data.attrs.join(" ");
                data.columns = columns;
                output.val(Template("speciallisttpl", data));
                flash(output);
                return false;
            });
            var oldFieldValue = [];
            field.on("change", function () {
                var value = $(this).val();
                if (value.indexOf("") > -1 && oldFieldValue.indexOf("") == -1) {
                    field.find("option").prop("selected", false);
                    field.find("option[value='']").prop("selected", true);
                } else {
                    field.find("option[value='']").prop("selected", false);
                    field.find("option[value='id'],option[value='title'],option[value='image'],option[value='diyname']").prop("selected", true);
                }
                refresh(field);
                oldFieldValue = field.val();
            });
        },
        blocklist: function (form) {
            var field = $("[name='field']", form);

            form.on("click", ".btn-command", function () {

                var attrs = ['id', 'empty', 'key', 'mod', 'row', 'orderby', 'orderway', 'offset', 'limit', 'cache', 'model', 'type', 'name', 'with', 'field'];
                var data = {attrs: []};
                $(form.serializeArray()).each(function (i, j) {
                    if (j.name == 'id' && j.value == '') {
                        j.value = 'item';
                    }
                    if (j.name == 'field' || j.name == 'type' || j.name == 'name') {
                        j.value = $.map($("#" + j.name + " option:selected", form).filter(function (key, value) {
                            return ["imglink", "textlink", "img"].indexOf($(value).attr("value")) == -1;
                        }), function (j) {
                            return $(j).attr("value");
                        }).join(",");
                    }
                    if (typeof data[j.name] == 'undefined') {
                        data[j.name] = j.value;

                        if (attrs.indexOf(j.name) > -1 && j.value != '') {
                            data.attrs.push(j.name + '="' + j.value + '"');
                        }
                    }
                });

                var tips = check(data);
                if (tips) {
                    Layer.msg(tips);
                    return;
                }

                var columns = [];

                if (field.find("option[value!='']:selected").size() > 0) {
                    $("option" + (field.find("option:selected").size() ? ":selected" : "") + "[value!='']", field).each(function (i, j) {
                        columns.push("{$" + data.id + "." + $(j).attr("value") + "}");
                    });
                }
                if (columns.length == 0) {
                    columns.push("{//你可以从左侧主表字段中选择需要显示的字段信息}");
                }
                data.attrs = data.attrs.join(" ");
                data.columns = columns;
                output.val(Template("blocklisttpl", data));
                flash(output);
                return false;
            });
            var oldFieldValue = [];
            field.on("change", function () {
                var value = $(this).val();
                if (value.indexOf("") > -1 && oldFieldValue.indexOf("") == -1) {
                    field.find("option").prop("selected", false);
                    field.find("option[value='']").prop("selected", true);
                } else {
                    field.find("option[value='']").prop("selected", false);
                    field.find("option[value='id'],option[value='title'],option[value='url'],option[value='image'],option[value='begintime'],option[value='endtime']").prop("selected", true);
                }
                refresh(field);
                oldFieldValue = field.val();
            });
        },
        userlist: function (form) {
            var field = $("[name='field']", form);

            form.on("click", ".btn-command", function () {

                var attrs = ['id', 'empty', 'key', 'mod', 'row', 'orderby', 'orderway', 'offset', 'limit', 'cache', 'model', 'type', 'with', 'field'];
                var data = {attrs: []};
                $(form.serializeArray()).each(function (i, j) {
                    if (j.name == 'id' && j.value == '') {
                        j.value = 'item';
                    }
                    if (j.name == 'field' || j.name == 'type') {
                        j.value = $.map($("#" + j.name + " option:selected", form).filter(function (key, value) {
                            return ["url", "fullurl", "imglink", "textlink", "img"].indexOf($(value).attr("value")) == -1;
                        }), function (j) {
                            return $(j).attr("value");
                        }).join(",");
                    }
                    if (typeof data[j.name] == 'undefined') {
                        data[j.name] = j.value;

                        if (attrs.indexOf(j.name) > -1 && j.value != '') {
                            data.attrs.push(j.name + '="' + j.value + '"');
                        }
                    }
                });

                var tips = check(data);
                if (tips) {
                    Layer.msg(tips);
                    return;
                }

                var columns = [];

                if (field.find("option[value!='']:selected").size() > 0) {
                    $("option" + (field.find("option:selected").size() ? ":selected" : "") + "[value!='']", field).each(function (i, j) {
                        columns.push("{$" + data.id + "." + $(j).attr("value") + "}");
                    });
                }
                if (columns.length == 0) {
                    columns.push("{//你可以从左侧主表字段中选择需要显示的字段信息}");
                }
                data.attrs = data.attrs.join(" ");
                data.columns = columns;
                output.val(Template("userlisttpl", data));
                flash(output);
                return false;
            });
            var oldFieldValue = [];
            field.on("change", function () {
                var value = $(this).val();
                if (value.indexOf("") > -1 && oldFieldValue.indexOf("") == -1) {
                    field.find("option").prop("selected", false);
                    field.find("option[value='']").prop("selected", true);
                } else {
                    field.find("option[value='']").prop("selected", false);
                    field.find("option[value='id'],option[value='avatar'],option[value='nickname']").prop("selected", true);
                }
                refresh(field);
                oldFieldValue = field.val();
            });
        },
        diydatalist: function (form) {
            var field = $("[name='field']", form);

            form.on("click", ".btn-command", function () {

                var diyform_id = $("#diyform_id").val();
                if (!diyform_id) {
                    Layer.msg("请选择自定义表单");
                    return false;
                }
                var attrs = ['id', 'empty', 'key', 'mod', 'row', 'orderby', 'orderway', 'offset', 'limit', 'cache', 'model', 'type', 'with', 'field'];
                var data = {attrs: ["diyform" + '="' + diyform_id + '"']};
                $(form.serializeArray()).each(function (i, j) {
                    if (j.name == 'id' && j.value == '') {
                        j.value = 'item';
                    }
                    if (j.name == 'field') {
                        return true;
                    }
                    if (typeof data[j.name] == 'undefined') {
                        data[j.name] = j.value;

                        if (attrs.indexOf(j.name) > -1 && j.value != '') {
                            data.attrs.push(j.name + '="' + j.value + '"');
                        }
                    }
                });

                var tips = check(data);
                if (tips) {
                    Layer.msg(tips);
                    return;
                }

                var columns = [];

                if (field.find("option[value!='']:selected").size() > 0) {
                    $("option" + (field.find("option:selected").size() ? ":selected" : "") + "[value!='']", field).each(function (i, j) {
                        columns.push("{$" + data.id + "." + $(j).attr("value") + "}");
                    });
                }
                if (columns.length == 0) {
                    columns.push("{//你可以从左侧选择字段中选择需要显示的字段信息}");
                }
                data.attrs = data.attrs.join(" ");
                data.columns = columns;
                output.val(Template("diydatalisttpl", data));
                flash(output);
                return false;
            });
            var oldFieldValue = [];
            field.on("change", function () {
                var diyform_id = $("#diyform_id").val();
                var value = $(this).val();
                if (value.indexOf("") > -1 && oldFieldValue.indexOf("") == -1) {
                    field.find("option").prop("selected", false);
                    field.find("option[data-id='" + diyform_id + "'][value='']").prop("selected", true);
                } else {
                    field.find("option[value='']").prop("selected", false);
                    field.find("option[data-id='" + diyform_id + "'][value='id']").prop("selected", true);
                }
                refresh(field);
                oldFieldValue = field.val();
            });
            form.on("change", "#diyform_id", function () {
                var value = $(this).val();
                $("select[name='field'] option[data-id],select[name='orderby'] option[data-id]", form).prop("selected", false).prop("disabled", true).addClass("hidden");
                $("select[name='field'] option[data-id='" + value + "'],select[name='orderby'] option[data-id='" + value + "']", form).prop("disabled", false).removeClass("hidden");
                $("select[name='field'],select[name='orderby']", form).selectpicker("refresh");
            });
        },
        query: function (form) {
            var field = $("[name='field']", form);

            form.on("click", ".btn-command", function () {
                var sql = $("#sql").val();
                if (sql == '') {
                    Layer.msg("请输入SQL语句");
                    return false;
                }
                var attrs = ['id', 'empty', 'key', 'mod', 'row', 'sql', 'bind'];
                var data = {attrs: []};
                $(form.serializeArray()).each(function (i, j) {
                    if (j.name == 'id' && j.value == '') {
                        j.value = 'item';
                    }
                    if (typeof data[j.name] == 'undefined') {
                        data[j.name] = j.value;

                        if (attrs.indexOf(j.name) > -1 && j.value != '') {
                            data.attrs.push(j.name + '="' + j.value + '"');
                        }
                    }
                });

                var tips = check(data);
                if (tips) {
                    Layer.msg(tips);
                    return;
                }

                data.attrs = data.attrs.join(" ");
                output.val(Template("querytpl", data));
                flash(output);
                return false;
            });
            var oldFieldValue = [];
            field.on("change", function () {
                var value = $(this).val();
                if (value.indexOf("") > -1 && oldFieldValue.indexOf("") == -1) {
                    field.find("option").prop("selected", false);
                    field.find("option[value='']").prop("selected", true);
                } else {
                    field.find("option[value='']").prop("selected", false);
                    field.find("option[value='id'],option[value='avatar'],option[value='nickname']").prop("selected", true);
                }
                refresh(field);
                oldFieldValue = field.val();
            });
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));

                //点击复制
                var clipboard = new ClipboardJS('.btn-copy', {
                    text: function () {
                        return output.val();
                    }
                });
                clipboard.on('success', function (e) {
                    Layer.msg("复制成功");
                    e.clearSelection();
                });
            }
        }
    };
    return Controller;
});
