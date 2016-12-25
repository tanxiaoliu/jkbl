<?php
namespace Admin\Controller;

use Common\Controller\AdminbaseController;
use PHPExcel_Cell;
use PHPExcel_IOFactory;

class SportController extends AdminbaseController
{

    protected $sport_model;

    public function _initialize()
    {
        parent::_initialize();
        $this->sport_model = M("SportRecord");
    }

    public function index()
    {
        $where = array();
        $count = $this->sport_model->count();
        $page = $this->page($count, 20);
        $sport = $this->sport_model
            ->where($where)
            ->order("add_time DESC")
            ->limit($page->firstRow, $page->listRows)
            ->select();
        $this->assign("page", $page->show('Admin'));
        $this->assign("sport", $sport);
        $this->display();
    }

    // 物品添加或编辑
    public function add()
    {
        $data = array();
        if (!empty($_POST)) {
            $data = I("post.");
            $data['nums'] = 0;
            $data['add_time'] = time();
            if ($this->sport_model->create($data) !== false) {
                if (isset($data['id'])) {
                    if ($this->sport_model->save() !== false) {
                        $this->success("保存成功！", U("sport/index"));
                    } else {
                        $this->error("保存失败！");
                    }
                } else {
                    if ($this->sport_model->add() !== false) {
                        $this->success("添加成功！", U("sport/index"));
                    } else {
                        $this->error("添加失败！");
                    }
                }
            } else {
                $this->error($this->sport_model->getError());
            }
        } else {
            if (isset($_GET['id']) && !empty($_GET['id'])) {
                $id = I("get.id", 0, 'intval');
                $data = $this->sport_model->find($id);
            }
            if (!empty($data)) {
                $this->assign('data', $data);
                $this->display(":Sport/edit");
            } else {
                $this->display();
            }
        }

    }

    // 删除导航分类
    public function delete()
    {
        $id = I("get.id", 0, 'intval');
        if ($this->sport_model->where(array('id' => $id))->delete() !== false) {
            $this->success("删除成功！");
        } else {
            $this->error("删除失败！");
        }
    }

    /**
     * 导入数据
     */
    public function importData()
    {
        if (!empty ($_FILES ['file_stu'] ['name'])) {
            $tmp_file = $_FILES ['file_stu'] ['tmp_name'];
            $file_types = explode(".", $_FILES ['file_stu'] ['name']);
            $file_type = $file_types [count($file_types) - 1];
            /*判别是不是.xls文件，判别是不是excel文件*/
            if (strtolower($file_type) != "xls") {
                $this->error('不是Excel文件，重新上传');
            }
            /*设置上传路径*/
            $savePath = SITE_PATH . '/data/upload/sport/';
            /*以时间来命名上传的文件*/
            $str = date('Ymdhis');
            $file_name = $str . "." . $file_type;
            /*是否上传成功*/
            if (!copy($tmp_file, $savePath . $file_name)) {
                $this->error('上传失败');
            }
            /*
               *对上传的Excel数据进行处理生成编程数据,这个函数会在下面第三步的ExcelToArray类中
              注意：这里调用执行了第三步类里面的read函数，把Excel转化为数组并返回给$res,再进行数据库写入
            */
            $res = $this->read($savePath . $file_name);
            /*
                 重要代码 解决Thinkphp M、D方法不能调用的问题
                 如果在thinkphp中遇到M 、D方法失效时就加入下面一句代码
             */
            //spl_autoload_register ( array ('Think', 'autoload' ) );
            /*对生成的数组进行数据库的写入*/
            foreach ($res as $k => $v) {
                if ($k != 1) {
                    if ($v[2] != 0) {
                        //添加运动记录
                        $data ['openid'] = $v[0];
                        $data ['nick_name'] = $v[1];
                        $data ['add_time'] = time();
                        $data ['step_nums'] = $v[2];
                        $result = M('SportRecord')->add($data);
                        if (!$result) {
                            $this->error('导入数据库失败');
                        }
                        //添加腾币记录
                        $coinData ['openid'] = $v[0];
                        $coinData ['type'] = 1;
                        $coinData ['add_time'] = time();
                        $coinData ['coin'] = $v[2];
                        $coinData ['type_id'] = $result;
                        $coinResult = M('CoinRecord')->add($coinData);
                        if (!$coinResult) {
                            $this->error('导入数据库失败');
                        }
                        //更新用户的总腾币
                        $map['user_login'] = $v[0];
                        M('Users')->where($map)->setInc('score', $v[2]);
                    }
                }
            }
            $this->success('导入数据成功', U("sport/index"));
            exit();
        } else {
            $this->error('导入数据失败', U("sport/index"));
            exit();
        }
    }

    /**
     * 导出Excel
     */
    public function pushExcel()
    {
        $data = D('users')->field('user_login as openid, user_nicename as nick_name')->select();
        $this->push($data, '运动记录' . date('Ymd', time()));
    }

    public function read($filename, $encode = 'utf-8')
    {
        include_once(SITE_PATH . '/simplewind/Lib/Util/PHPExcel.php');
        $objReader = PHPExcel_IOFactory::createReader('Excel5');
        $objReader->setReadDataOnly(true);
        $objPHPExcel = $objReader->load($filename);
        $objWorksheet = $objPHPExcel->getActiveSheet();
        $highestRow = $objWorksheet->getHighestRow();
        $highestColumn = $objWorksheet->getHighestColumn();
        $highestColumnIndex = PHPExcel_Cell::columnIndexFromString($highestColumn);
        $excelData = array();
        for ($row = 1; $row <= $highestRow; $row++) {
            for ($col = 0; $col < $highestColumnIndex; $col++) {
                $excelData[$row][] = (string)$objWorksheet->getCellByColumnAndRow($col, $row)->getValue();
            }
        }
        return $excelData;
    }

    public function push($data, $name = 'Excel')
    {
        include_once(SITE_PATH . '/simplewind/Lib/Util/PHPExcel.php');
        error_reporting(E_ALL);
        date_default_timezone_set('Europe/London');
        $objPHPExcel = new \PHPExcel();
        /*以下是一些设置 ，什么作者  标题啊之类的*/
        $objPHPExcel->getProperties()->setCreator("转弯的阳光")
            ->setLastModifiedBy("转弯的阳光")
            ->setTitle("数据EXCEL导出")
            ->setSubject("数据EXCEL导出")
            ->setDescription("备份数据")
            ->setKeywords("excel")
            ->setCategory("result file");
        /*以下就是对处理Excel里的数据， 横着取数据，主要是这一步，其他基本都不要改*/
        $objPHPExcel->setActiveSheetIndex(0)
            ->setCellValue('A1', 'openid')
            ->setCellValue('B1', 'nick_name')
            ->setCellValue('C1', 'step_nums');
        foreach ($data as $k => $v) {
            $num = $k + 2;
            $objPHPExcel->setActiveSheetIndex(0)
                //Excel的第A列，uid是你查出数组的键值，下面以此类推
                ->setCellValue('A' . $num, $v['openid'])
                ->setCellValue('B' . $num, $v['nick_name'])
                ->setCellValue('C' . $num, 0);
        }
        $objPHPExcel->setActiveSheetIndex(0);
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="' . $name . '.xls"');
        header('Cache-Control: max-age=0');
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save('php://output');
        exit;
    }
}