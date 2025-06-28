<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\User;
use App\Models\Device;
use App\Models\Content;
use App\Models\ContentView;
use App\Models\Subscription;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    //
    public function overview(Request $request)
    {
        $timeRange = $request->input('time_range', 'month'); // day, week, month, year

        return response()->json([
            'users' => $this->getUserStats($timeRange),
            'devices' => $this->getDeviceStats($timeRange),
            'content' => $this->getContentStats($timeRange),
            'subscriptions' => $this->getSubscriptionStats($timeRange),
            'time_range' => $timeRange,
            'last_updated' => now()->toDateTimeString()
        ]);
    }

    protected function getUserStats($timeRange)
    {
        $count = User::count();
        $change = $this->getPercentageChange(User::class, $timeRange);

        return [
            'total' => $count,
            'change_percentage' => $change,
            'chart' => $this->getTimeSeriesData(User::class, $timeRange)
        ];
    }

    protected function getDeviceStats($timeRange)
    {
        $count = Device::count();
        $change = $this->getPercentageChange(Device::class, $timeRange);
        $vipCount = Device::where('is_vip', true)->count();

        return [
            'total' => $count,
            'vip_devices' => $vipCount,
            'change_percentage' => $change,
            'chart' => $this->getTimeSeriesData(Device::class, $timeRange)
        ];
    }

    protected function getContentStats($timeRange)
    {
        $total = Content::count();
        $vip = Content::where('isvip', true)->count();
        $views = ContentView::count();
        $popularContent = Content::withCount('views')
            ->orderBy('views_count', 'desc')
            ->take(5)
            ->get(['id', 'title', 'views_count']);

        return [
            'total' => $total,
            'vip_content' => $vip,
            'total_views' => $views,
            'popular_content' => $popularContent,
            'views_chart' => $this->getContentViewTimeSeries($timeRange)
        ];
    }

    protected function getSubscriptionStats($timeRange)
    {
        $total = Subscription::count();
        $active = Subscription::where('is_active', true)->count();
       // $revenue = Subscription::sum('price'); // Assuming you have a price column

        return [
            'total' => $total,
            'active' => $active,
            'chart' => $this->getSubscriptionTimeSeries($timeRange)
        ];
    }

    protected function getViewStats($timeRange)
    {
        $total = ContentView::count();
        $vipViews = ContentView::whereHas('content', function ($q) {
            $q->where('isvip', true);
        })->count();

        return [
            'total' => $total,
            'vip_views' => $vipViews,

            'chart' => $this->getDetailedViewTimeSeries($timeRange)
        ];
    }

    protected function getPercentageChange($model, $range)
    {
        $currentPeriod = $this->getDateRange($range);
        $previousPeriod = $this->getDateRange($range, true);

        $currentCount = $model::whereBetween('created_at', $currentPeriod)->count();
        $previousCount = $model::whereBetween('created_at', $previousPeriod)->count();

        if ($previousCount === 0) return 100;

        return round((($currentCount - $previousCount) / $previousCount) * 100, 2);
    }

    protected function getTimeSeriesData($model, $range)
    {
        $format = $this->getDateFormat($range);
        $driver = DB::getDriverName();
        $dateExpression = $driver === 'pgsql'
            ? "TO_CHAR(created_at, '{$format}')"
            : "DATE_FORMAT(created_at, '{$format}')";

        return $model::select(
            DB::raw("{$dateExpression} as date"),
            DB::raw('count(*) as count')
        )
            ->whereBetween('created_at', $this->getDateRange($range))
            ->groupBy('date')
            ->orderBy('date')
            ->get();
    }


    protected function getContentViewTimeSeries($range)
    {
        $format = $this->getDateFormat($range);

        return ContentView::select(
            DB::raw("TO_CHAR(created_at, '{$format}') as date"),
            DB::raw('count(*) as views')
        )
            ->whereBetween('created_at', $this->getDateRange($range))
            ->groupBy('date')
            ->orderBy('date')
            ->get();
    }

    protected function getSubscriptionTimeSeries($range)
    {
        $format = $this->getDateFormat($range);

        return Subscription::select(
            DB::raw("TO_CHAR(created_at, '{$format}') as date"),
            DB::raw('count(*) as subscriptions'),
        )
            ->whereBetween('created_at', $this->getDateRange($range))
            ->groupBy('date')
            ->orderBy('date')
            ->get();
    }

    protected function getDetailedViewTimeSeries($range)
    {
        $format = $this->getDateFormat($range);

        return ContentView::select(
           
            DB::raw('count(*) as total_views'),
            DB::raw('sum(CASE WHEN contents.isvip = 1 THEN 1 ELSE 0 END) as vip_views')
        )
            ->join('contents', 'content_views.content_id', '=', 'contents.id')
            ->whereBetween('content_views.created_at', $this->getDateRange($range))
            ->groupBy('date')
            ->orderBy('date')
            ->get();
    }

    protected function getDateRange($range, $previous = false)
    {
        $now = Carbon::now();
        $start = $now->copy();

        switch ($range) {
            case 'day':
                $start->subDay();
                break;
            case 'week':
                $start->subWeek();
                break;
            case 'year':
                $start->subYear();
                break;
            case 'month':
            default:
                $start->subMonth();
                break;
        }

        if ($previous) {
            $periodStart = $start->copy();
            $periodEnd = $now->copy();

            return [
                $periodStart->sub($periodEnd->diff($start)),
                $start
            ];
        }

        return [$start, $now];
    }
    protected function getDateFormat($range)
    {
        $driver = DB::getDriverName(); // mysql or pgsql

        if ($driver === 'pgsql') {
            switch ($range) {
                case 'day':
                    return 'HH24:00'; // PostgreSQL uses 24-hour format
                case 'week':
                    return 'YYYY-MM-DD';
                case 'year':
                    return 'YYYY-MM';
                default:
                    return 'YYYY-MM-DD';
            }
        } else {
            // Default to MySQL syntax
            switch ($range) {
                case 'day':
                    return '%H:00';
                case 'week':
                    return '%Y-%m-%d';
                case 'year':
                    return '%Y-%m';
                default:
                    return '%Y-%m-%d';
            }
        }
    }
}
