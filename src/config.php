<?php

use Illuminate\Auth\AuthenticationException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

return [
    'response'=>[
        // 页码信息返回字段
        'page_info'=>[
            'current_page',
            'last_page',
            'per_page',
            'total',
        ],
    ],
    'exception' => [
        'do_report'=>[
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