<?php

declare(strict_types=1);

namespace RagSystem\Tests\Unit\Application\Service;

use RagSystem\Tests\Helpers\BaseTestCase;
use RagSystem\Application\Service\AuthorizationService;
use RagSystem\Domain\Model\User;
use Mockery;

/**
 * @covers \RagSystem\Application\Service\AuthorizationService
 * @covers \RagSystem\Domain\Model\User
 */
class AuthorizationServiceTest extends BaseTestCase
{
    private AuthorizationService $authorizationService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->authorizationService = new AuthorizationService();
    }

    /**
     * Тест проверки разрешения для администратора
     */
    public function testHasPermissionAdmin(): void
    {
        $user = new User('admin', 'admin@example.com', 'password_hash', User::ROLE_ADMIN);
        
        $result = $this->authorizationService->hasPermission($user, AuthorizationService::PERMISSION_DOCUMENT_UPLOAD);
        
        $this->assertTrue($result);
    }

    /**
     * Тест проверки разрешения для обычного пользователя
     */
    public function testHasPermissionUser(): void
    {
        $user = new User('user', 'user@example.com', 'password_hash', User::ROLE_USER);
        
        $result = $this->authorizationService->hasPermission($user, AuthorizationService::PERMISSION_DOCUMENT_VIEW);
        
        $this->assertTrue($result);
    }

    /**
     * Тест проверки отсутствующего разрешения для пользователя
     */
    public function testHasPermissionUserDenied(): void
    {
        $user = new User('user', 'user@example.com', 'password_hash', User::ROLE_USER);
        
        $result = $this->authorizationService->hasPermission($user, AuthorizationService::PERMISSION_DOCUMENT_UPLOAD);
        
        $this->assertFalse($result);
    }

    /**
     * Тест проверки разрешения для неактивного пользователя
     */
    public function testHasPermissionInactiveUser(): void
    {
        $user = new User('user', 'user@example.com', 'password_hash', User::ROLE_USER, false);
        
        $result = $this->authorizationService->hasPermission($user, AuthorizationService::PERMISSION_DOCUMENT_VIEW);
        
        $this->assertFalse($result);
    }

    /**
     * Тест проверки администратора
     */
    public function testIsAdmin(): void
    {
        $user = new User('admin', 'admin@example.com', 'password_hash', User::ROLE_ADMIN);
        
        $result = $this->authorizationService->isAdmin($user);
        
        $this->assertTrue($result);
    }

    /**
     * Тест проверки администратора для обычного пользователя
     */
    public function testIsAdminUser(): void
    {
        $user = new User('user', 'user@example.com', 'password_hash', User::ROLE_USER);
        
        $result = $this->authorizationService->isAdmin($user);
        
        $this->assertFalse($result);
    }

    /**
     * Тест проверки загрузки документов
     */
    public function testCanUploadDocuments(): void
    {
        $admin = new User('admin', 'admin@example.com', 'password_hash', User::ROLE_ADMIN);
        $user = new User('user', 'user@example.com', 'password_hash', User::ROLE_USER);
        
        $this->assertTrue($this->authorizationService->canUploadDocuments($admin));
        $this->assertFalse($this->authorizationService->canUploadDocuments($user));
    }

    /**
     * Тест проверки удаления документов
     */
    public function testCanDeleteDocuments(): void
    {
        $admin = new User('admin', 'admin@example.com', 'password_hash', User::ROLE_ADMIN);
        $user = new User('user', 'user@example.com', 'password_hash', User::ROLE_USER);
        
        $this->assertTrue($this->authorizationService->canDeleteDocuments($admin));
        $this->assertFalse($this->authorizationService->canDeleteDocuments($user));
    }

    /**
     * Тест проверки просмотра документов
     */
    public function testCanViewDocuments(): void
    {
        $admin = new User('admin', 'admin@example.com', 'password_hash', User::ROLE_ADMIN);
        $user = new User('user', 'user@example.com', 'password_hash', User::ROLE_USER);
        
        $this->assertTrue($this->authorizationService->canViewDocuments($admin));
        $this->assertTrue($this->authorizationService->canViewDocuments($user));
    }

    /**
     * Тест проверки поиска
     */
    public function testCanSearch(): void
    {
        $admin = new User('admin', 'admin@example.com', 'password_hash', User::ROLE_ADMIN);
        $user = new User('user', 'user@example.com', 'password_hash', User::ROLE_USER);
        
        $this->assertTrue($this->authorizationService->canSearch($admin));
        $this->assertTrue($this->authorizationService->canSearch($user));
    }

    /**
     * Тест проверки управления пользователями
     */
    public function testCanManageUsers(): void
    {
        $admin = new User('admin', 'admin@example.com', 'password_hash', User::ROLE_ADMIN);
        $user = new User('user', 'user@example.com', 'password_hash', User::ROLE_USER);
        
        $this->assertTrue($this->authorizationService->canManageUsers($admin));
        $this->assertFalse($this->authorizationService->canManageUsers($user));
    }

    /**
     * Тест получения разрешений пользователя
     */
    public function testGetUserPermissions(): void
    {
        $admin = new User('admin', 'admin@example.com', 'password_hash', User::ROLE_ADMIN);
        $user = new User('user', 'user@example.com', 'password_hash', User::ROLE_USER);
        
        $adminPermissions = $this->authorizationService->getUserPermissions($admin);
        $userPermissions = $this->authorizationService->getUserPermissions($user);
        
        $this->assertIsArray($adminPermissions);
        $this->assertIsArray($userPermissions);
        $this->assertContains(AuthorizationService::PERMISSION_DOCUMENT_UPLOAD, $adminPermissions);
        $this->assertNotContains(AuthorizationService::PERMISSION_DOCUMENT_UPLOAD, $userPermissions);
        $this->assertContains(AuthorizationService::PERMISSION_DOCUMENT_VIEW, $userPermissions);
    }

    /**
     * Тест проверки доступа к API эндпоинту
     */
    public function testCanAccessApiEndpoint(): void
    {
        $admin = new User('admin', 'admin@example.com', 'password_hash', User::ROLE_ADMIN);
        $user = new User('user', 'user@example.com', 'password_hash', User::ROLE_USER);
        
        // POST /api/v1/documents - только админ
        $this->assertTrue($this->authorizationService->canAccessApiEndpoint($admin, 'POST', '/api/v1/documents'));
        $this->assertFalse($this->authorizationService->canAccessApiEndpoint($user, 'POST', '/api/v1/documents'));
        
        // GET /api/v1/documents - все пользователи
        $this->assertTrue($this->authorizationService->canAccessApiEndpoint($admin, 'GET', '/api/v1/documents'));
        $this->assertTrue($this->authorizationService->canAccessApiEndpoint($user, 'GET', '/api/v1/documents'));
        
        // POST /api/v1/query - все пользователи
        $this->assertTrue($this->authorizationService->canAccessApiEndpoint($admin, 'POST', '/api/v1/query'));
        $this->assertTrue($this->authorizationService->canAccessApiEndpoint($user, 'POST', '/api/v1/query'));
        
        // GET /api/v1/users - только админ
        $this->assertTrue($this->authorizationService->canAccessApiEndpoint($admin, 'GET', '/api/v1/users'));
        $this->assertFalse($this->authorizationService->canAccessApiEndpoint($user, 'GET', '/api/v1/users'));
        
        // GET /api/v1/auth - все пользователи
        $this->assertTrue($this->authorizationService->canAccessApiEndpoint($admin, 'GET', '/api/v1/auth'));
        $this->assertTrue($this->authorizationService->canAccessApiEndpoint($user, 'GET', '/api/v1/auth'));
        
        // GET /api/v1/health - все пользователи
        $this->assertTrue($this->authorizationService->canAccessApiEndpoint($admin, 'GET', '/api/v1/health'));
        $this->assertTrue($this->authorizationService->canAccessApiEndpoint($user, 'GET', '/api/v1/health'));
    }

    /**
     * Тест проверки действия с документом
     */
    public function testCanPerformDocumentAction(): void
    {
        $admin = new User('admin', 'admin@example.com', 'password_hash', User::ROLE_ADMIN);
        $user = new User('user', 'user@example.com', 'password_hash', User::ROLE_USER);
        
        $this->assertTrue($this->authorizationService->canPerformDocumentAction($admin, 'upload'));
        $this->assertFalse($this->authorizationService->canPerformDocumentAction($user, 'upload'));
        
        $this->assertTrue($this->authorizationService->canPerformDocumentAction($admin, 'delete'));
        $this->assertFalse($this->authorizationService->canPerformDocumentAction($user, 'delete'));
        
        $this->assertTrue($this->authorizationService->canPerformDocumentAction($admin, 'view'));
        $this->assertTrue($this->authorizationService->canPerformDocumentAction($user, 'view'));
        
        $this->assertFalse($this->authorizationService->canPerformDocumentAction($admin, 'unknown'));
        $this->assertFalse($this->authorizationService->canPerformDocumentAction($user, 'unknown'));
    }

    /**
     * Тест получения доступных действий
     */
    public function testGetAvailableActions(): void
    {
        $admin = new User('admin', 'admin@example.com', 'password_hash', User::ROLE_ADMIN);
        $user = new User('user', 'user@example.com', 'password_hash', User::ROLE_USER);
        
        $adminActions = $this->authorizationService->getAvailableActions($admin);
        $userActions = $this->authorizationService->getAvailableActions($user);
        
        $this->assertIsArray($adminActions);
        $this->assertIsArray($userActions);
        
        $this->assertContains('view_documents', $adminActions);
        $this->assertContains('upload_documents', $adminActions);
        $this->assertContains('delete_documents', $adminActions);
        $this->assertContains('search', $adminActions);
        $this->assertContains('manage_users', $adminActions);
        
        $this->assertContains('view_documents', $userActions);
        $this->assertNotContains('upload_documents', $userActions);
        $this->assertNotContains('delete_documents', $userActions);
        $this->assertContains('search', $userActions);
        $this->assertNotContains('manage_users', $userActions);
    }

    /**
     * Тест с русскими символами
     */
    public function testWithRussianText(): void
    {
        $user = new User('пользователь', 'user@example.com', 'password_hash', User::ROLE_USER);
        
        $result = $this->authorizationService->canViewDocuments($user);
        
        $this->assertTrue($result);
    }

    /**
     * Тест с неизвестной ролью
     */
    public function testUnknownRole(): void
    {
        $user = new User('user', 'user@example.com', 'password_hash', 'unknown_role');
        
        $result = $this->authorizationService->hasPermission($user, AuthorizationService::PERMISSION_DOCUMENT_VIEW);
        
        $this->assertFalse($result);
    }

    /**
     * Тест с пустой ролью
     */
    public function testEmptyRole(): void
    {
        $user = new User('user', 'user@example.com', 'password_hash', '');
        
        $result = $this->authorizationService->hasPermission($user, AuthorizationService::PERMISSION_DOCUMENT_VIEW);
        
        $this->assertFalse($result);
    }

    /**
     * Тест с различными HTTP методами
     */
    public function testDifferentHttpMethods(): void
    {
        $admin = new User('admin', 'admin@example.com', 'password_hash', User::ROLE_ADMIN);
        $user = new User('user', 'user@example.com', 'password_hash', User::ROLE_USER);
        
        // PUT /api/v1/documents - не определено, доступ для всех
        $this->assertTrue($this->authorizationService->canAccessApiEndpoint($admin, 'PUT', '/api/v1/documents'));
        $this->assertTrue($this->authorizationService->canAccessApiEndpoint($user, 'PUT', '/api/v1/documents'));
        
        // PATCH /api/v1/documents - не определено, доступ для всех
        $this->assertTrue($this->authorizationService->canAccessApiEndpoint($admin, 'PATCH', '/api/v1/documents'));
        $this->assertTrue($this->authorizationService->canAccessApiEndpoint($user, 'PATCH', '/api/v1/documents'));
    }

    /**
     * Тест с различными путями API
     */
    public function testDifferentApiPaths(): void
    {
        $admin = new User('admin', 'admin@example.com', 'password_hash', User::ROLE_ADMIN);
        $user = new User('user', 'user@example.com', 'password_hash', User::ROLE_USER);
        
        // /api/v1/documents/123 - GET
        $this->assertTrue($this->authorizationService->canAccessApiEndpoint($admin, 'GET', '/api/v1/documents/123'));
        $this->assertTrue($this->authorizationService->canAccessApiEndpoint($user, 'GET', '/api/v1/documents/123'));
        
        // /api/v1/documents/123 - DELETE
        $this->assertTrue($this->authorizationService->canAccessApiEndpoint($admin, 'DELETE', '/api/v1/documents/123'));
        $this->assertFalse($this->authorizationService->canAccessApiEndpoint($user, 'DELETE', '/api/v1/documents/123'));
        
        // /api/v1/query/search - POST
        $this->assertTrue($this->authorizationService->canAccessApiEndpoint($admin, 'POST', '/api/v1/query/search'));
        $this->assertTrue($this->authorizationService->canAccessApiEndpoint($user, 'POST', '/api/v1/query/search'));
    }

    /**
     * Тест для покрытия приватного метода getPermissionForEndpoint - различные HTTP методы для документов
     */
    public function testGetPermissionForEndpointDocumentsMethods(): void
    {
        $admin = new User('admin', 'admin@example.com', 'password_hash', User::ROLE_ADMIN);
        $user = new User('user', 'user@example.com', 'password_hash', User::ROLE_USER);
        
        // POST /api/v1/documents - загрузка документов
        $this->assertTrue($this->authorizationService->canAccessApiEndpoint($admin, 'POST', '/api/v1/documents'));
        $this->assertFalse($this->authorizationService->canAccessApiEndpoint($user, 'POST', '/api/v1/documents'));
        
        // PUT /api/v1/documents - не определено, доступ для всех
        $this->assertTrue($this->authorizationService->canAccessApiEndpoint($admin, 'PUT', '/api/v1/documents'));
        $this->assertTrue($this->authorizationService->canAccessApiEndpoint($user, 'PUT', '/api/v1/documents'));
        
        // PATCH /api/v1/documents - не определено, доступ для всех
        $this->assertTrue($this->authorizationService->canAccessApiEndpoint($admin, 'PATCH', '/api/v1/documents'));
        $this->assertTrue($this->authorizationService->canAccessApiEndpoint($user, 'PATCH', '/api/v1/documents'));
    }

    /**
     * Тест для покрытия приватного метода getPermissionForEndpoint - различные HTTP методы для query
     */
    public function testGetPermissionForEndpointQueryMethods(): void
    {
        $admin = new User('admin', 'admin@example.com', 'password_hash', User::ROLE_ADMIN);
        $user = new User('user', 'user@example.com', 'password_hash', User::ROLE_USER);
        
        // POST /api/v1/query - поиск
        $this->assertTrue($this->authorizationService->canAccessApiEndpoint($admin, 'POST', '/api/v1/query'));
        $this->assertTrue($this->authorizationService->canAccessApiEndpoint($user, 'POST', '/api/v1/query'));
        
        // GET /api/v1/query - не определено, доступ для всех
        $this->assertTrue($this->authorizationService->canAccessApiEndpoint($admin, 'GET', '/api/v1/query'));
        $this->assertTrue($this->authorizationService->canAccessApiEndpoint($user, 'GET', '/api/v1/query'));
        
        // PUT /api/v1/query - не определено, доступ для всех
        $this->assertTrue($this->authorizationService->canAccessApiEndpoint($admin, 'PUT', '/api/v1/query'));
        $this->assertTrue($this->authorizationService->canAccessApiEndpoint($user, 'PUT', '/api/v1/query'));
    }

    /**
     * Тест для покрытия приватного метода getPermissionForEndpoint - различные HTTP методы для users
     */
    public function testGetPermissionForEndpointUsersMethods(): void
    {
        $admin = new User('admin', 'admin@example.com', 'password_hash', User::ROLE_ADMIN);
        $user = new User('user', 'user@example.com', 'password_hash', User::ROLE_USER);
        
        // GET /api/v1/users - управление пользователями
        $this->assertTrue($this->authorizationService->canAccessApiEndpoint($admin, 'GET', '/api/v1/users'));
        $this->assertFalse($this->authorizationService->canAccessApiEndpoint($user, 'GET', '/api/v1/users'));
        
        // POST /api/v1/users - управление пользователями
        $this->assertTrue($this->authorizationService->canAccessApiEndpoint($admin, 'POST', '/api/v1/users'));
        $this->assertFalse($this->authorizationService->canAccessApiEndpoint($user, 'POST', '/api/v1/users'));
        
        // PUT /api/v1/users - управление пользователями
        $this->assertTrue($this->authorizationService->canAccessApiEndpoint($admin, 'PUT', '/api/v1/users'));
        $this->assertFalse($this->authorizationService->canAccessApiEndpoint($user, 'PUT', '/api/v1/users'));
        
        // DELETE /api/v1/users - управление пользователями
        $this->assertTrue($this->authorizationService->canAccessApiEndpoint($admin, 'DELETE', '/api/v1/users'));
        $this->assertFalse($this->authorizationService->canAccessApiEndpoint($user, 'DELETE', '/api/v1/users'));
    }

    /**
     * Тест для покрытия приватного метода getPermissionForEndpoint - auth и health endpoints
     */
    public function testGetPermissionForEndpointAuthAndHealth(): void
    {
        $admin = new User('admin', 'admin@example.com', 'password_hash', User::ROLE_ADMIN);
        $user = new User('user', 'user@example.com', 'password_hash', User::ROLE_USER);
        
        // /api/v1/auth - доступно всем
        $this->assertTrue($this->authorizationService->canAccessApiEndpoint($admin, 'GET', '/api/v1/auth'));
        $this->assertTrue($this->authorizationService->canAccessApiEndpoint($user, 'GET', '/api/v1/auth'));
        $this->assertTrue($this->authorizationService->canAccessApiEndpoint($admin, 'POST', '/api/v1/auth'));
        $this->assertTrue($this->authorizationService->canAccessApiEndpoint($user, 'POST', '/api/v1/auth'));
        
        // /api/v1/health - доступно всем
        $this->assertTrue($this->authorizationService->canAccessApiEndpoint($admin, 'GET', '/api/v1/health'));
        $this->assertTrue($this->authorizationService->canAccessApiEndpoint($user, 'GET', '/api/v1/health'));
        $this->assertTrue($this->authorizationService->canAccessApiEndpoint($admin, 'POST', '/api/v1/health'));
        $this->assertTrue($this->authorizationService->canAccessApiEndpoint($user, 'POST', '/api/v1/health'));
    }

    /**
     * Тест для покрытия приватного метода getPermissionForEndpoint - неизвестные пути
     */
    public function testGetPermissionForEndpointUnknownPaths(): void
    {
        $admin = new User('admin', 'admin@example.com', 'password_hash', User::ROLE_ADMIN);
        $user = new User('user', 'user@example.com', 'password_hash', User::ROLE_USER);
        
        // /api/v1/unknown - требует системные права администратора
        $this->assertTrue($this->authorizationService->canAccessApiEndpoint($admin, 'GET', '/api/v1/unknown'));
        $this->assertFalse($this->authorizationService->canAccessApiEndpoint($user, 'GET', '/api/v1/unknown'));
        
        // /api/v1/test - требует системные права администратора
        $this->assertTrue($this->authorizationService->canAccessApiEndpoint($admin, 'POST', '/api/v1/test'));
        $this->assertFalse($this->authorizationService->canAccessApiEndpoint($user, 'POST', '/api/v1/test'));
    }
}
