<?php
/**
 * Description: 存储最后一次的token任务
 * Project: laravel_api
 * Author: Ciel (luffywang622@gmail.com)
 * Created on: 2019/03/26 16:21
 * Created by PhpStorm.
 */

namespace Cyd622\LaravelApi\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Cache;

class SaveUserTokenJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    protected $user;
    protected $token;

    /**
     * Create a new job instance.
     *
     * @param $user
     * @param $token
     */
    public function __construct($user, $token)
    {
        $this->user = $user;
        $this->token = $token;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $key = "User.{$this->user->id}:LastToken";
        Cache::forever($key, $this->token);
    }
}