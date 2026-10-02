<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * CM-10: un conteo de inventario físico (hoja "INVENTARIO FISICO" del
 * Excel), con historial — a diferencia del Excel, que borra los conteos al
 * cerrar el mes (CM-11), acá cada conteo guardado queda disponible para
 * siempre. Ver InventarioFisicoDetalle para el detalle por ítem.
 */
class InventarioFisico extends Model
{
    use Auditable;

    protected string $auditModuloNombre = 'Inventario físico';

    use HasFactory;
    use SoftDeletes;

    protected $table = 'inventarios_fisicos';

    protected $fillable = [
        'fecha',
        'realizado_por',
        'nombre_almacenero',
        'nombre_supervisor',
        'observacion_general',
        'employee_id',
    ];

    protected $casts = [
        'fecha' => 'date',
    ];

    public function detalles(): HasMany
    {
        return $this->hasMany(InventarioFisicoDetalle::class);
    }

    /**
     * Cuántos ítems contados tuvieron alguna diferencia (descuadre) —
     * usado en el listado para resaltar los inventarios que necesitan
     * revisión.
     */
    public function itemsConDiferencia(): int
    {
        return $this->detalles()->where('diferencia', '<>', 0)->count();
    }
}
