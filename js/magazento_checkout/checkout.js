var OSC = Class.create();
OSC.prototype = {
    initialize: function (a) {
        this.loadWaitingCartTotals = this.loadWaitingPayment = this.loadWaitingShippingMethod = false;
        this.failureUrl = a.failure;
        this.UpdateTotalsUrl = a.UpdateTotals;
        this.UpdateTotalsPaymentUrl = a.UpdateTotalsPayment;
        this.successUrl = a.success;
        this.response = []
    },
    ajaxFailure: function () {
        location.href = this.failureUrl
    },
    processRespone: function (a) {
        var b;
        if (a && a.responseText) try {
            b = a.responseText.evalJSON()
        } catch (c) {
            b = {}
        }
//        alert(b.error + " " + b.redirect + " " + b.usebilling + " " + b.shippingMethod) ;
        if (b.redirect) {
            location.href = b.redirect;
        } else if (b.error) {
            
            if (b.fields) {
                a = b.fields.split(",");
                for (var d = 0; d < a.length; d++)
                null == $(a[d]) && Validation.ajaxError(null, b.error)
            } else {
                if (b.error && b.payment) {
                    this.updatePayment();
                    payment.initWhatIsCvvListeners()
                } else {
                    alert(Translator.translate(b.error_messages));
                }
            }
        } else if (b.response_status_detail) {
            alert(Translator.translate(b.response_status_detail));
        } else {
            this.response = b;

            if (b.shippingMethod) {
                this.updateShippingMethod();
                //                    alert(b.usebilling);
            } else if (b.payment) {

                this.updatePayment();
                payment.initWhatIsCvvListeners()
            } else {

                this.updateCartTotals()
            }
        }
    },

    setLoaderShippingMethod: function (a) {
        this.loadWaitingShippingMethod = a;
        if (a == true) {
            $("onecheckout-loader") && Element.show("onecheckout-loader");
            $("onecheckout-shipping-methods") && Element.hide("onecheckout-shipping-methods")
        } else {
            if ($("billing:use_for_shipping_yes").checked == true) {
                $("onecheckout-loader") && Element.hide("onecheckout-loader");
            }
            $("onecheckout-shipping-methods") && Element.show("onecheckout-shipping-methods")
        }

    },

    resetLoaderShippingMethod: function () {
        this.setLoaderShippingMethod(false)
    },

    updateShippingMethod: function () {
        if ($("onecheckout-shipping-methods")) {

            $("onecheckout-shipping-methods").update(this.response.shippingMethod);
            this.resetLoaderShippingMethod();
            if ($$("#onecheckout-shipping-methods .no-display input").length != 0) {
                $$("#onecheckout-shipping-methods .no-display input")[0].checked == true && shippingMethod.saveShippingMethod();
            } else {
                this.response.payment && this.UpdateTotalsPayment()
            }
        } else {

            this.response.payment && this.UpdateTotalsPayment()
        }
    },

    setLoaderPayment: function (a) {
        this.loadWaitingPayment = a;
        if (a == true) {
            $("onecheckout-loader") && Element.show("onecheckout-loader");
            $("checkout-payment-method-load") && Element.hide("checkout-payment-method-load")
        } else {
            $("checkout-payment-method-load") && Element.show("checkout-payment-method-load")
        }
    },

    resetLoaderPayment: function () {
        this.setLoaderPayment(false)
    },

    updatePayment: function () {
        $("checkout-payment-method-load").update(this.response.payment);
        this.resetLoaderPayment();
        payment.switchMethod(payment.currentMethod);
        if ($$("#checkout-payment-method-load .no-display input").length != 0) $$("#checkout-payment-method-load .no-display input")[0].checked == true && payment.savePayment();
        else {

            var a = false;
            $$("#checkout-payment-method-load input").each(function (b) {
                if (b.checked == true) {
                    a = true;
                }
            });
            if (!a) {
                $$("#checkout-payment-method-load input").each(function (b) {
                    b.checked = true;
                    a = true;
                    throw $break;
                });
            }
            a == true ? payment.savePayment() : this.UpdateTotals()
        }
    },

    setLoaderCartTotals: function (a) {
        this.loadWaitingCartTotals = a;
        if (a == true) {
            $("onecheckout-loader") && Element.show("onecheckout-loader");
            $("checkout-review-load") && Element.hide("checkout-review-load")
        } else if (a == 'saving_order') {
            $("onecheckout-loader") && Element.show("onecheckout-loader");
        } else {
            $("onecheckout-loader") && Element.hide("onecheckout-loader");
            $("checkout-review-load") && Element.show("checkout-review-load")
        }
    },

    resetLoaderCartTotals: function () {
        this.setLoaderCartTotals(false)
    },

    updateCartTotals: function () {
        $("checkout-review-load").update(this.response.review);
        this.resetLoaderCartTotals();
        if (this.response.success) location.href = this.successUrl
    },

    UpdateTotals: function () {
        this.setLoaderCartTotals(true);
        new Ajax.Request(this.UpdateTotalsUrl, {
            method: "post",
            onComplete: this.resetLoaderCartTotals,
            onSuccess: this.processRespone.bind(this),
            onFailure: this.ajaxFailure.bind(this)
        })
    },
    UpdateTotalsPayment: function () {
        this.setLoaderPayment(true);
        new Ajax.Request(this.UpdateTotalsPaymentUrl, {
            method: "post",
            onComplete: this.resetLoaderPayment,
            onSuccess: this.processRespone.bind(this),
            onFailure: this.ajaxFailure.bind(this)
        })
    },
    showOptionsList: function (a, b) {
        if (a) {
            new Effect.toggle(b, "appear");
            new Effect.toggle(a.id, "appear");
            console.log(a.id.substring(0, 10));
            if (a.id.substring(0, 10) == "option-exp") new Effect.toggle("option-clo-" + a.id.substring(11));
            else new Effect.toggle("option-exp-" + a.id.substring(11))
        }
    }
};

