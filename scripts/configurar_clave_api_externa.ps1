# IZZY v014: ejecutar localmente con PowerShell en Laragon.
# No sube ni imprime claves. No reemplaza claves ya existentes.
param([string]$EnvPath = 'C:\credentials\newizzy\.env')
$ErrorActionPreference = 'Stop'
if (-not [System.IO.File]::Exists($EnvPath)) { throw "No existe el .env: $EnvPath. Compruebe IZZY_ENV_FILE en su entorno." }
$lines = [System.IO.File]::ReadAllLines($EnvPath)
$existing = @($lines | Where-Object { $_ -match '^\s*IZZY_EXTERNAL_API_ENCRYPTION_KEY\s*=' })
if ($existing.Count -gt 1) { throw 'Hay variables IZZY_EXTERNAL_API_ENCRYPTION_KEY duplicadas. Revise manualmente el .env.' }
if ($existing.Count -eq 1) {
    $value = ($existing[0] -split '=', 2)[1].Trim().Trim([char[]]@([char]34,[char]39))
    if ($value -match '^[a-fA-F0-9]{64}$') { Write-Output 'La clave AES existente es valida. Se conserva sin cambios.'; exit 0 }
    throw 'Ya existe una clave AES invalida. No se reemplazara automaticamente para evitar perdida de acceso a credenciales cifradas.'
}
$bytes = New-Object byte[] 32
$generator = [System.Security.Cryptography.RandomNumberGenerator]::Create()
try { $generator.GetBytes($bytes) } finally { $generator.Dispose() }
$hex = ([System.BitConverter]::ToString($bytes)).Replace('-', '').ToLowerInvariant()
# Agregar al archivo protegido sin registrar el secreto en salida o consola.
$original = [System.IO.File]::ReadAllText($EnvPath)
$separator = if ($original.EndsWith("`n") -or $original.Length -eq 0) { '' } else { "`r`n" }
$addition = $separator + 'IZZY_EXTERNAL_API_ENCRYPTION_KEY=' + $hex + "`r`n"
[System.IO.File]::AppendAllText($EnvPath, $addition, (New-Object System.Text.UTF8Encoding($false)))
Write-Output 'Clave AES de IZZY creada en el archivo protegido. Reinicie Laragon, ejecute Diagnostico y guarde la conexion.'
