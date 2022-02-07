define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {
    var flowDesignPanel;
    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'flow/start',
                    add_url: 'flow/scheme/add',
                    edit_url: '',
                    del_url: '',
                    table: 'flow_scheme',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                singleSelect: true,
                sortName: 'id',
                columns: [
                    [
                        { field: 'id', title: 'id', visible: false, operate: false },
                        { field: 'flowcode', title: '流程代码', operate: 'like' },
                        { field: 'flowname', title: '流程名称', operate: 'like' },
                        { field: 'frmtype', title: '表单方式', visible: false, operate: false },
                        { field: 'opentype', title: '表单方式', visible: false, operate: false },
                        { field: 'operate', title: __('Operate'), table: table, events: Controller.api.flowCode, formatter: Controller.api.formatter.flowCode }
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        add: function () {
            Controller.api.bindevent();
        },
        edit: function () {
            var options = JSON.parse($("input[name='row[flowcontent]']").val());
            var schemeContent = options;
            flowDesignPanel = $('#flow').flowdesign({
                height: 800,
                widht: 800,
                nodeData: schemeContent.nodes,
                flowcontent: schemeContent,
                OpenNode: function (object) {
                },
                OpenLine: function (id, object) {
                    return;
                }
            })
            document.getElementById("ok").addEventListener('click', function () {
                var content = flowDesignPanel.exportDataEx();
                var schemecontent = JSON.stringify(content);
                $("input[name='row[flowcontent]']").val(schemecontent);
            }, false)
            Controller.api.bindevent();
        },
        api: {
            flowCode: {
                'click .btnStart': function (e, value, row, index) {
                    e.stopPropagation();
                    var url = '';
                    if (row.frmtype == '2') {
                        url = Config.moduleurl + "/flow/" + row.flowcode + "/add?ids=" + row.id;
                        debugger;
                        Fast.api.open(url,row.flowname,{})
                    }
                    else {
                        var area = [$(window).width() > 800 ? '800px' : '95%', $(window).height() > 600 ? '600px' : '95%'];
                        url = Config.moduleurl + "/flow/formbuild/form?ids=" + row.id+"&mode=start";
                        Layer.open({
                            btnAlign: 'c',
                            title: row.flowname,
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
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            },
            layerfooter: function (layero, index, that,row) {
                var frame = Layer.getChildFrame('html', index);
                var layerfooter = '<div class="form-group layer-footer">'+
                '<div class="col-xs-12 col-sm-12" style="text-align: center;">'+
                    '<button id="save" type="button" class="btn btn-success btn-embossed" style="margin-left:5px">保存</button>'+
                    '<button id="start" type="button" class="btn btn-info btn-embossed" style="margin-left:5px">提交</button>'+
                    '<button type="button" id="flowchart" class="btn btn-warning btn-embossed" style="margin-left:5px">流程图</button></div></div>';
                if (layerfooter!='') {
                    $(".layui-layer-footer", layero).remove();
                    var footer = $("<div />").addClass('layui-layer-btn layui-layer-footer');
                    footer.html(layerfooter);
                    if ($(".row", footer).size() === 0) {
                        $(">", footer).wrapAll("<div class='row'></div>");
                    }
                    footer.insertAfter(layero.find('.layui-layer-content'));
                    layero.find('#save').click(function(){                       
                        var iframeWin = window[layero.find('iframe')[0]['name']];                     
                        var data = iframeWin.save(index);
                    })
                    layero.find('#start').click(function(){
                        var iframeWin = window[layero.find('iframe')[0]['name']];                       
                        var data = iframeWin.start(index);
                    })
                    layero.find('#flowchart').click(function(){
                        Fast.api.open('flow/scheme/flowchart?ids=' + row.id + '&flowcode=' + row.flowcode, '流程图', {});
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
            formatter: {
                flowCode: function (value, row, index) {
                    return '<button id="btnStart" type="button" class="btn btn-xs btn-primary btnStart" singleSelected=true>发起流程</button>'
                }
            }
        }
    };
    return Controller;
});