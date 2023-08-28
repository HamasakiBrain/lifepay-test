<?php
/**
 * Created by PhpStorm
 * User: JetBrain <amon_amonov@bk.ru>
 * Date: 28.08.2023
 * Time: 00:00
 */

namespace App\Services;

use App\Models\AcquirerInfo;
use App\Models\Booking;
use App\Utils\Acquirer;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class LifePayService extends Acquirer
{


    private string $API_URL = 'https://api.life-pay.ru/v1/';
    private string $API_TOKEN;
    public function __construct()
    {
        $this->API_TOKEN = config('acquires.lifepay.token');
    }



    public function payLink(int $booking, string $acqName = self::LIFEPAY): Response
    {
        $foundBooking = Booking::firstOrFail(['id', 'description', 'amount', 'method'], $booking);
        $data = [
            'amount' => $foundBooking->amount,
            'description' => $foundBooking->description,
            'method' => $foundBooking->method,
            'customer_email' => $foundBooking->customer_email ?? '',
            'number' => self::generateOrderNumber($foundBooking->id, 'lifePay', 'LP'),
        ];

        if($foundBooking->method == 'mobileCommerce') { // В случае использования метода оплаты mobileCommerce, аттрибут customer_phone является обязательным, в ином случае аттрибут обязательным не является.
            $data['customer_phone'] = $foundBooking->customer_phone;
        }


        if($foundBooking->status === 'success')
            return route('payment.status.update', ['number' => $data['number']]);

        $request = $this->sendRequest('bill', 'POST', $data);

        AcquirerInfo::firstOrNew(['booking_id' => $foundBooking->id], [
            'acquirer' => $acqName,
            'acquirerNumber' => $request['data']['number'],
        ]);

        if($request['code'] === 0) {
            return $request['data']['paymentUrl'];
        }
        return $request['message'];
    }

    public function confirmation(Request $request): bool
    {
        $booking = Booking::whereOrderid($request->number)->firstOrFail();
        $booking->update(['status' => $request->status]);

        return $booking->status == 'success';
    }

    public function checkPayment(int $booking): bool
    {
        return $this->sendRequest('bill/status', 'GET', [
            'number' => $booking
        ])?->data[0]['status'] === 10;
    }

    public function cancelPayment(int $booking): bool
    {
        return $this->sendRequest('bill/cancellation', 'POST', [
                'number' => $booking
            ])->code === 0;
    }


    /**
     * @param string $apiMethod пример: transactions
     * @param string $method
     * @param array $data
     * @param array $params
     * @return mixed
     * @throws GuzzleException
     * @throws \Exception
     */
    public function sendRequest(string $apiMethod, string $method = 'GET', array $data = [], array $params = []) : string
    {

        $url = $this->API_URL
            . '/' . $apiMethod
            . '?apiKey=' . $this->API_TOKEN
            .  http_build_query($params); // Формируем URL запроса
        $clientRequest = new Client(['verify' => false]); // Отключаем проверку на SSL

        $request = new \GuzzleHttp\Psr7\Request($method, $url, [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ], json_encode($data));

        try {
            $response = $clientRequest->send($request);
        } catch (\Exception $e) {
          throw new \Exception($e->getMessage());
        }
        return $response->getBody()->getContents();
    }


    /**
     * @param string $login Логин администратора в системе Lifepay. Как правило, это номер телефона в формате 7xxxxxxxxxx.
     * @param string $operator Логин оператора, который совершил транзакцию. Как правило, это номер телефона в формате 7xxxxxxxxxx.
     * @param string $date Дата транзакции в формате YYYY-MM-DD UTC+0.
     * @param int $limit Максимальное количество выводимых записей. Минимальное значение - 0, максимальное - 100. По умолчанию - 10.
     * @param int $offset Смещение записей для запроса. По умолчанию - 0.
     * @return array
     * @throws GuzzleException
     */
    public function getTransactions(string $login, string $operator, string $date, int $limit = 10, int $offset = 0) : array {
        return $this->sendRequest('transactions', 'GET', [], [
            'login' => $login,
            'operator' => $operator,
            'date' => $date,
            'limit' => $limit,
            'offset' => $offset
        ]);
    }

}
