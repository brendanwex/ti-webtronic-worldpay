<?php

return [
    'fields' => [
        'transaction_mode' => [
            'label' => 'Transaction Mode',
            'type' => 'radiotoggle',
            'default' => 'test',
            'options' => [
                'test' => 'Test Mode',
                'live' => 'Live Mode',
            ],
        ],
        'live_username' => [
            'label' => 'Live Username',
            'type' => 'text',
            'trigger' => [
                'action' => 'show',
                'field' => 'transaction_mode',
                'condition' => 'value[live]',
            ],
        ],
        'test_username' => [
            'label' => 'Test Username',
            'type' => 'text',
            'trigger' => [
                'action' => 'show',
                'field' => 'transaction_mode',
                'condition' => 'value[test]',
            ],
        ],
        'live_password' => [
            'label' => 'Live Password',
            'type' => 'text',
            'trigger' => [
                'action' => 'show',
                'field' => 'transaction_mode',
                'condition' => 'value[live]',
            ],
        ],
        'test_password' => [
            'label' => 'Test Password',
            'type' => 'text',
            'trigger' => [
                'action' => 'show',
                'field' => 'transaction_mode',
                'condition' => 'value[test]',
            ],
        ],

        'live_account' => [
            'label' => 'Live Account',
            'type' => 'text',
            'trigger' => [
                'action' => 'show',
                'field' => 'transaction_mode',
                'condition' => 'value[live]',
            ],
        ],
        'test_account' => [
            'label' => 'Test Account',
            'type' => 'text',
            'trigger' => [
                'action' => 'show',
                'field' => 'transaction_mode',
                'condition' => 'value[test]',
            ],
        ],

        'order_fee_type' => [
            'label' => 'lang:igniter.payregister::default.label_order_fee_type',
            'type' => 'radiotoggle',
            'span' => 'left',
            'default' => 1,
            'options' => [
                1 => 'lang:igniter.cart::default.menus.text_fixed_amount',
                2 => 'lang:igniter.cart::default.menus.text_percentage',
            ],
        ],
        'order_fee' => [
            'label' => 'lang:igniter.payregister::default.label_order_fee',
            'type' => 'currency',
            'span' => 'right',
            'default' => 0,
            'comment' => 'lang:igniter.payregister::default.help_order_fee',
        ],
        'order_total' => [
            'label' => 'lang:igniter.payregister::default.label_order_total',
            'type' => 'currency',
            'comment' => 'lang:igniter.payregister::default.help_order_total',
        ],
        'order_status' => [
            'label' => 'lang:igniter.payregister::default.label_order_status',
            'type' => 'select',
            'options' => [\Igniter\Admin\Models\Status::class, 'getDropdownOptionsForOrder'],
            'comment' => 'lang:igniter.payregister::default.help_order_status',
        ],
    ],
    'rules' => [
        ['transaction_mode', 'Transaction Mode', 'string'],
        ['live_username', 'Live API Key', 'nullable|required_if:transaction_mode,live|string'],
        ['test_username', 'Test API Key', 'nullable|required_if:transaction_mode,test|string'],
        ['live_password', 'Live API Key', 'nullable|required_if:transaction_mode,live|string'],
        ['test_password', 'Test API Key', 'nullable|required_if:transaction_mode,test|string'],
        ['live_account', 'Live API Key', 'nullable|required_if:transaction_mode,live|string'],
        ['test_account', 'Test API Key', 'nullable|required_if:transaction_mode,test|string'],
        ['order_fee_type', 'lang:igniter.payregister::default.label_order_fee_type', 'integer'],
        ['order_fee', 'lang:igniter.payregister::default.label_order_fee', 'nullable|numeric'],
        ['order_total', 'lang:igniter.payregister::default.label_order_total', 'nullable|numeric'],
        ['order_status', 'lang:igniter.payregister::default.label_order_status', 'nullable|integer'],
    ],
];
