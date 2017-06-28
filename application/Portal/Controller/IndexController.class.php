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
        $this->cleanTengbi();
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

    public function invite()
    {
        if (!empty($_POST)) {
            $code = $_POST['code'];
            $user = session('user');
            $where = array(
                'code' => $code
            );
            $invite = M('InviteCode')->where($where)->find();
            if (!empty($invite)) {
                if ($invite['status'] == 0) {
                    $this->error("邀请码已过期", U('Index/invite'), true);
                }
                $invite['status'] = 0;
                $invite['userid'] = $user['id'];
                $invite['update_time'] = time();
                if (M('InviteCode')->create($invite) !== false) {
                    if (M('InviteCode')->save() !== false) {
                        $this->success("欢迎来到健康部落", U('Index/index'), true);
                    }
                } else {
                    $this->error("网络异常，请重新输入", U('Index/invite'), true);
                }
            } else {
                $this->error("邀请码错误", U('Index/invite'), true);
            }
        }
        $this->display(":invite");
    }

    public function checkInvite()
    {
//        return true;
        $this->checkLogin();
        $user = session('user');
        $where = array(
            'userid' => $user['id']
        );
        $invite = M('InviteCode')->where($where)->find();
        if (empty($invite)) {
            redirect(U('invite'));
        }
    }
    //php  cli.php Portal/Index/clicli
    //php  /var/www/html/tg/jkbl/cli.php  Portal/Index/clicli
    public function clicli(){

        echo 1;
        exit();

    }
    private function cleanTengbi()
    {
        $m = date('m');
        // $time = intval(strtotime(date('Y-05-01 00:00:00',time())))-1;
        $time = intval(strtotime(date('Y-'.$m.'-01 00:00:00',time())))-1;
        // $time1 = date('Y-05-02 00:00:11',time());
        $time1 = date('Y-'.$m.'-02 00:00:11',time());
        $timeStra = intval(strtotime(date('Y-'.$m.'-02 00:00:11',time())));
        $insertKey['key'] = $time1;
        $insertKey['value'] = 1;
        $KeyValue = M('KeyValue')->where($insertKey)->find();
        if (intval(time())>$timeStra&&empty($KeyValue)) {
        // if (empty($KeyValue)) {
            $data = M('Users')->field('id,user_login,score,limit_score')->select();
            foreach ($data as $key => &$value) {
                $sum = M('CoinRecord')->where(array('openid'=>$value['user_login'],'type'=>1))->sum('coin');
                $thisMon = M('CoinRecord')->where(array('openid'=>$value['user_login'],'type'=>1,'add_time'=>array('gt',$time)))->sum('coin');
                $use = M('CoinRecord')->where(array('openid'=>$value['user_login'],'type'=>2))->sum('coin');
                $limit = $sum -$use;
                $value['score'] = $sum;
                if ($limit<$thisMon) {
                    $value['limit_score'] = $limit;
                }else{
                    $value['limit_score'] = ceil(($limit-$thisMon)/2+$thisMon);
                }
                /*echo 'sum:'.$sum;
                echo 'thisMon:'.$thisMon;
                echo 'use:'.$use;
                echo 'score:'.$value['limit_score'];
                print_r($value);
                exit();*/
                // M('Users')->where(array('id'=>$value['id']))->setField('limit_score',$value['limit_score']);
                $id = $value['id'];
                unset($value['id']);
                $users = D('users')->find($id);
                $users['score'] = $value['score'];
                $users['limit_score'] = $value['limit_score'];
                D('users')->save($users);
                // M('Users')->where(array('id'=>$id))->save($value);
            }
            $insertKey['add_time'] = time();
            $insertKey['status'] = 1;
            M('KeyValue')->add($insertKey);
        }
    }
    public function cleanInvite()
    {
        $map['id'] = array('neq', '1');

        $data = M('Users')->where($map)->field('id,user_login as openid,user_nicename as nick_name,groupid,score as num,school,avatar')->order('score desc, convert(user_nicename using gbk) ASC')->select();
        foreach ($data as $key => $value) {
                $where = array(
                    'userid' => $value['id']
                );
                $invite = M('InviteCode')->where($where)->find();
                if (empty($invite)&&!empty($value)) {
                    echo D('Users')->where(array('id'=>$value['id']))->delete();
                }
        }
        exit();
    }

    /**
     * 检查登录
     * @return mixed
     */
    public function checkLogin()
    {
//        $userInfo->openid = 'oexX2s29EG6CKqQUTgHn4wpsZAKE';
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
            redirect(U('weixinError'));
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
                $users['last_login_time'] = date("Y-m-d H:i:s", time());
                D('users')->save($users);
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
    public function daren()
    {
        $userInfo = $this->checkLogin();
        $this->checkInvite();
        $type = intval(I('type'));
        $usersModel = D('users');
        $user = array();

        if ($type == 1) {//腾币
            $map['id'] = array('neq', '1');
            $map['user_status'] = array('neq','0');
            $data = M('Users')->where($map)->field('user_status,user_login as openid,user_nicename as nick_name,groupid,score as num,school,avatar')->order('score desc, convert(user_nicename using gbk) ASC')->select();
        } elseif ($type == 2) {//爱心
            if(M('good_order')->select()) {
                $data = M('good_order')->join('cmf_users ON cmf_good_order.openid = cmf_users.user_login')
                    ->field('cmf_users.user_login as openid,cmf_users.groupid as groupid,cmf_users.school as school,cmf_users.user_nicename as nick_name,cmf_users.user_status,cmf_users.avatar,sum(cmf_good_order.price) as num')
                    ->where('cmf_good_order.type = 2')->order('num DESC, convert(cmf_users.user_nicename using gbk) ASC')->select();
            }
        } else {//毅力
            $map['step_nums'] = array('gt', 9999);
            $startYesterday = mktime(0, 0, 0, date('m'), date('d') - 1, date('Y'));
            $endYesterday = $startYesterday + 3600 * 24;
            $map['add_time'] = array('between', array($startYesterday, $endYesterday));
            $data = M('sport_record')->where($map)->field('openid')->group('openid')->select();
            foreach ($data as $key => $vl) {
                $data[$key]['num'] = $this->getDayCount($vl['openid']);
                $mapUser['user_login'] = $vl['openid'];
                $users = $usersModel->where($mapUser)->find();
                //隐藏分组
                if ($users['groupid']==20||(isset($users['user_status'])&&$users['user_status']==0)) {
                    unset($data[$key]);
                    continue;
                }
                $data[$key]['user_status'] = $users['user_status'];
                $data[$key]['avatar'] = $users['avatar'];
                $data[$key]['nick_name'] = $users['user_nicename'];
                $data[$key]['school'] = $users['school'];
            }
            $data = $this->multi_array_sort($data, 'num');
        }
        $flag_rank = 0;
        foreach ($data as $key => &$value) {
            //隐藏分组
            if ($value['groupid']==20||$value['user_status']==0) {
                unset($data[$key]);
                continue;
            }
            if ($userInfo->openid == $value['openid']&&($type == 2||$type == 1)) {
                $user['rank'] = $key + 1;
                $user['nick_name'] = $value['nick_name'];
                $user['num'] = $value['num'];
                $user['avatar'] = $value->avatar;
            }
            $value['rank_image'] = 0;
            if ($flag_rank==0&&$data[$key+1]['num']<=$value['num']) {
                if ($data[0]['num']==$value['num']) {
                    $value['rank_image'] = 1;
                }else if($data[1]['num']==$value['num']){
                    $value['rank_image'] = 2;
                }else if($data[2]['num']==$value['num']){
                    $value['rank_image'] = 3;
                }else{
                    $flag_rank = 1;
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
     * 多维数组排序
     * @param $multi_array
     * @param $sort_key
     * @param int $sort
     * @return array|bool
     */
    function multi_array_sort(&$multi_array, $sort_key, $sort=SORT_DESC)
    {
        if (is_array($multi_array)) {
            foreach ($multi_array as $row_array) {
                if (is_array($row_array)) {
                    $key_array[] = $row_array[$sort_key];
                } else {
                    return false;
                }
            }
        } else {
            return false;
        }

        array_multisort($key_array, $sort, $multi_array);
        return $multi_array;
    }

    /**
     * 获取连续天数
     * @author tanhuaxin
     * @param $openid
     * @param int $num
     * @param int $count
     * @return int
     */
    public function getDayCount($openid, $num = 1, $count = 2)
    {
        $map['openid'] = $openid;
        $sport_record = M('sport_record')->where($map)->order('add_time desc')->select();
        $i = 0;
        $nums = array();
        foreach ($sport_record as $key => $value) {
            if (isset($sport_record[$key-1])) {
                $t1 = strtotime(date('Y-m-d' . '00:00:00', $sport_record[$key-1]['add_time']));
                $t2 = strtotime(date('Y-m-d' . '00:00:00', $sport_record[$key]['add_time']));
                if (($t1-$t2)==(24*3600)&&$value['step_nums']>9999) {
                    $nums[$i]++;
                }else{
                    $i++;
                    $nums[$i] = 1;
                }
            }else{
                $nums[$i] = 1;
            }
        }
        rsort($nums,SORT_NUMERIC);
        return current($nums);
        /*$map['step_nums'] = array('gt', 9999);
        $startYesterday = strtotime(date('Y-m-d' . '00:00:00', time())) - 3600 * 24 * $count;
        $endYesterday = $startYesterday + 3600 * 24;
        $map['add_time'] = array('between', array($startYesterday, $endYesterday));
        $map['openid'] = $openid;
        $id = M('sport_record')->where($map)->getField('id');
        if ($id) {
            $num = $num + 1;
            $num = $this->getDayCount($openid, $num, $num + 1);
        }
        return $num;*/
    }

    /**
     * 联盟
     * @author tanhuaxin
     */
    public function member()
    {
        $userInfo = $this->checkLogin();
        $this->checkInvite();
        $map['user_login'] = $userInfo->openid;
        $users = M('Users')->where($map)->find();
        $map = array();
        $type = intval(I('type'));
        $data = array();
        $sum = 0;
        $status = empty($users['groupid']) ? 0 : 1;
        if ($status == 1 && (empty($data) || $type == 4 || $data['data'] == '[]')) {
            $typeName = date('Y-m-d', time() - 3600 * 24) . '  累计';//默认时间
            if (!empty($_POST) && $type == 4) {//时间段
                $startTime = strtotime(I('startTime'));
                $endTime = strtotime(I('endTime')) + 86399;
                $typeName = date('Y-m-d', $startTime) . ' ~ ' . date('Y-m-d', $endTime) . ' 累计';
                $map['add_time'] = array('between', array($startTime, $endTime));
            }
            if ($type == 1) {//昨天
                $typeName = '昨天统计 累计';
                $startYesterday = mktime(0, 0, 0, date('m'), date('d') - 1, date('Y'));
                $endYesterday = $startYesterday + 3600 * 24;
                $map['add_time'] = array('between', array($startYesterday, $endYesterday));
            } elseif ($type == 2) {//上周
                $typeName = '上周统计 累计';
                $beginLastweek = mktime(0, 0, 0, date('m'), date('d') - date('w') + 1 - 7, date('Y'));
                $endLastweek = mktime(23, 59, 59, date('m'), date('d') - date('w') + 7 - 7, date('Y'));
                $map['add_time'] = array('between', array($beginLastweek, $endLastweek));
            } elseif ($type == 3) {//上月
                $typeName = '上月统计 累计';
                $beginThismonth = mktime(0, 0, 0, date('m')-1, 1, date('Y'));
                $t = date('t',strtotime($beginThismonth));
                $endThismonth = mktime(23, 59, 59, date('m')-1, $t, date('Y'));
                $map['add_time'] = array('between', array($beginThismonth, $endThismonth));
            }
            $data = '[';
            if (!empty($map)) {
                $users = M('Users')->field('user_login,groupid,score,school')->where(array('groupid' => $users['groupid'],'user_status'=>array('neq','0')))->select();
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
                    $name = $users['user_nicename'];
                    $data .= "{value:{$value['num']}, name:'{$name}'},";
                }
            } else {
                $users = M('Users')->field('user_nicename,groupid,score,school')->where(array('groupid' => $users['groupid'],'user_status'=>array('neq','0')))->order('score DESC')->select();
                foreach ($users as $value) {
                    $sum += $value['score'];

                    $name = $value['user_nicename'];

                    $data .= "{value:{$value['score']}, name:'{$name}'},";
                }
            }
            $data = rtrim($data, ',') . ']';
        }
        $this->assign("userInfo", $userInfo);
        $this->assign("status", $status);
        $this->assign("sum", number_format($sum));
        $this->assign("data", $data);
        $this->assign("type", $typeName);
        $this->assign("footer", "zhishu");
        $this->display(":member");
    }

    public function attention()
    {
        $uid = intval(I('uid', 0, 'intval'));
        $this->checkLogin();
        $this->checkInvite();
        $user = session('user');
        $map = array(
            'uid' => $user['id'],
            'follow_uid' => $uid,
        );
        if (M('Attention')->where($map)->find()) {
            M('Attention')->where($map)->delete();
        } else {
            $data = array(
                'uid' => $user['id'],
                'follow_uid' => $uid,
                'add_time' => time(),
            );
            M('Attention')->add($data);
        }
        redirect(U('Index/huati', array('type' => 1, 'uid' => $uid)));
    }

    /**
     * 社区
     * @author tanhuaxin
     */
    public function community()
    {
        $this->checkLogin();
        $this->checkInvite();
        // $data = D('users')->find(1);
        // session('user', $data);
        $user = session('user');
        // $user['id'] = 1;
        $map['recommended'] = 1;
        $map['post_type'] = 1;
        $map['post_status'] = array('neq', 3);
        $posts = M('Posts')->field('id,post_title,post_date,istop')->where($map)->order('istop desc,recommended desc,post_date desc')->limit(5)->select();
        $postscount = M('Posts')->count();
        $userscount = M('Users')->count();
        $map['istop'] = 0;
        $map['recommended'] = 0;
        $type = intval(I('type', '0', 'intval'));
        if ($type == 1) {
            $attenUids = '';
            $attens = M('Attention')->where(array('uid' => $user['id']))->select();
            foreach ($attens as $key => $value) {
                $attenUids .= $value['follow_uid'] . ',';
            }
            $attenUids = rtrim($attenUids, ',');
            $map['post_author'] = array('IN', $attenUids);
            $pengyouquan = M('Posts')->field('id,post_content,post_date,post_image,post_author,post_like,comment_count')->where($map)->order('id DESC')->limit(50)->select();
        } else {
            $pengyouquan = M('Posts')->field('id,post_content,post_date,post_image,post_author,post_like,comment_count')->where($map)->order('id DESC')->limit(50)->select();
        }
        foreach ($pengyouquan as $key => $vl) {
            $users = D('users')->find($vl['post_author']);
            $pengyouquan[$key]['avatar'] = $users['avatar'];
            $pengyouquan[$key]['user_nicename'] = $users['user_nicename'];
            $pengyouquan[$key]['school'] = $users['school'];
            $pengyouquan[$key]['uid'] = $vl['post_author'];
            $pengyouquan[$key]['comment_count'] = D("Common/Comments")->where(array("post_table"=>'posts',"post_id"=>$vl['id'],"status"=>1))->count();
        }
        $this->assign("uid", $user['id']);
        // $this->assign("uid", 1);
        $this->assign("type", $type);
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
        $this->checkInvite();
        $user = session('user');
        // $user['id'] = 1;
        $map['post_author'] = $user['id'];
        $map['post_type'] = 1;
        $map['istop'] = 0;
        $map['recommended'] = 0;
        $map['post_status'] = array('neq', 3);
        $users = D('users')->find($user['id']);
        $type = intval(I('type', '1', 'intval'));
        $pengyouquan = array();
        if ($type == 1) {
            $pengyouquan = M('Posts')->field('id,post_content,post_date,post_image,post_author,post_like')
                ->where($map)
                ->order('id DESC')
                ->limit(50)->select();
            foreach ($pengyouquan as $key => &$vl) {
                $pengyouquan[$key]['avatar'] = $users['avatar'];
                $pengyouquan[$key]['user_nicename'] = $users['user_nicename'];
                $pengyouquan[$key]['uid'] = $users['id'];
                $pengyouquan[$key]['comment_count'] = D("Common/Comments")->where(array("post_table"=>'posts',"post_id"=>$vl['id'],"status"=>1))->count();
            }
        }
        if ($type == 2) {
            $where = array("uid" => $users['id'], "status" => 1);
            $pengyouquan = M('Comments')->where($where)->order('createtime DESC')->select();
            foreach ($pengyouquan as $key => &$vl) {
                $pengyouquan[$key]['avatar'] = $users['avatar'];
                $pengyouquan[$key]['user_nicename'] = $users['user_nicename'];
                $pengyouquan[$key]['uid'] = $users['id'];
                $posts = M('Posts')->find($vl['post_id']);
                if ($posts['post_status']!=3) {
                    if (empty($posts['post_title'])) {
                        $author = D('users')->find($posts['post_author']);
                        $pengyouquan[$key]['title'] = $author['user_nicename'] . '的话题';
                    } else {
                        $pengyouquan[$key]['title'] = $posts['post_title'];
                    }
                }else{
                    unset($pengyouquan[$key]);
                }
            }
        }
        if ($type == 3) {
            $where = array("user" => $user['id'], "action" => 'Portal-Article-do_like');
            $pengyouquan = M("CommonActionLog")->where($where)->order('last_time DESC')->limit(50)->select();
            foreach ($pengyouquan as $key => &$vl) {
                $pengyouquan[$key]['last_time'] = date('Y-m-d H:i:s', $pengyouquan[$key]['last_time']);
                $pengyouquan[$key]['avatar'] = $users['avatar'];
                $pengyouquan[$key]['user_nicename'] = $users['user_nicename'];
                $pengyouquan[$key]['uid'] = $users['id'];
                $pengyouquan[$key]['post_id'] = str_replace("posts", "", $vl['object']);
                $posts = M('Posts')->find($pengyouquan[$key]['post_id']);
                if ($posts['post_status']!=3) {
                    if (empty($posts['post_title'])) {
                        $author = D('users')->find($posts['post_author']);
                        $pengyouquan[$key]['title'] = $author['user_nicename'] . '的话题';
                    } else {
                        $pengyouquan[$key]['title'] = $posts['post_title'];
                    }
                }else{
                    unset($pengyouquan[$key]);
                }
            }
        }
        $this->assign("uid", $user['id']);
        // $this->assign("uid", 1);
        $this->assign("type", $type);
        $this->assign("pengyouquan", $pengyouquan);
        $this->assign("footer", "shequ");
        $this->display(":myhuati");
    }

    /**
     * 我的话题
     * @author tanhuaxin
     */
    public function huati()
    {
        $this->checkLogin();
        $this->checkInvite();
        $uid = intval(I('uid', '0', 'intval'));
        $map['post_author'] = $uid;
        $map['post_type'] = 1;
        $map['istop'] = 0;
        $map['recommended'] = 0;
        $map['post_status'] = array('neq', 3);
        $users = D('users')->find($uid);
        $user = session('user');
        $atten = M('Attention')->where(array('uid' => $user['id'], 'follow_uid' => $uid))->find();
        if ($atten) {
            $status = 1;
        } else {
            $status = 0;
        }
        $type = intval(I('type', '1', 'intval'));
        $pengyouquan = array();
        if ($type == 1) {
            $pengyouquan = M('Posts')->field('id,comment_count,post_content,post_date,post_image,post_author,post_like')
                ->where($map)
                ->order('id DESC')
                ->limit(50)->select();
            foreach ($pengyouquan as $key => &$vl) {
                $pengyouquan[$key]['avatar'] = $users['avatar'];
                $pengyouquan[$key]['user_nicename'] = $users['user_nicename'];
                $pengyouquan[$key]['uid'] = $users['id'];
                $pengyouquan[$key]['comment_count'] = D("Common/Comments")->where(array("post_table"=>'posts',"post_id"=>$vl['id'],"status"=>1))->count();
            }
        }
        if ($type == 2) {
            $where = array("uid" => $uid, "status" => 1);
            $pengyouquan = M('Comments')->where($where)->order('createtime DESC')->select();
            foreach ($pengyouquan as $key => &$vl) {
                $pengyouquan[$key]['avatar'] = $users['avatar'];
                $pengyouquan[$key]['user_nicename'] = $users['user_nicename'];
                $pengyouquan[$key]['uid'] = $users['id'];
                $posts = M('Posts')->find($vl['post_id']);
                if ($posts['post_status']!=3) {
                    if (empty($posts['post_title'])) {
                        $author = D('users')->find($posts['post_author']);
                        $pengyouquan[$key]['title'] = $author['user_nicename'] . '的话题';
                    } else {
                        $pengyouquan[$key]['title'] = $posts['post_title'];
                    }
                }else{
                    unset($pengyouquan[$key]);
                }
            }
        }
        if ($type == 3) {
            $where = array("user" => $uid, "action" => 'Portal-Article-do_like');
            $pengyouquan = M("CommonActionLog")->where($where)->order('last_time DESC')->limit(50)->select();
            foreach ($pengyouquan as $key => &$vl) {
                $pengyouquan[$key]['last_time'] = date('Y-m-d H:i:s', $pengyouquan[$key]['last_time']);
                $pengyouquan[$key]['avatar'] = $users['avatar'];
                $pengyouquan[$key]['user_nicename'] = $users['user_nicename'];
                $pengyouquan[$key]['uid'] = $users['id'];
                $pengyouquan[$key]['post_id'] = str_replace("posts", "", $vl['object']);
                $posts = M('Posts')->find($pengyouquan[$key]['post_id']);
                if ($posts['post_status']!=3) {
                    if (empty($posts['post_title'])) {
                        $author = D('users')->find($posts['post_author']);
                        $pengyouquan[$key]['title'] = $author['user_nicename'] . '的话题';
                    } else {
                        $pengyouquan[$key]['title'] = $posts['post_title'];
                    }
                }else{
                    unset($pengyouquan[$key]);
                }
                
            }
        }
        $this->assign("uid", $user['id']);
        $this->assign("attenuid", $uid);
        $this->assign("type", $type);
        $this->assign("status", $status);
        $this->assign("pengyouquan", $pengyouquan);
        $this->assign("footer", "shequ");
        $this->display(":huati");
    }

    /**
     * 发表说说
     * @author tanhuaxin
     */
    public function publishedpAbout()
    {
        $userInfo = $this->checkLogin();
        $this->checkInvite();
        $data['post_image'] = I('post_image');
        $data['post_content'] = I('post_content');
        $map['user_login'] = $userInfo->openid;
        $data['post_keywords'] = 'admin';
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
                'maxSize' => 999999999,
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
    public function index()
    {
        $userInfo = $this->checkLogin();
        $this->checkInvite();
        $map['openid'] = $userInfo->openid;
        $type = I('type', 0, 'int');
        $dates = '[';
        $datas = '[';
        if ($type == 1) {//周
            for ($i = 0; $i < 7; $i++) {
                $k = 7 - $i;
                $timeStart = mktime(0, 0, 0, date('m'), date('d') - date('w') + 1 - (7 * ($k - 1)), date('Y'));
                $timeEnd = mktime(23, 59, 59, date('m'), date('d') - date('w') + 7 - (7 * ($k - 1)), date('Y'));
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
//                $timeStart = mktime(0, 0, 0, date('m') - ($k - 1), 1, date('Y'));
//                $timeEnd = mktime(23, 59, 59, date('m') - ($k - 1), date('t'), date('Y'));
                $timeStart = mktime(0, 0, 0, date('m')-($k - 1), 1, date('Y'));
                $t = date('t',strtotime($timeStart));
                $timeEnd = mktime(23, 59, 59, date('m')-($k - 1), $t, date('Y'));
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
        $timeStart=mktime(0,0,0,date('m'),date('d')-1,date('Y'));
        $timeEnd=mktime(0,0,0,date('m'),date('d'),date('Y'))-1;
        $map['add_time'] = array('between', array($timeStart, $timeEnd));
        $nowNum = D('sport_record')->where($map)->sum('step_nums');
        //周
        $timeStart1=mktime(0,0,0,date('m'),date('d')-date('w')+1,date('Y'));
        $timeEnd1=mktime(23,59,59,date('m'),date('d')-date('w')+7,date('Y'));
        $map1['openid'] = $userInfo->openid;
        $map1['add_time'] = array('between', array($timeStart1, $timeEnd1));
        $nowNum1 = D('sport_record')->where($map1)->sum('step_nums');
        $nowCount1 = $count = date('w')==0?7:date('w')-1;
        
        //月
        $timeStart2=mktime(0,0,0,date('m'),1,date('Y'));
        $timeEnd2=mktime(23,59,59,date('m'),date('t'),date('Y'));
        $map2['openid'] = $userInfo->openid;
        $map2['add_time'] = array('between', array($timeStart2, $timeEnd2));
        $nowNum2 = D('sport_record')->where($map2)->sum('step_nums');
        $nowCount2 = $count = date('d')-1;

        $num = D('sport_record')->where(array('openid' => $userInfo->openid))->sum('step_nums');
        $umap['user_login'] = $userInfo->openid;
        $status = D('users')->where($umap)->getField('status');
        $nowNum = $nowNum ? $nowNum : 0;
        $nowNum1 = $nowNum1 ? $nowNum1 / $nowCount1 : 0;
        $nowNum2 = $nowNum2 ? $nowNum2 / $nowCount2 : 0;
        $this->assign("userInfo", $userInfo);
        $this->assign("num", number_format($num));
        $this->assign("nowNum", sprintf("%.2f", $nowNum));
        $this->assign("nowKa", $this->getKa($nowNum));
        $this->assign("nowNum1", sprintf("%.2f", $nowNum1));
        $this->assign("nowKa1", $this->getKa($nowNum1));
        $this->assign("nowNum2", sprintf("%.2f", $nowNum2));
        $this->assign("nowKa2", $this->getKa($nowNum2));
        $this->assign("dates", $dates);
        $this->assign("datas", $datas);
        $this->assign("status", $status);
        $this->assign("type", $type);
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
        $this->checkInvite();
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
        redirect(U('index'));
    }

    /**
     * 个人、联盟排名
     * @author tanhuaxin
     */
    public function rank()
    {
        $user = array();
        $userInfo = $this->checkLogin();
        $this->checkInvite();
        $type = I('type', '', 'intval');
        $grouptype = I('grouptype', '', 'intval');
        $rankDataCachKey = 'rankData_' . date('Y-m-d:H', time()) . '_' . $type . '_' . $grouptype;
        $rankUserCachKey = $userInfo->openid . 'rankUser_' . date('Y-m-d:H', time()) . '_' . $type . '_' . $grouptype;
        $data = array();
        $map = '';
        if (!empty($_POST) && $type == 4) {//时间段
            $startTime = strtotime(I('startTime'));
            $endTime = strtotime(I('endTime')) + 86399;
            $map['add_time'] = array('between', array($startTime, $endTime));
        } elseif ($type == 1) {//昨天
            // $startTime =strtotime(date('Y-m-d 00::00::00',time()))-24*3600;
            // $endTime = strtotime(date('Y-m-d 00::00::00',time()))-1;
            $startTime = mktime(0, 0, 0, date('m'), date('d') - 2, date('Y'));
            $endTime = $startTime + 3600 * 24;
            $map['add_time'] = array('between', array($startTime, $endTime));
        } elseif ($type == 2) {//上周
            $startTime = time()-(intval(7+date('N', time()))*24*3600);
            $endTime = time()-(intval(date('N', time()))*24*3600);
            $map['add_time'] = array('between', array($startTime, $endTime));
        } elseif ($type == 3) {//上月
            $startTime = mktime(0, 0, 0, date('m')-1, 1, date('Y'));
            $t = date('t',strtotime($startTime));
            $endTime = mktime(23, 59, 59, date('m')-1, $t, date('Y'));
            $map['add_time'] = array('between', array($startTime, $endTime));
        } else {
            $startTime = mktime(0, 0, 0, date('m'), date('d') - 1, date('Y'));
            $endTime = $startTime + 3600 * 24;
            $map['add_time'] = array('between', array($startTime, $endTime));
        }
        $usersModel = D('users');
        $flag_rank = 0;
        if ($grouptype == 0) {
            $map['openid'] = array('neq', 'admin');
            $data = D('sport_record')->where($map)->field('openid,sum(step_nums) as num')->group('openid')->order('num DESC , convert(nick_name using gbk) ASC')->select();
            foreach ($data as $key => $vl) {
                $map['user_login'] = $vl['openid'];
                $users = $usersModel->where($map)->find();
                if ($users['groupid']==20||$users['user_status']==0) {
                    unset($data[$key]);
                    continue;
                }
                $data[$key]['avatar'] = $users['avatar'];
                $data[$key]['nick_name'] = $users['user_nicename'];
                $data[$key]['school'] = $users['school'];                    
            }
        } elseif ($grouptype == 1) {
            $data = D('SportRecord')->where($map)->field('groupid,sum(step_nums) as num')->group('groupid')->order('num DESC , convert(nick_name using gbk) ASC')->select();
            $groups = array();
            foreach ($data as $key => $vl) {
                if ($vl['groupid'] > 0) {
                    if (!isset($groups[$vl['groupid']])) {
                        $groups[$vl['groupid']]['num'] = 0;
                        $group = M('Group')->find($vl['groupid']);
                        $groups[$vl['groupid']]['id'] = $group['id'];
                        $groups[$vl['groupid']]['nick_name'] = $group['name'];
                        $groups[$vl['groupid']]['avatar'] = '/data/upload/' . $group['logo'];
                    }
                    $count = M('SportRecord')->where(array_merge($map,array('groupid'=>$vl['groupid'])))->count();
                    $groups[$vl['groupid']]['num'] += $vl['num'];
                    $groups[$vl['groupid']]['avgNum'] = intval($groups[$vl['groupid']]['num'] / $count);
                    // $count = M('SportRecord')->where(array_merge($map,array('groupid'=>$vl['groupid'])))->group('openid')->select();
                    // $count = count($count);
                    // $groups[$vl['groupid']]['num'] += $vl['num'];
                    // $count = $usersModel->where(array('groupid' => $vl['groupid'],'user_status'=>array('neq','0')))->count();
                    // $day = ceil(($endTime-$startTime)/(3600*24));
                    // $groups[$vl['groupid']]['avgNum'] = intval($groups[$vl['groupid']]['num'] / ($day*$count));
                }
            }
            if (isset($groups[20])) {
                unset($groups[20]);
            }
            usort($groups, 'avgNum');
            $data = $this->multi_array_sort($groups, 'avgNum');
        }
        $user = $this->_getUserRank($grouptype, $data, $userInfo);

        if (!empty($_POST) && $type == 4) {//时间段
            $typeName = date('Y-m-d', $startTime) . '~' . date('Y-m-d', $endTime);
        } elseif ($type == 2) {//上周
            $typeName = '上周排行';
        } elseif ($type == 3) {//上月
            $typeName = '上月排行';
        } else {
            $typeName = '昨天排行';
        }
        if (empty($data)) {
            $status = 1;
            $this->assign("status", $status);
            $typeName = $typeName . '无记录';
        }
        $flag_rank = 0;
        $flag_key = $grouptype?'avgNum':'num';
        foreach ($data as $key => &$value) {
            $value['rank_image'] = 0;
            if ($flag_rank==0&&$data[$key+1][$flag_key]<=$value[$flag_key]) {
                if ($data[0][$flag_key]==$value[$flag_key]) {
                    $value['rank_image'] = 1;
                }else if($data[1][$flag_key]==$value[$flag_key]){
                    $value['rank_image'] = 2;
                }else if($data[2][$flag_key]==$value[$flag_key]){
                    $value['rank_image'] = 3;
                }else{
                    $flag_rank = 1;
                }
            }
        }
        $this->assign("startTime", $startTime);
        $this->assign("endTime", $endTime);
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
                    $user['rank'] = $key+1;
                    $user['nick_name'] = $users['user_nicename'];
                    $user['num'] = $vl['num'];
                    $user['avatar'] = $userInfo->headimgurl;
                }
            }
        } elseif ($grouptype == 1) {
            $rank = 0;
            foreach ($data as $key => $vl) {
                $rank++;
                $map['user_login'] = $userInfo->openid;
                $users = M('Users')->where($map)->find();
                if ($vl['id'] == $users['groupid']) {
                    $count = M('Users')->where(array('groupid' => $users['groupid'],'user_status'=>array('neq','0')))->count();
                    $user['rank'] = $rank;
                    $user['groupid'] = $vl['id'];
                    $user['nick_name'] = $vl['nick_name'];
                    $user['num'] = $vl['num'];
                    $user['avgNum'] = intval($vl['num'] / $count);
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
        $this->checkInvite();
        $data['openid'] = $userInfo->openid;
        // $data['openid'] = 'admin';
        $map['user_login'] = $data['openid'];
        $score = current(M('Users')->where($map)->getField('user_login,score,limit_score', 1));
        $selfgood = M("Good")->where(array('type' => 1))->order('add_time desc')->select();
        $othergood = M("Good")->where(array('type' => 2))->order('add_time desc')->select();
        $this->assign("nowscore", number_format($score['limit_score']));
        $this->assign("allscore", number_format($score['score']));
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
            $this->checkInvite();
            $data['openid'] = $userInfo->openid;
            $map['user_login'] = $data['openid'];
            $score = current(M('Users')->where($map)->getField('user_login,limit_score,score,coin', 1));
            if ($score['score'] <= $score['coin'] || $score['score'] <= 0) {
                $this->error("腾币不足", U('Index/shop'), true);
            }
            // $score = $score['score'] - $score['coin'];//余额
            $score = $score['limit_score'];//余额
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
                $this->error("下单失败,该商品不存在", U('Index/shop'), true);
            }
            if ($score < $data['price']) {
                $this->error("腾币不足", U('Index/shop'), true);
            }
            if (M('GoodOrder')->create($data) !== false) {
                $id = M('GoodOrder')->add();
                if ($id !== false) {
                    //更新用户余额
                    $map['user_login'] = $data['openid'];
                    $users = M('users')->where($map)->find();
                    if ($users) {
                        $users['coin'] += $good['price'];
                        $users['limit_score'] -= $good['price'];
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
                                $this->error("下单失败", U('Index/shop'), true);
                            }
                        } else {
                            M('GoodOrder')->where(array('id' => $id))->delete();
                            $this->error("下单失败", U('Index/shop'), true);
                        }
                    } else {
                        M('GoodOrder')->where(array('id' => $id))->delete();
                        $this->error("下单失败", U('Index/shop'), true);
                    }
                } else {
                    $this->error("下单失败", U('Index/shop'), true);
                }
            } else {
                $this->error("下单失败", U('Index/shop'), true);
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
        $this->checkInvite();
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
        $this->checkInvite();
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

    public function weixinError()
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
        $this->checkInvite();

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
                    echo "<script> window.alert = function(name){
                        var iframe = document.createElement('IFRAME');
                        iframe.style.display='none';
                        iframe.setAttribute('src', 'data:text/plain,');
                        document.documentElement.appendChild(iframe);
                        window.frames[0].window.alert(name);
                        iframe.parentNode.removeChild(iframe);
                    }; </script>";
                    echo "<script> alert('上传成功'); </script>";
                    echo "<meta http-equiv='Refresh' content='0;URL=" . U('index') . "'>";
                } else {
                    $this->error('上传数据出错', U('index'));
                }
            } else {
                $this->error('上传文件出错', U('index'));
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
        $this->checkInvite();
        $date = I('date', 0, 'int');
        $type = I('type', 0, 'int');
        $map['openid'] = $userInfo->openid;
        $record = array();
        if ($date == 1) {//周
            for ($i = 1; $i <= 52; $i++) {
                $timeStart = mktime(0, 0, 0, date('m'), date('d') - date('w') + 1 - (7 * ($i - 1)), date('Y'));
                $timeEnd = mktime(23, 59, 59, date('m'), date('d') - date('w') + 7 - (7 * ($i - 1)), date('Y'));
                //var_dump(date('y-m-d',$timeStart));
                //var_dump(date('y-m-d',$timeEnd));
                if ($timeStart > '1483056000') {
                    $map['add_time'] = array('between', array($timeStart, $timeEnd));
                    $data['add_time'] = date('Y年W周', $timeStart);
                    $step_nums = D('sport_record')->where($map)->sum('step_nums');
//                    $step_count = D('sport_record')->where($map)->count();
                    if($i == 1){
                        $count = date('w')==0?7:date('w')-1;
                    } else {
                        $count = 7;
                    }
                    if ($type == 1) {
                        $data['step_ka'] = $step_nums ? sprintf("%.2f", $this->getKa($step_nums/$count)) : 0;
                        if($data['step_ka'] != 0) {
                            $record[] = $data;
                        }
                    } else {
                        $data['step_nums'] = $step_nums ?sprintf("%.2f", $step_nums/$count): 0;
                        if($data['step_nums'] != 0) {
                            $record[] = $data;
                        }
                    }
                }
            }
        } elseif ($date == 2) {
            for ($i = 1; $i <= 25; $i++) {
//                $timeStart = mktime(0, 0, 0, date('m')-($i - 1), 1, date('Y'));
//                $t = date('t',strtotime($timeStart));
//                $timeEnd = mktime(23, 59, 59, date('m')-($i - 1), $t, date('Y'));

                $timeStart = mktime(0, 0, 0, date('m') - ($i - 1), 1, date('Y'));
                $t = date('t',strtotime($timeStart));
                $timeEnd = mktime(23, 59, 59, date('m') - ($i - 1), $t, date('Y'));
                if ($timeStart > '1483056000') {
                    $map['add_time'] = array('between', array($timeStart, $timeEnd));
                    $data['add_time'] = date('Y年m月', $timeStart);
                    $step_nums = D('sport_record')->where($map)->sum('step_nums');
//                    $step_count = D('sport_record')->where($map)->count();
                    if($i == 1){
                        $count = date('d')-1;
                    } else {
                        $count = date('t', $timeStart);
                    }
                    if ($type == 1) {
                        $data['step_ka'] = $step_nums ? sprintf("%.2f", $this->getKa($step_nums/$count)) : 0;
                        if($data['step_ka'] != 0) {
                            $record[] = $data;
                        }
                    } else {
                        $data['step_nums'] = $step_nums ?sprintf("%.2f", $step_nums/$count): 0;
                        if($data['step_nums'] != 0) {
                            $record[] = $data;
                        }
                    }
                }
            }
        } else {
            $map['openid'] = $userInfo->openid;
            $record = D('sport_record')->where($map)->order('add_time DESC')->select();
            if ($type == 1) {//卡
                foreach ($record as $key => $value) {
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
        $this->checkInvite();
        $map['user_login'] = $userInfo->openid;
        $groupid = I('groupid', 0, 'int');
        $group = M('Group')->find($groupid);

        $startYesterday = I('startTime');
        $endYesterday = I('endTime');
        if(empty($startYesterday) || empty($endYesterday)) {
            $startYesterday = mktime(0, 0, 0, date('m'), date('d') - 1, date('Y'));
            $endYesterday = $startYesterday + 3600 * 24;
        }
        $mapRec['add_time'] = array('between', array($startYesterday, $endYesterday));
        $users = M('sport_record')->field('openid')->where(array_merge($mapRec,array('groupid' => $groupid)))->group('openid')->select();
        foreach ($users as $key => &$value) {
            $value = M('Users')->field('user_nicename,avatar,school,user_login')->where(array('user_login'=>$value['openid']))->find();
            $mapRec['openid'] = $value['user_login'];
            $value['score'] = M('sport_record')->where($mapRec)->getField('sum(step_nums) as step_nums');
            $count = M('sport_record')->where($mapRec)->count();
            $value['score'] = intval($value['score']/$count);
        }
        $typeName = date('Y-m-d', $startYesterday) . '~' . date('Y-m-d', $endYesterday);
        $this->assign("type", $typeName);
        $this->assign("userInfo", $userInfo);
        $this->assign("data", $this->multi_array_sort($users, 'score'));
        $this->assign("group", $group);
        $this->assign("footer", "zhishu");
        $this->display(":rankdetail");
    }
}

