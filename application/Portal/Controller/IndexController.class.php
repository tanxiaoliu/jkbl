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
    const TOKEN = 'tengke';
    const APPID = 'wx243493b23d1f6432';
    const APPSECRET = 'b039a3327c3f6e5ab187385f748e112b';
    const API_BASE_URL_PREFIX = 'https://api.weixin.qq.com';
    const URL = 'http://thiff.togogosz.net';

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 对接公众号
     * @return mixed
     */
    public function test()
    {
        //微信登录
        $options = array(
            'token' => self::TOKEN, //填写你设定的key
            'appid' => self::APPID, //填写高级调用功能的app id
            'appsecret' => self::APPSECRET //填写高级调用功能的密钥
        );
        $this->weObj = new Wechat($options);
        $this->weObj->valid();
    }

    /**
     * 检查登录
     * @return mixed
     */
    public function checkLogin()
    {
//        $userInfo->openid = 'admin';
//        return $userInfo;
        if (sp_is_weixin()) {
            $userInfo = json_decode($_COOKIE['userInfo']);
            $user = session('user');
            if (empty($userInfo) || empty($user) || !isset($userInfo->openid)) {
                //微信登录
                $options = array(
                    'token' => self::TOKEN, //填写你设定的key
                    'appid' => self::APPID, //填写高级调用功能的app id
                    'appsecret' => self::APPSECRET //填写高级调用功能的密钥
                );
                $this->weObj = new Wechat($options);
                redirect($this->weObj->getOauthRedirect(self::URL . U('callback'), '', 'snsapi_userinfo'));
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
        $url = self::API_BASE_URL_PREFIX . '/sns/oauth2/access_token?appid=' . self::APPID . '&secret=' . self::APPSECRET . '&code=' . $code . '&code=' . $code . '&grant_type=authorization_code';
        $result = json_decode(file_get_contents($url));
        $url2 = self::API_BASE_URL_PREFIX . '/sns/userinfo?access_token=' . $result->access_token . '&openid=' . $result->openid . '&lang=zh_CN';
        $result2 = file_get_contents($url2);
        $userInfo = json_decode($result2);
        if (!empty($userInfo) || isset($userInfo->openid)) {
            //保存用户信息入库
            $map['user_login'] = $userInfo->openid;
            $users = D('users')->where($map)->find();
            if ($users) {
                session('ADMIN_ID', 1);
                session('user', $users);
                $data['last_login_time'] = date("Y-m-d H:i:s", time());
                D('users')->save($data);
                $userInfo->nickname = $users['user_nicename'];
                setcookie('userInfo', json_encode($userInfo));
            } else {
                $data['user_login'] = $userInfo->openid;
                $data['user_pass'] = sp_password('123456');
                $data['user_nicename'] = $userInfo->nickname;
                $data['avatar'] = $userInfo->headimgurl;
                $data['sex'] = $userInfo->sex;
                $data['last_login_time'] = date("Y-m-d H:i:s", time());
                $data['create_time'] = date("Y-m-d H:i:s", time());
                $data['user_type'] = 2;
                $data['id'] = D('users')->add($data);
                session('ADMIN_ID', 1);
                session('user', $data);
                setcookie('userInfo', $result2);
            }
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
        $type = intval(I('type'));
        $usersModel = D('users');
        $user = array();
        if ($type == 1) {//腾币
            $data = M('Users')->field('user_login as openid,user_nicename as nick_name,score as num,avatar')->order('score desc,last_login_time desc')->select();
            foreach ($data as $key => $vl) {
                if ($userInfo->openid == $vl['openid']) {
                    $user['rank'] = $key + 1;
                    $user['nick_name'] = $vl['nick_name'];
                    $user['num'] = $vl['num'];
                    $user['avatar'] = $userInfo->headimgurl;
                }
            }
        } elseif ($type == 2) {//爱心
            $data = D('good_order')->join('cmf_users ON cmf_good_order.openid = cmf_users.user_login')
                ->field('cmf_users.user_login as openid,cmf_users.user_nicename as nick_name,cmf_users.avatar,sum(cmf_good_order.price) as num')
                ->where('cmf_good_order.type = 2')->order('num DESC')->select();
            foreach ($data as $key => $vl) {
                if ($userInfo->openid == $vl['openid']) {
                    $user['rank'] = $key + 1;
                    $user['nick_name'] = $vl['nick_name'];
                    $user['num'] = $vl['num'];
                    $user['avatar'] = $userInfo->headimgurl;
                }
            }
        } else {//毅力
            $data = D('sport_record')->field('openid,count(id) as num')->group('openid')->order('num DESC')->select();
            foreach ($data as $key => $vl) {
                $map['user_login'] = $vl['openid'];
                $users = $usersModel->where($map)->find();
                $data[$key]['avatar'] = $users['avatar'];
                $data[$key]['nick_name'] = $users['user_nicename'];
                if ($userInfo->openid == $vl['openid']) {
                    $user['rank'] = $key + 1;
                    $user['nick_name'] = $users['user_nicename'];
                    $user['num'] = $vl['num'];
                    $user['avatar'] = $userInfo->headimgurl;
                }
            }
        }
        $this->assign("data", $data);
        $this->assign("user", $user);
        $this->assign("type", $type);
        $this->assign("footer", "fuli");
        $this->assign("userInfo", $userInfo);
        $this->display(":index");
    }

    /**
     * 联盟
     * @author tanhuaxin
     */
    public function member()
    {
        $userInfo = $this->checkLogin();
        $map['user_login'] = $userInfo->openid;
        $users = M('Users')->where($map)->find();
        $map = array();
        $type = intval(I('type'));
        $memberCachKey = $users['groupid'] . '_member_' . date('Y-m-d:H', time()) . '_' . $type;
        S($memberCachKey, null);
        $data = unserialize(S($memberCachKey));
//        $data = array();
        $sum = 0;
        $status = empty($users['groupid']) ? 0 : 1;
        if ($status == 1 && (empty($data) || $type == 4 || $data['data'] == '[]')) {
            $typeName = date('Y-m-d', time()-3600*24);//默认时间
            if (!empty($_POST) && $type == 4) {//时间段
                $startTime = strtotime(I('startTime'));
                $endTime = strtotime(I('endTime'));
                $typeName = date('Y-m-d', $startTime) . ' ~ ' . date('Y-m-d', $endTime);
                $map['add_time'] = array('between', array($startTime, $endTime));
            }
            if ($type == 1) {//昨天
                $typeName = '昨天统计';
                $startYesterday = mktime(0, 0, 0, date('m'), date('d') - 1, date('Y'));
                $endYesterday = mktime(0, 0, 0, date('m'), date('d'), date('Y')) - 1;
                $map['add_time'] = array('between', array($startYesterday, $endYesterday));
            } elseif ($type == 2) {//上周
                $typeName = '上周统计';
                $beginLastweek = mktime(0, 0, 0, date('m'), date('d') - date('w') + 1 - 7, date('Y'));
                $endLastweek = mktime(23, 59, 59, date('m'), date('d') - date('w') + 7 - 7, date('Y'));
                $map['add_time'] = array('between', array($beginLastweek, $endLastweek));
            } elseif ($type == 3) {//上月
                $typeName = '上月统计';
                $beginThismonth = mktime(0, 0, 0, date('m'), 1, date('Y'));
                $endThismonth = mktime(23, 59, 59, date('m'), date('t'), date('Y'));
                $map['add_time'] = array('between', array($beginThismonth, $endThismonth));
            }
            $data = '[';
            if (!empty($map)) {
                $users = M('Users')->field('user_login,groupid,score,school')->where(array('groupid' => $users['groupid']))->select();
                $ids = '';
                foreach ($users as $value) {
                    $ids .= $value["user_login"] . ',';
                }
                $ids = rtrim($ids, ',');
                $map['openid'] = array('in', $ids);
                $record = D('sport_record')->where($map)->field('openid,sum(step_nums) as num')->group('openid')->order('num DESC')->select();
                foreach ($record as $value) {
                    $map['user_login'] = $value['openid'];
                    $users = M('Users')->where($map)->find();
                    $sum += $value['num'];
                    if(empty($users['school'])){
                        $name = $users['school'].$users['user_nicename'];
                    } else {
                        $name = $users['school'].'-'.$users['user_nicename'];
                    }
                    $data .= "{value:{$value['num']}, name:'{$name}'},";
                }
            } else {
                $users = M('Users')->field('user_nicename,groupid,score,school')->where(array('groupid' => $users['groupid']))->order('score DESC')->select();
                foreach ($users as $value) {
                    $sum += $value['score'];
                    if(empty($value['school'])){
                        $name = $value['school'].$value['user_nicename'];
                    } else {
                        $name = $value['school'].'-'.$value['user_nicename'];
                    }
                    $data .= "{value:{$value['score']}, name:'{$name}'},";
                }
            }
            $data = rtrim($data, ',') . ']';
            $list = array(
                'data' => $data,
                'sum' => $sum,
                'status' => $status
            );
            S($memberCachKey, serialize($list), 3600);
        } else {
            if ($status == 0) {
                $data = '[]';
            } else {
                $sum = $data['sum'];
                $data = $data['data'];
                $status = $data['status'];
            }
        }
        $this->assign("userInfo", $userInfo);
        $this->assign("status", $status);
        $this->assign("sum", $sum);
        $this->assign("data", $data);
        $this->assign("type", $typeName);
        $this->assign("footer", "zhishu");
        $this->display(":member");
    }

    /**
     * 社区
     * @author tanhuaxin
     */
    public function community()
    {
        $this->checkLogin();
        $map['istop'] = 1;
        $map['recommended'] = 1;
        $map['post_type'] = 1;
        $map['post_status'] = array('neq', 3);
        $posts = M('Posts')->field('id,post_title,post_date')->where($map)->order('istop desc,recommended desc,post_date desc')->limit(5)->select();
        $postscount = M('Posts')->count();
        $userscount = M('Users')->count();
        $map['istop'] = 0;
        $map['recommended'] = 0;
        $pengyouquan = M('Posts')->field('id,post_content,post_date,post_image,post_author,post_like,comment_count')->where($map)->order('id DESC')->limit(30)->select();
        foreach ($pengyouquan as $key => $vl) {
            $map['id'] = $vl['post_author'];
            $users = D('users')->where($map)->find();
            $pengyouquan[$key]['avatar'] = $users['avatar'];
            $pengyouquan[$key]['user_nicename'] = $users['user_nicename'];
            $pengyouquan[$key]['school'] = $users['school'];
            $pengyouquan[$key]['uid'] = $vl['post_author'];
        }
        $user = session('user');
        $this->assign("uid", $user['id']);
        $this->assign("postscount", $postscount);
        $this->assign("userscount", $userscount);
        $this->assign("posts", $posts);
        $this->assign("pengyouquan", $pengyouquan);
        $this->assign("footer", "shequ");
        $this->display(":community");
    }

    /**
     * 我的话题
     * @author tanhuaxin
     */
    public function myhuati()
    {
        $this->checkLogin();
        $user = session('user');
        $map['post_author'] = $user['id'];
        $map['post_author'] = 1;
        $map['post_type'] = 1;
        $map['istop'] = 0;
        $map['recommended'] = 0;
        $map['post_status'] = array('neq', 3);
        $pengyouquan = M('Posts')->field('id,post_content,post_date,post_image,post_author,post_like')->where($map)->order('id DESC')->limit(30)->select();
        foreach ($pengyouquan as $key => $vl) {
            $map['id'] = $vl['post_author'];
            $users = D('users')->where($map)->find();
            $pengyouquan[$key]['avatar'] = $users['avatar'];
            $pengyouquan[$key]['user_nicename'] = $users['user_nicename'];
            $pengyouquan[$key]['uid'] = $vl['post_author'];
        }
        $this->assign("uid", $user['id']);
        $this->assign("pengyouquan", $pengyouquan);
        $this->assign("footer", "shequ");
        $this->display(":myhuati");
    }

    /**
     * 发表说说
     * @author tanhuaxin
     */
    public function publishedpAbout()
    {
        $userInfo = $this->checkLogin();
        $data['post_image'] = I('post_image');
        $data['post_content'] = I('post_content');
        $map['user_login'] = $userInfo->openid;
        $data['post_author'] = M('Users')->where($map)->getField('id');
        $data['post_date'] = date("Y-m-d H:i:s", time());
        M('Posts')->add($data);
        redirect(U('community'));
    }

    /**
     * 上传图片
     * @author tanhuaxin
     */
    public function uploadImage()
    {
        if (IS_POST) {
            $savepath = 'default/' . date('Ymd') . '/';
            //上传处理类
            $config = array(
                'rootPath' => './' . C("UPLOADPATH"),
                'savePath' => $savepath,
                'maxSize' => 20971520,
                'saveName' => array('uniqid', ''),
                'exts' => array('jpg', 'gif', 'png', 'jpeg'),
                'autoSub' => false,
            );
            $upload = new \Think\Upload($config);
            $info = $upload->upload();
            //开始上传
            if ($info) {
                //上传成功
                $oriName = $_FILES['file']['name'];
                //写入附件数据库信息
                $first = array_shift($info);
                $url = C("TMPL_PARSE_STRING.__UPLOAD__") . $savepath . $first['savename'];
                $preview_url = $url;
                $filepath = $savepath . $first['savename'];
                $this->ajaxReturn(array('preview_url' => $preview_url, 'filepath' => $filepath, 'url' => $url, 'name' => $oriName, 'status' => 1, 'message' => 'success'));
            } else {
                $this->ajaxReturn(array('name' => '', 'status' => 0, 'message' => $upload->getError()));
            }
        }
    }

    /**
     * 个人
     * @author tanhuaxin
     */
    public function personal()
    {
        $userInfo = $this->checkLogin();
        $map['openid'] = $userInfo->openid;
        $type = I('type', 0, 'int');
        $dates = '[';
        $datas = '[';
        if ($type == 1) {//周
            for ($i = 0; $i < 7; $i++) {
                $k = 7 - $i;
                $timeStart = mktime(0, 0, 0, date('m'), date('d') - date('w') - 6 - (7 * ($k - 1)), date('Y'));
                $timeEnd = mktime(23, 59, 59, date('m'), date('d') - date('w') - (7 * ($k - 1)), date('Y'));
                $map['add_time'] = array('between', array($timeStart, $timeEnd));
                $date = date('W周', $timeStart);
                $data = D('sport_record')->where($map)->sum('step_nums');
                $data = $data ?: 0;
                $dates .= "'$date',";
                $datas .= "$data,";
            }
        } elseif ($type == 2) {//月
            for ($i = 0; $i < 7; $i++) {
                $k = 7 - $i;
                $timeStart = mktime(0, 0, 0, date('m') - ($k - 1), 1, date('Y'));
                $timeEnd = mktime(23, 59, 59, date('m') - ($k - 1), date('t'), date('Y'));
                $map['add_time'] = array('between', array($timeStart, $timeEnd));
                $date = date('m月', $timeStart);
                $data = D('sport_record')->where($map)->sum('step_nums');
                $data = $data ?: 0;
                $dates .= "'$date',";
                $datas .= "$data,";
            }
        } else {//天
            for ($i = 0; $i < 7; $i++) {
                $k = 7 - $i;
                $timeStart = mktime(0, 0, 0, date('m'), date('d') - $k, date('Y'));
                $timeEnd = mktime(0, 0, 0, date('m'), date('d') - ($k - 1), date('Y')) - 1;
                $map['add_time'] = array('between', array($timeStart, $timeEnd));
                $date = date('d日', $timeStart);
                $data = D('sport_record')->where($map)->sum('step_nums');
                $data = $data ?: 0;
                $dates .= "'$date',";
                $datas .= "$data,";
            }
        }
        $dates = rtrim($dates, ',') . ']';
        $datas = rtrim($datas, ',') . ']';

        //天
        $timeStart = mktime(0, 0, 0, date('m'), date('d') - 6, date('Y'));
        $timeEnd = mktime(0, 0, 0, date('m'), date('d'), date('Y')) - 1;
        $map['add_time'] = array('between', array($timeStart, $timeEnd));
        $nowNum = D('sport_record')->where($map)->sum('step_nums');

        //周
        $timeStart1 = mktime(0, 0, 0, date('m'), date('d') - date('w') - 42, date('Y'));
        $timeEnd1 = mktime(23, 59, 59, date('m'), date('d') - date('w') - 6, date('Y'));
        $map1['add_time'] = array('between', array($timeStart1, $timeEnd1));
        $nowNum1 = D('sport_record')->where($map1)->sum('step_nums');

        //月
        $timeStart2 = mktime(0, 0, 0, date('m') - 6, 1, date('Y'));
        $timeEnd2 = mktime(23, 59, 59, date('m'), date('t'), date('Y'));
        $map2['add_time'] = array('between', array($timeStart2, $timeEnd2));
        $nowNum2 = D('sport_record')->where($map2)->sum('step_nums');

        $num = D('sport_record')->where(array('openid' => $userInfo->openid))->sum('step_nums');
        $umap['user_login'] = $userInfo->openid;
        $status = D('users')->where($umap)->getField('status');
        $nowNum = $nowNum ? $nowNum / 7 : 0;
        $nowNum1 = $nowNum1 ? $nowNum1 / 7 : 0;
        $nowNum2 = $nowNum2 ? $nowNum2 / 7 : 0;
        $this->assign("userInfo", $userInfo);
        $this->assign("num", $num);
        $this->assign("nowNum", sprintf("%.2f", $nowNum));
        $this->assign("nowKa", $this->getKa($nowNum));
        $this->assign("nowNum1", sprintf("%.2f", $nowNum1));
        $this->assign("nowKa1", $this->getKa($nowNum1));
        $this->assign("nowNum2", sprintf("%.2f", $nowNum2));
        $this->assign("nowKa2", $this->getKa($nowNum2));
        $this->assign("dates", $dates);
        $this->assign("datas", $datas);
        $this->assign("status", $status);
        $this->assign("footer", "zhishu");
        $this->display(":personal");
    }

    /**
     * 步数换大卡
     * @author tanhuaxin
     * @param $num
     * @return int|string
     */
    public function getKa($num)
    {
        $result = $num == 0 ? 0 : sprintf("%.2f", $num * 0.04);
        return $result;
    }

    /**
     * 修改名字
     */
    public function editName()
    {
        $userInfo = $this->checkLogin();
        if (IS_POST) {
            $school = I('school');
            $user_nicename = I('user_nicename');
            $map['user_login'] = $userInfo->openid;
            $usersModel = D('users');
            $status = $usersModel->where($map)->getField('status');
            if ($status == 0) {
                $data['user_nicename'] = $user_nicename;
                $data['school'] = $school;
                $data['status'] = 1;
                $usersModel->where($map)->save($data);
                $userInfo->nickname = $user_nicename;
                $userInfo->school = $school;
                $user = session('user');
                $user['user_nicename'] = $userInfo->nickname;
                $user['school'] = $userInfo->school;
                session('user', $user);
                setcookie('userInfo', json_encode($userInfo));
            }
        }
        redirect(U('personal'));
    }

    /**
     * 个人、联盟排名
     * @author tanhuaxin
     */
    public function rank()
    {
        $user = array();
        $userInfo = $this->checkLogin();
        $type = I('type', '', 'intval');
        $grouptype = I('grouptype', '', 'intval');
        $rankDataCachKey = 'rankData_' . date('Y-m-d:H', time()) . '_' . $type . '_' . $grouptype;
        $rankUserCachKey = $userInfo->openid . 'rankUser_' . date('Y-m-d:H', time()) . '_' . $type . '_' . $grouptype;
        $data = unserialize(S($rankDataCachKey));
        if (!empty($data)) {
            $user = unserialize(S($rankUserCachKey));
            $data = array();
        }
        if (empty($data) || $type == 4) {
            $map = '';
            if (!empty($_POST) && $type == 4) {//时间段
                $startTime = strtotime(I('startTime'));
                $endTime = strtotime(I('endTime'));
                $map['add_time'] = array('between', array($startTime, $endTime));
            }
            if ($type == 1) {//昨天
                $startYesterday = mktime(0, 0, 0, date('m'), date('d') - 1, date('Y'));
                $endYesterday = mktime(0, 0, 0, date('m'), date('d'), date('Y')) - 1;
                $map['add_time'] = array('between', array($startYesterday, $endYesterday));
            } elseif ($type == 2) {//上周
                $beginLastweek = mktime(0, 0, 0, date('m'), date('d') - date('w') + 1 - 7, date('Y'));
                $endLastweek = mktime(23, 59, 59, date('m'), date('d') - date('w') + 7 - 7, date('Y'));
                $map['add_time'] = array('between', array($beginLastweek, $endLastweek));
            } elseif ($type == 3) {//上月
                $beginThismonth = mktime(0, 0, 0, date('m'), 1, date('Y'));
                $endThismonth = mktime(23, 59, 59, date('m'), date('t'), date('Y'));
                $map['add_time'] = array('between', array($beginThismonth, $endThismonth));
            }
            $data = D('sport_record')->where($map)->field('openid,sum(step_nums) as num')->group('openid')->order('num DESC')->select();
            $usersModel = D('users');
            if ($grouptype == 0) {
                foreach ($data as $key => $vl) {
                    $map['user_login'] = $vl['openid'];
                    $users = $usersModel->where($map)->find();
                    $data[$key]['avatar'] = $users['avatar'];
                    $data[$key]['nick_name'] = $users['user_nicename'];
                    $data[$key]['school'] = $users['school'];
                }
            } elseif ($grouptype == 1) {
                $groups = array();
                foreach ($data as $key => $vl) {
                    $map['user_login'] = $vl['openid'];
                    $users = $usersModel->where($map)->find();
                    if ($users['groupid'] > 0) {
                        if (!isset($groups[$users['groupid']])) {
                            $groups[$users['groupid']]['num'] = 0;
                            $group = M('Group')->find($users['groupid']);
                            $groups[$users['groupid']]['id'] = $group['id'];
                            $count = $usersModel->where(array('groupid'=>$users['groupid']))->count();
                            $groups[$users['groupid']]['nick_name'] = $group['name'].'['.$count.']';
                            $groups[$users['groupid']]['avatar'] = '/data/upload/' . $group['logo'];
                            $groups[$users['groupid']]['avgNum'] = $groups[$users['groupid']]['sortByNum']/$count;
                        }
                        $groups[$users['groupid']]['num'] += $vl['num'];
                    }
                }
                unset($groups[0]);
                usort($groups, 'avgNum');
                $data = $groups;
            }
            $user = $this->_getUserRank($grouptype, $data, $userInfo);
            S($rankDataCachKey, serialize($data), 3600);
        } else {
            if (empty($user)) {
                $user = $this->_getUserRank($grouptype, $data, $userInfo);
                S($rankUserCachKey, serialize($user), 60);
            }
        }

        if (!empty($_POST) && $type == 4) {//时间段
            $typeName = date('Y-m-d', $startTime) . ' ~ ' . date('Y-m-d', $endTime);
        }
        if ($type == 1) {//昨天
            $typeName = '昨天排行';
        } elseif ($type == 2) {//上周
            $typeName = '上周排行';
        } elseif ($type == 3) {//上月
            $typeName = '上月排行';
        }
        if(empty($data)){
            $typeName = $typeName.'无记录';
        }
        $this->assign("grouptype", $grouptype);
        $this->assign("data", $data);
        $this->assign("user", $user);
        $this->assign("type", $typeName);
        $this->assign("footer", "zhishu");
        $this->display(":rank");
    }

    protected function _getUserRank($grouptype, $data, $userInfo)
    {
        $user = array();
        if ($grouptype == 0) {
            foreach ($data as $key => $vl) {
                $map['user_login'] = $userInfo->openid;
                $users = M('Users')->where($map)->find();
                if ($userInfo->openid == $vl['openid']) {
                    $user['rank'] = $key + 1;
                    $user['nick_name'] = $users['user_nicename'];
                    $user['num'] = $vl['num'];
                    $user['avatar'] = $userInfo->headimgurl;
                }
            }
        } elseif ($grouptype == 1) {
            foreach ($data as $key => $vl) {
                $map['user_login'] = $userInfo->openid;
                $users = M('Users')->where($map)->find();
                if ($vl['id'] == $users['groupid']) {
                    $user['rank'] = $key + 1;
                    $user['nick_name'] = $vl['nick_name'];
                    $user['num'] = $vl['num'];
                    $user['avatar'] = $vl['avatar'];
                }
            }
        }
        return $user;
    }


    /**
     * 商城
     * @author tanhuaxin
     */
    public function shop()
    {
        $userInfo = $this->checkLogin();
        $data['openid'] = $userInfo->openid;
        $map['user_login'] = $data['openid'];
        $score = current(M('Users')->where($map)->getField('user_login,score,coin', 1));
        $selfgood = M("Good")->where(array('type' => 1))->order('add_time desc')->select();
        $othergood = M("Good")->where(array('type' => 2))->order('add_time desc')->select();
        $this->assign("nowscore", $score['score'] - $score['coin']);
        $this->assign("allscore", $score['score']);
        $this->assign("selfgood", $selfgood);
        $this->assign("selfgood", $selfgood);
        $this->assign("othergood", $othergood);
        $this->assign("userInfo", $userInfo);
        $this->assign("footer", "fuli");
        $this->display(":shop");
    }

    /**
     * 下单
     * @author tanhuaxin
     */
    public function buy()
    {
        $data = array();
        if (!empty($_POST)) {
            $userInfo = $this->checkLogin();
            $data['openid'] = $userInfo->openid;
            $map['user_login'] = $data['openid'];
            $score = current(M('Users')->where($map)->getField('user_login,score,coin', 1));
            if ($score['score'] <= $score['coin'] || $score['score'] <= 0) {
                $this->success("腾币不足", U('Index/shop'), true);
            }
            $score = $score['score'] - $score['coin'];//余额
            $data['goodid'] = I('goodid', 0, 'intval');
            $data['username'] = I('username', '公益', 'htmlspecialchars');
            $data['address'] = I('address', '公益', 'htmlspecialchars');
            $data['phone'] = I('phone', '公益', 'string');
            $data['nums'] = 1;
            $data['add_time'] = time();
            $good = M('Good')->find($data['goodid']);
            if ($good) {
                $data['name'] = $good['name'];
                $data['price'] = $good['price'];
                $data['type'] = $good['type'];
            } else {
                $this->error("下单失败,该商品不存在", true);
            }
            if ($score < $data['price']) {
                $this->success("腾币不足", U('Index/shop'), true);
            }
            if (M('GoodOrder')->create($data) !== false) {
                $id = M('GoodOrder')->add();
                if ($id !== false) {
                    //更新用户余额
                    $map['user_login'] = $data['openid'];
                    $users = M('users')->where($map)->find();
                    if ($users) {
                        $users['coin'] += $good['price'];
                        if (M('users')->save($users)) {
                            $data1 = array(
                                'openid' => $map['user_login'],
                                'type' => 2,
                                'coin' => $good['price'],
                                'add_time' => time(),
                                'type_id' => $id,
                            );
                            if (M('CoinRecord')->add($data1)) {
                                if ($good['type'] == 2) {
                                    $this->success("THIFF将代您送出爱心~", U('Index/shop'), true);
                                } else {
                                    $this->success("下单成功", U('Index/shop'), true);
                                }
                            } else {
                                $users['coin'] -= $good['price'];
                                M('users')->save($users);
                                M('GoodOrder')->where(array('id' => $id))->delete();
                                $this->error("下单失败", true);
                            }
                        } else {
                            M('GoodOrder')->where(array('id' => $id))->delete();
                            $this->error("下单失败", true);
                        }
                    } else {
                        M('GoodOrder')->where(array('id' => $id))->delete();
                        $this->error("下单失败", true);
                    }
                } else {
                    $this->error("下单失败", true);
                }
            } else {
                $this->error("下单失败", true);
            }

        } else {
            //提示请使用微信登录
            die ('这是微信请求的接口地址，直接在浏览器里无效');
        }
    }

    /**
     * [orderList 订单记录]
     * @return [type] [description]
     */
    public function orderList()
    {
        $userInfo = $this->checkLogin();
        $map['openid'] = $userInfo->openid;
        $orders = M('GoodOrder')->where($map)->order('add_time desc')->select();
        foreach ($orders as $key => &$value) {
            $good = M('Good')->find($value['goodid']);
            if ($good) {
                $value['url'] = $good['url'];
            }
        }
        $this->assign("orders", $orders);
        $this->display(":orderlist");
    }

    /**
     * [orderList 腾币记录]
     * @return [type] [description]
     */
    public function coinList()
    {
        $userInfo = $this->checkLogin();
        $map['openid'] = $userInfo->openid;
        $Records = M('CoinRecord')->where($map)->order('add_time desc')->select();
        foreach ($Records as $key => &$value) {
            if ($value['type'] == 2) {
                $good = M('GoodOrder')->find($value['type_id']);
                if ($good) {
                    $value['name'] = $good['name'];
                    $value['status'] = $good['status'];
                    $value['goodtype'] = $good['type'];
                }
            }
        }
        $this->assign("Records", $Records);
        $this->display(":coinlist");
    }

    public function error()
    {
        $this->display(":error");
    }

    /**
     * 上传步数
     * @author tanhuaxin
     */
    public function uploadSport()
    {
        $num = I('num', 0, 'int');
        $userInfo = $this->checkLogin();

        if (IS_POST) {
            $savepath = 'default/' . date('Ymd') . '/';
            //上传处理类
            $config = array(
                'rootPath' => './' . C("UPLOADPATH"),
                'savePath' => $savepath,
                'maxSize' => 20971520,
                'saveName' => array('uniqid', ''),
                'exts' => array('jpg', 'gif', 'png', 'jpeg'),
                'autoSub' => false,
            );
            $upload = new \Think\Upload($config);
            $info = $upload->upload();
            //开始上传
            if ($info) {
                //写入附件数据库信息
                $first = array_shift($info);
                $url = C("TMPL_PARSE_STRING.__UPLOAD__") . $savepath . $first['savename'];
                $data['num'] = $num;
                $data['image'] = $url;
                $data['time'] = time();
                $data['openid'] = $userInfo->openid;
                $result = M('sport')->add($data);
                if ($result) {
                    header("Content-type:text/html;charset=utf-8");
                    echo "<script> alert('上传成功'); </script>";
                    echo "<meta http-equiv='Refresh' content='0;URL=" . U('personal') . "'>";
                } else {
                    $this->error('上传数据出错', U('personal'));
                }
            } else {
                $this->error('上传文件出错', U('personal'));
            }
        }
    }

    /**
     * 个人记录
     * @author tanhuaxin
     */
    public function personallist()
    {
        $userInfo = $this->checkLogin();
        $date = I('date', 0, 'int');
        $type = I('type', 0, 'int');
        $map['openid'] = $userInfo->openid;
        $record = array();
        if($date == 1){//周
            for ($i = 1; $i <= 52; $i++) {
                $timeStart = mktime(0, 0, 0, date('m'), date('d') - date('w') - 6 - (7 * ($i - 1)), date('Y'));
                $timeEnd = mktime(23, 59, 59, date('m'), date('d') - date('w') - (7 * ($i - 1)), date('Y'));
                if($timeStart > '1483056000') {
                    $map['add_time'] = array('between', array($timeStart, $timeEnd));
                    $data['add_time'] = date('Y年W周', $timeStart);
                    $step_nums = D('sport_record')->where($map)->sum('step_nums');
                    if ($type == 1) {
                        $data['step_ka'] = $step_nums ? $this->getKa($step_nums) : 0;
                    } else {
                        $data['step_nums'] = $step_nums ?: 0;
                    }
                    $record[] = $data;
                }
            }
        } elseif($date == 2){
            for ($i = 1; $i <= 25; $i++) {
                $timeStart = mktime(0, 0, 0, date('m') - ($i - 1), 1, date('Y'));
                $timeEnd = mktime(23, 59, 59, date('m') - ($i - 1), date('t'), date('Y'));
                if($timeStart > '1483056000') {
                    $map['add_time'] = array('between', array($timeStart, $timeEnd));
                    $data['add_time'] = date('Y年m月', $timeStart);
                    $step_nums = D('sport_record')->where($map)->sum('step_nums');
                    if ($type == 1) {
                        $data['step_ka'] = $step_nums ? $this->getKa($step_nums) : 0;
                    } else {
                        $data['step_nums'] = $step_nums ?: 0;
                    }
                    $record[] = $data;
                }
            }
        } else {
            $map['openid'] = $userInfo->openid;
            $record = D('sport_record')->where($map)->order('add_time DESC')->select();
            if($type == 1){//卡
                foreach ($record as $key=>$value){
                    $record[$key]['step_ka'] = $this->getKa($value['step_nums']);
                }
            }
        }
        $this->assign("userInfo", $userInfo);
        $this->assign("record", $record);
        $this->assign("type", $type);
        $this->assign("date", $date);
        $this->display(":personallist");
    }

    /**
     * 小组列表
     * @author tanhuaxin
     */
    public function rankdetail()
    {
        $userInfo = $this->checkLogin();
        $map['user_login'] = $userInfo->openid;
        $groupid = I('groupid', 0 , 'int');
        $group = M('Group')->find($groupid);
        $users = M('Users')->field('user_nicename,avatar,score,school')->where(array('groupid' => $groupid))->order('score DESC')->select();
        $this->assign("userInfo", $userInfo);
        $this->assign("data", $users);
        $this->assign("group", $group);
        $this->assign("footer", "zhishu");
        $this->display(":rankdetail");
    }
}

