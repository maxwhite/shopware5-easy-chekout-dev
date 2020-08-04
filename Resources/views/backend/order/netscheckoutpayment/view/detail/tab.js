// This tab will be shown in the oreder module


Ext.define('Shopware.apps.Order.netscheckoutpayment.view.detail.tab', {
    extend: 'Ext.container.Container',
    padding: 10,
    title: 'MyOwnTab',

    initComponent: function() {
        var me = this;

        me.items  =  [{
            xtype: 'label',
            html: '<h1>Hello world koko</h1>'
        }];

        me.callParent(arguments);
    }

});