var Billing = Class.create();
Billing.prototype = {
    initialize: function (a, b, c, d) {
        this.useBilling = a;
        this.saveCountryUrl = b;
        this.switchMethodUrl = c;
        this.addressUrl = d
    },
    enalbleShippingAddress: function () {
        //            this.setStepNumber();
        if ($("billing:use_for_shipping_yes").checked == true) {
            Element.show("shipping-address-form");
            this.useBilling = false;
            $("shipping-address-select") ? shipping.setAddress($("shipping-address-select").value) : shipping.saveCountry();
        }
        if ($("billing:use_for_shipping_yes").checked == false) {
            Element.hide("shipping-address-form");
            this.useBilling = true;
            this.saveCountry()
        }
    },
    saveCountry: function () {
        var a = $("billing:country_id").value,
            b = $("billing:postcode").value;
            c = $("billing:region_id").value;
            
            
        if (this.useBilling == false) {
            onecheckout.setLoaderPayment(true);
            new Ajax.Request(this.saveCountryUrl, {
                parameters: {
                    country_id: a,
                    postcode: b,
                    region_id: c,
                    usebilling: "false"
                },
                method: "post",
                onComplete: onecheckout.resetLoaderPayment.bind(onecheckout),
                onSuccess: onecheckout.processRespone.bind(onecheckout),
                onFailure: onecheckout.ajaxFailure.bind(onecheckout)
            })
        }
        
        if (this.useBilling == true) {
            onecheckout.setLoaderShippingMethod(true);
            new Ajax.Request(this.saveCountryUrl, {
                parameters: {
                    country_id: a,
                    postcode: b,
                    region_id: c,
                    usebilling: "true"
                },
                method: "post",
                onComplete: onecheckout.resetLoaderShippingMethod.bind(onecheckout),
                onSuccess: onecheckout.processRespone.bind(onecheckout),
                onFailure: onecheckout.ajaxFailure.bind(onecheckout)
            })

        }

    },
    setAddress: function (a) {
        if (a) request = new Ajax.Request(this.addressUrl + a, {
            method: "get",
            onSuccess: this.fillForm.bindAsEventListener(this),
            onFailure: onecheckout.ajaxFailure.bind(onecheckout)
        })
    },
    newAddress: function (a) {
        if (a) {
            this.resetSelectedAddress();
            Element.show("billing-new-address-form")
        } else Element.hide("billing-new-address-form")
    },
    resetSelectedAddress: function () {
        var a = $("billing-address-select");
        if (a) a.value = ""
    },
    fillForm: function (a) {
        var b = {};
        if (a && a.responseText) try {
            b = a.responseText.evalJSON()
        } catch (c) {
            b = {}
        } else this.resetSelectedAddress();
        arrElements = Form.getElements(CartTotals.form);
        for (var d in arrElements)
        if (arrElements[d].id) {
            a = arrElements[d].id.replace(/^billing:/, "");
            if (b[a] != undefined && b[a]) arrElements[d].value = b[a]
        }
        this.saveCountry()
    }
};
var Shipping = Class.create();
Shipping.prototype = {
    initialize: function (a, b) {
        this.saveCountryUrl = a;
        this.addressUrl = b
    },
    saveCountry: function () {
        if (billing.useBilling == false) {
            var a = $("shipping:country_id").value,
                b = $("shipping:postcode").value;
            c = $("shipping:region_id").value;
            onecheckout.setLoaderShippingMethod(true);
            new Ajax.Request(this.saveCountryUrl, {
                parameters: {
                    country_id: a,
                    postcode: b,
                    region_id: c,
                    usebilling: "false"
                },
                method: "post",
                onComplete: onecheckout.resetLoaderShippingMethod.bind(onecheckout),
                onSuccess: onecheckout.processRespone.bind(onecheckout),
                onFailure: onecheckout.ajaxFailure.bind(onecheckout)
            })
        }
    },
    setAddress: function (a) {
        if (a) request = new Ajax.Request(this.addressUrl + a, {
            method: "get",
            onSuccess: this.fillForm.bindAsEventListener(this),
            onFailure: onecheckout.ajaxFailure.bind(onecheckout)
        })
    },
    newAddress: function (a) {
        if (a) {
            this.resetSelectedAddress();
            Element.show("shipping-new-address-form")
        } else Element.hide("shipping-new-address-form");
        shipping.setSameAsBilling(false)
    },
    resetSelectedAddress: function () {
        var a = $("shipping-address-select");
        if (a) a.value = ""
    },
    setSameAsBilling: function (a) {
        ($("shipping:same_as_billing").checked = a) && this.syncWithBilling()
    },
    syncWithBilling: function () {
        $("billing-address-select") && this.newAddress(!$("billing-address-select").value);
        $("shipping:same_as_billing").checked = true;
        if (!$("billing-address-select") || !$("billing-address-select").value) {
            arrElements = Form.getElements(CartTotals.form);
            for (var a in arrElements) if (arrElements[a].id) {
                var b = $(arrElements[a].id.replace(/^shipping:/, "billing:"));
                if (b) arrElements[a].value = b.value
            }
            shippingRegionUpdater.update();
            $("shipping:region_id").value = $("billing:region_id").value;
            $("shipping:region").value = $("billing:region").value
        } else $("shipping-address-select").value = $("billing-address-select").value
    },
    fillForm: function (a) {
        var b = {};
        if (a && a.responseText) try {
            b = a.responseText.evalJSON()
        } catch (c) {
            b = {}
        } else this.resetSelectedAddress();
        arrElements = Form.getElements(CartTotals.form);
        for (var d in arrElements)
        if (arrElements[d].id) {
            a = arrElements[d].id.replace(/^shipping:/, "");
            if (b[a] != undefined && b[a]) arrElements[d].value = b[a]
        }
        this.saveCountry()
    },
    setRegionValue: function () {
        $("shipping:region").value = $("billing:region").value
    }
};
var ShippingMethod = Class.create();
ShippingMethod.prototype = {
    initialize: function (a, b) {
        this.saveUrl = a;
        this.isReloadPayment = b

    },
    saveShippingMethod: function () {
        for (var a = document.getElementsByName("shipping_method"), b = "", c = 0; c < a.length; c++)
        if (a[c].checked) b = a[c].value;
        if (b != "") {

            this.isReloadPayment == 1 && onecheckout.setLoaderPayment(true);
            new Ajax.Request(this.saveUrl, {
                parameters: {
                    shipping_method: b
                },
                method: "post",
                onComplete: onecheckout.resetLoaderPayment.bind(onecheckout),
                onSuccess: onecheckout.processRespone.bind(onecheckout),
                onFailure: onecheckout.ajaxFailure.bind(onecheckout)
            })
        }
    }
};
var Payment = Class.create();
Payment.prototype = {

    beforeInitFunc: $H({}),
    afterInitFunc: $H({}),
    beforeValidateFunc: $H({}),
    afterValidateFunc: $H({}),
    initialize: function (a) {
        this.saveUrl = a
    },
    init: function () {
        for (var a = $$("input[name^=payment]"), b = null, c = 0; c < a.length; c++) {
            if (a[c].name == "payment[method]") {
                if (a[c].checked) b = a[c].value
            } else a[c].disabled = true;
            a[c].setAttribute("autocomplete", "off")
        }
        b && this.switchMethod(b)
    },
    savePayment: function () {
        var a = document.getElementsByName("payment[method]");

        value = "";
        for (var b = 0; b < a.length; b++)
        if (a[b].checked) value = a[b].value;
        if (value != "") {
            onecheckout.setLoaderCartTotals(true);

            new Ajax.Request(this.saveUrl, {
                parameters: {
                    method: value
                },
                method: "post",
                onComplete: onecheckout.resetLoaderCartTotals.bind(onecheckout),
                onSuccess: onecheckout.processRespone.bind(onecheckout),
                onFailure: onecheckout.ajaxFailure.bind(onecheckout)
            })
        }
    },
    switchMethod: function (a) {

        if (this.currentMethod && $("payment_form_" + this.currentMethod)) {
            var b = $("payment_form_" + this.currentMethod);
            b.hide();
            b = b.select("input", "select", "textarea");
            for (var c = 0; c < b.length; c++) b[c].disabled = true
        }
        if ($("payment_form_" + a)) {
            b = $("payment_form_" + a);
            b.show();
            b = b.select("input", "select", "textarea");
            for (c = 0; c < b.length; c++) b[c].disabled = false
        } else $(document.body).fire("payment-method:switched", {
            method_code: a
        });
        this.currentMethod = a
    },
    initWhatIsCvvListeners: function () {
        $$(".cvv-what-is-this").each(function (a) {
            Event.observe(a, "click", toggleToolTip)
        })
    }
};



