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
use Wechat;

/**
 * 首页
 */
class IndexController extends HomebaseController {

	protected $weObj;
	protected $url;
	protected $userInfo;
	public function __construct()
	{
		parent::__construct ();
	}

	//首页 小夏是老猫除外最帅的男人了
	public function index()
	{
		if (sp_is_weixin()) {
			$this->userInfo = S('userInfo');
			if($this->userInfo == false){
				//微信登录
				$options = array(
					'token' => 'test', //填写你设定的key
					'appid' => 'wx33d9402ea60d3681', //填写高级调用功能的app id
					'appsecret' => 'eb34a1662269b9027b7ed8635b04c6ed' //填写高级调用功能的密钥
				);
				$this->weObj = new Wechat($options);
				$this->url = $this->weObj->getOauthRedirect('http://laoit.top/index.php?g=portal&m=index&a=test', '', 'snsapi_base');
				redirect($this->url);
			} else {
				$this->assign("userInfo", $this->userInfo);
				$this->display(":index");
			}
		} else {
			//提示请使用微信登录
			die ( '这是微信请求的接口地址，直接在浏览器里无效' );
		}
	}

	function test() {
        $code = $_GET['code'];
		$url = 'https://api.weixin.qq.com/sns/oauth2/access_token?appid=wx33d9402ea60d3681&secret=eb34a1662269b9027b7ed8635b04c6ed&code='.$code.'&grant_type=authorization_code';
		$result =  json_decode(file_get_contents($url));
		$url2 = 'https://api.weixin.qq.com/sns/userinfo?access_token='.$result->access_token.'&openid='.$result->openid.'&lang=zh_CN';
		$this->userInfo =  json_decode(file_get_contents($url2));
		S('userInfo', $this->userInfo);
		redirect('http://laoit.top');
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


