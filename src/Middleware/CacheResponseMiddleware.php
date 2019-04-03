<?php
/**
 * Description: 页面缓存中间件
 * Project: laravel_api
 * Author: Ciel (luffywang622@gmail.com)
 * Created on: 2019/04/03 10:53
 * Created by PhpStorm.
 */

namespace Cyd622\LaravelApi\Middleware;

use Illuminate\Http\Request;

class CacheResponseMiddleware
{
    /**
     * the cache tag
     */
    const CACHE_TAG = 'PageCache';

    /**
     * @var \Illuminate\Http\Request
     */
    protected $request;

    /**
     * @var \Closure
     */
    protected $next;

    /**
     * 缓存分钟
     *
     * @var int|null
     */
    protected $minutes;

    /**
     * 缓存数据
     *
     * @var array
     */
    protected $cacheContent;

    /**
     * 缓存命中状态，1为命中，0为未命中
     *
     * @var int
     */
    protected $cacheHit = 1;

    /**
     * 缓存Key
     *
     * @var string
     */
    protected $cacheKey;

    /**
     * Handle an incoming request
     * @param $request
     * @param \Closure $next
     * @param null $minutes
     *
     * @return mixed
     */
    public function handle(Request $request, \Closure $next, $minutes = null)
    {

        $this->prepare($request, $next, $minutes);

        // skip cache
        if ($request->has('skip_cache')) {
            return $next($request);
        }

        // flush cache
        if ($request->has('flush_cache')) {
            $this->cacheSet()->flush();
            return $next($request);
        }

        // clear current request cache
        if ($request->has('clear_cache')) {
            $this->cacheSet()->forget($this->cacheKey);
            return $next($request);
        }

        $this->responseCache();

        $response = \response()->make($this->cacheContent, 200, $this->getHeaders());
        if ($response->isSuccessful() && !$response->headers->has('ETag')) {
            $response->setEtag(sha1($response->getContent()));
        }

        $response->isNotModified(app('request'));
        return $response;
    }

    /**
     * resolve value
     * @param $request
     * @param \Closure $next
     * @param null $minutes
     */
    protected function prepare($request, \Closure $next, $minutes = null)
    {
        $this->request = $request;
        $this->next = $next;
        $this->cacheKey = $this->resolveKey();
        $this->minutes = $this->resolveMinutes($minutes);
    }

    /**
     * cache this response content
     */
    protected function responseCache()
    {
        $this->cacheContent = $this->cacheSet()->remember(
            $this->cacheKey,
            $this->minutes,
            function () {
                $this->cacheMissed();

                /** @var \Illuminate\Http\Response $response */
                $response = ($this->next)($this->request);

                // json数据是需要解析成数组格式
                if ($response instanceof \Illuminate\Http\JsonResponse) {
                    return $response->getData(true);
                }

                return $response->getContent();
            }
        );
    }

    /**
     * @return array
     */
    protected function getHeaders()
    {
        $headers = [
            'X-Cache' => $this->cacheHit ? 'Hit from cache' : 'Missed',
            'X-Cache-Key' => $this->cacheKey,
            'X-Cache-Expires' => now()->addMinutes($this->minutes)->format('Y-m-d H:i:s T'),
        ];
        return $headers;
    }

    /**
     * @return string
     */
    protected function resolveKey()
    {
        $queryKeys = ['skip_cache', 'flush_cache', 'clear_cache'];
        $query = $this->request->except($queryKeys);
        return md5($this->request->url() . json_encode($query) . $this->request->server('HTTP_X_REQUESTED_WITH'));
    }


    /**
     * @return \Illuminate\Cache\CacheManager|\Illuminate\Cache\TaggedCache|\Illuminate\Foundation\Application|mixed
     */
    protected function cacheSet()
    {
        if (\Cache::getStore() instanceof \Illuminate\Cache\TaggableStore) {
            return \Cache::tags(self::CACHE_TAG);
        }
        $this->cacheKey = self::CACHE_TAG . '.' . $this->cacheKey;
        return app('cache');
    }

    /**
     * @param null $minutes
     * @return int|mixed
     */
    protected function resolveMinutes($minutes = null)
    {
        return is_null($minutes)
            ? $this->getDefaultMinutes()
            : max($this->getDefaultMinutes(), intval($minutes));
    }

    protected function getDefaultMinutes()
    {
        return 10;
    }

    protected function cacheMissed()
    {
        $this->cacheHit = 0;
    }
}