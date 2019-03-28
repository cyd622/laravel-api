<?php
/**
 * Description: 分页转换器，返回字段统一
 * Project: laravel_api
 * Author: Ciel (luffywang622@gmail.com)
 * Created on: 2019/03/26 11:10
 * Created by PhpStorm.
 */

namespace Cyd622\LaravelApi\Response;


use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Http\Resources\Json\ResourceResponse;
use Illuminate\Pagination\AbstractPaginator;
use Illuminate\Support\Arr;

trait PaginatedCollection
{

    public static function collection($resource)
    {

        return new class($resource, get_called_class()) extends ResourceCollection
        {

            public $collects;

            public function __construct($resource, $collects)
            {
                $this->collects = $collects;
                parent::__construct($resource);
            }

            public function toResponse($request)
            {

                $paginationClass = new class($this) extends ResourceResponse
                {

                    public function toResponse($request)
                    {
                        $response = tap(response()->json(
                            $this->wrap(
                                $this->resource->resolve($request),
                                array_merge_recursive(
                                    $this->paginationInformation(),
                                    $this->resource->with($request),
                                    $this->resource->additional
                                )
                            ),
                            $this->calculateStatus()
                        ), function ($response) use ($request) {
                            $response->original = $this->resource->resource->pluck('resource');

                            $this->resource->withResponse($request, $response);
                        });

                        if ($response->isSuccessful() && !$response->headers->has('ETag')) {
                            $response->setEtag(sha1($response->getContent()));
                        }
                        $response->isNotModified(app('request'));
                        return $response;
                    }

                    protected function paginationInformation()
                    {
                        $pageInfoField = config('laravel_api.response.page_info', [
                            'current_page',
                            'last_page',
                            'per_page',
                            'total',
                        ]);

                        $pageInfo = Arr::only($this->resource->resource->toArray(), $pageInfoField);
                        return [
                            'message' => 'Success',
                            'code' => $this->calculateStatus(),
                            'meta' => ['page_info' => $pageInfo],
                        ];
                    }

                };
                return $this->resource instanceof AbstractPaginator
                    ? $paginationClass->toResponse($request)
                    : parent::toResponse($request);
            }

        };

    }
}
