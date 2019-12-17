<?php

namespace app\common\service;

use PHPMailer\PHPMailer\PHPMailer;

class EmailService
{
    const URL = 'smtpdm.aliyun.com';
    const USERNAME = 'www@bzex.vip';
    const PASSWORD = 'kimEX20190726';

    /**
     * @desc 发送短信
     * @param int $sendMail
     * @param string $msg
     * @return bool|string
     */
    public static function send($sendMail, $msg = '')
    {
        $mail = new PHPMailer();
        try {
            //服务器配置
            $mail->CharSet ="UTF-8";
            $mail->SMTPDebug = 0;
            $mail->isSMTP();
            $mail->Host = self::URL;
            $mail->SMTPAuth = true;
            $mail->Username = self::USERNAME;
            $mail->Password = self::PASSWORD;
            $mail->SMTPSecure = 'ssl';
            $mail->Port = 465;

            $mail->setFrom('www@bzex.vip', 'Mailer');  //发件人
            $mail->addAddress($sendMail);  // 收件人

            $mail->isHTML(true);
            $mail->Subject = '交易所邮箱提示';
            $mail->Body = $msg;

            $res = $mail->send();
            if (!$res) {
                throw new \Exception($mail->ErrorInfo);
            }
            return true;
        } catch (Exception $e) {
            return '邮件发送失败: ' . $mail->ErrorInfo;
        }
    }
}