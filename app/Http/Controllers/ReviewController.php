<?php

namespace App\Http\Controllers;

use App\Models\Review;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\Restaurant;
use App\Models\Image;
use App\Models\Order_item;
use Illuminate\Support\Facades\Storage;

class ReviewController extends Controller
{
    /**
     * عرض نموذج التقييم
     */
    public function create($slug, $trackingCode)
    {
        $restaurant = Restaurant::where('slug', $slug)->firstOrFail();
        $order = Order::where('tracking_code', $trackingCode)
            ->where('restaurant_id', $restaurant->id)
            ->firstOrFail();

        // التحقق من أن الطلب مكتمل
        if ($order->status !== 'delivered') {
            return redirect()->route('order.track', [$slug, $trackingCode])
                ->with('error', 'لا يمكن تقييم الطلب إلا بعد اكتمال التوصيل');
        }

        // التحقق من مرور 10 دقائق على التوصيل
        if ($order->delivered_at && $order->delivered_at->diffInMinutes(now()) < 10) {
            return redirect()->route('order.track', [$slug, $trackingCode])
                ->with('error', 'يجب الانتظار 10 دقائق بعد التوصيل قبل التقييم');
        }

        // التحقق من عدم التقييم مسبقاً
        if ($order->is_reviewed) {
            return redirect()->route('order.track', [$slug, $trackingCode])
                ->with('info', 'لقد قمت بتقييم هذا الطلب مسبقاً');
        }

        return view('themes.burger-theme.review-form', compact('restaurant', 'order'));
    }

    /**
     * حفظ التقييم
     */
    public function store(Request $request, $slug, $trackingCode)
    {
        $restaurant = Restaurant::where('slug', $slug)->firstOrFail();
        $order = Order::where('tracking_code', $trackingCode)
            ->where('restaurant_id', $restaurant->id)
            ->firstOrFail();

        // التحقق من الصلاحيات
        if ($order->status !== 'delivered') {
            return redirect()->back()->with('error', 'لا يمكن تقييم الطلب إلا بعد اكتمال التوصيل');
        }

        if ($order->is_reviewed) {
            return redirect()->back()->with('error', 'لقد قمت بتقييم هذا الطلب مسبقاً');
        }

        // التحقق من البيانات
        $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
            'images' => 'nullable|array|max:5',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        // إنشاء التقييم
        $review = Review::create([
            'order_id' => $order->id,
            'restaurant_id' => $restaurant->id,
            'customer_name' => $order->customer_name,
            'rating' => $request->rating,
            'comment' => $request->comment,
        ]);

        // حفظ الصور إذا وجدت
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $index => $image) {
                $path = $image->store('reviews/' . $review->id, 'public');
                
                Image::create([
                    'imageable_type' => Review::class,
                    'imageable_id' => $review->id,
                    'path' => $path,
                    'alt' => 'صورة تقييم ' . ($index + 1),
                    'sort_order' => $index,
                    'is_primary' => $index === 0,
                ]);
            }
        }

        return redirect()->route('order.track', [$slug, $trackingCode])
            ->with('success', 'شكراً لتقييمك! رأيك يساعدنا على التحسين');
    }

    /**
     * عرض تقييمات المطعم
     */
    public function index($slug)
    {
        $restaurant = Restaurant::where('slug', $slug)->firstOrFail();
        
        $reviews = $restaurant->reviews()->with('images')
            ->latest()
            ->paginate(10);

        return view('themes.burger-theme.reviews', compact('restaurant', 'reviews'));
    }
    /**
 * التحقق من رمز التتبع (AJAX)
 */
public function verify(Request $request)
{
    $request->validate([
        'tracking_code' => 'required|string',
        'product_id' => 'required|exists:products,id',
        'restaurant_id' => 'required|exists:restaurants,id',
    ]);

    $code = strtoupper(trim($request->tracking_code));
    
    // البحث عن الطلب
    $order = Order::where('tracking_code', $code)
        ->where('restaurant_id', $request->restaurant_id)
        ->where('status', 'delivered') // يجب أن يكون مكتملاً
        ->first();

    if (!$order) {
        return response()->json([
            'success' => false,
            'message' => 'رمز التتبع غير صحيح أو الطلب لم يكتمل بعد'
        ]);
    }

    // التحقق من أن المنتج موجود في هذا الطلب
    $orderItem = Order_item::where('order_id', $order->id)
        ->where('product_id', $request->product_id)
        ->first();

    if (!$orderItem) {
        return response()->json([
            'success' => false,
            'message' => 'هذا المنتج غير موجود في طلبك'
        ]);
    }

    // التحقق من عدم التقييم مسبقاً
    $existingReview = Review::where('order_id', $order->id)
        ->whereHas('order', function($query) use ($request) {
            $query->whereHas('items', function($q) use ($request) {
                $q->where('product_id', $request->product_id);
            });
        })
        ->exists();

    if ($existingReview) {
        return response()->json([
            'success' => false,
            'message' => 'لقد قمت بتقييم هذا المنتج مسبقاً'
        ]);
    }

    return response()->json([
        'success' => true,
        'order_id' => $order->id,
        'customer_name' => $order->customer_name
    ]);
}

/**
 * إرسال التقييم (AJAX)
 */
public function storeAjax(Request $request)
{
    $request->validate([
        'order_id' => 'required|exists:orders,id',
        'product_id' => 'required|exists:products,id',
        'restaurant_id' => 'required|exists:restaurants,id',
        'rating' => 'required|integer|min:1|max:5',
        'comment' => 'nullable|string|max:1000',
        'images' => 'nullable|array|max:5',
        'images.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
    ]);

    $order = Order::findOrFail($request->order_id);

    // إنشاء التقييم
    $review = Review::create([
        'order_id' => $order->id,
        'restaurant_id' => $request->restaurant_id,
        'customer_name' => $order->customer_name,
        'rating' => $request->rating,
        'comment' => $request->comment,
    ]);

    // حفظ الصور
    if ($request->hasFile('images')) {
        foreach ($request->file('images') as $index => $image) {
            $path = $image->store('reviews/' . $review->id, 'public');
            
            Image::create([
                'imageable_type' => Review::class,
                'imageable_id' => $review->id,
                'path' => $path,
                'alt' => 'صورة تقييم ' . ($index + 1),
                'sort_order' => $index,
                'is_primary' => $index === 0,
            ]);
        }
    }

    return response()->json([
        'success' => true,
        'message' => 'شكراً لتقييمك!'
    ]);
}
}
