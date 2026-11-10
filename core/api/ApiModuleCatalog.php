<?php
/** Catálogo único de recursos disponibles para integraciones IZZY API v1. */
declare(strict_types=1);

final class IzzyApiModuleCatalog
{
    public static function items(): array
    {
        return [
            ['clave'=>'clientes','nombre'=>'Clientes','icono'=>'fas fa-users','descripcion'=>'Consultar clientes de la empresa.','nota'=>'GET · POST · PATCH (fase 4.1)'],
            ['clave'=>'productos','nombre'=>'Productos e inventario','icono'=>'fas fa-boxes','descripcion'=>'Consultar el catálogo de productos.','nota'=>'GET disponible · Movimientos pendientes'],
            ['clave'=>'proveedores','nombre'=>'Proveedores','icono'=>'fas fa-truck','descripcion'=>'Consultar los proveedores registrados.','nota'=>'GET · POST · PATCH (fase 4.1)'],
            ['clave'=>'facturas','nombre'=>'Facturas','icono'=>'fas fa-file-invoice-dollar','descripcion'=>'Consultar facturas y líneas de detalle.','nota'=>'GET disponible · Emisión pendiente'],
            ['clave'=>'empresa','nombre'=>'Empresa','icono'=>'fas fa-building','descripcion'=>'Consultar la información de la empresa.','nota'=>'GET disponible'],
            ['clave'=>'secuencias','nombre'=>'Secuencias de facturación','icono'=>'fas fa-list-ol','descripcion'=>'Consultar CAI, rangos y vigencia fiscal sin reservar números.','nota'=>'GET disponible · Sin emisión']
        ];
    }
}
