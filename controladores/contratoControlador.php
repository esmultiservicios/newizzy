<?php
if ($peticionAjax) {
    require_once "../modelos/contratoModelo.php";
} else {
    require_once "./modelos/contratoModelo.php";
}

class contratoControlador extends contratoModelo
{
    private function respuesta($status, $title, $message, array $extra = [])
    {
        return json_encode(array_merge([
            'status' => $status,
            'title' => $title,
            'message' => $message
        ], $extra), JSON_UNESCAPED_UNICODE);
    }

    private function numero($valor)
    {
        $valor = str_replace(',', '', trim((string)$valor));
        return is_numeric($valor) ? (float)$valor : null;
    }

    private function fechaValida($fecha, $permitirVacia = false)
    {
        $fecha = trim((string)$fecha);
        if ($permitirVacia && $fecha === '') {
            return true;
        }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
            return false;
        }
        $d = DateTime::createFromFormat('Y-m-d', $fecha);
        return $d && $d->format('Y-m-d') === $fecha;
    }

    private function salarioCalculado($salarioMensual, $pagoPlanificadoId, $semanal)
    {
        if ($pagoPlanificadoId === 1) {
            return $semanal === 1
                ? round($salarioMensual / 4, 2)
                : round(($salarioMensual / 30) * 7, 2);
        }
        if ($pagoPlanificadoId === 2) {
            return round(($salarioMensual / 30) * 15, 2);
        }
        return round($salarioMensual, 2);
    }

    private function leerDatosContrato($esEdicion = false)
    {
        $required = [
            'contrato_colaborador_id' => 'Empleado',
            'contrato_tipo_contrato_id' => 'Tipo de contrato',
            'contrato_pago_planificado_id' => 'Pago planificado',
            'contrato_tipo_empleado_id' => 'Tipo de empleado',
            'contrato_salario_mensual' => 'Salario mensual',
            'contrato_fecha_inicio' => 'Fecha de inicio'
        ];

        if ($esEdicion) {
            $required = ['contrato_id' => 'Contrato'] + $required;
        }

        $faltantes = [];
        foreach ($required as $campo => $nombre) {
            if (!isset($_POST[$campo]) || trim((string)$_POST[$campo]) === '') {
                $faltantes[] = $nombre;
            }
        }

        if ($faltantes) {
            return [null, $this->respuesta(
                'error',
                'Campos incompletos',
                'Completa: ' . implode(', ', $faltantes) . '.',
                ['missing_fields' => array_keys(array_filter(
                    $required,
                    function ($label, $field) use ($faltantes) {
                        return in_array($label, $faltantes, true);
                    },
                    ARRAY_FILTER_USE_BOTH
                ))]
            )];
        }

        $contrato_id = $esEdicion ? (int)mainModel::cleanString($_POST['contrato_id']) : 0;
        $colaborador_id = (int)mainModel::cleanString($_POST['contrato_colaborador_id']);
        $tipo_contrato_id = (int)mainModel::cleanString($_POST['contrato_tipo_contrato_id']);
        $pago_planificado_id = (int)mainModel::cleanString($_POST['contrato_pago_planificado_id']);
        $tipo_empleado_id = (int)mainModel::cleanString($_POST['contrato_tipo_empleado_id']);

        $salario_mensual = $this->numero($_POST['contrato_salario_mensual']);
        if ($salario_mensual === null || $salario_mensual <= 0) {
            return [null, $this->respuesta(
                'error',
                'Salario inválido',
                'El salario mensual debe ser mayor que cero.'
            )];
        }

        $fecha_inicio = mainModel::cleanString($_POST['contrato_fecha_inicio']);
        $fecha_fin = isset($_POST['contrato_fecha_fin']) && $_POST['contrato_fecha_fin'] !== null
            ? mainModel::cleanString($_POST['contrato_fecha_fin'])
            : '';

        if (!$this->fechaValida($fecha_inicio)) {
            return [null, $this->respuesta('error', 'Fecha inválida', 'Revisa la fecha de inicio del contrato.')];
        }

        if (!$this->fechaValida($fecha_fin, true)) {
            return [null, $this->respuesta('error', 'Fecha inválida', 'Revisa la fecha final del contrato.')];
        }

        if ($fecha_fin !== '' && $fecha_fin < $fecha_inicio) {
            return [null, $this->respuesta(
                'error',
                'Rango de fechas inválido',
                'La fecha final no puede ser anterior a la fecha de inicio.'
            )];
        }

        $notas = mainModel::cleanString($_POST['contrato_notas'] ?? '');
        if (mb_strlen($notas) > 255) {
            return [null, $this->respuesta(
                'error',
                'Notas demasiado largas',
                'Las notas del contrato admiten un máximo de 255 caracteres.'
            )];
        }

        $estado = isset($_POST['contrato_activo']) && (int)$_POST['contrato_activo'] === 1 ? 1 : 2;
        $semanal = ($pago_planificado_id === 1 && !empty($_POST['calculo_semanal'])) ? 1 : 0;
        $salario = $this->salarioCalculado($salario_mensual, $pago_planificado_id, $semanal);

        return [[
            'contrato_id' => $contrato_id,
            'colaborador_id' => $colaborador_id,
            'tipo_contrato_id' => $tipo_contrato_id,
            'pago_planificado_id' => $pago_planificado_id,
            'tipo_empleado_id' => $tipo_empleado_id,
            'salario_mensual' => $salario_mensual,
            'salario' => $salario,
            'fecha_inicio' => $fecha_inicio,
            'fecha_fin' => $fecha_fin,
            'notas' => $notas,
            'estado' => $estado,
            'semanal' => $semanal
        ], null];
    }

    public function agregar_contrato_controlador()
    {
        $validacion = mainModel::validarSesion();
        if ($validacion['error']) {
            return $this->respuesta(
                'unauthorized',
                'Error de sesión',
                $validacion['mensaje'],
                ['redirect' => $validacion['redireccion'] ?? null]
            );
        }

        [$datos, $error] = $this->leerDatosContrato(false);
        if ($error !== null) {
            return $error;
        }

        if ($this->valid_contrato_modelo($datos['colaborador_id'])->num_rows > 0) {
            return $this->respuesta(
                'error',
                'Contrato activo existente',
                'El colaborador ya tiene un contrato activo.'
            );
        }

        $mainModel = new mainModel();
        $planConfig = $mainModel->getPlanConfiguracionMainModel();

        if (isset($planConfig['contratos'])) {
            $limiteContratos = (int)$planConfig['contratos'];

            if ($limiteContratos === 0) {
                return $this->respuesta(
                    'error',
                    'Acceso restringido',
                    'Tu plan actual no permite registrar contratos.'
                );
            }

            if ($limiteContratos > 0 && $this->getTotalContratosRegistrados() >= $limiteContratos) {
                return $this->respuesta(
                    'error',
                    'Límite alcanzado',
                    "Tu plan permite un máximo de {$limiteContratos} contratos activos."
                );
            }
        }

        $datos['usuario'] = (int)($_SESSION['colaborador_id_sd'] ?? 0);
        $datos['fecha_registro'] = date('Y-m-d H:i:s');

        if (!$this->agregar_contrato_modelo($datos)) {
            error_log('IZZY contrato alta: ' . $this->ultimo_error_contrato_modelo());
            return $this->respuesta(
                'error',
                'No se pudo registrar',
                'No fue posible registrar el contrato. Revisa los datos e intenta nuevamente.'
            );
        }

        return $this->respuesta(
            'success',
            'Contrato registrado',
            'El contrato se registró correctamente.',
            [
                'funcion' => 'listar_contratos();getTipoContrato();getPagoPlanificado();getTipoEmpleado();getEmpleado();',
                'clearForm' => true,
                'modal' => true
            ]
        );
    }

    public function edit_contrato_controlador()
    {
        $validacion = mainModel::validarSesion();
        if ($validacion['error']) {
            return $this->respuesta(
                'unauthorized',
                'Error de sesión',
                $validacion['mensaje'],
                ['redirect' => $validacion['redireccion'] ?? null]
            );
        }

        [$datos, $error] = $this->leerDatosContrato(true);
        if ($error !== null) {
            return $error;
        }

        if ($datos['contrato_id'] <= 0) {
            return $this->respuesta('error', 'Contrato inválido', 'No se pudo identificar el contrato a actualizar.');
        }

        if (
            $datos['estado'] === 1 &&
            $this->valid_contrato_modelo($datos['colaborador_id'], $datos['contrato_id'])->num_rows > 0
        ) {
            return $this->respuesta(
                'error',
                'Contrato activo existente',
                'El colaborador ya tiene otro contrato activo.'
            );
        }

        if (!$this->edit_contrato_modelo($datos)) {
            error_log('IZZY contrato edición: ' . $this->ultimo_error_contrato_modelo());
            return $this->respuesta(
                'error',
                'No se pudo actualizar',
                'No fue posible actualizar el contrato. Revisa los datos e intenta nuevamente.'
            );
        }

        return $this->respuesta(
            'success',
            'Contrato actualizado',
            'El contrato se actualizó correctamente.',
            [
                'funcion' => 'listar_contratos();getTipoContrato();getPagoPlanificado();getTipoEmpleado();getEmpleado();',
                'modal' => true
            ]
        );
    }

    public function delete_contrato_controlador()
    {
        $validacion = mainModel::validarSesion();
        if ($validacion['error']) {
            return $this->respuesta(
                'unauthorized',
                'Error de sesión',
                $validacion['mensaje'],
                ['redirect' => $validacion['redireccion'] ?? null]
            );
        }

        $contrato_id = isset($_POST['contrato_id']) ? (int)mainModel::cleanString($_POST['contrato_id']) : 0;
        if ($contrato_id <= 0) {
            return $this->respuesta('error', 'Contrato inválido', 'No se pudo identificar el contrato a eliminar.');
        }

        if ($this->valid_contrato_nomina_modelo($contrato_id)->num_rows > 0) {
            return $this->respuesta(
                'error',
                'No se puede eliminar',
                'El contrato tiene información de nómina asociada y debe conservarse.'
            );
        }

        if (!$this->delete_contrato_modelo($contrato_id)) {
            return $this->respuesta('error', 'No se pudo eliminar', 'No fue posible eliminar el contrato.');
        }

        return $this->respuesta(
            'success',
            'Contrato eliminado',
            'El contrato se eliminó correctamente.',
            ['funcion' => 'listar_contratos();']
        );
    }
}
