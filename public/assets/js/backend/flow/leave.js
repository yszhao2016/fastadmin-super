define(['jquery', 'bootstrap', 'backend', 'form', 'flow'], function ($, undefined, Backend, Form, Flow) {

    var Controller = {
        add: function () {
            Controller.api.bindevent();
        },
        edit: function () {
            Controller.api.bindevent();
        },
        api: {
            bindevent: function () {
                Flow.bindevents();
            }
        }
    };
    return Controller;
});