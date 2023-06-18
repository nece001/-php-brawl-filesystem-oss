<?php

namespace Nece\Brawl\FileSystem\Oss;

use Nece\Brawl\ConfigAbstract;

class Config extends ConfigAbstract
{
    public function buildTemplate()
    {

        $this->addTemplate(true, 'accessKeyId', 'accessKeyId', 'OSS通过使用AccessKeyId 和AccessKeySecret对称加密的方法来验证某个请求的发送者身份。AccessKeyId用于标识用户。');
        $this->addTemplate(true, 'accessKeySecret', 'accessKeySecret ', 'AccessKeySecret是用户用于加密签名字符串和OSS用来验证签名字符串的密钥，其中AccessKeySecret 必须保密。');
        $this->addTemplate(true, 'endpoint', '访问域名', '例：https://oss-cn-hangzhou.aliyuncs.com，参考：https://help.aliyun.com/document_detail/31837.htm?spm=a2c4g.31947.0.0.3f195d7aoBi8qR#concept-zt4-cvy-5db');
        $this->addTemplate(true, 'bucket', '存储桶', '存储空间是您用于存储对象（Object）的容器，所有的对象都必须隶属于某个存储空间。');
        // $this->addTemplate(true, 'region', '区域', '创建桶归属的region', '地域表示 OSS 的数据中心所在物理位置。参考：https://help.aliyun.com/document_detail/31837.htm?spm=a2c4g.31947.0.0.3f195d7aoBi8qR#concept-zt4-cvy-5db');
        $this->addTemplate(true, 'base_url', '基础URL', '例：http(s)://xxxxx.com');
        $this->addTemplate(false, 'sub_path', '子目录', '例：a/b');

        $this->addTemplate(false, 'timeout', '请求超时', '秒', '600');
        $this->addTemplate(false, 'connect_timeout', '连接超时', '秒', '60');
        $this->addTemplate(false, 'max_tries', '失败请求重试次数', '');
        $this->addTemplate(false, 'proxy', '代理', '例：http://xxx:xx');
    }
}
