@extends('layouts/layoutMaster')

@section('title', 'OTA Fleet Firmware Management')

@section('content')
<h4 class="mb-4">OTA Fleet Firmware Management</h4>

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

<div class="row g-4">
    <!-- Firmware Library -->
    <div class="col-12 col-lg-8">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Firmware Library</h5>
                <span class="badge bg-label-primary">{{ $firmwares->count() }} Available</span>
            </div>
            <div class="table-responsive text-nowrap">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Version</th>
                            <th>Build</th>
                            <th>Board</th>
                            <th>Model</th>
                            <th>Size</th>
                            <th>SHA256</th>
                            <th>Release Notes</th>
                            <th>Uploaded By</th>
                            <th>Uploaded At</th>
                            <th>Downloads</th>
                            <th>Deploys</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody class="table-border-bottom-0">
                        @forelse($firmwares as $fw)
                            <tr>
                                <td>
                                    <strong>v{{ $fw->version }}</strong>
                                    @if($fw->mandatory)
                                        <span class="badge bg-danger ms-1" style="font-size: 0.65rem;">Mandatory</span>
                                    @endif
                                </td>
                                <td><small class="text-muted">{{ $fw->build ?: 'N/A' }}</small></td>
                                <td>{{ $fw->board ?: 'N/A' }}</td>
                                <td>{{ $fw->model ?: 'N/A' }}</td>
                                <td>{{ $fw->formatted_size }}</td>
                                <td>
                                    <code class="text-truncate d-inline-block" style="max-width: 100px;" title="{{ $fw->sha256 }}">
                                        {{ substr($fw->sha256, 0, 8) }}...
                                    </code>
                                </td>
                                <td><small class="text-truncate d-inline-block" style="max-width: 120px;" title="{{ $fw->release_notes }}">{{ $fw->release_notes ?: '-' }}</small></td>
                                <td>{{ $fw->uploader ? $fw->uploader->name : 'System' }}</td>
                                <td>{{ $fw->created_at->format('Y-m-d H:i') }}</td>
                                <td>{{ $fw->download_count }}</td>
                                <td>{{ $fw->deploy_count }}</td>
                                <td>
                                    <div class="d-flex gap-2">
                                        <a href="{{ route('admin.ota.download', $fw->id) }}" class="btn btn-sm btn-icon btn-label-secondary" title="Download Binary">
                                            <i class="ti ti-download"></i>
                                        </a>
                                        <button class="btn btn-sm btn-icon btn-label-primary btn-deploy-fw" 
                                                data-id="{{ $fw->id }}" 
                                                data-version="{{ $fw->version }}"
                                                data-board="{{ $fw->board }}"
                                                data-model="{{ $fw->model }}"
                                                title="Deploy OTA">
                                            <i class="ti ti-device-mobile-message"></i>
                                        </button>
                                        <form action="{{ route('admin.ota.destroy', $fw->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete version v{{ $fw->version }}? This cannot be undone.');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-icon btn-label-danger" title="Delete">
                                                <i class="ti ti-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="12" class="text-center py-4 text-muted">No firmware uploaded yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Upload Firmware Form -->
    <div class="col-12 col-lg-4">
        <div class="card h-100">
            <h5 class="card-header">Upload Firmware</h5>
            <div class="card-body">
                <form action="{{ route('admin.ota.upload') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label" for="version">Version (SemVer)*</label>
                        <input type="text" class="form-control" id="version" name="version" placeholder="1.0.0" required />
                        <div class="form-text">Must be standard Major.Minor.Patch (e.g. 1.2.0)</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="build">Build Version (Optional)</label>
                        <input type="text" class="form-control" id="build" name="build" placeholder="e.g. build-101" />
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="board">Target Board (e.g. ESP32-CAM)*</label>
                        <input type="text" class="form-control" id="board" name="board" placeholder="ESP32-CAM" required />
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="model">Target Model (e.g. AI_THINKER)*</label>
                        <input type="text" class="form-control" id="model" name="model" placeholder="AI_THINKER" required />
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="min_version">Minimum Version (Optional)</label>
                        <input type="text" class="form-control" id="min_version" name="min_version" placeholder="0.9.0" />
                        <div class="form-text">Minimum version required to apply this update</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="firmware_file">Binary File (.bin)*</label>
                        <input type="file" class="form-control" id="firmware_file" name="firmware_file" accept=".bin" required />
                        <div class="form-text">Upload file size limit is 2MB.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="release_notes">Release Notes</label>
                        <textarea class="form-control" id="release_notes" name="release_notes" rows="2" placeholder="Describe the updates..."></textarea>
                    </div>
                    <div class="mb-3">
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" id="mandatory" name="mandatory">
                            <label class="form-check-label" for="mandatory">Mandatory Update</label>
                        </div>
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" id="rollback_allowed" name="rollback_allowed" checked>
                            <label class="form-check-label" for="rollback_allowed">Rollback Allowed</label>
                        </div>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="force" name="force">
                            <label class="form-check-label" for="force">Force Reboot & Apply</label>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Upload & Publish</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Fleet OTA Live Monitor -->
