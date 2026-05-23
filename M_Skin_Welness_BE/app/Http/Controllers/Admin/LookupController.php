<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AdminLookupService;
use Illuminate\Http\{RedirectResponse, Request};
use Illuminate\View\View;

class LookupController extends Controller
{
    public function __construct(private readonly AdminLookupService $lookups)
    {
    }

    public function index(Request $request): View
    {
        $catalogs = $this->lookups->catalogs();
        $active = $request->query('tab', array_key_first($catalogs));

        if (! isset($catalogs[$active])) {
            $active = array_key_first($catalogs);
        }

        $data = [];
        foreach ($catalogs as $key => $meta) {
            $info = $this->lookups->resolve($key);
            $data[$key] = [
                'label' => $meta['label'],
                'has_sort_order' => $info['has_sort_order'],
                'items' => $this->lookups->items($key),
            ];
        }

        return view('admin.lookups.index', [
            'catalogs' => $data,
            'active' => $active,
        ]);
    }

    public function store(Request $request, string $catalog): RedirectResponse
    {
        $data = $request->validate($this->lookups->rules($catalog));
        $this->lookups->create($catalog, $data);

        return redirect()
            ->route('admin.lookups.index', ['tab' => $catalog])
            ->with('status', 'Registro creado correctamente.');
    }

    public function update(Request $request, string $catalog, int $id): RedirectResponse
    {
        $data = $request->validate($this->lookups->rules($catalog, $id));
        $this->lookups->update($catalog, $id, $data);

        return redirect()
            ->route('admin.lookups.index', ['tab' => $catalog])
            ->with('status', 'Registro actualizado correctamente.');
    }

    public function destroy(string $catalog, int $id): RedirectResponse
    {
        $this->lookups->delete($catalog, $id);

        return redirect()
            ->route('admin.lookups.index', ['tab' => $catalog])
            ->with('status', 'Registro eliminado.');
    }
}
