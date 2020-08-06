// model payment

Ext.define('Shopware.apps.Order.netscheckoutpayment.model.Payment',
    {
        extend: 'Ext.data.Model',

        fields: [
            { name: 'id', type: 'string' },
            { name: 'orderId', type: 'string' },
            { name: 'amountAuthorized', type: 'int'},
            { name: 'amountCaptured', type: 'int'},
            { name: 'amountRefunded', type: 'int'},
        ],

        proxy: {
            type: 'ajax',

            api: {
                read:'{url controller="QuickPay" action="detail"}',
            },

            reader: {
                type: 'json',
                root: 'data',
                messageProperty: 'message'
            }
        },
    });