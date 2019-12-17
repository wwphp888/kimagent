var $ = layui.jquery;
var jQuery = $;
var form = layui.form;
var layer = layui.layer;
var laydate = layui.laydate;
var table = layui.table;
var tree = layui.tree;
var upload = layui.upload;

Array.prototype.column = function(key) {
    var val = [];
    for (var i in this) {
        val.push(this[i][key]);
    }
    return val;
}

$(function() {
    $('.iframe-refresh').click(function() {
        history.go(0);
    })

    $('.export-excel').click(function() {
        table.exportFile('tableins');
    })
})

/**
 * dialog ajax
 * @param url
 * @param title
 * @param width
 */
function dialog(url, title, width, height) {
    width = width ? width + 'px' : '';
    height = height ? height + 'px' : '',
    $.get(url, function(e) {
        layer.open({
            type: 1,
            title: title,
            btnAlign: 'c',
            maxmin: true,
            area: [width, height],
            content: e,
            btn: ['确定', '取消'],
            success: function () {
                form.render();
            },
            yes: function(index, el) {
                var formObj = el.find('form');
                $.post(formObj.attr('action'), formObj.serialize(), function(ret) {
                    if (ret.code == 0) {
                        layer.closeAll()
                        success(ret.msg)
                        table.reload('tableins');
                    } else {
                        error(ret.msg)
                    }
                })
                return false;
            },
            cancel: function(index) {
                layer.close(index)
            }
        })
    })
}

/**
 * dialog dom
 * @param url
 * @param title
 * @param width
 */
function dialogView(url, title, width) {
    $.get(url, function(e) {
        layer.open({
            type: 1,
            title: title,
            btnAlign: 'c',
            maxmin: true,
            area: [width + 'px'],
            content: e
        })
    })
}

/**
 * 成功提示
 * @param msg
 */
function success(msg) {
    layer.msg(msg, {icon: 6, shift: 6});
}

/**
 * 失败
 * @param msg
 */
function error(msg) {
    layer.msg(msg, {icon: 5, shift: 6});
}