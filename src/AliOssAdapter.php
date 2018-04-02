<?php
/**
 * Created by jacob.
 * Date: 2016/5/19 0019
 * Time: 下午 17:07
 */

namespace Jacobcyl\AliOSS;

use Dingo\Api\Contract\Transformer\Adapter;
use League\Flysystem\Adapter\AbstractAdapter;
use League\Flysystem\AdapterInterface;
use League\Flysystem\Config;
use League\Flysystem\Util;
use OSS\Core\OssException;
use OSS\OssClient;
use Log;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;

class AliOssAdapter extends AbstractAdapter
{
    /**
     * @var Log debug Mode true|false
     */
    protected $debug;
    /**
     * @var array
     */
    protected static $resultMap = [
        'Body'           => 'raw_contents',
        'Content-Length' => 'size',
        'ContentType'    => 'mimetype',
        'Size'           => 'size',
        'StorageClass'   => 'storage_class',
    ];

    /**
     * @var array
     */
    protected static $metaOptions = [
        'CacheControl',
        'Expires',
        'ServerSideEncryption',
        'Metadata',
        'ACL',
        'ContentType',
        'ContentDisposition',
        'ContentLanguage',
        'ContentEncoding',
    ];

    protected static $metaMap = [
        'CacheControl'         => 'Cache-Control',
        'Expires'              => 'Expires',
        'ServerSideEncryption' => 'x-oss-server-side-encryption',
        'Metadata'             => 'x-oss-metadata-directive',
        'ACL'                  => 'x-oss-object-acl',
        'ContentType'          => 'Content-Type',
        'ContentDisposition'   => 'Content-Disposition',
        'ContentLanguage'      => 'response-content-language',
        'ContentEncoding'      => 'Content-Encoding',
    ];

    //Aliyun OSS Client OssClient
    protected $client;
    //bucket name
    protected $bucket;

    protected $endPoint;
    
    protected $cdnDomain;

    protected $ssl;

    protected $isCname;

    //配置
    protected $options = [
        'Multipart'   => 128
    ];


    /**
     * AliOssAdapter constructor.
     *
     * @param OssClient $client
     * @param string    $bucket
     * @param string    $endPoint
     * @param bool      $ssl
     * @param bool      $isCname
     * @param bool      $debug
     * @param null      $prefix
     * @param array     $options
     */
    public function __construct(
        OssClient $client,
        $bucket,
        $endPoint,
        $ssl,
        $isCname = false,
        $debug = false,
        $cdnDomain,
        $prefix = null,
        array $options = []
    )
    {
        $this->debug = $debug;
        $this->client = $client;
        $this->bucket = $bucket;
        $this->setPathPrefix($prefix);
        $this->endPoint = $endPoint;
        $this->ssl = $ssl;
        $this->isCname = $isCname;
        $this->cdnDomain = $cdnDomain;
        $this->options = array_merge($this->options, $options);
    }

    /**
     * Get the OssClient bucket.
     *
     * @return string
     */
    public function getBucket()
    {
        return $this->bucket;
    }

    /**
     * Get the OSSClient instance.
     *
     * @return OssClient
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * Write a new file.
     *
     * @param string $path
     * @param string $contents
     * @param Config $config Config object
     *
     * @return array|false false on failure file meta data on success
     */
    public function write($path, $contents, Config $config)
    {
        $object = $this->applyPathPrefix($path);
        $options = $this->getOptions($this->options, $config);

        if (! isset($options[OssClient::OSS_LENGTH])) {
            $options[OssClient::OSS_LENGTH] = Util::contentSize($contents);
        }
        if (! isset($options[OssClient::OSS_CONTENT_TYPE])) {
            $options[OssClient::OSS_CONTENT_TYPE] = Util::guessMimeType($path, $contents);
        }
        try {
            $this->client->putObject($this->bucket, $object, $contents, $options);
        } catch (OssException $e) {
            $this->logErr(__FUNCTION__, $e);
            return false;
        }
        return $this->normalizeResponse($options, $path);
    }

    /**
     * Write a new file using a stream.
     *
     * @param string $path
     * @param resource $resource
     * @param Config $config Config object
     *
     * @return array|false false on failure file meta data on success
     */
    public function writeStream($path, $resource, Config $config)
    {
        $options = $this->getOptions($this->options, $config);
        $contents = stream_get_contents($resource);

        return $this->write($path, $contents, $config);
    }

