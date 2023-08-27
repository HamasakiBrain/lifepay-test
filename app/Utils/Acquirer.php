<?php
/**
 * Created by PhpStorm
 * User: JetBrain <amon_amonov@bk.ru>
 * Date: 28.08.2023
 * Time: 00:08
 */

namespace App\Utils;

use App\Models\AcquirerInfo;
use App\Services\LifePayService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;

abstract class Acquirer
{
    public const PAYMENT_TYPE = '';
    public const LIFEPAY = 'life-pay';

    public const ACQUIRES = [
      self::LIFEPAY => LifePayService::class
    ];

    public const SUCCESS_URL_CONF_ALIAS = 'acquires.payment_success';
    public const ERROR_URL_CONF_ALIAS = '';

    /**
     * Экземляр класса
     * @var LifePayService $instance
     */
    public static $instance = null;

    /**
     * Получение экземпляра класса
     * @param string $acqName
     * @return Acquirer
     */
    public static function instance(string $acqName = self::LIFEPAY): self
    {
        $class = Arr::get(self::ACQUIRES, $acqName, '');

        if(!$class) {
            new \Exception('Unkown Acquirer');
        }

        if(self::$instance === null){
            self::$instance = new $class();
        }
        return self::$instance;
    }

    /**
     * Получение ссылки на страницу с формой эквайера для оплаты
     * Создание модели AcquirerInfo c ифнормацией о количестве попытках
     * бронирования и последнем номере заказа отправленным в систему эквайринга
     * в случае если при получении формы оплаты, сообщается что оплата
     * уже произведена, то отдаем ссылку на обновленеие статуса заказа
     * (route('payment.status.update'))
     * @param int $booking
     * @param string $acqName
     * @return Response Http-ответ, содержащий сссылку ан форму оплаты заказа
     */
    public abstract function payLink(int $booking, string $acqName = self::LIFEPAY) : Response;

    /**
     * Подтверждение выполнения заказа от эквайера
     * отправка эквайером уведомления об изменение заказа
     * @param Request $request
     * @return Response
     */
    public abstract function confirmation(Request $request) : Response;

    /**
     * Проверка статуса выполнения заказа в системы эквайера
     * @param int $booking
     * @return bool
     */
    public abstract function checkPayment(int $booking) : bool;

    /**
     * Отмена платежа
     * @param int $booking
     * @return bool
     */
    public abstract function cancelPayment(int $booking) : bool;

    /**
     * Получение существующей информации по старым попыткам оплаты,
     * либо пустой модели для сохранения новой попытки
     * @param int $bookindId
     * @return AcquirerInfo
     */
    public static function getAcquirerInfo(int $bookindId): AcquirerInfo
    {
        return AcquirerInfo::firstOrNew(['booking_id' => $bookindId], [
            'acquirer' => self::PAYMENT_TYPE
        ]);
    }

    /**
     * Изменение статуса оплаты модели Booking и возврат средств в
     * случае, если не удается сохранить результаты оплаты
     * @param int $booking
     * @param bool $paid
     * @return bool
     */
    protected function bookingUpdate(int $booking, bool $paid = true): bool
    {
        return true;
    }

    /**
     * Get success redirect url
     * @return string
     */
    protected function successUrl() : string
    {
        return config('app.url')
            . '/' .
            config(static::SUCCESS_URL_CONF_ALIAS, '/success/default')
            . self::getAcquirerParam()
            ;
    }

    /**
     * Get error redirect url
     * @return string
     */
    protected function errorUrl() : string
    {
        return config('app.url')
            . '/' .
            config(static::ERROR_URL_CONF_ALIAS, '/error/default')
            . self::getAcquirerParam()
            ;
    }

    /**
     * Get acquirer params
     * @return string
     */
    public static function getAcquirerParam() : string
    {
        return '?acquirer='  . static::PAYMENT_TYPE;
    }

    /**
     * Generate order number
     * @param int $id
     * @param string $prefix
     * @param string $suffix
     * @return string
     */
    public static function generateOrderNumber(int $id, string $prefix, string $suffix) : string
    {
        return $prefix . '_' . $id . '_' . $suffix;
    }
}
