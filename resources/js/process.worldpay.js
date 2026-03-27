+function ($) {
    "use strict"

    var ProcessWorldPay = function (element, options) {
        this.$el = $(element)
        this.options = options || {}
        this.$checkoutFormContainer = this.$el.closest('[data-control="checkout"]')
        this.$checkoutForm = this.$checkoutFormContainer.find('form')
        this.$checkoutBtn = $('[data-checkout-control="submit"]')
        this.$iframe = $("#worldpay-frame");

        this.init()
    }



    ProcessWorldPay.prototype.init = function () {
        if (this.$checkoutFormContainer.checkout('selectedPaymentInput').val() !== 'worldpay') return


        var self = this


        this.$checkoutForm
            .on('submitCheckoutForm', $.proxy(this.submitFormHandler, this))
            .on('submit', function () {
                if (self.$checkoutForm.find('input[name="fields.payment"]:checked').val() !== 'worldpay')
                    return

                self.paymentElement?.update({readOnly: true});
            })
            .on('ajaxFail', function () {
                self.paymentElement?.update({readOnly: false});
            })
    }


    ProcessWorldPay.prototype.validationErrorHandler = function (event) {
        $('[data-checkout-control="submit"]').prop('disabled', false)
        this.paymentElement.update({readOnly: false});
    }

    ProcessWorldPay.prototype.submitFormHandler = function (event) {




        var self = this, $form = this.$checkoutForm

        if (this.$checkoutFormContainer.checkout('selectedPaymentInput').val() !== 'worldpay') return

        if (this.$iframe.data('wp-iframe') === '') return


        // Prevent the form from submitting with the default action
        event.preventDefault();


        self.$checkoutBtn.prop('disabled', true)

        var customOptions = {
            url: self.$iframe.data('wp-iframe'),
            type: 'lightbox',
            target: 'worldpay-frame',
            accessibility: true,
            debug: true,
            trigger : "checkout-form"
        };
        // Initialize the library and pass options
        var libraryObject = new WPCL.Library();
        libraryObject.setup(customOptions);

        //need this for some reason.
        $("#checkout-form").click();

    }

    ProcessWorldPay.DEFAULTS = {

    }

    // PLUGIN DEFINITION
    // ============================

    var old = $.fn.processWorldPay

    $.fn.processWorldPay = function (option) {
        var $this = $(this).first()
        var options = $.extend(true, {}, ProcessWorldPay.DEFAULTS, $this.data(), typeof option == 'object' && option)

        return new ProcessWorldPay($this, options)
    }

    $.fn.processWorldPay.Constructor = ProcessWorldPay

    $.fn.processWorldPay.noConflict = function () {
        $.fn.processWorldPay = old
        return this
    }

    $(document).render(function () {
        $('#worldpay-frame').processWorldPay()
    })
}(window.jQuery)
