<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Campaña - {{ $campana->nombre }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; color: #333; margin: 20px; }
        .header { text-align: center; border-bottom: 2px solid #2d6a4f; padding-bottom: 15px; margin-bottom: 20px; }
        .header h1 { color: #2d6a4f; margin: 0; font-size: 22px; }
        .header p { color: #666; margin: 5px 0 0; }
        .section { margin-bottom: 25px; }
        .section h2 { color: #2d6a4f; border-bottom: 1px solid #ddd; padding-bottom: 5px; font-size: 16px; }
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
        .info-item { padding: 5px 0; }
        .info-item strong { color: #555; }
        .kpi-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin: 15px 0; }
        .kpi-box { border: 1px solid #ddd; border-radius: 8px; padding: 15px; text-align: center; }
        .kpi-box .value { font-size: 20px; font-weight: bold; color: #2d6a4f; }
        .kpi-box .label { font-size: 11px; color: #888; margin-top: 3px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; font-size: 11px; }
        th { background-color: #2d6a4f; color: white; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        .footer { margin-top: 30px; text-align: center; font-size: 10px; color: #999; border-top: 1px solid #ddd; padding-top: 10px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>AgroControl - Reporte de Campaña</h1>
        <p>Generado el {{ now()->format('d/m/Y H:i') }}</p>
    </div>

    <div class="section">
        <h2>Datos de la Campaña</h2>
        <div class="info-grid">
            <div class="info-item"><strong>Nombre:</strong> {{ $campana->nombre }}</div>
            <div class="info-item"><strong>Cultivo:</strong> {{ $campana->cultivo }}</div>
            <div class="info-item"><strong>Extensión:</strong> {{ $campana->extension }} {{ $campana->tipo_extension === 'hectarea' ? 'ha' : 'topos' }}</div>
            <div class="info-item"><strong>Estado:</strong> {{ ucfirst($campana->estado) }}</div>
            <div class="info-item"><strong>Fecha inicio:</strong> {{ $campana->fecha_inicio?->format('d/m/Y') }}</div>
            <div class="info-item"><strong>Fecha cierre:</strong> {{ $campana->fecha_cierre?->format('d/m/Y') ?? 'N/A' }}</div>
        </div>
    </div>

    <div class="section">
        <h2>Resumen Financiero</h2>
        <div class="kpi-grid">
            <div class="kpi-box">
                <div class="value">S/ {{ number_format($balance['total_inversion'], 2) }}</div>
                <div class="label">Total Inversión</div>
            </div>
            <div class="kpi-box">
                <div class="value">S/ {{ number_format($balance['total_recaudado'], 2) }}</div>
                <div class="label">Total Recaudado</div>
            </div>
            <div class="kpi-box">
                <div class="value" style="color: {{ $balance['margen_ganancia_neta'] >= 0 ? '#2d6a4f' : '#e63946' }}">
                    S/ {{ number_format($balance['margen_ganancia_neta'], 2) }}
                </div>
                <div class="label">Margen Ganancia Neta</div>
            </div>
            <div class="kpi-box">
                <div class="value">{{ $campana->actividades->count() }}</div>
                <div class="label">Actividades Registradas</div>
            </div>
        </div>
    </div>

    @if($campana->transaccionesFinancieras->count() > 0)
    <div class="section">
        <h2>Transacciones Financieras</h2>
        <table>
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Tipo</th>
                    <th>Categoría</th>
                    <th>Descripción</th>
                    <th>Monto (PEN)</th>
                    <th>Estado Pago</th>
                </tr>
            </thead>
            <tbody>
                @foreach($campana->transaccionesFinancieras->sortByDesc('fecha') as $t)
                <tr>
                    <td>{{ $t->fecha->format('d/m/Y') }}</td>
                    <td>{{ ucfirst($t->tipo) }}</td>
                    <td>{{ $t->categoria ?? '-' }}</td>
                    <td>{{ $t->descripcion ?? '-' }}</td>
                    <td style="text-align: right;">S/ {{ number_format($t->monto, 2) }}</td>
                    <td>{{ $t->estado_pago === 'pagado' ? 'Pagado' : 'Por Cobrar' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    @if($campana->cosechasEnvios->count() > 0)
    <div class="section">
        <h2>Cosechas y Envíos</h2>
        <table>
            <thead>
                <tr>
                    <th>Fecha Envío</th>
                    <th>Tipo Venta</th>
                    <th>Unidades</th>
                    <th>Mercado</th>
                    <th>Estado</th>
                    <th>Monto Neto (PEN)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($campana->cosechasEnvios->sortByDesc('fecha_envio') as $c)
                <tr>
                    <td>{{ $c->fecha_envio->format('d/m/Y') }}</td>
                    <td>{{ ucfirst($c->tipo_venta) }}</td>
                    <td>{{ $c->cantidad_unidades }}</td>
                    <td>{{ $c->mercado_destino ?? '-' }}</td>
                    <td>{{ $c->estado === 'liquidado' ? 'Liquidado' : 'Pendiente' }}</td>
                    <td style="text-align: right;">S/ {{ number_format($c->monto_neto_final ?? 0, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <div class="footer">
        <p>AgroControl v1.0 — Sistema de Gestión Agrícola</p>
        <p>Documento generado automáticamente. Moneda: Sol Peruano (PEN)</p>
    </div>
</body>
</html>
