<?php

namespace Nece\Brawl\FileSystem\Oss;

use Nece\Brawl\ConfigAbstract;
use Nece\Brawl\FileSystem\FileSystemAbstract;
use Nece\Brawl\FileSystem\FileSystemException;
use OSS\Core\OssException;
use OSS\OssClient;
use Throwable;

class FileSystem extends FileSystemAbstract
{

    private $client;
    private $bucket;
    private $region;

    /**
     * 设置配置
     *
     * @Author nece001@163.com
     * @DateTime 2023-06-18
     *
     * @param ConfigAbstract $config
     *
     * @return void
     */
    public function setConfig(ConfigAbstract $config)
    {
        parent::setConfig($config);

        $this->bucket = $this->getConfigValue('bucket');
        $this->region = $this->getConfigValue('region');
    }

    /**
     * 获取客户端
     *
     * @Author nece001@163.com
     * @DateTime 2023-06-18
     *
     * @return \OSS\OssClient
     */
    private function getClient()
    {
        if (!$this->client) {

            $accessKeyId = $this->getConfigValue('accessKeyId');
            $accessKeySecret = $this->getConfigValue('accessKeySecret');
            $endpoint = $this->getConfigValue('endpoint');
            $ConnectTimeout = $this->getConfigValue('connect_timeout');
            $Timeout = $this->getConfigValue('timeout');
            $MaxTries = $this->getConfigValue('max_tries');
            $requestProxy = $this->getConfigValue('proxy');

            try {
                $ossClient = new OssClient($accessKeyId, $accessKeySecret, $endpoint, false, $requestProxy);
                // 设置是否开启SSL证书校验。
                $ossClient->setUseSSL(false);

                // 设置建立连接的超时时间。
                if ($ConnectTimeout) {
                    $ossClient->setConnectTimeout($ConnectTimeout);
                }

                // 设置Socket层传输数据的超时时间。
                if ($Timeout) {
                    $ossClient->setTimeout($Timeout);
                }

                // 设置失败请求重试次数。
                if ($MaxTries) {
                    $ossClient->setMaxTries($MaxTries);
                }

                $this->client = $ossClient;
            } catch (OssException $e) {
                $this->error_message = $e->getMessage();
                throw new FileSystemException('阿里云OSS客户端初始失败');
            }
        }

        return $this->client;
    }

    /**
     * 写文件内容
     *
     * @Author nece001@163.com
     * @DateTime 2023-06-17
     *
     * @param string $path 相对路径
     * @param string $content
     *
     * @return void
     */
    public function write(string $path, string $content): void
    {
        $object_key = $this->buildPathWithSubPath($path);

        try {
            // 文件内容可追加
            // $this->getClient()->putObject($this->bucket, $object_key, $content);
            $this->getClient()->appendObject($this->bucket, $object_key, $content, 0);
        } catch (Throwable $e) {
            $this->error_message = $e->getMessage();
            throw new FileSystemException('OSS写文件内容失败');
        }

        $this->setUri($object_key);
    }

    /**
     * 追加文件内容
     *
     * @Author nece001@163.com
     * @DateTime 2023-06-17
     *
     * @param string $path 相对路径（已存在的文件）
     * @param string $content
     *
     * @return void
     */
    public function append(string $path, string $content): void
    {
        try {
            $position = 0;
            if ($this->exists($path)) {
                $object_key = $path;
                $meta = $this->ossObjectHead($path);
                $position = intval($meta['content-length']);
            } else {
                $object_key = $this->buildPathWithSubPath($path);
            }

            $this->getClient()->appendObject($this->bucket, $object_key, $content, $position);
        } catch (Throwable $e) {
            $this->error_message = $e->getMessage();
            throw new FileSystemException('OSS追加文件内容失败');
        }

        $this->setUri($object_key);
    }

    /**
     * 复制文件
     *
     * @Author nece001@163.com
     * @DateTime 2023-06-17
     *
     * @param string $source 相对路径
     * @param string $destination 相对路径
     *
     * @return void
     */
    public function copy(string $source, string $destination): void
    {
        $object_key = $this->buildPathWithSubPath($destination);

        try {
            $this->getClient()->copyObject($this->bucket, $source, $this->bucket, $object_key);
        } catch (Throwable $e) {
            $this->error_message = $e->getMessage();
            throw new FileSystemException('OSS复制文件失败');
        }

        $this->setUri($object_key);
    }

    /**
     * 移动文件
     *
     * @Author nece001@163.com
     * @DateTime 2023-06-17
     *
     * @param string $source 相对路径
     * @param string $destination 相对路径
     *
     * @return void
     */
    public function move(string $source, string $destination): void
    {
        $object_key = $this->buildPathWithSubPath($destination);

        try {
            $this->getClient()->copyObject($this->bucket, $source, $this->bucket, $object_key);
            $this->getClient()->deleteObject($this->bucket, $source);
        } catch (Throwable $e) {
            $this->error_message = $e->getMessage();
            throw new FileSystemException('OSS移动文件失败');
        }

        $this->setUri($object_key);
    }

    /**
     * 上传文件
     *
     * @Author nece001@163.com
     * @DateTime 2023-06-17
     *
     * @param string $local 绝对路径
     * @param string $to 相对路径
     *
     * @return void
     */
    public function upload(string $local, string $to): void
    {
        $object_key = $this->buildPathWithSubPath($to);

        try {
            $this->getClient()->uploadFile($this->bucket, $object_key, $local);
        } catch (Throwable $e) {
            $this->error_message = $e->getMessage();
            throw new FileSystemException('OSS上传文件失败');
        }

        $this->setUri($object_key);
    }

