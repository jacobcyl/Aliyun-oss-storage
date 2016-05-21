<?php

namespace Jacobcyl\AliOSS;

use Jacobcyl\AliOSS\Plugins\PutFile;
use Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use League\Flysystem\Filesystem;
use OSS\OssClient;

class AliOssServiceProvider extends ServiceProvider
{

    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        //发布配置文件
        /*
        if (function_exists('config_path')) {
            $this->publishes([
                __DIR__ . '/config/config.php' => config_path('alioss.php'),
            ], 'config');
        }
        */

        Storage::extend('oss', function($app, $config)
        {
            $accessId  = $config['access_id'];
            $accessKey = $config['access_key'];
            $endPoint  = $config['endpoint'];
            $bucket    = $config['bucket'];
            $isCname   = $config['isCName'];

            $client  = new OssClient($accessId, $accessKey, $endPoint, $isCname);
            $adapter = new AliOssAdapter($client, $bucket);
            //Log::debug($client);
            $filesystem =  new Filesystem($adapter);
            
            $filesystem->addPlugin(new PutFile());
            //$filesystem->addPlugin(new CallBack());
            return $filesystem;
        });
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
    }

}
