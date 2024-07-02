<?php

namespace App\Http\Controllers\Payment;

use App\Helpers\Format;
use App\Http\Controllers\Controller;
use App\Http\Requests\Payment\PaycomRequest;
use App\Http\Responses\PaycomResponse;
use App\Models\Order;
use App\Models\Payments\Paycom;
use Illuminate\Http\Request;

class PaycomController extends Controller
{
    protected PaycomRequest $request;

    protected PaycomResponse $response;

    /**
     * Entry point of controller
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $this->request = new PaycomRequest($request->all());
        $this->response = new PaycomResponse($this->request);

        $methodName = $request->input('method');

        // Call method provided in request
        if (method_exists($this, $methodName)) {
            return $this->$methodName();
        }

        // Error response
        return $this->response->error(
            PaycomResponse::ERROR_METHOD_NOT_FOUND,
            'Method not found.',
            $methodName
        );
    }


    /**
     * Проверка возможности создания финансовой транзакции
     */
    protected function CheckPerformTransaction()
    {
        // Find order by id
        $order = Order::query()->find($this->request->order_id);

        // Check if order is available
        if (empty($order)) {
            return $this->response->invalidAccount();
        }

        // Validate order amount
        if ((100 * $order->total) !== (int) $this->request->amount) {
            return $this->response->invalidAmount();
        }

        // Order state before payment should be 'waiting pay'
        if ($order->state !== Order::STATE_WAITING_PAY) {
            return $this->response->invalidOrderState();
        }

        // Order is ready to be paid
        return $this->response->success(['allow' => true]);
    }


    /**
     * Создание финансовой транзакции
     */
    protected function CreateTransaction()
    {
        // Find order by id
        $order = Order::query()->find($this->request->order_id);

        // Check if order is available
        if (empty($order)) {
            return $this->response->invalidAccount();
        }

        // Validate order amount
        if ((100 * $order->total) !== (int) $this->request->amount) {
            return $this->response->invalidAmount();
        }

        // Order state before payment should be 'waiting pay'
        if ($order->state !== Order::STATE_WAITING_PAY) {
            return $this->response->invalidOrderState();
        }

        // Find payment by id
        $payment = Paycom::findById($this->request->transaction_id);

        /**
         * Payment is found
         */
        if ($payment) {
            // Validate payment state
            if ($payment->state !== Paycom::STATE_CREATED) {
                return $this->response->transactionNotActive();
            }

            // If payment timed out, cancel it and send error
            if ($payment->isExpired()) {
                return $this->response->cancelTransactionByTimeout($payment);
            }

            // Payment found and active, send it as response
            return $this->response->successResponse($payment, 'create_time');
        }

        /**
         * Payment is NOT found
         */
        // Validate new payment time
        if ($this->request->isExpired()) {
            return $this->response->transactionTimeout();
        }

        // Prevent duplicate payment for the same order
        if (Paycom::findByOrderId($order->id)) {
            return $this->response->transactionFound();
        }

        // Create new payment
        $payment = Paycom::createPayment($this->request);

        // Send newly created payment
        return $this->response->successResponse($payment, 'create_time');
    }


    /**
     * Проведение финансовой транзакции
     */
    protected function PerformTransaction()
    {
        // Find payment by id
        $payment = Paycom::findById($this->request->transaction_id);

        // If payment not found, send error
        if (empty($payment)) {
            return $this->response->transactionNotFound();
        }

        switch ($payment->state) {
            case Paycom::STATE_CREATED:
                // If payment is expired, then cancel it and send error
                if ($payment->isExpired()) {
                    return $this->response->cancelTransactionByTimeout($payment);
                }

                /**
                 * TRANSACTION IS ACTIVE
                 */
                // Mark order as completed
                $payment->order->markAsCompleted();

                // Mark payment as completed
                $payment->markAsCompleted();

                return $this->response->successResponse($payment, 'perform_time');

            case Paycom::STATE_COMPLETED:
                // If payment completed, just return it
                return $this->response->successResponse($payment, 'perform_time');

            default:
                // unknown situation
                return $this->response->couldNotPerform();
        }
    }


    /**
     * Отмена финансовой транзакции
     */
    protected function CancelTransaction()
    {
        // Find payment by id
        $payment = Paycom::findById($this->request->transaction_id);

        // If payment not found, send error
        if (empty($payment)) {
            return $this->response->transactionNotFound();
        }

        switch ($payment->state) {
            case Paycom::STATE_CANCELLED:
            case Paycom::STATE_CANCELLED_AFTER_COMPLETE:
                // If already cancelled, just return it
                return $this->response->successResponse($payment, 'cancel_time');

            case Paycom::STATE_CREATED:
                // Cancel active payment
                $payment->order->cancel();
                $payment->cancel($this->request->reason);

                return $this->response->successResponse($payment, 'cancel_time');

            case Paycom::STATE_COMPLETED:
                // Cancel completed payment
                $payment->order->cancelAfterCompleted();
                $payment->cancel($this->request->reason);

                return $this->response->successResponse($payment, 'cancel_time');

            default:
                // unknown situation
                return $this->response->couldNotPerform();
        }
    }


    /**
     * Проверка состояния финансовой транзакции
     */
    protected function CheckTransaction()
    {
        // Find payment by id
        $payment = Paycom::findById($this->request->transaction_id);

        // If payment not found, send error
        if (empty($payment)) {
            return $this->response->transactionNotFound();
        }

        // Prepare and send found payment
        return $this->response->success([
            'create_time'  => Format::toTimestamp($payment->create_time),
            'perform_time' => Format::toTimestamp($payment->perform_time),
            'cancel_time'  => Format::toTimestamp($payment->cancel_time),
            'transaction'  => (string) $payment->id,
            'state'        => $payment->state,
            'reason'       => $payment->reason,
        ]);
    }


    /**
     * Информация о транзакциях мерчанта
     */
    protected function GetStatement()
    {
        return $this->response->couldNotPerform();
    }
}
