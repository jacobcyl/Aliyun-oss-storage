#Aliyun-oss-storage for Lumen 5+
修改[jacobcyl/Aliyun-oss-storage](https://github.com/jacobcyl/Aliyun-oss-storage)的部分代码以便在lumen中运行.

##Installation

    "composer require jacobcyl/ali-oss-storage:^2.0"
    
Then in your `config/app.php` add this line to providers array:
```php
Jacobcyl\AliOSS\AliOssServiceProvider::class,
```


##Documentation
More development detail see [Aliyun OSS DOC](https://help.aliyun.com/document_detail/32099.html?spm=5176.doc31981.6.335.eqQ9dM)
##License
Source code is release under MIT license. Read LICENSE file for more information.
