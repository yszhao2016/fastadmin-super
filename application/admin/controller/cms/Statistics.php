<?php

namespace app\admin\controller\cms;

use addons\cms\model\SearchLog;
use app\admin\model\User;
use app\common\controller\Backend;

/**
 * 统计控制台管理
 *
 * @icon fa fa-bar-chart
 */
class Statistics extends Backend
{

    /**
     * Archives模型对象
     */
    protected $model = null;
    protected $noNeedRight = [];
    protected $isSuperAdmin = false;
    protected $searchFields = 'id,title';

    /**
     * 查看
     */
    public function index()
    {
        try {
            \think\Db::execute("SET @@sql_mode='';");
        } catch (\Exception $e) {

        }
        $config = get_addon_config('cms');
        if ($this->request->isPost()) {
            $date = $this->request->post('date', '');
            $type = $this->request->post('type', '');
            if ($type == 'sale') {
                list($orderSaleCategory, $orderSaleAmount, $orderSaleNums) = $this->getSaleStatisticsData($date);
                $statistics = ['orderSaleCategory' => $orderSaleCategory, 'orderSaleAmount' => $orderSaleAmount, 'orderSaleNums' => $orderSaleNums];
            } elseif ($type == 'percent') {
                list($orderPercentCategory, $orderPercentAmount, $orderPercentNums) = $this->getPercentStatisticsData($date);
                $statistics = ['orderPercentCategory' => $orderPercentCategory, 'orderPercentAmount' => $orderPercentAmount, 'orderPercentNums' => $orderPercentNums];
            } elseif ($type == 'order') {
                list($category, $data) = $this->getOrderStatisticsData($date);
                $statistics = ['category' => $category, 'data' => $data];
            } elseif ($type == 'archives') {
                list($category, $data) = $this->getArchivesStatisticsData($date);
                $statistics = ['category' => $category, 'data' => $data];
            }
            $this->success('', '', $statistics);
        }

        //管理员发文统计图表
        list($category, $data) = $this->getArchivesStatisticsData('');
        $this->assignconfig('adminArchivesListCategory', $category);
        $this->assignconfig('adminArchivesListData', $data);

        //今日订单和会员
        $totalOrderAmount = round(\app\admin\model\cms\Order::where('status', 'paid')->sum('payamount'), 2);
        $yesterdayOrderAmount = round(\app\admin\model\cms\Order::where('status', 'paid')->whereTime('paytime', 'yesterday')->sum('payamount'), 2);
        $todayOrderAmount = round(\app\admin\model\cms\Order::where('status', 'paid')->whereTime('paytime', 'today')->sum('payamount'), 2);
        $todayOrderRatio = $yesterdayOrderAmount > 0 ? ceil((($todayOrderAmount - $yesterdayOrderAmount) / $yesterdayOrderAmount) * 100) : ($todayOrderAmount > 0 ? 100 : 0);

        $totalUser = User::count();
        $yesterdayUser = User::whereTime('jointime', 'yesterday')->count();
        $todayUser = User::whereTime('jointime', 'today')->count();
        $todayUserRatio = $yesterdayUser > 0 ? ceil((($todayUser - $yesterdayUser) / $yesterdayUser) * 100) : ($todayUser > 0 ? 100 : 0);

        //文档和评论统计
        $totalArchives = \app\admin\model\cms\Archives::count();
        $unsettleArchives = \app\admin\model\cms\Archives::where('status', 'hidden')->count();
        $totalComment = \app\admin\model\cms\Comment::count();
        $unsettleComment = \app\admin\model\cms\Comment::where('status', 'hidden')->count();
        $diyformList = \app\admin\model\cms\Diyform::all();
        foreach ($diyformList as $index => $item) {
            $item->nums = \think\Db::name($item['table'])->count();
        }

        //订单数和订单额统计
        list($orderSaleCategory, $orderSaleAmount, $orderSaleNums) = $this->getSaleStatisticsData();
        $this->assignconfig('orderSaleCategory', $orderSaleCategory);
        $this->assignconfig('orderSaleAmount', $orderSaleAmount);
        $this->assignconfig('orderSaleNums', $orderSaleNums);

        //订单占比统计
        list($orderPercentCategory, $orderPercentAmount, $orderPercentNums) = $this->getPercentStatisticsData();
        $this->assignconfig('orderPercentCategory', $orderPercentCategory);
        $this->assignconfig('orderPercentAmount', $orderPercentAmount);
        $this->assignconfig('orderPercentNums', $orderPercentNums);

        //热门标签
        $tagList = \app\admin\model\cms\Tag::order('nums', 'desc')->limit(10)->select();
        $tagsTotal = 0;
        foreach ($tagList as $index => $item) {
            $tagsTotal += $item['nums'];
        }
        foreach ($tagList as $index => $item) {
            $item['percent'] = $tagsTotal > 0 ? round($item['nums'] / $tagsTotal * 100, 2) : 0;
        }

        //热门搜索列表
        $hotSearchList = SearchLog::order('nums', 'desc')->cache(3600)->limit(10)->select();
        $hotTagList = \addons\cms\model\Tag::order('nums', 'desc')->cache(3600)->limit(10)->select();
        $hotArchivesList = \addons\cms\model\Archives::order('views', 'desc')->cache(3600)->limit(10)->select();

        //付费排行榜
        $todayPaidTotal = \app\admin\model\cms\Order::whereTime('paytime', 'today')->sum("payamount");
        $todayPaidList = \app\admin\model\cms\Order::with(['archives'])->whereTime('paytime', 'today')->group('archives_id')->field("COUNT(*) as nums,SUM(payamount) as amount,archives_id")->order("amount", "desc")->limit(10)->select();
        foreach ($todayPaidList as $index => $item) {
            $item->percent = $todayPaidTotal > 0 ? round(($item['amount'] / $todayPaidTotal) * 100, 2) : 0;
        }

        $weekPaidTotal = \app\admin\model\cms\Order::whereTime('paytime', 'week')->sum("payamount");
        $weekPaidList = \app\admin\model\cms\Order::with(['archives'])->whereTime('paytime', 'week')->group('archives_id')->field("COUNT(*) as nums,SUM(payamount) as amount,archives_id")->order("amount", "desc")->limit(10)->select();
        foreach ($weekPaidList as $index => $item) {
            $item->percent = $weekPaidTotal > 0 ? round(($item['amount'] / $weekPaidTotal) * 100, 2) : 0;
        }

        $monthPaidTotal = \app\admin\model\cms\Order::whereTime('paytime', 'month')->sum("payamount");
        $monthPaidList = \app\admin\model\cms\Order::with(['archives'])->whereTime('paytime', 'month')->group('archives_id')->field("COUNT(*) as nums,SUM(payamount) as amount,archives_id")->order("amount", "desc")->limit(10)->select();
        foreach ($monthPaidList as $index => $item) {
            $item->percent = $monthPaidTotal > 0 ? round(($item['amount'] / $monthPaidTotal) * 100, 2) : 0;
        }

        //投稿排行榜
        $todayContributeTotal = \app\admin\model\cms\Archives::whereTime('createtime', 'today')->count();
        $todayContributeList = \app\admin\model\cms\Archives::with(['user'])->where('user_id', '>', 0)->whereTime('createtime', 'today')->group('user_id')->field("COUNT(*) as nums,user_id")->order("nums", "desc")->limit(10)->select();
        foreach ($todayContributeList as $index => $item) {
            $item->percent = $todayContributeTotal > 0 ? round(($item['nums'] / $todayContributeTotal) * 100, 2) : 0;
        }

        $weekContributeTotal = \app\admin\model\cms\Archives::whereTime('createtime', 'week')->count();
        $weekContributeList = \app\admin\model\cms\Archives::with(['user'])->where('user_id', '>', 0)->whereTime('createtime', 'week')->group('user_id')->field("COUNT(*) as nums,user_id")->order("nums", "desc")->limit(10)->select();
        foreach ($weekContributeList as $index => $item) {
            $item->percent = $weekContributeTotal > 0 ? round(($item['nums'] / $weekContributeTotal) * 100, 2) : 0;
        }

        $monthContributeTotal = \app\admin\model\cms\Archives::whereTime('createtime', 'month')->count();
        $monthContributeList = \app\admin\model\cms\Archives::with(['user'])->where('user_id', '>', 0)->whereTime('createtime', 'month')->group('user_id')->field("COUNT(*) as nums,user_id")->order("nums", "desc")->limit(10)->select();
        foreach ($monthContributeList as $index => $item) {
            $item->percent = $monthContributeTotal > 0 ? round(($item['nums'] / $monthContributeTotal) * 100, 2) : 0;
        }

        $this->view->assign("totalOrderAmount", $totalOrderAmount);
        $this->view->assign("yesterdayOrderAmount", $yesterdayOrderAmount);
        $this->view->assign("todayOrderAmount", $todayOrderAmount);
        $this->view->assign("todayOrderRatio", $todayOrderRatio);

        $this->view->assign("totalUser", $totalUser);
        $this->view->assign("yesterdayUser", $yesterdayUser);
        $this->view->assign("todayUser", $todayUser);
        $this->view->assign("todayUserRatio", $todayUserRatio);

        $this->view->assign("totalArchives", $totalArchives);
        $this->view->assign("unsettleArchives", $unsettleArchives);
        $this->view->assign("totalComment", $totalComment);
        $this->view->assign("unsettleComment", $unsettleComment);

        $this->view->assign("tagsList", $tagList);
        $this->view->assign("hotTagList", $hotTagList);
        $this->view->assign("hotArchivesList", $hotArchivesList);
        $this->view->assign("hotSearchList", $hotSearchList);

        $this->view->assign("todayPaidList", $todayPaidList);
        $this->view->assign("weekPaidList", $weekPaidList);
        $this->view->assign("monthPaidList", $monthPaidList);

        $this->view->assign("todayContributeList", $todayContributeList);
        $this->view->assign("weekContributeList", $weekContributeList);
        $this->view->assign("monthContributeList", $monthContributeList);

        $this->view->assign("modelList", \app\admin\model\cms\Modelx::order('id asc')->select());

        return $this->view->fetch();
    }

