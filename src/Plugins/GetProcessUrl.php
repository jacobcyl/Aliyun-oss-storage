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

class GetProcessUrl extends AbstractPlugin
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

    /**
     * 对oss图片进行操作
     * 阿里云 oss 帮助文档：https://help.aliyun.com/document_detail/44688.html?spm=a2c4g.11186623.6.1199.40572e934MoHWu
     * @param $path 文件路径
     * @param string $action 操作名称
     * @param array $option 操作参数
     * @return string 返回 url 链接地址
     * @throws \Exception
     */
    public function handle($path, $action = 'resize', $option = ['m' => 'fixed','h' => '100','w' => '100']){
        if(empty($action) || empty($option)){
            throw new \Exception('操作名称和操作参数不能为空！');
        }

        $url = $this->filesystem->getAdapter()->getUrl($path);

        $param = '';
        foreach ($option as $key => $value){
            $param .= ','.$key.'_'.$value;
        }

        return $url.'?x-oss-process=image/'.$action.$param;
    }
}
