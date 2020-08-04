// controller

Ext.define('Shopware.apps.Order.netscheckoutpayment.controller.NetsController',
{
    override: 'Shopware.apps.Order.controller.Main',

    showOrder: function(record) {
        var me = this;
        me.callParent(arguments);

        console.log('show oreder action');
    }

});