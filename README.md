# -php-brawl-filesystem-oss
php 文件存储基础服务适配项目（阿里云 OSS）

# 依赖
composer require aliyuncs/oss-sdk-php

# 示例

```php
    $conf = array(
        'accessKeyId' => 'xxx',
        'accessKeySecret' => 'xxxx',
        'endpoint' => 'https://oss-cn-hangzhou.aliyuncs.com',
        'bucket' => 'xxxx',
        'sub_path' => 'uploads/test',
        'base_url' => 'https://xxx.oss-cn-hangzhou.aliyuncs.com',
    );

    $config = FileSystemFactory::createConfig('Oss');
    $config->setConfig($conf);

    $fso = FileSystemFactory::createClient($config);
    try {

        $fso->write('c/' . time() . '.txt', 'test'); // 写文件内容
        $fso->append('uploads/test/c/1687085376.txt', '[test]'); // 文件存在则追加内容
        $fso->append('c/' . time() . '.txt', '[test]'); // 文件不存在则创建

        $fso->copy('uploads/test/c/1687085376.txt', 'a/1.txt');
        $fso->move('uploads/test/c/1687085376.txt', 'a/2.txt');
        $fso->upload('D:\Work\temp\ttt.txt', 'a/3.txt');

        var_dump($fso->exists('uploads/test/a/1.txt'));
        echo $fso->read('uploads/test/a/1.txt');
        $fso->delete('uploads/test/a/3.txt');

        $fso->mkDir('a/d');
        echo $fso->lastModified('uploads/test/a/1.txt');
        echo $fso->fileSize('uploads/test/a/1.txt');
        print_r($fso->readDir('uploads/test/a/'));

        echo $fso->getUri(), '<br>';
        echo $fso->getUrl(), '<br>';
        echo $fso->buildPreSignedUrl('uploads/test/c/1687089158.txt');
    } catch (Throwable $e) {
        echo $e->getMessage(), '<br>';
        echo $fso->getErrorMessage();
    }
```