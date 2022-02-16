define(['jquery', 'bootstrap', 'frontend', 'template', 'form'], function ($, undefined, Frontend, Template, Form) {
    var Controller = {
        my: function () {
            $(document).on('click', '.btn-delete', function () {
                var that = this;
                Layer.confirm("确认删除？删除后将不能恢复", {icon: 3}, function () {
                    var url = $(that).data("url");
                    Fast.api.ajax({
                        url: url,
                    }, function (data) {
                        Layer.closeAll();
                        location.reload();
                        return false;
                    });
                });
                return false;
            });
        },
        post: function () {
            require(['jquery-autocomplete'], function () {
                var search = $("#c-title");
                var form = search.closest("form");
                search.autoComplete({
                    minChars: 1,
                    cache: false,
                    menuClass: 'autocomplete-searchtitle',
                    header: Template('headertpl', {}),
                    footer: '',
                    source: function (term, response) {
                        try {
                            xhr.abort();
                        } catch (e) {
                        }
                        xhr = $.getJSON(search.data("suggestion-url"), {q: term}, function (data) {
                            response($.isArray(data) ? data : []);
                        });
                    },
                    renderItem: function (item, search) {
                        search = search.replace(/[-\/\\^$*+?.()|[\]{}]/g, '\\$&');
                        var regexp = new RegExp("(" + search.replace(/[\,|\u3000|\uff0c]/, ' ').split(' ').join('|') + ")", "gi");
                        Template.helper("replace", function (value) {
                            return value.replace(regexp, "<b>$1</b>");
                        });
                        return Template('itemtpl', {item: item, search: search});
                    },
                    onSelect: function (e, term, item) {
                        e.preventDefault();
                        if (typeof callback === 'function') {
                            callback.call(elem, term, item);
                        } else {
                            if ($(item).data("url")) {
                                location.href = $(item).data("url");
                            }
                            return false;
                        }
                    }
                });
            });
            require(['jquery-tagsinput'], function () {
                //标签输入
                var elem = "#c-tags";
                var tags = $(elem);
                tags.tagsInput({
                    width: 'auto',
                    defaultText: '输入后回车确认',
                    minInputWidth: 110,
                    height: '36px',
                    placeholderColor: '#999',
                    onChange: function (row) {
                        if (typeof callback === 'function') {

                        } else {
                            $(elem + "_addTag").focus();
                            $(elem + "_tag").trigger("blur.autocomplete").focus();
                        }
                    },
                    autocomplete: {
                        url: 'cms.archives/tags_autocomplete',
                        minChars: 1,
                        menuClass: 'autocomplete-tags'
                    }
                });
            });
            $(document).on('change', '#c-channel_id', function () {
                var model = $("option:selected", this).attr("model");
                var value = $(this).val();
                Fast.api.ajax({
                    url: 'cms.archives/get_channel_fields',
                    data: {channel_id: $(this).val(), archives_id: Config.archives_id}
                }, function (data) {
                    if ($("#extend").data("model") != model) {
                        $("div.form-group[data-field]").hide();
                        $.each(data.contributefields, function (i, j) {
                            $("div.form-group[data-field='" + j + "']").show();
                        });
                        $("#extend").html(data.html).data("model", model);
                        Form.api.bindevent($("#extend"));
                    }
                    return false;
                });
                localStorage.setItem('last_channel_id', $(this).val());
                $("#c-channel_ids option").prop("disabled", true);
                $("#c-channel_ids option[model!='" + model + "']").prop("selected", false);
                $("#c-channel_id option[model='" + model + "']:not([disabled])").each(function () {
                    $("#c-channel_ids option[model='" + $(this).attr("model") + "'][value='" + $(this).attr("value") + "']").prop("disabled", false);
                });
                if ($("#c-channel_ids").data("selectpicker")) {
                    $("#c-channel_ids").data("selectpicker").refresh();
                }
            });
            $(document).on("fa.event.appendfieldlist", ".downloadlist", function (a) {
                Form.events.plupload(this);
                $(".fachoose", this).off("click");
                Form.events.faselect(this);
            });
            Form.api.bindevent($("form[role=form]"), function (data, ret) {
                setTimeout(function () {
                    location.href = Fast.api.fixurl('cms.archives/my');
                }, 1500);
            });
            // $("#c-channel_id").trigger("change");
        }
    };
    return Controller;
});