var CartTotals = Class.create();
CartTotals.prototype = {
    initialize: function (a, b, c) {
        this.form = a;
        this.saveUrl = b;
        this.agreementsForm = c;
        this.onestepcheckourForm = new VarienForm(this.form)
    },
    save: function () {
        if ((new Validation(this.form)).validate()) {
            //Element.show("onecheckout-loader")
            onecheckout.setLoaderCartTotals('saving_order');
            var a = Form.serialize(this.form);
            if (this.agreementsForm) a += "&" + Form.serialize(this.agreementsForm);
            if ($(payment.currentMethod + "_cc_type")) {
                pay = "payment%5Bcc_type%5D=" + $(payment.currentMethod + "_cc_type").value + "&payment%5Bcc_exp_month%5D=" + $(payment.currentMethod + "_expiration").value + "&payment%5Bcc_exp_year%5D=" + $(payment.currentMethod + "_expiration_yr").value;
                a += "&" + pay;
            }
            a.save = true;

            if (payment.currentMethod.startsWith('sage')) {
                new Ajax.Request(savetotalsUrl, {
                    method: "post",
                    parameters: a,
                    onComplete: onecheckout.resetLoaderCartTotals.bind(onecheckout),
                    onSuccess: this.processRespone.bind(this),
                    onFailure: onecheckout.ajaxFailure.bind(false)
                })
            } else {
                if (!payment.currentMethod.startsWith('sage')) {
                    this.saveUrl = carttotalsUrl;
                }
                new Ajax.Request(this.saveUrl, {
                    method: "post",
                    parameters: a,
                    onComplete: onecheckout.resetLoaderCartTotals.bind(onecheckout),
                    onSuccess: onecheckout.processRespone.bind(onecheckout),
                    onFailure: onecheckout.ajaxFailure.bind(onecheckout)
                })
            }
        }
    },
    processRespone: function (a) {
        var b;
        if (a && a.responseText) try {
            b = a.responseText.evalJSON()
        } catch (c) {
            b = {}
        }
        //this.setLoadercoupon(true);
        if (b.success) {
            if (!payment.currentMethod.startsWith('sage')) {

                this.saveUrl = carttotalsUrl;
            }
            new Ajax.Request(this.saveUrl, {
                method: "post",
                parameters: a,
                onComplete: onecheckout.resetLoaderCartTotals.bind(onecheckout),
                onSuccess: onecheckout.processRespone.bind(onecheckout),
                onFailure: onecheckout.ajaxFailure.bind(onecheckout)
            })

        }

    }
};

