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
        return view('admin.dashboard');
    }

    // ──────────────────────────────────────────────────────────
    // Báo cáo thống kê
    // ──────────────────────────────────────────────────────────

    public function reports(Request $request)
    {
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
        $chartData                 = $bookingModel->getReportChartData($filters);
        $bookings                  = $bookingModel->getFilteredBookings($filters);
        $roomTypes                 = DB::select('SELECT id, type_name FROM room_types');

        return view('admin.reports', [
            'stats'     => $stats,
            'chartData' => json_encode($chartData),
            'bookings'  => $bookings,
            'filters'   => $filters,
            'roomTypes' => $roomTypes,
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