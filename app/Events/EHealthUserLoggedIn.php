<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\User;
use App\Models\LegalEntity;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Ця подія виникає після успішного входу користувача через eHealth.
 * Вона містить всі необхідні дані для подальшої обробки,
 * наприклад, для синхронізації статусу працівників.
 */
class EHealthUserLoggedIn
{
    use Dispatchable, SerializesModels;

    /**
     * Створює новий екземпляр події.
     *
     * Використовується PHP 8 constructor property promotion для чистоти коду.
     *
     * @param \App\Models\User $user Модель користувача, який увійшов до системи.
     * @param \App\Models\LegalEntity $legalEntity Модель юридичної особи, в контексті якої відбувся вхід.
     * @param string $authUserUUID UUID користувача, отриманий з токена доступу eHealth.
     */
    public function __construct(
        public User $user,
        public LegalEntity $legalEntity,
        public string $authUserUUID
    ) {
    }
}