var Coupon = Class.create();
Coupon.prototype = {
    initialize: function (a) {
        this.CouponUrl = a;
        this.response = [];
        c = $("coupon_code").value;
        if (c) {
            Element.hide("apply_coupon");
            $(coupon_code).disable();
        }

    },
    coupon: function (remove) {
        var b = 0;
        if (remove) {
            $("coupon_code").removeClassName('required-entry');
            b = "1";
        } else {
            $("coupon_code").addClassName('required-entry');
        }
        var c = $("coupon_form");

        if ((c = new Validation(c)) && c.validate()) {
            c = $("coupon_code").value;
            this.setLoadercoupon(false);
            new Ajax.Request(this.CouponUrl, {
                parameters: {
                    coupon_code: c,
                    remove: b
                },
                method: "post",
                onComplete: this.setLoadercoupon(false),
                onSuccess: this.processRespone.bind(this),
                onFailure: onecheckout.ajaxFailure.bind(this)
            })
        }
    },
    processRespone: function (a) {
        //            alert(a.responseText);
        var b;
        if (a && a.responseText) try {
            b = a.responseText.evalJSON()
        } catch (c) {
            b = {}
        }
        this.setLoadercoupon(true);

        if (b.error) {
            Element.hide("remove_coupon");
            Element.show("apply_coupon");
            $(coupon_code).enable();
            $("osc-coupon-message").update(b.error);

        } else if (b.success) {
            Element.show("remove_coupon");
            Element.hide("apply_coupon");
            $(coupon_code).disable();
            $("osc-coupon-message").update(b.success);

        }
        onecheckout.UpdateTotals();
    },
    setLoadercoupon: function (a) {
        if (a) {
            Element.hide("onecheckout-loader");
        } else {

            Element.show("onecheckout-loader");
        }
    }
};


