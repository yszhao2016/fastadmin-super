define(['gdmap'], function f() {
    var Controller = {
        index: function () {
            // 初始化表格参数配置
            var map = new AMap.Map('container', {
                resizeEnable: true,
                center: [119, 33],
                zoom: 13
            });

            function createInfoWindow(title, content) {
                var info = document.createElement("div");
                info.className = "custom-info input-card content-window-card";

                //可以通过下面的方式修改自定义窗体的宽高
                info.style.width = "250px";
                // 定义顶部标题
                var top = document.createElement("div");
                var titleD = document.createElement("div");
                var closeX = document.createElement("img");
                top.className = "info-top";
                titleD.innerHTML = title;
                closeX.src = "https://webapi.amap.com/images/close2.gif";
                closeX.onclick = closeInfoWindow;

                top.appendChild(titleD);
                top.appendChild(closeX);
                info.appendChild(top);

                // 定义中部内容
                var middle = document.createElement("div");
                middle.className = "info-middle";
                middle.style.backgroundColor = 'white';
                middle.innerHTML = content;
                info.appendChild(middle);

                // 定义底部内容
                var bottom = document.createElement("div");
                bottom.className = "info-bottom";
                bottom.style.position = 'relative';
                bottom.style.top = '0px';
                bottom.style.margin = '0 auto';
                var sharp = document.createElement("img");
                sharp.src = "https://webapi.amap.com/images/sharp.png";
                bottom.appendChild(sharp);
                info.appendChild(bottom);
                return info;
            }

            //关闭信息窗体
            function closeInfoWindow() {
                map.clearInfoWindow();
            }

            map.clearMap();  // 清除地图覆盖物


            var markers = JSON.parse(Config.list);

            //添加一些分布不均的点到地图上,地图上添加三个点标记，作为参照
            markers.forEach(function (marker) {

                var point = new AMap.Marker({
                    map: map,
                    icon: marker.icon,
                    position: [marker.lon, marker.lat],
                    offset: new AMap.Pixel(-13, -30)
                });
                point.on('click', function () {
                    infoWindow.open(map, point.getPosition());
                });

                // 设置鼠标划过点标记显示的文字提示
                point.setTitle('marker.site_name');

                // 设置label标签
                // label默认蓝框白底左上角显示，样式className为：amap-marker-label
                point.setLabel({
                    direction: 'right',
                    offset: new AMap.Pixel(0, 0),  //设置文本标注偏移量
                    content: "<div style='font-weight: bold'>" + marker.site_name + "</div>", //设置文本标注内容
                });
                //实例化信息窗体
                var title = marker.site_name,
                    content = [];
                content.push("所属于区县:" + marker.address);
                content.push("所属于园区:" + marker.industrial_park);

                if (marker.data) {
                    content.push("-------------------------------------------------");
                    content.push("检测时间:" + marker.data.cp_datatime_text);
                    for (let i in  marker.data.pollution) {
                        if (marker.data.pollution[i].name) {
                            content.push(  marker.data.pollution[i].name+"&nbsp：&nbsp" + marker.data.pollution[i].min + '~' + marker.data.pollution[i].max);
                        }
                    }
                }
                var infoWindow = new AMap.InfoWindow({
                    isCustom: true,  //使用自定义窗体
                    content: createInfoWindow(title, content.join("<br/>")),
                    offset: new AMap.Pixel(16, -45)
                });

            });
            var center = map.getCenter();
            map.setFitView();
        },
    };
    return Controller;
})