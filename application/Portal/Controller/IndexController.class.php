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
     * 检查登录
     * @return mixed
     */
    public function checkLogin()
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
                return $userInfo;
            }
        } else {
            //提示请使用微信登录
	        redirect(U('error'));
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
            redirect(U('index'));
        } else {
            die ('获取用户信息失败，请联系管理员');
        }
    }

    /**
     * 达人堂 小强是(爸爸&&最帅的男人)了
     * @author tanhuaxin
     */
    public function index()
    {

        $userInfo = $this->checkLogin();
        $type = I('type', 0, 'int');
        if($type == 0) {
            $data = D('sport_record')->field('openid,nick_name,count(step_nums) as num')->group('openid')->order('num DESC')->select();
            $usersModel = D('users');
            foreach ($data as $key => $vl) {
                $map['user_login'] = $vl['openid'];
                $data[$key]['avatar'] = $usersModel->where($map)->getField('avatar');
                if ($userInfo->openid == $vl['openid']) {
                    $user['rank'] = $key + 1;
                    $user['nick_name'] = $vl['nick_name'];
                    $user['num'] = $vl['num'];
                    $user['avatar'] = $userInfo->headimgurl;
                }
            }
        }
        $this->assign("data", $data);
        $this->assign("user", $user);
        $this->display(":index");
    }

    /**
     * 联盟
     * @author tanhuaxin
     */
    public function member()
    {
        $this->assign("userInfo", $this->checkLogin());
        $this->display(":member");
    }

    /**
     * 个人
     * @author tanhuaxin
     */
    public function personal()
    {
        $userInfo = $this->checkLogin();
        $map['openid'] = $userInfo->openid;
        $num = D('sport_record')->where($map)->sum('step_nums');
        $this->assign("userInfo", $userInfo);
        $this->assign("num", $num);
        $this->display(":personal");
    }

    /**
     * 个人、联盟排名
     * @author tanhuaxin
     */
    public function rank()
    {
        $userInfo = $this->checkLogin();
        $type = I('type', 0, 'int');
        if($type == 0) {
            $data = D('sport_record')->field('openid,nick_name,sum(step_nums) as num')->group('openid')->order('num DESC')->select();
            $usersModel = D('users');
            foreach ($data as $key => $vl) {
                $map['user_login'] = $vl['openid'];
                $data[$key]['avatar'] = $usersModel->where($map)->getField('avatar');
                if ($userInfo->openid == $vl['openid']) {
                    $user['rank'] = $key + 1;
                    $user['nick_name'] = $vl['nick_name'];
                    $user['num'] = $vl['num'];
                    $user['avatar'] = $userInfo->headimgurl;
                }
            }
        }
        $this->assign("data", $data);
        $this->assign("user", $user);
        $this->display(":rank");
    }


    /**
     * 商城
     * @author tanhuaxin
     */
    public function shop()
    {
        $userInfo = $this->checkLogin();
        $data['openid'] = $userInfo->openid;
        // $data['openid'] = 'admin';
    	$map['user_login'] = $data['openid'];
        $score = current(M('Users')->where($map)->getField('user_login,score,coin',1));
        print_r($score);exit();
        $selfgood = M("Good")->where(array('type' => 1))->order('add_time desc')->select();
        $othergood = M("Good")->where(array('type' => 2))->order('add_time desc')->select();
        $this->assign("nowscore",$score['score']-$score['coin']);
        $this->assign("allscore",$score['score']);
        $this->assign("selfgood", $selfgood);
        $this->assign("selfgood", $selfgood);
        $this->assign("othergood", $othergood);
        $this->assign("userInfo", $userInfo);
        $this->display(":shop");
    }

    /**
     * 下单
     * @author tanhuaxin
     */
    public function buy()
    {
        $data =array();
        if(!empty($_POST)){
            $userInfo = $this->checkLogin();
	        $data['openid'] = $userInfo->openid;
	        // $data['openid'] = 'admin';
			$map['user_login'] = $data['openid'];
            $score = current(M('Users')->where($map)->getField('user_login,score,coin',1));
            if ($score['score']<=$score['coin']||$score['score']<=0) {
				$this->success("腾币不足", U('Index/shop'),true);
            }
            $score = $score['score']-$score['coin'];//余额
	        $data['goodid'] = I('goodid', 0, 'intval');
	        $data['username'] = I('username', '', 'htmlspecialchars');
	        $data['address'] = I('address', 0, 'htmlspecialchars');
	        $data['phone'] = I('phone', 0, 'string');
			$data['nums'] = 1;
			$data['add_time'] = time();
	        $good = M('Good')->find($data['goodid']);
	        if ($good) {
	        	$data['name'] = $good['name'];
	        	$data['price'] = $good['price'];
	        	$data['type'] = $good['type'];
	        }else{
			    $this->error("下单失败,该商品不存在",true);
	        }
	        if ($score<$data['price']) {
				$this->success("腾币不足", U('Index/shop'),true);
            }
			if (M('GoodOrder')->create($data)!==false) {
				$id = M('GoodOrder')->add();
				if ($id!==false) {
					//更新用户余额
		            $map['user_login'] = $data['openid'];
		            $users = M('users')->where($map)->find();
		            if ($users) {
		                $users['coin'] += $good['price'];
		                if(M('users')->save($users))$this->success("下单成功", U('Index/shop'),true);
		                else {
		                	M('GoodOrder')->where(array('id'=>$id))->delete();
		                	$this->error("下单失败",true);
		                }
		            }else {
		                	M('GoodOrder')->where(array('id'=>$id))->delete();
		                	$this->error("下单失败",true);
					}					
				} else {
					$this->error("下单失败",true);
				}
			} else {
				$this->error("下单失败",true);
				// $this->error(M('GoodOrder')->getError());
			}
		}else {
            //提示请使用微信登录
            die ('这是微信请求的接口地址，直接在浏览器里无效');
        }
    }

    public function error(){
    	$this->display(":error");
    }
}

