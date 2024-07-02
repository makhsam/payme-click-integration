<?php

namespace App\Http\Responses;

use App\Helpers\Format;
use App\Http\Requests\Payment\PaycomRequest;
use App\Models\Payments\Paycom;

class PaycomResponse
{
    /**
     * Error codes
     */
    const ERROR_INVALID_JSON_RPC_OBJECT = -32600;
    const ERROR_METHOD_NOT_FOUND        = -32601;
    const ERROR_INSUFFICIENT_PRIVILEGE  = -32504;
    const ERROR_INTERNAL_SYSTEM         = -32400;

    const ERROR_INVALID_AMOUNT          = -31001; // Неверная сумма. Ошибка возникает когда сумма транзакции не совпадает с суммой заказа. Актуальна если выставлен одноразовый счёт.
    const ERROR_TRANSACTION_NOT_FOUND   = -31003; // Транзакция не найдена.
    const ERROR_COULD_NOT_CANCEL        = -31007; // Невозможно отменить транзакцию. Товар или услуга предоставлена потребителю в полном объеме.
    const ERROR_COULD_NOT_PERFORM       = -31008; // Невозможно выполнить операцию. Ошибка возникает если состояние транзакции, не позволяет выполнить операцию.
    const ERROR_INVALID_ACCOUNT         = -31050; // Ошибки, связанные с неверным пользовательским вводом “account“, например: введенный логин не найден, введенный номер телефона не найден и т.д.

    /**
     * @var PaycomRequest $request
     */
    protected $request;

    public function __construct(PaycomRequest $request)
    {
        $this->request = $request;
    }

    /**
     * Sends success response with the given result
     *
     * @param mixed $result
     */
    public function success($result)
    {
        return response()->json([
            'jsonrpc' => '2.0',
            'id' => $this->request->id,
            'result' => $result
        ], 200);
    }

    /**
     * Sends error response with given parameters
     *
     * @param int $code error code.
     * @param string|array $message error message.
     * @param string $data parameter name, that resulted to this error.
     */
    public function error($code, $message = null, $data = null)
    {
        if (is_array($message)) {
            $msg = $this->format(...$message);
        } else {
            $msg = $message;
        }

        return response()->json([
            'id' => $this->request->id,
            'error' => [
                'code' => $code,
                'message' => $msg,
                'data' => $data
            ]
        ], 200);
    }

    /**
     * Message format
     */
    protected function format($ru, $uz = '', $en = '')
    {
        return ['ru' => $ru, 'uz' => $uz, 'en' => $en];
    }

    /**
     * Success payment response
     */
    public function successResponse(Paycom $payment, string $action_time)
    {
        return $this->success([
            $action_time  => Format::toTimestamp($payment->{$action_time}),
            'transaction' => (string) $payment->id,
            'state'       => $payment->state
        ]);
    }

    /**
     * Cancel payment and respond with error
     */
    public function cancelTransactionByTimeout(Paycom $payment)
    {
        $payment->cancel(Paycom::REASON_CANCELLED_BY_TIMEOUT);

        return $this->error(
            self::ERROR_COULD_NOT_PERFORM,
            'Transaction is expired.'
        );
    }

    /**
     * Incorrect order code
     */
    public function invalidAccount()
    {
        return $this->error(
            self::ERROR_INVALID_ACCOUNT,
            [
                'Неверный код заказа.',
                'Harid kodida xatolik.',
                'Incorrect order code.'
            ],
            'order_id'
        );
    }

    /**
     * Incorrect amount
     */
    public function invalidAmount()
    {
        return $this->error(
            self::ERROR_INVALID_AMOUNT,
            'Incorrect amount.'
        );
    }

    /**
     * Order state is invalid
     */
    public function invalidOrderState()
    {
        return $this->error(
            self::ERROR_COULD_NOT_PERFORM,
            'Order state is invalid.'
        );
    }

    /**
     * Payment found, but is not active
     */
    public function transactionNotActive()
    {
        return $this->error(
            self::ERROR_COULD_NOT_PERFORM,
            'Transaction found, but is not active.'
        );
    }

    /**
     * Payment timeout
     */
    public function transactionTimeout()
    {
        return $this->error(
            self::ERROR_INVALID_ACCOUNT,
            [
                "С даты создания транзакции прошло " . Paycom::TIMEOUT . "мс",
                "Tranzaksiya yaratilgan sanadan " . Paycom::TIMEOUT . "ms o'tgan",
                "Since create time of the transaction passed " . Paycom::TIMEOUT . "ms"
            ],
            'time'
        );
    }

    public function transactionNotFound()
    {
        return $this->error(
            self::ERROR_TRANSACTION_NOT_FOUND,
            'Transaction not found.'
        );
    }

    public function transactionFound()
    {
        return $this->error(
            self::ERROR_INVALID_ACCOUNT,
            'Transaction exists for the order.'
        );
    }

    public function couldNotPerform()
    {
        return $this->error(
            self::ERROR_COULD_NOT_PERFORM,
            'Could not perform this operation.'
        );
    }
}
