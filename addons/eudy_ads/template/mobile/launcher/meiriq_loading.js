var meiriq_loading;

meiriq_loading = (function() {
    function meiriq_loading(loading_msg, background_color, loading_bar_color, loading_bar_bg, running_time) {
        this.loading_msg = loading_msg;
        this.background_color = background_color;
        this.loading_bar_color = loading_bar_color;
        this.loading_bar_bg = loading_bar_bg;
        this.running_time = running_time;
        if (!this.loading_msg) {
            this.loading_msg = "游戏加载中…";
        }
        if (!this.background_color) {
            this.background_color = "#fff";
        }
        if (!this.loading_bar_color) {
            this.loading_bar_color = "#ff671b";
        }
        if (!this.loading_bar_bg) {
            this.loading_bar_bg = "#ebebeb";
        }
        if (!this.running_time) {
            this.running_time = 2;
        }
    }

    meiriq_loading.prototype.start = function() {
        var dom, loading_bar_h, p_margin_top;
        dom = document.createElement('div');
        dom.setAttribute("id", "meiriq-loading");
        dom.style.position = 'absolute';
        dom.style.width = '100%';
        dom.style.height = '100%';
        dom.style.top = '0';
        dom.style.bottom = '0';
        dom.style.left = '0';
        dom.style.right = '0';
        dom.style.zIndex = 999;
        dom.style.backgroundColor = this.background_color;
        if (window.innerWidth > window.innerHeight) {
            p_margin_top = 5;
            loading_bar_h = 3;
        } else {
            p_margin_top = 8;
            loading_bar_h = 2;
        }
        dom.innerHTML = '<div id="meiriq-loading-bar" style="position: absolute;top: 49%;right: 5%;width: 90%;height: ' + loading_bar_h + '%;background-color: ' + this.loading_bar_bg + ';text-align: center;"><p style="position: absolute;margin: 0;padding: 0;font-family: SimHei;font-size: 17px;color: #000;margin-top: -' + p_margin_top + '%;width: 100%;">' + this.loading_msg + '</><div id="meiriq-progress" style="height: 100%;width: 1%;background-color: ' + this.loading_bar_color + ';-webkit-transition: width ' + this.running_time + 's;"></div></div><img width="" height="" style="">';
        document.body.insertBefore(dom, document.body.childNodes[0]);
        return document.addEventListener("DOMContentLoaded", function(event) {
            return document.getElementById('meiriq-progress').style.width = "100%";
        });
    };

    meiriq_loading.prototype.end = function() {
        var elem;
        return (elem = document.getElementById('meiriq-loading')).parentNode.removeChild(elem);
    };

    return meiriq_loading;

})();