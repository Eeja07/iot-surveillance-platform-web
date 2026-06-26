@extends('layouts/layoutMaster')

@section('title', 'Remote Device Configuration')

@section('content')
<h4 class="mb-4">Remote Device Configuration</h4>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <ul class="mb-0">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<!-- Stats & Fleet Operations -->
<div class="row g-4 mb-4">
    <!-- Camera Configurations Overview -->
    <div class="col-12 col-sm-6 col-md-3">
        <div class="card h-100 shadow-sm border-0">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <h4 class="mb-1 fw-bold" id="stat-total-cameras">{{ $cameras->filter(fn($c) => $c->is_active)->count() }} / {{ $cameras->count() }}</h4>
                        <p class="mb-0 text-muted" style="font-size: 0.8rem;">Online / Total Devices</p>
                    </div>
                    <div class="avatar bg-label-secondary border rounded p-2">
                        <span class="avatar-initial text-secondary"><i class="ti ti-camera ti-md"></i></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-md-3">
        <div class="card h-100 shadow-sm border-0">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        @php
                            $driftCount = $cameras->filter(fn($c) => app(\App\Services\DeviceConfigurationService::class)->isDrifted($c))->count();
                        @endphp
                        <h4 class="mb-1 fw-bold" id="stat-drifted-cameras">
                            {{ $driftCount }}
                        </h4>
                        <p class="mb-0 text-muted" style="font-size: 0.8rem;">Configuration Drift</p>
                    </div>
                    <div class="avatar bg-label-secondary border rounded p-2">
                        <span class="avatar-initial text-secondary"><i class="ti ti-alert-triangle ti-md"></i></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-md-3">
        <div class="card h-100 shadow-sm border-0">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        @php
                            $pendingCount = $cameras->filter(fn($c) => in_array($c->last_config_status, ['Pending', 'Queued', 'Sending']))->count();
                            $failedCount = $cameras->filter(fn($c) => in_array($c->last_config_status, ['Failed', 'Rejected', 'Timeout']))->count();
                        @endphp
                        <h4 class="mb-1 fw-bold" id="stat-pending-changes">{{ $pendingCount }}</h4>
                        <p class="mb-0 text-muted" style="font-size: 0.8rem;">Queue: {{ $pendingCount }} Active / {{ $failedCount }} Failed</p>
                    </div>
                    <div class="avatar bg-label-secondary border rounded p-2">
                        <span class="avatar-initial text-secondary"><i class="ti ti-refresh ti-md"></i></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-md-3">
        <div class="card h-100 shadow-sm border-0">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <h4 class="mb-1 fw-bold">{{ $profiles->count() }}</h4>
                        <p class="mb-0 text-muted" style="font-size: 0.8rem;">Profiles Defined</p>
                    </div>
                    <div class="avatar bg-label-secondary border rounded p-2">
                        <span class="avatar-initial text-secondary"><i class="ti ti-settings-automation ti-md"></i></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Camera List -->
    <div class="col-12 col-xl-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <h5 class="mb-0">Device List</h5>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-primary btn-sm" id="btn-bulk-configure" disabled data-bs-toggle="modal" data-bs-target="#modalBulkConfigure">
                        <i class="ti ti-settings me-1"></i> Configure Selected
                    </button>
                    <button type="button" class="btn btn-outline-primary btn-sm" id="btn-bulk-profile" disabled data-bs-toggle="modal" data-bs-target="#modalBulkProfile">
                        <i class="ti ti-settings-automation me-1"></i> Apply Profile
                    </button>
                    <div class="btn-group">
                        <button type="button" class="btn btn-outline-secondary btn-sm dropdown-toggle" id="btn-bulk-fleet-actions" data-bs-toggle="dropdown" aria-expanded="false" disabled>
                            <i class="ti ti-bolt me-1"></i> Fleet Actions
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item btn-bulk-fleet-action" href="#" data-action="reapply_desired">Reapply Desired Config</a></li>
                            <li><a class="dropdown-item btn-bulk-fleet-action" href="#" data-action="retry_failed">Retry Failed</a></li>
                            <li><a class="dropdown-item btn-bulk-fleet-action" href="#" data-action="retry_pending">Retry Pending</a></li>
                            <li><a class="dropdown-item btn-bulk-fleet-action" href="#" data-action="cancel_pending">Cancel Pending</a></li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="table-responsive text-nowrap">
                <form id="form-bulk-action" method="POST">
                    @csrf
                    <input type="hidden" name="operation" id="bulk-operation-input">
                </form>
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th style="width: 40px;">
                                    <input class="form-check-input" type="checkbox" id="check-all-cameras">
                                </th>
                                <th>Camera</th>
                                <th>Online Status</th>
                                <th>Assigned Profile</th>
                                <th>Drift Status</th>
                                <th>Sync Status</th>
                                <th>Last Sync</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody class="table-border-bottom-0">
                            @forelse($cameras as $camera)
                                @php
                                    $telemetry = $camera->latestTelemetry;
                                    
                                    // Check Drift using service layer
                                    $configService = app(\App\Services\DeviceConfigurationService::class);
                                    $drifted = $configService->isDrifted($camera);
                                    
                                    $driftFields = [];
                                    if ($drifted && $camera->desired_config) {
                                        $current = $camera->current_config ?: [];
                                        foreach ($camera->desired_config as $k => $v) {
                                            $curVal = $current[$k] ?? null;
                                            if (is_bool($v)) $curVal = (bool)$curVal;
                                            if ($curVal != $v) {
                                                $driftFields[] = $k;
                                            }
                                        }
                                    }

                                    // Map status
                                    $status = $camera->last_config_status ?: 'Applied';
                                    $statusClass = 'bg-label-secondary border';
                                    if (in_array($status, ['Applied'])) $statusClass = 'bg-label-success border border-success';
                                    elseif (in_array($status, ['Pending'])) $statusClass = 'bg-label-warning border border-warning';
                                    elseif (in_array($status, ['Queued'])) $statusClass = 'bg-label-secondary border';
                                    elseif (in_array($status, ['Sending'])) $statusClass = 'bg-label-secondary border';
                                    elseif (in_array($status, ['Failed', 'Rejected', 'Timeout'])) $statusClass = 'bg-label-danger border border-danger';
                                @endphp
                                <tr id="camera-row-{{ $camera->id }}">
                                    <td>
                                        <input form="form-bulk-action" class="form-check-input camera-checkbox" type="checkbox" name="camera_ids[]" value="{{ $camera->id }}">
                                    </td>
                                    <td>
                                        <div>
                                            <strong>{{ $camera->name }}</strong>
                                            <div class="text-muted" style="font-size: 0.75rem;">{{ $camera->device_id }}</div>
                                        </div>
                                    </td>
                                    <td>
                                        @if($camera->is_active)
                                            <span class="badge bg-label-success border border-success">Online</span>
                                        @else
                                            <span class="badge bg-label-danger border border-danger">Offline</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($camera->assignedProfile)
                                            <span class="badge bg-label-secondary border">{{ $camera->assignedProfile->name }}</span>
                                            @if($camera->assignedProfile->restart_required)
                                                <span class="badge bg-label-secondary border btn-xs ms-1" data-bs-toggle="tooltip" title="Profile requires restart on apply"><i class="ti ti-refresh" style="font-size: 0.8rem;"></i></span>
                                            @endif
                                        @else
                                            <span class="badge bg-label-secondary border">None</span>
                                        @endif
                                    </td>
                                    <td class="drift-status-cell">
                                        @if($drifted)
                                            <span class="badge bg-label-warning border border-warning" data-bs-toggle="tooltip" data-bs-html="true" title="Drifted fields: {{ implode(', ', $driftFields) }}">
                                                Drifted
                                            </span>
                                        @else
                                            <span class="badge bg-label-success border border-success">In Sync</span>
                                        @endif
                                    </td>
                                    <td class="pending-changes-cell">
                                        <span class="badge {{ $statusClass }}">{{ $status }}</span>
                                    </td>
                                    <td class="last-sync-cell">
                                        {{ $camera->last_sync ? $camera->last_sync->diffForHumans() : 'Never' }}
                                    </td>
                                    <td>
                                        <div class="dropdown">
                                            <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                                                <i class="ti ti-dots-vertical"></i>
                                            </button>
                                            <div class="dropdown-menu">
                                                <button type="button" class="dropdown-item btn-view-details"
                                                    data-id="{{ $camera->id }}"
                                                    data-name="{{ $camera->name }}"
                                                    data-current="{{ json_encode($camera->current_config) }}"
                                                    data-desired="{{ json_encode($camera->desired_config) }}"
                                                    data-current-version="{{ $camera->current_config_version ?? '0' }}"
                                                    data-desired-version="{{ $camera->desired_config_version ?? '0' }}"
                                                    data-current-hash="{{ $camera->current_config_hash ?? 'N/A' }}"
                                                    data-desired-hash="{{ $camera->desired_config_hash ?? 'N/A' }}"
                                                    data-last-applied="{{ $camera->last_applied_at ? $camera->last_applied_at->format('Y-m-d H:i:s') : 'Never' }}"
                                                    data-last-sync="{{ $camera->last_sync ? $camera->last_sync->format('Y-m-d H:i:s') : 'Never' }}"
                                                    data-last-failure="{{ $camera->last_failure_message ?? 'None' }}"
                                                    data-bs-toggle="modal" data-bs-target="#modalDeviceDetail">
                                                    <i class="ti ti-eye me-1"></i> View Details
                                                </button>
                                                <button type="button" class="dropdown-item btn-configure-single" 
                                                    data-id="{{ $camera->id }}" 
                                                    data-name="{{ $camera->name }}"
                                                    data-quality="{{ $telemetry->jpeg_quality ?? 15 }}"
                                                    data-size="{{ $telemetry->frame_size ?? 'VGA' }}"
                                                    data-capture="{{ $telemetry->capture_interval_ms ?? 5000 }}"
                                                    data-telemetry="{{ $telemetry->telemetry_interval_ms ?? 30000 }}"
                                                    data-buffer="{{ $telemetry->mqtt_buffer ?? 10 }}"
                                                    data-image="{{ $telemetry ? (int)$telemetry->image_enabled : 1 }}"
                                                    data-telem="{{ $telemetry ? (int)$telemetry->telemetry_enabled : 1 }}"
                                                    data-ota="{{ $telemetry ? (int)$telemetry->ota_enabled : 1 }}"
                                                    data-bs-toggle="modal" data-bs-target="#modalConfigureSingle">
                                                    <i class="ti ti-settings me-1"></i> Configure
                                                </button>
                                                <button type="button" class="dropdown-item btn-apply-profile" 
                                                    data-id="{{ $camera->id }}" 
                                                    data-name="{{ $camera->name }}"
                                                    data-bs-toggle="modal" data-bs-target="#modalApplyProfileSingle">
                                                    <i class="ti ti-settings-automation me-1"></i> Apply Profile
                                                </button>
                                                <button type="button" class="dropdown-item btn-save-profile"
                                                    data-quality="{{ $telemetry->jpeg_quality ?? 15 }}"
                                                    data-size="{{ $telemetry->frame_size ?? 'VGA' }}"
                                                    data-capture="{{ $telemetry->capture_interval_ms ?? 5000 }}"
                                                    data-telemetry="{{ $telemetry->telemetry_interval_ms ?? 30000 }}"
                                                    data-buffer="{{ $telemetry->mqtt_buffer ?? 10 }}"
                                                    data-image="{{ $telemetry ? (int)$telemetry->image_enabled : 1 }}"
                                                    data-telem="{{ $telemetry ? (int)$telemetry->telemetry_enabled : 1 }}"
                                                    data-ota="{{ $telemetry ? (int)$telemetry->ota_enabled : 1 }}"
                                                    data-bs-toggle="modal" data-bs-target="#modalCreateProfile">
                                                    <i class="ti ti-bookmark me-1"></i> Save as Profile
                                                </button>
                                                <div class="dropdown-divider"></div>
                                                <form action="{{ route('admin.config.restart') }}" method="POST" class="d-inline confirm-submit" data-message="Are you sure you want to restart this camera?">
                                                    @csrf
                                                    <input type="hidden" name="camera_ids[]" value="{{ $camera->id }}">
                                                    <button type="submit" class="dropdown-item text-warning">
                                                        <i class="ti ti-refresh me-1"></i> Restart
                                                    </button>
                                                </form>
                                                <form action="{{ route('admin.config.factory-reset') }}" method="POST" class="d-inline confirm-submit" data-message="WARNING: This will factory reset the camera configuration parameters. Proceed?">
                                                    @csrf
                                                    <input type="hidden" name="camera_ids[]" value="{{ $camera->id }}">
                                                    <button type="submit" class="dropdown-item text-danger">
                                                        <i class="ti ti-trash-x me-1"></i> Factory Reset
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center py-4">No cameras found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                
            </div>
        </div>
    </div>

    <!-- Configuration Profiles -->
    <div class="col-12 col-xl-4">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Configuration Profiles</h5>
                <button type="button" class="btn btn-primary btn-xs" data-bs-toggle="modal" data-bs-target="#modalCreateProfile">
                    <i class="ti ti-plus me-1"></i> Add
                </button>
            </div>
            <div class="card-body px-0">
                <div class="list-group list-group-flush">
                    @forelse($profiles as $profile)
                        <div class="list-group-item list-group-item-action flex-column align-items-start border-0 py-3">
                            <div class="d-flex w-100 justify-content-between">
                                <h6 class="mb-1"><strong>{{ $profile->name }}</strong></h6>
                                <div>
                                    <!-- Actions -->
                                    <button class="btn btn-xs btn-outline-info me-1 btn-edit-profile"
                                        data-id="{{ $profile->id }}"
                                        data-name="{{ $profile->name }}"
                                        data-quality="{{ $profile->jpeg_quality }}"
                                        data-size="{{ $profile->frame_size }}"
                                        data-capture="{{ $profile->capture_interval_ms }}"
                                        data-telemetry="{{ $profile->telemetry_interval_ms }}"
                                        data-buffer="{{ $profile->mqtt_buffer }}"
                                        data-image="{{ $profile->image_enabled ? 1 : 0 }}"
                                        data-telem="{{ $profile->telemetry_enabled ? 1 : 0 }}"
                                        data-ota="{{ $profile->ota_enabled ? 1 : 0 }}"
                                        data-bs-toggle="modal" data-bs-target="#modalEditProfile">
                                        <i class="ti ti-edit"></i>
                                    </button>
                                    <form action="{{ route('admin.config.profiles.store') }}" method="POST" class="d-inline">
                                        @csrf
                                        <input type="hidden" name="duplicate_from_id" value="{{ $profile->id }}">
                                        <button type="submit" class="btn btn-xs btn-outline-secondary me-1" title="Duplicate Profile">
                                            <i class="ti ti-copy"></i>
                                        </button>
                                    </form>
                                    @if(!in_array($profile->name, ['Low Bandwidth', 'Balanced', 'High Quality', 'Custom']))
                                        <form action="{{ route('admin.config.profiles.destroy', $profile->id) }}" method="POST" class="d-inline confirm-submit" data-message="Are you sure you want to delete this profile?">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-xs btn-outline-danger">
                                                <i class="ti ti-trash"></i>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                            <small class="text-muted d-block mt-2">
                                <span class="badge bg-label-secondary border me-1">JPEG: {{ $profile->jpeg_quality }}</span>
                                <span class="badge bg-label-secondary border me-1">Size: {{ $profile->frame_size }}</span>
                                <span class="badge bg-label-secondary border me-1">Cap: {{ $profile->capture_interval_ms }}ms</span>
                                <span class="badge bg-label-secondary border me-1">Telem: {{ $profile->telemetry_interval_ms }}ms</span>
                            </small>
                            <small class="text-muted d-block mt-1">
                                <span class="badge bg-label-secondary border me-1">Buffer: {{ $profile->mqtt_buffer }}</span>
                                <span class="badge bg-label-secondary border me-1">Img: {{ $profile->image_enabled ? 'On' : 'Off' }}</span>
                                <span class="badge bg-label-secondary border me-1">Telem: {{ $profile->telemetry_enabled ? 'On' : 'Off' }}</span>
                                <span class="badge bg-label-secondary border me-1">OTA: {{ $profile->ota_enabled ? 'On' : 'Off' }}</span>
                            </small>
                        </div>
                    @empty
                        <div class="text-center py-4 text-muted">No profiles defined.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Fleet operations & history -->
