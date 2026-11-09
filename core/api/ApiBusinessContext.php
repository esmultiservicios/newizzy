<?php
/** Identidad y autorización para endpoints de negocio v1. */
declare(strict_types=1);

final class IzzyApiBusinessContext
{
    private const MENUS = [
        'clientes'=>'clientes', 'productos'=>'productos',
        'proveedores'=>'proveedores', 'facturas'=>'facturas',
        'empresa'=>'empresa', 'secuencias'=>'secuencia'
    ];

    public static function identificar(string $modulo, string $accion): array
    {
        if (!isset(self::MENUS[$modulo])) throw new InvalidArgumentException('Módulo API no reconocido.');
        $authorization = (string)($_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '');
        if ($authorization==='' && function_exists('getallheaders')) {
            foreach(getallheaders() as $name=>$value) if(strcasecmp((string)$name,'Authorization')===0){$authorization=(string)$value;break;}
        }
        if (!preg_match('/^Bearer\s+(\S+)$/i',trim($authorization),$matches)) throw new DomainException('Se requiere Authorization: Bearer.');
        $token=$matches[1];
        if (str_starts_with($token,'izzya_')) {
            require_once __DIR__.'/ApiClientAuthService.php';
            $scope=$modulo.':'.($accion==='read'?'read':($modulo==='facturas'?'create':'write'));
            $tenant=(new IzzyApiClientAuthService())->autenticarToken($token,$scope);
            return ['tipo'=>'integracion','db'=>$tenant['db_cliente'],'empresa_id'=>null,'users_id'=>null,'solo_lectura'=>false];
        }
        if (!str_starts_with($token,'izzy_')) throw new DomainException('Token inválido.');
        require_once dirname(__DIR__).'/services/AuthService.php';
        $auth=new IzzyApiAuthService();
        $session=$auth->autenticarToken($token);
        $perfil=$auth->miPerfil($token);
        $clave=self::MENUS[$modulo];
        if (!in_array($clave,$perfil['permisos']['submenus']??[],true)
            && !in_array($clave,$perfil['permisos']['submenus1']??[],true)) throw new DomainException('Su privilegio no tiene acceso a este módulo.');
        $allowed=$accion==='read'?['consultar']:['guardar','crear','editar','actualizar'];
        if ($accion==='update') $allowed=['editar','actualizar'];
        if ($accion==='create') $allowed=['guardar','crear'];
        if (!array_intersect($allowed,$perfil['acciones']??[])) throw new DomainException('El usuario no tiene permiso para esta operación.');
        if ($accion!=='read' && !empty($perfil['cliente']['solo_lectura_pago'])) throw new DomainException('Cuenta restringida a consulta por estado de pago.');
        return ['tipo'=>'movil','db'=>$session['db_cliente'],'empresa_id'=>(int)$session['empresa_id'],'users_id'=>(int)$session['users_id'],'solo_lectura'=>!empty($perfil['cliente']['solo_lectura_pago'])];
    }
}