    /**
     * 获取订单销量销售额统计数据
     * @param string $date
     * @return array
     */
    protected function getSaleStatisticsData($date = '')
    {
        $starttime = \fast\Date::unixtime();
        $endtime = \fast\Date::unixtime('day', 0, 'end');

        $format = '%H:00';

        $orderList = \app\admin\model\cms\Order::where('paytime', 'between time', [$starttime, $endtime])
            ->field('paytime, status, COUNT(*) AS nums, SUM(payamount) AS amount, MIN(paytime) AS min_paytime, MAX(paytime) AS max_paytime, 
            DATE_FORMAT(FROM_UNIXTIME(paytime), "' . $format . '") AS paydate')
            ->group('paydate')
            ->select();
        $column = [];
        for ($time = $starttime; $time <= $endtime;) {
            $column[] = date("H:00", $time);
            $time += 3600;
        }
        $orderSaleNums = $orderSaleAmount = array_fill_keys($column, 0);
        foreach ($orderList as $k => $v) {
            $orderSaleNums[$v['paydate']] = $v['nums'];
            $orderSaleAmount[$v['paydate']] = round($v['amount'], 2);
        }
        $orderSaleCategory = array_keys($orderSaleAmount);
        $orderSaleAmount = array_values($orderSaleAmount);
        $orderSaleNums = array_values($orderSaleNums);
        return [$orderSaleCategory, $orderSaleAmount, $orderSaleNums];
    }

    /**
     * 获取订单占比统计数据
     * @param string $date
     * @return array
     */
    protected function getPercentStatisticsData($date = '')
    {
        $starttime = \fast\Date::unixtime();
        $endtime = \fast\Date::unixtime('day', 0, 'end');
        $modelList = [];
        $orderPercentCategory = $orderPercentAmount = $orderPercentNums = [];
        $list = \app\admin\model\cms\Order::with('archives')
            ->where('order.createtime', 'between time', [$starttime, $endtime])
            ->where('order.status', 'paid')
            ->field("archives.model_id,SUM(payamount) as amount,COUNT(*) as nums")
            ->group('archives.model_id')
            ->select();
        foreach ($list as $index => $item) {
            $modelList[$item['archives']['model']['name']] = $item['amount'];
            $name = $item['archives']['model']['name'];
            $name = $name ? $name : "其它";
            $orderPercentCategory[] = $name;
            $orderPercentAmount[] = ['value' => round($item['amount'], 2), 'name' => $name];
            $orderPercentNums[] = ['value' => $item['nums'], 'name' => $name];
        }
        if (!$orderPercentCategory) {
            $orderPercentCategory = [""];
            $orderPercentNums = [['value' => 0, 'name' => '订单数']];
            $orderPercentAmount = [['value' => 0, 'name' => '订单额']];
        }
        return [$orderPercentCategory, $orderPercentAmount, $orderPercentNums];
    }

    /**
     * 获取订单统计数据
     * @param string $date
     * @return array
     */
    protected function getOrderStatisticsData($date = '')
    {
        if ($date) {
            list($start, $end) = explode(' - ', $date);

            $starttime = strtotime($start);
            $endtime = strtotime($end);
        } else {
            $starttime = \fast\Date::unixtime('day', 0, 'begin');
            $endtime = \fast\Date::unixtime('day', 0, 'end');
        }
        $totalseconds = $endtime - $starttime;

        $format = '%Y-%m-%d';
        if ($totalseconds > 86400 * 30 * 2) {
            $format = '%Y-%m';
        } else {
            if ($totalseconds > 86400) {
                $format = '%Y-%m-%d';
            } else {
                $format = '%H:00';
            }
        }

        $model_id = $this->request->post("model_id");
        $orderList = \app\admin\model\cms\Order::where('paytime', 'between time', [$starttime, $endtime])
            ->where(function ($query) use ($model_id, $starttime, $endtime) {
                if ($model_id) {
                    //如果指定模型
                    $query->where('archives_id', 'in', function ($query) use ($model_id, $starttime, $endtime) {
                        $query->name("cms_archives")->where('model_id', $model_id)->where('id', 'in', function ($query) use ($starttime, $endtime) {
                            $query->name('cms_order')->where('paytime', 'between time', [$starttime, $endtime])->field('archives_id');
                        })->field('id');
                    });
                }
            })
            ->field('paytime, status, SUM(payamount) AS amount, MIN(paytime) AS min_paytime, MAX(paytime) AS max_paytime, 
            DATE_FORMAT(FROM_UNIXTIME(createtime), "' . $format . '") AS pay_date')
            ->group('pay_date')
            ->select();

        if ($totalseconds > 84600 * 30 * 2) {
            $starttime = strtotime('last month', $starttime);
            while (($starttime = strtotime('next month', $starttime)) <= $endtime) {
                $column[] = date('Y-m', $starttime);
            }
        } else {
            if ($totalseconds > 86400) {
                for ($time = $starttime; $time <= $endtime;) {
                    $column[] = date("Y-m-d", $time);
                    $time += 86400;
                }
            } else {
                for ($time = $starttime; $time <= $endtime;) {
                    $column[] = date("H:00", $time);
                    $time += 3600;
                }
            }
        }
        $list = array_fill_keys($column, 0);
        foreach ($orderList as $k => $v) {
            $list[$v['pay_date']] = round($v['amount'], 2);
        }
        $category = array_keys($list);
        $data = array_values($list);
        return [$category, $data];

    }

    /**
     * 获取发文统计数据
     * @param string $date
     * @return array
     */
    protected function getArchivesStatisticsData($date = '')
    {
        if ($date) {
            list($start, $end) = explode(' - ', $date);

            $starttime = strtotime($start);
            $endtime = strtotime($end);
        } else {
            $starttime = \fast\Date::unixtime('day', 0, 'begin');
            $endtime = \fast\Date::unixtime('day', 0, 'end');
        }
        $totalseconds = $endtime - $starttime;

        $format = '%Y-%m-%d';
        if ($totalseconds > 86400 * 30 * 2) {
            $format = '%Y-%m';
        } else {
            if ($totalseconds > 86400) {
                $format = '%Y-%m-%d';
            } else {
                $format = '%H:00';
            }
        }
        $model_id = $this->request->post("model_id", "");
        $archivesList = \app\admin\model\cms\Archives::with(["admin"])
            ->where('createtime', 'between time', [$starttime, $endtime])
            ->where(function ($query) use ($model_id) {
                if ($model_id) {
                    $query->where('model_id', $model_id);
                }
            })
            ->field('admin_id, createtime, status, COUNT(*) AS nums, MIN(createtime) AS min_createtime, MAX(createtime) AS max_createtime, 
            DATE_FORMAT(FROM_UNIXTIME(createtime), "' . $format . '") AS create_date')
            ->group('admin_id,create_date')
            ->select();

        if ($totalseconds > 84600 * 30 * 2) {
            $starttime = strtotime('last month', $starttime);
            while (($starttime = strtotime('next month', $starttime)) <= $endtime) {
                $column[] = date('Y-m', $starttime);
            }
        } else {
            if ($totalseconds > 86400) {
                for ($time = $starttime; $time <= $endtime;) {
                    $column[] = date("Y-m-d", $time);
                    $time += 86400;
                }
            } else {
                for ($time = $starttime; $time <= $endtime;) {
                    $column[] = date("H:00", $time);
                    $time += 3600;
                }
            }
        }
        $list = [];
        $dataList = [];
        $columnList = array_fill_keys($column, 0);
        foreach ($archivesList as $k => $v) {
            $nickname = $v->admin ? $v->admin->nickname : "未知";
            if (!isset($list[$nickname])) {
                $list[$nickname] = $columnList;
            }
            $list[$nickname][$v['create_date']] = $v['nums'];
        }
        foreach ($list as $index => $item) {
            $dataList[] = [
                'name'      => $index,
                'type'      => 'line',
                'smooth'    => true,
                'areaStyle' => [],
                'data'      => array_values($item)
            ];
        }
        $columnList = array_keys($columnList);
        return [$columnList, $dataList];

    }

}