<div class="card mt-4">
    <div class="card-header d-flex justify-content-between align-items-center pb-2">
        <h5 class="mb-0">Fleet OTA Live Monitor</h5>
        <span class="badge bg-label-warning animate-pulse" id="live-indicator"><i class="ti ti-wifi me-1"></i>Listening...</span>
    </div>
    <div class="table-responsive text-nowrap">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>Deployment ID</th>
                    <th>Target Firmware</th>
                    <th>Started At</th>
                    <th>Elapsed Time</th>
                    <th>Progress</th>
                    <th>Completed</th>
                    <th>Success</th>
                    <th>Failed</th>
                    <th>Cancelled</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="fleet-live-monitor-tbody">
                @forelse($liveDeployments as $deploy)
                    @php
                        $camCount = $deploy->deploymentCameras->count();
                        $successCount = $deploy->deploymentCameras->where('status', 'Success')->count();
                        $failedCount = $deploy->deploymentCameras->where('status', 'Failed')->count();
                        $cancelledCount = $deploy->deploymentCameras->where('status', 'Cancelled')->count();
                        $completedCount = $successCount + $failedCount + $cancelledCount;
                        $pct = $camCount > 0 ? round(($completedCount / $camCount) * 100) : 0;
                        
                        $elapsed = '-';
                        if ($deploy->started_at) {
                            $finished = $deploy->finished_at ?: now();
                            $elapsedSeconds = $deploy->started_at->diffInSeconds($finished);
                            $elapsed = $elapsedSeconds . 's';
                        }
                    @endphp
                    <tr id="fleet-deploy-row-{{ $deploy->id }}" data-started="{{ $deploy->started_at ? $deploy->started_at->timestamp : '' }}" data-finished="{{ $deploy->finished_at ? $deploy->finished_at->timestamp : '' }}">
                        <td>
                            <code class="text-truncate d-inline-block" style="max-width: 100px;" title="{{ $deploy->id }}">
                                {{ substr($deploy->id, 0, 8) }}...
                            </code>
                        </td>
                        <td><strong>v{{ $deploy->firmware->version }}</strong></td>
                        <td>{{ $deploy->started_at ? $deploy->started_at->format('Y-m-d H:i:s') : 'Scheduled' }}</td>
                        <td class="elapsed-time">{{ $elapsed }}</td>
                        <td style="width: 180px;">
                            <div class="d-flex align-items-center gap-2">
                                <div class="progress w-100" style="height: 8px;">
                                    <div class="progress-bar bg-primary fleet-progress-bar" 
                                         role="progressbar" 
                                         style="width: {{ $pct }}%;" 
                                         aria-valuenow="{{ $pct }}" 
                                         aria-valuemin="0" 
                                         aria-valuemax="100">
                                    </div>
                                </div>
                                <small class="fleet-progress-pct">{{ $pct }}%</small>
                            </div>
                        </td>
                        <td class="count-completed">{{ $completedCount }}/{{ $camCount }}</td>
                        <td class="text-success count-success">{{ $successCount }}</td>
                        <td class="text-danger count-failed">{{ $failedCount }}</td>
                        <td class="text-secondary count-cancelled">{{ $cancelledCount }}</td>
                        <td>
                            <span class="badge bg-label-{{ $deploy->status === 'Success' ? 'success' : ($deploy->status === 'Failed' ? 'danger' : ($deploy->status === 'Cancelled' ? 'secondary' : 'info')) }} status-text">
                                {{ $deploy->status }}
                            </span>
                        </td>
                        <td>
                            @if(in_array($deploy->status, ['Pending', 'Running', 'Scheduled']))
                                <button class="btn btn-xs btn-label-danger btn-cancel-ota" data-id="{{ $deploy->id }}">Cancel</button>
                            @else
                                -
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr id="fleet-live-placeholder">
                        <td colspan="11" class="text-center py-4 text-muted">No active OTA deployments running in the last 12 hours.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Camera-Level Live Monitor -->
