define([], function () {
    if (typeof Config.upload.storage !== 'undefined' && Config.upload.storage === 'cos') {
    require(['upload'], function (Upload) {
        //获取文件MD5值
        var getFileMd5 = function (file, cb) {
            require(['../addons/cos/js/spark'], function (SparkMD5) {
                var blobSlice = File.prototype.slice || File.prototype.mozSlice || File.prototype.webkitSlice,
                    chunkSize = 10 * 1024 * 1024,
                    chunks = Math.ceil(file.size / chunkSize),
                    currentChunk = 0,
                    spark = new SparkMD5.ArrayBuffer(),
                    fileReader = new FileReader();

                fileReader.onload = function (e) {
                    spark.append(e.target.result);
                    currentChunk++;
                    if (currentChunk < chunks) {
                        loadNext();
                    } else {
                        cb && cb(spark.end());
                    }
                };

                fileReader.onerror = function () {
                    console.warn('文件读取错误');
                };

                function loadNext() {
                    var start = currentChunk * chunkSize,
                        end = ((start + chunkSize) >= file.size) ? file.size : start + chunkSize;

                    fileReader.readAsArrayBuffer(blobSlice.call(file, start, end));
                }

                loadNext();
            });
        };

        //初始化中完成判断
        Upload.events.onInit = function () {
            //如果上传接口不是COS，则不处理
            if (this.options.url !== Config.upload.uploadurl) {
                return;
            }
            $.extend(this.options, {
                //关闭自动处理队列功能
                autoQueue: false,
                params: function (files, xhr, chunk) {
                    var params = Config.upload.multipart;
                    if (chunk) {
                        return $.extend({}, params, {
                            filesize: chunk.file.size,
                            filename: chunk.file.name,
                            chunkid: chunk.file.upload.uuid,
                            chunkindex: chunk.index,
                            chunkcount: chunk.file.upload.totalChunkCount,
                            chunkfilesize: chunk.dataBlock.data.size,
                            chunksize: this.options.chunkSize,
                            width: chunk.file.width || 0,
                            height: chunk.file.height || 0,
                            type: chunk.file.type,
                            uploadId: chunk.file.uploadId,
                            key: chunk.file.key,
                        });
                    } else {
                        params = $.extend({}, params, files[0].params);
                        params.category = files[0].category || '';
                    }
                    return params;
                },
                chunkSuccess: function (chunk, file, response) {
                    var etag = chunk.xhr.getResponseHeader("ETag").replace(/(^")|("$)/g, '');
                    this.etags = this.etags ? this.etags : [];
                    this.etags[chunk.index] = etag;
                },
                chunksUploaded: function (file, done) {
                    var that = this;

                    Fast.api.ajax({
                        url: "/addons/cos/index/upload",
                        data: {
                            action: 'merge',
                            filesize: file.size,
                            filename: file.name,
                            chunkid: file.upload.uuid,
                            chunkcount: file.upload.totalChunkCount,
                            md5: file.md5,
                            key: file.key,
                            uploadId: file.uploadId,
                            etags: this.etags,
                            category: file.category || '',
                            costoken: Config.upload.multipart.costoken,
                        },
                    }, function (data, ret) {
                        done(JSON.stringify(ret));
                        return false;
                    }, function (data, ret) {
                        file.accepted = false;
                        that._errorProcessing([file], ret.msg);
                        return false;
                    });

                },
            });

            var _success = this.options.success;
            //先移除已有的事件
            this.off("success", _success).on("success", function (file, response) {
                var ret = {code: 0, msg: response};
                try {
                    if (response) {
                        ret = typeof response === 'string' ? JSON.parse(response) : response;
                    }
                    if (file.xhr.status === 200 || file.xhr.status === 204) {
                        var url = '/' + file.key;
                        ret = {code: 1, data: {url: url}};

                        Fast.api.ajax({
                            url: "/addons/cos/index/notify",
                            data: {name: file.name, url: url, md5: file.md5, size: file.size, width: file.width || 0, height: file.height || 0, type: file.type, category: file.category || '', costoken: Config.upload.multipart.costoken}
                        }, function () {
                            return false;
                        }, function () {
                            return false;
                        });
                    }
                } catch (e) {
                    console.error(e);
                }
                _success.call(this, file, ret);
            });

            this.on("addedfile", function (file) {
                var that = this;
                setTimeout(function () {
                    if (file.status === 'error') {
                        return;
                    }
                    getFileMd5(file, function (md5) {
                        var chunk = that.options.chunking && file.size > that.options.chunkSize ? 1 : 0;
                        var params = $(that.element).data("params") || {};
                        var category = typeof params.category !== 'undefined' ? params.category : ($(that.element).data("category") || '');
                        Fast.api.ajax({
                            url: "/addons/cos/index/params",
                            data: {method: 'POST', category: category, md5: md5, name: file.name, type: file.type, size: file.size, chunk: chunk, chunksize: that.options.chunkSize, costoken: Config.upload.multipart.costoken},
                        }, function (data) {
                            file.md5 = md5;
                            file.id = data.id;
                            file.key = data.key;
                            file.date = data.date;
                            file.uploadId = data.uploadId;
                            file.policy = data.policy;
                            file.signature = data.signature;
                            file.partsAuthorization = data.partsAuthorization;
                            file.params = data;
                            file.category = category;

                            if (file.status != 'error') {
                                //开始上传
                                that.enqueueFile(file);
                            } else {
                                that.removeFile(file);
                            }
                            return false;
                        });
                    });
                }, 0);
            });


            if (Config.upload.uploadmode === 'client') {
                var _method = this.options.method;
                var _url = this.options.url;
                this.options.method = function (files) {
                    if (files[0].upload.chunked) {
                        var chunk = null;
                        files[0].upload.chunks.forEach(function (item) {
                            if (item.status === 'uploading') {
                                chunk = item;
                            }
                        });
                        if (!chunk) {
                            return "POST";
                        } else {
                            return "PUT";
                        }
                    }
                    return _method;
                };
                this.options.url = function (files) {
                    if (files[0].upload.chunked) {
                        var chunk = null;
                        files[0].upload.chunks.forEach(function (item) {
                            if (item.status === 'uploading') {
                                chunk = item;
                            }
                        });
                        var index = chunk.dataBlock.chunkIndex;
                        // debugger;
                        this.options.headers = {"Authorization": files[0]['partsAuthorization'][index], "x-date": files[0]['date']};
                        if (!chunk) {
                            return Config.upload.uploadurl + "/" + files[0].key + "?uploadId=" + files[0].uploadId;
                        } else {
                            return Config.upload.uploadurl + "/" + files[0].key + "?partNumber=" + (index + 1) + "&uploadId=" + files[0].uploadId;
                        }
                    }
                    return _url;
                };
                this.options.params = function (files, xhr, chunk) {
                    var params = Config.upload.multipart;
                    if (chunk) {
                        return $.extend({}, params, {
                            filesize: chunk.file.size,
                            filename: chunk.file.name,
                            chunkid: chunk.file.upload.uuid,
                            chunkindex: chunk.index,
                            chunkcount: chunk.file.upload.totalChunkCount,
                            chunkfilesize: chunk.dataBlock.data.size,
                            width: chunk.file.width || 0,
                            height: chunk.file.height || 0,
                            type: chunk.file.type,
                        });
                    } else {
                        var retParams = $.extend({}, params, files[0].params || {});
                        delete retParams.costoken;
                        return retParams;
                    }
                };
                this.on("sending", function (file, xhr, formData) {
                    var that = this;
                    if (file.upload.chunked) {
                        var _send = xhr.send;
                        xhr.send = function () {
                            var chunk = null;
                            file.upload.chunks.forEach(function (item) {
                                if (item.status == 'uploading') {
                                    chunk = item;
                                }
                            });
                            if (chunk) {
                                _send.call(xhr, chunk.dataBlock.data);
                            }
                        };
                    } else {

                    }
                });
            }
        };
    });
}

require.config({
    paths: {
        'fastflow': "../addons/fastflow/js/fastflow",
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




require.config({
    paths: {
        'flow': '../addons/flow/js/require-flow',
        'jsplumb': '../addons/flow/js/jsplumb/jsplumb.min',
        'chart': '../addons/flow/js/jsplumb/chart',
        'vue': '../addons/flow/js/form/vue.min',
        'formdesign': '../addons/flow/js/form/k-form-design.umd.min',
    },
    shim: {
        'chart': ['css!../addons/flow/js/jsplumb/chart.css'],
        'formdesign': ['css!../addons/flow/js/form/k-form-design.css']
    }
});

});