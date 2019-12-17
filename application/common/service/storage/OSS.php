<?php
namespace app\common\service\storage;

use OSS\Core\OssException;
use OSS\OssClient;

/**
 * 阿里云OSS上传
 * Class OSS
 */
class OSS
{
    protected static $accessId;

    protected static $accessKey;

    protected static $instance = null;

    //TODO 空间域名 Domain
    protected static $endpoint;

    //TODO 存储空间名称  公开空间
    protected static $bucket;


    /**
     * TODO 初始化
     * @return null|OssClient
     * @throws \OSS\Core\OssException
     */
    protected static function init()
    {
        $config = config('upload.');

        self::$accessId = $config['access_id'];
        self::$accessKey = $config['access_key'];
        self::$endpoint = $config['endpoint'];
        self::$bucket = $config['bucket'];

        if(!self::$accessId || !self::$accessKey || !self::$endpoint || !self::$bucket){
            exception('请设置 secretKey 和 accessKey 和 空间域名 和 存储空间名称');
        }
        if(self::$instance == null) {
            self::$instance = new OssClient(self::$accessId,self::$accessKey,self::$endpoint);
            if(!self::$instance->doesBucketExist(self::$bucket)) self::$instance->createBucket(self::$bucket,self::$instance::OSS_ACL_TYPE_PUBLIC_READ_WRITE);
        }
        return self::$instance;
    }

    /**
     * TODO 文件上传 名称
     * @param string $filename
     * @return string
     */
    public static function uploadImage($filename = 'image') {
        $request = app('request');
        $file = $request->file($filename);
        $filePath = $file->getRealPath();
        $ext = pathinfo($file->getInfo('name'), PATHINFO_EXTENSION);
        $key = substr(md5($file->getRealPath()) , 0, 5). date('YmdHis') . rand(0, 9999) . '.' . $ext;
        try{
            self::init();
            return self::$instance->uploadFile(self::$bucket, $key, $filePath);
        }catch (OssException $e){
            return $e->getMessage();
        }
    }

    /**
     * TODO 文件上传 内容
     * @param $key
     * @param $content
     * @return string
     */
    public static function uploadImageStream($key, $content)
    {
        try{
            self::init();
            return self::$instance->putObject(self::$storageName,$key,$content);
        }catch (OssException $e){
            return $e->getMessage();
        }
    }

    /**
     * TODO 删除资源
     * @param $key
     * @return mixed
     */
    public static function delete($key)
    {
        try {
            self::init();
            return self::$instance->deleteObject(self::$storageName,$key);
        } catch (OssException $e) {
            return $e->getMessage();
        }
    }

}