    public function writeFile($path, $filePath, Config $config){
        $object = $this->applyPathPrefix($path);
        $options = $this->getOptions($this->options, $config);

        $options[OssClient::OSS_CHECK_MD5] = true;

        if (! isset($options[OssClient::OSS_CONTENT_TYPE])) {
            $options[OssClient::OSS_CONTENT_TYPE] = Util::guessMimeType($path, '');
        }
        try {
            $this->client->uploadFile($this->bucket, $object, $filePath, $options);
        } catch (OssException $e) {
            $this->logErr(__FUNCTION__, $e);
            return false;
        }
        return $this->normalizeResponse($options, $path);
    }

    /**
     * Update a file.
     *
     * @param string $path
     * @param string $contents
     * @param Config $config Config object
     *
     * @return array|false false on failure file meta data on success
     */
    public function update($path, $contents, Config $config)
    {
        if (! $config->has('visibility') && ! $config->has('ACL')) {
            $config->set(static::$metaMap['ACL'], $this->getObjectACL($path));
        }
        // $this->delete($path);
        return $this->write($path, $contents, $config);
    }

    /**
     * Update a file using a stream.
     *
     * @param string $path
     * @param resource $resource
     * @param Config $config Config object
     *
     * @return array|false false on failure file meta data on success
     */
    public function updateStream($path, $resource, Config $config)
    {
        $contents = stream_get_contents($resource);
        return $this->update($path, $contents, $config);
    }

    /**
     * {@inheritdoc}
     */
    public function rename($path, $newpath)
    {
        if (! $this->copy($path, $newpath)){
            return false;
        }

        return $this->delete($path);
    }

