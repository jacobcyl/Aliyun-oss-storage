#Aliyun-oss-storage for Laravel 5+
Aliyun oss filesystem storage adapter for laravel 5. You can use Aliyun OSS just like laravel Storage as usual.    
借鉴了一些优秀的代码，综合各方，同时做了更多优化，将会添加更多完善的接口和插件，打造Laravel最好的OSS Storage扩展
##Inspired By
- [thephpleague/flysystem-aws-s3-v2](https://github.com/thephpleague/flysystem-aws-s3-v2)
- [apollopy/flysystem-aliyun-oss](https://github.com/apollopy/flysystem-aliyun-oss) 

##Require
- Laravel 5+
- cURL extension

##Installation
In order to install AliOSS-storage, just add

    "jacobcyl/ali-oss-storage": "^2.0"

to your composer.json. Then run `composer install` or `composer update`.  
Or you can simply run below command：

    "composer require jacobcyl/ali-oss-storage:^2.0"
    
to install.
Then in your `config/app.php` add
```php
Jacobcyl\AliOSS\AliOssServiceProvider::class,
```
##Configuration
add the following in app/filesystems.php:
```php
    'disks'=>[
        ...
        'oss' => [
                'driver'        => 'oss',
                'access_id'     => '<Your Aliyun OSS AccessKeyId>',
                'access_key'    => '<Your Aliyun OSS AccessKeySecret>',
                'bucket'        => '<OSS bucket name>',
                'endpoint'      => '<the endpoint of OSS, E.g: oss-cn-hangzhou.aliyuncs.com> OR your custom domain, E.g:img.abc.com',
                'isCName'       => <true if use custom domain as endpoint or false>,
                'debug'         => <true|false>
        ],
        ...
    ]
```
then set the default driver in app/filesystems.php:
```php
    'default' => 'oss',
```
Ok, you are finish to configure. Just feel free to use Aliyun OSS like Storage!

##Usage
See [Larave doc for Storage](https://laravel.com/docs/5.2/filesystem#custom-filesystems)
Or you can learn here:

> First you must use Storage facade

```php
use Storage;
```    
> Then You can use all APIs of laravel Storage

```php
Storage::disk('oss'); // if default filesystems driver is oss, you can skip this step

//fetch all files of specified bucket(see upond configuration)
Storage::files($directory);
Storage::allFiles($directory);

Storage::put('path/to/file/file.jpg', $contents); //first parameter is the target file path, second paramter is file content
Storage::putFile('path/to/file/file.jpg', 'local/path/to/local_file.jpg'); // upload file from local path
//new plugin for v2.0 version
Storage::putRemoteFile('target/path/to/file/jacob.jpg', 'http://example.com/jacob.jpg'); //upload remote file to storage by remote url

Storage::get('path/to/file/file.jpg'); // get the file object by path
Storage::exists('path/to/file/file.jpg'); // determine if a given file exists on the storage(OSS)
Storage::size('path/to/file/file.jpg'); // get the file size (Byte)
Storage::lastModified('path/to/file/file.jpg'); // get date of last modification

Storage::directories($directory); // Get all of the directories within a given directory
Storage::allDirectories($directory); // Get all (recursive) of the directories within a given directory

Storage::copy('old/file1.jpg', 'new/file1.jpg');
Storage::move('old/file1.jpg', 'new/file1.jpg');
Storage::rename('path/to/file1.jpg', 'path/to/file2.jpg');

Storage::prepend('file.log', 'Prepended Text'); // Prepend to a file.
Storage::append('file.log', 'Appended Text'); // Append to a file.

Storage::delete('file.jpg');
Storage::delete(['file1.jpg', 'file2.jpg']);

Storage::makeDirectory($directory); // Create a directory.
Storage::deleteDirectory($directory); // Recursively delete a directory.It will delete all files within a given directory, SO Use with caution please.
```

##Documentation
More development detail see [Aliyun OSS DOC](https://help.aliyun.com/document_detail/32099.html?spm=5176.doc31981.6.335.eqQ9dM)
##License
Source code is release under MIT license. Read LICENSE file for more information.
