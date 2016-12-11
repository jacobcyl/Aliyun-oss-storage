#Aliyun-oss-storage for Lumen 5+
修改[jacobcyl/Aliyun-oss-storage](https://github.com/jacobcyl/Aliyun-oss-storage)的部分代码使其支持在Lumen中运行.

##Installation

    "composer require jacobcyl/ali-oss-storage:^2.0"
    
Then in your `bootstrap/app.php` add this line to providers array:
```php
// use config/filesystems.php
$app->configure('filesystems');

// use filesystems, you can call app('filesystems')->disk('local')->files('/'); read files
$app->register(Illuminate\Filesystem\FilesystemServiceProvider::class);

$app->register(Jacobcyl\AliOSS\AliOssServiceProvider::class);
```


##Documentation
see [jacobcyl/Aliyun-oss-storage](https://github.com/jacobcyl/Aliyun-oss-storage)

##License
Source code is release under MIT license. Read LICENSE file for more information.
