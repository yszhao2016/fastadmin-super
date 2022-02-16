define(['jquery', 'bootstrap', 'backend', 'table', 'form', 'template'], function ($, undefined, Backend, Table, Form, Template) {

    //设置弹窗宽高
    Fast.config.openArea = ['80%', '80%'];

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'cms/archives/index',
                    add_url: 'cms/archives/add',
                    edit_url: 'cms/archives/edit',
                    del_url: 'cms/archives/del',
                    multi_url: 'cms/archives/multi',
                    dragsort_url: '',
                    table: 'cms_archives',
                }
            });

            var table = $("#table");

            //在表格内容渲染完成后回调的事件
            table.on('post-body.bs.table', function (e, settings, json, xhr) {
                //当为新选项卡中打开时
                if (Config.cms.archiveseditmode == 'addtabs') {
                    $(".btn-editone", this)
                        .off("click")
                        .removeClass("btn-editone")
                        .addClass("btn-addtabs")
                        .prop("title", __('Edit'));
                }
            });

            //当双击单元格时
            table.on('dbl-click-row.bs.table', function (e, row, element, field) {
                $(".btn-addtabs", element).trigger("click");
            });

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'weigh DESC,id DESC',
                searchFormVisible: Fast.api.query("model_id") ? true : false,
                fixedColumns: true,
                fixedRightNumber: 1,
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id'), sortable: true},
                        {
                            field: 'user_id',
                            title: __('User_id'),
                            visible: false,
                            addclass: 'selectpage',
                            extend: 'data-source="user/user/index" data-field="nickname"',
                            operate: '=',
                            formatter: Table.api.formatter.search
                        },
                        {
                            field: 'admin_id',
                            title: __('Admin_id'),
                            visible: false,
                            addclass: 'selectpage',
                            extend: 'data-source="auth/admin/selectpage" data-field="nickname"',
                            operate: '=',
                            formatter: Table.api.formatter.search
                        },
                        {
                            field: 'channel_id',
                            title: __('Channel_id'),
                            visible: false,
                            addclass: 'selectpage',
                            extend: 'data-source="cms/channel/index" data-field="name"',
                            operate: 'in',
                            formatter: Table.api.formatter.search
                        },
                        {
                            field: 'channel_ids',
                            title: __('Channel_ids'),
                            visible: false,
                            addclass: 'selectpage',
                            extend: 'data-source="cms/channel/index" data-field="name"',
                            operate: 'find_in_set',
                            formatter: Table.api.formatter.search
                        },
                        {
                            field: 'channel.name',
                            title: __('Channel'),
                            operate: false,
                            formatter: function (value, row, index) {
                                return '<a href="javascript:;" class="searchit" data-field="channel_id" data-value="' + row.channel_id + '">' + value + '</a>';
                            }
                        },
                        {
                            field: 'model_id', title: __('Model'), visible: false, align: 'left', addclass: "selectpage", extend: "data-source='cms/modelx/index' data-field='name'"
                        },
                        {
                            field: 'title', title: __('Title'), align: 'left', operate: 'like', customField: 'flag', formatter: function (value, row, index) {
                                var flagObj = $.extend({}, this, {searchList: Config.flagList});
                                return '<div class="archives-title"><a href="' + row.url + '" target="_blank"><span style="color:' + (row.style_color ? row.style_color : 'inherit') + ';font-weight:' + (row.style_bold ? 'bold' : 'normal') + '">' + value + '</span></a></div>' +
                                    '<div class="archives-label">' + Table.api.formatter.flag.call(flagObj, row['flag'], row, index) + '</div>';
                            }
                        },
                        {field: 'flag', title: __('Flag'), operate: 'find_in_set', visible: false, searchList: Config.flagList, formatter: Table.api.formatter.flag},
                        {
                            field: 'image', title: __('Image'), operate: false, events: Table.api.events.image, formatter: function (value, row, index) {
                                value = value == null || value.length === 0 ? '' : value.toString();
                                value = value ? value : '/assets/addons/cms/img/noimage.png';
                                var classname = typeof this.classname !== 'undefined' ? this.classname : 'img-sm img-center';
                                return '<a href="javascript:"><img class="' + classname + '" src="' + Fast.api.cdnurl(value) + '" /></a>';
                            }
                        },
                        {
                            field: 'price', title: __('Price'), operate: 'BETWEEN', sortable: true, formatter: function (value, row, index) {
                                return parseFloat(value) > 0 ? "<span class='text-danger'>" + value + "</span>" : value;
                            }
                        },
                        {field: 'views', title: __('Views'), operate: 'BETWEEN', sortable: true},
                        {
                            field: 'comments', title: __('Comments'), operate: 'BETWEEN', sortable: true, formatter: function (value, row, index) {
                                return '<a href="javascript:" data-url="cms/comment/index?type=archives&aid=' + row['id'] + '" title="评论列表" class="dialogit">' + value + '</a>';
                            }
                        },
                        {field: 'weigh', title: __('Weigh'), operate: false, sortable: true},
                        {
                            field: 'createtime',
                            title: __('Createtime'),
                            visible: false,
                            operate: 'RANGE',
                            addclass: 'datetimerange',
                            formatter: Table.api.formatter.datetime,
                            autocomplete: false
                        },
                        {
                            field: 'updatetime',
                            title: __('Updatetime'),
                            visible: false,
                            operate: 'RANGE',
                            addclass: 'datetimerange',
                            formatter: Table.api.formatter.datetime,
                            autocomplete: false
                        },
                        {
                            field: 'publishtime',
                            title: __('Publishtime'),
                            sortable: true,
                            operate: 'RANGE',
                            addclass: 'datetimerange',
                            formatter: Table.api.formatter.datetime,
                            datetimeFormat: "YYYY-MM-DD",
                            autocomplete: false
                        },
                        {field: 'status', title: __('Status'), searchList: {"normal": __('Status normal'), "hidden": __('Status hidden'), "rejected": __('Status rejected'), "pulloff": __('Status pulloff')}, formatter: Table.api.formatter.status},
                        {
                            field: 'operate',
                            title: __('Operate'),
                            clickToSelect: false,
                            table: table,
                            events: Table.api.events.operate,
                            formatter: Table.api.formatter.operate
                        }
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);

            var url = '';
            //当为新选项卡中打开时
            if (Config.cms.archiveseditmode == 'addtabs') {
                url = (url + '?ids=' + $(".commonsearch-table input[name=channel_id]").val());
            }
            $(".btn-add").off("click").on("click", function () {
                var url = 'cms/archives/add?channel=' + $(".commonsearch-table input[name=channel_id]").val();
                //当为新选项卡中打开时
                if (Config.cms.archiveseditmode == 'addtabs') {
                    Fast.api.addtabs(url, __('Add'));
                } else {
                    Fast.api.open(url, __('Add'), $(this).data() || {});
                }
                return false;
            });

            $(document).on("click", "a.btn-channel", function () {
                $("#archivespanel").toggleClass("col-md-9", $("#channelbar").hasClass("hidden"));
                $("#archivespanel").toggleClass("col-full-width", !$("#channelbar").hasClass("hidden"));
                $("#channelbar").toggleClass("hidden");
            });

            $(document).on("click", "a.btn-setspecial", function () {
                var ids = Table.api.selectedids(table);
                Layer.open({
                    title: __('Set special'),
                    content: Template("specialtpl", {}),
                    btn: [__('Ok')],
                    yes: function (index, layero) {
                        var special_id = $("select[name='special']", layero).val();
                        if (special_id == 0) {
                            Toastr.error(__('Please select special'));
                            return;
                        }
                        Fast.api.ajax({
                            url: "cms/archives/special/ids/" + ids.join(","),
                            type: "post",
                            data: {special_id: special_id},
                        }, function () {
                            table.bootstrapTable('refresh', {});
                            Layer.close(index);
                        });
                    },
                    success: function (layero, index) {
                    }
                });
            });

            $(document).on("click", "a.btn-setflag", function () {
                var ids = Table.api.selectedids(table);
                Layer.open({
                    title: __('Set flag'),
                    content: Template("flagtpl", {}),
                    btn: [__('Ok')],
                    yes: function (index, layero) {
                        var flag = $.map($("input[name='flag[]']:checked", layero), function (n, i) {
                            return n.value;
                        }).join(',');
                        if (flag == '') {
                            Toastr.error(__('Please select flag'));
                            return;
                        }
                        Fast.api.ajax({
                            url: "cms/archives/flag/ids/" + ids.join(","),
                            type: "post",
                            data: {flag: flag, type: $("input[name=type]:checked", layero).val()},
                        }, function () {
                            table.bootstrapTable('refresh', {});
                            Layer.close(index);
                        });
                    },
                    success: function (layero, index) {
                    }
                });
            });

            $(document).on("click", "a.btn-settag", function () {
                var ids = Table.api.selectedids(table);
                Layer.open({
                    title: __('Join to tag'),
                    content: Template("tagtpl", {}),
                    zIndex: 1000,
                    btn: [__('Ok')],
                    yes: function (index, layero) {
                        var tags = $("input[name='tags']", layero).val();
                        if (!tags) {
                            Toastr.error(__('至少输入一个标签'));
                            return;
                        }
                        Fast.api.ajax({
                            url: "cms/archives/tags/ids/" + ids.join(","),
                            type: "post",
                            data: {tags: tags},
                        }, function () {
                            table.bootstrapTable('refresh', {});
                            Layer.close(index);
                        });
                    },
                    success: function (layero, index) {
                        require(['jquery-tagsinput'], function () {
                            //标签输入
                            var elem = "#c-tags";
                            var tags = $(elem);
                            tags.tagsInput({
                                width: 'auto',
                                defaultText: '输入后空格确认',
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
                                    url: 'cms/tag/autocomplete',
                                    minChars: 1,
                                    menuClass: 'autocomplete-tags'
                                }
                            });
                        });
                    }
                });
            });

            require(['jstree'], function () {
                //全选和展开
                $(document).on("click", "#checkall", function () {
                    $("#channeltree").jstree($(this).prop("checked") ? "check_all" : "uncheck_all");
                });
                $(document).on("click", "#expandall", function () {
                    $("#channeltree").jstree($(this).prop("checked") ? "open_all" : "close_all");
                });
                $('#channeltree').on("changed.jstree", function (e, data) {
                    $(".commonsearch-table input[name=channel_id]").val(data.selected.join(","));
                    table.bootstrapTable('refresh', {});
                    return false;
                });
                $('#channeltree').jstree({
                    "themes": {
                        "stripes": true
                    },
                    "checkbox": {
                        "keep_selected_style": false,
                    },
                    "types": {
                        "channel": {
                            "icon": false,
                        },
                        "list": {
                            "icon": false,
                        },
                        "link": {
                            "icon": false,
                        },
                        "disabled": {
                            "check_node": false,
                            "uncheck_node": false
                        }
                    },
                    'plugins': ["types", "checkbox"],
                    "core": {
                        "multiple": true,
                        'check_callback': true,
                        "data": Config.channelList
                    }
                });
            });

            $(document).on('click', '.btn-copyselected', function () {
                var ids = Table.api.selectedids(table);
                Layer.confirm(__("Are you sure you want to copy %s records?", ids.length), {icon: 3}, function (index, layero) {
                    Fast.api.ajax({
                        url: "cms/archives/copy/ids/" + ids.join(","),
                        type: "post",
                    }, function () {
                        table.bootstrapTable('refresh', {});
                        Layer.close(index);
                    });
                });
                return false;
            });
            $(document).on('click', '.btn-move', function () {
                var ids = Table.api.selectedids(table);
                Layer.open({
                    title: __('Move'),
                    content: Template("channeltpl", {}),
                    btn: [__('Move')],
                    yes: function (index, layero) {
                        var channel_id = $("select[name='channel']", layero).val();
                        if (channel_id == 0) {
                            Toastr.error(__('Please select channel'));
                            return;
                        }
                        Fast.api.ajax({
                            url: "cms/archives/move/ids/" + ids.join(","),
                            type: "post",
                            data: {channel_id: channel_id},
                        }, function () {
                            table.bootstrapTable('refresh', {});
                            Layer.close(index);
                        });
                    },
                    success: function (layero, index) {
                    }
                });
            });
        },
        content: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'cms/archives/content/model_id/' + Config.model_id,
                    add_url: '',
                    edit_url: 'cms/archives/edit',
                    del_url: 'cms/archives/del',
                    multi_url: '',
                    dragsort_url: '',
                    table: '',
                }
            });

            var table = $("#table");
            //在表格内容渲染完成后回调的事件
            table.on('post-body.bs.table', function (e, settings, json, xhr) {
                //当为新选项卡中打开时
                if (Config.cms.archiveseditmode == 'addtabs') {
                    $(".btn-editone", this)
                        .off("click")
                        .removeClass("btn-editone")
                        .addClass("btn-addtabs")
                        .prop("title", __('Edit'));
                }
            });
            //默认字段
            var columns = [
                {checkbox: true},
                //这里因为涉及到关联多个表,因为用了两个字段来操作,一个隐藏,一个搜索
                {field: 'main.id', title: __('Id'), visible: false},
                {field: 'id', title: __('Id'), operate: false},
                {field: 'user_id', title: __('User_id'), formatter: Table.api.formatter.search},
                {
                    field: 'channel_id',
                    title: __('Channel_id'),
                    addclass: 'selectpage',
                    extend: 'data-source="cms/channel/index"',
                    formatter: Table.api.formatter.search
                },
                {field: 'channel_name', title: __('Channel_name'), operate: false},

                {
                    field: 'title', title: __('Title'), align: 'left', customField: 'flag', formatter: function (value, row, index) {
                        var flagObj = $.extend({}, this, {searchList: Config.flagList});
                        return '<div class="archives-title"><a href="' + row.url + '" target="_blank"><span style="color:' + (row.style_color ? row.style_color : 'inherit') + ';font-weight:' + (row.style_bold ? 'bold' : 'normal') + '">' + value + '</span></a></div>' +
                            '<div class="archives-label">' + Table.api.formatter.flag.call(flagObj, row['flag'], row, index) + '</div>';
                    }
                },
                {field: 'flag', title: __('Flag'), operate: 'find_in_set', visible: false, searchList: Config.flagList, formatter: Table.api.formatter.flag},
                {
                    field: 'image', title: __('Image'), operate: false, events: Table.api.events.image, formatter: function (value, row, index) {
                        value = value == null || value.length === 0 ? '' : value.toString();
                        value = value ? value : '/assets/addons/cms/img/noimage.png';
                        var classname = typeof this.classname !== 'undefined' ? this.classname : 'img-sm img-center';
                        return '<a href="javascript:"><img class="' + classname + '" src="' + Fast.api.cdnurl(value) + '" /></a>';
                    }
                },
                {field: 'views', title: __('Views'), operate: 'BETWEEN', sortable: true},
                {
                    field: 'comments', title: __('Comments'), operate: 'BETWEEN', sortable: true, formatter: function (value, row, index) {
                        return '<a href="javascript:" data-url="cms/comment/index?type=archives&aid=' + row['id'] + '" title="评论列表" class="dialogit">' + value + '</a>';
                    }
                }
            ];
            //动态追加字段
            $.each(Config.fields, function (i, j) {
                var data = {field: j.field, title: j.title, table: table, operate: (j.type === 'number' ? '=' : 'like')};
                //如果是图片,加上formatter
                if (j.type == 'image') {
                    data.events = Table.api.events.image;
                    data.formatter = Table.api.formatter.image;
                } else if (j.type == 'images') {
                    data.events = Table.api.events.image;
                    data.formatter = Table.api.formatter.images;
                } else if (j.type == 'radio' || j.type == 'checkbox' || j.type == 'select' || j.type == 'selects') {
                    data.formatter = Controller.api.formatter.content;
                    data.extend = j.content;
                    data.searchList = j.content;
                } else {
                    data.formatter = Table.api.formatter.content;
                }
                columns.push(data);
            });
            //追加操作字段
            columns.push({
                field: 'operate',
                title: __('Operate'),
                clickToSelect: false,
                table: table,
                width: '80px',
                events: Table.api.events.operate,
                formatter: Table.api.formatter.operate
            });

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                fixedColumns: true,
                fixedRightNumber: 1,
                columns: columns
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        diyform: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'cms/archives/diyform/diyform_id/' + Config.diyform_id,
                    add_url: '',
                    edit_url: 'cms/archives/edit',
                    del_url: 'cms/archives/del',
                    multi_url: '',
                    table: '',
                }
            });

            var table = $("#table");
            //在表格内容渲染完成后回调的事件
            table.on('post-body.bs.table', function (e, settings, json, xhr) {
                $(".btn-editone", this)
                    .off("click")
                    .removeClass("btn-editone")
                    .addClass("btn-addtabs")
                    .prop("title", __('Edit'));
            });
            //默认字段
            var columns = [
                {checkbox: true},
                {field: 'id', title: __('Id'), operate: false},
            ];
            //动态追加字段
            $.each(Config.fields, function (i, j) {
                var data = {field: j.field, title: j.title, operate: 'like'};
                //如果是图片,加上formatter
                if (j.type == 'image') {
                    data.formatter = Table.api.formatter.image;
                } else if (j.type == 'images') {
                    data.formatter = Table.api.formatter.images;
                } else if (j.type == 'radio' || j.type == 'check' || j.type == 'select' || j.type == 'selects') {
                    data.formatter = Controller.api.formatter.content;
                    data.extend = j.content;
                } else {
                    data.formatter = Table.api.formatter.content;
                }
                columns.push(data);
            });
            //追加操作字段
            columns.push({
                field: 'operate',
                title: __('Operate'),
                table: table,
                events: Table.api.events.operate,
                formatter: Table.api.formatter.operate
            });

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                columns: columns
            })
            ;

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        recyclebin: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    'dragsort_url': ''
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: 'cms/archives/recyclebin',
                pk: 'id',
                sortName: 'weigh',
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id')},
                        {field: 'title', title: __('Title'), align: 'left', operate: 'like'},
                        {field: 'image', title: __('Image'), operate: false, formatter: Table.api.formatter.image},
                        {
                            field: 'deletetime',
                            title: __('Deletetime'),
                            operate: 'RANGE',
                            addclass: 'datetimerange',
                            formatter: Table.api.formatter.datetime
                        },
                        {
                            field: 'operate',
                            width: '130px',
                            title: __('Operate'),
                            table: table,
                            events: Table.api.events.operate,
                            buttons: [
                                {
                                    name: 'Restore',
                                    text: __('Restore'),
                                    classname: 'btn btn-xs btn-info btn-ajax btn-restoreit',
                                    icon: 'fa fa-rotate-left',
                                    url: 'cms/archives/restore',
                                    refresh: true
                                },
                                {
                                    name: 'Destroy',
                                    text: __('Destroy'),
                                    classname: 'btn btn-xs btn-danger btn-ajax btn-destroyit',
                                    icon: 'fa fa-times',
                                    url: 'cms/archives/destroy',
                                    refresh: true
                                }
                            ],
                            formatter: Table.api.formatter.operate
                        }
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        add: function () {
            var last_channel_id = localStorage.getItem('last_channel_id');
            var channel = Fast.api.query("channel");
            if (channel) {
                var channelIds = channel.split(",");
                $(channelIds).each(function (i, j) {
                    if ($("#c-channel_id option[value='" + j + "']:disabled").size() > 0) {
                        return true;
                    }
                    last_channel_id = j;
                    return false;
                });
            }
            if (last_channel_id) {
                $("#c-channel_id option[value='" + last_channel_id + "']").prop("selected", true);
            }
            Controller.api.bindevent();
            $("#c-channel_id").trigger("change");
        },
        edit: function () {
            Controller.api.bindevent();
            $("#c-channel_id").trigger("change");
        },
        api: {
            formatter: {
                content: function (value, row, index) {
                    var extend = this.extend;
                    if (!value) {
                        return '';
                    }
                    var valueArr = value.toString().split(/\,/);
                    var result = [];
                    $.each(valueArr, function (i, j) {
                        result.push(typeof extend[j] !== 'undefined' ? extend[j] : j);
                    });
                    return result.join(',');
                }
            },
            bindevent: function () {
                var refreshStyle = function () {
                    var style = [];
                    if ($(".btn-bold").hasClass("active")) {
                        style.push("b");
                    }
                    if ($(".btn-color").hasClass("active")) {
                        style.push($(".btn-color").data("color"));
                    }
                    $("input[name='row[style]']").val(style.join("|"));
                };
                var insertHtml = function (html) {
                    if (typeof KindEditor !== 'undefined') {
                        KindEditor.insertHtml("#c-content", html);
                    } else if (typeof UM !== 'undefined' && typeof UM.list["c-content"] !== 'undefined') {
                        UM.list["c-content"].execCommand("insertHtml", html);
                    } else if (typeof UE !== 'undefined' && typeof UE.list["c-content"] !== 'undefined') {
                        UE.list["c-content"].execCommand("insertHtml", html);
                    } else if ($("#c-content").data("summernote")) {
                        $('#c-content').summernote('pasteHTML', html);
                    } else if (typeof Simditor !== 'undefined' && typeof Simditor.list['c-content'] !== 'undefined') {
                        Simditor.list['c-content'].setValue($('#c-content').val() + html);
                    } else {
                        Layer.open({
                            content: "你的编辑器暂不支持插入HTML代码，请手动复制以下代码到你的编辑器" + "<textarea class='form-control' rows='5'>" + html + "</textarea>", title: "温馨提示"
                        });
                    }
                };
                $(document).on("click", ".btn-paytag", function () {
                    insertHtml("##paidbegin##\n\n请替换付费标签内内容\n\n##paidend##");
                });
                $(document).on("click", ".btn-pagertag", function () {
                    insertHtml("##pagebreak##");
                });
                require(['jquery-autocomplete'], function () {
                    var search = $("#c-title");
                    var form = search.closest("form");
                    Template.helper("formatter", Table.api.formatter);
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
                            return Template('itemtpl', {item: item, search: search, context: {operate: false, searchList: {"normal": __('Status normal'), "hidden": __('Status hidden'), "rejected": __('Status rejected'), "pulloff": __('Status pulloff')}}});
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
                require(['jquery-colorpicker'], function () {
                    $('.colorpicker').colorpicker({
                        color: function () {
                            var color = "#000000";
                            var rgb = $("#c-title").css('color').match(/^rgb\(((\d+),\s*(\d+),\s*(\d+))\)$/);
                            if (rgb) {
                                color = rgb[1];
                            }
                            return color;
                        }
                    }, function (event, obj) {
                        $("#c-title").css('color', '#' + obj.hex);
                        $(event).addClass("active").data("color", '#' + obj.hex);
                        refreshStyle();
                    }, function (event) {
                        $("#c-title").css('color', 'inherit');
                        $(event).removeClass("active");
                        refreshStyle();
                    });
                });
                require(['jquery-tagsinput'], function () {
                    //标签输入
                    var elem = "#c-tags";
                    var tags = $(elem);
                    tags.tagsInput({
                        width: 'auto',
                        defaultText: '输入后空格确认',
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
                            url: 'cms/tag/autocomplete',
                            minChars: 1,
                            menuClass: 'autocomplete-tags'
                        }
                    });
                });
                //获取标题拼音
                var si;
                $(document).on("keyup", "#c-title", function () {
                    var value = $(this).val();
                    if (value != '' && !value.match(/\n/)) {
                        clearTimeout(si);
                        si = setTimeout(function () {
                            Fast.api.ajax({
                                loading: false,
                                url: "cms/ajax/get_title_pinyin",
                                data: {title: value, delimiter: "-"}
                            }, function (data, ret) {
                                $("#c-diyname").val(data.pinyin.substr(0, 100));
                                return false;
                            }, function (data, ret) {
                                return false;
                            });
                        }, 200);
                    }
                });
                $(document).on('click', '.btn-bold', function () {
                    $("#c-title").toggleClass("text-bold", !$(this).hasClass("active"));
                    $(this).toggleClass("text-bold active");
                    refreshStyle();
                });
                $(document).on('change', '#c-channel_id', function () {
                    var model = $("option:selected", this).attr("model");
                    var value = $("option:selected", this).val();
                    Fast.api.ajax({
                        url: 'cms/archives/get_fields_html',
                        data: {channel_id: value, archives_id: $("#archive-id").val()}
                    }, function (data) {
                        if ($("#extend").data("model") != model) {
                            $("#extend").html(data.html).data("model", model);
                            if (typeof data.publishfields !== 'undefined') {
                                $("#basic .form-group[data-field]").addClass("hidden");
                                $.each(data.publishfields, function (i, j) {
                                    $("#basic .form-group[data-field='" + j + "']").removeClass("hidden");
                                });
                            }
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
                //检测内容
                $(document).on("click", ".btn-legal", function (a) {
                    Fast.api.ajax({
                        url: "cms/ajax/check_content_islegal",
                        data: {content: $("#c-content").val()}
                    }, function (data, ret) {

                    }, function (data, ret) {
                        if ($.isArray(data)) {
                            if (data.length > 1) {
                                Layer.alert(__('Banned words') + "：" + data.join(","));
                            } else {
                                Layer.alert(ret.msg);
                            }
                            return false;
                        }
                    });
                });
                //提取关键字
                $(document).on("click", ".btn-keywords", function (a) {
                    Fast.api.ajax({
                        url: "cms/ajax/get_content_keywords",
                        data: {title: $("#c-title").val(), tags: $("#c-tags").val(), content: $("#c-content").val()}
                    }, function (data, ret) {
                        $("#c-keywords").val(data.keywords);
                        $("#c-description").val(data.description);
                    });
                });
                //提取缩略图
                $(document).on("click", ".btn-getimage", function (a) {
                    var image = $("<div>" + $("#c-content").val() + "</div>").find('img').first().attr('src');
                    if (image) {
                        var obj = $("#c-image");
                        if (obj.val() != '') {
                            Layer.confirm("缩略图已存在，是否替换？", {icon: 3}, function (index) {
                                obj.val(image).trigger("change");
                                layer.close(index);
                                Toastr.success("提取成功");
                            });
                        } else {
                            obj.val(image).trigger("change");
                            Toastr.success("提取成功");
                        }

                    } else {
                        Toastr.error("未找到任何图片");
                    }
                    return false;
                });
                //提取组图
                $(document).on("click", ".btn-getimages", function (a) {
                    var image = $("<div>" + $("#c-content").val() + "</div>").find('img').first().attr('src');
                    if (image) {
                        var imageArr = [];
                        $("<div>" + $("#c-content").val() + "</div>").find('img').each(function (i, j) {
                            if (i > 3) {
                                return false;
                            }
                            imageArr.push($(this).attr("src"));
                        });
                        image = imageArr.slice(0, 4).join(",");
                        var obj = $("#c-images");
                        if (obj.val() != '') {
                            Layer.confirm("文章组图已存在，是否替换？", {icon: 3}, function (index) {
                                obj.val(image).trigger("change");
                                layer.close(index);
                                Toastr.success("提取成功");
                            });
                        } else {
                            obj.val(image).trigger("change");
                            Toastr.success("提取成功");
                        }

                    } else {
                        Toastr.error("未找到任何图片");
                    }
                    return false;
                });
                $.validator.config({
                    rules: {
                        diyname: function (element) {
                            if (element.value.toString().match(/^\d+$/)) {
                                return __('Can not be only digital');
                            }
                            if (!element.value.toString().match(/^[a-zA-Z0-9\-_]+$/)) {
                                return __('Please input character or digital');
                            }
                            return $.ajax({
                                url: 'cms/archives/check_element_available',
                                type: 'POST',
                                data: {id: $("#archive-id").val(), name: element.name, value: element.value},
                                dataType: 'json'
                            });
                        },
                        isnormal: function (element) {
                            return $("#c-status").val() == 'normal' ? true : false;
                        }
                    }
                });
                var iscontinue = false;
                $(document).on("click", ".btn-continue", function () {
                    iscontinue = true;
                    $(this).prev().trigger("click");
                });
                Form.api.bindevent($("form[role=form]"), function () {
                    if (iscontinue) {
                        $(window).scrollTop(0);
                        location.reload();
                        top.window.Toastr.success(__('Operation completed'));
                        return false;
                    } else {
                        if (Config.cms.archiveseditmode == 'addtabs') {
                            var obj = top.window.$("ul.nav-addtabs li.active");
                            top.window.Toastr.success(__('Operation completed'));
                            top.window.$(".sidebar-menu a[url$='/cms/archives'][addtabs]").click();
                            top.window.$(".sidebar-menu a[url$='/cms/archives'][addtabs]").dblclick();
                            obj.find(".fa-remove").trigger("click");
                        }
                    }
                });
            }
        }
    };
    return Controller;
});
