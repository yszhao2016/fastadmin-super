<?php

namespace app\admin\controller\flow;

use think\Db;
use think\Config;
use app\common\controller\Backend;
use think\console\Input;

/**
 *
 *
 * @icon fa fa-circle-o
 */
class Bizscheme extends Backend
{

    /**
     * Bizscheme模型对象
     * @var \app\admin\model\flow\Bizscheme
     */
    protected $model = null;
    protected $scheme = null;
    protected $noNeedRight = ['*'];

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\flow\Bizscheme;
        $this->scheme = new \app\admin\model\flow\Scheme;
        $schemeId = $this->request->request('ids');
    }

    /**
     * 查看
     */
    public function index()
    {
        //当前是否为关联查询
        $this->relationSearch = false;
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        $schemeId = $this->request->request('ids');
        $ins = $this->scheme->get($schemeId);
        if ($this->request->isAjax()) {
            $schemeId = $this->request->request('scheme_id');
            $ins = $this->scheme->get($schemeId);
            //如果发送的来源是Selectpage，则转发到Selectpage
            $dbname = Config::get('database.database');
            $prefix = Config::get('database.prefix');
            //从数据库中获取表字段信息
            $sql = "SELECT COLUMN_KEY,COLUMN_NAME,COLUMN_TYPE,COLUMN_COMMENT,COLUMN_DEFAULT FROM `information_schema`.`columns` "
                . "WHERE TABLE_SCHEMA = ? AND table_name = ? "
                . "ORDER BY ORDINAL_POSITION";
            //加载主表的列
            $columnList = Db::query($sql, [$dbname, $ins['bizscheme']]);
            $total = count($columnList);
            $list = $columnList;

            $list = collection($list)->toArray();
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        $this->view->assign('scheme_id', $this->request->request('ids'));
        $this->view->assign('tableName', $ins['bizscheme']);
        return $this->view->fetch();
    }

    public function add()
    {
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            $schemeId = $this->request->request('scheme_id');
            $ins = $this->scheme->get($schemeId);
            $bizScheme = $ins['bizscheme'];

            if ($params) {
                if ($this->dataLimit && $this->dataLimitFieldAutoFill) {
                    $params[$this->dataLimitField] = $this->auth->id;
                }
                try {
                    $dbname = Config::get('database.database');
                    $prefix = Config::get('database.prefix');
                    //从数据库中获取表字段信息
                    $sql = "SELECT COLUMN_KEY,COLUMN_NAME,COLUMN_TYPE,COLUMN_COMMENT,COLUMN_DEFAULT FROM `information_schema`.`columns` "
                        . "WHERE TABLE_SCHEMA = ? AND table_name = ? and COLUMN_NAME=? "
                        . "ORDER BY ORDINAL_POSITION";
                    //加载主表的列
                    $row = Db::query($sql, [$dbname, $ins['bizscheme'], $params['fieldcode']]);
                    if ($row) {
                        $this->error('字段已经存在,请更改字段名称');
                    }

                    $sql = "ALTER TABLE $bizScheme  add ( ";

                    switch (strtolower($params['type'])) {
                        case "enum":
                        case "set":
                            $code = explode(',', $params['default']);
                            $res = "";
                            foreach ($code as $l) {
                                $res = $res . "'" . $l . "'" . ",";
                            }
                            $res = rtrim($res, ',');
                            $sql .= ' ' . $params['fieldcode'] . ' ' . $params['type'] . "($res) " . " COMMENT '" . $params['fieldname'] . "',";
                            break;
                        case "text":
                        case "datetime":
                            $sql .= ' ' . $params['fieldcode'] . ' ' . $params['type'] . " COMMENT '" . $params['fieldname'] . "',";
                            break;
                        case "int":
                            $defaultvalue = is_numeric($params['default']) == false ? 0 : $params['default'];
                            $sql .= ' ' . $params['fieldcode'] . ' ' . $params['type'] . " default '" . $defaultvalue . "' COMMENT '" . $params['fieldname'] . "',";
                            break;
                        default:
                            $sql .= ' ' . $params['fieldcode'] . ' ' . $params['type'] . " default '" . $params['default'] . "' COMMENT '" . $params['fieldname'] . "',";
                            break;
                    }

                    $sql = rtrim($sql, ',');
                    $sql = $sql . ")";
                    $result = Db::execute($sql);
                    if ($result !== false) {
                        $this->success();
                    } else {
                        $this->error($this->model->getError());
                    }
                } catch (\think\exception\PDOException $e) {
                    $this->error($e->getMessage());
                } catch (\think\Exception $e) {
                    $this->error($e->getMessage());
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $this->view->assign('scheme_id', $this->request->request('scheme_id'));
        return $this->view->fetch();
    }

    public function edit($ids = null)
    {
        $column = $this->request->request('ids');
        $schemeId = $this->request->request('scheme_id');
        $ins = $this->scheme->get($schemeId);
        //如果发送的来源是Selectpage，则转发到Selectpage
        $dbname = Config::get('database.database');
        $prefix = Config::get('database.prefix');
        //从数据库中获取表字段信息
        $sql = "SELECT COLUMN_KEY,COLUMN_NAME,COLUMN_TYPE,COLUMN_COMMENT,COLUMN_DEFAULT FROM `information_schema`.`columns` "
            . "WHERE TABLE_SCHEMA = ? AND table_name = ? and COLUMN_NAME=? "
            . "ORDER BY ORDINAL_POSITION";
        //加载主表的列
        $row = Db::query($sql, [$dbname, $ins['bizscheme'], $column]);
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        $row = $row[0];
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                try {
                    $bizScheme = $ins['bizscheme'];
                    $sql = "ALTER TABLE $bizScheme  ";
                    switch (strtolower($params['COLUMN_TYPE'])) {
                        case "enum":
                        case "set":
                            $code = explode(',', $params['COLUMN_DEFAULT']);
                            $res = "";
                            foreach ($code as $l) {
                                $res = $res . "'" . $l . "'" . ",";
                            }
                            $res = rtrim($res, ',');
                            $sql .= ' modify column ' . $params['COLUMN_NAME'] . ' ' . $params['COLUMN_TYPE'] . "($res)," . " COMMENT '" . $params['COLUMN_COMMENT'] . "'";
                            break;
                        case "int":
                            $defaultvalue = is_numeric($params['default']) == false ? 0 : $params['default'];
                            $sql .= ' modify column ' . $params['COLUMN_NAME'] . ' ' . $params['COLUMN_TYPE'] . "   default '" . $defaultvalue . "' COMMENT '" . $params['COLUMN_COMMENT'] . "'";
                            break;
                        case "text":
                        case "datetime":
                            $sql .= ' modify column ' . $params['COLUMN_NAME'] . ' ' . $params['COLUMN_TYPE'] . " COMMENT '" . $params['COLUMN_COMMENT'] . "'";
                            break;
                        default:
                            $sql .= ' modify column ' . $params['COLUMN_NAME'] . ' ' . $params['COLUMN_TYPE'] . "   default '" . $params['COLUMN_DEFAULT'] . "' COMMENT '" . $params['COLUMN_COMMENT'] . "'";
                            break;
                    }
                    $result = Db::execute($sql);
                    if ($result !== false) {
                        $this->success();
                    } else {
                        $this->error($row->getError());
                    }
                } catch (\think\exception\PDOException $e) {
                    $this->error($e->getMessage());
                } catch (\think\Exception $e) {
                    $this->error($e->getMessage());
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $this->view->assign("row", $row);
        return $this->view->fetch();
    }

    public function del($ids = "")
    {
        $schemeId = $this->request->request('scheme_id');
        $columnname = $this->request->request('ids');
        $ins = $this->scheme->get($schemeId);
        $bizScheme = $ins['bizscheme'];
        $sql = "ALTER TABLE $bizScheme  drop column $columnname";
        $result = Db::execute($sql);
        if ($result !== false) {
            $this->success();
        } else {
            $this->error($this->model->getError());
        }
    }

    public function Release()
    {
        //php think crud --table=fa_admin --controller=flow/bizscheme --model=flow/bizscheme
        $prefix = Config::get('database.prefix');
        $msg = "";
        $schemeId = $this->request->request('scheme_id');
        $ins = $this->scheme->get($schemeId);
        $bizScheme = strtolower($ins['bizscheme']);
        $flowcode = strtolower($ins['flowcode']);
        $result = null;
        $commandtype = 'FlowCrud';
        $argv = array("--table=$bizScheme", "--controller=flow/$flowcode", "--model=flow/$flowcode");
        array_push($argv, "-force=1");
        $commandName = "\\app\\admin\\command\\" . ucfirst($commandtype);
        $input = new Input($argv);
        $output = new \app\admin\model\flow\Output();
        $command = new $commandName($commandtype);
        $data = [
            'type'        => $commandtype,
            'params'      => json_encode($argv),
            'command'     => "php think {$commandtype} " . implode(' ', $argv),
            'executetime' => time(),
        ];
        //Command::save($data);

        $command->run($input, $output);
        $result = implode("\n", $output->getMessage());
        Db::commit();
        if (strpos($result, 'Successed') == true) {
            $msg = "发布成功";
            $this->scheme->where('id', $schemeId)->update(['isenable' => '1']);
        } else {
            $msg = $result;
        }
        $result = array("code" => 1, "msg" => $msg);
        return json($result);
    }
}
