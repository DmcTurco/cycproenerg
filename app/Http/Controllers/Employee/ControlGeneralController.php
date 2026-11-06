<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Asesor;
use App\Models\ControlGeneral;
use App\Models\Logs;
use App\Models\ParametroControlGeneral;
use App\Models\PersonaCampo;
use App\Services\ControlGeneralClasificador;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Control General (Internas · TC · Habilitación): una pantalla con una
 * pestaña por hoja del Excel "Control General CLB - C&C.xlsm", solo con las
 * columnas que la usuaria mira a diario, filtro por empresa (CLB / C&C) y
 * edición en la misma tabla (se guarda al cambiar cada celda).
 */
class ControlGeneralController extends Controller
{
    /** Lo que se puede editar en cada etapa (columnas amarillas del Excel). */
    private const EDITABLES = [
        ControlGeneral::APROBADA => ['construida', 'asesor', 'cuadrilla', 'accion', 'observacion'],
        ControlGeneral::TC_CONSTRUIDA => ['resultado_tc', 'observacion', 'fecha_observacion', 'contratista', 'asesor', 'cuadrilla'],
        ControlGeneral::HABILITACION => ['fecha_programacion', 'tecnico', 'observado', 'habilitado', 'observacion'],
        ControlGeneral::ANULACION => ['accion', 'estado_tramite', 'observacion'],
        ControlGeneral::HABILITADA => ['habilitado', 'fecha_habilitacion', 'observacion'],
    ];

    public function index(Request $request)
    {
        $etapa = $request->query('etapa');
        if (!array_key_exists($etapa, ControlGeneral::ETAPAS)) {
            $etapa = ControlGeneral::APROBADA;
        }
        $empresa = in_array($request->query('empresa'), ['CLB', 'CYC'], true) ? $request->query('empresa') : null;
        $filtro = $request->query('filtro') ?: null;
        $diasLimite = (int) (ParametroControlGeneral::actual()->dias_limite_aprobacion ?: 20);

        $terminos = $this->terminos($request);
        $distritos = collect((array) $request->query('distritos', []))
            ->map(fn ($d) => trim((string) $d))->filter()->unique()->values();
        $buscando = $terminos->isNotEmpty() || $distritos->isNotEmpty();

        $base = ControlGeneral::query()
            ->when($empresa, fn ($q) => $q->whereHas('solicitud.empresa', fn ($e) => $e->where('codigo', $empresa)))
            ->when($terminos->isNotEmpty(), fn ($q) => $this->aplicarBusqueda($q, $terminos))
            ->when($distritos->isNotEmpty(), fn ($q) => $q->whereHas('solicitud.ubicacion', fn ($u) => $u->whereIn('distrito', $distritos->all())));

        $conteos = (clone $base)->selectRaw('etapa, count(*) as total')->groupBy('etapa')->pluck('total', 'etapa');

        // Buscando sin pestaña elegida (p. ej. desde "Ver en Control General"
        // del detalle de la solicitud) o si la pestaña actual quedó vacía:
        // abrir la primera pestaña que tenga resultados.
        if ($buscando && !$conteos->get($etapa)) {
            $etapa = collect(array_keys(ControlGeneral::ETAPAS))->first(fn ($e) => $conteos->get($e)) ?? $etapa;
        }

        $enEtapa = (clone $base)->where('etapa', $etapa);
        $subconteos = $this->subconteos($etapa, $enEtapa, $diasLimite);

        $registros = (clone $enEtapa)
            ->when($filtro, fn ($q) => $this->aplicarFiltro($q, $etapa, $filtro, $diasLimite))
            ->with(['solicitud.empresa', 'solicitud.solicitante', 'solicitud.ubicacion', 'solicitud.proyecto', 'solicitud.instalacion', 'solicitud.estadoPortal'])
            ->tap(fn ($q) => $this->ordenar($q, $etapa))
            ->paginate(50)
            ->appends($request->query());

        return view('employee.pages.control-general.index', [
            'registros' => $registros,
            'etapa' => $etapa,
            'empresa' => $empresa,
            'filtro' => $filtro,
            'conteos' => $conteos,
            'subconteos' => $subconteos,
            'diasLimite' => $diasLimite,
            'duplicados' => $etapa === ControlGeneral::HABILITACION ? $this->suministrosDuplicados() : [],
            'sugerencias' => $this->sugerencias(),
            'editables' => self::EDITABLES[$etapa],
            'ultimaCarga' => Logs::where('estado', 'completado')->latest()->first(),
            'terminos' => $terminos->all(),
            'distritos' => $distritos->all(),
            'distritosDisponibles' => $this->distritosDisponibles(),
            'noEncontrados' => $this->noEncontrados($terminos),
        ]);
    }