    /**
     * {@inheritdoc}
     */
    public function copy($path, $newpath)
    {
        $object = $this->applyPathPrefix($path);
        $newObject = $this->applyPathPrefix($newpath);
        try{
            $this->client->copyObject($this->bucket, $object, $this->bucket, $newObject);
        } catch (OssException $e) {
            $this->logErr(__FUNCTION__, $e);
            return false;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function delete($path)
    {
        $bucket = $this->bucket;
        $object = $this->applyPathPrefix($path);

        try{
            $this->client->deleteObject($bucket, $object);
        }catch (OssException $e) {
            $this->logErr(__FUNCTION__, $e);
            return false;
        }

        return ! $this->has($path);
    }

    /**
     * {@inheritdoc}
     */
    public function deleteDir($dirname)
    {
        $dirname = rtrim($this->applyPathPrefix($dirname), '/').'/';
        $dirObjects = $this->listDirObjects($dirname, true);

        if(count($dirObjects['objects']) > 0 ){

            foreach($dirObjects['objects'] as $object)
            {
                $objects[] = $object['Key'];
            }

            try {
                $this->client->deleteObjects($this->bucket, $objects);
            } catch (OssException $e) {
                $this->logErr(__FUNCTION__, $e);
                return false;
            }

        }

        try {
            $this->client->deleteObject($this->bucket, $dirname);
        } catch (OssException $e) {
            $this->logErr(__FUNCTION__, $e);
            return false;
        }

        return true;
    }

    /**
     * 列举文件夹内文件列表；可递归获取子文件夹；
     * @param string $dirname 目录
     * @param bool $recursive 是否递归
     * @return mixed
     * @throws OssException
     */
    public function listDirObjects($dirname = '', $recursive =  false)
    {
        $delimiter = '/';
        $nextMarker = '';
        $maxkeys = 1000;

        //存储结果
        $result = [];

        while(true){
            $options = [
                'delimiter' => $delimiter,
                'prefix'    => $dirname,
                'max-keys'  => $maxkeys,
                'marker'    => $nextMarker,
            ];

            try {
                $listObjectInfo = $this->client->listObjects($this->bucket, $options);
            } catch (OssException $e) {
                $this->logErr(__FUNCTION__, $e);
                // return false;
                throw $e;
            }

            $nextMarker = $listObjectInfo->getNextMarker(); // 得到nextMarker，从上一次listObjects读到的最后一个文件的下一个文件开始继续获取文件列表
            $objectList = $listObjectInfo->getObjectList(); // 文件列表
            $prefixList = $listObjectInfo->getPrefixList(); // 目录列表

            if (!empty($objectList)) {
                foreach ($objectList as $objectInfo) {

                    $object['Prefix']       = $dirname;
                    $object['Key']          = $objectInfo->getKey();
                    $object['LastModified'] = $objectInfo->getLastModified();
                    $object['eTag']         = $objectInfo->getETag();
                    $object['Type']         = $objectInfo->getType();
                    $object['Size']         = $objectInfo->getSize();
                    $object['StorageClass'] = $objectInfo->getStorageClass();

                    $result['objects'][] = $object;
                }
            }else{
                $result["objects"] = [];
            }

            if (!empty($prefixList)) {
                foreach ($prefixList as $prefixInfo) {
                    $result['prefix'][] = $prefixInfo->getPrefix();
                }
            }else{
                $result['prefix'] = [];
            }

            //递归查询子目录所有文件
            if($recursive){
                foreach( $result['prefix'] as $pfix){
                    $next  =  $this->listDirObjects($pfix , $recursive);
                    $result["objects"] = array_merge($result['objects'], $next["objects"]);
                }
            }

            //没有更多结果了
            if ($nextMarker === '') {
                break;
            }
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function createDir($dirname, Config $config)
    {
        $object = $this->applyPathPrefix($dirname);
        $options = $this->getOptionsFromConfig($config);

        try {
            $this->client->createObjectDir($this->bucket, $object, $options);
        } catch (OssException $e) {
            $this->logErr(__FUNCTION__, $e);
            return false;
        }

        return ['path' => $dirname, 'type' => 'dir'];
    }

    /**
     * {@inheritdoc}
     */
    public function setVisibility($path, $visibility)
    {
        $object = $this->applyPathPrefix($path);
        $acl = ( $visibility === AdapterInterface::VISIBILITY_PUBLIC ) ? OssClient::OSS_ACL_TYPE_PUBLIC_READ : OssClient::OSS_ACL_TYPE_PRIVATE;

        $this->client->putObjectAcl($this->bucket, $object, $acl);

        return compact('visibility');
    }

    /**
     * {@inheritdoc}
     */
    public function has($path)
    {
        $object = $this->applyPathPrefix($path);

        return $this->client->doesObjectExist($this->bucket, $object);
    }

    /**
     * {@inheritdoc}
     */
    public function read($path)
    {
        $result = $this->readObject($path);
        $result['contents'] = (string) $result['raw_contents'];
        unset($result['raw_contents']);
        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function readStream($path)
    {
        $result = $this->readObject($path);
        $result['stream'] = $result['raw_contents'];
        rewind($result['stream']);
        // Ensure the EntityBody object destruction doesn't close the stream
        $result['raw_contents']->detachStream();
        unset($result['raw_contents']);

        return $result;
    }

    /**
     * Read an object from the OssClient.
     *
     * @param string $path
     *
     * @return array
     */
    protected function readObject($path)
    {
        $object = $this->applyPathPrefix($path);

        $result['Body'] = $this->client->getObject($this->bucket, $object);
        $result = array_merge($result, ['type' => 'file']);
        return $this->normalizeResponse($result, $path);
    }

    /**
     * {@inheritdoc}
     */
    public function listContents($directory = '', $recursive = false)
    {
        $dirObjects = $this->listDirObjects($directory, true);
        $contents = $dirObjects["objects"];

        $result = array_map([$this, 'normalizeResponse'], $contents);
        $result = array_filter($result, function ($value) {
            return $value['path'] !== false;
        });

        return Util::emulateDirectories($result);
    }

    /**
     * {@inheritdoc}
     */
    public function getMetadata($path)
    {
        $object = $this->applyPathPrefix($path);

        try {
            $objectMeta = $this->client->getObjectMeta($this->bucket, $object);
        } catch (OssException $e) {
            $this->logErr(__FUNCTION__, $e);
            return false;
        }

        return $objectMeta;
    }

    /**
     * {@inheritdoc}
     */
    public function getSize($path)
    {
        $object = $this->getMetadata($path);
        $object['size'] = $object['content-length'];
        return $object;
    }

    /**
     * {@inheritdoc}
     */
    public function getMimetype($path)
    {
        if( $object = $this->getMetadata($path))
            $object['mimetype'] = $object['content-type'];
        return $object;
    }

    /**
     * {@inheritdoc}
     */
    public function getTimestamp($path)
    {
        if( $object = $this->getMetadata($path))
            $object['timestamp'] = strtotime( $object['last-modified'] );
        return $object;
    }

    /**
     * {@inheritdoc}
     */
    public function getVisibility($path)
    {
        $object = $this->applyPathPrefix($path);
        try {
            $acl = $this->client->getObjectAcl($this->bucket, $object);
        } catch (OssException $e) {
            $this->logErr(__FUNCTION__, $e);
            return false;
        }
        
        if ($acl == OssClient::OSS_ACL_TYPE_PUBLIC_READ ){
            $res['visibility'] = AdapterInterface::VISIBILITY_PUBLIC;
        }else{
            $res['visibility'] = AdapterInterface::VISIBILITY_PRIVATE;
        }

        return $res;
    }


    /**
     * @param $path
     *
     * @return string
     */
    public function getUrl( $path )
    {
        if (!$this->has($path)) throw new FileNotFoundException($filePath.' not found');
        return ( $this->ssl ? 'https://' : 'http://' ) . ( $this->isCname ? ( $this->cdnDomain == '' ? $this->endPoint : $this->cdnDomain ) : $this->bucket . '.' . $this->endPoint ) . '/' . ltrim($path, '/');
    }

    /**
     * The the ACL visibility.
     *
     * @param string $path
     *
     * @return string
     */
    protected function getObjectACL($path)
    {
        $metadata = $this->getVisibility($path);

        return $metadata['visibility'] === AdapterInterface::VISIBILITY_PUBLIC ? OssClient::OSS_ACL_TYPE_PUBLIC_READ : OssClient::OSS_ACL_TYPE_PRIVATE;
    }

    /**
     * Normalize a result from OSS.
     *
     * @param array  $object
     * @param string $path
     *
     * @return array file metadata
     */
    protected function normalizeResponse(array $object, $path = null)
    {
        $result = ['path' => $path ?: $this->removePathPrefix(isset($object['Key']) ? $object['Key'] : $object['Prefix'])];
        $result['dirname'] = Util::dirname($result['path']);

        if (isset($object['LastModified'])) {
            $result['timestamp'] = strtotime($object['LastModified']);
        }

        if (substr($result['path'], -1) === '/') {
            $result['type'] = 'dir';
            $result['path'] = rtrim($result['path'], '/');

            return $result;
        }
        
        $result = array_merge($result, Util::map($object, static::$resultMap), ['type' => 'file']);

        return $result;
    }

    /**
     * Get options for a OSS call. done
     *
     * @param array  $options
     *
     * @return array OSS options
     */
    protected function getOptions(array $options = [], Config $config = null)
    {
        $options = array_merge($this->options, $options);

        if ($config) {
            $options = array_merge($options, $this->getOptionsFromConfig($config));
        }

        return array(OssClient::OSS_HEADERS => $options);
    }

    /**
     * Retrieve options from a Config instance. done
     *
     * @param Config $config
     *
     * @return array
     */
    protected function getOptionsFromConfig(Config $config)
    {
        $options = [];

        foreach (static::$metaOptions as $option) {
            if (! $config->has($option)) {
                continue;
            }
            $options[static::$metaMap[$option]] = $config->get($option);
        }

        if ($visibility = $config->get('visibility')) {
            // For local reference
            // $options['visibility'] = $visibility;
            // For external reference
            $options['x-oss-object-acl'] = $visibility === AdapterInterface::VISIBILITY_PUBLIC ? OssClient::OSS_ACL_TYPE_PUBLIC_READ : OssClient::OSS_ACL_TYPE_PRIVATE;
        }

        if ($mimetype = $config->get('mimetype')) {
            // For local reference
            // $options['mimetype'] = $mimetype;
            // For external reference
            $options['Content-Type'] = $mimetype;
        }

        return $options;
    }

    /**
     * @param $fun string function name : __FUNCTION__
     * @param $e
     */
    protected function logErr($fun, $e){
        if( $this->debug ){
            Log::error($fun . ": FAILED");
            Log::error($e->getMessage());
        }
    }
}
