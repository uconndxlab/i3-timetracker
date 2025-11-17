{{-- 
@include('partials.table', [
    'items' => $projects,
    'columns' => [
        ['key' => 'name', 'label' => 'Project Name', 'sortable' => true],
        ['key' => 'desc', 'label' => 'Description', 'sortable' => false],
        ['key' => 'active', 'label' => 'Status', 'sortable' => true, 'type' => 'boolean'],
        ['key' => 'created_at', 'label' => 'Created', 'sortable' => true, 'type' => 'date'],
    ],
    'actions' => [
        ['key' => 'view', 'label' => 'View', 'icon' => 'eye', 'route' => 'projects.show'],
        ['key' => 'edit', 'label' => 'Edit', 'icon' => 'pencil-square', 'route' => 'projects.edit'],
        ['key' => 'delete', 'label' => 'Delete', 'icon' => 'trash', 'route' => 'projects.destroy'],
    ],
    'empty_message' => 'No projects found.',
    'empty_icon' => 'folder-x',
    'create_route' => 'projects.create',
    'create_label' => 'Add New Project'
])
--}}
@php
function buildParams($paramConfig, $item) {
    $result = [];
    foreach ($paramConfig as $paramName => $itemProperty) {
        $result[$paramName] = $item->{$itemProperty};
    }
    return $result;
}
@endphp