    /**
     * Lo que se busca: los recuadros del buscador (terminos[]) más "search"
     * (enlaces). Se pueden pegar varios suministros o N° de solicitud de una
     * columna del Excel: se separan por espacios, saltos de línea, comas o ;.
     */
    private function terminos(Request $request)
    {
        return collect((array) $request->query('terminos', []))
            ->merge($request->filled('search') ? [$request->query('search')] : [])
            ->flatMap(fn ($t) => preg_split('/[\s,;]+/u', trim((string) $t)))
            ->map(fn ($t) => trim($t))
            ->filter(fn ($t) => $t !== '')
            ->unique()
            ->values();
    }

    /**
     * Números (suministro, N° de solicitud o DNI): basta que coincida UNO de
     * ellos — así se ve una lista completa de una vez. Si es un solo número,
     * coincidencia parcial; si son varios, exacta. Textos (nombre del
     * cliente o distrito): cada uno debe aparecer. Los distritos elegidos de
     * la lista van aparte (distritos[]): basta que coincida uno.
     */
    private function aplicarBusqueda(Builder $q, $terminos): void
    {
        [$numeros, $textos] = $terminos->partition(fn ($t) => ctype_digit($t));

        $q->whereHas('solicitud', function ($s) use ($numeros, $textos) {
            if ($numeros->count() === 1) {
                $n = '%' . $numeros->first() . '%';
                $s->where(fn ($w) => $w->whereLike('numero_solicitud', $n)
                    ->orWhereLike('numero_suministro', $n)
                    ->orWhereHas('solicitante', fn ($p) => $p->whereLike('numero_documento', $n)));
            } elseif ($numeros->count() > 1) {
                $lista = $numeros->values()->all();
                $s->where(fn ($w) => $w->whereIn('numero_solicitud', $lista)
                    ->orWhereIn('numero_suministro', $lista)
                    ->orWhereHas('solicitante', fn ($p) => $p->whereIn('numero_documento', $lista)));
            }

            foreach ($textos as $texto) {
                $t = '%' . $texto . '%';
                $s->where(fn ($w) => $w->whereLike('numero_solicitud', $t)
                    ->orWhereLike('numero_suministro', $t)
                    ->orWhereHas('solicitante', fn ($p) => $p->whereLike('nombre', $t))
                    ->orWhereHas('ubicacion', fn ($u) => $u->whereLike('distrito', $t)));
            }
        });
    }

    /**
     * Distritos que existen en Control General, para la lista del buscador
     * (al escribir "la vic" sugiere LA VICTORIA y queda como un solo recuadro).
     */
    private function distritosDisponibles(): array
    {
        return ControlGeneral::query()
            ->join('ubicacions', 'ubicacions.solicitud_id', '=', 'control_generales.solicitud_id')
            ->whereNotNull('ubicacions.distrito')
            ->distinct()
            ->orderBy('ubicacions.distrito')
            ->pluck('ubicacions.distrito')
            ->all();
    }

    /**
     * Con una lista de varios números: cuáles no están en Control General
     * (en ninguna etapa), para avisarle a la usuaria.
     */
    private function noEncontrados($terminos): array
    {
        $numeros = $terminos->filter(fn ($t) => ctype_digit($t))->values();
        if ($numeros->count() < 2) {
            return [];
        }

        $encontrados = ControlGeneral::query()
            ->join('solicituds', 'solicituds.id', '=', 'control_generales.solicitud_id')
            ->leftJoin('solicitantes', 'solicitantes.id', '=', 'solicituds.solicitante_id')
            ->where(fn ($w) => $w->whereIn('solicituds.numero_solicitud', $numeros)
                ->orWhereIn('solicituds.numero_suministro', $numeros)
                ->orWhereIn('solicitantes.numero_documento', $numeros))
            ->get(['solicituds.numero_solicitud', 'solicituds.numero_suministro', 'solicitantes.numero_documento'])
            ->flatMap(fn ($r) => [$r->numero_solicitud, $r->numero_suministro, $r->numero_documento])
            ->filter()
            ->map(fn ($v) => (string) $v)
            ->unique();

        return $numeros->diff($encontrados)->values()->all();
    }

