<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\User;
use App\Models\PriceSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    // ──────────────────────────────────────────────────────────
    // Dashboard
    // ──────────────────────────────────────────────────────────

    public function dashboard()
    {
        return redirect()->route('admin.reports');
    }

    // ──────────────────────────────────────────────────────────
    // Báo cáo thống kê
    // ──────────────────────────────────────────────────────────

    public function reports(Request $request)
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'room_type_id' => 'nullable|integer|exists:room_types,id',
            'status' => 'nullable|in:pending,confirmed,checked_in,completed,cancelled',
        ]);

        $filters = [
            'start_date'   => $request->input('start_date', ''),
            'end_date'     => $request->input('end_date', ''),
            'room_type_id' => $request->input('room_type_id', ''),
            'status'       => $request->input('status', ''),
        ];

        $bookingModel = new Booking();
        $userModel    = new User();

        $stats                     = $bookingModel->getReportStats($filters);
        $stats['total_customers']  = $userModel->countCustomers();
        $roomCount                 = max(1, (int) DB::table('rooms')->count());
        $firstStay                 = $filters['start_date'] ?: DB::table('bookings')->min('check_in') ?: now()->toDateString();
        $lastStay                  = $filters['end_date'] ?: DB::table('bookings')->max('check_out') ?: now()->toDateString();
        $periodDays                = max(1, \Carbon\Carbon::parse($firstStay)->diffInDays(\Carbon\Carbon::parse($lastStay)) + 1);
        $soldNights                = max(0, (int) ($stats['total_nights'] ?? 0));
        $stats['adr']              = $soldNights > 0 ? $stats['total_revenue'] / $soldNights : 0;
        $stats['revpar']           = $stats['total_revenue'] / ($roomCount * $periodDays);
        $stats['occupancy_rate']   = min(100, ($soldNights / ($roomCount * $periodDays)) * 100);
        $trendRows                 = $bookingModel->getReportChartData($filters);
        $trendEnd                  = $filters['end_date']
            ? \Carbon\Carbon::parse($filters['end_date'])->startOfDay()
            : (count($trendRows) ? \Carbon\Carbon::parse(end($trendRows)->date)->startOfDay() : now()->startOfDay());
        $trendStart                 = $filters['start_date']
            ? \Carbon\Carbon::parse($filters['start_date'])->startOfDay()
            : (count($trendRows) ? \Carbon\Carbon::parse($trendRows[0]->date)->startOfDay() : $trendEnd->copy()->subDays(29));
        if ($trendStart->greaterThan($trendEnd)) {
            [$trendStart, $trendEnd] = [$trendEnd->copy(), $trendStart->copy()];
        }
        if ($trendStart->diffInDays($trendEnd) > 364) {
            $trendStart = $trendEnd->copy()->subDays(364);
        }
        $trendsByDate = collect($trendRows)->keyBy(fn ($row) => (string) $row->date);
        $chartData = [];
        for ($date = $trendStart->copy(); $date->lte($trendEnd); $date->addDay()) {
            $row = $trendsByDate->get($date->toDateString());
            $chartData[] = [
                'date' => $date->toDateString(),
                'revenue' => (float) ($row->revenue ?? 0),
                'bookings' => (int) ($row->bookings ?? 0),
            ];
        }
        $typeChartData             = $bookingModel->getReportRoomTypeData($filters);
        $statusChartData = [
            ['Chờ xác nhận', (int) ($stats['pending_count'] ?? 0)],
            ['Đã xác nhận', (int) ($stats['confirmed_count'] ?? 0)],
            ['Đang lưu trú', (int) ($stats['checked_in_count'] ?? 0)],
            ['Hoàn thành', (int) ($stats['completed_count'] ?? 0)],
            ['Đã hủy', (int) ($stats['cancelled_count'] ?? 0)],
        ];
        $bookings                  = $bookingModel->getFilteredBookings($filters);
        $roomTypes                 = DB::select('SELECT id, type_name FROM room_types');
        $roomInventory             = DB::table('rooms')->selectRaw('room_type_id, COUNT(*) AS total')->groupBy('room_type_id')->pluck('total', 'room_type_id');

        return view('admin.reports', [
            'stats'     => $stats,
            'chartData' => $chartData,
            'typeChartData' => $typeChartData,
            'statusChartData' => $statusChartData,
            'bookings'  => $bookings,
            'filters'   => $filters,
            'roomTypes' => $roomTypes,
            'roomInventory' => $roomInventory,
        ]);
    }

    // ──────────────────────────────────────────────────────────
    // Cài đặt điều chỉnh giá
    // ──────────────────────────────────────────────────────────

    public function priceSettings()
    {
        $settings = PriceSetting::all();
        return view('admin.price_settings.index', compact('settings'));
    }

    public function priceSettingsCreate()
    {
        return view('admin.price_settings.create');
    }

    public function priceSettingsStore(Request $request)
    {
        $request->validate([
            'name'             => 'required|string|max:100',
            'start_date'       => 'required|date',
            'end_date'         => 'required|date|gte:start_date',
            'adjustment_type'  => 'required|in:percent,fixed',
            'adjustment_value' => 'required|numeric|min:0.01',
        ], [
            'end_date.gte'            => 'Ngày kết thúc phải lớn hơn hoặc bằng ngày bắt đầu.',
            'adjustment_value.min'    => 'Giá trị điều chỉnh phải lớn hơn 0.',
        ]);

        $priceSettingModel = new PriceSetting();

        if ($priceSettingModel->checkOverlap($request->start_date, $request->end_date)) {
            return back()->withInput()
                ->with('error', 'Khoảng thời gian này đã bị trùng lặp với một cài đặt giá đang bật. Vui lòng chọn ngày khác.');
        }

        PriceSetting::create([
            'name'             => $request->name,
            'start_date'       => $request->start_date,
            'end_date'         => $request->end_date,
            'adjustment_type'  => $request->adjustment_type,
            'adjustment_value' => $request->adjustment_value,
            'status'           => $request->has('status') ? 1 : 0,
        ]);

        return redirect()->route('admin.price-settings.index')
            ->with('success', 'Thêm dịp điều chỉnh giá thành công.');
    }

    public function priceSettingsEdit(int $id)
    {
        $setting = PriceSetting::findOrFail($id);
        return view('admin.price_settings.edit', compact('setting'));
    }

    public function priceSettingsUpdate(Request $request, int $id)
    {
        $request->validate([
            'name'             => 'required|string|max:100',
            'start_date'       => 'required|date',
            'end_date'         => 'required|date|gte:start_date',
            'adjustment_type'  => 'required|in:percent,fixed',
            'adjustment_value' => 'required|numeric|min:0.01',
        ], [
            'end_date.gte'         => 'Ngày kết thúc phải lớn hơn hoặc bằng ngày bắt đầu.',
            'adjustment_value.min' => 'Giá trị điều chỉnh phải lớn hơn 0.',
        ]);

        $priceSettingModel = new PriceSetting();

        if ($priceSettingModel->checkOverlap($request->start_date, $request->end_date, $id)) {
            return back()->withInput()
                ->with('error', 'Khoảng thời gian này đã bị trùng lặp với một cài đặt giá đang bật. Vui lòng chọn ngày khác.');
        }

        PriceSetting::findOrFail($id)->update([
            'name'             => $request->name,
            'start_date'       => $request->start_date,
            'end_date'         => $request->end_date,
            'adjustment_type'  => $request->adjustment_type,
            'adjustment_value' => $request->adjustment_value,
            'status'           => $request->has('status') ? 1 : 0,
        ]);

        return redirect()->route('admin.price-settings.index')
            ->with('success', 'Cập nhật thành công.');
    }

    public function priceSettingsDelete(int $id)
    {
        PriceSetting::findOrFail($id)->delete();

        return redirect()->route('admin.price-settings.index')
            ->with('success', 'Đã xóa cài đặt giá.');
    }
}
