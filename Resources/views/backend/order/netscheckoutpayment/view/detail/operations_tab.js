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

            me.netsAmountAuthorized = 0;

            me.items = [
                me.createForm()
            ];

            me.loadPayment();

            me.callParent(arguments);
        },

        createForm : function () {
            var me = this;
            return me.createDetailsContainer();
        },

        loadPayment : function () {

            var me = this;

            me.setLoading(true);
            console.log('loadPaymentStart');
            var form = me.detailsContainer.getForm();

           // form.findField('amountAuthorized').disabled = true;
            me.netsCheckoutPaymentStore.load({

                params: {
                    id: me.record.get('number'),
                    type: 'user'
                },

                callback: function(records, operation, success) {

                    form.loadRecord(records[0]);
                    console.log(records);
                    me.setLoading(false);

                   // form.findField('amountAuthorized').disabled = false;

                    console.log('disabled');
                },
                scope: this
            });

        },

        /**
         *
         */
        createDetailsContainer: function()
        {
            var me = this;

            me.detailsContainer = Ext.create('Ext.form.Panel', {
                cls: 'confirm-panel-main',
                bodyPadding: 5,
                margin: '0 0 5 0',
                layout: 'anchor',
                title: 'Nets Api operations',
                url: 'NetsCheckout/capture',
                defaults: {
                    anchor: '40%',
                    align: 'stretch',
                    width: '40%'
                },

                buttons: [
                    {
                        text: me.snippets.captureButtonText,
                        handler: function() {

                            Ext.Msg.show({
                                title:'Would you like to capture ?',
                                msg: 'Amount will be captured',
                                buttons: Ext.Msg.YESNOCANCEL,
                                icon: Ext.Msg.QUESTION,
                                fn: function(btn) {
                                    if (btn === 'yes') {
                                        me.setLoading(true);
                                        me.detailsContainer.submit({
                                            success: function(form, operation) {

                                                me.setLoading(false);

                                                me.loadPayment();

                                                Shopware.Notification.createGrowlMessage(me.title, 'Success');
                                            },
                                            failure: function(form, operation) {

                                                // var response = operation.result;
                                                me.setLoading(false);

                                                me.loadPayment();

                                                Shopware.Notification.createGrowlMessage(me.title, 'Failure');
                                            }
                                        });

                                    } else if (btn === 'no') {
                                        console.log('No pressed');
                                    } else {
                                        console.log('Cancel pressed');
                                    }
                                }
                            });
                        }
                    },

                    {
                        text: me.snippets.cancelButtonText,
                        handler: function() {
                            /*var form = this.up('form').getForm(); // get the basic form
                            if (form.isValid()) { // make sure the form contains valid data before submitting
                                form.submit({
                                    success: function(form, action) {
                                        Ext.Msg.alert('Success', action.result.msg);
                                    },
                                    failure: function(form, action) {
                                        Ext.Msg.alert('Failed', action.result.msg);
                                    }
                                });
                            } else { // display error alert if the data is invalid
                                Ext.Msg.alert('Invalid Data', 'Please correct form errors.')
                            }
                            */
                        }
                    }
                ],

                items: me.createFields()

            });

            return me.detailsContainer;
        },

        createFields: function() {
            var me = this;

            var items = [
                {
                    xtype: 'textfield',
                    name: 'amountAuthorized',
                    fieldLabel: 'Amount to be captured',
                    value: me.netsAmountAuthorized
                },
                // hiddenfield
                {
                    xtype: 'hiddenfield',
                    name: 'id',
                    value: me.record.get('number')
                },
            ];

            return items;
        },

    });