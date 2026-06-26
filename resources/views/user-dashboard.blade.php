@extends('layouts/layoutMaster')

@section('title', 'Dashboard User')



@section('page-script')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const allCameraCards = document.querySelectorAll('.camera-card');

            // 1. Client-Side State Machine & Freshness Engine
            function updateClientSideStates() {
                let totalCount = 0;
                let onlineCount = 0;
                let warningCount = 0;
                let offlineCount = 0;

                const now = Date.now();

                allCameraCards.forEach(card => {
                    totalCount++;
                    const cameraId = card.dataset.cameraId;
                    const timestamp = parseInt(card.dataset.latestImageTimestamp) || 0;

                    let elapsed = 0;
                    let status = 'OFFLINE';

                    if (timestamp > 0) {
                        elapsed = (now - timestamp) / 1000;
                        if (elapsed < 0) elapsed = 0;

                        const reconnectDelta = parseInt(card.dataset.reconnectDelta) || 0;
                        const publishFailDelta = parseInt(card.dataset.publishFailDelta) || 0;
                        const hasWarning = (reconnectDelta > 0 || publishFailDelta > 0);

                        if (elapsed <= 15) {
                            status = hasWarning ? 'WARNING' : 'ONLINE';
                        } else if (elapsed <= 60) {
                            status = 'WARNING';
                        } else {
                            status = 'OFFLINE';
                        }
                    }

                    if (status === 'ONLINE') onlineCount++;
                    else if (status === 'WARNING') warningCount++;
                    else offlineCount++;



                    // Update Health Status Badge
                    const healthBadge = document.getElementById(`health-badge-${cameraId}`);
                    if (healthBadge) {
                        healthBadge.textContent = status;
                        healthBadge.className = 'badge telemetry-health-badge';
                        if (status === 'ONLINE') {
                            healthBadge.classList.add('bg-label-success');
                        } else if (status === 'WARNING') {
                            healthBadge.classList.add('bg-label-warning');
                        } else {
                            healthBadge.classList.add('bg-label-danger');
                        }
                    }

                    // Update Freshness Text
                    const freshnessEl = document.getElementById(`freshness-${cameraId}`);
                    if (freshnessEl) {
                        if (timestamp === 0) {
                            freshnessEl.textContent = 'Offline';
                        } else if (elapsed <= 60) {
                            freshnessEl.textContent = `Updated ${Math.round(elapsed)} sec ago`;
                        } else {
                            freshnessEl.textContent = `Offline ${Math.round(elapsed / 60)} min`;
                        }
                    }

                    // Update modal preview health badge and freshness
                    const modalHealth = document.getElementById(`modal-preview-health-${cameraId}`);
                    if (modalHealth) {
                        modalHealth.textContent = status;
                        modalHealth.className = 'badge telemetry-health-badge';
                        if (status === 'ONLINE') {
                            modalHealth.classList.add('bg-label-success');
                        } else if (status === 'WARNING') {
                            modalHealth.classList.add('bg-label-warning');
                        } else {
                            modalHealth.classList.add('bg-label-danger');
                        }
                    }

                    const modalFreshness = document.getElementById(`modal-preview-freshness-${cameraId}`);
                    if (modalFreshness) {
                        if (timestamp === 0) {
                            modalFreshness.textContent = 'Offline';
                        } else if (elapsed <= 60) {
                            modalFreshness.textContent = `Updated ${Math.round(elapsed)} sec ago`;
                        } else {
                            modalFreshness.textContent = `Offline ${Math.round(elapsed / 60)} min`;
                        }
                    }
                });

                // Update header summary numbers
                const elTotal = document.getElementById('summary-total');
                const elOnline = document.getElementById('summary-online');
                const elWarning = document.getElementById('summary-warning');
                const elOffline = document.getElementById('summary-offline');

                if (elTotal) elTotal.textContent = totalCount;
                if (elOnline) elOnline.textContent = onlineCount;
                if (elWarning) elWarning.textContent = warningCount;
                if (elOffline) elOffline.textContent = offlineCount;

                document.querySelectorAll('.summary-total-denominator').forEach(el => {
                    el.textContent = totalCount;
                });

                const onlinePercent = totalCount > 0 ? Math.round((onlineCount / totalCount) * 100) : 0;
                const warningPercent = totalCount > 0 ? Math.round((warningCount / totalCount) * 100) : 0;
                const offlinePercent = totalCount > 0 ? Math.round((offlineCount / totalCount) * 100) : 0;

                const elOnlinePercent = document.getElementById('summary-online-percent');
                const elWarningPercent = document.getElementById('summary-warning-percent');
                const elOfflinePercent = document.getElementById('summary-offline-percent');

                if (elOnlinePercent) elOnlinePercent.textContent = `${onlinePercent}%`;
                if (elWarningPercent) elWarningPercent.textContent = `${warningPercent}%`;
                if (elOfflinePercent) elOfflinePercent.textContent = `${offlinePercent}%`;
            }

            // Initialize state machine
            updateClientSideStates();
            setInterval(updateClientSideStates, 1000);

            // 2. Subscribe ke channel kamera masing-masing menggunakan WebSocket (Reverb)
            if (window.Echo) {
                // Subscribe to detections channel for real-time person detection updates
                window.Echo.channel('detections')
                    .listen('.person.detected', (data) => {
                        // Show browser toast
                        let toastContainer = document.querySelector('.toast-container');
                        if (!toastContainer) {
                            toastContainer = document.createElement('div');
                            toastContainer.className = 'toast-container position-fixed bottom-0 end-0 p-3';
                            toastContainer.style.zIndex = '1100';
                            document.body.appendChild(toastContainer);
                        }

                        const toastId = 'toast-' + Date.now() + '-' + Math.floor(Math.random() * 1000);
                        const toastHTML = `
                            <div id="${toastId}" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
                                <div class="toast-header bg-danger text-white">
                                    <strong class="me-auto">Person detected</strong>
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
                                </div>
                                <div class="toast-body">
                                    <strong>${data.camera_name}</strong><br>
                                    Confidence: ${data.confidence}
                                </div>
                            </div>
                        `;
                        toastContainer.insertAdjacentHTML('beforeend', toastHTML);
                        const toastElement = document.getElementById(toastId);
                        const bs = window.bootstrap || bootstrap;
                        if (bs && bs.Toast) {
                            const toast = new bs.Toast(toastElement, { delay: 5000 });
                            toast.show();
                            toastElement.addEventListener('hidden.bs.toast', () => {
                                toastElement.remove();
                            });
                        }

                        const detectionCard = document.getElementById('latest-person-detection-card');
                        if (detectionCard) {
                            detectionCard.style.display = 'flex';
                        }

                        const cameraNameEl = document.getElementById('detection-camera-name');
                        if (cameraNameEl) cameraNameEl.textContent = data.camera_name;

                        const confidenceEl = document.getElementById('detection-confidence');
                        if (confidenceEl) confidenceEl.textContent = data.confidence;

                        const timeEl = document.getElementById('detection-time');
                        if (timeEl) timeEl.textContent = data.timestamp;

                        const snapshotEl = document.getElementById('detection-snapshot');
                        if (snapshotEl) snapshotEl.src = data.image_url;

                        const noDetectionEl = document.getElementById('no-person-detection-placeholder');
                        if (noDetectionEl) noDetectionEl.style.display = 'none';
                    });

                allCameraCards.forEach(cameraCard => {
                    const imgElement = cameraCard.querySelector('.camera-feed-image');
                    const timestampElement = cameraCard.querySelector('.camera-timestamp');
                    const channelId = imgElement ? imgElement.dataset.websocketChannel : null;

                    if (channelId) {
                        window.Echo.channel(channelId)
                            .listen('.image.received', (data) => {
                                if (imgElement.src !== data.image_url) {
                                    imgElement.src = data.image_url;
                                }
                                if (timestampElement) {
                                    timestampElement.textContent = 'Update: ' + data.captured_at;
                                }

                                cameraCard.dataset.latestImageTimestamp = data.latest_image_timestamp;
                                cameraCard.dataset.reconnectDelta = data.mqtt_reconnect.replace('+', '');
                                cameraCard.dataset.publishFailDelta = data.publish_fail.replace('+', '');

                                // Update telemetry fields in compact view
                                const rssiEl = document.getElementById(`telemetry-rssi-${data.camera_id}`);
                                const heapEl = document.getElementById(`telemetry-heap-${data.camera_id}`);
                                const publishEl = document.getElementById(`telemetry-publish-${data.camera_id}`);
                                const mqttEl = document.getElementById(`telemetry-mqtt-${data.camera_id}`);
                                const wsEl = document.getElementById(`telemetry-ws-${data.camera_id}`);
                                const reconnectEl = document.getElementById(`telemetry-reconnect-${data.camera_id}`);
                                const wsCloseEl = document.getElementById(`telemetry-ws-close-${data.camera_id}`);
                                const pubFailEl = document.getElementById(`telemetry-pub-fail-${data.camera_id}`);
                                const uptimeEl = document.getElementById(`telemetry-uptime-${data.camera_id}`);

                                if (rssiEl) rssiEl.textContent = data.rssi;
                                if (heapEl) heapEl.textContent = data.heap;
                                if (publishEl) publishEl.textContent = data.publish_ms;
                                if (mqttEl) mqttEl.textContent = data.mqtt_connected;
                                if (wsEl) wsEl.textContent = data.ws_connected;
                                if (reconnectEl) reconnectEl.textContent = data.mqtt_reconnect;
                                if (wsCloseEl) wsCloseEl.textContent = data.ws_close_count;
                                if (pubFailEl) pubFailEl.textContent = data.publish_fail;
                                if (uptimeEl) uptimeEl.textContent = data.uptime;

                                // Update modal preview elements
                                const modalRssi = document.getElementById(`modal-preview-rssi-${data.camera_id}`);
                                const modalHeap = document.getElementById(`modal-preview-heap-${data.camera_id}`);
                                const modalPublish = document.getElementById(`modal-preview-publish-${data.camera_id}`);
                                const modalMqtt = document.getElementById(`modal-preview-mqtt-${data.camera_id}`);
                                const modalWs = document.getElementById(`modal-preview-ws-${data.camera_id}`);
                                const modalReconnect = document.getElementById(`modal-preview-reconnect-${data.camera_id}`);
                                const modalWsClose = document.getElementById(`modal-preview-ws-close-${data.camera_id}`);
                                const modalPubFail = document.getElementById(`modal-preview-pub-fail-${data.camera_id}`);
                                const modalUptime = document.getElementById(`modal-preview-uptime-${data.camera_id}`);
                                const modalImage = document.getElementById(`preview-image-${data.camera_id}`);

                                if (modalRssi) modalRssi.textContent = data.rssi;
                                if (modalHeap) modalHeap.textContent = data.heap;
                                if (modalPublish) modalPublish.textContent = data.publish_ms;
                                if (modalMqtt) modalMqtt.textContent = data.mqtt_connected;
                                if (modalWs) modalWs.textContent = data.ws_connected;
                                if (modalReconnect) modalReconnect.textContent = data.mqtt_reconnect;
                                if (modalWsClose) modalWsClose.textContent = data.ws_close_count;
                                if (modalPubFail) modalPubFail.textContent = data.publish_fail;
                                if (modalUptime) modalUptime.textContent = data.uptime;
                                if (modalImage) modalImage.src = data.image_url;

                                // Also update new fields from image received if present
                                const fEl = document.getElementById(`modal-firmware-${data.camera_id}`);
                                if (fEl && data.firmware) fEl.textContent = data.firmware;
                                const otaBuild = document.getElementById(`modal-build-${data.camera_id}`);
                                if (otaBuild && data.build) otaBuild.textContent = data.build;
                                const otaBoard = document.getElementById(`modal-board-${data.camera_id}`);
                                if (otaBoard && data.board) otaBoard.textContent = data.board;
                                const otaModel = document.getElementById(`modal-model-${data.camera_id}`);
                                if (otaModel && data.model) otaModel.textContent = data.model;
                                const otaLast = document.getElementById(`modal-last-ota-${data.camera_id}`);
                                if (otaLast && data.last_ota) otaLast.textContent = data.last_ota;
                                const otaCurDep = document.getElementById(`modal-current-deployment-${data.camera_id}`);
                                if (otaCurDep && data.current_deployment_id) otaCurDep.textContent = data.current_deployment_id;
                                const otaSup = document.getElementById(`modal-ota-supported-${data.camera_id}`);
                                if (otaSup && data.ota_supported) otaSup.textContent = data.ota_supported;
                                const otaRun = document.getElementById(`modal-ota-running-${data.camera_id}`);
                                if (otaRun && data.ota_running) otaRun.textContent = data.ota_running;
                                const otaSpc = document.getElementById(`modal-free-ota-space-${data.camera_id}`);
                                if (otaSpc && data.free_ota_space) otaSpc.textContent = data.free_ota_space;
                                const otaRes = document.getElementById(`modal-last-ota-result-${data.camera_id}`);
                                if (otaRes && data.last_ota_result) otaRes.textContent = data.last_ota_result;
                                const wifiCh = document.getElementById(`modal-wifi-channel-${data.camera_id}`);
                                if (wifiCh && data.wifi_channel) wifiCh.textContent = data.wifi_channel;
                                const wifiBssid = document.getElementById(`modal-wifi-bssid-${data.camera_id}`);
                                if (wifiBssid && data.wifi_bssid) wifiBssid.textContent = data.wifi_bssid;

                                updateClientSideStates();
                            })
                            .listen('.telemetry.updated', (data) => {
                                // Update compact view telemetry
                                const rssiEl = document.getElementById(`telemetry-rssi-${data.camera_id}`);
                                const heapEl = document.getElementById(`telemetry-heap-${data.camera_id}`);
                                const publishEl = document.getElementById(`telemetry-publish-${data.camera_id}`);
                                const mqttEl = document.getElementById(`telemetry-mqtt-${data.camera_id}`);
                                const wsEl = document.getElementById(`telemetry-ws-${data.camera_id}`);
                                const uptimeEl = document.getElementById(`telemetry-uptime-${data.camera_id}`);

                                if (rssiEl) rssiEl.textContent = data.rssi;
                                if (heapEl) heapEl.textContent = data.free_heap;
                                if (publishEl) publishEl.textContent = data.publish_ms;
                                if (mqttEl) mqttEl.textContent = data.mqtt_status;
                                if (wsEl) wsEl.textContent = data.ws_status;
                                if (uptimeEl) uptimeEl.textContent = data.uptime;

                                // Update modal preview
                                const modalRssi = document.getElementById(`modal-preview-rssi-${data.camera_id}`);
                                const modalHeap = document.getElementById(`modal-preview-heap-${data.camera_id}`);
                                const modalPublish = document.getElementById(`modal-preview-publish-${data.camera_id}`);
                                const modalMqtt = document.getElementById(`modal-preview-mqtt-${data.camera_id}`);
                                const modalWs = document.getElementById(`modal-preview-ws-${data.camera_id}`);
                                const modalUptime = document.getElementById(`modal-preview-uptime-${data.camera_id}`);

                                if (modalRssi) modalRssi.textContent = data.rssi;
                                if (modalHeap) modalHeap.textContent = data.free_heap;
                                if (modalPublish) modalPublish.textContent = data.publish_ms;
                                if (modalMqtt) modalMqtt.textContent = data.mqtt_status;
                                if (modalWs) modalWs.textContent = data.ws_status;
                                if (modalUptime) modalUptime.textContent = data.uptime;

                                // Update detail telemetry modal
                                const detRssi = document.getElementById(`modal-rssi-${data.camera_id}`);
                                const detHeap = document.getElementById(`modal-heap-${data.camera_id}`);
                                const detPublish = document.getElementById(`modal-publish-${data.camera_id}`);
                                const detMqtt = document.getElementById(`modal-mqtt-${data.camera_id}`);
                                const detWs = document.getElementById(`modal-ws-${data.camera_id}`);
                                const detUptime = document.getElementById(`modal-uptime-${data.camera_id}`);

                                if (detRssi) detRssi.textContent = data.rssi;
                                if (detHeap) detHeap.textContent = data.free_heap;
                                if (detPublish) detPublish.textContent = data.publish_ms;
                                if (detMqtt) detMqtt.textContent = data.mqtt_status;
                                if (detWs) detWs.textContent = data.ws_status;
                                if (detUptime) detUptime.textContent = data.uptime;

                                // Update new OTA/WiFi fields
                                const fEl = document.getElementById(`modal-firmware-${data.camera_id}`);
                                if (fEl) fEl.textContent = data.firmware;
                                const otaBuild = document.getElementById(`modal-build-${data.camera_id}`);
                                if (otaBuild) otaBuild.textContent = data.build;
                                const otaBoard = document.getElementById(`modal-board-${data.camera_id}`);
                                if (otaBoard) otaBoard.textContent = data.board;
                                const otaModel = document.getElementById(`modal-model-${data.camera_id}`);
                                if (otaModel) otaModel.textContent = data.model;
                                const otaLast = document.getElementById(`modal-last-ota-${data.camera_id}`);
                                if (otaLast) otaLast.textContent = data.last_ota;
                                const otaCurDep = document.getElementById(`modal-current-deployment-${data.camera_id}`);
                                if (otaCurDep) otaCurDep.textContent = data.current_deployment_id;
                                const otaSup = document.getElementById(`modal-ota-supported-${data.camera_id}`);
                                if (otaSup) otaSup.textContent = data.ota_supported;
                                const otaRun = document.getElementById(`modal-ota-running-${data.camera_id}`);
                                if (otaRun) otaRun.textContent = data.ota_running;
                                const otaSpc = document.getElementById(`modal-free-ota-space-${data.camera_id}`);
                                if (otaSpc) otaSpc.textContent = data.free_ota_space;
                                const otaRes = document.getElementById(`modal-last-ota-result-${data.camera_id}`);
                                if (otaRes) otaRes.textContent = data.last_ota_result;
                                const wifiCh = document.getElementById(`modal-wifi-channel-${data.camera_id}`);
                                if (wifiCh) wifiCh.textContent = data.wifi_channel;
                                const wifiBssid = document.getElementById(`modal-wifi-bssid-${data.camera_id}`);
                                if (wifiBssid) wifiBssid.textContent = data.wifi_bssid;

                                // Update Configuration Fields
                                const pName = data.assigned_profile ? data.assigned_profile.name : 'None';
                                const profEl = document.getElementById(`modal-assigned-profile-${data.camera_id}`);
                                if (profEl) profEl.textContent = pName;

                                const lastC = document.getElementById(`modal-last-config-${data.camera_id}`);
                                if (lastC) lastC.textContent = data.last_config_time;
                                const lastS = document.getElementById(`modal-last-sync-${data.camera_id}`);
                                if (lastS) lastS.textContent = data.last_sync;

                                // Helper to update field, text, and drift styling
                                const updateFieldDrift = (fieldKey, telemetryVal, profileVal, textVal, expectedText) => {
                                    const cell = document.getElementById(`modal-${fieldKey}-${data.camera_id}`);
                                    const row = document.getElementById(`row-${fieldKey}-${data.camera_id}`);
                                    if (cell) {
                                        if (data.assigned_profile && telemetryVal != profileVal) {
                                            cell.textContent = `${textVal} (Expected: ${expectedText})`;
                                            if (row) row.className = 'table-warning text-danger fw-bold';
                                        } else {
                                            cell.textContent = textVal;
                                            if (row) row.className = '';
                                        }
                                    }
                                };

                                if (data.assigned_profile) {
                                    const prof = data.assigned_profile;
                                    updateFieldDrift('jpeg', data.jpeg_quality, prof.jpeg_quality, data.jpeg_quality, prof.jpeg_quality);
                                    updateFieldDrift('size', data.frame_size, prof.frame_size, data.frame_size, prof.frame_size);
                                    updateFieldDrift('capture-interval', data.capture_interval_ms, prof.capture_interval_ms, `${data.capture_interval_ms} ms`, `${prof.capture_interval_ms}ms`);
                                    updateFieldDrift('telemetry-interval', data.telemetry_interval_ms, prof.telemetry_interval_ms, `${data.telemetry_interval_ms} ms`, `${prof.telemetry_interval_ms}ms`);
                                    updateFieldDrift('mqtt-buffer', data.mqtt_buffer, prof.mqtt_buffer, data.mqtt_buffer, prof.mqtt_buffer);

                                    const imgB = data.image_enabled === 'Enabled';
                                    const pImgB = !!prof.image_enabled;
                                    updateFieldDrift('image-enabled', imgB, pImgB, data.image_enabled, pImgB ? 'Enabled' : 'Disabled');

                                    const telB = data.telemetry_enabled === 'Enabled';
                                    const pTelB = !!prof.telemetry_enabled;
                                    updateFieldDrift('telemetry-enabled', telB, pTelB, data.telemetry_enabled, pTelB ? 'Enabled' : 'Disabled');

                                    const otaB = data.ota_enabled === 'Enabled';
                                    const pOtaB = !!prof.ota_enabled;
                                    updateFieldDrift('ota-enabled', otaB, pOtaB, data.ota_enabled, pOtaB ? 'Enabled' : 'Disabled');
                                } else {
                                    // No profile assigned
                                    const fields = ['jpeg', 'size', 'capture-interval', 'telemetry-interval', 'mqtt-buffer', 'image-enabled', 'telemetry-enabled', 'ota-enabled'];
                                    fields.forEach(f => {
                                        const cell = document.getElementById(`modal-${f}-${data.camera_id}`);
                                        const row = document.getElementById(`row-${f}-${data.camera_id}`);
                                        if (row) row.className = '';
                                        if (cell) {
                                            if (f === 'jpeg') cell.textContent = data.jpeg_quality;
                                            if (f === 'size') cell.textContent = data.frame_size;
                                            if (f === 'capture-interval') cell.textContent = `${data.capture_interval_ms} ms`;
                                            if (f === 'telemetry-interval') cell.textContent = `${data.telemetry_interval_ms} ms`;
                                            if (f === 'mqtt-buffer') cell.textContent = data.mqtt_buffer;
                                            if (f === 'image-enabled') cell.textContent = data.image_enabled;
                                            if (f === 'telemetry-enabled') cell.textContent = data.telemetry_enabled;
                                            if (f === 'ota-enabled') cell.textContent = data.ota_enabled;
                                        }
                                    });
                                }

                                updateClientSideStates();
                            });
                    }
                });
            }

            document.querySelectorAll('.camera-card').forEach(card => {
                card.addEventListener('click', function (e) {
                    if (e.target.closest('button') || e.target.closest('a') || e.target.closest('.modal')) {
                        return;
                    }
                    const cameraId = card.dataset.cameraId;
                    const modalEl = document.getElementById(`cameraPreviewModal-${cameraId}`);
                    if (modalEl) {
                        const modal = new bootstrap.Modal(modalEl);
                        modal.show();
                    }
                });
            });

            // --- Toggle collapse untuk grup ---
            document.querySelectorAll('[data-bs-toggle="collapse"]').forEach(button => {
                button.addEventListener('click', function () {
                    const icon = this.querySelector('.toggle-icon');
                    if (icon) icon.classList.toggle('collapsed');
                });
            });

            // --- Auto-submit form filter ---
            const groupSelect = document.getElementById('groupFilter');
            if (groupSelect) {
                groupSelect.addEventListener('change', function () {
                    this.form.submit();
                });
            }
        });
    </script>
