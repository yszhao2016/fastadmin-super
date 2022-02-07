define(['vue','formdesign'], function (Vue,undefined) {

    var Controller = {
        index: function () {
            debugger;
            let jsonData = { list: [{ "type": "input", "name": "单行文本", "options": { "width": "100%", "defaultValue": "", "placeholder": "请输入", "disabled": false }, "model": "input_1574002292465", "key": "input_1574002292465", "rules": [{ "required": false, "message": "必填项" }] }], "config": { "layout": "horizontal", "labelCol": { "span": 4 }, "wrapperCol": { "span": 18 }, "hideRequiredMark": false, "width": "100%", "marginTop": "0px", "marginRight": "0px", "marginBottom": "0px", "marginLeft": "0px" } }
            let vm = new Vue({
                el: '#app',
                data: {
                    jsonData
                },
                methods: {
                    handleSubmit() {
                    }
                }
            })
        },
        add: function () {

        },
        edit: function () {

        },
        api: {
            bindevent: function () {

            }
        }
    };
    return Controller;
});