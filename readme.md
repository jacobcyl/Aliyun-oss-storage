#Aliyun-oss-storage for Lumen 5+
修改[jacobcyl/Aliyun-oss-storage](https://github.com/jacobcyl/Aliyun-oss-storage)的部分代码使其支持在Lumen中运行.

##Installation

    "composer require robincode/ali-oss-storage"
    
Now, create `config/filesystems.php`, or copy from Laravel 5+.
```php
<?php

return [

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app'),
        ],

        'oss' => [
                'driver'        => 'oss',
                'access_id'     => '<Your Aliyun OSS AccessKeyId>',
                'access_key'    => '<Your Aliyun OSS AccessKeySecret>',
                'bucket'        => '<OSS bucket name>',
                'endpoint'      => '<the endpoint of OSS, E.g: oss-cn-hangzhou.aliyuncs.com> OR your custom domain, E.g:img.abc.com',
                'isCName'       => <true if use custom domain as endpoint or false>,
                'debug'         => <true|false>
        ],

    ],

];

```

In your `bootstrap/app.php` add this code.
```php
// use config/filesystems.php
$app->configure('filesystems');

// use filesystems, you can call app('filesystems')->disk('local')->files('/'); read files
$app->register(Illuminate\Filesystem\FilesystemServiceProvider::class);

// add oss extend
$app->register(Jacobcyl\AliOSS\AliOssServiceProvider::class);
```


##Documentation
see [jacobcyl/Aliyun-oss-storage](https://github.com/jacobcyl/Aliyun-oss-storage)

##License
Source code is release under MIT license. Read LICENSE file for more information.
