<?php

namespace App\Enums;

enum UserRole: string
{
    case ADMIN = 'admin';
    case MANAGER = 'manager';
    case USER = 'user';
    case HR = 'hr';
    case ACCOUNTANT = 'accountant';

    /**
     * Get all role values
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get role label
     */
    public function label(): string
    {
        return match($this) {
            self::ADMIN => 'Administrator',
            self::MANAGER => 'Manager',
            self::USER => 'User',
            self::HR => 'Human Resources',
            self::ACCOUNTANT => 'Accountant',
        };
    }

    /**
     * Get role permissions
     */
    public function permissions(): array
    {
        return match($this) {
            self::ADMIN => ['*'],
            self::MANAGER => [
                'work.manage',
                'hrm.view',
                'crm.manage',
                'warehouse.view'
            ],
            self::USER => [
                'work.view',
                'work.create',
                'work.update_own'
            ],
            self::HR => [
                'hrm.manage',
                'work.view'
            ],
            self::ACCOUNTANT => [
                'warehouse.manage',
                'crm.view'
            ],
        };
    }
}