<div class="row g-4 mt-1">
    <!-- Fleet Operations Card -->
    <div class="col-12 col-xl-4">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="mb-0">Fleet Operations</h5>
            </div>
            <div class="card-body d-flex flex-column gap-2">
                <!-- Apply Profile to Fleet -->
                <form action="{{ route('admin.config.fleet-operation') }}" method="POST" class="confirm-submit mb-2" data-message="Are you sure you want to apply this profile to the ENTIRE active fleet?">
                    @csrf
                    <input type="hidden" name="operation" value="apply_profile">
                    <label class="form-label">Apply Profile to Fleet</label>
                    <div class="input-group">
                        <select name="profile_id" class="form-select form-select-sm" required>
                            <option value="">Choose Profile...</option>
                            @foreach($profiles as $p)
                                <option value="{{ $p->id }}">{{ $p->name }}</option>
                            @endforeach
                        </select>
                        <button type="submit" class="btn btn-outline-secondary btn-sm">Apply</button>
                    </div>
                </form>

                <hr class="my-1">

                <!-- Reapply Desired -->
                <form action="{{ route('admin.config.fleet-operation') }}" method="POST" class="confirm-submit mb-2" data-message="Are you sure you want to reapply the desired configuration to ALL devices?">
                    @csrf
                    <input type="hidden" name="operation" value="reapply_desired">
                    <div class="d-grid">
                        <button type="submit" class="btn btn-outline-secondary btn-sm">
                            <i class="ti ti-check me-1"></i> Reapply Desired Config
                        </button>
                    </div>
                </form>

                <!-- Retry Failed -->
                <form action="{{ route('admin.config.fleet-operation') }}" method="POST" class="confirm-submit mb-2" data-message="Are you sure you want to retry failed configuration commands?">
                    @csrf
                    <input type="hidden" name="operation" value="retry_failed">
                    <div class="d-grid">
                        <button type="submit" class="btn btn-outline-secondary btn-sm">
                            <i class="ti ti-rotate me-1"></i> Retry Failed Configs
                        </button>
                    </div>
                </form>

                <!-- Retry Pending -->
                <form action="{{ route('admin.config.fleet-operation') }}" method="POST" class="confirm-submit mb-2" data-message="Are you sure you want to force retry pending configuration commands?">
                    @csrf
                    <input type="hidden" name="operation" value="retry_pending">
                    <div class="d-grid">
                        <button type="submit" class="btn btn-outline-secondary btn-sm">
                            <i class="ti ti-refresh me-1"></i> Retry Pending Configs
                        </button>
                    </div>
                </form>

                <!-- Cancel Pending -->
                <form action="{{ route('admin.config.fleet-operation') }}" method="POST" class="confirm-submit mb-2" data-message="Are you sure you want to cancel all pending configuration queues?">
                    @csrf
                    <input type="hidden" name="operation" value="cancel_pending">
                    <div class="d-grid">
                        <button type="submit" class="btn btn-outline-secondary btn-sm">
                            <i class="ti ti-square-x me-1"></i> Cancel Pending Queue
                        </button>
                    </div>
                </form>

                <hr class="my-1">

                <!-- Restart Fleet -->
                <form action="{{ route('admin.config.fleet-operation') }}" method="POST" class="confirm-submit mb-2" data-message="Are you sure you want to restart ALL active fleet devices?">
                    @csrf
                    <input type="hidden" name="operation" value="restart">
                    <div class="d-grid">
                        <button type="submit" class="btn btn-outline-danger btn-sm">
                            <i class="ti ti-refresh me-1"></i> Restart Entire Fleet
                        </button>
                    </div>
                </form>

                <!-- Factory Reset Fleet -->
                <form action="{{ route('admin.config.fleet-operation') }}" method="POST" class="confirm-submit mb-2" data-message="WARNING: This will factory reset configuration parameters for ALL active fleet devices. Proceed?">
                    @csrf
                    <input type="hidden" name="operation" value="factory_reset">
                    <div class="d-grid">
                        <button type="submit" class="btn btn-outline-danger btn-sm">
                            <i class="ti ti-trash-x me-1"></i> Factory Reset Fleet
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- History list -->
    <div class="col-12 col-xl-8">
        <div class="card h-100">
            <div class="card-header pb-2">
                <h5 class="mb-0">Configuration & Command History</h5>
            </div>
            <!-- Filters -->
            <div class="card-body">
                <form method="GET" action="{{ route('admin.config.index') }}" class="row g-2 align-items-center mb-3">
                    <div class="col-12 col-sm-3">
                        <select name="camera_id" class="form-select form-select-sm">
                            <option value="">All Cameras</option>
                            @foreach($cameras as $c)
                                <option value="{{ $c->id }}" {{ request('camera_id') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 col-sm-3">
                        <select name="status" class="form-select form-select-sm">
                            <option value="">All Statuses</option>
                            <option value="Pending" {{ request('status') == 'Pending' ? 'selected' : '' }}>Pending</option>
                            <option value="Queued" {{ request('status') == 'Queued' ? 'selected' : '' }}>Queued</option>
                            <option value="Sending" {{ request('status') == 'Sending' ? 'selected' : '' }}>Sending</option>
                            <option value="Applied" {{ request('status') == 'Applied' ? 'selected' : '' }}>Applied</option>
                            <option value="Failed" {{ request('status') == 'Failed' ? 'selected' : '' }}>Failed</option>
                            <option value="Rejected" {{ request('status') == 'Rejected' ? 'selected' : '' }}>Rejected</option>
                            <option value="Cancelled" {{ request('status') == 'Cancelled' ? 'selected' : '' }}>Cancelled</option>
                        </select>
                    </div>
                    <div class="col-12 col-sm-3">
                        <input type="date" name="date" class="form-control form-control-sm" value="{{ request('date') }}">
                    </div>
                    <div class="col-12 col-sm-3 d-flex gap-1">
                        <button type="submit" class="btn btn-primary btn-sm w-100">Filter</button>
                        <a href="{{ route('admin.config.index') }}" class="btn btn-outline-secondary btn-sm w-100">Reset</a>
                    </div>
                </form>
            </div>
            <div class="table-responsive text-nowrap">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Device</th>
                            <th>Status</th>
                            <th>Target Payload</th>
                            <th>Details / Response</th>
                            <th>Changed Fields</th>
                            <th>Initiated By</th>
                            <th>Time</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody class="table-border-bottom-0" id="history-table-body">
                        @forelse($histories as $history)
                            <tr id="history-row-{{ $history->id }}">
                                <td>
                                    <strong>{{ $history->camera ? $history->camera->name : 'Unknown' }}</strong>
                                </td>
                                <td class="status-cell">
                                    @php
                                        $hStatus = $history->status;
                                        $hClass = 'bg-label-secondary border';
                                        if ($hStatus === 'Applied') $hClass = 'bg-label-success border border-success';
                                        elseif ($hStatus === 'Sent') $hClass = 'bg-label-secondary border';
                                        elseif ($hStatus === 'Pending') $hClass = 'bg-label-warning border border-warning';
                                        elseif ($hStatus === 'Queued') $hClass = 'bg-label-secondary border';
                                        elseif ($hStatus === 'Sending') $hClass = 'bg-label-secondary border';
                                        elseif (in_array($hStatus, ['Failed', 'Rejected', 'Timeout'])) $hClass = 'bg-label-danger border border-danger';
                                    @endphp
                                    <span class="badge {{ $hClass }}">{{ $hStatus }}</span>
                                </td>
                                <td>
                                    <code class="text-truncate d-block" style="max-width: 150px;" title="{{ json_encode($history->new_config) }}">
                                        {{ json_encode($history->new_config) }}
                                    </code>
                                </td>
                                <td class="message-cell" style="font-size: 0.8rem; white-space: normal; max-width: 200px;">
                                    {{ $history->message }}
                                </td>
                                <td>
                                    @if(!empty($history->changed_fields))
                                        @foreach($history->changed_fields as $f)
                                            <span class="badge bg-label-secondary border" style="font-size: 0.65rem;">{{ $f }}</span>
                                        @endforeach
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>
                                    {{ $history->user ? $history->user->name : 'System' }}
                                </td>
                                <td>
                                    {{ $history->created_at ? $history->created_at->format('Y-m-d H:i:s') : '-' }}
                                </td>
                                <td>
                                    @if($history->new_config && !isset($history->new_config['action']) && $history->status !== 'Cancelled' && $history->camera)
                                        <form action="{{ route('admin.config.rollback') }}" method="POST" class="d-inline confirm-submit" data-message="Are you sure you want to rollback to this configuration state?">
                                            @csrf
                                            <input type="hidden" name="camera_id" value="{{ $history->camera_id }}">
                                            <input type="hidden" name="history_id" value="{{ $history->id }}">
                                            <button type="submit" class="btn btn-xs btn-outline-warning" title="Rollback to this state">
                                                <i class="ti ti-history me-1"></i> Rollback
                                            </button>
                                        </form>
                                    @else
                                        -
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-4">No configuration history found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="card-footer py-2">
                {{ $histories->links() }}
            </div>
        </div>
    </div>
</div>

<!-- ==========================================
     MODALS
     ========================================== -->

<!-- Modal: Configure Single Camera -->
<div class="modal fade" id="modalConfigureSingle" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form action="{{ route('admin.config.apply') }}" method="POST" class="modal-content">
            @csrf
            <input type="hidden" name="camera_ids[]" id="config-single-camera-id">
            <div class="modal-header">
                <h5 class="modal-title">Configure Camera: <span id="config-single-camera-name"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-6">
                        <label class="form-label">JPEG Quality (10-63)</label>
                        <input type="number" name="jpeg_quality" id="config-single-jpeg" class="form-control" min="10" max="63">
                    </div>
                    <div class="col-6">
                        <label class="form-label">Frame Size</label>
                        <select name="frame_size" id="config-single-size" class="form-select">
                            <option value="">Keep current...</option>
                            <option value="QQVGA">QQVGA (160x120)</option>
                            <option value="QVGA">QVGA (320x240)</option>
                            <option value="VGA">VGA (640x480)</option>
                            <option value="SVGA">SVGA (800x600)</option>
                            <option value="XGA">XGA (1024x768)</option>
                            <option value="SXGA">SXGA (1280x1024)</option>
                            <option value="UXGA">UXGA (1600x1200)</option>
                        </select>
                    </div>
                    <div class="col-6">
                        <label class="form-label">Capture Interval (ms)</label>
                        <input type="number" name="capture_interval_ms" id="config-single-capture" class="form-control" min="100">
                    </div>
                    <div class="col-6">
                        <label class="form-label">Telemetry Interval (ms)</label>
                        <input type="number" name="telemetry_interval_ms" id="config-single-telemetry" class="form-control" min="1000">
                    </div>
                    <div class="col-12">
                        <label class="form-label">MQTT Buffer Queue Size</label>
                        <input type="number" name="mqtt_buffer" id="config-single-buffer" class="form-control" min="0">
                    </div>
                    
                    <div class="col-12 mt-3">
                        <input type="hidden" name="image_enabled_present" value="1">
                        <input type="hidden" name="telemetry_enabled_present" value="1">
                        <input type="hidden" name="ota_enabled_present" value="1">
                        
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" name="image_enabled" value="1" id="config-single-image">
                            <label class="form-check-label" for="config-single-image">Enable Image Stream</label>
                        </div>
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" name="telemetry_enabled" value="1" id="config-single-telem">
                            <label class="form-check-label" for="config-single-telem">Enable Telemetry Stream</label>
                        </div>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="ota_enabled" value="1" id="config-single-ota">
                            <label class="form-check-label" for="config-single-ota">Enable OTA Updates</label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Apply Configuration</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Apply Profile Single -->
<div class="modal fade" id="modalApplyProfileSingle" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form action="{{ route('admin.config.apply-profile') }}" method="POST" class="modal-content">
            @csrf
            <input type="hidden" name="camera_ids[]" id="profile-single-camera-id">
            <div class="modal-header">
                <h5 class="modal-title">Apply Profile: <span id="profile-single-camera-name"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <label class="form-label">Select Reusable Configuration Profile</label>
                <select name="profile_id" class="form-select" required>
                    <option value="">Choose Profile...</option>
                    @foreach($profiles as $p)
                        <option value="{{ $p->id }}">{{ $p->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Apply Profile</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Create / Save Profile -->
<div class="modal fade" id="modalCreateProfile" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form action="{{ route('admin.config.profiles.store') }}" method="POST" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">Create Configuration Profile</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label">Profile Name</label>
                        <input type="text" name="name" class="form-control" placeholder="e.g. Low Bandwidth Mode" required>
                    </div>
                    <div class="col-6">
                        <label class="form-label">JPEG Quality (10-63)</label>
                        <input type="number" name="jpeg_quality" id="create-profile-jpeg" class="form-control" min="10" max="63" value="15" required>
                    </div>
                    <div class="col-6">
                        <label class="form-label">Frame Size</label>
                        <select name="frame_size" id="create-profile-size" class="form-select" required>
                            <option value="QQVGA">QQVGA</option>
                            <option value="QVGA">QVGA</option>
                            <option value="VGA" selected>VGA</option>
                            <option value="SVGA">SVGA</option>
                            <option value="XGA">XGA</option>
                            <option value="SXGA">SXGA</option>
                            <option value="UXGA">UXGA</option>
                        </select>
                    </div>
                    <div class="col-6">
                        <label class="form-label">Capture Interval (ms)</label>
                        <input type="number" name="capture_interval_ms" id="create-profile-capture" class="form-control" min="100" value="5000" required>
                    </div>
                    <div class="col-6">
                        <label class="form-label">Telemetry Interval (ms)</label>
                        <input type="number" name="telemetry_interval_ms" id="create-profile-telemetry" class="form-control" min="1000" value="30000" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label">MQTT Buffer Queue Size</label>
                        <input type="number" name="mqtt_buffer" id="create-profile-buffer" class="form-control" min="0" value="10" required>
                    </div>
                    
                    <div class="col-12 mt-3">
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" name="image_enabled" value="1" id="create-profile-image" checked>
                            <label class="form-check-label" for="create-profile-image">Enable Image Stream</label>
                        </div>
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" name="telemetry_enabled" value="1" id="create-profile-telem" checked>
                            <label class="form-check-label" for="create-profile-telem">Enable Telemetry Stream</label>
                        </div>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="ota_enabled" value="1" id="create-profile-ota" checked>
                            <label class="form-check-label" for="create-profile-ota">Enable OTA Updates</label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Profile</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Edit Profile -->
<div class="modal fade" id="modalEditProfile" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form id="form-edit-profile" method="POST" class="modal-content">
            @csrf
            @method('PUT')
            <div class="modal-header">
                <h5 class="modal-title">Edit Configuration Profile</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label">Profile Name</label>
                        <input type="text" name="name" id="edit-profile-name" class="form-control" required>
                    </div>
                    <div class="col-6">
                        <label class="form-label">JPEG Quality (10-63)</label>
                        <input type="number" name="jpeg_quality" id="edit-profile-jpeg" class="form-control" min="10" max="63" required>
                    </div>
                    <div class="col-6">
                        <label class="form-label">Frame Size</label>
                        <select name="frame_size" id="edit-profile-size" class="form-select" required>
                            <option value="QQVGA">QQVGA</option>
                            <option value="QVGA">QVGA</option>
                            <option value="VGA">VGA</option>
                            <option value="SVGA">SVGA</option>
                            <option value="XGA">XGA</option>
                            <option value="SXGA">SXGA</option>
                            <option value="UXGA">UXGA</option>
                        </select>
                    </div>
                    <div class="col-6">
                        <label class="form-label">Capture Interval (ms)</label>
                        <input type="number" name="capture_interval_ms" id="edit-profile-capture" class="form-control" min="100" required>
                    </div>
                    <div class="col-6">
                        <label class="form-label">Telemetry Interval (ms)</label>
                        <input type="number" name="telemetry_interval_ms" id="edit-profile-telemetry" class="form-control" min="1000" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label">MQTT Buffer Queue Size</label>
                        <input type="number" name="mqtt_buffer" id="edit-profile-buffer" class="form-control" min="0" required>
                    </div>
                    
                    <div class="col-12 mt-3">
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" name="image_enabled" value="1" id="edit-profile-image">
                            <label class="form-check-label" for="edit-profile-image">Enable Image Stream</label>
                        </div>
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" name="telemetry_enabled" value="1" id="edit-profile-telem">
                            <label class="form-check-label" for="edit-profile-telem">Enable Telemetry Stream</label>
                        </div>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="ota_enabled" value="1" id="edit-profile-ota">
                            <label class="form-check-label" for="edit-profile-ota">Enable OTA Updates</label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Update Profile</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Bulk Configure -->
<div class="modal fade" id="modalBulkConfigure" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Bulk Configure Selected Cameras</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted">Set fields you wish to update. Unfilled fields will not be sent to the devices (partial configuration).</p>
                <div class="row g-3">
                    <div class="col-6">
                        <label class="form-label">JPEG Quality (10-63)</label>
                        <input type="number" form="form-bulk-action" name="jpeg_quality" class="form-control" min="10" max="63" placeholder="Leave empty to ignore">
                    </div>
                    <div class="col-6">
                        <label class="form-label">Frame Size</label>
                        <select form="form-bulk-action" name="frame_size" class="form-select">
                            <option value="">Leave empty to ignore</option>
                            <option value="QQVGA">QQVGA</option>
                            <option value="QVGA">QVGA</option>
                            <option value="VGA">VGA</option>
                            <option value="SVGA">SVGA</option>
                            <option value="XGA">XGA</option>
                            <option value="SXGA">SXGA</option>
                            <option value="UXGA">UXGA</option>
                        </select>
                    </div>
                    <div class="col-6">
                        <label class="form-label">Capture Interval (ms)</label>
                        <input type="number" form="form-bulk-action" name="capture_interval_ms" class="form-control" min="100" placeholder="Leave empty to ignore">
                    </div>
                    <div class="col-6">
                        <label class="form-label">Telemetry Interval (ms)</label>
                        <input type="number" form="form-bulk-action" name="telemetry_interval_ms" class="form-control" min="1000" placeholder="Leave empty to ignore">
                    </div>
                    <div class="col-12">
                        <label class="form-label">MQTT Buffer Queue Size</label>
                        <input type="number" form="form-bulk-action" name="mqtt_buffer" class="form-control" min="0" placeholder="Leave empty to ignore">
                    </div>
                    
                    <div class="col-12 mt-3">
                        <div class="form-check form-switch mb-2">
                            <input form="form-bulk-action" type="hidden" name="image_enabled_present" value="1">
                            <input form="form-bulk-action" class="form-check-input" type="checkbox" name="image_enabled" value="1" checked>
                            <label class="form-check-label">Enable Image Stream</label>
                        </div>
                        <div class="form-check form-switch mb-2">
                            <input form="form-bulk-action" type="hidden" name="telemetry_enabled_present" value="1">
                            <input form="form-bulk-action" class="form-check-input" type="checkbox" name="telemetry_enabled" value="1" checked>
                            <label class="form-check-label">Enable Telemetry Stream</label>
                        </div>
                        <div class="form-check form-switch">
                            <input form="form-bulk-action" type="hidden" name="ota_enabled_present" value="1">
                            <input form="form-bulk-action" class="form-check-input" type="checkbox" name="ota_enabled" value="1" checked>
                            <label class="form-check-label">Enable OTA Updates</label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" form="form-bulk-action" id="btn-submit-bulk-config" class="btn btn-primary">Apply Bulk Config</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Bulk Apply Profile -->
<div class="modal fade" id="modalBulkProfile" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Apply Profile to Selected Cameras</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <label class="form-label">Select Profile to Apply</label>
                <select form="form-bulk-action" name="profile_id" class="form-select" required>
                    <option value="">Choose Profile...</option>
                    @foreach($profiles as $p)
                        <option value="{{ $p->id }}">{{ $p->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" form="form-bulk-action" id="btn-submit-bulk-profile" class="btn btn-primary">Apply Profile to Selected</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Device Detail (Desired vs Current State) -->
<div class="modal fade" id="modalDeviceDetail" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Device Configuration Details: <span id="detail-camera-name"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Metadata Alerts -->
                <div class="row g-2 mb-3">
                    <div class="col-md-4">
                        <div class="card bg-lighter shadow-none border p-2">
                            <small class="text-muted d-block">Last Applied</small>
                            <strong id="detail-last-applied" style="font-size: 0.85rem;">-</strong>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-lighter shadow-none border p-2">
                            <small class="text-muted d-block">Last Sync</small>
                            <strong id="detail-last-sync" style="font-size: 0.85rem;">-</strong>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-lighter shadow-none border p-2">
                            <small class="text-muted d-block">Last Failure</small>
                            <strong id="detail-last-failure" class="text-danger" style="font-size: 0.85rem;">-</strong>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- Left: Current Config -->
                    <div class="col-md-6 border-end">
                        <h6 class="text-primary mb-2">Current State (Reported)</h6>
                        <div class="mb-2">
                            <small class="text-muted">Version:</small> <strong id="detail-current-version">-</strong>
                        </div>
                        <div class="mb-3">
                            <small class="text-muted">Hash:</small> <code id="detail-current-hash" class="d-block text-truncate" style="max-width: 100%;">N/A</code>
                        </div>
                        <div class="bg-light p-3 rounded" style="font-family: monospace; font-size: 0.85rem; min-height: 200px;" id="detail-current-json">
                        </div>
                    </div>

                    <!-- Right: Desired Config -->
                    <div class="col-md-6">
                        <h6 class="text-success mb-2">Desired State (Target)</h6>
                        <div class="mb-2">
                            <small class="text-muted">Version:</small> <strong id="detail-desired-version">-</strong>
                        </div>
                        <div class="mb-3">
                            <small class="text-muted">Hash:</small> <code id="detail-desired-hash" class="d-block text-truncate" style="max-width: 100%;">N/A</code>
                        </div>
                        <div class="bg-light p-3 rounded" style="font-family: monospace; font-size: 0.85rem; min-height: 200px;" id="detail-desired-json">
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('page-script')
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Checkbox selections for bulk actions
    const checkAll = document.getElementById('check-all-cameras');
    const checkboxes = document.querySelectorAll('.camera-checkbox');
    const btnBulkConfig = document.getElementById('btn-bulk-configure');
    const btnBulkProfile = document.getElementById('btn-bulk-profile');
    const btnBulkFleetActions = document.getElementById('btn-bulk-fleet-actions');
    const formBulkAction = document.getElementById('form-bulk-action');

    function updateBulkButtons() {
        const checkedCount = document.querySelectorAll('.camera-checkbox:checked').length;
        btnBulkConfig.disabled = checkedCount === 0;
        btnBulkProfile.disabled = checkedCount === 0;
        if (btnBulkFleetActions) {
            btnBulkFleetActions.disabled = checkedCount === 0;
        }
    }

    if (checkAll) {
        checkAll.addEventListener('change', function () {
            checkboxes.forEach(cb => {
                cb.checked = checkAll.checked;
            });
            updateBulkButtons();
        });
    }

    checkboxes.forEach(cb => {
        cb.addEventListener('change', updateBulkButtons);
    });

    // Handle form bulk configuration submission routing
    const btnSubmitBulkConfig = document.getElementById('btn-submit-bulk-config');
    if (btnSubmitBulkConfig) {
        btnSubmitBulkConfig.addEventListener('click', function(e) {
            e.preventDefault();
            if (confirm("Are you sure you want to apply the configuration to the selected cameras?")) {
                formBulkAction.action = "{{ route('admin.config.apply') }}";
                formBulkAction.submit();
            }
        });
    }

    // Handle form bulk profile submission routing
    const btnSubmitBulkProfile = document.getElementById('btn-submit-bulk-profile');
    if (btnSubmitBulkProfile) {
        btnSubmitBulkProfile.addEventListener('click', function(e) {
            e.preventDefault();
            if (confirm("Are you sure you want to apply this profile to the selected cameras?")) {
                formBulkAction.action = "{{ route('admin.config.apply-profile') }}";
                formBulkAction.submit();
            }
        });
    }

    // Handle bulk fleet actions dropdown click
    document.querySelectorAll('.btn-bulk-fleet-action').forEach(item => {
        item.addEventListener('click', function(e) {
            e.preventDefault();
            const action = this.getAttribute('data-action');
            if (confirm(`Are you sure you want to run this fleet action on the selected cameras?`)) {
                document.getElementById('bulk-operation-input').value = action;
                formBulkAction.action = "{{ route('admin.config.fleet-operation') }}";
                formBulkAction.submit();
            }
        });
    });

    // Single camera configure details population
    const modalConfigureSingle = document.getElementById('modalConfigureSingle');
    if (modalConfigureSingle) {
        modalConfigureSingle.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            document.getElementById('config-single-camera-id').value = button.getAttribute('data-id');
            document.getElementById('config-single-camera-name').textContent = button.getAttribute('data-name');
            document.getElementById('config-single-jpeg').value = button.getAttribute('data-quality');
            document.getElementById('config-single-size').value = button.getAttribute('data-size');
            document.getElementById('config-single-capture').value = button.getAttribute('data-capture');
            document.getElementById('config-single-telemetry').value = button.getAttribute('data-telemetry');
            document.getElementById('config-single-buffer').value = button.getAttribute('data-buffer');
            
            document.getElementById('config-single-image').checked = button.getAttribute('data-image') == 1;
            document.getElementById('config-single-telem').checked = button.getAttribute('data-telem') == 1;
            document.getElementById('config-single-ota').checked = button.getAttribute('data-ota') == 1;
        });
    }

    // Save configuration parameters to a new profile modal population
    const modalCreateProfile = document.getElementById('modalCreateProfile');
    if (modalCreateProfile) {
        modalCreateProfile.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            if (button && button.hasAttribute('data-quality')) {
                document.getElementById('create-profile-jpeg').value = button.getAttribute('data-quality');
                document.getElementById('create-profile-size').value = button.getAttribute('data-size');
                document.getElementById('create-profile-capture').value = button.getAttribute('data-capture');
                document.getElementById('create-profile-telemetry').value = button.getAttribute('data-telemetry');
                document.getElementById('create-profile-buffer').value = button.getAttribute('data-buffer');
                document.getElementById('create-profile-image').checked = button.getAttribute('data-image') == 1;
                document.getElementById('create-profile-telem').checked = button.getAttribute('data-telem') == 1;
                document.getElementById('create-profile-ota').checked = button.getAttribute('data-ota') == 1;
            }
        });
    }

    // Apply Profile modal population
    const modalApplyProfileSingle = document.getElementById('modalApplyProfileSingle');
    if (modalApplyProfileSingle) {
        modalApplyProfileSingle.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            document.getElementById('profile-single-camera-id').value = button.getAttribute('data-id');
            document.getElementById('profile-single-camera-name').textContent = button.getAttribute('data-name');
        });
    }

    // Edit profile modal population
    const modalEditProfile = document.getElementById('modalEditProfile');
    if (modalEditProfile) {
        modalEditProfile.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const profileId = button.getAttribute('data-id');
            document.getElementById('form-edit-profile').action = `{{ url('/dashboard/admin/config/profiles') }}/${profileId}`;
            
            document.getElementById('edit-profile-name').value = button.getAttribute('data-name');
            document.getElementById('edit-profile-jpeg').value = button.getAttribute('data-quality');
            document.getElementById('edit-profile-size').value = button.getAttribute('data-size');
            document.getElementById('edit-profile-capture').value = button.getAttribute('data-capture');
            document.getElementById('edit-profile-telemetry').value = button.getAttribute('data-telemetry');
            document.getElementById('edit-profile-buffer').value = button.getAttribute('data-buffer');
            
            document.getElementById('edit-profile-image').checked = button.getAttribute('data-image') == 1;
            document.getElementById('edit-profile-telem').checked = button.getAttribute('data-telem') == 1;
            document.getElementById('edit-profile-ota').checked = button.getAttribute('data-ota') == 1;
        });
    }

    // Details modal trigger
    document.addEventListener('click', function(e) {
        const btn = e.target.closest('.btn-view-details');
        if (btn) {
            populateDetailModal(btn);
        }
    });

    function populateDetailModal(button) {
        const name = button.getAttribute('data-name');
        const currentStr = button.getAttribute('data-current') || '{}';
        const desiredStr = button.getAttribute('data-desired') || '{}';
        const curVersion = button.getAttribute('data-current-version');
        const desVersion = button.getAttribute('data-desired-version');
        const curHash = button.getAttribute('data-current-hash');
        const desHash = button.getAttribute('data-desired-hash');
        const lastApplied = button.getAttribute('data-last-applied');
        const lastSync = button.getAttribute('data-last-sync');
        const lastFailure = button.getAttribute('data-last-failure');

        document.getElementById('detail-camera-name').textContent = name;
        document.getElementById('detail-current-version').textContent = curVersion;
        document.getElementById('detail-desired-version').textContent = desVersion;
        document.getElementById('detail-current-hash').textContent = curHash;
        document.getElementById('detail-desired-hash').textContent = desHash;
        document.getElementById('detail-last-applied').textContent = lastApplied;
        document.getElementById('detail-last-sync').textContent = lastSync;
        document.getElementById('detail-last-failure').textContent = lastFailure;

        let currentObj = {};
        let desiredObj = {};
        try { currentObj = JSON.parse(currentStr) || {}; } catch(e){}
        try { desiredObj = JSON.parse(desiredStr) || {}; } catch(e){}

        const keys = ['jpeg_quality', 'frame_size', 'capture_interval_ms', 'telemetry_interval_ms', 'mqtt_buffer', 'image_enabled', 'telemetry_enabled', 'ota_enabled'];

        let currentHtml = '';
        let desiredHtml = '';

        keys.forEach(key => {
            const curVal = currentObj[key] !== undefined ? currentObj[key] : 'N/A';
            const desVal = desiredObj[key] !== undefined ? desiredObj[key] : 'N/A';

            let curClass = 'd-block px-2 py-1';
            let desClass = 'd-block px-2 py-1';

            if (curVal.toString() !== desVal.toString()) {
                curClass = 'bg-label-danger px-2 py-1 rounded d-block';
                desClass = 'bg-label-warning px-2 py-1 rounded d-block';
            }

            currentHtml += `<div class="mb-1 ${curClass}"><strong>${key}:</strong> ${curVal}</div>`;
            desiredHtml += `<div class="mb-1 ${desClass}"><strong>${key}:</strong> ${desVal}</div>`;
        });

        document.getElementById('detail-current-json').innerHTML = currentHtml;
        document.getElementById('detail-desired-json').innerHTML = desiredHtml;
    }

    // Confirmation dialog handler
    document.querySelectorAll('.confirm-submit').forEach(form => {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            const message = this.getAttribute('data-message') || "Are you sure you want to proceed?";
            if (confirm(message)) {
                this.submit();
            }
        });
    });

    // Helper functions for badges in JS
    function getStatusBadge(status) {
        if (status === 'Applied') return '<span class="badge bg-label-success">Applied</span>';
        if (status === 'Pending') return '<span class="badge bg-label-warning">Pending</span>';
        if (status === 'Queued') return '<span class="badge bg-label-info">Queued</span>';
        if (status === 'Sending') return '<span class="badge bg-label-primary">Sending</span>';
        if (status === 'Sent') return '<span class="badge bg-label-primary">Sent</span>';
        if (status === 'Failed') return '<span class="badge bg-label-danger">Failed</span>';
        if (status === 'Rejected') return '<span class="badge bg-label-danger">Rejected</span>';
        if (status === 'Timeout') return '<span class="badge bg-label-danger">Timeout</span>';
        if (status === 'Cancelled') return '<span class="badge bg-label-secondary">Cancelled</span>';
        if (status === 'Expired') return '<span class="badge bg-label-secondary">Expired</span>';
        return `<span class="badge bg-label-secondary">${status}</span>`;
    }

    // Laravel Reverb Realtime Configuration Listener
    if (window.Echo) {
        window.Echo.channel('device-configs')
            .listen('.config.status.updated', (e) => {
                console.log('Realtime Config Update:', e);

                // 1. Update or Prepend History Row
                const existingRow = document.getElementById(`history-row-${e.history_id}`);
                if (existingRow) {
                    const statusCell = existingRow.querySelector('.status-cell');
                    const messageCell = existingRow.querySelector('.message-cell');
                    if (statusCell) statusCell.innerHTML = getStatusBadge(e.status);
                    if (messageCell) messageCell.textContent = e.message;
                } else {
                    const tbody = document.getElementById('history-table-body');
                    if (tbody) {
                        const newRow = document.createElement('tr');
                        newRow.id = `history-row-${e.history_id}`;
                        
                        let changedBadges = '-';
                        if (e.changed_fields && e.changed_fields.length > 0) {
                            changedBadges = e.changed_fields.map(f => `<span class="badge bg-label-secondary" style="font-size: 0.65rem;">${f}</span>`).join(' ');
                        }

                        let actionHtml = '-';
                        if (e.new_config && !e.new_config.action && e.status !== 'Cancelled') {
                            actionHtml = `
                                <form action="{{ route('admin.config.rollback') }}" method="POST" class="d-inline confirm-submit" data-message="Are you sure you want to rollback to this configuration state?">
                                    @csrf
                                    <input type="hidden" name="camera_id" value="${e.camera_id}">
                                    <input type="hidden" name="history_id" value="${e.history_id}">
                                    <button type="submit" class="btn btn-xs btn-outline-warning" title="Rollback to this state">
                                        <i class="ti ti-history me-1"></i> Rollback
                                    </button>
                                </form>
                            `;
                        }

                        newRow.innerHTML = `
                            <td><strong>${e.camera_name}</strong></td>
                            <td class="status-cell">${getStatusBadge(e.status)}</td>
                            <td><code class="text-truncate d-block" style="max-width: 150px;" title="${JSON.stringify(e.new_config)}">${JSON.stringify(e.new_config)}</code></td>
                            <td class="message-cell" style="font-size: 0.8rem; white-space: normal; max-width: 200px;">${e.message}</td>
                            <td>${changedBadges}</td>
                            <td>System</td>
                            <td>${e.created_at}</td>
                            <td>${actionHtml}</td>
                        `;
                        tbody.insertBefore(newRow, tbody.firstChild);
                        
                        // Re-bind confirmation submit logic on dynamically added row
                        const newForm = newRow.querySelector('.confirm-submit');
                        if (newForm) {
                            newForm.addEventListener('submit', function (evt) {
                                evt.preventDefault();
                                if (confirm(this.getAttribute('data-message') || "Are you sure?")) {
                                    this.submit();
                                }
                            });
                        }

                        // Limit rows to 15
                        if (tbody.children.length > 15) {
                            tbody.removeChild(tbody.lastChild);
                        }
                    }
                }

                // 2. Update Camera Table Row
                const cameraRow = document.getElementById(`camera-row-${e.camera_id}`);
                if (cameraRow) {
                    const pendingCell = cameraRow.querySelector('.pending-changes-cell');
                    const driftCell = cameraRow.querySelector('.drift-status-cell');
                    const lastSyncCell = cameraRow.querySelector('.last-sync-cell');
                    const viewDetailsBtn = cameraRow.querySelector('.btn-view-details');

                    if (pendingCell) {
                        pendingCell.innerHTML = getStatusBadge(e.status);
                    }

                    if (lastSyncCell && e.last_sync) {
                        lastSyncCell.textContent = 'Just now';
                    }

                    // Update button data attributes for live details view
                    if (viewDetailsBtn) {
                        if (e.current_config) viewDetailsBtn.setAttribute('data-current', JSON.stringify(e.current_config));
                        if (e.desired_config) viewDetailsBtn.setAttribute('data-desired', JSON.stringify(e.desired_config));
                        if (e.current_config_version !== undefined) viewDetailsBtn.setAttribute('data-current-version', e.current_config_version);
                        if (e.desired_config_version !== undefined) viewDetailsBtn.setAttribute('data-desired-version', e.desired_config_version);
                        if (e.current_config_hash) viewDetailsBtn.setAttribute('data-current-hash', e.current_config_hash);
                        if (e.desired_config_hash) viewDetailsBtn.setAttribute('data-desired-hash', e.desired_config_hash);
                        if (e.last_applied_at) viewDetailsBtn.setAttribute('data-last-applied', e.last_applied_at);
                        if (e.last_sync) viewDetailsBtn.setAttribute('data-last-sync', e.last_sync);
                        if (e.last_failure_message) viewDetailsBtn.setAttribute('data-last-failure', e.last_failure_message);
                    }

                    // Update drift status badge in real time
                    if (driftCell) {
                        const curHash = e.current_config_hash || '';
                        const desHash = e.desired_config_hash || '';
                        if (curHash !== desHash) {
                            let driftFieldsList = e.changed_fields || [];
                            driftCell.innerHTML = `
                                <span class="badge bg-label-danger" data-bs-toggle="tooltip" data-bs-html="true" title="Drifted fields: ${driftFieldsList.join(', ')}">
                                    Configuration Drift
                                </span>
                            `;
                        } else {
                            driftCell.innerHTML = `<span class="badge bg-label-success">In Sync</span>`;
                        }
                    }
                }
            });
    }
});
</script>
@endsection
