define([], function () {
    require.config({
    paths: {
        'fastflow': "../addons/fastflow/js/fastflow",
		'fastflowbase': "../addons/fastflow/js/fastflowbase",
        'fastflow-fixed-columns': "../addons/fastflow/js/fastflow-fixed-columns",
    },
    shim: {
        'fastflow': {
            exports: 'fastflow'
        },
    }
});

$.ajax({
    type: "POST",
    url: "fastflow/flow/bill/getBadge",
    data: {},
    dataType: "json",
    success: function (data) {
        if (data['code'] == 1) {
            data['data'].forEach(function (item) {
                if (item['count'] > 0) {
                    $('a[addtabs=' + item['id'] + ']').append('<span class="pull-right-container fastflow-badge" style="margin-right: 20px"> <small class="' + item['shape'] + ' pull-right ' + item['color'] + '">' + item['show'] + '</small></span>');
                }
            });
        }
    }
});




});