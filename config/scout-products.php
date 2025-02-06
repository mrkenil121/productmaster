<?php

return [
    'settings' => [
        'displayedAttributes' => [
            'id',
            'code',
            'name',
            'manufacturer',
            'combination_string',
            'mrp',
            'sales_price',
            'is_active',
            'is_discontinued',
            'is_assured',
            'is_refrigerated',
            'created_at',
            'updated_at',
            'published_at',
            'creator_name',
            'publisher_name'
        ],
        'searchableAttributes' => [
            'name',
            'code',
            'manufacturer',
            'combination_string'
        ],
        'filterableAttributes' => [
            'is_active',
            'is_discontinued',
            'is_assured',
            'is_refrigerated',
            'mrp',
            'sales_price'
        ],
        'sortableAttributes' => [
            'created_at',
            'updated_at',
            'published_at',
            'mrp',
            'sales_price'
        ]
    ]
];