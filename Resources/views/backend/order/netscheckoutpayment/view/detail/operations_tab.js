// operations tab

Ext.define('Shopware.apps.Order.netscheckoutpayment.view.detail.OperationsTab',
    {
        extend: 'Ext.container.Container',

        alias: 'widget.order-netscheckout-panel',

        cls: Ext.baseCSSPrefix + 'order-netscheckout-panel',

        id: 'order-netscheckout-panel',

        padding: 10,

        snippets: {
            title: '{s name=order/tab/title}Nets Checkout{/s}',
            captureButtonText: '{s name=order/capture_button/text}Capture{/s}',
            cancelButtonText: '{s name=order/cancel_button/text}Cancel{/s}',
            refundButtonText: '{s name=order/refund_button/text}Refund{/s}',
            reloadButtonText: '{s name=order/reload_button/text}Reload{/s}',
            gridTitle: '{s name=order/grid/title}Payment History{/s}',
        },

        initComponent: function() {
            var me = this;

            me.title = me.snippets.title;

            me.items = [
                me.createToolbar(),
            ];

            console.log('init component');

            //console.log(me.record);


            me.callParent(arguments);
        },

        createToolbar: function()
        {
            var me = this;

            var status = false;//me.record.raw.quickpay.status * 1;

            me.captureButton = Ext.create('Ext.button.Button', {
                iconCls: 'sprite-tick',
                text: me.snippets.captureButtonText,
                action: 'capturePayment',
                disabled: status, //Only fully authorized
                handler: function() {
                    //me.fireEvent('showCaptureConfirmWindow', me.getPaymentData(), me.record, me);
                    console.log('capture button has been pressed');

                    console.log(Ext.getVersion());

                }
            });

            me.cancelButton = Ext.create('Ext.button.Button', {
                iconCls: 'sprite-cross',
                text: me.snippets.cancelButtonText,
                action: 'cancelPayment',
                disabled: status, //Capture not yet requested
                handler: function() {
                    //me.fireEvent('showCancelConfirmWindow', me.getPaymentData(), me.record, me);
                }
            });

            me.refundButton = Ext.create('Ext.button.Button', {
                iconCls: 'sprite-arrow-return-180-left',
                text: me.snippets.refundButtonText,
                action: 'refundPayment',
                disabled: status, //only fully captured
                handler: function() {
                    //me.fireEvent('showRefundConfirmWindow', me.getPaymentData(), me.record, me);
                }
            });

            me.toolbar = Ext.create('Ext.toolbar.Toolbar', {
                dock: 'top',
                ui: 'shopware-ui',
                margin: '0 0 10px 0',
                style: {
                    padding: 0,
                    backgroundColor: 'transparent'
                },
                items: [
                    me.captureButton,
                    me.cancelButton,
                    me.refundButton,
                  //  me.reloadButton
                ]
            });

            return me.toolbar;
        },
    });