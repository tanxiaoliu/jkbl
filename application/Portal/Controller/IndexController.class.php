<?php
/*
 *      _______ _     _       _     _____ __  __ ______
 *     |__   __| |   (_)     | |   / ____|  \/  |  ____|
 *        | |  | |__  _ _ __ | | _| |    | \  / | |__
 *        | |  | '_ \| | '_ \| |/ / |    | |\/| |  __|
 *        | |  | | | | | | | |   <| |____| |  | | |
 *        |_|  |_| |_|_|_| |_|_|\_\\_____|_|  |_|_|
 */
/*
 *     _________  ___  ___  ___  ________   ___  __    ________  _____ ______   ________
 *    |\___   ___\\  \|\  \|\  \|\   ___  \|\  \|\  \ |\   ____\|\   _ \  _   \|\  _____\
 *    \|___ \  \_\ \  \\\  \ \  \ \  \\ \  \ \  \/  /|\ \  \___|\ \  \\\__\ \  \ \  \__/
 *         \ \  \ \ \   __  \ \  \ \  \\ \  \ \   ___  \ \  \    \ \  \\|__| \  \ \   __\
 *          \ \  \ \ \  \ \  \ \  \ \  \\ \  \ \  \\ \  \ \  \____\ \  \    \ \  \ \  \_|
 *           \ \__\ \ \__\ \__\ \__\ \__\\ \__\ \__\\ \__\ \_______\ \__\    \ \__\ \__\
 *            \|__|  \|__|\|__|\|__|\|__| \|__|\|__| \|__|\|_______|\|__|     \|__|\|__|
 */
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2014 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: Dean <zxxjjforever@163.com>
// +----------------------------------------------------------------------
namespace Portal\Controller;
use Common\Controller\HomebaseController; 
/**
 * 首页
 */
class IndexController extends HomebaseController {
	protected $theme = 'jkbl';

	
    //首页 小夏是老猫除外最帅的男人了
	public function index()
	{
		if (sp_is_weixin()) {
			//微信登录
			/*$options = array(
				'token' => 'tokenaccesskey', //填写你设定的key
				'encodingaeskey' => 'encodingaeskey', //填写加密用的EncodingAESKey
				'appid' => 'wxdk1234567890', //填写高级调用功能的app id
				'appsecret' => 'xxxxxxxxxxxxxxxxxxx' //填写高级调用功能的密钥
			);
			$weObj = new \Wechat($options);
			$weObj->valid();
			$result = $weObj->getOauthRedirect($weObj->getOauthAccessToken());
			$UserInfo = $weObj->getOauthUserinfo($result['access_token'],$result['openid']);//获取授权后的用户资料
			var_dump($UserInfo);exit;
			$type = $weObj->getRev()->getRevType();
			switch ($type) {
				case \Wechat::MSGTYPE_TEXT:
					$weObj->text("hello, I'm wechat")->reply();
					exit;
					break;
				case \Wechat::MSGTYPE_EVENT:
					//....
					break;
				case \Wechat::MSGTYPE_IMAGE:
					//...
					break;
				default:
					$weObj->text("help info")->reply();
			}
*/
		} else {
			//提示请使用微信登录
		}
		$this->display(":index");
	}

	/**
	 * 联盟
	 * @author tanhuaxin
     */
	public function member()
	{
		$this->display(":member");
	}

	/**
	 * 个人
	 * @author tanhuaxin
	 */
	public function personal()
	{
		$this->display(":personal");
	}

	/**
	 * 排行榜
	 * @author tanhuaxin
	 */
	public function rank()
	{
		$this->display(":rank");
	}

	/**
	 * 商城
	 * @author tanhuaxin
	 */
	public function shop()
	{
		$this->display(":shop");
	}
}


