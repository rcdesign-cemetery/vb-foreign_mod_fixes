/*
vBCollapse.prototype.init = function (a, b) {
    this.link = a;
    this.factory = b;
    this.targetid = null;
    this.target = null;
    this.image = null;
    var c = this.link.id.match(/^collapse_(.*)$/);
    this.targetid = c[1];
    this.iscat = this.targetid.match(/c_cat([0-9]+)/);
    this.target = YAHOO.util.Dom.get(this.targetid);
    this.target.vBCollapseInstance = this;
    if (!this.iscat) {
        this.blockheight = parseInt(YAHOO.util.Dom.get(this.targetid).offsetHeight);
        YAHOO.util.Dom.setStyle(this.targetid, "overflow", "hidden")
    }
    var d = this.link.getElementsByTagName("img");
    this.image = d[0];
    if (this.target) {
        YAHOO.util.Event.on(this.link, "click", this.toggle_collapse, this, true)
    } else {
        YAHOO.util.Dom.setStyle(this.link, "display", "hidden")
    }
};
vBCollapse.prototype.collapse = function () {
    if (this.iscat) {
        YAHOO.util.Dom.setStyle(this.target, "display", "none")
    } else {
        var a = new YAHOO.util.Anim(this.targetid, {
            height: {
                to: 0
            }
        }, 0.3, YAHOO.util.Easing.easeIn);
        a.animate()
    }
    this.save_collapsed(true);
    if (this.image) {
        var b = new RegExp("\\.png$");
        this.image.src = this.image.src.replace(b, '_collapsed.png')
    }
};
vBCollapse.prototype.expand = function () {
    if (this.iscat) {
        YAHOO.util.Dom.setStyle(this.target, "display", "")
    } else {
        var a = new YAHOO.util.Anim(this.targetid, {
            height: {
                from: 0,
                to: this.blockheight
            }
        }, 0.3, YAHOO.util.Easing.easeOut);
        a.animate()
    }
    this.save_collapsed(false);
    if (this.image) {
        var b = new RegExp("_collapsed\\.png$");
        this.image.src = this.image.src.replace(b, '.png')
    }
};
vBCollapse.prototype.toggle_collapse = function (e) {
    YAHOO.util.Event.stopEvent(e);
    if (!is_regexp) {
        return false
    }
    if (this.iscat) {
        if (YAHOO.util.Dom.getStyle(this.target, 'display') == 'none') {
            this.expand()
        } else {
            this.collapse()
        }
    } else {
        var a = parseInt(YAHOO.util.Dom.get(this.targetid).offsetHeight)
        if (a < 5 || a == 0) {
            this.expand()
        } else {
            this.collapse()
        }
    }
    return false
};
*/
PopupMenu.prototype.set_display = function (a, b) {
    if (YAHOO.lang.isNull(this.menu)) {
        return
    }
    this.display = a;
    if (a && YAHOO.env.ua.ie > 0 && YAHOO.env.ua.ie < 8) {
        if (YAHOO.lang.isNull(this.locator)) {
            var c = this.contains_form_elements(this.menu);
            if (c) {
                console.log("Popup '%s' contains form elements", this.control.innerHTML);
                this.form = c.form
            }
            this.locator = this.menu.parentNode.insertBefore(document.createElement("div"), this.menu);
            YAHOO.util.Dom.addClass(this.locator, "popupbody");
            YAHOO.util.Dom.setStyle(this.locator, "visibility", "hidden");
            YAHOO.util.Dom.setStyle(this.locator, "display", "block");
            YAHOO.util.Dom.setStyle(this.locator, "margin", "0");
            YAHOO.util.Dom.setStyle(this.locator, "padding", "0");
            YAHOO.util.Dom.setStyle(this.locator, "border", "none");
            YAHOO.util.Dom.setStyle(this.menu, "display", "block");
            YAHOO.util.Dom.setStyle(this.locator, "width", this.menu.offsetWidth + "px");
            YAHOO.util.Dom.setStyle(this.locator, "height", this.menu.offsetHeight + "px");
            YAHOO.util.Dom.setStyle(this.menu, "display", "none");
            this.menu.form = document.body.appendChild(document.createElement("form"));
            this.menu.form.appendChild(this.menu);
            if (this.form) {
                YAHOO.util.Event.on(this.menu.form, "submit", this.handle_popup_form_submit, this, true);
                YAHOO.util.Event.on(this.menu.form, "reset", this.handle_popup_form_reset, this, true)
            }
        }
        if (this.check_menu(this.menu)) {
            YAHOO.util.Dom.setStyle(this.menu, "opacity", 0);
            YAHOO.util.Dom.setStyle(this.menu, "display", "block");
            var d = new YAHOO.util.Anim(this.menu, {
                opacity: {
                    from: 0,
                    to: 1
                }
            }, 0.3, YAHOO.util.Easing.easeOut);
            d.animate();
            this.set_offset(this.menu, b);
            this.set_control_style()
        }
    } else if (a) {
        if (this.check_menu(this.menu)) {
            YAHOO.util.Dom.setStyle(this.menu, "opacity", 0);
            YAHOO.util.Dom.setStyle(this.menu, "display", "block");
            var d = new YAHOO.util.Anim(this.menu, {
                opacity: {
                    from: 0,
                    to: 1
                }
            }, 0.3, YAHOO.util.Easing.easeOut);
            d.animate();
            this.set_offset(this.menu, b);
            this.set_control_style()
        }
    } else {
        YAHOO.util.Dom.setStyle(this.menu, "display", "none");
        this.set_control_style()
    }
};
