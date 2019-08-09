<?php

use Illuminate\Auth\AuthenticationException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

return [
    'response' => [
        // 页码信息返回字段
        'page_info' => [
            'current_page',
            'last_page',
            'per_page',
            'total',
        ],
    ],
    'middleware' => [
        'database_listen' => [
            'log_max_files' => 30,
            'listen_type' => [
                'select',
                'update',
                'delete',
                'insert',
            ]
        ],
    ],
    'exception' => [
        // 是否强制http状态码。null,空，false,表示不是强制
        'force_http_code' => 200,
        // 在获取不到异常信息的状态码时设置状态码，设置force_http_code时，不生效
        'default_http_code' => 500,
        'do_report' => [
            UnauthorizedHttpException::class => [
                'msg' => '未授权或Token签名失效',
                'http_code' => 401,
                'status_code' => 104011
            ],
            AuthenticationException::class => [
                'msg' => '未授权或Token签名失效',
                'status_code' => 104013
            ],
        ],
    ]
];