    /**
     * Guarda una celda. Si la marca mueve la fila de etapa (Construida = Sí,
     * TC Concluida, Habilitado = Sí, Anular/Reingresar...), se mueve al toque.
     */
    public function update(Request $request, ControlGeneral $controlGeneral)
    {
        $editables = self::EDITABLES[$controlGeneral->etapa] ?? [];

        $datos = $request->validate([
            'campo' => ['required', Rule::in($editables)],
            'valor' => ['nullable'],
        ]);

        $campo = $datos['campo'];
        $valor = $this->validarValor($request, $campo);

        $controlGeneral->{$campo} = $valor;
        if ($campo === 'observacion' && $controlGeneral->etapa === ControlGeneral::TC_CONSTRUIDA && $valor && !$controlGeneral->fecha_observacion) {
            $controlGeneral->fecha_observacion = now()->toDateString();
        }
        $controlGeneral->save();

        $nueva = ControlGeneralClasificador::aplicarMovimientos($controlGeneral);

        return response()->json([
            'ok' => true,
            'movida' => (bool) $nueva,
            'etapa' => $controlGeneral->etapa,
            'mensaje' => $nueva ? 'Pasó a ' . ControlGeneral::ETAPAS[$nueva] : 'Guardado',
            'situacion' => $controlGeneral->situacion(),
            'fecha_observacion' => $controlGeneral->fecha_observacion?->toDateString(),
        ]);
    }

