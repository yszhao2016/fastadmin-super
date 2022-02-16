define(['jquery', 'bootstrap', 'backend', 'table', 'form', 'clipboard'], function ($, undefined, Backend, Table, Form, ClipboardJS) {
    var Controller = {
        index: function () {
            var previewUrl = $("#previewiframe").attr("src");
            require(['jquery-colorpicker'], function () {
                $('.colorpicker').colorpicker({
                    color: function (btn) {
                        var color = "#000000";
                        var rgb = $(this.eve).val().match(/^rgb\(((\d+),\s*(\d+),\s*(\d+))\)$/);
                        if (rgb) {
                            color = rgb[1];
                        }
                        return color;
                    }
                }, function (btn, obj) {
                    $(btn).closest(".input-group").find("input").val('#' + obj.hex).css('backgroundColor', '#' + obj.hex);
                }, function (btn, obj) {
                    $(btn).closest(".input-group").find("input").val('').css('backgroundColor', '#' + obj.hex);
                });
            });
            Form.api.bindevent($("form[role=form]"), function () {
                $("#previewiframe").attr("src", previewUrl + ($("#config-form input[name='preview']").val() == 1 ? "?mode=preview" : ""));
            });
            $(document).on("fa.event.appendfieldlist", ".tabbarlist .btn-append", function (e, obj) {
                if ($(".tabbarlist table tr.tabbarlist-item").length > 5) {
                    $(".tabbarlist table tr.tabbarlist-item:last").remove();
                    Layer.msg("最多允许添加5个");
                }
                Form.events.faselect(obj);
            });
            $(document).on("change", ".tabbarlist .tabbar-img-value", function (e, obj) {
                $(this).next().attr("src", Fast.api.cdnurl($(this).val(), true));
            });
            $(document).on("change", ".tabbarlist .c-tabbar-list-midbutton", function (e, obj) {
                $(".tabbarlist .c-tabbar-list-midbutton").not(this).prop("checked", false);
            });
            $(document).on("click", ".btn-preview", function (e, obj) {
                $("#config-form input[name='preview']").val(1);
            });
            $(document).on("click", ".btn-preview,.btn-save", function (e, obj) {
                $("#config-form input[name='preview']").val($(this).data("preview"));
                $("#config-form").submit();
            });
            $(document).on("click", ".btn-select-page", function (e, obj) {
                var that = this;
                Fast.api.open("cms/ajax/get_page_list", "选择路径", {
                    callback: function (data) {
                        $(that).parent().prev().val(data);
                    }
                })
            });
        },
    };
    return Controller;
});
