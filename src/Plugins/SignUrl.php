<?php
/**
 * Created by summer.
 * User: summer
 * Date: 18/7/22
 * Time: 下午7:03
 */

namespace Jacobcyl\AliOSS\Plugins;

use League\Flysystem\Plugin\AbstractPlugin;
use Illuminate\Contracts\Filesystem\FileNotFoundException;

class SignUrl extends AbstractPlugin
{

    /**
     * Get the method name.
     *
     * @return string
     */
    public function getMethod()
    {
        return 'signUrl';
    }

    public function handle($path, $timeout = 600){
        if (!$this->filesystem->getAdapter()->has($path)) throw new FileNotFoundException($path.' not found');
        return $this->filesystem->getAdapter()->getClient()->signUrl($this->filesystem->getAdapter()->getBucket(),$path,$timeout);
    }
}