@endsection

@section('content')
    {{-- Header Dashboard --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1">Dashboard Pemantauan Kamera</h4>
            <p class="mb-0 text-muted">Selamat datang kembali, <strong>{{ auth()->user()->name ?? 'User' }}</strong>.</p>
        </div>

        <div>
            <a href="{{ route('user.camera-groups.index') }}" class="btn btn-outline-primary shadow-sm">
                <i class="ti ti-settings me-1"></i> Kelola Grup
            </a>
        </div>
    </div>

    @php
        $totalCountVal = $totalCameras ?? 0;
        $onlinePercent = $totalCountVal > 0 ? round(($onlineCameras ?? 0) / $totalCountVal * 100) : 0;
        $warningPercent = $totalCountVal > 0 ? round(($warningCameras ?? 0) / $totalCountVal * 100) : 0;
        $offlinePercent = $totalCountVal > 0 ? round(($offlineCameras ?? 0) / $totalCountVal * 100) : 0;
        $groupedCameras = $cameras->groupBy(function ($camera) {
            return $camera->group ? $camera->group->name : 'Tanpa Grup';
        });
        $showGroupHeaders = ($currentGroup ?? 'Semua Kamera') == 'Semua Kamera';
    @endphp

    {{-- Section 1: Realtime Cameras --}}
    <div class="mb-5">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0 fw-bold"><i class="ti ti-video me-2 text-primary"></i>Realtime Cameras</h5>
            @if(($currentGroup ?? 'Semua Kamera') != 'Semua Kamera')
                <span class="badge bg-label-primary border ms-2"
                    style="border-color: #dbeafe !important;">{{ $currentGroup }}</span>
            @endif
        </div>

        {{-- Filter Grup --}}
        @if(count($groups ?? []) > 1)
            <div class="card mb-4 border-0 shadow-sm">
                <div class="card-body py-3 px-4">
                    <form method="POST" action="{{ route('user.dashboard.groups') }}" id="groupFilterForm">
                        @csrf
                        <div class="row align-items-center">
                            <div class="col-auto">
                                <label class="form-label mb-0 fw-semibold"><i class="ti ti-filter me-1 text-primary"></i> Filter
                                    Grup:</label>
                            </div>
                            <div class="col-md-4">
                                <select name="group" id="groupFilter" class="form-select border-0 bg-light">
                                    @foreach($groups as $group)
                                        <option value="{{ $group }}" {{ ($currentGroup ?? '') == $group ? 'selected' : '' }}>
                                            {{ $group }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        @endif

        {{-- Grid Kamera --}}
        @if($cameras->count() > 0)
            <div class="row g-3">
                <div class="col-12">
                    @foreach($groupedCameras as $groupName => $groupCameras)
                        <div class="mb-3">
                            @if($showGroupHeaders)
                                <div class="group-header">
                                    <h5>
                                        <i class="ti ti-folder me-2"></i>
                                        {{ $groupName }}
                                        <span class="badge bg-label-primary border ms-2"
                                            style="border-color: #dbeafe !important;">{{ $groupCameras->count() }}</span>
                                    </h5>
                                    <div class="group-actions">
                                        <button class="btn btn-sm btn-light" type="button" data-bs-toggle="collapse"
                                            data-bs-target="#group-{{ \Illuminate\Support\Str::slug($groupName) }}"
                                            aria-expanded="true">
                                            <i class="ti ti-chevron-down toggle-icon"></i>
                                        </button>
                                    </div>
                                </div>
                            @endif

                            <div class="collapse show" id="group-{{ \Illuminate\Support\Str::slug($groupName) }}">
                                <div class="row g-3">
                                    @foreach($groupCameras as $camera)
                                        @php $telemetry = $camera->latestTelemetry; @endphp
                                        <div class="col-12 col-md-6 col-lg-4 col-xl-3 camera-card" data-camera-id="{{ $camera->id }}"
                                            data-latest-image-timestamp="{{ $camera->latest_image_at ? $camera->latest_image_at->timestamp * 1000 : 0 }}"
                                            data-reconnect-delta="{{ $telemetry ? $telemetry->reconnect_delta : 0 }}"
                                            data-publish-fail-delta="{{ $telemetry ? $telemetry->publish_fail_delta : 0 }}">

                                            <div class="card h-100 shadow-sm border-0">

                                                <div
                                                    class="card-header d-flex justify-content-between align-items-center bg-transparent border-0 pb-0 px-3 pt-3">
                                                    <div class="min-w-0">
                                                        <h6 class="card-title mb-0 fw-bold text-truncate" style="max-width: 100%;">
                                                            {{ $camera->name }}</h6>
                                                        @if($camera->group)
                                                            <small class="text-muted d-block text-truncate" style="font-size: 0.7rem;">
                                                                <i class="ti ti-map-pin me-1"
                                                                    style="font-size: 0.75rem;"></i>{{ $camera->group->name }}
                                                            </small>
                                                        @endif
                                                    </div>

                                                    @php
                                                        $lastDetection = \App\Models\DetectionEvent::whereHas('imageRecord', function ($q) use ($camera) {
                                                            $q->where('camera_id', $camera->id);
                                                        })->latest()->first();

                                                        $status = $camera->operational_status;
                                                        $badgeText = $status;
                                                        $badgeClass = 'bg-label-secondary';

                                                        if ($status === 'ONLINE') {
                                                            if ($lastDetection && now()->diffInSeconds($lastDetection->created_at) < 15) {
                                                                $badgeText = 'DETECTING';
                                                                $badgeClass = 'bg-label-danger';
                                                            } else {
                                                                $badgeText = 'ONLINE';
                                                                $badgeClass = 'bg-label-success';
                                                            }
                                                        } elseif ($status === 'WARNING') {
                                                            $badgeText = 'WARNING';
                                                            $badgeClass = 'bg-label-warning';
                                                        } else {
                                                            $badgeText = 'OFFLINE';
                                                            $badgeClass = 'bg-label-danger';
                                                        }
                                                    @endphp
                                                    <span class="badge {{ $badgeClass }} telemetry-health-badge"
                                                        id="health-badge-{{ $camera->id }}" style="font-size: 0.65rem;">
                                                        {{ $badgeText }}
                                                    </span>
                                                </div>

                                                <div class="card-body p-0 text-center bg-dark d-flex align-items-center justify-content-center"
                                                    style="overflow: hidden; background-color: #111 !important; aspect-ratio: 4 / 3; width: 100%;">
                                                    <img class="img-fluid camera-feed-image"
                                                        style="width: 100%; height: auto; aspect-ratio: 4 / 3; object-fit: contain;"
                                                        data-camera-id="{{ $camera->id }}"
                                                        data-websocket-channel="{{ $camera->websocket_channel_id }}"
                                                        src="{{ $camera->latest_image_path ? asset('https://apiminio.miot-its.org/cctv/' . $camera->latest_image_path) : 'https://placehold.co/640x480/293445/FFFFFF?text=No+Feed' }}"
                                                        alt="Live feed untuk {{ $camera->name }}">
                                                </div>

                                                <div class="card-body p-3 border-top">
                                                    <div class="row g-2" style="font-size: 0.75rem;">
                                                        <div class="col-12 d-flex justify-content-between align-items-center">
                                                            <span class="text-muted"><i class="ti ti-activity me-1"></i>Last
                                                                Heartbeat</span>
                                                            <small class="text-muted fw-semibold text-truncate ms-2"
                                                                id="freshness-{{ $camera->id }}" style="max-width: 150px;">
                                                                {{ $camera->freshness_indicator }}
                                                            </small>
                                                        </div>
                                                        <div class="col-12 d-flex justify-content-between align-items-center mt-1">
                                                            <span class="text-muted"><i class="ti ti-alert-triangle me-1"></i>Last
                                                                Detection</span>
                                                            <small class="text-muted fw-semibold text-truncate ms-2"
                                                                style="max-width: 150px;">
                                                                {{ $lastDetection ? $lastDetection->created_at->diffForHumans() : 'None' }}
                                                            </small>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div
                                                    class="card-footer d-flex justify-content-between align-items-center bg-transparent border-top py-2 px-3">
                                                    <button type="button" class="btn btn-xs btn-outline-secondary"
                                                        data-bs-toggle="modal" data-bs-target="#telemetryModal-{{ $camera->id }}">
                                                        Health Details
                                                    </button>
                                                    <a href="{{ route('log.history.explorer', $camera->id) }}"
                                                        class="btn btn-xs btn-outline-secondary">Riwayat</a>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Telemetry Detail Modal -->
                                        <div class="modal fade" id="telemetryModal-{{ $camera->id }}" tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog modal-dialog-centered">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Health Details: {{ $camera->name }}</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"
                                                            aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body p-0">
                                                        <table class="table table-striped table-sm mb-0">
                                                            <tbody>
                                                                <tr>
                                                                    <td class="ps-3"><strong>RSSI</strong></td>
                                                                    <td class="pe-3 text-end" id="modal-rssi-{{ $camera->id }}">
                                                                        {{ $telemetry ? $telemetry->formatted_rssi : 'N/A' }}</td>
                                                                </tr>
                                                                <tr>
                                                                    <td class="ps-3"><strong>Heap</strong></td>
                                                                    <td class="pe-3 text-end" id="modal-heap-{{ $camera->id }}">
                                                                        {{ $telemetry ? $telemetry->formatted_heap : 'N/A' }}</td>
                                                                </tr>
                                                                <tr>
                                                                    <td class="ps-3"><strong>Publish Latency</strong></td>
                                                                    <td class="pe-3 text-end" id="modal-publish-{{ $camera->id }}">
                                                                        {{ $telemetry ? $telemetry->formatted_publish : 'N/A' }}</td>
                                                                </tr>
                                                                <tr>
                                                                    <td class="ps-3"><strong>MQTT Status</strong></td>
                                                                    <td class="pe-3 text-end" id="modal-mqtt-{{ $camera->id }}">
                                                                        {{ $telemetry ? $telemetry->mqtt_status_text : 'N/A' }}</td>
                                                                </tr>
                                                                <tr>
                                                                    <td class="ps-3"><strong>WS Status</strong></td>
                                                                    <td class="pe-3 text-end" id="modal-ws-{{ $camera->id }}">
                                                                        {{ $telemetry ? $telemetry->ws_status_text : 'N/A' }}</td>
                                                                </tr>
                                                                <tr>
                                                                    <td class="ps-3"><strong>Reconnect Count</strong></td>
                                                                    <td class="pe-3 text-end" id="modal-reconnect-{{ $camera->id }}">
                                                                        {{ $telemetry ? $telemetry->mqtt_reconnect : 0 }}</td>
                                                                </tr>
                                                                <tr>
                                                                    <td class="ps-3"><strong>WS Close Count</strong></td>
                                                                    <td class="pe-3 text-end" id="modal-ws-close-{{ $camera->id }}">
                                                                        {{ $telemetry ? $telemetry->ws_close_count : 0 }}</td>
                                                                </tr>
                                                                <tr>
                                                                    <td class="ps-3"><strong>Publish Fail Count</strong></td>
                                                                    <td class="pe-3 text-end" id="modal-publish-fail-{{ $camera->id }}">
                                                                        {{ $telemetry ? $telemetry->publish_fail : 0 }}</td>
                                                                </tr>
                                                                <tr>
                                                                    <td class="ps-3"><strong>Capture Count</strong></td>
                                                                    <td class="pe-3 text-end" id="modal-capture-{{ $camera->id }}">
                                                                        {{ $telemetry ? $telemetry->capture_ok : 0 }}</td>
                                                                </tr>
                                                                <tr>
                                                                    <td class="ps-3"><strong>Publish Count</strong></td>
                                                                    <td class="pe-3 text-end"
                                                                        id="modal-publish-count-{{ $camera->id }}">
                                                                        {{ $telemetry ? $telemetry->publish_ok : 0 }}</td>
                                                                </tr>
                                                                <tr>
                                                                    <td class="ps-3"><strong>Transport Recovery</strong></td>
                                                                    <td class="pe-3 text-end" id="modal-recovery-{{ $camera->id }}">
                                                                        {{ $telemetry ? $telemetry->transport_recovery : 0 }}</td>
                                                                </tr>
                                                                <tr>
                                                                    <td class="ps-3"><strong>Uptime</strong></td>
                                                                    <td class="pe-3 text-end" id="modal-uptime-{{ $camera->id }}">
                                                                        {{ $telemetry ? $telemetry->formatted_uptime : 'N/A' }}</td>
                                                                </tr>
                                                                <tr>
                                                                    <td class="ps-3"><strong>Firmware</strong></td>
                                                                    <td class="pe-3 text-end" id="modal-firmware-{{ $camera->id }}">
                                                                        {{ $telemetry ? ($telemetry->firmware ?: 'N/A') : 'N/A' }}
                                                                    </td>
                                                                </tr>
                                                                <tr>
                                                                    <td class="ps-3"><strong>OTA Supported</strong></td>
                                                                    <td class="pe-3 text-end"
                                                                        id="modal-ota-supported-{{ $camera->id }}">
                                                                        {{ $telemetry ? ($telemetry->ota_supported ? 'Yes' : 'No') : 'No' }}
                                                                    </td>
                                                                </tr>
                                                                <tr>
                                                                    <td class="ps-3"><strong>OTA Running</strong></td>
                                                                    <td class="pe-3 text-end" id="modal-ota-running-{{ $camera->id }}">
                                                                        {{ $telemetry ? ($telemetry->ota_running ? 'Yes' : 'No') : 'No' }}
                                                                    </td>
                                                                </tr>
                                                                <tr>
                                                                    <td class="ps-3"><strong>Free OTA Space</strong></td>
                                                                    <td class="pe-3 text-end"
                                                                        id="modal-free-ota-space-{{ $camera->id }}">
                                                                        {{ $telemetry ? ($telemetry->free_ota_space ? round($telemetry->free_ota_space / 1024 / 1024, 2) . ' MB' : 'N/A') : 'N/A' }}
                                                                    </td>
                                                                </tr>
                                                                <tr>
                                                                    <td class="ps-3"><strong>Last OTA Result</strong></td>
                                                                    <td class="pe-3 text-end"
                                                                        id="modal-last-ota-result-{{ $camera->id }}">
                                                                        {{ $telemetry ? ($telemetry->last_ota_result ?: 'N/A') : 'N/A' }}
                                                                    </td>
                                                                </tr>
                                                                <tr>
                                                                    <td class="ps-3"><strong>Build</strong></td>
                                                                    <td class="pe-3 text-end" id="modal-build-{{ $camera->id }}">
                                                                        {{ $telemetry ? ($telemetry->build ?: 'N/A') : 'N/A' }}
                                                                    </td>
                                                                </tr>
                                                                <tr>
                                                                    <td class="ps-3"><strong>Board</strong></td>
                                                                    <td class="pe-3 text-end" id="modal-board-{{ $camera->id }}">
                                                                        {{ $telemetry ? ($telemetry->board ?: 'N/A') : 'N/A' }}
                                                                    </td>
                                                                </tr>
                                                                <tr>
                                                                    <td class="ps-3"><strong>Model</strong></td>
                                                                    <td class="pe-3 text-end" id="modal-model-{{ $camera->id }}">
                                                                        {{ $telemetry ? ($telemetry->model ?: 'N/A') : 'N/A' }}
                                                                    </td>
                                                                </tr>
                                                                <tr>
                                                                    <td class="ps-3"><strong>Last OTA</strong></td>
                                                                    <td class="pe-3 text-end" id="modal-last-ota-{{ $camera->id }}">
                                                                        {{ $telemetry ? ($telemetry->last_ota ? $telemetry->last_ota->toDateTimeString() : 'N/A') : 'N/A' }}
                                                                    </td>
                                                                </tr>
                                                                <tr>
                                                                    <td class="ps-3"><strong>Current Deployment</strong></td>
                                                                    <td class="pe-3 text-end"
                                                                        id="modal-current-deployment-{{ $camera->id }}">
                                                                        {{ $telemetry ? ($telemetry->current_deployment_id ?: 'N/A') : 'N/A' }}
                                                                    </td>
                                                                </tr>
                                                                <tr>
                                                                    <td class="ps-3"><strong>WiFi Channel</strong></td>
                                                                    <td class="pe-3 text-end" id="modal-wifi-channel-{{ $camera->id }}">
                                                                        {{ $telemetry ? ($telemetry->wifi_channel ?: 'N/A') : 'N/A' }}
                                                                    </td>
                                                                </tr>
                                                                <tr>
                                                                    <td class="ps-3"><strong>WiFi BSSID</strong></td>
                                                                    <td class="pe-3 text-end" id="modal-wifi-bssid-{{ $camera->id }}">
                                                                        {{ $telemetry ? ($telemetry->wifi_bssid ?: 'N/A') : 'N/A' }}
                                                                    </td>
                                                                </tr>
                                                                <tr>
                                                                    <td colspan="2" class="bg-light ps-3"><strong>Remote
                                                                            Configuration</strong></td>
                                                                </tr>
                                                                <tr>
                                                                    <td class="ps-3"><strong>Current Profile</strong></td>
                                                                    <td class="pe-3 text-end"
                                                                        id="modal-assigned-profile-{{ $camera->id }}">
                                                                        {{ $camera->assignedProfile ? $camera->assignedProfile->name : 'None' }}
                                                                    </td>
                                                                </tr>
                                                                @php
                                                                    $profile = $camera->assignedProfile;
                                                                 @endphp
                                                                <tr class="{{ $profile && $telemetry && $profile->jpeg_quality != $telemetry->jpeg_quality ? 'table-warning text-danger fw-bold' : '' }}"
                                                                    id="row-jpeg-{{ $camera->id }}">
                                                                    <td class="ps-3"><strong>JPEG Quality</strong></td>
                                                                    <td class="pe-3 text-end" id="modal-jpeg-{{ $camera->id }}">
                                                                        {{ $telemetry ? $telemetry->jpeg_quality : 'N/A' }}
                                                                        @if($profile && $telemetry && $profile->jpeg_quality != $telemetry->jpeg_quality)
                                                                            (Expected: {{ $profile->jpeg_quality }})
                                                                        @endif
                                                                    </td>
                                                                </tr>
                                                                <tr class="{{ $profile && $telemetry && $profile->frame_size != $telemetry->frame_size ? 'table-warning text-danger fw-bold' : '' }}"
                                                                    id="row-size-{{ $camera->id }}">
                                                                    <td class="ps-3"><strong>Frame Size</strong></td>
                                                                    <td class="pe-3 text-end" id="modal-size-{{ $camera->id }}">
                                                                        {{ $telemetry ? $telemetry->frame_size : 'N/A' }}
                                                                        @if($profile && $telemetry && $profile->frame_size != $telemetry->frame_size)
                                                                            (Expected: {{ $profile->frame_size }})
                                                                        @endif
                                                                    </td>
                                                                </tr>
                                                                <tr class="{{ $profile && $telemetry && $profile->capture_interval_ms != $telemetry->capture_interval_ms ? 'table-warning text-danger fw-bold' : '' }}"
                                                                    id="row-capture-{{ $camera->id }}">
                                                                    <td class="ps-3"><strong>Capture Interval</strong></td>
                                                                    <td class="pe-3 text-end"
                                                                        id="modal-capture-interval-{{ $camera->id }}">
                                                                        {{ $telemetry && $telemetry->capture_interval_ms !== null ? $telemetry->capture_interval_ms . ' ms' : 'N/A' }}
                                                                        @if($profile && $telemetry && $profile->capture_interval_ms != $telemetry->capture_interval_ms)
                                                                            (Expected: {{ $profile->capture_interval_ms }}ms)
                                                                        @endif
                                                                    </td>
                                                                </tr>
                                                                <tr class="{{ $profile && $telemetry && $profile->telemetry_interval_ms != $telemetry->telemetry_interval_ms ? 'table-warning text-danger fw-bold' : '' }}"
                                                                    id="row-telemetry-{{ $camera->id }}">
                                                                    <td class="ps-3"><strong>Telemetry Interval</strong></td>
                                                                    <td class="pe-3 text-end"
                                                                        id="modal-telemetry-interval-{{ $camera->id }}">
                                                                        {{ $telemetry && $telemetry->telemetry_interval_ms !== null ? $telemetry->telemetry_interval_ms . ' ms' : 'N/A' }}
                                                                        @if($profile && $telemetry && $profile->telemetry_interval_ms != $telemetry->telemetry_interval_ms)
                                                                            (Expected: {{ $profile->telemetry_interval_ms }}ms)
                                                                        @endif
                                                                    </td>
                                                                </tr>
                                                                <tr class="{{ $profile && $telemetry && $profile->mqtt_buffer != $telemetry->mqtt_buffer ? 'table-warning text-danger fw-bold' : '' }}"
                                                                    id="row-buffer-{{ $camera->id }}">
                                                                    <td class="ps-3"><strong>MQTT Buffer Size</strong></td>
                                                                    <td class="pe-3 text-end" id="modal-mqtt-buffer-{{ $camera->id }}">
                                                                        {{ $telemetry && $telemetry->mqtt_buffer !== null ? $telemetry->mqtt_buffer : 'N/A' }}
                                                                        @if($profile && $telemetry && $profile->mqtt_buffer != $telemetry->mqtt_buffer)
                                                                            (Expected: {{ $profile->mqtt_buffer }})
                                                                        @endif
                                                                    </td>
                                                                </tr>
                                                                <tr class="{{ $profile && $telemetry && (bool) $profile->image_enabled != (bool) $telemetry->image_enabled ? 'table-warning text-danger fw-bold' : '' }}"
                                                                    id="row-image-{{ $camera->id }}">
                                                                    <td class="ps-3"><strong>Image Stream</strong></td>
                                                                    <td class="pe-3 text-end"
                                                                        id="modal-image-enabled-{{ $camera->id }}">
                                                                        {{ $telemetry && $telemetry->image_enabled !== null ? ($telemetry->image_enabled ? 'Enabled' : 'Disabled') : 'N/A' }}
                                                                        @if($profile && $telemetry && (bool) $profile->image_enabled != (bool) $telemetry->image_enabled)
                                                                            (Expected:
                                                                            {{ $profile->image_enabled ? 'Enabled' : 'Disabled' }})
                                                                        @endif
                                                                    </td>
                                                                </tr>
                                                                <tr class="{{ $profile && $telemetry && (bool) $profile->telemetry_enabled != (bool) $telemetry->telemetry_enabled ? 'table-warning text-danger fw-bold' : '' }}"
                                                                    id="row-telem-{{ $camera->id }}">
                                                                    <td class="ps-3"><strong>Telemetry Stream</strong></td>
                                                                    <td class="pe-3 text-end"
                                                                        id="modal-telemetry-enabled-{{ $camera->id }}">
                                                                        {{ $telemetry && $telemetry->telemetry_enabled !== null ? ($telemetry->telemetry_enabled ? 'Enabled' : 'Disabled') : 'N/A' }}
                                                                        @if($profile && $telemetry && (bool) $profile->telemetry_enabled != (bool) $telemetry->telemetry_enabled)
                                                                            (Expected:
                                                                            {{ $profile->telemetry_enabled ? 'Enabled' : 'Disabled' }})
                                                                        @endif
                                                                    </td>
                                                                </tr>
                                                                <tr class="{{ $profile && $telemetry && (bool) $profile->ota_enabled != (bool) $telemetry->ota_enabled ? 'table-warning text-danger fw-bold' : '' }}"
                                                                    id="row-ota-{{ $camera->id }}">
                                                                    <td class="ps-3"><strong>OTA Stream</strong></td>
                                                                    <td class="pe-3 text-end" id="modal-ota-enabled-{{ $camera->id }}">
                                                                        {{ $telemetry && $telemetry->ota_enabled !== null ? ($telemetry->ota_enabled ? 'Enabled' : 'Disabled') : 'N/A' }}
                                                                        @if($profile && $telemetry && (bool) $profile->ota_enabled != (bool) $telemetry->ota_enabled)
                                                                            (Expected: {{ $profile->ota_enabled ? 'Enabled' : 'Disabled' }})
                                                                        @endif
                                                                    </td>
                                                                </tr>
                                                                <tr>
                                                                    <td class="ps-3"><strong>Last Configuration</strong></td>
                                                                    <td class="pe-3 text-end" id="modal-last-config-{{ $camera->id }}">
                                                                        {{ $camera->last_config_time ? $camera->last_config_time->toDateTimeString() : 'Never' }}
                                                                    </td>
                                                                </tr>
                                                                <tr>
                                                                    <td class="ps-3"><strong>Last Sync</strong></td>
                                                                    <td class="pe-3 text-end" id="modal-last-sync-{{ $camera->id }}">
                                                                        {{ $camera->last_sync ? $camera->last_sync->toDateTimeString() : 'Never' }}
                                                                    </td>
                                                                </tr>
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-sm btn-secondary"
                                                            data-bs-dismiss="modal">Close</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Camera Preview & Telemetry Modal -->
                                        <div class="modal fade" id="cameraPreviewModal-{{ $camera->id }}" tabindex="-1"
                                            aria-hidden="true">
                                            <div class="modal-dialog modal-dialog-centered modal-lg">
                                                <div class="modal-content">
                                                    <div class="modal-header py-2 px-3">
                                                        <h5 class="modal-title fw-bold" id="preview-title-{{ $camera->id }}">
                                                            {{ $camera->name }}</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"
                                                            aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body p-0">
                                                        <div class="row g-0">
                                                            <!-- Left side: Image -->
                                                            <div class="col-12 col-md-7 bg-dark d-flex align-items-center justify-content-center"
                                                                style="overflow: hidden; background-color: #111 !important; aspect-ratio: 4 / 3;">
                                                                <img id="preview-image-{{ $camera->id }}" class="modal-preview-image"
                                                                    style="width: 100%; height: auto; aspect-ratio: 4 / 3; object-fit: contain;"
                                                                    src="{{ $camera->latest_image_path ? asset('https://apiminio.miot-its.org/cctv/' . $camera->latest_image_path) : 'https://placehold.co/640x480/293445/FFFFFF?text=No+Feed' }}">
                                                            </div>
                                                            <!-- Right side: Telemetry details -->
                                                            <div class="col-12 col-md-5 p-3 d-flex flex-column justify-content-between">
                                                                <div>
                                                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                                                        @php
                                                                            $status = $camera->operational_status;
                                                                            $badgeClass = 'bg-label-danger';
                                                                            if ($status === 'ONLINE')
                                                                                $badgeClass = 'bg-label-success';
                                                                            elseif ($status === 'WARNING')
                                                                                $badgeClass = 'bg-label-warning';
                                                                        @endphp
                                                                        <span class="badge {{ $badgeClass }} telemetry-health-badge"
                                                                            id="modal-preview-health-{{ $camera->id }}">
                                                                            {{ $status }}
                                                                        </span>
                                                                        <small class="text-muted fw-semibold"
                                                                            id="modal-preview-freshness-{{ $camera->id }}">
                                                                            {{ $camera->freshness_indicator }}
                                                                        </small>
                                                                    </div>
                                                                    <div class="row g-2 text-start">
                                                                        <div class="col-6">
                                                                            <div class="small text-truncate"><span
                                                                                    class="text-muted">RSSI:</span> <strong
                                                                                    id="modal-preview-rssi-{{ $camera->id }}">{{ $telemetry ? $telemetry->formatted_rssi : 'N/A' }}</strong>
                                                                            </div>
                                                                            <div class="small text-truncate"><span
                                                                                    class="text-muted">Heap:</span> <strong
                                                                                    id="modal-preview-heap-{{ $camera->id }}">{{ $telemetry ? $telemetry->formatted_heap : 'N/A' }}</strong>
                                                                            </div>
                                                                            <div class="small text-truncate"><span
                                                                                    class="text-muted">Publish:</span> <strong
                                                                                    id="modal-preview-publish-{{ $camera->id }}">{{ $telemetry ? $telemetry->formatted_publish : 'N/A' }}</strong>
                                                                            </div>
                                                                            <div class="small text-truncate"><span
                                                                                    class="text-muted">MQTT:</span> <strong
                                                                                    id="modal-preview-mqtt-{{ $camera->id }}">{{ $telemetry ? $telemetry->mqtt_status_text : 'N/A' }}</strong>
                                                                            </div>
                                                                            <div class="small text-truncate"><span
                                                                                    class="text-muted">WS:</span> <strong
                                                                                    id="modal-preview-ws-{{ $camera->id }}">{{ $telemetry ? $telemetry->ws_status_text : 'N/A' }}</strong>
                                                                            </div>
                                                                        </div>
                                                                        <div class="col-6">
                                                                            <div class="small text-truncate"><span
                                                                                    class="text-muted">Reconnect:</span> <strong
                                                                                    id="modal-preview-reconnect-{{ $camera->id }}">{{ $telemetry ? $telemetry->reconnect_delta_text : '+0' }}</strong>
                                                                            </div>
                                                                            <div class="small text-truncate"><span class="text-muted">WS
                                                                                    Close:</span> <strong
                                                                                    id="modal-preview-ws-close-{{ $camera->id }}">{{ $telemetry ? $telemetry->ws_close_delta_text : '+0' }}</strong>
                                                                            </div>
                                                                            <div class="small text-truncate"><span
                                                                                    class="text-muted">Pub Fail:</span> <strong
                                                                                    id="modal-preview-pub-fail-{{ $camera->id }}">{{ $telemetry ? $telemetry->publish_fail_delta_text : '+0' }}</strong>
                                                                            </div>
                                                                            <div class="small text-truncate"><span
                                                                                    class="text-muted">Uptime:</span> <strong
                                                                                    id="modal-preview-uptime-{{ $camera->id }}">{{ $telemetry ? $telemetry->formatted_uptime : 'N/A' }}</strong>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="mt-4 d-flex justify-content-end gap-2">
                                                                    <a href="{{ route('log.history.explorer', $camera->id) }}"
                                                                        class="btn btn-sm btn-primary">Riwayat Lengkap</a>
                                                                    <button type="button" class="btn btn-sm btn-secondary"
                                                                        data-bs-dismiss="modal">Tutup</button>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @else
            <div class="card border-0 shadow-sm py-5 text-center mb-5">
                <div class="card-body">
                    <div class="avatar avatar-lg bg-label-secondary mx-auto mb-3"
                        style="width: 56px; height: 56px; display: flex; align-items: center; justify-content: center; border-radius: 50%;">
                        <i class="ti ti-camera-off fs-3"></i>
                    </div>
                    <h5 class="fw-semibold">No cameras available</h5>
                    <p class="text-muted mx-auto" style="max-width: 320px;">Choose another group or add new cameras to start
                        monitoring.</p>
                </div>
            </div>
        @endif
    </div>

    {{-- Section 2: Recent Detection --}}
    <div class="mb-5">
        <h5 class="mb-3 fw-bold"><i class="ti ti-user-search me-2 text-danger"></i>Recent Detection</h5>

        <div class="card shadow-sm border-0">
            <div class="card-header d-flex justify-content-between align-items-center bg-transparent border-0 pb-0">
                <h6 class="mb-0 fw-bold">Latest Person Detection</h6>
                <span class="badge bg-label-danger" id="detection-realtime-badge">
                    <span class="spinner-grow spinner-grow-sm text-danger me-1" role="status"
                        style="width: 8px; height: 8px;"></span>Realtime active
                </span>
            </div>
            <div class="card-body mt-2">
                <div id="no-person-detection-placeholder" style="{{ $latestDetection ? 'display: none;' : '' }}">
                    <div class="py-5 text-center">
                        <div class="avatar avatar-lg bg-label-secondary mx-auto mb-3"
                            style="width: 56px; height: 56px; display: flex; align-items: center; justify-content: center; border-radius: 50%;">
                            <i class="ti ti-user-off fs-3"></i>
                        </div>
                        <h5 class="fw-semibold">No detections found</h5>
                        <p class="text-muted mx-auto" style="max-width: 320px;">No person detection events have been
                            recorded yet.</p>
                    </div>
                </div>

                <div id="latest-person-detection-card" class="row align-items-center"
                    style="{{ $latestDetection ? '' : 'display: none;' }}">
                    <div class="col-12 col-md-4 mb-3 mb-md-0 text-center bg-dark rounded d-flex align-items-center justify-content-center"
                        style="overflow: hidden; max-height: 240px; aspect-ratio: 4 / 3;">
                        <img id="detection-snapshot" class="img-fluid"
                            src="{{ $latestDetection ? \Illuminate\Support\Facades\Storage::disk('s3')->url($latestDetection->imageRecord->path) : '' }}"
                            style="max-height: 240px; object-fit: contain;">
                    </div>
                    <div class="col-12 col-md-8 ps-md-4">
                        <div class="row g-3">
                            <div class="col-6 col-sm-4">
                                <span class="text-muted d-block" style="font-size: 0.8rem;">Camera</span>
                                <strong id="detection-camera-name"
                                    style="font-size: 1.1rem;">{{ $latestDetection->imageRecord->camera->name ?? 'N/A' }}</strong>
                            </div>
                            <div class="col-6 col-sm-4">
                                <span class="text-muted d-block" style="font-size: 0.8rem;">Confidence</span>
                                <span class="badge bg-label-danger" id="detection-confidence" style="font-size: 1rem;">
                                    {{ $latestDetection ? number_format($latestDetection->confidence * 100, 2) . '%' : '0.00%' }}
                                </span>
                            </div>
                            <div class="col-12 col-sm-4">
                                <span class="text-muted d-block" style="font-size: 0.8rem;">Timestamp</span>
                                <strong
                                    id="detection-time">{{ $latestDetection ? $latestDetection->created_at->format('Y-m-d H:i:s') : 'N/A' }}</strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Section 3: Overview & Device Status --}}
    <div class="mb-5">
        <h5 class="mb-3 fw-bold"><i class="ti ti-chart-bar me-2 text-secondary"></i>Overview & Device Status</h5>
        <div class="row g-4">
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card h-100 shadow-sm border-0">
                    <div class="card-body d-flex flex-column justify-content-between p-3">
                        <div class="d-flex align-items-start justify-content-between w-100">
                            <div class="content-left">
                                <span class="text-muted">Total Cameras</span>
                                <h3 class="mb-0 mt-1 fw-bold" id="summary-total">{{ $totalCameras ?? 0 }}</h3>
                                <small class="text-muted fw-semibold">Active Fleet</small>
                            </div>
                            <span class="badge bg-label-secondary border rounded p-2">
                                <i class="ti ti-camera ti-sm text-secondary"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card h-100 shadow-sm border-0">
                    <div class="card-body d-flex flex-column justify-content-between p-3">
                        <div class="d-flex align-items-start justify-content-between w-100">
                            <div class="content-left">
                                <span class="text-muted">Online Cameras</span>
                                <h3 class="mb-0 mt-1 fw-bold">
                                    <span id="summary-online">{{ $onlineCameras ?? 0 }}</span> / <span
                                        class="summary-total-denominator text-muted">{{ $totalCameras ?? 0 }}</span>
                                </h3>
                                <small class="text-muted fw-semibold"
                                    id="summary-online-percent">{{ $onlinePercent }}%</small>
                            </div>
                            <span class="badge bg-label-secondary border rounded p-2">
                                <i class="ti ti-circle-check ti-sm text-secondary"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card h-100 shadow-sm border-0">
                    <div class="card-body d-flex flex-column justify-content-between p-3">
                        <div class="d-flex align-items-start justify-content-between w-100">
                            <div class="content-left">
                                <span class="text-muted">Warning Cameras</span>
                                <h3 class="mb-0 mt-1 fw-bold">
                                    <span id="summary-warning">{{ $warningCameras ?? 0 }}</span> / <span
                                        class="summary-total-denominator text-muted">{{ $totalCameras ?? 0 }}</span>
                                </h3>
                                <small class="text-muted fw-semibold"
                                    id="summary-warning-percent">{{ $warningPercent }}%</small>
                            </div>
                            <span class="badge bg-label-secondary border rounded p-2">
                                <i class="ti ti-alert-circle ti-sm text-secondary"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card h-100 shadow-sm border-0">
                    <div class="card-body d-flex flex-column justify-content-between p-3">
                        <div class="d-flex align-items-start justify-content-between w-100">
                            <div class="content-left">
                                <span class="text-muted">Offline Cameras</span>
                                <h3 class="mb-0 mt-1 fw-bold">
                                    <span id="summary-offline">{{ $offlineCameras ?? 0 }}</span> / <span
                                        class="summary-total-denominator text-muted">{{ $totalCameras ?? 0 }}</span>
                                </h3>
                                <small class="text-muted fw-semibold"
                                    id="summary-offline-percent">{{ $offlinePercent }}%</small>
                            </div>
                            <span class="badge bg-label-secondary border rounded p-2">
                                <i class="ti ti-circle-x ti-sm text-secondary"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection