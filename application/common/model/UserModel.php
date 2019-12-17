<?php

namespace app\common\model;

class UserModel extends BaseModel
{
    /**
     * @var 表
     */
    protected static $name = 'fuser';

    /**
     * @var 主键
     */
    protected static $pk = 'fId';

    public static function getReasonType() {
        return [
            0 => [
                1 => '身份证信息是倒退的，请重新上传。',
                2 => '请手持身份证。',
                3 => '您的证书过期了。',
                4 => '身份证信息模糊不清，您可以使用更好的设备。',
                5 => '请不要阻塞识别信息。',
                6 => '试着让你的身份证靠近照相机，露出你的脸。'
            ],
            1 => [
                1 => 'ID card information is backward, please upload again.',
                2 => 'Please hold your ID card.',
                3 => 'Your certificate has expired。',
                4 => 'I.D. information is ambiguous, you can use better equipment.',
                5 => 'Please do not block identification information.',
                6 => 'Try to get your ID card close to the camera and show your face.'
            ]
        ];

    }
}