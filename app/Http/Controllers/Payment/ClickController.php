<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payments\Click;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ClickController extends Controller
{
    /**
     * Click config attributes
     */
    protected $merchant_id;
    protected $merchant_user_id;
    protected $service_id;
    protected $secret_key;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->merchant_id = config('click.merchant_id');
        $this->merchant_user_id = config('click.merchant_user_id');
        $this->service_id = config('click.service_id');
        $this->secret_key = config('click.secret_key');
    }

    /**
     * Validate the request
     *
     * @param array $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator($data)
    {
        return Validator::make($data, [
            'service_id' => ['required', 'integer'],            // ID of the service, the same with $this->service_id
            'click_trans_id' => ['required', 'integer'],        // Payment ID in CLICK system [changes when later paid]
            'click_paydoc_id' => ['required', 'integer'],       // Payment Number in CLICK system [constant]
            'merchant_trans_id' => ['required', 'string'],      // i.e. $order->id
            'merchant_prepare_id' => ['nullable', 'integer'],   // i.e. $payment->id, required for complete()
            'amount' => ['required', 'numeric'],
            'action' => ['required', 'integer'],
            'error' => ['required', 'integer'],
            'error_note' => ['required', 'string'],
            'sign_time' => ['required', 'date_format:Y-m-d H:i:s'],
            'sign_string' => ['required'],
        ]);
    }


    /**
     * Preparation and verification of payment. [action => 0]
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function prepare(Request $request)
    {
        $validator = $this->validator($request->all());

        // Проверка отправлено ли все параметры
        if ($validator->fails()) {
            return response()->json([
                'error' => -8,
                'error_note' => 'Error in request from click'
            ]);
        }

        // Validated request data
        $data = $validator->validated();

        // Проверка хеша
        $sign_string = md5($data['click_trans_id'] .
            $data['service_id'] .
            $this->secret_key .
            $data['merchant_trans_id'] .
            $data['amount'] .
            $data['action'] .
            $data['sign_time']);

        // Send error if sign_string does not match
        if ($sign_string !== $data['sign_string']) {
            return response()->json([
                'error' => -1,
                'error_note' => 'SIGN CHECK FAILED!'
            ]);
        }

        // For prepare() request action must be 0
        if ((int) $data['action'] != 0) {
            return response()->json([
                'error' => -3,
                'error_note' => 'Action not found'
            ]);
        }

        // Проверить ID заказа
        $order = Order::query()->find($data['merchant_trans_id']);

        if (empty($order)) {
            return response()->json([
                'error' => -5,
                'error_note' => 'Order does not exist'
            ]);
        }

        // Проверить сумму заказа
        if ($order->total != $data['amount']) {
            return response()->json([
                'error' => -2,
                'error_note' => 'Incorrect order amount'
            ]);
        }

        // Проверка статуса заказа, не отменен или нет
        if ($order->state != Order::STATE_WAITING_PAY) {
            return response()->json([
                'error' => -9,
                'error_note' => 'Order state is invalid'
            ]);
        }

        // Все проверки прошли успешно,
        // теперь будем сохранять в базу что подготовка к оплате успешно прошла
        $payment = Click::createPayment($data);

        return response()->json([
            'error' => 0,
            'error_note' => 'Success',
            'click_trans_id' => $data['click_trans_id'],
            'merchant_trans_id' => $data['merchant_trans_id'],
            'merchant_prepare_id' => $payment->id,
        ]);
    }


    /**
     * Completion of the payment. [action => 1]
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function complete(Request $request)
    {
        $validator = $this->validator($request->all());

        // Проверка отправлено ли все параметры
        if ($validator->fails()) {
            return response()->json([
                'error' => -8,
                'error_note' => 'Error in request from click'
            ]);
        }

        // Validated request data
        $data = $validator->validated();

        // Проверка хеша
        $sign_string = md5($data['click_trans_id'] .
            $data['service_id'] .
            $this->secret_key .
            $data['merchant_trans_id'] .
            $data['merchant_prepare_id'] .
            $data['amount'] .
            $data['action'] .
            $data['sign_time']);

        // Send error if sign_string does not match
        if ($sign_string !== $data['sign_string']) {
            return response()->json([
                'error' => -1,
                'error_note' => 'SIGN CHECK FAILED!'
            ]);
        }

        // For complete() request action must be 1
        if ((int) $data['action'] != 1) {
            return response()->json([
                'error' => -3,
                'error_note' => 'Action not found'
            ]);
        }

        // Проверить ID заказа
        $order = Order::query()->find($data['merchant_trans_id']);

        if (empty($order)) {
            return response()->json([
                'error' => -5,
                'error_note' => 'Order does not exist'
            ]);
        }

        // Проверка статуса заказа, не отменен или нет
        if ($order->state != Order::STATE_WAITING_PAY) {
            return response()->json([
                'error' => -9,
                'error_note' => 'Order state is invalid'
            ]);
        }

        $payment = Click::query()->find($data['merchant_prepare_id']);

        // Check if payment exists
        if (empty($payment)) {
            return response()->json([
                'error' => -6,
                'error_note' => 'Transaction does not exist'
            ]);
        }

        // Incorrect parameter amount
        if ($payment->amount != $data['amount']) {
            return response()->json([
                'error' => -2,
                'error_note' => 'Incorrect parameter amount'
            ]);
        }

        // Payment already paid
        if ($payment->state == Click::STATE_COMPLETED) {
            return response()->json([
                'error' => -4,
                'error_note' => 'Already paid'
            ]);
        }

        // Payment already cancelled
        if ($payment->state == Click::STATE_CANCELLED) {
            return response()->json([
                'error' => -9,
                'error_note' => 'Transaction cancelled'
            ]);
        }

        // Ошибка! Деньги с карты пользователя не списались
        if ($data['error'] < 0) {
            $order->cancel();
            $payment->cancel($data['error']);

            return response()->json([
                'error' => -9,
                'error_note' => 'Transaction cancelled'
            ]);
        }

        /**
         * Деньги списаны с карты пользователя
         *
         * TRANSACTION IS ACTIVE
         */

        // Mark order as completed
        $order->markAsCompleted();

        // Mark payment as completed
        $payment->markAsCompleted();

        // Return success
        return response()->json([
            'error' => 0,
            'error_note' => 'Success',
            'click_trans_id' => $data['click_trans_id'],
            'merchant_trans_id' => $data['merchant_trans_id'],
            'merchant_confirm_id' => $payment->id,
        ]);
    }
}
