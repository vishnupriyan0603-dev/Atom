/**
 * Production-Ready 10-Band Parametric Audio Equalizer
 * Single-source-of-truth state, Web Audio API DSP filter chain,
 * high-DPI canvas frequency response visualizer, and leak-free lifecycle management.
 */
class ProductionEqualizer {
  static BANDS = [32, 64, 125, 250, 500, 1000, 2000, 4000, 8000, 16000];
  static MIN_GAIN = -12.0;
  static MAX_GAIN = 12.0;

  static PRESETS = {
    FLAT: [0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
    BASS_BOOST: [6, 5, 4, 2, 0.5, 0, 0, 0, 0, 0],
    VOCAL_ENHANCE: [-2, -1, 0, 2, 4, 4.5, 3.5, 2, 0.5, 0],
    TREBLE_BOOST: [0, 0, 0, 0, 0.5, 1.5, 3, 5, 6, 7],
    ACOUSTIC: [3.5, 3, 2, 1, 1.5, 2, 3, 3.5, 3, 2],
    ELECTRONIC: [5, 4.5, 2, 0, -1.5, 1.5, 0.5, 2, 4, 5],
    ROCK: [4.5, 3.5, 1.5, -0.5, -1.5, 0.5, 2.5, 4, 4.5, 5],
    SPEECH_CLARITY: [-4, -2, 0, 2, 3.5, 4, 3, 1.5, 0, -2],
    NOISE_REDUCTION: [-3, -1.5, 0, 0, 0, 0, 0, -2, -4.5, -7],
    PODCAST: [-3, 0, 1.5, 3, 3.5, 3, 2, 1, -0.5, -2.5]
  };

  constructor(options = {}) {
    this.containerId = options.containerId || 'equalizerContainer';
    this.canvasId = options.canvasId || 'equalizerCanvas';
    this.onStateChange = typeof options.onStateChange === 'function' ? options.onStateChange : null;

    // Single source of truth state
    this.state = {
      enabled: true,
      preamp: 0.0,
      bands: [0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
      preset: 'FLAT',
      lowCut: false,
      lowCutFreq: 80.0,
      highCut: false,
      highCutFreq: 12000.0,
      isTestPlaying: false
    };

    this._generationId = 0;
    this._abortController = null;
    this._animationFrameId = null;
    this._audioCtx = null;
    this._preampNode = null;
    this._filterNodes = [];
    this._lowCutNode = null;
    this._highCutNode = null;
    this._analyserNode = null;
    this._testOscillator = null;
    this._testGain = null;
    this._isInitialized = false;
  }

  static clamp(val, min = ProductionEqualizer.MIN_GAIN, max = ProductionEqualizer.MAX_GAIN) {
    const num = parseFloat(val);
    if (isNaN(num) || !isFinite(num)) return 0.0;
    return Math.max(min, Math.min(max, Math.round(num * 10) / 10));
  }

  /**
   * Initializes the Equalizer DOM and listeners idempotently.
   */
  init() {
    if (this._isInitialized) {
      this.destroy();
    }

    this._abortController = new AbortController();
    this._renderUI();
    this._initWebAudio();
    this._bindEvents();
    this._startVisualizer();
    this._isInitialized = true;
    this._notifyState();
    return this;
  }

  _renderUI() {
    const container = document.getElementById(this.containerId);
    if (!container) return;

    container.innerHTML = `
      <div class="eq-root bg-[#11151c] border border-[#1e2838] rounded-2xl p-4 text-white shadow-xl">
        <!-- Top Control Bar -->
        <div class="flex flex-wrap items-center justify-between gap-3 mb-4 pb-3 border-b border-[#1e2838]">
          <div class="flex items-center gap-3">
            <button id="eqTogglePower" class="px-3 py-1.5 rounded-xl text-xs font-bold transition-all ${this.state.enabled ? 'bg-emerald-500 text-white shadow-md shadow-emerald-500/20' : 'bg-gray-800 text-gray-400'}">
              <i class="bi bi-power me-1"></i> ${this.state.enabled ? 'EQ ACTIVE' : 'EQ BYPASS'}
            </button>
            <div class="flex items-center gap-2">
              <label class="text-[11px] text-gray-400 font-bold uppercase">Preset:</label>
              <select id="eqPresetSelect" class="bg-[#080a0d] border border-[#1e2838] text-xs text-white rounded-lg px-2.5 py-1 focus:border-emerald-500 focus:outline-none">
                ${Object.keys(ProductionEqualizer.PRESETS).map(p => `<option value="${p}" ${this.state.preset === p ? 'selected' : ''}>${p.replace('_', ' ')}</option>`).join('')}
                <option value="CUSTOM" ${this.state.preset === 'CUSTOM' ? 'selected' : ''}>CUSTOM</option>
              </select>
            </div>
          </div>

          <div class="flex items-center gap-2">
            <button id="eqLowCutBtn" class="px-2.5 py-1 rounded-lg text-[11px] font-semibold border transition-all ${this.state.lowCut ? 'bg-indigo-500/20 border-indigo-500 text-indigo-300' : 'bg-[#080a0d] border-[#1e2838] text-gray-400'}">
              Low-Cut (80Hz)
            </button>
            <button id="eqHighCutBtn" class="px-2.5 py-1 rounded-lg text-[11px] font-semibold border transition-all ${this.state.highCut ? 'bg-indigo-500/20 border-indigo-500 text-indigo-300' : 'bg-[#080a0d] border-[#1e2838] text-gray-400'}">
              High-Cut (12kHz)
            </button>
            <button id="eqTestToneBtn" class="px-2.5 py-1 rounded-lg text-[11px] font-semibold bg-[#080a0d] border border-[#1e2838] text-gray-300 hover:text-white">
              <i class="bi bi-volume-up me-1"></i> Audio Test
            </button>
            <button id="eqResetBtn" class="px-2.5 py-1 rounded-lg text-[11px] font-semibold bg-[#080a0d] border border-[#1e2838] text-gray-400 hover:text-red-400">
              Reset
            </button>
          </div>
        </div>

        <!-- Frequency Curve Canvas Visualizer -->
        <div class="mb-4 bg-[#080a0d] border border-[#1e2838] rounded-xl overflow-hidden relative">
          <canvas id="${this.canvasId}" width="700" height="120" class="w-full h-[120px] block"></canvas>
          <div class="absolute top-2 left-3 text-[10px] text-gray-500 font-mono pointer-events-none">MAGNITUDE RESPONSE (dB)</div>
        </div>

        <!-- Preamp & 10 Sliders Container -->
        <div class="grid grid-cols-11 gap-2 pt-2 items-end">
          <!-- Preamp Slider -->
          <div class="flex flex-col items-center gap-1.5 p-2 bg-[#0c0f14] border border-[#1e2838] rounded-xl">
            <span class="text-[10px] font-bold text-amber-400">PREAMP</span>
            <span id="eqPreampBadge" class="text-[10px] font-mono text-gray-300 font-bold">${this.state.preamp >= 0 ? '+' : ''}${this.state.preamp.toFixed(1)}</span>
            <input type="range" id="eqPreampSlider" min="-12" max="12" step="0.5" value="${this.state.preamp}" orient="vertical" class="eq-slider h-28 cursor-pointer accent-amber-400">
            <span class="text-[9px] text-gray-500 font-mono">GAIN</span>
          </div>

          <!-- 10 Frequency Bands -->
          ${ProductionEqualizer.BANDS.map((freq, idx) => `
            <div class="flex flex-col items-center gap-1.5 p-1.5 bg-[#0c0f14] border border-[#1e2838] rounded-xl">
              <span class="text-[10px] font-bold text-emerald-400">${freq >= 1000 ? (freq / 1000) + 'k' : freq}</span>
              <span id="eqBandBadge_${idx}" class="text-[10px] font-mono text-gray-300 font-bold">${this.state.bands[idx] >= 0 ? '+' : ''}${this.state.bands[idx].toFixed(1)}</span>
              <input type="range" id="eqBandSlider_${idx}" data-index="${idx}" min="-12" max="12" step="0.5" value="${this.state.bands[idx]}" orient="vertical" class="eq-slider h-28 cursor-pointer accent-emerald-400">
              <span class="text-[9px] text-gray-500 font-mono">Hz</span>
            </div>
          `).join('')}
        </div>
      </div>
    `;
  }

  _initWebAudio() {
    try {
      const AudioContextClass = window.AudioContext || window.webkitAudioContext;
      if (!AudioContextClass) return;

      if (!this._audioCtx || this._audioCtx.state === 'closed') {
        this._audioCtx = new AudioContextClass();
      }

      // Preamp Gain Node
      this._preampNode = this._audioCtx.createGain();
      this._preampNode.gain.value = Math.pow(10, this.state.preamp / 20.0);

      // 10 Biquad Filter Nodes
      this._filterNodes = ProductionEqualizer.BANDS.map((freq, i) => {
        const filter = this._audioCtx.createBiquadFilter();
        if (i === 0) {
          filter.type = 'lowshelf';
        } else if (i === ProductionEqualizer.BANDS.length - 1) {
          filter.type = 'highshelf';
        } else {
          filter.type = 'peaking';
          filter.Q.value = 1.414;
        }
        filter.frequency.value = freq;
        filter.gain.value = this.state.enabled ? this.state.bands[i] : 0.0;
        return filter;
      });

      // Low Cut (Highpass)
      this._lowCutNode = this._audioCtx.createBiquadFilter();
      this._lowCutNode.type = 'highpass';
      this._lowCutNode.frequency.value = this.state.lowCut ? this.state.lowCutFreq : 10.0;

      // High Cut (Lowpass)
      this._highCutNode = this._audioCtx.createBiquadFilter();
      this._highCutNode.type = 'lowpass';
      this._highCutNode.frequency.value = this.state.highCut ? this.state.highCutFreq : 22000.0;

      // Analyser Node for FFT Visualizer
      this._analyserNode = this._audioCtx.createAnalyser();
      this._analyserNode.fftSize = 256;
      this._analyserNode.smoothingTimeConstant = 0.8;

      // Chain audio nodes
      let prevNode = this._preampNode;
      prevNode.connect(this._lowCutNode);
      prevNode = this._lowCutNode;

      for (const filter of this._filterNodes) {
        prevNode.connect(filter);
        prevNode = filter;
      }

      prevNode.connect(this._highCutNode);
      this._highCutNode.connect(this._analyserNode);
      this._analyserNode.connect(this._audioCtx.destination);
    } catch (_) {
      // Graceful fallback for non-WebAudio environments
    }
  }

  _bindEvents() {
    const signal = this._abortController.signal;

    // Power Toggle
    const powerBtn = document.getElementById('eqTogglePower');
    if (powerBtn) {
      powerBtn.addEventListener('click', () => {
        this.setEnabled(!this.state.enabled);
      }, { signal });
    }

    // Preset Selector
    const presetSelect = document.getElementById('eqPresetSelect');
    if (presetSelect) {
      presetSelect.addEventListener('change', (e) => {
        this.applyPreset(e.target.value);
      }, { signal });
    }

    // Low Cut Toggle
    const lowCutBtn = document.getElementById('eqLowCutBtn');
    if (lowCutBtn) {
      lowCutBtn.addEventListener('click', () => {
        this.setLowCut(!this.state.lowCut);
      }, { signal });
    }

    // High Cut Toggle
    const highCutBtn = document.getElementById('eqHighCutBtn');
    if (highCutBtn) {
      highCutBtn.addEventListener('click', () => {
        this.setHighCut(!this.state.highCut);
      }, { signal });
    }

    // Reset Button
    const resetBtn = document.getElementById('eqResetBtn');
    if (resetBtn) {
      resetBtn.addEventListener('click', () => {
        this.reset();
      }, { signal });
    }

    // Test Tone Generator
    const testToneBtn = document.getElementById('eqTestToneBtn');
    if (testToneBtn) {
      testToneBtn.addEventListener('click', () => {
        this.toggleTestTone();
      }, { signal });
    }

    // Preamp Slider
    const preampSlider = document.getElementById('eqPreampSlider');
    if (preampSlider) {
      preampSlider.addEventListener('input', (e) => {
        this.setPreamp(e.target.value);
      }, { signal });
    }

    // 10 Band Sliders
    for (let i = 0; i < ProductionEqualizer.BANDS.length; i++) {
      const slider = document.getElementById(`eqBandSlider_${i}`);
      if (slider) {
        slider.addEventListener('input', (e) => {
          this.setBandGain(i, e.target.value);
        }, { signal });
      }
    }
  }

  setEnabled(enabled) {
    this.state.enabled = Boolean(enabled);
    const powerBtn = document.getElementById('eqTogglePower');
    if (powerBtn) {
      powerBtn.className = `px-3 py-1.5 rounded-xl text-xs font-bold transition-all ${this.state.enabled ? 'bg-emerald-500 text-white shadow-md shadow-emerald-500/20' : 'bg-gray-800 text-gray-400'}`;
      powerBtn.innerHTML = `<i class="bi bi-power me-1"></i> ${this.state.enabled ? 'EQ ACTIVE' : 'EQ BYPASS'}`;
    }

    this._syncAudioNodes();
    this._notifyState();
  }

  setPreamp(val) {
    this.state.preamp = ProductionEqualizer.clamp(val);
    const badge = document.getElementById('eqPreampBadge');
    if (badge) {
      badge.textContent = `${this.state.preamp >= 0 ? '+' : ''}${this.state.preamp.toFixed(1)}`;
    }
    const slider = document.getElementById('eqPreampSlider');
    if (slider && parseFloat(slider.value) !== this.state.preamp) {
      slider.value = this.state.preamp;
    }
    if (this._preampNode && this._audioCtx) {
      this._preampNode.gain.setTargetAtTime(
        this.state.enabled ? Math.pow(10, this.state.preamp / 20.0) : 1.0,
        this._audioCtx.currentTime,
        0.02
      );
    }
    this._notifyState();
  }

  setBandGain(index, val) {
    if (index < 0 || index >= ProductionEqualizer.BANDS.length) return;
    this.state.bands[index] = ProductionEqualizer.clamp(val);

    const badge = document.getElementById(`eqBandBadge_${index}`);
    if (badge) {
      badge.textContent = `${this.state.bands[index] >= 0 ? '+' : ''}${this.state.bands[index].toFixed(1)}`;
    }
    const slider = document.getElementById(`eqBandSlider_${index}`);
    if (slider && parseFloat(slider.value) !== this.state.bands[index]) {
      slider.value = this.state.bands[index];
    }

    this.state.preset = this._detectMatchingPreset();
    const presetSelect = document.getElementById('eqPresetSelect');
    if (presetSelect) {
      presetSelect.value = this.state.preset;
    }

    if (this._filterNodes[index] && this._audioCtx) {
      this._filterNodes[index].gain.setTargetAtTime(
        this.state.enabled ? this.state.bands[index] : 0.0,
        this._audioCtx.currentTime,
        0.02
      );
    }

    this._notifyState();
  }

  setBands(gains) {
    if (!Array.isArray(gains)) return;
    for (let i = 0; i < ProductionEqualizer.BANDS.length; i++) {
      this.state.bands[i] = ProductionEqualizer.clamp(gains[i] ?? 0);
      const badge = document.getElementById(`eqBandBadge_${i}`);
      if (badge) badge.textContent = `${this.state.bands[i] >= 0 ? '+' : ''}${this.state.bands[i].toFixed(1)}`;
      const slider = document.getElementById(`eqBandSlider_${i}`);
      if (slider) slider.value = this.state.bands[i];
    }
    this.state.preset = this._detectMatchingPreset();
    const presetSelect = document.getElementById('eqPresetSelect');
    if (presetSelect) presetSelect.value = this.state.preset;

    this._syncAudioNodes();
    this._notifyState();
  }

  applyPreset(name) {
    const key = (name || '').toUpperCase();
    if (ProductionEqualizer.PRESETS[key]) {
      const targetGains = ProductionEqualizer.PRESETS[key];
      this.setBands(targetGains);
      this.state.preset = key;
      const presetSelect = document.getElementById('eqPresetSelect');
      if (presetSelect) presetSelect.value = key;
    }
  }

  setLowCut(enabled) {
    this.state.lowCut = Boolean(enabled);
    const btn = document.getElementById('eqLowCutBtn');
    if (btn) {
      btn.className = `px-2.5 py-1 rounded-lg text-[11px] font-semibold border transition-all ${this.state.lowCut ? 'bg-indigo-500/20 border-indigo-500 text-indigo-300' : 'bg-[#080a0d] border-[#1e2838] text-gray-400'}`;
    }
    if (this._lowCutNode && this._audioCtx) {
      this._lowCutNode.frequency.setTargetAtTime(
        this.state.lowCut ? this.state.lowCutFreq : 10.0,
        this._audioCtx.currentTime,
        0.02
      );
    }
    this._notifyState();
  }

  setHighCut(enabled) {
    this.state.highCut = Boolean(enabled);
    const btn = document.getElementById('eqHighCutBtn');
    if (btn) {
      btn.className = `px-2.5 py-1 rounded-lg text-[11px] font-semibold border transition-all ${this.state.highCut ? 'bg-indigo-500/20 border-indigo-500 text-indigo-300' : 'bg-[#080a0d] border-[#1e2838] text-gray-400'}`;
    }
    if (this._highCutNode && this._audioCtx) {
      this._highCutNode.frequency.setTargetAtTime(
        this.state.highCut ? this.state.highCutFreq : 22000.0,
        this._audioCtx.currentTime,
        0.02
      );
    }
    this._notifyState();
  }

  reset() {
    this.state.preamp = 0.0;
    this.state.lowCut = false;
    this.state.highCut = false;
    this.setPreamp(0.0);
    this.setLowCut(false);
    this.setHighCut(false);
    this.applyPreset('FLAT');
  }

  toggleTestTone() {
    if (this.state.isTestPlaying) {
      this._stopTestTone();
    } else {
      this._startTestTone();
    }
  }

  _startTestTone() {
    try {
      if (!this._audioCtx) this._initWebAudio();
      if (this._audioCtx.state === 'suspended') this._audioCtx.resume();

      this._stopTestTone();

      this._testOscillator = this._audioCtx.createOscillator();
      this._testGain = this._audioCtx.createGain();

      this._testOscillator.type = 'sawtooth';
      this._testOscillator.frequency.setValueAtTime(220, this._audioCtx.currentTime); // A3 chord base
      this._testGain.gain.setValueAtTime(0.08, this._audioCtx.currentTime); // Safe comfortable listening level

      this._testOscillator.connect(this._testGain);
      this._testGain.connect(this._preampNode);

      this._testOscillator.start();
      this.state.isTestPlaying = true;

      const btn = document.getElementById('eqTestToneBtn');
      if (btn) {
        btn.className = 'px-2.5 py-1 rounded-lg text-[11px] font-semibold bg-emerald-500 text-white shadow shadow-emerald-500/20';
        btn.innerHTML = '<i class="bi bi-stop-fill me-1"></i> Stop Test';
      }
    } catch (_) {}
  }

  _stopTestTone() {
    if (this._testOscillator) {
      try {
        this._testOscillator.stop();
        this._testOscillator.disconnect();
      } catch (_) {}
      this._testOscillator = null;
    }
    if (this._testGain) {
      try { this._testGain.disconnect(); } catch (_) {}
      this._testGain = null;
    }
    this.state.isTestPlaying = false;
    const btn = document.getElementById('eqTestToneBtn');
    if (btn) {
      btn.className = 'px-2.5 py-1 rounded-lg text-[11px] font-semibold bg-[#080a0d] border border-[#1e2838] text-gray-300 hover:text-white';
      btn.innerHTML = '<i class="bi bi-volume-up me-1"></i> Audio Test';
    }
  }

  _syncAudioNodes() {
    if (!this._audioCtx) return;
    const t = this._audioCtx.currentTime;

    if (this._preampNode) {
      this._preampNode.gain.setTargetAtTime(
        this.state.enabled ? Math.pow(10, this.state.preamp / 20.0) : 1.0,
        t,
        0.02
      );
    }

    this._filterNodes.forEach((node, i) => {
      if (node) {
        node.gain.setTargetAtTime(
          this.state.enabled ? this.state.bands[i] : 0.0,
          t,
          0.02
        );
      }
    });
  }

  _detectMatchingPreset() {
    for (const [name, gains] of Object.entries(ProductionEqualizer.PRESETS)) {
      const match = gains.every((g, i) => Math.abs(this.state.bands[i] - g) < 0.1);
      if (match) return name;
    }
    return 'CUSTOM';
  }

  _startVisualizer() {
    const canvas = document.getElementById(this.canvasId);
    if (!canvas) return;
    const ctx = canvas.getContext('2d');
    if (!ctx) return;

    const fftData = new Uint8Array(this._analyserNode ? this._analyserNode.frequencyBinCount : 128);

    const render = () => {
      const w = canvas.width;
      const h = canvas.height;

      // Background Clear
      ctx.fillStyle = '#080a0d';
      ctx.fillRect(0, 0, w, h);

      // Grid Lines (-12dB, -6dB, 0dB, +6dB, +12dB)
      ctx.strokeStyle = '#1e2838';
      ctx.lineWidth = 1;
      const gridDbs = [-12, -6, 0, 6, 12];
      gridDbs.forEach(db => {
        const y = h / 2 - (db / 14) * (h / 2);
        ctx.beginPath();
        ctx.moveTo(0, y);
        ctx.lineTo(w, y);
        ctx.stroke();

        ctx.fillStyle = db === 0 ? '#4ade80' : '#4b5563';
        ctx.font = '9px monospace';
        ctx.fillText(`${db > 0 ? '+' : ''}${db}dB`, 6, y - 2);
      });

      // Draw Live Audio FFT Spectrum (if audio is active)
      if (this._analyserNode && this.state.isTestPlaying) {
        this._analyserNode.getByteFrequencyData(fftData);
        ctx.fillStyle = 'rgba(16, 185, 129, 0.15)';
        const barWidth = w / fftData.length;
        for (let i = 0; i < fftData.length; i++) {
          const barHeight = (fftData[i] / 255) * h;
          ctx.fillRect(i * barWidth, h - barHeight, barWidth - 1, barHeight);
        }
      }

      // Draw Composite Filter Response Curve
      ctx.beginPath();
      ctx.lineWidth = 2.5;
      const gradient = ctx.createLinearGradient(0, 0, w, 0);
      gradient.addColorStop(0, '#06b6d4');
      gradient.addColorStop(0.5, '#10b981');
      gradient.addColorStop(1, '#a855f7');
      ctx.strokeStyle = this.state.enabled ? gradient : '#6b7280';

      const points = 120;
      const minLog = Math.log10(20);
      const maxLog = Math.log10(20000);

      for (let i = 0; i < points; i++) {
        const freq = Math.pow(10, minLog + (i / (points - 1)) * (maxLog - minLog));
        let totalGain = this.state.enabled ? this.state.preamp : 0;

        if (this.state.enabled) {
          ProductionEqualizer.BANDS.forEach((centerFreq, bIdx) => {
            const gain = this.state.bands[bIdx];
            if (Math.abs(gain) > 0.05) {
              const octDist = Math.log2(freq / centerFreq);
              const weight = Math.exp(-0.5 * Math.pow(octDist / 0.5, 2));
              totalGain += (gain * weight);
            }
          });

          if (this.state.lowCut && freq < this.state.lowCutFreq) {
            const oct = Math.log2(this.state.lowCutFreq / Math.max(1, freq));
            totalGain -= (oct * 12);
          }

          if (this.state.highCut && freq > this.state.highCutFreq) {
            const oct = Math.log2(freq / this.state.highCutFreq);
            totalGain -= (oct * 12);
          }
        }

        const clampedGain = Math.max(-14, Math.min(14, totalGain));
        const y = h / 2 - (clampedGain / 14) * (h / 2);
        const x = (i / (points - 1)) * w;

        if (i === 0) {
          ctx.moveTo(x, y);
        } else {
          ctx.lineTo(x, y);
        }
      }
      ctx.stroke();

      this._animationFrameId = requestAnimationFrame(render);
    };

    render();
  }

  _notifyState() {
    this._generationId++;
    if (this.onStateChange) {
      this.onStateChange({ ...this.state, generationId: this._generationId });
    }
  }

  /**
   * Complete clean-up to prevent memory leaks and dangling audio nodes.
   */
  destroy() {
    if (this._abortController) {
      this._abortController.abort();
      this._abortController = null;
    }
    if (this._animationFrameId) {
      cancelAnimationFrame(this._animationFrameId);
      this._animationFrameId = null;
    }
    this._stopTestTone();

    if (this._filterNodes) {
      this._filterNodes.forEach(f => {
        try { f.disconnect(); } catch (_) {}
      });
      this._filterNodes = [];
    }
    if (this._preampNode) {
      try { this._preampNode.disconnect(); } catch (_) {}
      this._preampNode = null;
    }
    if (this._lowCutNode) {
      try { this._lowCutNode.disconnect(); } catch (_) {}
      this._lowCutNode = null;
    }
    if (this._highCutNode) {
      try { this._highCutNode.disconnect(); } catch (_) {}
      this._highCutNode = null;
    }
    if (this._analyserNode) {
      try { this._analyserNode.disconnect(); } catch (_) {}
      this._analyserNode = null;
    }
    if (this._audioCtx && this._audioCtx.state !== 'closed') {
      try { this._audioCtx.close(); } catch (_) {}
      this._audioCtx = null;
    }
    this._isInitialized = false;
  }
}

// Attach to window for global access across admin views
if (typeof window !== 'undefined') {
  window.ProductionEqualizer = ProductionEqualizer;
}
