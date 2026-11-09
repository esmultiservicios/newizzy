<?php
/** Navegación móvil: centraliza el cálculo en AuthService y evita duplicar permisos. */
declare(strict_types=1);

final class IzzyApiNavigationService
{
    private IzzyApiAuthService $auth;

    public function __construct()
    {
        require_once dirname(__DIR__) . '/services/AuthService.php';
        $this->auth = new IzzyApiAuthService();
    }

    public function menus(string $accessToken): array
    {
        return $this->auth->menusNavegacion($accessToken);
    }
}
