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
        //微信登录
        $options = array(
            'token' => self::TOKEN, //填写你设定的key
            'appid' => self::APPID, //填写高级调用功能的app id
            'appsecret' => self::APPSECRET //填写高级调用功能的密钥
        );
        $this->weObj = new Wechat($options);
        $type = $this->weObj->getRev()->getRevType();
        $openid = $this->weObj->getRev()->getRevFrom();
        switch ($type) {
            case Wechat::MSGTYPE_TEXT:
                $this->saveUserInfo($openid);
                $this->weObj->text($openid . '111')->reply();
                break;
            case Wechat::MSGTYPE_EVENT:
                $news = array("0"=>array(
                    'Title' => '健康部落',
                    'Description' => '幸福家庭，健康生活!',
                    'PicUrl' => 'http://thiff.togogosz.net/themes/jkbl/Public/assets/images/img.png',
                    'Url' => 'http://thiff.togogosz.net?openid=' . $openid
                ));
                $this->saveUserInfo($openid);
                $this->weObj->news($news)->reply();
                break;
            default:
                $this->saveUserInfo($openid);
                $this->weObj->text($openid . '333')->reply();
        }
    }
    /**
     * [opidLogin 微信登录]
     * @return [type] [description]
     */
    public function opidLogin(){
        if (sp_is_weixin()) {
            $userInfo = session('userInfo');
            $user = session('user');
            $ADMIN_ID = session('ADMIN_ID');
            if (!$userInfo||!$user||!$ADMIN_ID) {
                $openid = I('openid');
                if (empty($openid)) {
                    redirect(U('noerror'));
                }
                $map['user_login'] = $openid;
                $user = D('users')->where($map)->find();
                if (empty($user)) {
                    redirect(U('noerror'));
                }            
                session('user', $user);
                $userInfo['openid'] = $openid;
                $userInfo['nickname'] = $user['user_nicename'];
                $userInfo['headimgurl'] = $user['avatar'];
                $userInfo = $this->arrayToObject($userInfo);
                session('userInfo', $userInfo);
                session('ADMIN_ID', 1);
            }
        } else {
            //提示请使用微信登录
            redirect(U('error'));
        }
        
    }
    public function saveUserInfo($openid){
//        $userInfo = json_decode($_COOKIE['userInfo']);
//        if (!empty($userInfo) || empty($user) || !isset($userInfo->openid)) {
            $url1 = Wechat::API_BASE_URL_PREFIX . '/cgi-bin/token?grant_type=client_credential&appid=' . self::APPID . '&secret=' . self::APPSECRET;
            $result1 = json_decode(file_get_contents($url1));
            $url2 = Wechat::API_BASE_URL_PREFIX . '/cgi-bin/user/info?access_token=' . $result1->access_token . '&openid=' . $openid . '&lang=zh_CN';
            $result2 = file_get_contents($url2);
            $userInfo = json_decode($result2);
            if ($userInfo->subscribe == 0) {
                redirect(U('follow'));
            } elseif ($userInfo->subscribe == 1) {
                //保存用户信息入库
                $map['user_login'] = $userInfo->openid;
                $users = D('users')->where($map)->find();
                if ($users) {
                    session('ADMIN_ID', 1);
                    session('user', $users);
                    $data['last_login_time'] = date("Y-m-d H:i:s", time());
                    D('users')->save($data);
                    $userInfo->nickname = $users['user_nicename'];
                    session('userInfo', $userInfo);
//                    setcookie('userInfo', json_encode($userInfo));
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
                    session('userInfo', $userInfo);
//                    setcookie('userInfo', $result2);
                }
//                  redirect(U('index'), array('openid' => '11'));
            } else {
                redirect(U('error'));
            }
//        } else {
//            return $userInfo;
//        }
    }

    /**
     * 达人堂 小强是(爸爸&&最帅的男人)了
     * @author tanhuaxin
     */
    public function index()
    {
        $this->opidLogin();
        $userInfo = session('userInfo');
        // $userInfo->openid = 'admin';
        // $userInfo->headimgurl = 'admin';
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

//        $userInfo = $this->checkLogin();
        $userInfo = session('userInfo');
        // $userInfo->openid = 'admin';
        $map['user_login'] = $userInfo->openid;
        $users = M('Users')->where($map)->find();
        $map = array();
        $record = array();
        $type = intval(I('type'));
        $memberCachKey = $users['groupid'] . '_member_' . date('Y-m-d:H', time()) . '_' . $type;
        S($memberCachKey,null);
        $data = unserialize(S($memberCachKey));
        $sum = 0;
        $status = empty($users['groupid'])?0:1;
        $typeName = '';
        if ($status==1 && (empty($data) || $type == 4 || $data['data']=='[]')) {
            if (!empty($_POST) && $type == 4) {//时间段
                $startTime = strtotime(I('startTime'));
                $endTime = strtotime(I('endTime'));
                $typeName = date('Y-m-d', $startTime).' - '.date('Y-m-d', $endTime);
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
                $users = M('Users')->field('user_login,groupid,score')->where(array('groupid' => $users['groupid']))->select();
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
                    $data .= "{value:{$value['num']}, name:'{$users['user_nicename']}'},";
                }
            } else {
                $users = M('Users')->field('user_nicename,groupid,score')->where(array('groupid' => $users['groupid']))->select();
                foreach ($users as $value) {
                    $sum += $value['score'];
                    $data .= "{value:{$value['score']}, name:'{$value['user_nicename']}'},";
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
            if ($status==0) {
                $data = '[]';
            }else{
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
        $map['istop'] = 1;
        $map['recommended'] = 1;
        $map['post_type'] = 1;
        $map['post_status'] = array('neq',3);
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
//        $this->checkLogin();
        $user = session('user');
        $map['post_author'] = $user['id'];
        $map['post_type'] = 1;
        $map['istop'] = 0;
        $map['recommended'] = 0;
        $map['post_status'] = array('neq',3);
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
//        $userInfo = $this->checkLogin();
        $userInfo = session('userInfo');
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
        $upload_setting = sp_get_upload_setting();

        $filetypes = array(
            'image' => array('title' => 'Image files', 'extensions' => $upload_setting['image']['extensions']),
            'video' => array('title' => 'Video files', 'extensions' => $upload_setting['video']['extensions']),
            'audio' => array('title' => 'Audio files', 'extensions' => $upload_setting['audio']['extensions']),
            'file' => array('title' => 'Custom files', 'extensions' => $upload_setting['file']['extensions'])
        );

        $image_extensions = explode(',', $upload_setting['image']['extensions']);

        if (IS_POST) {
            $all_allowed_exts = array();
            foreach ($filetypes as $mfiletype) {
                array_push($all_allowed_exts, $mfiletype['extensions']);
            }
            $all_allowed_exts = implode(',', $all_allowed_exts);
            $all_allowed_exts = explode(',', $all_allowed_exts);
            $all_allowed_exts = array_unique($all_allowed_exts);

            $file_extension = sp_get_file_extension($_FILES['file']['name']);
            $upload_max_filesize = $upload_setting['upload_max_filesize'][$file_extension];
            $upload_max_filesize = empty($upload_max_filesize) ? 2097152 : $upload_max_filesize;//默认2M

            $app = I('post.app/s', '');
            if (!in_array($app, C('MODULE_ALLOW_LIST'))) {
                $app = 'default';
            } else {
                $app = strtolower($app);
            }

            $savepath = $app . '/' . date('Ymd') . '/';
            //上传处理类
            $config = array(
                'rootPath' => './' . C("UPLOADPATH"),
                'savePath' => $savepath,
                'maxSize' => $upload_max_filesize,
                'saveName' => array('uniqid', ''),
                'exts' => $all_allowed_exts,
                'autoSub' => false,
            );
            $upload = new \Think\Upload($config);//
            $info = $upload->upload();
            //开始上传
            if ($info) {
                //上传成功
                $oriName = $_FILES['file']['name'];
                //写入附件数据库信息
                $first = array_shift($info);
                if (!empty($first['url'])) {
                    $url = $first['url'];
                    $storage_setting = sp_get_cmf_settings('storage');
                    $qiniu_setting = $storage_setting['Qiniu']['setting'];
                    $url = preg_replace('/^https/', $qiniu_setting['protocol'], $url);
                    $url = preg_replace('/^http/', $qiniu_setting['protocol'], $url);

                    $preview_url = $url;

                    if (in_array($file_extension, $image_extensions)) {
                        if (C('FILE_UPLOAD_TYPE') == 'Qiniu' && $qiniu_setting['enable_picture_protect']) {
                            $preview_url = $url . $qiniu_setting['style_separator'] . $qiniu_setting['styles']['thumbnail300x300'];
                            $url = $url . $qiniu_setting['style_separator'] . $qiniu_setting['styles']['watermark'];
                        }
                    } else {
                        $preview_url = '';
                        $url = sp_get_file_download_url($first['savepath'] . $first['savename'], 3600 * 24 * 365 * 50);//过期时间设置为50年
                    }

                } else {
                    $url = C("TMPL_PARSE_STRING.__UPLOAD__") . $savepath . $first['savename'];
                    $preview_url = $url;
                }
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
//        $userInfo = $this->checkLogin();
        $userInfo = session('userInfo');
        $map['openid'] = $userInfo->openid;
        $num = D('sport_record')->where($map)->sum('step_nums');
        $umap['user_login'] = $userInfo->openid;
        $status = D('users')->where($umap)->getField('status');
        $this->assign("userInfo", $userInfo);
        $this->assign("num", $num);
        $this->assign("status", $status);
        $this->assign("footer", "zhishu");
        $this->display(":personal");
    }

    /**
     * 修改名字
     */
    public function editName()
    {
//        $userInfo = $this->checkLogin();
        $userInfo = session('userInfo');
        if (IS_POST) {
            $user_nicename = I('user_nicename');
            $map['user_login'] = $userInfo->openid;
            $usersModel = D('users');
            $status = $usersModel->where($map)->getField('status');
            if ($status == 0) {
                $data['user_nicename'] = $user_nicename;
                $data['status'] = 1;
                $usersModel->where($map)->save($data);
                $userInfo->nickname = $user_nicename;
                $data['user_nicename'] = $userInfo->nickname;
                $user = session('user');
                $user['user_nicename'] = $userInfo->nickname;
                session('user',$user);
//                setcookie('userInfo', json_encode($userInfo));
                session('userInfo', $userInfo);
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
        $data = array();
        $user = array();
//        $userInfo = $this->checkLogin();
        $userInfo = session('userInfo');
        // $userInfo->openid = 'admin';
        // $userInfo->headimgurl = 'admin';
        $type = intval(I('type'));
        $grouptype = intval(I('grouptype'));
        $rankDataCachKey = 'rankData_' . date('Y-m-d:H', time()) . '_' . $type . '_' . $grouptype;
        $rankUserCachKey = $userInfo->openid . 'rankUser_' . date('Y-m-d:H', time()) . '_' . $type . '_' . $grouptype;
        $data = unserialize(S($rankDataCachKey));
        if (!empty($data)) {
            $user = unserialize(S($rankUserCachKey));
        }
        if (empty($data) || $type == 4) {
            $map = '';
            if (!empty($_POST) && $type == 4) {//时间段
                $startTime = strtotime(I('startTime'));
                $endTime = strtotime(I('endTime'));
                $typeName = date('Y-m-d', $startTime).' - '.date('Y-m-d', $endTime);
                $map['add_time'] = array('between', array($startTime, $endTime));
            }
            if ($type == 1) {//昨天
                $typeName = '昨天排行';
                $startYesterday = mktime(0, 0, 0, date('m'), date('d') - 1, date('Y'));
                $endYesterday = mktime(0, 0, 0, date('m'), date('d'), date('Y')) - 1;
                $map['add_time'] = array('between', array($startYesterday, $endYesterday));
            } elseif ($type == 2) {//上周
                $typeName = '上周排行';
                $beginLastweek = mktime(0, 0, 0, date('m'), date('d') - date('w') + 1 - 7, date('Y'));
                $endLastweek = mktime(23, 59, 59, date('m'), date('d') - date('w') + 7 - 7, date('Y'));
                $map['add_time'] = array('between', array($beginLastweek, $endLastweek));
            } elseif ($type == 3) {//上月
                $typeName = '上月排行';
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
                    /*if ($userInfo->openid == $vl['openid']) {
                        $user['rank'] = $key + 1;
                        $user['nick_name'] = $users['user_nicename'];
                        $user['num'] = $vl['num'];
                        $user['avatar'] = $userInfo->headimgurl;
                    }*/
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
                            $groups[$users['groupid']]['nick_name'] = $group['name'];
                            $groups[$users['groupid']]['avatar'] = '/data/upload/' . $group['logo'];
                        }
                        $groups[$users['groupid']]['num'] += $vl['num'];
                    }
                }
                unset($groups[0]);
                usort($groups, 'sortByNum');
                /*foreach ($groups as $key => $vl) {
                    $map['user_login'] = $userInfo->openid;
                    $users = $usersModel->where($map)->find();
                    if ($vl['id']==$users['groupid']) {
                        $user['rank'] = $key + 1;
                        $user['nick_name'] = $vl['nick_name'];
                        $user['num'] = $vl['num'];
                        $user['avatar'] = $vl['avatar'];
                    }
                }*/
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

        $this->assign("grouptype", $grouptype);
        $this->assign("data", $data);
        $this->assign("user", $user);
        $this->assign("typeName", $typeName);
        $this->assign("type", $type);
        $this->assign("footer", "zhishu");
        // $this->assign("userInfo", $userInfo);
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
//        $userInfo = $this->checkLogin();
        $userInfo = session('userInfo');
        // $userInfo->openid = 'admin';
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
//            $userInfo = $this->checkLogin();
            $userInfo = session('userInfo');
            $data['openid'] = $userInfo->openid;
            // $data['openid'] = 'admin';
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
                            if (M('CoinRecord')->add($data1)){
                                if ($good['type']==2) {
                                    $this->success("THIFF将代您送出爱心~", U('Index/shop'), true);
                                }else{
                                    $this->success("下单成功", U('Index/shop'), true);
                                }
                            }else {
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
                // $this->error(M('GoodOrder')->getError());
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
//        $userInfo = $this->checkLogin();
        $userInfo = session('userInfo');
        $map['openid'] = $userInfo->openid;
        // $map['openid'] = 'admin';
        $orders = M('GoodOrder')->where($map)->order('add_time desc')->select();
        foreach ($orders as $key => &$value) {
            $good = M('Good')->find($value['goodid']);
            if ($good) {
                $value['url'] = $good['url'];
            }
        }
        // $this->assign("userInfo", $userInfo);
        $this->assign("orders", $orders);
        $this->display(":orderlist");
    }

    /**
     * [orderList 腾币记录]
     * @return [type] [description]
     */
    public function coinList()
    {
//        $userInfo = $this->checkLogin();
        $userInfo = session('userInfo');
        $map['openid'] = $userInfo->openid;
        // $map['openid'] = 'admin';
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
        // $this->assign("userInfo", $userInfo);
        $this->assign("Records", $Records);
        $this->display(":coinlist");
    }
    
    public function error()
    {
        $this->display(":error");
    }
    
    public function noerror()
    {
        $this->display(":noerror");
    }

    /**
     * 上传步数
     * @author tanhuaxin
     */
    public function uploadSport()
    {
        $num = I('num', 0, 'int');
//        $userInfo = $this->checkLogin();
        $userInfo = session('userInfo');
        $upload_setting = sp_get_upload_setting();

        $filetypes = array(
            'image' => array('title' => 'Image files', 'extensions' => $upload_setting['image']['extensions']),
            'video' => array('title' => 'Video files', 'extensions' => $upload_setting['video']['extensions']),
            'audio' => array('title' => 'Audio files', 'extensions' => $upload_setting['audio']['extensions']),
            'file' => array('title' => 'Custom files', 'extensions' => $upload_setting['file']['extensions'])
        );

        $image_extensions = explode(',', $upload_setting['image']['extensions']);

        if (IS_POST) {
            $all_allowed_exts = array();
            foreach ($filetypes as $mfiletype) {
                array_push($all_allowed_exts, $mfiletype['extensions']);
            }
            $all_allowed_exts = implode(',', $all_allowed_exts);
            $all_allowed_exts = explode(',', $all_allowed_exts);
            $all_allowed_exts = array_unique($all_allowed_exts);

            $file_extension = sp_get_file_extension($_FILES['file']['name']);
            $upload_max_filesize = $upload_setting['upload_max_filesize'][$file_extension];
            $upload_max_filesize = empty($upload_max_filesize) ? 2097152 : $upload_max_filesize;//默认2M

            $app = I('post.app/s', '');
            if (!in_array($app, C('MODULE_ALLOW_LIST'))) {
                $app = 'default';
            } else {
                $app = strtolower($app);
            }

            $savepath = $app . '/' . date('Ymd') . '/';
            //上传处理类
            $config = array(
                'rootPath' => './' . C("UPLOADPATH"),
                'savePath' => $savepath,
                'maxSize' => $upload_max_filesize,
                'saveName' => array('uniqid', ''),
                'exts' => $all_allowed_exts,
                'autoSub' => false,
            );
            $upload = new \Think\Upload($config);//
            $info = $upload->upload();
            //开始上传
            if ($info) {
                //上传成功
                //写入附件数据库信息
                $first = array_shift($info);
                if (!empty($first['url'])) {
                    $url = $first['url'];
                    $storage_setting = sp_get_cmf_settings('storage');
                    $qiniu_setting = $storage_setting['Qiniu']['setting'];
                    $url = preg_replace('/^https/', $qiniu_setting['protocol'], $url);
                    $url = preg_replace('/^http/', $qiniu_setting['protocol'], $url);

                    if (in_array($file_extension, $image_extensions)) {
                        if (C('FILE_UPLOAD_TYPE') == 'Qiniu' && $qiniu_setting['enable_picture_protect']) {
                            $url = $url . $qiniu_setting['style_separator'] . $qiniu_setting['styles']['watermark'];
                        }
                    } else {
                        $url = sp_get_file_download_url($first['savepath'] . $first['savename'], 3600 * 24 * 365 * 50);//过期时间设置为50年
                    }
                } else {
                    $url = C("TMPL_PARSE_STRING.__UPLOAD__") . $savepath . $first['savename'];
                }

                $data['num'] = $num;
                $data['image'] = $url;
                $data['time'] = time();
                $data['openid'] = $userInfo->openid;
                $result = M('sport')->add($data);
                if ($result) {
                    header("Content-type:text/html;charset=utf-8");
                    echo "<script> alert('上传成功'); </script>"; 
                    echo "<meta http-equiv='Refresh' content='0;URL=".U('personal')."'>"; 
                    // $this->success("上传成功", U('personal'), true);
                    // redirect(U('personal'));
                } else {
                    $this->error('上传数据出错', U('personal'));
                }
            } else {
                $this->error('上传文件出错', U('personal'));
            }
        }
    }

    public function follow()
    {
        $this->display(":follow");
    }
    public  function arrayToObject($e){
        if( gettype($e)!='array' ) return;
        foreach($e as $k=>$v){
            if( gettype($v)=='array' || getType($v)=='object' )
                $e[$k]=(object)arrayToObject($v);
        }
        return (object)$e;
    }
}
