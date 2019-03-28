<?php
/**
 * Description: 统一API响应
 * Project: laravel_api
 * Author: Ciel (luffywang622@gmail.com)
 * Created on: 2019/03/26 12:13
 * Created by PhpStorm.
 */

namespace Cyd622\LaravelApi\Response;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;

trait ApiResponse
{
    /**
     * @var int $httpCode
     */
    protected $httpCode = Response::HTTP_OK;
    /**
     * @var int|null
     */
    protected $statusCode;

    /**
     * @return int
     */
    public function getHttpCode()
    {
        return $this->httpCode;
    }

    /**
     * @param $httpCode
     * @return $this
     */
    public function setHttpCode($httpCode)
    {
        $this->httpCode = $httpCode;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getStatusCode()
    {
        return $this->statusCode;
    }

    /**
     * @param  $statusCode
     * @return $this
     */
    public function setStatusCode($statusCode)
    {
        $this->statusCode = $statusCode;
        return $this;
    }

    /**
     * @param $data
     * @param array $header
     * @return JsonResponse
     */
    private function respond($data, array $header = [])
    {
        $response = \response()->json($data, $this->getHttpCode(), $header);
        if ($response->isSuccessful() && !$response->headers->has('ETag')) {
            $response->setEtag(sha1($response->getContent()));
        }
        $response->isNotModified(app('request'));
        return $response;
    }

    /**
     * @param string $message
     * @param  $data
     * @param array $meta
     * @return JsonResponse
     */
    private function buildRespond(string $message, $data, array $meta = [])
    {
        // 处理分页数据
        if ($data instanceof LengthAwarePaginator) {

            $pageInfoField = config('laravel_api.response.page_info', [
                'current_page',
                'last_page',
                'per_page',
                'total',
            ]);

            $meta['page_info'] = Arr::only($data->toArray(), $pageInfoField);

            $data = $data->items();
        }

        $responded = [
            'data' => $data,
            'message' => $message,
            'code' => $this->statusCode ? $this->statusCode : $this->httpCode,
        ];

        if ($meta) {
            $responded['meta'] = $meta;
        }

        return $this->respond($responded);
    }

    /**
     * @param string $message
     * @param array $data
     * @return JsonResponse
     */
    protected function message($message, array $data = [])
    {
        return $this->buildRespond($message, $data);
    }

    /**
     * @param string $message
     * @return JsonResponse
     */
    protected function created($message = 'Created')
    {
        return $this->setHttpCode(Response::HTTP_CREATED)->message($message);
    }

    /**
     * @param $data
     * @param array|null $meta
     * @param string $message
     * @return JsonResponse
     */
    protected function success($data, array $meta = null, $message = 'Success')
    {
        return $this->buildRespond($message, $data, $meta ? $meta : []);
    }

    /**
     * @param string $message
     * @param int $httpCode
     * @param int $statusCode
     * @return JsonResponse
     */
    protected function error(string $message = 'Error', int $httpCode = null, int $statusCode = null)
    {
        $httpCode = $httpCode ?? Response::HTTP_BAD_REQUEST;
        $statusCode = $statusCode ?? $httpCode;
        return $this->setHttpCode($httpCode)->setStatusCode($statusCode)->message($message);
    }

    /**
     * @param string $message
     * @return JsonResponse
     */
    protected function internalError($message = 'Internal Error')
    {
        return $this->error($message);
    }

    /**
     * @param string $message
     * @return JsonResponse
     */
    protected function notFond($message = 'Not Fond')
    {
        return $this->error($message, Response::HTTP_NOT_FOUND);
    }

    /**
     * @param string $message
     * @return JsonResponse
     */
    protected function unauthorized($message = 'Unauthorized')
    {
        return $this->error($message, Response::HTTP_UNAUTHORIZED);
    }

    /**
     * @param string $message
     * @return JsonResponse
     */
    protected function forbidden($message = 'Forbidden')
    {
        return $this->error($message, Response::HTTP_FORBIDDEN);
    }

    /**
     * @param string $message
     * @return JsonResponse
     */
    protected function unprocessableEntity($message = 'Unprocessable Entity')
    {
        return $this->error($message, Response::HTTP_UNPROCESSABLE_ENTITY);
    }

}