var Quickcheckout_Agreements = Class.create();
Quickcheckout_Agreements.prototype = {
    initialize: function () {},
    show: function (c) {
        b = 'checkouttermscontent';
        b += c;
        Element.show(b)
    },
    hide: function (c) {
        b = 'checkouttermscontent' + c;
        Element.hide(b)
    }
};


var Login = Class.create();
Login.prototype = {
    initialize: function (a) {
        this.loginUrl = a;
        this.loadWaitingLogin = false;
        this.response = []
    },
    show: function () {
        Element.show("osc-login-form")
    },
    hide: function () {
        Element.hide("osc-login-form")
    },
    login: function () {
        var a = $("login-form");
        if ((a = new Validation(a)) && a.validate()) {
            a = $("login-email").value;
            var b = $("login-password").value;
            this.setLoaderLogin(true);
            new Ajax.Request(this.loginUrl, {
                parameters: {
                    username: a,
                    password: b
                },
                method: "post",
                onComplete: this.resetLoaderLogin,
                onSuccess: this.processRespone.bind(this),
                onFailure: onecheckout.ajaxFailure.bind(this)
            })
        }
    },
    processRespone: function (a) {
        var b;
        if (a && a.responseText) try {
            b = a.responseText.evalJSON()
        } catch (c) {
            b = {}
        }
        if (b.error) {
            $("osc-error-message").update(b.error);
            this.resetLoaderLogin()
        } else location.href = ""
    },
    setLoaderLogin: function (a) {
        if (this.loadWaitingLogin == a) {

            Element.hide("onecheckout-loader");
            Element.show("osc-login-form")
        } else {
            Element.show("onecheckout-loader");
            Element.show("osc-login-form")
        }
    },
    resetLoaderLogin: function () {
        this.setLoaderLogin(false)
    }
};

var Forgotpass = Class.create();
Forgotpass.prototype = {
    initialize: function (a) {
        this.forgotpassUrl = a;
        this.response = []
    },
    forgotpass: function () {
        var a = $("forgotpass-form");
        if ((a = new Validation(a)) && a.validate()) {
            a = $("email_address").value;
            this.setLoaderforgot(false);
            new Ajax.Request(this.forgotpassUrl, {
                parameters: {
                    email: a
                },
                method: "post",
                onComplete: this.setLoaderforgot(false),
                onSuccess: this.processRespone.bind(this),
                onFailure: onecheckout.ajaxFailure.bind(this)
            })
        }
    },
    processRespone: function (a) {
        var b;
        if (a && a.responseText) try {
            b = a.responseText.evalJSON()
        } catch (c) {
            b = {}
        }
        if (b.error) {
            $("osc-forgotpass-error-message").update(b.error);
            this.setLoaderforgot(true)
        }
    },
    setLoaderforgot: function (a) {
        if (a) {
            Element.hide("onecheckout-loader_forgotpass");
        } else {
            $('onecheckout-loader_forgotpass').update(forgot_loading_text);
            Element.show("onecheckout-loader_forgotpass");
        }
    }
};