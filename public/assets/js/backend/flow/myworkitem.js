define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'flow/myworkitem/index',
                    add_url: 'flow/myworkitem/add',
                    edit_url: 'flow/myworkitem/edit',
                    del_url: 'flow/myworkitem/del',
                    multi_url: 'flow/myworkitem/multi',
                    table: 'flow_task',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'createtime',
                sortOrder: "desc",
                selectSingle: true,
                searchFormVisible: true,
                columns: [
                    [
                        { field: 'id', title: __('id'), visible: false, operate: false },
                        { field: 'url', title: __('url'), visible: false, operate: false },
                        { field: 'originator', title: __('originator'), visible: false, operate: false },
                        { field: 'bizobjectid', title: __('业务表id'), visible: false, operate: false },
                        { field: 'schemeid', visible: false, operate: false },
                        { field: 'frmtype', visible: false, operate: false },
                        { field: 'instancestatus', visible: false, operate: false },
                        { field: 'flowcode', visible: false, operate: false },
                        { field: 'instancecode', title: __('流水号'), operate: "LIKE", formatter: Controller.api.formatter.browser, events: Controller.api.events.browser },
                        { field: 'flowname', title: '流程名称', operate: "LIKE" },
                        { field: 'stepname', title: '步骤', operate: false, operate: "LIKE" },
                        { field: 'nickname', title: __('Nickname'), operate: "LIKE" },
                        { field: 'createtime', title: __('createtime'), operate: 'RANGE', addclass: 'datetimerange' },
                        { field: 'completedtime', title: __('Completedtime'), operate: 'RANGE', addclass: 'datetimerange' },
                        { field: 'status', title: '状态', formatter: Controller.api.formatter.status, operate: false }
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        add: function () {
            Controller.api.bindevent();
        },
        detail: function () {

        },
        edit: function () {
            Controller.api.bindevent();
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            },
            formatter: {
                status: function (value, row, index) {
                    var res = '';
                    var className = '';
                    switch (row.instancestatus) {
                        case 0:
                            res = '待提交';
                            className = 'primary';
                            break;
                        case 1:
                            res = '审批中';
                            className = 'info';
                            break;
                        case 2:
                            res = '已完成';
                            className = 'success';
                            break;
                        case 3:
                            res = '已取消';
                            className = 'success';
                            break;
                        default:
                            break;
                    }
                    return '<a href="javascript:;" class="searchit" data-toggle="tooltip" "><span class="label label-' + className + '">' + res + '</span></a>';
                },
                browser: function (value, row, index) {
                    //这里我们直接使用row的数据
                    return '<a class="btn btn-xs btn-browser">' + value + '</a>';
                },
            },
            events: {//绑定事件的方法
                browser: {
                    'click .btn-browser': function (e, value, row, index) {
                        e.stopPropagation();
                        var url = row.url;
                        if (url != '' && url != null) {
                            url = url + '/edit?ids=' + row.bizobjectid + '&taskid=' + row.id;
                            if (url.indexOf("{ids}") !== -1) {
                                url = Table.api.replaceurl(url, { ids: ids.length > 0 ? ids.join(",") : 0 }, table);
                            }
                        }
                        else {
                            url = 'flow/' + row.flowcode + '/edit?ids=' + row.bizobjectid + '&taskid=' + row.id;
                            if (url.indexOf("{ids}") !== -1) {
                                url = Table.api.replaceurl(url, { ids: ids.length > 0 ? ids.join(",") : 0 }, table);
                            }
                        }
                        var title = row.flowname + '(' + row.instancecode + ')';
                        if (row.frmtype == '2') {
                            Fast.api.open(url, title, {});
                        }
                        else {
                            var area = [$(window).width() > 800 ? '800px' : '95%', $(window).height() > 600 ? '600px' : '95%'];
                            var mode  = row.instancestatus == 0 ?'submit':'edit';
                            url = Config.moduleurl + "/flow/formbuild/form?mode="+mode+"&ids=" + row.schemeid + "&bizobjectid=" + row.bizobjectid + '&taskid=' + row.id;
                            Layer.open({
                                btnAlign: 'c',
                                title: title,
                                zIndex: 100,
                                type: 2,
                                maxmin: true,
                                area: area,
                                content: url,
                                success: function (layero, index) {
                                    var that = this;
                                    Controller.api.layerfooter(layero, index, that,row);
                                },
                            })
                        }
                    }
                },
            },
            layerfooter: function (layero, index, that, row) {
                var frame = Layer.getChildFrame('html', index);
                var iframeWin = window[layero.find('iframe')[0]['name']];
                var layerfooter = '<div class="form-group layer-footer"><div class="col-xs-12 col-sm-12" style="text-align: center;">';
                var para = "?mode=edit&ids=" + row.schemeid + "&bizobjectid=" + row.bizobjectid + '&taskid=' + row.id;
                if(row.instancestatus==0){
                   layerfooter+='<button type="button" id="start" class="btn btn-success btn-embossed" style="margin-left:5px">提交</button>';
                }
                if(row.instancestatus==1){
                   layerfooter+='<button type="button" id="agree" class="btn btn-success btn-embossed" style="margin-left:5px">同意</button>'+
                   '<button type="submit" id="refuse" class="btn btn-danger btn-embossed" style="margin-left:5px">拒绝</button>';
                }
                if(row.originator==Config.adminId){
                    layerfooter+='<button type="button" id="cancel" class="btn btn-danger btn-embossed" style="margin-left:5px">取消</button>';
                }
                layerfooter+='<button type="button" id="flowchart" class="btn btn-warning btn-embossed" style="margin-left:5px">流程图</button>';
                if (layerfooter != '') {
                    $(".layui-layer-footer", layero).remove();
                    var footer = $("<div />").addClass('layui-layer-btn layui-layer-footer');
                    footer.html(layerfooter);
                    if ($(".row", footer).size() === 0) {
                        $(">", footer).wrapAll("<div class='row'></div>");
                    }
                    footer.insertAfter(layero.find('.layui-layer-content'));
                    layero.find('#agree').click(function () {                     
                        var data = iframeWin.agree(index,para);
                    })
                    layero.find('#start').click(function () {
                        var data = iframeWin.agree(index,para);
                    })
                    layero.find('#cancel').click(function () {
                        Layer.confirm(__('确定要拒绝流程吗?', that), { icon: 3, title: __('Warning'), offset: 150, shadeClose: true }, function (chindex) {
                            iframeWin.cancel(index, para);
                        });
                    })
                    layero.find('#refuse').click(function () {
                        Layer.confirm(__('确定要拒绝流程吗?', that), { icon: 3, title: __('Warning'), offset: 150, shadeClose: true }, function (chindex) {
                            var layerIndex =  Layer.load(0);
                            layero.find('#refuse').attr("disabled",true);
                            iframeWin.refuse(index, para);
                            layer.close(chindex);
                        });
                    })
                    layero.find('#flowchart').click(function () {
                        Fast.api.open('flow/scheme/flowchart?ids=' + row.id + '&flowcode=' + row.flowcode+ '&taskid=' + row.id, '流程图', {});
                        return false;
                    })
                    var titHeight = layero.find('.layui-layer-title').outerHeight() || 0;
                    var btnHeight = layero.find('.layui-layer-btn').outerHeight() || 0;
                    //重设iframe高度
                    $("iframe", layero).height(layero.height() - titHeight - btnHeight);
                }
                //修复iOS下弹出窗口的高度和iOS下iframe无法滚动的BUG
                if (/iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream) {
                    var titHeight = layero.find('.layui-layer-title').outerHeight() || 0;
                    var btnHeight = layero.find('.layui-layer-btn').outerHeight() || 0;
                    $("iframe", layero).parent().css("height", layero.height() - titHeight - btnHeight);
                    $("iframe", layero).css("height", "100%");
                }
            },
        }
    };
    return Controller;
});