<div class="card shadow-sm" id="dynamic-table-container">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h6 class="mb-0">
            <i class="bi bi-table me-2"></i> 
            {{ $items->total() }} {{ $items->total() == 1 ? Str::singular($title ?? 'Item') : Str::plural($title ?? 'Items') }} Found
        </h6>
        <div class="d-flex gap-2">
            <small class="text-muted">
                Showing {{ $items->firstItem() ?? 0 }}-{{ $items->lastItem() ?? 0 }} of {{ $items->total() }}
            </small>
        </div>
    </div>
    <div class="card-body p-0">
        @if($items->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            @foreach($columns as $col)
                                <th>
                                    @if(!empty($col['sortable']))
                                        <a href="#" onclick="sortBy(event, '{{ $col['key'] }}', '{{ request('sort') == $col['key'] && request('direction') == 'asc' ? 'desc' : 'asc' }}')" class="text-decoration-none text-dark">
                                            {{ $col['label'] ?? ucfirst($col['key']) }}
                                            @if(request('sort') == $col['key'])
                                                <i class="bi bi-chevron-{{ request('direction') == 'asc' ? 'up' : 'down' }}"></i>
                                            @else
                                                <i class="bi bi-chevron-expand text-muted"></i>
                                            @endif
                                        </a>
                                    @else
                                        {{ $col['label'] ?? ucfirst($col['key']) }}
                                    @endif
                                </th>
                            @endforeach
                            @if(!empty($actions))
                                <th width="120">Actions</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($items as $item)
                            <tr>
                                @foreach($columns as $col)
                                    <td>
                                        @php
                                            $keys = explode('.', $col['key']);
                                            if (count($keys) > 1) {
                                                $value = $item;
                                                foreach ($keys as $nestedKey) {
                                                    if (is_object($value) && isset($value->{$nestedKey})) {
                                                        $value = $value->{$nestedKey};
                                                    } else {
                                                        $value = null;
                                                        break;
                                                    }
                                                }
                                            } else {
                                                $value = $item->{$col['key']} ?? null;
                                            }
                                            $type = $col['type'] ?? 'text';
                                        @endphp

                                        @switch($type)
                                            @case('boolean')
                                                <div class="d-flex justify-content-center align-items-center h-100">
                                                    <div class="form-check mb-0">
                                                        <input class="form-check-input" type="checkbox" disabled 
                                                            {{ $value ? 'checked' : '' }}
                                                            style="width: 1.3rem; height: 1.3rem; cursor: default; 
                                                                border-color: #dee2e6;
                                                                background-color: {{ $value ? '#0d6efd' : '#fff' }};
                                                                box-shadow: none;
                                                                border-color: {{ $value ? '#0d6efd' : '#dee2e6' }};
                                                                border-radius: 0.5rem;">
                                                    </div>
                                                </div>
                                                @break

                                            @case('duration')
                                                @if(is_numeric($value) && $value > 0)
                                                    @php
                                                        $hours = number_format($value / 60, 2);
                                                    @endphp
                                                    {{ $hours }} hr
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                                @break

                                            @case('date')
                                                @if($value)
                                                    {{ $value->format('M d, Y') }}
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                                @break
                                                
                                            @case('datetime')
                                                @if($value)
                                                    {{ $value->format('M d, Y g:i A') }}
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                                @break
                                                
                                            @case('email')
                                                @if($value)
                                                    <a href="mailto:{{ $value }}" class="text-decoration-none">
                                                        <i class="bi bi-envelope me-1"></i> {{ $value }}
                                                    </a>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                                @break
                                                
                                            @case('url')
                                                @if($value)
                                                    <a href="{{ $value }}" target="_blank" class="text-decoration-none">
                                                        <i class="bi bi-box-arrow-up-right me-1"></i> {{ $value }}
                                                    </a>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                                @break
                                                
                                            @default
                                                @if($col['key'] == 'name' && isset($col['route']))
                                                    <div class="fw-semibold">
                                                        <a href="{{ route($col['route'], $item) }}" class="text-decoration-none text-primary">
                                                            {{ $value }}
                                                        </a>
                                                    </div>
                                                    
                                                @else
                                                    {{ $value ?: '-' }}
                                                @endif
                                        @endswitch
                                    </td>
                                @endforeach
                                
                                @if(!empty($actions))
                                    <td>
                                        <div class="d-flex gap-1">
                                            @foreach($actions as $action)
                                                @php
                                                    $showAction = true;
                                                    if (isset($action['show_if'])) {
                                                        $propertyName = $action['show_if'];
                                                        $showAction = isset($item->$propertyName) && $item->$propertyName;
                                                    }
                                                @endphp
                                                @if($showAction)
                                                    @switch($action['key'])
                                                        @case('view')
                                                            <a href="{{ route($action['route'], $item) }}" 
                                                            class="btn btn-sm btn-outline-primary" 
                                                            title="{{ $action['label'] ?? 'View' }}">
                                                                <i class="bi bi-{{ $action['icon'] ?? 'eye' }}"></i>
                                                            </a>
                                                            @break
                                                            
                                                        @case('edit')
                                                            <a href="{{ route($action['route'], $item) }}" 
                                                            class="btn btn-sm btn-outline-secondary" 
                                                            title="{{ $action['label'] ?? 'Edit' }}">
                                                                <i class="bi bi-{{ $action['icon'] ?? 'pencil-square' }}"></i>
                                                            </a>
                                                            @break
                                                            
                                                        @case('delete')
                                                            <button type="button"
                                                                class="btn btn-sm btn-outline-danger"
                                                                title="{{ $action['label'] ?? 'Delete' }}"
                                                                onclick="confirmDelete('{{ $item->name ?? 'this item' }}', '{{ route($action['route'], $item) }}')">
                                                                <i class="bi bi-{{ $action['icon'] ?? 'trash' }}"></i>
                                                            </button>
                                                            @break
                                                        
                                                        @case('toggle_admin')
                                                            <form action="{{ route($action['route'], $item) }}" method="POST" style="display: inline;">
                                                                @csrf
                                                                <button type="submit" 
                                                                class="btn btn-sm btn-outline-transparent"
                                                                    title="{{ $action['label'] ?? 'Toggle Admin' }}"
                                                                    onclick="return confirm('Confirm that you want to {{ $item->is_admin ? 'revoke' : 'grant' }} administrative privileges to {{ $item->name }}?')">
                                                                    @if(isset($action['icon_src']))
                                                                        <img src="{{ $action['icon_src'] }}" alt="i3 logo" style="width: 28px; height: 28px;">
                                                                    @else
                                                                        <i class="bi bi-{{ $action['icon'] ?? 'gear' }}"></i>
                                                                    @endif
                                                                </button>
                                                            </form>
                                                            @break
                                                            
                                                        @default
                                                            @if(isset($action['route']))
                                                                @php
                                                                    $params = isset($action['params']) 
                                                                        ? buildParams($action['params'], $item) 
                                                                        : $item;
                                                                @endphp
                                                                <a href="{{ route($action['route'], $params) }}" 
                                                                class="btn btn-sm btn-outline-{{ $action['color'] ?? 'secondary' }}" 
                                                                title="{{ $action['label'] ?? 'Action' }}">
                                                                    <i class="bi bi-{{ $action['icon'] ?? 'gear' }}"></i>
                                                                </a>
                                                            @endif
                                                    @endswitch
                                                @endif
                                            @endforeach
                                        </div>
                                    </td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="text-center py-5">
                <i class="bi bi-{{ $empty_icon ?? 'inbox' }} text-muted" style="font-size: 4rem;"></i>
                <h4 class="text-muted mt-3">{{ $empty_message ?? 'No Items Found' }}</h4>
                @if(isset($create_route))
                    <div class="mt-3">
                        <a href="{{ route($create_route) }}" class="btn btn-primary">
                            <i class="bi bi-plus-circle me-1"></i> {{ $create_label ?? 'Add New Item' }}
                        </a>
                    </div>
                @endif
            </div>
        @endif
    </div>
    @if($items->hasPages())
        <div class="card-footer">
            <div class="d-flex justify-content-center">
                {{ $items->links('partials.pagination') }}
            </div>
        </div>
    @endif
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete <strong id="deleteItemName"></strong>?</p>
                <p class="text-muted">This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <form id="deleteForm" method="POST" style="display: inline;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-trash me-1"></i> Delete
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    function confirmDelete(itemName, deleteUrl) {
        document.getElementById('deleteItemName').textContent = itemName;
        document.getElementById('deleteForm').action = deleteUrl;
        new bootstrap.Modal(document.getElementById('deleteModal')).show();
    }

    function sortBy(event, column, direction) {
        event.preventDefault();
        const url = new URL(window.location);
        url.searchParams.set('sort', column);
        url.searchParams.set('direction', direction);
        
        const tableContainer = document.getElementById('dynamic-table-container');
        const originalOpacity = tableContainer.style.opacity;
        tableContainer.style.opacity = '0.5';

        fetch(url.toString())
            .then(response => response.text())
            .then(html => {
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                const newTable = doc.getElementById('dynamic-table-container');
                if (newTable) {
                    tableContainer.innerHTML = newTable.innerHTML;
                    window.history.pushState({}, '', url.toString());
                } else {
                    // Fallback to full reload if the new table isn't found
                    window.location.href = url.toString();
                }
            })
            .catch(error => {
                console.error('Error fetching sorted table data:', error);
                // Fallback to full reload on error
                window.location.href = url.toString();
            })
            .finally(() => {
                tableContainer.style.opacity = originalOpacity;
            });
    }
</script>