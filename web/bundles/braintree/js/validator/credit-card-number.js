/*jshint bitwise: false*/
define([
    'underscore',
    'orotranslation/js/translator',
    'braintree/js/adapter/credit-card-validator-adapter'
], function(_, __, creditCardValidator) {
    'use strict';

    var defaultParam = {
        message: 'entrepids.braintree.validation.credit_card'
    };

    /**
     * @export braintree/js/validator/credit-card-number
     */
    return [
        'credit-card-number',
        function(value, element) {
            return creditCardValidator.validate(element);
        },
        function(param) {
            param = _.extend({}, defaultParam, param);
            return __(param.message);
        }
    ];
});