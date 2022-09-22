<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Cart;
use App\Models\Order;
use App\Models\Shipping;
use App\User;
use PDF;
use Notification;
use Helper;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use App\Notifications\StatusNotification;

class OrderController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $orders=Order::with(['shipping'])->orderBy('id','DESC')->paginate(10);
        return view('backend.pages.order.index')->with('orders',$orders);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $rule = [
            'first_name'=>'string|required',
            'quantity'=>'required',
            'shipping_id'=>'required',
            'quantity'=>'required',
            'address1'=>'string|required',
            'phone'=>'numeric|required',
        ];
        $msg = [];
        $attributes = [
            'first_name'=>'First Name',
            'address1'=>'Address',
            'phone'=>'Phone Number',
            'post_code'=>'string|nullable',
            'shipping'=>'Shipping Method'
        ];
        Validator::make($request->all(),$rule,$msg,$attributes);

        $insert=new Order();
        $insert->product_title = $request->product_title;
        $insert->first_name = $request->first_name;
        $insert->address1 = $request->address1;
        $insert->phone = $request->phone;
        $insert->quantity = $request->quantity;
        $insert->shipping_id = $request->shipping_id;
        $insert->pamyment_methods = $request->pamyment_methods;
        $insert->payment_number = $request->payment_number;
        // = $request->payment_number;
        $insert->order_number ='ORD-'.strtoupper(Str::random(10));
        $insert->email = $request->email;
        $shipping_price = Shipping::find($request->shipping_id);

        // calculation
        $subtotal =$request->product_price*$request->quantity;
        $total = $subtotal + $shipping_price->price;
        $insert->total_amount = $total;
        $insert->sub_total = $subtotal;

        $insert->country = $request->payment_number;
        $insert->last_name = $request->payment_number;
        $insert->save();
        // $order_data=$request->all();
        // $order_data['order_number']='ORD-'.strtoupper(Str::random(10));
        // // $order_data['user_id']=$request->user()->id;
        // $order_data['shipping_id']=$request->shipping;
        // // $shipping=Shipping::where('id',$order_data['shipping_id'])->pluck('price');
        // // return session('coupon')['value'];
        // // $order_data['sub_total']=Helper::totalCartPrice();
        // $order_data['quantity']=$request->quantity;
        // // return $order_data['total_amount'];
        // $order_data['status']="new";
        // if(request('payment_method')=='paypal'){
        //     $order_data['payment_method']='paypal';
        //     $order_data['payment_status']='paid';
        // }
        // else{
        //     $order_data['payment_method']='cod';
        //     $order_data['payment_status']='Unpaid';
        // }
        // $order->fill($order_data);
        // $status=$order->save();
        // if($order)
        // // dd($order->id);
        // $users=User::where('role','admin')->first();
        // $details=[
        //     'title'=>'New order created',
        //     'actionURL'=>route('order.show',$order->id),
        //     'fas'=>'fa-file-alt'
        // ];
        // Notification::send($users, new StatusNotification($details));
        // if(request('payment_method')=='paypal'){
        //     return redirect()->route('payment')->with(['id'=>$order->id]);
        // }
        // else{
        //     session()->forget('cart');
        //     session()->forget('coupon');
        // }
        // Cart::where('user_id', auth()->user()->id)->where('order_id', null)->update(['order_id' => $order->id]);

        // dd($users);
        request()->session()->flash('success','Your product successfully placed in order');
        return redirect()->route('home')->with('script_msg','<script> alert("Your order successfully received");</script>');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $order=Order::find($id);
        // return $order;
        return view('backend.order.show')->with('order',$order);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $order=Order::find($id);
        return view('backend.order.edit')->with('order',$order);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $order=Order::find($id);
        $this->validate($request,[
            'status'=>'required|in:new,process,delivered,cancel'
        ]);
        $data=$request->all();
        // return $request->status;
        if($request->status=='delivered'){
            foreach($order->cart as $cart){
                $product=$cart->product;
                // return $product;
                $product->stock -=$cart->quantity;
                $product->save();
            }
        }
        $status=$order->fill($data)->save();
        if($status){
            request()->session()->flash('success','Successfully updated order');
        }
        else{
            request()->session()->flash('error','Error while updating order');
        }
        return redirect()->route('order.index');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $order=Order::find($id);
        if($order){
            $status=$order->delete();
            if($status){
                request()->session()->flash('success','Order Successfully deleted');
            }
            else{
                request()->session()->flash('error','Order can not deleted');
            }
            return redirect()->route('order.index');
        }
        else{
            request()->session()->flash('error','Order can not found');
            return redirect()->back();
        }
    }

    public function orderTrack(){
        return view('frontend.pages.order-track');
    }

    public function productTrackOrder(Request $request){
        // return $request->all();
        $order=Order::where('user_id',auth()->user()->id)->where('order_number',$request->order_number)->first();
        if($order){
            if($order->status=="new"){
            request()->session()->flash('success','Your order has been placed. please wait.');
            return redirect()->route('home');

            }
            elseif($order->status=="process"){
                request()->session()->flash('success','Your order is under processing please wait.');
                return redirect()->route('home');

            }
            elseif($order->status=="delivered"){
                request()->session()->flash('success','Your order is successfully delivered.');
                return redirect()->route('home');

            }
            else{
                request()->session()->flash('error','Your order canceled. please try again');
                return redirect()->route('home');

            }
        }
        else{
            request()->session()->flash('error','Invalid order numer please try again');
            return back();
        }
    }

    // PDF generate
    public function pdf(Request $request){
        $order=Order::getAllOrder($request->id);
        // return $order;
        $file_name=$order->order_number.'-'.$order->first_name.'.pdf';
        // return $file_name;
        $pdf=PDF::loadview('backend.order.pdf',compact('order'));
        return $pdf->download($file_name);
    }
    // Income chart
    public function incomeChart(Request $request){
        $year=\Carbon\Carbon::now()->year;
        // dd($year);
        $items=Order::with(['cart_info'])->whereYear('created_at',$year)->where('status','delivered')->get()
            ->groupBy(function($d){
                return \Carbon\Carbon::parse($d->created_at)->format('m');
            });
            // dd($items);
        $result=[];
        foreach($items as $month=>$item_collections){
            foreach($item_collections as $item){
                $amount=$item->cart_info->sum('amount');
                // dd($amount);
                $m=intval($month);
                // return $m;
                isset($result[$m]) ? $result[$m] += $amount :$result[$m]=$amount;
            }
        }
        $data=[];
        for($i=1; $i <=12; $i++){
            $monthName=date('F', mktime(0,0,0,$i,1));
            $data[$monthName] = (!empty($result[$i]))? number_format((float)($result[$i]), 2, '.', '') : 0.0;
        }
        return $data;
    }
}