    /**
     * Hoja "Resumen" del Excel: conteos por etapa y empresa.
     */
    public function resumen()
    {
        $diasLimite = (int) (ParametroControlGeneral::actual()->dias_limite_aprobacion ?: 20);

        $porEmpresa = function (callable $filtro) {
            $fila = ['CLB' => 0, 'CYC' => 0];
            foreach (['CLB', 'CYC'] as $codigo) {
                $q = ControlGeneral::query()->whereHas('solicitud.empresa', fn ($e) => $e->where('codigo', $codigo));
                $filtro($q);
                $fila[$codigo] = $q->count();
            }
            $fila['total'] = $fila['CLB'] + $fila['CYC'];
            return $fila;
        };
        $en = fn (string $etapa, ?callable $extra = null) => $porEmpresa(function ($q) use ($etapa, $extra) {
            $q->where('etapa', $etapa);
            if ($extra) {
                $extra($q);
            }
        });

        $secciones = [
            '1. Redes aprobadas (Estado 02)' => [
                'Total aprobadas' => $en(ControlGeneral::APROBADA),
                'En plazo' => $en(ControlGeneral::APROBADA, fn ($q) => $this->aplicarFiltro($q, ControlGeneral::APROBADA, ControlGeneral::PLAZO_EN_PLAZO, $diasLimite)),
                "EN DESFASE (más de {$diasLimite} días)" => $en(ControlGeneral::APROBADA, fn ($q) => $this->aplicarFiltro($q, ControlGeneral::APROBADA, ControlGeneral::PLAZO_DESFASE, $diasLimite)),
                'Sin fecha de aprobación' => $en(ControlGeneral::APROBADA, fn ($q) => $this->aplicarFiltro($q, ControlGeneral::APROBADA, ControlGeneral::PLAZO_SIN_FECHA, $diasLimite)),
                'Avanzaron en portal (revisar)' => $en(ControlGeneral::APROBADA, fn ($q) => $this->aplicarFiltro($q, ControlGeneral::APROBADA, ControlGeneral::PLAZO_AVANZO, $diasLimite)),
            ],
            '2. Construidas – TC pendiente de concluir' => [
                'Total' => $en(ControlGeneral::TC_CONSTRUIDA),
                'Rechazada' => $en(ControlGeneral::TC_CONSTRUIDA, fn ($q) => $q->where('resultado_tc', 'Rechazada')),
                'Pendiente' => $en(ControlGeneral::TC_CONSTRUIDA, fn ($q) => $q->where('resultado_tc', 'Pendiente')),
                'Sin registro de TC' => $en(ControlGeneral::TC_CONSTRUIDA, fn ($q) => $this->aplicarFiltro($q, ControlGeneral::TC_CONSTRUIDA, 'Sin registro', $diasLimite)),
            ],
            '3. TC concluida – habilitación' => [
                'Total por habilitar' => $en(ControlGeneral::HABILITACION),
                'Por programar' => $en(ControlGeneral::HABILITACION, fn ($q) => $this->aplicarFiltro($q, ControlGeneral::HABILITACION, 'Por programar', $diasLimite)),
                'Programado' => $en(ControlGeneral::HABILITACION, fn ($q) => $this->aplicarFiltro($q, ControlGeneral::HABILITACION, 'Programado', $diasLimite)),
                'Observado' => $en(ControlGeneral::HABILITACION, fn ($q) => $this->aplicarFiltro($q, ControlGeneral::HABILITACION, 'Observado', $diasLimite)),
            ],
            '4. Anulaciones y reingresos' => [
                'Total' => $en(ControlGeneral::ANULACION),
                'Reingresar' => $en(ControlGeneral::ANULACION, fn ($q) => $q->where('accion', 'Reingresar')),
                'Anular' => $en(ControlGeneral::ANULACION, fn ($q) => $q->where('accion', 'Anular')),
            ] + collect(ControlGeneral::ESTADOS_TRAMITE)->mapWithKeys(fn ($estado) => [
                "Trámite: {$estado}" => $en(ControlGeneral::ANULACION, fn ($q) => $q->where('estado_tramite', $estado)),
            ])->all(),
            '5. Habilitadas' => [
                'Total habilitadas' => $en(ControlGeneral::HABILITADA),
                'Habilitadas este mes' => $en(ControlGeneral::HABILITADA, fn ($q) => $q->whereBetween('fecha_habilitacion', [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()])),
            ],
        ];

        return view('employee.pages.control-general.resumen', [
            'secciones' => $secciones,
            'diasLimite' => $diasLimite,
            'ultimaCarga' => Logs::where('estado', 'completado')->latest()->first(),
        ]);
    }

    private function validarValor(Request $request, string $campo)
    {
        $reglas = match ($campo) {
            'construida', 'observado', 'habilitado' => ['nullable', 'boolean'],
            'fecha_observacion', 'fecha_programacion', 'fecha_habilitacion' => ['nullable', 'date'],
            'accion' => ['nullable', Rule::in(ControlGeneral::ACCIONES)],
            'estado_tramite' => ['nullable', Rule::in(ControlGeneral::ESTADOS_TRAMITE)],
            'resultado_tc' => ['nullable', Rule::in(ControlGeneral::RESULTADOS_TC)],
            'observacion' => ['nullable', 'string', 'max:2000'],
            default => ['nullable', 'string', 'max:255'],
        };

        $valor = $request->validate(['valor' => $reglas])['valor'] ?? null;

        if (in_array($campo, ['construida', 'observado', 'habilitado'], true)) {
            return (bool) $valor;
        }

        $valor = is_string($valor) ? trim($valor) : $valor;

        return $valor === '' ? null : $valor;
    }

