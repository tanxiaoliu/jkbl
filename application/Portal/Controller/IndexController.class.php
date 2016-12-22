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
class IndexController extends HomebaseController
{

	protected $weObj;
	protected $theme = 'jkbl';
	const TOKEN = 'test';
	const APPID = 'wx33d9402ea60d3681';
	const APPSECRET = 'eb34a1662269b9027b7ed8635b04c6ed';
	const API_BASE_URL_PREFIX = 'https://api.weixin.qq.com';
	const URL = 'http://laoit.top';

	public function __construct()
	{
		parent::__construct();
	}

	/**
	 * 检测用户是否登录
	 * @author tanhuaxin
	 */
	public function index()
	{
		if (sp_is_weixin()) {
			$userInfo = json_decode($_COOKIE['userInfo']);
			if (empty($userInfo) || !isset($userInfo->openid)) {
				//微信登录
				$options = array(
					'token' => 'test', //填写你设定的key
					'appid' => 'wx33d9402ea60d3681', //填写高级调用功能的app id
					'appsecret' => 'eb34a1662269b9027b7ed8635b04c6ed' //填写高级调用功能的密钥
				);
				$this->weObj = new Wechat($options);
				redirect($this->weObj->getOauthRedirect(self::URL . U('callback')));
			} else {
				redirect(U('home'));
			}
		} else {
			//提示请使用微信登录
			die ('这是微信请求的接口地址，直接在浏览器里无效');
		}
	}

	/**
	 * code回调页面
	 * @author tanhuaxin
	 */
	function callback()
	{
		$code = $_GET['code'];
		$url = self::API_BASE_URL_PREFIX . '/sns/oauth2/access_token?appid=' . self::APPID . '&secret=' . self::APPSECRET . '&code=' . $code . '&grant_type=authorization_code';
		$result = json_decode(file_get_contents($url));
		$url2 = self::API_BASE_URL_PREFIX . '/sns/userinfo?access_token=' . $result->access_token . '&openid=' . $result->openid . '&lang=zh_CN';
		$result2 = file_get_contents($url2);
		$userInfo = json_decode($result2);
		if (!empty($userInfo) || isset($userInfo->openid)) {
			//保存用户信息入库
			$map['user_login'] = $userInfo->openid;
			$users = D('users')->where($map)->find();
			if ($users) {
				$data['last_login_time'] = date("Y-m-d H:i:s", time());
				D('users')->save($data);
			} else {
				$data['user_login'] = $userInfo->openid;
				$data['user_pass'] = sp_password('123456');
				$data['user_nicename'] = $userInfo->nickname;
				$data['avatar'] = $userInfo->headimgurl;
				$data['sex'] = $userInfo->sex;
				$data['last_login_time'] = date("Y-m-d H:i:s", time());
				$data['create_time'] = date("Y-m-d H:i:s", time());
				$data['user_type'] = 2;
				D('users')->add($data);
			}
			setcookie('userInfo', $result2);
			redirect(U('home'));
		} else {
			die ('获取用户信息失败，请联系管理员');
		}
	}

	//首页 小强是爸爸除外最帅的男人了
	public function home()
	{
		$userInfo = $_COOKIE['userInfo'];
		$this->assign("userInfo", json_decode($userInfo));
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
		$userInfo = $_COOKIE['userInfo'];
		$this->assign("userInfo", json_decode($userInfo));
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
		$selfgood = M("Good")->where(array('type'=>1))->order('add_time desc')->select();
		$othergood = M("Good")->where(array('type'=>2))->order('add_time desc')->select();
		$this->assign("selfgood",$selfgood);
		$this->assign("othergood",$othergood);
		$this->display(":shop");
	}

	/**
	 * 商城
	 * @author tanhuaxin
	 */
	public function buy()
	{
		print_r(I('post.'));exit();
	}



