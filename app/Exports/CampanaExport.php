<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CampanaExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    protected $campana;
    protected $balance;

    public function __construct($campana, array $balance)
    {
        $this->campana = $campana;
        $this->balance = $balance;
    }

    public function collection()
    {
        $rows = collect();

        $this->campana->transaccionesFinancieras->each(function ($t) use ($rows) {
            $rows->push([
                'tipo' => 'Transacción',
                'fecha' => $t->fecha->format('Y-m-d'),
                'descripcion' => $t->descripcion ?? $t->categoria,
                'monto' => $t->monto,
                'estado' => $t->estado_pago,
            ]);
        });

        $this->campana->cosechasEnvios->each(function ($c) use ($rows) {
            $rows->push([
                'tipo' => 'Cosecha/Envío',
                'fecha' => $c->fecha_envio->format('Y-m-d'),
                'descripcion' => "Venta {$c->tipo_venta} - {$c->cantidad_unidades} unidades",
                'monto' => $c->monto_neto_final ?? 0,
                'estado' => $c->estado,
            ]);
        });

        return $rows->sortBy('fecha')->values();
    }

    public function headings(): array
    {
        return ['Tipo', 'Fecha', 'Descripción', 'Monto (PEN)', 'Estado'];
    }

    public function map($row): array
    {
        return [
            $row['tipo'],
            $row['fecha'],
            $row['descripcion'],
            number_format($row['monto'], 2),
            $row['estado'],
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
