<?php
/** Construye navegación usando la autenticación y el token de usuario existentes. */
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
        $perfil = $this->auth->miPerfil($accessToken);
        $sesion = $this->auth->autenticarToken($accessToken);
        $plan = (int)($perfil['cliente']['plan_id'] ?? 0);
        $privilegio = (int)($perfil['usuario']['privilegio_id'] ?? 0);
        if ($plan <= 0 || $privilegio <= 0) return [];

        $peticionAjax = true;
        require_once dirname(__DIR__) . '/configGenerales.php';
        require_once dirname(__DIR__) . '/mainModel.php';
        $db = (new mainModel())->connectionDBLocal($sesion['db_cliente']);
        try {
            $menus = [];
            $stmt = $db->prepare('SELECT m.menu_id AS id, m.name AS clave, m.descripcion AS nombre, m.icon AS icono, m.orden '
                . 'FROM menu m INNER JOIN acceso_menu a ON a.menu_id=m.menu_id '
                . 'INNER JOIN menu_plan p ON p.menu_id=m.menu_id AND p.planes_id=? '
                . 'WHERE a.privilegio_id=? AND a.estado=1 AND p.estado=1 AND m.visible=1 ORDER BY m.orden,m.menu_id');
            if (!$stmt) throw new RuntimeException('No se pudieron consultar los menús.');
            $stmt->bind_param('ii', $plan, $privilegio); $stmt->execute(); $rows = $stmt->get_result();
            while ($row = $rows->fetch_assoc()) {
                $row['id']=(int)$row['id']; $row['orden']=(int)$row['orden']; $row['submenus']=[];
                $menus[$row['id']]=$row;
            }
            $stmt->close();

            $submenus=[];
            $stmt = $db->prepare('SELECT s.submenu_id AS id, s.menu_id, s.name AS clave, s.descripcion AS nombre, s.icon AS icono, s.orden '
                . 'FROM submenu s INNER JOIN acceso_submenu a ON a.submenu_id=s.submenu_id '
                . 'INNER JOIN submenu_plan p ON p.submenu_id=s.submenu_id AND p.planes_id=? '
                . 'WHERE a.privilegio_id=? AND a.estado=1 AND p.estado=1 AND s.visible=1 ORDER BY s.orden,s.submenu_id');
            if (!$stmt) throw new RuntimeException('No se pudieron consultar los submenús.');
            $stmt->bind_param('ii', $plan, $privilegio); $stmt->execute(); $rows=$stmt->get_result();
            while ($row=$rows->fetch_assoc()) {
                $parent=(int)$row['menu_id']; if (!isset($menus[$parent])) continue;
                $row['id']=(int)$row['id']; $row['orden']=(int)$row['orden']; $row['submenu_nivel_2']=[];
                $submenus[$row['id']]=$row; $menus[$parent]['submenus'][]=$row['id'];
            }
            $stmt->close();

            $stmt = $db->prepare('SELECT s1.submenu1_id AS id, s1.submenu_id, s1.name AS clave, s1.descripcion AS nombre, s1.icon AS icono, s1.orden '
                . 'FROM submenu1 s1 INNER JOIN acceso_submenu1 a ON a.submenu1_id=s1.submenu1_id '
                . 'INNER JOIN submenu1_plan p ON p.submenu1_id=s1.submenu1_id AND p.planes_id=? '
                . 'WHERE a.privilegio_id=? AND a.estado=1 AND p.estado=1 AND s1.visible=1 ORDER BY s1.orden,s1.submenu1_id');
            if (!$stmt) throw new RuntimeException('No se pudieron consultar los submenús de nivel 2.');
            $stmt->bind_param('ii', $plan, $privilegio); $stmt->execute(); $rows=$stmt->get_result();
            while ($row=$rows->fetch_assoc()) {
                $parent=(int)$row['submenu_id']; if (!isset($submenus[$parent])) continue;
                $row['id']=(int)$row['id']; $row['orden']=(int)$row['orden'];
                $submenus[$parent]['submenu_nivel_2'][]=$row;
            }
            $stmt->close();

            foreach ($menus as &$menu) {
                $children=[];
                foreach ($menu['submenus'] as $submenuId) $children[]=$submenus[$submenuId];
                $menu['submenus']=$children;
            }
            unset($menu);
            return array_values($menus);
        } finally { $db->close(); }
    }
}
