<?php

declare(strict_types=1);

namespace App\Enums;

enum RolePermissionEnum: string
{
    case SUPER_ADMIN = 'super admin';
    case CREATE_USER = 'create user';
    case READ_USER = 'read user';
    case UPDATE_USER = 'update user';
    case DELETE_USER = 'delete user';
    case CREATE_ORGANIZATION = 'create organization';
    case READ_ORGANIZATION = 'read organization';
    case UPDATE_ORGANIZATION = 'update organization';
    case DELETE_ORGANIZATION = 'delete organization';
    case CAN_UPLOAD_DATA = 'can upload data';
    case CAN_VIEW_DATA = 'can view data';
    case CAN_DELETE_DATA = 'can delete data';

    public const array PERMISSIONS = [
        self::CREATE_USER,
        self::READ_USER,
        self::UPDATE_USER,
        self::DELETE_USER,
        self::CREATE_ORGANIZATION,
        self::READ_ORGANIZATION,
        self::UPDATE_ORGANIZATION,
        self::DELETE_ORGANIZATION,
        self::CAN_UPLOAD_DATA,
        self::CAN_VIEW_DATA,
        self::CAN_DELETE_DATA,
    ];
    public const array ROLES = [self::SUPER_ADMIN];
}
