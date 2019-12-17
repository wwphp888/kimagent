Vue.prototype.$ELEMENT = {size: 'small'};
Vue.prototype.baseUrl = '/';
/**
 * 提示框
 * @param msg
 * @param callback
 */
Vue.prototype.$Malert = function (msg, callback) {
    this.$alert(msg, '提示', {
        callback: action => {
            callback();
        }
    });
}

/**
 * 确认框
 * @param msg
 * @param callback
 */
Vue.prototype.$Mconfirm = function (msg, callback) {
    this.$confirm(msg, '提示').then(() => {
        callback();
    }).catch(() => {
    });
}

/**
 * 确认框
 * @param msg
 * @param callback
 */
Vue.prototype.$load = function () {
    return this.$loading({target: '.my-content'});
}

/**
 * post请求
 * @param url
 * @param success
 */
Vue.prototype.$post = function (url, data, success) {
    //let load = this.$load();
    $.post(this.baseUrl + url, data, ret => {
        // load.close();
        if (ret.code == 10010) {
            this.$Malert(ret.msg);
        } else if (ret.code == 1) {
            if (ret.msg) {
                this.$message.success(ret.msg);
            }
            if (typeof success == 'function') {
                success(ret.data);
            }
        } else {
            this.$message.error(ret.msg);
        }
    }, 'json');
}

/**
 * post请求
 * @param url
 * @param success
 */
Vue.prototype.$get = function (url, success) {
    //let load = this.$load();
    $.get(this.baseUrl + url, ret => {
        // load.close();
        if (ret.code == 10010) {
            this.$Malert(ret.msg);
        } else if (ret.code == 1) {
            if (ret.msg) {
                this.$message.success(ret.msg);
            }
            if (typeof success == 'function') {
                success(ret.data);
            }
        } else {
            this.$message.error(ret.msg);
        }
    }, 'json');
}

/**
 * 得到唯一的ID
 */
Vue.prototype.$guid = function () {
    function S4() {
        return (((1 + Math.random()) * 0x10000) | 0).toString(16).substring(1);
    }

    return (S4() + S4() + "-" + S4() + "-" + S4() + "-" + S4() + "-" + S4() + S4() + S4());
}

/**
 * 得到唯一的ID
 */
Vue.prototype.$toPage = function (url) {
    // $.cookie('curPage', url);
    // if (id) {
    //     $.cookie('curPageId', id);
    // }
    $.get(this.baseUrl + url, html => {
        $('.content').html(html);
    });
}


/**
 * 时间转换
 * @param value
 * @return ''
 */
Vue.filter('formatDate', function (value) {
    if (!value) {
        return '';
    }
    let date = new Date(value*1000);
    let y = date.getFullYear();
    let MM = date.getMonth() + 1;
    MM = MM < 10 ? ('0' + MM) : MM;
    let d = date.getDate();
    d = d < 10 ? ('0' + d) : d;
    let h = date.getHours();
    h = h < 10 ? ('0' + h) : h;
    let m = date.getMinutes();
    m = m < 10 ? ('0' + m) : m;
    let s = date.getSeconds();
    s = s < 10 ? ('0' + s) : s;
    return y + '-' + MM + '-' + d + ' ' + h + ':' + m + ':' + s;
})
