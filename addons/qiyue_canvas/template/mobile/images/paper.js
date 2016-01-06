(function() {
    function n(d) {
        return document.getElementById(d)
    }

    function e() {
        function u() {
            "number" == typeof window.orientation && 0 !== window.orientation || "function" != typeof e || location.reload()
        }
        window.onorientationchange = u;
        if ("number" == typeof window.orientation && 0 !== window.orientation) {
            alert("请旋转您的手机为竖立状态，在横屏界面下无法操作！"), u()
        } else {
            e = null;
            var s = document.body.clientWidth,
                t = s / 640;
            document.body.style.fontSize = 16 * t + "px";
            document.body.style.width = s + "px";
            var g = n("canvas"),
                r = n("paperLogo");
            g.width = 590 * t;
            g.height = 590 * t;
            var p = new a(g);
            p.b = s;
            document.getElementById("clearCanvas").onclick = function() {
                p.A();
                c = 0;
                f = ""
            };
            r = new Image;
            r.src = j;
            // r.src = "data:image/png;base64," + j;
            document.ontouchmove = function(h) {
                h.preventDefault()
            };
            var k = 0,
                m = n("palette"),
                d = n("penColor");
            n("btnPenColor").onclick = function() {
                k ? (k = 0, m.style.display = "none") : (k = 1, m.style.display = "block")
            };
            m.onclick = function(h) {
                h = h.target;
                if ("LI" == h.tagName) {
                    var q = h.getAttribute("value");
                    p.color = q;
                    d.style.background = h.style.background;
                    k = 0;
                    m.style.display = "none"
                }
            };
            n("shareBtn").onclick = function() {
                if (0 == p.k) {
                    alert("别逗了，我怎么什么都没看到哪！")
                } else {
                    if (20 > p.k) {
                        alert("不要这么任性，这也太简洁了！")
                    } else {
                        if (c == p.k && "" != f) {
                            l("share")
                        } else {
                            c = p.k;
                            l("upload", "<p>生成图片中...</p>");
                            var h, q = p.B("jpeg", 0.6);
                            "data:image/png" == q.substr(0, 14) ? (q = q.substr(22), h = "png") : (q = q.substr(23), h = "jpg");
                            if ("png" == h) {
                                var v = -1 !== navigator.userAgent.indexOf("Messenger");
                                if (-1 === navigator.userAgent.indexOf("NetType/WIFI") && v && !confirm("生成的图片为" + Math.round(q.length / 1024) + "k，是否上传？")) {
                                    l("editor");
                                    return
                                }
                            }
                            l("upload", "<p>上传图片中(共" + Math.round(q.length / 1024) + ")</p>");
                            o(q, h)
                        }
                    }
                }
            };
            n("toEditorBtn").onclick = function() {
                l("editor")
            };
            (s = location.href.match(/canvas&pic=(\d+)/)) ? (l("show", window.sysinfo.attachurl + "images/" + window.sysinfo.uniacid + "/" + s[1].substr(0, 4) + "/" + s[1].substr(4, 2) + "/" + s[1] + ".jpg"), window.localStorage && localStorage.onewordId == s[1] && (n("saveMemo").style.display = "block", setShareData(window.shareData.title, window.sysinfo.siteurl + "&pic=" + s[1], window.sysinfo.attachurl + "images/" + window.sysinfo.uniacid + "/" + s[1].substr(0, 4) + "/" + s[1].substr(4, 2) + "/" + s[1] + ".jpg"))) : l("editor")
        }
    }

    function o(g, h) {
        var d = new XMLHttpRequest;
        h = h || "jpg";
        d.onreadystatechange = function() {
            if (4 == d.readyState && 200 == d.status) {
                var k = d.responseText;
                k && /^\d+$/.test(k) ? (f = window.sysinfo.attachurl + "images/" + window.sysinfo.uniacid + "/" + k.substr(0, 4) + "/" + k.substr(4, 2) + "/" + k + ".jpg", setShareData(window.shareData.title, window.sysinfo.siteurl + "&pic=" + k, f), l("share"), window.localStorage && (localStorage.onewordId = k)) : (alert("上传失败，请稍后再试！"), l("editor"))
            } else {
                4 == d.readyState && 200 !== d.status && (alert("网络忙，请稍后再试！code:" + d.status), l("editor"))
            }
        };
        d.ontimeout = function() {
            alert("上传超时，请在较好的网络环境中再试！");
            l("editor")
        };
        d.upload && (d.upload.onprogress = function(k) {
            k.lengthComputable && l("upload", "<p>图片上传中<span>(共" + Math.round(k.total / 1024) + "k/进度" + Math.round(k.loaded / k.total * 100) + "%)</span></p>")
        });
        d.open("POST", window.sysinfo.siteurl + "&a=upload", !0);
        d.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
        g = "dataStr=" + encodeURIComponent(g) + "&type=" + h;
        l("upload", "<p>图片上传中(" + Math.round(g.length / 1024) + "k)...</p>");
        d.send(g)
    }

    function l(d, g) {
        "upload" == d && "" != g ? (i(), n("editorPage").style.display = "block", n("uploadPage").style.display = "block", n("uploadPage").innerHTML = g) : "editor" == d ? (i(), n("editorPage").style.display = "block") : "show" == d && "" != g ? (i(), n("showPage").style.display = "block", n("showImg").src = g) : "share" == d && (i(), n("editorPage").style.display = "block", n("shareAlert").style.display = "block", setTimeout(function() {
            n("shareAlert").style.display = "none"
        }, 15000))
    }

    function i() {
        n("editorPage").style.display = n("uploadPage").style.display = n("showPage").style.display = "none"
    }

    function a(d) {
        if (d.nodeType) {
            this.canvas = d
        } else {
            if ("string" == typeof d) {
                this.canvas = document.getElementById(d)
            } else {
                return
            }
        }
        this.C()
    }
    var b = navigator.userAgent.match(/ OS (\d+).*? Mac OS/) || !1,
        f = "",
        c = 0;
    window.console && window.console.log && console.log("Copyright by vivatech.cn");
    a.prototype = {
        lineWidth: 1,
        color: "rgba(0,0,0, .6)",
        c: 8,
        b: 320,
        v: paper,
        k: 0,
        C: function() {
            var d = this;
            if (this.canvas.getContext) {
                this.a = this.canvas.getContext("2d");
                this.a.strokeStyle = this.color;
                this.a.fillStyle = this.color;
                this.h(this.canvas, "selectstart", function() {
                    return !1
                });
                this.o = new Image;
                this.o.src = this.v;
                this.p = new Image;
                this.p.src = j;
                // this.p.src = "data:image/png;base64," + j;
                var g = function(h) {
                    var m, k;
                    if ("touchstart" == h.type) {
                        if (2 <= h.touches.length) {
                            return
                        }
                        m = h.touches[0].pageX;
                        k = h.touches[0].pageY;
                        d.f(d.canvas, "mousedown", g)
                    } else {
                        m = h.pageX, k = h.pageY
                    }
                    d.D(m, k, h.type);
                    h.preventDefault()
                };
                this.h(this.canvas, "touchstart", g);
                this.h(this.canvas, "mousedown", g)
            }
        },
        D: function(h, m, g) {
            var k = this;
            this.i = this.canvas.getBoundingClientRect();
            this.i = {
                left: this.i.left + window.scrollX,
                top: this.i.top + window.scrollY
            };
            window.getSelection() ? window.getSelection().removeAllRanges() : document.selection.empty();
            this.a.moveTo(h - this.i.left, m - this.i.top);
            this.e = null;
            this.s = 0;
            this.d = [];
            this.lineWidth = this.c / 2 * (this.b / 320);
            this.g && (this.f(document, "mousemove", this.g), this.f(document, "touchmove", this.g), this.f(document, "mouseup", this.j), this.f(document, "touchend", this.j));
            this.g = function(p) {
                var d, q;
                if ("touchmove" == p.type) {
                    if (2 <= p.touches.length) {
                        return
                    }
                    d = p.touches[0].pageX;
                    q = p.touches[0].pageY
                } else {
                    d = p.pageX, q = p.pageY
                }
                k.t(d, q);
                p.preventDefault()
            };
            this.j = function() {
                k.F()
            };
            "touchstart" == g ? (this.h(document, "touchmove", this.g), this.h(document, "touchend", this.j)) : (this.h(document, "mousemove", this.g), this.h(document, "mouseup", this.j));
            this.w();
            this.t(h, m)
        },
        t: function(g, h) {
            var d;
            d = 0;
            g -= this.i.left;
            h -= this.i.top;
            if (this.d.length && (d = this.d[this.d.length - 1], d = Math.sqrt((d.x - g) * (d.x - g) + (d.y - h) * (d.y - h)), 0 == d)) {
                return
            }
            this.k++;
            this.d.push({
                x: g,
                y: h,
                r: d
            });
            3 <= this.d.length && (d = this.d.shift(), this.q(d))
        },
        q: function(k, s) {
            var h = k.x,
                r = k.y,
                q = k.r,
                p = b ? 4 : 2;
            if (!this.e || 0 !== q) {
                var m = this.d.length ? this.d[0] : null;
                if (q) {
                    this.a.moveTo(this.e.x, this.e.y);
                    if (!this.s && (this.s = 1, m && q > m.r * p)) {
                        for (q /= 4, p = 1; 4 >= p; p++) {
                            this.u(h + p / 4 * (this.e.x - h), r + p / 4 * (this.e.y - r))
                        }
                    }
                    s || (s = q < 0.003125 * this.b ? this.b / 320 * this.c * 1.625 : q < 0.00625 * this.b ? this.b / 320 * this.c * 1.375 : q < 0.009375 * this.b ? this.b / 320 * this.c * 1.25 : q < 0.015625 * this.b ? this.b / 320 * this.c * 1.125 : q < 0.021875 * this.b ? this.b / 320 * this.c : q < 0.028125 * this.b ? this.b / 320 * this.c * 0.875 : q < 0.034375 * this.b ? this.b / 320 * this.c * 0.75 : q < 0.046875 * this.b ? this.b / 320 * this.c * 0.625 : q < 0.0625 * this.b ? this.b / 320 * this.c * 0.5 : this.b / 320 * this.c * 0.375);
                    this.n = s;
                    Math.abs(this.lineWidth - this.n) > this.b / 320 * this.c * 0.025 && (this.lineWidth -= (this.lineWidth - this.n) / 8, this.a.lineWidth = this.lineWidth)
                }
                this.e = k;
                this.u(h, r)
            }
        },
        F: function() {
            this.f(document, "mousemove", this.g);
            this.f(document, "touchmove", this.g);
            this.f(document, "mouseup", this.j);
            this.f(document, "touchend", this.j);
            --this.a.lineWidth;
            for (var d; this.d.length;) {
                d = this.d.shift(), this.q(d, this.b / 320 * this.c / 4)
            }
        },
        A: function() {
            this.e = null;
            this.k = 0;
            this.a.clearRect(0, 0, this.canvas.width, this.canvas.height);
            this.a.beginPath()
        },
        u: function(h, q) {
            var g, p, m;
            this.a.fillStyle = this.color;
            this.a.strokeStyle = this.color;
            this.a.lineTo(this.e.x, this.e.y);
            this.a.stroke();
            this.a.beginPath();
            this.a.beginPath();
            this.a.strokeStyle = "rgba(0, 0, 0, 0)";
            if (this.l || this.m) {
                if (g = this.l - h, p = this.m - q, m = Math.sqrt((this.l - h) * (this.l - h) + (this.m - q) * (this.m - q)), Math.abs(m) > this.lineWidth / 3) {
                    m = Math.floor(Math.abs(m) / (this.lineWidth / 3));
                    for (var k = 1; k <= m; k++) {
                        Math.abs(this.lineWidth - this.n) > this.b / 320 * this.c * 0.025 && (this.lineWidth -= Math.round(this.lineWidth - this.n) / 8, this.a.lineWidth = this.lineWidth), this.a.arc(this.l - k / m * g, this.m - k / m * p, this.lineWidth, 0, 2 * Math.PI), this.a.fill(), this.a.stroke(), this.a.beginPath()
                    }
                }
            }
            this.a.arc(h, q, this.lineWidth, 0, 2 * Math.PI);
            this.a.fill();
            this.a.stroke();
            this.a.beginPath();
            this.l = h;
            this.m = q
        },
        w: function() {
            this.m = this.l = 0
        },
        h: function(g, h, d) {
            g.attachEvent ? g.attachEvent("on" + h, d) : g.addEventListener(h, d, !1)
        },
        f: function(g, h, d) {
            g.detachEvent ? g.detachEvent("on" + h, d) : g.removeEventListener(h, d, !1)
        },
        B: function(h, q) {
            var g;
            var p;
            if (this.p.complete && this.o.complete) {
                g = h || "png";
                p = q || 0.7;
                var m = document.createElement("canvas");
                m.width = m.height = 590;
                var k = m.getContext("2d");
                k.drawImage(this.o, 0, 0, 590, 590);
                k.drawImage(this.canvas, 0, 0, 590, 590);
                k.drawImage(this.p, 19, 16, 101, 39);
                g = m.toDataURL("image/" + g, p)
            } else {
                g = ""
            }
            return g
        }
    };
    e()
})();
