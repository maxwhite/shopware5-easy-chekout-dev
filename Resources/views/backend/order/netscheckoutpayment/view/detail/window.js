// window extends

Ext.define('Shopware.apps.Order.netscheckoutpayment.view.detail.Window',
    {
        override: 'Shopware.apps.Order.view.detail.Window',


        createTabPanel: function()
        {
            var me = this;
            var tab_panel = me.callParent(arguments);

            console.log('on window.js');


            /*
            tab_panel.add(Ext.create('Shopware.apps.Order.netscheckoutpayment.view.detail.OperationsTab', {
                record: me.record,
                //quickpayPaymentStore: Ext.getStore('quickpay-payment-store')
            }));

            */


            return tab_panel;
        }


    });