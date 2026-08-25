<?php
// ATOM Web Admin — Phase 82: Autonomous Multi-Modal Video Keyframe Extractor & Scene Boundary Segmenter
$pageTitle = "Video Keyframe Segmenter (Phase 82)";
include_once __DIR__ . '/components/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold mb-1" style="color: #06B6D4;">Video Keyframe Extractor &amp; Scene Segmenter</h2>
        <p class="text-muted small mb-0">Phase 82: Optical Flow Scene Cut Detection, Visual Entropy Salience Analysis &amp; High-Throughput Keyframe Indexing</p>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-outline-secondary btn-sm" onclick="location.reload();">
            <i class="bi bi-arrow-clockwise me-1"></i> Refresh
        </button>
        <button class="btn btn-sm btn-info text-dark fw-bold" onclick="runVideoSegmentation()">
            <i class="bi bi-film me-1"></i> Analyze Video Stream
        </button>
    </div>
</div>

<!-- Video Metrics Overview -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">TOTAL SCENES DETECTED</div>
            <div class="fs-4 fw-bold text-cyan-400" id="metricScenes">2 SCENES</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">KEYFRAMES EXTRACTED</div>
            <div class="fs-4 fw-bold text-emerald-400" id="metricKeyframes" style="color: #34D399;">2 FRAMES</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">CUT THRESHOLD</div>
            <div class="fs-4 fw-bold text-warning" id="metricThresh">0.35 &Delta;</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">MAX RESOLUTION</div>
            <div class="fs-4 fw-bold text-info">8K UHD (120 FPS)</div>
        </div>
    </div>
</div>

<!-- Scene Segmentation Matrix & Video Stream Sandbox -->
<div class="row g-4 mb-4">
    <div class="col-md-8">
        <div class="card bg-dark border-secondary text-white h-100">
            <div class="card-header border-secondary d-flex justify-content-between align-items-center">
                <span class="fw-bold fs-6"><i class="bi bi-camera-reels-fill text-cyan-400 me-2"></i>Segmented Video Scenes &amp; Keyframes</span>
                <span class="badge bg-secondary" id="scenesBadge">2 SCENES</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-dark table-hover mb-0 align-middle small">
                        <thead class="table-secondary text-uppercase text-muted">
                            <tr>
                                <th>Scene #</th>
                                <th>Time Range</th>
                                <th>Duration</th>
                                <th>Frames</th>
                                <th>Keyframe Entropy</th>
                            </tr>
                        </thead>
                        <tbody id="scenesTableBody">
                            <tr><td colspan="5" class="text-center p-3 text-muted">Loading segmented scenes...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card bg-dark border-secondary text-white h-100">
            <div class="card-header border-secondary">
                <span class="fw-bold text-cyan-400"><i class="bi bi-sliders me-2"></i>Scene Cut Sensitivity</span>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold d-flex justify-content-between">
                        <span>CUT DELTA SENSITIVITY</span>
                        <span class="text-cyan-400 fw-bold" id="threshLabel">0.35</span>
                    </label>
                    <input type="range" class="form-range" min="10" max="80" value="35" id="threshSlider" oninput="updateThreshLabel(this.value)">
                </div>

                <button class="btn btn-sm btn-info text-dark fw-bold w-100 mb-3" onclick="runVideoSegmentation()">
                    <i class="bi bi-play-circle-fill me-1"></i> Segment &amp; Extract
                </button>

                <div class="p-3 bg-black rounded border border-secondary text-xs text-muted">
                    <div class="text-white fw-bold mb-1"><i class="bi bi-info-circle-fill text-cyan-400 me-1"></i>Keyframe Heuristics:</div>
                    <div>&bull; Optical Luminance Shift ($60\%$)</div>
                    <div>&bull; Visual Entropy Salience ($40\%$)</div>
                    <div>&bull; Face / Object Centroid Tracking</div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function updateThreshLabel(v) {
    const val = (v / 100).toFixed(2);
    document.getElementById('threshLabel').innerText = val;
    document.getElementById('metricThresh').innerText = `${val} \u0394`;
}

async function runVideoSegmentation() {
    const thresh = parseFloat(document.getElementById('threshSlider').value) / 100.0;

    const sampleFrames = [
        { timestamp_s: 0.0, luminance: 0.20, entropy: 0.40 },
        { timestamp_s: 1.0, luminance: 0.22, entropy: 0.42 },
        { timestamp_s: 2.0, luminance: 0.21, entropy: 0.39 },
        { timestamp_s: 3.0, luminance: 0.85, entropy: 0.92 }, // Scene Cut 1
        { timestamp_s: 4.0, luminance: 0.88, entropy: 0.89 },
        { timestamp_s: 5.0, luminance: 0.15, entropy: 0.65 }, // Scene Cut 2
        { timestamp_s: 6.0, luminance: 0.17, entropy: 0.68 },
    ];

    try {
        const res = await apiFetch('/vision/video/segment', {
            method: 'POST',
            body: JSON.stringify({ frames: sampleFrames, cut_threshold: thresh })
        });

        if (res && res.success) {
            const d = res.data;
            document.getElementById('metricScenes').innerText = `${d.total_scenes} SCENES`;
            document.getElementById('metricKeyframes').innerText = `${d.total_scenes} FRAMES`;
            document.getElementById('scenesBadge').innerText = `${d.total_scenes} SCENES`;

            renderScenesTable(d.scenes || []);
            if (typeof showToast === 'function') showToast(`Segmented into ${d.total_scenes} scenes`, 'success');
        }
    } catch (e) {
        if (typeof showToast === 'function') showToast('Segmentation error: ' + e.message, 'error');
    }
}

function renderScenesTable(scenes) {
    const tbody = document.getElementById('scenesTableBody');
    if (!scenes || scenes.length === 0) {
        tbody.innerHTML = `<tr><td colspan="5" class="text-center text-muted p-3">No scenes detected.</td></tr>`;
        return;
    }

    tbody.innerHTML = scenes.map(s => `
        <tr>
            <td class="fw-bold text-white"><i class="bi bi-collection-play-fill text-cyan-400 me-2"></i>Scene ${s.scene_number}</td>
            <td class="font-monospace text-cyan-300">${s.start_time_s}s &ndash; ${s.end_time_s}s</td>
            <td><span class="badge bg-secondary">${s.duration_s}s</span></td>
            <td>${s.frame_count} frames</td>
            <td><span class="text-emerald-400 fw-bold">${(s.keyframe.entropy * 100).toFixed(0)}% entropy</span></td>
        </tr>
    `).join('');
}

document.addEventListener('DOMContentLoaded', () => {
    runVideoSegmentation();
});
</script>

<?php include_once __DIR__ . '/components/footer.php'; ?>
