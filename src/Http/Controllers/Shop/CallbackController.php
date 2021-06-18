<?php
namespace Amsgames\LaravelShop\Http\Controllers\Shop;
use Validator;
use Shop;
use Illuminate\Http\Request;
use Amsgames\LaravelShop\Http\Controllers\Controller;
class CallbackController extends Controller
{
    /**
     * Process payment callback.
     *
     * @param Request $request   Request.
     * 
     * @return redirect
     */
    protected function process(Request $request, $status, $order_id, $shoptoken)
    {
        $validator = Validator::make(
            [
                'order_id'  => $order_id,
                'status'    => $status,
                'shoptoken' => $shoptoken
            ],
            [
                'order_id'  => 'required|exists:' . config('shop.order_table') . ',id',
                'status'    => 'required|in:success,fail',
                'shoptoken' => 'required|exists:' . config('shop.transaction_table') . ',token,order_id,' . $order_id,
            ]
        );

        if ($validator->fails()) {
            abort(404);
        }
		
        $order = call_user_func(config('shop.order') . '::find', $order_id);
        $transaction = $order->transactions()->where('token', $shoptoken)->first();
        Shop::callback($order, $transaction, $status, $request->all());
        $transaction->token = null;
        $transaction->save();
        return redirect()->route(config('shop.callback_redirect_route'), ['orderId' => $order->id]);        
    }
}
