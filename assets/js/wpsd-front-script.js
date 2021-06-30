;(function (window, $) {
    // USE STRICT
    'use strict'
    let stripe
    var wpsdDonateAmount = 0
    var wpsdCustomAmount = false
    var card = null
    var donation_id = null
    var donation_message = null
    var client_key = null
    var payment_method_id = null
    var customer_id = null
    var recurring = false
    var stripeFormPresent = document.getElementById('card-element') //console.log("Form Present:", stripeFormPresent);
    init()
    async function init() {
        if (stripeFormPresent != null) {
            addListeners()
            if (wpsdAdminScriptObj.publishable_key == '') {
                showError(
                    wpsdAdminScriptObj.validation.not_valid.publishable_key
                )
                return false
            } else {
                stripe = Stripe(wpsdAdminScriptObj.publishable_key, {
                    locale: wpsdAdminScriptObj.locale
                })
                createCardForm()
            }
        }
    }
    function addListeners() {
        $('#wpsd_donator_country').on('change', function (ev) {
            fetchStates(ev.target.value).catch((e) => console.log(e))
        })

        $('#wpsd_donate_other_amount').on('keyup', function () {
            this.value = this.value.replace(/[^0-9\.]/g, '')
            $('#wpsd_donate_other_amount_wrapper').addClass('bg-white')
            $('input[name="wpsd_donate_amount"]').prop('checked', false)
        })

        // $("input[name='wpsd_donate_amount_radio']").on('click', function () {
        //     console.log(
        //         'clicked radio btn',
        //         document.querySelector('input[name="wpsd_donate_amount_radio"]')
        //             .value,
        //         $('input[name=wpsd_donate_amount_radio]').val()
        //     )
        //     $('#wpsd_donate_other_amount').val('')
        //     $('#wpsd_donate_other_amount_wrapper').removeClass('bg-white')
        // })

        $('.wpsd-donate-button').on('click', function (e) {
            e.preventDefault()
            onSubmit()
                .then(() => {})
                .catch((e) => console.log(e))
        })

        $('#wpsd_donator_state').on('change', function () {
            $(this).css({
                color: '#000',
                'font-size': '16px',
                'text-transform': 'none'
            })
        })

        $("input[name='wpsd_donate_amount_radio']").on('change', function (e) {
            let target = e.target
            //let updated_value = target.value.replace(/[^0-9\.]/g, '')

            var options = {
                maximumFractionDigits: 2,
                currency: currency,
                style: 'currency',
                currencyDisplay: 'symbol'
            }

            let updated_value = localStringToNumber(
                target.value
            ).toLocaleString(undefined, options)

            console.log(updated_value)
            $('#wpsd_donate_other_amount').val(updated_value)
        })
    }

    async function onSubmit() {
        var valid = validateForm()
        console.log('donation amount onSubmit', wpsdDonateAmount)
        if (!valid) {
            return false
        }
        recurring = parseInt($('#wpsd_is_recurring:checked').val())
        var err = null
        await charge().catch((e) => (err = e))
        if (err) {
            showError(err)
            return false
        }
        return true
    }
    async function charge() {
        disableSubmitBtn()
        // 1. send donation info to the back-end.
        if (!donation_id) {
            const donation_result = await sendDonationInfo()
            donation_id = donation_result.donation_id
            donation_message = donation_result.message
        }

        // 2. create payment method.
        if (!payment_method_id) {
            const payment_method = await createPaymentMethod(donation_id)
            payment_method_id = payment_method.id
        }

        // 3. create customer.
        if (!customer_id) {
            customer_id = await createCustomer(payment_method_id)
        }

        // 4. create payment intent.
        if (!client_key) {
            const payment_intent = await createPaymentIntent(
                payment_method_id,
                customer_id,
                donation_id
            )
            client_key = payment_intent.client_key
        }

        // 5.confirm the payment:
        await confirmPayment(client_key)
        donation_id = null
        payment_method_id = null
        customer_id = null
        client_key = null
        activateSubmitBtn()
        showMessage(donation_message)
        return true
    }
    async function fetchStates(country) {
        $('#wpsd_donator_country').prop('disabled', true)
        disableSubmitBtn()
        const data = await request('wpsd_get_states', 'GET', null, {
            code: country
        })
        $('#wpsd_donator_country').prop('disabled', false)
        activateSubmitBtn()
        $('#wpsd_donator_country').css({
            color: '#000',
            'font-size': '16px',
            'text-transform': 'none'
        })
        addStates(data.states)
    }
    function addStates(states) {
        if (!states.length) {
            $('#wpsd_donator_state').parent().css('display', 'none')
            return
        }
        $('#wpsd_donator_state').parent().css('display', 'block')
        var defaultOption = $('#wpsd_donator_state').find('option')[0]
        var options = defaultOption.outerHTML
        for (var i = 0; i < states.length; i++) {
            var option =
                "<option value='" +
                states[i].name +
                "'>" +
                states[i].name +
                '</option>'
            options += option
        }
        $('#wpsd_donator_state').html(options)
    }

    async function sendDonationInfo() {
        const requestData = {
            action: 'wpsd_donation',
            wpsdSecretKey: wpsdAdminScriptObj.publishable_key,
            amount: wpsdDonateAmount,
            custom_amount: wpsdCustomAmount ? 1 : 0,
            currency: wpsdAdminScriptObj.currency,
            first_name: $('#wpsd_donator_first_name').val(),
            last_name: $('#wpsd_donator_last_name').val(),
            email: $('#wpsd_donator_email').val(),
            phone: $('#wpsd_donator_phone').val(),
            country: $('#wpsd_donator_country').val(),
            state: $('#wpsd_donator_state').val(),
            city: $('#wpsd_donator_city').val(),
            zip: $('#wpsd_donator_zip').val(),
            address: $('#wpsd_donator_address').val(),
            campaign: $('#wpsd_campaign').val(),
            campaign_id: $('#wpsd_campaign_id').val(),
            fund: $('#wpsd_fund').val(),
            fund_id: $('#wpsd_fund_id').val(),
            in_memory_of_field_id: $('#wpsd_in_memory_of_field_id').val(),
            in_memory_of: $('#wpsd_in_memory_of').val(),
            is_recurring: parseInt($('#wpsd_is_recurring:checked').val())
        }
        console.log('sendDonationInfo', requestData)
        return await request('wpsd_donation', 'POST', requestData)
    }

    // Calls stripe.confirmCardPayment
    // If the card requires authentication Stripe shows a pop-up modal to
    // prompt the user to enter authentication details without leaving the page.
    async function confirmPayment() {
        var method = { card: card }
        if (payment_method_id) {
            method = payment_method_id
        }
        const result = await stripe.confirmCardPayment(client_key, {
            payment_method: method
        })
        activateSubmitBtn()
        if (result.error) {
            // Show error to customer
            throw result.error.message
        }
        return true
    }
    async function createCustomer(paymentMethod) {
        const requestData = {
            action: 'wpsd_create_customer',
            wpsdSecretKey: wpsdAdminScriptObj.publishable_key,
            donation_id: donation_id,
            payment_method_id: paymentMethod
        }
        const data = await request('wpsd_create_customer', 'POST', requestData)
        return data.customer_id
    }
    async function createPaymentMethod() {
        const name =
            $('#wpsd_donator_first_name').val() +
            ' ' +
            $('#wpsd_donator_last_name').val()
        const paymentMethodData = {
            type: 'card',
            card: card,
            billing_details: {
                name: name,
                email: $('#wpsd_donator_email').val(),
                phone: $('#wpsd_donator_phone').val(),
                address: {
                    city: $('#wpsd_donator_city').val(),
                    country: $('#wpsd_donator_country').val(),
                    line1: $('#wpsd_donator_address').val(),
                    postal_code: $('#wpsd_donator_zip').val(),
                    state: $('#wpsd_donator_state').val()
                }
            }
        }
        console.log('createPaymentMethod', paymentMethodData)
        const result = await stripe.createPaymentMethod(paymentMethodData)
        // Handle result.error or result.paymentMethod
        if (result.error) {
            activateSubmitBtn()
            throw result.error.message
        }
        return result.paymentMethod
    }

    async function createPaymentIntent(
        paymentMethodId,
        customerId,
        donation_id
    ) {
        const requestData = {
            donation_id: donation_id,
            payment_method_id: paymentMethodId,
            customer_id: customerId
        }

        console.log('createPaymentIntent', requestData)
        return await request('wpsd_payment_intent', 'POST', requestData)
    }

    async function request(action, type, data = null, params = null) {
        return new Promise((resolve, reject) => {
            disableSubmitBtn()
            // get current locale to prevent a bug in wordpress:
            var url = wpsdAdminScriptObj.ajaxurl + '?action=' + action
            var lang = window.location.href.match(/lang=\w+/g)
            if (lang && lang.length) {
                lang = lang[0]
                lang = lang.replace('lang=', '')
                url += '&lang=' + lang
            }
            const requestOptions = {
                url: url,
                dataType: 'JSON',
                success: function (response) {
                    activateSubmitBtn()
                    resolve(response.data)
                },
                error: function (response) {
                    activateSubmitBtn()
                    if (response?.responseJSON?.data) {
                        reject(response.responseJSON.data)
                    } else if (response?.statusText) {
                        reject(response.statusText)
                    }
                }
            }
            if (type === 'POST') {
                requestOptions.type = type
                requestOptions.contentType = 'application/json'
            }
            if (data) {
                requestOptions.data = JSON.stringify(data)
            }
            if (params) {
                const fields = Object.keys(params)
                for (let field of fields) {
                    requestOptions.url += '&' + field + '=' + params[field]
                }
            }
            $.ajax(requestOptions)
        })
    }

    function createCardForm() {
        var elements = stripe.elements()
        var style = {
            base: {
                color: '#32325d',
                fontFamily: 'Arial, sans-serif',
                fontSmoothing: 'antialiased',
                fontSize: '16px',
                '::placeholder': {}
            },
            invalid: {
                fontFamily: 'Arial, sans-serif',
                color: '#fa755a'
            }
        }
        card = elements.create('card', { style: style })
        // Stripe injects an iframe into the DOM
        card.mount('#card-element')
        card.on('change', function (event) {
            // Disable the Pay button if there are no card details in the Element
            $('.wpsd-donate-button').attr('disabled', event.empty)
            if (event.error) {
                activateSubmitBtn()
                showError(event.error.message)
            }
        })
    }

    function validateForm() {
        if (wpsdAdminScriptObj.publishable_key == '') {
            showError(wpsdAdminScriptObj.validation.not_valid.publishable_key)
            return false
        }
        if ($('#wpsd_donator_first_name').val() == '') {
            showError(wpsdAdminScriptObj.validation.required.first_name)
            $('#wpsd_donator_first_name').focus()
            return false
        }
        if ($('#wpsd_donator_last_name').val() == '') {
            showError(wpsdAdminScriptObj.validation.required.last_name)
            $('#wpsd_donator_last_name').focus()
            return false
        }
        if ($('#wpsd_donator_address').val() == '') {
            showError(wpsdAdminScriptObj.validation.required.address)
            $('#wpsd_donator_address').focus()
            return false
        }
        if ($('#wpsd_donator_email').val() == '') {
            showError(wpsdAdminScriptObj.validation.required.email)
            $('#wpsd_donator_email').focus()
            return false
        }
        if (!wpsd_validate_email($('#wpsd_donator_email').val())) {
            showError(wpsdAdminScriptObj.validation.not_valid.email)
            $('#wpsd_donator_email').focus()
            return false
        }

        var other_amount = localStringToNumber(
            $('#wpsd_donate_other_amount').val()
        )

        other_amount = (other_amount * 100).toFixed(0)
        console.log(other_amount)
        if (!other_amount || other_amount < 50) {
            console.log(
                `this is not valid donation, ${$(
                    '#wpsd_donate_other_amount'
                ).val()}, must be at least 0.50`
            )
            showError(wpsdAdminScriptObj.validation.not_valid.donation_amount)
            return false
        }

        wpsdDonateAmount = other_amount
        wpsdCustomAmount = true

        return true
    }

    function activateSubmitBtn() {
        $('.wpsd-donate-button').removeAttr('disabled')
        $('.wpsd-donate-button').removeClass('disabled')
    }
    function disableSubmitBtn() {
        $('.wpsd-donate-button').attr('disabled', true)
        $('.wpsd-donate-button').addClass('disabled')
    }

    // Show the customer the error from Stripe if their card fails to charge
    function showError(errorMsgText) {
        $('.wpsd-donation-message-con').removeClass('message-hidden')
        setTimeout(function () {
            $('#wpsd-donation-message').fadeIn(function () {
                $(this).addClass('error')
                $(this).css('visibility', 'visible')
                if (
                    typeof errorMsgText === 'object' &&
                    errorMsgText.hasOwnProperty('errors')
                ) {
                    //
                    var html = `<p>${errorMsgText.message}</p>`
                    html += '<ul>'
                    errorMsgText.errors.forEach((item) => {
                        html += `<li>${item}</li>`
                    })
                    html += '</ul>'
                    $(this).html(html)
                } else {
                    $(this).html(errorMsgText)
                }
            })
            setTimeout(function () {
                $('#wpsd-donation-message').fadeIn(function () {
                    $(this).removeClass('error')
                    $(this).css('visibility', 'hidden')
                    setTimeout(function () {
                        $('.wpsd-donation-message-con').addClass(
                            'message-hidden'
                        )
                    }, 300)
                })
            }, 90000)
        }, 200)
    }

    // Show the customer the error from Stripe if their card fails to charge
    function showMessage(message) {
        $('.wpsd-donation-message-con').removeClass('message-hidden')
        setTimeout(function () {
            $('#wpsd-donation-message').fadeIn(function () {
                $(this).addClass('success')
                $(this).css('visibility', 'visible')
                $(this).html(message)
            })
            window.location.href =
                'https://asianlegacylibrary.org/donate/thank-you/'
            setTimeout(function () {
                $('#wpsd-donation-message').fadeIn(function () {
                    $(this).removeClass('success')
                    $(this).css('visibility', 'hidden')
                    setTimeout(function () {
                        $('.wpsd-donation-message-con').addClass(
                            'message-hidden'
                        )
                    }, 300)
                })
            }, 90000)
        }, 200)
    }

    function wpsd_validate_email($email) {
        var emailReg = /^([\w-\.]+@([\w-]+\.)+[\w-]{2,4})?$/
        return emailReg.test($email)
    }

    var currency = wpsdAdminScriptObj.currency
    addCurrencyFieldType()
    function addCurrencyFieldType() {
        // amount:
        var currencyInput = document.querySelector('input[type="currency"]')
        if (!currencyInput) {
            return
        }
        // format initial value
        onBlur({ target: currencyInput })

        // bind event listeners
        currencyInput.addEventListener('focus', onFocus)
        currencyInput.addEventListener('blur', onBlur)
    }
    function localStringToNumber(s) {
        return Number(String(s).replace(/[^0-9.-]+/g, ''))
    }

    function onFocus(e) {
        if (!e.target) {
            return
        }
        var value = e.target.value
        e.target.value = value ? localStringToNumber(value) : ''
    }

    function onBlur(e) {
        if (!e.target) {
            return
        }
        var value = e.target.value

        var options = {
            maximumFractionDigits: 2,
            currency: currency,
            style: 'currency',
            currencyDisplay: 'symbol'
        }

        e.target.value = value
            ? localStringToNumber(value).toLocaleString(undefined, options)
            : ''
    }
})(window, jQuery)
