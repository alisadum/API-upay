    <?php

    namespace App\Http\Controllers\Api;

    use App\Http\Controllers\Controller;
    use App\Models\Category;
    use App\Models\Merchant;
    use App\Models\Notification;
    use App\Models\Outlet;
    use App\Models\Promotion;
    use App\Models\Voucher;
    use Illuminate\Http\Request;
    use Illuminate\Support\Facades\Auth;
    use Illuminate\Support\Facades\Log;

    class MerchantController extends Controller
    {
        public function profile()
        {
            $merchant = Auth::user()->loadMissing('merchant')->merchant;
            if (!$merchant) {
                return response()->json(['message' => 'Anda belum terdaftar sebagai merchant.'], 404);
            }
            return response()->json($merchant);
        }

        public function updateProfile(Request $request)
        {
            $request->validate([
                'business_name' => 'sometimes|required|string|max:255',
                'address' => 'sometimes|required|string|max:255',
                'whatsapp' => 'sometimes|required|string|max:25',
                'foto' => 'sometimes|nullable|image|mimes:jpg,jpeg,png|max:2048',
            ]);

            $merchant = Auth::user()->merchant;
            if (!$merchant) {
                return response()->json(['message' => 'Anda belum terdaftar sebagai merchant.'], 404);
            }

            if ($request->has('business_name')) $merchant->business_name = $request->business_name;
            if ($request->has('address')) $merchant->address = $request->address;
            if ($request->has('whatsapp')) $merchant->whatsapp = $request->whatsapp;
            if ($request->hasFile('foto')) $merchant->photo_path = $request->file('foto')->store('merchants', 'public');

            $merchant->save();

            return response()->json([
                'message' => 'Profil merchant berhasil diperbarui.',
                'merchant' => $merchant
            ]);
        }

        public function createPromotion(Request $request)
        {
            $request->validate([
                'title' => 'required|string|max:255',
                'description' => 'required|string',
                'original_price' => 'required|numeric|min:0',
                'promo_type' => 'required|in:discount_percent,buy_get_free',
                'discount_percent' => 'required_if:promo_type,discount_percent|numeric|min:0|max:100',
                'buy_quantity' => 'required_if:promo_type,buy_get_free|integer|min:1',
                'free_quantity' => 'required_if:promo_type,buy_get_free|integer|min:1',
                'terms_conditions' => 'required|string',
                'location' => 'nullable|string|max:255',
                'outlet_ids' => 'sometimes|array',
                'outlet_ids.*' => 'exists:outlets,id',
                'category_id' => 'required|exists:categories,id',
                'start_time' => 'nullable|date',
                'end_time' => 'nullable|date|after:start_time',
                'available_seats' => 'nullable|integer|min:0',
                'photo' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            ]);

            $merchant = Auth::user()->merchant;
            if (!$merchant || !$merchant->is_approved) {
                return response()->json(['message' => 'Anda bukan merchant yang disetujui.'], 403);
            }

            $photoPath = $request->hasFile('photo') ? $request->file('photo')->store('promotions', 'public') : null;
            $location = $request->input('location');
            $outletIds = $request->input('outlet_ids', []);

            if (empty($outletIds) && !$location) {
                return response()->json(['message' => 'Lokasi atau setidaknya satu outlet harus diisi.'], 422);
            }

            if (!empty($outletIds)) {
                $validOutlets = Outlet::whereIn('id', $outletIds)->where('merchant_id', $merchant->id)->count();
                if ($validOutlets !== count($outletIds)) {
                    return response()->json(['message' => 'Salah satu outlet tidak valid atau bukan milik Anda.'], 403);
                }
                $location = null;
            }

            $discountedPrice = $request->promo_type === 'buy_get_free'
                ? $request->original_price * $request->buy_quantity
                : $request->original_price - ($request->original_price * $request->discount_percent / 100);

            $promotion = $merchant->promotions()->create([
                'title' => $request->title,
                'description' => $request->description,
                'original_price' => $request->original_price,
                'promo_type' => $request->promo_type,
                'discount_percent' => $request->promo_type === 'discount_percent' ? $request->discount_percent : null,
                'buy_quantity' => $request->promo_type === 'buy_get_free' ? $request->buy_quantity : null,
                'free_quantity' => $request->promo_type === 'buy_get_free' ? $request->free_quantity : null,
                'price' => $discountedPrice,
                'terms_conditions' => $request->terms_conditions,
                'location' => $location,
                'category_id' => $request->category_id,
                'start_time' => $request->start_time,
                'end_time' => $request->end_time,
                'available_seats' => $request->available_seats,
                'photo_path' => $photoPath,
                'is_approved' => false,
            ]);

            if (!empty($outletIds)) {
                $promotion->outlets()->attach($outletIds);
            }

            Notification::create([
                'user_id' => 1,
                'type' => 'new_promo',
                'message' => "Promosi baru '{$promotion->title}' dari {$merchant->business_name} menunggu persetujuan.",
            ]);

            return response()->json([
                'message' => 'Promosi berhasil dibuat, menunggu persetujuan admin.',
                'promotion' => $promotion
            ], 201);
        }

        public function updatePromotion(Request $request, Promotion $promotion)
        {
            $request->validate([
                'title' => 'sometimes|required|string|max:255',
                'description' => 'sometimes|required|string',
                'original_price' => 'sometimes|required|numeric|min:0',
                'promo_type' => 'sometimes|required|in:discount_percent,buy_get_free',
                'discount_percent' => 'required_if:promo_type,discount_percent|numeric|min:0|max:100',
                'buy_quantity' => 'required_if:promo_type,buy_get_free|integer|min:1',
                'free_quantity' => 'required_if:promo_type,buy_get_free|integer|min:1',
                'terms_conditions' => 'sometimes|required|string',
                'location' => 'sometimes|nullable|string|max:255',
                'outlet_ids' => 'sometimes|array',
                'outlet_ids.*' => 'exists:outlets,id',
                'category_id' => 'sometimes|required|exists:categories,id',
                'start_time' => 'sometimes|nullable|date',
                'end_time' => 'sometimes|nullable|date|after:start_time',
                'available_seats' => 'sometimes|nullable|integer|min:0',
                'photo' => 'sometimes|nullable|image|mimes:jpg,jpeg,png|max:2048',
            ]);

            $merchant = Auth::user()->merchant;
            if (!$merchant || $promotion->merchant_id !== $merchant->id) {
                return response()->json(['message' => 'Anda tidak memiliki akses ke promosi ini.'], 403);
            }

            if ($request->has('title')) $promotion->title = $request->title;
            if ($request->has('description')) $promotion->description = $request->description;
            if ($request->has('original_price')) $promotion->original_price = $request->original_price;
            if ($request->has('promo_type')) {
                $promotion->promo_type = $request->promo_type;
                if ($request->promo_type === 'buy_get_free') {
                    $promotion->discount_percent = null;
                    $promotion->buy_quantity = $request->buy_quantity;
                    $promotion->free_quantity = $request->free_quantity;
                    $promotion->price = $request->original_price * $request->buy_quantity;
                } else {
                    $promotion->buy_quantity = null;
                    $promotion->free_quantity = null;
                    $promotion->discount_percent = $request->discount_percent;
                    $promotion->price = $request->original_price - ($request->original_price * $request->discount_percent / 100);
                }
            }
            if ($request->has('terms_conditions')) $promotion->terms_conditions = $request->terms_conditions;
            if ($request->has('location')) {
                $promotion->location = $request->location;
                $promotion->outlets()->detach();
            }
            if ($request->has('outlet_ids')) {
                $outletIds = $request->outlet_ids;
                $validOutlets = Outlet::whereIn('id', $outletIds)->where('merchant_id', $merchant->id)->count();
                if ($validOutlets !== count($outletIds)) {
                    return response()->json(['message' => 'Salah satu outlet tidak valid atau bukan milik Anda.'], 403);
                }
                $promotion->location = null;
                $promotion->outlets()->sync($outletIds);
            }
            if ($request->has('category_id')) $promotion->category_id = $request->category_id;
            if ($request->has('start_time')) $promotion->start_time = $request->start_time;
            if ($request->has('end_time')) $promotion->end_time = $request->end_time;
            if ($request->has('available_seats')) $promotion->available_seats = $request->available_seats;
            if ($request->hasFile('photo')) $promotion->photo_path = $request->file('photo')->store('promotions', 'public');

            if (!$promotion->location && $promotion->outlets->isEmpty()) {
                return response()->json(['message' => 'Lokasi atau setidaknya satu outlet harus diisi.'], 422);
            }

            $promotion->is_approved = false;
            $promotion->save();

            Notification::create([
                'user_id' => 1,
                'type' => 'promo_updated',
                'message' => "Promosi '{$promotion->title}' dari {$merchant->business_name} diperbarui, menunggu persetujuan.",
            ]);

            return response()->json([
                'message' => 'Promosi berhasil diperbarui, menunggu persetujuan admin.',
                'promotion' => $promotion
            ]);
        }

        public function getOwnPromotions()
        {
            $merchant = Auth::user()->merchant;
            if (!$merchant) {
                return response()->json(['message' => 'Anda bukan merchant.'], 403);
            }

            $promotions = $merchant->promotions()->with('category', 'outlets')->get();
            return response()->json($promotions);
        }

        public function getPromotion(Promotion $promotion)
        {
            $merchant = Auth::user()->merchant;
            Log::info('getPromotion called', ['promotion_id' => $promotion->id, 'merchant_id' => $merchant?->id]);
            if (!$merchant || $promotion->merchant_id !== $merchant->id) {
                Log::error('Access denied', ['promotion_id' => $promotion->id, 'merchant_id' => $merchant?->id]);
                return response()->json(['message' => 'Anda tidak memiliki akses ke promosi ini.'], 403);
            }

            $promotion->load('category', 'outlets');
            return response()->json($promotion);
        }

        public function deletePromotion(Promotion $promotion)
        {
            $merchant = Auth::user()->merchant;
            if (!$merchant || $promotion->merchant_id !== $merchant->id) {
                return response()->json(['message' => 'Anda tidak memiliki akses ke promosi ini.'], 403);
            }

            $promotion->outlets()->detach();
            $promotion->delete();
            return response()->json(['message' => 'Promosi berhasil dihapus.']);
        }

        public function getOrders()
        {
            $merchant = Auth::user()->merchant;
            if (!$merchant) {
                Log::error('getOrders: Merchant not found', ['user_id' => Auth::id()]);
                return response()->json(['message' => 'Anda bukan merchant.'], 403);
            }

            Log::info('getOrders called', ['merchant_id' => $merchant->id]);
            $orders = Voucher::whereIn('promotion_id', $merchant->promotions->pluck('id'))
                ->with('user', 'promotion.category', 'promotion.outlets')
                ->get();
            Log::info('Orders retrieved', ['count' => $orders->count()]);

            return response()->json($orders);
        }

        public function redeemVoucher(Request $request)
        {
            $request->validate(['voucher_code' => 'required|string']);

            $merchant = Auth::user()->merchant;
            if (!$merchant) {
                return response()->json(['message' => 'Anda bukan merchant.'], 403);
            }

            $voucher = Voucher::with('promotion.category', 'promotion.outlets')->where('code', $request->voucher_code)->first();
            if (!$voucher) {
                return response()->json(['message' => 'Voucher tidak ditemukan.'], 404);
            }
            if ($voucher->is_redeemed || $voucher->status === 'used') {
                return response()->json(['message' => 'Voucher sudah digunakan.'], 409);
            }
            if (!$voucher->is_paid) {
                return response()->json(['message' => 'Voucher belum dibayar.'], 422);
            }
            if ($voucher->promotion->merchant_id !== $merchant->id) {
                return response()->json(['message' => 'Voucher ini bukan untuk merchant Anda.'], 403);
            }

            $voucher->is_redeemed = true;
            $voucher->status = 'used';
            $voucher->redeemed_at = now();
            $voucher->save();

            Notification::create([
                'user_id' => $voucher->user_id,
                'type' => 'voucher_redeemed',
                'message' => "Terima kasih telah menggunakan voucher {$voucher->code} di {$voucher->promotion->title}!",
            ]);

            return response()->json(['message' => 'Voucher berhasil diredeem.']);
        }

        public function confirmWhatsAppPayment(Request $request)
        {
            $request->validate([
                'voucher_id' => 'required|exists:vouchers,id',
                'proof_path' => 'required|image|mimes:jpg,jpeg,png|max:2048',
            ]);

            $merchant = Auth::user()->merchant;
            if (!$merchant) {
                return response()->json(['message' => 'Anda bukan merchant.'], 403);
            }

            $voucher = Voucher::with('promotion.category', 'promotion.outlets')->find($request->voucher_id);
            if (!$voucher || $voucher->payment_method !== 'whatsapp' || $voucher->promotion->merchant_id !== $merchant->id) {
                return response()->json(['message' => 'Voucher tidak valid atau bukan milik merchant Anda.'], 403);
            }

            if ($voucher->is_paid) {
                return response()->json(['message' => 'Voucher sudah dibayar.'], 409);
            }

            $proofPath = $request->file('proof_path')->store('proofs', 'public');
            $voucher->proof_path = $proofPath;
            $voucher->is_paid = true;
            $voucher->save();

            Notification::create([
                'user_id' => $voucher->user_id,
                'type' => 'payment_confirmed',
                'message' => "Pembayaran untuk voucher {$voucher->code} telah dikonfirmasi oleh merchant.",
            ]);

            return response()->json(['message' => 'Pembayaran WhatsApp berhasil dikonfirmasi.']);
        }

        public function getOutlets()
        {
            $merchant = Auth::user()->merchant;
            if (!$merchant) {
                return response()->json(['message' => 'Anda bukan merchant.'], 403);
            }

            $outlets = $merchant->outlets()->get();
            return response()->json($outlets);
        }

        public function createOutlet(Request $request)
        {
            $request->validate([
                'name' => 'required|string|max:255',
                'address' => 'required|string|max:255',
                'city' => 'nullable|string|max:100',
                'province' => 'nullable|string|max:100',
            ]);

            $merchant = Auth::user()->merchant;
            if (!$merchant) {
                return response()->json(['message' => 'Anda bukan merchant.'], 403);
            }

            $outlet = $merchant->outlets()->create([
                'name' => $request->name,
                'address' => $request->address,
                'city' => $request->city,
                'province' => $request->province,
            ]);

            return response()->json([
                'message' => 'Outlet berhasil dibuat.',
                'outlet' => $outlet
            ], 201);
        }
    }