    private function aplicarFiltro(Builder $q, string $etapa, string $filtro, int $diasLimite): void
    {
        $limite = now()->subDays($diasLimite)->toDateString();
        $estado02 = fn ($s) => $s->where('codigo', 'like', '02%');

        match ($etapa) {
            ControlGeneral::APROBADA => match ($filtro) {
                ControlGeneral::PLAZO_SIN_FECHA => $q->whereNull('fecha_aprobacion'),
                ControlGeneral::PLAZO_AVANZO => $q->whereNotNull('fecha_aprobacion')->whereDoesntHave('solicitud.estadoPortal', $estado02),
                ControlGeneral::PLAZO_DESFASE => $q->where('fecha_aprobacion', '<', $limite)->whereHas('solicitud.estadoPortal', $estado02),
                ControlGeneral::PLAZO_EN_PLAZO => $q->where('fecha_aprobacion', '>=', $limite)->whereHas('solicitud.estadoPortal', $estado02),
                default => null,
            },
            ControlGeneral::TC_CONSTRUIDA => $filtro === 'Sin registro'
                ? $q->where(fn ($w) => $w->whereNull('resultado_tc')->orWhere('resultado_tc', 'Sin registro'))
                : $q->where('resultado_tc', $filtro),
            ControlGeneral::HABILITACION => match ($filtro) {
                'Observado' => $q->where('observado', true),
                'Programado' => $q->where('observado', false)->whereNotNull('fecha_programacion'),
                'Por programar' => $q->where('observado', false)->whereNull('fecha_programacion'),
                default => null,
            },
            ControlGeneral::ANULACION => in_array($filtro, ControlGeneral::ACCIONES, true)
                ? $q->where('accion', $filtro)
                : $q->where('estado_tramite', $filtro),
            default => null,
        };
    }

    /** Chips de filtro de cada pestaña, con su contador. */
    private function subconteos(string $etapa, Builder $enEtapa, int $diasLimite): array
    {
        $opciones = match ($etapa) {
            ControlGeneral::APROBADA => ControlGeneral::CONTROLES_PLAZO,
            ControlGeneral::TC_CONSTRUIDA => ['Sin registro', 'Pendiente', 'Rechazada'],
            ControlGeneral::HABILITACION => ControlGeneral::SITUACIONES,
            ControlGeneral::ANULACION => ControlGeneral::ESTADOS_TRAMITE,
            default => [],
        };

        $conteos = [];
        foreach ($opciones as $opcion) {
            $q = clone $enEtapa;
            $this->aplicarFiltro($q, $etapa, $opcion, $diasLimite);
            $conteos[$opcion] = $q->count();
        }

        return $conteos;
    }

    private function ordenar(Builder $q, string $etapa): void
    {
        match ($etapa) {
            ControlGeneral::APROBADA, ControlGeneral::TC_CONSTRUIDA => $q->orderByRaw('fecha_aprobacion asc nulls last'),
            ControlGeneral::HABILITACION => $q->orderByRaw('fecha_programacion asc nulls first')->orderBy('fecha_ingreso_etapa'),
            ControlGeneral::ANULACION => $q->orderByDesc('fecha_marca'),
            default => $q->orderByDesc('fecha_habilitacion'),
        };
        $q->orderBy('id');
    }

    /** Columna "Duplicado" de 3-Habilitación: mismo suministro más de una vez. */
    private function suministrosDuplicados(): array
    {
        return ControlGeneral::query()
            ->where('etapa', ControlGeneral::HABILITACION)
            ->join('solicituds', 'solicituds.id', '=', 'control_generales.solicitud_id')
            ->whereNotNull('solicituds.numero_suministro')
            ->groupBy('solicituds.numero_suministro')
            ->havingRaw('count(*) > 1')
            ->pluck('solicituds.numero_suministro')
            ->all();
    }

    /**
     * Listas para los desplegables (hoja "Listas" del Excel): personas ya
     * registradas en el sistema + nombres que ya se escribieron a mano.
     */
    private function sugerencias(): array
    {
        $usados = fn (string $campo) => ControlGeneral::query()->whereNotNull($campo)->distinct()->pluck($campo);
        $personas = PersonaCampo::query()->where('estado', PersonaCampo::ESTADO_ACTIVO)->pluck('nombre');
        $ordenar = fn ($c) => $c->map(fn ($n) => trim($n))->filter()->unique()->sort()->values()->all();

        return [
            'asesor' => $ordenar(Asesor::pluck('nombre')->merge($usados('asesor'))),
            'cuadrilla' => $ordenar($personas->merge($usados('cuadrilla'))),
            'tecnico' => $ordenar($personas->merge($usados('tecnico'))),
            'contratista' => $ordenar($personas->merge($usados('contratista'))),
        ];
    }
}
