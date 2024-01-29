<?php

namespace app\admin\controller\hj212;

use app\common\controller\Backend;
use app\common\library\Utils;
use function fast\e;
use think\Db;
use think\Exception;
use think\exception\PDOException;
use think\exception\ValidateException;
use app\hj212\segment\converter\DataReverseConverter;
use think\Loader;

/**
 *
 *
 * @icon fa fa-circle-o
 */
class Data extends Backend
{

    /**
     * Data模型对象
     * @var \app\admin\model\hj212\Data
     */
    protected $model = null;
    protected $searchFields = "site_name,cn,mn";
    protected $siteList = array();

    public function _initialize()
    {

        parent::_initialize();
        $this->model = new \app\admin\model\hj212\Data;
        $this->view->assign("statusList", ["0" => __('No'), "1" => __('Yes')]);
        $data_id = $this->request->param('data_id', 0);

        //设置站点列表
        $list = Db::name('hj212_site')->select();
        $site = collection($list)->toArray();
        $siteArr = array();
        foreach ($site as $v) {
            $siteArr[$v['id']] = $v['site_name'];
        }
        $this->siteList = $siteArr;
        $this->assignconfig('siteList', $this->siteList);
        $this->assignconfig('data_id', $data_id);


    }


    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */

    /**
     * 查看
     */
    public function index()
    {
        //设置过滤方法

        $this->request->filter(['strip_tags', 'trim']);
        $siteArr = $this->siteList;
        $filter = $this->request->get("filter", '');
        $filterArr = json_decode($filter, true);
        $limit = $this->request->get("limit/d", 999999);
        if (isset($filterArr['cp_datatime'])) {
            $datatimearr = explode(' - ', $filterArr['cp_datatime']);
            $start = date("Y-m", strtotime($datatimearr[0]));
            $end = date("Y-m", strtotime($datatimearr[1]));
            $suffixArr = Utils::getYMRange($start, $end);
        } else {
            $suffixArr[] = date("Ym");
        }
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
//            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $f = 0;
            $query = "";
            $unionQuery = array();
            foreach ($suffixArr as $suffix) {
                //需求处理月份 不存在的表
                $tableName = "hj212_data_" . $suffix;
                $sqlTableName = "fa_" . $tableName;
                $isExist = Utils::isTableExist($tableName);
                if (!$isExist) {
                    continue;
                }
                if ($f == 0) {
                    $where = $this->getWhere($tableName);

                    $query = DB::name($tableName)
                        ->field("{$sqlTableName}.id as id,qn,cn,mn,cp_datatime,site_name,is_alarm,{$sqlTableName}.created_at as created_at")
                        ->join('hj212_device', "{$sqlTableName}.mn=hj212_device.device_code", 'left')
                        ->join('hj212_site', 'hj212_device.site_id=hj212_site.id', 'left')
                        ->where("cn", "in", ["2011", "2051", "2061", "2031"]);
//                        ->where($where);
                    foreach ($where as $k => $v) {
                        if (is_array($v)) {
                            call_user_func_array([$query, 'where'], $v);
                        } else {
                            $query->where($v);
                        }
                    }


                } else {

                    $where = $this->getWhere($tableName);
                    $query1 = DB::name($tableName)
                        ->field("{$sqlTableName}.id as id,qn,cn,mn,cp_datatime,site_name,is_alarm,{$sqlTableName}.created_at as created_at")
                        ->join('hj212_device', "{$sqlTableName}.mn=hj212_device.device_code", 'left')
                        ->join('hj212_site', 'hj212_device.site_id=hj212_site.id', 'left')
                        ->where("cn", "in", ["2011", "2051", "2061", "2031"]);
//                        ->where($where);
                    foreach ($where as $k => $v) {
                        if (is_array($v)) {
                            call_user_func_array([$query1, 'where'], $v);
                        } else {
                            $query1->where($v);
                        }
                    }
                    $unionQuery[] = $query1->buildSql();

                }
                $f++;
            }
            if($unionQuery){
                $query = $query->union($unionQuery,true);
            }

            if ($query) {
                $list = $query->order('id',"desc")
                    ->paginate($limit);
            } else {
                $count = 0;
                $list = [];
            }

            $result = array("total" => $list->total(), "rows" => $list->items());

            return json($result);
        }
        return $this->view->fetch();
    }

    /**
     * 添加
     */
    public function add()
    {
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                $params = $this->preExcludeFields($params);

                if ($this->dataLimit && $this->dataLimitFieldAutoFill) {
                    $params[$this->dataLimitField] = $this->auth->id;
                }
                $result = false;
                Db::startTrans();
                try {
                    //是否采用模型验证
                    if ($this->modelValidate) {
                        $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                        $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.add' : $name) : $this->modelValidate;
                        $this->model->validateFailException(true)->validate($validate);
                    }
                    //获取设备密码
                    $device = \app\admin\model\hj212\Device::where('device_code', $params['mn'])->field('device_pwd')->find();
                    $params['pw'] = isset($device->device_pwd) ? $device->device_pwd : '';
                    //获取当前时间
                    $currentDate = date('YmdHis', time());
                    $params['cp_datatime'] = $currentDate;

                    $result = $this->model->allowField(true)->save($params);
                    Db::commit();
                } catch (ValidateException $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                } catch (PDOException $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                } catch (Exception $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                }
                if ($result !== false) {
                    $this->success();
                } else {
                    $this->error(__('No rows were inserted'));
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        return $this->view->fetch();
    }

    /**
     * 编辑
     */
    public
    function edit($ids = null)
    {
        $row = $this->model->get($ids);
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        $adminIds = $this->getDataLimitAdminIds();
        if (is_array($adminIds)) {
            if (!in_array($row[$this->dataLimitField], $adminIds)) {
                $this->error(__('You have no permission'));
            }
        }
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                $params = $this->preExcludeFields($params);
                $result = false;
                Db::startTrans();
                try {
                    //是否采用模型验证
                    if ($this->modelValidate) {
                        $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                        $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.edit' : $name) : $this->modelValidate;
                        $row->validateFailException(true)->validate($validate);
                    }
                    $data = new DataReverseConverter();

                    //获取设备密码
                    $device = \app\admin\model\hj212\Device::where('device_code', $params['mn'])->field('device_pwd')->find();
                    print_r($params);
                    die;
                    $params['pw'] = isset($device->device_pwd) ? $device->device_pwd : '';
                    //获取当前时间
                    $currentDate = date('YmdHis', time());
                    $params['cp_datatime'] = $currentDate;

                    $result = $row->allowField(true)->save($params);
                    Db::commit();
                } catch (ValidateException $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                } catch (PDOException $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                } catch (Exception $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                }
                if ($result !== false) {
                    $this->success();
                } else {
                    $this->error(__('No rows were updated'));
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $this->view->assign("row", $row);
        return $this->view->fetch();
    }

    /**
     * 数据分析
     */
    public function analysisdata()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags', 'trim']);
        $id = $this->request->param('data_id', 0);
        $device_code = $this->request->param('mn', 0);
        $time = $this->request->param('time', date("Ym"));
        if (trim($time, " ") == "null") {
            $time = date("Ym");
        }
        $suffix = substr($time, 0, 6);
//        $list = $this->model
//            ->where(function ($query) use ($Id) {
//                if ($Id) {
//                    $query->where('id', $Id);
//                }
//            })
//            ->find();
//        $site = '';
//        $pollutionInfo = array();
//        if ($list) {
//            $device_code = $list['mn'];
        $site = Db::name('hj212_device')->alias("device")
            ->join("fa_hj212_site site", "site.id = device.site_id", "left")
            ->where(['device.device_code' => $device_code])
            ->find();


        //获取监测因子信息
        $pollutionInfo = Db::name('hj212_pollution_' . $suffix)->alias('p')
            ->join("hj212_pollution_code c", "c.code = p.code", "LEFT")
            ->join("hj212_alarm a", "a.code = c.code", "LEFT")
            ->where(['p.data_id' => $id])
            ->field('p.code,a.*,c.*,p.max,p.min,p.avg,p.rtd')
            ->select();

//        }
//        var_dump($pollutionInfo);
        $this->view->assign('mn', $device_code);
        $this->view->assign('pollutionsite', $site);
        $this->view->assign('pollutionInfo', $pollutionInfo);

        return $this->view->fetch();
    }


    private
    function getWhere($tableName, $searchfields = null, $relationSearch = null)
    {


        $searchfields = is_null($searchfields) ? $this->searchFields : $searchfields;
        $relationSearch = is_null($relationSearch) ? $this->relationSearch : $relationSearch;
        $search = $this->request->get("search", '');
        $filter = $this->request->get("filter", '');
        $op = $this->request->get("op", '', 'trim');
        //       $sort = $this->request->get("sort", !empty($this->model) && $this->model->getPk() ? $this->model->getPk() : 'id');
        $order = $this->request->get("order", "DESC");
        $offset = $this->request->get("offset/d", 0);
        $limit = $this->request->get("limit/d", 999999);
        $aliasName = "";

        if (!empty($this->model) && $this->relationSearch) {
            $name = Db::name($tableName)->getTable();
            $alias[$name] = Loader::parseName(basename(str_replace('\\', '/', get_class($this->model))));
            $aliasName = $alias[$name] . '.';
        }

        $adminIds = $this->getDataLimitAdminIds();
        if (is_array($adminIds)) {
            $where[] = [$aliasName . $this->dataLimitField, 'in', $adminIds];
        }
        if ($search) {
            $searcharr = is_array($searchfields) ? $searchfields : explode(',', $searchfields);
            foreach ($searcharr as $k => &$v) {
                $v = stripos($v, ".") === false ? $aliasName . $v : $v;
            }
            unset($v);
            $where[] = [implode("|", $searcharr), "LIKE", "%{$search}%"];
        }
        $filter = (array)json_decode($filter, true);
        $op = (array)json_decode($op, true);
        $filter = $filter ? $filter : [];
        $where = [];
        if ($search) {
            $searcharr = is_array($searchfields) ? $searchfields : explode(',', $searchfields);
            foreach ($searcharr as $k => &$v) {
                $v = stripos($v, ".") === false ? $aliasName . $v : $v;
            }
            unset($v);
            $where[] = [implode("|", $searcharr), "LIKE", "%{$search}%"];
        }
//        var_dump($where);exit;
        $index = 0;
        foreach ($filter as $k => $v) {
            if (!preg_match('/^[a-zA-Z0-9_\-\.]+$/', $k)) {
                continue;
            }
            $sym = isset($op[$k]) ? $op[$k] : '=';
            if (stripos($k, ".") === false) {
                $k = $aliasName . $k;
            }
            $v = !is_array($v) ? trim($v) : $v;
            $sym = strtoupper(isset($op[$k]) ? $op[$k] : $sym);
            //null和空字符串特殊处理
            if (!is_array($v)) {
                if (in_array(strtoupper($v), ['NULL', 'NOT NULL'])) {
                    $sym = strtoupper($v);
                }
                if (in_array($v, ['""', "''"])) {
                    $v = '';
                    $sym = '=';
                }
            }

            switch ($sym) {
                case '=':
                case '<>':
                    $where[] = [$k, $sym, (string)$v];
                    break;
                case 'LIKE':
                case 'NOT LIKE':
                case 'LIKE %...%':
                case 'NOT LIKE %...%':
                    $where[] = [$k, trim(str_replace('%...%', '', $sym)), "%{$v}%"];
                    break;
                case '>':
                case '>=':
                case '<':
                case '<=':
                    $where[] = [$k, $sym, intval($v)];
                    break;
                case 'FINDIN':
                case 'FINDINSET':
                case 'FIND_IN_SET':
                    $v = is_array($v) ? $v : explode(',', str_replace(' ', ',', $v));
                    $findArr = array_values($v);
                    foreach ($findArr as $idx => $item) {
                        $bindName = "item_" . $index . "_" . $idx;
                        $bind[$bindName] = $item;
                        $where[] = "FIND_IN_SET(:{$bindName}, `" . str_replace('.', '`.`', $k) . "`)";
                    }
                    break;
                case 'IN':
                case 'IN(...)':
                case 'NOT IN':
                case 'NOT IN(...)':
                    $where[] = [$k, str_replace('(...)', '', $sym), is_array($v) ? $v : explode(',', $v)];
                    break;
                case 'BETWEEN':
                case 'NOT BETWEEN':
                    $arr = array_slice(explode(',', $v), 0, 2);
                    if (stripos($v, ',') === false || !array_filter($arr)) {
                        continue 2;
                    }
                    //当出现一边为空时改变操作符
                    if ($arr[0] === '') {
                        $sym = $sym == 'BETWEEN' ? '<=' : '>';
                        $arr = $arr[1];
                    } elseif ($arr[1] === '') {
                        $sym = $sym == 'BETWEEN' ? '>=' : '<';
                        $arr = $arr[0];
                    }
                    $where[] = [$k, $sym, $arr];
                    break;
                case 'RANGE':
                case 'NOT RANGE':
                    $v = str_replace(' - ', ',', $v);
                    $arr = array_slice(explode(',', $v), 0, 2);
                    if (stripos($v, ',') === false || !array_filter($arr)) {
                        continue 2;
                    }
                    //当出现一边为空时改变操作符
                    if ($arr[0] === '') {
                        $sym = $sym == 'RANGE' ? '<=' : '>';
                        $arr = $arr[1];
                    } elseif ($arr[1] === '') {
                        $sym = $sym == 'RANGE' ? '>=' : '<';
                        $arr = $arr[0];
                    }
                    $tableArr = explode('.', $k);
                    if (count($tableArr) > 1 && $tableArr[0] != $name && !in_array($tableArr[0], $alias) && !empty($this->model)) {
                        //修复关联模型下时间无法搜索的BUG
                        $relation = Loader::parseName($tableArr[0], 1, false);
                        $alias[$this->model->$relation()->getTable()] = $tableArr[0];
                    }
                    $where[] = [$k, str_replace('RANGE', 'BETWEEN', $sym) . ' TIME', $arr];
                    break;
                case 'NULL':
                case 'IS NULL':
                case 'NOT NULL':
                case 'IS NOT NULL':
                    $where[] = [$k, strtolower(str_replace('IS ', '', $sym))];
                    break;
                default:
                    break;
            }
            $index++;
        }
        return $where;
    }
}
