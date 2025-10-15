<?php

declare(strict_types=1);

namespace RagSystem\Application\Service;

use RagSystem\Domain\Model\User;

class AuthorizationService
{
    // Права доступа для разных действий
    public const string PERMISSION_DOCUMENT_UPLOAD = 'document.upload';
    public const string PERMISSION_DOCUMENT_DELETE = 'document.delete';
    public const string PERMISSION_DOCUMENT_VIEW = 'document.view';
    public const string PERMISSION_QUERY_SEARCH = 'query.search';
    public const string PERMISSION_USER_MANAGE = 'user.manage';
    public const string PERMISSION_SYSTEM_ADMIN = 'system.admin';

    /**
     * Маппинг ролей на разрешения
     */
    private const array ROLE_PERMISSIONS = [
        User::ROLE_ADMIN => [
            self::PERMISSION_DOCUMENT_UPLOAD,
            self::PERMISSION_DOCUMENT_DELETE,
            self::PERMISSION_DOCUMENT_VIEW,
            self::PERMISSION_QUERY_SEARCH,
            self::PERMISSION_USER_MANAGE,
            self::PERMISSION_SYSTEM_ADMIN,
        ],
        User::ROLE_USER => [
            self::PERMISSION_DOCUMENT_VIEW,
            self::PERMISSION_QUERY_SEARCH,
        ],
    ];

    /**
     * Проверяет, имеет ли пользователь указанное разрешение
     */
    public function hasPermission(User $user, string $permission): bool
    {
        if (!$user->isActive()) {
            return false;
        }

        $userPermissions = self::ROLE_PERMISSIONS[$user->getRole()] ?? [];
        return in_array($permission, $userPermissions, true);
    }

    /**
     * Проверяет, является ли пользователь администратором
     */
    public function isAdmin(User $user): bool
    {
        return $this->hasPermission($user, self::PERMISSION_SYSTEM_ADMIN);
    }

    /**
     * Проверяет, может ли пользователь загружать документы
     */
    public function canUploadDocuments(User $user): bool
    {
        return $this->hasPermission($user, self::PERMISSION_DOCUMENT_UPLOAD);
    }

    /**
     * Проверяет, может ли пользователь удалять документы
     */
    public function canDeleteDocuments(User $user): bool
    {
        return $this->hasPermission($user, self::PERMISSION_DOCUMENT_DELETE);
    }

    /**
     * Проверяет, может ли пользователь просматривать документы
     */
    public function canViewDocuments(User $user): bool
    {
        return $this->hasPermission($user, self::PERMISSION_DOCUMENT_VIEW);
    }

    /**
     * Проверяет, может ли пользователь выполнять поиск
     */
    public function canSearch(User $user): bool
    {
        return $this->hasPermission($user, self::PERMISSION_QUERY_SEARCH);
    }

    /**
     * Проверяет, может ли пользователь управлять другими пользователями
     */
    public function canManageUsers(User $user): bool
    {
        return $this->hasPermission($user, self::PERMISSION_USER_MANAGE);
    }

    /**
     * Получает все разрешения пользователя
     */
    public function getUserPermissions(User $user): array
    {
        if (!$user->isActive()) {
            return [];
        }

        return self::ROLE_PERMISSIONS[$user->getRole()] ?? [];
    }

    /**
     * Проверяет доступ к API эндпоинту
     */
    public function canAccessApiEndpoint(User $user, string $method, string $path): bool
    {
        $permission = $this->getPermissionForEndpoint($method, $path);

        if (!$permission) {
            return true;
        }

        return $this->hasPermission($user, $permission);
    }

    /**
     * Определяет необходимое разрешение для API эндпоинта
     */
    private function getPermissionForEndpoint(string $method, string $path): ?string
    {
        // Маршруты для документов
        if (str_starts_with($path, '/api/v1/documents')) {
            return match ($method) {
                'POST' => self::PERMISSION_DOCUMENT_UPLOAD,
                'DELETE' => self::PERMISSION_DOCUMENT_DELETE,
                'GET' => self::PERMISSION_DOCUMENT_VIEW,
                default => null,
            };
        }

        // Маршруты для поиска
        if (str_starts_with($path, '/api/v1/query')) {
            return match ($method) {
                'POST' => self::PERMISSION_QUERY_SEARCH,
                default => null,
            };
        }

        // Маршруты для пользователей (только для админов)
        if (str_starts_with($path, '/api/v1/users')) {
            return self::PERMISSION_USER_MANAGE;
        }

        // Маршруты авторизации доступны всем
        if (str_starts_with($path, '/api/v1/auth')) {
            return null;
        }

        // Маршруты health check доступны всем
        if (str_starts_with($path, '/api/v1/health')) {
            return null;
        }

        // По дефолту требуется авторизация
        return self::PERMISSION_SYSTEM_ADMIN;
    }

    /**
     * Проверяет, может ли пользователь выполнить действие с документом
     */
    public function canPerformDocumentAction(User $user, string $action): bool
    {
        return match ($action) {
            'upload' => $this->canUploadDocuments($user),
            'delete' => $this->canDeleteDocuments($user),
            'view' => $this->canViewDocuments($user),
            default => false,
        };
    }

    /**
     * Получает список доступных действий для пользователя
     */
    public function getAvailableActions(User $user): array
    {
        $actions = [];

        if ($this->canViewDocuments($user)) {
            $actions[] = 'view_documents';
        }

        if ($this->canUploadDocuments($user)) {
            $actions[] = 'upload_documents';
        }

        if ($this->canDeleteDocuments($user)) {
            $actions[] = 'delete_documents';
        }

        if ($this->canSearch($user)) {
            $actions[] = 'search';
        }

        if ($this->canManageUsers($user)) {
            $actions[] = 'manage_users';
        }

        return $actions;
    }
}