<div class="card mt-4">
    <div class="card-header">
        <h5 class="mb-0">Camera-Level Live Monitor</h5>
    </div>
    <div class="table-responsive text-nowrap">
        <table class="table table-hover" id="live-monitor-table">
            <thead>
                <tr>
                    <th>Camera</th>
                    <th>Target Version</th>
                    <th>Status</th>
                    <th>Progress</th>
                    <th>Last Message</th>
                    <th>Started At</th>
                </tr>
            </thead>
            <tbody id="live-monitor-tbody">
                @php
                    $activeCameraRuns = [];
                    foreach ($liveDeployments as $deploy) {
                        foreach ($deploy->deploymentCameras as $cam) {
                            $activeCameraRuns[] = $cam;
                        }
                    }
                @endphp
                @forelse($activeCameraRuns as $camRun)
                    <tr id="live-row-{{ $camRun->camera->device_id }}">
                        <td><strong>{{ $camRun->camera->name }}</strong></td>
                        <td>v{{ $camRun->target_version }}</td>
                        <td>
                            <span class="badge bg-label-{{ $camRun->status === 'Success' ? 'success' : ($camRun->status === 'Failed' ? 'danger' : ($camRun->status === 'Cancelled' ? 'secondary' : 'info')) }} status-text">
                                {{ $camRun->status }}
                            </span>
                        </td>
                        <td style="width: 200px;">
                            <div class="d-flex align-items-center gap-2">
                                <div class="progress w-100" style="height: 8px;">
                                    <div class="progress-bar bg-{{ $camRun->status === 'Success' ? 'success' : ($camRun->status === 'Failed' ? 'danger' : 'info') }}" 
                                         role="progressbar" 
                                         style="width: {{ $camRun->progress }}%;" 
                                         aria-valuenow="{{ $camRun->progress }}" 
                                         aria-valuemin="0" 
                                         aria-valuemax="100">
                                    </div>
                                </div>
                                <small class="progress-pct">{{ $camRun->progress }}%</small>
                            </div>
                        </td>
                        <td class="message-text">{{ $camRun->message ?: '-' }}</td>
                        <td>{{ $camRun->started_at ? $camRun->started_at->format('H:i:s') : '-' }}</td>
                    </tr>
                @empty
                    <tr id="no-live-placeholder">
                        <td colspan="6" class="text-center py-4 text-muted">No active camera updates.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Deployment History with Filters -->