    /**
     * 文件是否存在
     *
     * @Author nece001@163.com
     * @DateTime 2023-06-17
     *
     * @param string $path 相对路径
     *
     * @return boolean
     */
    public function exists(string $path): bool
    {
        try {
            $this->setUri($path);
            return $this->getClient()->doesObjectExist($this->bucket, $path);
        } catch (Throwable $e) {
            $this->error_message = $e->getMessage();
            throw new FileSystemException('OSS判断文件是否存在失败');
        }
    }

    /**
     * 读取文件内容
     *
     * @Author nece001@163.com
     * @DateTime 2023-06-17
     *
     * @param string $path 相对路径
     *
     * @return string
     */
    public function read(string $path): string
    {
        try {
            return $this->getClient()->getObject($this->bucket, $path);
        } catch (Throwable $e) {
            $this->error_message = $e->getMessage();
            throw new FileSystemException('OSS读取文件内容失败');
        }
    }

    /**
     * 删除文件
     *
     * @Author nece001@163.com
     * @DateTime 2023-06-17
     *
     * @param string $path 相对路径
     *
     * @return void
     */
    public function delete(string $path): void
    {
        try {
            $this->getClient()->deleteObject($this->bucket, $path);
        } catch (Throwable $e) {
            $this->error_message = $e->getMessage();
            throw new FileSystemException('OSS删除文件失败');
        }
    }

    /**
     * 创建目录
     *
     * @Author nece001@163.com
     * @DateTime 2023-06-17
     *
     * @param string $path 相对路径
     *
     * @return void
     */
    public function mkDir(string $path): void
    {
        try {
            $this->getClient()->putObject($this->bucket, $path, '');
        } catch (Throwable $e) {
            $this->error_message = $e->getMessage();
            throw new FileSystemException('OSS创建目录失败');
        }
    }

    /**
     * 获取最后更新时间
     *
     * @Author nece001@163.com
     * @DateTime 2023-06-17
     *
     * @param string $path 相对路径
     *
     * @return integer
     */
    public function lastModified(string $path): int
    {
        try {
            $meta = $this->ossObjectHead($path);
            return strtotime($meta['last-modified']);
        } catch (Throwable $e) {
            $this->error_message = $e->getMessage();
            throw new FileSystemException('OSS创建目录失败');
        }
    }

    /**
     * 获取文件大小(字节数)
     *
     * @Author nece001@163.com
     * @DateTime 2023-06-17
     *
     * @param string $path 相对路径
     *
     * @return integer
     */
    public function fileSize(string $path): int
    {
        try {
            $meta = $this->ossObjectHead($path);
            return intval($meta['content-length']);
        } catch (Throwable $e) {
            $this->error_message = $e->getMessage();
            throw new FileSystemException('OSS创建目录失败');
        }
    }

    /**
     * 列表
     *
     * @Author nece001@163.com
     * @DateTime 2023-06-17
     *
     * @param string $path 相对路径
     *
     * @return array
     */
    public function readDir(string $path): array
    {
        try {
            $objectList = $this->ossListObject($path);

            $list = array();
            foreach ($objectList as $objectInfo) {
                $list[] = $objectInfo->getKey();
            }

            return $list;
        } catch (Throwable $e) {
            $this->error_message = $e->getMessage();
            throw new FileSystemException('OSS目录列表失败');
        }
    }

    /**
     * 生成预签名 URL
     *
     * @Author nece001@163.com
     * @DateTime 2023-06-17
     * 
     * @param string $path 相对路径
     * @param int $expires 过期时间
     *
     * @return string
     */
    public function buildPreSignedUrl(string $path, $expires = 120): string
    {
        try {
            return $this->getClient()->generatePresignedUrl($this->bucket, $path, time() + $expires);
            // return $this->getClient()->signUrl($this->bucket, $path, $expires);
        } catch (Throwable $e) {
            $this->error_message = $e->getMessage();
            throw new FileSystemException('OSS生成预签名 URL失败');
        }
    }

    /**
     * 获取元数据
     *
     * @Author nece001@163.com
     * @DateTime 2023-06-18
     *
     * @param string $object_key
     * @param boolean $simple
     *
     * @return array
     */
    private function ossObjectHead(string $object_key, $simple = true)
    {
        if ($simple) {
            // 获取文件的部分元信息。
            $result = $this->getClient()->getSimplifiedObjectMeta($this->bucket, $object_key);
        } else {
            $result = $this->getClient()->getObjectMeta($this->bucket, $object_key);
        }
        // print_r($result);

        return $result;
    }

    /**
     * 文件列表
     *
     * @Author nece001@163.com
     * @DateTime 2023-06-18
     *
     * @param string $path
     * @param integer $limit
     * @param string $marker
     *
     * @return array
     */
    private function ossListObject(string $path, $limit = 200, $marker = null)
    {
        $options = array(
            OssClient::OSS_PREFIX => $path,
            OssClient::OSS_MAX_KEYS => $limit,
        );

        if ($marker) {
            $options[OssClient::OSS_MARKER] = $marker;
        }

        // $listObjectInfo = $this->getClient()->listObjects($this->bucket, $options);
        $listObjectInfo = $this->getClient()->listObjectsV2($this->bucket, $options);
        return $listObjectInfo->getObjectList();
    }
}
