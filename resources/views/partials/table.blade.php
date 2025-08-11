{{-- 
Usage example:
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

<div class="card shadow-sm">
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
                                        <a href="#" onclick="sortBy('{{ $col['key'] }}', '{{ request('sort') == $col['key'] && request('direction') == 'asc' ? 'desc' : 'asc' }}')" class="text-decoration-none text-dark">
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
                                            $value = $item->{$col['key']} ?? null;
                                            $type = $col['type'] ?? 'text';
                                        @endphp

                                        @switch($type)
                                            @case('boolean')
                                                @if($value)
                                                    <span class="badge bg-success">
                                                        <i class="bi bi-check-circle me-1"></i> Active
                                                    </span>
                                                @else
                                                    <span class="badge bg-secondary">
                                                        <i class="bi bi-pause-circle me-1"></i> Inactive
                                                    </span>
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
                                                        
                                                    @default
                                                        @if(isset($action['route']))
                                                            <a href="{{ route($action['route'], $item) }}" 
                                                               class="btn btn-sm btn-outline-{{ $action['color'] ?? 'secondary' }}" 
                                                               title="{{ $action['label'] ?? 'Action' }}">
                                                                <i class="bi bi-{{ $action['icon'] ?? 'gear' }}"></i>
                                                            </a>
                                                        @endif
                                                @endswitch
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
                {{ $items->links() }}
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

    function sortBy(column, direction) {
        const url = new URL(window.location);
        url.searchParams.set('sort', column);
        url.searchParams.set('direction', direction);
        window.location = url;
    }
</script>