<div class="card mt-4">
    <div class="card-header pb-2">
        <h5 class="mb-0">Deployment History</h5>
        <!-- Filters Form -->
        <form method="GET" action="{{ route('admin.ota.index') }}" class="row g-3 mt-2">
            <div class="col-12 col-md-3">
                <label class="form-label" for="filterCamera">Camera</label>
                <select class="form-select form-select-sm" id="filterCamera" name="camera_id">
                    <option value="">All Cameras</option>
                    @foreach($cameras as $cam)
                        <option value="{{ $cam->id }}" {{ request('camera_id') == $cam->id ? 'selected' : '' }}>
                            {{ $cam->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-md-3">
                <label class="form-label" for="filterVersion">Version</label>
                <select class="form-select form-select-sm" id="filterVersion" name="version">
                    <option value="">All Versions</option>
                    @foreach($firmwares->unique('version') as $fw)
                        <option value="{{ $fw->version }}" {{ request('version') == $fw->version ? 'selected' : '' }}>
                            v{{ $fw->version }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-md-3">
                <label class="form-label" for="filterStatus">Status</label>
                <select class="form-select form-select-sm" id="filterStatus" name="status">
                    <option value="">All Statuses</option>
                    <option value="Success" {{ request('status') == 'Success' ? 'selected' : '' }}>Success</option>
                    <option value="Failed" {{ request('status') == 'Failed' ? 'selected' : '' }}>Failed</option>
                    <option value="Cancelled" {{ request('status') == 'Cancelled' ? 'selected' : '' }}>Cancelled</option>
                </select>
            </div>
            <div class="col-12 col-md-2">
                <label class="form-label" for="filterDate">Date</label>
                <input type="date" class="form-control form-control-sm" id="filterDate" name="date" value="{{ request('date') }}">
            </div>
            <div class="col-12 col-md-1 d-flex align-items-end">
                <button type="submit" class="btn btn-sm btn-primary w-100">Filter</button>
            </div>
        </form>
    </div>
    <div class="table-responsive text-nowrap">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>Camera</th>
                    <th>Target Version</th>
                    <th>Status</th>
                    <th>Message</th>
                    <th>Started</th>
                    <th>Finished</th>
                    <th>Duration</th>
                </tr>
            </thead>
            <tbody>
                @forelse($history as $record)
                    <tr>
                        <td><strong>{{ $record->camera ? $record->camera->name : 'Unknown' }}</strong></td>
                        <td>v{{ $record->target_version }}</td>
                        <td>
                            <span class="badge bg-label-{{ $record->status === 'Success' ? 'success' : ($record->status === 'Failed' ? 'danger' : ($record->status === 'Cancelled' ? 'secondary' : 'warning')) }}">
                                {{ $record->status }}
                            </span>
                        </td>
                        <td><small class="text-wrap d-block" style="max-width: 250px;">{{ $record->message ?: '-' }}</small></td>
                        <td>{{ $record->started_at ? $record->started_at->format('Y-m-d H:i:s') : '-' }}</td>
                        <td>{{ $record->finished_at ? $record->finished_at->format('Y-m-d H:i:s') : '-' }}</td>
                        <td>
                            @if($record->duration_ms)
                                {{ number_format($record->duration_ms / 1000, 2) }}s
                            @else
                                -
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center py-4">No historical records found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($history->hasPages())
        <div class="card-footer">
            {{ $history->links() }}
        </div>
    @endif
</div>

<!-- Deploy Modal -->
<div class="modal fade" id="deployModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Deploy OTA Firmware</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info py-2">
                    Deploying: <strong id="deploy-version-label">v1.0.0</strong> (<span id="deploy-board-label">ESP32-CAM</span> / <span id="deploy-model-label">AI_THINKER</span>)
                </div>

                <!-- Older Version Warning Banner -->
                <div id="older-version-warning" class="alert alert-warning d-none">
                    <h6 class="alert-heading d-flex align-items-center mb-1">
                        <i class="ti ti-alert-triangle me-2"></i> Warning: Downgrade Detected
                    </h6>
                    <span>One or more of the selected cameras are running a newer version than the firmware you are about to deploy. Do you wish to continue?</span>
                </div>

                <!-- Hardware Incompatibility Warning Banner -->
                <div id="hardware-mismatch-warning" class="alert alert-danger d-none">
                    <h6 class="alert-heading d-flex align-items-center mb-1">
                        <i class="ti ti-ban me-2"></i> Error: Incompatible Hardware
                    </h6>
                    <span>One or more of the selected cameras do not match the target board/model requirements. Check the targets carefully!</span>
                </div>

                <div class="mb-3">
                    <label class="form-label d-block">Target Selection</label>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="deployTarget" id="targetSingle" value="single" checked>
                        <label class="form-check-label" for="targetSingle">Single Camera</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="deployTarget" id="targetSelected" value="selected">
                        <label class="form-check-label" for="targetSelected">Selected Cameras</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="deployTarget" id="targetAll" value="all">
                        <label class="form-check-label" for="targetAll">Entire Fleet</label>
                    </div>
                </div>

                <!-- Single Camera Target -->
                <div class="mb-3" id="single-camera-selection">
                    <label class="form-label" for="cameraSelect">Select Camera</label>
                    <select class="form-select" id="cameraSelect">
                        @foreach($cameras as $cam)
                            @php
                                $latestFw = $cam->latestTelemetry ? $cam->latestTelemetry->firmware : 'Unknown';
                                $camBoard = $cam->latestTelemetry ? $cam->latestTelemetry->board : '';
                                $camModel = $cam->latestTelemetry ? $cam->latestTelemetry->model : '';
                            @endphp
                            <option value="{{ $cam->id }}" data-fw="{{ $latestFw }}" data-board="{{ $camBoard }}" data-model="{{ $camModel }}">
                                {{ $cam->name }} (Current: v{{ $latestFw }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Selected Cameras Target -->
                <div class="mb-3 d-none text-start" id="selected-cameras-selection" style="max-height: 200px; overflow-y: auto; border: 1px solid #eee; padding: 10px; border-radius: 4px;">
                    <label class="form-label d-block">Select Cameras</label>
                    @foreach($cameras as $cam)
                        @php
                            $latestFw = $cam->latestTelemetry ? $cam->latestTelemetry->firmware : 'Unknown';
                            $camBoard = $cam->latestTelemetry ? $cam->latestTelemetry->board : '';
                            $camModel = $cam->latestTelemetry ? $cam->latestTelemetry->model : '';
                        @endphp
                        <div class="form-check">
                            <input class="form-check-input camera-checkbox" type="checkbox" value="{{ $cam->id }}" id="chk-{{ $cam->id }}" data-fw="{{ $latestFw }}" data-board="{{ $camBoard }}" data-model="{{ $camModel }}">
                            <label class="form-check-label" for="chk-{{ $cam->id }}">
                                {{ $cam->name }} (Current: v{{ $latestFw }})
                            </label>
                        </div>
                    @endforeach
                </div>

                <!-- Rollout Strategy -->
                <div class="mb-3">
                    <label class="form-label" for="rolloutPercentage">Staged Rollout Batch</label>
                    <select class="form-select" id="rolloutPercentage">
                        <option value="100">100% (All at once)</option>
                        <option value="50">50% Staged</option>
                        <option value="25">25% Staged</option>
                        <option value="10">10% Staged</option>
                    </select>
                </div>

                <!-- Scheduling -->
                <div class="mb-3">
                    <label class="form-label d-block">Deployment Time</label>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="deployScheduleType" id="schedNow" value="now" checked>
                        <label class="form-check-label" for="schedNow">Deploy Now</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="deployScheduleType" id="schedLater" value="later">
                        <label class="form-check-label" for="schedLater">Schedule Later</label>
                    </div>
                </div>

                <div class="mb-3 d-none" id="scheduled-time-container">
                    <label class="form-label" for="scheduledAt">Schedule Date/Time</label>
                    <input type="datetime-local" class="form-control" id="scheduledAt">
                </div>

                <!-- Notes -->
                <div class="mb-3">
                    <label class="form-label" for="deployNotes">Notes / Description</label>
                    <textarea class="form-control" id="deployNotes" rows="2" placeholder="e.g. Stability release"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="btn-submit-deployment">Trigger Deployment</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('page-script')
<script>
document.addEventListener('DOMContentLoaded', function() {
    let currentFwId = null;
    let currentFwVersion = '';
    let currentFwBoard = '';
    let currentFwModel = '';

    const deployModal = new bootstrap.Modal(document.getElementById('deployModal'));
    const btnSubmit = document.getElementById('btn-submit-deployment');
    const olderWarning = document.getElementById('older-version-warning');
    const mismatchWarning = document.getElementById('hardware-mismatch-warning');

    // Interval ticking for live elapsed time
    setInterval(() => {
        document.querySelectorAll('#fleet-live-monitor-tbody tr').forEach(row => {
            const statusText = row.querySelector('.status-text').innerText.trim();
            if (['Pending', 'Running'].includes(statusText)) {
                const startedTimestamp = parseInt(row.getAttribute('data-started'));
                if (startedTimestamp) {
                    const elapsed = Math.round(Date.now() / 1000 - startedTimestamp);
                    row.querySelector('.elapsed-time').innerText = elapsed + 's';
                }
            }
        });
    }, 1000);

    // Watch scheduler toggle
    document.querySelectorAll('input[name="deployScheduleType"]').forEach(radio => {
        radio.addEventListener('change', function() {
            const container = document.getElementById('scheduled-time-container');
            if (this.value === 'later') {
                container.classList.remove('d-none');
            } else {
                container.classList.add('d-none');
            }
        });
    });

    // Show warnings if version downgrade or hardware mismatches
    function checkVersionWarnings() {
        const targetType = document.querySelector('input[name="deployTarget"]:checked').value;
        const selectedVersion = currentFwVersion;
        
        let hasNewer = false;
        let hasMismatch = false;

        const compareSemver = (curr, target) => {
            const c = curr.split('.').map(Number);
            const t = target.split('.').map(Number);
            for(let i=0; i<3; i++) {
                if(isNaN(c[i]) || isNaN(t[i])) return false;
                if(c[i] > t[i]) return true;
                if(c[i] < t[i]) return false;
            }
            return false;
        };

        const validateHardware = (board, model) => {
            if (!board || !model) return false;
            return (board.toLowerCase() !== currentFwBoard.toLowerCase() || 
                    model.toLowerCase() !== currentFwModel.toLowerCase());
        };

        if (targetType === 'single') {
            const selectEl = document.getElementById('cameraSelect');
            const selectedOpt = selectEl.options[selectEl.selectedIndex];
            if(selectedOpt) {
                const currentFw = selectedOpt.getAttribute('data-fw');
                const camBoard = selectedOpt.getAttribute('data-board');
                const camModel = selectedOpt.getAttribute('data-model');

                if (currentFw !== 'Unknown' && compareSemver(currentFw, selectedVersion)) {
                    hasNewer = true;
                }
                if (validateHardware(camBoard, camModel)) {
                    hasMismatch = true;
                }
            }
        } else if (targetType === 'selected') {
            document.querySelectorAll('.camera-checkbox:checked').forEach(chk => {
                const currentFw = chk.getAttribute('data-fw');
                const camBoard = chk.getAttribute('data-board');
                const camModel = chk.getAttribute('data-model');

                if (currentFw !== 'Unknown' && compareSemver(currentFw, selectedVersion)) {
                    hasNewer = true;
                }
                if (validateHardware(camBoard, camModel)) {
                    hasMismatch = true;
                }
            });
        } else {
            // Entire fleet
            document.querySelectorAll('.camera-checkbox').forEach(chk => {
                const currentFw = chk.getAttribute('data-fw');
                const camBoard = chk.getAttribute('data-board');
                const camModel = chk.getAttribute('data-model');

                if (currentFw !== 'Unknown' && compareSemver(currentFw, selectedVersion)) {
                    hasNewer = true;
                }
                if (validateHardware(camBoard, camModel)) {
                    hasMismatch = true;
                }
            });
        }

        if (hasNewer) {
            olderWarning.classList.remove('d-none');
        } else {
            olderWarning.classList.add('d-none');
        }

        if (hasMismatch) {
            mismatchWarning.classList.remove('d-none');
        } else {
            mismatchWarning.classList.add('d-none');
        }
    }

    // Target type change event
    document.querySelectorAll('input[name="deployTarget"]').forEach(radio => {
        radio.addEventListener('change', function() {
            const singleSel = document.getElementById('single-camera-selection');
            const multiSel = document.getElementById('selected-cameras-selection');
            
            if(this.value === 'single') {
                singleSel.classList.remove('d-none');
                multiSel.classList.add('d-none');
            } else if(this.value === 'selected') {
                singleSel.classList.add('d-none');
                multiSel.classList.remove('d-none');
            } else {
                singleSel.classList.add('d-none');
                multiSel.classList.add('d-none');
            }
            checkVersionWarnings();
        });
    });

    document.getElementById('cameraSelect').addEventListener('change', checkVersionWarnings);
    document.querySelectorAll('.camera-checkbox').forEach(chk => {
        chk.addEventListener('change', checkVersionWarnings);
    });

    // Open Deploy Modal
    document.querySelectorAll('.btn-deploy-fw').forEach(btn => {
        btn.addEventListener('click', function() {
            currentFwId = this.getAttribute('data-id');
            currentFwVersion = this.getAttribute('data-version');
            currentFwBoard = this.getAttribute('data-board');
            currentFwModel = this.getAttribute('data-model');
            
            document.getElementById('deploy-version-label').innerText = 'v' + currentFwVersion;
            document.getElementById('deploy-board-label').innerText = currentFwBoard;
            document.getElementById('deploy-model-label').innerText = currentFwModel;
            
            olderWarning.classList.add('d-none');
            mismatchWarning.classList.add('d-none');
            
            checkVersionWarnings();
            deployModal.show();
        });
    });

    // Submit deployment with confirmation prompt
    btnSubmit.addEventListener('click', function() {
        const targetType = document.querySelector('input[name="deployTarget"]:checked').value;
        const rolloutPercentage = document.getElementById('rolloutPercentage').value;
        const schedType = document.querySelector('input[name="deployScheduleType"]:checked').value;
        const scheduledAt = document.getElementById('scheduledAt').value;
        const notes = document.getElementById('deployNotes').value;
        let cameraIds = [];

        if (targetType === 'single') {
            cameraIds.push(document.getElementById('cameraSelect').value);
        } else if (targetType === 'selected') {
            document.querySelectorAll('.camera-checkbox:checked').forEach(chk => {
                cameraIds.push(chk.value);
            });
            if(cameraIds.length === 0) {
                alert('Please select at least one camera.');
                return;
            }
        }

        if (schedType === 'later' && !scheduledAt) {
            alert('Please select a schedule time.');
            return;
        }

        // CONFIRMATION DIALOG
        const message = `Are you sure you want to trigger v${currentFwVersion} OTA update on target "${targetType}"?\n\n` + 
                        `Rollout batch size: ${rolloutPercentage}%\n` + 
                        `Timing: ${schedType === 'now' ? 'Deploy Now' : 'Scheduled at ' + scheduledAt}`;
        
        if (!confirm(message)) {
            return;
        }

        btnSubmit.disabled = true;
        btnSubmit.innerText = 'Triggering...';

        fetch('{{ route("admin.ota.deploy") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                firmware_id: currentFwId,
                target_type: targetType,
                camera_ids: cameraIds,
                rollout_percentage: rolloutPercentage,
                scheduled_at: schedType === 'later' ? scheduledAt : null,
                notes: notes
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                deployModal.hide();
                location.reload();
            } else {
                alert('Deployment failed: ' + data.message);
                btnSubmit.disabled = false;
                btnSubmit.innerText = 'Trigger Deployment';
            }
        })
        .catch(err => {
            console.error(err);
            alert('An error occurred during deployment triggering.');
            btnSubmit.disabled = false;
            btnSubmit.innerText = 'Trigger Deployment';
        });
    });

    // Cancel deployment
    document.addEventListener('click', function(e) {
        if(e.target && e.target.classList.contains('btn-cancel-ota')) {
            const btn = e.target;
            const deploymentId = btn.getAttribute('data-id');
            
            if(!confirm('Are you sure you want to cancel this OTA deployment? This will abort pending and staged updates for all target cameras.')) return;
            
            btn.disabled = true;
            btn.innerText = 'Canceling...';

            fetch('{{ route("admin.ota.cancel") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    deployment_id: deploymentId
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Cancellation failed: ' + data.message);
                    btn.disabled = false;
                    btn.innerText = 'Cancel';
                }
            })
            .catch(err => {
                console.error(err);
                alert('An error occurred.');
                btn.disabled = false;
                btn.innerText = 'Cancel';
            });
        }
    });

    // Real-time update using Laravel Reverb (via Echo)
    if (window.Echo) {
        window.Echo.channel('ota-updates')
            .listen('.ota.status.updated', (e) => {
                console.log('Realtime OTA status update received:', e);

                if (e.type === 'fleet_update') {
                    // Update Fleet Live Monitor
                    const placeholder = document.getElementById('fleet-live-placeholder');
                    if (placeholder) placeholder.remove();

                    let row = document.getElementById('fleet-deploy-row-' + e.deployment_id);
                    if (!row) {
                        const tbody = document.getElementById('fleet-live-monitor-tbody');
                        row = document.createElement('tr');
                        row.id = 'fleet-deploy-row-' + e.deployment_id;
                        row.setAttribute('data-started', Math.round(Date.now() / 1000));
                        tbody.prepend(row);
                    }

                    let badgeClass = 'info';
                    if (e.status === 'Success') badgeClass = 'success';
                    else if (e.status === 'Failed') badgeClass = 'danger';
                    else if (e.status === 'Cancelled') badgeClass = 'secondary';

                    const actionCellHtml = ['Pending', 'Running', 'Scheduled'].includes(e.status)
                        ? `<button class="btn btn-xs btn-label-danger btn-cancel-ota" data-id="${e.deployment_id}">Cancel</button>`
                        : '-';

                    row.innerHTML = `
                        <td>
                            <code class="text-truncate d-inline-block" style="max-width: 100px;" title="${e.deployment_id}">
                                ${e.deployment_id.substring(0, 8)}...
                            </code>
                        </td>
                        <td><strong>-</strong></td>
                        <td>Just Now</td>
                        <td class="elapsed-time">${e.elapsed_seconds || 0}s</td>
                        <td style="width: 180px;">
                            <div class="d-flex align-items-center gap-2">
                                <div class="progress w-100" style="height: 8px;">
                                    <div class="progress-bar bg-primary fleet-progress-bar" 
                                         role="progressbar" 
                                         style="width: ${e.progress}%;" 
                                         aria-valuenow="${e.progress}" 
                                         aria-valuemin="0" 
                                         aria-valuemax="100">
                                    </div>
                                </div>
                                <small class="fleet-progress-pct">${e.progress}%</small>
                            </div>
                        </td>
                        <td class="count-completed">${e.completed_count}/${e.total_count}</td>
                        <td class="text-success count-success">${e.success_count}</td>
                        <td class="text-danger count-failed">${e.failed_count}</td>
                        <td class="text-secondary count-cancelled">${e.cancelled_count}</td>
                        <td>
                            <span class="badge bg-label-${badgeClass} status-text">
                                ${e.status}
                            </span>
                        </td>
                        <td>${actionCellHtml}</td>
                    `;
                } else {
                    // Update Camera-Level Live Monitor
                    const placeholder = document.getElementById('no-live-placeholder');
                    if (placeholder) placeholder.remove();

                    let row = document.getElementById('live-row-' + e.device_id);
                    if (!row) {
                        const tbody = document.getElementById('live-monitor-tbody');
                        row = document.createElement('tr');
                        row.id = 'live-row-' + e.device_id;
                        tbody.prepend(row);
                    }

                    let badgeClass = 'info';
                    if (e.status === 'Success') badgeClass = 'success';
                    else if (e.status === 'Failed') badgeClass = 'danger';
                    else if (e.status === 'Cancelled') badgeClass = 'secondary';

                    let progressClass = 'info';
                    if (e.status === 'Success') progressClass = 'success';
                    else if (e.status === 'Failed') progressClass = 'danger';

                    row.innerHTML = `
                        <td><strong>${e.camera_name}</strong></td>
                        <td>v${e.version}</td>
                        <td>
                            <span class="badge bg-label-${badgeClass} status-text">
                                ${e.status}
                            </span>
                        </td>
                        <td style="width: 200px;">
                            <div class="d-flex align-items-center gap-2">
                                <div class="progress w-100" style="height: 8px;">
                                    <div class="progress-bar bg-${progressClass}" 
                                         role="progressbar" 
                                         style="width: ${e.progress}%;" 
                                         aria-valuenow="${e.progress}" 
                                         aria-valuemin="0" 
                                         aria-valuemax="100">
                                    </div>
                                </div>
                                <small class="progress-pct">${e.progress}%</small>
                            </div>
                        </td>
                        <td class="message-text">${e.message || '-'}</td>
                        <td>${new Date().toLocaleTimeString()}</td>
                    `;
                }
            });
    }
});
</script>
@endsection
